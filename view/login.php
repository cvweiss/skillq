<?php
if($_POST)
{

	@$username = $_POST["username"];
	@$password = $_POST["password"];
	@$autologin = isset($_POST["autologin"]) ? 1 : 0;
	@$requesturi = $_POST["requesturi"];

	if(!$username)
	{
		$error = "No username given";
	}
	elseif(!$password)
	{
		$error = "No password given";
	}   
	elseif($username && $password)
	{
		$check = User::checkLogin($username, $password);
		if($check > 0) // Success
		{
			$message = User::setLogin($username, $password, $autologin);
			$app->view(new \Slim\Extras\Views\Twig());
			$twig = $app->view()->getEnvironment();
			$u = User::getUserInfo();
			$twig->addGlobal("sessionusername", $u["username"]);
			$twig->addGlobal("sessionuserid", $u["id"]);
			$twig->addGlobal("sessionadmin", $u["admin"]);
			$twig->addGlobal("sessionmoderator", (bool) $u["moderator"]);
			$ignoreUris = array("/register/", "/login/", "/logout/");
			return $app->redirect("/");
		}
		else
		{
			$error = "No such user exists, try again";
		}
	}
	return $app->render("login.html", array("message" => $error, "type" => "error"));
}

if (User::isLoggedIn()) return $app->redirect("/");

$app->render("login.html");
