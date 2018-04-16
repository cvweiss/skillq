<?php

require_once "../init.php";

$hours = 24;
$queues = Db::query("select * from skq_character_info where queueFinishes > now() and queueFinishes < date_add(now(), interval $hours hour)");
foreach ($queues as $queue) {
	// Need to get the user info
	$chars = [$queue['characterID']];
	$chars = findChars($queue['characterID'], $chars);
	$email = null;
	foreach ($chars as $charID) {
		$apiCount = Db::queryField("select count(*) count from skq_scopes where characterID = :charID", "count", ['charID' => $charID]);
		if ($apiCount < 4) continue;
		$config = UserConfig::loadUserConfig($charID);
		$email = @$config['email'];
		if ($email != null) break;
	}
	if ($email == null) continue;

	$email    = $email;
	$name     = $queue["characterName"];
	$subject  = "$name skill notification."; 
	$url      = "https://skillq.net/char/" . urlencode($name);
	$body     = "Your character, <a href='$url'>$name</a>, has less than 24 hours remaining in their skill queue.<br/><br/>-- SkillQ.net";
	$event    = "${hours}hrQ:$name";

	$count = Db::queryField("select count(*) count from skq_email_history where email = :email and event = :event", "count", [':email' => $email, ':event' => $event]);
	if ($count == 0) {
		CreateEmail::create($email, $subject, $body);
		Db::execute("insert into skq_email_history (email, event, expireTime) values (:email, :event, date_add(now(), interval 24 hour))", [":email" => $email, ":event" => $event]);
	}
}
Db::execute("delete from skq_email_history where expireTime < now()");

function findChars($charID, &$chars = []) {
        if (sizeof($chars) == 0) $chars = [$charID];
        foreach ($chars as $char) {
                $result = Db::query("select char2 c from skq_character_associations where char1 = :char", [':char' => $char]);
                foreach ($result as $row) {
                        $nextChar = (int) $row['c'];
                        if (!in_array($nextChar, $chars)) {
                                $chars[] = $nextChar;
                                findChars($nextChar, $chars);
                        }
                }
        }
        foreach ($chars as $char) {
                $result = Db::query("select char1 c from skq_character_associations where char2 = :char", [':char' => $char]);
                foreach ($result as $row) {
                        $nextChar = (int) $row['c'];
                        if (!in_array($nextChar, $chars)) {
                                $chars[] = $nextChar;
                                findChars($nextChar, $chars);
                        }
                }
        }
        return array_unique($chars);
}
