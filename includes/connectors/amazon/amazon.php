<?php

// AMAZON SP-API

// use \GuzzleHttp\Psr7\Query;
// use GuzzleHttp\Exception\ClientException;
// use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\MultipartStream;
// use GuzzleHttp\Psr7\Request;
// use GuzzleHttp\Psr7\Response;
// use GuzzleHttp\Client;
if (!class_exists('amazon')) {
	class amazon
	{
		private $account;
		private $endpoint;
		private $access_token;
		private $refresh_offset_sec = 60;
		private $resourceMethod;
		private $resourcePath;
		private $rateLimit = 0;
		private $rateBursted = false;
		private static array $countryMap = [
			'CA' => [
				'name'   => 'Canada',
				'id'     => 'A2EUQ1WTGCTBG2',
				'region' => 'us-east-1',
				'url'    => 'https://sellercentral.amazon.ca',
				'endpoint' => 'sellingpartnerapi-na.amazon.com',
			],
			'US' => [
				'name'   => 'United States of America',
				'id'     => 'ATVPDKIKX0DER',
				'region' => 'us-east-1',
				'url'    => 'https://sellercentral.amazon.com',
				'endpoint' => 'sellingpartnerapi-na.amazon.com',
			],
			'MX' => [
				'name'   => 'Mexico',
				'id'     => 'A1AM78C64UM0Y8',
				'region' => 'us-east-1',
				'url'    => 'https://sellercentral.amazon.com.mx',
				'endpoint' => 'sellingpartnerapi-na.amazon.com',
			],
			'BR' => [
				'name'   => 'Brazil',
				'id'     => 'A2Q3Y263D00KWC',
				'region' => 'us-east-1',
				'url'    => 'https://sellercentral.amazon.com.br',
				'endpoint' => 'sellingpartnerapi-na.amazon.com',
			],
			'ES' => [
				'name'   => 'Spain',
				'id'     => 'A1RKKUPIHCS9HS',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral-europe.amazon.com',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'GB' => [
				'name'   => 'United Kingdom',
				'id'     => 'A1F83G8C2ARO7P',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral-europe.amazon.com',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'FR' => [
				'name'   => 'France',
				'id'     => 'A13V1IB3VIYZZH',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral-europe.amazon.com',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'BE' => [
				'name'   => 'Belgium',
				'id'     => 'AMEN7PMS3EDWL',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.com.be',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'NL' => [
				'name'   => 'Netherlands',
				'id'     => 'A1805IZSGTT6HS',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.nl',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'DE' => [
				'name'   => 'Germany',
				'id'     => 'A1PA6795UKMFR9',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral-europe.amazon.com',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'IT' => [
				'name'   => 'Italy',
				'id'     => 'APJ6JRA9NG5V4',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral-europe.amazon.com',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'SE' => [
				'name'   => 'Sweden',
				'id'     => 'A2NODRKZP88ZB9',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.se',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'PL' => [
				'name'   => 'Poland',
				'id'     => 'A1C3SOZRARQ6R3',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.pl',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'EG' => [
				'name'   => 'Egypt',
				'id'     => 'ARBP9OOSHTCHU',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.eg',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'TR' => [
				'name'   => 'Turkey',
				'id'     => 'A33AVAJ2PDY3EV',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.com.tr',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'SA' => [
				'name'   => 'Saudi Arabia',
				'id'     => 'A17E79C6D8DWNP',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.sa',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'AE' => [
				'name'   => 'United Arab Emirates',
				'id'     => 'A2VIGQ35RCS4UG',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.ae',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'IN' => [
				'name'   => 'India',
				'id'     => 'A21TJRUUN4KGV',
				'region' => 'eu-west-1',
				'url'    => 'https://sellercentral.amazon.in',
				'endpoint' => 'sellingpartnerapi-eu.amazon.com',
			],
			'SG' => [
				'name'   => 'Singapore',
				'id'     => 'A19VAU5U5O7RUS',
				'region' => 'us-west-2',
				'url'    => 'https://sellercentral.amazon.sg',
				'endpoint' => 'sellingpartnerapi-fe.amazon.com',
			],
			'AU' => [
				'name'   => 'Australia',
				'id'     => 'A39IBJ37TRP1C6',
				'region' => 'us-west-2',
				'url'    => 'https://sellercentral.amazon.com.au',
				'endpoint' => 'sellingpartnerapi-fe.amazon.com',
			],
			'JP' => [
				'name'   => 'Japan',
				'id'     => 'A1VC38T7YXB528',
				'region' => 'us-west-2',
				'url'    => 'https://sellercentral.amazon.co.jp',
				'endpoint' => 'sellingpartnerapi-fe.amazon.com',
			]
		];
		public $marketplace;

		function __construct($account)
		{
			$this->account = $account;
			$this->access_token = $this->set_access_token();
			$this->endpoint = $this->set_endpoint();
			$this->marketplace = $this->set_marketplace();
			// $this->region = $account->region;
		}

		private function get_access_token()
		{
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.amazon.com/auth/o2/token',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => 'grant_type=refresh_token&refresh_token=' . $this->account->refresh_token . '&client_id=' . $this->account->client_id . '&client_secret=' . $this->account->client_secret,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/x-www-form-urlencoded'
				),
			));

			$response = curl_exec($curl);
			curl_close($curl);

			return json_decode($response);
		}

		private function set_access_token()
		{
			global $db;

			if (empty($this->account->access_token) || time() > strtotime($this->account->token_expiry) || time() > (strtotime($this->account->token_expiry) - $this->refresh_offset_sec)) {
				$token = $this->get_access_token();
				if (isset($token->access_token)) {
					$db->where('account_id', $this->account->account_id);
					$db->update(TBL_AZ_ACCOUNTS, array('access_token' => $token->access_token, 'token_expiry' => date('Y-m-d H:i:s', strtotime("+1 hour"))));
					return $token->access_token;
				}
			} else {
				return $this->account->access_token;
			}
		}

		private function set_endpoint()
		{
			return isset(self::$countryMap[$this->account->region]) ? self::$countryMap[$this->account->region]['endpoint'] : null;
		}

		private function set_marketplace()
		{
			return isset(self::$countryMap[$this->account->region]) ? self::$countryMap[$this->account->region]['id'] : null;
		}

		public function request($resourceMethod, $resourcePath, $queryParams = [], $formParams = [], $headerParams = [], $multipart = false)
		{
			global $log;

			if ($this->rateBursted) {
				$log->write($resourcePath . " :: " . $resourceMethod . " :: QuotaExceeded", "sp_api_request");
				return false;
			}

			$this->resourceMethod = $resourceMethod;
			$this->resourcePath = $resourcePath;

			$query = '';
			$httpBody = '';

			if ($queryParams)
				$query = \GuzzleHttp\Psr7\Query::build($queryParams);

			$defaultHeaders = array(
				'Accept: application/json',
				'x-amz-access-token: ' . $this->access_token
			);

			$headers = array_merge(
				$defaultHeaders,
				$headerParams
			);
			// for model (json/xml)
			if (count($formParams) > 0) {
				if ($multipart) {
					$multipartContents = [];
					foreach ($formParams as $formParamName => $formParamValue) {
						$formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
						foreach ($formParamValueItems as $formParamValueItem) {
							$multipartContents[] = [
								'name' => $formParamName,
								'contents' => $formParamValueItem
							];
						}
					}
					// for HTTP post (form)
					$httpBody = new MultipartStream($multipartContents);
				} elseif ($headers['Content-Type'] === 'application/json') {
					$httpBody = \GuzzleHttp\json_encode($formParams);
				} else {
					// for HTTP post (form)
					$httpBody = \GuzzleHttp\Psr7\Query::build($formParams);
				}
			}

			sleep($this->rateLimit);

			$curl = curl_init();
			$curlArray = array(
				CURLOPT_URL => 'https://' . $this->endpoint . '/' . $this->resourcePath . ($query ? "?{$query}" : ''),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => $this->resourceMethod,
				CURLOPT_HEADER => true,
				CURLOPT_HTTPHEADER => $headers
			);
			if (!empty($formParams)) {
				$headers[] = 'Content-Type: application/json';
				$curlArray[CURLOPT_POSTFIELDS] = json_encode($formParams);
				$curlArray[CURLOPT_HTTPHEADER] = $headers;
			}
			curl_setopt_array($curl, $curlArray);

			$response = curl_exec($curl);
			$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$response_headers = substr($response, 0, $header_size);
			$response_body = substr($response, $header_size);
			curl_close($curl);

			$headers = explode("\n", $response_headers);
			foreach ($headers as $header) {
				list($key, $value) = explode(':', $header, 2);
				$headers[trim($key)] = trim($value);
			}

			if (strpos($response, 'QuotaExceeded') !== FALSE)
				$this->rateBursted = true;

			$this->rateLimit = $headers['x-amzn-RateLimit-Limit'];

			$log->write('https://' . $this->endpoint . '/' . $this->resourcePath . ($query ? "?{$query}" : '') . "\nhttpcode: " . $httpcode . "\nPayload: " . json_encode($formParams) . "\nResponse: " . $response_body, "sp_api_request");

			if ($httpcode === 200 || $httpcode === 202) {
				return $response_body;
			} else {
				return (array_merge(array("type" => "error"), empty(json_decode($response, true)) ? [] : json_decode($response, true)));
			}
		}

		public function GetOrders($params)
		{
			$response = $this->request(
				'GET',
				'orders/v0/orders',
				$params
			);

			if (!isset($response['type'])) {
				$response = json_decode($response, true);
				$orders = $response['payload']['Orders'];
			}
			return $orders;
		}

		public function GetOrder($orderId)
		{
			$response = $this->request(
				'GET',
				'orders/v0/orders/' . $orderId
			);
			if (!isset($response['type'])) {
				$response = json_decode($response, true);
				$order = $response['payload'];
			}
			return $order;
		}

		public function getOrderNextToken($token)
		{
		}

		public function ListOrderItems($orderId)
		{
			$response = $this->request(
				'GET',
				'orders/v0/orders/' . $orderId . '/orderItems'
			);
			if (!isset($response['type'])) {
				$response = json_decode($response, true);
				$items = $response['payload']['OrderItems'];
			}
			return $items;
		}

		public function ListPickupSlots($order_id, $packageDetails)
		{
			$listHandoverSlotsRequest = array_merge(
				[
					"amazonOrderId" => $order_id,
					"marketplaceId" => $this->marketplace,
				],
				$packageDetails
			);

			$response = $this->request(
				'POST',
				'easyShip/2022-03-23/timeSlot',
				[],
				$listHandoverSlotsRequest
			);
			if (!isset($response['type'])) {
				$response = json_decode($response, true);
				var_dump($response);
				// $items = $response['payload']['OrderItems'];
			}
			return $items;
		}

		public function createReport($reportTypes, $startTime, $endTime)
		{
			$data = array(
				"reportType" => $reportTypes,
				"marketplaceIds" => [$this->marketplace],
				"dataStartTime" => date("Y-m-d\TH:i:s.v\Z", strtotime($startTime)),
				"dataEndTime" => date("Y-m-d\TH:i:s.v\Z", strtotime($endTime))
			);
			$url = "reports/2021-06-30/reports";
			$response = ($this->request("POST", $url, [], $data));
			return $response;
		}

		public function requestReports($reportTypes, $pageSize)
		{
			$data = array(
				"reportTypes" => $reportTypes,
				"marketplaceIds" => $this->marketplace,
				"pageSize" => $pageSize
			);

			$url = "reports/2021-06-30/reports";
			$response = ($this->request("GET", $url, $data));

			if (!isset($response['type'])) {
				$response = json_decode($response, true);
			}

			return $response;
		}

		public function downloadReports($documentId)
		{
			$url = "reports/2021-06-30/documents/" . $documentId;
			$response = ($this->request("GET", $url));

			if (!isset($response['type'])) {
				$response = json_decode($response, true);
			}

			return $response;
		}

		public function getReportDocumentById($reportId)
		{
			$url = "reports/2021-06-30/reports/" . $reportId;
			$response = ($this->request("GET", $url));

			if (!isset($response['type'])) {
				$response = json_decode($response, true);
			}

			return $response;
		}
	}
}
