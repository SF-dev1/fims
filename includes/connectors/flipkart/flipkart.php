<?php
// include(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
include(ROOT_PATH . '/includes/vendor/autoload.php');
if (file_exists(ROOT_PATH . '/includes/connectors/flipkart/flipkart-payments.php'))
	include(ROOT_PATH . '/includes/connectors/flipkart/flipkart-payments.php');

class connector_flipkart
{

	private $api_url = "";
	private $account = "";
	private $orders = array();
	private $returns = array();
	private $correct_sku = array();
	private $wrong_sku = array();
	private $retries = 2;
	private $sandbox = false;
	public $payments = "";
	function __construct($fk_account, $sandbox = false, $force = false)
	{
		global $api_url, $correct_sku, $wrong_sku, $log;

		$this->account = $fk_account;
		if (class_exists('flipkart_payments'))
			$this->payments = new flipkart_payments($this->account);

		if ($this->account->sandbox)
			$this->sandbox = true;

		if (date('H', time()) == '03') {
			$this->delete_old_files(ROOT_PATH . '/log/' . date("Y", time()) . '/');
			$this->delete_old_files(ROOT_PATH . '/labels/');
			$this->delete_old_files(ROOT_PATH . '/packlists/' . date("Y", time()) . '/');
		}

		//
		$api_url = "https://api.flipkart.net/sellers";
		if ($sandbox)
			$api_url = "https://sandbox-api.flipkart.net/sellers";
	}

	/** FK API CORE SECTION BEGINS */
	function fk_run_curl($url, $request, $header, $post = array(), $error = false)
	{
		global $log, $sandbox, $debug;

		// if (empty($access_token) )
		// 	$GLOBALS['access_token'] = $this->check_access_token();

		// sleep(0.5);
		if (isset($log->logfile))
			$old_logfile = $log->logfile;

		$listing = '';
		if (strrpos($url, 'listings') !== FALSE)
			$listing = 'listings-';

		$test = '';
		if (isset($_GET['test']) && $_GET['test'] == 1)
			$test = '-test';

		if ($sandbox)
			$test .= '-sandbox';

		$curl = curl_init();

		$curl_array = array(
			CURLOPT_URL => $url,
			// CURLOPT_HEADER => true,
			CURLOPT_VERBOSE => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $request,
			CURLOPT_HTTPHEADER => $header,
		);

		// Requires json formated data in $post
		$post_data = "";
		if (!empty($post) && $request == "POST") {
			$curl_array += array(
				CURLOPT_POSTFIELDS => $post,
				CURLOPT_POST => true
			);
			$post_data = "\n:: Payload:" . $post . " ";
		}

		curl_setopt_array($curl, $curl_array);
		$response = curl_exec($curl);
		$info = curl_getinfo($curl);
		$errno = curl_errno($curl);
		$err = curl_error($curl);

		curl_close($curl);
		$return_j = json_decode($response);

		$details = $this->account->account_name . " \n:: URL: " . $url . " \n:: Request Header: " . json_encode($header) . " \n:: Response Header: " . json_encode($info) . " \n:: Type: " . $request . " " . $post_data . " \n:: Response: " . $response;
		if (isset($_GET['test']) && $_GET['test'] == 1) {
			echo '<pre>';
			debug_print_backtrace();
			var_dump($response);
		}
		$log->write($details, 'api-request-' . $listing . $this->account->account_name . $test);

		if (strpos($response, "<html>") !== FALSE) {
			$error = "Error with API. Error: " . $response;
			$log->write($error, 'api-request-' . $listing . $this->account->account_name . $test);
			return $error;
		}

		if ($err) {
			$response = array(
				'error' => "cURL Error #" . $errno . ": " . $err,
			);
			return json_encode($response);
		} elseif (isset($return_j->error) && $return_j->error !== null) {

			if ($return_j->error == "invalid_token" && !$error) {
				$access_token = $this->check_access_token(true);
				$this->fk_run_curl($url, $request, $header, $post, true);
			} else {
				echo $error = "Error with API. Error: " . $response;
				$log->write($error, 'api-request-' . $listing . $this->account->account_name . $test);
				return;
			}
		} else {
			if (isset($old_logfile)) {
				$log->logfile = $old_logfile;
			}
			return $response;
		}
	}

	function check_access_token($force = false)
	{
		global $db;

		if ($force) {
			return $this->get_access_token();
		}

		if (isset($this->account->access_token) && !empty($this->account->access_token) && $this->account) {
			if ($this->account->access_expiry < time() || empty($this->account->access_expiry)) {
				return $this->get_access_token();
			} else {
				return $this->account->access_token;
			}
		} else {
			return $this->get_access_token();
		}
	}

	function get_access_token()
	{
		global $db, $sandbox;

		$url = "https://api.flipkart.net/oauth-service/oauth/token?grant_type=client_credentials&scope=Seller_Api";
		if ($sandbox)
			$url = "https://sandbox-api.flipkart.net:443/oauth-service/oauth/token?grant_type=client_credentials&scope=Seller_Api";
		$request = "GET";
		$header = array(
			"Authorization: Basic " . base64_encode($this->account->app_id . ':' . $this->account->app_secret),
			"cache-control: no-cache",
		);

		$curl = curl_init();

		$curl_array = array(
			CURLOPT_URL => $url,
			// CURLOPT_HEADER => true,
			CURLOPT_VERBOSE => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $request,
			CURLOPT_HTTPHEADER => $header,
		);

		curl_setopt_array($curl, $curl_array);
		$response = curl_exec($curl);

		curl_close($curl);

		// $response = $this->fk_run_curl($url, $request, $header);

		if (strpos($response, 'Error') !== true && !is_null($response)) {
			$resp = json_decode($response);
			$access_token = $resp->access_token;
			$access_expiry = time() + $resp->expires_in;
			$data = array(
				'access_token' => $access_token,
				'access_expiry' => $access_expiry,
				'api_status' => true
			);
			$db->where('account_id', $this->account->account_id);
			$db->update(TBL_FK_ACCOUNTS, $data);
			return $access_token;
		} else {
			echo $response;
			$db->where('account_id', $this->account->account_id);
			$db->update(TBL_FK_ACCOUNTS, array('api_status' => false));
			throw new Exception($response, 1);
		}
	}

	/** FK PRODUCT API CALLBACK SECTION BEGINS */
	function get_item_details($sku = '')
	{
		global $api_url;

		$curl = curl_init();
		$sku = curl_escape($curl, $sku);
		curl_close($curl);
		$url = $api_url . "/skus/" . rawurlencode($sku) . "/listings"; // not on v2 API
		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$item_json = $this->fk_run_curl($url, $request, $header);

		return json_decode($item_json);
	}

	function get_products_details($sku)
	{ // v3 API
		global $api_url;

		if (empty($sku))
			return;

		$curl = curl_init();
		$sku = curl_escape($curl, $sku);
		curl_close($curl);
		$url = $api_url . "/listings/v3/" . $sku;
		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$item_json = $this->fk_run_curl($url, $request, $header);

		return json_decode($item_json);
	}

	function get_product_by_lisitng($listing_id)
	{
		global $api_url;

		$url = $api_url . "/skus/listings/" . $listing_id; // not on v2 API
		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$item_json = $this->fk_run_curl($url, $request, $header);

		return json_decode($item_json);
	}

	function create_listings($products)
	{
		global $api_url;

		if (empty($products)) {
			return 'No product values sent';
		}

		$url = $api_url . "/skus/listings/bulk";
		$request = "POST";
		$listings = array();
		foreach ($products as $product) {
			$listing['skuId'] = $product['sku'];
			$listing['fsn'] = $product['fsn'];
			$listing['attributeValues'] = $product['attribute_value'];
			$listings[] = $listing;
		}
		$post = new stdClass();
		$post->listings = $listings;

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$product = $this->fk_run_curl($url, $request, $header, json_encode($post));

		return $product; // SANDBOX
	}

	function create_test_order($products)
	{
		global $api_url;

		if (empty($products)) {
			return 'No product values sent';
		}

		var_dump($products);

		$url = $api_url . "/orders/sandbox/test_orders";
		$request = "POST";
		$orderItems = array();
		foreach ($products as $product) {
			$listing['listingId'] = $product['listingId'];
			$listing['quantity'] = $product['quantity'];
			$orderItems[] = $listing;
		}
		$post = new stdClass();
		$post->shipmentType = "";
		$post->orderItems = $orderItems;

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$product = $this->fk_run_curl($url, $request, $header, json_encode($post));

		return $product; // SANDBOX
	}

	/**
	 * Allowed attribute vales in array
	 * array(
	 *   "mrp" => "549",
	 *    "selling_price" => "119",
	 *    "listing_status" => "ACTIVE",
	 *    "fulfilled_by" => "seller",
	 *    "national_shipping_charge" => "100",
	 *    "zonal_shipping_charge" => "70",
	 *    "local_shipping_charge" => "50",
	 *    "procurement_type" => "express", //REGULAR, EXPRESS, MADE_TO_ORDER, DOMESTIC, and INTERNATIONAL
	 *    "procurement_sla" => "2",
	 *    "stock_count" => "100",
	 *    "package_length" => "25",
	 *    "package_breadth" => "17",
	 *    "package_height" => "3",
	 *    "package_weight" => "0.200",
	 *	  "hsn" => "6209",
	 *	  "selling_region_restriction": "none",
	 *);
	 */
	function update_product_v2($sku, $fsn, $attributeValues = array())
	{
		global $api_url;

		if (empty($attributeValues)) {
			return 'No Attribute values sent';
		}

		if (empty($sku)) {
			$db->where('fsn', $fsn);
			$db->where('account_id', $this->account->account_id);
			$sku = $db->getValue(TBL_FK_ORDERS, 'sku');
		}

		$url = $api_url . "/skus/" . rawurlencode($sku) . "/listings/";
		$request = "POST";
		$post = array(
			"fsn" => $fsn,
			"attributeValues" => $attributeValues
		);

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$product = $this->fk_run_curl($url, $request, $header, json_encode($post));

		return $product;
	}

	function update_product($sku, $fsn, $attributeValues = array(), $price_only = true)
	{
		global $api_url;

		if (empty($attributeValues)) {
			return 'No Attributes value sent';
		}

		$product = $this->get_products_details($sku);

		if (isset($product->available)) {
			$url = $api_url . "/listings/v3/update";
			if ($price_only)
				$url = $api_url . "/listings/v3/update/price";
			$request = "POST";
			$post = array(
				$sku => array(
					"product_id" => $fsn,
					"price" => array(
						"selling_price" => $product->available->{$sku}->price->selling_price,
						// "selling_price" => 999,
						"currency" => "INR",
						"mrp" => $product->available->{$sku}->price->mrp,
					),
				),
			);

			if (!$price_only) {
				$post[$sku]["tax"] = $product->available->{$sku}->tax;
				$post[$sku]["listing_status"] = $product->available->{$sku}->listing_status;
				$post[$sku]["fulfillment_profile"] = $product->available->{$sku}->fulfillment_profile;
				$post[$sku]["packages"] = $product->available->{$sku}->packages;
				$post[$sku]["locations"] = $product->available->{$sku}->locations;
			}

			if (isset($attributeValues['selling_price']) && !empty($attributeValues['selling_price'])) {
				$post[$sku]['price']['selling_price'] = $attributeValues['selling_price'];
			}
			if (isset($attributeValues['hsn']) && !empty($attributeValues['hsn']) && isset($attributeValues['tax_code']) && !empty($attributeValues['tax_code'])) {
				$post[$sku]['tax']->hsn = $attributeValues['hsn'];
				$post[$sku]['tax']->tax_code = $attributeValues['tax_code'];
			}
			if (isset($attributeValues['listing_status']) && !empty($attributeValues['listing_status'])) {
				$post[$sku]['listing_status'] = $attributeValues['listing_status'];
			}
			if (isset($attributeValues['shipping_fees']) && !empty($attributeValues['shipping_fees'])) {
				$post[$sku]['shipping_fees'] = $product->available->{$sku}->shipping_fees;
				$post[$sku]['shipping_fees']->local = $attributeValues['shipping_fees']['local'];
				$post[$sku]['shipping_fees']->zonal = $attributeValues['shipping_fees']['zonal'];
				$post[$sku]['shipping_fees']->national = $attributeValues['shipping_fees']['national'];
			}
			if (isset($attributeValues['stock_count']) && !empty($attributeValues['stock_count'])) {
				$post[$sku]["locations"] = $product->available->{$sku}->locations;
				$post[$sku]['locations'][0]->inventory = $attributeValues['stock_count'];
			}

			$header = array(
				"authorization: Bearer " . $this->check_access_token(),
				"content-type: application/json",
				"charset: utf-8",
			);

			$product = $this->fk_run_curl($url, $request, $header, json_encode($post));
		} else {
			$product = json_encode($product);
		}

		return $product;
	}

	function update_product_by_listing($listing_id, $fsn, $attributeValues = array())
	{
		global $api_url;

		if (empty($attributeValues)) {
			return 'No Attribute values sent';
		}

		$url = $api_url . "/skus/listings/" . rawurlencode($listing_id);
		$request = "POST";
		$post = array(
			"fsn" => $fsn,
			"attributeValues" => $attributeValues
		);

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$product = $this->fk_run_curl($url, $request, $header, json_encode($post));

		return $product;
	}

	/** ORDERS API CALLBACK SECTION BEGINS */

	// Get Orders
	function get_to_pack_orders_v3($hasMore = false, $nextPageUrl = '', $custom_parameters = "")
	{
		global $api_url, $orders;

		$url = $api_url . "/v3/shipments/filter/";
		$request = "POST";
		$post = array(
			'filter' => array(
				'type' => 'preDispatch',
				'states' => array(
					'APPROVED', // Todays
					// 'PACKING_IN_PROGRESS',
					// 'PACKED'
					// 'READY_TO_DISPATCH'
				),
				// "serviceProfiles" =>  array("NON_FBF"), // "Seller_Fulfilment",
			),
		);

		if (!empty($custom_parameters) && is_array($custom_parameters)) {
			$post['filter'] += $custom_parameters;
		}

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		if (!$hasMore) {
			$orders_json = $this->fk_run_curl($url, $request, $header, json_encode($post));
			$orders_obj = json_decode($orders_json);
			if (!empty($orders_obj->shipments)) {
				$orders = $orders_obj->shipments;
				if ($orders_obj->hasMore) {
					$this->get_to_pack_orders_v3(true, $orders_obj->nextPageUrl);
				}
			} else {
				echo json_encode($post, JSON_PRETTY_PRINT) . '<br />';
				echo json_encode($orders_json, JSON_PRETTY_PRINT) . '<br />';
			}
		} else {
			$orders_json = $this->fk_run_curl($api_url . $nextPageUrl, 'GET', $header);
			$orders_obj = json_decode($orders_json);
			if ($orders_obj->shipments) {
				$order_items = $orders_obj->shipments;
				$orders = array_merge($orders, $order_items);
				if ($orders_obj->hasMore) {
					$this->get_to_pack_orders_v3(true, $orders_obj->nextPageUrl);
				}
			} else {
				echo json_encode($post, JSON_PRETTY_PRINT) . '<br />';
				echo json_encode($orders_json, JSON_PRETTY_PRINT) . '<br />';
			}
		}

		return $orders;
	}

	function get_to_pack_orders($hasMore = false, $nextPageUrl = '', $custom_parameters = "")
	{
		global $api_url, $orders;

		$url = $api_url . "/v2/orders/search";
		$request = "POST";
		$post = array(
			'filter' => array(
				'states' => array(
					'APPROVED', // Todays
				),
			),
			// "pagination" => array(
			// 	"pageSize" => "1",
			// ),
		);

		if (!empty($custom_parameters) && is_array($custom_parameters)) {
			$post['filter'] += $custom_parameters;
		}

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		if (!$hasMore) {
			$orders_json = $this->fk_run_curl($url, $request, $header, json_encode($post));
			$orders_obj = json_decode($orders_json);
			if (!empty($orders_obj->orderItems)) {
				$orders_v2 = $orders_obj->orderItems;
				$orders = $this->convert_orders_v2_to_v3($orders_v2);
				if ($orders_obj->hasMore) {
					$this->get_to_pack_orders(true, $orders_obj->nextPageUrl);
				}
			} else {
				echo json_encode($post, JSON_PRETTY_PRINT) . '<br />';
				echo json_encode($orders_json, JSON_PRETTY_PRINT) . '<br />';
			}
		} else {
			$orders_json = $this->fk_run_curl($api_url . $nextPageUrl, 'GET', $header);
			$orders_obj = json_decode($orders_json);
			if ($orders_obj->orderItems) {
				$order_items_v2 = $orders_obj->orderItems;
				$order_items = $this->convert_orders_v2_to_v3($order_items_v2);
				$orders = array_merge($orders, $order_items);
				if ($orders_obj->hasMore) {
					$this->get_to_pack_orders(true, $orders_obj->nextPageUrl);
				}
			} else {
				echo json_encode($post, JSON_PRETTY_PRINT) . '<br />';
				echo json_encode($orders_json, JSON_PRETTY_PRINT) . '<br />';
			}
		}

		return $orders;
	}

	function get_to_dispatch_orders($hasMore = false, $nextPageUrl = '')
	{
		global $api_url, $orders;

		$url = $api_url . "/v2/orders/search";
		$request = "POST";
		$post = array(
			'filter' => array(
				'states' => array(
					'PACKED',
				),
			),
		);

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		if (!$hasMore) {
			$orders_json = $this->fk_run_curl($url, $request, $header, json_encode($post));
			$orders_obj = json_decode($orders_json);
			if ($orders_obj->orderItems) {
				$orders = $orders_obj->orderItems;
				if ($orders_obj->hasMore) {
					$this->get_to_dispatch_orders(true, $orders_obj->nextPageUrl);
				}
			}
		} else {
			$orders_json = $this->fk_run_curl($api_url . $nextPageUrl, 'GET', $header);
			$orders_obj = json_decode($orders_json);
			if ($orders_obj->orderItems) {
				$order_items = $orders_obj->orderItems;
				$orders = array_merge($orders, $order_items);
				if ($orders_obj->hasMore) {
					$this->get_to_dispatch_orders(true, $orders_obj->nextPageUrl);
				}
			}
		}

		return $orders;
	}

	function get_to_handover_orders($hasMore = false, $nextPageUrl = '')
	{
		global $api_url, $orders;

		$url = $api_url . "/v2/orders/search";
		$request = "POST";
		$post = array(
			'filter' => array(
				// 'orderDate' => array(
				// 	'fromDate' => date('c', strtotime("today - 10 day")),
				// 	'toDate' => date( 'c', strtotime("tomorrow")-1), // Specify the date
				// ),
				'states' => array(
					'READY_TO_DISPATCH', // All in handover - No date is required
				),
			),
		);

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		if (!$hasMore) {
			$orders_json = $this->fk_run_curl($url, $request, $header, json_encode($post));
			$orders_obj = json_decode($orders_json);
			$orders = $orders_obj->orderItems;
			if ($orders_obj->hasMore) {
				$this->get_to_handover_orders(true, $orders_obj->nextPageUrl);
			}
		} else {
			$orders_json = $this->fk_run_curl($api_url . $nextPageUrl, 'GET', $header);
			$orders_obj = json_decode($orders_json);
			$order_items = $orders_obj->orderItems;
			$orders = array_merge($orders, $order_items);
			if ($orders_obj->hasMore) {
				$this->get_to_handover_orders(true, $orders_obj->nextPageUrl);
			}
		}

		return $orders;
	}

	function get_order_details($shipmentIds, $is_orderItemIds = false, $is_orderIds = false, $retries = 2)
	{
		global $api_url, $debug;

		if ($is_orderItemIds)
			$url = $api_url . "/v3/shipments?orderItemIds=" . rawurlencode(str_replace(' ', '', $shipmentIds)); // should have no white spaces between each shipmentIds
		elseif ($is_orderIds)
			$url = $api_url . "/v3/shipments?orderIds=" . rawurlencode(str_replace(' ', '', $shipmentIds)); // should have no white spaces between each shipmentIds
		else
			$url = $api_url . "/v3/shipments?shipmentIds=" . rawurlencode(str_replace(' ', '', $shipmentIds)); // should have no white spaces between each shipmentIds

		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json",
			"accept: application/json",
		);

