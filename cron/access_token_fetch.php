<?php

require_once "../init.php";

use zkillboard\crestsso\CrestSSO;
use cvweiss\Guzzler;

$guzzler = new Guzzler(3);

global $clientID, $secretKey, $callbackURL, $scopes;

$accessTokens = [];
$errorTokens = [];

$i = [];
Db::execute("update skq_scopes set lastChecked = 0 where errorCount > 0 and lastErrorCode in (403, 502)");
$minutely = date('Hi');
while ($minutely == date('Hi')) {
	$exclude = implode(",", $i);
	$notIn = sizeof($i) > 0 ? " and characterID not in (" . implode(",", $i) . ") " : "";
	$row = Db::queryRow("select characterID, scope, refresh_token from skq_scopes where lastChecked < date_sub(now(), interval 60 minute) and errorCount < 10 and refresh_token != '' $notIn order by lastChecked limit 1", [], 0);
	if (sizeof($row) != 0) {
		$charID = $row['characterID'];
		$refreshToken = $row['refresh_token'];

		if (in_array($charID, $i)) { echo "breaking on $charID\n"; sleep(1); continue; }
		$i[] = $charID;

		$headers = ['Authorization' =>'Basic ' . base64_encode($clientID . ':' . $secretKey), "Content-Type" => "application/json"];
		$url = 'https://login.eveonline.com/oauth/token';
		$params = ['row' => $row];

		$guzzler->call($url, "accessTokenSuccess", "fail", $params, $headers, 'POST', json_encode(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]));
		Util::out("  SSO: " . substr("$charID", strlen("$charID") - 6, 6));
	} else sleep(1);
	$guzzler->tick();
	while ($redis->llen("skq:esiQueue") > 100) usleep(100000);
}
$guzzler->finish();
while ($redis->llen("skq:esiQueue") > 0) usleep(100000);

function accessTokenSuccess(&$guzzler, &$params, &$content)
{
	global $redis;

	$row = $params['row'];
	$charID = $row['characterID'];
	$refreshToken = $row['refresh_token'];

	$json = json_decode($content, true);
	$accessToken = @$json['access_token'];

	$scopes = Db::query("select * from skq_scopes where characterID = :charID", [':charID' => $charID]);
	foreach ($scopes as $row) {
		$row['accessToken'] = $accessToken;	
		Db::execute("update skq_scopes set errorCount = 0, lastErrorCode = 0 where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope']]);
		$redis->rpush("skq:esiQueue", serialize($row));
	}
}

function fail($guzzler, $params, $ex)
{
	$code = $ex->getCode();
	$row = $params['row'];

	switch ($code) {
		case 0:
		case 502:
			Db::execute("update skq_scopes set lastChecked = 0 where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope'], ':code' => $code]);
			break;
		case 400:
			if (strpos($ex->getMessage(), "invalid_token") !== false) {
				// Delete twice as quick
				Db::execute("update skq_scopes set errorCount = errorCount + 1, lastErrorCode = :code where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope'], ':code' => $code]);
			}
		default:
			Util::out($code . " " . $ex->getMessage(). "\n" . print_r($row, true));
	}

	Db::execute("update skq_scopes set errorCount = errorCount + 1, lastErrorCode = :code where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope'], ':code' => $code]);
}
