<?php

if (!class_exists('ajio_dashboard')) {
	class ajio_dashboard extends ajio
	{

		function __construct($account)
		{
			$this->account = $account;
			$this->is_login = $account->login_status;
			$this->force = false;
			$this->reties = 3;

			parent::__construct($this->account);
		}

		function login()
		{
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://seller.ajio.com/malekith/hawkeye/v1/login',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => '{"clientId":"string","username":"' . $this->account->account_username . '","password":"' . $this->account->account_password . '"}',
				CURLOPT_HTTPHEADER => array(
					'Accept: application/json, text/plain, */*',
					'Content-Type: application/json'
				),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			return $response;
		}

		function check_login()
		{
			global $db, $log;

			if (empty($this->account->account_token) || is_null($this->account->account_token) || $this->force || !$this->account->login_status) {
				$response = json_decode($this->login());

				$log->write("Login\nResponse: " . json_encode($response), 'ajio-dashboard-' . str_replace(' ', '-', strtolower($this->account->account_name)));

				if ($response->success) {
					$this->is_login = true;
					$this->account->account_token = $response->result->accessToken;
					$db->where('account_id', $this->account->account_id);
					$db->update(TBL_AJ_ACCOUNTS, array('account_token' => $this->account->account_token, 'login_status' => 1));
					sleep(2);
				} else if (!$response->success && (strpos($response->errorMessage, 'authentication failed coz Login locked') !== FALSE) && $this->reties > 0) {
					$this->reties--;
					$this->check_login();
				} else {
					$this->is_login = false;
					$db->where('account_id', $this->account->account_id);
					$db->update(TBL_AJ_ACCOUNTS, array('account_token' => NULL, 'login_status' => 0));
					return array("type" => "error", "message" => $response->errorMessage, "error" => $response);
				}
			}
		}

		function send_request($url, $payload = array(), $file_upload = array(), $accept_type = "Accept: application/json, text/javascript, text/plain, */*; q=0.01", $method = "", $add_header = array())
		{
			global $log;

			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => array(
					'Accept: application/json, text/plain, */*',
					'Authorization: Bearer ' . $this->account->account_token,
				),
			));

			$response = curl_exec($curl);
			curl_close($curl);

			$log->write("URL: " . $url . "\nAccount: " . json_encode($this->account) . "\nResponse: " . $response, 'ajio-dashboard-' . str_replace(' ', '-', strtolower($this->account->account_name)));

			if (strpos($response, 'Invalid token') !== false || strpos($response, 'Invalid Request') !== false) {
				if (strpos($response, 'Invalid token') !== false) {
					$this->force = true;
					$login_response = $this->check_login();
					if (isset($login_response['type']) && $login_response['type'] == "error")
						return $login_response;
				}

				if ($this->is_login) {
					return $this->send_request($url, $payload, $file_upload, $accept_type, $method, $add_header);
				} else {
					return $response;
				}
			} else {
				return $response;
			}
		}

		function get_orders($start_date, $end_date, $download = false)
		{
			$url = 'https://seller.ajio.com/malekith/vision/v1/dropship/order/report?page=0&size=2000&from=' . $start_date . '&to=' . $end_date . '&pobIds=' . $this->account->account_pobIds;
			$response = $this->send_request($url);
			return $response;
		}

		function get_shipments($start_date, $end_date, $download = false)
		{
			$url = 'https://seller.ajio.com/malekith/vision/v1/dropship/order/report?page=0&size=2000&from=' . $start_date . '&to=' . $end_date . '&pobIds=' . $this->account->account_pobIds;
			$response = $this->send_request($url);
			return $response;
		}

		function get_returns($start_date, $end_date, $download = false)
		{
			$url = 'https://seller.ajio.com/malekith/vision/v1/dropship/rtv/report?page=0&size=2000&from=' . $start_date . '&to=' . $end_date . '&pobIds=' . $this->account->account_pobIds;
			$response = $this->send_request($url);
			return $response;
		}

		function get_settlements($start_date, $end_date, $download = false)
		{
			$url = 'https://seller.ajio.com/malekith/vision/v1/dropship/payment/report?page=0&size=2000&from=' . $start_date . '&to=' . $end_date . '&pobIds=' . $this->account->account_pobIds;
			$response = $this->send_request($url);
			return $response;
		}
	}
}
