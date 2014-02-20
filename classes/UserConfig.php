<?php

class UserConfig
{
	private static $userConfig = null;

	public static function getUserId()
	{
		$userInfo = User::getUserInfo();
		if ($userInfo === null) throw new Exception("user is not logged in");
		$id = $userInfo["id"];
		return $id;
	}

	public static function loadUserConfig($id)
	{
		if (UserConfig::$userConfig != null) return;
		UserConfig::$userConfig = array();
		$result = Db::query("select * from skq_users_config where id = :id", array(":id" => $id), 0);
		foreach ($result as $row) {
			UserConfig::$userConfig[$row["key"]] = $row["value"];
		}
	}

	public static function get($key, $defaultValue = null)
	{
		if (!User::isLoggedIn()) return $defaultValue;
		$id = UserConfig::getUserId();
		UserConfig::loadUserConfig($id);

		$value = isset(UserConfig::$userConfig["$key"]) ? UserConfig::$userConfig["$key"] : null;
		if ($value === null) return $defaultValue;
		$value = json_decode($value, true);
		return $value;
	}

	public static function set($key, $value, $default = null)
	{
		if (!User::isLoggedIn()) throw new Exception("User is not logged in.");
		$id = UserConfig::getUserId();
		UserConfig::$userConfig = null;

		if (is_null($value) || $value === $default || (is_string($value) && strlen(trim($value)) == 0)) {
			// Just remove the row and let the defaults take over
			return Db::execute("delete from skq_users_config where id = :id and `key` = :key", array(":id" => $id, ":key" => $key));
		}

		$value = json_encode($value);
		return Db::execute("insert into skq_users_config (id, `key`, `value`) values (:id, :key, :value)
                                on duplicate key update `value` = :value", array(":id" => $id, ":key" => $key, ":value" => $value));
	}
}
