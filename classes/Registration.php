<?php
class Registration
{
	public static function checkRegistration($email, $username)
	{
		$check = Db::query("SELECT username, email FROM skq_users WHERE email = :email OR username = :username", array(":email" => $email, ":username" => $username), 0);
		return $check;
	}

	public static function registerUser($username, $password, $email)
	{
		global $siteName;
		$check = Db::queryField("SELECT count(*) count FROM skq_users WHERE email = :email OR username = :username", "count", array(":email" => $email, ":username" => $username), 0);
		if ($check == 0) {
			$hashedpassword = Password::genPassword($password);
			Db::query("INSERT INTO skq_users (username, password, email) VALUES (:username, :password, :email)", array(":username" => $username, ":password" => $hashedpassword, ":email" => $email));
			$subject = "$siteName Registration";
			$message = "Thank you, $username, for registering at $siteName";
			Email::create($email, $subject, $message);
			$message = "You have been registered, you should recieve a confirmation email in a moment, in the mean time you can click login and login!";
			Email::create("squizzc@gmail.com", "New user!", "$username has just created an account on skillq");
			return array("type" => "success", "message" => $message);
		}
		else
		{
			$message = "Username / email is already registered";
			return array("type" => "error", "message" => $message);
		}
	}
}
