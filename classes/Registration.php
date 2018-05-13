<?php

/**
 * Class Registration
 */
class Registration
{
    /**
     * @param string $email
     * @param string $username
     * @return array
     */
    public static function canRegister($email, $username)
    {
        $check = (int) Db::queryField("SELECT count(*) count FROM skq_users WHERE email = :email OR characterID = :username", "count", [":email" => $email, ":username" => $username], 0);
	return $check === 0;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $email
     * @return array
     */
    public static function registerUser($username, $password, $email)
    {
        global $siteName;
        $check = (int) Db::queryField(
          "SELECT count(*) count FROM skq_users WHERE email = :email OR characterID = :username",
          "count",
          array(":email" => $email, ":username" => $username),
          0
        );
        if ($check === 0) {
            $hashedpassword = Password::genPassword($password);
            Db::execute(
              "INSERT INTO skq_users (characterID, password, email) VALUES (:username, :password, :email)",
              array(":username" => $username, ":password" => $hashedpassword, ":email" => $email)
            );
            $subject = "$siteName Registration";
            $message = "Thank you, $username, for registering at $siteName";
            CreateEmail::create($email, $subject, $message);
            $message = "You have been registered, you should recieve a confirmation email in a moment, in the mean time you can click login and login!";
            CreateEmail::create("squizzc@gmail.com", "New user!", "$username has just created an account on skillq");

            return array("type" => "success", "message" => $message);
        } else {
            $message = "Username / email is already registered";

            return array("type" => "error", "message" => $message);
        }
    }
}
