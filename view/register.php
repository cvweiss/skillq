<?php
if ($_POST) {
    $username  = "";
    $password  = "";
    $password2 = "";
    $email     = "";

    @$username = $_POST["username"];
    @$password = $_POST["password"];
    @$password2 = $_POST["password2"];
    @$email = $_POST["email"];

    $error = null;
    if (!$password || !$password2) {
        $error = "Missing password, please retry.";
    } elseif (!$email) {
        $error = "Missing email, please retry.";
    } elseif ($password != $password2) {
        $error = "Passwords don't match, please retry.";
    } elseif (!$username) {
        $error = "Missing username, please retry.";
    }
    if ($error != null) {
        $app->render("register.html", array("message" => $error, "type" => "error"));
    }

    if ($username && $email && ($password == $password2)) // woohoo
    {
        // Lets check if the user isn't already registered
        if (Registration::canRegister($username, $email)) // He hasn't already registered, lets do et!
        {
            $message = Registration::registerUser($username, $password, $email);

            User::setLogin($username, $password, true);
            $app->view(new \Slim\Extras\Views\Twig());
            $twig = $app->view()->getEnvironment();
            $u    = User::getUserInfo();
            $twig->addGlobal("sessionusername", $u["username"]);
            $twig->addGlobal("sessionuserid", $u["id"]);
            $twig->addGlobal("sessionadmin", $u["admin"]);
            $twig->addGlobal("sessionmoderator", (bool) $u["moderator"]);
            $app->redirect("/manage/");

            return $app->render("register.html", array("type" => $message["type"], "message" => $message["message"]));
        } else {
		return $app->render("register.html", array("type" => "error", "message" => "Unable to register with that username or email."));
	}
    }
}

$app->render("register.html");
