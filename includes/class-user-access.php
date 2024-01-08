<?php
include_once(ROOT_PATH . '/includes/vendor/autoload.php');

use DeviceDetector\DeviceDetector;

/**
 * 
 */
class userAccess
{
	/*** Declare instance ***/
	private static $instance = NULL;
	private $cron;
	private $is_bot;
	private $secret_salt;
	public $is_loggedIn;
	public $header;
	public $max_session_limit = 28800; //seconds
	public $current_user;
	public $is_disabled;
	public $is_trustedDevice;

	function __construct()
	{
		$this->current_user = NULL;
		$this->is_loggedIn = false;
		$this->is_disabled = false;
		$this->is_trustedDevice = false;
		$this->is_bot = false;
		$this->secret_salt = '4420d1918bbcf7686defdf9560bb5087d20076de5f77b7cb4c3b40bf46ec428b';
		$this->cron = [
			// "::1", // LOCALHOST
			"195.201.26.157", // CRONJOB.ORG
			"116.203.134.67", // CRONJOB.ORG
			"116.203.129.16", // CRONJOB.ORG
			"23.88.105.37", // CRONJOB.ORG
			"103.4.255.80", // FK PUSH NOTIFICATION
			"3.6.159.149", // QR.AI
			"13.126.133.67" // QR.AI
		];
	}

	public function sec_session_start()
	{
		$session_name = 'fims_sid';   // Set a custom session name 
		$secure = false;
		if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443)
			$secure = true;

		$httponly = true; // This stops JavaScript being able to access the session id.

