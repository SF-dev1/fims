<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
	include_once(dirname(dirname(__FILE__)) . '/config.php');
	include_once(ROOT_PATH . '/includes/connectors/shopify/shopify.php');
	include_once(ROOT_PATH . '/includes/connectors/shopify/shopify-dashboard.php');
	include_once(ROOT_PATH . '/shopify/functions.php');
	global $db, $accounts, $account, $logisticPartner;
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
		case 'get_orders_count':
			$handler = $_GET['handler'];
			$order_type = array("pending", "new", "packing", "rtd", "shipped", "cancelled", "pickup_pending", "in_transit", "ndr");
			$is_returns = false;
			if ($handler == "return") {
				$order_type = array("start", "pending_pickup", "in_transit", "out_for_delivery", "delivered", "received", "claimed", "completed");
				$is_returns = true;
			}
			$orders_count = view_orders_count($order_type, $is_returns);
			echo json_encode(array('orders' => $orders_count));
			break;

		case 'view_orders':
			$type = $_GET['type'];
			$is_returns = ($_GET['handler'] == "return") ? true : false;
			$orders = view_orders($type, $is_returns);
			// echo $db->getLastQuery();

			$output = array('data' => array());
			$output_orders = array();
			foreach ($orders as $order) {
				if (in_array($order->orderId, $output_orders)) {
					$data = create_order_view($order, $type, $is_returns, true);
					$data['checkbox'] = "";
					$output['data'][] = $data;
				} else {
					$output_orders[] = $order->orderId;
					$output['data'][] = create_order_view($order, $type, $is_returns);
				}
			}
			echo json_encode($output);
			break;

		case 'cancel_order':
			$reason = $_POST['comment'];
			$orderId = $_POST['orderId'];

			$db->startTransaction();

			try {
				$sp = new shopify_dashboard($account);
				$db->join(TBL_SP_ORDERS . " o", "o.orderId = f.orderId");
				$db->join(TBL_SP_ACCOUNTS . ' a', 'a.account_id=o.accountId');
				$db->join(TBL_SP_PAYMENTS_GATEWAY . ' pg', 'pg.pg_provider_id=a.account_active_refund_pg_id');

				$db->where("f.orderId", $orderId);
				$db->where("f.fulfillmentType", "forward");
				$db->where("f.fulfillmentStatus", "cancelled", "!=");
				$db->where("f.channel", "shopify");
				$order = $db->getOne(TBL_FULFILLMENT . " f", "f.*, o.*, a.account_active_whatsapp_id");
				$logisticDetails = json_decode($order["lpShipmentDetails"], true);

				$fulfillment = $sp->cancel_fulfilment($orderId);
				$logisticPartner->cancelShipment($logisticDetails["awb_code"]);
				$data = array();
				$data["idenifierType"] = "orderId";
				$data["idenifierValue"] = $orderId;
				$data["medium"] = ["whatsapp"];
				$data["active_whatsapp_id"] = $order["account_active_whatsapp_id"];
				$data["templateId"] = get_template_id_by_name("order_cancelled");
				$notification = new Notification();
				$notification->send($data);

				if ($order["replacementOrder"]) {
					$original_order = find_initial_order($order["orderItemId"]);
					if ($original_order["paymentType"] == "cod") {
						if (!is_null($original_order["refundId"])) { // CANCEL PREVIOUS LINK
							$response = $api->PayoutLink->fetch($original_order["refundId"])->cancel();
							$data = array(
								'refundStatus' => $response->status,
								'cancelledDate' => $db->now()
							);
							$db->where('refundId', $original_order["refundId"]);
							$db->update(TBL_SP_REFUNDS, $data);
						}
						$refund_amount = (($original_order->totalPrice - $original_order->shippingCharge - $original_order->paymentGatewayDiscount));

						$customer_details = json_decode($original_order["billingAddress"], true);
						$payment_details = array(
							'account_number' => $original_order["pg_provider_account"],
							'amount' => (int) $refund_amount * 100,
							'currency' => 'INR',
							'accept_partial' => false,
							'description' => 'Refund for Sylvi Order #' . $original_order["orderNumberFormated"],
							'contact' => array(
								"type" => "customer",
								'name' => $customer_details["name"],
								'email' => $customer_details["email"],
								'contact' => substr(str_replace(array('+', ' '), '', $customer_details["phone"]), -10),
							),
							'send_sms' => true,
							'send_email' => true,
							"purpose" => "refund",
							'reminder_enable' => true,
							'notes' => array(),
						);
						$response = $api->PayoutLink->create($payment_details);
						$log->write(json_encode($response), 'shopify-refund');
						$data = array(
							'refundId' => $response->id,
							'orderId' => $original_order["orderId"],
							'orderItemId' => $original_order["orderItemId"],
							'refundLinkURL' => $response->short_url,
							'refundStatus' => $response->status,
							'refundAmount' => $refund_amount,
							'refundPg' => $original_order["pg_provider_id"],
							'createdDate' => $db->now()
						);
						$db->insert(TBL_SP_REFUNDS, $data);

						// ADD REFUND DETAILS TO COMMENT 
						$comment_data = array(
							'comment' => 'Refund Initiated\r\nRef: ' . $response->short_url . '\r\nAmt: ' . $refund_amount,
							'orderId' => $original_order["orderId"],
							'commentFor' => 'refund',
							'userId' => $current_user['userID'],
						);
						$db->insert(TBL_SP_COMMENTS, $comment_data);
					}
				} else if ($order["paymentType"] == "prepaid") {
					$data["templateId"] = get_template_id_by_name("replcement_refund");
					$notification->send($data);
				}
				$db->where("orderId", $orderId);
				$db->update(TBL_SP_ORDERS, array("status", "cancelled"));
				$db->where("orderId", $orderId);
				$db->update(TBL_FULFILLMENT, array("fulfillmentStatus", "cancelled"));
				$db->commit();
				echo json_encode(array('type' => 'success'));
			} catch (Exception $e) {
				echo json_encode(array('type' => 'error'));
				$db->rollback();
			}
			break;


		case 'add_order_to_logistic_aggregator':
			$orderId = $_POST['orderId'];
			$db->where('orderId', $_POST['orderId']);
			$db->where('status', 'cancelled', '!=');
			$db->join(TBL_HSN_RATE . ' hsn', 'hsn.hsn_code=o.hsn');
			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId');
			$db->join(TBL_SP_ACCOUNTS_LOCATIONS . ' sal', 'sal.accountId=o.accountId');
			// $db->join(TBL_SP_LOGISTIC.' sl', 'sl.account_id=sa.account_id');
			$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_provider_id=sa.account_active_logistic_id');
			$db->orderBy('orderConfirmedDate', 'ASC');
			$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . ' o');
			$confirmed_orders = array();
			$invoiceNumberSet = false;
			if ($db->count > 1) {
				foreach ($orders as $order) {
					if (is_null($order->invoiceNumber) && !$invoiceNumberSet) {
						$invoiceNumber = get_invoice_number($order->accountId);
						$invoiceNumberSet = true;
					}

					if (is_null($order->orderConfirmedDate)/* && $order->orderItemId == $orderItemId*/) {
						$data = array(
							'status' => 'packing',
							'orderConfirmedDate' => $db->now(),
							'invoiceNumber' => $invoiceNumber,
							'invoiceDate' => date('Y-m-d', time()),
							'dispatchByDate' => (date('H:m', time()) < '12' ? date('Y-m-d H:i:s', strtotime('tomorrow 12 PM')) : date('Y-m-d H:i:s', strtotime('today 12 PM')))
						);
						$order->invoiceNumber = $invoiceNumber;
						$order->invoiceDate = date('Y-m-d', time());
						$db->where('orderId', $order->orderId);
						$db->where('status', 'cancelled', '!=');
						if ($db->update(TBL_SP_ORDERS, $data)) {
							$return = array("type" => "success", "message" => "Multi Item Order successfull marked as Confirmed.", 'is_multiQuantity' => true);
						} else {
							$return = array("type" => "error", "message" => "Unable to update multi item order status", "error" => $db->getLastError());
						}
						$order->orderConfirmedDate = $db->now();
						// $confirmed_orders[] = $order;
					}

					if (!is_null($order->orderConfirmedDate)) {
						$confirmed_orders[] = $order;
					} else if (count($confirmed_orders) != count($orders)) {
						echo json_encode($return);
						exit;
					}
				}
			} else {
				$confirmed_orders = $orders;
				if (is_null($confirmed_orders[0]->invoiceNumber))
					$invoiceNumber = get_invoice_number($confirmed_orders[0]->accountId);
				else
					$invoiceNumber = $confirmed_orders[0]->invoiceNumber;
				$data = array(
					'invoiceNumber' => $invoiceNumber,
					'invoiceDate' => date('Y-m-d', time()),
				);
				$db->where('orderId', $orderId);
				$db->where('status', 'cancelled', '!=');
				$db->update(TBL_SP_ORDERS, $data);
				$orders[0]->invoiceNumber = $invoiceNumber;
				$orders[0]->invoiceDate = date('Y-m-d', time());
			}

			if ($orders[0]->orderType == "pickup")
				return;

			if (count($confirmed_orders) == count($orders)) {
				$serviceable_pincode = $logisticPartner->check_courier_serviceability(json_decode($confirmed_orders[0]->pickupDetails)->zip, json_decode($confirmed_orders[0]->deliveryAddress)->zip, '0.2', true, '', $confirmed_orders[0]->lpOrderId, false, true);
				if (
					(isset($serviceable_pincode['error']) &&
						($serviceable_pincode['error']['message'] == "Delivery pincode not serviceable" ||
							strpos($serviceable_pincode['error']['message'], "not serviceable") !== FALSE ||
							strpos($serviceable_pincode['error']['message'], "No courier service available") !== FALSE ||
							strpos($serviceable_pincode['error']['message'], "Service not available") !== FALSE ||
							strpos($serviceable_pincode['error']['message'], "courier not available") !== FALSE ||
							strpos($serviceable_pincode['error']['message'], "serviceable courier not found") !== FALSE ||
							strpos($serviceable_pincode['error']['message'], "Postcode not found") !== FALSE
						)
					)
				) {
					if ($confirmed_orders[0]->paymentType == "prepaid") { // MARK ORDER AS SELF SHIP
						$db->where('orderId', $confirmed_orders[0]->orderId);
						$db->where('status', 'cancelled', '!=');
						$db->update(TBL_SP_ORDERS, array('enableSelfShip' => '1', 'orderType' => 'SelfShip'));
						echo json_encode(array('type' => 'info', 'message' => 'Delivery pincode not serviceable. Order Moved to SelfShip.', 'enableSelfShip' => true));
					} else {
						echo json_encode(array('type' => 'error', 'message' => 'Delivery pincode not serviceable for this COD.'));
					}
				} else {
					$lpResponse = $logisticPartner->createOrder($confirmed_orders);
					if ($lpResponse['type'] === "success") {
						$lpResponse['channel'] = 'shopify';
						$label = $logisticPartner->generateLabel($lpResponse);
						if ($label['type'] == "success") {
							// UPDATE SHIPPING STATUS ON SHOPIFY WITH TRACKING ID AND TRACKING URL
							$tracking_details = array(
								"tracking_number" => $label['data']['awb_code'],
								"tracking_company" => $label['data']['courier_name'],
								"tracking_url" => $label['data']['tracking_url'],
								"locationId" => $lpResponse['locationId']
							);
							$sp = new shopify_dashboard($account);
							$update = $sp->fulfill_order_sp($confirmed_orders[0]->orderId, $tracking_details, true);
							if ($update['status'] == "success" || $update['errorMessage'] == "base - Line items are already fulfilled") {
								$return = array('type' => 'success', 'message' => 'Successfully added order to Shipping Queue');
							} else {
								$return = array('type' => 'error', 'message' => 'Unable to update status on Shopify.', 'error' => $update);
							}
						} else {
							$return = $label;
						}
						echo json_encode($return);
					} else if ($lpResponse['type'] === "info" && $lpResponse['message'] === "AWB already assigned to order") {
						$lpResponse['channel'] = 'shopify';
						$label = $logisticPartner->generateLabel($lpResponse['error']);
						$tracking_details = array(
							"tracking_number" => $label['data']['awb_code'],
							"tracking_company" => $label['data']['courier_name'],
							"tracking_url" => $label['data']['tracking_url'],
							"locationId" => $lpResponse['error']['locationId']
						);
						$return = array('type' => 'success', 'message' => 'Successfully added order to Shipping Queue');
						$sp = new shopify_dashboard($account);
						$update = $sp->fulfill_order_sp($confirmed_orders[0]->orderId, $tracking_details, true);
						echo json_encode($return);
					} else {
						// IF RESPONSE IS "Pickup pincode not serviceable" enableSelfShip
						echo json_encode($lpResponse);
					}
				}
			}
			break;

		case 'generate_label':
			$orderID = $_REQUEST['orderId'];
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
			$db->where('o.orderId', $orderID);
			$db->where('status', 'cancelled', '!=');
			$order = $db->getOne(TBL_SP_ORDERS . ' o', array('JSON_UNQUOTE(JSON_EXTRACT(pickupDetails, "$.zip")) as from_pincode, JSON_UNQUOTE(JSON_EXTRACT(deliveryAddress, "$.zip")) as billing_pincode', 'locationId', 'lpOrderId', 'lpShipmentId', 'o.orderId as order_id', 'orderNumberFormated', 'lpProvider', 'trackingId', '0.200 as weight'));
			// GENERATE LABEL AND DOWNLOAD
			$label = $logisticPartner->generateLabel($order);
			if ($label['type'] == "success") {
				// UPDATE SHIPPING STATUS ON SHOPIFY WITH TRACKING ID AND TRACKING URL
				$tracking_details = array(
					"tracking_number" => $label['data']['awb_code'],
					"tracking_company" => $label['data']['courier_name'],
					"tracking_url" => $label['data']['tracking_url'],
					"locationId" => $order['locationId']
				);
				$sp = new shopify_dashboard($account);
				$sp_update = false;
				if (!is_null($order['trackingId']))
					$sp_update = true;
				$update = $sp->fulfill_order_sp($orderID, $tracking_details, $sp_update);
				if ((isset($update['status']) && $update['status'] == "success") || (isset($update['errorMessage']) && $update['errorMessage'] == "base - Line items are already fulfilled")) {
					$return = array('type' => 'success', 'message' => 'Successfully added order to Shipping Queue');
				} else {
					$return = array('type' => 'error', 'message' => 'Unable to update status on Shopify.', 'error' => $update);
				}
			} else {
				$return = $label;
			}
			echo json_encode($return);
			break;

		case 'generate_selfship_label':
			$orderId = $_REQUEST['orderId'];
			$accountId = $_REQUEST['accountId'];
			$response = create_label($orderId);
			echo json_encode($response);
			break;

		case 'get_labels':
			$account = get_current_account($_REQUEST['account']);
			$shipmentIds = array_unique(explode(',', $_REQUEST['shipmentIds']));

			$db->where('o.orderId', $shipmentIds, 'IN');
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
			$db->join(TBL_SP_LOGISTIC . ' l', 'l.lp_provider_id=f.lpProvider');
			$db->orderBy('logistic', 'ASC');
			$db->orderBy('sku', 'ASC');
			$db->groupBy('o.orderId');
			$orders = $db->get(TBL_SP_ORDERS . ' o', NULL, array('o.orderId', 'f.logistic', 'o.sku', 'o.orderType', 'l.lp_provider_name'));
			$path = ROOT_PATH . '/labels/';
			$single_label_path = $path . 'single/';
			$filename = 'Shopify-Labels-' . date('d') . '-' . date('M') . '-' . date('Y') . '-' . str_replace(' ', '', $account->account_name);

			$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => [101.5, 147]]);
			// $mpdf = new \Mpdf\Mpdf();
			foreach ($orders as $order) {
				$mpdf->AddPage();
				$shipping_label = $single_label_path . 'FullLabel-Shopify-' . $order['orderId'] . '.pdf';
				$mpdf->setSourceFile($shipping_label);
				$tplId = $mpdf->ImportPage(1);
				$mpdf->UseTemplate($tplId, 0, 0, 101.5, 149);
				if (strtolower($order['lp_provider_name']) == "shiprocket") {
					$mpdf->SetLineWidth(7);
					$mpdf->SetDrawColor(255, 255, 255);
					$mpdf->Line(80, 138, 95.5, 138);
				}
			}
			$mpdf->SetTitle($filename);
			$mpdf->SetDisplayMode('fullpage');
			$mpdf->SetCompression(true);
			$mpdf->mirrorMargins = true;
			$mpdf->Output($filename . '.pdf', 'I');
			break;

		case 'generate_packing_list':
			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId');
			$db->where('status', 'packing');
			$db->groupBy('sku');
			$db->orderBy('sku', ASC);
			$orders = $db->get(TBL_SP_ORDERS . ' o', NULL, 'sku, SUM(quantity) as quantity, sa.account_name');
			$packing_list = array();
			foreach ($orders as $order) {
				$packing_list[$order['account_name']][$order['sku']] = $order['quantity'];
			}

			$table_content = "<style>table {font-family: arial, sans-serif;border-collapse: collapse;font-size:12px;margin:0 auto;}td, th {border: 1px solid #000;text-align: left;padding: 3px;}th {text-align:center;}tr:nth-child(even) {background-color: #dddddd;}</style>";
			$table_content .= '<center>PICK LIST - ' . date("d M, Y - H:i", time()) . '</center><br />';
			foreach ($packing_list as $account => $products) {
				$total_qty = 0;
				$table_content .= '<table><tr><td colspan="2"><strong><center>' . $account . '</center></strong></td></tr><tr><th>Sku</th><th width="100">Qty</th></tr>';
				foreach ($products as $product => $qty) {
					$table_content .= '<tr><td>' . $product . '</td><td><center>' . $qty . '</center></td></tr>';
					$total_qty += $qty;
				}
				$table_content .= "<tr><td><b>Total Quanity</b></td><td><b><center>" . $total_qty . "</center></b></td></tr></table>";
			}

			echo $table_content;
			break;

			// COMMENTS
		case 'get_comments':
			echo json_encode(get_comments($_REQUEST['orderId'], false));
			break;

		case 'view_comments':
			$db->where('party_id', 0);
			$users = $db->objectBuilder()->get(TBL_USERS, NULL, 'userID, display_name');

			$orderId = $_GET['orderId'];
			$db->where('orderId', $orderId);
			$db->orderBy('createdDate', 'DESC');
			$comments = $db->objectBuilder()->get(TBL_SP_COMMENTS);
			if ($comments) {
				$response = array();
				$assuredDelivery = false;
				$tag = "";
				foreach ($comments as $comment) {
					$data['comment'] = str_replace('\r\n', '<br />', $comment->comment);
					$data['orderItemId'] = $comment->orderId;
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
			$orderId = $_POST['orderId'];
			$commentAdditional = '';

			// $ndrApprovedReason = isset($_POST['ndr_approved_reason']) ? $_POST['ndr_approved_reason'] : NULL;

			$comment_data = array(
				'comment' => $db->escape($comment),
				'orderId' => $orderId,
				'commentFor' => strtolower($commentFor),
				'userId' => $current_user['userID'],
			);

			if (!empty($commentAdditional))
				$comment_data['commentAdditional'] = $commentAdditional;

			$message = "";
			if ($commentFor == "ndr_approved") {
				// $data['shipmentStatus'] = 'undelivered';
				$data['isReturnApproved'] = true;
				$data['isFlagged'] = false;
				$db->where('orderId', $orderId);
				$db->where('status', 'cancelled', '!=');
				$db->update(TBL_SP_ORDERS, $data);

				// $update_data = array(
				// 	'r_reason' => 'undelivered',
				// 	'r_subReason' => $ndrApprovedReason,
				// 	'r_comments' => $comment
				// );

				// $db->where('orderId', $orderId);
				// $db->where('status', 'cancelled', '!=');
				// $db->join(TBL_SP_ORDERS.' o', 'o.orderItemId=r.orderItemId');
				// $db->update(TBL_SP_RETURNS.' r', $update_data);
			}

			if ($commentFor == 'assured_delivery') {
				$db->where('orderId', $orderId);
				$db->update(TBL_SP_ORDERS, array('isAssuredDelivery' => 1));
			}

			if ($db->insert(TBL_SP_COMMENTS, $comment_data))
				echo json_encode(array('type' => 'success', 'message' => 'Comment Successfully added' . $message));
			else
				echo json_encode(array('type' => 'error', 'message' => 'Unable to add comment', 'error' => $db->getLastError()));
			break;

		case 'get_comments_v1':
			echo json_encode(get_comments_v1($orders[0]->orderId, false));
			break;

		case 'add_comment_v1':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			sleep(2);
			echo json_encode(array('type' => 'success', 'message' => 'Comment Successfully added' . $message));
			return;
			$comment = $_POST['comment'];
			$comment_for = $_POST['comment_for'];
			$orderId = $_POST['orderId'];
			$orderItemId = $_POST['orderItemId'];
			$assuredDelivery = isset($_POST['assured_delivery']) ? $_POST['assured_delivery'] : NULL;
			$ndrApprovedReason = isset($_POST['ndr_approved_reason']) ? $_POST['ndr_approved_reason'] : NULL;

			$comment_data = array(
				'comment' => $db->escape($comment),
				'orderItemId' => $orderItemId,
				'comment_for' => strtolower($comment_for),
				'created_on' => date('c'),
				'created_by' => $current_user['userID'],
				'assured_delivery' => $assuredDelivery,
				'ndr_approved_reason' => $ndrApprovedReason
			);

			if ($assuredDelivery)
				$data['isAssuredDelivery'] = $assuredDelivery;

			$message = "";
			if ($comment_for == "NDR_Approved") {
				$data['shipmentStatus'] = 'undelivered-rto';
				$data['isReturnApproved'] = true;
				$data['isFlagged'] = false;
				$message = ' & NDR Approved';

				$update_data = array(
					'r_reason' => 'undelivered',
					'r_subReason' => $ndrApprovedReason,
					'r_comments' => $comment
				);
				$db->where('orderItemId', $orderItemId);
				$db->update(TBL_SP_RETURNS, $update_data);
			}

			$db->where('orderId', $orderId);
			$current_comments = json_decode($db->getValue(TBL_SP_ORDERS, 'comments'));
			if ($current_comments)
				$comments = array_merge($current_comments, array($comment_data));
			else
				$comments = array($comment_data);

			$data['comments'] = json_encode($comments);
			$db->where('orderId', $orderId);
			if ($db->update(TBL_SP_ORDERS, $data))
				echo json_encode(array('type' => 'success', 'message' => 'Comment Successfully added' . $message));
			else
				echo json_encode(array('type' => 'error', 'message' => 'Unable to add comment'));
			break;

		case 'get_customer_orders':
			$customerId = $db->subQuery();
			$customerId->where('orderId', $_GET['orderId']);
			$customerId->get(TBL_SP_ORDERS, null, "customerId");

			$db->where('customerId', $customerId);
			$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'INNER');
			$db->orderBy('orderDate', 'DESC');
			$orders = $db->get(TBL_SP_ORDERS . ' o', NULL, array('o.orderId, COALESCE(status, "-") as status, COALESCE(spStatus, "-") as spStatus, COALESCE(f.shipmentStatus, "-") as shipmentStatus, orderNumberFormated, r_returnType, COALESCE(r_status, "-") as r_status, account_store_url'));
			if (count($orders) > 1)
				echo json_encode(array('type' => 'success', 'data' => $orders));
			else
				echo json_encode(array('type' => 'success', 'data' => ''));
			break;

		case 'get_address':
			sleep(4);
			$orderId = $_GET['orderId'];
			$db->where('orderId', $orderId);
			$delivery_address = $db->getValue(TBL_SP_ORDERS, 'deliveryAddress');
			echo json_encode(array('type' => 'success', 'data' => json_decode($delivery_address, 1)));
			break;

		case 'update_address':
			$orderId = $_REQUEST['orderId'];
			$name = $_POST['name'];
			$address1 = $_POST['address1'];
			$address2 = $_POST['address2'];
			$city = $_POST['city'];
			$province = $_POST['province'];
			$zip = $_POST['zip'];
			$phone = $_POST['phone'];
			$sp = new shopify_dashboard($account);
			$updateInfo = array(
				"shipping_address" => array(
					"address1" => $address1,
					"address2" => $address2,
					"city" => $city,
					"province" => $province,
					"zip" => $zip,
					"phone" => $phone
				)
			);
			$update = $sp->update_order_sp($orderId, $updateInfo);
			$result = array_diff(array_intersect($update['shipping_address'], $updateInfo['shipping_address']), $updateInfo['shipping_address']);
			if (empty($result)) {
				$db->where('orderId', $orderId);
				$delivery_address = json_decode($db->getValue(TBL_SP_ORDERS, 'deliveryAddress'));
				$delivery_address->name = $name;
				$delivery_address->address1 = $address1;
				$delivery_address->address2 = $address2;
				$delivery_address->city = $city;
				$delivery_address->province = $province;
				$delivery_address->zip = $zip;
				$delivery_address->phone = $phone;
				$db->where('orderId', $orderId);
				$db->update(TBL_SP_ORDERS, array('deliveryAddress' => json_encode($delivery_address)));
				echo json_encode(array('type' => 'success', 'message' => 'Successfully updated address'));
			} else
				echo json_encode(array('type' => 'error', 'message' => 'Unable to updated address'));
			break;

		case 'get_pincode_details':
			$pincode = $_GET['pincode'];
			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_URL => 'http://postalpincode.in/api/pincode/' . $pincode,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
			));

			$pin_response = curl_exec($curl);
			curl_close($curl);

			$pin_response = json_decode($pin_response, 1);
			$city = "";
			$state = "";
			if ($pin_response) {
				if ($pin_response['Message'] != "No records found") {
					$city = $pin_response['PostOffice'][0]['District'];
					$state = $pin_response['PostOffice'][0]['State'];

					$return = array(
						'type' => 'success',
						'data' => array(
							'city' => ucfirst(strtolower($city)),
							'state' => ucfirst(strtolower($state))
						),
					);
				} else {
					$return = array(
						'type' => 'success',
						'error' => $pin_response['Message']
					);
				}
			} else {
				$return = array(
					'type' => 'error',
					'message' => 'Unable to fetch pincode details'
				);
			}

			echo json_encode($return);
			break;

		case 'set_flag':
			$orderItemId = $_POST['orderItemId'];
			$flag = $_POST['flag'];
			$data = array('isFlagged' => filter_var($flag, FILTER_VALIDATE_BOOLEAN));
			$db->where('orderItemId', $orderItemId);
			if ($db->update(TBL_SP_ORDERS, $data)) {
				$return = "success";
			} else {
				$return = "error";
			}
			echo json_encode(array("type" => $return, "flag" => $flag));
			break;

		case 'get_order_details':
			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'INNER');
			$db->join(TBL_SP_ACCOUNTS_LOCATIONS . ' sl', 'sl.locationId=o.locationId', 'LEFT');
			$db->joinWhere(TBL_SP_ACCOUNTS_LOCATIONS . ' sl', 'sl.accountId=o.accountId');
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
			$db->join(TBL_PRODUCTS_CATEGORY . " c", "c.catid=pm.category", "LEFT");
			$db->join(TBL_REGISTERED_WARRANTY . " w", "w.order_id=o.orderNumberFormated", "LEFT");
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
			$db->where('orderNumber', $_REQUEST['ordernumber']);
			$db->where('o.accountId', $_REQUEST['accountId']);
			$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . ' o', NULL, '"Shopify" as marketplace, o.orderId, o.productId, o.orderItemId, o.accountId, sa.account_name as accountName, sl.locationName as location, o.orderDate, o.quantity, o.title, o.sku, o.deliveryAddress, o.customerPrice, o.sellingPrice, o.invoiceNumber, o.invoiceDate, o.invoiceAmount, f.deliveredDate, o.uid as uids, o.variantId, o.returnGrace, c.catid, c.categoryName, 0 as cid, 6 as warrantyPeriod, if(isnull(w.insertDate), 0, 1) as warrantyRegistred');

			if ($orders) {
				echo json_encode(array("type" => "success", "data" => $orders));
			} else {
				echo json_encode(array("type" => "error", "msg" => "No order details found."));
			}
			break;

			// RETURN/REPLACE REQUEST
		case 'create_refund_replace':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			// $_POST['qc_enable'] = "1";
			$db->startTransaction();
			$uids = explode(',', $_POST['uids'][0]);
			$order_data = array(
				"customerId" => $_POST['customerId'],
				"lineItems" => array()
			);
			// $replacement_uids = array();
			foreach ($uids as $value) {
				// $replacementOrderId = null;
				// $has_replacement = false;
				// if($_POST[$value]['return_replacement'] === 'replace') {//if customer repleacement order
				// 	$db->where('orderItemId', $_POST[$value]['orderItemId']);
				// 	$order = $db->objectBuilder()->getOne(TBL_SP_ORDERS.' o', 'o.*');
				// 	$has_replacement = true;
				// 	$replacement_uids[$value] = "";

				// 	$order_data["billingAddress"] = json_decode($order->billingAddress);
				// 	$order_data["deliveryAddress"] = json_decode($order->deliveryAddress);

				// 	$key = array_search($order->variantId, array_column($order_data['lineItems'], 'variant_id'));
				// 	if ($key !== false){
				// 		$order_data['lineItems'][$key]['quantity'] += 1;
				// 	} else {
				// 		$order_data['lineItems'][] = array(
				// 			"variant_id" => $order->variantId,
				// 			"quantity" => 1
				// 		);
				// 	}
				// }

				$pickup_comment = $_POST['pickup_type'] == "self_ship" ? '\r\nPickup: Self Ship' : '';
				$comment_data = array(
					'comment' => ucwords($_POST[$value]['return_replacement']) . ' Order Created \r\nSKU:' . $_POST[$value]['sku'] . $pickup_comment,
					'orderId' => $_POST['orderId'],
					'commentFor' => 'return_initiated',
					'userId' => $current_user['userID'],
				);
				$db->insert(TBL_SP_COMMENTS, $comment_data);


				$return_data = array(
					'orderItemId'          =>  $_POST[$value]['orderItemId'],
					'r_source'             =>  'customer_return',
					'r_status'             =>  'start',
					'r_quantity'           =>  1,
					'r_productId'          =>  $_POST[$value]['productId'],
					'r_sku'                =>  $_POST[$value]['sku'],
					'r_returnType'         =>  $_POST[$value]['return_replacement'],
					'r_reason'             =>  $_POST[$value]['replacement_reason_type'] . ' - ' . $_POST[$value]['replacement_reason'],
					'r_subReason'          =>  $_POST[$value]['replacement_subreason'],
					'r_uid'                =>  json_encode(array($value)),
					'r_replacementOrderId' =>  NULL,
					'r_createdDate'        =>  date('Y-m-d H:i:s'),
					'insertDate'           =>  date('Y-m-d H:i:s'),
					'insertBy'             =>  $current_user['userID'],
					'lastUpdateAt'         =>  date('Y-m-d H:i:s'),
				);

				$return_id = $db->insert(TBL_SP_RETURNS, $return_data);
				$return_ids[] = $return_id;
				// if ($has_replacement)
				// 	$replacement_uids[$value] = $return_id;
			}

			// if($replacement_uids) {//if customer repleacement order
			// 	$sp = new shopify_dashboard($account);
			// 	$replacementOrderId = $sp->create_replacement_order_sp((object)$order_data);

			// 	$db->where('returnId', $return_ids, 'IN');
			// 	$db->where('r_returnType', 'replace');
			// 	$db->update(TBL_SP_RETURNS, array('r_replacementOrderId' => $replacementOrderId));
			// }

			if (!$return_ids) {
				echo json_encode(array("type" => "error", "message" => "Error creating Refund/Replacemnet order"));
				exit;
			}

			$db->join(TBL_SP_ORDERS . ' o', 'o.orderItemId=r.orderItemId');
			$db->join(TBL_SP_ACCOUNTS . ' a', 'a.account_id=o.accountId');
			$db->where('(r_source = ? AND (r_status = ? OR r_status = ? ))', array('customer_return', 'start', 'awb_assigned'));
			// $db->where('o.orderId', $_POST['orderId']);
			$db->where('returnId', $return_ids, 'IN');

			$returns = $db->objectBuilder()->get(TBL_SP_RETURNS . ' r', NULL, 'orderId, returnId, r.orderItemId, a.account_active_whatsapp_id, orderNumberFormated, sku, title, r_quantity, sellingPrice, hsn, account_return_address, r_uid, r_subReason');
			if ($returns) {
				if ($_POST['pickup_type'] == "free_pickup") {
					$return_data = new stdClass();
					$return_data->orderId = $returns[0]->orderId;
					$return_data->returnId = $returns[0]->returnId;
					$return_data->orderItemId = $returns[0]->orderItemId;
					$return_data->orderNumberFormated = $returns[0]->orderNumberFormated;
					$return_data->pickupAddress = (object)$_POST['return'];
					$return_data->pickupAddress->phone = $_POST['whatsapp_number'];
					$return_data->pickupAddress->email = $_POST['email_address'];
					$return_data->returnAddress = json_decode($returns[0]->account_return_address);
					$return_data->marketplace = "shopify";

					// CREATE A SINGLE SHIPMENT WITH ALL ITEMS
					$return_data->order_items = array();
					$subtotal = 0;
					$return_data->qcEnable = filter_var($_POST['qc_enable'], FILTER_VALIDATE_BOOLEAN) ? "1" : "0";
					foreach ($returns as $return) {
						$key = array_search($return->sku, array_column((array)$return_data->order_items, 'sku'));
						if ($key !== false) {
							$subtotal += $return->sellingPrice;
							$return_data->order_items[$key]['units'] += $return->r_quantity;
							$return_data->order_items[$key]["selling_price"] += ($return->sellingPrice * $return->r_quantity);

							if ($return_data->qcEnable)
								$return_data->order_items[$key]["qc_serial_no"] .= ', ' . json_decode($return->r_uid)[0];
						} else {
							$line_item = array(
								"sku" => $return->sku,
								"name" => $return->title,
								"units" => $return->r_quantity,
								"selling_price" => ($return->sellingPrice * $return->r_quantity),
								"discount" => 0,
								"hsn" => isset($return->hsn) ? $return->hsn : '0',
								"qc_enable" => "0",
							);
							$subtotal += ($return->sellingPrice * $return->r_quantity);

							if ($return_data->qcEnable) {
								$line_item["qc_enable"] = "1";
								$line_item["qc_brand"] = $return_data->returnAddress->brand;
								$line_item["qc_serial_no"] = json_decode($return->r_uid)[0];
								// "qc_product_image" => "https://s3-ap-southeast-1.amazonaws.com/kr-multichannel/1636713733zxjaV.png" // TODO: ADD FULL IMAGE COLUMN TO THE PRODUCT MASTER TABLE AND UPLOAD ALL THE HD IMAGES
							}
							$return_data->order_items[] = $line_item;
						}
					}
					$return_data->discount = 0;
					$return_data->sub_total = $subtotal;

					$response = $logisticPartner->createReturn($return_data, true);
				} else { // SELF SHIP
					$fulfilment_data = array(
						'fulfillmentStatus'  => 'start',
						'fulfillmentType'    => 'return',
						'orderId'            => $returns[0]->orderId,
						'channel'            => 'shopify',
						'lpOrderId'          => null,
						'lpShipmentId'       => null,
						'lpManifestId'       => null,
						'lpProvider'         => 5,
						'lpShipmentDetails'  => null,
						'trackingId'         => null,
						'logistic'           => null,
						'shipmentStatus'     => 'awaiting_tracking_details',
						'shippingZone'       => null,
						'shippingSlab'       => null,
						'shippingFee'        => null,
						'codFees'            => 0,
						'rtoShippingFee'     => null,
						'createdBy'          => $current_user['userID'],
						'canceledBy'         => null,
						'cancellationReason' => null,
						'createdDate'        => date('Y-m-d H:i:s'),
						'expectedPickupDate' => $tracking_response['data']['expectedPickupDate'],
						'expectedDate'       => date('Y-m-d H:i:s', strtotime('+ 10 days')),
						'deliveredDate'      => null,
						'cancelledDate'      => null,
					);
					$response = array(
						'type' => 'error'
					);
					if ($db->insert(TBL_FULFILLMENT, $fulfilment_data))
						$response['type'] = 'success';
				}

				if ($response['type'] == 'error') {
					echo json_encode(array("type" => "error", "message" => $response['message']));
					$db->rollback();
				} else {
					if ($_POST['pickup_type'] == "free_pickup") {
						$db->where('returnId', $return_ids, 'IN');
						$db->update(TBL_SP_RETURNS, array('r_status' => 'awb_assigned'));
					}
					$db->commit();
					$data = array();
					$data["idenifierType"] = "shopify_return";
					$data["idenifierValue"] = $returns[0]->orderId;
					$data["medium"] = $_REQUEST['share_update'];
					$data["active_whatsapp_id"] = $returns[0]["account_active_whatsapp_id"];
					$data["active_email_id"] = $returns[0]["account_active_email_id"];
					$data["templateId"] = get_template_id_by_name("shopify_return");
					$notification = new Notification();
					$notification->send($data);
					echo json_encode(array("type" => "success", "message" => "Refund/Replacemnet created successfully"));
				}
			} else {
				echo json_encode(array("type" => "error", "message" => "Return order not found"));
			}
			break;

			// SELF SHIP
		case 'update_to_self_ship':
			$orderId = $_REQUEST['orderId'];
			$db->where('orderId', $orderId);
			if ($db->update(TBL_SP_ORDERS, array('orderType' => 'SelfShip', 'status' => 'packing'))) {
				// CREATE SELFSHIP LABEL
				$return = array('type' => 'success', 'message' => 'Successfully updated order to SelfShip');
			} else {
				$return = array('type' => 'error', 'message' => 'Unable to update order to SelfShip.', 'error' => $db->getLastError());
			}
			break;

		case 'update_self_ship_order_status':
			$orderId = $_REQUEST['orderId'];
			$status = $_REQUEST['status'];
			$db->where('orderId', $orderId);
			if ($db->update(TBL_SP_ORDERS, array('shipmentStatus' => $status))) {
				$return = array('type' => 'success', 'message' => 'Successfully updated order status');
			} else {
				$return = array('type' => 'error', 'message' => 'Unable to update order status.', 'error' => $db->getLastError());
			}
			break;

			// SELF PICK
		case 'update_to_self_pickup':
			$orderId = $_REQUEST['orderId'];
			$db->where('orderId', $orderId);
			if ($db->update(TBL_SP_ORDERS, array('orderType' => 'pickup'))) {
				// CREATE SELFSHIP LABEL
				$return = array('type' => 'success', 'message' => 'Successfully updated order to Pickup');
			} else {
				$return = array('type' => 'error', 'message' => 'Unable to update order to Pickup.', 'error' => $db->getLastError());
			}
			echo json_encode($return);
			break;

			// SELF RETURN
		case 'convert_to_self_return':
			$db->startTransaction();
			$fulfilment_data = array(
				'fulfillmentStatus'  => 'start',
				'fulfillmentType'    => 'return',
				'orderId'            => $_REQUEST["orderId"],
				'channel'            => 'shopify',
				'lpOrderId'          => null,
				'lpShipmentId'       => null,
				'lpManifestId'       => null,
				'lpProvider'         => 5,
				'lpShipmentDetails'  => null,
				'trackingId'         => null,
				'logistic'           => null,
				'shipmentStatus'     => 'awaiting_tracking_details',
				'shippingZone'       => null,
				'shippingSlab'       => null,
				'shippingFee'        => null,
				'codFees'            => 0,
				'rtoShippingFee'     => null,
				'createdBy'          => $current_user['userID'],
				'canceledBy'         => null,
				'cancellationReason' => null,
				'createdDate'        => date('Y-m-d H:i:s'),
				'expectedPickupDate' => $tracking_response['data']['expectedPickupDate'],
				'expectedDate'       => date('Y-m-d H:i:s', strtotime('+ 10 days')),
				'deliveredDate'      => null,
				'cancelledDate'      => null,
			);
			$response = array(
				'type' => 'error'
			);
			if ($db->insert(TBL_FULFILLMENT, $fulfilment_data)) {

				$db->where("fulfillmentId", $_REQUEST["fulfillmentId"]);
				$updateDetails = array("fulfillmentStatus" => "cancelled", "shipmentStatus" => "cancelled", "canceledBy" => $current_user["userId"], "cancelledDate" => date("Y-m-d H:i:s"), "cancellationReason" => "Self Return");
				if ($db->update(TBL_FULFILLMENT, $updateDetails))
					$response['type'] = 'success';
			}

			if ($response['type'] == 'error') {
				echo json_encode(array("type" => "error", "message" => $response['message']));
				$db->rollback();
			} else {

				$db->commit();
				// foreach ($_REQUEST['share_update'] as $key => $medium){
				// if ($key == "whatsapp") {
				$data = array();
				$data["idenifierType"] = "shopify_self_return";
				$data["idenifierValue"] = $returns[0]->orderId;
				$data["medium"] = ["whatsapp"];
				$data["active_whatsapp_id"] = $returns[0]["account_active_whatsapp_id"];
				$data["templateId"] = 8;
				$notification = new Notification();
				$notification->send($data);
				// }
				// 	} elseif ($key == "whatsapp") {
				// 		$data = notification::send('whatsapp', 'customer_return', $account->firm_id, $_REQUEST, $account->account_active_whatsapp_id);
				// 	} elseif ($key == "email") {
				// 		$data = notification::send('email', 'customer_return', $account->firm_id, $_REQUEST, $account->account_active_email_id);
				// 	}
				// }
				echo json_encode(array("type" => "success", "message" => "Refund/Replacemnet created successfully"));
			}
			break;

			// SHIPROCKET ONLY. TRIGGERED AFTER SHIP SCAN SUCCESS
		case 'schedule_pickup':
			$shipment_ids = ($_REQUEST['type'] == "single") ? array($_REQUEST['shipmentIds']) : json_decode($_REQUEST['shipmentIds'], 1);

			if (empty($shipment_ids)) {
				echo json_encode(array("type" => "error", "message" => "No shipments to schedule."));
				return;
			}

			// foreach($shipment_ids as $shipments){
			$response = $logisticPartner->requestPickup($shipment_ids);
			$log->write(json_encode($response), 'shiprocket-pickup-request');
			if ((isset($response['pickup_status']) && $response['pickup_status'] === (int)"1") || isset($response['message']) && $response['message'] == "Already in Pickup Queue.") {
				echo json_encode(array("type" => "success", "message" => "Successfully scheduled pickup"));
			} else {
				echo json_encode(array("type" => "error", "message" => "Unable to schedule pickup", "error" => $response));
			}
			// }
			break;

		case 'get_manifest':
			$account_shipments = json_decode($_POST['shipmentIds'], 1);
			$manifest = $logisticPartner->generateManifest($account_shipments);
			if ($manifest['type'] == "success") {
				$manifest_pdf = file_get_contents($manifest['manifest_url']);
				if (strncmp($manifest_pdf, "%PDF-", 4) === 0) {
					$path = ROOT_PATH . '/labels/manifest/';
					$filename = 'Manifest-' . date('Ymd') . '.pdf';
					$file = $path . $filename;
					if (file_put_contents($file, $manifest_pdf)) {
						header('Content-Description: File Transfer');
						header('Content-Type: application/octet-stream');
						header('Content-Disposition: attachment; filename="' . basename($file) . '"');
						header('Expires: 0');
						header('Cache-Control: must-revalidate');
						header('Pragma: public');
						header('Content-Length: ' . filesize($file));
						flush(); // Flush system output buffer
						readfile($file);
						die();
					} else {
						exit('Cannot save file!');
					}
				} else {
					exit('Invalid file!');
				}
			} else if ($manifest['type'] == "info" && $manifest['message'] == "Manifest already generated for requested orders.") {
				$path = ROOT_PATH . '/labels/manifest/';
				$filename = 'Manifest-' . date('Ymd') . '.pdf';
				$file = $path . $filename;
				if (file_exists($file)) {
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="' . basename($file) . '"');
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . filesize($file));
					flush(); // Flush system output buffer
					readfile($file);
					die();
				} else {
					exit('Cannot find manifest file!');
				}
			} else {
				exit($manifest['message'] . ' :: ' . json_encode($manifest['error']));
			}
			break;

		case 'mark_orders':
			switch ($_REQUEST['type']) {
				case 'approved':
					$orderId = $_POST['orderId'];
					$sp = new shopify_dashboard($account);
					$order_details = $sp->shopify->Order($orderId)->get();
					$update = $sp->update_order_sp($orderId, array('tags' => array_merge(array('Approved'), explode(', ', $order_details['tags']))));
					// if ($update['status'] == "success"){
					$data = array(
						'status' => 'packing',
						'orderConfirmedDate' => $db->now(),
						'dispatchByDate' => (date('H:m', strtotime($db->now())) < '12' ? date('Y-m-d H:i:s', strtotime('tomorrow 12 PM')) : date('Y-m-d H:i:s', strtotime('today 12 PM')))
					);
					$db->where('orderId', $orderId);
					if ($db->update(TBL_SP_ORDERS, $data)) {
						$return = array("type" => "success", "message" => "Order successfull fulfilled on Shopify");
					} else {
						$return = array("type" => "error", "message" => "Unable to update order status", "error" => $db->getLastError());
					}
					// } else {
					// 	$return = array("type" => "error", "message" => "Unable to update order tag on Shopify", "error" => $update);
					// }
					echo json_encode($return);
					break;

				case 'self_approved': // SELF SHIP
					$orderId = $_REQUEST['orderId'];
					$orderType = $_REQUEST['orderType'];
					// $orderItemId = $_POST['orderItemId'];
					$sp = new shopify_dashboard($account);
					$order_details = $sp->shopify->Order($orderId)->get();
					$update = $sp->update_order_sp($orderId, array('tags' => array_merge(array('Approved'), explode(', ', $order_details['tags']))));

					// CREATE LABEL
					if ($orderType == "self_ship")
						$response = create_label($orderId);
					else
						$response['type'] = "success";

					if ($response['type'] == "success") {
						$data = array(
							'status' => 'packing',
							'orderConfirmedDate' => $db->now(),
							'labelGeneratedDate' => $db->now(),
							'dispatchByDate' => (date('H:m', time()) < '12' ? date('Y-m-d H:i:s', strtotime('tomorrow 12 PM')) : date('Y-m-d H:i:s', strtotime('today 12 PM')))
						);
						$db->where('orderId', $orderId);
						if ($db->update(TBL_SP_ORDERS, $data)) {
							$fulfilment_data = array(
								'fulfillmentStatus'  => 'start',
								'fulfillmentType'    => 'forward',
								'orderId'            => $orderId,
								'channel'            => 'shopify',
								'lpOrderId'          => NULL,
								'lpShipmentId'       => NULL,
								'lpManifestId'       => NULL,
								'lpProvider'         => ($orderType == "self_ship" ? 4 : 5),
								'lpShipmentDetails'  => NULL,
								'trackingId'         => $orderId,
								'logistic'           => $orderType,
								'shipmentStatus'     => 'awb_assigned',
								'shippingZone'       => NULL,
								'shippingSlab'       => NULL,
								'shippingFee'        => NULL,
								'codFees'            => 0,
								'rtoShippingFee'     => NULL,
								'createdBy'          => $current_user['userID'],
								'canceledBy'         => NULL,
								'cancellationReason' => NULL,
								'createdDate'        => date('Y-m-d H:i:s'),
								'expectedPickupDate' => date('Y-m-d H:i:s'),
								'expectedDate'       => date('Y-m-d H:i:s', strtotime('+ 10 days')),
								'deliveredDate'      => null,
								'cancelledDate'      => null,
							);
							$db->insert(TBL_FULFILLMENT, $fulfilment_data);
							$return = array("type" => "success", "message" => "Order successfull approved.");
						} else {
							$return = array("type" => "error", "message" => "Unable to update order status", "error" => $db->getLastError());
						}
					} else {
						$return = array("type" => "error", "message" => "Unable to generate label");
					}
					echo json_encode($return);
					break;

				case 'self_shipped': // SELF SHIP
					// error_reporting(E_ALL);
					// ini_set('display_errors', '1');
					// echo '<pre>';
					$orderId = $_POST['orderId'];
					$orderItemId = $_POST['orderItemId'];
					$trackingId = $_POST['trackingId'];
					$isReturn = isset($_POST['isReturn']) ? (bool)$_POST['isReturn'] : false;
					$trackingLink = $_POST['trackingLink'];
					$logistic = $_POST['logistic'];
					$logisticProvider = $_POST['logisticProvider'];
					$pickedUpDate = $_POST['pickedUpDate'];

					// UPDATE SHIPPING STATUS ON SHOPIFY WITH TRACKING ID AND TRACKING URL
					if (!$isReturn) {
						$tracking_details = array(
							"tracking_number" => $trackingId,
							"tracking_company" => $logistic,
							"tracking_url" => $trackingLink
						);
						$sp = new shopify_dashboard($account);
						$update = $sp->fulfill_order_sp($orderId, $tracking_details, true);
						$status_update = $sp->update_fulfilment_status_sp($orderId, 'in_transit');
					}

					$data = array(
						// 'lpProviderId' => 0,
						'lpProvider' => $logisticProvider,
						'lpShipmentDetails' => json_encode(array("trackingLink" => $trackingLink)),
						'trackingId' => $trackingId,
						'logistic' => $logistic,
						'pickedUpDate' => $pickedUpDate,
						'shipmentStatus' => 'in_transit',
					);
					if (!$isReturn) {
						$data['expectedDate'] = date('Y-m-d 00:00:00', strtotime($pickedUpDate . ' + 7 days'));
						$db->where('fulfillmentType', 'forward');
					} else {
						$db->where('fulfillmentType', 'return');
					}
					$db->where('fulfillmentStatus', 'cancelled', '!=');
					$db->where('orderId', $orderId);
					if ($db->update(TBL_FULFILLMENT, $data)) {
						$return = array("type" => "success", "message" => "Order successfull fulfilled on Shopify");
					} else {
						$return = array("type" => "error", "message" => "Unable to update order status", "error" => $db->getLastError());
					}
					echo json_encode($return);
					break;

				case 'self_delivered': // SELF SHIP
					$orderId = $_POST['orderId'];
					$fulfillmentId = $_POST['fulfillmentId'];

					$sp = new shopify_dashboard($account);
					$update = $sp->update_fulfilment_status_sp($orderId, 'delivered');

					// SEND WHATSAPP PUSH NOTIFICATION WITH INVOICE
					$db->where('fulfillmentId', $fulfillmentId);
					if ($db->update(TBL_FULFILLMENT, array('shipmentStatus' => 'delivered', 'deliveredDate' => date('c')))) {
						$return = array("type" => "success", "message" => "Order successfull updated to delivered");
					} else {
						$return = array("type" => "error", "message" => "Unable to update order status", "error" => $db->getLastError());
					}

					echo json_encode($return);
					break;

				case 'rtd':
					$account_id = $_POST['account_id'];
					$tracking_id = $_POST['tracking_id'];
					$responses = update_orders_to_rtd($account_id, $tracking_id);

					$i = 0;
					$return = array();
					foreach ($responses as $response) {
						$return[$i]['type'] = "error";
						if ($response['type'] == "success") {
							$return[$i]['type'] = "success";
						}
						$title = ($response["title"] != "" ? $response["title"] : "Title Not Found");
						$order_item_id = ($response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
						$return[$i]['content'] = '
						<li class="order-item-card">
							<div class="order-title">' . $title . '</div>
							<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $tracking_id . '</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
						</li>';
						$i++;
					}

					echo json_encode($return);
					break;

				case 'delivered': // MANUAL VIA ORDER ACTION BUTTON
					$db->join(TBL_SP_ORDERS . ' o', 'f.orderId=o.orderId');
					$db->joinWhere(TBL_SP_ORDERS . ' o', 'o.orderId', $_POST['orderId']);
					$db->where('(f.trackingId = ? AND fulfillmentType = ? AND shipmentStatus != ?)', array($_POST['trackingId'], 'return', 'forward_shipment_delivered'));
					$fulfillmentOrder = $db->getOne(TBL_FULFILLMENT . ' f', 'fulfillmentId, orderItemId');
					if ($fulfillmentOrder && $fulfillmentOrder['fulfillmentId']) {
						// CANCEL THE RETURN FULFILMENT
						$update_data = array(
							'fulfillmentStatus' => 'cancelled',
							'shipmentStatus' => 'forward_shipment_delivered',
							'cancellationReason' => 'Forward Delivered - RTO Cancelled',
							'deliveredDate' => NULL,
							'cancelledDate' => $db->now()
						);
						$db->where('fulfillmentId', $fulfillmentOrder['fulfillmentId']);
						$db->update(TBL_FULFILLMENT, $update_data);

						// CANCEL RETURN
						$db->join(TBL_SP_ORDERS . ' o', 'o.orderItemId=r.orderItemId');
						$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
						$db->where('(f.shipmentStatus != ? OR f.shipmentStatus != ? OR f.shipmentStatus != ?)', array('return_received', 'return_claimed', 'return_completed'));
						$db->where('r.r_subReason', 'undelivered');
						$db->where('r.r_source', 'courier_return');
						$db->where('r.orderItemId', $fulfillmentOrder['orderItemId']); // USE IN AS THERE CAN BE MULTIPLE RETURNS FOR SAME ORDER
						if ($returnId = $db->getValue(TBL_SP_RETURNS . ' r', 'returnId')) {
							$update_details = array(
								'r_status' => 'cancelled',
								'r_reason' => 'forward_shipment_delivered',
								'r_subReason' => 'forward_shipment_delivered',
								'r_cancellationDate' => $db->now()
							);
							$db->where('returnId', $returnId);
							$db->update(TBL_SP_RETURNS, $update_details);
						}
					}

					// UPDATE FORWARD FULFILMENT STATUS
					$db->where('orderId', $_POST['orderId']);
					$db->where('(f.trackingId = ? AND fulfillmentType = ? AND shipmentStatus != ?)', array($_POST['trackingId'], 'forward', 'cancelled'));
					$update = $db->update(TBL_FULFILLMENT . ' f', array('shipmentStatus' => 'delivered', 'deliveredDate' => $_POST['delivered_date'] . ' ' . date('H:i:s')));
					if ($update) {
						// UPDATE STATUS ON SHOPIFY
						$sp = new shopify_dashboard($this->sp_account);
						$update = $sp->update_fulfilment_status_sp($order_details['orderId'], 'delivered');
						$log->write("Shipment Status: delivered\nCurrent Status: undelivered-rto\nUpdate Status: delivered", 'shopify-order-status');

						$return = array("type" => "success", "message" => "Order successfull updated to delivered");
					} else {
						$return = array("type" => "error", "message" => "Unable to update order status", "error" => $db->getLastError());
					}
					// var_dump($update);
					echo json_encode($return);
					break;

				case 'undelivered': // MANUAL VIA ORDER ACTION BUTTON
					$order_id = $_POST['orderId'];
					$db->join(TBL_SP_ORDERS . ' o', 'f.orderId=o.orderId');
					$db->joinWhere(TBL_SP_ORDERS . ' o', 'o.orderId', $_POST['orderId']);
					$db->where('(f.fulfillmentStatus = ? AND fulfillmentType = ? AND shipmentStatus != ?)', array('shipped', 'forward', 'delivered'));
					$fulfillmentOrder = $db->getOne(TBL_FULFILLMENT . ' f', 'o.orderNumberFormated, trackingId, lpOrderId, logistic');

					$sp = new shopify_dashboard($account);
					$sp->update_fulfilment_status_sp($order_id, 'failure');

					$data = new stdClass();
					$data->order_id = $fulfillmentOrder['orderNumberFormated']; // ORDER NUMBER FORMATED
					$data->awb = $fulfillmentOrder['trackingId'];
					$data->current_status = "RTO INITIATED";
					$data->current_timestamp = date("d m Y H:i:s"); // 11 09 2023 00:30:06
					$data->lpOrderId = $fulfillmentOrder['lpOrderId'];
					$data->logistic = $fulfillmentOrder['logistic'];
					$sp->insert_return($data, $data->logistic, 'push_notify');
					break;

					// case 'rto_approved':
					// 	$orderId = $_POST['orderId'];

					// 	$db->where('orderId', $orderId);
					// 	if ($db->update(TBL_SP_ORDERS, array('shipmentStatus' => 'undelivered-rto', 'isReturnApproved' => true))){
					// 		$return = array("type" => "success", "message" => "Order successfull updated to delivered");
					// 	} else {
					// 		$return = array("type" => "error", "message" => "Unable to update order status", "error" => $db->getLastError());
					// 	}

					// 	echo json_encode($return);
					// 	break;

				case 'return_received':
					$tracking_id = $_POST['tracking_id'];
					$db->startTransaction();
					$responses = update_returns_status($tracking_id, 'received');

					$i = 0;
					$return = array();
					foreach ($responses as $response) {
						$return[$i]['type'] = "error";
						if ($response['type'] == "success") {
							$return[$i]['type'] = "success";
						}
						$title = ($response["title"] != "" ? $response["title"] : "Title Not Found");
						$order_item_id = ($response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
						$return[$i]['content'] = '
						<li class="order-item-card">
							<div class="order-title">' . $title . '</div>
							<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $tracking_id . '</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
						</li>';
						$i++;
					}

					echo json_encode($return);
					break;

				case 'return_completed':
					// error_reporting(E_ALL);
					// ini_set('display_errors', '1');
					// echo '<pre>';
					$tracking_id = $_REQUEST['tracking_id'];
					$db->startTransaction();
					$responses = update_returns_status($tracking_id, 'completed');

					$i = 0;
					$return = array();
					foreach ($responses as $response) {
						$return[$i]['type'] = "error";
						if ($response['type'] == "success") {
							$return[$i]['type'] = "success";
						}
						$title = ($response["title"] != "" ? $response["title"] : "Title Not Found");
						$order_item_id = ($response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
						$return[$i]['content'] = '
						<li class="order-item-card">
							<div class="order-title">' . $title . '</div>
							<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $tracking_id . '</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
						</li>';
						$i++;
					}

					echo json_encode($return);
					break;
			}
			break;

		case 'reassign_courier':
			// GET ORDER STATUS [SHOULD BE RETURN PENDING]
			if ($_POST['same_pickup_address'] === "true") {
				$orderId = $_POST['lporderId'];
				$lpOrder = $logisticPartner->getOrder($orderId);

				if ($lpOrder['data']["status"] == "RETURN PENDING") {
					$address = json_decode($account->account_return_address);
					$pickup_order = array(
						'from_pincode' => $_POST['zip'],
						'to_pincode' => $address->zip,
						'weight' => '0.200',
						'lpShipmentId' => $_POST['shipmentId'],
						'lpOrderId' => $_POST['lporderId'],
					);

					$tracking_response = $logisticPartner->assign_return_tracking_id($pickup_order, 0);
					if ($tracking_response['type'] == "success") {
						// $response['data']['returnId'] = 'R-'.$order->orderNumberFormated;
						$tracking_response['message'] = 'Return successfully created and pickup generated';
						$shipmentDetails = $tracking_response['data']['shipmentDetails'];

						// Fulllfillment
						$fulfilment_data = array(
							'fulfillmentStatus'  => 'start',
							'fulfillmentType'    => 'return',
							'orderId'            => $_POST['orderId'],
							'channel'            => 'shopify',
							'lpOrderId'          => $pickup_order['lpOrderId'],
							'lpShipmentId'       => $pickup_order['lpShipmentId'],
							'lpManifestId'       => null,
							'lpProvider'         => $account->lp_provider_id,
							'lpShipmentDetails'  => $tracking_response['data']['shipmentDetails'],
							'trackingId'         => $tracking_response['data']['trackingId'],
							'logistic'           => $tracking_response['data']['logistic'],
							'shipmentStatus'     => $tracking_response['data']['shipmentStatus'],
							'shippingZone'       => $tracking_response['data']['shippingZone'],
							'shippingSlab'       => $tracking_response['data']['shippingSlab'],
							'shippingFee'        => $tracking_response['data']['shippingFee'],
							'codFees'            => 0,
							'rtoShippingFee'     => $tracking_response['data']['rtoShippingFee'],
							// 'isQcEnabled'		 => (strpos(json_decode($shipmentDetails)['courier_name'], 'QC') !== FALSE) ? 1 : 0,
							'createdBy'          => $current_user['userID'],
							'canceledBy'         => null,
							'cancellationReason' => null,
							'createdDate'        => date('Y-m-d H:i:s'),
							'expectedPickupDate' => $tracking_response['data']['expectedPickupDate'],
							'expectedDate'       => $tracking_response['data']['expectedDate'],
							'deliveredDate'      => null,
							'cancelledDate'      => null,
						);

						$db->insert(TBL_FULFILLMENT, $fulfilment_data);

						$db->where('returnId', $_POST['returnId']);
						$db->update(TBL_SP_RETURNS, array('r_status' => 'awb_assigned'));

						$db->where('fulfillmentId', $_POST['fulfillmentId']);
						$db->update(TBL_FULFILLMENT, array('fulfillmentStatus' => 'cancelled', 'shipmentStatus' => 'cancelled', 'canceledBy' => $current_user['userID'], 'cancellationReason' => 'Courier Cancelled. Reassign', 'cancelledDate' => date('Y-m-d H:i:s')));
					}
					// {"type":"success","message":"Return successfully created and pickup generated","data":{"shipmentStatus":"awb_assigned","logistic":"Delhivery","trackingId":"19041449834371","shipmentDetails":"{\"courier_company_id\":61,\"awb_code\":\"19041449834371\",\"cod\":0,\"order_id\":376272719,\"shipment_id\":375638586,\"awb_code_status\":1,\"assigned_date_time\":{\"date\":\"2023-07-25 13:30:43.000000\",\"timezone_type\":3,\"timezone\":\"Asia\\\/Kolkata\"},\"applied_weight\":0.2,\"company_id\":1628757,\"courier_name\":\"Delhivery Reverse\",\"child_courier_name\":null,\"freight_charges\":112.1,\"invoice_no\":\"\",\"transporter_id\":\"06AAPCS9575E1ZR\",\"transporter_name\":\"Delhivery\",\"shipped_by\":{\"shipper_company_name\":\"Madhukar sriramula \",\"shipper_address_1\":\"Vasavi College of Engineering,\",\"shipper_address_2\":\"Ibrahimbagh,\",\"shipper_city\":\"HYDERABAD\",\"shipper_state\":\"TELANGANA\",\"shipper_country\":\"India\",\"shipper_postcode\":\"500031\",\"shipper_first_mile_activated\":0,\"shipper_phone\":\"9951814884\",\"lat\":null,\"long\":null,\"shipper_email\":\"madhu.sriramula85@gmail.com\",\"extra_info\":[],\"rto_company_name\":\"Madhukar sriramula \",\"rto_address_1\":\"Vasavi College of Engineering,\",\"rto_address_2\":\"Ibrahimbagh,\",\"rto_city\":\"HYDERABAD\",\"rto_state\":\"TELANGANA\",\"rto_country\":\"India\",\"rto_postcode\":\"500031\",\"rto_phone\":\"9951814884\",\"rto_email\":\"madhu.sriramula85@gmail.com\"},\"pickup_scheduled_date\":\"2023-07-26 09:00:00\",\"pickup_status\":1}","shippingFee":112.1,"rtoShippingFee":112.1,"expectedDate":"2023-08-10 00:00:00","expectedPickupDate":"2023-07-26 09:00:00","shippingZone":"z_d","shippingSlab":"0.200"}}
					echo json_encode($tracking_response);
				}
			}

			// GET SHIPMENT STATUS
			break;

			// SCAN & SHIP
		case 'scan_ship':
			$tracking_id = $_GET['tracking_id'];

			$db->join(TBL_PRODUCTS_MASTER . " p", "p.sku=o.sku", "LEFT");
			$db->join(TBL_PRODUCTS_COMBO . " c", "c.sku=o.sku", "LEFT");
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
			$db->join(TBL_SP_LOGISTIC . ' sl', 'f.lpProvider=sl.lp_provider_id');
			$db->where('f.trackingId', $tracking_id);
			$db->where('o.status', "RTD");
			$orders = $db->ObjectBuilder()->get(TBL_SP_ORDERS . ' o', null, "o.orderId, o.orderItemId, o.accountId, o.quantity, o.uid, o.discountCodes, p.sku, p.pid, c.cid, sl.lp_provider_name");
			$lpProvider = "";
			if ($orders) {
				// Process start
				$data  = array(
					"logType" => "Order Process Start",
					"identifier" => $orders[0]->orderId,
					"userId" => $current_user['userID']
				);
				$db->insert(TBL_PROCESS_LOG, $data);

				$return = array();
				$content = array();
				$items = array();
				$fulfillable_quantity = 0;
				foreach ($orders as $order) {
					$discountCodes = array_column(json_decode($order->discountCodes), 'code');
					$fathers_day_discount_codes = json_decode(get_option('gift_card_discount_codes'), true);
					$gift_box_svg = '<svg version="1.1" height="20px" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1000 1000" enable-background="new 0 0 1000 1000" xml:space="preserve"><g><g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)"><path d="M2629.1,4988.9C1718.6,4848,997.9,4172.2,1000,3459.7c2-500.2,336.9-984,794.2-1145.3c261.3-91.9,616.6-57.2,839.1,83.7c332.8,210.3,449.1,616.6,251.1,884c-106.2,140.9-194,122.5-232.7-49c-51-228.6-155.2-320.5-373.6-330.7c-183.7-10.2-302.2,38.8-420.6,173.5c-253.1,287.9-230.7,606.3,61.3,902.4c424.6,430.8,1147.4,598.2,1772.1,408.3c412.4-126.6,955.5-492,1261.7-851.3c261.3-306.2,410.4-602.3,512.4-1014.7c26.5-112.3,57.2-212.3,67.4-220.5c32.7-34.7,69.4,142.9,67.4,332.8c-6.1,706.4-469.6,1439.3-1231,1949.7C3815.3,4954.2,3249.8,5086.9,2629.1,4988.9z"/><path d="M7584,4733.7c-698.2-57.2-1318.8-502.2-1572-1126.9c-128.6-316.4-161.3-530.8-163.3-1086.1c0-490,18.4-745.2,51-712.5c10.2,10.2,32.7,198,51,416.5c36.7,481.8,79.6,767.6,145,998.3c214.4,737,837,1206.6,1596.5,1206.6c312.4,0,524.7-73.5,690-238.9c110.3-110.2,191.9-263.4,191.9-359.3c0-151.1-191.9-279.7-443-296c-228.7-14.3-318.5,18.4-469.6,175.6c-126.6,130.7-128.6,132.7-147,81.7c-26.5-69.4,8.2-212.3,73.5-306.2c173.5-251.1,620.6-338.9,949.3-185.8c369.5,173.5,479.8,481.8,302.2,835c-130.7,255.2-414.4,483.8-694.1,559.4C8010.7,4731.6,7769.8,4748,7584,4733.7z"/><path d="M3939.8,3088.2c-171.5-20.4-418.5-151.1-653.3-347.1c-406.3-340.9-637-796.2-573.7-1139.2c40.8-220.5,149-351.2,426.7-518.6c285.8-171.5,653.3-300.1,1086.1-377.7c183.7-34.7,622.7-73.5,814.6-73.5c112.3,0,114.3-2.1,161.3-81.7c65.3-116.4,138.8-145,418.5-169.5c222.5-20.4,247-20.4,314.4,16.3c85.8,47,138.8,126.6,157.2,230.7l10.2,75.5l414.4,2c473.7,0,690,30.6,1045.3,145c430.8,138.8,761.5,347.1,857.5,541c71.5,142.9,71.5,336.9,4.1,530.8c-126.6,355.2-528.8,765.6-943.2,959.5c-159.3,73.5-287.9,73.5-449.1-4.1c-138.8-67.4-365.4-298.1-518.6-530.8c-136.8-210.3-361.4-643.1-471.6-914.6c-51-128.6-75.5-165.4-100-155.2c-16.3,6.1-114.3,20.4-216.4,32.7l-183.7,20.4L5417.9,1557C4862.6,2594.1,4342,3133.1,3939.8,3088.2z M4199.1,2610.4c196-93.9,438.9-392,765.6-937.1c230.7-383.8,265.4-459.4,249.1-541c-6.1-38.8-22.5-71.5-32.7-71.5c-30.6,0-326.6,218.5-547.1,404.2c-240.9,202.1-702.3,673.7-792.1,810.5c-91.9,140.9-118.4,236.8-85.8,316.4C3803,2708.4,3978.6,2714.6,4199.1,2610.4z M7481.9,2498.1c132.7-61.3,136.8-200.1,14.3-379.7c-122.5-177.6-518.6-526.7-849.3-751.3c-210.3-140.9-506.3-306.2-524.7-294c-20.4,12.2,40.8,136.8,212.3,436.9c265.4,465.5,581.8,873.8,737,953.4C7206.3,2534.9,7377.8,2549.2,7481.9,2498.1z"/><path d="M2616.9,421.9c-93.9-28.6-247-171.5-291.9-269.5c-34.7-73.5-38.8-144.9-38.8-765.6c0-759.5,4.1-775.8,142.9-914.6c142.9-142.9,69.4-136.8,1353.6-142.9l1157.6-6.1v1063.6V448.4l-1126.9-2C3084.4,444.3,2659.8,436.2,2616.9,421.9z"/><path d="M6348.8-613.2v-1061.6h1129h1129l112.3,57.2c124.5,63.3,230.7,187.8,263.4,310.3c14.3,53.1,20.4,316.4,16.3,730.9l-6.1,649.2l-53.1,100c-57.2,114.3-185.8,222.5-300.1,255.2c-47,12.2-483.8,20.4-1182.1,20.4H6348.8V-613.2z"/><path d="M2714.9-3089.6c0-1337.2,0-1345.4,112.3-1492.4c32.7-42.9,100-100,151.1-130.6l93.9-55.1l935-6.1l933-6.1v1461.7v1459.7H3827.5H2714.9V-3089.6z"/><path d="M6348.8-3318.3V-4778h904.4h902.4l104.1,49c120.5,55.1,236.8,183.7,269.5,296c18.4,55.1,24.5,438.9,24.5,1327v1247.4H7451.3H6348.8V-3318.3z"/></g></g></svg>';
					$gift_box = "";
					$discount_code = array_intersect($discountCodes, $fathers_day_discount_codes);
					if (!empty($discount_code)) {
						$gift_box = '<div class="form-group item-info original_sku">
														<div class="col-md-12 gift">' . $gift_box_svg . ' - Gift Card [' . implode(", ", $discount_code) . ']</div>
													</div>';
					}

					if ($order->cid) {
						$db->where('cid', $order->cid);
						$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
						$products = json_decode($products['pid']);
						foreach ($products as $product) {
							$uids = json_decode($order->uid, true);
							$uids_count = is_null($uids) ? 0 : count($uids);
							$db->where('pid', $product);
							$item = $db->getOne(TBL_PRODUCTS_MASTER, 'pid as item_id, sku as parent_sku');
							$item['orderItemId'] = $order->orderItemId;
							$item['uids'] = []; //is_null($uids) ? [] : $uids;
							$item['quantity'] = $order->quantity;
							$item['scanned_qty'] = $uids_count;
							$item['scan'] = ($order->quantity == $uids_count) ? true : false;
							$item_index = isset($items[$order->orderItemId]) ? array_search($item['item_id'], array_column($items[$order->orderItemId], 'item_id')) : null;
							$all_uids = "";
							if (!is_null($uids)) {
								$all_uids = implode(', ', $uids);
							}
							$all_success = "hide";
							if ($order->quantity == $uids_count)
								$all_success = "";

							if (is_null($item_index) || $item_index === FALSE) {
								$items[] = $item;
								$fulfillable_quantity += $order->quantity;
								$content[$order->orderItemId][$item['item_id']] = '<div class="item_group item_' . $item["item_id"] . '" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg" width="100" height="100"/>
													</div>
												</div>
												<div class="col-md-10">
													<div class="form-group item-info parent_sku">
														<div class="col-md-3">Parent SKU:</div>
														<div class="col-md-9 sku">' . $item["parent_sku"] . '</div>
													</div>
													<div class="form-group item-info original_sku">
														<div class="col-md-3">SKU:</div>
														<div class="col-md-9 sku">' . $order->sku . '</div>
													</div>
													<div class="form-group item-info qty">
														<div class="col-md-3">Quantity:</div>
														<div class="col-md-9">' . ($item['quantity'] > 1 ? "<sapn class='label label-danger'>" . $item['quantity'] . "</span>" : $item['quantity']) . '</div>
													</div>
													<div class="form-group item-info uids hide">
														<div class="col-md-3">UID:</div>
														<div class="col-md-8 uid">' . $all_uids . '</div>
														<div class="col-md-1 scanned text-right ' . $all_success . '"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
													</div>
												</div>
											</div>
										</div>';
							} else {
								$fulfillable_quantity += $order->quantity;
								$items[$item_index]['quantity'] += $order->quantity;
								$content[$order->orderItemId][$item['item_id']] = '<div class="item_group item_' . $item["item_id"] . '" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg" width="100" height="100"/>
													</div>
												</div>
												<div class="col-md-10">
													<div class="form-group item-info parent_sku">
														<div class="col-md-3">Parent SKU:</div>
														<div class="col-md-9 sku">' . $item["parent_sku"] . '</div>
													</div>
													<div class="form-group item-info original_sku">
														<div class="col-md-3">SKU:</div>
														<div class="col-md-9 sku">' . $order->sku . '</div>
													</div>
													' . $corrected_sku . '
													<div class="form-group item-info qty">
														<div class="col-md-3">Quantity:</div>
														<div class="col-md-9">' . ($items[$order->orderItemId][$item_index]['quantity'] > 1 ? "<sapn class='label label-danger'>" . $items[$order->orderItemId][$item_index]['quantity'] . "</span>" : $items[$order->orderItemId][$item_index]['quantity']) . '</div>
													</div>
													<div class="form-group item-info uids hide">
														<div class="col-md-3">UID:</div>
														<div class="col-md-8 uid">' . $all_uids . '</div>
														<div class="col-md-1 scanned text-right ' . $all_success . '"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
													</div>
												</div>
											</div>
										</div>';
							}
						}
					} else {
						$uids = json_decode($order->uid, true);
						$uids_count = is_null($uids) ? 0 : count($uids);
						$item['item_id'] = $order->pid;
						$item['parent_sku'] = $order->sku;
						$item['orderItemId'] = $order->orderItemId;
						$item['uids'] = []; //is_null($uids) ? [] : $uids;
						$item['quantity'] = $order->quantity;
						$item['scanned_qty'] = $uids_count;
						$item['scan'] = ($order->quantity == $uids_count) ? true : false;
						$item_index = array_search($order->item_id, array_column($items, 'item_id'));

						$all_uids = "";
						if (!is_null($uids)) {
							$all_uids = implode(', ', $uids);
						}
						$all_success = "hide";
						if ($order->quantity == $uids_count)
							$all_success = "";

						if (!$item_index) {
							$items[] = $item;
							$fulfillable_quantity += $order->quantity;
							$content[$order->orderItemId][$item['item_id']] = '<div class="item_group item_' . $item["item_id"] . '" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg" width="100" height="100"/>
													</div>
												</div>
												<div class="col-md-10">
													' . $gift_box . '
													<div class="form-group item-info parent_sku">
														<div class="col-md-3">Parent SKU:</div>
														<div class="col-md-9 sku">' . $item["parent_sku"] . '</div>
													</div>
													<div class="form-group item-info original_sku">
														<div class="col-md-3">SKU:</div>
														<div class="col-md-9 sku">' . $order->sku . '</div>
													</div>
													<div class="form-group item-info qty">
														<div class="col-md-3">Quantity:</div>
														<div class="col-md-9">' . ($items[$item_index]['quantity'] > 1 ? "<sapn class='label label-danger'>" . $items[$item_index]['quantity'] . "</span>" : $items[$item_index]['quantity']) . '</div>
													</div>
													<div class="form-group item-info uids hide">
														<div class="col-md-3">UID:</div>
														<div class="col-md-8 uid">' . $all_uids . '</div>
														<div class="col-md-1 scanned text-right ' . $all_success . '"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
													</div>
												</div>
											</div>
										</div>';
						} else {
							$fulfillable_quantity += $order->quantity;
							$items[$item_index]['quantity'] += $order->quantity;
							$content[$order->orderItemId][$item['item_id']] = '<div class="item_group item_' . $item["item_id"] . '" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg" width="100" height="100"/>
													</div>
												</div>
												<div class="col-md-10">
													<div class="form-group item-info parent_sku">
														<div class="col-md-3">Parent SKU:</div>
														<div class="col-md-9 sku">' . $item["parent_sku"] . '</div>
													</div>
													<div class="form-group item-info original_sku">
														<div class="col-md-3">SKU:</div>
														<div class="col-md-9 sku">' . $order->sku . '</div>
													</div>
													<div class="form-group item-info qty">
														<div class="col-md-3">Quantity:</div>
														<div class="col-md-9">' . ($items[$item_index]['quantity'] > 1 ? "<sapn class='label label-danger'>" . $items[$item_index]['quantity'] . "</span>" : $items[$item_index]['quantity']) . '</div>
													</div>
													<div class="form-group item-info uids hide">
														<div class="col-md-3">UID:</div>
														<div class="col-md-8 uid">' . $all_uids . '</div>
														<div class="col-md-1 scanned text-right ' . $all_success . '"><span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span></div>
													</div>
												</div>
											</div>
										</div>';
						}
						$lpProvider = $order->lp_provider_name;
						$accountId = $order->accountId;
					}

					$lpProvider = $order->lp_provider_name;
					$accountId = $order->accountId;
				}

				$content_html = '';
				foreach ($content as $html) {
					$content_html .= implode('', $html);
				}
				$response = array("type" => "success", "items" => $items, "fulfillable_quantity" => $fulfillable_quantity, "orderId" => $orders[0]->orderId, "lpProvider" => $lpProvider, "accountId" => $accountId, "content" => preg_replace('/\>\s+\</m', '><', $content_html));
			} else {
				$db->where('orderId', $orders[0]['orderId']);
				$order = $db->ObjectBuilder()->getOne(TBL_SP_ORDERS, "status, spStatus");
				if ($order) {
					if ($order->spStatus == "CANCELLED" || $order->status == "CANCELLED") {
						$response = array("type" => "info", "message" => "Order Cancelled<br />DO NOT PROCESS");
					} else {
						$response = array("type" => "error", "message" => "Order status is " . strtoupper($order->status));
					}
				} else {
					$response = array("type" => "error", "message" => "Invalid Tracking ID");
				}
			}
			echo json_encode($response);
			break;

		case 'get_uid_details':
			$uid = $_GET['uid'];
			$db->where('inv_status', 'qc_verified');
			$db->where('inv_id', $uid);
			$item_id = $db->getValue(TBL_INVENTORY, 'item_id');
			if ($item_id) {
				$data  = array(
					"logType" => "Product Process Start",
					"identifier" => $uid,
					"userId" => $current_user['userID']
				);
				if ($db->insert(TBL_PROCESS_LOG, $data))
					echo json_encode(array('type' => 'success', 'item_id' => $item_id));
				else
					echo json_encode(array('type' => 'error', 'message' => 'Log Error : ' . $db->getLastError()));
			} else {
				$db->where('inv_id', $uid);
				$status = $db->getValue(TBL_INVENTORY, 'inv_status');
				echo json_encode(array('type' => 'error', 'message' => 'Product status is ' . strtoupper($status)));
			}
			break;

		case 'sideline_product':
			$data  = array(
				"logType" => "Order Process Sidelined",
				"identifier" => $_REQUEST["orderId"],
				"userId" => $current_user['userID']
			);
			if ($db->insert(TBL_PROCESS_LOG, $data))
				echo json_encode(array('type' => 'success', 'message' => "Order successfully sidelined"));
			else
				echo json_encode(array('type' => 'error', 'message' => 'Log Error : ' . $db->getLastError()));
			break;

		case 'save_scan_ship':
			$scanned_orders = json_decode($_REQUEST['scanned_items']);
			$orderId = $_REQUEST['orderId'];
			$update_status = array();
			$orderItemId = "";
			foreach ($scanned_orders as $orderItemId => $uids) {
				$db->where('orderItemId', $orderItemId);
				$order_detail = array(
					'uid' => json_encode($uids),
					'status' => 'shipped',
					'shippedDate' => $db->now(),
				);
				if ($db->update(TBL_SP_ORDERS, $order_detail)) {
					foreach ($uids as $uid) {
						// CHANGE UID STATUS
						$inv_status = $stockist->update_inventory_status($uid, 'sales');
						// ADD TO LOG
						$log_status = $stockist->add_inventory_log($uid, 'sales', 'Shopify Sales :: ' . $orderItemId);
						// ADD PROCESS LOG
						$data  = array(
							"logType" => "Order Process End",
							"identifier" => $orderId,
							"userId" => $current_user['userID']
						);
						$log_status = $db->insert(TBL_PROCESS_LOG, $data);

						if ($inv_status && $log_status)
							$update_status[$orderItemId] = true;
						else
							$update_status[$orderItemId] = false;
					}
				} else {
					$update_status[$orderItemId] = false;
				}
			}

			if (in_array(false, $update_status))
				$return = array("type" => "error", "message" => "Error Updating order", "error" => $update_status, "error_message" => $db->getLastError());
			else {
				$db->where('o.orderId', $orderId);
				$db->where('status', 'cancelled', '!=');
				$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
				$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentStatus', 'cancelled', '!=');
				$order = $db->objectBuilder()->getOne(TBL_SP_ORDERS . ' o', array('fulfillmentId', 'lpShipmentId', 'lpManifestId', 'orderNumberFormated', 'trackingId', 'logistic', 'JSON_UNQUOTE(JSON_EXTRACT(deliveryAddress, "$.phone")) as phone'));
				$update_data = array(
					'fulfillmentStatus' => 'shipped'
				);
				if ($order->logistic == "pickup") {
					$update_data['shipmentStatus'] = 'delivered';
					$update_data['pickedUpDate'] = date('Y-m-d H:i:s');
					$update_data['deliveredDate'] = date('Y-m-d H:i:s');
				}
				$db->where('fulfillmentId', $order->fulfillmentId);
				$db->update(TBL_FULFILLMENT, $update_data);

				$return = array("type" => "success", "message" => "Order successfull shipped", "orderId" => $orderId, "shipmentId" => $order->lpShipmentId);
			}

			echo json_encode($return);
			break;

			// SEARCH ORDER
		case 'search_order':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$search_key = trim($_GET['search_order_key']);
			$search_by = $_GET['search_order_by'];

			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
			$db->join(TBL_SP_ACCOUNTS . " a", "a.account_id=o.accountId", "LEFT");
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
			$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
			$db->join(TBL_FULFILLMENT . ' fr', 'fr.orderId=o.orderId', 'LEFT');
			$db->joinWhere(TBL_FULFILLMENT . ' fr', 'fr.fulfillmentType', 'return');
			$db->join(TBL_SP_LOGISTIC . ' l', 'l.lp_provider_id=f.lpProvider', 'LEFT');
			if ($search_by == "uid") {
				$db->where('o.uid', '%' . $search_key . '%', 'LIKE');
			} else if ($search_by == 'trackingId') {
				$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', "LEFT");
				$db->where('(f.trackingId LIKE ? OR fr.trackingId LIKE ?)', array($search_key, $search_key));
				// $db->where('f.trackingId', $search_key);
			} else if ($search_by == 'returnId') {
				$db->where('r.' . $search_by, $search_key);
			} else if ($search_by == 'mobileNumber') {
				$search_key_str = trim(chunk_split($search_key, 5, ' '));
				$db->where('(o.deliveryAddress LIKE ? OR o.billingAddress LIKE ? OR o.deliveryAddress LIKE ? OR o.billingAddress LIKE ?)', array('%' . $search_key . '%', '%' . $search_key . '%', '%' . $search_key_str . '%', '%' . $search_key_str . '%'));
			} else if ($search_by == 'orderNumber') {
				$db->where('(o.orderNumber LIKE ? OR o.orderNumberFormated LIKE ?)', array($search_key, $search_key));
			} else {
				$db->where('o.' . $search_by, $search_key, 'LIKE');
			}

			$db->orderBy('o.insertDate', "DESC");
			$db->groupBy('o.orderId');
			$orders = $db->ObjectBuilder()->get(TBL_SP_ORDERS . ' o', null, "f.*, o.*, a.account_name, a.account_store_url, l.lp_provider_name, l.lp_provider_tracking_url, l.lp_provider_order_url, COALESCE(pm.thumb_image_url) as produtImage");
			// $orders = $db->ObjectBuilder()->get(TBL_SP_ORDERS .' o', null, "o.orderItemId as OID, r.returnId as RID, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as produtImage, o.*, r.*, c.*, COALESCE(p.corrected_sku, p.sku) as sku");

			$response['type'] = "success";
			if (!$orders) {
				$response['orderType'] = '';
				$response['content'] = '<div class="modal-body-empty text-center">No order found!!!</div>';
			} else {
				$response['content'] = "";
				foreach ($orders as $order) {
					$order_type = $order->replacementOrder ? 'Replacement Order' : strtoupper($order->paymentType);
					$address = json_decode($order->deliveryAddress);
					$on_hold = ($order->hold ? '<i class="fas fa-hand-paper"></i>' : '');
					$flag_class = ($order->isFlagged == true ? ' active' : '');
					$replacement_order_details = "";
					if ($order->status == "shipped" && is_null($order->pickedUpDate)) {
						$on_hold = '[PICKUP PENDING]';
					}
					if ($order->status == "shipped" && !is_null($order->pickedUpDate) && $order->shipmentStatus != "delivered") {
						$on_hold = '[IN-TRANSIT]';
					}

					if ($order_type == "Replacement Order") {
						$db->join(TBL_SP_ORDERS . ' o', 'r.orderItemId=o.orderItemId');
						$db->where('r.r_replacementOrderId', $order->orderId);
						$replacementOrderId = $db->getValue(TBL_SP_RETURNS . ' r', 'o.orderNumberFormated');
						$replacement_order_details = '<dl class="price">
																		<dt class="font-rlight font-rgrey">Replacement Order ID:</dt>
																		<dd class="amount">' . $replacementOrderId . '</dd>
																	</dl>';
					}

					$order_delivered_cancelled_date = '<dl class="fsn">
																		<dt class="font-rlight font-rgrey">Delivery Date:&nbsp;</dt>
																		<dd class="amount">' . (is_null($order->deliveredDate) ? "NA" : date("M d, Y h:i A", strtotime($order->deliveredDate))) . '</dd>
																	</dl>';
					if (!is_null($order->cancelledDate)) {
						$order_delivered_cancelled_date = '<dl class="fsn">
																		<dt class="font-rlight font-rgrey">Cancelled Date:&nbsp;</dt>
																		<dd class="amount">' . (is_null($order->cancelledDate) ? "NA" : date("M d, Y h:i A", strtotime($order->cancelledDate))) . '</dd>
																	</dl>';
					}

					$logistic_details = "";
					$delivery_details = "";
					$order_status = "";
					$delivery_details = '
																		<div class="col-md-12 sub-header">
																			<span class="card-label">Delivery Details:&nbsp;</span>
																			<span class="order_date">' . $address->name . ', ' . $address->city . ', ' . $address->province . '</span>
																			<span class="card-label">Contact No.:&nbsp;</span>
																			<span class="order_date"><a href="	' . str_replace(' ', '', $address->phone) . '" rel="nofollow">' . str_replace(' ', '', $address->phone) . '</a></span>
																		</div>';

					if (strtolower($order->status) != "new") {
						$shipment_status = "";
						if ($order->trackingId) {
							$logistic_details = '
																		<div class="col-md-12 sub-header">
																			<span class="card-label">Logistic Provider:&nbsp;</span>
																			<span class="order_date"><a class="font-rblue" href="' . $order->lp_provider_order_url . $order->lpOrderId . '" target="_blank">' . $order->lp_provider_name . '</a></span>
																			<span class="card-label">Logistic Partner:&nbsp;</span>
																			<span class="order_date">' . $order->logistic . '</span>
																			<span class="card-label">Tracking ID:&nbsp;</span>
																			<span class="order_date"><a class="font-rblue" href="' . $order->lp_provider_tracking_url . $order->trackingId . '" target="_blank">' . $order->trackingId . '</a></span>
																		</div>';

							$shipment_status = '<span class="card-label">Shipment Status.:&nbsp;</span>
																			<span class="order_date">' . ucwords(str_replace(array('_', '-'), ' ', $order->shipmentStatus)) . '</span>';
						}

						$order_status = '
																		<div class="col-md-12 sub-header">
																			<span class="card-label">Shopify Status:&nbsp;</span>
																			<span class="order_date">' . ucwords(str_replace(array('_', '-'), ' ', $order->spStatus)) . '</span>
																			' . $shipment_status . '
																		</div>';
					}

					$db->join(TBL_SP_ORDERS . ' o', 'o.orderItemId=r.orderItemId', 'INNER');
					$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.orderId', $order->orderId);
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'return');
					$db->join(TBL_SP_LOGISTIC . " l", "l.lp_provider_id=f.lpProvider", "LEFT");
					$db->where('r.orderItemId', $order->orderItemId);
					// $db->groupBy('returnId');
					$returns = $db->ObjectBuilder()->get(TBL_SP_RETURNS . ' r');
					$return_count = $db->count;
					$order_returns = "";
					$order_claims = "";
					if (!$returns)
						$order_returns = '<dt class="font-rlight font-rgrey">No Return Details Found</dt>';
					else {
						$on_hold = '[NDR/RMA]';
						if (!is_null($order->cancelledDate))
							$on_hold = '[CANCELLED]';

						$r = 0;
						foreach ($returns as $return) {
							$return->r_completionDate = ($return->r_status == "return_cancelled") ? $return->r_cancellationDate : $return->r_completionDate;
							$r_uid_links = array();
							if (!is_null($return->r_uid)) {
								// foreach (json_decode($return->r_uid, true) as $uid => $condition) {
								foreach (json_decode($return->r_uid, true) as $uid) {
									$condition = "";
									$r_uid_links[] = sprintf('<a class="tooltips" data-placement="right" data-original-title="%s" href="' . BASE_URL . '/inventory/history.php?uid=%s" target="_blank">%s</a>', ucfirst($condition), $uid, $uid);
								}
							}
							$return_replacement_order_details = "";
							if ($return->r_returnType == "replace") {
								$db->where('orderId', $return->r_replacementOrderId);
								$r_replacementOrderId = $db->getValue(TBL_SP_ORDERS, 'orderNumberFormated');
								$return_replacement_order_details = '<dl><dt class="font-rlight font-rgrey">Replacement Order Id</dt>
																		<dd class="r_id">' . $r_replacementOrderId . '</dd></dl>';
							}

							$order_returns .= '						
																	<div class="order_return">
																		<dl><dt class="font-rlight font-rgrey">Return Status</dt>
																		<dd class="r_status">' . ($return->r_status == "" ? "NA" : strtoupper($return->r_status)) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Return Action</dt>
																		<dd class="r_sub_reason">' . ($return->r_returnType == "" ? "NA" : strtoupper($return->r_returnType)) . '</dd></dl>
																		' . $return_replacement_order_details . '
																		<dl><dt class="font-rlight font-rgrey">Return Type</dt>
																		<dd class="r_status">' . str_replace('_', ' ', strtoupper($return->r_source)) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Tracking ID</dt>
																		<dd class="r_sub_reason"><a class="font-rblue" href="' . $return->lp_provider_tracking_url . $return->trackingId . '" target="_blank">' . $return->trackingId . '</a> [' . strtoupper($return->shipmentStatus) . ']</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Return ID</dt>
																		<dd class="r_id">' . $return->returnId . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Created Date</dt>
																		<dd class="r_sub_reason">' . (($return->r_createdDate == "" || is_null($return->r_createdDate)) ? "NA" : date("M d, Y h:i A", strtotime($return->r_createdDate))) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Delivered Date</dt>
																		<dd class="r_sub_reason">' . (($return->deliveredDate == "" || is_null($return->deliveredDate)) ? "NA" : date("M d, Y h:i A", strtotime($return->deliveredDate))) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Received Date</dt>
																		<dd class="r_sub_reason">' . (($return->r_receivedDate == "" || is_null($return->r_receivedDate)) ? "NA" : date("M d, Y h:i A", strtotime($return->r_receivedDate))) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Completed Date</dt>
																		<dd class="r_sub_reason">' . (($return->r_completionDate == "" || is_null($return->r_completionDate)) ? "NA" : date("M d, Y h:i A", strtotime($return->r_completionDate))) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Return UID</dt>
																		<dd class="r_status">' . (!empty($r_uid_links) ? implode(', ', $r_uid_links) : "NA") . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Reason</dt>
																		<dd class="r_reason">' . $return->r_reason . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Sub-Reason</dt>
																		<dd class="r_sub_reason">' . str_replace(array('\r\n', '\n'), '<br />', $return->r_subReason) . '</dd></dl>
																		<!--<dl><dt class="font-rblue comment-label" data-placement="left" data-original-title="" title="">Buyer Comment</dt>
																		<dd class="r_comment">' . str_replace('\n', "<br />", $return->r_comments) . '</dd></dl>-->
																	</div>';

							if ($return_count > 1 && $return_count - 1 != $r)
								$order_returns .= '<hr>';
							$r++;

							$db->where('orderItemId', $return->orderItemId);
							$db->where('returnId', $return->returnId);
							$claims = $db->ObjectBuilder()->get(TBL_SP_CLAIMS);
							if (!$claims)
								$order_claims = '<dt class="font-rlight font-rgrey">No Claim Details Found</dt>';
							else {
								foreach ($claims as $claim) {
									$order_claims .= '
																	<div class="return_claim">
																		<dl><dt class="font-rlight font-rgrey">Claim Status</dt>
																		<dd class="c_status">' . strtoupper($claim->claimStatus) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Claim ID</dt>
																		<dd class="c_id"><a class="font-rblue" target="_blank" href="#">' . $claim->claimId . '</a></dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Claim Date</dt>
																		<dd class="c_date">' . ($claim->createdDate == "" ? "NA" : date("M d, Y h:i A", strtotime($claim->createdDate))) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Claim Updated</dt>
																		<dd class="c_date">' . ($claim->updatedDate == "" ? "NA" : date("M d, Y h:i A", strtotime($claim->updatedDate))) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Claim Reason</dt>
																		<dd class="c_reason">' . $claim->claimProductCondition . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Claim Reimbursement</dt>
																		<dd class="c_reimbursement">&#x20b9; ' . (($claim->claimReimbursmentAmount == "" || is_null($claim->claimReimbursmentAmount)) ? "0.00" : $claim->claimReimbursmentAmount) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Claim Comments</dt>
																		<dd class="c_comments">' . ($claim->claimComments == "" ? "NA" : $claim->claimComments) . '</dd></dl>
																		<dl><dt class="font-rlight font-rgrey">Claim Notes</dt>
																		<dd class="c_comments">' . ($claim->claimNotes == "" ? "NA" : str_replace("\n", "<br />", $claim->claimNotes)) . '</dd></dl>
																	</div>';
								}
							}
						}
					}
					$order_payments = '<dt class="font-rlight font-rgrey">No Payments Details Found</dt>';

					$response['orderType'] = $order->status;
					if (is_null($order->pickedUpDate))
						$response['orderType'] = 'pickup_pending';
					if (!is_null($order->pickedUpDate))
						$response['orderType'] = 'in_transit';
					if ($returns)
						$response['orderType'] = 'ndr';

					$response['content'] .= '
														<article class="return-order-card clearfix detail-view">
															<div class="col-md-12 order-details-header">
																<div class="col-md-2 product-image"><div class="bookmark"><a class="flag' . $flag_class . '" data-orderitemid="' . $order->orderItemId . '" href="javascript:void()"><i class="fa fa-bookmark"></i></a></div><img src="' . IMAGE_URL . '/uploads/products/' . $order->produtImage . '" onerror="this.src=\'https://via.placeholder.com/150x150\'"></div>
																<div class="col-md-10 order-details">
																	<header class="col-md-7 return-card-header font-rlight">
																		<div>
																			<h4>
																				<div class="article-title font-rblue font-rreguler">' . $order->title . '&nbsp;&nbsp;<span class="label label-success">' . $order->account_name . '</span></div>
																			</h4>
																		</div>
																	</header>
																	<div class="col-md-12 sub-header">
																		<span class="card-label">SKU:&nbsp;</span>
																		<span class="order_id">' . $order->sku . '</span>
																		<span class="card-label">HSN:&nbsp;</span>
																		<span class="order_type">' . $order->hsn . '</span>
																		<span class="card-label">UID:&nbsp;</span>
																		<span class="order_type">' . (is_null($order->uid) ? "NA" : implode(', ', json_decode($order->uid))) . '</span>
																	</div>
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Quantity:&nbsp;</span>
																		<span class="quantity">' . $order->quantity . '/' . $order->orderedQuantity . '</span>
																		<span class="card-label">Price:&nbsp;</span>
																		<span class="sellingPrice">' . $order->sellingPrice . '</span>
																		<span class="card-label">Shipping:&nbsp;</span>
																		<span class="shippingCharge">' . $order->shippingCharge . '</span>
																		<span class="card-label">Total:&nbsp;</span>
																		<span class="totalPrice">' . $order->totalPrice . '</span>
																		<span class="card-label">Fulfilment Type:&nbsp;</span>
																		<span class="order_type">' . $order->orderType . '</span>
																	</div>
																	' . $logistic_details . '
																	' . $delivery_details . '
																	' . $order_status . '
																	<div class="col-md-12 sub-header">
																		<span class="order"><a class="view_order" target="_blank" href="' . BASE_URL . '/shopify/order.php?orderId=' . $order->orderId . '" />View Order</a>&nbsp;&nbsp;|&nbsp;&nbsp;</span>
																		<span class="comments"><a href="javascript:void(0);" class="view_comment" data-accountid="' . $order->accountId . '" data-orderitemid="' . $order->orderItemId . '" data-orderid="' . $order->orderId . '" data-type="' . $order->status . '" />View Comments</a></span>
																	</div>
																</div>
															</div>
															<section class="col-md-12 row return-card-details">
																<div class="col-md-3 order_details">
																	<dl class="status">
																		<dt class="font-rlight font-rgrey">Order Status:&nbsp;</dt>
																		<dd class="amount">' . strtoupper($order->status) . ' ' . $on_hold . '</dd>
																	</dl>
																	<dl class="orderId">
																		<dt class="font-rlight font-rgrey">Order ID:&nbsp;</dt>
																		<dd class="amount"><a class="font-rblue" target="_blank" href="https://' . $order->account_store_url . '/admin/orders/' . $order->orderId . '">' . $order->orderId . '</a></dd>
																	</dl>
																	<dl class="orderItemId">
																		<dt class="font-rlight font-rgrey">Order Item ID:&nbsp;</dt>
																		<dd class="amount">' . $order->orderItemId . '</dd>
																	</dl>
																	<dl class="orderNumber">
																		<dt class="font-rlight font-rgrey">Order Number:&nbsp;</dt>
																		<dd class="amount">' . $order->orderNumberFormated . '</dd>
																	</dl>
																	<dl class="price">
																		<dt class="font-rlight font-rgrey">Order Type:&nbsp;</dt>
																		<dd class="amount">' . $order_type . '</dd>
																	</dl>
																	' . $replacement_order_details . '
																	<dl class="sku">
																		<dt class="font-rlight font-rgrey">Invoice Number:&nbsp;</dt>
																		<dd class="amount">' . ($order->invoiceNumber == "" ? "Not Generated" : $order->invoiceNumber) . '</dd>
																	</dl>
																	<dl class="sku">
																		<dt class="font-rlight font-rgrey">Invoice Date:&nbsp;</dt>
																		<dd class="amount">' . (is_null($order->invoiceDate) ? "Not Generated" : date("M d, Y", strtotime($order->invoiceDate))) . '</dd>
																	</dl>
																	<dl class="quantity">
																		<dt class="font-rlight font-rgrey">Order Date:&nbsp;</dt>
																		<dd class="qty">' . date("M d, Y h:i A", strtotime($order->orderDate)) . '</dd>
																	</dl>
																	<dl class="fsn">
																		<dt class="font-rlight font-rgrey">Label Generated:&nbsp;</dt>
																		<dd class="amount">' . (is_null($order->labelGeneratedDate) ? "NA" : date("M d, Y h:i A", strtotime($order->labelGeneratedDate))) . '</dd>
																	</dl>
																	<dl class="fsn">
																		<dt class="font-rlight font-rgrey">RTD Date:&nbsp;</dt>
																		<dd class="amount">' . (is_null($order->rtdDate) ? "NA" : date("M d, Y h:i A", strtotime($order->rtdDate))) . '</dd>
																	</dl>
																	<dl class="fsn">
																		<dt class="font-rlight font-rgrey">Shipped Date:&nbsp;</dt>
																		<dd class="amount">' . (is_null($order->shippedDate) ? "NA" : date("M d, Y h:i A", strtotime($order->shippedDate))) . '</dd>
																	</dl>
																	<dl class="fsn">
																		<dt class="font-rlight font-rgrey">Pickup Date:&nbsp;</dt>
																		<dd class="amount">' . (is_null($order->pickedUpDate) ? "NA" : date("M d, Y h:i A", strtotime($order->pickedUpDate))) . '</dd>
																	</dl>
																	' . $order_delivered_cancelled_date . '
																</div>
																<div class="col-md-3 order_returns">
																	' . $order_returns . '
																</div>
																<div class="col-md-3 order_claims">
																	' . $order_claims . '
																</div>
																<div class="col-md-3 order_payments">
																	' . $order_payments . '
																</div>
															</section>
														</article>';
				}
			}

			echo json_encode($response);
			break;

			// RETURNS
		case 'import_returns':
			$handle = new \Verot\Upload\Upload($_FILES['returns_csv']);
			$logistic = $_REQUEST['logistic_provider'];
			if ($handle->uploaded) {
				$handle->file_overwrite = true;
				$handle->dir_auto_create = true;
				$handle->file_new_name_ext = "";
				$handle->process(ROOT_PATH . "/uploads/shopify_returns/");
				$handle->clean();
				$file = ROOT_PATH . "/uploads/shopify_returns/" . $handle->file_dst_name;

				$returns = csv_to_array($file);
				$insert = 0;
				$exists = 0;
				$update = 0;
				$error = array();
				foreach ($returns as $return) {
					$invoice_number = $return['Order ID'];
					$sku = $return['Channel SKU'];
					$return_delivered_date = strtotime($return["RTO Delivered Date"]);
					$return_initiated_date = $return['RTO Initiated Date'];
					$rto_awb_code = $return['RTO AWB Code'];
					$awb_code = $return["AWB Code"];
					$return_awb = ($rto_awb_code) ? $rto_awb_code : $awb_code;
					$db->where('invoiceNumber', $invoice_number);
					$db->where('sku', $sku);
					$order = $db->objectBuilder()->getOne(TBL_SP_ORDERS);
					if ($order) {
						$db->where('orderItemId', $order->orderItemId);
						$db->where('r_trackingId', $return_awb);
						if ($db->has(TBL_SP_RETURNS)) {
							$exists++;
							$db->where('orderItemId', $order->orderItemId);
							$db->where('r_trackingId', $return_awb);
							$db->where('(r_status = ? OR r_status = ? OR r_status = ? )', array('return_received', 'return_completed', 'return_claimed'));
							if ($db->getOne(TBL_SP_RETURNS))
								continue;

							// UPDATE STATUS
							if ($return_delivered_date > 0) {
								$details = array(
									'r_status' => 'delivered',
									'r_status' => 'delivered',
									'r_deliveredDate' => date('Y-m-d H:i:s', $return_delivered_date),
								);
								$db->where('orderItemId', $order->orderItemId);
								$db->where('r_trackingId', $return_awb);
								if ($db->update(TBL_SP_RETURNS, $details))
									$update++;
								else
									$error[$return_awb] = "Unable to update the return details. Error: " . $db->getLastError();
							}
							continue;
						} else {
							$reason = 'undelivered';
							$sub_reason = 'undelivered';

							$details = array(
								'orderItemId' => $order->orderItemId,
								'r_source' => 'courier_return',
								'r_status' => 'in_transit',
								'r_quantity' => $order->quantity,
								'r_productId' => $order->productId,
								'r_sku' => $order->sku,
								'r_trackingId' => $return_awb,
								'r_courierName' => $order->forwardLogistic,
								'r_lpOrderId' => $order->lpOrderId,
								'r_lpProvider' => $logistic,
								'r_reason' => $reason,
								'r_subReason' => $sub_reason,
								'r_status' => 'in_transit',
								'r_createdDate' => date('Y-m-d H:i:s', strtotime($return_initiated_date)),
								'r_expectedDate' => date('Y-m-d H:i:s', strtotime($return_initiated_date . '  +30 days')),
								'insertBy' => 'csv_bulk_import',
								'insertDate' => $db->now()
							);

							if ($db->insert(TBL_SP_RETURNS, $details)) {
								$insert++;
							} else {
								$error[$return_awb] = 'Unable to insert return. Error: ' . $db->getLastError();
							}
						}
					} else {
						$error[$return_awb] = 'Unable to find order data';
					}
				}
				$response = array(
					'type'		=> 'success',
					'total'		=> count($returns),
					'insert' 	=> $insert,
					'update'	=> $update,
					'exists'	=> $exists,
					'error'		=> $error,
				);
			} else {
				$response = array(
					'type'		=> 'error',
					'error' 	=> 'Unable to upload the file.',
				);
			}

			echo json_encode($response);
			break;

		case 'update_returns_status':
			$returnId = $_POST['returnId'];
			$orderItemId = $_POST['orderItemId'];
			$uids = isset($_POST['uids']) ? $_POST['uids'] : NULL;
			$productCondition = $_POST['productCondition'];
			$trackingId = $_POST['trackingId'];
			$db->startTransaction();
			if (isset($_POST['claimId']) && !empty(trim($_POST['claimId']))) {
				$claimId = trim($_POST['claimId']);
				$db->where('returnId', $returnId);
				$db->where('claimId', $claimId);
				$claim = $db->get(TBL_SP_CLAIMS);
				if (!$claim) {
					$details = array(
						'orderItemId' => $orderItemId,
						'claimId' => $claimId,
						'returnId' => $returnId,
						'claimStatus' => 'pending',
						'claimStatusLP' => 'pending',
						'claimProductCondition' => $productCondition,
						'createdBy' => $current_user['userID'],
						'createdDate' => $db->now(),
					);
					if ($db->insert(TBL_SP_CLAIMS, $details)) {
						$udpate_fulfilment = array(
							'shipmentStatus' => 'lost'
						);
						$db->where('trackingId', $trackingId);
						$db->where('fulfillmentType', 'return');
						$db->where('(shipmentStatus = ? OR shipmentStatus = ? OR shipmentStatus = ? OR shipmentStatus = ? OR shipmentStatus = ?)', array('start', 'awb_assigned', 'pickup_exception', 'out_for_delivery', 'in_transit'));
						$db->update(TBL_FULFILLMENT, $udpate_fulfilment);

						$details = array(
							'r_status' => 'return_claimed',
							'r_uid' => is_null($uids) ? $uids : json_encode($uids)
						);
						$db->where('returnId', $returnId);
						if ($db->update(TBL_SP_RETURNS, $details)) {
							$db->commit();
							$return = array('type' => 'success', 'message' => 'Claim details successfull added');
						} else {
							$db->rollback();
							$return = array('type' => 'error', 'message' => 'Claim details added but status not updated');
						}
					} else {
						$db->rollback();
						$return = array('type' => 'error', 'message' => 'Unable to add claim details. Please try again later', 'error' => $db->getLastError());
					}
				} else {
					$db->rollback();
					$return = array('type' => 'error', 'message' => 'Claim already exist for this return');
				} // CREATE CLAIM
			} else {
				$details = array('r_uid' => json_encode($uids));
				$db->where('returnId', $returnId);
				if ($db->update(TBL_SP_RETURNS, $details)) {
					$responses = update_returns_status($orderItemId, 'completed', true, true);
					if (count($responses) === 1) {
						$return = $responses[0];
						if ($responses[0]['type'] == "success") {
							$return = $responses[0];
							$return["message"] = "Return marked completed with order ID " . $orderItemId;
						}
					} else {
						$return["type"] = "success";
						$return["message"] = "Multiple returns marked completed with order ID " . $orderItemId;
					}
				}
			}

			echo json_encode($return);
			break;

		case 'update_claim_details':
			$orderItemId = $_REQUEST['orderItemId'];
			$returnId = $_REQUEST['returnId'];
			$claimId = $_REQUEST['claimId'];
			$claimNotes = $_REQUEST['claimNotes'];
			$claimProductCondition = $_REQUEST['claimProductCondition'];
			$claimReimbursmentAmount = isset($_REQUEST['claimReimbursmentAmount']) ? $_REQUEST['claimReimbursmentAmount'] : NULL;
			$return = array(
				"type" => "error",
				"message" => "Error updating reimbursement details to order ID " . $orderItemId
			);

			$db->startTransaction();
			$details = array(
				'claimStatus' => 'resolved',
				'claimStatusLP' => 'resolved',
				'claimComments' => trim($claimNotes) . ' (' . $current_user['user_nickname'] . ')',
				'closedBy' => $current_user['userID'],
				'closedDate' => $db->now()
			);

			if (!is_null($claimReimbursmentAmount)) {
				$details['claimReimbursmentAmount'] = $claimReimbursmentAmount;
			}

			$db->where('orderItemId', $orderItemId);
			$db->where('returnId', $returnId);
			$db->where('claimId', $claimId);
			if ($db->update(TBL_SP_CLAIMS, $details)) {
				$responses = update_returns_status($orderItemId, 'completed', true, true, $returnId, $claimProductCondition);
				if (count($responses) === 1) {
					$return = $responses[0];
					if ($responses[0]['type'] == "success") {
						$return = $responses[0];
						$return["message"] = "Return reimbursement added and marked completed for order ID " . $orderItemId;
					}
				} else {
					$return["type"] = "success";
					$return["message"] = "Multiple returns marked completed for order ID " . $orderItemId;
				}
			}

			echo json_encode($return);
			break;

		case 'update_claim_status':
			$orderItemId = $_REQUEST['orderItemId'];
			$returnId = $_REQUEST['returnId'];
			$trackingId = $_REQUEST['trackingId'];
			$claimId = $_REQUEST['claimId'];
			$claimStatus = $_REQUEST['claimStatus'];
			$return = array(
				"type" => "error",
				"message" => "Unable to update claim status for " . $orderItemId
			);

			$details = array(
				'claimStatus' => 'pending',
				'claimStatusLP' => $claimStatus,
			);

			$db->where('orderItemId', $orderItemId);
			$db->where('returnId', $returnId);
			$db->where('claimId', $claimId);
			if ($db->update(TBL_SP_CLAIMS, $details)) {
				$return["type"] = "success";
				$return["message"] = "Claim status successfull updated for " . $orderItemId;
				$return["trackingId"] = $trackingId;
			} else {
				$return["error"] = $db->getLastError();
			}

			echo json_encode($return);
			break;

		case 'update_claim_id':
			$orderItemId = $_REQUEST['orderItemId'];
			$returnId = $_REQUEST['returnId'];
			$claimId = $_REQUEST['claimId'];
			$newClaimId = $_REQUEST['newClaimId'];

			$db->where('returnId', $returnId);
			$db->where('claimId', $claimId);
			$old_notes = $db->getValue(TBL_SP_CLAIMS, 'claimNotes');
			$details = array(
				'claimId' => $newClaimId,
				'claimNotes' => 'Claim ID updated from ' . $claimId . ' to ' . $newClaimId . ' - ' . date('d-M-Y H:i:s', time()) . "\n\n" . $old_notes
			);

			$db->where('orderItemId', $orderItemId);
			$db->where('returnId', $returnId);
			$db->where('claimId', $claimId);
			if ($db->update(TBL_SP_CLAIMS, $details)) {
				$return = array("type" => "success", "message" => "Claim details successfully updated");
			} else {
				$return = array("type" => "success", "message" => "Unable to update claim details", "error" => $db->getLastError());
			}

			echo json_encode($return);
			break;

			// OVERVIEW
		case 'overview':
			$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : "";
			$start_date = $_GET['start_date'];
			$end_date = $_GET['end_date'] . ' 23:59:59';
			$response = array();
			switch ($type) {
				case 'order_statistic':
					// total
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['total_orders']['count'] = $total_orders = $db->count;
					$response['total_orders']['percentage'] = '100';

					// cancelled
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('(o.status = ? OR o.status = ?)', array('cancelled', 'cancel'));
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['cancelled_orders']['count'] = $cancelled_orders = $db->count;
					$response['cancelled_orders']['percentage'] = number_format((($cancelled_orders / $total_orders) * 100), '2', '.', '');

					// new
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('(o.status = ? OR o.status = ?)', array('new', 'packing'));
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['new_orders']['count'] = $new_orders = $db->count;
					$response['new_orders']['percentage'] = number_format((($new_orders / $total_orders) * 100), '2', '.', '');

					// shipped
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('o.status', 'shipped');
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['shipped_orders']['count'] = $shipped_orders = $db->count;
					$response['shipped_orders']['percentage'] = number_format((($shipped_orders / $total_orders) * 100), '2', '.', '');

					// in_transit
					$db->where('o.status', 'shipped');
					$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
					$db->where('(f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus IS NULL)', array('picked_up', 'in-transit', 'in_transit', 'out_for_delivery', 'shipped'));
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['in_transit_orders']['count'] = $in_transit_orders = $db->count;
					$response['in_transit_orders']['percentage'] = number_format((($in_transit_orders / $shipped_orders) * 100), '2', '.', '');

					// delivered
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('o.status', 'shipped');
					$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
					$db->where('f.shipmentStatus', 'delivered');
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['delivered_orders']['count'] = $delivered_orders = $db->count;
					$response['delivered_orders']['percentage'] = number_format((($delivered_orders / $shipped_orders) * 100), '2', '.', '');

					// courier_return
					$db->where('o.status', 'shipped');
					$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
					$db->where('(f.shipmentStatus = ? OR f.shipmentStatus = ?)', array('undelivered', 'undelivered-rto'));
					// $db->where('r.r_source', 'courier_return');
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['courier_return']['count'] = $courier_return_orders = $db->count;
					$response['courier_return']['percentage'] = number_format((($courier_return_orders / $shipped_orders) * 100), '2', '.', '');

					// customer_return
					$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId');
					$db->where('o.status', 'shipped');
					$db->where('r.r_source', 'customer_return');
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['customer_return']['count'] = $customer_return_orders = $db->count;
					$response['customer_return']['percentage'] = number_format((($customer_return_orders / $delivered_orders) * 100), '2', '.', '');

					echo json_encode($response);
					break;

				case 'shipment_statistic':
					// total
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['total_orders']['count'] = $total_orders = $db->count;
					$response['total_orders']['percentage'] = '100';

					// cancelled
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('(o.status = ? OR o.status = ?)', array('cancelled', 'cancel'));
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['cancelled_orders']['count'] = $cancelled_orders = $db->count;
					$response['cancelled_orders']['percentage'] = number_format((($cancelled_orders / $total_orders) * 100), '2', '.', '');

					// new
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('(o.status = ? OR o.status = ?)', array('new', 'packing'));
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['new_orders']['count'] = $new_orders = $db->count;
					$response['new_orders']['percentage'] = number_format((($new_orders / $total_orders) * 100), '2', '.', '');

					// in_transit
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('o.status', 'shipped');
					$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
					$db->where('(f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus IS NULL)', array('awb_assigned', 'picked_up', 'in-transit', 'in_transit', 'out_for_delivery', 'shipped'));
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['in_transit_orders']['count'] = $shipped_orders = $db->count;
					$response['in_transit_orders']['percentage'] = number_format((($shipped_orders / $total_orders) * 100), '2', '.', '');

					// delivered
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId', 'LEFT');
					$db->where('o.status', 'shipped');
					$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
					$db->where('f.shipmentStatus', 'delivered');
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['delivered_orders']['count'] = $delivered_orders = $db->count;
					$response['delivered_orders']['percentage'] = number_format((($delivered_orders / $total_orders) * 100), '2', '.', '');

					// courier_return
					// $db->join(TBL_SP_RETURNS.' r', 'r.orderItemId=o.orderItemId');
					$db->where('o.status', 'shipped');
					$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
					$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
					$db->where('(f.shipmentStatus = ? OR f.shipmentStatus = ?)', array('undelivered', 'undelivered-rto'));
					// $db->where('r.r_source', 'courier_return');
					$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
					$db->get(TBL_SP_ORDERS . ' o');
					$response['courier_return']['count'] = $courier_return_orders = $db->count;
					$response['courier_return']['percentage'] = number_format((($courier_return_orders / $total_orders) * 100), '2', '.', '');

					echo json_encode($response);
					break;
			}
			break;

			// case 'test':
			// 	// SELECT * FROM `bas_sp_orders` WHERE `status` LIKE 'shipped' AND `invoiceDate` >= '2022-04-01 00:00:00'
			// 	echo '<pre>';
			// 	$db->where('(status = ? OR status = ? OR status = ?)', array('shipped', 'packing', 'rtd'));
			// 	$db->where('invoiceDate', '2022-04-01 00:00:00', '>=');
			// 	$db->orderBy('orderConfirmedDate', 'ASC');
			// 	$orders = $db->objectBuilder()->get(TBL_SP_ORDERS);
			// 	// var_dump($orders);
			// 	foreach ($orders as $order){
			// 		$invoiceNumber = get_invoice_number($order->accountId);
			// 		$data = array(
			// 			'invoiceNumber' => $invoiceNumber,
			// 		);
			// 		$db->where('orderId', $order->orderId);
			// 		if ($db->update(TBL_SP_ORDERS, $data)){
			// 			echo "Invoice successfull created for ".$order->orderId." invoice id ".$invoiceNumber."<br />";
			// 		} else {
			// 			echo "Unable to generate Invoice for ".$order->orderId."<br />";
			// 		}
			// 	}
			// break;

			// GLOBAL FUNCTIONS
		case 'create_invoice':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$orderId = $_REQUEST['orderId'];
			create_invoice($orderId);
			break;

		case 'export_orders':
		case 'generate':
			if (isset($_GET['type']) && isset($_GET['type']) == "report") {
			} else {
				$order_type = $_GET['order_type'];
				$start_date = $_GET['start_date'];
				$end_date = $_GET['end_date'] . '  23:59:59';
				switch ($order_type) {
					case 'in_transit':
						$is_returns = ($_GET['handler'] == "return") ? true : false;
						$orders = view_orders($order_type, $is_returns);

						// Create new PHPExcel object
						$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
						// Set document properties
						$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
							->setLastModifiedBy("Ishan Kukadia")
							->setTitle("FIMS - " . $order_type . " Orders")
							->setSubject("FIMS - " . $order_type . " Orders")
							->setDescription("FIMS - " . $order_type . " Orders");

						$activeSheet = $objPHPExcel->getActiveSheet();

						// Add some data
						$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('A1', 'Order ID')
							->setCellValue('B1', 'Tracking ID')
							->setCellValue('C1', 'Logistic Partner')
							->setCellValue('D1', 'Pickup Date')
							->setCellValue('E1', 'In-transit Days');

						if (!empty($orders)) {
							$row = 2;
							foreach ($orders as $order) {
								$diff = round((time() - strtotime($order->pickedUpDate)) / (86400));
								$activeSheet
									->setCellValue('A' . $row, $order->orderNumberFormated)
									->setCellValue('B' . $row, $order->trackingId)
									->setCellValue('C' . $row, $order->logistic)
									->setCellValue('D' . $row, $order->pickedUpDate)
									->setCellValue('E' . $row, $diff);
								if ($diff <= 5)
									break;
								$row++;
							}
						}

						$activeSheet
							->getStyle('B:B')
							->getNumberFormat()
							->setFormatCode(PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);

						foreach (range('A', $activeSheet->getHighestColumn()) as $col) {
							$activeSheet->getColumnDimension($col)->setAutoSize(true);
						}

						// Set active sheet index to the first sheet, so Excel opens this as the first sheet
						$objPHPExcel->setActiveSheetIndex(0);

						// Redirect output to a client’s web browser (Excel2007)
						header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Disposition: attachment;filename="' . $order_type . '-' . date('Y-m-d') . '.xlsx"');
						header('Cache-Control: max-age=0');
						// If you're serving to IE 9, then the following may be needed
						header('Cache-Control: max-age=1');

						// If you're serving to IE over SSL, then the following may be needed
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
						header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
						header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
						header('Pragma: public'); // HTTP/1.0
						$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
						$objWriter->save('php://output');
						exit;
						break;

					case 'overview_shipment_in_transit':
						$db->where('o.status', 'shipped');
						$db->join(TBL_FULFILLMENT . " f", "f.orderId=o.orderId", "INNER");
						$db->joinWhere(TBL_FULFILLMENT . ' f', 'f.fulfillmentType', 'forward');
						$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId');
						// $db->join(TBL_SP_ACCOUNTS_LOCATIONS.' sal', 'sal.accountId=o.accountId');
						// $db->join(TBL_SP_LOGISTIC.' sl', 'sl.account_id=sa.account_id');
						$db->join(TBL_SP_LOGISTIC . ' sl', 'sl.lp_provider_id=sa.account_active_logistic_id');
						$db->where('(f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus = ? OR f.shipmentStatus IS NULL)', array('awb_assigned', 'picked_up', 'in-transit', 'in_transit', 'out_for_delivery', 'shipped'));
						$db->where('orderDate', array($start_date, $end_date), 'BETWEEN');
						$db->orderBy('orderConfirmedDate', 'ASC');
						$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . ' o');

						// Create new PHPExcel object
						$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
						// Set document properties
						$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
							->setLastModifiedBy("Ishan Kukadia")
							->setTitle("FIMS - " . $order_type . " Orders")
							->setSubject("FIMS - " . $order_type . " Orders")
							->setDescription("FIMS - " . $order_type . " Orders");

						$activeSheet = $objPHPExcel->getActiveSheet();

						// Add some data
						$objPHPExcel->setActiveSheetIndex(0)
							->setCellValue('A1', 'Order ID')
							->setCellValue('B1', 'Status')
							->setCellValue('C1', 'Tracking ID')
							->setCellValue('D1', 'Courier Partner')
							->setCellValue('E1', 'Logistic Aggregator')
							->setCellValue('F1', 'Order Confirmed Date');

						if (!empty($orders)) {
							$row = 2;
							foreach ($orders as $order) {
								$activeSheet
									->setCellValue('A' . $row, $order->orderNumberFormated)
									->setCellValue('B' . $row, $order->shipmentStatus)
									->setCellValue('C' . $row, (is_numeric($order->trackingId) ? $order->trackingId . ' ' : $order->trackingId))
									->setCellValue('D' . $row, $order->logistic)
									->setCellValue('E' . $row, $order->lp_provider_name)
									->setCellValue('F' . $row, $order->orderConfirmedDate);
								$row++;
							}
						}

						$activeSheet
							->getStyle('B:B')
							->getNumberFormat()
							->setFormatCode(PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);

						foreach (range('A', $activeSheet->getHighestColumn()) as $col) {
							$activeSheet->getColumnDimension($col)->setAutoSize(true);
						}

						// Set active sheet index to the first sheet, so Excel opens this as the first sheet
						$objPHPExcel->setActiveSheetIndex(0);

						// Redirect output to a client’s web browser (Excel2007)
						header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
						header('Content-Disposition: attachment;filename="' . $order_type . '-' . $start_date . '-to-' . $end_date . '.xlsx"');
						header('Cache-Control: max-age=0');
						// If you're serving to IE 9, then the following may be needed
						header('Cache-Control: max-age=1');

						// If you're serving to IE over SSL, then the following may be needed
						header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
						header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
						header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
						header('Pragma: public'); // HTTP/1.0
						$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
						$objWriter->save('php://output');
						break;
				}
			}
			break;

		case 'send_notification_message':
			$medium = explode(',', $_REQUEST['medium']);
			$notificationType = $_REQUEST['notificationType'];
			$masterTemplateId = get_template_id_by_name($notificationType);
			$accountId = $_REQUEST['accountId'];
			$identifierType = $_REQUEST['identifierType'];
			$identifierValue = $_REQUEST['identifierValue'];
			$clients = $db->getOne(TBL_SP_ACCOUNTS, 'account_active_sms_id, account_active_email_id, account_active_whatsapp_id');

			$response = array(
				'status' => true,
				'type' => 'error',
				'message' => 'Error sending notifications',
				'data' => array(
					'sms' => array(
						'sent' => false,
						'message' => 'Not Enabled/Configured'
					),
					'whatsapp' => array(
						'sent' => false,
						'message' => 'Not Enabled/Configured'
					),
					'email' => array(
						'sent' => false,
						'message' => 'Not Enabled/Configured'
					)
				)
			);

			foreach ($response['data'] as $mode => $mode_value) {
				if (!in_array($mode, $medium))
					unset($response['data'][$mode]);
			}

			$sms_status = false;
			if (in_array('sms', $medium)) {
				// $clients['account_active_sms_id'];
			}

			$whatsapp_status = false;
			if (in_array('whatsapp', $medium)) {
				$data = array(
					"identifierValue" => $identifierValue,
					"identifierType" => $identifierType,
					"medium" => $medium,
					"templateId" => $masterTemplateId,
					"active_whatsapp_id" => $clients["account_active_whatsapp_id"]
				);
				$notification = new Notification();
				$s_whatsApp = $notification->send($data);

				if ($s_whatsApp['status'] == "success") {
					$whatsapp_status = true;
					$response['data']['whatsapp']['sent'] = true;
					$response['data']['whatsapp']['message'] = 'WhatsApp Successfully sent';
				}
			}

			$email_status = false;
			if (in_array('email', $medium)) {
				// $clients['account_active_email_id'];
			}

			if ((in_array('sms', $medium) && $sms_status) || (in_array('whatsapp', $medium) && $whatsapp_status) || (in_array('email', $medium) && $email_status)) {
				$response['type'] = 'success';
				$response['message'] = 'Notifications sent successfully';
			}
			echo json_encode($response);
			break;

		case 'get_all_settlements':
			$db->groupBy('sp.settlementId');
			$db->groupBy('date(sp.deliveredDate)');
			$settlements = $db->get(TBL_SP_REMITTANCE . ' sp', null, 'sp.settlementId,sp.deliveredDate,SUM(sp.remittedAmount) as total_value');
			$return = array();
			$i = 0;

			foreach ($settlements as $settlement) {
				$j = 0;
				foreach ($settlement as $s_value) {
					if ($j == 0)
						$return['data'][$i][] = '<a target="_blank" href="' . BASE_URL . '/flipkart/payments.php?search_by=neft_id&search_value=' . $s_value . '">' . $s_value . '</a>';
					else if ($j == 2)
						$return['data'][$i][] = '&#8377 ' . number_format((float)$s_value, 2, '.', ',');
					else
						$return['data'][$i][] = $s_value;

					$j++;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'import_payment':
			$account_id = $_REQUEST['account_id'];
			$shopify_account = $_REQUEST['shopify_account'];

			$handle = new \Verot\Upload\Upload($_FILES['orders_csv']);

			if ($handle->uploaded) {
				$handle->file_overwrite = true;
				$handle->dir_auto_create = true;
				$handle->process(ROOT_PATH . "/uploads/payment_sheets/");
				$handle->clean();
				$file = ROOT_PATH . "/uploads/payment_sheets/" . $handle->file_dst_name;
			} else {
				echo json_encode(array('error' => 'Unable to upload file'));
			}

			$response = import_payments($file, $account_id, $shopify_account);

			echo json_encode($response);
			break;

		case 'import_payment_charge':
			$handle = new \Verot\Upload\Upload($_FILES['charge_csv']);

			if ($handle->uploaded) {
				$handle->file_overwrite = true;
				$handle->dir_auto_create = true;
				$handle->process(ROOT_PATH . "/uploads/payment_sheets/");
				$handle->clean();
				$file = ROOT_PATH . "/uploads/payment_sheets/" . $handle->file_dst_name;
			} else {
				echo json_encode(array('error' => 'Unable to upload file'));
			}

			$response = import_payments_charges($file,);

			echo json_encode($response);
			break;

		case 'get_all_unsettled':
			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', "LEFT");
			$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
			$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'LEFT');
			$db->where('o.is_difference_amt', 0);
			$db->orderBy("o.orderId");
			$db->where('o.orderDate', array('2023-04-01', '2023-07-01'), 'BETWEEN');
			$settlements = $db->objectBuilder()->get(TBL_SP_ORDERS . " o", NULL, 'o.*, f.remainingCharge, o.orderId, f.shippingFee, f.codFees, f.rtoShippingFee , sa.account_name, sa.account_id, r.returnId as return_id, r.r_source, r.r_status, rm.remittedAmount');
			// settlements = $db->objectBuilder()->get(TBL_SP_ORDERS ." o",null,'o.orderId,o.totalPrice,o.status,o.customerPrice,o.orderItemId,o.orderDate,o.shippedDate,o.accountId,o.shippingCharge, f.fulfillmentType,o.paymentType,o.spStatus,o.paymentGatewayAmount,f.remainingCharge,f.orderId,f.shippingFee,f.codFees,f.rtoShippingFee, sa.account_Id,sa.account_name,r.returnId as return_id,r.r_source,r.r_status,rm.remittedAmount');

			$orders = array();
			$payout = 0;

			$net_settlement = 0;
			for ($i = 0; $i < sizeof($settlements); $i++) {
				$account_name = $settlements[$i]->account_name;
				$orderItemId = $settlements[$i]->orderItemId;
				$orderId = $settlements[$i]->orderId;
				$orderDate = date('d M, Y', strtotime($settlements[$i]->orderDate));
				$shippedDate = date('d M, Y', strtotime($settlements[$i]->shippedDate));
				$dueDate = date('d M, Y', strtotime($settlements[$i]->orderDate));
				$accountId = $settlements[$i]->accountId;
				$sort_date = date('Y-m-d', strtotime($settlements[$i]->orderDate));
				$net_settlement = getSettlementAmount($settlements[$i]);

				$net_payout = calculate_actual_payout($settlements[$i]);
				for ($j = $i + 1; $j < sizeof($settlements); $j++) {
					if ($settlements[$j]->orderId === $orderId) {
						$net_payout += calculate_actual_payout($settlements[$j]);
						$db->where('channel_order_id', $settlements[$j]->orderNumberFormated);
						$pgCharge = $db->getOne(TBL_SP_CHARGE, 'amount');
						$net_payout += abs((float)$pgCharge["amount"]);
						$i = $j;
					} else {
						break;
					}
				}

				$difference = $net_settlement - $net_payout;
				if (!isset($orders[$settlement->orderItemId]) && round_value($difference) >= 0.5 || round_value($difference) <= -0.5) {
					$orders[$orderItemId] = array(
						'account_name' => $account_name,
						'orderItemId' => $orderItemId,
						'orderId' => $orderId,
						'orderDate' => $orderDate,
						'shippedDate' => $shippedDate,
						'dueDate' => $orderDate,
						'netPayout' => round_value($net_payout),
						'netSettlement' => round_value($net_settlement),
						'difference' => round_value($difference),
						'accountId' => $accountId,
						'sort_date' => $orderDate,
					);
				}
			}
			$return = array();
			$i = 0;
			foreach ($orders as $order) {
				foreach ($order as $value) {
					$return['data'][$i][] = $value;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'get_difference_details':
			if (!isset($_REQUEST['orderItemId']) && !isset($_REQUEST['account_id']))
				return;

			$orderItemId = $_REQUEST['orderItemId'];
			$account_id = $_REQUEST['account_id'];
			$type = $_REQUEST['type'];

			$account_key = "";
			foreach ($accounts as $a_key => $r_account) {
				if ($r_account->account_id == $account_id)
					$account_key = $a_key;
			}
			$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
			$db->join(TBL_SP_CLAIMS . " c", "o.orderItemId=c.orderItemId", "LEFT");
			$db->join(TBL_SP_PRODUCTS . ' sp', 'sp.variantId=o.variantId', 'LEFT');
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'LEFT');
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
			$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'LEFT');

			$db->where('o.orderItemId', $orderItemId);
			$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . " o", null, 'o.*, pm.thumb_image_url, r.returnId as return_id, r.r_status, r.r_source, r.r_createdDate, r.r_receivedDate, r.r_completionDate, c.claimId, c.claimStatus, c.claimProductCondition, c.claimReimbursmentAmount, sa.*, sp.handle, f.*, rm.remittedAmount, rm.remittanceDate');
			$order = $orders[0];
			$other_order = isset($orders[1]) ? $orders[1] : null;
			$inner_content = '';
			// ccd($order);
			$return = get_difference_details($order, $other_order);

			$settlements = array_merge(array($return['expected_payout']), $return['settlements'], array($return['difference']));
			$key_order = array_keys($settlements[0]);
			$output_content = array();
			foreach ($settlements as $settlement) {
				uksort($settlement, function ($key1, $key2) use ($key_order) {
					return ((array_search($key1, $key_order) > array_search($key2, $key_order)) ? 1 : -1);
				});
				foreach ($settlement as $s_key => $s_value) {
					$output_content[$s_key][] = $s_value;
				}
			}
			// ccd($output_content);
			$j = 0;
			$last = count($output_content['settlement_date']) - 1;
			$other_header = '';
			$inner_content = '<tbody>';
			foreach ($output_content as $level_key => $level_value) {
				// var_dump($level_value);
				$editables = array('sale_amount', 'refund_amount', 'protection_fund', 'payment_type', 'commission_rate', 'commission_fee', 'commission_incentive', 'collection_fee', 'pick_and_pack_fee', 'fixed_fee', 'shipping_slab', 'shipping_zone', 'shipping_fee', 'commission_fee_waiver', 'reverse_shipping_fee', 'shipping_fee_waiver', 'flipkart_discount', 'other_fees', 'shopsy_marketing_fee');
				$i = 0;
				if ($level_key == 'settlement_date')
					$inner_content .= '<tr class="accordion-title-container">';
				else if ($level_key == 'total')
					$inner_content .= '<tr class="net-earnings-row">';
				else if ($level_key == 'sale_amount') {
					$inner_content .= '<tr class="grey-highlight marketplace-fee-row">';
				} else if ($level_key == 'order_item_amount' ||  $level_key == 'shipping_charges')
					$inner_content .= '<tr class="marketplace-fee-child">';
				else if ($j >= 7 &&  $j <= 12) {
					$other_header .= '<tr class="waiver-fee-child">
										  <td class="title" style="width:120px;">' . str_replace('_', ' ', $level_key) . '</td>
										</tr>';
					$inner_content .= '<tr class="waiver-fee-child">';
				} else if ($level_key == 'shipping_charge') {
					$inner_content .= '<tr class="grey-highlight waiver-fee-row">';
				} else if ($level_key == 'reimbursements') {
					$inner_content .= '<tr class="grey-highlight taxes-row">';
				} else if ($level_key == 'payment_gateway') {
					$inner_content .= '<tr class="grey-highlight pg-row">';
				} else if ($level_key == 'lost_credit' || $level_key == 'soft_refund') {
					$inner_content .= '<tr class="taxes-child">';
				} else if ($level_key == 'pg_charges' || $level_key == 'pg_gst') {
					$inner_content .= '<tr class="pg-child">';
				} else
					$inner_content .= '<tr class="grey-highlight">';

				$available_width = 100;
				$width = ($available_width / ($last + 1)) >= 15 ? 15 : $available_width / ($last + 1);
				foreach ($level_value as $value) {
					if ($level_key == 'settlement_date') {
						if ($i == 0) {
							// $payment_type = 'Expected Payout';
							$available_width -= $width;
						} elseif ($i == $last) {
							$payment_type = 'Difference';
							$width = $available_width;
							// } elseif ($i == $last - 1) {
							// 	$payment_type = 'Payment Gateway Remittance';
							// 	$available_width -= $width;
						} else {
							// 	$payment_type = 'Logistics Settlement';
							$payment_type = $value;
							$available_width -= $width;
						}
						var_dump($payment_type);
						var_dump($value);
						return;
						$inner_content .= '
									<td class="transaction-data-neft" style="width:' . $width . '%">
										<span class="transaction-neft-value">' . $payment_type . '</span>
										<div class="transaction-date">
											' . $value . '
										</div>
									</td>';
					} else {
						$editor = '';
						// if ($i == $last && in_array($level_key, $editables)){
						// 	$editor = '<a class="update_difference" data-itemId="'.$orderItemId.'" data-accountId="'.$account_id.'" data-key="'.$level_key.'" data-value="'.($value).'">Update</a>&nbsp;';
						// 	if($level_key == 'commission_rate' || $level_key == 'shipping_slab' || $level_key == 'payment_type' || $level_key == 'shipping_zone'  || $level_key == 'shipping_zone')
						// 		$editor = '<a class="update_difference" data-itemId="'.$orderItemId.'" data-accountId="'.$account_id.'" data-key="'.$level_key.'" data-value="'.$value.'">Update</a>&nbsp;';
						// }
						if ($i == $last && !empty($value))
							$inner_content .= '<td>' . $editor . $value . '</td>';
						elseif ($i != $last)
							$inner_content .= '<td>' . $value . '</td>';
					}
					$i++;
				}
				$inner_content .= '</tr>';
				$j++;
			}

			$inner_content .= '</tbody>';
			$returns = "";
			$claims = "";
			// ccd($inner_content);

			if (isset($order->return_id)) {
				$returns .= '
						<div class="details-col-title">
							<div class="order-title">Return ID </div>
							<div class="order-title">Status</div>
							<div class="order-title">Type</div>
							<div class="order-title">Delivered Date</div>
							<div class="order-title">Received Date</div>
							<div class="order-title">Completed Date</div>
						</div>
						<div class="details-col-value">
							<div class="heading-value">' . (isset($order->return_id) ? '<a href="https://seller.flipkart.com/index.html#dashboard/return_orders/search?type=return_id&id=' . $order->return_id . '" target="_blank" >' . $order->return_id . '</a>' : "NA") . '</div>
							<div class="heading-value">' . (isset($order->status) ? strtoupper(str_replace('return_', '', $order->status)) : "NA") . '</div>
							<div class="heading-value">' . (isset($order->r_source) ? strtoupper(str_replace('_', ' ', $order->r_source)) : "NA") . '</div>
							<div class="heading-value">' . (($order->r_createdDate == "0000-00-00 00:00:00" || $order->r_createdDate == NULL) ? "NA" : date('d M, Y', strtotime($order->r_createdDate))) . '</div>
							<div class="heading-value">' . ((isset($order->return_id) ? ($order->r_receivedDate == "" ? "NA" : date('d M, Y', strtotime($order->r_receivedDate))) : "NA")) . '</div>
							<div class="heading-value">' . ((isset($order->r_completionDate) ? ($order->r_completionDate == "" ? "NA" : date('d M, Y', strtotime($order->r_completionDate))) : "NA")) . '</div>
						</div>';
			}

			if (isset($order->claimId)) {
				$claims .= '

						<div class="details-col-title">
							<div class="order-title">Claim ID</div>
							<div class="order-title">Status</div>
							<div class="order-title">Condition</div>
							<div class="order-title">Claim Date</div>
							<div class="order-title">Claim Completed</div>
						</div>
						<div class="details-col-value">
							<div class="heading-value">' . (isset($order->claimId) ? ('<a href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/' . $order->claimId . '/SELLER_SELF_SERVE" target="_blank" >' . $order->claimId . '</a>') : "NA") . '</div>
							<div class="heading-value">' . (isset($order->claimStatus) ? strtoupper($order->claimStatus) : "NA") . '</div>
							<div class="heading-value">' . (isset($order->claimProductCondition) ? strtoupper($order->claimProductCondition) : "NA") . '</div>
							<div class="heading-value">' . (isset($order->createdDate) ? date('d M, Y', strtotime($order->createdDate)) : "NA") . '</div>
							<div class="heading-value">' . ($order->claimStatus != 'pending' ? (isset($order->lastUpdateAt) ? date('d M, Y', strtotime($order->lastUpdateAt)) : "NA") : "No") . '</div>
						</div>';
			}

			$incentive_details = "";
			$pick_and_pack_fee = "";

			$content = '<div class="product-details-transaction-container">
							<div class="product-details-container">
								<div class="row product-container clearfix">
									<div class="col-md-1 product-image-container">
										<div class="product-img-holder">
											<img src="' . IMAGE_URL . '/uploads/products/' . $order->thumb_image_url . '" onerror="this.src=\'https://via.placeholder.com/100x100\'">
										</div>	
										<div class="cod-prepaid">' . strtoupper($order->paymentType) . '</div>								
									</div>
									<div class="col-md-10 details-holder">
										<div class="product-title">
											<a target="_blank" href=https://' . $order->account_domain . "/products/" . $order->handle . '>' . $order->title . '</a>
										</div>
										<div class="product-order-details">
											<div class="order-details-container">
												<div class="details-col-title">
													<div class="order-title">Item ID </div>
													<div class="order-title">Status</div>
													<div class="order-title">SKU</div>
													<div class="order-title">Shipped Date </div>
													<div class="order-title">Sopify OrderId </div>
													<div class="order-title">Order Number </div>
												</div>
												<div class="details-col-value">
													<div class="heading-value"><a  target="_blank" href=//' . $order->account_store_url . '/admin/orders/' . $order->orderId . '>' . $orderItemId . '</a></div>
													<div class="heading-value">' . $order->status . '</div>
													<div class="heading-value">' . $order->quantity . ' x ' . $order->sku . '</div>
													<div class="heading-value">' . date('d M, Y', ($order->shippedDate == NULL ? strtotime($order->dispatchByDate) : strtotime($order->dispatchByDate))) . '</div>
													<div class="heading-value">' . $order->orderNumberFormated . '</div>
													<div class="heading-value">' . $order->orderId . '</div>
												</div>
												' . $returns . $claims . '
											</div>
										</div>
									</div>
								</div>
								<div class="transactions-history">
								<div class="transactions-history-details">
									<div class="transactions-accordion-container">
										<div class="transaction-table-container">
											<div class="transactions-table">
												<table class="table-head" style="margin-left: 0px; float: left;">
													<thead>
														<tr class="accordion-title-container">
															<td class="transaction-data-neft accordion-title" style="width:180px;">All Transactions</td>
														</tr>
														<tr class="grey-highlight marketplace-fee-row">
															<td class="title" style="width:120px;">Sale Amount <i class="fa fa-chevron-down"></td>
														</tr>
														<tr class=" marketplace-fee-child">
															<td class="title" style="width:120px;">Order Item Amount</td>
														</tr>
														<tr class=" marketplace-fee-child">
															<td class="title" style="width:120px;">Shipping Charges</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Refund Amount</td>
														</tr>
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">Payment Type</td>
														</tr>
														<tr class="grey-highlight waiver-fee-row">
															<td class="title" style="width:120px;">Shipping Charge <i class="fa fa-chevron-down"></i></td>
														</tr>
														<tr class="waiver-fee-child">
															<td class="title" style="width:120px;">Freight Charges</td>
														</tr>
														<tr class="waiver-fee-child">
															<td class="title" style="width:120px;">COD Charge</td>
														</tr>
														<tr class="waiver-fee-child">
															<td class="title" style="width:120px;">RTO Freight</td>
														</tr>
														<tr class="waiver-fee-child">
															<td class="title" style="width:120px;">COD Charge Reversed</td>
														</tr>
														<tr class="waiver-fee-child">
															<td class="title" style="width:120px;">Reverse Freight Charges</td>
														</tr>
														<tr class="waiver-fee-child">
															<td class="title" style="width:120px;">Reverse Freight Charges Reversed</td>
														</tr>
														<tr class="grey-highlight taxes-row">
															<td class="title" style="width:120px;">Reimbursements <i class="fa fa-chevron-down"></i></td>
														</tr>
														<tr class="taxes-child">
															<td class="title" style="width:120px;">Lost Credit</td>
														</tr>
														<tr class="taxes-child">
															<td class="title" style="width:120px;">Soft Refund</td>
														</tr> 
														<tr class="grey-highlight pg-row">
															<td class="title" style="width:120px;">Payment Gateway<i class="fa fa-chevron-down"></i></td>
														</tr>
														<tr class="pg-child">
															<td class="title" style="width:120px;">PG Charges</td>
														</tr>
														<tr class="pg-child">
															<td class="title" style="width:120px;">PG GST</td>
														</tr>
														<tr class="grey-highlight ">
															<td class="title">Other Fees </td>
														</tr>
														
														<tr class="grey-highlight">
															<td class="title" style="width:120px;">GST</td>
														</tr>
													</thead>
												</table>
												<table class="table-content">' .
				$inner_content
				. '</table>
											</div>
										</div>
									</div>
								</div>
							</div>
						
						</div>';

			echo json_encode(array('type' => 'success', 'data' => $content));
			break;

		case 'get_all_search_payments':
			if ($_REQUEST['search_by'] == "pro.offerId") {
				$db->where('offerId', $_REQUEST['search_value']);
				$promoDates = $db->getOne(TBL_FK_PROMOTIONS, 'promoOptInDate, promoStartDate, promoPreSaleStarts, promoEndDate');
				if ($promoDates['promoOptInDate'] > $promoDates['promoStartDate'])
					$promoDates['promoStartDate'] = $promoDates['promoOptInDate'];
				if (is_null($promoDates['promoPreSaleStarts']))
					$promoDates['promoPreSaleStarts'] = date('Y-m-d H:i:s', strtotime($promoDates['promoStartDate'] . ' - 4 hours'));
			}

			$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "LEFT");
			$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
			$db->join(TBL_FK_RETURNS . " r", "o.orderItemId=r.orderItemId", "LEFT");
			// $db->joinWhere('(r.r_shipmentStatus != ? OR r.r_shipmentStatus != ? OR r.r_shipmentStatus != ? OR r.r_shipmentStatus != ?)', array('start', 'in_transit', 'out_for_delivery', 'delivered'));
			if ($_REQUEST['search_by'] == "pro.offerId") {
				$db->join(TBL_FK_PROMOTIONS . " pro", "pro.listingId=o.listingId", "INNER");
				$db->joinWhere(TBL_FK_PROMOTIONS . " pro", 'pro.promoType', 'MP_INC');
				$db->joinWhere(TBL_FK_PROMOTIONS . " pro", 'pro.accountId=o.account_id');
				$db->where('o.orderDate', array($promoDates['promoPreSaleStarts'], $promoDates['promoEndDate']), 'BETWEEN');
			}
			$db->where($_REQUEST['search_by'], $_REQUEST['search_value']);
			$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
			$db->orderBy('o.orderDate, o.shippedDate, o.dispatchAfterDate', 'ASC');
			$db->groupBy('o.orderItemId');
			$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'p.paymentId, a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, COALESCE(o.shippedDate, o.dispatchByDate) AS shippedDate, SUM(p.paymentValue) AS paymentValue, r.r_shipmentStatus, o.commissionIncentive, o.is_flipkartPlus, o.orderDate');
			// echo '<pre>';
			// echo $db->getLastQuery();
			// echo count($settlements);
			// exit;

			$orders = array();
			$payout = 0;
			foreach ($settlements as $settlement) {
				if ($_REQUEST['search_by'] == "pro.offerId") {
					if ($order->orderDate >= $promoDates['promoPreSaleStarts'] && $order->orderDate <= $promoDates['promoStartDate'] && !$order->is_flipkartPlus)
						continue;
				}

				$account_id = $settlement['account_id'];
				$account_key = "";
				foreach ($accounts as $a_key => $account) {
					if ($account->account_id == $account_id)
						$account_key = $a_key;
				}

				$fk = new connector_flipkart($accounts[$account_key]);
				$net_settlement = (float)number_format($fk->payments->calculate_net_settlement($settlement['orderItemId']), 2, '.', '');
				$net_payout = (float)number_format($fk->payments->calculate_actual_payout($settlement['orderItemId']), 2, '.', '');
				$difference = number_format($net_settlement - $net_payout, 2, '.', '');
				// $difference = ($difference > 0) ? '<span class="label label-sm label-success">'.number_format($difference, 2, '.', '').' </span>' : '<span class="label label-sm label-warning">'.number_format($difference, 2, '.', '').' </span>';
				$isIncentive = ($settlement['commissionIncentive'] > 0) ? true : false;
				$dueDate = $fk->payments->get_due_date($settlement['orderItemId'], $isIncentive);
				// if (strtotime($dueDate) < time())
				// 	$dueDate .= ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>';

				$orders[$settlement['orderItemId']] = array(
					'account_name' => $settlement['account_name'],
					'orderItemId' => $settlement['orderItemId'],
					'orderId' => $settlement['orderId'],
					'orderDate' => date('Y-m-d', strtotime($settlement['orderDate'])),
					'shippedDate' => date('Y-m-d', strtotime($settlement['shippedDate'])),
					'dueDate' => $dueDate,
					'netPayout' => $net_payout,
					'netSettlement' => $net_settlement,
					'difference' => $difference,
					'accountId' => $settlement['account_id'],
					'sort_date' => date('Ymd', strtotime($dueDate)),
				);
			}

			$return = array();
			$i = 0;
			foreach ($orders as $order) {
				foreach ($order as $value) {
					$return['data'][$i][] = $value;
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			echo json_encode($return);
			break;

		case 'refetch_billing_details':
			if (!isset($_REQUEST['orderItemId']))
				return;

			$orderItemId = $_REQUEST['orderItemId'];
			$account_id = (int)$_REQUEST['account_id'];

			$account_key = "";
			foreach ($accounts as $a_key => $account) {
				if ($account->account_id == $account_id)
					$account_key = $a_key;
			}

			$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=o.accountId', 'INNER');
			$db->join(TBL_SP_ACCOUNTS_LOCATIONS . ' sl', 'sl.locationId=o.locationId', 'LEFT');
			$db->joinWhere(TBL_SP_ACCOUNTS_LOCATIONS . ' sl', 'sl.accountId=o.accountId');
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.sku=o.sku", "LEFT");
			$db->join(TBL_PRODUCTS_CATEGORY . " c", "c.catid=pm.category", "LEFT");
			$db->join(TBL_REGISTERED_WARRANTY . " w", "w.order_id=o.orderNumberFormated", "LEFT");
			$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId');
			$db->where('orderItemId', $orderItemId);
			$db->where('o.accountId', $_REQUEST['account_id']);
			$orders = $db->objectBuilder()->get(TBL_SP_ORDERS . ' o', NULL, '"Shopify" as marketplace, o.orderId, o.productId, o.orderItemId, o.accountId, sa.account_name as accountName, sl.locationName as location, o.orderDate, o.quantity, o.title, o.sku, o.deliveryAddress, o.customerPrice, o.sellingPrice, o.invoiceNumber, o.invoiceDate, o.invoiceAmount, f.deliveredDate, o.uid as uids, o.variantId, o.returnGrace, c.catid, c.categoryName, 0 as cid, 6 as warrantyPeriod, if(isnull(w.insertDate), 0, 1) as warrantyRegistred');

			$details = array();
			if (isset($orders[0]->priceComponents)) {
				$details['sellingPrice'] = $orders[0]->priceComponents->sellingPrice;
				$details['totalPrice'] = $orders[0]->priceComponents->totalPrice;
				$details['shippingCharge'] = $orders[0]->priceComponents->shippingCharge;
				$details['customerPrice'] = $orders[0]->priceComponents->customerPrice;
				$details['flipkartDiscount'] = $orders[0]->priceComponents->flipkartDiscount;
			}


			if (!empty($details)) {
				$db->where('orderItemId', $orderItemId);
				if ($db->update(TBL_SP_ORDERS, $details))
					echo json_encode(array('type' => 'success', 'msg' => 'Successfully fetched billing details'));
				else
					echo json_encode(array('type' => 'error', 'msg' => 'Error updating billing details', 'response' => $orders));
			} else
				echo json_encode(array('type' => 'error', 'msg' => 'Error fetching billing details', 'response' => $orders));
			break;
	}
} else {
	exit('Invalid request!');
}
