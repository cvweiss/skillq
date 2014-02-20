<?php

if (!User::isLoggedIn()) return $app->redirect("/login/");
$userID = User::getUserID();

if ($_POST) {
	@$keyID = $_POST["keyid"];
	@$vCode = $_POST["vcode"];

	// Validate that the code is good
	$result = Api::checkApi($keyID, $vCode);
	if ($result !== true) return $app->render("message.html", array("type" => "error", "message" => $result));
	else {
		Api::addApi($keyID, $vCode);
		Api::processApi(null, $keyID, $vCode);
	}
	$app->redirect("/manage/");
}
if (isset($action) && isset($id)) {
	switch($action) {
		case "toggle":
			$result = Db::query("select keyRowID from skq_api where userID = :userID", array(":userID" => $userID), 0);
			$keyRows = array();
			foreach($result as $row) $keyRows[] = $row["keyRowID"];
			$keyRows = implode(",", $keyRows);
			
			Db::execute("update skq_character_info set display = !display where keyRowID in ($keyRows) and characterID = :id", array(":id" => $id));
		break;
		case "delete":
			$rows = Db::execute("delete from skq_api where keyRowID = :keyRowID and userID = :userID", array(":keyRowID" => $id, ":userID" => $userID));
			if ($rows > 0) Db::execute("delete from skq_character_info where keyRowID = :keyRowID", array(":keyRowID" => $id));
		break;
	}
	
	$app->redirect("/manage/");
}

$apis = Db::query("select * from skq_api where userID = :userID order by keyID", array(":userID" => User::getUserID()));
$keyRowIDs = array();
foreach($apis as $api) {
	$keyRowIDs[$api["keyRowID"]] = array();
	$keyRowIDs[$api["keyRowID"]]["info"] = $api; 
}

foreach($keyRowIDs as $keyRowID=>$ignore) {
		$keyRowIDs[$keyRowID]["chars"] = Db::query("select * from skq_character_info where keyRowID = :keyRowID order by skillsTrained desc", array(":keyRowID" => $keyRowID), 0);
}

Info::addInfo($keyRowIDs);
return $app->render("manage.html", array("keys" => $keyRowIDs, "apis" => $apis));
