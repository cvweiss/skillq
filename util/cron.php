<?php

require_once __DIR__ . "/../init.php";

function clearQueue()
{
	$toClean = Db::query(
			"select characterID from skq_character_training where trainingEndTime < now()",
			array(),
			0
			);
	foreach ($toClean as $clean) {
		$charID = $clean["characterID"];
		Db::execute(
				"replace into skq_character_training (characterID, trainingStartTime, trainingEndTime, trainingTypeID, trainingStartSP, trainingToLevel) select characterID, startTime, endTime, typeID, startSP, level from skq_character_queue where characterID = :charID and endTime > now() order by queuePosition limit 1",
				array(":charID" => $charID)
			   );
	}
	sleep(1);
	return;
}

function checkApis()
{
	Db::execute("update skq_api set errorCode = 0 where errorCode = 221 and lastValidation < date_sub(now(), interval 6 hour)");
	$apis = Db::query(
			"select keyRowID, keyID, vCode from skq_api where (errorCode < 200 or errorCode > 299) and cachedUntil < date_sub(now(), interval 1 hour)",
			array(),
			0
			);
	foreach ($apis as $api) {
		try {
			Api::processApi($api["keyRowID"], $api["keyID"], $api["vCode"]);
			sleep(1);
		} catch (Exception $ex) {
			// Do nothing
		}
	}
}

