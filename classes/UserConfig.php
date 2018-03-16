<?php

/**
 * Class UserConfig
 */
class UserConfig
{
    private static $userConfig = null;

    /**
     * @return int
     * @throws Exception
     */
    public static function getUserId()
    {
	return @$_SESSION['character_id'];
    }

    /**
     * @param int $id
     */
    public static function loadUserConfig($id)
    {
        UserConfig::$userConfig = array();
        $result                 = Db::query("select * from skq_users_config where id = :id", array(":id" => $id), 0);
        foreach ($result as $row) {
            UserConfig::$userConfig[$row["key"]] = json_decode($row["value"], true);
        }
	return UserConfig::$userConfig;
    }

    /**
     * @param string $key
     * @param mixed  $defaultValue
     * @return mixed
     */
    public static function get($key, $defaultValue = null)
    {
        if (!User::isLoggedIn()) {
            return $defaultValue;
        }
        $id = UserConfig::getUserId();
        UserConfig::loadUserConfig($id);

        $value = isset(UserConfig::$userConfig["$key"]) ? UserConfig::$userConfig["$key"] : null;
        if ($value === null) {
            return $defaultValue;
        }
        $value = json_decode($value, true);

        return $value;
    }

    /**
     * @param string $key
     * @param        $value
     * @param null   $default
     * @return mixed
     * @throws Exception
     */
    public static function set($key, $value, $default = null)
    {
        if (!User::isLoggedIn()) throw new Exception("User is not logged in.");
        $id = UserConfig::getUserId();

        if (is_null($value) || $value === $default || (is_string($value) && strlen(trim($value)) == 0)) {
            // Just remove the row and let the defaults take over
            return Db::execute("delete from skq_users_config where id = :id and `key` = :key", [':id' => $id, ':key' => $key]);
        }

	$value = json_encode($value);
        return Db::execute("insert into skq_users_config (id, `key`, `value`) values (:id, :key, :value) on duplicate key update `value` = :value", [":id" => $id, ":key" => $key, ":value" => $value]);
    }
}
