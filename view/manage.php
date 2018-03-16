<?php

if (!User::isLoggedIn()) {
    return $app->redirect("/login/");
}
$charID = User::getUserID();

global $chars;
$c = [];
foreach ($chars as $ch) {
        $c[] = $ch['characterID'];
}

if (sizeof($c) == 0) {
	$app->redirect('/logout/');
}

if ($_POST) {
	$removeID = $_POST['remove'];
	if (in_array($removeID, $c)) {
		Db::execute("delete from skq_character_info where characterID = :charID", [':charID' => $removeID]);
		Db::execute("delete from skq_scopes where characterID = :charID", [':charID' => $removeID]);
		Db::execute("delete from skq_character_associations where char1 = :charID or char2 = :charID", [':charID' => $removeID]);;
	}
	return;
}

global $chars;
$c = [];
foreach ($chars as $ch) {
	$c[] = $ch['characterID'];
}
$scopes = Db::query("select * from skq_character_info where characterID in (" . implode(',', $c) . ") order by characterName");
return $app->render("manage.html", ["scopes" => $scopes]);
