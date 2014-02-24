<?php

/**
 * Class CreateEmail
 */
class CreateEmail
{
    /**
     * @param string $email
     * @param string $subject
     * @param string $body
     */
    public static function create($email, $subject, $body)
    {
        Db::execute(
          "insert into skq_emails (recipient, subject, content) values (:e, :s, :c)",
          array(":e" => $email, ":s" => $subject, ":c" => $body)
        );
    }
}
