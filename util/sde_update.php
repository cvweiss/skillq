<?php

require_once "../init.php";

$tables = array("dgmAttributeCategories", "dgmAttributeTypes", "dgmTypeAttributes", "invGroups", "invTypes");

foreach ($tables as $table)
{
	$sdeDb = $argv[1];
	$ourTable = $table;
	if ($ourTable == "mapRegions") $ourTable = "regions";
	if ($ourTable == "mapSolarSystems") $ourTable = "systems";
	$ourTable = "ccp_$ourTable";
	echo "$ourTable\n";
	if ($table != "invTypes") Db::execute("truncate $ourTable");
	Db::execute("replace into $ourTable select * from $sdeDb.$table");
}