function updateChars()
{
	$task     = "";
	$keyRowID = 0;
	$charID   = 0;

	$chars = Db::query(
			"select * from skq_character_info where cachedUntil < now() and display = 1 and subFlag != 2 order by lastChecked",
			array(),
			0
			);
	foreach ($chars as $char) {
		try {
			Log::log("Updating " . $char["characterName"]);
			$charID   = $char["characterID"];
			$keyRowID = $char["keyRowID"];

			Db::execute(
					"update skq_character_info set lastChecked = now() where keyRowID = :keyRowID and characterID = :charID",
					array(":keyRowID" => $keyRowID, ":charID" => $charID)
				   );

			$keyInfo = Db::queryRow(
					"select * from skq_api where keyRowID = :keyRowID and errorCode = 0 and (expires is null or expires > now())",
					array(":keyRowID" => $keyRowID),
					0
					);

			// If we don't have any API data for this character then move on...
			if (count($keyInfo) == 0) {
				Db::execute("update skq_character_info set cachedUntil = date_add(now(), interval 24 hour) where keyRowID = :keyRowID and characterID = :charID",
						array(":keyRowID" => $keyRowID, ":charID" => $charID));
				Log::log("No API on record for this char...");
				continue;
			}

			$keyID       = $keyInfo["keyID"];
			$vCode       = $keyInfo["vCode"];
			$accessMask  = $keyInfo["accessMask"];
			$pheal       = Util::getPheal($keyID, $vCode);
			$arr         = array("characterID" => $charID);
			$cachedUntil = null;
			$task        = null;


			try {
				if ($accessMask & 131072) {
					$task        = "SkillInTraining";
					$t           = $pheal->charScope->SkillInTraining($arr);
					$cachedUntil = $t->cached_until;
					Db::execute(
							"update skq_character_skills set queue = 0, training = 0 where characterID = :charID",
							array(":charID" => $charID)
						   );
					if (((int) $t->skillInTraining) == 0) {
						Db::execute(
								"delete from skq_character_training where characterID = :charID",
								array(":charID" => $charID)
							   );
					} else {
						Db::execute(
								"replace into skq_character_training values (:charID, :startTime, :endTime, :typeID, :startSP, :destSP, :toLevel)",
								array(
									":charID"    => $charID,
									":startTime" => $t->trainingStartTime,
									":endTime"   => $t->trainingEndTime,
									":typeID"    => $t->trainingTypeID,
									":startSP"   => $t->trainingStartSP,
									":destSP"    => $t->trainingDestinationSP,
									":toLevel"   => $t->trainingToLevel
								     )
							   );
					}
					Db::execute(
							"update skq_character_skills set training = :level where characterID = :charID and typeID = :typeID",
							array(":charID" => $charID, ":typeID" => $t->trainingTypeID, ":level" => $t->trainingToLevel)
						   );
				} else {
					Db::execute(
							"delete from skq_character_training where characterID = :charID",
							array(":charID" => $charID)
						   );
				}
			} catch (Exception $ex) {
				Log::log("Unable to fetch SkillInTraining: " . $ex->getMessage());
			}

			try {
				$queueFinishes = 0;
				if ($accessMask & 262144) {
					$task        = "SkillQueue";
					$q           = $pheal->charScope->SkillQueue($arr);
					$cachedUntil = $cachedUntil < $q->cached_until ? $cachedUntil : $q->cached_until;
					$queue       = $q->skillqueue;
					Db::execute(
							"delete from skq_character_queue where characterID = :charID",
							array(":charID" => $charID)
						   );
					foreach ($queue as $qs) {
						$queueFinishes = max($queueFinishes, $qs->endTime);
						Db::execute(
								"insert into skq_character_queue (characterID, queuePosition, typeID, level, startSP, endSP, startTime, endTime) values
								(:charID, :qp, :typeID, :level, :startSP, :endSP, :startTime, :endTime)",
								array(
									":charID"    => $charID,
									":qp"        => $qs->queuePosition,
									":typeID"    => $qs->typeID,
									":level"     => $qs->level,
									":startSP"   => $qs->startSP,
									":endSP"     => $qs->endSP,
									":startTime" => $qs->startTime,
									":endTime"   => $qs->endTime
								     )
							   );
						Db::execute(
								"update skq_character_skills set queue = :level where characterID = :charID and typeID = :typeID",
								array(":charID" => $charID, ":typeID" => $qs->typeID, ":level" => $qs->level)
							   );
					}
				} else {
					Db::execute(
							"delete from skq_character_queue where characterID = :charID",
							array(":charID" => $charID)
						   );
				}
			} catch (Exception $ex) {
				Log::log("Unable to fetch SkillQueue: " . $ex->getMessage());
			}

			try {
				if ($accessMask & 8) {
					$task        = "CharacterSheet";
					$s           = $pheal->charScope->CharacterSheet($arr);
					$cachedUntil = $cachedUntil < $s->cached_until ? $cachedUntil : $s->cached_until;
					if ($s->allianceID === null) {
						$s->allianceID = 0;
					}
					if ($s->allianceID != 0) {
						Db::execute(
								"insert ignore into skq_alliances (allianceID, allianceName) values (:alliID, :alliName)",
								array(":alliID" => $s->allianceID, ":alliName" => $s->allianceName)
							   );
					}
					Db::execute(
							"insert ignore into skq_corporations (corporationID, corporationName) values (:corpID, :corpName)",
							array(":corpID" => $s->corporationID, ":corpName" => $s->corporationName)
						   );
					$cloneSP    = $s->cloneSkillPoints;
					$skills     = $s->skills;
					$skillCount = sizeof($skills);
					if ($char["cachedUntil"] == 0) {
						Db::execute(
								"delete from skq_character_info where keyRowID = :keyRowID and characterID = :charID",
								array(":keyRowID" => $keyRowID, ":charID" => $charID)
							   );
					}
					Db::execute(
							"insert into skq_character_info (keyRowID, characterID, characterName, dob, corporationID, allianceID, race, bloodline, ancestry, balance, skillsTrained, cloneSkillPoints, queueFinishes)
							values (:keyRowID, :charID, :name, :dob, :corpID, :alliID, :race, :bloodline, :ancestry, :balance, :skillsTrained, :cloneSkillPoints, :queueFinishes)
							on duplicate key update corporationID = :corpID, allianceID = :alliID, balance = :balance, skillsTrained = :skillsTrained, queueFinishes = :queueFinishes, cloneSkillPoints = :cloneSkillPoints",
							array(
								":keyRowID"         => $keyRowID,
								":charID"           => $charID,
								":name"             => $s->name,
								":corpID"           => $s->corporationID,
								":alliID"           => $s->allianceID,
								":race"             => $s->race,
								":bloodline"        => $s->bloodLine,
								":ancestry"         => $s->ancestry,
								":dob"              => $s->DoB,
								":balance"          => $s->balance,
								":skillsTrained"    => $skillCount,
								":queueFinishes"    => $queueFinishes,
								":cloneSkillPoints" => $cloneSP
							     )
						   );

					foreach ($skills as $skill) {
						Db::execute(
								"insert into skq_character_skills (characterID, typeID, level, skillPoints) values (:charID, :typeID, :level, :skillPoints) on duplicate key update level = :level, skillPoints = :skillPoints",
								array(
									":charID"      => $charID,
									":typeID"      => $skill->typeID,
									":level"       => $skill->level,
									":skillPoints" => $skill->skillpoints
								     )
							   );
					}

					$attributes   = $s->attributes;
					$intelligence = $attributes->intelligence;
					$willpower    = $attributes->willpower;
					$memory       = $attributes->memory;
					$charisma     = $attributes->charisma;
					$perception   = $attributes->perception;

					$enhancers = $s->attributeEnhancers;
					@Db::execute(
							"replace into skq_character_implants (characterID, attributeName, attributeID, baseValue, bonus, implantName) values (:charID, 'intelligence', 1, :value, :bonus, :name)",
							array(
								":charID" => $charID,
								":value"  => $intelligence,
								":bonus"  => (int) $enhancers->intelligenceBonus->augmentatorValue,
								":name"   => $enhancers->intelligenceBonus->augmentatorName
							     )
						    );
					@Db::execute(
							"replace into skq_character_implants (characterID, attributeName, attributeID, baseValue, bonus, implantName) values (:charID, 'willpower', 2, :value, :bonus, :name)",
							array(
								":charID" => $charID,
								":value"  => $willpower,
								":bonus"  => (int) $enhancers->willpowerBonus->augmentatorValue,
								":name"   => $enhancers->willpowerBonus->augmentatorName
							     )
						    );
					@Db::execute(
							"replace into skq_character_implants (characterID, attributeName, attributeID, baseValue, bonus, implantName) values (:charID, 'memory', 3, :value, :bonus, :name)",
							array(
								":charID" => $charID,
								":value"  => $memory,
								":bonus"  => (int) $enhancers->memoryBonus->augmentatorValue,
								":name"   => $enhancers->memoryBonus->augmentatorName
							     )
						    );
					@Db::execute(
							"replace into skq_character_implants (characterID, attributeName, attributeID, baseValue, bonus, implantName) values (:charID, 'charisma', 4, :value, :bonus, :name)",
							array(
								":charID" => $charID,
								":value"  => $charisma,
								":bonus"  => (int) $enhancers->charismaBonus->augmentatorValue,
								":name"   => $enhancers->charismaBonus->augmentatorName
							     )
						    );
					@Db::execute(
							"replace into skq_character_implants (characterID, attributeName, attributeID, baseValue, bonus, implantName) values (:charID, 'perception', 5, :value, :bonus, :name)",
							array(
								":charID" => $charID,
								":value"  => $perception,
								":bonus"  => (int) $enhancers->perceptionBonus->augmentatorValue,
								":name"   => $enhancers->perceptionBonus->augmentatorName
							     )
						    );
				}
			} catch (Exception $ex) {
				Log::log("Unable to fetch CharacterSheet: " . $ex->getMessage());
			}
			if ($cachedUntil == null) {
				Db::execute(
						"update skq_character_info set cachedUntil = date_add(now(), interval 2 hour) where keyRowID = :keyRowID and characterID = :charID",
						array(":keyRowID" => $keyRowID, ":charID" => $charID)
					   );
			} else {
				Db::execute(
						"update skq_character_info set cachedUntil = :cachedUntil where keyRowID = :keyRowID and characterID = :charID",
						array(":cachedUntil" => $cachedUntil, ":keyRowID" => $keyRowID, ":charID" => $charID)
					   );
			}

			sleep(1);
		} catch (Exception $ex) {
			Log::log("Error with task $task");
			// Recheck in a few minutes
			Db::execute(
					"update skq_character_info set cachedUntil = date_add(now(), interval 15 minute) where keyRowID = :keyRowID and characterID = :charID",
					array(":keyRowID" => $keyRowID, ":charID" => $charID)
				   );
			// Get the access on that key double checked
			Db::execute(
					"update skq_api set cachedUntil = 0 where keyRowID = :keyRowID",
					array(":keyRowID" => $keyRowID)
				   );
			// do nothing for now
			sleep(1);
		}
	}
}

