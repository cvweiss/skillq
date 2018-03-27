<?php

$config = array();
require_once("view/components/config.php");

$isShare  = false;
$charInfo = false;
if (isset($bypassLogin)) {
	$charInfo = Db::queryRow("select * from skq_character_info i where characterName = :name", [":name" => $name], 1);
	$isShare  = true;
} else if (isset($_SESSION['character_id']) && $_SESSION['character_id'] > 0) {
	$bypassLogin = false;

	$pageCharID = (int) Db::queryField("select characterID from skq_character_info where characterName = :name", "characterID", [':name' => $name]);
	global $validChars;
	$count = in_array($pageCharID, $validChars);
	$charInfo = $count == 0 ? null : Db::queryRow("select i.* from skq_character_info i where characterID = :charID", [':charID' => $pageCharID]);
	$isShare  = false;
}

if (!$charInfo && $isShare) {
	return $app->render("404.html", ['message' => "Invalid share - did it expire?", "type" => "error"], 404);
}
if (!$charInfo) {
	return $app->render("404.html", ['message' => "Not your character to view...", "type" => "error"], 404);
}

if (!isset($pageType)) {
	$pageType = "overview";
} else {
	$pageType = strtolower($pageType);
}


$charID   = $charInfo["characterID"];
$training = Db::queryRow("select * from skq_character_training where characterID = :charID and trainingEndTime > now()", [":charID" => $charID], 1);
if ($training == null or sizeof($training) == 0) {
	Db::queryRow("select * from skq_character_queue where characterID = :charID and endTime > now() order by queuePosition limit 1", [":charID" => $charID], 1);
}
$skills = Db::query("select s.typeID, i.typeName, s.level, s.training, s.queue, g.groupID, g.groupName, s.skillPoints from skq_character_skills s left join ccp_invTypes i on (s.typeID = i.typeID) left join ccp_invGroups g on (i.groupID = g.groupID) where characterID = :charID order by g.groupName, i.typeName", [":charID" => $charID], 1);
$queue  = Db::query("select typeID, level, startTime, endTime, startSP, endSP from skq_character_queue where characterID = :charID and endTime >= now() order by queuePosition", [":charID" => $charID], 1);
$wallet = $isShare ? array() : Db::query("select * from skq_character_wallet where characterID = :charID order by dttm desc", array(":charID" => $charID), 1);
$headAttr = Db::query("select * from skq_character_implants where characterID = :charID order by attributeID", array(":charID" => $charID), 0);

