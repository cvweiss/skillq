<?php 
$userID = User::getUserID();
$userInfo = User::getUserInfo($userID);

$config = array();
$config["info"] = $userInfo;
//$config["sendQEmails"] = UserConfig::get("sendQEmails", true);
//$config["sendAccEmails"] = UserConfig::get("sendAccEmails", false);
//$config["refreshPages"] = UserConfig::get("refreshPages", true);

