<?php

require_once "../init.php";

use zkillboard\crestsso\CrestSSO;
use cvweiss\Guzzler;

$guzzler = new Guzzler();

global $clientID, $secretKey, $callbackURL, $scopes;

$accessTokens = [];
$errorTokens = [];

$minutely = date('Hi');
while ($minutely == date('Hi')) {
	$result = Db::query("select * from skq_scopes where lastChecked < date_sub(now(), interval 58 minute) order by lastChecked", [], 0);
	foreach ($result as $row) {
		$charID = $row['characterID'];
		$refreshToken = $row['refresh_token'];
		$headers = ['Authorization' =>'Basic ' . base64_encode($clientID . ':' . $secretKey), "Content-Type" => "application/json"];
		$url = 'https://login.eveonline.com/oauth/token';
		$params = ['row' => $row];

		$scope = $row['scope'];

		$accessToken = $redis->get("at:$charID:$refreshToken");
		$params['store'] = false;
		if ($accessToken == null && $row['scope'] != 'publicData') {
			//echo "fetching $charID\n";
			$redis->setex("at:$charID:$refreshToken", 60, "pending");
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

	$redis->setex("at:$charID:$refreshToken", 1600, $accessToken);
}

function fail($guzzler, $params, $ex)
{
        $code = $ex->getCode();
        $row = $params['row'];
        echo "access token fetch: $code " . $row['characterID'] . " " . $row['scope'] . "\n" . $params['content'] . "\n";
}
