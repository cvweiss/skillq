<?php

require_once __DIR__ . "/../init.php";

// Check for queues going to end in the next 24 hours
$queues = Db::query("select * from skq_character_info where queueFinishes > now() and queueFinishes < date_add(now(), interval 24 hour)");
foreach($queues as $queue) {
	// Need to get the user info
	$name = $queue["characterName"];
	$api = Db::queryRow("select * from skq_api where keyRowID = :keyRowID", array(":keyRowID" => $queue["keyRowID"]));
	$userID = $api["userID"];
	$userInfo = Db::queryRow("select * from skq_users where id = :userID", array(":userID" => $userID));
	$email = $userInfo["email"];
	$subject = "$name skill notification";
	$body = "Your character, $name, has less than 24 hours remaining in their skill queue.\n\n-- SkillQ.net";
	$event = "24hr:$name";
	try {
		Db::execute("insert into skq_email_history (email, event) values (:email, :event)", array(":email" => $email, ":event" => $event));
		Email::create($email, $subject, $body);
	} catch (Exception $ex) {
		continue;
	}
}
