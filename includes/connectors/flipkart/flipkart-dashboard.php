<?php
// include(ROOT_PATH.'/flipkart/function.php');
// class connector_flikart_listener
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<	pre>';

/**
 * 
 */
class flipkart_dashboard extends connector_flipkart
{
	private $fk_account = "";
	private $sandbox = false;
	public $is_login = false;
	private $debug = false;
	private $first = false;
	private $force = false;
	private $cookie_string = "";
	private $fk_csrf_token = "";
	private $secret_key = "CrckvasdjlwinzmcCCskLm24"; // random secret key
	private $ams_account_password = 'KyzlZCyFHmTVv1PT86f2WQ==';

	function __construct($fk_account, $sandbox = false, $force = false, $debug = false, $first = false)
	{
		$this->fk_account = $fk_account;
		$this->debug = $debug;
		$this->first = $first;
		$this->force = $force;

		if ($this->fk_account->sandbox || $sandbox)
			$this->sandbox = true;

		parent::__construct($this->fk_account, $this->sandbox, $force);

		if ($this->debug)
			echo '<pre>';

		$this->check_login_fk();
	}

	function login_fk()
	{
		global $log, $db;

		$fk_curl = curl_init();
		$this->cookie_string = "";

		$url = "https://seller.flipkart.com";
		curl_setopt_array($fk_curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_FOLLOWLOCATION => false,
		));

		$response = curl_exec($fk_curl);
		$info = curl_getinfo($fk_curl);
		$err = curl_error($fk_curl);

