<?php

require_once "../init.php";

use zkillboard\crestsso\CrestSSO;
use cvweiss\Guzzler;

$guzzler = new Guzzler(25, 100);

global $clientID, $secretKey, $callbackURL, $scopes;

$errorTokens = [];

$minutely = date('Hi');
while ($minutely == date('Hi')) {
	$row = unserialize($redis->lpop("skq:esiQueue"));
	if ($row == null) {
		$guzzler->tick();
		sleep(1);
		continue;
	}

	$charID = $row['characterID'];
	$refreshToken = $row['refresh_token'];
	$headers = ['Authorization' =>'Basic ' . base64_encode($clientID . ':' . $secretKey), "Content-Type" => "application/json"];
	$url = 'https://login.eveonline.com/oauth/token';
	$params = ['row' => $row];

	$scope = $row['scope'];
	Db::execute("update skq_scopes set lastChecked = now() where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $scope]);

	$accessToken = @$row['accessToken'];

	$row = $params['row'];
	$charID = $row['characterID'];
	$refreshToken = $row['refresh_token'];
	$scope = $row['scope'];
	$fields = [];

	$accessToken = @$row['accessToken'];
	$headers = ['Content-Type: application/json'];

	$fields = "?token=" . rawurlencode($accessToken);

	$url = "";
	switch ($scope) {
		case 'esi-skills.read_skills.v1':
			$url = "https://esi.tech.ccp.is/v4/characters/$charID/skills/$fields";
			$guzzler->call($url, "loadSkills", "fail", $params, $headers);
			break;
		case 'esi-skills.read_skillqueue.v1':
			$url = "https://esi.tech.ccp.is/v2/characters/$charID/skillqueue/$fields";
			$guzzler->call($url, "loadQueue", "fail", $params, $headers);
			break;
		case 'esi-wallet.read_character_wallet':
			$url = "https://esi.tech.ccp.is/v1/characters/$charID/wallet/$fields";
			$guzzler->call($url, "loadWallet", "fail", $params, $headers);
			break;
		case 'publicData':
			$url = "https://esi.tech.ccp.is/v4/characters/$charID/";
			$guzzler->call($url, "loadPublicData", "fail", $params, $headers);
			break;
		default:
			Util::out("Unknown scope: $scope");
	}

	Db::execute("update skq_scopes set lastChecked = now() where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $scope]);
	Db::execute("update skq_character_info set lastChecked = now() where characterID = :charID", [':charID' => $charID]);
}
$guzzler->finish();

function loadSkills(&$guzzler, &$params, &$content)
{
	clearError($params['row']);
	$skills = json_decode($content, true);
	$charID = $params['row']['characterID'];
	if (isset($skills['skills'])) {
		foreach ($skills['skills'] as $skill) {
			Db::execute("insert ignore into skq_character_skills (characterID, typeID) values (:charID, :typeID)", [':charID' => $charID, ':typeID' => $skill['skill_id']]);
			Db::execute("update skq_character_skills set level = :level, skillPoints = :points where characterID = :charID and typeID = :typeID", [':charID' => $charID, ':typeID' => $skill['skill_id'], ':level' => $skill['trained_skill_level'], ':points' => $skill['skillpoints_in_skill']]);
		}
		Db::execute("update skq_character_info set skillsTrained = :count, skillPoints = :sp where characterID = :charID", [':charID' => $charID, ':count' => count($skills['skills']), ':sp'=> $skills['total_sp']]);
	}
	if (isset($skills['unallocated_sp'])) {
		Db::execute("update skq_character_info set unallocated_sp = :usp where characterID = :charID", [':charID' => $charID, ':usp' => $skills['unallocated_sp']]);
	}
}

function loadQueue(&$guzzler, &$params, &$content) 
{
	clearError($params['row']);
	$queue = json_decode($content, true);
	$charID = $params['row']['characterID'];
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
							":startSP"   => $qs['training_start_sp'],
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
	$maxQueueTime = Db::queryField("select max(endTime) endTime from skq_character_queue where characterID = :charID", "endTime", [':charID' => $charID]);
	Db::execute("update skq_character_info set queueFinishes = :endTime where characterID = :charID", [":charID" => $charID, ":endTime" => $maxQueueTime]);
}

function loadWallet(&$guzzler, &$params, &$content)
{
	clearError($params['row']);
	$wallet = json_decode($content, true);
	$charID = $params['row']['characterID'];
	Db::execute("update skq_character_info set balance = :balance where characterID = :charID", [':charID' => $charID, ':balance' => $wallet]);
}

function loadPublicData(&$guzzler, &$params, &$content)
{
	clearError($params['row']);
	$result = json_decode($content, true);
	$charID = $params['row']['characterID'];
	if (isset($result['name'])) {
		Db::execute("update skq_character_info set dob = :dob, characterName = :name, bloodline = :bld, ancestry = :race, corporationID = :corp, allianceID = :alli where characterID = :charID", [":charID" => $charID, ":dob" => $result['birthday'], ":name" => $result['name'], ":bld" => $result['bloodline_id'], ":race" => $result['race_id'], ":corp" => (int) @$result['corporation_id'], ':alli' => (int) @$result['alliance_id']]);
		Db::execute("insert ignore into skq_corporations values (:corpID, :corpName, 0)", [':corpID' => (int) @$result['corporation_id'], ":corpName" => "Pending API Fetch"]);
		Db::execute("insert ignore into skq_alliances values (:alliID, :alliName, 0)", [':alliID' => (int) @$result['alliance_id'], ':alliName' => "Pending API Fetch"]);
	}
}

function getAccessToken(&$guzzler, $refreshToken, $success, $fail, &$params, $clientID, $clientSecret)
{  
	$headers = ['Authorization' =>'Basic ' . base64_encode($ccpClientID . ':' . $ccpSecret), "Content-Type" => "application/json"];
	$url = 'https://login.eveonline.com/oauth/token';
	$guzzler->call($url, $success, $fail, $params, $headers, 'POST', json_encode(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]));
}

function clearError($row)
{
	Db::execute("update skq_scopes set errorCount = 0, lastErrorCode = 0 where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope']]);
}

function fail($guzzler, $params, $ex)
{
	$code = $ex->getCode();
	$row = $params['row'];

	Db::execute("update skq_scopes set errorCount = errorCount + 1, lastErrorCode = :code where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope'], ':code' => $code]);

	$json = json_decode($params['content'], true);
	if (@$json['error'] == 'invalid_grant' || @$json['error'] == 'invalid_token') {
		$code = 400;
	}

	switch ($code) {
		case 403:
		case 420:
		case 502:
			// Try again in 5 minutes
			Db::execute("update skq_scopes set lastChecked = date_sub(lastChecked, interval 55 minute) where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope'], ':code' => $code]);
			break;
		case 400:
		case 500:
			// Ignore and try again later
			break;
		default:
			Util::out("$code " . $row['characterID'] . " " . $row['scope'] . "\n" . @$params['content']);
	}
}
