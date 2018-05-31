<?php

require_once "../init.php";

if (date('i') != 0) exit();

$result = Db::query("select * from (select characterID, count(*) count from skq_scopes group by 1) as foo where count = 1");
$tables = ['skq_character_assets', 'skq_character_certs', 'skq_character_implants', 'skq_character_info', 'skq_character_queue', 'skq_character_shares', 'skq_character_skills', 'skq_character_training', 'skq_character_wallet', 'skq_scopes', 'skq_users', 'skq_users_config', 'skq_character_tracking'];
foreach ($result as $row) {
	$charID = $row['characterID'];
	Util::out("Cleaning up $charID");
	foreach ($tables as $table) {
		Db::execute("delete from $table where characterID = :charID", [':charID' => $charID]);
	}
	Db::execute("delete from skq_character_associations where char1 = :charID or char2 = :charID", [':charID' => $charID]);
}

foreach ($tables as $table) {
	$result = Db::query("select distinct a.characterID from $table a where a.characterID not in (select distinct b.characterID from skq_scopes b)");
	if (sizeof($result)) {
		foreach ($result as $row) {
			$charID = $row['characterID'];
			Util::out("Removing $charID from $table");
			Db::execute("delete from $table where characterID = :charID", [':charID' => $charID]);
		}
	}
}
