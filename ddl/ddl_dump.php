<?php
// yea, this code sucks, but it gets the job done for mostly one-off executions

ini_set("auto_detect_line_endings", true);
require_once __DIR__ . "/../init.php";

$dest = __DIR__ . "/tables.sql";
$ccpDest = __DIR__ . "/ccp_tables.sql";
$destDir = __DIR__ . "/sql/";

exec("rm $destDir/*.sql 2>/dev/null");
exec("rm $destDir/*.gz 2>/dev/null"); 

echo "Dumping table list...\n";
exec("mysqldump -u $dbUser -p{$dbPassword} -h {$dbHost} -d {$dbName} > $dest");
echo "Splitting table list...\n";
@mkdir($destDir);
$tables = doSplitDumpFile($dest, $destDir, false, false);
$ccpTables = array();
foreach($tables as $table) {
	if (startsWith($table, "ccp_")) $ccpTables[] = $table;
}
$ccp = implode(" ", $ccpTables);

echo "Pulling CCP tables...\n";
exec("mysqldump -u $dbUser -p{$dbPassword} -h {$dbHost} {$dbName} $ccp > $ccpDest");
echo "Splitting CCP tables...\n";
doSplitDumpFile($ccpDest, $destDir, false, false);
echo "Fixing AUTO_INCREMENTs\n";
exec("sed -i 's/ AUTO_INCREMENT=[0-9]* / AUTO_INCREMENT=1 /g' $destDir/*");
exec("rm $destDir/*temporary.sql 2>/dev/null");

echo "Cleaning up...\n";
unlink($ccpDest);
unlink($dest);

function doSplitDumpFile($file, $destDir, $listOnly = false, $compressed = false) {
	$tables = array();
	$currentTable = "";
	$currentTableHandle = null;

	if (endsWith($file, ".gz")) $handle = gzopen($file, "r");
	else $handle = fopen($file, "r");

	while ($buffer = fgets($handle)) {
		if ($currentTable != "" && !startsWith($buffer, "--")) {
			$buffer = str_replace("ENGINE=Aria", "ENGINE=MyIsam", $buffer);
			$buffer = str_replace("PAGE_CHECKSUM=0", "", $buffer);
			$buffer = str_replace("PAGE_CHECKSUM=1", "", $buffer);
			$buffer = str_replace("TRANSACTIONAL=0", "", $buffer);
			$buffer = str_replace("TRANSACTIONAL=1", "", $buffer);
			$buffer = str_replace("DELAY_KEY_WRITE=1", "", $buffer);
			$buffer = str_replace("DELAY_KEY_WRITE=0", "", $buffer);
			if (!$listOnly && !startsWith($buffer, "/*!40")) fwrite($currentTableHandle, "$buffer");
		}
		if (startsWith($buffer, "-- Table structure for table `")) {
			$split = explode("`", $buffer);
			$currentTable = $split[1];
			if ($currentTableHandle != null) fclose($currentTableHandle);
			if (!$listOnly) {
				if ($compressed) $currentTableHandle = gzopen("$destDir/$currentTable.sql.gz", "w");
				else $currentTableHandle = fopen("$destDir/$currentTable.sql", "w+");
			}
			$tables[] = $currentTable;
		}
	}
	if ($currentTableHandle != null) fclose($currentTableHandle);
	fclose($handle);
	return $tables;
}

function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
	return substr($haystack, -strlen($needle)) === $needle;
}
