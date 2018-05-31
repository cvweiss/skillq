<?php

require_once '../init.php';

use cvweiss\Guzzler;

$minute = date('Hi');
if ($minute >= 1100 && $minute <= 1105) {
    $redis->setex('skq:tqStatus', 300, 'OFFLINE'); // Just in case the result is cached on their end as online
    $redis->setex('skq:tqCount', 300, 0);
} else {
    $guzzler = new Guzzler();
    $guzzler->call("https://esi.evetech.net/v1/status/", "success", "fail");
    $guzzler->finish();
}

function success($guzzler, $params, $content)
{
    global $redis;

    if ($content == "") return;

    $root = json_decode($content, true);

    $loggedIn = (int) @$root['players'];
    $redis->set('skq:tqCountInt', $loggedIn);

    $serverStatus = $loggedIn > 100 ? 'ONLINE' : 'OFFLINE';
    $loggedIn = $loggedIn == 0 ? $serverStatus : number_format($loggedIn, 0);

    $redis->setex('skq:tqStatus', 300, $serverStatus);
    $redis->setex('skq:tqCount', 300, $loggedIn);
}

function fail($guzzler, $params, $ex)
{
    global $redis;

    $redis->setex('skq:tqStatus', 300, 'UNKNOWN');
    $redis->setex('skq:tqCount', 300, 0);
    $redis->setex('skq:tqCountInt', 300, 0);
}