function sendEmails()
{
	$emails = Db::query("select * from skq_emails where isSent = 0", array(), 0);
	foreach ($emails as $email) {
		$emailID = $email["emailID"];
		$to      = $email["recipient"];
		$subject = $email["subject"];
		$body    = $email["content"];

		$mail = new PHPMailer();
		$mail->SetFrom('noreply@skillq.net', "SkillQ");
		$mail->AddReplyTo("noreply@skillq.net", "SkillQ");
		$mail->AddAddress($to);
		$mail->Subject = $subject;
		//$mail->Body = $body;
		$mail->MsgHTML($body);
		if ($mail->Send()) {
			Db::execute(
					"update skq_emails set isSent = 1, sentTime = now() where emailID = :emailID",
					array(":emailID" => $emailID)
				   );
		} else {
			Db::execute(
					"update skq_emails set isSent = -1 where emailID = :emailID",
					array(":emailID" => $emailID)
				   );
		}
	}
}

function statusCheck()
{
	// Check for queues going to end in the next 24 hours
	Db::execute("delete from skq_email_history where expireTime < now()");
	statusCheckHours(24);
	statusCheckHours(6);
	apiExpiresCheck();
}

function apiExpiresCheck()
{
	$apiExpires = Db::query("select * from skq_api where expires > now() and expires < date_add(now(), interval 24 hour)");
	foreach($apiExpires as $row) {
		$keyID = $row["keyID"];
		$email = Db::queryField("select email from skq_users where id = :id", "email", array(":id" => $row["userID"]));
		if ($email == "") continue;
		$event = "ApiExpires:" . $row["keyID"];
		$toons = array();
		$t = Db::query("select * from skq_character_info where keyRowID = :id order by skillsTrained desc", array(":id" => $row["keyRowID"]));
		foreach($t as $row) {
			$toons[] = $row["characterName"];
		}
		$toons = implode(", ", $toons);
		$count = Db::queryField(
				"select count(*) count from skq_email_history where email = :email and event = :event",
				"count",
				array(":email" => $email, ":event" => $event),
				0
				);
		if ($count == 0) {
			$subject = "API expiring soon...";
			$body = "Your Eve Online API Key ID $keyID is expiring soon.  If this expires SkillQ will no longer be able to monitor skills.  This API is for the following characters: $toons<br/><br/>--SkillQ.net";
			CreateEmail::create($email, $subject, $body);
			Db::execute(
					"insert into skq_email_history (email, event, expireTime) values (:email, :event, date_add(now(), interval 24 hour))",
					array(":email" => $email, ":event" => $event)
				   );
		}
	}
}

