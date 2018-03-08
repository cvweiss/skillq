<?php

use zkillboard\crestsso\CrestSSO;

global $clientID, $secretKey, $callbackURL, $scopes;

$sso = new CrestSSO($clientID, $secretKey, $callbackURL, $scopes);
$loginURL = $sso->getLoginURL($_SESSION);
$app->redirect($loginURL);
