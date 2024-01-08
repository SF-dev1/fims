<?php

if (!class_exists('whatsAppEngati')) {
	class whatsAppEngati
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
		}

		private function whatsapp_curl($method, $params = "", $post = array())
		{
			if (!$this->api_enabled)
				return 'WhatsApp sending is disabled';

			$header = array(
				'Authorization: Basic ' . $this->secret_key,
				"Content-Type: application/json"
			);

			$url = $this->api_endpoint . $this->client_key . '/template';
			$request = curl_init();

			// ccd($this->client_key);
			curl_setopt($request, CURLOPT_URL, $url);
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($request, CURLOPT_POST, 1);
			curl_setopt($request, CURLOPT_HTTPHEADER, $header);
			// curl_setopt($request, CURLOPT_POSTFIELDS, "{\n\t\"phoneNumber\": \"918780774040\",\n\t\"payload\": {\n\t\t\"components\": [\n\t\t\t{\n\t\t\t\t\"payload\": \"body\",\n\t\t\t\t\"parameters\": [\n\t\t\t\t\t{\n\t\t\t\t\t\t\"type\": \"text\",\n\t\t\t\t\t\t\"text\": \"Ishan\"\n\t\t\t\t\t},\n\t\t\t\t\t{\n\t\t\t\t\t\t\"type\": \"text\",\n\t\t\t\t\t\t\"text\": \"31032023\"\n\t\t\t\t\t},\n\t\t\t\t\t{\n\t\t\t\t\t\t\"type\": \"text\",\n\t\t\t\t\t\t\"text\": \"Xpressbees\"\n\t\t\t\t\t}\n\t\t\t\t]\n\t\t\t},\n\t\t\t{\n                \"type\": \"button\",\n                \"sub_type\": \"url\",\n                \"index\": \"0\",\n                \"parameters\": [\n                    {\n                        \"type\": \"text\",\n                        \"text\": \"0123456789\"\n                    }\n                ]\n            }\n\t\t],\n\t\t\"namespace\": \"d29bcac5_439b_4f83_bd36_9f95cffc483f\",\n\t\t\"name\": \"rma_pickup\",\n\t\t\"language\": {\n\t\t\t\"policy\": \"deterministic\",\n\t\t\t\"code\": \"en\"\n\t\t}\n\t}\n}");
			// curl_setopt($request, CURLOPT_HTTPHEADER, $header);

			if (!empty($post) && $method == "POST") {
				curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($post));
			}

			$response = curl_exec($request);


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
		// public function sendMessage($msg_type, $post_params,$parameters){
		public function sendMessage($data)
		{
			global $log, $db;

			$textParameter = [];
			$mediaParameter = [];
			$buttonParameter = [];
			$textComponent = [];
			$mediaComponent = [];
			$component = array();
			$this->template_id = $this->getTemplateByName($data->type);

			if ($this->template_id) {
				if (!empty($data->parameter['text'])) {
					foreach ($data->parameter['text'] as $param) {
						$textParameter[] = ["type" => "text", "text" => $param];
					}
				}
				if (!empty($data->parameter['media'])) {
					foreach ($data->parameter['media'] as $param) {
						$mediaParameter = ["type" => $param['type'], "url" => $param['value']];
					}
				}
				if (!empty($data->parameter['button'])) {
					foreach ($data->parameter['button'] as $key => $param) {
						$buttonParameter = [
							"type" => "button",
							"sub_type" => $param['sub_type'],
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
					'phoneNumber' => $data->post_params['to'],
					'payload' => [
						"namespace" => "d29bcac5_439b_4f83_bd36_9f95cffc483f",
						"name" => $data->type,
						"language" => [
							"policy" => "deterministic",
							"code" => "en",
						],
						"components" =>
						$component
					],
				);


				$log->write($data->type, 'obc-whatsapp-test');
				$log->write($data->post_params, 'obc-whatsapp-test');
				$log->write($this->templates, 'obc-whatsapp-test');

				$this->template_id = $data->type;
				$response = $this->whatsapp_curl('POST', '', $parameters);
				ccd($response);
				$response = json_decode($response);
				if (isset($response) && isset($response->responseObject->message_id)) {
					$log->write("WhatsApp Successful\nParams: " . json_encode($data->post_params) . "\nResponse: " . json_encode($response), 'obc-whatsapp');
					return array('status' => 'success', 'message_id' => $response->responseObject->message_id);
				} else {
					$log->write("WhatsApp Unsuccessful\nParams: " . json_encode($data->post_params) . "\nResponse: " . json_encode($response), 'obc-whatsapp');
					return isset($response->body->msg) ? $response->body->msg : $response;
				}
			} else {
				$log->write("WhatsApp Unsuccessful\nParams: " . json_encode($data->post_params) . "\nResponse: " . json_encode($response), 'obc-whatsapp');
				return isset($response->body->msg) ? $response->body->msg : $response;
			}
		}

		private function getTemplateByName($templateName)
		{
			global $db;
			$db->where('m.templateName', '%' . $templateName . '%', 'LIKE');
			$db->join(TBL_TEMPLATE_MASTER . ' m', 'm.templateId=w.masterTemplateId');
			$template = $db->objectBuilder()->getOne(TBL_TEMPLATE_WHATSAPP . ' w');
			if ($template) {
				return $template->templateName;
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