		if (session_status() != PHP_SESSION_ACTIVE) {
			// Forces sessions to only use cookies.
			if (ini_set('session.use_only_cookies', 1) === TRUE) {
				header("Location: " . BASE_URL . "/login/?error=Could not initiate a safe session. Please try again.");
				exit();
			}

			// Gets current cookies params.
			$cookieParams = session_get_cookie_params();
			session_set_cookie_params($this->max_session_limit, "/", $cookieParams["domain"], $secure, $httponly);

			// Sets the session name to the one set above.
			session_name($session_name);

			// Start Session
			session_start();
			// session_write_close();		   // Close session for cocurrent scripts to work
			// session_regenerate_id();	   // regenerated the session, delete the old one.
		}
	}

	public function sec_session_destroy()
	{
		// Unset all session values 
		$_SESSION = array();

		// get session parameters 
		$params = session_get_cookie_params();

		// Delete the actual cookie. 
		setcookie(
			session_name(),
			'',
			time() - 42000,
			$params["path"],
			$params["domain"],
			$params["secure"],
			$params["httponly"]
		);

		// Destroy session 
		session_destroy();
	}

	public function sec_session_reset()
	{
		session_start();
		$this->sec_session_destroy();
		$this->sec_session_start();
	}

	public function login($username, $password, $remember)
	{
		global $db;

		$db->where('user_login', $username);
		// Using prepared statements means that SQL injection is not possible. 
		if ($this->current_user = $db->getOne(TBL_USERS)) {
			$user_id = $this->current_user['userID'];
			// If the user exists we check if the account is locked from too many login attempts 
			if ($this->checkbrute($user_id) == true) {
				// Account is locked. Send an email to user saying their account is locked
				return array('status' => false, 'message' => 'To many login attempts. Retry after 2 hours.');
			} else {
				// Check if the password in the database matches the password the user submitted.
				if ($password == $this->current_user['user_pass']) { // Password is correct!
					// Start Session
					$this->sec_session_start();

					// Get the user-agent string of the user.
					// $user_agent = $this->get_user_agent();

					// XSS protection as we might print this value
					$user_id = preg_replace("/[^0-9]+/", "", $user_id);
					$_SESSION['user_id'] = $user_id;

					// XSS protection as we might print this value
					$username = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $username);
					$_SESSION['username'] = $username;

					$_SESSION['login_string'] = hash('sha512', $this->current_user['user_pass'] . $username . $this->secret_salt);
					$_SESSION['timeout'] = (time() + (86400 * 7)); // 7 DAYS

					if ($remember) {
						setcookie('un', $username, (time() + (86400 * 365)), "/"); // 1 Year
						setcookie('up', $password, (time() + (86400 * 365)), "/"); // 1 Year
					} else {
						setcookie('un', '', (time() - 3600), "/");
						setcookie('up', '', (time() + 3600), "/");
					}

					// Login successful.
					$_SESSION['is_loggedIn'] = $this->is_loggedIn = true;

					// Trusted device
					$_SESSION['is_trustedDevice'] = $this->is_trustedDevice = $this->check_trusted_device();

					// Disabled
					$_SESSION['is_disabled'] = $this->is_disabled = boolval($this->current_user['user_status']);

					// REMOVE BRUTE DATA ON SUCCESSFUL LOGIN
					$db->where('user_id', $user_id);
					$db->delete(TBL_LOGIN_ATTEMPTS);

					session_write_close();
				} else {
					// Password is not correct. We record this attempt in the database
					$details = array(
						'user_id' => $user_id,
						'time' => time()
					);
					$db->insert(TBL_LOGIN_ATTEMPTS, $details);
					return array('status' => false, 'message' => 'Invalid password');
				}
			}
		} else {
			return array('status' => false, 'message' => 'Invalid username'); // No user exists.
		}
	}

	private function checkbrute($user_id)
	{
		global $db;
		// Get timestamp of current time 
		$now = time();

		// All login attempts are counted from the past 2 hours. 
		$valid_attempts = $now - (2 * 60 * 60);

		$db->where('user_id', $user_id);
		$db->where('time', $valid_attempts, '>');

		if ($logins = $db->withTotalCount()->has(TBL_LOGIN_ATTEMPTS)) {
			// If there have been more than 5 failed logins 
			if ($logins > 5) {
				return true;
			} else {
				return false;
			}
		}
	}

	public function login_check()
	{
		global $db;

		// Start Session
		$this->sec_session_start();

		// Set Header
		$this->header = apache_request_headers();
		// Check if all session variables are set 
		if (isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['login_string'])) {
			$user_id = $_SESSION['user_id'];
			$login_string = $_SESSION['login_string'];
			$username = $_SESSION['username'];

			// Get the user-agent string of the user.
			// $user_agent = $this->get_user_agent();

			$db->where('userID', $user_id);
			if ($this->current_user = $this->get_current_user_details()) {
				$login_check = hash('sha512', $this->current_user['user_pass'] . $username . $this->secret_salt);

				if (hash_equals($login_check, $login_string)) {
					$this->is_loggedIn = true;
					$this->is_trustedDevice = $this->check_trusted_device();
					$this->is_disabled = boolval($this->current_user['user_status']);
				} elseif ($_SESSION['timeout'] + (10 * 60) < time()) {
					$this->is_loggedIn = false;
				} else {
					$this->is_loggedIn = false;
				}
			} else {
				// Not logged in 
				$this->is_loggedIn = false;
			}
		} elseif ($this->is_bot()) { // Bot Check
			$this->is_bot = true;
			$this->is_loggedIn = true;
			$this->is_trustedDevice = true;
			return;
		} elseif ($this->is_api()) { // API Check
			$this->is_loggedIn = true;
			$this->is_trustedDevice = true;
			return;
		} else {
			// Not logged in 
			$this->is_loggedIn = false;
		}

		session_write_close();
	}

	public function is_api()
	{
		global $log;
		if (isset($this->header['Referer']) && $this->header['Referer'] == 'https://sylvi.in/')
			$referrer = 'api.sylvi.in';
		else if (isset($this->header['Referer']) && $this->header['Referer'] == 'https://thefira.com/')
			$referrer = 'api.thefira.com';
		else if (isset($this->header['referrer'], $this->header['Referer']) && $this->header['referrer'] == $this->header['Referer'])
			$referrer = $this->header['Referer'];
		else if (isset($this->header['referrer'], $this->header['Referer']) && $this->header['referrer'] != $this->header['Referer'])
			$referrer = $this->header['referrer'];
		else if (isset($this->header['referrer']))
			$referrer = $this->header['referrer'];
		else
			$referrer = $this->header['Referer'];

		if ($referrer == "api.sylvi.in" || $referrer == "api.thefira.com" || (isset($referrer, $this->header['x-api-key'], $this->header['x-api-secret']) && $this->validateAPICredentials($referrer, $this->header['x-api-key'], $this->header['x-api-secret']))) {
			$this->current_user = array(
				"userID" => 0,
				"display_name" => $referrer,
				"user_role" => "api",
				"user_status" => 0,
			);
			$log->write('authorised', '/api/header');
			return true;
		}
		$log->write("unauthorised\r\n" . json_encode($this->header), '/api/header');
		return false;
	}

	public function is_bot()
	{
		if (in_array($this->getIPAddress(), $this->cron)) {
			$this->current_user = array(
				"userID" => 0,
				"display_name" => "cron_bot",
				"user_role" => "bot",
				"user_status" => 0,
			);
			return true;
		}
		return false;
	}

	public function checkCapability($page, $menu_capabilities = false)
	{

		$default_allowed_capabilities = array('reset_password', 'verify');

		$user_capabilities = $this->getUserCapabilities($this->current_user['userID'], $menu_capabilities);
		$capabilities = array();
		foreach ($user_capabilities as $capability) {
			if (is_array($capability)) {
				$capabilities = array_merge($capabilities, $capability);
			}
		}

		if (check_key_exist_multi($capabilities, $page))
			return true;

		return false;
	}

	public function getUserCapabilities($user_id, $menu_capabilities = false, $user_role = "")
	{
		global $db;

		if ($this->is_bot)
			return [];

		$db->where('userID', $user_id);
		$user_data = $db->get(TBL_USERS, 1, array('user_capabilities', 'user_role'));

		if (empty($user_role))
			$user_role = $user_data[0]['user_role'];

		$role_capabilities = $this->getCapabilitiesByRole($user_role);

		$capabilities['role_capabilities'] = $role_capabilities;
		$capabilities['special_capabilities'] = json_decode($user_data[0]['user_capabilities'], true);

		if ($menu_capabilities) {
			if (isset($capabilities['special_capabilities'])) {
				foreach ($capabilities['special_capabilities'] as $cap_key => $cap_value) {
					if (array_key_exists($cap_key, $capabilities['role_capabilities']))
						$capabilities['role_capabilities'][$cap_key] = array_merge_recursive($capabilities['role_capabilities'][$cap_key], $cap_value);
					else
						$capabilities['role_capabilities'][$cap_key] = $cap_value;
				}
				unset($capabilities['special_capabilities']);
			}
		}

		return $capabilities;
	}

	public function getCapabilitiesByRole($role)
	{
		$roles = json_decode(get_option('user_roles'), true);

		return $roles[$role];
	}

	public function generateValidationString($type)
	{
		if ($type == "email") {
			$return = md5(rand(0, 1000));
		}
		if ($type == "mobile") {
			$return = rand(100000, 900000);
		}

		return $return;
	}

	public function generateAPICredentials($host)
	{
		$response['key'] = $key = base64_encode('api-' . $host . '-key');
		$response['secret'] = base64_encode('api-' . $key . '-secret');
		return $response;
	}

	public function validateAPICredentials($host, $key, $secret)
	{
		$decodedSecret = base64_decode($secret);
		$decodedKey = base64_decode($key);

		if ($decodedKey == 'api-' . $host . '-key' && $decodedSecret == 'api-' . $key . '-secret')
			return true;

		return false;
	}

	public function sendVerificationLink($userID, $type = "", $additionalContent = "")
	{
		global $db, $notification, $sms;

		$db->where('userID', $userID);
		$details = $db->getOne(TBL_USERS, array('user_email', 'user_login', 'user_activation_key', 'display_name', 'user_otp', 'user_mobile', 'party_id', 'user_role'));
		$data = new stdClass();
		$data->medium = 'email';
		$to_emails = array(
			"email" => $details['user_email'],
			'name' => $details['display_name']
		);
		$data->to[0] = (object)$to_emails;

		if ($type == "reset_password") {
			$verification_link = BASE_URL . '/recover/reset_password.php?token=' . $details['user_activation_key'];
			$data->body = str_replace(array("##DISPLAYNAME##", "##EMAILTO##", "##EMAILVERIFICATIONLINK##"), array($details['display_name'], $details['user_email'], $verification_link), get_template('email', 'reset_password'));
			$data->subject = 'Reset Password | FIMS';
			$notification->send($data);
		} elseif ($type == "email_verification") {
			$activeEmailClientId = null;
			if (isset($details["activeEmailClientId"]))
				$activeEmailClientId = $details["activeEmailClientId"];
			else {
				$db->where("option_key", "active_email_account_id");
				$activeEmailClientId = $db->getValue(TBL_OPTIONS, "option_value");
			}
			$data = array();
			$db->where("emailClientId", $activeEmailClientId);
			$data["from"] = $db->getOne(TBL_CLIENTS_EMAIL, "emailClientFromEmail as email, emailClientFromName as name");
			$data["subject"] = "Email Verification";
			$data["medium"] = array("email");
			$data["link"] = BASE_URL . '/recover/verify.php?case=email_verification&token=' . $details['user_activation_key'];
			$data["templateId"] = get_template_id_by_name("email_verification");
			$data["active_email_id"] = $activeEmailClientId;
			$data["identifierType"] = "userID";
			$data["identifierValue"] = $userID;
			$notification = new Notification();
			$notification->send($data);
		} elseif ($type == "mobile_verification") {
			$data = array();
			$db->where("option_key", "active_sms_account_id");
			$data["active_sms_client"] = $db->getValue(TBL_OPTIONS, "option_value");
			$data["medium"] = ["sms"];
			$data["identifierType"] = "user_login";
			$data["identifierValue"] = $details["user_login"];
			$data["type"] = $type;
			$data["contactNumber"] = "91" . $details["user_mobile"];
			$notification = new Notification();
			$notification->send($data);
		} elseif ($type == "device_approval") {
			$data = array();
			$db->where("option_key", "active_sms_account_id");
			$data["active_sms_client"] = $db->getValue(TBL_OPTIONS, "option_value");
			$data["medium"] = ["sms"];
			$data["identifierType"] = "user_login";
			$data["identifierValue"] = $details["user_login"];
			$data["type"] = $type;
			$data["contactNumber"] = "91" . $details["user_mobile"];
			$notification = new Notification();
			$notification->send($data);
		} else {
			$data = array();
			$db->where("option_key", "active_sms_account_id");
			$data["active_sms_client"] = $db->getValue(TBL_OPTIONS, "option_value");
			$data["type"] = "mobile_verification";
			$data["contactNumber"] = "91" . $details["user_mobile"];
			
			$activeEmailClientId = null;
			if (isset($details["activeEmailClientId"]))
				$activeEmailClientId = $details["activeEmailClientId"];
			else {
				$db->where("option_key", "active_email_account_id");
				$activeEmailClientId = $db->getValue(TBL_OPTIONS, "option_value");
			}
			$data = array();
			$db->where("emailClientId", $activeEmailClientId);
			$data["from"] = $db->getOne(TBL_CLIENTS_EMAIL, "emailClientFromEmail as email, emailClientFromName as name");
			$data["subject"] = "Email Verification";
			$data["medium"] = array("email", "sms");
			$data["link"] = BASE_URL . '/recover/verify.php?case=email_verification&token=' . $details['user_activation_key'];
			$data["templateId"] = get_template_id_by_name("email_verification");
			$data["active_email_id"] = $activeEmailClientId;
			$data["identifierType"] = "userID";
			$data["identifierValue"] = $userID;
			
			$notification = new Notification();
			$ssms = $notification->send($data);

			if ($ssms['type'] == "success") {
				$sms_status = true;
				$response['data']['sms']['sent'] = true;
				$response['data']['sms']['message'] = 'SMS Successfully sent';
				$response['data']['sms']['message_id'] = $ssms['message_id'];
			} else {
				$sms_status = false;
				$response['data']['sms']['sent'] = false;
				$response['data']['sms']['message'] = $ssms['message'];
			}
		}
	}

	private function get_current_user()
	{
		global $db;

		$this->sec_session_start();
		if (isset($_SESSION['user_id'])) {
			$db->where('userID', $_SESSION['user_id']);
			return $this->get_current_user_details();
		} else {
			return null;
		}
		session_write_close();
	}

	private function get_current_user_details()
	{
		global $db;

		$user = $db->getOne(TBL_USERS);
		unset($user['updatedDate']);
		unset($user['createdDate']);
		if ($user['party_id']) {
			$db->where('party_id', $user['party_id']);
			$user['party'] = $db->getOne(TBL_PARTIES);
		}

		return $user;
	}

	private static function get_user_agent()
	{
		return $_SERVER['HTTP_USER_AGENT'];
	}

	private function check_trusted_device()
	{
		global $db;

		$device_details = $this->get_device_details();
		$db->where('user_device_expiry > ? OR user_device_expiry IS NULL', array(time()));
		$db->where('user_device_client', $device_details['client']);
		$db->where('user_device_type', $device_details['type']);
		$db->where('user_device_brand', $device_details['brand']);
		$db->where('user_device_os', $device_details['os']);
		$db->where('user_device_os_version', $device_details['os_version']);
		$db->where('user_id', $this->current_user['userID']);
		if ($db->has(TBL_USER_DEVICES))
			return true;

		return false;
	}

	public function addTrustedDevice($expiry = null)
	{
		global $db;

		$device_details = $this->get_device_details();
		$details = array(
			'user_device_useragent' => $device_details['user_agent'],
			'user_device_client' => $device_details['client'],
			'user_device_client_verion' => $device_details['client_verion'],
			'user_device_type' => $device_details['type'],
			'user_device_brand' => $device_details['brand'],
			'user_device_os' => $device_details['os'],
			'user_device_os_version' => $device_details['os_version'],
			'user_device_model' => $device_details['model'],
			'user_id' => $this->current_user['userID']
		);

		if (!is_null($expiry))
			$details['user_device_expiry'] = $expiry;

		if ($db->insert(TBL_USER_DEVICES, $details))
			return true;

		return false;
	}

	public function getIPAddress()
	{
		$ipaddress = '';
		if (getenv('HTTP_CLIENT_IP'))
			$ipaddress = getenv('HTTP_CLIENT_IP');
		else if (getenv('HTTP_X_FORWARDED_FOR'))
			$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
		else if (getenv('HTTP_X_FORWARDED'))
			$ipaddress = getenv('HTTP_X_FORWARDED');
		else if (getenv('HTTP_FORWARDED_FOR'))
			$ipaddress = getenv('HTTP_FORWARDED_FOR');
		else if (getenv('HTTP_FORWARDED'))
			$ipaddress = getenv('HTTP_FORWARDED');
		else if (getenv('REMOTE_ADDR'))
			$ipaddress = getenv('REMOTE_ADDR');
		else
			$ipaddress = '';

		return $ipaddress;
	}

	public function get_location_details()
	{
		$ipaddress = $this->getIPAddress();
		if (!empty($ipaddress))
			$ipaddress = '?ip=' . $ipaddress;

		$location = json_decode(file_get_contents("http://www.geoplugin.net/json.gp" . $ipaddress));

		$return = "";
		if ($location->geoplugin_status == 200) {

			$return = array(
				'ip' => $location->geoplugin_request,
				'city' => $location->geoplugin_city,
				'state' => $location->geoplugin_region,
				'country' => $location->geoplugin_countryName,
				'lat' => $location->geoplugin_latitude,
				'lon' => $location->geoplugin_longitude,
			);
		}
		return $return;
	}

	public function get_device_details()
	{
		$user_agent = $this->get_user_agent();
		$dd = new DeviceDetector();
		$dd->setUserAgent($user_agent);
		$dd->parse();

		$return = array();
		if ($dd->isBot()) {
			$return['botInfo'] = $dd->getBot();
		} else {
			$clientInfo = $dd->getClient();
			$osInfo = $dd->getOs();

			$return = array(
				'user_agent' => $user_agent,
				'client' => $clientInfo['name'],
				'client_verion' => $clientInfo['version'],
				'os' => $osInfo['name'],
				'os_version' => $osInfo['version'],
				'type' => ucfirst($dd->getDeviceName()),
				'brand' => $dd->getBrandName(),
				'model' => $dd->getModel(),
				$clientInfo['type'] => $clientInfo['name'] . ' v' . $clientInfo['version'], // MAY CHANGE IF CLIENT TYPE IS DIFFERENT e.g. BOT
			);
		}

		return $return;
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new userAccess;
		}
		return self::$instance;
	}
}
