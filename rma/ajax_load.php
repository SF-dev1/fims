<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

use Razorpay\Api\Api;

if (isset($_REQUEST['action'])) {
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	include_once(ROOT_PATH . '/rma/functions.php');
	include_once(ROOT_PATH . '/includes/class-notification.php');
	global $db, $accounts, $account, $logisticPartner, $notification;
	$accounts = $accounts['shopify'];

	$active_lp = isset($_REQUEST['lpProvider']) ? $_REQUEST['lpProvider'] : NULL;
	$accountId = isset($_REQUEST['accountId']) ? $_REQUEST['accountId'] : NULL;
	if ($accountId) {
		if ($active_lp)
			$account = get_current_account($accountId, $active_lp);
		else
			$account = get_current_account($accountId);
		$active_lp = strtolower($account->lp_provider_name);
	}

	if ($active_lp && $account) {
		include_once(ROOT_PATH . '/includes/class-' . $active_lp . '-client.php');
	}

	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
	switch ($action) {
		case 'get_orders_details':
			$marketplace = $_GET['marketplace'] ?? $_REQUEST['marketplace'];
			$orderId = $_GET['order_key'] ?? $_REQUEST['order_key'];
			echo get_uid_details($marketplace, $orderId, null, '',);
			break;

		case 'get_order_details':
			$marketplace = $_GET['marketplace'] ?? $_REQUEST['marketplace'];
			$orderId = $_GET['order_key'] ?? $_REQUEST['order_key'];
			echo get_uid_details($marketplace, $orderId, null, '', true);
			break;

		case 'get_RMA_details':
			$marketplace = $_GET['marketplace'] ?? $_REQUEST['marketplace'];
			$orderId = $_GET['order_key'] ?? $_REQUEST['order_key'];
			return get_uid_details($marketplace, $orderId);
			break;

		case 'get_ui_details': // WHEN UID IS NOT AVAILABLE
			$uid = $_REQUEST['uid'];
			$orderKey = $_REQUEST['orderKey'];
			$marketplace = $_REQUEST['marketPlace'];
			$warrantyPeriod = $_REQUEST['warrantyPeriod'];
			if (empty($uid)) {
				echo json_encode(array("type" => "error", "msg" => "UID cannot be empty"));
				return;
			}

			$db->joinWhere(TBL_INVENTORY . 'in', 'in.inv_id=il.inv_id');
			$db->where('log_type', 'sales');
			$db->where('log_details', '%Stock Transfer%', 'LIKE');
			$db->where('inv_id', $uid);
			$inventoryLog = $db->objectBuilder()->getOne(TBL_INVENTORY_LOG);
			$details = array('uid' => json_encode(array($inventoryLog->inv_id)));
			if ($marketplace == 'amazon' && $uid) {
				if ($db->where('orderItemId', $orderKey)->update(TBL_AZ_ORDERS, $details)) {
					echo get_uid_details($marketplace, NULL, $orderKey);
				} else {
					echo json_encode(array("type" => "error", "msg" => "No order details found."));
				}
			} elseif ($marketplace == 'flipkart') {
				if ($db->where('orderItemId', $orderKey)->update(TBL_FK_ORDERS, $details)) {
					echo get_uid_details($marketplace, NULL, $orderKey, $warrantyPeriod);
				}
			}
			break;

		case 'get_pincode_details':
			$pincode = $_REQUEST['pincode'];

			$db->where('pincode', $pincode);
			$pin = $db->getOne(TBL_PINCODE_SERVICEABILITY, array('CONCAT(UCASE(LEFT(city, 1)), LCASE(SUBSTRING(city, 2))) as city', 'CONCAT(UCASE(LEFT(state, 1)), LCASE(SUBSTRING(state, 2))) as state'));
			if ($pin)
				echo json_encode(array_merge(array("type" => "success"), $pin));
			else
				echo json_encode(array("type" => "error", "msg" => "No pincode details found."));
			break;

		case 'create_rma':
			$accountId = $_POST['accountId'];
			$data = array(
				'accountId' => $accountId,
				'status' => 'start',
				'internalStaus' => 'start',
				'marketplace' => $_POST['marketplace'],
				'orderId' => $_POST['orderId'],
				'orderItemId' => $_POST['orderItemId'],
				'invoiceDate' => $_POST['invoiceDate'],
				'warrantyPeriod' => $_POST['warrantyPeriod'],
				'complain' => $db->escape($_POST['complains']),
				'returnType' => $_POST['pickup_type'],
				'returnAddress' => json_encode($_POST['return']),
				'mobileNumber' => $_POST['mobile_number'],
				'whatsappNumber' => $_POST['whatsapp_number'],
				'emailAddress' => $_POST['email_address'],
				'notificationSubscription' => isset($_POST['share_update']) ? json_encode(array_keys($_POST['share_update'])) : '',
				'rmaLog' => json_encode(array('content' => 'RMA Created', 'user' => $current_user['userID'], 'timestamp' => date('c'))),
				'qcEnable' => filter_var($_POST['qc_enable'], FILTER_VALIDATE_BOOLEAN) ? "1" : "0",
				'createdBy' => $current_user['userID'],
				'createdDate' => $db->now()
			);

			$item_estimate = json_decode($_POST['estimate']);
			$estimateTotal = 0;
			$shippingTotal = 0;
			$items_data = array();
			$uids = array();
			$productIssues = array();

			foreach ($_POST['uids'] as $uid) {
				$uid_details = $_POST[$uid];
				array_push($uids, $uid);
				$productIssues[$uid] = $_POST[$uid]['damaged'];

				$data['uids'] = json_encode($uids);
				$data['productCondition'] = "damaged";
				$data['productIssues'] = json_encode($productIssues);
				$data['productAction'] = 'repair';

				$estimate_data = array();
				$shipping_data = array();
				foreach ($item_estimate as $item) {
					if ($item->uid == $uid) {
						$estimate_data[] = array(
							'part' => $item->part,
							'quantity' => $item->quantity,
							'price' => $item->price,
							'approved' => true
						);
						$estimateTotal += (int)$item->price;
					} elseif ($item->uid == "Shipping") {
						$shipping_data = array(
							'service' => $item->part,
							'quantity' => $item->quantity,
							'price' => $item->price,
							'approved' => true
						);
						$shippingTotal += $item->price;
					}
				}
				$data['estimate'] = json_encode($estimate_data);
				$data['estimateStatus'] = 'creation_approved';
				$data['estimateTotal'] = $estimateTotal;
				$data['estimateTotalApproved'] = $estimateTotal;
				$data['estimateShipping'] = json_encode($shipping_data);
				$data['estimateShippingTotal'] = $shippingTotal;
			}

			$pickupRequest = false;
			if ($data['returnType'] == "free_pickup")
				$pickupRequest = true;

			$paymentRequest = false;
			if ($shippingTotal > 0)
				$paymentRequest = true;

			$db->startTransaction();
			$rmaId = $db->insert(TBL_RMA, $data);

			if ($rmaId) {
				$db->commit();
				$rmaNumber = date('Ymd', time()) . sprintf('%06d', $rmaId);
				$db->where('rmaId', $rmaId);
				$db->update(TBL_RMA, array('rmaNumber' => $rmaNumber));
				echo json_encode(array('type' => 'success', 'message' => 'Successfully created RMA', 'data' => array('rmaId' => $rmaId, 'rmaNumber' => $rmaNumber, 'pickupRequest' => $pickupRequest, 'paymentRequest' => $paymentRequest)));
			} else {
				$db->rollback();
				echo json_encode(array('type' => 'error', 'message' => 'Unable to Create RMA', 'error' => $db->getLastError()));
			}
			break;

		case 'create_pickup':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$rmaId = $_REQUEST['rmaId'];
			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=ra.accountId');
			$db->where('rmaId', $rmaId);
			$rma = $db->objectBuilder()->getOne(TBL_RMA . ' ra', array('ra.rmaId', 'ra.rmaNumber', 'ra.accountId', 'ra.marketplace', 'ra.orderId', 'ra.orderItemId', 'ra.returnType', 'ra.returnAddress', 'ra.mobileNumber', 'ra.emailAddress', 'ra.qcEnable', 'ra.uids', 'sa.account_name', 'sa.account_return_address', 'sa.account_domain', 'sa.account_api_key', 'sa.account_api_pass', 'sa.account_api_secret', 'sa.account_active_logistic_id', 'sa.firm_id'));

			$db->where('orderItemId', $rma->orderItemId);
			if ($rma->marketplace == "shopify")
				$order = $db->objectBuilder()->getOne(TBL_SP_ORDERS, array('sku, title, hsn, sellingPrice'));

			if ($rma->marketplace == "amazon")
				$order = $db->objectBuilder()->getOne(TBL_AZ_ORDERS, array('sku', 'title', '9101 as hsn', 'orderTotal as sellingPrice'));

			if ($rma->marketplace == "flipkart")
				$order = $db->objectBuilder()->getOne(TBL_FK_ORDERS, array('sku', 'title', 'hsn', 'sellingPrice'));

			$rma = (object)array_merge((array)$rma, (array)$order);

			$db->where('lp_provider_id', $rma->account_active_logistic_id);
			$account = $db->objectBuilder()->getOne(TBL_SP_LOGISTIC);
			include_once(ROOT_PATH . '/includes/class-' . strtolower($account->lp_provider_name) . '-client.php');
			$rma->pickupAddress = json_decode($rma->returnAddress);
			$rma->pickupAddress->email = trim($rma->emailAddress);
			$rma->pickupAddress->phone = trim($rma->mobileNumber);
			$rma->returnAddress = json_decode($rma->account_return_address);
			$rma->orderNumberFormated = $rma->rmaNumber;
			$rma->orderId = $rma->rmaNumber;

			// CREATE A SINGLE SHIPMENT WITH ALL ITEMS
			$rma->order_items = array();
			$subtotal = 0;
			// foreach ($rma as $return){
			// 	$key = array_search($return->sku, array_column((array)$rma->order_items, 'sku'));
			// 	if ($key !== false){
			// 		$subtotal += $return->sellingPrice;
			// 		$rma->order_items[$key]['units'] += 1;
			// 		$rma->order_items[$key]["selling_price"] += ($return->sellingPrice);

			// 		if ($rma->qcEnable)
			// 			$rma->order_items[$key]["qc_serial_no"] .= ', '.json_decode($return->r_uid)[0];
			// 	} else {
			$line_item = array(
				"sku" => $rma->sku,
				"name" => $rma->title,
				"units" => count(json_decode($rma->uids)),
				"selling_price" => ($rma->sellingPrice * count(json_decode($rma->uids))),
				"discount" => 0,
				"hsn" => isset($rma->hsn) ? $rma->hsn : '0',
			);
			$subtotal += ($rma->sellingPrice * count(json_decode($rma->uids)));

			if ($rma->qcEnable) {
				$line_item["qc_enable"] = "1";
				$line_item["qc_brand"] = $rma->returnAddress->brand;
				$line_item["qc_serial_no"] = implode(',', json_decode($rma->uids));
				// "qc_product_image" => "https://s3-ap-southeast-1.amazonaws.com/kr-multichannel/1636713733zxjaV.png" // TODO: ADD FULL IMAGE COLUMN TO THE PRODUCT MASTER TABLE AND UPLOAD ALL THE HD IMAGES
			}
			$rma->order_items[] = $line_item;
			// 	}
			// }
			$rma->discount = 0;
			$rma->sub_total = $subtotal;

			$response = $logisticPartner->createReturn($rma, true, true); // CREATE RETURN AND REQUEST PICKUP
			if ($response['type'] == "success") {
				$db->where('rmaId', $rmaId);
				$db->update(TBL_RMA, array('status' => 'pending_pickup'));
			}
			echo json_encode($response);
			break;

		case 'create_payment_link':
			// sleep(5);
			// echo '{"type":"success","message":"Successfully created payment link","data":{"rmaId":"'.$_REQUEST['rmaId'].'","rmaNumber":null,"paymentLink":"https:\/\/rzp.io\/i\/r6RijNBb"}}';
			// exit;
			ignore_user_abort(true); // THIS REQUEST NEEDS TO BE COMPLETED WITHOUT ANY INTEREPTION
			$rmaId = $_REQUEST['rmaId'];

			$db->where('rmaId', $rmaId);
			$db->groupBy('paymentFor');
			$paid_amounts = $db->get(TBL_RMA_PAYMENTS, NULL, 'SUM(amount) as amount, SUM(amountPaid) as amountPaid, (SUM(amount) - SUM(amountPaid)) as amountPending, paymentFor');
			$shippingPaidAmount = 0;
			$servicePaidAmount = 0;
			foreach ($paid_amounts as $amount) {
				if ($amount['paymentFor'] == "logistic") {
					$shippingPaidAmount = $amount['amountPaid'];
				}
				if ($amount['paymentFor'] == "rma") {
					$servicePaidAmount = $amount['amountPaid'];
				}
			}

			$db->where('rmaId', $rmaId);
			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=ra.accountId');
			$rma = $db->objectBuilder()->getOne(TBL_RMA . ' ra', 'marketplace, orderId, orderItemId, returnType, estimateShippingTotal, rmaNumber, JSON_UNQUOTE(JSON_EXTRACT(returnAddress,"$.address_name")) as name, mobileNumber, emailAddress, notificationSubscription, account_rma_invoice_number, account_active_pg_id');
			$notification_mediums = array_fill_keys(array_keys(array_flip(json_decode($rma->notificationSubscription))), true);

			if ($rma->returnType == "paid_pickup") {
				$db->where('pg_provider_id', $rma->account_active_pg_id);
				$pg_creds = $db->getOne(TBL_SP_PAYMENTS_GATEWAY . ' pg', array('pg_provider_id', 'pg_provider_key', 'pg_provider_secret'));

				$amount = ((int)$rma->estimateShippingTotal - (int)$shippingPaidAmount);

				$payment_api = new Api($pg_creds['pg_provider_key'], $pg_creds['pg_provider_secret']);
				$data = array(
					"amount" => $amount * 100,
					"currency" => "INR",
					"accept_partial" => false,
					// "expire_by" => strtotime('+24 hours'),
					"reference_id" => $rma->rmaNumber . '-Shipping',
					"description" => "Shipping for RMA #" . $rma->rmaNumber,
					"customer" => array(
						"name" => $rma->name,
						"contact" => "+91" . $rma->mobileNumber,
						"email" => $rma->emailAddress
					),
					"notify" => $notification_mediums,
					"reminder_enable" => true,
					// "callback_url" => BASE_URL."/rma/notifications-receiver.php?event=rma_payment_update",
					// "callback_method" => "get",
					// "options" => array(
					// 	"checkout" =>
					// 	array(
					// 		"name" => "Sylvi"
					// 	)
					// )
				);
				$response = $payment_api->paymentLink->create($data);
				$log->write("Data:" . json_encode($data) . "\nResponse: " . json_encode($response), 'razorpay-pl-create');
			}

			$insert = array(
				'rmaId' => $rmaId,
				'rmaNumber' => $rma->rmaNumber,
				'paymentGatewayId' => $pg_creds['pg_provider_id'],
				'amount' => $amount,
				'paymentFor' => 'shipping',
				'paymentStatus' => 'pending',
				'paymentLinkId' => $response->id,
				'paymentLink' => $response->short_url,
				'createdDate' => $db->now()
			);

			$db->startTransaction();
			if ($db->insert(TBL_RMA_PAYMENTS, $insert)) {
				$db->where('rmaId', $rmaId);
				$db->update(TBL_RMA, array('status', 'pending_payment'));
				$db->commit();
				echo json_encode(array('type' => 'success', 'message' => 'Successfully created payment link', 'data' => array('rmaId' => $rmaId, 'rmaNumber' => $rma->rmaNumber, 'paymentLink' => $response->short_url)));
			} else {
				$db->rollback();
				echo json_encode(array('type' => 'error', 'message' => 'Unable to Insert Payment Link', 'error' => $db->getLastError()));
			}
			break;

		case 'send_notification_message':
			if (isset($_REQUEST['rmaId'])) {
				$rmaId = $_REQUEST['rmaId'];

				$db->join(TBL_SP_ACCOUNTS . ' sp', 'sp.account_id=r.accountId');
				$db->join(TBL_RMA_PAYMENTS . ' rp', 'rp.rmaId=r.rmaId', 'LEFT');
				$db->where('r.rmaId', $rmaId);
				$data = $db->objectBuilder()->getOne(TBL_RMA . ' r', 'r.rmaId,r.rmaNumber, rp.paymentLink, marketplace, returnType, JSON_UNQUOTE(JSON_EXTRACT(returnAddress,"$.name")) as name, mobileNumber, whatsappNumber, emailAddress, notificationSubscription, account_active_sms_id, account_active_email_id, account_active_whatsapp_id');

				if (isset($data->notificationSubscription) && !empty($data->notificationSubscription)) {
					$notification_mediums = array_fill_keys(array_keys(array_flip(json_decode($data->notificationSubscription))), true);
				}
			}

			$response = array();

			$sms_status = false;
			if (isset($notification_mediums)) {
				if ($notification_mediums['sms']) {
					if ($data->returnType == "paid_pickup") { // PAID PICK UP
						$data->template = 'sylvi_refund';
					} else if ($data->returnType == "self_ship") {
						if ($data->marketplace == "shopify")
							$data->template = 'rma_self_ship';
						else
							$data->template = 'rma_self_ship_sf';
					} else { // FREE PICKUP
						$data->template = 'rma_creation_pickup';
					}

					$data->medium = 'sms';
					$data->template = 'rma_pickup';
					$data->active_account_id = $data->account_active_sms_id;
					$data->rmaID = $data->rmaId;
					$data->mobile_number =  array('91' . $data->mobileNumber);
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

				$whatsapp_status = false;
				if (isset($notification_mediums['whatsapp'])) {
					if ($data->returnType == "paid_pickup") {
						// PAID PICK UP
						$data->type = 'rma_pickup';
						$post_params = array(
							'to' => trim('91' . $data->whatsappNumber), // SHOULD INCLUDE ISD CODE and +
							'name' => $data->name,
							'params' => array(
								$data->name,
								$data->rmaNumber
							),
							'button_params' => array(
								substr($data->paymentLink, strrpos($data->paymentLink, '/') + 1)
							)
						);
					} else if ($data->returnType == "self_ship") {
						$post_params = array(
							'to' => trim('91' . $data->whatsappNumber), // SHOULD INCLUDE ISD CODE and +
							'name' => $data->name,
							'params' => array(
								$data->name,
								$data->rmaNumber
							)
						);
						if ($data->marketplace == "shopify") {
							$data->type = 'rma-sylviselfship';
						} else {
							$data->type = 'rma-selfship';
						}
					} else {
						$data->type = 'rma-pickup';
						$post_params = array(
							'to' => trim('91' . $data->whatsappNumber), // SHOULD INCLUDE ISD CODE and +
							'name' => $data->name,
							'params' => array(
								$data->name,
								$data->rmaNumber,
								$data->returnLogistic,
								$data->lp_provider_tracking_url . $data->returnTrackingId
							)
						);
					}
					// $whatsApp = new whatsApp($data->account_active_whatsapp_id);
					// $s_whatsApp = $whatsApp->sendMessage($template, $post_params);
					$data->medium = 'whatsapp';
					$data->active_account_id = $data->account_active_whatsapp_id;
					$data->rmaID = $data->rmaId;
					$text = ['Ishan', '31032023', 'Xpressbees'];
					$button = [['sub_type' => 'url', 'type' => 'text', 'value' => 'https://sylvi.in/1']];
					$data->parameter = ['text' => $text, 'button' => $button];
					$data->template = 'rma_pickup';
					$data->post_params = $post_params;
					$s_whatsApp = $notification->send($data);

					if ($s_whatsApp['status'] == "success") {
						$whatsapp_status = true;
						$response['data']['whatsapp']['sent'] = true;
						$response['data']['whatsapp']['message'] = 'WhatsApp Successfully sent';
						$response['data']['whatsapp']['message_id'] = $s_whatsApp['message_id'];
					}
				}

				$email_status = true;
				if (isset($notification_mediums['email'])) {
					$email_body = str_replace(array("##SELLERNAME##", "##DISTRIBUTORNAME##", "##DISTRIBUTOREMAIL##"), array('test', 'test distributor', 'distributor email'), get_template('email', 'welcome_onboard'));
					$data->email_body = $email_body;
					$data->medium = 'email';
					$data->rmaID = $data->rmaId;
					$data->active_account_id = $data->account_active_email_id;
					$data->template = 'rma_pickup';
					$email = $notification->send($data);
					if ($email == 1) {
						$email_status = true;
						$response['data']['email']['sent'] = true;
						$response['data']['email']['message'] = 'Email Successfully sent';
					} else {
						$email_status = false;
						$response['data']['email']['sent'] = false;
						$response['data']['email']['message'] = 'Problem with Email sent';
					}
				}

				if ($sms_status && $whatsapp_status && $email_status) {
					$response['type'] = 'success';
					$response['message'] = 'All notifications sent successfully';
				}
			}
			echo json_encode($response);
			break;

			// VIEW
		case 'get_orders_count':
			$handler = $_GET['handler'];
			if ($handler == "return")
				$order_type = array("start", "pending_pickup", "payment_pending", "in_transit", "out_for_delivery", "delivered", "cancelled");

			if ($handler == "repairs")
				$order_type = array("received", "repair_payment_pending", "repair_in_progress", "pending_estimate_approval", "qc_in_progress", "completed");

			if ($handler == "reship")
				$order_type = array("packing", "ready_to_dispatch", "shipped", "pending_pickup", "in_transit", "delivered");

			$orders_count = view_orders_count($order_type);
			echo json_encode(array('orders' => $orders_count));
			break;

		case 'view_orders':
			$type = $_GET['type'];
			// $is_returns = ($_GET['handler'] == "return") ? true : false;
			$orders = view_orders($type);
			// echo $db->getLastQuery();

			$output = array('data' => array());
			foreach ($orders as $order) {
				$output['data'][] = create_order_view($order, $type);
			}
			echo json_encode($output);
			break;

		case 'get_comments':
			echo json_encode(get_comments($_REQUEST['rmaId'], false));
			break;

		case 'view_comments':
			$db->where('party_id', 0);
			$users = $db->objectBuilder()->get(TBL_USERS, NULL, 'userID, display_name');

			$rmaId = $_GET['rmaId'];
			$db->where('rmaId', $rmaId);
			$db->orderBy('createdDate', 'DESC');
			$comments = $db->objectBuilder()->get(TBL_RMA_COMMENTS);

			if ($comments) {
				$response = array();
				$assuredDelivery = false;
				$tag = "";
				foreach ($comments as $comment) {
					$data['comment'] = str_replace('\r\n', '<br />', $comment->comment);
					$data['orderItemId'] = $comment->rmaId;
					$data['created_by'] = get_user_by_id($users, $comment->userId)->display_name;
					$data['created_on'] = date('M d, Y H:i', strtotime($comment->createdDate));
					$data['comment_for'] = ucwords(str_replace('_', ' ', $comment->commentFor));
					// $data['assured_delivery'] = $comment->assured_delivery;
					$assuredDelivery = ($comment->commentFor == "assured_delivery" ? true : false);
					if (strpos($comment->commentFor, 'call') !== FALSE)
						$tag = ucwords(str_replace('_', ' ', $comment->commentFor));

					$response[] = $data;
				}
				echo json_encode(array('type' => 'success', 'data' => $response, 'assuredDelivery' => $assuredDelivery, 'tag' => $tag));
			} else {
				echo json_encode(array('type' => 'success', 'data' => ''));
			}
			break;

		case 'add_comment':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			// sleep(2);
			// echo json_encode(array('type' => 'success', 'message' => 'Comment Successfully added'.$message));
			// return;

			$comment = $_POST['comment'];
			$commentFor = $_POST['comment_for'];
			$rmaId = $_POST['rmaId'];
			$commentAdditional = '';

			// $ndrApprovedReason = isset($_POST['ndr_approved_reason']) ? $_POST['ndr_approved_reason'] : NULL;

			$comment_data = array(
				'comment' => $db->escape($comment),
				'rmaId' => $rmaId,
				'commentFor' => strtolower($commentFor),
				'userId' => $current_user['userID'],
			);

			if (!empty($commentAdditional))
				$comment_data['commentAdditional'] = $commentAdditional;

			$message = "";

			if ($db->insert(TBL_RMA_COMMENTS, $comment_data))
				echo json_encode(array('type' => 'success', 'message' => 'Comment Successfully added' . $message));
			else
				echo json_encode(array('type' => 'error', 'message' => 'Unable to add comment', 'error' => $db->getLastError()));
			break;

		case 'self_shipped': // SELF SHIP
			$orderId = $_POST['orderId'];
			$orderItemId = $_POST['orderItemId'];
			$trackingId = $_POST['trackingId'];
			$logistic = $_POST['logistic'];
			$logisticProvider = $_POST['logisticProvider'];
			$pickedUpDate = $_POST['pickedUpDate'];

			// UPDATE SHIPPING STATUS ON SHOPIFY WITH TRACKING ID AND TRACKING URL
			$tracking_details = array(
				"tracking_number" => $trackingId,
				"tracking_company" => $logistic,
			);
			$data1 = array(
				'lpProvider' => $logisticProvider,
				'trackingId' => $trackingId,
				'logistic' => $logistic,
				'pickedUpDate' => $pickedUpDate,
				'shipmentStatus' => 'in_transit',
			);
			$db->where('f.orderId', $_POST['rmaNumber']);
			$db->joinWhere(TBL_RMA . ' r', 'r.rmaNumber = f.orderId');

			if ($db->update(TBL_FULFILLMENT . ' f', $data1)) {
				$return = array("type" => "success", "message" => "Order successfully fulfilled on Shopify");
			} else {
				$return = array("type" => "error", "message" => "Unable to update order status", "error" => $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'mark_orders':
			switch ($_REQUEST['type']) {
				case 'delivered':

					$db->where('f.orderId', $_POST['rmaNumber']);
					$db->joinWhere(TBL_RMA . ' r', 'r.rmaNumber = f.orderId');
					$update = $db->update(TBL_FULFILLMENT . ' f', array('shipmentStatus' => 'delivered', 'deliveredDate' => $_POST['delivered_date'] . ' 23:59:59'));

					if ($update) {
						$log->write("Shipment Status: delivered\nCurrent Status: undelivered-rto\nUpdate Status: delivered", 'shopify-order-status');

						$return = array("type" => "success", "message" => "Order successfully updated to delivered");
					} else {
						$return = array("type" => "error", "message" => "Unable to update order status", "error" => $db->getLastError());
					}
					echo json_encode($return);
					break;
			}
			break;
	}
}
