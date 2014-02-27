<?php
$app->notFound(function () use ($app) {
    $app->render('404.html');
});

// Default route
$app->get("/", function () use ($app){
    include( "view/index.php" );
});

// Register
$app->get("/register/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/register.php" );
});
$app->post("/register/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/register.php" );
});

// Login stuff
$app->get("/login/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/login.php" );
});
$app->post("/login/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/login.php" );
});

// Logout
$app->get("/logout/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/logout.php" );
});

// Management
$app->get("/manage/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/manage.php" );
});
$app->get("/manage/action/:action/:id/", function($action, $id) use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/manage.php" );
});
$app->post("/manage/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/manage.php" );
});

// Management
$app->get("/account/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/account.php" );
});
$app->post("/account/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/account.php" );
});

$app->get("/char/:name/", function($name) use ($app) {
	include("view/char.php");
});
$app->get("/char/:name/share/:shareID", function($name, $shareID) use ($app) {
	include("view/charShare.php");
});
$app->get("/char/:name/:pageType/", function($name, $pageType) use ($app) {
	include("view/char.php");
});

$app->get("/shares/", function() use ($app) {
    global $cookie_name, $cookie_time;
	include("view/shares.php");
});
$app->post("/shares/", function() use ($app) {
    global $cookie_name, $cookie_time;
	include("view/shares.php");
});
$app->get("/shares/action/:action/:id/", function($action, $id) use ($app) {
    global $cookie_name, $cookie_time;
	include( "view/shares.php" );
});

// EveInfo
$app->get("/item/:id/", function($id) use ($app) {
    global $oracleURL;
    include ("view/item.php" );
});

