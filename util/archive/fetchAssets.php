<?php

require_once "../init.php";


$apis = Db::query("select a.keyID, a.vCode, b.characterID, a.keyRowID from skq_api a left join skq_character_info b on (a.keyRowID = b.keyRowID) where assets < now() and errorCode = 0", array(), 0);

foreach ($apis as $api)
{
	$keyID = $api["keyID"];
	$vCode = $api["vCode"];
	$charID = $api["characterID"];

	$pheal = Util::getPheal($keyID, $vCode);

	$arr = array("characterID" => $charID);

	try {
		$q = $pheal->charScope->AssetList($arr);
		Db::execute("delete from skq_character_assets where characterID = :charID", array(":charID" => $charID));
	} catch (Exception $ex)
	{
		Db::execute("update skq_api set assets = date_add(now(), interval 30 day) where keyRowID = :rowID", array(":rowID" => $api["keyRowID"]));
		sleep(30);
		continue;
	}

	$value = 0;
	$cachedUntil = $q->cached_until;
	foreach($q->assets as $assets)
	{
		$value += addAssets($charID, $assets);
	}
	Db::execute("update skq_api set assets = :cachedUntil where keyRowID = :rowID", array(":cachedUntil" => $cachedUntil, ":rowID" => $api["keyRowID"]));
	Db::execute("update skq_character_info set assetsValue = :value where characterID = :charID", array(":charID" => $charID, ":value" => $value));
}

function addAssets($charID, $asset)
{
	$value = 0;
	if (isset($asset->itemID))
	{
		$value += $asset->quantity * 1 * Db::queryField("select avg(avgPrice) avgPrice from  skq_item_history where regionID = 10000002 and typeID = :typeID and priceDate >= date_sub(date(now()), interval 30 day)", "avgPrice", array(":typeID" => $asset->typeID));
		Db::execute("insert into skq_character_assets (characterID, locationID, itemID, typeID, quantity, flag, singleton, rawQuantity, value) values (:charID, :locID, :itemID, :typeID, :quantity, :flag, :singleton, :rawQ, (:quantity * :value))", array(":charID" => $charID, ":locID" => $asset->locationID, ":itemID" => $asset->itemID, ":typeID" => $asset->typeID, ":quantity" => $asset->quantity, ":flag" => $asset->flag, ":singleton" => $asset->singleton, ":rawQ" => (1 * @$asset->rawQuantity), ":value" => $value));
	}
	if (isset($asset["itemID"]))
	{
		$value += $asset["quantity"] * 1 * Db::queryField("select avg(avgPrice) avgPrice from  skq_item_history where regionID = 10000002 and typeID = :typeID and priceDate >= date_sub(date(now()), interval 30 day)", "avgPrice", array(":typeID" => $asset["typeID"]));
		Db::execute("insert into skq_character_assets (characterID, locationID, itemID, typeID, quantity, flag, singleton, rawQuantity, value) values (:charID, :locID, :itemID, :typeID, :quantity, :flag, :singleton, :rawQ, (:quantity * :value))", array(":charID" => $charID, ":locID" => $asset["locationID"], ":itemID" => $asset["itemID"], ":typeID" => $asset["typeID"], ":quantity" => $asset["quantity"], ":flag" => $asset["flag"], ":singleton" => $asset["singleton"], ":rawQ" => (1 * @$asset["rawQuantity"]), ":value" => $value));
	}

	if (isset($asset->contents)) $value += addAssets($charID, $asset->contents);
	if (isset($asset["contents"])) $value += addAssets($charID, $asset["contents"]);
	return $value;
}