		$details = $this->fk_account->account_name . " \n:: URL: " . $url . " \n:: Response Header: " . json_encode($info) . " \n:: Type: GET";
		if ($this->debug)
			var_dump($details);
		$log->write($details, 'fk_dashboard-' . $this->fk_account->account_name);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}

		$this->cookie_string = "";
		$this->cookie_string = implode(';', array_map(
			function ($v, $k) {
				return sprintf("%s=%s", $k, $v);
			},
			$cookies,
			array_keys($cookies)
		));

		// REQUEST 2 FOR LOGIN
		if ($this->first)
			$post_data = '{"authName":"flipkart","username":"' . $this->fk_account->account_email . '","password":"' . openssl_decrypt($this->fk_account->account_password, "AES-128-CBC", $this->secret_key) . '","userNameType":"email"}';
		else
			$post_data = '{"authName":"flipkart","username":"' . $this->fk_account->account_name . '/skmimarketplacemanagement@gmail.com","password":"' . openssl_decrypt($this->ams_account_password, "AES-128-CBC", $this->secret_key) . '","userNameType":"email"}';

		$header = array(
			"accept: */*",
			"accept-encoding: gzip, deflate, br",
			"accept-language: en-US,en;q=0.5",
			"cache-control: no-cache",
			"connection: keep-alive",
			"content-length: " . strlen($post_data),
			"content-type: application/json",
			"host: seller.flipkart.com",
			"referer: https://seller.flipkart.com/",
			"user-agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0",
		);

		$url = "https://seller.flipkart.com/login";
		curl_setopt_array($fk_curl, array(
			CURLOPT_URL => "https://seller.flipkart.com/login",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $post_data,
			CURLOPT_HTTPHEADER => $header,
		));

		$response = curl_exec($fk_curl);
		$info = curl_getinfo($fk_curl);
		$err = curl_error($fk_curl);
		$body = json_decode(substr($response, curl_getinfo($fk_curl, CURLINFO_HEADER_SIZE)));
		if ($info['http_code'] != 200 || $body->message != "The Seller were authenticated successfully") {
			curl_close($fk_curl);
			$this->is_login = false;

			$db->where('account_id', $this->fk_account->account_id);
			$db->update(TBL_FK_ACCOUNTS, array('login_status' => false));

			if ($body->message != "The Seller were authenticated successfully")
				return 'Unable to login. Please try again later.';
			return 'Invalid username/password entered';
		}

		$details = $this->fk_account->account_name . " \n:: URL: " . $url . " \n:: Request Header: " . json_encode($header) . " \n:: Response Header: " . json_encode($info) . " \n:: Type: POST  \n:: Payload: " . json_encode($post_data) . " \n:: Response: " . $response;
		if ($this->debug)
			var_dump($details);
		$log->write($details, 'fk_dashboard-' . $this->fk_account->account_name);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		$cookies['sellerId'] = $body->data->sellerId;
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}
		$cookies['is_login'] = "true";

		$this->cookie_string = "";
		$this->cookie_string = implode(';', array_map(
			function ($v, $k) {
				return sprintf("%s=%s", $k, $v);
			},
			$cookies,
			array_keys($cookies)
		));

		$this->cookie_string = str_replace('connect_sid', 'connect.sid', $this->cookie_string); // Standard Fix. PHP converts periods to underscore in any variables

		$header = array(
			"Accept: application/json, text/javascript, */*; q=0.01",
			"Accept-Encoding: gzip, deflate, br",
			"Accept-Language: en-US,en;q=0.5",
			"Cache-Control: no-cache",
			"Connection: keep-alive",
			"Cookie: " . $this->cookie_string,
			"Host: seller.flipkart.com",
			"Pragma: no-cache",
			"Referer: https://seller.flipkart.com/index.html",
			"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36",
			"X-Requested-With: XMLHttpRequest",
		);

		$url = "https://seller.flipkart.com/getFeaturesForSeller";
		curl_setopt_array($fk_curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			// CURLOPT_POSTFIELDS => "",
			CURLOPT_HTTPHEADER => $header
		));

		$response = curl_exec($fk_curl);
		$info = curl_getinfo($fk_curl);
		$err = curl_error($fk_curl);
		$response = str_replace("<br>", ' ', $response);
		$details = $this->fk_account->account_name . " \n:: URL: " . $url . " \n:: Request Header: " . json_encode($header) . " \n:: Response Header: " . json_encode($info) . " \n:: Type: GET  \n:: Payload: \n:: Response: " . $response;
		if ($this->debug)
			var_dump($details);

		$log->write($details, 'fk_dashboard-' . $this->fk_account->account_name);
		$response_details = json_decode($response);
		$this->fk_csrf_token = $response_details->csrfToken;
		$details = array(
			'csrf_token' => $this->fk_csrf_token,
			'cookie_string' => $this->cookie_string,
			'login_status' => true,
		);
		if ($this->first) {
			$location_details = $this->get_location_id();
			$capabilities = $this->get_fulfilment_modes($location_details->multiLocationList[0]->capabilities);
			$api_access = $this->get_api_access();
			$details["account_name"] = $response_details->sellerDetails->displayName;
			$details["account_mobile"] = str_replace("+91", "", $response_details->sellerDetails->phoneNumber);
			$details["fk_account_name"] = $response_details->sellerDetails->sellerName;
			$details["seller_id"] = $response_details->sellerDetails->sellerId;
			$details["locationId"] = $location_details->multiLocationList[0]->locationId;
			$details["is_sellerSmart"] = $capabilities['FBF_LITE'];
		}
		$db->where('account_id', $this->fk_account->account_id);
		$db->update(TBL_FK_ACCOUNTS, $details);
		$this->is_login = true;
		curl_close($fk_curl);
	}

	function check_login_fk()
	{
		global $db;

		$db->where('account_id', $this->fk_account->account_id);
		$login_details = $db->getOne(TBL_FK_ACCOUNTS, 'csrf_token, cookie_string, login_status');

		if (empty($login_details['csrf_token']) || empty($login_details['cookie_string']) || $this->force || !$login_details['login_status'])
			$this->login_fk();
		else {
			$this->fk_csrf_token = $login_details['csrf_token'];
			$this->cookie_string = $login_details['cookie_string'];
			$response = $this->send_request('https://seller.flipkart.com/napi/listing/listingsCountForStates');
			$response_j = json_decode($response);
			if ($response == "Found. Redirecting to /?referral_url=%252Fnapi%252Flisting%252FlistingsCountForStates")
				$this->login_fk();
			else if (isset($response_j->listings_count))
				$this->is_login = true;
			else {
				$this->is_login = false;
				$db->where('account_id', $this->fk_account->account_id);
				$db->update(TBL_FK_ACCOUNTS, array('login_status' => false));
				return 'Unable to Login!';
			}
		}
	}

	function send_request($url, $payload = array(), $file_upload = array(), $accept_type = "Accept: application/json, text/javascript, text/plain, */*; q=0.01", $method = "", $add_header = array())
	{
		global $log;

		$fk_curl = curl_init();
		$request = "GET";
		if (!empty($method))
			$request = $method;

		$base = parse_url($url);

		$header = array(
			$accept_type,
			"Accept-Encoding: gzip, deflate, br",
			"Accept-Language: en-US,en;q=0.5",
			"Connection: keep-alive",
			"Content-Type: application/json",
			"Host: " . $base['host'],
			"Origin: " . $base['scheme'] . '://' . $base['host'],
			"Referer: " . $base['scheme'] . '://' . $base['host'] . "/index.html",
			"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:61.0) Gecko/20100101 Firefox/61.0",
			"X-LOCATION-ID: " . $this->fk_account->locationId,
			"X-Requested-With: XMLHttpRequest",
			"cache-control: no-cache",
			"cookie: s_nr=" . round(microtime(true) * 1000) . "-Repeat;" . $this->cookie_string,
		);

		if (!empty($file_upload)) {
			if (isset($file_upload['header']) && !empty($file_upload['header'])) {
				foreach ($file_upload['header'] as $h_key => $h_value) {
					$header[] = $h_key . ': ' . $h_value;
				}
			}

			foreach ($file_upload['files'] as $file) {
				$cFile = curl_file_create($file['file_path'], $file['mime'], $file['filename']);
				$payload[$file['file_parameter']] = $cFile;
			}
			// unset($header[4]); // UNSET HEADER WITH APPLICATION/JSON 
			$header[4] = "Content-Type: multipart/form-data";
		}

		if (!empty($payload)) {
			$request = "POST";
			if (!empty($method))
				$request = $method;
			curl_setopt($fk_curl, CURLOPT_CUSTOMREQUEST, $request);
			curl_setopt($fk_curl, CURLOPT_POSTFIELDS, $payload);
			if (empty($file_upload))
				$header[] = "Content-Length: " . strlen($payload);
			$header[] = "fk-csrf-token: " . $this->fk_csrf_token;

			if (!empty($add_header)) {
				$isExistContentType = array_filter($add_header, function ($element) {
					return strpos($element, 'Content-Type') !== FALSE;
				});
				if ($isExistContentType)
					unset($header[4]);
				$header = array_merge($header, $add_header);
			}
		}

		curl_setopt($fk_curl, CURLOPT_URL, $url);
		curl_setopt($fk_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($fk_curl, CURLOPT_ENCODING, "");
		curl_setopt($fk_curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($fk_curl, CURLOPT_TIMEOUT, 600);
		curl_setopt($fk_curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($fk_curl, CURLOPT_HTTPHEADER, $header);

		$return = curl_exec($fk_curl);
		$info = curl_getinfo($fk_curl);
		$errno = curl_errno($fk_curl);
		$err = curl_error($fk_curl);

		$details = $this->fk_account->account_name . " \n:: URL: " . $url . " \n:: Request Header: " . json_encode($header) . " \n:: Response Header: " . json_encode($info) . " \n:: Type: " . $request . " " . json_encode($payload) . " \n:: Response: " . $return;
		if ($this->debug)
			var_dump($details);
		$log->write($details, 'fk_dashboard-' . $this->fk_account->account_name);

		curl_close($fk_curl);

		if ($err) {
			$log->write("cURL Error #:" . $err, 'fk_dashboard-' . $this->fk_account->account_name);
			return $reutrn['err'] = "cURL Error #:" . $err;
		}

		return $return;
	}

	function send_external_request($url, $payload = array(), $file_upload = array(), $accept_type = "Accept: */*", $method = "")
	{
		global $log;

		$fk_curl = curl_init();
		$this->cookie_string = "";

		curl_setopt_array($fk_curl, array(
			CURLOPT_URL => 'https://www.flipkart.com',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_FOLLOWLOCATION => false,
		));

		$response = curl_exec($fk_curl);
		$info = curl_getinfo($fk_curl);
		$err = curl_error($fk_curl);

		$details = $this->fk_account->account_name . " \n:: URL: " . $url . " \n:: Response Header: " . json_encode($info) . " \n:: Type: GET";
		if ($this->debug)
			var_dump($details);
		$log->write($details, 'fk_dashboard-' . $this->fk_account->account_name);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}

		$cookie_string = "";
		$cookie_string = implode(';', array_map(
			function ($v, $k) {
				return sprintf("%s=%s", $k, $v);
			},
			$cookies,
			array_keys($cookies)
		));

		// CREATE A REQUEST WITH COOKIES
		$fk_curl = curl_init();
		$request = "GET";
		if (!empty($method))
			$request = $method;

		$base = parse_url($url);

		$header = array(
			$accept_type,
			"Accept-Encoding: gzip, deflate, br",
			"Accept-Language: en-US,en;q=0.5",
			"Connection: keep-alive",
			"Content-Type: text/plain",
			"Host: " . $base['host'],
			"Origin: https://www.flipkart.com",
			"Referer: https://www.flipkart.com/",
			"X-user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.67 Safari/537.36 FKUA/website/42/website/Desktop",
			"cache-control: no-cache",
			"cookie: " . $cookie_string,
		);

		if (!empty($payload)) {
			$request = "POST";
			if (!empty($method))
				$request = $method;
			curl_setopt($fk_curl, CURLOPT_CUSTOMREQUEST, $request);
			curl_setopt($fk_curl, CURLOPT_POSTFIELDS, $payload);
			if (empty($file_upload))
				$header[] = "Content-Length: " . strlen($payload);
		}

		curl_setopt($fk_curl, CURLOPT_URL, $url);
		curl_setopt($fk_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($fk_curl, CURLOPT_ENCODING, "");
		curl_setopt($fk_curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($fk_curl, CURLOPT_TIMEOUT, 600);
		curl_setopt($fk_curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($fk_curl, CURLOPT_HTTPHEADER, $header);

		$return = curl_exec($fk_curl);
		$info = curl_getinfo($fk_curl);
		$errno = curl_errno($fk_curl);
		$err = curl_error($fk_curl);

		$details = $this->fk_account->account_name . " \n:: URL: " . $url . " \n:: Request Header: " . json_encode($header) . " \n:: Response Header: " . json_encode($info) . " \n:: Type: " . $request . " " . json_encode($payload) . " \n:: Response: " . $return;
		if ($this->debug)
			var_dump($details);
		$log->write($details, 'fk_dashboard-' . $this->fk_account->account_name);

		curl_close($fk_curl);

		if ($err) {
			$log->write("cURL Error #:" . $err, 'fk_dashboard-' . $this->fk_account->account_name);
			return $reutrn['err'] = "cURL Error #:" . $err;
		}

		return $return;
	}

	function is_login()
	{
		return $this->is_login;
	}

	// ACCOUNT DETAILS
	function get_location_id()
	{
		$url = "https://seller.flipkart.com/napi/get-locations?locationType=pickup&include=state&capabilities=NON_FBF%2CFBF_LITE";
		$response = $this->send_request($url);
		$response_j = json_decode($response);
		return $response_j->result;
	}

	function get_fulfilment_modes($capabilities)
	{
		$details = array(
			"NON_FBF" => false,
			"FBF_LITE" => false
		);

		foreach ($capabilities as $capability) {
			if ($capability->capability_id == "NON_FBF" && $capability->capability_status == "ACTIVE") {
				$details['NON_FBF'] = true;
			}
			if ($capability->capability_id == "FBF_LITE" && $capability->capability_status == "ACTIVE") {
				$details['FBF_LITE'] = true;
			}
		}

		return $details;
	}

	// REPORTS
	function request_report($type, $data = null)
	{
		global $db;

		switch ($type) {
			case 'get_reports':
				$url = "https://seller.flipkart.com/napi/metrics/bizReport/report/getReports?sellerId=" . $this->fk_account->seller_id;
				$payload = '{"filters":[{"values":["' . $this->fk_account->seller_id . '"],"name":"seller_id","operator":"EQ"}],"page_size":10,"sellerId":"' . $this->fk_account->seller_id . '"}';
				$response = $this->send_request($url, $payload);
				return $response;
				break;

			case 'download_report':
				$file_name = $data['request_id'] . '.' . $data['file_format'];
				if (!file_exists(UPLOAD_PATH . '/' . $data['type'] . '/' . $file_name)) {
					$url = 'https://seller.flipkart.com/napi/metrics/bizReport/downloadReport/' . $data['request_id'] . '.' . $data['file_format'] . '?token=' . $data['request_id'] . '&sellerId=' . $this->fk_account->seller_id;
					$response = $this->send_request($url);
					if (file_put_contents(UPLOAD_PATH . '/' . $data['type'] . '/' . $file_name, $response)) {
						return $file_name;
					}
				}
				return 'error download file';
				break;

			case 'settled_transactions':
				$url = "https://seller.flipkart.com/napi/metrics/bizReport/report/2/detail?reportName=Settled+Transactions&sellerId=" . $this->fk_account->seller_id;
				$response = $this->send_request($url);
				$response = json_decode($response);
				foreach ($response->filters as $filter) {
					if ($filter->name == "date" && $filter->max_value <= $data["to_date"]) {
						$data["to_date"] = $filter->max_value;
						continue;
					}
				}

				$url = 'https://seller.flipkart.com/napi/metrics/bizReport/submitReport?reportName=' . urlencode("Settled Transactions") . '&sellerId=' . $this->fk_account->seller_id;
				$payload = '{"from_date":"' . $data["from_date"] . '","to_date":"' . $data["to_date"] . '","requested_by":"' . $this->fk_account->account_email . '","file_format":"EXCEL","filters":[{"values":["' . $data["from_date"] . '","' . $data["to_date"] . '"],"operator":"RANGE","name":"date"},{"values":["' . $this->fk_account->seller_id . '"],"name":"seller_id","operator":"EQ"}],"sellerId":"' . $this->fk_account->seller_id . '"}';
				$response = $this->send_request($url, $payload);
				$response_j = json_decode($response);

				if (!is_null($response_j)) {
					$request_id = $response_j->request_id->request_id;
					if (isset($response_j->error) && strpos($response_j->error->error->errors[0]->message, 'download link is still active') !== FALSE) {
						$request_id = str_replace('REQ-', '', $response_j->error->error->errors[0]->params->request_id);
					}
					$details = array(
						"reportId" => $request_id,
						"reportStatus" => 'queued',
						"reportType" => 'Payments',
						"reportSubType" => 'Settle Transactions',
						"reportFromDate" => $data["from_date"],
						"reportToDate" => $data["to_date"],
						"accountId" => $this->fk_account->account_id,
					);

					if ($db->insert(TBL_FK_REPORTS, $details)) {
						$details['type'] = 'success';
						$details['response'] = $response;
						$details['message'] = 'New report with ID ' . $request_id . ' successfully requested.';
						$details['status'] = 'Inserted';
						return $details;
					} else {
						$details['type'] = 'info';
						$details['response'] = $response;
						$details['message'] = 'Report already requested with ' . $request_id . '.';
						$details['status'] = 'Already In Que';
						return $details;
					}
				} else {
					$details['type'] = 'error';
					$details['response'] = $response;
					$details['message'] = 'Error requesting report';
					$details['status'] = 'Unable to generate report. Please try again.';
					return $details;
				}
				break;

			case 'commission_invoice':
				$url = "https://seller.flipkart.com/napi/metrics/bizReport/report/2/detail?reportName=" . urlencode("Commission Invoice") . "&sellerId=" . $this->fk_account->seller_id;
				$validate = $this->send_request($url);
				$validate = json_decode($validate);
				foreach ($validate->filters as $filter) {
					if ($filter->name == "date" && $filter->max_value == $data["to_date"]) {
						$url = "https://seller.flipkart.com/napi/metrics/bizReport/submitReport?reportName=" . urlencode("Commission Invoice") . "&sellerId=" . $this->fk_account->seller_id;
						$payload = '{"from_date":"' . $data["from_date"] . '","to_date":"' . $data["to_date"] . '","requested_by":"' . $this->fk_account->account_email . '","file_format":"ZIP","filters":[{"values":["' . $data["from_date"] . '","' . $data["to_date"] . '"],"operator":"RANGE","name":"date"},{"values":["' . $this->fk_account->seller_id . '"],"name":"seller_id","operator":"EQ"}],"sellerId":"' . $this->fk_account->seller_id . '"}';
						$response = $this->send_request($url, $payload);
						$response_j = json_decode($response);
						if (!is_null($response_j)) {
							$request_id = $response_j->request_id->request_id;
							if (isset($response_j->error) && strpos($response_j->error->error->errors[0]->message, 'download link is still active') !== FALSE) {
								$request_id = str_replace('REQ-', '', $response_j->error->error->errors[0]->params->request_id);
							}
							$details = array(
								"reportId" => $request_id,
								"reportStatus" => 'queued',
								"reportType" => 'Invoices',
								"reportSubType" => 'Commission Invoice',
								"reportFromDate" => $data["from_date"],
								"reportToDate" => $data["to_date"],
								"accountId" => $this->fk_account->account_id,
							);

							if ($db->insert(TBL_FK_REPORTS, $details)) {
								$details['response'] = $response;
								$details['message'] = 'New report with ID ' . $request_id . ' successfully requested.';
								$details['status'] = 'Inserted';
								return $details;
							} else {
								$details['type'] = 'info';
								$details['response'] = $response;
								$details['message'] = 'Report already requested with ' . $request_id . '.';
								$details['status'] = 'Already In Que';
								return $details;
							}
						} else {
							$details['type'] = 'error';
							$details['response'] = $response;
							$details['message'] = 'Error requesting report';
							$details['status'] = 'Unable to generate report. Please try again.';
							return $details;
						}
					} else {
						$details['type'] = 'error';
						$details['message'] = 'Report for request date not available';
						$details['status'] = 'Unable to generate report. Please try again.';
						return $details;
					}
				}
				break;

			case 'create_ff_orders_report':
				$url = 'https://seller.flipkart.com/napi/sfx/reports/generateReport?sellerId=' . $this->fk_account->seller_id;
				$payload = '{"from_date":"' . $data['from_date'] . '","to_date":"' . $data['to_date'] . '","warehouse_id":"' . $data['warehouse_id'] . '","report_id":"2","sellerId":"' . $this->fk_account->seller_id . '"}';
				$response = $this->send_request($url, $payload);
				$response = json_decode($response);
				if (isset($response->result)) {
					$report_id = $response->result;
					$details = array(
						"reportId" => $report_id,
						"reportStatus" => 'queued',
						"reportType" => 'Flipkart Fulfilment Orders Report',
						"reportSubType" => $data['warehouse_id'],
						"reportFromDate" => $data["from_date"],
						"reportToDate" => $data["to_date"],
						"accountId" => $this->fk_account->account_id,
					);

					if ($db->insert(TBL_FK_REPORTS, $details)) {
						$details['response'] = $response;
						$details['status'] = 'Inserted';
						return $details;
					} else {
						$details['response'] = $response;
						$details['status'] = 'Already In Que';
						return $details;
					}
				} else {
					$details['response'] = $response;
					$details['status'] = 'Error';
					return $details;
				}
				break;

			case 'get_ff_orders_report_status':
				$url = 'https://seller.flipkart.com/napi/sfx/reports/getGeneratedReports?page_number=0&page_size=20&warehouse=' . $data['warehouse_id'] . '&job=Invoice+(CSV)&sellerId=' . $this->fk_account->seller_id;
				$response = $this->send_request($url);
				return $response;
				break;

			case 'download_ff_orders_report':
				$url = 'https://seller.flipkart.com/napi/sfx/reports/downloadReport?jobId=' . $data['reportId'] . '&sellerId=' . $this->fk_account->seller_id;
				$response = $this->send_request($url);
				$file_name = $data['reportId'] . '.csv';
				if (!is_dir(UPLOAD_PATH . '/ff_orders/'))
					mkdir(UPLOAD_PATH . '/ff_orders/', 0777, true);

				if (file_put_contents(UPLOAD_PATH . '/ff_orders/' . $file_name, $response))
					return $file_name;
				break;
		}
	}

	// SPF PAYMENT
	function get_payments_details($orderItemId)
	{
		$url = 'https://seller.flipkart.com/napi/payments/details?param=' . $orderItemId . '&service_type=orderItem&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		return json_decode($response);
	}

	function get_transaction_history($orderItemId)
	{
		$url = 'https://seller.flipkart.com/napi/payments/history?param=' . $orderItemId . '&service_type=orderItem&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		return json_decode($response);
	}

	function get_all_transaction_aggregated_amount($orderItemId)
	{
		$url = 'https://seller.flipkart.com/napi/payments/getAllTransactionAggregatedAmount?param=' . $orderItemId . '&pageType=allTransaction&type=order_item_transactions&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		return json_decode($response);
	}

	function get_spf_amount($orderItemId)
	{
		$response = $this->get_payments_details($orderItemId);
		if ($response->transactions_summary) {
			$spf = number_format($response->transactions_summary->protection_fund, 2, '.', '');
			if ($spf == (int)0) {
				$return = array('type' => 'info', 'message' => 'No SPF Received yet.', 'amount' => $spf);
			} else {
				$return = array('type' => 'success', 'message' => 'SPF amount of Rs.' . $spf . ' received.', 'amount' => $spf);
			}
		} else {
			$return = array('type' => 'info', 'message' => 'Unable to get SPF details.', 'amount' => 0);
		}

		return $return;
	}

	// PROFILE MANAGEMENT
	function update_holiday($enable)
	{
		$url = 'https://seller.flipkart.com/napi/manageProfile/updateWorkingHours?sellerId=' . $this->fk_account->seller_id;

		// Enable Sunday
		if ($enable)
			$payload = '{"working_schedule":{"monday":{"start_time":"9:00:00","end_time":"18:00:00"},"tuesday":{"start_time":"09:00:00","end_time":"18:00:00"},"wednesday":{"start_time":"09:00:00","end_time":"18:00:00"},"thursday":{"start_time":"09:00:00","end_time":"18:00:00"},"friday":{"start_time":"09:00:00","end_time":"18:00:00"},"saturday":{"start_time":"09:00:00","end_time":"18:00:00"},"sunday":{"start_time":"09:00:00","end_time":"18:00:00"}},"weeklyOff":{"weekly":{"comment":"","day":"sunday","enabled":true}},"sellerId":"' . $this->fk_account->seller_id . '"}';
		else
			$payload = '{"working_schedule":{"monday":{"start_time":"9:00:00","end_time":"18:00:00"},"tuesday":{"start_time":"09:00:00","end_time":"18:00:00"},"wednesday":{"start_time":"09:00:00","end_time":"18:00:00"},"thursday":{"start_time":"09:00:00","end_time":"18:00:00"},"friday":{"start_time":"09:00:00","end_time":"18:00:00"},"saturday":{"start_time":"09:00:00","end_time":"18:00:00"},"sunday":{"start_time":"09:00:00","end_time":"18:00:00"}},"weeklyOff":{"weekly":{"comment":"","day":null,"enabled":false}},"sellerId":"' . $this->fk_account->seller_id . '"}';

		$response = $this->send_request($url, $payload);
		return $response;
	}

	// PROMOTIONS MANAGEMENT
	function get_flipkart_promotions($viewType = 'ELIGIBLE')
	{
		$url = 'https://seller.flipkart.com/napi/fkpromo/get-fk-promotions?pageNo=1&offset=100&viewType=' . $viewType . '&excludePrebookOffers=false&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);

		return json_decode($response);
	}

	function get_flipkart_promotion_lid($offerId, $opted = false, $timestamp = "", $entityType = "")
	{
		if ($entityType != "")
			$entityType = "&entityType=" . $entityType;
		$url = 'https://seller.flipkart.com/napi/fkpromo/download-eligible-listings?offerId=' . urlencode($offerId) . $entityType . '&sellerId=' . $this->fk_account->seller_id;
		$opted_sufix = "";
		if ($opted) {
			$url = 'https://seller.flipkart.com/napi/fkpromo/download-mapped-listings?offerId=' . urlencode($offerId) . '&sellerId=' . $this->fk_account->seller_id;
			$opted_sufix = '-opted-fk';
		}

		if (!empty($timestamp))
			$timestamp = '-' . $timestamp;
		$response = $this->send_request($url);
		$file_name = str_replace(':', '-', $offerId) . '-' . $this->fk_account->seller_id . $opted_sufix . $timestamp . '.csv';
		if (file_exists(UPLOAD_PATH . '/promotion_offer/' . $file_name))
			return $file_name;

		if (strpos($response, 'Failed to download eligible listings') !== FALSE) {
			return array('error' => 'Failed to download eligible listings');
		} else {
			if (!file_exists(UPLOAD_PATH . '/promotion_offer/' . $file_name)) {
				if (file_put_contents(UPLOAD_PATH . '/promotion_offer/' . $file_name, $response)) {
					// $url = BASE_URL.'/flipkart/ajax_load.php?account_id='.$this->fk_account->account_id.'&file_name='.$file_name;
					// $response = $this->send_request($url);
					return $file_name;
				}
			}
			return $file_name;
		}
	}

	function check_promotion_eligibility($file_name, $discount, $discountType, $startDate, $endDate, $mpIncAmt)
	{

		$url = BASE_URL . '/flipkart/get_eligible_listings.php';
		$payload = array(
			'internal' => true,
			'submit' => "Get Eligible Listings",
			'account_id' => $this->fk_account->account_id,
			'discount' => $discount,
			'discountType' => $discountType,
			'startDate' => $startDate,
			'endDate' => $endDate,
			'incentive' => $mpIncAmt
		);
		$file_upload['files'][basename($file_name)]['file_path'] = $file_name;
		$file_upload['files'][basename($file_name)]['file_parameter'] = "fileToUpload";
		$file_upload['files'][basename($file_name)]['mime'] = 'text/csv';
		$file_upload['files'][basename($file_name)]['filename'] = basename($file_name);

		$response = $this->send_request($url, $payload, $file_upload);
		$response = json_decode($response);
		return $response;
	}

	function optin_flipkart_promotion($offerId, $listingIds, $entityType)
	{

		$file_upload = $this->create_promotion_listing_file($offerId, $listingIds, 'ADD');

		$url = "https://seller.flipkart.com/napi/fkpromo/upload-selsub-listings";
		$payload = array(
			'offerId' => $offerId,
			'operation' => "WHITELIST",
			'listType' => "WHITELIST",
			'entityType' => $entityType
		);

		$file_upload['header'] = array(
			'x-offer-id' => $offerId,
			'x-operation' => "WHITELIST",
			'x-list-type' => "WHITELIST",
			'x-entity-type' => $entityType
		);

		$response = $this->send_request($url, $payload, $file_upload, 'Accept: text/plain, */*; q=0.01');
		// if ($response == "{}"){
		if (strpos($response, '"statusCode":204') !== FALSE || $response == "{}") {
			$fileValidationsStatus = $this->check_promotion_file_validation_status($offerId);
			if ($fileValidationsStatus) {
				$url = 'https://seller.flipkart.com/napi/fkpromo/fk-promotion-opt-in?offerId=' . urlencode($offerId) . '&tncId=45606&entityType=' . $entityType . '&sellerName=' . $this->fk_account->account_name . 'sellerId=' . $this->fk_account->seller_id;
				$payload = '{"sellerId":"' . $this->fk_account->seller_id . '"}';
				$response = $this->send_request($url, $payload);
				if ($response == '{"result":{"entityType":"OFFER","sellerOfferId":"' . $offerId . '","entityId":"' . $offerId . '"}}') {
					return true;
				}
				return false;
			}
		} else {
			return false;
		}
		// Request URL: https://seller.flipkart.com/napi/fkpromo/fk-promotion-upload-post-opt-in
		// Request Method: POST
		// PAYLOAD: 
		// offerId: nb:mp:03c43eac08
		// operation: DELETE
		// listType: ADD
		// file: (binary)		

		// GET VALIDATION STATUS OF PUT FILE REQUEST 
		// URL - https://seller.flipkart.com/napi/fkpromo/fk-promotion-file-validation-status?offerId=nb%3Amp%3A020de26227%2C&sellerId=046d75e5aee14e2f
		// Request Method: GET
		// INPROGRESS RESPONSE - {"result":{"fileUploadStatus":{"nb:mp:020de26227":{"type":"BLACKLIST","operation":null,"fileStatus":{"validateStatus":"NOT_FOUND","updateStatus":"IN_PROCESS","updateVersion":1,"jobMeta":null}}}}}
		// UPDATED RESPONSE - {"result":{"fileUploadStatus":{"nb:mp:020de26227":{"type":"BLACKLIST","operation":null,"fileStatus":{"validateStatus":"NOT_FOUND","updateStatus":"UPDATED","updateVersion":1,"jobMeta":null}}}}}

		// $url = 'https://seller.flipkart.com/napi/fkpromo/fk-promotion-opt-in?offerId='.urlencode($offerId).'&tncId=13&sellerId='.$this->fk_account->seller_id;
		// $payload = '{"sellerId":"'.$this->fk_account->seller_id.'"}';
		// $response = $this->send_request($url, $payload);
		// if ($response == '{}'){
		// 	return true;
		// } 
		// return false;
	}

	function optout_flipkart_promotion($offerId)
	{
		// URL: https://seller.flipkart.com/napi/fkpromo/fk-promotion-opt-out?offerId=nb%3Amp%3A0396e02c08&sellerId=046d75e5aee14e2f
		// Method: DELETE
		// PAYLOAD: sellerId=046d75e5aee14e2f
		// RESPONSE: {}
	}

	function update_flipkart_promotion($offerId, $type, $listingIds, $entityType)
	{
		$payload['offerId'] = $offerId;
		$payload['operation'] = $type;
		$payload['listType'] = $type;
		$payload['entityType'] = $entityType;

		$file_upload = $this->create_promotion_listing_file($offerId, $listingIds, $type);

		$file_upload['header'] = array(
			'x-offer-id' => $offerId,
			'x-operation' => $type,
			'x-list-type' => $type,
			'x-entity-type' => $entityType
		);

		$url = "https://seller.flipkart.com/napi/fkpromo/fk-promotion-upload-post-opt-in";
		$response = $this->send_request($url, $payload, $file_upload, 'Accept: text/plain, */*; q=0.01');
		if (strpos($response, '"statusCode":204') !== FALSE) {
			if ($this->check_promotion_file_validation_status($offerId, 0)) {
				$url = 'https://seller.flipkart.com/napi/fkpromo/fk-promotion-update-listings?sellerId=' . $this->fk_account->seller_id;
				$payload = '{"offerId":"' . $offerId . '","entityType":"' . $entityType . '","sellerId":"' . $this->fk_account->seller_id . '"}';
				$response = $this->send_request($url, $payload, array(), 'application/json, text/javascript, */*; q=0.01', "PUT");
				if ($response == '{}') {
					return $this->check_promotion_file_validation_status($offerId, 0, true);
				}
				return false;
			}
		} else {
			return false;
		}

		// Expected response for the successful file uploaded - GET
		// {"headers":{"date":"Fri, 29 Mar 2019 13:37:02 GMT","content-type":"application/json","connection":"close","x-e-id":"10.33.42.160","x-elb-id":"10.33.42.160","x-elb-app-server":"10.32.169.227"},"statusCode":204}

		// get status of the uploaded file - GET
		// URL - https://seller.flipkart.com/napi/fkpromo/fk-promotion-file-validation-status?offerId=nb%3Amp%3A020de26227%2C&sellerId=046d75e5aee14e2f
		// RESPONSE - {"result":{"fileUploadStatus":{"nb:mp:020de26227":{"type":"BLACKLIST","operation":"DELETE","fileStatus":{"validateStatus":"PROCESSED","updateStatus":"NOT_UPDATED","updateVersion":null,"jobMeta":{"passedCount":507,"failedCount":0}}}}}}

		// PUT REQUEST FOR SUCCESSFUL OPT OUT
		// URL - https://seller.flipkart.com/napi/fkpromo/fk-promotion-update-listings?sellerId=046d75e5aee14e2f
		// Request Method: PUT
		// PAYLOAD - offerId=nb%3Amp%3A020de26227&sellerId=046d75e5aee14e2f
		// RESPONSE - {}

		// GET STATUS OF PUT FILE REQUEST
		// URL - https://seller.flipkart.com/napi/fkpromo/fk-promotion-file-validation-status?offerId=nb%3Amp%3A020de26227%2C&sellerId=046d75e5aee14e2f
		// INPROGRESS RESPONSE - {"result":{"fileUploadStatus":{"nb:mp:020de26227":{"type":"BLACKLIST","operation":null,"fileStatus":{"validateStatus":"NOT_FOUND","updateStatus":"IN_PROCESS","updateVersion":1,"jobMeta":null}}}}}
		// UPDATED RESPONSE - {"result":{"fileUploadStatus":{"nb:mp:020de26227":{"type":"BLACKLIST","operation":null,"fileStatus":{"validateStatus":"NOT_FOUND","updateStatus":"UPDATED","updateVersion":1,"jobMeta":null}}}}}
	}

	function create_promotion_listing_file($offerId, $listings, $type)
	{
		array_unshift($listings, "**** Instructions: Enter Listing IDs from row 3 (Don't edit the first 2 rows)  ****", "ListingIDs");

		$file = UPLOAD_PATH . '/promotion_offer/sampleFile-' . str_replace(":", "_", $offerId) . '-' . $type . '-' . time() . $this->fk_account->seller_id . '.csv';
		$handle = fopen($file, 'w') or die("X_x");
		foreach ($listings as $line) {
			fputcsv($handle, array($line), ",", '"');
		}
		fclose($handle);

		$file_upload['files'][basename($file)]['file_path'] = $file;
		$file_upload['files'][basename($file)]['file_parameter'] = "file";
		$file_upload['files'][basename($file)]['mime'] = 'text/csv';
		$file_upload['files'][basename($file)]['filename'] = basename($file);

		return $file_upload;
	}

	function check_promotion_file_validation_status($offerId, $retires = 0, $is_update = false)
	{
		$url = 'https://seller.flipkart.com/napi/fkpromo/fk-promotion-file-validation-status?offerId=' . urlencode($offerId . ',') . '&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		$response = json_decode($response);
		if ($is_update) {
			$validateStatus = $response->result->fileUploadStatus->{$offerId}->fileStatus->updateStatus;
		} else {
			$validateStatus = $response->result->fileUploadStatus->{$offerId}->fileStatus->validateStatus;
		}
		if ($validateStatus == "PROCESSED" || $validateStatus == "UPDATED") {
			return true;
		}
		if (($validateStatus == "IN_PROCESS" || $validateStatus == "CREATED") && $retires < 6) {
			sleep(5); // recheck after 5 seconds
			return $this->check_promotion_file_validation_status($offerId, $retires++, $is_update);
		} else if ($retires > 6) {
			return false;
		}
	}

	function check_flipkart_promotion($offerId)
	{
		$url = 'https://seller.flipkart.com/napi/fkpromo/get-fk-promotion-by-id?offerId=' . urlencode($offerId) . '&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		return json_decode($response);
	}

	// ORDER LABELS - TEMP
	function get_label_dashboard($shipment_id)
	{
		$url = "https://seller.flipkart.com/napi/shipments/print_labels?shipmentIds=" . $shipment_id;
		$return = $this->send_request($url, array(), array(), "Accept: application/pdf");
		return $return;
	}

	function get_order_details($shipmentIds, $is_orderItemIds = false, $is_orderIds = false, $retires = 2)
	{
		global $db;

		$response = array();
		foreach (explode(',', $shipmentIds) as $shipmentId) {
			if ($is_orderItemIds) {
				$order_item_id = $shipmentId;
			} else {
				$db->where('shipmentId', $shipmentId);
				$order_item_id = $db->getValue(TBL_FK_ORDERS, 'orderItemId');
			}
			$url = "https://seller.flipkart.com/napi/my-orders/search?sellerId=" . $this->fk_account->seller_id;
			$payload =  '{"status": "order_item_search","payload":{"params":{"seller_id": "' . $this->fk_account->seller_id . '","service_profile": "NON_FBF","type": "NORMAL","id": "' . $order_item_id . '"}},"sellerId": "' . $this->fk_account->seller_id . '"}';
			$return = $this->send_request($url, $payload, array());

			if (strpos($return, '"total":0') !== FALSE) {
				$response = array();
			} else {
				$orders = $this->order_details_convert_dashboard_order_to_v3_api_response($return);
				foreach ($orders->shipments as $order) {
					$response[] = $order;
				}
			}
		}
		return $response;
	}

	function get_products_details_dashboard($fsn)
	{
		$url = "https://seller.flipkart.com/napi/listing/listingsDataForStates";
		$payload = '{"search_text":"' . $fsn . '","search_filters":"","column":{"pagination":{"batch_no":0,"batch_size":10}}}';
		$response = $this->send_request($url, $payload);
		return json_decode($response);
	}

	function order_details_convert_dashboard_order_to_v3_api_response($order)
	{
		$order = json_decode($order);
		$v3_schema_object = false;
		if ($order->total > 0) {
			$order = $order->items[0][0];
			$packages = array();
			foreach ($order->sub_shipments[0]->packages as $package) {
				$packages[] = $package->package_id;
			}

			$isReplacement = empty($order->order_items[0]->replacement) ? "false" : "true";
			$on_hold = $order->on_hold ? "true" : "false";
			$is_mps = $order->is_mps ? "true" : "false";
			$service_profile = ($order->service_profile == "FBF_LITE") ? "Smart_Fulfilment" : "Seller_Filfilment";
			$v3_schema = '
				{
					"shipmentId": "' . $order->id . '",
					"dispatchByDate": "' . $order->dispatch_by_date . '",
					"dispatchAfterDate": "' . $order->dispatch_after_date . '",
					"updatedAt": "' . $order->order_items[0]->modified_date . '",
					"locationId": "' . $this->fk_account->locationId . '",
					"hold": ' . strval($on_hold) . ',
					"mps": ' . $is_mps . ',
					"subShipments": [
					{
						"subShipmentId": "SS-1",
						"packages": ' . json_encode($order->sub_shipments[0]->packages) . '
					}],
					"orderItemId": "' . $order->order_items[0]->order_item_id . '",
					"orderId": "' . $order->order_items[0]->order_id . '",
					"cancellationGroupId": "' . $order->order_items[0]->enforced_group_id . '",
					"orderDate": "' . $order->order_items[0]->order_date . '",
					"paymentType": "' . strtoupper($order->payment_type) . '",
					"status": "' . strtoupper($order->order_items[0]->status) . '",
					"quantity": ' . $order->order_items[0]->quantity . ',
					"fsn": "' . $order->order_items[0]->fsn . '",
					"sku": "' . $order->order_items[0]->sku . '",
					"listingId": "' . $order->order_items[0]->listing_id . '",
					"hsn": "' . $order->order_items[0]->product_details->hsn_sac_code . '",
					"title": "' . $order->order_items[0]->product_details->title . '",
					"packageIds": ' . json_encode($packages) . ',
					"sla": ' . $order->order_items[0]->internal_sla . ',
					"priceComponents":
					{
						"sellingPrice": ' . $order->order_items[0]->pricing->list_price . ',
						"totalPrice": ' . $order->order_items[0]->pricing->total_price . ',
						"shippingCharge": ' . $order->order_items[0]->pricing->shipping_fees . ',
						"customerPrice": ' . $order->order_items[0]->pricing->total_price . ',
						"flipkartDiscount": ' . $order->order_items[0]->pricing->promotion_discount . '
					},
					"serviceProfile": "' . $service_profile . '",
					"isReplacement": ' . $isReplacement . ',
					"forms": [],
					"stateDocuments": [],
					"shipmentType": "NORMAL",
					"deliveryAddress":
					{
						"firstName": "' . $order->buyer->first_name . '",
						"lastName": "' . $order->buyer->first_name . '",
						"pinCode": "' . $order->buyer->shipping_address->pincode . '",
						"city": "' . $order->buyer->shipping_address->city . '",
						"state": "' . $order->buyer->shipping_address->state . '",
						"stateCode": "",
						"addressLine1": "' . ($order->buyer->shipping_address->line1) . '",
						"addressLine2": "' . ($order->buyer->shipping_address->line2) . '",
						"landmark": "",
						"contactNumber": "' . $order->buyer->shipping_address->phone . '"
					},
					"billingAddress":
					{
						"firstName": "' . $order->buyer->first_name . '",
						"lastName": "' . $order->buyer->first_name . '",
						"pinCode": "' . $order->buyer->shipping_address->pincode . '",
						"city": "' . $order->buyer->shipping_address->city . '",
						"state": "' . $order->buyer->shipping_address->state . '",
						"stateCode": null,
						"addressLine1": "' . ($order->buyer->shipping_address->line1) . '",
						"addressLine2": "' . ($order->buyer->shipping_address->line2) . '",
						"landmark": "",
						"contactNumber": "' . $order->buyer->shipping_address->phone . '"
					},
					"buyerDetails":
					{
						"firstName": "' . $order->buyer->first_name . '",
						"lastName": "' . $order->buyer->first_name . '"
					},
					"sellerAddress":
					{
						"sellerName": "Shreehan Enterprise",
						"pinCode": "395006",
						"city": "SURAT",
						"state": "GUJARAT",
						"stateCode": null,
						"addressLine1": "Shreehan Enterprise 4002/B, 4th floor, Silver Business Hub,",
						"addressLine2": "Near ROB Amroli Approach, Uttran Amroli Road, Uttran,",
						"landmark": null
					},
					"returnAddress":
					{
						"firstName": null,
						"lastName": null,
						"pinCode": null,
						"city": null,
						"state": null,
						"stateCode": null,
						"addressLine1": null,
						"addressLine2": null,
						"landmark": null,
						"contactNumber": null
					},
					"tracking_subShipments": [
					{
						"subShipmentId": "SS-1",
						"courierDetails":
						{
							"pickupDetails":
							{
								"trackingId": "' . $order->tracking->pickup_tracking_id . '",
								"vendorName": "' . $order->tracking->pickup_vendor_display_name . '"
							},
							"deliveryDetails":
							{
								"trackingId": "' . $order->tracking->tracking_id . '",
								"vendorName": "' . $order->tracking->delivery_vendor_display_name . '"
							}
						},
						"shipmentDimensions":
						{
							"length": 25,
							"breadth": 17,
							"height": 3,
							"weight": 0.15
						}
					}]
				}';

			$v3_schema = json_decode(str_replace(array('	', "\n"), '', $v3_schema));
			$v3_schema_object = new stdClass();
			$v3_schema_object->shipments = array($v3_schema);
		}

		return $v3_schema_object;
	}

	function order_details_convert_fbf_order_to_v3_api_response($order, $warehouse_id, $retry = false)
	{
		$v3_schema_object = new stdClass();
		$orderItemId = str_replace('"', '', $order['Order Item ID']);

		$order_details = $this->get_payments_details($orderItemId);
		$order_tranactions = $this->get_transaction_history($orderItemId);

		if (empty($order_details) || is_null($order_details) || is_null($order_details->order_id)) {
			$v3_schema_object->error = "Payments details not found for item ID " . $orderItemId;
			return $v3_schema_object;
		}
		if (empty($order_tranactions) || is_null($order_tranactions) || is_null($order_tranactions->order_item_id)) {
			$v3_schema_object->error = "Transactions details not found for item ID " . $orderItemId;
			return $v3_schema_object;
		}

		$dbd = findObjectById($order_details->timeline, 'event', 'dispatched_date');
		$isReplacement = (isset($order_details->parent_replacement_item) && !is_null($order_details->parent_replacement_item)) ? "true" : "false";
		$replacementOrderItemId = ($isReplacement ? substr($order_details->parent_replacement_item, 0, 17) : "");
		$on_hold = "false";
		$is_mps = "false";
		$payment_type = $order_details->order_type == "postpaid" ? "cod" : $order_details->order_type;
		$v3_schema = '
			{
				"shipmentId": null,
				"dispatchByDate": "' . date('Y-m-d H:i:s', strtotime($dbd->event_date)) . '",
				"dispatchAfterDate": "' . date('Y-m-d H:i:s', strtotime($dbd->event_date)) . '",
				"updatedAt": "' . date('Y-m-d H:i:s', strtotime($order['Order Date'])) . '",
				"locationId": "' . $warehouse_id . '",
				"hold": 0,
				"mps": ' . $is_mps . ',
				"subShipments": [
				{
					"subShipmentId": "SS-1",
					"packages": ""
				}],
				"orderItemId": "' . $orderItemId . '",
				"orderId": "' . $order['Order ID'] . '",
				"cancellationGroupId": "grp' . $orderItemId . '",
				"orderDate": "' . date('Y-m-d H:i:s', strtotime($order['Order Date'])) . '",
				"paymentType": "' . strtoupper($payment_type) . '",
				"status": "SHIPPED",
				"quantity": ' . $order['Qty'] . ',
				"fsn": "' . $order['Product ID'] . '",
				"sku": "' . $order['SKU ID'] . '",
				"listingId": "' . $order_details->listing_id . '",
				"hsn": "' . $order_details->hsn_code . '",
				"title": "' . $order['Product Title'] . '",
				"packageIds": [],
				"sla": "1",
				"priceComponents":
				{
					"sellingPrice": ' . ($order_details->order_item_value->sale_amount->net_amount) . ',
					"shippingCharge": ' . (int)($order_details->order_item_value->sale_amount->customer_paid_shipping_revenue) . ',
					"totalPrice": ' . ($order_details->order_item_value->net_amount) . ',
					"customerPrice": ' . ($order_details->order_item_value->sale_amount->net_amount - $order_details->order_item_value->sale_amount->customer_paid_shipping_revenue) . ',
					"flipkartDiscount": ' . ($order_details->total_offer->net_amount - $order_details->seller_share->net_amount) . '
				},
				"serviceProfile": "FBF",
				"is_replacement": ' . $isReplacement . ',
				"replacementOrderItemId": "' . $replacementOrderItemId . '",
				"forms": [],
				"stateDocuments": [],
				"shipmentType": "NORMAL",
				"deliveryAddress":
				{
					"firstName": "' . $order['Customer Name(Shipping Address)'] . '",
					"lastName": "",
					"pinCode": "' . $order['Pincode (Shipping Address)'] . '",
					"city": "' . $order['City (Shipping Address)'] . '",
					"state": "' . $order['State (Shipping Address)'] . '",
					"stateCode": "' . get_stateCode($order['State (Shipping Address)']) . '",
					"addressLine1": "' . ($retry ? $order['Address (Shipping Address)'] : addslashes($order['Address (Shipping Address)'])) . '",
					"addressLine2": "",
					"landmark": "",
					"contactNumber": ""
				},
				"billingAddress":
				{
					"firstName": "' . $order['Customer Name(Billing Address)'] . '",
					"lastName": "",
					"pinCode": "' . $order['Pincode (Billing Address)'] . '",
					"city": "' . $order['City (Billing Address)'] . '",
					"state": "' . $order['State (Billing Address)'] . '",
					"stateCode": "' . get_stateCode($order['State (Billing Address)']) . '",
					"addressLine1": "' . ($retry ? $order['Address (Billing Address)'] : addslashes($order['Address (Billing Address)'])) . '",
					"addressLine2": "",
					"landmark": "",
					"contactNumber": ""
				},
				"buyerDetails":
				{
					"firstName": "' . $order['Customer Name(Billing Address)'] . '",
					"lastName": ""
				},
				"tracking_subShipments": [
				{
					"subShipmentId": "SS-1",
					"courierDetails":
					{
						"pickupDetails":
						{
							"trackingId": "",
							"vendorName": ""
						},
						"deliveryDetails":
						{
							"trackingId": "",
							"vendorName": ""
						}
					},
					"shipmentDimensions":
					{
						"length": "' . $order_tranactions->forward_shipping_details[0]->weight_details->length . '",
						"breadth": "' . $order_tranactions->forward_shipping_details[0]->weight_details->breadth . '",
						"height": "' . $order_tranactions->forward_shipping_details[0]->weight_details->height . '",
						"weight": "' . $order_tranactions->forward_shipping_details[0]->weight_details->seller_dead_weight . '"
					}
				}],
				"invoice_details":
				{
					"invoiceDate": "' . date('Y-m-d H:i:s', strtotime($order['Invoice Date'])) . '",
					"invoiceNumber": "' . $order['Invoice ID'] . '",
					"invoiceAmount": "' . $order['Total Amount'] . '",
					"sgstRate": "' . number_format(floatval($order['SGST Unit tax']), 2, '.', '') . '",
					"cgstRate": "' . number_format(floatval($order['CGST Unit tax']), 2, '.', '') . '",
					"igstRate": "' . number_format(floatval($order['IGST Unit tax']), 2, '.', '') . '"
				},
				"is_shopsy": ' . (int)$order_details->shopsy_order . '
			}';
		$v3_schema_j = json_decode(str_replace(array('	', "\n"), '', $v3_schema));
		if (json_last_error() != JSON_ERROR_NONE) {
			$v3_schema_object = $this->order_details_convert_fbf_order_to_v3_api_response($order, $warehouse_id, true);
			return $v3_schema_object;
		}
		$v3_schema_object->shipments = array($v3_schema_j);

		return $v3_schema_object;
	}

	// RETURNS
	function get_returns_details($return_id)
	{
		$url = 'https://seller.flipkart.com/napi/returns/searchReturnsV2?return_id=' . $return_id . '&isMPSOrLargeSeller=true&shipment_type=NORMAL&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		return json_decode($response);
	}

	function get_digital_pod($date, $logistic = "Ekart Logistics")
	{ // date-format = yyyy-mm-dd
		$url = 'https://seller.flipkart.com/napi/returns/downloadBulkProofOfPickup?sellerId=' . $this->fk_account->seller_id . '&date=' . $date . '&vendor_name=' . urlencode($logistic) . '&locationId=' . $this->fk_account->locationId;
		$response = $this->send_request($url);
		$j_response = json_decode($response);
		if (json_last_error() == JSON_ERROR_NONE)
			return array('type' => 'error', 'msg' => $j_response->errors[0]->message);

		return array('type' => 'success', 'data' => $response);
	}

	// INCIDENT MANAGER
	function get_incident_status($incidentId)
	{
		global $db;

		$url = 'https://seller.flipkart.com/napi/case-manager/issue-thread?refNum=' . $incidentId . '&SC=SELLER_SELF_SERVE&flow=GA&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		$response_j = json_decode($response);

		if ($response_j->result->displayStatus == 'SELLER_ACTION_PENDING') {
			$status = "seller_action_pending";
		} else if (strpos($response_j->result->displayStatus, 'PENDING') !== FALSE || $response_j->result->displayStatus == "OPEN" || is_null($response_j->result->displayStatus)) {
			$status = "pending";
		} else {
			$status = $response_j->result->displayStatus;
		}

		// if($status == 'pending'){
		// 	$return = array('type' => 'info', 'message' => 'No update in incident status.');
		// } else {
		$db->where('claimID', $incidentId);
		if ($db->update(TBL_FK_CLAIMS, array('claimStatusFK' => strtolower($status))))
			$return = array('type' => 'success', 'message' => 'Incident status update to ' . $status);
		else
			$return = array('type' => 'success', 'message' => 'Incident status update to ' . $status . ' but unable to update the claim status');
		// }

		return $return;
	}

	function get_incident_content($incidentId)
	{
		global $db;

		$url = 'https://seller.flipkart.com/napi/case-manager/issue-thread?refNum=' . $incidentId . '&SC=SELLER_SELF_SERVE&flow=GA&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		$response_j = json_decode($response);

		return $response_j;
	}

	// CASE MANAGER
	function get_ticket_instanceId()
	{
		$url = 'https://seller.flipkart.com/napi/ga/createInstance?sellerId=' . $this->fk_account->seller_id;

		$payload = '{"payload":{"flowDefinitionKey":"GA","userId":"' . $this->fk_account->seller_id . '","source":"BROWSE"},"sellerId":"' . $this->fk_account->seller_id . '"}';
		$response = $this->send_request($url, $payload);
		$response = json_decode($response);
		$instance_id = $response->result->instanceId;

		return $instance_id;
	}

	function create_ticket($issueType, $mandatory_fields, $subject, $content, $insert = true, $attachments = array(), $instance_id = "")
	{
		global $db, $log, $current_user;

		if (empty($instance_id))
			$instance_id = $this->get_ticket_instanceId();
		$url = 'https://seller.flipkart.com/napi/ga/createIncident?sellerId=' . $this->fk_account->seller_id;
		$alt_issueType = "";
		$table = TBL_FK_INCIDENTS;

		if ($issueType == "I_AM_UNHAPPY_WITH_THE_SPF_SETTLED_AMOUNT")
			$payload = '{"body":{"additionalFields":[{"name":"CHANNEL_NAME","value":"SSS_WEB_NEW"},{"name":"SELLER_EMAIL","value":"' . $this->fk_account->account_email . '"},{"name":"CLAIM_INCIDENT_ID","value":"' . $mandatory_fields['base_incident'] . '"},{"name":"CALLBACK_NUMBER","value":"+91' . str_replace('-', '', $this->fk_account->account_mobile) . '"}],"attachments":[],"issueType":"I_AM_UNHAPPY_WITH_THE_SPF_SETTLED_AMOUNT","sourceClient":"SELLER_SELF_SERVE","threads":[{"contentType":"TEXT_PLAIN","createdBy":"SELLER","text":"' . $content . '"}],"subject":"' . $subject . '"},"instanceId":"' . $instance_id . '","sellerId":"' . $this->fk_account->seller_id . '"}';
		if ($issueType == "WHY_MY_RETURN_ORDER_IS_NOT_RECEIVED")
			$payload = '{"body":{"additionalFields":[{"name":"SELLER_ID","value":"' . $this->fk_account->seller_id . '"},{"name":"CHANNEL_NAME","value":"SSS_WEB_NEW"},{"name":"SELLER_EMAIL","value":"' . $this->fk_account->account_email . '"},{"name":"ORDER_ID","value":"' . $mandatory_fields['order_id'] . '"},{"name":"TRACKING_ID","value":"' . $mandatory_fields['tracking_id'] . '"},{"name":"CALLBACK_NUMBER","value":"+91' . str_replace('-', '', $this->fk_account->account_mobile) . '"}],"attachments":[],"issueType":"' . $issueType . '","sourceClient":"SELLER_SELF_SERVE","threads":[{"contentType":"TEXT_PLAIN","createdBy":"SELLER","text":"' . $content . '"}],"subject":"Why my return order is not received?"},"instanceId":"' . $instance_id . '","sellerId":"' . $this->fk_account->seller_id . '"}';
		if ($issueType == "I_HAVE_NOT_RECEIVED_THE_PRODUCT_POST_60_DAYS_HOW_TO_CLAIM_SPF") {
			// $payload = '{"body":{"additionalFields":[{"name":"SELLER_ID","value":"'.$this->fk_account->seller_id.'"},{"name":"CHANNEL_NAME","value":"SSS_WEB_NEW"},{"name":"SELLER_EMAIL","value":"'.$this->fk_account->account_email.'"},{"name":"ORDER_ID","value":"'.$mandatory_fields['order_id'].'"},{"name":"CALLBACK_NUMBER","value":"+91'.str_replace('-', '', $this->fk_account->account_mobile).'"}],"attachments":[],"issueType":"'.$issueType.'","sourceClient":"SELLER_SELF_SERVE","threads":[{"contentType":"TEXT_PLAIN","createdBy":"SELLER","text":"'.$content.'"}],"subject":"Re: I have not received the product, post 60 days. How to claim SPF?"},"instanceId":"'.$instance_id.'","sellerId":"'.$this->fk_account->seller_id.'"}';
			$payload = '{"body":{"additionalFields":[{"name":"CHANNEL_NAME","value":"SSS_WEB_NEW"},{"name":"SELLER_EMAIL","value":"' . $this->fk_account->account_email . '"},{"name":"ORDER_ID","value":"' . $mandatory_fields['order_id'] . '"},{"name":"CALLBACK_NUMBER","value":"+91' . str_replace('-', '', $this->fk_account->account_mobile) . '"}],"attachments":[],"issueType":"' . $issueType . '","sourceClient":"SELLER_SELF_SERVE","threads":[{"contentType":"TEXT_PLAIN","createdBy":"SELLER","text":"' . $content . '"}],"subject":"I have not received the product, post 60 days. How to claim SPF?"},"instanceId":"' . $instance_id . '","sellerId":"' . $this->fk_account->seller_id . '"}';
		}
		if ($issueType == "I_WANT_TO_VERIFY_MY_REPLACEMENT_ORDER_DETAILS" || $issueType == "I_WANT_TO_VERIFY_THE_ORDER_QUANTITY" || $issueType == "I_HAVE_RECEIVED_A_DUPLICATE_ORDER") {
			$payload = '{"body":{"additionalFields":[{"name":"SELLER_ID","value":"' . $this->fk_account->seller_id . '"},{"name":"CHANNEL_NAME","value":"SSS_WEB_NEW"},{"name":"SELLER_EMAIL","value":"' . $this->fk_account->account_email . '"},{"name":"ORDER_ID","value":"' . $mandatory_fields['order_id'] . '"},{"name":"CALLBACK_NUMBER","value":"+91' . str_replace('-', '', $this->fk_account->account_mobile) . '"}],"attachments":[],"issueType":"' . $issueType . '","sourceClient":"SELLER_SELF_SERVE","threads":[{"contentType":"TEXT_PLAIN","createdBy":"SELLER","text":"' . $content . '"}],"subject":"' . $subject . '"},"instanceId":"' . $instance_id . '","sellerId":"' . $this->fk_account->seller_id . '"}';
			$alt_issueType = 'Marketplace cancellation requests';
		}
		if ($issueType == "WHY_WAS_MY_PREVIOUS_CLAIM_REJECTED_EVEN_THOUGH_I_HAD_FULFILLED_ALL_THE_REQUIREMENTS_FROM_MY_END") {
			$payload = '';
		}
		if ($issueType == "SPF Claim") {
			$table = TBL_FK_CLAIMS;
			if (!$mandatory_fields['is_return_delivered']) {
				$attachment_json = array();
				foreach (json_decode($mandatory_fields['attachments'], 1) as $attachments) {
					$attachment_json[] = array(
						'fileId' => substr($attachments['path'], strrpos($attachments['path'], '/') + 1),
						'fileName' => $attachments['fileName']
					);
				}
				$payload = '{"body":{"additionalFields": [{"name": "CHANNEL_NAME","value": "SSS_WEB_NEW"},{"name": "SELLER_EMAIL","value": "' . $this->fk_account->account_email . '"},{"name": "ORDER_ID","value": "' . $mandatory_fields['order_id'] . '"},{"name": "CALLBACK_NUMBER","value": "+91' . str_replace('-', '', $this->fk_account->account_mobile) . '"}],"attachments": ' . json_encode($attachment_json) . ',"issueType": "MY_RETURN_STATUS_HAS_NOT_CHANGED_SO_UNABLE_TO_CLAIM_SPF","sourceClient": "SELLER_SELF_SERVE","threads": [{"contentType": "TEXT_PLAIN","createdBy": "SELLER","text": "' . $content . '"}],"subject": "' . $subject . '"},"instanceId": "' . $instance_id . '","sellerId": "' . $this->fk_account->seller_id . '"}';
			} else {
				$url = 'https://seller.flipkart.com/case_management/sourceclient/SPF/issues?sellerId=' . $this->fk_account->seller_id;
				$payload = '{"SELLER_EMAIL":{"response": "' . $this->fk_account->account_email . '","isAdditionalField": true},"subject":{"response": "' . $subject . '","isAdditionalField": false},"CALLBACK_NUMBER":{"response": "+91' . str_replace('-', '', $this->fk_account->account_mobile) . '","isAdditionalField": true},"ORDER_ID":{"response": "' . $mandatory_fields["order_id"] . '"},"ORDER_ITEM_ID":{"response": "' . $mandatory_fields["order_item_id"] . '"},"sellerId":{"response": "' . $this->fk_account->seller_id . '","isAdditionalField": true},"issueTypeName":{"response": "SPF Claim","isAdditionalField": false},"SPF_PRODUCT_TYPE":{"response": "' . $mandatory_fields["product_type"] . '","isAdditionalField": false},"SPF_PRODUCT_ISSUE_TYPE":{"response": "' . $subject . '","isAdditionalField": false},"content":{"response": "' . $content . '","isAdditionalField": false},"attachments":{"response": ' . $mandatory_fields["attachments"] . '},"REQUEST_CALLBACK":{"isAdditionalField": true},"CLAIM_INCIDENT_ID":{"isAdditionalField": true},"fileUploadData":{},"fileUploadRequired":{}}';
			}
		}

		$request_response = $this->send_request($url, $payload);
		// $response = '{"referenceNumber":"IN2107021731181108724","displayStatus":"OPEN","issueType":3485,"subject":"I received a damaged product","actionsAllowed":{"REOPEN":false,"TAKE_SURVEY":false,"REQUEST_CALLBACK":false,"CLOSE":false,"VIEW":true,"REPLY":true},"threads":[{"id":1491373452,"createdBy":"SELLER","text":"Dear Team, \n\nOrder ID: OD122087228110479000\nItem ID: 12208722811047900\nReturn ID: 12102213757137637728\nSPF Reason: Scratches on Dial + Scratches on Belt + Display Damage + Physical Damage + Used &amp; Dusty Dial + Dusty Belt + Rust marks\nKindly reimburse the loss.\n\nRegards, \nORM Team (IK)","contentType":"TEXT_HTML","createdDate":"2021-07-02T17:31:18.000"}],"attachments":[{"id":124466847,"fileName":"OD122087228110479000 (10).jpg","fileUploadUrl":"http://10.24.0.64/attachments/2ec43f29-f039-418c-8ed1-f1cdc12f9e03","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466855,"fileName":"OD122087228110479000 (7).jpg","fileUploadUrl":"http://10.24.0.64/attachments/0fb547ea-c200-40fc-a878-e076801254b0","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466849,"fileName":"OD122087228110479000 (9).jpg","fileUploadUrl":"http://10.24.0.64/attachments/5ccff5ab-667b-4b7d-88ef-5f74e3d6def2","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466854,"fileName":"OD122087228110479000 (6).jpg","fileUploadUrl":"http://10.24.0.64/attachments/5d8a7446-18f1-4be8-9448-94538b1b6f5f","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466851,"fileName":"OD122087228110479000 (4).jpg","fileUploadUrl":"http://10.24.0.64/attachments/b0dcc114-0875-45e4-89c9-72c1b409b300","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466846,"fileName":"OD122087228110479000 (3).jpg","fileUploadUrl":"http://10.24.0.64/attachments/cbbf0849-0997-4aa0-936a-931ca72f0e0e","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466848,"fileName":"OD122087228110479000 (2).jpg","fileUploadUrl":"http://10.24.0.64/attachments/d82d9f2a-8653-4d62-ab4b-33994b5c5cf0","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466850,"fileName":"OD122087228110479000 (8).jpg","fileUploadUrl":"http://10.24.0.64/attachments/e82c0640-466d-4317-b34f-5af0f2e431e2","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466852,"fileName":"OD122087228110479000 (5).jpg","fileUploadUrl":"http://10.24.0.64/attachments/fc54115b-a9b6-4b5b-87ac-db4f743be2c2","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0},{"id":124466853,"fileName":"OD122087228110479000 (1).jpg","fileUploadUrl":"http://10.24.0.64/attachments/ddcc33a1-7119-46f2-b25c-5a09fb7ae181","updatedDate":null,"createdDate":"2021-07-02T17:31:18.000","size":0}],"createdDate":"2021-07-02T17:31:18.000","resolvedDate":null,"sourceClient":"SPF","sellerId":"382b6085cb78407a","callbackNumber":"+919687660234","orderId":"OD122087228110479000","orderItemId":"12208722811047900"}';
		$log->write("PAYLOAD: " . $payload . "\nResponse: " . $request_response, 'incidents');
		$incidentId = "";
		if (strpos($request_response, 'Failed to create ticket') === FALSE) {
			$response = json_decode($request_response);
			if ($issueType == "SPF Claim" && $mandatory_fields['is_return_delivered'])
				$incidentId = $response->referenceNumber;
			else
				$incidentId = $response->result->issueId;
		}

		if ($incidentId != "" && !is_null($incidentId)) {
			if ($insert) {
				$a = array_flip(explode(',', $mandatory_fields['order_item_id']));
				$output_ids = array_map(function ($val) {
					return false;
				}, $a);

				if ($issueType == "SPF Claim") {
					$details = array(
						"orderItemId" => $mandatory_fields['order_item_id'],
						"returnId" => $mandatory_fields['return_id'],
						"claimID" => $incidentId,
						"claimStatus" => "pending",
						"claimStatusFK" => "pending",
						"claimDate" => $db->now(),
						"claimProductCondition" => $mandatory_fields['product_condition'],
						"createdBy" => $current_user['userID'],
						// "createdDate" => $db->now(),
					);
				} else {
					$details = array(
						"incidentId" => $incidentId,
						"accountId" => $this->fk_account->account_id,
						"referenceId" => json_encode($output_ids),
						"issueType" => $alt_issueType != "" ? $alt_issueType : str_replace("_", " ", $issueType),
						"subject" => $subject,
						"createdBy" => $current_user['userID'],
						"incidentStatus" => "active",
						"createdDate" => $db->now(),
					);
				}

				if ($db->insert($table, $details)) {
					$return["type"] = "success";
					$return["message"] = "Incident successfully created with id " . $incidentId;
					$return["incidentId"] = $incidentId;
				} else {
					$return["type"] = "error";
					$return["message"] = "Error inserting incident. " . $db->getLastError()/*. " :: ".$db->getLastQuery()*/;
					$return["incidentId"] = "";
				}
			} else {
				$return["type"] = "success";
				$return["message"] = "Incident successfully created with id " . $incidentId;
				$return["incidentId"] = $incidentId;
			}
		} else {
			$return["type"] = "error";
			$return["message"] = "Error creating incident. Please try later.";
			$return["incidentId"] = "";
			$return["response"] = $request_response;
		}

		return $return;
	}

	/**
	 * Create Flipkart Ticket
	 * @param string $issueTypeId 
	 * @param string $orderItemIds [comma seperated ids]
	 * @param string $subject 
	 * @param string $content 
	 * @param array $attachments [optional]
	 * @param boolen $insert [default: true]
	 * @return array
	 */
	function create_ticket_v1($issueTypeId, $orderItemIds, $subject, $content, $attachments = array(), $insert = true)
	{
		global $db, $log;

		$url = 'https://seller.flipkart.com/api/issueServices/submitIssue?sellerId=' . $this->fk_account->seller_id;

		$payload = '{"issueType":"' . $issueTypeId . '","sourceClient":"SELLER_SELF_SERVE","subject":"' . $subject . '","additionalFields":[{"name":"SELLER_EMAIL","value":"' . $this->fk_account->account_email . '"},{"name":"CALLBACK_NUMBER","value":"+91' . $this->fk_account->account_mobile . '"},{"name":"sellerId","value":"' . $this->fk_account->seller_id . '"},{"name":"ORDER_ID","value":""},{"name":"ORDER_ITEM_ID","value":""},{"name":"TRACKING_ID","value":""},{"name":"FSN","value":""},{"name":"FEED_ID","value":""},{"name":"NFT","value":""},{"name":"ACTUAL_WEIGHT","value":""},{"name":"SELLER_NAMES","value":""},{"name":"SKU_ID","value":""},{"name":"BRAND_NAME","value":""},{"name":"RECALL_ID","value":""},{"name":"CONSIGNMENT_ID","value":""},{"name":"LISTING_ID","value":""},{"name":"PROMOTION_NAME","value":""},{"name":"CAMPAIGN_NAME","value":""},{"name":"TXN_NUMBER","value":""},{"name":"CLAIM_INCIDENT_ID","value":""},{"name":"NEW_EMAIL_ID","value":""},{"name":"NEW_ADDRESS_WITH_PINCODE","value":""},{"value":"SSS_WEB_NEW","name":"CHANNEL_NAME","type":"STRING"}],"threads":[{"text":"' . $content . '","createdBy":"SELLER","contentType":"TEXT_PLAIN"}],"attachments":[],"sellerId":"' . $this->fk_account->seller_id . '"}';
		$response = $this->send_request($url, $payload);
		$log->write($response, 'incidents');
		$response = json_decode($response);
		$incidentId = $response->result->issueId;
		if ($incidentId != "" || !is_null($incidentId)) {
			$issueType = $response->result->category->subCategory->issueType;
			if (is_null($issueType))
				$issueType = 'Marketplace cancellation requests';

			$a = array_flip(explode(',', $orderItemIds));
			$output_ids = array_map(function ($val) {
				return false;
			}, $a);
			/*$a = explode(',', $orderItemIds);
			$output_ids = array_map(function($val) { return false; }, $a);
			$ids_arr = array();
			foreach (array_intersect(array_keys($a), array_keys($output_ids)) as $key) {
				$ids_arr[$key]['orderItemId'] = $a[$key];
				$ids_arr[$key]['status'] = $output_ids[$key];
			}*/
			$details = array(
				"incidentId" => $incidentId,
				"accountId" => $this->fk_account->account_id,
				"referenceId" => json_encode($output_ids),
				"issueType" => $issueType,
				"subject" => $subject,
				"createdBy" => 0,
				// "createdBy" => $currentUser->userId,
				"incidentStatus" => "active",
				"createdDate" => date("c"),
			);

			if ($insert) {
				if ($db->insert(TBL_FK_INCIDENTS, $details)) {
					$return["type"] = "success";
					$return["message"] = "Incident successfully created with id " . $incidentId;
					$return["incidentId"] = $incidentId;
				} else {
					$return["type"] = "error";
					$return["message"] = "Error inserting incident. " . $db->getLastError()/*. " :: ".$db->getLastQuery()*/;
					$return["incidentId"] = "";
				}
			} else {
				$return["type"] = "success";
				$return["message"] = "Incident successfully created with id " . $incidentId;
				$return["incidentId"] = $incidentId;
			}
		} else {
			$return["type"] = "error";
			$return["message"] = "Error creating incident. Please try later.";
			$return["incidentId"] = "";
		}

		return $return;
	}

	function create_spf_attachements($file)
	{
		$url = 'https://seller.flipkart.com/documents/upload/case-manager-temp';
		$delimiter = '----WebKitFormBoundary' . uniqid();
		$payload = $this->create_post($delimiter, array(
			'seller' => $this->fk_account->seller_id
		), array(
			'file' => array(
				'filename' => $file['file']['name'],
				'type' => $file['file']['type'],
				'content' => fopen($file['file']['tmp_name'], 'rb'),
			)
		));
		$header = array(
			'Content-Type: multipart/form-data; boundary=' . $delimiter,
		);
		$response = $this->send_request($url, $payload, array(), "Accept: application/json, text/javascript, text/plain, */*; q=0.01", array(), $header);
		return json_decode($response);
		// return json_decode('{"code":415,"message":"HTTP 415 Unsupported Media Type"}');		
	}

	// SELLER SMART
	function add_fbf_quantity($lid, $qty, $binId)
	{

		$url = 'https://seller.flipkart.com/napi/fbfLite/inventory?listingId=' . $lid . '&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		$response_j = json_decode($response);
		$fsn = substr($lid, 3, 16);
		if ($response_j->listingState == "ACTIVE") {
			$url = 'https://seller.flipkart.com/napi/fbfLite/inwardProducts?sellerId=' . $this->fk_account->seller_id;
			$payload = '{"listingId":"' . $lid . '","inventory":[{"binId":"' . $binId . '","quantity":' . $qty . '}],"length":"","breadth":"","height":"","weight":"","taxRate":"","updateLBHW":false,"stateCode":"' . $response_j->stateCode . '","fsn":"' . $fsn . '","pincode":"' . $response_j->pincode . '","sellerId":"' . $this->fk_account->seller_id . '"}';
			$response = $this->send_request($url, $payload);
			return $response;
		} else {
			return 'LISTING INACTIVE LID: ' . $lid . ' FSN: ' . $fsn;
		}
	}

	// FLIPKAT FULFILMENT
	function get_ff_orders_report($warehouse_id, $dates = array())
	{
		if (empty($dates)) {
			$dates = array(
				'from_date' => date('Y-m-d', strtotime('-3 days')),
				'to_date' => date('Y-m-d', strtotime('-2 days'))
			);
		}
		// var_dump($dates);
		// $url = 'https://seller.flipkart.com/napi/sfx/reports/generateReport?sellerId='.$this->fk_account->seller_id;
		// $payload = '{"from_date":"'.$dates['from_date'].'","to_date":"'.$dates['to_date'].'","warehouse_id":"'.$warehouse_id.'","report_id":"2","sellerId":"'.$this->fk_account->seller_id.'"}';
		// $response = $this->send_request($url, $payload);
		$response = '{"result":"90b60ec8-fa1f-4dd8-990c-21401e357d13"}';
		$response = json_decode($response);
		$response = $response->result;
		$details = array(
			"reportId" => $response,
			"reportStatus" => 'queued',
			"reportType" => 'Flipkart Fulfilment',
			"reportSubType" => 'Orders Report',
			"reportFromDate" => $dates["from_date"],
			"reportToDate" => $dates["to_date"],
			"accountId" => $this->fk_account->account_id,
		);

		if ($db->insert(TBL_FK_REPORTS, $details)) {
			$details['response'] = $response;
			$details['status'] = 'Inserted';
			return $details;
		} else {
			$details['response'] = $response;
			$details['status'] = 'Already In Que';
			return $details;
		}
		return $response;
	}

	// ADS
	/**
	 * Description
	 * @param text $filter [status=SERVICEABLE&]
	 * @return json
	 */
	function get_ads($filter = "")
	{
		$url = "https://seller.flipkart.com/napi/ads/campaign/list?" . $filter . "page_size=10&page_number=1";
		$response = $this->send_request($url);
		return json_decode($response);
	}

	function get_ad_details($adId)
	{
		$url = "https://seller.flipkart.com/napi/ads/campaign/" . $adId . "/get";
		$response = $this->send_request($url);
		return json_decode($response);
	}

	/**
	 * Get Ads Group Details
	 * @param integer $commodityId 
	 * @param datetime $date [2019-03-26T21:48:32+05:30]
	 * @return json
	 */
	function get_adgroups_details($commodityId, $date)
	{
		$url = "https://seller.flipkart.com/napi/ads/campaign/get_commodity_pricing";
		$payload = '{"commodityIds":["' . $commodityId . '"],"campaignStartDate":"' . $date . '"}';
		$response = $this->send_request($url, $payload);
		return json_decode($response);
	}

	/**
	 * Update CPC of the ads by Ads ID
	 * @param json $payload 
	 * @return json
	 */
	function update_cpc($adId, $payload)
	{
		$url = 'https://seller.flipkart.com/napi/ads/campaign/' . $adId . '/edit';
		$response = $this->send_request($url, $payload, array(), 'application/json, text/javascript, */*; q=0.01', "PUT");
		return $response;
	}

	// LISTING MANAGEMENT
	function get_usual_price($lid)
	{
		// $url = "https://seller.flipkart.com/napi/listing/anchorPrice?listingIds=".$lid;
		$url = "https://seller.flipkart.com/napi/listing/pricingInfo?listingIds=" . $lid;
		$response = $this->send_request($url);
		$return = json_decode($response);
		if ($return->{$lid}->usual_price == "NA")
			$return->{$lid}->usual_price = $return->{$lid}->final_price;

		return $return->{$lid}->usual_price;
	}

	// API
	function get_api_access()
	{
		$fk_curl = curl_init();

		// VISIT PAGE
		$url = "https://api.flipkart.net/oauth-register/login";
		curl_setopt_array($fk_curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_FOLLOWLOCATION => false,
		));

		$response = curl_exec($fk_curl);
		$info = curl_getinfo($fk_curl);
		$err = curl_error($fk_curl);

		// GET CSRF TOKEN
		preg_match('/name=\"CSRFToken\" value=\"([^"]+)\"/', $response, $matches);
		$CSRFToken = $matches[1];

		// GET COOKIES
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}

		// SET COOKIE STRING
		$cookies = implode(';', array_map(
			function ($v, $k) {
				return sprintf("%s=%s", $k, $v);
			},
			$cookies,
			array_keys($cookies)
		));

		// CREATE USER CREDENTIAL POST DATA
		$post_data = "j_username=" . urlencode($this->fk_account->account_email) . "&j_password=" . openssl_decrypt($this->fk_account->account_password, "AES-128-CBC", $this->secret_key) . "&CSRFToken=" . $CSRFToken . "&login=";
		$header = array(
			"Cookie: " . $cookies,
			"content-type: application/x-www-form-urlencoded",
		);

		var_dump($header);

		curl_setopt_array($fk_curl, array(
			CURLOPT_URL => "https://api.flipkart.net/oauth-register/login.do",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $post_data,
			CURLOPT_HTTPHEADER => $header,
			// CURLOPT_VERBOSE => true,
		));

		$response = curl_exec($fk_curl);
		// $info = curl_getinfo($fk_curl);
		// $err = curl_error($fk_curl);

		// GET COOKIES
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		$cookies = array();
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}

		// SET COOKIE STRING
		$cookies = implode(';', array_map(
			function ($v, $k) {
				return sprintf("%s=%s", $k, $v);
			},
			$cookies,
			array_keys($cookies)
		));

		// GET REGISTER APPLICATION PAGE
		$header = array(
			"Cookie: " . $cookies,
		);

		var_dump($header);

		curl_setopt_array($fk_curl, array(
			CURLOPT_URL => "https://api.flipkart.net/oauth-register/register",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => $header,
		));

		$response = curl_exec($fk_curl);
		$info = curl_getinfo($fk_curl);
		$err = curl_error($fk_curl);

		var_dump($err);
		var_dump($info);
		var_dump($response);

		// curl_close($fk_curl);
		// echo $response;


		// 
		// curl_setopt_array($curl, array(
		// 	CURLOPT_URL => "https://api.flipkart.net/oauth-register/register",
		// 	CURLOPT_RETURNTRANSFER => true,
		// 	CURLOPT_ENCODING => "",
		// 	CURLOPT_MAXREDIRS => 10,
		// 	CURLOPT_TIMEOUT => 0,
		// 	CURLOPT_FOLLOWLOCATION => true,
		// 	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		// 	CURLOPT_CUSTOMREQUEST => "POST",
		// 	CURLOPT_POSTFIELDS => "appName=FIMS_token2&appDescription=FIMS_token2&appType=self_access_application&scopes=Seller_Api&redirectURL=&CSRFToken=18771d19-fd50-44d3-88b3-4903ab5432f9",
		// 	CURLOPT_HTTPHEADER => array(
		// 		"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
		// 		"Accept-Encoding: gzip, deflate, br",
		// 		"Accept-Language: en-US,en;q=0.9,gu;q=0.8,hi;q=0.7,pl;q=0.6",
		// 		"Cache-Control: no-cache",
		// 		"Connection: keep-alive",
		// 		"DNT: 1",
		// 		"Host: api.flipkart.net",
		// 		"Pragma: no-cache",
		// 		"Referer: https://api.flipkart.net/oauth-register/applications",
		// 		"Sec-Fetch-Dest: document",
		// 		"Sec-Fetch-Mode: navigate",
		// 		"Sec-Fetch-Site: same-origin",
		// 		"Sec-Fetch-User: ?1",
		// 		"Upgrade-Insecure-Requests: 1",
		// 		"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36",
		// 		"Content-Type: application/x-www-form-urlencoded",
		// 		"Cookie: JSESSIONID=E752CA644CA24E7409D0B8402A3F9EF9"
		// 	),
		// ));

		// $response = curl_exec($curl);

		// curl_close($curl);
		// echo $response;



		// preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
		// $cookies = array();
		// foreach($matches[1] as $item) {
		// 	parse_str($item, $cookie);
		// 	$cookies = array_merge($cookies, $cookie);
		// }

		// $cookies = implode(';', array_map(
		// 	function ($v, $k) { return sprintf("%s=%s", $k, $v); },
		// 	$cookies,
		// 	array_keys($cookies)
		// ));

		// // STAGE 3
		// $url = "https://api.flipkart.net/oauth-register/register";
		// $post_data = "appName=FIMS&appDescription=FIMS&appType=self_access_application&scopes=Seller_Api&redirectURL=https%3A%2F%2Fwww.skmienterprise.com%2Ffims%2Fflipkart%2Fnotifications-receiver.php%3Faccount%3D3&CSRFToken=".$CSRFToken;

		// $header = array(
		// 	"accept: */*",
		// 	"accept-encoding: gzip, deflate, br",
		// 	"accept-language: en-US,en;q=0.5",
		// 	"cache-control: no-cache",
		// 	"connection: keep-alive",
		// 	"content-length: ".strlen($post_data),
		// 	"content-type: application/x-www-form-urlencoded",
		// 	"host: api.flipkart.net",
		// 	"referer: https://seller.flipkart.com/",
		// 	"user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:54.0) Gecko/20100101 Firefox/54.0",
		// );

		// curl_setopt_array($fk_curl, array(
		// 	CURLOPT_URL => "https://api.flipkart.net/oauth-register/login.do",
		// 	CURLOPT_RETURNTRANSFER => true,
		// 	CURLOPT_HEADER => true,
		// 	CURLOPT_MAXREDIRS => 10,
		// 	CURLOPT_TIMEOUT => 30,
		// 	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		// 	CURLOPT_CUSTOMREQUEST => "POST",
		// 	CURLOPT_POSTFIELDS => $post_data,
		// 	CURLOPT_HTTPHEADER => $header,
		// ));

		// $response = curl_exec($fk_curl);
		// $info = curl_getinfo($fk_curl);
		// $err = curl_error($fk_curl);
	}

	// KEYWORD JUNCTION
	function update_listing_keywords($fsn, $sku, $keywords)
	{
		// CREATE DRAFT
		$url = "https://seller.flipkart.com/napi/edit-product/create-draft?productId=" . $fsn . "&skuId=" . $sku;
		$draft_response = $response['create_draft'] = $this->send_request($url);
		$draft_response = json_decode($draft_response);
		$requestId = $draft_response->requestId;

		// GET DRAFT
		$url = "https://seller.flipkart.com/napi/edit-product/get-drafts?requestIds=" . $requestId;
		$draft = $response['get_draft'] = $this->send_request($url);
		$draft = json_decode($draft);

		// var_dump($draft);
		// exit;

		// GET SUMMARY
		$url = "https://seller.flipkart.com/napi/edit-product/get-summary?requestIds=" . $requestId;
		$response['summary'] = $this->send_request($url);

		$draft[0]->catalogRequestEntity->catalogAttributes->keywords = array();
		foreach ($keywords as $keyword) {
			$draft[0]->catalogRequestEntity->catalogAttributes->keywords[] = array("value" => $keyword['value'], "qualifier" => null);
		}
		// $brand = new stdClass();
		// $brand->value = 'Skmei';
		// $brand->qualifier = null;
		// $draft[0]->catalogRequestEntity->catalogAttributes->brand[0] = $brand;
		// $brand_id = new stdClass();
		// $brand_id->value = 'BRBX24T282KE';
		// $brand_id->qualifier = null;
		// $draft[0]->catalogRequestEntity->catalogAttributes->brand_id[0] = $brand_id;
		// $draft[0]->skuId = "SKMEI-1270-BLUE-236";
		$payload = new stdClass();
		$payload->model = $draft[0];

		// SAVE LISTING
		$url = "https://seller.flipkart.com/napi/edit-product/save-draft";
		$payload = json_encode($payload);
		$save_listing = $this->send_request($url, $payload);
		$response['save_listing'] = $save_listing;
		$save_listing_j = json_decode($save_listing);
		if (isset($save_listing_j->error) && !empty($save_listing_j->error->message)) {
			echo '<br />' . $save_listing_j->error->message . '<br />';
			return $response;
		}

		// GET DRAFT
		$url = "https://seller.flipkart.com/napi/edit-product/get-drafts?requestIds=" . $requestId;
		$draft = $response['update_draft'] = $this->send_request($url);
		// $response['update_draft'] = json_decode($draft);

		// GET SUMMARY
		$url = "https://seller.flipkart.com/napi/edit-product/get-summary?requestIds=" . $requestId;
		$response['update_summary'] = $this->send_request($url);

		// SUBMIT LISTING
		$url = "https://seller.flipkart.com/napi/edit-product/submit-draft";
		$payload = json_decode($draft);
		$listing_update = $response['submit_listing'] = $this->send_request($url, json_encode($payload[0]));
		// $response['submit_listing'] = json_decode($listing);
		$listing_update = json_decode($listing_update);

		if (isset($listing_update->error))
			$response['error_message'] = $listing_update->error->message;

		return $response;
	}

	function get_search_terms($search_term)
	{
		$url = "https://1.rome.api.flipkart.com/api/4/discover/autosuggest";
		$payload = '{"query":"' . $search_term . '","contextUri":"/","marketPlaceId":"FLIPKART","types":["QUERY","QUERY_STORE","PRODUCT","RICH","PARTITION"],"rows":10,"zeroPrefixHistory":false,"userTimeStamp":' . round(microtime(true) * 1000) . ',"searchBrowseHistory":[]}';
		$request = $this->send_external_request($url, $payload);
		$response = json_decode($request);
		if ($response->RESPONSE->suggestions)
			return $response->RESPONSE->suggestions;
		else
			return false;
	}

	function get_current_keywords($search_term, $ignore = array())
	{
		$ignore_keys = array('sonata', 'fastrack', 'titan', 'style feathers', 'style');
		if (!empty($ignore))
			$ignore_keys = array_merge($ignore_keys, $ignore);

		$keywords = array();
		foreach ($search_term as $search) {
			$keywords_suggestions = $this->get_search_terms($search);
			sleep(2);
			if ($keywords_suggestions) {
				foreach ($keywords_suggestions as $keywords_data) {
					if (isset($keywords_data->data->component->value->backFill)) {
						$keyword = $keywords_data->data->component->value->backFill;
						if (preg_match('(' . implode('|', $ignore_keys) . ')', $keyword) === 0 && !in_array($keyword, $keywords))
							$keywords[] = $keyword;
					}
				}
			}
		}
		return $keywords;
	}

	// GET RATE CARD
	/**
	 * Update CPC of the ads by Ads ID
	 * @param string $date, YYYY-MM-DD format// 2020-06-22
	 * @param string $fsn,
	 * @return json
	 */
	function get_flipkart_rate_card($date, $fsn)
	{
		$url = 'https://seller.flipkart.com/napi/rate-card/fetchRateCardFees?service_profile=NON_FBF&date=' . $date . '&fsn=' . $fsn . '&sellerId=' . $this->fk_account->seller_id;
		$response = $this->send_request($url);
		return json_decode($response, true);
	}

	// CLASS GLOBAL USAGE
	function create_post($delimiter, $postFields, $fileFields = array())
	{
		// form field separator
		$eol = "\r\n";
		$data = '';
		// populate normal fields first (simpler)
		foreach ($postFields as $name => $content) {
			$data .= "--$delimiter" . $eol;
			$data .= 'Content-Disposition: form-data; name="' . $name . '"';
			$data .= $eol . $eol; // note: double endline
			$data .= $content;
			$data .= $eol;
		}
		// populate file fields
		foreach ($fileFields as $name => $file) {
			$data .= "--$delimiter" . $eol;
			// fallback on var name for filename
			if (!array_key_exists('filename', $file)) {
				$file['filename'] = $name;
			}
			// "filename" attribute is not essential; server-side scripts may use it
			$data .= 'Content-Disposition: form-data; name="' . $name . '";' .
				' filename="' . $file['filename'] . '"' . $eol;
			// this is, again, informative only; good practice to include though
			$data .= 'Content-Type: ' . $file['type'] . $eol;
			// this endline must be here to indicate end of headers
			$data .= $eol;
			// the file itself (note: there's no encoding of any kind)
			if (is_resource($file['content'])) {
				// rewind pointer
				rewind($file['content']);
				// read all data from pointer
				while (!feof($file['content'])) {
					$data .= fgets($file['content']);
				}
				$data .= $eol;
			} else {
				// check if we are loading a file from full path
				if (strpos($file['content'], '@') === 0) {
					$file_path = substr($file['content'], 1);
					$fh = fopen(realpath($file_path), 'rb');
					if ($fh) {
						while (!feof($fh)) {
							$data .= fgets($fh);
						}
						$data .= $eol;
						fclose($fh);
					}
				} else {
					// use data as provided
					$data .= $file['content'] . $eol;
				}
			}
		}
		// last delimiter
		$data .= "--" . $delimiter . "--$eol";
		return $data;
	}
}
