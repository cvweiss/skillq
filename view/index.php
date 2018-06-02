<?php

$config      = array();
$pageRefresh = 3600;
$chars       = null;
$showEmailReminder = false;
if (@$_SESSION['character_id'] > 0) {
	global $chars;
	if (sizeof($chars) == 0) {
		return $app->redirect("/logout/");
	}
	Info::addInfo($chars);
	$c = array();
	foreach ($chars as $char) {
		$endTime = Db::queryField("select trainingEndTime from skq_character_training where characterID = :charID", "trainingEndTime", [':charID' => $char['characterID']]);
		$seconds = strtotime($endTime) - time();
		if ($seconds > 0) $pageRefresh = min($pageRefresh, $seconds);
	}
	require_once("view/components/config.php");
	$config = UserConfig::loadUserConfig($_SESSION['character_id']);
	$showEmailReminder = @$config['email'] == "";
	if (@$config['nagger'] == 'nonag') $showEmailReminder = false;
}

$app->render("index.html", ["pageTitle" => "Home", "chars" => $chars, "pageRefresh" => $pageRefresh, "config" => $config, 'showEmailReminder' => $showEmailReminder]);
