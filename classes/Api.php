<?php
/**
 * Various API helper functions for the website
 */
class Api
{

	/**
	 * Checks a key for validity and KillLog access.
	 *
	 * @static
	 * @param $keyID The keyID to be checked.
	 * @param $vCode The vCode to be checked
	 * @return string A message, Success on success, otherwise an error.
	 */
	public static function checkAPI($keyID, $vCode)
	{
		$keyID = trim($keyID);
		$vCode = trim($vCode);
		if ($keyID == "" || $vCode == "") return "Error, no keyID and/or vCode";
		$keyID = (int)$keyID;
		if ($keyID == 0) return "Invalid keyID.  Did you get the keyID and vCode mixed up?";

		$pheal = Util::getPheal($keyID, $vCode);
		try
		{
			$result = $pheal->accountScope->APIKeyInfo();
		}
		catch (Exception $e)
		{
			return "Error: " . $e->getCode() . " Message: " . $e->getMessage();
		}

		return true;
	}

	/**
	 * Adds a key to the database.
	 *
	 * @static
	 * @param $keyID
	 * @param $vCode
	 * @param null $label
	 * @return string
	 */
	public static function addApi($keyID, $vCode)
	{
		$userID = User::getUserID();
		if ($userID == null) throw new Exception("Must be logged in to add an API");

		$exists = Db::queryRow("SELECT userID, keyID, vCode FROM skq_api WHERE keyID = :keyID AND vCode = :vCode", array(":keyID" => $keyID, ":vCode" => $vCode), 0);
		if ($exists == null) {
			// Insert the api key
			Db::execute("INSERT INTO skq_api (userID, keyID, vCode) VALUES (:userID, :keyID, :vCode)", array(":userID" => $userID, ":keyID" => $keyID, ":vCode" => $vCode));
		} else if ($exists["userID"] == 0) {
			return "keyID $keyID previously existed in our database.";
		} else {
			return "keyID $keyID is already in the database...";
		}

		$pheal = Util::getPheal($keyID, $vCode);
		$result = $pheal->accountScope->APIKeyInfo();
		$key = $result->key;
		$keyType = $key->type;

		if ($keyType == "Account") $keyType = "Character";

		return "Success, your $keyType key has been added.";
	}

	/**
	 * Deletes a key owned by the currently logged in user.
	 *
	 * @static
	 * @param $keyID
	 * @return string
	 */
	public static function deleteKey($keyID)
	{
		$userID = user::getUserID();
		Db::execute("DELETE FROM skq_api WHERE userID = :userID AND keyID = :keyID", array(":userID" => $userID, ":keyID" => $keyID));
		return "$keyID has been deleted";
	}

	/**
	 * Returns a list of keys owned by the currently logged in user.
	 *
	 * @static
	 * @return Returns
	 */
	public static function getKeys()
	{
		$userID = user::getUserID();
		$result = Db::query("SELECT keyID, vCode, label, lastValidation FROM skq_api WHERE userID = :userID order by keyID", array(":userID" => $userID), 0);
		return $result;
	}

	/**
	 * Returns an array of charactery keys.
	 *
	 * @static
	 * @return Returns
	 */
	public static function getCharacterKeys()
	{
		$userID = user::getUserID();
		$result = Db::query("select c.* from skq_api_characters c left join skq_api a on (c.keyID = a.keyID) where a.userID = :userID", array(":userID" => $userID), 0);
		return $result;
	}

	/**
	 * Returns an array of the characters assigned to this user.
	 *
	 * @static
	 * @return array
	 */
	public static function getCharacters()
	{
		$userID = user::getUserID();
		$db = Db::query("SELECT characterID FROM skq_api_characters c left join skq_api a on (c.keyID = a.keyID) where userID = :userID", array(":userID" => $userID), 0);
		$results = Info::addInfo($db);
		return $results;
	}

