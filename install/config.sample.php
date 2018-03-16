<?php
date_default_timezone_set("UTC");

// MySQL Database parameters
$dbUser = "";
$dbPassword = "";
$dbName = "";
$dbHost = "";

// Base
$baseFile = __FILE__;
$baseDir = dirname($baseFile) . "/";
$baseUrl = "/";
chdir($baseDir);

// Replace $baseAddr with own address
$baseAddr = "skillq.net";
$siteName = "SkillQ.net";

// Memcache
$memcacheServer = "127.0.0.1";
$memcachePort = "11211";

$phealCacheLocation = "/var/www/skillq.net/cache/pheal/";

// Application ESI Data
// Unless you've already done so you'll need to create an application via https://developers.eveonline.com/
$scopes = ['esi-skills.read_skills.v1', 'esi-skills.read_skillqueue.v1', 'esi-wallet.read_character_wallet'];
// The callbackURL must be your address plus '/ccp/callback'. Your created application must match it.
$callbackURL = 'https://skillq.net/ccp/callback'; // Remember to change it to http if you aren't using ssl or tls.
$clientID = 'Your apps client id';
$secretKey = 'Your apps secret key'; // Feel free to implement this more securely depending on use case.

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
