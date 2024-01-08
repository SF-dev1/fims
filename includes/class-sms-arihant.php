<?php

if (!class_exists('smsArihant')) {
	class smsArihant
	{

		private $api_key;
		private $api_endpoint;
		private $api_enabled;
		private $service;
		private $client_id;

		function __construct($client)
		{
			global $log;

			$log->write($client, 'obc-sms-test');
			$log->write(debug_backtrace(), 'obc-sms-test');
			$this->client = $client;
			$this->api_key = $client->smsApiKey;
			$this->client_id = $client->smsClientSecret;
			$this->api_endpoint = $client->smsEndpoint;
			$this->api_enabled = $client->smsClientStatus;
		}

		private function sms_curl($method, $params = "", $post = array())
		{
			if (!$this->api_enabled)
				return json_encode(array('type' => 'error', 'message' => 'SMS sending is disabled'));

			$default_param = array(
				"ApiKey" => $this->api_key,
				"ClientId" => $this->client_id,
			);

			$default_query = "";
			$query = "";
			$header = array();
			if (!empty($params)) {
				foreach ($params as $key => $value)
					$query .= $key . '=' . urlencode($value) . '&';
				$query = rtrim($query, '& ');
			}

			if ($method == "POST") {
				$post_data = array_merge($default_param, $post);
				$header[] = 'Content-Type: application/json';
			} else {
				$default_query = '?';
				foreach ($default_param as $key => $value)
					$default_query .= $key . '=' . urlencode($value) . '&';
			}

			$url = $this->api_endpoint . $this->service . $default_query . $query;

			$request = curl_init(); // initiate curl object
			$curl_array = array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => false,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_HTTPHEADER => $header,
				CURLOPT_SSL_VERIFYPEER => false // Temp addition as of error: SSL CERTIFICATE PROBLEM UNABLE TO GET LOCAL ISSUER CERTIFICATE
			);

			if (!empty($post) && $method == "POST") {
				$curl_array += array(
					CURLOPT_POSTFIELDS => json_encode($post_data),
					CURLOPT_POST => true
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
				return 'Nothing was returned. Do you have a connection to SMS server?';

			return $response;
		}

		/**
		 *	@param 	string 	$message 			 - Content of WhatsApp
		 *	@param 	array 	$number 			 - Array of mobile number
		 *	@param 	string 	$sender 			 - Sender ID to send WhatsApp (Default: IMSDAS) (IMSDAS, FIMSIN, SSYLVI, STYFTR)
		 *
		 *	@return array|string $return - Contains various parameters
		 */

		public function sendMessage($data, $sender_id = 'IMSDAS')
		{
			global $log;

			$this->service = 'SendSMS';

			$numbers = implode(',', $data->mobile_number);

			if (isset($data->template)) {
				$data->message = $this->getTemplateByName($data->template, $data);
			}

			if ($data->message) {
				$post_params = array(
					"SenderId" => $sender_id,
					"Message" => $data->message,
					"MobileNumbers" => $numbers,
				);

				$response = $this->sms_curl('POST', '', $post_params);
				$response = json_decode($response);

				if (isset($response->ErrorCode) && $response->ErrorCode == "0") {
					$log->write("SMS Successful\nParams: " . json_encode($post_params) . "\nResponse: " . json_encode($response), 'obc-sms');
					return array('type' => 'success', 'message_id' => $response->Data[0]->MessageId);
				} else {
					$log->write("SMS Unsuccessful\nParams: " . json_encode($post_params) . "\nResponse: " . json_encode($response), 'obc-sms');
					return isset($response->ErrorDescription) ? $response->ErrorDescription : (array)$response;
				}
			}
			return array('type' => 'error', 'message' => 'Template Not Found');
		}

		public function get_sms_status($message_id)
		{
			$this->service = 'MessageStatus';

			$params = array(
				'MessageId' => $message_id
			);

			$response = $this->sms_curl('GET', $params);
			$response = json_decode($response);

			if ($response->ErrorCode == "0")
				return array('status' => $response->Data->Status, 'done_date' => $response->Data->DoneDate);
			else
				return isset($response->ErrorDescription) ? $response->ErrorDescription : $response;
		}

		private function getTemplateByName($templateName, $data)
		{
			global $db;

			$db->where('m.templateName', '%' . $templateName . '%', 'LIKE');
			$db->join(TBL_TEMPLATE_MASTER . ' m', 'm.templateId=w.masterTemplateId');
			$template = $db->objectBuilder()->getOne(TBL_TEMPLATE_SMS . ' w');

			if ($template) {
				$message = get_template_message($template->templateContent, $data->rmaID);
				return $message;
			}

			return null;
		}
	}
}
