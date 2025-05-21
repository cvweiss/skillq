<?php

use zkillboard\eveonlineoauth2\EveOnlineSSO;

global $clientID, $secretKey, $callbackURL, $scopes, $redis;

// The fact I have to do this right now is annoying as fuck
$uri = $_SERVER['REQUEST_URI'];
$s = explode('?', $uri);
$query_params = $s[1];
$params = explode('&', $query_params);
$p = [];
foreach ($params as $param) {
	$kv = explode('=', $param);
	$p[$kv[0]] = $kv[1];
}

global $clientID, $secretKey, $callbackURL, $scopes;

$sso = new EveOnlineSSO($clientID, $secretKey, $callbackURL, $scopes);
$code = $p['code']; //filter_input(INPUT_GET, 'code');
$state = $p['state']; //$app->request()->get('state');
$userInfo = $sso->handleCallback($code, $state, $_SESSION);
$charID = $userInfo['characterID'];
$refreshToken = $userInfo['refreshToken'];
$scopes = explode(' ', $userInfo['scopes']);
Db::execute("delete from skq_scopes where characterID = :charID", [':charID' => $charID]);
foreach ($scopes as $scope) {
	Db::execute("insert ignore into skq_scopes (characterID, scope, refresh_token) values(:charID, :scope, :refreshToken)", [':charID' => $charID, ':scope' => $scope, ':refreshToken' => $refreshToken]);
}
// make sure any other records have the same refresh token
Db::execute("update skq_scopes set refresh_token = :refreshToken, lastSsoChecked = 0 where characterID = :charID", [':charID' => $charID, ':refreshToken' => $refreshToken]);

$keys = $redis->keys("guzzler:etags:*$charID*");
foreach ($keys as $key) $redis->del($key);
Db::execute("insert ignore into skq_scopes (characterID, scope, refresh_token) values(:charID, 'publicData', '')", [':charID' => $charID]);
Db::execute("update skq_scopes set lastChecked = 0 where characterID = :charID", [':charID' => $charID]);

if (!isset($_SESSION['character_id']) || $_SESSION['character_id'] == "")  $_SESSION['character_id'] = $charID;
Db::execute("delete from skq_character_associations where char2 = :charID", [':charID' => $charID]);
if ($_SESSION['character_id'] > "" && $charID != $_SESSION['character_id']) {
	Db::execute("insert ignore into skq_character_associations values (:char1, :char2)", [':char1' => $_SESSION['character_id'], ':char2' => $charID]);
}

Db::execute("insert ignore into skq_character_info (characterID, characterName) values (:charID, :charName)", [':charID' => $charID, ':charName' => $userInfo['characterName']]);

return $app->redirect("/char/" . $userInfo['characterName'] . "/");
