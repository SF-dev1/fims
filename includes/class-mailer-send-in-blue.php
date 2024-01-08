<?php

use PHPMailer\PHPMailer\PHPMailer;

require 'class-mailer-php.php';
require 'class-mailer-smtp.php';
if (!class_exists('mailerSendInBlue')) {
	class mailerSendInBlue
	{
		protected $mail;

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

		/**
		 *	@param 	array 	$data->to 			 - [To name, Email ]		- Required
		 *	@param 	array 	$data->from 		 - [From name, Email ]		- Default [SKMI Enterprise, fims@skmienterprise.com]
		 *	@param 	string 	$data->body			 - Email body HTML			- Required
		 *	@param	array	$data->attachments	 - [attachments files]
		 *	@param	array	$data->reply_to		 - [Reply To name, Email]
		 *	@param	array	$data->to_orthers	 - [cc {name, email}/ bcc {name, email}]
		 *	@param	string	$data->subject		 - Subject Of Mail			- Required
		 *
		 *	@return bool|string success => true | error => error message
		 */
		public function send($data)
		{

			$this->mail->clearAttachments();
			$this->mail->clearAllRecipients();

			//Set who the message is to be sent from
			if (isset($data->from)) {
				$this->mail->setFrom($data->from["email"], (empty($data->from["name"]) ? $data->from["email"] : $data->from["name"]));
			} else {
				$this->mail->setFrom('fims@skmienterprise.com', 'SKMI Enterprise');
			}

			//Set an alternative reply-to address
			if (isset($data->reply_to) && $data->reply_to != "")
				$this->mail->addReplyTo($data->reply_to);
			else
				$this->mail->addReplyTo('no-reply@skmienterprise.com', 'SKMI Enterprise');

			//Set who the message is to be sent to
			if (count($data->to) > 1) {
				$i = 0;
				foreach ($data->to as $to_email) {
					$this->mail->addAddress($to_email["email"], (empty($to_email["name"]) ? $to_email["email"] : $to_email["name"]));
				}
			} else {
				$this->mail->addAddress($data->to[0]["email"], (empty($data->to[0]["name"]) ? $data->to[0]["email"] : $data->to[0]["name"]));
			}

			//Set cc & bcc
			if (isset($data->to_others) && !empty($data->to_others)) {
				if (isset($data->to_others->cc) && !empty($data->to_others->cc))
					$this->mail->addCC($data->to_others->cc);

				if (isset($data->to_others->bcc) && !empty($data->to_others->bcc))
					$this->mail->addBCC($data->to_others->bcc);
			}

			//Set the subject line
			$this->mail->Subject = $data->subject;

			// Set email format to HTML
			$this->mail->isHTML(true);

			$this->mail->msgHTML($data->email_body);

			//Attachment files
			if (isset($data->attachments) && !is_null($data->attachments)) {
				foreach ($data->attachments as $attachment) {
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
	}
}
