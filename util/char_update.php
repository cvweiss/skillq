<?php

require_once "../init.php";

use zkillboard\crestsso\CrestSSO;
global $clientID, $secretKey, $callbackURL, $scopes;

$guzzler = new Guzzler(10);
$accessTokens = [];

$sso = new CrestSSO($clientID, $secretKey, $callbackURL, $scopes);

$result = Db::query("select * from scopes where lastChecked < date_sub(now(), interval 1 hour)");
foreach ($result as $row) {
	$charID = $row['character_id'];
	$scope = $row['scope'];
	$params = ['row' => $row];
	$refreshToken = $row['refresh_token'];
	$fields = [];

	$accessToken = isset($accessTokens[$refreshToken]) ? $accessTokens[$refreshToken] : $sso->getAccessToken($refreshToken);
	$accessTokens[$refreshToken] = $accessToken;

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
			
		default:
			echo("Unknown scope: $scope\n");
	}
	Db::execute("update scopes set lastChecked = now() where character_id = :charID and scope = :scope", [':charID' => $charID, ':scope' => $scope]);
}

function loadSkills($charID, $skills)
{
	foreach ($skills['skills'] as $skill) {
		Db::execute("insert ignore into skq_character_skills (characterID, typeID) values (:charID, :typeID)", [':charID' => $charID, ':typeID' => $skill['skill_id']]);
		Db::execute("update skq_character_skills set level = :level, skillPoints = :points where characterID = :charID and typeID = :typeID", [':charID' => $charID, ':typeID' => $skill['skill_id'], ':level' => $skill['trained_skill_level'], ':points' => $skill['skillpoints_in_skill']]);
	}
	Db::execute("update skq_character_info set skillsTrained = :count, skillPoints = :sp where characterID = :charID", [':charID' => $charID, ':count' => count($skills['skills']), ':sp'=> $skills['total_sp']]);
}

function loadQueue($charID, $queue)
{
	$first = true;
	Db::execute("delete from skq_character_queue where characterID = :charID", [":charID" => $charID]);
	Db::execute("update skq_character_skills set queue = 0 where characterID = :charID", [":charID" => $charID]);
	Db::execute("delete from skq_character_training where characterID = :charID", [':charID' => $charID]);
	foreach ($queue as $qs) {
		if ($first) {
			if (isset($qs['start_date'])) Db::execute("replace into skq_character_training values (:charID, :tst, :tet, :tti, :tss, :tds, :ttl)", [':charID' => $charID, ':tst' => $qs['start_date'], ':tet' => @$qs['finish_date'], ':tti' => $qs['skill_id'], ':tss' => $qs['level_start_sp'], ':tds' => $qs['level_end_sp'], ':ttl' => $qs['finished_level']]);
			$first = false;
		}

		Db::execute("insert into skq_character_queue (characterID, queuePosition, typeID, level, startSP, endSP, startTime, endTime) values
				(:charID, :qp, :typeID, :level, :startSP, :endSP, :startTime, :endTime)",
				array(
					":charID"    => $charID,
					":qp"        => $qs['queue_position'],
					":typeID"    => $qs['skill_id'],
					":level"     => $qs['finished_level'],
					":startSP"   => $qs['level_start_sp'],
					":endSP"     => $qs['level_end_sp'],
					":startTime" => @$qs['start_date'],
					":endTime"   => @$qs['finish_date']
				     )
			   );
		Db::execute("update skq_character_skills set queue = :level where characterID = :charID and typeID = :typeID",
				[":charID" => $charID, ":typeID" => $qs['skill_id'], ":level" => $qs['finished_level']]);
	}
}

function loadWallet($charID, $wallet)
{
	Db::execute("update skq_character_info set balance = :balance where characterID = :charID", [':charID' => $charID, ':balance' => $wallet]);
}
