<?php
if ($_POST) {

    @$email = $_POST["email"];

    $error = "An undefined error has occured....";
    if (!$email) {
        $error = "No email given";
    } elseif ($email) {
	$accounts = Db::query("select * from skq_users where email = :email", array(":email" => $email), 0);
	if (sizeof($accounts)) {
		foreach($accounts as $account) {
			$id = $account["id"];
			$email = $account["email"];
			$hash = gen_uuid();
			$username = $account["username"];
			Db::execute("update skq_users set change_hash = :hash, change_expiration = date_add(now(), interval 2 day) where id = :id", array(":id" => $id, ":hash" => $hash));
			Email::send($email, "SkillQ - Password reset", "A password reset has been requested for your account at the email $email, if you did this, click <a href='http://skillq.net/resetpassword/$hash'>here</a> to reset your password.  If you didn't do this, then do nothing at all and your account will remain unchanged.<br/><br/>Thank you,<br/>SkillQ<br/><br/>PS: In case you forgot your user name: $username");
		}
		$error = "A password reset has been emailed to $email";
        } else {
            $error = "No such user exists, try again";
        }
    }

    return $app->render("login.html", array("message" => $error, "type" => "error"));
}

if (User::isLoggedIn()) {
    return $app->redirect("/");
}

$app->render("forgotpassword.html");

/**
 * @return string
 */
function gen_uuid()
{
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for "time_low"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),
      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,
      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,
      // 48 bits for "node"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
}
