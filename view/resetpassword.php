<?php
if ($_POST) {
    $password  = "";
    $password2 = "";
    $hash = "";

    @$password = $_POST["password"];
    @$password2 = $_POST["password2"];
    @$hash = $_POST["hash"];

    $error = null;
    if (!$password || !$password2) {
        $error = "Missing password, please retry.";
    } elseif ($password != $password2) {
        $error = "Passwords don't match, please retry.";
    } elseif ($hash == "") {
	$error = "Invalid hash...";
    }
    if ($error != null) {
        $app->render("resetpassword.html", array("message" => $error, "type" => "error"));
    }

    $hashedPassword = Password::genPassword($password);
    $aff = Db::execute("update skq_users set password = :hashedpw, change_hash = null, change_expiration = null where change_hash = :hash", array("hashedpw" => $hashedPassword, ":hash" => $hash));
    if ($aff) $error = "Your password has been reset, go ahead and log in!";
    else $error = "Unable to reset your password, your link is either too old or invalid.";
    return $app->render("resetpassword.html", array("type" => "error", "message" => $error));
}

$app->render("resetpassword.html", array("hash" => $hash));
