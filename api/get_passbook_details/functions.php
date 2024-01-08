<?php

function getPassbookData($curl, $token, $page = 1, $fromDate = null, $toDate = null)
{
	if (!isset($fromDate) || empty($fromDate) || $fromDate == "")
		$fromDate = date('Y-M-d', strtotime('-1 day'));
	if (!isset($toDate) || empty($toDate) || $toDate == "")
		$toDate = date('Y-M-d');
	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://apiv2.shiprocket.in/v1/account/details/passbook?from=' . $fromDate . '&is_web=1&per_page=100&search=&to=' . $toDate . '&type=&page=' . $page,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		CURLOPT_HTTPHEADER => array(
			'authority: apiv2.shiprocket.in',
			'accept: application/json, text/plain, */*',
			'authorization: Bearer ' . $token,
			'cache-control: no-cache',
			'origin: https://app.shiprocket.in',
			'referer: https://app.shiprocket.in/',
		),
	));
	$response = curl_exec($curl);
	return json_decode($response);
}

function insert_remittance_charge($data)
{
	global $db;
	foreach ($data as $types) {
		foreach ($types as  $k => $type_orders) {
			foreach ($type_orders as $order) {
				$db->where('orderNumberFormated', $order->channel_order_id);
				if ($db->has(TBL_SP_ORDERS)) {
					$db->where('awb_code', $order->awb_code);
					$db->where('category', $order->category);
					if (!$db->has(TBL_SP_CHARGE)) {

						$details =  array(
							'channel_order_id' => $order->channel_order_id,
							'awb_code' => trim($order->awb_code),
							'category' => $order->category,
							'amount' => isset($order->debit) ? -$order->debit : $order->credit,
							'description' => $order->description,
							'created_at' => date('Y-m-d H:i:s', strtotime($order->created_at)),
						);
						$db->insert(TBL_SP_CHARGE, $details);

						// Remitted amt calculate
						$db->where('orderId', $order->channel_order_id);
						if ($db->has(TBL_SP_REMITTANCE)) {
							$db->where('orderId', $order->channel_order_id);
							$old_remitted_amt = $db->getOne(TBL_SP_REMITTANCE, 'remittedAmount');
							if ($order->category != 'Brand Boost') {
								$new_dbt_remitted_amt = (float)$old_remitted_amt['remittedAmount'] - (float)$order->debit;
								$new_dbt_remitted_amt = $new_dbt_remitted_amt +  (float)$order->credit;
								$dbt_amt = array(
									'remittedAmount' => $new_dbt_remitted_amt
								);
								$db->startTransaction();
								$db->where('orderId', $order->channel_order_id);
								$db->update(TBL_SP_REMITTANCE, $dbt_amt);
								$db->commit();
								//check difference
								$db->where('orderId', $order->channel_order_id);
								$nw_remitted_amt = $db->getOne(TBL_SP_REMITTANCE, 'remittedAmount');
								$db->join(TBL_SP_RETURNS . ' r', 'r.orderItemId=o.orderItemId', 'LEFT');
								$db->join(TBL_FULFILLMENT . ' f', 'f.orderId=o.orderId', 'LEFT');
								$db->where('orderNumberFormated', $order->channel_order_id);
								$db->join(TBL_SP_REMITTANCE . ' rm', 'rm.orderId=o.orderNumberFormated', 'INNER');
								$settlements = $db->objectBuilder()->getOne(TBL_SP_ORDERS . " o", 'o.*,
									f.fulfillmentType,f.remainingCharge,f.orderId,f.shippingFee,f.codFees,f.rtoShippingFee,
									r.returnId as return_id,r.r_source,r.r_status,rm.remittedAmount');
								$net_payout = calculate_actual_payout($settlements);
								$net_payout_flt = number_format(($net_payout), '2', '.', '');
								if ($net_payout_flt == $nw_remitted_amt['remittedAmount']) {
									$data = array(
										'is_difference_amt' => 1
									);
									$db->startTransaction();
									$db->where('orderNumberFormated', $order->channel_order_id);
									$db->update(TBL_SP_ORDERS, $data);
									$db->commit();
								}
							}
						}
					} else {
						$details = array(
							'channel_order_id' => $order->channel_order_id,
							'awb_code' => $order->awb_code,
							'category' => $order->category,
							'amount' => isset($order->debit) ? -$order->debit : $order->credit,
							'description' => $order->description,
							'created_at' => date('Y-m-d H:i:s', strtotime($order->created_at)),
						);
						$db->where('awb_code', $order->awb_code);
						$db->where('category', $order->category);
						$db->update(TBL_SP_CHARGE, $details);
					}
				}
			}
		}
	}
}

function calculate_actual_payout($order)
{
	// ccd($order);
	$sale_amounts = (float)($order->customerPrice) + (float)($order->shippingCharge);
	// var_dump($sale_amounts);

	$reverseShippingFee = 0;
	// variable "$other_order" not declared
	// if(isset($other_order)){
	// 	if($other_order->fulfillmentType == 'return'){
	// 		$reverseShippingFee = -(float)$other_order->shippingFee;
	// 	}
	// }
	// var_dump($reverseShippingFee);

	if (isset($order->return_id)) {
		if (isset($order->r_source) && isset($order->totalPrice) && $order->r_source == 'courier_return') {
			$refundAmount = -(float)($order->totalPrice);
		} elseif (isset($order->customerPrice)) {
			$refundAmount = -(float)$order->customerPrice;
		}
	} else {
		$refundAmount = 0;
	}

	// var_dump($refundAmount);

	$freightCharge = isset($order->shippingFee) ? -(float)$order->shippingFee : 0;
	// var_dump($freightCharge);

	$rtoFreightCharge = 0;
	$codFeesReversed = 0;
	if (isset($order->return_id) && $order->r_source != 'customer_return') {
		// if($order->returnId && $order->r_source != 'customer_return'){
		if (isset($order->paymentType) && $order->paymentType == 'cod') {
			$rtoFreightCharge = isset($order->rtoShippingFee) ? -(float)$order->rtoShippingFee : 0;
		}
		$codFeesReversed = (float)$order->codFees;
	}
	$codCharge = isset($order->codFees) && isset($order->paymentType) && $order->paymentType == 'cod' ? -(float)$order->codFees : 0;
	// var_dump($codCharge);

	$total_shipping_charges = $freightCharge + $codCharge + $rtoFreightCharge + $codFeesReversed + $reverseShippingFee;
	// var_dump($total_shipping_charges);

	$pg_charges = 0;

	if (isset($order->paymentType) && $order->paymentType == 'prepaid') {
		$pg_charges = -number_format($sale_amounts * 2.5 / 100, 2, '.', '');
	}
	$pg_gst = $pg_charges * 0.18;
	if ($order->paymentGatewayMethod == "card" && $sale_amounts < 2000) {
		$pg_gst = 0;
	}
	$pg_gst = (float)number_format($pg_gst, 2);
	// var_dump($pg_charges);
	// var_dump($pg_gst);

	$paymentGatewayAmount = isset($order->paymentGatewayAmount) && isset($order->spStatus) && $order->spStatus != 'delivered' ? (float)$order->paymentGatewayAmount : 0;
	$payout = ($sale_amounts) + $refundAmount + $total_shipping_charges + $paymentGatewayAmount + (float)$pg_charges + $pg_gst;
	return $payout;
}