	/**
	 * Tests the access mask for KillLog access
	 *
	 * @static
	 * @param $accessMask
	 * @return bool
	 */
	private static function hasBits($accessMask, $mask)
	{
		return ((int)($accessMask & $mask) > 0);
	}

	public static function processApi($keyRowID, $keyID = null, $vCode = null)
	{
		if ($keyID == null || $vCode == null) {
			$row = Db::queryRow("select keyID, vCode from skq_api where keyRowID = :keyRowID", array(":keyRowID" => $keyRowID), 0);
			$keyID = $row["keyID"];
			$vCode = $row["vCode"];
		}
		if ($keyRowID == null) {
			$row = Db::queryRow("select keyRowID from skq_api where keyID = :keyID and vCode = :vCode and errorCode = 0", array(":keyID" => $keyID, ":vCode" => $vCode), 0);
			$keyRowID = $row["keyRowID"];
		}
		if ($keyID == null || strlen($keyID) == "") throw new Exception("Invalid keyID! :$keyRowID:$keyID:$vCode:");
		if ($vCode == null || strlen($vCode) == "") throw new Exception("Invalid vCode! :$keyRowID:$keyID:$vCode:");
		$pheal = Util::getPheal($keyID, $vCode);
		try {
			Db::execute("update skq_api set errorCode = 0, accessMask = 0, lastValidation = now() where keyRowID = :keyRowID", array(":keyRowID" => $keyRowID));
			$result = $pheal->accountScope->APIKeyInfo();
			$accessMask = $result->key->accessMask;
			$expires = $result->key->expires;
			if ($expires == 0) $expires = null;
			$cachedUntil = $result->cachedUntil;
			Db::execute("update skq_api set accessMask = :aM, expires = :expires, cachedUntil = :cachedUntil where keyRowID = :keyRowID",
					array(":keyRowID" => $keyRowID, ":aM" => $accessMask, ":expires" => $expires, ":cachedUntil" => $cachedUntil));

			if ($accessMask & 33554432) {
				$account = $pheal->accountScope->AccountStatus();
				$paidUntil = $account->paidUntil;
				$createDate = $account->createDate;
				$logonCount = $account->logonCount;	
				$logonMinutes = $account->logonMinutes;
				Db::execute("replace into skq_api_account (keyRowID, paidUntil, createDate, logonCount, logonMinutes)
						values (:keyRowID, :paidUntil, :createDate, :logonCount, :logonMinutes)",
						array(":keyRowID" => $keyRowID, ":paidUntil" => $paidUntil, ":createDate" => $createDate,
							":logonCount" => $logonCount, ":logonMinutes" => $logonMinutes));
			} else {
				Db::execute("delete from skq_api_account where keyRowID = :keyRowID", array(":keyRowID" => $keyRowID));
			}

		} catch (Exception $ex) {
			$code = $ex->getCode();
			Db::execute("update skq_api set errorCode = :code where keyRowID = :keyRowID", array(":keyRowID" => $keyRowID, ":code" => $code));
			Db::execute("delete from skq_character_info = keyRowID = :keyRowID", array(":keyRowID" => $keyRowID));
			throw $ex;

		}
		$allToons = array();
		$chars = $result->key->characters;
		foreach($chars as $char) {
			$charID = $char["characterID"];
			$name = $char["characterName"];
			$allToons[] = $charID;
			Db::execute("insert ignore into skq_character_info (keyRowID, characterID, characterName) values (:keyRowID, :charID, :name)",
					array(":keyRowID" => $keyRowID, ":charID" =>$charID, ":name" => $name));
		}
		if (sizeof($allToons) == 0) Db::execute("delete from skq_character_info where keyRowID = :keyRowID", array(":keyRowID" => $keyRowID));
		else Db::execute("delete from skq_character_info where keyRowID = :keyRowID and characterID not in (" . implode(",", $allToons) . ")", array(":keyRowID" => $keyRowID));
	}
}
