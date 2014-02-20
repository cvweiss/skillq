<?php

if (!User::isLoggedIn()) return $app->redirect("/login/");
$userID = User::getUserID();

if ($_POST) {
		@$sendQEmails = isset($_POST["sendQEmails"]) && "" != $_POST["sendQEmails"];	
		@$sendAccEmails = isset($_POST["sendAccEmails"]) && "" != $_POST["sendAccEmails"];
		@$refreshPages = isset($_POST["refreshPages"]) && "" != $_POST["refreshPages"];
		@$email = $_POST["email"];

		if(filter_var($email, FILTER_VALIDATE_EMAIL))
		{
				Db::execute("update skq_users set email = :email where id = :userID", array(":email" => $email, ":userID" => $userID));
		}

		UserConfig::set("sendQEmails", $sendQEmails, true);
		UserConfig::set("sendAccEmails", $sendAccEmails, false);
		UserConfig::set("refreshPages", $refreshPages, true);

		return $app->redirect("/account/");
}

require_once("view/components/config.php");

return $app->render("account.html", array("config" => $config));
