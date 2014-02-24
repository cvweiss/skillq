<?php

if (!User::isLoggedIn()) {
    $app->redirect("/login/");
}
$userID = User::getUserID();

global $characters;
$characters = Db::query(
  "select characterID, characterName from skq_api a left join skq_character_info i on (a.keyRowID = i.keyRowID) where a.userID = :userID and display = 1 order by skillsTrained desc, skillPoints desc, characterName",
  array(":userID" => $userID),
  1
);

Db::execute("delete from skq_character_shares where expirationTime < now()");

$message = null;
if ($_POST) {
    $charID = $_POST["characterid"];

    $validChar = false;
    foreach ($characters as $character) {
        $validChar |= $character["characterID"] == $charID;
    }

    $shareID = trim($_POST["shareid"]);
    if (strlen($shareID) == 0) {
        do {
            $shareID = gen_uuid();
        } while (Db::queryField(
          "select count(*) count from skq_character_shares where shareID = :shareID",
          "count",
          array(":shareID" => $shareID)
        ));
    } else {
        $count = Db::queryField(
          "select count(*) count from skq_character_shares where shareID = :shareID",
          "count",
          array(":shareID" => $shareID),
          0
        );
        if ($count > 0) {
            $message = "Sorry, that shareID is already taken!";
        }
    }

    if ($message == null && !$validChar) {
        $message = "Invalid character selection.";
    }

    if ($message === null) {
        Db::execute(
          "insert into skq_character_shares (userID, shareID, characterID, expirationTime) values
                                  (:userID, :shareID, :charID, date_add(now(), interval 3 day))",
          array(":userID" => $userID, ":charID" => $charID, ":shareID" => $shareID)
        );
        $message = "Your share has been created!";
    }
}
if (isset($action) && isset($id)) {
    switch ($action) {
        case "delete":
            Db::execute(
              "delete from skq_character_shares where userID = :userID and shareID = :shareID",
              array(":userID" => $userID, ":shareID" => $id)
            );
            $message = "The share has been deleted.";
            break;
    }
}

$shares = Db::query(
  "select * from skq_character_shares where userID = :userID order by expirationTime",
  array(":userID" => $userID),
  1
);
Info::addInfo($shares);

$app->render("shares.html", array("shares" => $shares, "message" => $message));


/**
 * @return string
 */
function gen_uuid()
{
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for "time_low"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),
      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,
      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,
      // 48 bits for "node"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
}
