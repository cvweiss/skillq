<?php

require_once __DIR__ . "/../init.php";

// Populate the skill attributes table
Db::execute("insert ignore into skq_skill_attributes (typeID) select typeID from ccp_invTypes");

$skills = Db::query("select typeID from skq_skill_attributes", array(), 0);
foreach ($skills as $skill) {
    $typeID     = $skill["typeID"];
    $attributes = Db::query("select * from ccp_dgmTypeAttributes where typeID = :typeID", array(":typeID" => $typeID));

    $trialTrainable = 1;
    $primary        = $secondary = $requiredSkill1 = $requiredSkillLevel1 = $skillLevel = null;
    $requiredSkill2 = $requiredSkill3 = $requiredSkillLevel2 = $requiredSkillLevel3 = null;
    $timeMultiplier = 1;
    foreach ($attributes as $attribute) {
        $attributeID    = $attribute["attributeID"];
        $attributeValue = (int) (is_null($attribute["valueInt"]) ? $attribute["valueFloat"] : $attribute["valueInt"]);
        $attributeInfo  = Db::queryRow(
          "select * from ccp_dgmAttributeTypes where attributeID = :aID",
          array(":aID" => $attributeID)
        );
        switch ($attributeID) {
            case 180:
                $primary = Db::queryField(
                  "select attributeName from ccp_dgmAttributeTypes where attributeID = :aID",
                  "attributeName",
                  array(":aID" => $attributeValue)
                );
                break;
            case 181:
                $secondary = Db::queryField(
                  "select attributeName from ccp_dgmAttributeTypes where attributeID = :aID",
                  "attributeName",
                  array(":aID" => $attributeValue)
                );
                break;
            case 182:
                $requiredSkill1 = $attributeValue;
                break;
            case 183:
                $requiredSkill2 = $attributeValue;
                break;
            case 184:
                $requiredSkill3 = $attributeValue;
                break;
            case 275:
                $timeMultiplier = $attributeValue;
                break;
            case 277:
                $requiredSkillLevel1 = $attributeValue;
                break;
            case 278:
                $requiredSkillLevel2 = $attributeValue;
                break;
            case 279:
                $requiredSkillLevel3 = $attributeValue;
                break;
            case 280:
                $skillLevel = $attributeValue;
                break;
            case 1047:
                if ($attributeValue == 1) {
                    $trialTrainable = 0;
                }
                break;
        }
    }
    if ($skillLevel == null) {
        $skillLevel = 0;
    }
    echo "$typeID $timeMultiplier $skillLevel $primary $secondary $requiredSkill1 $requiredSkillLevel1 $requiredSkill2 $requiredSkillLevel2 $requiredSkill3 $requiredSkillLevel3\n";
    Db::execute(
      "update skq_skill_attributes set trialTrainable = :tt, timeMultiplier = :tm, skillLevel = :sl, primaryAttribute = :p, secondaryAttribute = :s, requiredSkill1 = :rs1, requiredSkillLevel1 = :rsl1, requiredSkill2 = :rs2, requiredSkillLevel2 = :rsl2, requiredSkill3 = :rs3, requiredSkillLevel3 = :rsl3 where typeID = :typeID",
      array(
        ":tm"     => $timeMultiplier,
        ":p"      => $primary,
        ":s"      => $secondary,
        ":rs1"    => $requiredSkill1,
        ":rs2"    => $requiredSkill2,
        ":rs3"    => $requiredSkill3,
        ":rsl1"   => $requiredSkillLevel1,
        ":rsl2"   => $requiredSkillLevel2,
        ":rsl3"   => $requiredSkillLevel3,
        ":typeID" => $typeID,
        ":tt"     => $trialTrainable,
        ":sl"     => $skillLevel
      )
    );
}

// Remove typeIDs that have no skill requirements
Db::execute("delete from skq_skill_attributes where requiredSkillLevel1 is null and requiredSkillLevel2 is null and requiredSkillLevel3 is null");
