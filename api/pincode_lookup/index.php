<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
include_once(dirname(dirname(__FILE__)) . '/header-option.php');
include_once(ROOT_PATH . '/shopify/functions.php');

global $log, $db, $accounts, $account, $logisticPartner;
$accounts = $accounts['shopify'];

$active_lp = NULL;
$accountId = isset($_REQUEST['accountId']) ? $_REQUEST['accountId'] : NULL;
if ($accountId) {
	$account = get_current_account($accountId);
	$active_lp = strtolower($account->lp_provider_name);
}

if ($active_lp && $account) {
	include_once(ROOT_PATH . '/includes/class-' . $active_lp . '-client.php');
} else {
	echo '{"status":false,"type":"error","message":"Logistic Partner not configured."}';
}

$pincode = $_REQUEST['zipcode'];
$reverse = $_REQUEST['reverse'];
$qc = isset($_REQUEST['qc']) ? filter_var($_REQUEST['qc'], FILTER_VALIDATE_BOOLEAN) : 0;
$order_id = ($qc ? $_REQUEST['orderId'] : null);
if ($pincode) {
	if (!$reverse) {
		$response = $logisticPartner->check_courier_serviceability('394105', $pincode, '0.2', 1, 'air', $order_id, 0, 1);
		$log->write(json_encode($response), '/api/pincode_lookup');
		if ($response) {
			$db->where('pincode', $pincode);
			$location = $db->getOne(TBL_PINCODE_SERVICEABILITY);

			$false_vector = '<span style="padding-right: 5px;"><svg height="15px" viewBox="0 0 512 512" width="15px" xmlns="http://www.w3.org/2000/svg"><path d="m256 0c-141.164062 0-256 114.835938-256 256s114.835938 256 256 256 256-114.835938 256-256-114.835938-256-256-256zm0 0" fill="#f44336"/><path d="m350.273438 320.105469c8.339843 8.34375 8.339843 21.824219 0 30.167969-4.160157 4.160156-9.621094 6.25-15.085938 6.25-5.460938 0-10.921875-2.089844-15.082031-6.25l-64.105469-64.109376-64.105469 64.109376c-4.160156 4.160156-9.621093 6.25-15.082031 6.25-5.464844 0-10.925781-2.089844-15.085938-6.25-8.339843-8.34375-8.339843-21.824219 0-30.167969l64.109376-64.105469-64.109376-64.105469c-8.339843-8.34375-8.339843-21.824219 0-30.167969 8.34375-8.339843 21.824219-8.339843 30.167969 0l64.105469 64.109376 64.105469-64.109376c8.34375-8.339843 21.824219-8.339843 30.167969 0 8.339843 8.34375 8.339843 21.824219 0 30.167969l-64.109376 64.105469zm0 0" fill="#fafafa"/></svg></span>';
			$true_vector = '<span style="padding-right: 5px;"><svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"viewBox="0 0 367.805 367.805" style="enable-background:new 0 0 367.805 367.805;" xml:space="preserve" width="15px" height="15px"><g><path style="fill:#3BB54A;" d="M183.903,0.001c101.566,0,183.902,82.336,183.902,183.902s-82.336,183.902-183.902,183.902 S0.001,285.469,0.001,183.903l0,0C-0.288,82.625,81.579,0.29,182.856,0.001C183.205,0,183.554,0,183.903,0.001z"/><polygon style="fill:#D4E1F4;" points="285.78,133.225 155.168,263.837 82.025,191.217 111.805,161.96 155.168,204.801 256.001,103.968 \t"/></g></svg></span>';

			if (isset($response['error']['status']) && $response['error']['status'] == 404) { // No Service
				$reply['status'] = true;
				$response_html = '<div class="shippin_details">
									<div><span style="display: block;">Shipping To: <strong>' . ucwords(strtolower($location['city'])) . ', India</strong></span></div>
									<div style="">' . $false_vector . ' NO COD&nbsp;|&nbsp;
										<span style="padding-right: 5px;">
											<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 367.805 367.805" style="enable-background:new 0 0 367.805 367.805;" xml:space="preserve" width="15px" height="15px">
												<g>
													<path style="fill:#3BB54A;" d="M183.903,0.001c101.566,0,183.902,82.336,183.902,183.902s-82.336,183.902-183.902,183.902 S0.001,285.469,0.001,183.903l0,0C-0.288,82.625,81.579,0.29,182.856,0.001C183.205,0,183.554,0,183.903,0.001z"></path>
													<polygon style="fill:#D4E1F4;" points="285.78,133.225 155.168,263.837 82.025,191.217 111.805,161.96 155.168,204.801 256.001,103.968     "></polygon>
												</g>
											</svg>
										</span> Delivery to <span id="pinAnchor" style="cursor:pointer;"><strong><u>' . $pincode . '</u></strong></span> by <strong>' . date('M d, Y', strtotime('today 1 PM +9 days')) . '</strong></div>
									<div style=""><span style="display: block;"> If you order in next: <span id="counter"></span></span></div>
								</div>';
				$reply['message'] = $response_html;
				$reply['zipdata'] = array(
					'pincode' => $pincode,
					'city' => ucwords($location['city']),
					'state' => ucwords($location['state']),
					'edd' => 9,
					'region' => ucwords($location['state']),
					'cut_off_time' => date('c', strtotime('today 1 PM +9 days')),
					'delivery_by' => "<div class=\"custom_edd\"><span style=\"font-size: 16.25px;top: -5px;position: relative;\">Delivery by: <strong>" . date('l, d M', strtotime('today 1 PM +9 days')) . "</strong></span></div>",
					'shown_edd' => date('M d, Y', strtotime('today 1 PM +9 days')),
					'edd_source' => "ZipCode_Lookup",
					'html_default' => $response_html
				);
			} else if (is_array($response)) {
				$lp_data = $response['courier'];
				$is_cod = $lp_data['cod'];
				$cod_text = $is_cod ? 'COD Available' : 'No COD';
				$response_html = '<div class="shippin_details">
									<div><span style="display: block;">Shipping To: <strong>' . ucwords(strtolower($location['city'])) . ', India</strong></span></div>
									<div style="">' . ($is_cod ? $true_vector : $false_vector) . ' ' . $cod_text . '&nbsp;|&nbsp;
										<span style="padding-right: 5px;">
											<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 367.805 367.805" style="enable-background:new 0 0 367.805 367.805;" xml:space="preserve" width="15px" height="15px">
												<g>
													<path style="fill:#3BB54A;" d="M183.903,0.001c101.566,0,183.902,82.336,183.902,183.902s-82.336,183.902-183.902,183.902 S0.001,285.469,0.001,183.903l0,0C-0.288,82.625,81.579,0.29,182.856,0.001C183.205,0,183.554,0,183.903,0.001z"></path>
													<polygon style="fill:#D4E1F4;" points="285.78,133.225 155.168,263.837 82.025,191.217 111.805,161.96 155.168,204.801 256.001,103.968     "></polygon>
												</g>
											</svg>
										</span> Delivery to <span id="pinAnchor" style="cursor:pointer;"><strong><u>' . $pincode . '</u></strong></span> by <strong>' . $lp_data['etd'] . '</strong></div>
									<div style=""><span style="display: block;"> If you order in next: <span id="counter"></span></span></div>
								</div>';

				$reply['status'] = true;
				$reply['message'] = $response_html;
				$reply['zipdata'] = array(
					'pincode' => $pincode,
					'city' => ucwords($location['city']),
					'state' => ucwords($location['state']),
					'edd' => 4,
					'region' => ucwords($location['state']),
					'cut_off_time' => date('H', time()) < date('H', strtotime($lp_data['cutoff_time'])) ? date('c', strtotime('today ' . $lp_data['cutoff_time'])) : date('c', strtotime('tomorrow ' . $lp_data['cutoff_time'])),
					'delivery_by' => "<div class=\"custom_edd\"><span style=\"font-size: 16.25px;top: -5px;position: relative;\">Delivery by: <strong>" . date('l, d M', strtotime($lp_data['etd'])) . "</strong></span></div>",
					'shown_edd' => $lp_data['etd'],
					'edd_source' => "ZipCode_Lookup",
					'html_default' => $response_html
				);
			}

			echo json_encode($reply);
		}
	} else {
		// echo '{"status":true,"type":"success","message":"Pincode Servicable","zipdata":{"pincode":"683574","city":"ANGAMALLY","state":"KERALA","courier":null,"rate":null,"edd":null,"edd_source":"ZipCode_Lookup"}}';
		// exit;
		$response = $logisticPartner->check_courier_serviceability($pincode, '394105', '0.2', 0, '', $order_id, 1, 1);
		$log->write(json_encode($response), '/api/pincode_lookup_returns');
		if (isset($response['courier_id'])) {
			$db->where('pincode', $pincode);
			$location = $db->getOne(TBL_PINCODE_SERVICEABILITY);
			$return = array(
				"status" => true,
				"type" => "success",
				"message" => "Pincode Servicable",
				"zipdata" => array(
					'pincode' => $pincode,
					'city' => ucwords($location['city']),
					'state' => ucwords($location['state']),
					'courier' => $response['courier']['courier_name'],
					'rate' => $response['courier']['rate'],
					'edd' => $response['courier']['estimated_delivery_days'],
					'edd_source' => "ZipCode_Lookup"
				)
			);
			// header('Content-Type: text/html; charset=UTF-8');
			echo json_encode($return);
		} else if (isset($response['error'])) {
			echo '{"status":true,"type":"error","message":' . $response['error']['message'] . '}';
		} else {
			echo '{"status":true,"type":"error","message":"No courier partner available for ' . $pincode . ' to 394105."}';
		}
	}
} else {
	echo '{"status":false,"type":"array","message":{"zipcode":["The zipcode field is required."]}}';
}
