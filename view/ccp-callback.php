<?php

use zkillboard\crestsso\CrestSSO;

global $clientID, $secretKey, $callbackURL, $scopes;

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

$sso = new CrestSSO($clientID, $secretKey, $callbackURL, $scopes);
$code = $p['code']; //filter_input(INPUT_GET, 'code');
$state = $p['state']; //$app->request()->get('state');
$userInfo = $sso->handleCallback($code, $state, $_SESSION);
$charID = $userInfo['characterID'];
$refreshToken = $userInfo['refreshToken'];
$scopes = explode(' ', $userInfo['scopes']);
Db::execute("delete from skq_scopes where characterID = :charID", [':charID' => $charID]);
foreach ($scopes as $scope) {
	Db::execute("insert ignore into skq_scopes values(:charID, :scope, :refreshToken, now(), 0, 0)", [':charID' => $charID, ':scope' => $scope, ':refreshToken' => $refreshToken]);
}
Db::execute("insert ignore into skq_scopes values(:charID, 'publicData', '', now(), 0, 0)", [':charID' => $charID]);
Db::execute("update skq_scopes set lastChecked = 0 where characterID = :charID", [':charID' => $charID]);

if (!isset($_SESSION['character_id']) || $_SESSION['character_id'] == "")  $_SESSION['character_id'] = $charID;
if ($_SESSION['character_id'] > "" && $charID != $_SESSION['character_id']) {
	Db::execute("insert ignore into skq_character_associations values (:char1, :char2)", [':char1' => $_SESSION['character_id'], ':char2' => $charID]);
}

Db::execute("insert ignore into skq_character_info (characterID, characterName) values (:charID, :charName)", [':charID' => $charID, ':charName' => $userInfo['characterName']]);

return $app->redirect("/char/" . $userInfo['characterName'] . "/");
