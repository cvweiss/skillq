<?php
date_default_timezone_set("UTC");

// Database parameters
$dbUser = "";
$dbPassword = "";
$dbName = "";
$dbHost = "";

// Base
$baseFile = __FILE__;
$baseDir = dirname($baseFile) . "/";
$baseUrl = "/";
chdir($baseDir);

$baseAddr = "skillq.net";
$siteName = "SkillQ.net";

// Memcache
$memcacheServer = "127.0.0.1";
$memcachePort = "11211";

$phealCacheLocation = "/var/www/skillq.net/cache/pheal/";

// Cookiiieeeee
$cookie_name = "skQ";
$cookie_time = (3600 * 24 * 30); // 30 days

// Slim config
// to enable log, add "log.writer" => call after "log.enabled" => true, - you might have to load it in index after init has run and do $config["log.writer"] = call;
$config = array(
	"templates.path" => $baseDir."templates/",
	"mode" => "development",
	"debug" => true,
	"log.enabled" => false
	);

$logfile = "/var/log/www/skillq.log";

$emailsmtp = "localhost";
$emailusername = "noreply@skillq.net";
$sentfromemail = "noreply@skillq.net";
$sentfromdomain = "SkillQ";
