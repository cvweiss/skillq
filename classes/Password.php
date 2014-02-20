<?php
class Password
{
	public static function genPassword($password)
	{
		for ($i = 0; $i <= 87421; $i++)
		{
			if ($i == 0)
				$pw = hash("sha256", $password);
			else
				$pw = hash("sha256", $pw);
		}
		return $pw;
	}

	public static function updatePassword($password)
	{
		$userID = user::getUserID();
		$password = self::genPassword($password);
		Db::execute("UPDATE zz_users SET password = :password WHERE id = :userID", array(":password" => $password, ":userID" => $userID));
		return "Updated password";
	}

	public static function checkPassword($password)
	{
		$userID = user::getUserID();
		$password = self::genPassword($password);
		$pw = Db::queryField("SELECT password FROM zz_users WHERE id = :userID", "password", array(":userID" => $userID));
		if ($pw == $password)
			return true;
		else
			return false;
	}
}