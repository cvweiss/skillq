<?php

require_once "../init.php";

use zkillboard\crestsso\CrestSSO;
use cvweiss\Guzzler;

$guzzler = new Guzzler(3);

global $clientID, $secretKey, $callbackURL, $scopes;

$accessTokens = [];
$errorTokens = [];

Db::execute("update skq_scopes set lastChecked = 0 where errorCount > 0 and lastErrorCode in (403, 502)");
$minutely = date('Hi');
while ($minutely == date('Hi')) {
	$result = Db::query("select characterID, scope, refresh_token from skq_scopes where lastChecked < date_sub(now(), interval 60 minute) and errorCount < 10 order by lastChecked", [], 0);
	foreach ($result as $row) {
		if ($minutely != date('Hi')) break;
		$charID = $row['characterID'];
		$scope = $row['scope'];
		$refreshToken = $row['refresh_token'];

		$key = "skq:$charID:$refreshToken";

		$curValue = $redis->get($key);
		if ($curValue != false) {
			continue;
		}
		$redis->setex($key, 120, "pending");

		$headers = ['Authorization' =>'Basic ' . base64_encode($clientID . ':' . $secretKey), "Content-Type" => "application/json"];
		$url = 'https://login.eveonline.com/oauth/token';
		$params = ['row' => $row];

		$scope = $row['scope'];

		$accessToken = $redis->get("at:$charID:$refreshToken");
		$params['store'] = false;
		if ($row['scope'] == 'publicData') $redis->rpush("skq:esiQueue", serialize($row));
		else {
			$guzzler->call($url, "accessTokenSuccess", "fail", $params, $headers, 'POST', json_encode(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]));
		}
	}
	$guzzler->tick();
	sleep(1);
}
$guzzler->finish();

function accessTokenSuccess(&$guzzler, &$params, &$content)
{
	global $redis;

	$row = $params['row'];
	$charID = $row['characterID'];
	$refreshToken = $row['refresh_token'];

	$json = json_decode($content, true);
	$accessToken = @$json['access_token'];

	$scopes = Db::query("select * from skq_scopes where characterID = :charID and refresh_token = :rt", [':charID' => $charID, ':rt' => $refreshToken]);
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

	Db::execute("update skq_scopes set errorCount = errorCount + 1, lastErrorCode = :code where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope'], ':code' => $code]);

	$json = json_decode($params['content'], true);
	if (@$json['error'] == 'invalid_grant' || @$json['error'] == 'invalid_token') {
		//Db::execute("delete from skq_scopes where characterID = :charID and scope = :scope", [':charID' => $row['characterID'], ':scope' => $row['scope']]);
		return;
	}
	//echo "access token fetch: $code " . $row['characterID'] . " " . $row['scope'] . "\n" . $params['content'] . "\n";
	//print_r($guzzler->getLastHeaders());
}
