<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
include_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
include_once(dirname(dirname(__FILE__)) . '/header-option.php');
ignore_user_abort(true); // optional
global $log, $db;

$db->where('fulfillmentStatus', 'shipped');
$db->where('logistic', 'Smartr Logistics');
$db->where('pickedUpDate', NULL, 'IS');
$orders = $db->objectBuilder()->get(TBL_FULFILLMENT, NULL, 'fulfillmentId, trackingId');
if ($orders) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://smartexpress-prod-api-appservice.azurewebsites.net/SKMobilityWS.asmx/GenerateToken',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => 'ClientUID=A2699DC9-4AA4-4529-BE5C-747166B542D9&ClientKey=200316E7-D8BD-450B-8AD6-2B18FE1EC5C7&AppID=EX&DeviceID=12312367778',
		CURLOPT_HTTPHEADER => array(
			'sec-ch-ua: "Not/A)Brand";v="99", "Google Chrome";v="115", "Chromium";v="115"',
			'DNT: 1',
			'sec-ch-ua-mobile: ?0',
			'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
			'Content-Type: application/x-www-form-urlencoded',
			'Accept: */*',
			'Referer: http://smartr.in/',
			'sec-ch-ua-platform: "macOS"'
		),
	));

	$response = curl_exec($curl);
	curl_close($curl);

	$response = simplexml_load_string($response);
	$token = json_decode(str_replace(array('RESULT_START:', ':RESULT_END'), "", $response))->Table[0]->TokenNumber;

	foreach ($orders as $order) {
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://smartexpress-prod-api-appservice.azurewebsites.net/SKMobilityWS.asmx/GetAWBMilestonesInfo',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => 'TokenNumber=' . $token . '&awbNumber=' . $order->trackingId . '&sessionId=6eb9a550-3dae-433a-ace5-650636282294&loginName=trackonweb&awbPrefix=XSE',
			CURLOPT_HTTPHEADER => array(
				'sec-ch-ua: "Not/A)Brand";v="99", "Google Chrome";v="115", "Chromium";v="115"',
				'DNT: 1',
				'sec-ch-ua-mobile: ?0',
				'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
				'Content-Type: application/x-www-form-urlencoded',
				'Accept: */*',
				'Referer: http://smartr.in/',
				'sec-ch-ua-platform: "macOS"'
			),
		));

		$response = curl_exec($curl);
		curl_close($curl);

		$response = simplexml_load_string($response);
		$tracking_events = json_decode(str_replace(array('RESPONSE_START:SUCCESS:RESPONSE_ENDRESULT_START:', ':RESULT_END'), "", $response))->Table;
		foreach ($tracking_events as $event) {
			if ($event->Milestone == "PICKED UP") {
				$pickedUpDate = date('Y-m-d H:i:s', strtotime($event->UTCInsertedTime . ' +330 minutes')); // UTC TO IST
				$data = array('pickedUpDate' => $pickedUpDate);
				$db->where('fulfillmentId', $order->fulfillmentId);
				if ($db->update(TBL_FULFILLMENT, $data)) {
					echo 'Successfully updated pickup date to ' . $pickedUpDate . ' for tracking ID ' . $order->trackingId . '<br />';
				}
			}
		}
	}
} else {
	echo 'No orders to update';
}
