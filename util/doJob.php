#!/usr/bin/php5
<?php
// Command line execution?
$cle = "cli" == php_sapi_name();
if (!$cle) return; // Prevent web execution

$base = __DIR__;
require_once "$base/../init.php";
require_once "$base/cron.php";

if (!isset($argv[1])) die("Must provide a job name");


$job = $argv[1];
Log::log("executing cron: $job");
$job();