function subscriptionCheck()
{
	Db::execute("update skq_character_info set subFlag = 0");
	$expiringSoon = Db::query(
			"select * from skq_api_account where paidUntil > now() and paidUntil < date_add(now(), interval 3 day)"
			);
	foreach ($expiringSoon as $row) {
		Db::execute(
				"update skq_character_info set subFlag = 1 where keyRowID = :keyRowID",
				array(":keyRowID" => $row["keyRowID"])
			   );
	}
	$expiringToday = Db::query(
			"select * from skq_api_account where paidUntil > now() and paidUntil < date_add(now(), interval 1 day)"
			);
	foreach ($expiringToday as $row) {
		$account = $row;
		$api = Db::queryRow("select * from skq_api where keyRowID = :id", array(":id" => $row["keyRowID"]));
		if ($api == null) continue;
		$email = Db::queryField("select email from skq_users where id = :id", "email", array(":id" => $api["userID"]));
		if ($email == "") continue;
		$event = "SubEnding:" . $api["keyRowID"];
		$toons = array();
		$t = Db::query("select * from skq_character_info where keyRowID = :id order by skillsTrained desc", array(":id" => $api["keyRowID"]));
		foreach($t as $row) {
			$toons[] = $row["characterName"];
		}
		$toons = implode(", ", $toons);
		$count = Db::queryField(
				"select count(*) count from skq_email_history where email = :email and event = :event",
				"count",
				array(":email" => $email, ":event" => $event),
				0
				);
		if ($count == 0) {
			$subject = "Warning! Eve Online subscription ending soon...";
			$body = "Your Eve Online account subscription is ending at " . $account["paidUntil"] . " UTC.  This account holds the following characters: $toons<br/><br/>--SkillQ.net";
			CreateEmail::create($email, $subject, $body);		
			Db::execute(   
					"insert into skq_email_history (email, event, expireTime) values (:email, :event, date_add(now(), interval 24 hour))",
					array(":email" => $email, ":event" => $event)
				   );
		}
	}

	$expired = Db::query("select * from skq_api_account where paidUntil < now()");
	foreach ($expired as $row) {
		Db::execute(
				"update skq_character_info set subFlag = 2 where keyRowID = :keyRowID",
				array(":keyRowID" => $row["keyRowID"])
			   );
	}
}

