<?php
// Load Twig globals
$app->view(new \Slim\Extras\Views\Twig());
$twig = $app->view()->getEnvironment();

\Slim\Extras\Views\Twig::$twigOptions = array(
    'charset'           => 'utf-8',
    'cache'             => 'cache/templates',
    'auto_reload'       => true,
    'strict_variables'  => false,
    'autoescape'        => true
);

\Slim\Extras\Views\Twig::$twigExtensions = array(
);

// Twig globals
$twig->addGlobal("siteurl", $baseAddr);
//$twig->addGlobal("fullsiteurl", "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
$twig->addGlobal("image_character", "https://images.evetech.net/characters/");
$twig->addGlobal("image_corporation", "https://images.evetech.net/corporations/");
$twig->addGlobal("image_alliance", "https://images.evetech.net/alliances/");
$twig->addGlobal("siteName", $siteName);

$twig->addExtension(new UserGlobals());

$twig->addFunction("pageTimer", new Twig_Function_Function("Util::pageTimer"));
$twig->addFunction("queryCount", new Twig_Function_Function("Db::getQueryCount"));
$twig->addFunction("isActive", new Twig_Function_Function("Util::isActive"));

$twig->addGlobal("sessionusername", @$_SESSION['character_id']);
$twig->addGlobal("theme", UserConfig::get("theme", "cyborg"));
$twig->addGlobal("themes", ['amelia', 'cerulean', 'cyborg', 'default', 'journal', 'readable', 'simplex', 'slate', 'spacelab', 'spruce', 'superhero', 'united']);
$twig->addGlobal("fluid", UserConfig::get("fluid", ""));

$chars = [];
$userID = null;
if (isset($_SESSION['character_id']) && $_SESSION['character_id'] > 0) {
	$userID = $_SESSION['character_id'];
    Db::execute("insert into skq_users (characterID, dateCreated, lastAccess) values (:charID, now(), now()) on duplicate key update lastAccess = now()", [':charID' => $userID]);
	$chars = findChars($_SESSION['character_id']);
	$validChars = $chars;
	$orderBy = UserConfig::get("orderBy", "skillPoints desc");
	$groupOrderBy = UserConfig::get("groupOrderBy", "grouped desc");
	foreach ($chars as $charID) {
		// Make sure we are always displaying the current skill in the queue
		Db::execute("replace into skq_character_training (characterID, trainingStartTime , trainingEndTime , trainingTypeID , trainingStartSP , trainingDestinationSP , trainingToLevel ) select characterID , startTime , endTime , typeID , startSP , endSP , level from skq_character_queue  where endTime >= now() and characterID = :charID order by startTime limit 1", [':charID' => $charID]);
	}
	$chars = Db::query("select distinct i.characterID, characterName, trainingTypeID typeID, trainingToLevel, trainingEndTime, balance, skillPoints, queueFinishes, grouped from skq_character_info i left join skq_character_training t on (i.characterID = t.characterID) where i.characterID in (" . implode(",", $chars) . ") order by $groupOrderBy, $orderBy");
	$twig->addGlobal("characters", $chars);
}

function findChars($charID, &$chars = []) {
	if (sizeof($chars) == 0) $chars = [$charID];
	foreach ($chars as $char) {
		$result = Db::query("select char2 c from skq_character_associations where char1 = :char", [':char' => $char], 1);
		foreach ($result as $row) {
			$nextChar = (int) $row['c'];
			if (!in_array($nextChar, $chars)) {
				$chars[] = $nextChar;
				findChars($nextChar, $chars);
			}
		}
	}
	/*foreach ($chars as $char) {
		$result = Db::query("select char1 c from skq_character_associations where char2 = :char", [':char' => $char], 1);
		foreach ($result as $row) {
			$nextChar = (int) $row['c'];
			if (!in_array($nextChar, $chars)) {
				$chars[] = $nextChar;
				findChars($nextChar, $chars);
			}
		}
	}*/
	return array_unique($chars);
}