$skillTrain = array();
if ($pageType == "train") {
	$implants   = Db::query("select * from skq_character_implants where characterID = :charID order by (baseValue + bonus) desc, baseValue desc, bonus desc, attributeID", [":charID" => $charID], 1);
	$attributes = array();
	foreach ($implants as $implant) {
		$attributeValue                        = $implant["baseValue"] + $implant["bonus"];
		$attributes[$implant["attributeName"]] = $attributeValue;
	}
	$allSkills = Db::query("select * from skq_skill_attributes");
	foreach ($allSkills as $skill) {
		$typeID = $skill["typeID"];
		if (!isset($attributes[$skill["primaryAttribute"]]) || !isset($attributes[$skill["secondaryAttribute"]])) {
			continue;
		}
		$primaryValue            = $attributes[$skill["primaryAttribute"]];
		$secondaryValue          = $attributes[$skill["secondaryAttribute"]];
		$currentTrained          = Db::queryRow(
				"select * from skq_character_skills where characterID = :charID and typeID = :typeID",
				array(":charID" => $charID, ":typeID" => $typeID),
				1
				);
		$skill["training"]       = $currentTrained == null ? 0 : $currentTrained["training"];
		$skill["level"]          = $currentTrained == null ? 0 : $currentTrained["level"];
		$skill["skillPoints"]    = $currentTrained == null ? 0 : $currentTrained["skillPoints"];
		$skill["queue"]          = $currentTrained == null ? 0 : $currentTrained["queue"];
		$skill["primaryValue"]   = $primaryValue;
		$skill["secondaryValue"] = $secondaryValue;
		$skillTrain[]            = $skill;
	}
	$preReqs     = array();
	$timedSkills = array();
	$sqrt2x4     = 4 * sqrt(2);
	$iterations  = 0;
	do {
		$processed = 0;
		foreach ($skillTrain as $skill) {
			$typeID = $skill["typeID"];
			if (isset($preReqs[$typeID])) {
				continue;
			}
			$processed++;
			$typeName          = Info::getItemName($typeID);
			$skill["typeName"] = $typeName;
			$totalSP           = 256000 * $skill["timeMultiplier"];
			$spDiff            = $totalSP - $skill["skillPoints"];
			$spPerHour         = 60 * $skill["primaryValue"] + 30 * $skill["secondaryValue"];
			$time              = ceil($spDiff / $spPerHour * 3600);
			if (!isset($timedSkills[$time])) {
				$timedSkills[$time] = array();
			}
			$preReqs[$typeID] = $skill;
			$skill["time"]    = $time;
			if ($time == 0) {
				continue;
			}
			$timedSkills[$time][] = $skill;
		}
	} while ($processed != 0 && ++$iterations <= 10);
	ksort($timedSkills);
	$skillTrain = array();
	foreach ($timedSkills as $time => $skills) {
		$timer              = secondsToTime($time);
		$skillTrain[$timer] = $skills;
	}
}

Info::addInfo($charInfo);
$charInfo["nameEncoded"] = urlencode($charInfo["characterName"]);
$training["typeID"]      = isset($training["trainingTypeID"]) ? $training["trainingTypeID"] : 0;
Info::addInfo($training);
Info::addInfo($skills);
Info::addInfo($queue);
Info::addInfo($wallet);
$maxSeconds = 0;
foreach ($queue as $skill) {
	$maxSeconds = max($maxSeconds, $skill["endTimeSeconds"]);
}

$message = "";
$pageRefresh = 3600;
$lastChecked = Db::queryField("select unix_timestamp(min(lastChecked)) lastChecked from skq_scopes where characterID = :charID", "lastChecked", [':charID' => $charID], 0);
$pageRefresh = $lastChecked == 0 ? 1 : 3600 - (time() - $lastChecked);
$pageRefresh = max(1, min(3600, $pageRefresh));
if ($pageRefresh <= 60) {
	$message = "API update imminent, page will automatically reload in... <span id='pageRefresher'></span>";
}

$totalSP = 0;
$groupSP = array();
foreach ($skills as $skill) {
	$groupID = $skill["groupID"];
	if (!isset($groupSP["$groupID"])) {
		$groupSP["$groupID"]          = array();
		$groupSP["$groupID"]["sp"]    = 0;
		$groupSP["$groupID"]["count"] = 0;
	}
	$groupSP["$groupID"]["sp"] += $skill["skillPoints"];
	$groupSP["$groupID"]["count"]++;
	$totalSP += $skill["skillPoints"];
}

$app->render(
		"char.html",
		array(
			"char"          => $charInfo,
			"training"      => $training,
			"skills"        => $skills,
			"queue"         => $queue,
			"pageRefresh"   => $pageRefresh,
			"queueFinishes" => $maxSeconds,
			"groupSP"       => $groupSP,
			"totalSP"       => $totalSP,
			"wallet"        => $wallet,
			"pageType"      => $pageType,
			"isShare"       => $isShare,
			"skillTrain"    => $skillTrain,
			"config"        => $config,
			"implants"      => $headAttr,
			"message"	=> $message,
		     )
	    );

/**
 * @param int $seconds
 * @return string
 */
function secondsToTime($seconds)
{
	$dtF = new DateTime('UTC');
	$dtT = clone $dtF;
	$dtT->modify("+$seconds seconds");

	return $dtF->diff($dtT)->format('%ad %hh %im %ss');
}
