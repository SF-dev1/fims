<?php
// https://www.skmienterprise.com/fims-sand/fims/rma/notifications-receiver.php?event=rma_payment_update
// https://www.skmienterprise.com/fims-sand/fims/rma/notifications-receiver.php?event=rma_payment_update&razorpay_payment_id=pay_LSwBx4zraima4c&razorpay_payment_link_id=plink_LStkQDURFe8Ic2&razorpay_payment_link_reference_id=20230316000092-Shipping&razorpay_payment_link_status=paid&razorpay_signature=d5b8472784f8487a131ebd294abf63d06adb97cbbc681ae3009245619a10fcdf

// echo '<pre>';
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
include(dirname(dirname(__FILE__)) . '/config.php');
global $db, $accounts;

$debug = false;
if (isset($_GET['test']) && $_GET['test'] == "1")
	$debug = true;

$test = '';
if ($debug)
	$test = '-test';

$headers = apache_request_headers();
$rawData = file_get_contents("php://input");

switch ($_REQUEST['event']) {
	case 'rma_payment_update':
		// $log->write(json_encode($headers), 'notification-razorpay-rma'.$test);
		$log->write($rawData, 'notification-razorpay-rma' . $test);

		$data = json_decode($rawData);
		if ($data->event == "payment_link.paid") {
			$link_id = $data->payload->payment_link->entity->id;
			$db->where('paymentLinkId', $link_id);
			$link_data = $db->objectBuilder()->getOne(TBL_RMA_PAYMENTS);
			if ($link_data) {
				// var_dump($link_data);
				$db->where('paymentLinkId', $link_id);
				if ($db->update(TBL_RMA_PAYMENTS, array('paymentStatus' => 'paid'))) {
					// ON SHIPPING PAYMENT SUCCESS PROCESS THE RETURN PICKUP
					if ($link_data->paymentFor == "Shipping") {
						$rmaId = $link_data->rmaId;
						$db->join(TBL_SP_ACCOUNTS . ' sa', 'sa.account_id=ra.accountId');
						$db->where('rmaId', $rmaId);
						$rma = $db->objectBuilder()->getOne(TBL_RMA . ' ra', array('ra.rmaId', 'ra.rmaNumber', 'ra.accountId', 'ra.marketplace', 'ra.orderId', 'ra.orderItemId', 'ra.returnType', 'ra.returnAddress', 'ra.mobileNumber', 'ra.emailAddress', 'sa.account_name', 'sa.account_return_address', 'sa.account_domain', 'sa.account_api_key', 'sa.account_api_pass', 'sa.account_api_secret', 'sa.account_active_logistic_id', 'sa.firm_id'));

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
						$rma->pickupAddress->email = $rma->emailAddress;
						$rma->pickupAddress->phone = $rma->mobileNumber;
						$rma->returnAddress = json_decode($rma->account_return_address);
						$rma->orderNumberFormated = $rma->rmaNumber;
						$rma->r_quantity = 1;
						$rma->spDiscount = 0;

						var_dump($rma);
						exit;

						$response = $logisticPartner->createReturn($rma, true, true); // CREATE RETURN AND REQUEST PICKUP
						// SEND NOTIFICATIONS TO CUSTOMER
					}

					// ON PARTS PAYMENT SUCCESS PROCESS THE REPAIR WORK
				} else {
					// UNABLE TO UPDATE THE PAYMENT STATUS
				}
			} else {
				// NO DATA FOUND
				exit;
			}
		}

		break;
}
