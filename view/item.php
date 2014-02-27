<?php
if (!is_numeric($id))
{
    $id = Info::getItemId($id);
    if ($id > 0) $app->redirect("/item/$id/");
    $app->redirect("/");
}

$info = Db::queryRow("select typeID, typeName, description from ccp_invTypes where typeID = :id", array(":id" => $id), 3600);
$info["description"] = str_replace("<br>", "\n", $info["description"]);
$info["description"] = strip_tags($info["description"]);
$info["attributes"] = Db::query("SELECT categoryName, coalesce(displayName, attributeName) attributeName, coalesce(valueint,valuefloat) value  FROM ccp_invTypes JOIN ccp_dgmTypeAttributes ON (ccp_invTypes.typeid = ccp_dgmTypeAttributes.typeid) JOIN ccp_dgmAttributeTypes ON (ccp_dgmTypeAttributes.attributeid = ccp_dgmAttributeTypes.attributeid) LEFT JOIN ccp_dgmAttributeCategories ON (ccp_dgmAttributeTypes.categoryid=ccp_dgmAttributeCategories.categoryid) WHERE ccp_invTypes.typeid = :typeID and ccp_dgmAttributeCategories.categoryid is not null and displayName is not null and ccp_dgmAttributeTypes.categoryID not in (8,9) ORDER BY ccp_dgmAttributeCategories.categoryid,   ccp_dgmAttributeTypes.attributeid", array(":typeID" => $id));

$info["reqs"] = getRequirements($id);
$info["enables"] = getEnables($id);

$app->render("item.html", array("info" => $info));

function getEnables($typeID) {
	$enables = Db::query("select * from (select i.typeID, typeName, requiredSkillLevel1 neededLevel from skq_skill_attributes s left join ccp_invTypes i on (i.typeID = s.typeID) where requiredSkill1 = :typeID union select i.typeID, typeName, requiredSkillLevel2 neededLevel from skq_skill_attributes s left join ccp_invTypes i on (i.typeID = s.typeID) where requiredSkill2 = :typeID union select i.typeID, typeName, requiredSkillLevel3 neededLevel from skq_skill_attributes s left join ccp_invTypes i on (i.typeID = s.typeID) where requiredSkill3 = :typeID) as foo order by neededLevel, typeName", array(":typeID" => $typeID));
	Info::addInfo($enables);
	return $enables;
}

function getRequirements($typeID, &$visited = array()) {
	$reqs = Db::queryRow("select * from skq_skill_attributes where typeID = :typeID", array(":typeID" => $typeID));
	$reqSkills = array();
	if (count($reqs)) {
		for($i = 1; $i <= 3; $i++) {
			$skill = getReqs($reqs, $i);
			if (count($skill) && !in_array($skill["typeID"], $visited)) {
				$reqSkills["req{$i}"] = $skill;
				$visited[] = $skill["typeID"];
			}
		}
		for($i = 1; $i <= 3; $i++) {
			if (isset($reqSkills["req{$i}"])) {
				$skill = $reqSkills["req{$i}"];
				$reqq = getRequirements($skill["typeID"], $visited);
				if (count($reqq)) $skill["reqs"] = $reqq;
				$reqSkills["req{$i}"] = $skill;
			}
		}
	}
	return $reqSkills;
}

function getReqs($reqs, $reqNum) {
	if (!isset($reqs["requiredSkill{$reqNum}"])) return array();
	$skill = array();
	$skill["typeID"] = $reqs["requiredSkill{$reqNum}"];
	$skill["requiredSkillLevel" ] = $reqs["requiredSkillLevel{$reqNum}"];
	Info::addInfo($skill);

	return $skill;
}
