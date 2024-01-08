<?php


//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Asia/Kolkata');

require 'class-mailer-exception.php';
require 'class-mailer-php.php';
require 'class-mailer-smtp.php';

/**
 * 
 */
class mailer
{

	protected $mail;

	/*** Declare instance ***/
	private static $instance = NULL;

	function __construct()
	{
		//Create a new PHPMailer instance
		$this->mail = new PHPMailer;

		//Tell PHPMailer to use SMTP
		$this->mail->isSMTP();

		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$this->mail->SMTPDebug = 0;

		//Whether to use SMTP authentication
		$this->mail->SMTPAuth = true;

		//Set the hostname of the mail server
		$this->mail->Host = get_option('email_host');

		//Set the SMTP port number - likely to be 25, 465 or 587
		$this->mail->Port = 587;

		//Username to use for SMTP authentication
		$this->mail->Username = get_option('email_username');

		//Password to use for SMTP authentication
		$this->mail->Password = get_option('email_password');
	}

	public function send($subject, $to, $to_name, $to_others = "", $body = NULL, $reply_to = "", $attachments = array())
	{

		//Set who the message is to be sent from
		$this->mail->setFrom('fims@skmienterprise.com', 'SKMI Enterprise');

		//Set an alternative reply-to address
		if ($reply_to == "")
			$this->mail->addReplyTo('no-reply@skmienterprise.com', 'SKMI Enterprise');
		else
			$this->mail->addReplyTo($reply_to);

		//Set who the message is to be sent to
		if (is_array($to)) {
			$i = 0;
			foreach ($to as $to_email) {
				if (!is_array($to_name))
					$to_name = "";
				else
					$to_name = $to_name[$i];

				$this->mail->addAddress($to_email, $to_name);
			}
		} else {
			$this->mail->addAddress($to, $to_name);
		}

		//Set cc & bcc
		if (!empty($to_others)) {
			if (isset($to_others['cc']))
				$this->mail->addCC($to_others['cc']);

			if (isset($to_others['bcc']))
				$this->mail->addBCC($to_others['bcc']);
		}

		//Set the subject line
		$this->mail->Subject = $subject;

		// Set email format to HTML
		$this->mail->isHTML(true);

		//Set Message in the HTML Content/Plain text
		$this->mail->msgHTML($body);

		//Attachment files
		if (!is_null($attachments)) {
			foreach ($attachments as $attachment) {
				$this->mail->addAttachment($attachment); // FULL PATH TO FILE
			}
		}

		//send the message, check for errors
		if (!$this->mail->send()) {
			return 'Email Error: ' . $this->mail->ErrorInfo;
		} else {
			return true;
		}
	}

	public function clearAttachments()
	{
		$this->mail->clearAttachments();
	}

	public function clearAllRecipients()
	{
		$this->mail->clearAllRecipients();
	}

	/**
	 *
	 * Return current instance or create new instance
	 *
	 * @return object (PDO)
	 *
	 * @access public
	 *
	 */
	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new mailer;
		}
		return self::$instance;
	}
}


// USAGE
// $mailer = new mailer();
// $mailer->send('This a Subject Line', 'ishan576@gmail.com', 'Ishan Kukadia', 'Hi, <br />This is the mail content. <br /> And oh yes it supports HTML tags. <br /> Hurry!!!');
// $mailer->send('This a Subject Line', 'test-i9wb5@mail-tester.com', 'test-i9wb5@mail-tester.com', 'Hi, <br />This is the mail content. <br /> And oh yes it supports HTML tags. <br /> Hurry!!!');