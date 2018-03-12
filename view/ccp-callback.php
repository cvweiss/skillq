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
foreach ($scopes as $scope) {
	Db::execute("insert ignore into skq_scopes values(:charID, :scope, :refreshToken, 0)", [':charID' => $charID, ':scope' => $scope, ':refreshToken' => $refreshToken]);
}
Db::execute("insert ignore into skq_scopes values(:charID, 'publicData', '', 0)", [':charID' => $charID]);

if (!isset($_SESSION['character_id']) || $_SESSION['character_id'] == "")  $_SESSION['character_id'] = $charID;
if ($_SESSION['character_id'] > "" && $charID != $_SESSION['character_id']) {
	Db::execute("insert ignore into skq_character_associations values (:char1, :char2)", [':char1' => $_SESSION['character_id'], ':char2' => $charID]);
}

Db::execute("insert ignore into skq_character_info (characterID, characterName) values (:charID, :charName)", [':charID' => $charID, ':charName' => $userInfo['characterName']]);

return $app->redirect("/char/" . $userInfo['characterName'] . "/");

/*
@$username = $_POST["username"];
@$password = $_POST["password"];
@$autologin = 1;
@$requesturi = $_POST["requesturi"];

$error = "An undefined error has occured....";
if (!$username) {
	$error = "No username given";
} elseif (!$password) {
	$error = "No password given";
} elseif ($username && $password) {
	$check = User::checkLogin($username, $password);
	if ($check > 0) // Success
	{
		$message = User::setLogin($username, $password, $autologin);
		$app->view(new \Slim\Extras\Views\Twig());
		$twig = $app->view()->getEnvironment();
		$u    = User::getUserInfo();
		$twig->addGlobal("sessionusername", $u["username"]);
		$twig->addGlobal("sessionuserid", $u["id"]);
		$twig->addGlobal("sessionadmin", $u["admin"]);
		$twig->addGlobal("sessionmoderator", (bool) $u["moderator"]);
		$ignoreUris = array("/register/", "/login/", "/logout/");

		return $app->redirect("/");
	} else {
		$error = "That username and password combination does not exist.  Please insert another quarter and try again.";
	}
}

return $app->render("login.html", array("message" => $error, "type" => "error"));

if (User::isLoggedIn()) {
	return $app->redirect("/");
}

$app->render("login.html");
*/