		$order_details = $this->fk_run_curl($url, $request, $header);
		$order_details = json_decode($order_details);
		if (isset($order_details->errors)) {
			if ($retries > 0) {
				sleep(1);
				$retries--;
				return $this->get_order_details($shipmentIds, $is_orderItemIds, $is_orderIds, $retries);
			} else {
				return $order_details;
			}
		} else {
			$order_details = $this->arrange_order_details($order_details);
			return $order_details;
		}
	}

	function get_order_shipment_details_v2($orderItemIds, $retries = 2)
	{
		global $api_url;

		if (empty($orderItemIds))
			return 'order item id not found';

		$url = $api_url . "/v2/orders/shipments?orderItemIds=" . $orderItemIds;
		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$order_detail = $this->fk_run_curl($url, $request, $header);
		$order_detail = json_decode($order_detail);
		if (isset($order_detail->errors)) {
			if ($retries > 0) {
				sleep(1);
				$retries--;
				return $this->get_order_shipment_details_v2($orderItemIds, $retries);
			} else {
				return $order_detail;
			}
		}

		return $order_detail;
	}

	function get_order_shipment_details($shipmentIds, $retries = 2)
	{
		global $api_url;

		// $url = $api_url."/v2/orders/shipments?orderItemIds=".$orderItemIds;
		$url = $api_url . "/v3/shipments/" . rawurlencode(str_replace(' ', '', $shipmentIds)); // should have no white spaces between each shipmentIds
		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$order_detail = $this->fk_run_curl($url, $request, $header);
		$order_detail = json_decode($order_detail);
		if (isset($order_detail->errors)) {
			if ($retries > 0) {
				sleep(1);
				$retries--;
				return $this->get_order_shipment_details($shipmentIds, $retries);
			} else {
				return $order_detail;
			}
		}

		// if (strpos($order_detail, '"error"') !== FALSE || empty($order_detail)){
		// 	return 'Unable to fetch shipment details';
		// } else {
		$shipmentIds = explode(',', $shipmentIds);
		if (count($shipmentIds) === 1) {
			$order_detail->shipments[0]->courierDetails = $order_detail->shipments[0]->subShipments[0]->courierDetails;
			$shipment_details = $this->get_order_shipment_details_v2($order_detail->shipments[0]->orderItems[0]->id);
			$order_detail->shipments[0]->deliveryAddress->contactNumber = $shipment_details->shipments[0]->deliveryAddress->contactNumber;
			$order_detail->shipments[0]->billingAddress->contactNumber = $shipment_details->shipments[0]->billingAddress->contactNumber;
		} else {
			for ($i = 0; $i < count($shipmentIds); $i++) {
				$shipment_details = $this->get_order_shipment_details_v2($order_detail->shipments[$i]->orderItems[0]->id);
				$order_detail->shipments[$i]->deliveryAddress->contactNumber = $shipment_details->shipments[0]->deliveryAddress->contactNumber;
				$order_detail->shipments[$i]->billingAddress->contactNumber = $shipment_details->shipments[0]->billingAddress->contactNumber;
				$order_detail->shipments[$i]->courierDetails = $order_detail->shipments[$i]->subShipments[0]->courierDetails;
			}
		}
		return $order_detail;
		// }
	}

	function get_order_invoice_details($shipmentId, $retries = 2)
	{
		global $api_url;

		$url = $api_url . "/v3/shipments/" . rawurlencode($shipmentId) . "/invoices";
		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json",
			"accept: application/json"
		);

		$order_detail = $this->fk_run_curl($url, $request, $header);
		$order_detail = json_decode($order_detail);
		if (isset($order_detail->errors)) {
			if ($retries > 0) {
				sleep(1);
				$retries--;
				return $this->get_order_invoice_details($shipmentId, $retries);
			} else {
				return $order_detail;
			}
		}

		return $order_detail;
	}

	function get_handover_shipments_count()
	{ // for NON_FBF orders only
		global $api_url;

		$url = $api_url . "/v3/shipments/handover/counts?locationId=" . $this->account->locationId;
		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json",
			"accept: application/json"
		);

		$order_detail = $this->fk_run_curl($url, $request, $header);
		$order_detail = json_decode($order_detail);

		return $order_detail;
	}

	function cancel_order($orderItemId)
	{
		global $api_url;

		$url = $api_url . "/v2/orders/cancel";
		$request = "POST";

		$post = array(
			'orderItems' => array(
				'orderItemId' 	=> $orderItemId,
				'reason' => 'cannot_procure_item'
			),
		);

		$post = json_encode($post);

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json",
			"cache-control: no-cache",
			"Content-Length: " . strlen($post)
		);

		// $debug = new debug();
		// $debug->do_pre($header);

		$return = $this->fk_run_curl($url, $request, $header, $post);

		return $return;
	}

	function get_next_page($nextPageUrl)
	{
		global $api_url, $orders;

		$request = "GET";
		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$return = $this->fk_run_curl($api_url . $nextPageUrl, $request, $header);

		return $return;
	}

	function pack_orders($shipmentIds)
	{
		global $api_url, $log, $db;

		$log->write('Label Generation batch of ' . count($shipmentIds) . ' for ' . $this->account->account_name, 'label-creation');

		foreach ($shipmentIds as $shipmentId) {
			$db->where('shipmentId', $shipmentId);
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS, NULL, 'orderId, orderItemId, shipmentId, quantity, subItems');
			$taxItems = array();
			$subShipments = array();
			foreach ($orders as $order) {
				$subItems = json_decode($order->subItems);
				$subItems = $subItems[0];

				$taxItem = new stdClass();
				$taxItem->taxRate = "0";
				$taxItem->quantity = $order->quantity;
				$taxItem->orderItemId = $order->orderItemId;
				$taxItems[] = $taxItem;

				$subShipment = new stdClass();
				$subShipment->subShipmentId = $subItems->subShipmentId;
				$subShipment->dimensions->breadth = strval($subItems->packages[0]->dimensions->breadth);
				$subShipment->dimensions->length = strval($subItems->packages[0]->dimensions->length);
				$subShipment->dimensions->height = strval($subItems->packages[0]->dimensions->height);
				// $subShipment->dimensions->weight = strval($subItems->packages[0]->dimensions->weight);
				$subShipment->dimensions->weight = strval(0.150);
				$subShipments[] = $subShipment;
			}

			$invoices = new stdClass();
			$invoices->orderId = $order->orderId;
			$invoices->invoiceNumber = "0";
			$invoices->invoiceDate = date('Y-m-d', time());

			$shipments[] = array(
				'taxItems'		=> $taxItems,
				'invoices'		=> array($invoices),
				'shipmentId' 	=> $shipmentId,
				'locationId'	=> $this->account->locationId,
				'subShipments'	=> $subShipments,
			);
		}

		$url = $api_url . "/v3/shipments/labels";
		$request = "POST";

		$post = new stdClass();
		$post->shipments = $shipments;
		$post = json_encode($post);

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json",
			"cache-control: no-cache",
			"Content-Length: " . strlen($post)
		);

		$return = $this->fk_run_curl($url, $request, $header, $post);
		return $return;
	}

	function generate_labels($orderItemIds, $label = false, $invoice = false, $redownload = false, $output = true)
	{
		global $api_url, $log, $db;

		$request = "GET";
		if (empty($orderItemIds))
			return;

		if (is_object($orderItemIds))
			$orderItemIds = (array) $orderItemIds;

		$return = '';

		$path = ROOT_PATH . '/labels/';
		$filename = 'Flipkart-Labels-' . date('d') . '-' . date('M') . '-' . date('Y') . '-' . $this->account->account_name;

		ob_start();
		$unpacked_orders = array();
		$cancelled_orders = array();
		$forwardTrackingIds = array();

		$log->write('Label Generation batch of ' . count($orderItemIds), 'labels');
		$order_ids = array();
		$mpdf = new \Mpdf\Mpdf();

		$log->write($orderItemIds, 'labels');

		foreach ($orderItemIds as $orderItemsId) {

			if (empty($orderItemsId) || is_null($orderItemsId))
				continue;

			$log->write('Inititated ' . $orderItemsId['orderItemId'], 'labels');
			$file_n = "";

			if ($redownload)
				unlink($path . 'single/FullLabel-' . $orderItemsId['shipmentId'] . '.pdf');

			$order_details = "";
			if (check_valid_pdf($path . 'single/FullLabel-' . $orderItemsId['shipmentId'] . '.pdf')) {
				if (in_array($orderItemsId['orderId'], $order_ids)) {
					continue;
				} else {
					$order_ids[] = $orderItemsId['orderId'];
				}
				$file_n = $path . 'single/FullLabel-' . $orderItemsId['shipmentId'] . '.pdf';
				// echo $file_n .' - exists <br />';
				// continue;
				// $data = file_get_contents($file_n);
				// if (!preg_match("/^%startxref/", $data)){
				// 	unlink($file_n);
				// 	continue;
				// }
			} else {
				// $order_details = $this->get_order_details($orderItemsId['shipmentId']);
				// if ($order_details && !isset($order_details->error)){
				// 	if (in_array($orderItemsId['orderId'], $order_ids)) {
				// 		continue;
				// 	} else {
				// 		$order_ids[] = $orderItemsId['orderId'];
				// 	}

				// 	// if ($order_details && isset($order_details->status) && ($order_details->status == "APPROVED")){
				// 	// 	$unpacked_orders[] = $order_details->orderId;
				// 	// 	$log->write('Order with ID '.$order_id.' & Item ID '.$order_details->orderItemId.' still not packed on flipkart');
				// 	// 	continue;
				// 	// }

				// 	$shipment_details = $this->get_order_shipment_details($orderItemsId['shipmentId']);
				// 	if ($shipment_details){
				// 		$forwardTrackingId = $shipment_details->shipments[0]->courierDetails->deliveryDetails->trackingId;
				// 		if (in_array($forwardTrackingId, $forwardTrackingIds)){
				// 			continue;
				// 		}
				// 	}

				// 	if ($order_details && isset($order_details->status) && $order_details->status == "CANCELLED"){
				// 		$cancelled_orders[] = $order_details->orderId;
				// 		$this->update_order_to_cancel(null, $order_details->shipmentId, $order_details->quantity);
				// 		$log->write('CANCELLED: Order with ID '.$order_details->orderId.' & Item ID '.$order_details->orderItemId, 'labels');
				// 		continue;
				// 	}

				$file_n = $this->get_label($orderItemsId['shipmentId'], $path, $redownload);
				// echo $file_n .' - NEW <br />';
				// continue;
				// }
			}

			if (strpos($file_n, 'FullLabel-') !== FALSE) {
				$log->write('Label Generated for OrderItemID ' . $orderItemsId['orderItemId'] . ' File: ' . $file_n, 'labels');
			} else {
				$error = 'Unable to generate label for OrderItemID ' . $orderItemsId['orderItemId'] . ' Error: ' . json_encode($order_details);
				$log->write($error, 'labels');
				continue;
			}

			$file = $path . 'single/FullLabel-' . $orderItemsId['shipmentId'] . '.pdf';
			if ($label) {
				$shipping_label = $this->create_shipping_label($file, $orderItemsId);
				$mpdf->AddPage('P', '', '', '', '', 0, 0, 0, 0, 0, 0, '', '', '', '', '', '', '', '', '', array(92, 129));
				$mpdf->setSourceFile($shipping_label);
				$tplId = $mpdf->ImportPage(1);
				$mpdf->UseTemplate($tplId);
			}
			if ($invoice) {
				$invoice = $this->create_invoices($file, $orderItemsId['shipmentId']);
				$mpdf->AddPage('L', '', '', '', '', 0, 0, 0, 0, 0, 0, '', '', '', '', '', '', '', '', '', 'A5');
				$mpdf->setSourceFile($invoice);
				$tplId = $mpdf->ImportPage(1);
				$mpdf->UseTemplate($tplId);
			}
		}
		$name = '';
		if ($label) {
			$name .= "Labels-";
		}
		if ($invoice) {
			$name .= "Invoice-";
		}
		$filename = 'Flipkart-' . $name . date('d') . '-' . date('M') . '-' . date('Y') . '-' . $this->account->account_name . '.pdf';

		if (!$output)
			return $filename;

		ob_end_clean();

		if ($output /*&& check_valid_pdf($filename)*/) {
			$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
			// $mpdf->Output($path.'final/'.$filename, \Mpdf\Output\Destination::FILE);
		} else
			echo 'No label generated. <br />Cancelled Orders: ' . implode(',', $cancelled_orders);
	}

	function get_label($shipment_id, $path, $redownload = false, $i = 0)
	{
		global $api_url, $db, $log;

		$file_n = '';
		if (empty($shipment_id)) {
			return;
		}

		$db->where('shipmentId', $shipment_id);
		$order = $db->ObjectBuilder()->getOne(TBL_FK_ORDERS, 'account_id, orderItemId');
		if ($this->account->account_id != $order->account_id)
			return;

		if (check_valid_pdf($path . 'single/FullLabel-' . $shipment_id . '.pdf')) {
			$file_n = $path . 'single/FullLabel-' . $shipment_id . '.pdf';
			// $data = file_get_contents($file_n);
			// if (!preg_match("/^%startxref/", $data)){
			// 	@unlink($file_n);
			// 	$this->get_label($shipment_id, $path, $i+1);
			// }
		} else {
			// if ($this->account->account_id == 1){
			// 	$return = $this->get_label_dashboard($shipment_id);
			// } else {
			$url = $api_url . "/v3/shipments/" . urlencode($shipment_id) . "/labelOnly/pdf";
			$request = "GET";

			$header = array(
				"authorization: Bearer " . $this->check_access_token(),
				"cache-control: no-cache",
				"content-type: application/json",
				"accept: application/pdf"
			);

			$return = $this->fk_run_curl($url, $request, $header, true);
			// }
			$return_j = json_decode($return);

			if (isset($return_j->errorCode)) {
				$return = 'cURL Error : ' . $return_j->errorCode;
			}

			if (isset($return_j->orderItems[0]->errorCode) && strpos($return_j->orderItems[0]->errorCode, 'ORDER_ITEM_PACKING_IN_PROGRESS' !== FALSE)) {
				$return = 'cURL Error : Order ID with ' . $shipment_id . ' not LABEL GENERATION IN PROGRESS';
			}

			if (isset($return_j->orderItems[0]->errorCode) && strpos($return_j->orderItems[0]->errorCode, 'ORDER_ITEM_NOT_PACKED' !== FALSE)) {
				$return = 'cURL Error : Order ID with ' . $shipment_id . ' not marked PACKED';
			}

			if (isset($return_j->orderItems[0]->errorCode) && strpos($return_j->orderItems[0]->errorCode, 'INVALID_ITEM_IDS' !== FALSE)) {
				$return = 'cURL Error : Order Item ID with ' . $shipment_id . ' is not valid';
			}

			if ($i > 3) {
				ob_start();
				$pdf = new FPDI('P', 'mm', 'A4');
				$pdf->AddPage();
				$pdf->SetFont('Helvetica');
				$pdf->SetFontSize(10);
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetXY(5, 5);
				$pdf->MultiCell(95, 5, 'Unable to generate the labels for order ID ' . $shipment_id . json_encode($return), 0, 'R');
				$pdf->Output($path . 'FullLabel-' . $shipment_id . '.pdf', 'F');
				$file_n = $path . 'single/FullLabel-' . $shipment_id . '.pdf';
				ob_end_clean();
			} elseif ((strpos($return, 'cURL Error') !== FALSE || strpos($return, 'startxref') === FALSE) && $i < 3) {
				$this->get_label($shipment_id, $path, $redownload, $i + 1);
			} else {
				$file = fopen($path . 'single/FullLabel-' . $shipment_id . '.pdf', 'w') or die("X_x");
				fwrite($file, $return);
				fclose($file);
				$log->write('OI: ' . $order->orderItemId . "\tLabel Generated", 'order-status');
				$db->where('shipmentId', $shipment_id);
				$db->update(TBL_FK_ORDERS, array('labelGeneratedDate' => date('c')));
			}

			// Check if the file is generated
			if (file_exists($path . 'single/FullLabel-' . $shipment_id . '.pdf')) {
				$file_n = $path . 'single/FullLabel-' . $shipment_id . '.pdf';
			} else {
				$file_n = "Error Creating Label for " . $shipment_id;
			}
		}

		return $file_n;
	}

	function create_shipping_label($file, $order_item_id, $output = false)
	{
		global $wrong_sku, $correct_sku, $db;

		$mpdf = new \Mpdf\Mpdf([
			"default_font" => "DejaVuSans",
			'mode' => 's',
		]);
		$mpdf->setSourceFile($file);
		$mpdf->AddPage('P', '', '', '', '', 0, 0, 0, 0, 0, 0, '', '', '', '', '', '', '', '', '', array(92, 129));

		// import page 1
		$tplIdx = $mpdf->ImportPage(1);
		$mpdf->useTemplate($tplIdx, -58, -7);
		// $mpdf->useTemplate($tplIdx);
		$mpdf->SetLineWidth(3);
		$mpdf->SetDrawColor(255, 255, 255);
		$mpdf->Line(3.25, 0, 3.25, 148);
		$mpdf->Line(90, 0, 90, 148);
		$mpdf->Line(0, 129, 105, 129);
		$mpdf->SetTextColor(0, 0, 0);

		$s_sku = trim($order_item_id['sku']);
		$p_name = trim($order_item_id['title']);
		$dispatch_date = date('d-M-Y', strtotime($order_item_id['dispatchByDate']));
		$replacementOrder = $order_item_id['replacementOrder'];
		$order_type = $order_item_id['order_type'] == "FBF_LITE" ? "FBF_LITE" : "";

		// REPLACE THE INCORRECT SKU CODE WITH CORRECT
		$i = 0;
		$filename = ROOT_PATH . '/labels/single/Shipping-' . $order_item_id["shipmentId"] . '.pdf';
		$corrected_sku = $this->get_corrected_sku($order_item_id['fsn']);
		if ($corrected_sku) {
			$mpdf->SetLineWidth(3);
			// Remove divider line on each side
			$mpdf->Line(13.4, 96, 77, 96);
			$mpdf->Line(13.4, 98, 77, 98);

			// 
			// $mpdf->SetFont('DejaVuSans','B');
			// $mpdf->SetFontSize(5.5);
			// $mpdf->SetXY(5.5, 82.5);
			// $mpdf->MultiCell(10,2.25, 'watch',0,'L', 0);

			$mpdf->SetFont('DejaVuSans', '');
			$mpdf->SetFontSize(6);
			$mpdf->SetXY(10.8, 94.6);
			$mpdf->MultiCell(67, 2.25, $corrected_sku . ' | ' . substr($p_name, 0, strpos($p_name, " - For")), 0, 'L', 0);
			$i++;
		}

		// ADD FBF_LITE TAG
		if ($order_type == "FBF_LITE") {
			$mpdf->SetTextColor(0, 0, 0);
			$mpdf->SetFont('DejaVuSans', 'B');
			$mpdf->SetFontSize(6);
			$mpdf->SetXY(65, 4);
			$mpdf->WriteCell(15, 3.8, 'FBF-LITE', 0,  0, 'C', 0);
		}

		// ADD PRIORITY TAG
		if ($dispatch_date == date('d-M-Y', time())) {
			$mpdf->SetFillColor(0, 0, 0);
			$mpdf->SetTextColor(255, 255, 255);
			$mpdf->SetLineWidth(1);
			$mpdf->SetFont('DejaVuSans', '');
			$mpdf->SetFontSize(6);
			$mpdf->SetXY(65, 7.5);
			$mpdf->WriteCell(15, 3.8, 'PRIORITY', 0,  0, 'C', 1);
		}

		// ADD REPLACEMENT ORDER TAG
		if ($replacementOrder) {
			$mpdf->SetTextColor(0, 0, 0);
			$mpdf->SetFont('DejaVuSans', 'B');
			$mpdf->SetFontSize(6);
			$mpdf->SetXY(71, 88.3);
			$mpdf->WriteCell(15, 3, 'REPODR', 0,  0, 'C', 0);
		}

		ob_clean();
		if ($output) {
			$mpdf->Output($filename, 'I');
		} else {
			$mpdf->Output($filename, 'F');
			return $filename;
		}
	}

	function create_invoices($file, $order_item_id, $output = false)
	{

		// initiate FPDI
		$mpdf = new \Mpdf\Mpdf();

		// set the source file
		$mpdf->setSourceFile($file);
		$mpdf->AddPage('L', '', '', '', '', 0, 0, 0, 0, 0, 0, '', '', '', '', '', '', '', '', '', 'A5');

		// import page 1
		$tplId = $mpdf->importPage(1);

		// use the imported page and place it at point 10,10 with a width of 100 mm
		$mpdf->UseTemplate($tplId, 0, -121);

		$mpdf->SetFillColor(255, 255, 255);
		$mpdf->Rect(5, 0, 200, 11, 'F');

		$filename = ROOT_PATH . '/labels/single/Invoice-' . $order_item_id . '.pdf';
		if ($output) {
			$mpdf->Output($filename, 'I');
		} else {
			$mpdf->Output($filename, 'F');
			return $filename;
		}
	}

	function get_corrected_sku($fsn)
	{
		global $db;

		$db->where('mp_id', $fsn);
		$db->where('account_id', $this->account->account_id);
		$correct_sku = $db->getValue(TBL_PRODUCTS_ALIAS, 'corrected_sku');

		return $correct_sku;
	}

	//
	function mark_rtd_order_on_fk($shipmentId)
	{
		global $api_url;

		if ($shipmentId) {
			$url = $api_url . "/v3/shipments/dispatch";
			$request = "POST";

			$post = new stdClass();
			$post->shipmentIds = array($shipmentId);
			$post->locationId = $this->account->locationId;

			$header = array(
				"authorization: Bearer " . $this->check_access_token(),
				"content-type: application/json"
			);

			$return = $this->fk_run_curl($url, $request, $header, json_encode($post));

			return $return;
		} else {
			return 'Invalid Tracking ID. Please try again.';
		}
	}

	function get_orders($type)
	{
		global $db, $log;

		if (empty($type)) {
			exit('Sync type not described.');
		}

		$now = time();
		$db->where('option_key', 'lastSyncedOn');
		$option = $db->getOne("bas_options");
		$lastSyncedOn = $option['option_value'];
		// var_dump($lastSyncedOn);
		// $log->logfile = TODAYS_LOG_PATH.'/orders.log';

		switch (strtolower($type)) {
			case 'new':
				// $modified_param['orderDate'] = array(
				// 	// 'fromDate' => date('c', $lastSyncedOn),
				// 	'fromDate' => date('c', strtotime("09 Nov 2018 02:00 PM")),
				// 	'toDate' => date( 'c', $now), // Specify the date
				// );

				if (date('H', time()) < '14') {
					$modified_param['dispatchAfterDate'] = array(
						'from' => date('c', strtotime("yesterday -2 days 12:00 PM")),
						// 'from' => date('c', strtotime("03 Nov 2018 02:00 PM")),
						// 'fromDate' => date('c', $lastSyncedOn),
						'to' => date('c', strtotime("today 02:00 PM")), // Specify the date
					);
				} else {
					$modified_param['orderDate'] = array(
						'from' => date('c', strtotime("yesterday -2 days 12:00 PM")),
						// 'fromDate' => date('c', strtotime("03 Nov 2018 02:00 PM")),
						// 'from' => date('c', strtotime("03 Nov 2018 02:00 PM")),
						// 'fromDate' => date('c', $lastSyncedOn),
						'to' => date('c', strtotime("tomorrow 02:00 PM")), // Specify the date
					);
				}

				$orders = $this->get_to_pack_orders_v3(null, null, $modified_param);
				// var_dump($orders);
				// $orders[] = $this->get_order_details("4854978767047500");
				// var_dump($modified_param);
				// var_dump($orders);
				// exit;

				if (!$orders) {
					$log->write('No orders to Sync!!!', 'orders-sync');
					// return 'No orders to Sync!!!'; 
					exit('No orders to Sync!!!');
				}
				$order_shipments = new stdClass();
				$order_shipments->shipments = $orders;
				$orders = $this->arrange_order_details($order_shipments);
				$log->write('Found ' . count($orders) . ' Orders!!!', 'orders-sync');
				$this->insert_orders($orders);
				break;

			case 'packing':
				$orders = $this->get_to_dispatch_orders();

				if (!$orders) {
					$log->write('No orders to Sync!!!', 'orders-sync');
					return; // exit('No orders to Sync!!!');
				}
				$log->write('Found ' . count($orders) . ' Orders to PACK!!!', 'orders-sync');

				$orders_chunk = array_chunk($orders, 20);
				foreach ($orders_chunk as $order_chunk) {
					$order_item_ids = array();
					foreach ($order_chunk as $order) {
						$order_item_ids[] = $order->orderItemId;
					}
					$order_details = $this->get_order_details(implode(',', $order_item_ids));
					$shipment_details = $this->get_order_shipment_details(implode(',', $order_item_ids));

					usort($order_details->orderItems, "cmp");
					usort($shipment_details->shipments, "cmp_s");

					$i = 0;
					foreach ($order_details->orderItems as $order) {
						$this->update_orders(array($order), 'NON_FBF', 'packing', $shipment_details->shipments[$i]);
						$i++;
					}
				}
				break;

			case 'handover':
				$this->update_handovered_orders();
				break;
		}

		$data = array(
			'option_value' => $now
		);
		$db->where('option_key', 'lastSyncedOn');
		if ($db->update('bas_options', $data))
			$log->write('LastSyncedOn updated to ' . $now, 'orders');
		else
			$log->write('LastSyncedOn update failed: ' . $db->getLastError(), 'orders');

		return;
	}

	function insert_orders($orders, $type = "NON_FBF")
	{
		global $db, $log, $stockist;

		$return = false;
		foreach ($orders as $order) {
			// echo $order->orderItemId .'<br />';
			if ($type == "FBF") {
				$order_type = $type;
				$order_status = "SHIPPED";
			} else {
				$order_type = ($order->serviceProfile == "Smart_Fulfilment") ? 'FBF_LITE' : 'NON_FBF';
				$order_status = "NEW";
			}

			$parent_skus = $this->get_cost_price_by_fsn($order->fsn);
			if ($parent_skus === 'Parent SKU not found') {
				$return = 'Parent SKU not found for order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId . ' :: Data: ' . json_encode($order) . ' :: BACK TRACE: ' . json_encode(debug_backtrace());
				// echo $return.'<br />';
				$log->write($return);
				continue;
			}

			$details = array(
				'orderItemId' => $order->orderItemId,
				'orderId' => $order->orderId,
				'shipmentId' => $order->shipmentId,
				'status' => $order_status,
				'account_id' => $this->account->account_id,
				'fk_status' => $order->status,
				'hold' => $order->hold,
				'locationId' => $order->locationId,
				'order_type' => $order_type,
				'orderDate' => date('Y-m-d H:i:s', strtotime($order->orderDate)),
				'dispatchAfterDate' => date('Y-m-d H:i:s', strtotime($order->dispatchAfterDate)),
				'dispatchByDate' => date('Y-m-d H:i:s', strtotime($order->dispatchByDate)),
				'updatedAt' => date('Y-m-d H:i:s', strtotime($order->updatedAt)),
				'sla' => $order->sla,
				'ordered_quantity' => $order->quantity,
				'quantity' => $order->quantity,
				'title' => $order->title,
				'listingId' => $order->listingId,
				'fsn' => $order->fsn,
				'sku' => $order->sku,
				'hsn' => $order->hsn,
				'dispatchServiceTier' => isset($order->dispatchServiceTier) ? $order->dispatchServiceTier : "",
				'sellingPrice' => $order->priceComponents->sellingPrice,
				'customerPrice' => $order->priceComponents->customerPrice,
				'shippingCharge' => $order->priceComponents->shippingCharge,
				'totalPrice' => $order->priceComponents->totalPrice,
				'flipkartDiscount' => $order->priceComponents->flipkartDiscount,
				'paymentType' => strtolower($order->paymentType),
				'stateDocuments' => json_encode($order->stateDocuments),
				'subItems' => json_encode($order->subShipments),
				'costPrice' => $parent_skus,
				'insertDate' => $db->now(),
			);

			if ($type == "FBF") {
				$details['deliveryAddress'] = json_encode($order->deliveryAddress);
				$details['billingAddress'] = json_encode($order->billingAddress);
				$details['labelGeneratedDate'] = $order->invoice_details->invoiceDate;
				$details['rtdDate'] = $order->invoice_details->invoiceDate;
				$details['shippedDate'] = $order->invoice_details->invoiceDate;
				$details['invoiceDate'] = $order->invoice_details->invoiceDate;
				$details['invoiceNumber'] = $order->invoice_details->invoiceNumber;
				$details['invoiceAmount'] = $order->invoice_details->invoiceAmount;
				$details['sgstRate'] = $order->invoice_details->sgstRate;
				$details['cgstRate'] = $order->invoice_details->cgstRate;
				$details['igstRate'] = $order->invoice_details->igstRate;
			}

			// Order Item Details
			// $order_details = $this->get_order_shipment_details($order->orderItemId);
			// $details['deliveryAddress'] = json_encode($order_details->shipments[0]->deliveryAddress);
			// $details['billingAddress'] = json_encode($order_details->shipments[0]->billingAddress);

			// $db->where('orderId', $order->orderId);
			// $db->where('dispatchByDate', $order->dispatchByDate, "!=");
			// if ($db->has(TBL_FK_ORDERS)){
			// 	$log->write('REPLACEMENT ORDER QUERY: ');
			// 	$details['replacementOrder'] = true;
			// }

			$details['replacementOrder'] = $order->is_replacement;
			if ($order->is_replacement) {
				$details['orderDate'] = date('Y-m-d H:i:s');
				if ($type == "FBF") {
					$db->where('orderItemId', $order->replacementOrderItemId);
					$db->update(TBL_FK_RETURNS, array('r_replacementOrderItemId' => $order->orderItemId));
				}
			}
			$db->where('orderItemId', $order->orderItemId);
			if (!$db->has(TBL_FK_ORDERS)) {
				if ($db->insert(TBL_FK_ORDERS, $details)) {
					$return = 'INSERT: New Order with order ID ' . $details['orderId'] . ' & Item ID ' . $details['orderItemId'] . ' added.';
					$log->write($return, 'new-orders');
					$log->write('OI: ' . $order->orderItemId . "\tNew", 'order-status');
					// LOW PRICING ALERT
					$fulfilled = (($order->serviceProfile == "Smart_Fulfilment" || $order->serviceProfile == "FBF") ? true : false);
					// $this->check_low_pricing_alert($order->fsn, $order->priceComponents->totalPrice, $fulfilled, $order->orderItemId, $order->listingId );
				} else {
					$return = 'INSERT: Unable to insert order with order ID ' . $details['orderId'] . ' & Item ID ' . $details['orderItemId'] . ' :: ' . $db->getLastError() . ' :: ' . $db->getLastQuery();
					$log->write($return, 'new-orders');
				}
			} else {
				$data = array(
					'fk_status' => $order->status,
					'dispatchAfterDate' => date('Y-m-d H:i:s', strtotime($order->dispatchAfterDate)),
					'dispatchByDate' => date('Y-m-d H:i:s', strtotime($order->dispatchByDate)),
					'updatedAt' => date('Y-m-d H:i:s', strtotime($order->updatedAt)),
				);
				$db->where('orderItemId', $order->orderItemId);
				$db->update(TBL_FK_ORDERS, $data);
				$return = 'UPDATE: FK Status/DAD/DBD Details changed for order ID ' . $details['orderId'] . ' & Item ID ' . $details['orderItemId'] . ' :: Data: ' . json_encode($data) . ' :: BACK TRACE: ' . json_encode(debug_backtrace());
				$log->write($return);
			}
		}

		return $return;
	}

	function update_orders($orders, $type = "NON_FBF", $status = 'packing', $order_details = NULL, $import = false)
	{
		global $db, $log;

		// $log->logfile = TODAYS_LOG_PATH.'/orders.log';
		$return = "";

		$was_orders_details_null = false;
		if ($order_details == NULL)
			$was_orders_details_null = true;

		if (isset($_GET['test']) && $_GET['test'] == "1") {
			echo '<pre>';
			print_r($orders);
		}

		foreach ($orders as $order) {
			$details = array();
			// echo $order->orderItemId .'<br />';
			$shipmentId = $order->shipmentId;
			$orderItemId = $order->orderItemId;
			$orderId = $order->orderId;

			// TEMP FIX TILL THE API DOESN'T SEND SERVICE_PROFILE
			if ($import) {
				$db->where('orderItemId', $orderItemId);
				$db->update(TBL_FK_ORDERS, array('order_type' => 'FBF_LITE'));
			}

			$our_status = $this->get_our_order_status($shipmentId);
			if ($import && ($our_status == "SHIPPED" || $our_status == "RTD" || $our_status == "PACKING")) {
				$return = 'IMPORT: Already SHIPPED or RTD order with Order ID ' . $orderId . ' & Item ID ' . $orderItemId . ' .';
				$log->write($return, 'orders');
				return $return;
			}

			// Order Item Details
			if ($order_details == NULL || $was_orders_details_null) {
				$order_details = $this->get_order_shipment_details($shipmentId);
				if ($order_details != 'Unable to fetch shipment details')
					$order_details = $order_details->shipments[0];
				else {
					$log->write('UNABLE TO FETCH SHIPMENT DETAILS for shipment ID ' . $order->shipmentId . ' against Item ID ' . $order->orderItemId, 'orders');
					return;
				}

				if (isset($_GET['test']) && $_GET['test'] == "1")
					var_dump($order_details);
			}

			if (isset($order_details->orderItems[1]->id) && $order_details->orderItems[1]->id != $order->orderItemId) {
				$log->write('ORDER ITEM ID MISMATCH: Unable to update Order. Mismatch at ' . $order_details->orderItems[0]->id . ' against Item ID ' . $order->orderItemId, 'orders');
				return;
			}

			$details = array();
			$details['status'] = strtoupper($status);
			if ($status == "packing") {
				// if ($type == 'FBF_LITE' && $order->status == "READY_TO_DISPATCH")
				// $details['status'] = 'PACKING';
				// $details['fk_status'] = $order->status;
				$details['deliveryAddress'] = json_encode($order_details->deliveryAddress);
				$details['billingAddress'] = json_encode($order_details->billingAddress);
				$details['pickupDetails'] = json_encode($order_details->courierDetails->pickupDetails);
				$details['deliveryDetails'] = json_encode($order_details->courierDetails->deliveryDetails);
				$details['forwardTrackingId'] = $order_details->courierDetails->deliveryDetails->trackingId;
				$details['dispatchByDate'] = $order->dispatchByDate;
			}

			$db->where('shipmentId', $shipmentId);
			if ($db->has(TBL_FK_ORDERS)) {
				$db->where('shipmentId', $shipmentId);
				if ($db->update(TBL_FK_ORDERS, $details)) {
					$return = 'UPDATE: Updated Order with order ID ' . $orderId . ' & Item ID ' . $orderItemId . json_encode($details);
					$log->write($return, 'orders');
				} else {
					$return = 'UPDATE: Unable to update order with order ID ' . $orderId . ' & Item ID ' . $orderItemId . ' ' . $db->getLastError();
					$log->write($return, 'orders');
				}
			} else {
				$return = $this->insert_orders(array($order), $type);
				$this->update_orders(array($order), $type, $status, $order_details);
			}
		}

		return $return;
	}

	function update_order_to_cancel($trackingId, $shipmentId = NULL, $qty = 1, $orderItemId = NULL)
	{
		global $db, $log, $stockist;

		if (empty($trackingId) && empty($shipmentId) && empty($orderItemId))
			return;

		if (!is_null($orderItemId))
			$db->where('orderItemId', $orderItemId);
		else if (!is_null($shipmentId))
			$db->where('shipmentId', $shipmentId);
		else
			$db->where('forwardTrackingId', $trackingId);

		$db->where('account_id', $this->account->account_id);
		$db->join(TBL_FK_RETURNS . ' r', 'o.orderItemId=r.orderItemId', 'LEFT');
		$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', null, 'o.*, r.*, o.orderItemId as orderItemId');
		if ($orders) {
			// TO DO: CANCELLATION ON TH BASIS OF ORDERITEM ID ONLY
			foreach ($orders as $order) {
				$fk_order = $this->get_order_details($order->shipmentId);
				if (is_array($fk_order) && !is_object($fk_order)) {
					foreach ($fk_order as $fk_odr) { // MULTI QTY ORDER PATCH FOR GETTING CORRECT ORDER
						if ($fk_odr->orderItemId != $order->orderItemId)
							continue;
						$fk_order = $fk_odr;
					}
				}

				// ORDER ITEM ID TRACKER FOR STOCKIST - TEMP
				$json_order = array(
					"orderItemId" => $order->orderItemId
				);

				$hasReturns = !is_null($order->returnId);

				// PARTIAL CANCELLATION
				if ($qty != "all" && ($order->quantity != (int)0 && $order->quantity != $qty) && ($fk_order->status != "RETURN_REQUESTED" || $fk_order->status != "RETURNED" || $fk_order->status != "CANCELLED")) {
					if (($order->quantity - $qty) == (int)0)
						$order->status = "CANCELLED";

					$details = array(
						'status'	=> $order->status,
						'fk_status' => $fk_order->status,
						'quantity'	=> $order->quantity - $qty,
						'sellingPrice' => $fk_order->priceComponents->sellingPrice,
						'totalPrice' => $fk_order->priceComponents->totalPrice,
						'shippingCharge' => $fk_order->priceComponents->shippingCharge,
						'customerPrice' => $fk_order->priceComponents->customerPrice,
						'flipkartDiscount' => $fk_order->priceComponents->flipkartDiscount,
					);

					if (!is_null($orderItemId))
						$db->where('orderItemId', $orderItemId);
					else if (!is_null($shipmentId))
						$db->where('shipmentId', $shipmentId);
					else
						$db->where('forwardTrackingId', $trackingId);

					if ($order->status == "SHIPPED" && !$hasReturns) {
						$log->write('NO RETURN: No return found for this SHIPPED order with ' . $order->orderId . ' & Item ID ' . $order->orderItemId . ' with quantity ' . $qty, 'cancel-orders');
						$return['title'] = $order->title;
						$return['order_item_id'] = $order->orderItemId;
						$return['trackingId'] = $trackingId;
						$return['message'] = 'No return for this shipped order';
						$return['type'] = "error";
					} else {
						if ($db->update(TBL_FK_ORDERS, $details)) {
							$log->write('PARTIAL_CANCELLED: Order qty reduced by ' . $qty . ' with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'cancel-orders');
							if ($order->status == "SHIPPED" && $hasReturns)
								return $return;

							if ($this->payments)
								$this->payments->update_fk_payout($order->orderItemId);
						}
					}
				} else if (($order->status != "CANCELLED") && ($fk_order->status == "RETURN_REQUESTED" || $fk_order->status == "RETURNED" || $fk_order->status == "CANCELLED")) {
					$details = array(
						'status' => 'CANCELLED',
						'fk_status' => $fk_order->status,
						'quantity'	=> $order->quantity - $qty,
					);

					if ($qty == "all")
						$qty = $order->ordered_quantity;

					// MOSTLY THIS IS NOT REQUIRED AS IT HAS ALREADY BEEN DONE IN PARTIAL CANCELLATION. 
					// TO DO: DEBUG TEST ON PARTIAL ORDER CANCELLATION
					if ($order->quantity != $qty && $order->quantity != (int)0)
						$details['status'] = $order->status;

					if (is_null($trackingId))
						$db->where('orderItemId', $order->orderItemId);
					else
						$db->where('forwardTrackingId', $trackingId);

					if ($order->status == "SHIPPED" && !$hasReturns) {
						$log->write('NO RETURN: No return found for this SHIPPED order with ' . $order->orderId . ' & Item ID ' . $order->orderItemId . ' with quantity ' . $qty, 'cancel-orders');
						$return['title'] = $order->title;
						$return['order_item_id'] = $order->orderItemId;
						$return['trackingId'] = $trackingId;
						$return['message'] = 'No return for this shipped order';
						$return['type'] = "error";
					} else {
						if ($db->update(TBL_FK_ORDERS, $details)) {
							$log->write('CANCELLED: Order status updated to CANCELLED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId . ' with quantity ' . $qty, 'cancel-orders');
							$return['title'] = $order->title;
							$return['order_item_id'] = $order->orderItemId;
							$return['trackingId'] = $trackingId;
							$return['message'] = 'Cancelled';
							$return['type'] = "success";

							if ($order->status == "SHIPPED" && $hasReturns)
								return $return;
							else if ($order->status == "SHIPPED" && !$hasReturns) { // ORDER IS SHIPPED AND THERE IS NO RETURNs
								// CHECK FOR UID
								if (!is_null($order->uid)) {
									foreach (json_decode($order->uid) as $uid) {
										$stockist->update_inventory_status($uid, 'qc_verified');
										$stockist->add_inventory_log($uid, 'sales_return', 'Flipkart Order Cancelled :: ' . $order->orderItemId);
									}
								}
							}

							if ($this->payments)
								$this->payments->update_fk_payout($order->orderItemId);
						}
					}
				} else if ($order->status == "CANCELLED") {
					$log->write('CANCELLED: Order status already marked to CANCELLED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'cancel-orders');
					$return['title'] = $order->title;
					$return['order_item_id'] = $order->orderItemId;
					$return['trackingId'] = $trackingId;
					$return['message'] = 'Already Marked Cancelled';
					$return['type'] = "error";
				} else {
					$log->write('Unable to Updated Order status to CANCELLED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId . '. Order Status is ' . $order->status . ' on Flipkart.', 'cancel-orders');
					$return['title'] = $order->title;
					$return['order_item_id'] = $order->orderItemId;
					$return['trackingId'] = $trackingId;
					$return['message'] = 'Order status on Flipkart is ' . $order->status;
					$return['type'] = "error";
				}
			}
			return $return;
		}
	}

	function update_orders_to_shipped($trackingId)
	{
		global $db, $log, $stockist;

		if (empty($trackingId)) {
			return;
		}

		// error_reporting(E_ALL);
		// ini_set('display_errors', '1');
		// echo '<pre>';

		// $log->logfile = TODAYS_LOG_PATH.'/shipped-orders.log';

		$db->where('pickupDetails', '%' . $trackingId . '%', 'LIKE');
		$db->orWhere('deliveryDetails', '%' . $trackingId . '%', 'LIKE');
		$db->orWhere('forwardTrackingId', $trackingId);
		$db->where('account_id', $this->account->account_id);
		if ($orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS)) {
			if (count($orders) != 0) {
				foreach ($orders as $order) {
					if ($order->account_id != $this->account->account_id) {
						$log->write('UPDATE_FK: Unable to update Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
						$return['title'] = $order->title;
						$return['order_item_id'] = $order->orderItemId;
						$return['trackingId'] = $trackingId;
						$return['message'] = 'Order of another account.';
						$return['type'] = "error";
						return $return;
					}

					if ($order->status == "PACKING") {
						$log->write('UPDATE: Order not yet HANOVERED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
						$return['title'] = $order->title;
						$return['order_item_id'] = $order->orderItemId;
						$return['trackingId'] = $trackingId;
						$return['message'] = 'Order not HANOVERED!!!';
						$return['type'] = "error";
						return $return;
					}

					if ($order->status == "SHIPPED") {
						$log->write('UPDATE: Order status already marked as SHIPPED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
						$return['title'] = $order->title;
						$return['order_item_id'] = $order->orderItemId;
						$return['trackingId'] = $trackingId;
						$return['message'] = 'Already marked SHIPPED!!!';
						$return['type'] = "error";
						return $return;
					}

					if ($order->status == "CANCELLED") {
						$log->write('UPDATE: Order status already marked as CANCELLED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
						$return['title'] = $order->title;
						$return['order_item_id'] = $order->orderItemId;
						$return['trackingId'] = $trackingId;
						$return['message'] = 'Already marked CANCELLED!!!';
						$return['type'] = "error";
						return $return;
					}

					$order_details = $order;
					// var_dump($order_details);

					/*$order_details_all = $this->get_order_details($order->shipmentId);
					$order_details = "";
					if ($order_details_all && is_array($order_details_all) && !isset($order_details_all->error)){ // MULTI QTY ORDER
						foreach ($order_details_all as $order_detail) {
							if ($order_detail->orderItemId == $order->orderItemId){
								$order_details = $order_detail;
								// continue;
							}
						}
					} else {
						$log->write('Unable to get order details with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
						$return['title'] = $order->title;
						$return['order_item_id'] = $order->orderItemId;
						$return['trackingId'] = $trackingId;
						$return['message'] = 'Unable to get order details from Flipkart!!!';
						$return['type'] = "error";
						return $return;
					}*/

					if ($order_details->status == "PACKED" || $order_details->status == "NEW") {
						$log->write('UPDATE: Order not yet PACKED on FK with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
						$return['title'] = $order->title;
						$return['order_item_id'] = $order->orderItemId;
						$return['trackingId'] = $trackingId;
						$return['message'] = 'Order status is ' . $order_details->status . ' on Flipkart!!!';
						$return['type'] = "error";
						return $return;
					}
					// /* TEMP */
					// if (empty($order_details)){
					// 	$order_details = new stdClass();
					// $order_details->status = $order->status;
					// }
					// /* TEMP */
					// var_dump($order_details);

					// if (!isset($order_details->status)){
					// 	$order_details->status = $order->status;
					// }

					if ((($order_details->status == "READY_TO_DISPATCH" || $order_details->status == "RTD") && $order->status == "RTD") || $order_details->status == "PICKUP_COMPLETE" || $order_details->status == "SHIPPED" || $order_details->status == "DELIVERED") {
						// if ((($order->fk_status == "READY_TO_DISPATCH" || $order->status == "RTD") || $order->fk_status == "PACKED") || $order->fk_status == "PICKUP_COMPLETE" || $order->fk_status == "SHIPPED" || $order->fk_status == "DELIVERED") {
						$details = array('status' => 'SHIPPED', 'shippedDate' => date('c'));
						$db->where('orderItemId', $order->orderItemId);
						if ($db->update(TBL_FK_ORDERS, $details)) {
							$log->write('OI: ' . $order->orderItemId . "\tShipped", 'order-status');
							$log->write('UPDATE: Updated Order status to SHIPPED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
							$return['title'] = $order->title;
							$return['order_item_id'] = $order->orderItemId;
							$return['trackingId'] = $trackingId;
							$return['message'] = 'Successful';
							$return['type'] = "success";

							$json_order = array(
								"orderItemId" => $order->orderItemId
							);

							if ($this->payments)
								$this->payments->update_fk_payout($order->orderItemId);
						} else {
							$log->write('ERROR: Unable to update Order status to SHIPPED with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'shipped-orders');
							$return['title'] = $order->title;
							$return['order_item_id'] = $order->orderItemId;
							$return['trackingId'] = $trackingId;
							$return['message'] = 'Unable to update Status';
							$return['type'] = "error";
						}
					} elseif ($order_details->status == "CANCELLED" || $order_details->status == "RETURNED" || $order_details->status == "RETURN_REQUESTED") {
						// echo 'in-shipped-cancel';
						$return = $this->update_order_to_cancel($trackingId, NULL, $order->quantity);
						$return['type'] = "error";
					}
				}
				return $return;
			}
		}
	}

	function update_orders_to_rtd($trackingId)
	{
		global $db, $log;

		if (empty($trackingId)) {
			return;
		}

		$db->where('pickupDetails', '%' . $trackingId . '%', 'LIKE');
		$db->orWhere('deliveryDetails', '%' . $trackingId . '%', 'LIKE');
		if ($db->has(TBL_FK_ORDERS)) {
			$db->where('pickupDetails', '%' . $trackingId . '%', 'LIKE');
			$db->orWhere('deliveryDetails', '%' . $trackingId . '%', 'LIKE');
			$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
			// $db->where('status', 'PACKING');
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.*, r.returnId, r.r_status');
			$orders_count = count($orders);

			$i = 0;
			$return = array();
			foreach ($orders as $order) {
				// if ($i > $orders_count){
				// 	continue;
				// }

				// SHIPPED/RTD ORDERS
				if ($order->status == 'RTD' || $order->status == 'SHIPPED') {
					$log->write('UPDATE_FK: Order already marked as ' . $order->status . ' with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['trackingId'] = $trackingId;
					$return[$i]['message'] = 'Order already marked as ' . $order->status;
					$return[$i]['type'] = "error";
					continue;
				}

				// Other account order verification
				if ($order->account_id != $this->account->account_id) {
					$log->write('UPDATE_FK: Unable to update Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['trackingId'] = $trackingId;
					$return[$i]['message'] = 'Order of another account.';
					$return[$i]['type'] = "error";
					continue;
				}

				// CANCELLED ORDER
				if ($order->fk_status == "RETURN_REQUESTED" || $order->fk_status == "RETURNED" || $order->fk_status == "CANCELLED" || (!is_null($order->returnId) && $order->r_status != "return_cancelled")) {
					$log->write('UPDATE_FK: Order marked cancelled with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
					$return[$i] = $this->update_order_to_cancel($trackingId, NULL, $order->quantity);
					$return[$i]['type'] = 'error';
					continue;
				}

				if ($order->order_type == "NON_FBF")
					$rtd_return = $this->mark_rtd_order_on_fk($order->shipmentId);
				$details = array('status' => 'RTD', 'rtdDate' => date('c'));
				// $rtd_return = "SUCCESS";

				$db->where('orderItemId', $order->orderItemId);
				if ($db->update(TBL_FK_ORDERS, $details)) {
					// FBF ORDERS OVERRIDE
					if ($order->order_type == "FBF_LITE"/* && $order->fk_status == "READY_TO_DISPATCH"*/) {
						$log->write('OI: ' . $order->orderItemId . "\tRTD", 'order-status');
						$log->write('UPDATE: Updated Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
						$return[$i]['title'] = $order->title . ' <span class="label label-primary">FBF LITE</span>';
						$return[$i]['order_item_id'] = $order->orderItemId;
						$return[$i]['trackingId'] = $trackingId;
						$return[$i]['message'] = 'Successful';
						$return[$i]['type'] = "success";
					} else {
						if (strpos($rtd_return, 'Invalid Tracking ID') !== FALSE) {
							$log->write('UPDATE_FK: Unable to update Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId . ' Response ' . $rtd_return, 'rtd-orders');
							$return[$i]['title'] = $order->title . ' <span class="label label-primary">NON FBF</span>';
							$return[$i]['order_item_id'] = $order->orderItemId;
							$return[$i]['trackingId'] = $trackingId;
							$return[$i]['message'] = 'Successful. But shipment not marked RTD on Flipkart.';
							$return[$i]['type'] = "error";
						}
						if (strpos($rtd_return, 'ORDER_ITEM_ALREADY_DISPATCHED') !== FALSE) {
							$log->write('UPDATE_FK: Unable to update Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId . ' Response ' . $rtd_return, 'rtd-orders');
							$return[$i]['title'] = $order->title . ' <span class="label label-primary">NON FBF</span>';
							$return[$i]['order_item_id'] = $order->orderItemId;
							$return[$i]['trackingId'] = $trackingId;
							$return[$i]['type'] = "error";
							$return[$i]['message'] = 'Successful. But shipment is already dispatched on Flipkart.';
						}
						if (strpos($rtd_return, 'SUCCESS') !== FALSE) {
							$log->write('OI: ' . $order->orderItemId . "\tRTD", 'order-status');
							$log->write('UPDATE: Updated Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId . ' Response ' . $rtd_return, 'rtd-orders');
							$return[$i]['title'] = $order->title . ' <span class="label label-primary">NON FBF</span>';
							$return[$i]['order_item_id'] = $order->orderItemId;
							$return[$i]['trackingId'] = $trackingId;
							$return[$i]['message'] = 'Successful';
							$return[$i]['type'] = "success";
						}
					}
				} else {
					$log->write('UPDATE_FK: Unable to update Order status to RTD with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId . ' ERROR :: ' . $db->getLastError(), 'rtd-orders');
					$return[$i]['title'] = $order->title . ' <span class="label label-primary">NON FBF</span>';
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['trackingId'] = $trackingId;
					$return[$i]['message'] = 'Unable to update status.';
					$return[$i]['type'] = "error";
					$return[$i]['error'] = $db->getLastQuery();
				}
				$i++;
			}
		} else {
			$log->write('UPDATE_FK: Order not found with Tracking ID ' . $trackingId . ' & Item ID ' . $order->orderItemId, 'rtd-orders');
			$return[0]['title'] = 'Not Found';
			$return[0]['order_item_id'] = 'Not Found';
			$return[0]['trackingId'] = $trackingId;
			$return[0]['message'] = 'Not found';
			$return[0]['type'] = "error";
			$return[0]['error'] = $db->getLastQuery();
		}
		return $return;
	}

	function update_order_to_pack($shipment_id, $shipments_details = NULL, $order = NULL, $invoice_details = NULL)
	{
		global $db, $log;

		if (empty($shipment_id)) {
			return;
		}

		if ($order == NULL) {
			$order = $this->get_order_details($shipment_id);
			$order = (object)$order[0];
		}

		$log->write(json_encode($order), 'pack-orders');

		if (isset($order->error)) {
			$log->write('ERROR : ' . $shipment_id . ' :: ' . json_encode($order->error), 'pack-orders');
			return 'error';
		}

		$over_order_status = $this->get_our_order_status($order->orderItemId);

		if ($over_order_status == "PACKING" || $over_order_status == "SHIPPED")
			return 'error';

		if ($over_order_status == "RTD") {
			$log->write('ORDER ALREADY RTD: Unable to update Order status as ' . $order->status . ' for order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'pack-orders');
			return;
		}

		if ($order->status == 'PACKED' || $order->status == 'READY_TO_DISPATCH' || $order->status == 'SHIPPED' || $order->status == 'PICKUP_COMPLETE' || $order->status == 'DISPATCHED') { // FK STATUSES
			// $status = 'RTD';
			// if ($order->status == 'PACKED' || ($order->status == 'READY_TO_DISPATCH' && $order->serviceProfile == "Smart_Fulfilment") )
			$status = 'PACKING';

			if ($shipments_details == NULL) {
				$shipments_details = $this->get_order_shipment_details($shipment_id);
				$shipments_details = $shipments_details->shipments[0];
			}

			if ($shipments_details->shipmentId != $order->shipmentId) {
				$log->write('ORDER ITEM ID MISMATCH: Unable to update Order status as ' . $order->status . ' for order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'pack-orders');
				return 'error';
			}

			if ($invoice_details == NULL) {
				$invoice_details = $this->get_order_invoice_details($shipment_id);
				$invoice_details = $invoice_details->invoices[0];
			}

			$data = array(
				'status' 			=> $status,
				'fk_status' 		=> $order->status,
				'order_type'		=> ($order->serviceProfile == "Smart_Fulfilment" ? 'FBF_LITE' : 'NON_FBF'),
				'hold'				=> 0,
				'deliveryAddress' 	=> json_encode($shipments_details->deliveryAddress),
				'billingAddress' 	=> json_encode($shipments_details->billingAddress),
				'forwardTrackingId' => $shipments_details->courierDetails->deliveryDetails->trackingId,
				'pickupDetails' 	=> json_encode($shipments_details->courierDetails->pickupDetails),
				'deliveryDetails' 	=> json_encode($shipments_details->courierDetails->deliveryDetails),
				'invoiceDate' 		=> $invoice_details->invoiceDate,
				'invoiceNumber' 	=> $invoice_details->invoiceNumber,
				'invoiceAmount' 	=> $invoice_details->orderItems[0]->invoiceAmount,
				'sgstRate' 			=> isset($invoice_details->orderItems[0]->taxDetails->sgstRate) ? $invoice_details->orderItems[0]->taxDetails->sgstRate : 0,
				'cgstRate' 			=> isset($invoice_details->orderItems[0]->taxDetails->cgstRate) ? $invoice_details->orderItems[0]->taxDetails->cgstRate : 0,
				'igstRate' 			=> isset($invoice_details->orderItems[0]->taxDetails->igstRate) ? $invoice_details->orderItems[0]->taxDetails->igstRate : 0,
			);

			$db->where('orderItemId', $order->orderItemId);
			if ($db->update(TBL_FK_ORDERS, $data)) {
				$log->write('UPDATE: Updated Order status to PACKING with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'pack-orders');
				// $string = json_encode(debug_backtrace());
				// if (strpos($string, 'flipkart-listener.php') !== FALSE)
				// if (!$internal)
				$log->write('OI: ' . $order->orderItemId . "\tPACKING" . "\t BACK TRACE: " . json_encode(debug_backtrace()), 'order-status');
			} else
				$log->write('UPDATE: Unable to update Order status to PACKING with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'pack-orders');

			if ($order->serviceProfile != "Smart_Fulfilment")
				$this->get_label($shipment_id, ROOT_PATH . '/labels/');

			return 'success';
		} elseif ($order->status == 'CANCELLED') {
			$this->update_order_to_cancel(NULL, NULL, $order->quantity, $order->orderItemId);
			return 'cancel';
		} else {
			$log->write('UPDATE: Unable to update Order status as status is ' . $order->status . ' for order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'pack-orders');
			return 'error';
		}
	}

	function update_order_status($shipmentId)
	{
		global $db, $log;

		if (empty($shipmentId)) {
			return;
		}

		// $log->logfile = TODAYS_LOG_PATH.'/fk-order-status.log';

		$order_details = $this->get_order_details($shipmentId);
		$order_details = $order_details[0];
		$log->write($order_details, 'fk-order-status');

		if ($order_details) {
			$db->where('shipmentId', $shipmentId);
			$order = $db->ObjectBuilder()->getOne(TBL_FK_ORDERS);

			$details['fk_status'] = $order_details->status;
			$details['dispatchByDate'] = $order_details->dispatchByDate;
			$details['dispatchAfterDate'] = $order_details->dispatchAfterDate;

			if ($order_details->status == 'CANCELLED' || $order_details->status == "RETURNED") {
				$this->update_order_to_cancel(NULL, $shipmentId, $order_details->quantity);
				return;
			}

			if ($order_details->status == 'SHIPPED' || $order_details->status == 'PICKUP_COMPLETE' || $order_details->status == 'DISPATCHED' || $order_details->status == 'DELIVERED') {
				$details['fk_status'] = $order_details->status;
				$details['status'] = 'SHIPPED'; // DO NOT UPDATE OUR STATUS. IT WILL CAUSE INVENTORY SYNC ISSUE. WE WILL SCAN AND MARK SHIPPED TO AVOID SYNC ISSUE

				$db->where('shipmentId', $shipmentId);
				if ($db->update(TBL_FK_ORDERS, $details))
					$log->write('STATUS UPDATE: Updated Order fk_status to SHIPPED with order ID ' . $order_details->orderId . ' & Item ID ' . $order_details->orderItemId . ' added.', 'fk-order-status');
				else
					$log->write('STATUS UPDATE: Unable to update order fk_status to SHIPPED with order ID ' . $orderId . ' & Item ID ' . $order_details->orderItemId . ' :: ' . $db->getLastError(), 'fk-order-status');

				return;
			}

			if (($order_details->dispatchAfterDate != $order->dispatchAfterDate) || ($order_details->dispatchByDate != $order->dispatchByDate)) {
				$db->where('shipmentId', $shipmentId);
				if ($db->update(TBL_FK_ORDERS, $details))
					$log->write('STATUS UPDATE: Updated Order Dispatch dates with order ID ' . $order_details->orderId . ' & Item ID ' . $order_details->orderItemId . ' added.', 'fk-order-status');
				else
					$log->write('STATUS UPDATE: Unable to update order Dispatch dates with order ID ' . $order_details->orderId . ' & Item ID ' . $order_details->orderItemId . ' :: ' . $db->getLastError(), 'fk-order-status');

				return;
			}
		}
	}

	/**
	 *  $details = array($key => $value);
	 */
	function update_fk_order_status($shipmentId, $details, $returns = false)
	{
		global $db, $log, $account;

		if ($details['fk_status'] == 'DELIVERED') {
			$db->where('shipmentId', $shipmentId);
			$db->update(TBL_FK_ORDERS, array('deliveredDate' => $db->now()));
		}

		$db->where('shipmentId', $shipmentId);
		if ($db->update(TBL_FK_ORDERS, $details))
			$log->write('UPDATE: Order status for order Shipment ID ' . $shipmentId . ' :: ' . json_encode($details), 'fk-order-status');
		else
			$log->write('UPDATE: Unable to update order status for order Shipment ID ' . $shipmentId . ' :: ' . $db->getLastError(), 'fk-order-status');
	}

	// RUN CRON BETWEEN 2-8
	function update_handovered_orders()
	{
		global $db, $log;
		// $log->logfile = TODAYS_LOG_PATH.'/shipped-orders.log';

		$db->where('status', 'RTD');
		$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS);

		foreach ($orders as $order) {
			$details = array();
			$orderItemId = $order->orderItemId;
			$orderId = $order->orderId;

			$order_details = $this->get_order_details($orderItemId);
			$order_details = $order_details[0];

			// Order Item Details
			if (strtolower($order_details->status) == 'SHIPPED') {
				$details['status'] = 'SHIPPED';
				$details['fk_status'] = $order_details->status;

				$db->where('orderItemId', $orderItemId);
				if ($db->update(TBL_FK_ORDERS, $details))
					$log->write('UPDATE: Updated Order status to SHIPPED with order ID ' . $orderId . ' & Item ID ' . $orderItemId . ' added.', 'shipped-orders');
				else
					$log->write('UPDATE: Unable to update order status to SHIPPED with order ID ' . $orderId . ' & Item ID ' . $orderItemId . ' :: ' . $db->getLastError(), 'shipped-orders');
			}
		}
	}

	/** FIMS ORDERS SECTION BEGIN */

	// ADMIN PANEL
	function view_orders($type, $is_returns = false, $is_count = false)
	{
		global $db;

		if ($is_returns) {
			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
			// $db->where('o.account_id', $this->account->account_id);
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			// $db->joinWhere(TBL_PRODUCTS_ALIAS ." p", "p.account_id", $this->account->account_id);
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
			$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'INNER');
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			// $db->where('o.order_type', 'FBF', '!=');
			if ($type == "return_claimed")
				$db->where('(r.r_shipmentStatus LIKE ? AND r.r_approvedSPFId IS NULL)', array(trim($type) . '%'));
			else if ($type == "return_claimed_undelivered")
				$db->where('(r.r_shipmentStatus LIKE ? AND r.r_approvedSPFId IS NOT NULL)', array('return_claimed%'));
			else
				$db->where('r.r_shipmentStatus', trim($type), "LIKE");

			if ($type == "return_received") {
				$db->orderBy('r_receivedDate', 'ASC');
				$orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', null, "o.orderDate, o.orderId, o.orderItemId, o.order_type, o.title, o.account_id, fa.account_name, fa.locationId, o.totalPrice, o.fsn, o.uid, o.shippedDate, COALESCE(p.corrected_sku, p.sku) as sku, o.status, o.is_flagged, o.quantity, r.returnId, r.r_source, r.r_quantity, r.r_createdDate, r.r_reason, r.r_subReason, r.r_trackingId, r.r_shipmentStatus, r.r_comments, r.r_deliveredDate, r.r_receivedDate, pc.pid as combo_ids, pc.thumb_image_url as cti, pm.pid as pid, pm.thumb_image_url as pti");
			} else if ($type == "return_claimed" || $type == "return_claimed_undelivered") {
				$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "INNER");
				$db->joinWhere(TBL_FK_CLAIMS . " c", 'c.claimStatus', 'pending');
				$db->orderBy('claimDate', 'ASC');
				$orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', null, "o.orderId, o.orderItemId, o.order_type, o.title, o.account_id, fa.account_name, fa.locationId, o.totalPrice, o.fsn, o.uid, o.shippedDate, COALESCE(p.corrected_sku, p.sku) as sku, o.status, o.is_flagged, o.sellingPrice, o.shippingCharge, o.quantity, r.returnId, r.r_source, r.r_quantity, r.r_createdDate, r.r_reason, r.r_subReason, r.r_trackingId, r.r_shipmentStatus, r.r_comments, r.r_deliveredDate, r.r_receivedDate, r.r_approvedSPFId, c.claimId, c.claimStatus, c.claimStatusFK, c.claimDate, c.claimComments, c.claimNotes, c.claimReimbursmentAmount, c.claimProductCondition, c.lastUpdateAt, pc.pid as combo_ids, pc.thumb_image_url as cti, pm.pid, pm.thumb_image_url as pti");
				// echo $db->getLastQuery();
			} else if ($type == "return_completed") {
				$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "LEFT");
				$db->where('r.r_completionDate', array(date('Y-m-d H:i:s', strtotime('-14 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
				$orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', null, "o.orderId, o.orderItemId, o.order_type, o.title, o.account_id, fa.account_name, fa.locationId, o.totalPrice, o.fsn, o.uid, o.shippedDate, COALESCE(p.corrected_sku, p.sku) as sku, o.status, o.is_flagged, o.quantity, r.returnId, r.r_source, r.r_quantity, r.r_createdDate, r.r_reason, r.r_subReason, r.r_trackingId, r.r_shipmentStatus, r.r_comments, r.r_receivedDate, r.r_completionDate, c.claimId, c.claimStatus, c.claimStatusFK, c.claimDate, c.claimComments, c.claimReimbursmentAmount, c.claimProductCondition, c.lastUpdateAt, pc.thumb_image_url as cti, pm.thumb_image_url as pti");
			} else if ($type == "return_unexpected") {
				$db->orderBy('r_createdDate', 'DESC');
				$orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', null, "o.orderDate, o.orderId, o.orderItemId, o.order_type, o.title, o.account_id, fa.account_name, fa.locationId, o.totalPrice, o.fsn, o.uid, o.shippedDate, COALESCE(p.corrected_sku, p.sku) as sku, o.status, o.is_flagged, o.quantity, r.returnId, r.r_source, r.r_quantity, r.r_createdDate, r.r_reason, r.r_subReason, r.r_trackingId, r.r_shipmentStatus, r.r_comments, r.r_deliveredDate, r.r_receivedDate, pc.pid as combo_ids, pc.thumb_image_url as cti, pm.pid as pid, pm.thumb_image_url as pti");
			} else if ($type == "delivered") {
				$db->orderBy('r_expectedDate', 'ASC');
				$db->orderBy('r_deliveredDate', 'ASC');
				$orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', null, "o.orderId, o.orderItemId, o.order_type, o.title, o.account_id, fa.account_name, fa.locationId, o.totalPrice, o.fsn, o.uid, o.shippedDate, COALESCE(p.corrected_sku, p.sku) as sku, o.status, o.is_flagged, o.quantity, r.returnId, r.r_source, r.r_quantity, r.r_createdDate, r.r_reason, r.r_subReason, r.r_trackingId, r.r_shipmentStatus, r.r_comments, r.r_deliveredDate, r.r_expectedDate, pc.pid as combo_ids, pc.thumb_image_url as cti, pm.pid as pid, pm.thumb_image_url as pti");
			} else {
				$db->orderBy('r_createdDate', 'ASC');
				$orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', null, "o.orderId, o.orderItemId, o.order_type, o.title, o.account_id, fa.account_name, fa.locationId, o.totalPrice, o.fsn, o.uid, o.shippedDate, COALESCE(p.corrected_sku, p.sku) as sku, o.status, o.is_flagged, o.quantity, r.returnId, r.r_source, r.r_quantity, r.r_createdDate, r.r_reason, r.r_subReason, r.r_trackingId, r.r_shipmentStatus, r.r_comments, r.r_createdDate, r.r_expectedDate, pc.pid as combo_ids, pc.thumb_image_url as cti, pm.pid as pid, pm.thumb_image_url as pti");
			}

			$orders_n = array();
			foreach ($orders as $order) {
				$orders_n[$order->orderId][] = $order;
			}
		} else {
			// $db->where('o.account_id', $this->account->account_id);

			if ($type == 'shipped') {
				if (date('H', time()) < '09') {
					$db->where('shippedDate', array(date('Y-m-d H:i:s', strtotime('yesterday 09:00 AM')), date('Y-m-d H:i:s', strtotime('today 09:00 AM'))), 'BETWEEN');
				} else {
					$db->where('shippedDate', array(date('Y-m-d H:i:s', strtotime('today 09:00 AM')), date('Y-m-d H:i:s', strtotime('tomorrow 09:00 AM'))), 'BETWEEN');
				}
			}

			if ($type == 'cancelled') {
				$db->where('o.lastUpdateAt', array(date('Y-m-d H:i:s', strtotime('-3 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
			}

			if ($type == "new") {
				$db->where("o.dispatchAfterDate", date('Y-m-d H:i:s', time()), "<=");
			}

			if ($type == "upcoming") {
				$db->where("o.status", "NEW");
				$db->where("(o.dispatchAfterDate > ? OR o.hold = ?)", array(date('Y-m-d H:i:s', time()), "1"));
			} else {
				$db->where('o.status', strtoupper($type));
				$db->where('o.hold', '0');
			}
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
			$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'INNER');
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->orderBy('o.orderDate', 'ASC');
			$db->orderBy('o.dispatchAfterDate', 'ASC');
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', null, "o.*, fa.account_name, fa.locationId, COALESCE(p.corrected_sku, p.sku) as sku, pc.thumb_image_url as cti, pm.thumb_image_url as pti");
			// echo $db->getLastQuery();
			// return;
			// $orders = $db->ObjectBuilder()->rawQuery('CALL view_orders(?, ?)', array(strtoupper($type), $this->account->account_id));
			$orders_n = array();
			foreach ($orders as $order) {
				if (isset($order->forwardTrackingId) && $order->forwardTrackingId != "")
					$orders_n[$order->forwardTrackingId][] = $order;
				else
					$orders_n[$order->shipmentId][] = $order;
			}
		}

		if ($is_count)
			return count($orders_n);
		else
			return $orders_n;
	}

	function get_order_count($type, $is_returns = false)
	{
		global $db;

		$orders_n = 0;
		$lp_orders = array();
		$upcoming_dispatchByDate = array();
		if ($is_returns) {
			if ($type == "return_completed") {
				$db->where('r.r_completionDate', array(date('Y-m-d H:i:s', strtotime('-14 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
			}

			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
			if ($type == "return_claimed") {
				$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "INNER");
				$db->joinWhere(TBL_FK_CLAIMS . " c", 'c.claimStatus', 'pending');
				$db->where('(r.r_shipmentStatus LIKE ? AND r.r_approvedSPFId IS NULL)', array(trim($type) . '%'));
			} else if ($type == "return_claimed_undelivered") {
				$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "INNER");
				$db->joinWhere(TBL_FK_CLAIMS . " c", 'c.claimStatus', 'pending');
				$db->where('(r.r_shipmentStatus LIKE ? AND r.r_approvedSPFId IS NOT NULL)', array('return_claimed%'));
			} else
				$db->where('r.r_shipmentStatus', trim($type), "LIKE");

			$db->where('o.order_type', 'FBF', '!=');
			$db->groupBy('o.orderId');
			$orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', null, "COUNT(o.orderItemId) as orders_count");
			$orders_n += count($orders);
		} else {
			if ($type == 'shipped') {
				$db->where('hold', '0');
				$db->where('status', strtoupper($type));
				if (date('H', time()) < '09') {
					$db->where('o.shippedDate', array(date('Y-m-d H:i:s', strtotime('yesterday 09:00 AM')), date('Y-m-d H:i:s', strtotime('today 09:00 AM'))), 'BETWEEN');
				} else {
					$db->where('o.shippedDate', array(date('Y-m-d H:i:s', strtotime('today 09:00 AM')), date('Y-m-d H:i:s', strtotime('tomorrow 09:00 AM'))), 'BETWEEN');
				}

				$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
				$db->groupBy('a.account_name');
				$db->groupBy('o.order_type');
				$db->groupBy('vendorName');
				// $db->orderBy('a.account_id', 'ASC');

				$orders = $db->objectBuilder()->get(TBL_FK_ORDERS . ' o', null, array('COUNT(o.orderItemId) as order_count', 'a.account_name', 'o.order_type', 'JSON_UNQUOTE( JSON_EXTRACT(`pickupDetails`, "$.vendorName") ) AS vendorName'));

				foreach ($orders as $order) {
					$orders_n += $order->order_count;
					// $lp_orders['logistic_partner'][$order->account_name][$order->vendorName][$order->order_type] = $order->order_count;
				}
			} else if ($type == 'upcoming') {
				$db->where("status", "NEW");
				$db->where("(dispatchAfterDate > ? OR hold = ?)", array(date('Y-m-d H:i:s', time()), "1"));
				// $db->where("(status = ? AND dispatchAfterDate < ?)", array("NEW", date('Y-m-d H:i:s', time())));
				// $db->orWhere("(status = ? AND (hold = ? OR hold = ?))", array("NEW", "0", "1"));

				$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS, null, "COUNT(orderItemId) as orders_count");
				$orders_n += $orders[0]->orders_count;

				$db->where("status", "NEW");
				$db->where("(dispatchAfterDate > ? AND hold = ?)", array(date('Y-m-d H:i:s', time()), "0"));
				$db->orderBy('dispatchDate', 'ASC');
				$db->groupBy('order_type, dispatchDate');
				$dispatchByDates = $db->ObjectBuilder()->get(TBL_FK_ORDERS, null, "DISTINCT(DATE_FORMAT(dispatchByDate, '%Y-%m-%d' )) AS dispatchDate, order_type, COUNT(orderItemId) as order_count");
				foreach ($dispatchByDates as $dates) {
					$upcoming_dispatchByDate[$dates->order_type][$dates->dispatchDate] = $dates->order_count;
				}
			} else {
				$db->where('hold', '0');
				$db->where('status', strtoupper($type));
				if ($type == 'new') {
					$db->where("dispatchAfterDate", date('Y-m-d H:i:s', time()), "<=");
				}

				if ($type == 'cancelled') {
					$db->where('orderDate', array(date('Y-m-d H:i:s', strtotime('-7 days')), date('Y-m-d H:i:s', strtotime('tomorrow') - 1)), 'BETWEEN');
				}

				$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS, null, "COUNT(orderItemId) as orders_count");
				$orders_n += $orders[0]->orders_count;
			}
		}

		$return = array('count' => $orders_n);
		if (!empty($lp_orders))
			$return = array_merge($return, $lp_orders);

		if (!empty($upcoming_dispatchByDate))
			$return = array_merge($return, array('dispatchByDate' => $upcoming_dispatchByDate));

		return $return;
	}

	/** RETURNS API CALLBACK SECTION BEGIN */
	// RETURNS
	function get_returns()
	{
		global $orders;

		$types = array("customer_return", "courier_return");

		$orders = array();
		foreach ($types as $type) {
			$order = $this->get_return_approved_orders($type);
			// $orders = array_merge($orders, $order);
			if ($order)
				$this->insert_return_orders($order, $type);
			else
				echo 'No orders Fetch for: ' . $this->account->account_name . ' :: ' . $type . "\n";
		}

		return $orders;
	}

	function get_return_by_id($returnId)
	{
		global $api_url;

		$url = $api_url . "/v2/returns?returnIds=" . $returnId;
		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		$order_detail = $this->fk_run_curl($url, $request, $header);
		$order_detail = json_decode($order_detail);

		return $order_detail;
	}

	/**
	 * date format - 2018-03-16T00:00:00+05:30
	 **/
	function get_return_approved_orders($type, $hasMore = false, $nextPageUrl = '', $dates = array())
	{
		global $api_url, $returns, $orders;

		if ($nextPageUrl != "") {
			$url = $api_url . "/v2" . $nextPageUrl;
		} else {
			$modified_date = "";
			$create_after = "";

			if (empty($dates)) {
				$modified_date = "&modifiedAfter=" . urlencode(date('c', strtotime("-35 minutes")));
				// $create_after = "&createdAfter=".urlencode(date('c', strtotime("today -10 days")));
			} else {
				if (isset($dates['modifiedAfter']))
					$modified_date = "&modifiedAfter=" . urlencode($dates['modifiedAfter']);
				if (isset($dates['createdAfter']))
					$create_after = "&createdAfter=" . urlencode($dates['createdAfter']);
			}

			$url = $api_url . "/v2/returns?source=" . $type . $modified_date . $create_after . "&locationId=" . $this->account->locationId;
		}

		$request = "GET";

		$header = array(
			"authorization: Bearer " . $this->check_access_token(),
			"content-type: application/json"
		);

		if (!$hasMore) {
			$orders_json = $this->fk_run_curl($url, $request, $header);
			if ($orders_json) {
				$orders_obj = json_decode($orders_json);
				$orders = $orders_obj->returnItems;
				if (isset($orders_obj->hasMore) && $orders_obj->hasMore) {
					$this->get_return_approved_orders($type, true, $orders_obj->nextUrl);
				}
			}
		} else {
			$orders_json = $this->fk_run_curl($url, $request, $header);
			if ($orders_json) {
				$orders_obj = json_decode($orders_json);
				$order_items = $orders_obj->returnItems;
				$orders = array_merge($orders, $order_items);
				if (isset($orders_obj->hasMore) && $orders_obj->hasMore) {
					$this->get_return_approved_orders($type, true, $orders_obj->nextUrl);
				}
			}
		}

		return $orders;
	}

	/** FIMS RETURNS SECTION BEGIN */
	/**
	 * ONLY FOR PUSH NOTIFICATION
	 */
	function insert_return($orders)
	{
		global $db, $log, $stockist;

		// $log->logfile = TODAYS_LOG_PATH.'/returns.log';
		// if ($this->account->sandbox)
		// 	$log->logfile = TODAYS_LOG_PATH.'/returns-sandbox.log'; $log->write(json_encode($orders));

		foreach ($orders as $order) {
			$details = array();
			$orderItemId = $order->orderItemId;

			$details = array(
				'orderItemId' => $order->orderItemId,
				'returnId' => $order->returnId,
				'r_status' => 'created', // STATIC STATUS FOR ALL NEW RETURNS
				'r_source' => $order->source,
				'r_action' => $order->action,
				'r_quantity' => $order->quantity,
				'r_createdDate' => $order->createdDate,
				'r_courierName' => $order->courierName,
				'r_reason' => $order->reason,
				'r_subReason' => $order->subReason,
				'r_trackingId' => $order->trackingId,
				'r_shipmentStatus' => 'start', // STATIC STATUS FOR ALL NEW RETURNS
				'r_shipmentId' => $order->shipmentId,
				'r_comments' => $db->escape($order->comments),
				'r_completionDate' => $order->completionDate,
				'r_replacementOrderItemId' => $order->replacementOrderItemId,
				'r_productId' => $order->productId,
				'r_listingId' => $order->listingId,
				'r_expectedDate' => $order->expectedDate,
				'r_serviceProfile' => $order->serviceProfile,
				'insertDate' => $db->now(),
				'insertBy' => 'push_notify'
			);

			if (/*$order->reason == "MISSING_ITEM" && $order->subReason == "MISSING_PRODUCT" && */(strpos($order->action, 'dnt_expect') !== FALSE)) {
				$details['r_status'] = $order->status;
				$details['r_action'] = $order->action;
				$details['r_shipmentStatus'] = 'return_unexpected';
			}

			if (is_null($order->expectedDate) && (strpos($details['r_action'], 'dnt_expect') === FALSE))
				$details['r_expectedDate'] = date('c', strtotime("+29 day"));

			if ($db->insert(TBL_FK_RETURNS, $details)) {
				// TO-DO: CHECK IF THE ORDER IS SHIPPED. CANCEL IF NOT SHIPPED
				// echo $db->getLastQuery() .'<br />';
				$log->write('OI: ' . $orderItemId . "\tReturn Initiated", 'order-status');
				$log->write('INSERT: Inserted Return Order with return ID ' . $order->returnId . ' & Item ID ' . $orderItemId . "\t" . json_encode($details), 'returns');
			} else {
				// echo 'INSERT ERROR: '.$db->getLastError() .' QUERY:'.$db->getLastQuery() .'<br />';
				$log->write('INSERT: Unable to insert return order with OrderItem ID ' . $orderItemId . ' ' . $db->getLastError(), 'returns');
			}
		}

		return;
	}

	// INSERT VIA CRON
	function insert_return_orders($orders, $type)
	{
		global $db, $log, $stockist;

		// $log->logfile = TODAYS_LOG_PATH.'/returns.log';

		$insert = 0;
		$update = 0;
		$error = 0;

		foreach ($orders as $order) {
			$details = array();
			$orderItemId = $order->orderItemId;
			$orderId = $order->orderId;

			$details = array(
				'orderItemId' => $order->orderItemId,
				'returnId' => $order->returnId,
				'r_source' => $type,
				'r_action' => isset($order->action) ? $order->action : "",
				'r_quantity' => $order->quantity,
				'r_createdDate' => $order->createdDate,
				'r_courierName' => $order->courierName,
				'r_reason' => $order->reason,
				'r_subReason' => $order->subReason,
				'r_trackingId' => $order->trackingId,
				'r_shipmentStatus' => $order->shipmentStatus,
				'r_shipmentId' => $order->shipmentId,
				'r_comments' => $db->escape($order->comments),
				// 'r_completionDate' => $order->completionDate,
				// 'r_productId' => $order->productId,
				// 'r_listingId' => $order->listingId,
				'r_expectedDate' => $order->expectedDate,
				'insertDate' => $db->now(),
				'insertBy' => 'CRON'
			);

			$details['r_status'] = 'start';
			if (isset($order->status) && !empty($order->status))
				$details['r_status'] = $order->status;

			if (($order->reason == "MISSING_ITEM" && $order->subReason == "MISSING_PRODUCT") || (strpos($details['r_action'], 'dnt_expect') !== FALSE)) {
				$details['r_status'] = $order->status;
				$details['r_action'] = 'refund_dnt_expect';
				$details['r_shipmentStatus'] = 'return_unexpected';
			}

			if (is_null($order->expectedDate) && (strpos($details['r_action'], 'dnt_expect') === FALSE))
				$details['r_expectedDate'] = date('c', strtotime("+29 day"));

			if ($order->serviceProfile == "FBF" && $order->status == "delivered") {
				$details['r_status'] = "return_completed";
				$details['r_shipmentStatus'] = "return_completed";
				$details['r_receivedDate'] = date('c');
				$details['r_completionDate'] = date('c');
			}

			$db->where('returnId', $order->returnId);
			if ($db->has(TBL_FK_RETURNS)) {
				$db->where('returnId = ? AND r_shipmentStatus != ? AND r_shipmentStatus != ? AND r_shipmentStatus != ? AND r_shipmentStatus != ?', array($order->returnId, $order->shipmentStatus, 'return_received', 'return_completed', 'return_claimed'));
				if ($db->has(TBL_FK_RETURNS)) {
					$update++;
					$this->update_return_orders(array($order), $type);
				}
			} else {
				if ($db->insert(TBL_FK_RETURNS, $details)) {
					// echo $db->getLastQuery() .'<br />';
					$insert++;
					$log->write('OI: ' . $orderItemId . "\tReturn Initiated", 'order-status');
					$log->write('INSERT: Inserted Return Order with order ID ' . $orderId . ' & Item ID ' . $orderItemId . "\n" . json_encode($details), 'returns-cron');
				} else {
					// echo 'INSERT ERROR: '.$db->getLastError() .' QUERY:'.$db->getLastQuery() .'<br />';
					$error++;
					$log->write('INSERT: Unable to insert return order with order ID ' . $orderId . ' & Item ID ' . $orderItemId . ' ' . $db->getLastError(), 'returns-cron');
				}
			}
		}

		// echo '<pre>';
		echo 'Fetch for: ' . $this->account->account_name . ' :: ' . $type . "\n";
		echo 'Total Fetch: ' . count($orders) . "\n";
		echo 'Insert: ' . $insert . "\n";
		echo 'Update: ' . $update . "\n";
		echo 'Error: ' . $error . "\n";
		return;
	}

	// UPDATE RETURNS CRON
	function update_return_orders($orders, $type)
	{
		global $db, $log, $stockist;

		// $log->logfile = TODAYS_LOG_PATH.'/returns-update.log';
		$update = 0;
		$error = 0;

		foreach ($orders as $order) {
			$details = array();
			$orderItemId = $order->orderItemId;
			$orderId = $order->orderId;

			$payment_details = $this->get_payments_details($orderItemId);
			$is_delivered = false;
			if ($payment_details && $payment_details->return_type == "NA" && strtotime($order->orderDate) < strtotime("today -25 days") && $order->status != "cancelled" && $order->status == "created" && $order->shipmentStatus == "start") {
				$order->status = 'cancelled';
				$is_delivered = true;
			}

			$db->where('returnId', $order->returnId);
			$return = $db->ObjectBuilder()->getOne(TBL_FK_RETURNS);
			if ($return->r_status == "return_received" || $return->r_status == "return_completed" || $return->r_status == "return_claimed" || $return->r_status == "return_cancelled"/* || $return->r_status == "delivered"*/) {
				return;
			}

			if ($order->status == "completed") {
				$order->status = "delivered";
				$order->shipmentStatus = "delivered";
			}

			$details = array(
				// 'status' => 'RETURN',
				'r_status' => $order->status,
				'r_source' => $type,
				'r_quantity' => $order->quantity,
				'r_createdDate' => $order->createdDate,
				'r_courierName' => $order->courierName,
				'r_reason' => $order->reason,
				'r_subReason' => $order->subReason,
				'r_trackingId' => $order->trackingId,
				'r_shipmentStatus' => $order->shipmentStatus,
				'r_shipmentId' => $order->shipmentId,
				'r_comments' => $db->escape($order->comments),
				'r_serviceProfile' => $order->serviceProfile,
			);

			if (!is_null($order->expectedDate))
				$details['r_expectedDate'] = $order->expectedDate;

			if ($order->status == "cancelled") {
				if ($is_delivered)
					$details['r_status'] = 'delivered-cancelled';
				$details['r_shipmentStatus'] = 'return_cancelled';
				$details['r_cancellationDate'] = date('c');
			}

			if ($order->status == "delivered")
				$details['r_deliveredDate'] = date('c'); // AS PER FLIPKART

			if ($order->serviceProfile == "FBF" && $order->status == "delivered") {
				$details['r_status'] = "return_completed";
				$details['r_shipmentStatus'] = "return_completed";
				$details['r_receivedDate'] = date('c');
				$details['r_completionDate'] = date('c');
			}

			if (strpos($return->r_action, 'dnt_expect') !== FALSE && $order->status == "delivered") {
				$details['r_shipmentStatus'] = "return_unexpected";
			}

			$db->where('returnId', $order->returnId);
			if ($db->has(TBL_FK_RETURNS)) {
				$db->where('returnId', $order->returnId);
				if ($db->update(TBL_FK_RETURNS, $details)) {
					$update++;
					// echo 'UPDATE: -- '.$db->getLastQuery() .'<br />';
					$log->write('UPDATE: Updated Return Order with order ID ' . $orderId . ' & Item ID ' . $orderItemId . ' ' . json_encode($details), 'returns-update');
					if ($order->status == "cancelled" && $return->r_shipmentStatus != 'return_cancelled') {
						$db->where('returnId', $order->returnId);
						if ($db->has(TBL_FK_CLAIMS))
							$this->update_claim_details($order->returnId, 'CLAIM_WITHDRAWN', 'Return cancelled on ' . date('Y-m-d H:i:s'), '0.00', '', 'cancelled');
					}
				} else {
					$error++;
					// echo 'UPDATE ERROR: '.$db->getLastError() .' QUERY:'.$db->getLastQuery() .'<br />';
					$log->write('UPDATE: Unable to update return order with order ID ' . $orderId . ' & Item ID ' . $orderItemId . ' ' . $db->getLastError(), 'returns-update');
				}
			}
		}
		echo 'Update for: ' . $this->account->account_name . ' :: ' . $type . "\n";
		echo 'Total Fetch: ' . count($orders) . "\n";
		echo 'Update: ' . $update . "\n";
		echo 'Error: ' . $error . "\n";
		return;
	}

	// TRIGGERED FROM mark_return_received, mark_return_complete
	function update_return_received($trackingId, $type)
	{
		global $db, $log, $stockist, $current_user;

		if (empty($trackingId)) {
			return;
		}

		// $log->logfile = TODAYS_LOG_PATH.'/return-received.log';
		if ($type == "completed") {
			$db->where('(r.r_shipmentStatus = ? OR r.r_shipmentStatus = ?)', array('return_received', 'return_completed'));
		} else {
			$db->where('(r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus = ? OR r.r_shipmentStatus IS NULL)', array('start', 'in_transit', 'out_for_delivery', 'delivered', 'return_claimed_undelivered', 'return_received'));
		}
		$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
		$db->where('(r.r_trackingId = ? OR o.pickupDetails LIKE ? OR o.deliveryDetails LIKE ?)', array($trackingId, '%' . $trackingId . '%', '%' . $trackingId . '%'));
		if ($orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', null, "o.title, o.totalPrice, o.quantity, o.commissionFee, o.fixedFee, o.invoiceAmount, o.shippingZone, o.shippingSlab, o.fsn, o.uid, o.orderId, o.is_flagged, r.*")) {
			$i = 0;
			foreach ($orders as $order) {
				if (($order->r_shipmentStatus == 'return_received' && $type == "received") || ($order->r_shipmentStatus == 'return_completed' && $type == "completed")) {
					$log->write('RETURNS: Return already marked ' . $order->r_shipmentStatus . ' with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'return-received');
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['trackingId'] = $trackingId;
					$return[$i]['message'] = 'Return already marked ' . strtoupper($order->r_shipmentStatus);
					$return[$i]['type'] = "error";
				} else if ($order->r_shipmentStatus == 'return_claimed' && $type == "completed") {
					$log->write('RETURNS: Return claimed so cannot be Acknoledged with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'return-received');
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['trackingId'] = $trackingId;
					$return[$i]['message'] = 'Return already marked ' . strtoupper($order->r_shipmentStatus);
					$return[$i]['type'] = "error";
				} else if (($order->r_shipmentStatus != 'return_received' && $order->r_shipmentStatus != 'return_claimed_undelivered') && $type == "completed") {
					$log->write('RETURNS: Return not marked received so cannot be Acknoledged with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'return-received');
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['trackingId'] = $trackingId;
					$return[$i]['message'] = 'Return status is ' . $order->r_shipmentStatus;
					$return[$i]['type'] = "error";
				} else if ($order->r_shipmentStatus == 'return_completed' || ($type == "received" && $order->r_shipmentStatus == 'return_acknowledge')) {
					$log->write('RETURNS: Return already marked ' . $order->r_shipmentStatus . ' with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'return-received');
					$return[$i]['title'] = $order->title;
					$return[$i]['order_item_id'] = $order->orderItemId;
					$return[$i]['trackingId'] = $trackingId;
					$return[$i]['message'] = 'Return already marked ' . strtoupper($order->r_shipmentStatus);
					$return[$i]['type'] = "error";
					// } else if ($order->r_shipmentStatus == 'return_claimed_undelivered' && is_null($order->r_approvedSPFId) ){
					// 	$log->write('RETURNS: Return already marked '.$order->r_shipmentStatus.' with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'return-received');
					// 	$return[$i]['title'] = $order->title;
					// 	$return[$i]['order_item_id'] = $order->orderItemId;
					// 	$return[$i]['trackingId'] = $trackingId;
					// 	$return[$i]['message'] = 'Return received for SPF Claimed Order.';
					// 	$return[$i]['type'] = "error";
					// 	// return $return;
				} else {
					$details = array(
						'r_shipmentStatus' => 'return_' . $type,
						'r_status' => 'return_' . $type,
					);

					if ($type == "received" || $order->r_shipmentStatus == 'return_claimed_undelivered')
						$details['r_receivedDate'] = date('c');

					if ($type == "completed") {
						$details['r_completionDate'] = date('c');
						$details['r_uid'] = null;
						if ($order->uid)
							$details['r_uid'] = json_encode(array_map(function ($val) {
								return 'saleable';
							}, array_flip(json_decode($order->uid, true))));
					}

					if ($order->r_source == "customer_return" && $type == "completed") {
						if ($order->r_quantity != $order->quantity) {
							$return[$i]['title'] = $order->title;
							$return[$i]['order_item_id'] = $order->orderItemId;
							$return[$i]['trackingId'] = $trackingId;
							$return[$i]['message'] = 'Customer Return with qty mismatch. Acknowledge Manually.';
							$return[$i]['type'] = "error";
							continue;
						}
					}
					$db->where('r_trackingId', $order->r_trackingId);
					if ($db->update(TBL_FK_RETURNS, $details)) {

						if ($order->r_shipmentStatus == 'return_claimed_undelivered') {
							$reimbursemed_amount = (is_null($order->r_approvedSPFId)) ? "0.00" : $order->r_approvedSPFAmt;
							$this->update_claim_details($order->returnId, 'CLAIM_WITHDRAWN', 'Return Received on ' . date('Y-m-d H:i:s'), $reimbursemed_amount, date('Y-m-d H:i:s'), 'received');
						}

						// CHECK FOR MULTIPLE SINGLE QTY RETURNS AND CANCEL THEM
						if ($order->quantity == $order->r_quantity && $type == "completed") {
							$db->where('orderItemId', $order->orderItemId);
							$db->where('r_trackingId', $trackingId, '!=');
							$db->where('(r_shipmentStatus = ? OR r_shipmentStatus = ? OR r_shipmentStatus = ? OR r_shipmentStatus IS NULL)', array('start', 'in_transit', 'out_for_delivery'));
							$access_returns = $db->getValue(TBL_FK_RETURNS, 'returnId', NULL);
							foreach ($access_returns as $return_id) {
								$access_details['r_status'] = 'cancelled';
								$access_details['r_shipmentStatus'] = 'return_cancelled';
								$access_details['r_cancellationDate'] = date('c');
								$db->where('returnId', $return_id);
								$db->update(TBL_FK_RETURNS, $access_details);
								$log->write('OI: ' . $order->orderItemId . "\tReturn Cancelled against received " . $trackingId . " \n:: USER: " . $current_user['userID'] . "\n:: BACK TRACE: " . json_encode(debug_backtrace()), 'order-status');
							}
						}

						$return[$i]['title'] = $order->title;
						$return[$i]['order_item_id'] = $order->orderItemId;
						$return[$i]['trackingId'] = $trackingId;
						$return[$i]['is_flagged'] = $order->is_flagged;

						if ($type == "received") {
							$is_approved_claim = (!is_null($order->r_approvedSPFId) ? " for SPF Claimed order" : "");
							$log->write('OI: ' . $order->orderItemId . "\tReturn Received \n:: USER: " . $current_user['userID'] . "\n:: BACK TRACE: " . json_encode(debug_backtrace()), 'order-status');
							$log->write('RETURNS: Received Received with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'return-received');
							$return[$i]['message'] = 'Return Received Successfully' . $is_approved_claim;
						} else {
							$log->write('RETURNS: Received Acknowledged with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'return-received');
							$return[$i]['message'] = 'Return Acknowledged Successfully';
						}
						$return[$i]['type'] = "success";

						// FEATURE -- REDUCE RETURNS STOCK --
						// FEATURE -- ADD NORMAL STOCK --
						if ($type == "completed") {
							$log->write('OI: ' . $order->orderItemId . "\tReturn Completed", 'order-status');
							if ($this->payments)
								$this->payments->fetch_payout($order->orderItemId);

							// UID DRIVE
							if (!is_null($order->uid)) {
								$uids = json_decode($order->uid, true);
								foreach ($uids as $uid) {
									$stockist->update_inventory_status($uid, 'qc_pending');
									$stockist->add_inventory_log($uid, 'sales_return', 'Flipkart Return :: ' . $order->returnId);
									// CHECK IF WARRANTY REGISTER & REMOVE DETAILS IF EXISTS
								}
							}
						}
					} else {
						$log->write('RETURNS: Unable to update return with order ID ' . $order->orderId . ' & Item ID ' . $order->orderItemId, 'return-received');
						$return[$i]['title'] = $order->title;
						$return[$i]['order_item_id'] = $order->orderItemId;
						$return[$i]['trackingId'] = $trackingId;
						$return[$i]['message'] = 'Return Not Updated';
						$return[$i]['type'] = "error";
						$return[$i]['error'] = $db->getLastQuery();
					}
				}
				$i++;
			}
		} else {
			// echo $db->getLastQuery();
			// echo $db->getLastError();
			// exit;
			$log->write('RETURNS: Not found for ' . $trackingId, 'return-received');
			$return[0]['title'] = "";
			$return[0]['order_item_id'] = "";
			$return[0]['trackingId'] = $trackingId;
			$return[0]['message'] = 'Return Not Found';
			$return[0]['type'] = "error";
		}
		return $return;
	}

	// PUSH NOTIFICATION
	function update_fk_return_order_status($returnId, $status, $orders)
	{
		global $db, $log, $stockist;

		// $log->logfile = TODAYS_LOG_PATH.'/returns.log';
		foreach ($orders as $order) {
			$details['r_status'] = strtolower($status);
			$details['r_shipmentStatus'] = strtolower($status);
			if ($status == 'RETURN_COMPLETED') {
				$status = 'delivered';
				$details['r_status'] = 'delivered';
				$details['r_deliveredDate'] = date('c'); // AS PER FLIPKART
				$details['r_shipmentStatus'] = 'delivered';
			}

			$db->where('orderItemId', $order->orderItemId);
			$order = $db->objectBuilder()->getOne(TBL_FK_ORDERS, 'orderItemId, status, fsn, quantity');

			$db->where('returnId', $returnId);
			$return_details = $db->getOne(TBL_FK_RETURNS, 'r_shipmentStatus, r_expectedDate, r_action, r_quantity');
			$shipments_status = $return_details['r_shipmentStatus'];

			// GET RETURN STATUS AND UPDATE ONLY IF RECEIVED/CLAIMED/COMPLETED
			if ($shipments_status == 'return_completed' || $shipments_status == 'return_claimed' || $shipments_status == 'return_received' || strpos($shipments_status, 'return_claimed') !== FALSE)
				$details['r_shipmentStatus'] = $shipments_status;

			// UNEXPECTED RETURNS
			// if ($status == 'RETURN_COMPLETED' && (is_null($return_details['r_expectedDate']) || $return_details['r_action'] == 'refund_dnt_expect'))
			if (($status == 'RETURN_COMPLETED' || $status == "delivered") && (is_null($return_details['r_expectedDate']) || (strpos($return_details['r_action'], 'dnt_expect') !== FALSE)))
				$details['r_shipmentStatus'] = 'return_unexpected';

			// CANCELLED BEFORE SHIPPED
			if (isset($order->status) && ($order->status == "NEW" || $order->status == "PACKING" || $order->status == "RTD")) {
				$details['r_status'] = 'delivered';
				$details['r_shipmentStatus'] = 'delivered';
				// $details['r_completionDate'] = date('c');
				// $details['r_receivedDate'] = date('c');
			}

			// CANCELLED RETURNS
			if (strtolower($status) == 'cancelled') {
				$details['r_status'] = 'cancelled';
				$details['r_shipmentStatus'] = 'return_cancelled';
				$details['r_cancellationDate'] = date('c');
			}

			// FALLBACK IN CASE IF THE CRON HAS UPDATED THE RETURN STATUS AND CONTINUE WITHOUT FURTHER ACTION
			if (strtolower($details['r_shipmentStatus']) == $shipments_status) {
				$log->write('UPDATE: Already updated Return Order with return ID ' . $returnId . ' & Item ID ' . $order->orderItemId . ' with status ' . $shipments_status, 'returns');
				return;
			}

			$db->where('returnId', $returnId);
			if ($db->update(TBL_FK_RETURNS, $details)) {
				$log->write('UPDATE: Updated Return Order with return ID ' . $returnId . ' & Item ID ' . $order->orderItemId . ' ' . json_encode($details), 'returns');
				if (strtolower($status) == "return_cancelled"/* || strtolower($status) == "return_completed"*/) {
					$log->write('OI: ' . $order->orderItemId . "\tReturn Cancelled", 'order-status');
					if ($this->payments)
						$this->payments->update_fk_payout($order->orderItemId);
				}

				$db->where('returnId', $returnId);
				if ($db->has(TBL_FK_CLAIMS))
					$this->update_claim_details($order->returnId, 'CLAIM_WITHDRAWN', 'Return cancelled on ' . date('Y-m-d H:i:s'), '0.00', '', 'cancelled');
			} else {
				$log->write('UPDATE: Unable to update return with return ID ' . $returnId . ' & order Item ID ' . $order->orderItemId . ' ' . $db->getLastError(), 'returns');
			}
		}
	}

	// PUSH NOTIFICATION
	function update_fk_return_order_details($returnId, $orders)
	{
		global $db, $log;

		// $log->logfile = TODAYS_LOG_PATH.'/returns.log';

		foreach ($orders as $order) {
			foreach ($order as $o_key => $o_value) {
				if (($o_key != "orderItemId") && ($o_key != "returnId")) {
					$details['r_' . $o_key] = $o_value;
				}
			}

			$db->where('returnId', $returnId);
			if ($db->has(TBL_FK_RETURNS)) {
				$db->where('returnId', $returnId);
				if ($db->update(TBL_FK_RETURNS, $details)) {
					$log->write('UPDATE: Updated Return Order with return ID ' . $returnId . ' & Item ID ' . $order->orderItemId . ' ' . json_encode($details), 'returns');
				} else {
					$log->write('UPDATE: Unable to update return with return ID ' . $returnId . ' & order Item ID ' . $order->orderItemId . ' ' . $db->getLastError(), 'returns');
				}
			} else {
				// $returns = $this->get_return_by_id($returnId);
				// $this->insert_return($returns->returnItems);
				// $this->update_fk_return_order_details($returnId, $orders);
				$log->write('UPDATE: NOT FOUND Return with return ID ' . $returnId . ' & order Item ID ' . $order->orderItemId . ' ' . $db->getLastError(), 'returns');
			}
		}
	}

	// AFTER RETURN IS RECEIVED AND NO CLAIM IS NEEDED = MARK_RETURN_COMPLETED
	function update_return_received_status($orderItemId, $return_id, $condition, $is_cancel = false, $uid = "")
	{
		global $db, $log, $stockist;

		// $log->logfile = TODAYS_LOG_PATH.'/return-completed.log';
		$details = array(
			'r_uid' => json_encode(explode(',', $uid)),
			'r_completionDate' => date('c'),
			'r_shipmentStatus' => 'return_completed',
			'r_status' => 'return_completed',
		);

		if ($is_cancel)
			$details['r_receivedDate'] = date('c');

		$db->where('returnId', $return_id);
		if ($db->update(TBL_FK_RETURNS, $details)) {
			$log->write('RETURNS: Received Acknoledged with Order Item ID ' . $orderItemId . ' with ' . $condition . ' condition. No claim filed.', 'return-completed');
			$log->write('OI: ' . $orderItemId . "\tReturn Completed 1", 'order-status');

			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "INNER");
			$db->where('r.returnId', $return_id);
			$order = $db->ObjectBuilder()->getOne(TBL_FK_RETURNS . ' r', null, "o.title, r.*");

			foreach ($condition as $pid => $con) {
				if ($con == "saleable") {
					if (!empty($uid)) {
						$stockist->update_inventory_status($uid, 'qc_pending');
						$stockist->add_inventory_log($uid, 'sales_return', 'Flipkart Return :: ' . $return_id);
					}
				}
				if ($con == "damaged") {
					if (!empty($uid)) {
						$stockist->update_inventory_status($uid, 'qc_pending');
						$stockist->add_inventory_log($uid, 'sales_return', 'Flipkart Return :: ' . $return_id);
					}
				}
			}
			return 'success';
		}
		return 'error';
	}

	/** CLAIMS SECTION BEGIN */
	function update_claim_id($return_id, $new_claim_id, $old_claim_id, $old_notes)
	{
		global $db, $log;

		// $log->logfile = TODAYS_LOG_PATH.'/return-claims.log';
		$db->where('returnId', $return_id);
		$db->where('claimID', $old_claim_id);
		if ($db->update(TBL_FK_CLAIMS, array('claimID' => $new_claim_id, 'claimStatusFK' => 'pending', 'claimNotes' => 'Claim ID updated from ' . $old_claim_id . ' to ' . $new_claim_id . ' - ' . date('d-M-Y H:i:s', time()) . "\n\n" . $old_notes))) {
			$log->write('Return ID ' . $return_id . ' :: Claim ID updated from ' . $old_claim_id . ' to ' . $new_claim_id, 'return-claims');
			return array('type' => 'success');
		} else {
			$log->write('Return ID ' . $return_id . ' :: Unable to update Claim ID from ' . $old_claim_id . ' to ' . $new_claim_id . ' :: Error: ' . $db->getLastError(), 'return-claims');
			return array('type' => 'error', 'msg' => 'Error updating claim ID', 'log' => $db->getLastError());
		}
	}

	function update_claim_status($orderItemId, $return_id, $claim_id, $condition, $uid, $re_claim)
	{
		global $db, $log, $stockist, $current_user;

		// error_reporting(E_ALL);
		// ini_set('display_errors', '1');
		// echo '<pre>';

		if (empty($claim_id)) {
			$return['type'] = $this->update_return_received_status($orderItemId, $return_id, $condition, false, $uid);
			$return['message'] = 'Return Acknowledged Successfully';
			return $return;
		}

		if (empty($orderItemId) || empty($return_id) || empty($condition))
			return;

		// $log->logfile = TODAYS_LOG_PATH.'/return-claims.log';

		if (in_array('damaged', $condition))
			$claim_condition = 'damaged';
		else if (in_array('wrong', $condition))
			$claim_condition = 'wrong';
		else if (in_array('damaged', $condition) && in_array('wrong', $condition))
			$claim_condition = 'wrong damaged';
		else if (in_array('missing', $condition))
			$claim_condition = 'missing';
		else if (in_array('undelivered', $condition))
			$claim_condition = 'undelivered';
		else
			$claim_condition = 'saleable';

		$details = array(
			'orderItemId' => $orderItemId,
			'returnId' => $return_id,
			'claimID' => trim($claim_id),
			'claimStatus' => 'pending',
			'claimDate' => date('c'),
			'claimProductCondition' => $claim_condition,
			'createdBy' => $current_user['userID']
		);

		if ($re_claim) {
			// UPDATE CLAIM STATUS
			$update_details = array(
				'claimStatus' => 'pending',
				'claimProductCondition' => $claim_condition
			);
			$db->where('claimID', $claim_id);
			$db->where('returnId', $return_id);
			if ($db->update(TBL_FK_CLAIMS, $update_details)) {
				// UPDATE RETURN STATUS
				$db->where('returnId', $return_id);
				$db->update(TBL_FK_RETURNS, array('r_status' => 'return_claimed', 'r_shipmentStatus' => (($claim_condition == 'undelivered' ? 'return_claimed_undelivered' : ($claim_condition == 'missing' ? 'return_claimed_missing' : 'return_claimed')))));
				$log->write('CLAIM: Re-Claim logged for Return ID ' . $return_id . ' and Claim ID ' . $claim_id, 'return-claims');
				$log->write('OI: ' . $orderItemId . "\tReturn Claimed", 'order-status');
				return 'success reclaim';
			}
			return 'error reclaim';
		}

		$db->where('claimID', $claim_id);
		$db->where('returnId', $return_id);
		if ($db->has(TBL_FK_CLAIMS)) {
			return 'existing';
		}

		if ($db->insert(TBL_FK_CLAIMS, $details)) {
			$r_uid[$uid] = $claim_condition;
			$r_uid = json_encode($r_uid);
			$db->where('returnId', $return_id);
			$db->update(TBL_FK_RETURNS, array('r_uid' => $r_uid, 'r_status' => 'return_claimed', 'r_shipmentStatus' => (($claim_condition == 'undelivered' ? 'return_claimed_undelivered' : ($claim_condition == 'missing' ? 'return_claimed_missing' : 'return_claimed')))));
			$log->write('CLAIM: New claim logged for Return ID ' . $return_id . ' and Claim ID ' . $claim_id, 'return-claims');
			$log->write('OI: ' . $orderItemId . "\tReturn Claimed", 'order-status');
			return 'success';
		} else {
			return $db->getLastError();
		}

		return 'error';
	}

	function update_claim_details($return_id, $claim_status, $claim_comments, $claim_amount, $received_on = "", $return_status = "", $product_condition = "")
	{
		global $db, $log, $stockist, $current_user;

		if (empty($return_id) || empty($claim_status) || empty($claim_comments))
			return;

		// $log->logfile = TODAYS_LOG_PATH.'/return-claims.log';

		$return_status = ($claim_status == 'return_pending') ? 'claimed' : ($return_status != '' ? $return_status : 'completed');

		$return_details = array(
			'r_shipmentStatus' => 'return_' . strtolower($return_status)
		);

		if (!empty($received_on)) {
			$return_details['r_receivedDate'] = date('c', strtotime($received_on));
			if ($claim_status != "CLAIM_WITHDRAWN")
				$return_details['r_completionDate'] = date('c');
		}

		if ($return_status == 'completed')
			$return_details['r_completionDate'] = date('c');


		$details = array(
			// 'status' => 'RETURN_'.strtoupper($return_status),
			'claimStatus' => $claim_status,
			'claimComments' => $claim_comments . ' (' . $current_user["user_nickname"] . ')',
			'claimReimbursmentAmount' => $claim_amount
		);

		// var_dump($return_details);
		// var_dump($details);
		// exit;

		$db->where('returnId', $return_id);
		$db->where('claimStatus', 'pending');
		if ($db->update(TBL_FK_CLAIMS, $details)) {
			$db->where('returnId', $return_id);
			$db->update(TBL_FK_RETURNS, $return_details);

			// Update order details
			$db->where('returnId', $return_id);
			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "LEFT");
			$order = $db->objectBuilder()->getOne(TBL_FK_RETURNS . ' r', 'o.orderItemId, fsn, quantity, COALESCE(r_uid, uid) as uid');
			$db->where('orderItemId', $order->orderItemId);
			$db->update(TBL_FK_ORDERS, array('protectionFund' => $claim_amount));
			$log->write('CLAIM UPDATE: Claim update for Return ID ' . $return_id, 'return-claims');

			// UID DRIVE
			if (!is_null($order->uid) && $return_status == 'completed' && ($product_condition != "undelivered" || $product_condition != "missing" || $product_condition != "wrong")) {
				$uids = json_decode($order->uid, true);
				foreach ($uids as $uid => $con) {
					if ($con == "saleable" || $con == "damaged" || !empty($received_on)) {
						if (!empty($uid)) {
							$stockist->update_inventory_status($uid, 'qc_pending');
							$stockist->add_inventory_log($uid, 'sales_return', 'Flipkart Return :: ' . $return_id);
						}
					}
				}
			}

			return true;
		}

		return false;
	}

	/** REPORTS SECTION BEGINS */
	function generate_report($date_start, $date_end, $type = 'order', $output = false, $data = NULL)
	{
		global $db;

		// $type - Orders/Returns/Payments/All

		if ($type == "all") {
			$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "LEFT");
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id", $this->account->account_id);
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
			$db->where('o.orderDate', array(date('Y-m-d H:i:s', strtotime($date_start)), date('Y-m-d H:i:s', strtotime($date_end . ' 23:59:59'))), 'BETWEEN');
			$db->orWhere('r.r_receivedDate', array(date('Y-m-d H:i:s', strtotime($date_start)), date('Y-m-d H:i:s', strtotime($date_end . ' 23:59:59'))), 'BETWEEN');
			$db->where('o.account_id', $this->account->account_id);
			$db->orderBy('o.shippedDate', 'ASC');
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.orderId, o.orderItemId, o.dispatchAfterDate, o.shippedDate, o.deliveryAddress, o.order_type, o.paymentType, o.title, o.fsn, o.sku, COALESCE(pc.sku, pm.sku) as parentSku, pb.brandName, o.quantity, o.sellingPrice, o.shippingCharge, o.totalPrice, o.replacementOrder, o.status, r.returnId, r.r_source, r.r_shipmentStatus, r.r_trackingId, r.r_deliveredDate, r.r_receivedDate, r.r_expectedDate, r.r_completionDate, r.r_reason, r.r_subReason, r.r_comments, c.claimID, c.claimDate, c.claimProductCondition, c.claimStatus, c.claimReimbursmentAmount');
			$data[] = array('Account', 'Marketplace', 'Date', 'Shipped Date', 'OrderID', 'OrderItemID', 'Buyer\'s Name', 'Product Name', 'FSN', 'SKU', 'Parent SKU', 'Brand', 'City', 'State', 'State Code', 'Pin Code', 'Payment Type', 'Order Type', 'QTY', 'Price', 'Shipping', 'Invoice Amount', 'Replacement Order', 'Status', 'Return ID', 'Return Type', 'Return Status', 'Return Tracking Id', 'Return Delivered Date', 'Return Received Date', 'Return Completed Date', 'Return Expected Date', 'Return Reason', 'Return Subreason', 'Return Comments', 'ClaimID', 'Claim Date', 'Claim Type', 'Claim Status', 'Claim Reimbursed');
		}
		if ($type == "orders") {
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id", $this->account->account_id);
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
			$db->where('o.shippedDate', array(date('Y-m-d H:i:s', strtotime($date_start)), date('Y-m-d H:i:s', strtotime($date_end . ' 23:59:59'))), 'BETWEEN');
			$db->where('o.account_id', $this->account->account_id);
			$db->orderBy('o.shippedDate', 'ASC');
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.orderId, o.orderItemId, o.dispatchAfterDate, o.shippedDate, o.deliveryAddress, o.order_type, o.paymentType, o.title, o.fsn, o.sku, COALESCE(pc.sku, pm.sku) as parentSku, pb.brandName, o.quantity, o.sellingPrice, o.shippingCharge, o.totalPrice, o.replacementOrder, o.status, o.costPrice');
			$data[] = array('Account', 'Marketplace', 'Date', 'Shipped Date', 'OrderID', 'OrderItemID', 'Buyer\'s Name', 'Product Name', 'FSN', 'SKU', 'Parent SKU', 'Brand', 'City', 'State', 'State Code', 'Pin Code', 'Payment Type', 'Order Type', 'QTY', 'Price', 'Shipping', 'Invoice Amount', 'Replacement Order', 'Status', 'XP');
		}
		if ($type == "returns") {
			$db->join(TBL_FK_ORDERS . " o", "r.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id", $this->account->account_id);
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
			$db->where('r.r_receivedDate', array(date('Y-m-d H:i:s', strtotime($date_start . ' 00:00:00')), date('Y-m-d H:i:s', strtotime($date_end . ' 23:59:59'))), 'BETWEEN');
			$db->where('o.account_id', $this->account->account_id);
			$db->orderBy('r.r_receivedDate', 'ASC');
			$orders = $db->ObjectBuilder()->get(TBL_FK_RETURNS . ' r', NULL, 'o.orderId, o.orderItemId, o.dispatchAfterDate, o.shippedDate, o.deliveryAddress, o.order_type, o.paymentType, o.title, o.fsn, o.sku, COALESCE(pc.sku, pm.sku) as parentSku, pb.brandName, o.quantity, o.sellingPrice, o.shippingCharge, o.totalPrice, o.replacementOrder, o.status, r.returnId, r.r_source, r.r_shipmentStatus, r.r_trackingId, r.r_deliveredDate, r.r_receivedDate, r.r_expectedDate, r.r_completionDate, r.r_reason, r.r_subReason, r.r_comments');
			$data[] = array('Account', 'Marketplace', 'Date', 'Shipped Date', 'OrderID', 'OrderItemID', 'Buyer\'s Name', 'Product Name', 'FSN', 'SKU', 'Parent SKU', 'Brand', 'City', 'State', 'State Code', 'Pin Code', 'Payment Type', 'Order Type', 'QTY', 'Price', 'Shipping', 'Invoice Amount', 'Replacement Order', 'Status', 'Return ID', 'Return Type', 'Return Status', 'Return Tracking Id', 'Return Delivered Date', 'Return Received Date', 'Return Completed Date', 'Return Expected Date', 'Return Reason', 'Return Subreason', 'Return Comments');
		}
		if ($type == "claims") {
			$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "LEFT");
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id", $this->account->account_id);
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
			$db->where('c.claimDate', array(date('Y-m-d H:i:s', strtotime($date_start)), date('Y-m-d H:i:s', strtotime($date_end . ' 23:59:59'))), 'BETWEEN');
			$db->where('o.account_id', $this->account->account_id);
			$db->orderBy('c.claimDate', 'ASC');
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.orderId, o.orderItemId, o.dispatchAfterDate, o.shippedDate, o.deliveryAddress, o.order_type, o.paymentType, o.title, o.fsn, o.sku, COALESCE(pc.sku, pm.sku) as parentSku, pb.brandName, o.quantity, o.sellingPrice, o.shippingCharge, o.totalPrice, o.replacementOrder, o.status, r.returnId, r.r_source, r.r_shipmentStatus, r.r_trackingId, r.r_deliveredDate, r.r_receivedDate, r.r_expectedDate, r.r_completionDate, r.r_reason, r.r_subReason, r.r_comments, c.claimID, c.claimDate, c.claimProductCondition, c.claimStatus, c.claimReimbursmentAmount');
			$data[] = array('Account', 'Marketplace', 'Date', 'Shipped Date', 'OrderID', 'OrderItemID', 'Buyer\'s Name', 'Product Name', 'FSN', 'SKU', 'Parent SKU', 'Brand', 'City', 'State', 'State Code', 'Pin Code', 'Payment Type', 'Order Type', 'QTY', 'Price', 'Shipping', 'Invoice Amount', 'Replacement Order', 'Status', 'Return ID', 'Return Type', 'Return Status', 'Return Tracking Id', 'Return Delivered Date', 'Return Received Date', 'Return Completed Date', 'Return Expected Date', 'Return Reason', 'Return Subreason', 'Return Comments', 'ClaimID', 'Claim Date', 'Claim Type', 'Claim Status', 'Claim Reimbursed');
		}
		if ($type == "payments") {
			// $db->join(TBL_PRODUCTS_ALIAS ." p", "p.mp_id=o.fsn", "LEFT");
			// $db->joinWhere(TBL_PRODUCTS_ALIAS ." p", "p.account_id", $this->account->account_id);
			// $db->join(TBL_PRODUCTS_MASTER ." pm", "pm.pid=p.pid", "LEFT");
			// $db->join(TBL_PRODUCTS_COMBO ." pc", "pc.cid=p.cid", "LEFT");
			// $db->join(TBL_PRODUCTS_BRAND ." pb", "pb.brandid=pm.brand", "LEFT");
			// $db->where('o.orderDate', array(date('Y-m-d H:i:s', strtotime($date_start)), date('Y-m-d H:i:s', strtotime($date_end .' 23:59:59'))), 'BETWEEN');
			// $db->where('o.account_id', $this->account->account_id);
			// $db->orderBy('o.orderDate', 'ASC');

			$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "LEFT");
			$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id", $this->account->account_id);
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
			$db->where('o.orderDate', array(date('Y-m-d H:i:s', strtotime($date_start)), date('Y-m-d H:i:s', strtotime($date_end . ' 23:59:59'))), 'BETWEEN');
			$db->where('o.account_id', $this->account->account_id);
			$db->orderBy('o.orderDate', 'ASC');
			$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.orderId, o.orderItemId, o.dispatchAfterDate, o.shippedDate, o.deliveryAddress, o.order_type, o.paymentType, o.title, o.fsn, o.sku, COALESCE(pc.sku, pm.sku) as parentSku, pb.brandName, o.quantity, o.sellingPrice, o.shippingCharge, o.totalPrice, o.replacementOrder, o.status, o.settlementStatus, r.returnId, r.r_source, r.r_shipmentStatus, r.r_trackingId, r.r_deliveredDate, r.r_receivedDate, r.r_expectedDate, r.r_completionDate, r.r_reason, r.r_subReason, r.r_comments, c.claimID, c.claimDate, c.claimProductCondition, c.claimStatus, c.claimReimbursmentAmount');
			// $orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS .' o', NULL, 'o.orderId, o.orderItemId, o.dispatchAfterDate, o.shippedDate, o.deliveryAddress, o.order_type, o.paymentType, o.title, o.fsn, o.sku, COALESCE(pc.sku, pm.sku) as parentSku, pb.brandName, o.quantity, o.sellingPrice, o.shippingCharge, o.totalPrice, o.replacementOrder, o.status, o.settlementStatus');
			$data[] = array('Account', 'Marketplace', 'Date', 'Shipped Date', 'OrderID', 'OrderItemID', 'Buyer\'s Name', 'Product Name', 'FSN', 'SKU', 'Parent SKU', 'Brand', 'City', 'State', 'State Code', 'Pin Code', 'Payment Type', 'Order Type', 'QTY', 'Price', 'Shipping', 'Invoice Amount', 'Replacement Order', 'Status', 'Return ID', 'Return Type', 'Return Status', 'Return Tracking Id', 'Return Delivered Date', 'Return Received Date', 'Return Completed Date', 'Return Expected Date', 'Return Reason', 'Return Subreason', 'Return Comments', 'ClaimID', 'Claim Date', 'Claim Type', 'Claim Status', 'Claim Reimbursed', 'Settlement Status', 'Expected Settlement', 'Actual Settled', 'Difference');
			// $data[] = array('Account', 'Marketplace', 'Date', 'Shipped Date', 'OrderID', 'OrderItemID', 'Buyer\'s Name', 'Product Name', 'FSN', 'SKU', 'Parent SKU', 'Brand', 'City', 'State', 'State Code', 'Pin Code', 'Payment Type', 'Order Type', 'QTY', 'Price', 'Shipping', 'Invoice Amount', 'Replacement Order', 'Status', 'Settlement Status', 'Expected Settlement', 'Actual Settled', 'Difference');
		}

		foreach ($orders as $order) {
			$deliveryAddress = json_decode($order->deliveryAddress);
			$details = array();
			$details[] = $this->account->fk_account_name;
			$details[] = 'Flipkart';
			$details[] = date('d-M-Y H:i:s', strtotime($order->dispatchAfterDate));
			$details[] = ($order->shippedDate == "0000-00-00 00:00:00" ? "NA" : date('d-M-Y H:i:s', strtotime($order->shippedDate)));
			$details[] = $order->orderId;
			$details[] = "'" . $order->orderItemId;
			$name = ((isset($deliveryAddress->firstName) && $deliveryAddress->firstName != "") ? $deliveryAddress->firstName : '') . ((isset($deliveryAddress->lastName) && $deliveryAddress->lastName != "") ? ' ' . $deliveryAddress->lastName : '');
			$name = (strpos($name, ',') !== FALSE) ? '"' . $name . '"' : $name;
			$details[] = $name;
			$details[] = str_replace(",", "-", $order->title);
			$details[] = $order->fsn;
			$details[] = $order->sku;
			$details[] = $order->parentSku;
			$details[] = $order->brandName;
			$details[] = (isset($deliveryAddress->city) ? $deliveryAddress->city : "");
			$details[] = (isset($deliveryAddress->stateName) ? $deliveryAddress->stateName : $deliveryAddress->state);
			$details[] = (isset($deliveryAddress->stateCode) ? $deliveryAddress->stateCode : "");
			$details[] = (isset($deliveryAddress->pincode) ? $deliveryAddress->pincode : "");
			$details[] = $order->paymentType;
			$details[] = $order->order_type;
			$details[] = $order->quantity;
			$details[] = $order->sellingPrice;
			$details[] = $order->shippingCharge;
			$details[] = $order->totalPrice;
			$details[] = ($order->replacementOrder ? "Yes" : "No");

			if ($type == "claims" || $type == "returns" || $type == "payments" || $type == "all") {
				$details[] = ($order->r_shipmentStatus == 'return_completed' || $order->r_shipmentStatus == 'return_claimed' || $order->r_shipmentStatus == 'return_received') ? 'RETURNED' : $order->status;
				$details[] = ($order->returnId != NULL ? "'" . (string)$order->returnId : "");
				$details[] = $order->r_source;
				$details[] = $order->r_shipmentStatus;
				$details[] = $order->r_trackingId;
				$details[] = ($order->r_deliveredDate == NULL || $order->r_deliveredDate == "1970-01-01 05:30:00") ? 'NOT DELIVERED' : date('d-M-Y H:i:s', strtotime($order->r_deliveredDate));
				$details[] = ($order->r_receivedDate == NULL || $order->r_receivedDate == "1970-01-01 05:30:00") ? "" : date('d-M-Y H:i:s', strtotime($order->r_receivedDate));
				$details[] = ($order->r_completionDate == NULL || $order->r_completionDate == "1970-01-01 05:30:00") ? "" : date('d-M-Y H:i:s', strtotime($order->r_completionDate));
				if ($order->returnId != NULL)
					$details[] = $order->r_expectedDate == NULL ? 'RETURN NOT EXPECTED' : date('d-M-Y H:i:s', strtotime($order->r_expectedDate));
				else
					$details[] = "";
				$details[] = $order->r_reason;
				$details[] = $order->r_subReason;
				$details[] = $order->r_comments;

				if ($type == "claims" || $type == "payments" || $type == "all") {
					$details[] = $order->claimID;
					$details[] = $order->claimDate;
					$details[] = $order->claimProductCondition;
					$details[] = $order->claimStatus;
					$details[] = $order->claimReimbursmentAmount;
				}

				if ($type == "payments" || $type == "all") {
					$details[] = ($order->settlementStatus ? 'SETTLED' : 'PENDING');
					$expected_settlement = (float)number_format($this->payments->calculate_actual_payout($order->orderItemId), 2, '.', '');
					$actual_settled = (float)number_format($this->payments->calculate_net_settlement($order->orderItemId), 2, '.', '');
					$details[] = $expected_settlement;
					$details[] = $actual_settled;
					$details[] = number_format($actual_settled - $expected_settlement, 2, '.', '');
				}
				// } else if ($type == "payments"){
				// 	$expected_settlement = $this->payments->calculate_actual_payout($order->orderItemId);
				// 	$actual_settled = $this->payments->calculate_net_settlement($order->orderItemId);
				// 	$details[] = $order->status;
				// 	$details[] = $order->settlementStatus;
				// 	$details[] = $expected_settlement;
				// 	$details[] = $actual_settled;
				// 	$details[] = $expected_settlement - $actual_settled;
			} else {
				$details[] = $order->status;
				$details[] = $order->costPrice;
			}
			$data[] = $details;
		}
		// echo '<pre>';
		// var_dump($data);
		// exit;

		if ($output) {
			$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
			// Set document properties
			$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
				->setLastModifiedBy("Ishan Kukadia")
				->setTitle("All Orders")
				->setSubject("All Orders")
				->setDescription("All Orders");

			// Add some data
			$objPHPExcel->setActiveSheetIndex(0);

			$i = 1;
			foreach ($data as $rows) {
				$j = "A";
				foreach ($rows as $row) {
					$objPHPExcel->getActiveSheet()->setCellValue($j . $i, $row);
					$j++;
				}
				$i++;
			}

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="' . $type . '-report-' . $this->account->account_name . '.xlsx"');
			header('Cache-Control: max-age=0');

			// If you're serving to IE 9, then the following may be needed
			header('Cache-Control: max-age=1');

			// If you're serving to IE over SSL, then the following may be needed
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
			header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header('Pragma: public'); // HTTP/1.0
			$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xls');
			$objWriter->save('php://output');
			exit;
		} else {
			return $data;
		}
	}

	/** GLOBAL USAGE SECTION BEGINS */
	function get_our_order_status($orderItemId)
	{
		global $db;

		$db->where('orderItemId', $orderItemId);
		$status = $db->getValue(TBL_FK_ORDERS, 'status');

		return $status;
	}

	function get_corrent_incorrect_skus()
	{
		global $db, $wrong_sku, $correct_sku;

		$db->where('account_id', $this->account->account_id);
		$db->where('corrected_sku', NULL, 'IS NOT');
		$skus = $db->objectBuilder()->get(TBL_PRODUCTS_ALIAS, null, 'sku, corrected_sku');

		$wrong_sku = array_map(function ($values) {
			return $values->sku;
		}, $skus);

		$correct_sku = array_map(function ($values) {
			return $values->corrected_sku;
		}, $skus);
	}

	function get_cost_price_by_fsn($fsn)
	{
		global $db, $stockist;

		$db->where('mp_id', $fsn);
		$db->where('account_id', $this->account->account_id);
		$db->where('marketplace', 'flipkart');
		$parentSkuID = $db->objectBuilder()->getOne(TBL_PRODUCTS_ALIAS, 'alias_id, pid, cid');
		if ($parentSkuID) {
			$cost_price = 0;
			if ($parentSkuID->cid) {
				$db->where('cid', $parentSkuID->cid);
				$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
				$products = json_decode($products['pid']);
				$units = count($products);

				foreach ($products as $pro) {
					$cost_price += $stockist->get_current_acp($pro);
				}
			} else {
				$cost_price = $stockist->get_current_acp($parentSkuID->pid);
			}
		} else {
			$cost_price = "Parent SKU not found";
		}

		return $cost_price;
	}

	function get_skus_pid($json_ids)
	{
		global $db;

		$ids = json_decode($json_ids);
		$sku = array();
		foreach ($ids as $id) {
			$db->where('pid', $id);
			$sku[] = $db->getOne(TBL_PRODUCTS_MASTER, 'pid, sku');
		}

		return json_encode($sku);
	}

	function get_packlist_for_day($type, $dispatchByDate = "")
	{
		global $db;

		$db->join(TBL_FK_ACCOUNTS . ' fa', 'fa.account_id=o.account_id', 'LEFT');
		if (!empty($dispatchByDate) && $type != 'all') {
			$db->where('o.status', 'NEW');
			$db->where('o.dispatchByDate', $dispatchByDate . '%', 'LIKE');
			if (date('D', strtotime($dispatchByDate)) == "Sat")
				$db->where("(o.dispatchByDate LIKE ? OR o.dispatchByDate LIKE ?)", array($dispatchByDate . '%', date('Y-m-d', strtotime($dispatchByDate . ' + 1 day')) . '%',));
			else
				$db->where('o.dispatchByDate', $dispatchByDate . '%', 'LIKE');
			$db->where('o.order_type', $type);
			$db->where('o.hold', 0);
		} else if ($type == "all") {
			$db->where('o.status', 'NEW');
			$db->where("o.dispatchByDate", date('Y-m-d H:i:s', strtotime("today 02:00 PM")), ">=");
		} else {
			// $db->where('status', 'NEW');
			// $db->where('dispatchAfterDate', date('Y-m-d H:i:s', time()), '<=');
			// $db->where('dispatchByDate', '2019-11-06 14:00:00', '=');
			$db->where('o.status', 'PACKING');
			$db->where('o.fk_status', 'CANCELLED', '!=');
			$db->where('o.order_type', $type);
			$db->where('o.hold', 0);
		}

		$db->orderBy('o.account_id', 'ASC');
		$orders = $db->objectBuilder()->get(TBL_FK_ORDERS . ' o', null, 'o.account_id, o.fsn, o.quantity, fa.fk_account_name');
		$return = array();
		$quantity_total = 0;

		foreach ($orders as $order) {
			$parent_skus = get_parent_sku('flipkart', $order->account_id, 'mp_id', $order->fsn);
			if (is_array($parent_skus)) {
				$multi_qty_orders++;
				foreach ($parent_skus as $parent_sku) {
					if (isset($return[$parent_sku])) {
						// SET SKU & ACCOUNT QTY
						if (isset($return[$parent_sku][$order->fk_account_name]))
							$return[$parent_sku][$order->fk_account_name] += (int)$order->quantity;
						else
							$return[$parent_sku][$order->fk_account_name] = (int)$order->quantity;

						// SET SKU TOTAL
						if (isset($return[$parent_sku]["total"]))
							$return[$parent_sku]["total"] += (int)$order->quantity;
						else
							$return[$parent_sku]["total"] = (int)$order->quantity;

						// SET ACCOUNT TOTAL
						if (isset($return["Total Orders"][$order->fk_account_name]))
							$return["Total Orders"][$order->fk_account_name] += (int)$order->quantity;
						else
							$return["Total Orders"][$order->fk_account_name] = (int)$order->quantity;

						// SET MULTI QTY TOTAL
						if ($order->quantity > 1) {
							if (isset($return["Total Multi Qty Orders"][$order->fk_account_name]))
								$return["Total Multi Qty Orders"][$order->fk_account_name] += 1;
							else
								$return["Total Multi Qty Orders"][$order->fk_account_name] = 1;
						}

						$return["Total Orders"]['multi_qty_orders'] = 1;
					} else {
						// SET SKU & ACCOUNT QTY
						if (isset($return[$parent_sku][$order->fk_account_name]))
							$return[$parent_sku][$order->fk_account_name] += (int)$order->quantity;
						else
							$return[$parent_sku][$order->fk_account_name] = (int)$order->quantity;

						// SET SKU TOTAL
						if (isset($return[$parent_sku]["total"]))
							$return[$parent_sku]["total"] += (int)$order->quantity;
						else
							$return[$parent_sku]["total"] = (int)$order->quantity;

						// SET ACCOUNT TOTAL
						if (isset($return["Total Orders"][$order->fk_account_name]))
							$return["Total Orders"][$order->fk_account_name] += (int)$order->quantity;
						else
							$return["Total Orders"][$order->fk_account_name] = (int)$order->quantity;

						// SET MULTI QTY TOTAL
						if ($order->quantity > 1) {
							if (isset($return["Total Multi Qty Orders"][$order->fk_account_name]))
								$return["Total Multi Qty Orders"][$order->fk_account_name] += 1;
							else
								$return["Total Multi Qty Orders"][$order->fk_account_name] = 1;
						}
					}

					if (isset($return["Total Orders"]['combo_orders']))
						$return["Total Orders"]['combo_orders'] += 1;
					else
						$return["Total Orders"]['combo_orders'] = 1;
				}
			} else {
				$parent_sku = $parent_skus;
				if (isset($return[$parent_sku])) {
					// SET SKU & ACCOUNT QTY
					if (isset($return[$parent_sku][$order->fk_account_name]))
						$return[$parent_sku][$order->fk_account_name] += (int)$order->quantity;
					else
						$return[$parent_sku][$order->fk_account_name] = (int)$order->quantity;

					// SET SKU TOTAL
					if (isset($return[$parent_sku]["total"]))
						$return[$parent_sku]["total"] += (int)$order->quantity;
					else
						$return[$parent_sku]["total"] = (int)$order->quantity;

					// SET ACCOUNT TOTAL
					if (isset($return["Total Orders"][$order->fk_account_name]))
						$return["Total Orders"][$order->fk_account_name] += (int)$order->quantity;
					else
						$return["Total Orders"][$order->fk_account_name] = (int)$order->quantity;

					if ($order->quantity > 1) {
						if (isset($return["Total Multi Qty Orders"][$order->fk_account_name]))
							$return["Total Multi Qty Orders"][$order->fk_account_name] += 1;
						else
							$return["Total Multi Qty Orders"][$order->fk_account_name] = 1;
					}
				} else {
					// SET SKU & ACCOUNT QTY
					if (isset($return[$parent_sku][$order->fk_account_name]))
						$return[$parent_sku][$order->fk_account_name] += (int)$order->quantity;
					else
						$return[$parent_sku][$order->fk_account_name] = (int)$order->quantity;

					// SET SKU TOTAL
					if (isset($return[$parent_sku]["total"]))
						$return[$parent_sku]["total"] += (int)$order->quantity;
					else
						$return[$parent_sku]["total"] = (int)$order->quantity;

					// SET ACCOUNT TOTAL
					if (isset($return["Total Orders"][$order->fk_account_name]))
						$return["Total Orders"][$order->fk_account_name] += (int)$order->quantity;
					else
						$return["Total Orders"][$order->fk_account_name] = (int)$order->quantity;

					// SET MULTI QTY TOTAL
					if ($order->quantity > 1) {
						if (isset($return["Total Multi Qty Orders"][$order->fk_account_name]))
							$return["Total Multi Qty Orders"][$order->fk_account_name] += 1;
						else
							$return["Total Multi Qty Orders"][$order->fk_account_name] = 1;
					}
				}
			}
			$quantity_total += $order->quantity;
			$return["Total Orders"]['quantity_total'] = $quantity_total;
		}
		ksort($return);

		return $return;
	}

	function get_packlist_for_fbf_orders($account_id, $dispatchByDate = "")
	{
		global $db;

		if (!empty($dispatchByDate)) {
			$db->where('status', 'NEW');
			if (date('D', strtotime($dispatchByDate)) == "Sat")
				$db->where("(dispatchByDate LIKE ? OR dispatchByDate LIKE ?)", array($dispatchByDate . '%', date('Y-m-d', strtotime($dispatchByDate . ' + 1 day')) . '%',));
			else
				$db->where('dispatchByDate', $dispatchByDate . '%', 'LIKE');
		} else {
			$db->where('status', 'PACKING');
			// $db->where('status', 'NEW');
			// $db->where('dispatchAfterDate', date('Y-m-d H:i:s', time()), '<=');
			// $db->where('dispatchByDate', '2020-01-28 14:00:00', '<=');
		}
		$db->where('hold', 0);;
		$db->where('order_type', 'FBF_LITE');
		$db->where('fk_status', 'CANCELLED', '!=');
		$db->where('account_id', $account_id);
		$db->orderBy('account_id', 'ASC');
		$orders = $db->objectBuilder()->get(TBL_FK_ORDERS);
		// echo $db->getLastQuery();
		$return = array();
		$quantity_total = 0;

		foreach ($orders as $order) {
			if (isset($return[$order->listingId])) {
				$return[$order->listingId]['qty'] += (int)$order->quantity;
				$return[$order->listingId]['sku'] = $order->sku;
			} else {
				$return[$order->listingId]['qty'] = (int)$order->quantity;
				$return[$order->listingId]['sku'] = $order->sku;
			}
		}
		return $return;
	}

	function get_packlist_for_day_v1($products_wise = false)
	{
		global $db, $account;

		$db->where('account_id', $account->account_id);
		$db->where('status', 'PACKING');
		$db->where('hold', 0);
		$db->where('order_type', $type);
		$orders = $db->get(TBL_FK_ORDERS);

		$products = array();
		$total_orders = count($orders);
		$total_watch_orders = 0;
		$total_qty = 0;
		$multi_qty_orders = 0;

		if ($orders) {
			foreach ($orders as $order) {
				$order = (object)$order;
				if ($order->quantity > 1) {
					$multi_qty_orders += 1;
				}
				if (isset($products[$order->fsn])) {
					if (substr($order->fsn, 0, 3) === "WAT" || substr($order->fsn, 0, 3) === "SMW" || substr($order->fsn, 0, 3) === "PSL" || substr($order->fsn, 0, 3) === "VSL" || substr($order->fsn, 0, 3) === "CBK") {
						$products[$order->fsn] += (int)$order->quantity;
						$total_watch_orders += 1;
					} else {
						$products[$order->sku] += (int)$order->quantity;
					}
				} else {
					if (substr($order->fsn, 0, 3) === "WAT" || substr($order->fsn, 0, 3) === "SMW" || substr($order->fsn, 0, 3) === "PSL" || substr($order->fsn, 0, 3) === "VSL" || substr($order->fsn, 0, 3) === "CBK") {
						$products[$order->fsn] = (int)$order->quantity;
						$total_watch_orders += 1;
					} else {
						$products[$order->sku] += (int)$order->quantity;
					}
				}
			}

			foreach ($products as $pkey => $pvalue) {

				$db->where('mp_id', $pkey);
				$db->where('account_id', $account->account_id);
				$db->where('marketplace', 'flipkart');
				if ($p_query = $db->getOne(TBL_PRODUCTS_ALIAS)) {
					$pid = $p_query['pid'];
					$cid = $p_query['cid'];

					if ($cid) {
						$cols = array("pid", "sku");
						$db->where('cid', $cid);
						if ($products_wise) {
							$c_query = $db->getOne(TBL_PRODUCTS_COMBO, $cols);
							$pids = json_decode($c_query['pid']);
							foreach ($pids as $pid) {
								$cols = array("selling_price", "sku");
								$db->where('pid', $pid);
								$query = $db->getOne(TBL_PRODUCTS_MASTER, $cols);

								if (isset($skus[$query['sku']])) {
									$skus[$query['sku']] += $pvalue;
								} else {
									$skus[$query['sku']] = $pvalue;
								}
							}
						} else {
							$query = $db->getOne(TBL_PRODUCTS_COMBO, $cols);
							if (isset($skus[$query['sku']])) {
								$skus[$query['sku']] += $pvalue;
							} else {
								$skus[$query['sku']] = $pvalue;
							}
						}
					} else {
						$cols = array("selling_price", "sku");
						$db->where('pid', $pid);
						$query = $db->getOne(TBL_PRODUCTS_MASTER, $cols);

						if (isset($skus[$query['sku']])) {
							$skus[$query['sku']] += $pvalue;
						} else {
							$skus[$query['sku']] = $pvalue;
						}
					}
				} else {
					$skus[$pkey] = $pvalue;
				}
				$total_qty += $pvalue;
			}

			$return .= 'Orders For: ' . $account->account_name . "\n";
			$return .= "================================\n";
			$return .= 'Total Order: ' . $total_orders . "\n";
			$return .= 'Total Watches Order: ' . $total_watch_orders . "\n";
			$return .= 'Total Watches Qty: ' . $total_qty . "\n";
			$return .= "Multi Qty Orders: " . $multi_qty_orders . "\n";
			$return .= "================================\n";

			ksort($skus);

			foreach ($skus as $sku => $qty) {
				$return .= $sku . ' - ' . $qty . "\n";
			}

			return $return;
		} else {
			return 'No order to pack yet!';
		}
	}

	function arrange_orders($shipmentIds, $columns = "*")
	{
		global $db, $correct_sku, $wrong_sku;

		$this->get_corrent_incorrect_skus();

		if (!empty($shipmentIds)) {
			$data = array();
			foreach ($shipmentIds as $shipmentId) {
				$db->where("shipmentId", $shipmentId);
				$data[] = $db->getOne(TBL_FK_ORDERS, $columns);
			}
		}

		$sort = array();
		foreach ($data as $k => $v) {
			$deliveryAddress = json_decode($v['deliveryAddress']);
			$sort['state'][$k] = $deliveryAddress->stateCode;
			$vendor = json_decode($v['pickupDetails']);
			$sort['vendor'][$k] = $vendor->vendorName;
			$sort['qty'][$k] = $v['quantity'];
			if (in_array($v['sku'], $wrong_sku)) {
				$sort['sku'][$k] = $correct_sku[array_search($v['sku'], $wrong_sku)];
			} else {
				$sort['sku'][$k] = $v['sku'];
			}
		}

		# sort by event_type desc and then title asc
		array_multisort($sort['vendor'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $sort['qty'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $sort['sku'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $sort['state'], SORT_ASC | SORT_STRING | SORT_FLAG_CASE, $data);

		return $data;
	}

	function arrange_order_details_v1($order_shipments)
	{
		$count = count($order_shipments->shipments);

		$orderItems = array();
		for ($i = 0; $i < $count; $i++) {
			$orderItems[$i] = $order_shipments->shipments[$i]->orderItems[0]; //
			$orderItems[$i]->hold = isset($order_shipments->shipments[$i]->hold) ? $order_shipments->shipments[$i]->hold : false;
			$orderItems[$i]->shipmentId = $order_shipments->shipments[$i]->shipmentId;
			$orderItems[$i]->locationId = $order_shipments->shipments[$i]->locationId;
			$orderItems[$i]->sla = isset($order_shipments->shipments[$i]->sla) ? $order_shipments->shipments[$i]->sla : 1;
			$orderItems[$i]->dispatchByDate = $order_shipments->shipments[$i]->dispatchByDate;
			$orderItems[$i]->dispatchAfterDate = $order_shipments->shipments[$i]->dispatchAfterDate;
			$orderItems[$i]->updatedAt = $order_shipments->shipments[$i]->updatedAt;
			$orderItems[$i]->mps = $order_shipments->shipments[$i]->mps;
			$orderItems[$i]->stateDocuments = $order_shipments->shipments[$i]->forms; //
			$orderItems[$i]->shipmentType = $order_shipments->shipments[$i]->shipmentType;
			$orderItems[$i]->subShipments = $order_shipments->shipments[$i]->subShipments;
		}

		if ($count === 1) {
			$orderItems = $orderItems[0];
		}

		return $orderItems;
	}

	function arrange_order_details($order_shipments)
	{
		if (isset($_GET['test']) && $_GET['test'] == 1) {
			debug_print_backtrace();
			var_dump($order_shipments);
		}
		$shipments = $order_shipments->shipments;

		$i = 0;
		$orderItems = array();
		foreach ($shipments as $shipment) {
			$order_items = count($shipment->orderItems);

			$orderItems[$i] = new stdClass();
			foreach ($shipment->orderItems as $order_item) {
				$orderItems[$i] = $order_item;
				$orderItems[$i]->hold = isset($shipment->hold) ? $shipment->hold : false;
				$orderItems[$i]->shipmentId = $shipment->shipmentId;
				$orderItems[$i]->locationId = $shipment->locationId;
				$orderItems[$i]->sla = isset($shipment->sla) ? $shipment->sla : 1;
				$orderItems[$i]->dispatchByDate = $shipment->dispatchByDate;
				$orderItems[$i]->dispatchAfterDate = $shipment->dispatchAfterDate;
				$orderItems[$i]->updatedAt = $shipment->updatedAt;
				$orderItems[$i]->mps = $shipment->mps;
				$orderItems[$i]->stateDocuments = $shipment->forms; //
				$orderItems[$i]->shipmentType = $shipment->shipmentType;
				$orderItems[$i]->subShipments = $shipment->subShipments;

				if ($order_items > 1)
					$i++;
			}
			$i++;
		}
		return $orderItems;
	}

	function convert_orders_v2_to_v3($orders)
	{
		foreach ($orders as $order) {
			$order_item_ids[] = $order->orderItemId;
		}

		$order_item = $this->get_order_details(implode(',', $order_item_ids), true);

		return $order_item[0];
	}

	function csv_to_array($filename = '', $sorting = FALSE, $delimiter = ',')
	{

		ini_set('auto_detect_line_endings', TRUE);
		if (!file_exists($filename) || !is_readable($filename))
			return FALSE;

		$header = NULL;
		$data = array();
		if (($handle = fopen($filename, 'r')) !== FALSE) {
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
				if (!$header) {
					$header = $row;
				} else {
					if (count($header) > count($row)) {
						$difference = count($header) - count($row);
						for ($i = 1; $i <= $difference; $i++) {
							$row[count($row) + 1] = $delimiter;
						}
					}
					$data[] = array_combine($header, $row);
				}
			}
			fclose($handle);
		}

		if ($sorting) {
			$sort = array();
			foreach ($data as $k => $v) {
				$sort['qty'][$k] = $v['Quantity'];
				if (in_array($v['SKU'], $wrong_sku)) {
					$sort['sku'][$k] = $correct_sku[array_search($v['SKU'], $wrong_sku)];
				} else {
					$sort['sku'][$k] = $v['SKU'];
				}
			}
			# sort by event_type desc and then title asc
			array_multisort($sort['qty'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $sort['sku'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $data);
		}

		return $data;
	}

	function delete_old_files($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != ".."/* && $object != 'single' && $object != 'final'*/) {
					if (filetype($dir . "/" . $object) == "dir") $this->delete_old_files($dir . "/" . $object);
					if (filemtime($dir . "/" . $object) <= time() - 60 * 60 * 24 * 7) @unlink($dir . "/" . $object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	function check_low_pricing_alert($fsn, $sellingPrice, $fulfilled = false, $orderItemId, $lid)
	{
		global $current_account, $delivery_charges, $sms;

		$current_account = $this->account;
		$sp_details = get_selling_price($fsn, $sellingPrice);
		if ($fulfilled == true && $sp_details['price'] < 500) {
			$sp_details['price'] += 40; // (Rs. 40 FBF above 500 delivery charges)
		} else {
			// get_shipping_values()
			$sp_details['price'] += $delivery_charges;
		}

		// $order_details = $this->get_order_details($shipmentId);

		if (isset($_GET['test']) && $_GET['test'] == 1) {
			var_dump($sp_details);
			var_dump($sellingPrice);
			// var_dump($order_details);
		}

		$offer_listings = array("LSTWATEVYK5CEJEZRYROCKCPY", "LSTWATF3K899WR9HWZU8F3WTG", "LSTWATFFHBZTMKPG4GNGFEXBO", "LSTWATEPJFGHZZ6ZHZU4R6LID", "LSTWATEHW85FMRRDEYNMJTUJH", "LSTWATEPK3M7FGNYQGPFYKWWB", "LSTWATEHS7GVZCYXCJVTGYMT2", "LSTWATFYUD9EQHVJ5Z5ZYORKD", "LSTWATEYMKNYYH6W6WEDCNY1X", "LSTWATEVZFJHFGDB5CSFEHKDP", "LSTWATESM3KC2MGHEGYER36BB", "LSTWATEPKWBZYCNSPHXESEVGH", "LSTWATEW3QGRCNSHYPSDA0HHI", "LSTWATEXPAZVYBH4CWRPSWBXN", "LSTWATEHS7HFYMNNY4QHPVWAQ", "LSTWATES63YUHVHJH8ZHKD6SW", "LSTWATEHW85EP3BAPBNEXT32F", "LSTWATEAZYV7HPQASHGHNWGN5", "LSTWATF5QE2ZYQ2BC5ZGMYGZ8", "LSTWATF3BGYKNYWRKWAQRFN8I", "LSTWATEKM9QPGH3JX5YV41CYG", "LSTWATEH9RK9USHTHCXYBYFGQ", "LSTWATENHF7PF3JTTHMDRTL46", "LSTWATEXTXWCUUZQ6MSTKNL8Z", "LSTWATEGCVE3N8FCBSVOQTAQY", "LSTWATF8J54YGTFV2FGJUEABG", "LSTWATF4BSTYMSHNPGVL7KVVL", "LSTWATF3DUU3HYGYHBPB2S6G7", "LSTWATFYTTR5K3HHPGFEUPE4P", "LSTWATEAP88XEHYA8C8RPPT2A", "LSTWATFFHBZY7AZYWEB3Y1W4O", "LSTWATF33NGZRGSZZCZDQTTZQ", "LSTWATEVAHYC9VWVQGYHFAM1H", "LSTWATEAVZQBJVFTNAG39RNNR", "LSTWATEVYM4XKGZHVBPPSNACC", "LSTWATEGSEUXRTFHRSSPJ6VEJ", "LSTWATES62ZGT8CWJTHW3FXUV", "LSTWATEZXZG3RCCGFEYHS4N5O", "LSTWATF3BGY9UGKH4UXQLG8CK", "LSTWATF3H9Z2NQVZFG2DOTCWC", "LSTWATEW3Q2JKWC29HWYNKJUW", "LSTWATEHS7WTKS5UHQCYSHU2Z", "LSTWATEP3CAMVW2KESTKBAQWV", "LSTWATF4VRGJWQ9TCY9NVNXLI", "LSTWATEAWG5CAWSGNMHGJEQNC", "LSTWATEYCH34VFAQUWBZTJTV1", "LSTWATF29UYHRVGZW24ZYJTSH", "LSTWATEHUU4DGHTPZZZPSHR6P", "LSTWATEY6QAP9GNHUXAAAQGHW", "LSTWATFFHBZQHA46ZB7LQGAB9", "LSTWATF3NJ4GPAZVJFRWMCBG2", "LSTWATEY3KFVQ2CRFDJAMPD4N", "LSTWATF6YA5JXG4MJ9HF5ZXWX", "LSTWATEBGHFFYQZS35FADPWTT", "LSTWATEQRBEVTBXGCFGGGWQAT", "LSTWATEZGUARQRSJKDYM1UARB", "LSTWATERQVBVQUCXC9B8XCP0M", "LSTWATEQAEUZKZTSKNHGBYPAQ", "LSTWATEY6QAS8ZYYTVSBRY1Z8", "LSTWATECV4RMMQ7TYY7WEEVO7", "LSTWATFFHBZEZZXPZRYO4Z3WJ", "LSTWATEHS7WGAJ6HGYSU6OKEE", "LSTWATEDSZJHZG4MUBN4II3XU", "LSTWATF2FDU5N3BRRDZQ61Y7Y", "LSTWATEBBNFYWYFGQCJX4INZA", "LSTWATF7H3PM5HYTJ8JUBTJRQ", "LSTWATF4SG9XJ3W6MENF49MSB", "LSTWATEX2WEMGBYNWEDLZ1FS7", "LSTWATEP3CAAGYGYRCWTYTZXB", "LSTWATF84PZWERHDBJ9LETZP3", "LSTWATF4HQWDKVXRATZTSWTLH", "LSTWATEHW859MVCKYERVK3BYN", "LSTWATECV4RR567MSUCNZSGTJ", "LSTWATENFGKGGTNYMXSESA9JE", "LSTWATF5KGUBGWHGBZBGA6UWN", "LSTWATFFHBZBJ3KYTH9NSY6CX", "LSTWATEJ5Z357ZFVBZDIBNRXE", "LSTWATF3HFBBP7ATBP2JXYAUE", "LSTWATF7H3A594X8G5FA0RUCX", "LSTWATETFCNF73EWTPWMZDGDI", "LSTWATEHUU3MHVPGQNYBSUXSN", "LSTWATEY6HK2GQ5DXN2ZJBKZT", "LSTWATECV4R8MWUKTWBOM7N8I", "LSTWATF48NRZTQCMGHGJKICOY", "LSTWATEY6QAMCPQWZZTBS2KFU", "LSTWATEHS7WKCGZCWSKXWA9YB", "LSTWATFF82GHVRTGBMAHLBTVM", "LSTWATEJ5Z3WCDBFAH7OTWNQN", "LSTWATF4SG9ZHNSV6EBPJC1VL", "LSTWATEDJG6SMZT48RT2V202R", "LSTWATEGCVE2PHZZERZKN7JMH", "LSTWATF62FHNKUTBTVHBB7T0X", "LSTWATENFGK6ZDHHJRBILM4BG", "LSTWATEAZYVVVGSAKADLNZSXP", "LSTWATEH97ENGZB5HFQJISJHJ", "LSTWATF49YWGTWWH4WSIZRPID", "LSTWATF3MNUFAQZZBVHEYYWQY", "LSTWATEVYK5CEJEZRYRGU1CYG", "LSTWATF3K899WR9HWZUTSNXAW", "LSTWATFFHBZTMKPG4GNARDDHZ", "LSTWATEPJFGHZZ6ZHZUEWJQ2R", "LSTWATEHW85FMRRDEYNMECTOQ", "LSTWATEPK3M7FGNYQGPE0EV6Q", "LSTWATEHS7GVZCYXCJVZWLAPS", "LSTWATFYUD9EQHVJ5Z5SCPUCI", "LSTWATEYMKNYYH6W6WEV7FARY", "LSTWATECV4R5R7SZB5TDYJ349", "LSTWATE54HWJGSZGRTMJTJXCN", "LSTWATEVZFJHFGDB5CSEQNMMK", "LSTWATESM3KC2MGHEGYIOW2Q5", "LSTWATEPKWBZYCNSPHXQDNIHO", "LSTWATEW3QGRCNSHYPSU5C3PZ", "LSTWATEXPAZVYBH4CWRJSM5LH", "LSTWATE54HWHVBZYZ6QFAV8AK", "LSTWATEHS7HFYMNNY4QLWCRHC", "LSTWATES63YUHVHJH8Z4JUN9H", "LSTWATEHW85EP3BAPBNKYPINJ", "LSTWATEAZYV7HPQASHGOGOM9O", "LSTWATF5QE2ZYQ2BC5ZT3HQBA", "LSTWATF3BGYKNYWRKWACCEHH1", "LSTWATEKM9QPGH3JX5YX0NCGV", "LSTWATEH9RK9USHTHCXEBTHSG", "LSTWATENHF7PF3JTTHM0KQOQO", "LSTWATEGCVE3N8FCBSV1HRHK5", "LSTWATEXTXWCUUZQ6MS1O2AKM", "LSTWATF8J54YGTFV2FG62IWFF", "LSTWATF4BSTYMSHNPGVJGN4UQ", "LSTWATFYTTR5K3HHPGFPCGSCW", "LSTWATF3DUU3HYGYHBPSDXFI5", "LSTWATFFHBZY7AZYWEB6KPQAO", "LSTWATEAP88XEHYA8C8AA9UMH", "LSTWATEGCVECFAMXYVYPTB6J1", "LSTWATECWEYAKCCVUJP9U0GQ9", "LSTWATF33NGZRGSZZCZ7BPGGL", "LSTWATEVAHYC9VWVQGYMYVXOL", "LSTWATEVYM4XKGZHVBPMBIWT6", "LSTWATES62ZGT8CWJTHHXNSCW", "LSTWATEZXZG3RCCGFEYSV0YIH", "LSTWATF3BGY9UGKH4UX79EZ58", "LSTWATF3H9Z2NQVZFG2TF7E4V", "LSTWATEHS7WTKS5UHQCBPXLV0", "LSTWATEW3Q2JKWC29HWYJHT1N", "LSTWATEP3CAMVW2KESTTAPBWF", "LSTWATF4VRGJWQ9TCY990HUWT", "LSTWATEAWG5CAWSGNMHTNAYDK", "LSTWATEYCH34VFAQUWBJQRLIM", "LSTWATEHUU4DGHTPZZZXFUGIF", "LSTWATEY6QAP9GNHUXABRNS52", "LSTWATFFHBZQHA46ZB721XPZV", "LSTWATF3NJ4GPAZVJFRTKHJVG", "LSTWATF6YA5JXG4MJ9HTKSKKT", "LSTWATEBGHFFYQZS35FNBRFCW", "LSTWATEZGUARQRSJKDY3I3CV4", "LSTWATERQVBVQUCXC9BGITZCP", "LSTWATEQAEUZKZTSKNHJPGETQ", "LSTWATESZ9ZTYZEZUMDNSVJ4C", "LSTWATEY6QAS8ZYYTVSAVP9JL", "LSTWATECV4RMMQ7TYY732WODY", "LSTWATFYHSN94JBGDYT7IOKMY", "LSTWATFFHBZEZZXPZRYGC8I2C", "LSTWATESWVXPXFHCWUZFMPYKG", "LSTWATEQQYXQJHZFJKWZNOAIF", "LSTWATEHS7WGAJ6HGYSRA0BNL", "LSTWATEDSZJHZG4MUBN3HZMWL", "LSTWATF2FDU5N3BRRDZPZQXFE", "LSTWATF7H3PM5HYTJ8JSFJ3IJ", "LSTWATF4SG9XJ3W6MENBERKN3", "LSTWATEX2WEMGBYNWEDSWDGII", "LSTWATF84PZWERHDBJ9EALOOW", "LSTWATF4HQWDKVXRATZBPSAVZ", "LSTWATEHW859MVCKYER1JTASU", "LSTWATECV4RR567MSUCHPFWB6", "LSTWATENFGKGGTNYMXSLOXVUX", "LSTWATF5KGUBGWHGBZBEOOHWQ", "LSTWATFFHBZBJ3KYTH9W845AL", "LSTWATF3HFBBP7ATBP2WIUR6T", "LSTWATEJ5Z357ZFVBZD2UCJTH", "LSTWATF7H3A594X8G5FBF9KUA", "LSTWATETFCNF73EWTPWEGQIKK", "LSTWATEHUU3MHVPGQNYVX319S", "LSTWATFFHBZAA2QMJ72BIQYRG", "LSTWATES62QT4DEUJZM7OAQ7X", "LSTWATEY6HK2GQ5DXN2L85OB2", "LSTWATECV4R8MWUKTWBGAR5YV", "LSTWATF48NRZTQCMGHGWK6O0W", "LSTWATEHS7WKCGZCWSKGDWMUU", "LSTWATFF82GHVRTGBMA10QWNT", "LSTWATEJ5Z3WCDBFAH712HDOC", "LSTWATEGCVE2PHZZERZQIIDZV", "LSTWATEDJG6SMZT48RTJFT7IH", "LSTWATF62FHNKUTBTVHV93ZXA", "LSTWATENFGK6ZDHHJRBRJNQ4U", "LSTWATEP3CASFN8J5FFAVB4NA", "LSTWATEH97ENGZB5HFQP71NJY", "LSTWATF49YWGTWWH4WSK0JC4F", "LSTWATF3MNUFAQZZBVHMY2SY6");

		if ($sellingPrice < $sp_details['price'] && !in_array($lid, $offer_listings)) {
			$msg = "LPA: Price for the " . $fsn . " of " . $current_account->account_name . " with Item ID " . $orderItemId . " is below settlement. Kindly check for price manually.";
			// $ssms = $sms->send_sms($msg, array(get_option('sms_notification_number')), 'IMSDAS');
			$url = BASE_URL . "/flipkart/set_min_price.php?account_id=" . $current_account->account_id . "&fsn=" . $fsn;
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($curl);
			curl_close($curl);
			// if ($_GET['test'] == 1){
			// 	var_dump($ssms);
			// 	var_dump($result);
			// 	var_dump($curl);
			// }			
		}
		return;
	}
}
