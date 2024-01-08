<?php

// ob_start();
echo '<pre>';
// exit('Manual Block. No Process for now...');

// var_dump((date('D', time()) == "Sat" && date('H', time()) >  "14") || (date('D', time()) == "Sun" && date('H', time()) <  "14"));

if (((date('D', time()) == "Sat" && date('H', time()) >  "14") || (date('D', time()) == "Sun" && date('H', time()) <  "14")) && ($_GET['is_forced'] != "1"))
	exit('Its weekend!!! No more orders to process');

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

include_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
include(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
include(ROOT_PATH . '/includes/connectors/flipkart/flipkart-dashboard.php');
ini_set('max_execution_time', 1800);

$debug = false;
if (isset($_GET['test']) && $_GET['test'] == 1)
	$debug = true;

if (!isset($_GET['account_id']) || (int)$_GET['account_id'] == 0)
	exit('No account requested');

$account_id = (int)$_GET['account_id'];
$account_key = "";
foreach ($accounts['flipkart'] as $a_key => $account) {
	if ($account_id == $account->account_id)
		$account_key = $a_key;
}

// Declare global scopes
$GLOBALS['current_account'] = $accounts['flipkart'][$account_key]; // Shivansh - 2, Sylvi - 1, Shreehan - 0
$GLOBALS['logfile'] = 'fbf-' . $current_account->account_name;
$log->logfile = TODAYS_LOG_PATH . '/' . $logfile . '.log';
$GLOBALS['fk'] = new flipkart_dashboard((object)$current_account);
$sellerId = $current_account->seller_id;
$retries = 0;
$login_retires = 0;

$max_loop_count = 50;
if (isset($_GET['max_loop_count']) && !empty($_GET['max_loop_count']))
	$max_loop_count = (int)$_GET['max_loop_count'];


if (isset($_GET['is_automated']) && $_GET['is_automated'] == 1) {
	$counts = json_decode(get_fbf_order_counts($sellerId));
	$orders_count = $counts->allOrdersCounts[0]->counts->to_pick_list;
	if ($orders_count != (int)"0") {
		$loop_count = round_up($orders_count / $max_loop_count, 0);
		for ($i = 0; $i < $loop_count; $i++) {
			// if ($orders_count < 50)
			// 	$max_loop_count = $orders_count;
			generate_picklist();
			sleep(2);
		}
	}
}

if (isset($_GET['generate_picklist']) && $_GET['generate_picklist'] == 1) {
	exit("All picklists generated");
}

if (isset($_GET['picklist_id']) && !empty($_GET['picklist_id'])) {
	$picklists[] = trim($_GET['picklist_id']);
	echo 'Picklists : ' . trim($_GET['picklist_id']) . '<br />';
} else {
	$all_picklists = json_decode(get_picklists($sellerId));
	foreach ($all_picklists->pick_lists as $picklist) {
		$picklists[] = $picklist->pick_list_id;
	}
}

if (empty($picklists)) {
	$log->write('No picklist found', $logfile);
	exit('No picklist found');
}

foreach ($picklists as $pickListId) {
	$picklists_details = get_picklist_details($pickListId, $sellerId);
	if (strpos($picklists_details, 'Failed to get pick list') === false) {
		$start_picklist = start_picklist($pickListId, $sellerId);
		$picklist_items = json_decode($picklists_details);

		if ($picklist_items->pending_shipments_count > (int)0) {
			foreach ($picklist_items->shipments as $shipment) {
				$multi_items = false;
				if (count($shipment->items) > 1)
					$multi_items = true;

				// if ($multi_items)
				// 	var_dump($shipment);
				// continue;
				// $order_item_id = $shipment->items[0]->order_item_id;
				// if ($order_item_id == "11666883134638200")
				// 	continue;
				$order_item_details = array();

				if ($shipment->items[0]->status == "COMPLETED" || $shipment->items[0]->status == "CANCELLED") {
					continue;
					// } else if ($shipment->items[0]->status == "IN_PROCESS"){
					// 	$lid = $shipment->items[0]->listing_id;
					// 	echo $lid.'<br />';
					// 	$tracking_details = $fk->get_order_shipment_details($shipment->external_shipment_id);
					// 	$tracking_id = $tracking_details->shipments[0]->courierDetails->deliveryDetails->trackingId;
					// 	echo $tracking_id.'<br />';
					// 	$order_item_id = $shipment->items[0]->order_item_id;
					// 	$status = itemConformationStatus($shipment->external_shipment_id, $sellerId, $pickListId);
					// 	if ($status || $status != "{}"){
					// 		$file = $fk->get_label($external_shipment_id, ROOT_PATH.'/labels/');
					// 		echo $file.'<br />';
					// 		if (empty($tracking_id)){
					// 			$enrich = enrich($shipment->external_shipment_id, $sellerId);
					// 			$order_details = json_decode($enrich);
					// 			$tracking_id = $order_details[0]->view->sub_shipments[0]->tracking->tracking_id;
					// 			echo $tracking_id.'<br />';
					// 		}
					// 		$scanComplete = scanComplete($pickListId, $sellerId, $tracking_id);
					// 		if ($scanComplete){
					// 			$output = json_decode($scanComplete);
					// 			if(isset($output->error)){
					// 				print_r($output);
					// 				echo "UNABLE TO COMPLETE SCAN for orderItemID ".$order_item_id." and LID ".$lid."<br />";
					// 			} else {
					// 				print_r($output);
					// 				echo '<br />';
					// 			}
					// 		}
					// 	}
				} else {
					// if ($shipment->items[0]->status == "OPEN"){
					$lid = $shipment->items[0]->listing_id;
					echo $lid . '<br />';
					// 1
					get_listing_details($lid, $sellerId);
					$pack_listing_scan = scanListingID($lid, $pickListId, $sellerId);
					if ($pack_listing_scan) {
						$pack_listing_scan = json_decode($pack_listing_scan);
						// var_dump($pack_listing_scan);
						$is_cancelled = false;
						$quantity = 1;
						if (isset($pack_listing_scan->error) && $pack_listing_scan->error->details->error->errors[0]->code == "CANCELLED_SHIPMENT_ITEM") {
							$external_shipment_id = $pack_listing_scan->error->details->error->errors[0]->params->external_shipment_id;
							$is_cancelled = true;
							$quantity--;
						} else {
							$external_shipment_id = $pack_listing_scan->external_shipment_id;
						}
						// get_listing_details($lid, $sellerId);
						// 2
						$enrich = enrich($external_shipment_id, $sellerId);

						// Multi Qty
						if (!empty($pack_listing_scan->to_be_scanned)) {
							if ($multi_items) { // WILL ADD THE FIRST ITEM
								$order_item_details[$pack_listing_scan->scanned->order_item_id] = array(
									'order_item_id' => $pack_listing_scan->scanned->order_item_id,
									'quantity' => $quantity
								);
							}
							foreach ($pack_listing_scan->to_be_scanned as $multi_shipment) { // Loop for multi Items
								if ($multi_items) // MULTI ITEM QTY RESET
									$quantity = 0;

								for ($i = 0; $i < $multi_shipment->quantity; $i++) { // Loop for multi Qty
									$pack_listing_scan_multi = scanListingID($multi_shipment->listing_id, $pickListId, $sellerId);
									// $enrich = enrich($external_shipment_id, $sellerId);
									$quantity++;
								}

								// var_dump($quantity);
								if (array_key_exists($multi_shipment->order_item_id, $order_item_details)) {
									$order_qtys = $order_item_details[$multi_shipment->order_item_id]['quantity'] + $quantity;
									$order_item_details[$multi_shipment->order_item_id] = array(
										'order_item_id' => $multi_shipment->order_item_id,
										'quantity' => $order_qtys
									);
								} else {
									$order_item_details[] = array(
										'order_item_id' => $multi_shipment->order_item_id,
										'quantity' => $quantity
									);
								}
							}
							// Reset the array keys
							$order_item_details = array_values($order_item_details);
						} else {
							$order_item_details[] = array(
								'order_item_id' => $pack_listing_scan->scanned->order_item_id,
								'quantity' => $quantity
							);
						}

						// var_dump($order_item_details);
						// exit;

						if ($enrich) {
							$order_details = json_decode($enrich);
							if ($is_cancelled)
								continue;

							$tracking_id = $order_details[0]->view->sub_shipments[0]->tracking->tracking_id;
							echo $tracking_id . '<br />';
							echo $order_item_id = $pack_listing_scan->scanned->order_item_id;
							echo '<br />';

							$db->where('orderItemId', $order_item_id);
							if (!$db->has(TBL_FK_ORDERS)) {
								echo 'Order not found. <br />';
								import_order($fk, $order_item_id, $current_account->account_id);
								sleep(1); // let the import get completed.
							}

							$lbhw = array();
							if ($current_account->account_id == 9) {
								$product = $order_details[0]->view->order_items[0]->product;
								$lbhw = array(
									'length' => $product->length,
									'breadth' => $product->breadth,
									'height' => $product->height,
									'weight' => $product->weight
								);
							}

							// 3 MARK RTD
							$rtd = rtd($pickListId, $external_shipment_id, $order_item_details, $sellerId, $lbhw);
							if ($rtd == "{}") {
								$status = itemConformationStatus($external_shipment_id, $sellerId, $pickListId);
								// echo 'Status::<br />';
								// var_dump($status);
								// echo '<br />';
								$log->write($status, $logfile);
								if (/*$status != "{}" || */$status) {
									if (check_valid_pdf(ROOT_PATH . '/labels/single/FullLabel-' . $external_shipment_id . '.pdf') === FALSE) {
										unlink(ROOT_PATH . '/labels/single/FullLabel-' . $external_shipment_id . '.pdf');
									}

									$file = $fk->get_label($external_shipment_id, ROOT_PATH . '/labels/');

									if (check_valid_pdf($file)) {
										$log->write("Label Created: " . $file . "\n", $logfile);
										echo "Label Created: " . $file . '<br />';
										if (empty($tracking_id)) {
											$enrich = enrich($external_shipment_id, $sellerId);
											$order_details = json_decode($enrich);
											$tracking_id = $order_details[0]->view->sub_shipments[0]->tracking->tracking_id;
											echo $tracking_id . '<br />';
										}

										$scanComplete = scanComplete($pickListId, $sellerId, $tracking_id);
										if ($scanComplete) {
											$output = json_decode($scanComplete);
											if (isset($output->error)) {
												if ($debug) {
													print_r($output);
												}
												echo "UNABLE TO COMPLETE SCAN for orderItemID " . $order_item_id . " and LID " . $lid . "<br />";
											} else {
												if ($debug) {
													print_r($output);
													echo '<br />';
												}
											}
										}
									}
								} else {
									$log->write("Label sidlined or no status response received.", $logfile);
									continue;
								}
							} else {
								$rtd = json_decode($rtd);
								$message = $rtd->error->message;
								$statusCode = $rtd->error->details->statusCode;
								$errorCode = $rtd->error->details->error->errors[0]->code;
								$errorMessage = $rtd->error->details->error->errors[0]->message;
								echo $message . ' (' . $statusCode . ') Error: (' . $errorCode . ') ' . $errorMessage . '<br />';
							}
						}
					}
				}
				// exit;

				echo '<br />-----<br />';
			}
		}

		get_picklist_details($pickListId, $sellerId);
	}
}
// ob_end_flush();
exit;

function import_order($fk, $orderItemId, $account_id)
{
	$return = $fk->get_order_details($orderItemId, true);
	$insert = $fk->insert_orders($return);
	if (strpos($insert, 'INSERT: New Order with order ID') !== FALSE || strpos($insert, 'UPDATE: Updated Order with order ID') !== FALSE) {
		echo 'Order Imported Successfully ' . $orderItemId . '<br />';
	} else if (strpos($insert, 'INSERT: Already exists order with order ID') !== FALSE) {
		echo 'Order Already Exists ' . $orderItemId . '<br />';
	} else {
		echo 'Error Importing Order ' . $orderItemId . ' Error:' . $insert . '<br />';
	}
}

function get_fbf_order_counts($sellerId)
{
	global $fk, $log, $logfile, $debug;
	$url = "https://seller.flipkart.com/napi/fbfLite/picklist/allTypeCounts?sellerId=" . $sellerId;

	$return = $fk->send_request($url);
	if ($debug) {
		echo 'FBF Order Count: ';
		var_dump($return);
		echo '<br />';
	}

	$log->write('FBF Order Count: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at FBF Order Count: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at FBF Order Count: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function generate_picklist()
{
	global $fk, $current_account, $log, $logfile, $debug, $max_loop_count;

	$url = "https://seller.flipkart.com/napi/fbfLite/create/pick_list?type=REGULAR&size=" . $max_loop_count . "&locationId=" . $current_account->locationId; // &quantity=sinlge; &quantity=multi

	$return = $fk->send_request($url, array(), array(), "Accept: application/pdf");

	if (strpos($return, 'startxref') !== FALSE) {
		ob_start();
		$value = TODAYS_PICKLIST_PATH . '/P' . date("d", time()) . date("m", time()) . date("y", time()) . '-' . time() . '-' . $current_account->account_name . '.pdf';
		file_put_contents($value, $return);
		ob_end_clean();
	}

	$log->write('Generate Picklists: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at Generate Packlist: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Generate Packlist: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function get_picklists($sellerId)
{
	global $fk, $log, $logfile, $debug;
	$url = "https://seller.flipkart.com/napi/fbfLite/get/pending/pick_list?sellerId=" . $sellerId;

	$return = $fk->send_request($url);
	if ($debug) {
		echo 'Packlists: ';
		var_dump($return);
		echo '<br />';
	}

	$log->write('Generate Picklists: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at Packlists: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Packlists: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function start_picklist($pickListId, $sellerId)
{
	global $fk, $log, $logfile, $debug;
	$payload = json_encode(array("pickListId" => $pickListId, "sellerId" => $sellerId));

	$url = "https://seller.flipkart.com/napi/fbfLite/mark/pick_progress?sellerId=" . $sellerId;
	$return = $fk->send_request($url, $payload);
	if ($debug) {
		echo 'Packlist Start: ';
		echo $payload . '<br />';
		var_dump($return);
		echo '<br />';
	}

	$log->write('Packlist Start: ' . $pickListId . ' ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at Packlist Start: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Packlist Start: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function get_picklist_details($pickListId, $sellerId)
{
	global $fk, $log, $logfile, $debug;
	$url = "https://seller.flipkart.com/napi/fbfLite/get/pick_list?pickListId=" . $pickListId . "&sellerId=" . $sellerId;
	$return = $fk->send_request($url);
	if ($debug) {
		echo 'Packlist Details: ';
		var_dump($return);
		echo '<br />';
	}

	$log->write('Packlist Details: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at Packlist Details: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Packlist Details: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function get_listing_details($listing_id, $sellerId)
{
	global $fk, $log, $logfile, $debug;
	$url = "https://seller.flipkart.com/napi/fbfLite/getListingDetails?listingId=" . $listing_id . "&sellerId=" . $sellerId;
	$return = $fk->send_request($url);
	if ($debug) {
		echo 'Listing Details: ';
		var_dump($return);
		echo '<br />';
	}

	$log->write('Listing Details: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at Listing Details: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Listing Details: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function scanListingID($lid, $pickListId, $sellerId)
{
	global $fk, $log, $logfile, $debug;
	// Scan Listing ID
	$payload = json_encode(array("listingId" => $lid, "pickListId" => $pickListId, "sellerId" => $sellerId));

	$url = "https://seller.flipkart.com/napi/fbfLite/get/shipment/pick/list?sellerId=" . $sellerId;
	$return = $fk->send_request($url, $payload);
	if ($debug) {
		echo 'Scan LID: ';
		echo $payload . '<br />';
		var_dump($return);
		echo '<br />';
	}

	$log->write('Scan LID: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at Scan LID: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Scan LID: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function enrich($external_shipment_id, $sellerId)
{
	global $fk, $log, $logfile, $debug;
	// enrich to get tracking details
	$url = "https://seller.flipkart.com/napi/fbfLite/get/enrich?id=" . $external_shipment_id . "&sellerId=" . $sellerId;
	$return = $fk->send_request($url);
	if ($debug) {
		echo 'Enrich: ';
		var_dump($return);
		echo '<br />';
	}

	$log->write('Enrich: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at Enrich: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Enrich: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function rtd($pickListId, $external_shipment_id, $order_items, $sellerId, $lbhw = array())
{
	global $fk, $log, $logfile, $debug;
	$item_payload = array();
	foreach ($order_items as $order_item) {
		$item_payload[] = '{"invoice_date":"' . date('Y-m-d', time()) . '","order_item_id":"' . $order_item["order_item_id"] . '","quantity":' . $order_item["quantity"] . '}';
	}
	$item_payload = implode(',', $item_payload);
	if (empty($lbhw))
		$payload = '{"payload":{"pick_list_id":"' . $pickListId . '","shipments":[{"external_shipment_id":"' . $external_shipment_id . '","order_items":[' . $item_payload . '],"dimensions":{"length":25,"breadth":5,"height":15,"weight":0.1}}]},"sellerId":"' . $sellerId . '"}';
	else
		$payload = '{"payload":{"pick_list_id":"' . $pickListId . '","shipments":[{"external_shipment_id":"' . $external_shipment_id . '","order_items":[' . $item_payload . '],"dimensions":{"length":' . $lbhw["length"] . ',"breadth":' . $lbhw["breadth"] . ',"height":' . $lbhw["height"] . ',"weight":' . $lbhw["weight"] . '}}]},"sellerId":"' . $sellerId . '"}';

	// RTD
	$url = "https://seller.flipkart.com/napi/fbfLite/pick_list/shipment/pack?sellerId=" . $sellerId;
	$return = $fk->send_request($url, $payload);

	if ($debug) {
		echo 'RTD : ';
		echo $payload . '<br />';
		var_dump($return);
		echo '<br />';
	}

	$log->write('RTD: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at RTD: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at RTD: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function itemConformationStatus($external_shipment_id, $sellerId, $pickListId)
{
	global $fk, $log, $logfile, $debug, $retries, $current_account;
	// Check label status
	$url = "https://seller.flipkart.com/napi/fbfLite/shipments/is_confirm?external_shipment_id=" . $external_shipment_id . "&sellerId=" . $sellerId;
	$response = $fk->send_request($url);

	if ($debug) {
		echo 'Label Status: ';
		var_dump($response);
		echo '<br />';
	}

	$log->write('Label Status: ' . $response . "\n", $logfile);
	if (isset($response['err']) && !empty($response['err'])) {
		$log->write("Error at Label Status: " . $response['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Label Status: " . $response['err'] . '<br />';
		return false;
	}

	$status = json_decode($response);
	$return = false;
	// $log->write($external_shipment_id.'::'.$status->item_confirmation_status->{$external_shipment_id}, 'debug-process-'.$logfile);
	// $log->logfile = TODAYS_LOG_PATH.'/fbf-'.$current_account->account_name.'.log';
	if ($status->item_confirmation_status->{$external_shipment_id} === false) {
		$retries++;
		$log->write('retry:' . $retries, $logfile);
		if ($retries < 20) {
			sleep(2);  // wait for 2 sec before we retry.
			$return = itemConformationStatus($external_shipment_id, $sellerId, $pickListId);
		} else {
			$log->write('Sidline Initiated', $logfile);
			$sideline = sideline_order($external_shipment_id, $pickListId, $sellerId);
			$retries = 0; // reset the global retries
			if ($sideline) {
				$return = 'sidelined';
			}
		}
	} else {
		$retries = 0; // reset the global retries
		$return = true;
	}

	// var_dump($return);
	return $return;
}

function sideline_order($external_shipment_id, $pickListId, $sellerId)
{
	global $fk, $debug;
	$payload = '{"external_shipment_id":"' . $external_shipment_id . '","pick_list_id":"' . $pickListId . '","sellerId":"' . $sellerId . '"}';

	$url = "https://seller.flipkart.com/napi/fbfLite/sidelinePicklistGroup?sellerId=" . $sellerId;

	$return = $fk->send_request($url, $payload);
	echo 'Sideline Order: ';
	echo $payload . '<br />';
	// var_dump($return);
	// echo '<br />';

	if (isset($return['err']) && !empty($return['err'])) {
		echo "Error at Sideline Order: cURL Error #:" . $return['err'] . "<br />";
		return false;
	}
	return true;
}

function scanComplete($pickListId, $sellerId, $tracking_id)
{
	global $fk, $log, $logfile, $debug;
	// Scan Tracking and clear
	$payload = '{"pickListId":"' . $pickListId . '","sellerId":"' . $sellerId . '","trackingId":"' . $tracking_id . '"}';
	$url = "https://seller.flipkart.com/napi/fbfLite/pick_list/shipment/rts?sellerId=" . $sellerId;

	$return = $fk->send_request($url, $payload);
	if ($debug) {
		echo 'Scan Tracking ID: ';
		echo $payload . '<br />';
		var_dump($return);
		echo '<br />';
	}

	$log->write('Scan Tracking: ' . $return . "\n", $logfile);
	if (isset($return['err']) && !empty($return['err'])) {
		$log->write("Error at Scan Tracking: " . $return['err'] . "\n", $logfile);
		if ($debug)
			echo "Error at Scan Tracking: " . $return['err'] . '<br />';
		return false;
	}
	return $return;
}

function round_up($value, $precision)
{
	// REF: https://stackoverflow.com/questions/8239600/rounding-up-to-the-second-decimal-place/8239620#8239620
	// ANOTHER REF: https://www.php.net/manual/en/function.round.php#122313
	$pow = pow(10, $precision);
	return (ceil($pow * $value) + ceil($pow * $value - ceil($pow * $value))) / $pow;
}
