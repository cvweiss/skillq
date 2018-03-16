<?php

if (!User::isLoggedIn()) {
    return $app->redirect("/login/");
}
global $userID;

if ($_POST) {
    @$email = $_POST["email"];
    @$nagger = $_POST['nagger'];

    if ($email == "" || filter_var($email, FILTER_VALIDATE_EMAIL)) {
    	UserConfig::set("email", $email, "");
    }
    UserConfig::set("nagger", $nagger == "" ? "nag" : "nonag", "nag");
    $app->redirect("/account/");
}

$config = UserConfig::loadUserConfig($userID);
$app->render("account.html", ['config' => $config]);
