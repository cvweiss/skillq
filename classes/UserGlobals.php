<?php

/**
 * Class UserGlobals
 */
class UserGlobals extends Twig_Extension
{
    /**
     * @return string
     */
    public function getName()
    {
        return "UserGlobals";
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        $result = array();
        if (isset($_SESSION["loggedin"])) {
            $u = User::getUserInfo();
            $this->addGlobal($result, "sessionusername", $u["username"]);
            $this->addGlobal($result, "sessionuserid", $u["id"]);
            $this->addGlobal($result, "sessionadmin", (bool) $u["admin"]);
            $this->addGlobal($result, "sessionmoderator", (bool) $u["moderator"]);
            $this->addGlobal($result, "sessiontheme", UserConfig::get("theme"), "cyborg");
            global $characters;
            $characters = Db::query(
              "select characterID, characterName from skq_api a left join skq_character_info i on (a.keyRowID = i.keyRowID) where a.userID = :userID and display = 1 order by skillsTrained desc, skillPoints desc, characterName",
              array(":userID" => $u["id"]),
              1
            );
            $this->addGlobal($result, "characters", $characters);
        }

        return $result;
    }

    /**
     * @param array  $array
     * @param string $key
     * @param mixed  $value
     * @param string   $defaultValue
     */
    private function addGlobal(&$array, $key, $value, $defaultValue = null)
    {
        if ($value == null && $defaultValue == null) {
            return;
        } else {
            if ($value == null) {
                $array[$key] = $defaultValue;
            } else {
                $array[$key] = $value;
            }
        }
    }
}
