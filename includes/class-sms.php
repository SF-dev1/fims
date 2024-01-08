<?php

if (!class_exists('SMS')) {
	class SMS
	{

		private static $instance = NULL;

		function __construct($client = "1")
		{
			global $db;

			$db->where('smsClientId', $client);
			$client = $db->objectBuilder()->getOne(TBL_CLIENTS_SMS, array('smsClientName', 'smsApiKey', 'smsClientSecret', 'smsEndpoint', 'smsClientStatus'));
			$this->setClient($client);
		}

		/**
		 *
		 *	@param 	string 	$message 			 - Content of SMS
		 *	@param 	array 	$number 			 - Array of mobile number
		 *	@param 	string 	$sender 			 - Sender ID to send SMS (Default: IMSDAS) (IMSDAS, FIMSIN, SSYLVI, STYFTR)
		 *
		 *	@return array|string $return - Contains various parameters
		 */

		private function setClient($client)
		{
			include_once(ROOT_PATH . '/includes/class-sms-' . strtolower($client->smsClientName) . '.php');
			$newClass = 'sms' . ucfirst($client->smsClientName);
			$this->client = new $newClass($client);
			return $this->client;
		}

		public function caller($to_call, $arg = '')
		{
			if (is_callable([$this->client, $to_call])) {
				$this->client->$to_call($arg[0], $arg[1]);
			}
		}


		public static function getInstance()
		{
			if (!self::$instance) {
				self::$instance = new SMS;
			}
			return self::$instance;
		}
	}
}
