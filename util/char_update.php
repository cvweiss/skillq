<?php

require_once "../init.php";

use zkillboard\crestsso\CrestSSO;
global $clientID, $secretKey, $callbackURL, $scopes;

$accessTokens = [];
$errorTokens = [];

$sso = new CrestSSO($clientID, $secretKey, $callbackURL, $scopes);

$minutely = date('Hi');
while ($minutely == date('Hi')) {
	$result = Db::query("select * from skq_scopes where lastChecked < date_sub(now(), interval 1 hour) order by characterID, scope");
	if (sizeof($result) == 0) sleep(1);
	foreach ($result as $row) {
		$charID = $row['characterID'];
		$scope = $row['scope'];
		$params = ['row' => $row];
		$refreshToken = $row['refresh_token'];
		$fields = [];

		$accessToken = null;
		if ($row['refresh_token'] != '') {
			$accessToken = isset($accessTokens[$refreshToken]) ? $accessTokens[$refreshToken] : $sso->getAccessToken($refreshToken);
			if (isset($accessToken['error'])) {
				if ($accessToken['error'] == 'invalid_token') {
					Db::execute("delete from skq_scopes where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $scope]); 
				} else {
					Db::execute("update skq_scopes set lastChecked = now() where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $scope]);
					echo "$charID $scope\n";
					print_r($accessToken);
				}
				continue;
			}
			$accessTokens[$refreshToken] = $accessToken;
		}
		if ($scope == 'publicData' || ($accessToken != null && !isset($accessToken['error']))) {
			switch ($scope) {
				case 'esi-skills.read_skills.v1':
					$url = "https://esi.tech.ccp.is/v4/characters/$charID/skills/";
					$result = $sso->doCall($url, $fields, $accessToken);
					loadSkills($charID, json_decode($result, true));
					break;
				case 'esi-skills.read_skillqueue.v1':
					$url = "https://esi.tech.ccp.is/v2/characters/$charID/skillqueue/";
					$result = $sso->doCall($url, $fields, $accessToken);
					loadQueue($charID, json_decode($result, true));
					break;
				case 'esi-wallet.read_character_wallet':
					$url = "https://esi.tech.ccp.is/v1/characters/$charID/wallet/";
					$result = $sso->doCall($url, $fields, $accessToken);
					loadWallet($charID, json_decode($result, true));
					break;
				case 'publicData':
					$url = "https://esi.tech.ccp.is/v4/characters/$charID/";
					$result = $sso->doCall($url, $fields, $accessToken);
					loadPublicData($charID, json_decode($result, true));
					break;
				default:
					echo("Unknown scope: $scope\n");
			}
		} else {
			echo "Failure with $charID $scope $refreshToken\n";
		}
		Db::execute("update skq_scopes set lastChecked = now() where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $scope]);
	}
}

function loadSkills($charID, $skills)
{
	if (isset($skills['skills'])) {
		foreach ($skills['skills'] as $skill) {
			Db::execute("insert ignore into skq_character_skills (characterID, typeID) values (:charID, :typeID)", [':charID' => $charID, ':typeID' => $skill['skill_id']]);
			Db::execute("update skq_character_skills set level = :level, skillPoints = :points where characterID = :charID and typeID = :typeID", [':charID' => $charID, ':typeID' => $skill['skill_id'], ':level' => $skill['trained_skill_level'], ':points' => $skill['skillpoints_in_skill']]);
		}
		Db::execute("update skq_character_info set skillsTrained = :count, skillPoints = :sp where characterID = :charID", [':charID' => $charID, ':count' => count($skills['skills']), ':sp'=> $skills['total_sp']]);
	}
}

function loadQueue($charID, $queue)
{
	if (sizeof($queue) > 0) {
		$firstV = array_shift(array_slice($queue, 0, 1)); 
		if (isset($firstV['level_start_sp'])) {
			Db::execute("delete from skq_character_queue where characterID = :charID", [":charID" => $charID]);
			Db::execute("update skq_character_skills set queue = 0 where characterID = :charID", [":charID" => $charID]);
			Db::execute("delete from skq_character_training where characterID = :charID", [':charID' => $charID]);
			foreach ($queue as $qs) {
				Db::execute("insert ignore into skq_character_queue (characterID, queuePosition, typeID, level, startSP, endSP, startTime, endTime) values
						(:charID, :qp, :typeID, :level, :startSP, :endSP, :startTime, :endTime)",
						array(
							":charID"    => $charID,
							":qp"        => $qs['queue_position'],
							":typeID"    => $qs['skill_id'],
							":level"     => $qs['finished_level'],
							":startSP"   => $qs["training_start_sp"],
							":endSP"     => $qs['level_end_sp'],
							":startTime" => @$qs['start_date'],
							":endTime"   => @$qs['finish_date']
						     )
					   );
				Db::execute("update skq_character_skills set queue = :level where characterID = :charID and typeID = :typeID",
						[":charID" => $charID, ":typeID" => $qs['skill_id'], ":level" => $qs['finished_level']]);
			}
		}
	} 
	Db::execute("replace into skq_character_training select characterID, startTime, endTime, typeID, startSP, endSP, level from skq_character_queue where characterID = :charID and endTime > now() order by endTime  limit 1", [':charID' => $charID]);
}

function loadWallet($charID, $wallet)
{
	Db::execute("update skq_character_info set balance = :balance where characterID = :charID", [':charID' => $charID, ':balance' => $wallet]);
}

function loadPublicData($charID, $result)
{
	if (isset($result['name'])) {
		Db::execute("update skq_character_info set dob = :dob, characterName = :name, bloodline = :bld, ancestry = :race, corporationID = :corp, allianceID = :alli where characterID = :charID", [":charID" => $charID, ":dob" => $result['birthday'], ":name" => $result['name'], ":bld" => $result['bloodline_id'], ":race" => $result['race_id'], ":corp" => (int) @$result['corporation_id'], ':alli' => (int) @$result['alliance_id']]);
		Db::execute("insert ignore into skq_corporations values (:corpID, :corpName, 0)", [':corpID' => (int) @$result['corporation_id'], ":corpName" => "Pending API Fetch"]);
		Db::execute("insert ignore into skq_alliances values (:alliID, :alliName, 0)", [':alliID' => (int) @$result['alliance_id'], ':alliName' => "Pending API Fetch"]);
	}
}
