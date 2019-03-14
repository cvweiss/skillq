<?php

require_once "../../init.php";

$sdeDb = $argv[1];
$dbUser = $argv[2];
$dbPassword = $argv[3];

echo "db: $sdeDb user $dbUser pass: '$dbPassword'\n";

$tables = array("dgmAttributeCategories", "dgmAttributeTypes", "dgmTypeAttributes", "invGroups", "invTypes");

foreach ($tables as $table)
{
	$ourTable = $table;
	if ($ourTable == "mapRegions") $ourTable = "regions";
	if ($ourTable == "mapSolarSystems") $ourTable = "systems";
	$ourTable = "ccp_$ourTable";
	echo "$ourTable\n";
	if ($table != "invTypes") Db::execute("truncate $ourTable");
	Db::execute("replace into $ourTable select * from $sdeDb.$table");
}
