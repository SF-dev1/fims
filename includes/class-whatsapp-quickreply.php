<?php

if (!class_exists('whatsAppQuickreply')) {
	class whatsAppQuickreply
	{

		private static $instance = NULL;

		private $site;
		private $secret_key;
		private $client_key;
		private $api_endpoint;
		private $api_enabled;
		private $templates;
		private $template_id;

		function __construct($client)
		{
			$this->secret_key = $client->whatsappClientSecret;
			$this->client_key = $client->whatsappClientKey;
			$this->api_endpoint = $client->whatsappClientEndpoint;
			$this->templates = json_decode(get_option('whatsapp_' . $client->whatsappClientName . '_template_ids'), true);
			$this->api_enabled = $client->whatsappClientStatus;
			// var_dump($this);
		}

		private function whatsapp_curl($method, $params = "", $post = array())
		{
			if (!$this->api_enabled)
				return 'WhatsApp sending is disabled';

			$header = array(
				"secret-key: " . $this->secret_key,
				"client-id: " . $this->client_key,
				"Content-Type: application/json"
			);

			$url = $this->api_endpoint . '?templateId=' . $this->template_id;

			$request = curl_init(); // initiate curl object
			$curl_array = array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_HTTPHEADER => $header,
			);

			if (!empty($post) && $method == "POST") {
				$curl_array += array(
					CURLOPT_POSTFIELDS => json_encode($post),
				);
			}

			curl_setopt_array($request, $curl_array);
			$response = curl_exec($request); // execute curl fetch and store results in $response
			if (curl_errno($request)) {
				$error_msg = curl_error($request);
			}
			curl_close($request); // close curl object

			if (isset($error_msg))
				return $error_msg;

			if (!$response)
				return 'Nothing was returned. Do you have a connection to WhatsApp server?';

			return $response;
		}

		/**
		 *	@param 	string 	$message 			 - Content of WhatsApp
		 *	@param 	array 	$number 			 - Array of mobile number
		 *	@param 	string 	$sender 			 - Sender ID to send WhatsApp (Default: IMSDAS) (IMSDAS, FIMSIN, SSYLVI, STYFTR)
		 *
		 *	@return array|string $return - Contains various parameters
		 */
		public function sendMessage($msg_type, $post_params)
		{
			global $log, $db;

			$log->write($msg_type, 'obc-whatsapp-test');
			$log->write($post_params, 'obc-whatsapp-test');
			$log->write($this->templates, 'obc-whatsapp-test');

			$this->template_id = $msg_type;

			$response = $this->whatsapp_curl('POST', '', $post_params);
			$response = json_decode($response);
			if (isset($response) && $response->msg == "Campaign template sent") {
				$log->write("WhatsApp Successful\nParams: " . json_encode($post_params) . "\nResponse: " . json_encode($response), 'obc-whatsapp');
				return array('status' => 'success');
			} else {
				$log->write("WhatsApp Unsuccessful\nParams: " . json_encode($post_params) . "\nResponse: " . json_encode($response), 'obc-whatsapp');
				return isset($response->body->msg) ? $response->body->msg : $response;
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