/**
 * @param integer $hours
 */
function statusCheckHours($hours)
{
	$queues = Db::query(
			"select * from skq_character_info where subFlag != 2 and queueFinishes > now() and queueFinishes < date_add(now(), interval $hours hour)"
			);
	foreach ($queues as $queue) {
		// Need to get the user info
		$name     = $queue["characterName"];
		$api      = Db::queryRow(
				"select * from skq_api where keyRowID = :keyRowID",
				array(":keyRowID" => $queue["keyRowID"])
				);
		$userID   = $api["userID"];
		$userInfo = Db::queryRow("select * from skq_users where id = :userID", array(":userID" => $userID));
		$email    = $userInfo["email"];
		$subject  = "$name skill notification";
		$url      = "http://skillq.net/char/" . urlencode($name);
		$body     = "Your character, <a href='$url'>$name</a>, has less than $hours hours remaining in their skill queue.<br/><br/>-- SkillQ.net";
		$event    = "${hours}hrQ:$name";
		try {
			$count = Db::queryField(
					"select count(*) count from skq_email_history where email = :email and event = :event",
					"count",
					array(":email" => $email, ":event" => $event),
					0
					);
			if ($count == 0) {
				CreateEmail::create($email, $subject, $body);
				Db::execute(
						"insert into skq_email_history (email, event, expireTime) values (:email, :event, date_add(now(), interval 24 hour))",
						array(":email" => $email, ":event" => $event)
					   );
			}
		} catch (Exception $ex) {
			print_r($ex);
			continue;
		}
	}
	// Check for out of date clones
	Db::execute(
			"update skq_character_info i, (select characterID, sum(skillPoints) skillPoints from skq_character_skills group by 1) as s set i.skillPoints = s.skillPoints where i.characterID = s.characterID"
		   );
	$insufficientClones = Db::query(
			"select keyRowID, characterID, characterName, skillPoints, cloneSkillPoints from skq_character_info where cloneSkillPoints < skillPoints and display = 1 and subFlag != 2"
			);
	foreach ($insufficientClones as $row) {
		$name     = $row["characterName"];
		$sp       = $row["skillPoints"];
		$cloneSP  = $row["cloneSkillPoints"];
		$api      = Db::queryRow(
				"select * from skq_api where keyRowID = :keyRowID",
				array(":keyRowID" => $row["keyRowID"])
				);
		$userID   = $api["userID"];
		$userInfo = Db::queryRow("select * from skq_users where id = :userID", array(":userID" => $userID));
		$email    = $userInfo["email"];
		$subject  = "$name has an insufficient clone!";
		$url      = "http://skillq.net/char/" . urlencode($name);
		$body     = "Your character, <a href='$url'>$name</a>, has " . number_format(
				$sp,
				0
				) . " SP, however, your clone can only support " . number_format(
					$cloneSP,
					0
					) . " SP.  If you get podded you will lose skill points!  It is highly recommended you update your clone as soon as possible!<br/><br/>-- SkillQ.net";
		$event    = "24InsuffClone:$name";
		try {
			$count = Db::queryField(
					"select count(*) count from skq_email_history where email = :email and event = :event",
					"count",
					array(":email" => $email, ":event" => $event),
					0
					);
			if ($count == 0) {
				CreateEmail::create($email, $subject, $body);
				Db::execute(
						"insert into skq_email_history (email, event, expireTime) values (:email, :event, date_add(now(), interval 7 day))",
						array(":email" => $email, ":event" => $event)
					   );
			}
		} catch (Exception $ex) {
			print_r($ex);
			continue;
		}
	}
}

