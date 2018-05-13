<?php

Db::execute("delete from skq_character_shares where expirationTime < now()");
$charID = Db::queryField("select characterID from skq_character_info where characterName = :name", "characterID", [':name' => $name]);
$share = Db::queryRow("select * from skq_character_shares where characterID = :charID and shareID = :shareID", [':charID' => $charID, ":shareID" => $shareID], 1);

if ($share) {
    Db::execute(
      "update skq_character_shares set views = views + 1 where shareID = :shareID",
      array(":shareID" => $shareID),
      1
    );
    $bypassLogin = true;
    include "view/char.php";
} else $app->render("404.html", ['message' => "Invalid share - did it expire?", "type" => "error"], 404);
