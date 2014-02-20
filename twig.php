<?php
// Load Twig globals
$app->view(new \Slim\Extras\Views\Twig());
$twig = $app->view()->getEnvironment();

\Slim\Extras\Views\Twig::$twigOptions = array(
    'charset'           => 'utf-8',
    'cache'             => 'cache/templates',
    'auto_reload'       => true,
    'strict_variables'  => false,
    'autoescape'        => true
);

\Slim\Extras\Views\Twig::$twigExtensions = array(
);

// Twig globals
$twig->addGlobal("siteurl", $baseAddr);
//$twig->addGlobal("fullsiteurl", "http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
$twig->addGlobal("image_character", "https://image.eveonline.com/Character/");
$twig->addGlobal("image_corporation", "https://image.eveonline.com/Corporation/");
$twig->addGlobal("image_alliance", "https://image.eveonline.com/Alliance/");
$twig->addGlobal("siteName", $siteName);

$twig->addExtension(new UserGlobals());

$twig->addFunction("pageTimer", new Twig_Function_Function("Util::pageTimer"));
$twig->addFunction("queryCount", new Twig_Function_Function("Db::getQueryCount"));
$twig->addFunction("isActive", new Twig_Function_Function("Util::isActive"));

$igb = stristr(@$_SERVER["HTTP_USER_AGENT"], "EVE-IGB");
$twig->addGlobal("eveigb", $igb);
