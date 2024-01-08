<?php

if (!class_exists('whatsAppTellephant')) {
	class whatsAppTellephant
	{

		private static $instance = NULL;

		private $secret_key;
		private $api_endpoint;
		private $api_enabled;

		function __construct($client)
		{
			global $db, $log;

			$log->write($client, 'obc-whatsapp-test');
			$log->write(debug_backtrace(), 'obc-whatsapp-test');
			$this->client = $client;
			$this->secret_key = $client->whatsappClientKey;
			$this->api_endpoint = $client->whatsappClientEndpoint;
			$this->api_enabled = $client->whatsappClientStatus;
		}

		private function whatsapp_curl($method, $params = "", $post = array())
		{
			if (!$this->api_enabled)
				return 'WhatsApp sending is disabled';

			$header = array(
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
		public function sendMessage($msg_type, $post_params, $parameters)
		{
			global $log, $db;
			$textParameter = [];
			$mediaParameter = [];
			$buttonParameter = [];
			$textComponent = [];
			$mediaComponent = [];
			$component = array();
			$this->template_id = $this->getTemplateByName($msg_type);
			if (!empty($parameters['text'])) {
				foreach ($parameters['text'] as $param) {
					$textParameter[] = ["type" => "text", "text" => $param];
				}
			}
			if (!empty($parameters['media'])) {
				foreach ($parameters['media'] as $param) {
					$mediaParameter = ["type" => $param['type'], "url" => $param['value']];
				}
			}
			if (!empty($parameters['button'])) {
				foreach ($parameters['button'] as $key => $param) {
					$buttonParameter = [
						"type" => "button",
						"subType" => $param['sub_type'],
						"index" => $key,
						"parameters" => [
							[
								"type" => $param['type'],
								$param['type'] => $param['value']
							]
						]
					];
				}
			}
			if (!empty($textParameter)) {
				$textComponent = [
					"type" => "body",
					"parameters" => $textParameter
				];
			}
			if (!empty($mediaParameter)) {
				$mediaComponent = [
					"type" => "header",
					"parameters" => [
						[
							"type" => "media",
							"media" => $mediaParameter
						]
					]
				];
			}

			if (!empty($mediaComponent)) {
				array_push($component, $mediaComponent);
			}
			if (!empty($textComponent)) {
				array_push($component, $textComponent);
			}
			if (!empty($buttonParameter)) {
				array_push($component, $buttonParameter);
			}

			$parameters = array(
				'apikey' => $this->secret_key,
				'to' => '91' . $post_params['whatsapp_number'],
				"channels" => ["whatsapp"],
				'whatsapp' => [
					"contentType" => "template",
					"template" => [
						"templateId" => $this->template_id,
						"language" => "en",
						"components" => $component
					]
				],
			);


			$response = $this->whatsapp_curl('POST', '', $parameters);
			$response = json_decode($response);
			if (isset($response) && isset($response->messageId)) {
				$log->write("WhatsApp Successful\nParams: " . json_encode($post_params) . "\nResponse: " . json_encode($response), 'obc-whatsapp');
				return array('status' => 'success', 'message_id' => $response->messageId);
			} else {
				$log->write("WhatsApp Unsuccessful\nParams: " . json_encode($post_params) . "\nResponse: " . json_encode($response), 'obc-whatsapp');
				return isset($response->body->msg) ? $response->body->msg : $response;
			}
		}

		private function getTemplateByName($templateName)
		{
			global $db;

			$db->where('m.templateName', '%' . $templateName . '%', 'LIKE');
			$db->join(TBL_TEMPLATE_MASTER . ' m', 'm.templateId=w.masterTemplateId');
			$template = $db->objectBuilder()->getOne(TBL_TEMPLATE_WHATSAPP . ' w');
			return $template->template_name;
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
