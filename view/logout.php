<?php

if(isset($_SERVER["HTTP_REFERER"])) $requesturi = $_SERVER["HTTP_REFERER"];

unset($_SESSION["loggedin"]);
$twig = $app->view()->getEnvironment();
$twig->addGlobal("sessionusername", "");
$twig->addGlobal("sessionuserid", "");
$twig->addGlobal("sessionadmin", "");
$twig->addGlobal("sessionmoderator", "");
setcookie($cookie_name, "", time()-$cookie_time, "/");

$app->render("logout.html", array("message" => "You are now logged out"));
