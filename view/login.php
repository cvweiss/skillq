<?php

use zkillboard\eveonlineoauth2\EveOnlineSSO;

global $clientID, $secretKey, $callbackURL, $scopes;

$sso = new EveOnlineSSO($clientID, $secretKey, $callbackURL, $scopes);
$loginURL = $sso->getLoginURL($_SESSION);
$app->redirect($loginURL);
