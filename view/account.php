<?php

if (!User::isLoggedIn()) {
    return $app->redirect("/login/");
}
global $userID;

if ($_POST) {
    @$email = $_POST["email"];
    @$nagger = $_POST['nagger'];
    @$theme = $_POST['theme'];
    @$fluid = $_POST['fluid'];

    if ($email == "" || filter_var($email, FILTER_VALIDATE_EMAIL)) {
    	UserConfig::set("email", $email, "");
    }
    UserConfig::set("nagger", $nagger == "" ? "nag" : "nonag", "nag");
    UserConfig::set("theme", $theme == "" ? "default" : $theme);
    UserConfig::set("fluid", $fluid == "yes" ? "-fluid" : "");
    $app->redirect("/account/");
}

$config = UserConfig::loadUserConfig($userID);
$app->render("account.html", ['config' => $config]);
