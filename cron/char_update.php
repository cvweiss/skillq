<?php

require_once "../init.php";

use zkillboard\crestsso\CrestSSO;
use cvweiss\Guzzler;

$guzzler = new Guzzler(50, 100);

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
	$scope = $row['scope'];
	$refreshToken = $row['refresh_token'];
	$accessToken = @$row['accessToken'];

	$headers = ['Authorization' =>"Bearer $accessToken", "Content-Type" => "application/json"];
	$params = ['row' => $row];

	switch ($scope) {
		case 'esi-skills.read_skills.v1':
			$url = "https://esi.tech.ccp.is/v4/characters/$charID/skills/";
			$guzzler->call($url, "loadSkills", "fail", $params, $headers);
			break;
		case 'esi-skills.read_skillqueue.v1':
			$url = "https://esi.tech.ccp.is/v2/characters/$charID/skillqueue/";
			$guzzler->call($url, "loadQueue", "fail", $params, $headers);
			break;
		case 'esi-wallet.read_character_wallet':
			$url = "https://esi.tech.ccp.is/v1/characters/$charID/wallet/";
			$guzzler->call($url, "loadWallet", "fail", $params, $headers);
			break;
		case 'publicData':
			$url = "https://esi.tech.ccp.is/v4/characters/$charID/";
			$guzzler->call($url, "loadPublicData", "fail", $params, $headers);
			break;
		default:
			Util::out("Unknown scope: $scope");
	}
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
	Db::execute("update skq_scopes set lastChecked = now() where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $params['row']['scope']]);
	Util::out("Fetch: " . substr("$charID", strlen("$charID") - 6, 6) . " esi-skills.read_skills.v1");
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
				Db::execute("update skq_character_skills set queue = :level where characterID = :charID and typeID = :typeID and :endTime > now() and queue = 0",
						[":charID" => $charID, ":typeID" => $qs['skill_id'], ":level" => $qs['finished_level'], ':endTime' => @$qs['finish_date']]);
			}
		}
	} 
	Db::execute("replace into skq_character_training select characterID, startTime, endTime, typeID, startSP, endSP, level from skq_character_queue where characterID = :charID and endTime > now() order by endTime  limit 1", [':charID' => $charID]);
	$maxQueueTime = Db::queryField("select max(endTime) endTime from skq_character_queue where characterID = :charID", "endTime", [':charID' => $charID]);
	Db::execute("update skq_character_info set queueFinishes = :endTime where characterID = :charID", [":charID" => $charID, ":endTime" => $maxQueueTime]);
	Db::execute("update skq_scopes set lastChecked = now() where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $params['row']['scope']]);
	Util::out("Fetch: " . substr("$charID", strlen("$charID") - 6, 6) . " esi-skills.read_skillqueue.v1");
}

function loadWallet(&$guzzler, &$params, &$content)
{
	clearError($params['row']);
	$wallet = json_decode($content, true);
	$charID = $params['row']['characterID'];
	Db::execute("update skq_character_info set balance = :balance where characterID = :charID", [':charID' => $charID, ':balance' => $wallet]);
	Db::execute("update skq_scopes set lastChecked = now() where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $params['row']['scope']]);
	Util::out("Fetch: " . substr("$charID", strlen("$charID") - 6, 6) . " esi-wallet.read_character_wallet");
}

function loadPublicData(&$guzzler, &$params, &$content)
{
	clearError($params['row']);
	$result = json_decode($content, true);
	$charID = $params['row']['characterID'];
	if (isset($result['name'])) {
		Db::execute("update skq_character_info set characterName = :name, corporationID = :corp, allianceID = :alli where characterID = :charID", [":charID" => $charID, ":name" => $result['name'], ":corp" => (int) @$result['corporation_id'], ':alli' => (int) @$result['alliance_id']]);
		Db::execute("insert ignore into skq_corporations values (:corpID, :corpName, 0)", [':corpID' => (int) @$result['corporation_id'], ":corpName" => "Pending API Fetch"]);
		Db::execute("insert ignore into skq_alliances values (:alliID, :alliName, 0)", [':alliID' => (int) @$result['alliance_id'], ':alliName' => "Pending API Fetch"]);
	}
	Db::execute("update skq_scopes set lastChecked = now() where characterID = :charID and scope = :scope", [':charID' => $charID, ':scope' => $params['row']['scope']]);
	Util::out("Fetch: " . substr("$charID", strlen("$charID") - 6, 6) . " publicData");
}

function clearError($row)
{
	Db::execute("update skq_scopes set errorCount = 0, lastErrorCode = 0 where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope']]);
}

function fail($guzzler, $params, $ex)
{
	$code = $ex->getCode();
echo $code .  $ex->getMessage() . "\n";
	$row = $params['row'];

	Db::execute("update skq_scopes set errorCount = errorCount + 1, lastErrorCode = :code where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope'], ':code' => $code]);

	$json = json_decode($params['content'], true);
	if (@$json['error'] == 'invalid_grant' || @$json['error'] == 'invalid_token') {
		$code = 400;
	}

	switch ($code) {
		case 400:
		case 403:
		case 420:
		case 500:
		case 502:
			// Try again in 5 minutes
			Db::execute("update skq_scopes set lastChecked = 0 where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope'], ':code' => $code]);
			break;
		default:
			Util::out("$code " . $row['characterID'] . " " . $row['scope'] . "\n" . @$params['content']);
	}
}
