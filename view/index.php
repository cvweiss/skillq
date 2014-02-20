<?php

$config = array();
$pageRefresh = 3600;
$chars = null;
if (User::isLoggedIn()) {
	$chars = Db::query("select i.characterID, characterName, trainingTypeID typeID, trainingToLevel, trainingEndTime, balance, i.cachedUntil, queueFinishes, subFlag from skq_api a left join skq_character_info i on (a.keyRowID = i.keyRowID) left join skq_character_training t on (i.characterID = t.characterID) where a.userID = :userID and display = 1 order by skillsTrained desc, skillPoints desc, characterName",
			array(":userID" => User::getUserID()), 1);	
	if (sizeof($chars) == 0) return $app->redirect("/manage/");
	Info::addInfo($chars);
	$c = array();
	foreach($chars as $char) {
		@$seconds = $char["cachedUntilSeconds"];
		if ($seconds > 0) $pageRefresh = min($pageRefresh, $char["cachedUntilSeconds"]);
	}
	require_once("view/components/config.php");
} 


$app->render("index.html", array("pageTitle" => "Home", "chars" => $chars, "pageRefresh" => $pageRefresh, "config" => $config));
