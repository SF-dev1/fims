<?php
//Import the PHPMailer class into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//SMTP needs accurate times, and the PHP time zone MUST be set
//This should be done in your php.ini, but this is how to do it if you don't have access to that
date_default_timezone_set('Asia/Kolkata');

require 'class-mailer-exception.php';
//require 'class-mailer-php.php';
//require 'class-mailer-smtp.php';

/**
 * 
 */
class mailer
{

	protected $mail;

	/*** Declare instance ***/
	private static $instance = NULL;

	function __construct($client = "1")
	{
		//Create a new PHPMailer instance
		global $db;

		$db->where('emailClientId', $client);
		$client = $db->objectBuilder()->getOne(TBL_CLIENTS_EMAIL, array('emailClientName', 'emailClientKey', 'emailClientSecret', 'emailClientFromEmail', 'emailClientFromName', 'emailClientHost'));
		$this->setClient($client);
	}


	private function setClient($client)
	{
		$className = str_replace(array("-", "  ", " "), array("", " ", "-"), $client->emailClientName);
		include_once(ROOT_PATH . '/includes/class-mailer-' . strtolower($className) . '.php');

		$newClass = 'mailer' . ucfirst(str_replace('-', '', $className));
		$this->client = new $newClass($client);
		return $this->client;
	}

	public function caller($to_call, $arg = '')
	{
		if (is_callable([$this->client, $to_call])) {
			$this->client->$to_call($arg[0], $arg[1]);
		}
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