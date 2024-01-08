<?php

if (!class_exists('whatsApp')) {
	class whatsApp
	{

		private static $instance = NULL;
		public $client;

		function __construct($client = NULL, $params = NULL, $type = null, $parameter = null)
		{
			global $db, $log;

			if (is_null($client))
				return;

			$db->where('whatsappClientId', $client);
			$client = $db->objectBuilder()->getOne(TBL_CLIENTS_WHATSAPP, array('whatsappClientId', 'whatsappClientName', 'whatsappClientKey', 'whatsappClientSecret', 'whatsappClientEndpoint', 'whatsappClientStatus', 'whatsappClientAppId', 'whatsappClientAuthToken'));
			$this->setClient($client, $params, $type, $parameter);
		}

		private function setClient($client, $params, $type, $parameter)
		{
			include_once(ROOT_PATH . '/includes/class-whatsapp-' . $client->whatsappClientName . '.php');
			$newClass = 'whatsApp' . ucfirst($client->whatsappClientName);
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
				self::$instance = new whatsApp;
			}
			return self::$instance;
		}
	}
}