function updateWallet()
{
	$result = Db::query(
			"select keyRowID, characterID from skq_character_info where display = 1 and subFlag != 2 and walletCachedUntil < now()",
			array(),
			0
			);
	foreach ($result as $row) {
		$charID = $row["characterID"];
		$api    = Db::queryRow(
				"select keyID, vCode, accessMask from skq_api where keyRowID = :keyRowID and errorCode = 0 and (expires is null or expires > now())",
				array(":keyRowID" => $row["keyRowID"])
				);

		if (count($api) == 0) {
			Db::execute(
					"update skq_character_info set walletCachedUntil = date_add(now(), interval 12 hour) where keyRowID = :keyRowID",
					array(":keyRowID" => $row["keyRowID"])
				   );
			continue; // No API on record, move along
		}

		$keyID      = $api["keyID"];
		$vCode      = $api["vCode"];
		$accessMask = $api["accessMask"];
		if ($accessMask & 2097152) {
			try {
				$pheal = Util::getPheal($keyID, $vCode);
				$arr   = array("characterID" => $charID, "rowCount" => 1000);
				$q     = $pheal->charScope->WalletJournal($arr);
			} catch (Exception $ex) {
				continue;
			}
			$cachedUntil = $q->cached_until;
			Db::execute(
					"update skq_character_info set walletCachedUntil = :cachedUntil where characterID = :charID",
					array(":charID" => $charID, ":cachedUntil" => $cachedUntil)
				   );
			foreach ($q->transactions as $record) {
				Db::execute(
						"insert ignore into skq_character_wallet (characterID, dttm, refID, refTypeID, ownerName1, ownerID1, ownerName2, ownerID2, argName1, argID1,amount, balance, reason, taxReceiverID, taxAmount) values (:charID, :dttm , :refID, :refTypeID, :ownerName1, :ownerID1, :ownerName2, :ownerID2, :argName1, :argID1, :amount, :balance, :reason, :taxReceiverID, :taxAmount)",
						array(
							":charID"        => $charID,
							":dttm"          => $record["date"],
							":refID"         => $record["refID"],
							":refTypeID"     => $record["refTypeID"],
							":ownerName1"    => $record["ownerName1"],
							":ownerID1"      => $record["ownerID1"],
							":ownerName2"    => $record["ownerName2"],
							":ownerID2"      => $record["ownerID2"],
							":argName1"      => $record["argName1"],
							":argID1"        => $record["argID1"],
							":amount"        => $record["amount"],
							":balance"       => $record["balance"],
							":reason"        => $record["reason"],
							":taxReceiverID" => $record["taxReceiverID"],
							":taxAmount"     => $record["taxAmount"]
						     )
					   );
			}
		} else {
			Db::execute(
					"update skq_character_info set walletCachedUntil = date_add(now(), interval 8 hour) where characterID = :charID",
					array(":charID" => $charID)
				   );
		}
	}
	Db::execute("delete from skq_character_wallet where dttm < date_sub(now(), interval 30 day)");
}
