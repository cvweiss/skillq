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

if ($_POST) {
	$removeID = $_POST['remove'];
	if (in_array($removeID, $c)) {
		Db::execute("delete from skq_character_info where characterID = :charID", [':charID' => $removeID]);
		Db::execute("delete from skq_scopes where characterID = :charID", [':charID' => $removeID]);
		Db::execute("delete from skq_character_associations where char1 = :charID or char2 = :charID", [':charID' => $removeID]);;
	}
	return;
}

if (isset($action) && isset($id)) {
    switch ($action) {
        case "toggle":
            $result  = Db::query("select keyRowID from skq_scopes where characterID = :charID", array(":charID" => $charID), 0);
            $keyRows = array();
            foreach ($result as $row) {
                $keyRows[] = $row["keyRowID"];
            }
            $keyRows = implode(",", $keyRows);

            Db::execute(
              "update skq_character_info set display = !display where keyRowID in ($keyRows) and characterID = :id",
              array(":id" => $id)
            );
            break;
        case "delete":
            $rows = Db::execute(
              "delete from skq_scopes where keyRowID = :keyRowID and characterID = :charID",
              array(":keyRowID" => $id, ":charID" => $charID)
            );
            if ($rows > 0) {
                Db::execute(
                  "delete from skq_character_info where keyRowID = :keyRowID",
                  array(":keyRowID" => $id)
                );
            }
            break;
    }

    $app->redirect("/manage/");
}


global $chars;
$c = [];
foreach ($chars as $ch) {
	$c[] = $ch['characterID'];
}
$scopes = Db::query("select * from skq_character_info where characterID in (" . implode(',', $c) . ") order by characterName");
return $app->render("manage.html", ["scopes" => $scopes]);
