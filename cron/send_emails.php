<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once "../init.php";

$emails = Db::query("select * from skq_emails where isSent = 0", array(), 0);
foreach ($emails as $email) {
	$emailID = $email["emailID"];
	$to      = $email["recipient"];
	$subject = $email["subject"];
	$body    = $email["content"];

	$mail = new PHPMailer();
	$mail->Debugoutput = 'error_log';
	$mail->isSMTP();
	$mail->Host = 'smtp.googlemail.com';
	$mail->SMTPAuth = true;
	$mail->Username = $email_id;
	$mail->Password = $email_pw;
	$mail->SMTPSecure = 'ssl';
	$mail->Port = 465;

	$mail->AddAddress($to);
	$mail->Subject = $subject;
	$mail->setFrom($email_id, "SkillQ");
	$mail->MsgHTML($body);

	Db::execute("update skq_emails set isSent = :isSent, sentTime = now() where emailID = :emailID", [":emailID" => $emailID, ':isSent' => ($mail->Send() ? 1 : -1)]);
	Util::out("Email sent to $to");
}
