<?php
include(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
/**
 * 
 */
class flipkart_payments
{
	var $account;
	var $tier;
	var $service_gst;
	var $order_date;
	var $product_gst;
	var $is_shopsy;

	/*** Declare instance ***/
	// private static $instance = NULL;

	function __construct($account)
	{

		$this->account = $account;
		$this->tier = "bronze";
		$this->service_gst = 18;
		$this->product_gst = 18;
		$this->order_date = date('Y-m-d H:i:s', time());
	}

	function insert_settlements($order, $type)
	{
		global $db, $log;

		// if (trim($order['neft_id']) == "ZERO_PAYMENT_ADVICE"){
		// 	$return = 'Invalid NEFT_ID:  ZERO PAYMENT ADVICE';
		// 	$log->write($return, 'settlements-orders');
		// 	return $return;
		// }

		if (trim($order['neft_id']) == "DISBURSEMENT_CREATED") {
			$return = 'Invalid NEFT_ID:  Unable to insert settlement for Order Item ID ' . $order['order_item_id'];
			$log->write($return, 'settlements-orders');
			return $return;
		}

		$db->where('orderItemId', $order['order_item_id']);
		$db->where('paymentId', trim($order['neft_id']));

		if (!$db->has(TBL_FK_PAYMENTS)) {
			if ($type == "orders") {
				$details = array(
					'orderItemId' 					=> $order['order_item_id'],
					'paymentId' 					=> trim($order['neft_id']),
					'paymentType'					=> strtolower(isset($order['order_type']) ? $order['order_type'] : $order['neft_type']),
					'account_id'					=> $this->account->account_id,
					'paymentDate'					=> (isset($order['payment_date']) ? date('Y-m-d', strtotime($order['payment_date'])) : date('Y-m-d', strtotime($order['neft_date']))),
					'paymentValue'					=> (isset($order['bank_settlement_value']) ? (float)number_format($order['bank_settlement_value'], 2, '.', '') : (float)number_format($order['settlement_value'], 2, '.', '')),
					'sale_amount' 					=> $order['sale_amount'],
					'total_offer_amount'			=> $order['total_offer_amount'],
					'my_share' 						=> $order['my_share'],
					'customer_shipping_amount' 		=> $order['customer_shipping_amount'],
					'marketplace_fee' 				=> $order['marketplace_fee'], //(float)number_format($order['marketplace_fee']-$order['shipping_fee_waiver'], 2, '.', ''), // to overcome diff as shipping_fee_waiver are deducted from marketplace_fees and are not counted in taxes
					'tax_collected_at_source'		=> $order['tcs'],
					'tds'							=> $order['tds'],
					'taxes' 						=> $order['gst_on_mp_fees'],
					'protection_fund' 				=> $order['protection_fund'],
					'refund' 						=> $order['refund'],
					'commission_rate' 				=> number_format($order['commission_rate'], 2, '.', ''),
					'commission' 					=> number_format($order['commission'], 2, '.', ''),
					'commission_fee_waiver'			=> number_format($order['commission_fee_waiver'], 2, '.', ''),
					'collection_fee' 				=> $order['collection_fee'],
					'fixed_fee' 					=> $order['fixed_fee'],
					'pick_and_pack_fee'				=> $order['pick_and_pack_fee'], // Flipkart Fulfilled
					'customer_shipping_fee_type' 	=> $order['customer_shipping_fee_type'], // Flipkart Fulfilled
					'customer_shipping_fee' 		=> (isset($order['customer_shipping_fee']) ? $order['customer_shipping_fee'] : (isset($order['customer_shipping_incl._gst']) ? $order['customer_shipping_incl._gst'] : $order['shipping_fee'])),
					'shipping_fee' 					=> $order['shipping_fee'],
					'shipping_fee_waiver'			=> $order['shipping_fee_waiver'],
					'reverse_shipping_fee' 			=> $order['reverse_shipping_fee'],
					'shopsy_marketing_fee'			=> $order['shopsy_marketing_fee'],
					'customer_add_ons_amount'		=> $order['customer_add-ons_amount_recovery'],
					'product_cancellation_fee' 		=> $order['product_cancellation_fee'],
					'service_cancellation_fee' 		=> (isset($order['service_cancellation_fee']) ? $order['service_cancellation_fee'] : 0),
					'fee_discount' 					=> (isset($order['fee_discount']) ? $order['fee_discount'] : 0),
					'chargeable_wt_slab' 			=> $order['chargeable_wt._slab'],
					'shipping_zone' 				=> strtolower($order['shipping_zone']),
				);
			}

			if ($db->insert(TBL_FK_PAYMENTS, $details)) {

				if ($type == "orders") {
					if ($order['fulfilment_type'] == "Flipkart Fulfilment")
						return;

					$data = array();

					if (strpos($order['additional_information'], 'Flipkart Plus') !== FALSE) // when the order is Flipkart Plus Order
						$data['is_flipkartPlus'] = 1;

					if (strpos($order['additional_information'], 'COD_TO_PREPAID') !== FALSE) // when the order payment type is changed
						$data['paymentType'] = "prepaid";

					if (!is_null($order['shopsy_order'])) {
						$data['is_shopsy'] = 1;
					}

					// Get Settlement Value
					// $db->where('orderItemId', $order['order_item_id']);
					// $order_settlement = (float)$db->get(TBL_FK_ORDERS, 'netSettlement');
					// $settlement = $order_settlement + (float)$order['settlement_value'];
					// $data['netSettlement'] = $settlement;

					$shipping_zone = $db->get(TBL_FK_ORDERS, 'shippingZone');
					if ($shipping_zone['shippingZone'] != strtolower($order['shipping_zone'] && !empty($shipping_zone['shippingZone'])))
						$data['shippingZone'] = strtolower($order['shipping_zone']);

					if (!empty($data)) {
						$db->where('orderItemId', $order['order_item_id']);
						if ($db->update(TBL_FK_ORDERS, $data)) {
							if (isset($data['paymentType']) || isset($data['shippingZone']))
								$this->update_fk_payout($order['order_item_id']);
						}
					}

					// Upadte new settlement value
					// $this->update_settlement_status($order['order_item_id']);
					$this->fetch_payout($order['order_item_id']);
					$this->update_settlement_status($order['order_item_id']);
				}

				$return = 'INSERT: New settlement for Order Item ID ' . $order['order_item_id'] . ' and NEFT ID ' . $order['neft_id'] . ' added.';
				$log->write($return, 'settlements-orders');
			} else {
				// echo $db->getLastError() .'<br />';
				$return = 'INSERT: Unable to insert settlement for Order Item ID ' . $order['order_item_id'] . ' and NEFT ID ' . $order['neft_id'] . "\n:: " . $db->getLastError() . "\n:: " . $db->getLastQuery();
				$log->write($return, 'settlements-orders');
			}
		} else {
			$db->where('orderItemId', $order['order_item_id']);
			$db->where('paymentId', trim($order['neft_id']));
			$payment_details = $db->objectBuilder()->getOne(TBL_FK_PAYMENTS);
			if ($payment_details->paymentValue != (float)number_format($order['settlement_value'], 2, '.', '')) {
				if ($type == "orders" /*&& $order['order_item_id'] == "11347173740455100"*/) {
					$details = array(
						// 'orderItemId' 					=> $order['order_item_id'],
						// 'paymentId' 					=> $order['neft_id'],
						// 'paymentType'					=> strtolower(isset($order['order_type']) ? $order['order_type'] : $order['neft_type']),
						'account_id'					=> $this->account->account_id,
						'paymentDate'					=> (isset($order['payment_date']) ? date('Y-m-d', strtotime($order['payment_date'])) : date('Y-m-d', strtotime($order['neft_date']))),
						'paymentValue'					=> (isset($order['bank_settlement_value']) ? (float)number_format($order['bank_settlement_value'], 2, '.', '') : (float)number_format($order['settlement_value'], 2, '.', '')),
						'sale_amount' 					=> $order['sale_amount'],
						'total_offer_amount'			=> $order['total_offer_amount'],
						'my_share' 						=> $order['my_share'],
						'customer_shipping_amount' 		=> $order['customer_shipping_amount'],
						'marketplace_fee' 				=> $order['marketplace_fee'], //(float)number_format($order['marketplace_fee']-$order['shipping_fee_waiver'], 2, '.', ''), // to overcome diff as shipping_fee_waiver are deducted from marketplace_fees and are not counted in taxes
						'tax_collected_at_source'		=> $order['tcs'],
						'tds'							=> $order['tds'],
						'taxes' 						=> $order['gst_on_mp_fees'],
						'protection_fund' 				=> $order['protection_fund'],
						'refund' 						=> $order['refund'],
						// 'commission_rate' 				=> number_format($order['commission_rate'], 2, '.', ''),
						'commission' 					=> number_format($order['commission'], 2, '.', ''),
						'commission_fee_waiver'			=> number_format($order['commission_fee_waiver'], 2, '.', ''),
						'collection_fee' 				=> $order['collection_fee'],
						'fixed_fee' 					=> $order['fixed_fee'],
						'pick_and_pack_fee'				=> $order['pick_and_pack_fee'], // Flipkart Fulfilled
						'customer_shipping_fee_type' 	=> $order['customer_shipping_fee_type'], // Flipkart Fulfilled
						'customer_shipping_fee' 		=> (isset($order['customer_shipping_fee']) ? $order['customer_shipping_fee'] : (isset($order['customer_shipping_incl._gst']) ? $order['customer_shipping_incl._gst'] : $order['shipping_fee'])),
						'shipping_fee' 					=> $order['shipping_fee'],
						'shipping_fee_waiver'			=> $order['shipping_fee_waiver'],
						'reverse_shipping_fee' 			=> $order['reverse_shipping_fee'],
						'shopsy_marketing_fee'			=> $order['shopsy_marketing_fee'],
						'customer_add_ons_amount'		=> $order['customer_add-ons_amount_recovery'],
						'product_cancellation_fee' 		=> $order['product_cancellation_fee'],
						'service_cancellation_fee' 		=> (isset($order['service_cancellation_fee']) ? $order['service_cancellation_fee'] : 0),
						'fee_discount' 					=> (isset($order['fee_discount']) ? $order['fee_discount'] : 0),
						'chargeable_wt_slab' 			=> $order['chargeable_wt._slab'],
						'shipping_zone' 				=> strtolower($order['shipping_zone']),
					);

					// Update the details
					$db->where('orderItemId', $order['order_item_id']);
					$db->where('paymentId', trim($order['neft_id']));
					$db->update(TBL_FK_PAYMENTS, $details);

					if (strpos($order['additional_information'], 'Flipkart Plus') !== FALSE) // when the order is Flipkart Plus Order
						$data['is_flipkartPlus'] = 1;

					if (strpos($order['additional_information'], 'COD_TO_PREPAID') !== FALSE) // when the order payment type is changed
						$data['paymentType'] = "prepaid";

					if (!is_null($order['shopsy_order'])) {
						$data['is_shopsy'] = 1;
						$data['shopsySellingPrice'] = $order['amount'];
					}

					if (isset($data) && !empty($data)) {
						$db->where('orderItemId', $order['order_item_id']);
						$db->update(TBL_FK_ORDERS, $data);
					}

					// Upadte new settlement value
					$this->fetch_payout($order['order_item_id']);
					$this->update_settlement_status($order['order_item_id']);

					$return = 'UPDATE: Updated settlement for Order Item ID ' . $order['order_item_id'] . ' and NEFT ID ' . $order['neft_id'] . ' added.';
					$log->write($return, 'settlements-orders');
				}
			} else {
				$return = 'INSERT: Already exists settlement for Order Item ID ' . $order['order_item_id'] . ' and NEFT ID ' . $order['neft_id'];
				$log->write($return, 'settlements-orders');
			}
		}

		return $return;
	}

	function update_settlement_status($orderItemId)
	{
		global $db, $log;

		// Get Settlement Value
		$settlement = $this->calculate_net_settlement($orderItemId);
		$payout = $this->calculate_actual_payout($orderItemId);
		$difference = (float)number_format($settlement - (float)$payout, 2, '.', '');

		$data['settlementStatus'] = 0;
		if ($difference <= 0.5 && $difference >= -0.05)
			$data['settlementStatus'] = 1;

		$status = ($data['settlementStatus'] ? 'settled' : 'unsettled');

		/*echo '<pre>';
		echo $orderItemId.'<br />';
		echo($settlement).'<br />';
		echo($payout).'<br />';
		echo ($difference).'<br />';
		print_r($data);
		echo '====<br />';*/

		$db->setQueryOption('SQL_NO_CACHE');
		$db->where('orderItemId', $orderItemId);
		if ($db->update(TBL_FK_ORDERS, $data)) {
			$return = 'UPDATE: Settlement for Order Item ID ' . $orderItemId . ' updated to ' . $status;
			$log->write($return, 'settlements-status');
			if ($status == "settled") {

				// INCASE IF WE HAVE ANY INCIDENTED TAGGED TO THE ORDER
				$db->where('referenceId', '%' . $orderItemId . '%', 'LIKE');
				$db->where('issueType', 'Payments');
				$db->where('incidentStatus', 'active');
				$incident = $db->getOne(TBL_FK_INCIDENTS, 'incidentId, referenceId');
				if ($incident) {
					$referenceIds = str_replace('"' . $orderItemId . '": false', '"' . $orderItemId . '": true', $incident['referenceId']);

					$details = array('referenceId' => $referenceIds);
					$return = array();
					if (strpos($referenceIds, 'false') === false) {
						$return['all'] = 'closed';
						$details['incidentStatus'] = 'closed';
					}

					$db->where('incidentId', $incident['incidentId']);
					$db->update(TBL_FK_INCIDENTS, $details);
				}
			}
		}

		// if (isset($old_logfile))
		// 	$log->logfile = $old_logfile;

		return $return;
	}

	// GET FK PAYOUT AND UPDATE
	function update_fk_payout($order_item_id)
	{
		global $log, $db;

		if (empty($order_item_id)) {
			return false;
		}

		// $log->logfile = TODAYS_LOG_PATH.'/orders-payout.log';

		$payout = $this->get_fk_payout($order_item_id);

		if ($payout) {
			$details = array(
				'shippingZone' => $payout['shipping_zone'],
				'shippingSlab' => $payout['shipping_slab'],
				'commissionRate' => $payout['commission_rate'],
				'commissionFee' => $payout['commission_fees'],
				'commissionIncentive' => $payout['commission_incentive'],
				'collectionFee' => $payout['collection_fees'],
				'pickPackFee' => $payout['pick_and_pack_fee'],
				'fixedFee' => $payout['fixed_fees'],
				'forwardShippingFee' => $payout['shipping_fee'],
				'refundAmount' => $payout['refund_amount'],
				'reverseShippingFee' => $payout['reverse_shipping_fee'],
				'shippingFeeWaiver' => $payout['shipping_fee_waiver'],
				'shopsyMarketingFee' => $payout['shopsy_marketing_fee'],
				'commissionFeeWaiver' => $payout['commission_fee_waiver'],
				'discountRefundAmount' => $payout['refund_discount_amount'],
				'otherFees' => $payout['other_fees'],
				'tcs' => $payout['tcs'],
				'tds' => $payout['tds'],
			);

			// if (isset($_GET['test']) && $_GET['test'] == "1"){
			// 	echo '<pre>';
			// 	var_dump($details);
			// }

			$db->where('orderItemId', $order_item_id);
			// $db->setQueryOption ('SQL_NO_CACHE');

			if ($db->update(TBL_FK_ORDERS, $details)) {
				$this->update_settlement_status($order_item_id);
				$log->write('UPDATE: Updated orders payout details with order Item ID ' . $order_item_id, 'orders-payout');
			} else {
				$log->write('UPDATE: Unable to update orders payout details with order Item ID ' . $order_item_id, 'orders-payout');
			}
		} else {
			$log->write('UPDATE: Unable to fetch orders details with order Item ID ' . $order_item_id, 'orders-payout');
			return false;
		}
		return true;
	}

	// Triggered if order is shipped
	function get_fk_payout($orderItemId)
	{
		global $db;

		// $db->setQueryOption ('SQL_NO_CACHE');
		$db->where('orderItemId', $orderItemId);
		$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
		$db->join(TBL_PRODUCTS_CATEGORY . " c", "c.catid=p.catID", "LEFT");
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
		$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
		$db->join(TBL_PRODUCTS_BRAND . " cb", "cb.brandid=pc.brand", "LEFT");
		$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");
		$db->join(TBL_FK_WAREHOUSES . " wa", "wa.warehouse_name=o.locationId", "LEFT");
		// $order = $db->objectBuilder()->getOne(TBL_FK_ORDERS .' o');
		$order = $db->objectBuilder()->getOne(TBL_FK_ORDERS . ' o', 'o.*, p.*, c.*, COALESCE(cb.brandName, pb.brandName) as brandName, COALESCE(o.invoiceDate, o.shippedDate, o.dispatchByDate) as outwardDate, o.hsn, JSON_UNQUOTE( JSON_EXTRACT(wa.warehouse_address, "$.state_code") ) AS dispatch_stateCode, JSON_UNQUOTE( JSON_EXTRACT(wa.warehouse_address, "$.zip") ) AS dispatch_pincode');

		if ($order) {
			$this->is_shopsy = $order->is_shopsy;
			if ($order->status == "CANCELLED") {
				$payout = array(
					'commission_fees' => 0,
					'commission_rate' => 0,
					'commission_incentive' => 0,
					'fixed_fees' => 0,
					'collection_fees' => 0,
					'pick_and_pack_fee' => 0,
					'shipping_zone' => "",
					'shipping_slab' => "",
					'shipping_fee' => 0,
					'reverse_shipping_fee' => 0,
					'shopsy_marketing_fee' => 0,
					'shipping_fee_waiver' => 0,
					'commission_fee_waiver' => 0,
					'refund_amount' => ((($order->totalPrice)) * -1),
					'refund_discount_amount' => (($order->flipkartDiscount * $order->quantity) * -1),
					'other_fees' => $order->otherFees,
					'tcs' => 0,
					'tds' => 0,
				);
				return $payout;
			}

			$order_type = (($order->order_type == "FBF_LITE"  || $order->order_type == "NON_FBF") ? "NON_FBF" : $order->order_type); // All order fulfilled by seller FBF_LITE and NON_FBF are termed as NON_FBF

			$this->order_date = date('Y-m-d H:i:s', strtotime($order->orderDate));
			if ($order->replacementOrder) {
				$db->where('orderId', $order->orderId);
				$this->order_date = date('Y-m-d H:i:s', strtotime($db->getValue(TBL_FK_ORDERS, 'orderDate')));
			}

			$this->tier = $this->get_seller_tier(); // returns default gold.
			// var_dump($this->tier);
			$shipping_details = $order->deliveryAddress;
			$shipping_details = json_decode($shipping_details);
			$order->dispatch_stateCode = (strpos($order->locationId, 'LOC') !== FALSE && is_null($order->dispatch_stateCode)) ? 'IN-GJ' : $order->dispatch_stateCode;
			if (is_null($shipping_details->stateCode) || empty($shipping_details->stateCode))
				$shipping_details->stateCode = get_stateCode($shipping_details->state);

			$shipping_zone = $this->get_shipping_zone($shipping_details->stateCode, $order->dispatch_stateCode);
			$shipping_slab = $order->shippingSlab;
			// if (empty($shipping_zone)) {
			// 	$shipping_zone = 'national';
			if ((substr($shipping_details->pinCode, 0, 3) === '395' || substr($shipping_details->pinCode, 0, 3) === '394') && $shipping_details->stateCode == 'IN-GJ' && $order->dispatch_stateCode == "IN-GJ")
				$shipping_zone = 'local';
			// else if (($shipping_details->stateCode == 'IN-GJ' || $shipping_details->stateCode == 'IN-MH') && $order->dispatch_stateCode == "IN-GJ" )
			// 	$shipping_zone = 'zonal'; 
			// }

			if (empty($shipping_slab)) {
				$shipping_slab = '0.0-0.5';
			}

			// if (!is_null($order->shipmentId) && $order->order_type != "FBF"){
			// 	// V3 updated the response to total order value instead of each item value - HARD FIX
			// 	$order->totalPrice = $order->totalPrice/$order->quantity;
			// 	$order->flipkartDiscount = $order->flipkartDiscount/$order->quantity;
			// }

			$orderFlipkartDiscount = (($order->is_flipkartPlus) ? (float)($order->flipkartDiscount) : 0);
			$gross_price = $order->totalPrice + $orderFlipkartDiscount;
			$comm_rate = $this->get_platform_fees(($order->totalPrice / $order->quantity), $order->categoryName, $order->brandName, $order->listingId, $order->is_flipkartPlus);

			$incentinve_rebat_0 = array('22016947908635600', '12157059735294400', '22174383358383200', '12176866412650700', '12192223008830300', '22203228789103200', '22205923866047700', '12213612125748200', '22214223986806600', '12016290070506700', '22016814108884700', '12016865261353600', '12016940465118000', '12017497313621700', '12017627042221900', '12017647313417600', '12017738075466400', '12017757022355300', '12017757208387200', '12017912807674800', '22018388020171700', '12018444462491500', '12018443851853100', '12018484490630100', '12018548621108700', '12018573813904300', '12018647749275700', '22018662755358300', '22018669507736400', '12018696651205700', '12018793805705500', '12018814889192000', '12019127552873300', '22019341296714100', '12019419285685500', '12019451665228100', '12019500203758500', '12020185103546300', '12020231104568401', '12020490393653000', '12020546052995300', '12020647452950300', '22020942152822500', '12021059124170301', '22021067073374300', '12021079566414200', '22021107535186500', '22021136493677701', '12021168153401900', '12021216156473400', '12021231074362600', '12021239654834700', '12021340697712500', '12021386605064902', '12021396940010100', '12021463605656900', '12022587125845500', '22023104991464700', '12024549666117300', '22025580821066400', '12030853713688100', '22186330934810100', '12190284965891100', '12191389136635100', '22195661380536300', '12205688374913200', '12211469539420800', '12163451286188800', '22205832110711000', '12205842525778200', '22206895959610100', '12202779941758503', '12209362836547000', '22211382507497200');
			if (in_array($order->orderItemId, $incentinve_rebat_0))
				$comm_rate['basic_rate'] = 0;

			$incentinve_rebat_7_5 = array('11939630396072600', '11939637204665600', '11939780317378100', '11939783723450300', '11939849138613200', '11939882355806700', '11939920475914000', '11939937766030600', '11939951841471502', '11940046791553100', '11940127172566800', '11940161751318000', '11940166073202800', '11940176667584300', '11940196674862200', '11940228872651900', '11940431543607500', '11940505582184100', '11940522255371400', '21940547045664100', '11940666705798700', '11940669670126000', '11940686158114000', '11940725486366900', '11940724903716700', '11940747697605200', '11940811243167600', '11940818837107000', '11940821910617300', '11940828823135600', '11940836165145000', '11940852543100400', '11940859037662400', '11940891106362501', '11940895709495100', '11940923004452800', '11941013600255300', '21941055569878400', '11941121931954300', '11941489807311600', '11941498137343700', '11941526011565300', '11941529504138300', '11941670359497900', '11941677400484900', '11941747119224700', '11941760257395300', '11941762874355400', '11941876903548400', '11941942549247200', '11941944383138100', '11942026223888400', '11942375221077100', '11942521663857600', '11942530468360900', '11942532528698900', '11942535973587200', '11942614126205300', '11942665001033300', '11942715981144500', '11942716838076300', '11942753041964800', '11942859254196900', '11942920070010400', '11943081977300700', '11943125808472100', '11943139331201000', '11943195376901001', '11943209033031500', '11943243541097500', '11943277578487200', '11943302397508600', '11943323302513500', '11943377975323100', '11943397514048500', '11943408659835500', '11943435836514000', '11943441862512700', '11943456460211900', '11943572654937700', '11943584670782500', '11943584415274800', '11943589282490500', '11943631328664800', '11943746524175900', '11943753898625000', '11943831904051700', '11943909456424600', '21943959121707600', '21944058547696800', '11944059725585400', '11944105666281100', '11944116401893400', '11944150502887500', '11944454936486800', '11944845459455500', '11945060494848001', '11945260369783900', '11945852107920800', '11945948625224600', '11945966601708800', '11945964063508801', '11945980474534100', '11945999614993800', '11946010526144600', '11946040831918200', '11946053322575800', '11946058218271600', '11946066287420800', '11946067817890000', '11946073908627200', '11946093143067101', '11946090745432900', '11946126947737300', '11946140052785100', '11946150844590600', '11946152021814600', '11946153344640400', '11946154867163000', '11946156875492600', '11946170835108900', '11946173533954600', '11946180345313000', '11946196666330700', '11946200939192400', '11946202774554801', '11946209564934000', '11946209969631200', '11946213518565000', '21946222832122700', '11946226079587900', '11946235309263800', '11946237342376000', '11946256411300100', '11946257989963400', '11946259438696500', '11946261945094800', '11946269216778500', '11946271685956900', '11946279232844705', '11946287406274400', '11946309304722100', '11946318357014100', '11946318767275200', '11946321723143100', '11946323300977900', '11946339672406200', '11946498308274500', '11946596603747900', '11946631832841000', '11946648260211400', '11946652290770100', '11946655871227801', '11946684985621500', '11946710385961000', '11946712887087100', '11946730423842200', '11946777449858400', '11946819537417200', '11946879521371100', '11946887930583300', '11946902729122700', '11946911399571100', '11946984931631800', '21947009151985500', '11947021195892800', '11947028483366700', '11947035306563100', '11947061098232200', '11947100946725700', '11947101931203900', '11947103820382600', '11947118303926500', '11947173331903500', '11947300430796300', '11947432270328000', '11947451453455300', '11947469471765100', '11947500600683100', '11947501486056000', '11947499304096700', '11947521069995200', '11947522269524200', '11947553521338800', '11947554499875500', '11947566837408500', '11947573115364000', '11947573361607900', '11947596031940300', '11947603572690000', '11947607253784700', '11947607216016700', '11947612431432500', '11947613754680300', '11947632222602200', '11947639177286900', '11947645483083800', '11947653225045900', '11947666759604700', '11947680567801500', '21947709519952800', '11947703421345500', '11947722355716900', '11947725560157800', '11947729936571300', '11947768747595600', '11947782476997700', '11947802141526400', '11947807804413800', '11947810471952200', '11947827759236400', '11947848433628300', '11947884200372100', '11947887964526800', '11947894193216500', '11947911681634900', '11947913203333600', '11947933162652100', '11948074712828302', '11948268197814000', '21948274270456000', '11948353182066300', '11948364715568500', '11948415384762900', '11948426393767200', '11948431577482300', '11948485752813700', '11948510507906100', '11948516446792400', '11948521669576000', '11948534318968300', '11948635104963700', '11948640543181500', '11948655107273704', '11948732074120500', '11948751157828300', '11948801782370600', '11948804405270000', '11948823178984000', '11948929835643100', '11949143123701600', '11949164309272900', '11949174250293000', '11949179450197300', '11949191706834800', '11949189931157900', '11949195314883800', '11949200078716300', '11949221914065800', '11949235106935100', '21949236541063800', '11949244593344800', '21949254863186400', '21949256450833100', '11949268258631000', '21949273983341200', '11949281096377700', '11949290039410000', '11949288233687000', '11949297972277700', '11949309150562300', '11949311459716300', '11949316969025800', '11949317466798200', '11949319266510000', '11949332738724700', '11949335508340600', '11949346120457600', '11949348866922300', '11949373186395700', '11949381659722600', '21949407482437200', '11949418160340400', '11949421498125500', '11949422275960900', '11949428095952400', '11949429390562901', '11949455458577700', '11949471913063800', '11949472803477000', '11949475255836500', '11949491396098900', '11949549698168000', '11949569762171200', '11949570329486900', '11949584917673200', '11949594596092600', '11949610874450900', '21949612238726200', '11949625054440300', '11949628338961300', '11949635904306200', '11949640019096700', '11949639895707400', '11949650505696000', '11949653208180000', '11949683915931600', '11949688212067900', '11949690247206700', '11949695845814100', '11949713031421000', '11949719050236200', '11949719694364000', '11949724636340400', '11949752126450800', '11949783483105600', '11949795561990100', '11949804388601300', '11949843350527700', '11949982401851300', '11949991464034000', '11950036953566800', '11950039057098200', '11950055737597600', '11950106079761700', '11950107297142800', '11950113947348100', '11950118236093200', '11950138754926800', '11950142456121000', '11950153921497200', '11950167038236800', '21950176541143600', '11950206264266300', '11950213106056600', '11950237474368701', '11950245646275500', '11950251019296900', '11950268691202100', '11950309409368300', '11950318530748200', '11950333256187300', '11950339425076900', '11950345852638101', '11950366412767800', '11950381493078900', '11950396493397400', '11950426492191900', '11950430483440600', '11950432576481800', '11950441452934100', '11950451897017800', '21950461153134303', '11950465087521700', '21950491798816200', '11950496757663700', '11950496999993502', '11950498942170900', '11950504972814400', '11950508008701900', '11950517220082900', '11950524203466200', '11950526302332100', '11950531726886800', '11950542044142700', '11950544411088700', '11950552550462500', '11950556565361500', '11950559787344300', '11950564461521300', '11950568000276300', '11950618518845001', '11950620101442700', '11950620276590200', '11950622790906600', '11950624885807200', '11950647250730201', '11950675725197500', '11950885498908600', '11950914200227400', '11950935143237600', '11950977987785700', '11950978593978900', '11950978376723000', '11951006177116900', '11951006583022200', '11951007544080500', '11951023051848800', '21951054745166200', '21951055342350100', '11951057873890300', '11951067026588800', '11951093878875500', '11951098040428800', '11951101539344000', '11951109277088800', '11951110950317400', '11951116703042600', '11951121145313000', '11951134099426600', '11951137664473100', '11951164188184600', '11951168880765000', '11951170685847200', '11951164974156000', '11951183306518100', '11951210761484500', '11951233759244100', '11951238959516600', '11951245963810100', '11951257010312800', '11951274637323400', '11951277764573600', '11951283116593200', '11951288407756800', '11940228872651901', '11951304056310500', '11951310729626200', '11951318422958000', '11951345124205000', '11951348763952400', '11951359972953600', '11951361310187900', '11951364032608000', '11951367638780100', '11951368528543000', '11951381162685400', '11951425037668300', '11951436012870200', '21951436388988600', '11951438667720100', '11951444516524900', '11951445173151700', '11951452971385300', '11951453376637700', '11951480347660400', '11951484937151400', '11951539674482600', '11951804864013400', '11951848515052900', '11951863928187100', '11951869822542000', '11951878415950300', '11951948728970000', '11951978817538200', '11951980194278100', '11951994634050600', '11952047497534500', '11952050721012100', '11952081715871700', '11952116271464100', '11952122005287900', '11952171418373700', '11952188984193300', '11952225217476300', '11952271762404600', '11952278196407000', '11952286553751800', '11952346615343200', '11952348816760400', '11946010526144601', '11952478086174000', '11952617089293300', '11952647545823900', '11952669170605500', '11952705383480900', '11952697720058400', '11952730390015500', '11952736989537300', '11952743714472800', '11952766400390000', '11952779993985800', '11952782884907900', '21952775645157400', '11952799151845900', '11952812697960200', '11952825780613800', '21952835640894700', '11952858508524600', '11952859863688100', '11952893051231600', '11952900035496200', '11952913592010000', '11952937799665800', '11952969703644900', '11952976086961900', '11953009022543500', '11953012226801500', '11953062731145400', '11953087599936800', '11953089789286500', '11953112602057500', '11953114876417700', '11953132436605300', '11953134677140100', '11953135092843600', '11953143026633400', '11953169970062100', '11953196282520600', '11953315933057200', '11953356713536700', '11953484868156000', '11953502480043400', '11953502753950800', '11953513733098700', '11953528938350800', '21953538935241400', '11953554420881600', '11953568043412800', '11953568885070600', '11953576086108700', '11953580695838000', '11953585203225600', '11953602056193000', '11953604584652100', '11953625871365604', '11953635160823000', '11953633675721900', '11953700948757600', '11953707856498700', '11953730028531000', '11953749547275300', '11953753523553800', '11953763587665100', '11953774992950500', '11953839574142000', '11953858347798900', '21953896863984000', '21953920720443500', '11953948937248500', '11953955608435700', '11953995644913300', '11953996599367300', '11954026435473700', '11954048822462900', '11954120015975900', '11954179438482301', '11954185703794600', '11954378156515000', '11954382257051200', '11954426161352100', '11954428230794400', '11954539103815600', '11954573383244500', '21954597807335100', '11954602163896500', '11954615042868300', '11954667612823500', '11954685191537400', '11954716275506700', '11954723993442100', '11954787250173100', '11954822863166800', '11954831337307300', '11954832318163900', '11954842232303900', '11954846576975800', '11954849082605700', '11954864551282400', '11955000662095700', '11955229091051300', '11955252799474500', '11955254665760700', '11949244593344802', '11955267877373700', '11955269032366500', '11955270740157800', '11955277576287100', '11955279932438900', '11955282385508200', '11955298885386800', '11955317041503900', '11955320558013600', '11955332502768500', '11955344022042000', '11955353254217000', '11955372569107300', '11955427035351900', '11955429086396400', '11955500631447900', '11955508885472500', '11955517621298400', '11955552083116500', '11955566137437100', '11940747697605201', '11955575019238200', '11955582138817800', '11955593581051900', '11955593403027300', '21955605347700800', '11955614815077100', '11955628689798800', '11955654065598500', '11955664573904000', '11955674958082200', '11955678358086900', '11955684104222400', '11955690801475200', '11955698559654300', '11951401318277901', '11955732778992400', '11955735621780600', '11955768754208600', '11955768589047900', '11955780214808700', '11955822690690500', '11955829129293000', '11955846183336505', '11955921829132100', '21955959059340600', '11956044543784100', '11956077036981200', '11956083363996000', '11956115807052000', '11956179864173900', '11956179916452000', '11956240536942400', '11956245906093600', '11956257642875400', '11956259926437600', '11956264230316300', '11956306352903200', '11956309745277300', '11956315460880400', '11956330280917000', '11956331285620700', '11956334715676300', '11956342600241500', '11956344475802600', '11956346610540800', '11956357497932800', '11956359814634800', '11956363675148400', '11956394678260000', '11956400765392500', '11956412088417900', '11956442781178300', '11956455452171200', '11956477466063900', '11956482157897604', '11956507984900300', '11956541947415400', '11956568928222000', '11956581711635800', '11956604884228000', '11956605804068600', '11956606429822400', '11956613331005300', '21956615410284300', '11956617079312800', '11956620266227900', '11956625129806400', '11956627081778300', '11956700122086900', '11956725674380800', '11956828623116400', '11956908294677300', '11956909932842301', '11956956773264000', '21956959029183400', '11956969177058700', '21956972716638600', '11956995945631300', '11957014263176400', '11957018109293000', '11957040413070600', '11957107560921400', '11957133040048500', '11957135805654800', '11957167052693200', '11957169656652601', '11957335607305000', '11957386760377400', '11957396798821900', '11957414986368700', '11957461924162700', '11957462744726400', '11957469634770900', '11957477162190400', '11949143227391801', '21943246533385102', '21952835640894701', '11956585462507301', '11953944285653005', '11956346610540801', '11957133040048501', '11948631863773201', '11939435366064900', '11939435658101300', '11939668862123800', '11939790692390300', '11939802522262200', '11939812468940700', '11939832012022300', '11939862135714900', '11939949008622100', '11939975989892500', '11939994834131000', '11940019910750100', '11940025106840000', '11940033749044100', '11940093725924200', '11940164107553700', '11940182890582500', '11940186407828800', '11940192214861900', '11940214120432200', '11940220766053900', '11940251075372000', '11940254074663400', '21940489584208100', '11940619855505200', '11940664884906800', '11940762737204000', '11940873432255200', '11940902064527000', '11940922128338200', '11940928150342200', '11940951318051000', '11940949649994500', '11940964311680100', '11940981109448700', '11941016404488800', '11941065013990000', '11941076430667500', '11941077446493600', '11941519698777800', '11941537861417400', '11941579134323000', '11941595663231600', '21941621361232000', '11941653669775000', '11941653830054600', '11941665902606000', '11941666538848000', '11941724002140400', '11941886340653900', '11941914004686000', '11941982453320500', '11941984877181400', '11942013153834400', '11942017786592100', '11942139760563400', '11942374162373400', '11942395870801100', '11942420094390000', '11942503475330700', '11942544840861900', '11942563126234700', '11942581311361200', '11942597111368000', '11942723640446200', '11942736167560800', '11942737348726400', '11942739082373702', '11943081201423900', '11943117978536500', '11943139763313900', '11943233902163500', '11943269055153400', '11943276665750800', '11943305132643000', '11943318309643700', '11943319011871800', '11943341784515300', '11943342092772400', '11943347027404400', '11943390445473600', '11943402112315000', '11943405474372600', '11943442212428700', '11943472111983500', '11943559657501700', '21943561800034200', '11943564311023000', '11943643978780600', '11943688549741800', '11943709004467400', '11943755613968700', '11943769049520800', '11943906645302300', '11943994790874400', '11944037112557000', '11944075419067100', '11944086925730700', '11944123091435100', '11944138420848400', '11944146350073100', '11944190466650000', '21944212587310300', '11944261873468101', '11944282882991300', '11944349240806700', '11944351618448900', '11944363471300700', '11944396449006400', '11944406233047800', '11944450009096200', '11944527181651800', '11944965933470900', '11944978084972100', '11944996247043400', '11945086902124900', '11945108621497700', '11945130725806700', '11945309772895000', '11945337311861300', '11945379061042500', '11945427687176900', '11945427281881900', '11945628267930300', '11945818300994300', '11945819081518000', '11945877572512900', '11945904970768400', '11945922312492400', '11945925499556000', '11946067709102100', '11946074785804800', '11946115779001800', '11946124783815100', '11946138851731000', '11946190282323900', '11946193501742400', '11946195142510600', '11946205642984200', '11946251031616000', '11946306701551300', '21946307845496201', '21946318786491200', '11946458379170400', '11946617790607200', '11946642716907100', '11946651641184200', '11946658562310800', '11946728732031300', '11946732938323600', '11946749734153200', '11946776364606500', '11946802113884000', '11946811945674600', '11946878652377900', '11946927348414900', '11946985703540600', '11947061095101500', '11947105291092300', '11947129025698300', '11947128792304001', '11947136296810900', '11947255223454300', '21947538058177100', '11947621130161300', '11947672456495100', '11947705969714500', '11947853807090600', '11947880513587900', '11947900793794800', '11947926849632600', '11947939832032600', '11947952163811000', '11947957981695400', '11947981882350100', '11948006801972100', '11948011621147300', '11948012847134600', '11948018292957800', '11948024303065300', '11948037479854200', '11948065464357900', '11948067781423300', '11948076162154000', '11948313927764800', '11948344520895600', '11948345600701800', '11948405794395000', '11948408018356000', '11948431726307900', '11948470603295900', '11948506512793300', '11948570644712000', '11948583752290900', '11948620798627600', '11948645899680800', '11948666171395400', '11948695485984900', '11948700946962000', '11948736975061300', '11948745864808700', '11948784740823900', '11948805633468400', '11948820641103300', '11948837460726900', '11948849745750000', '11948890149828600', '11948904825743900', '11948910647905500', '11948910259464000', '11948925688703100', '11948927480848700', '11948933198777500', '11948932836960200', '11948934617937300', '11948941843163300', '11949019522764200', '11949074754355400', '11949090404808000', '11949097515311001', '11949114036886600', '11949124978182700', '11949148019405800', '11949151244088600', '11949157037781300', '11949185670005400', '11949193398093100', '11949218538524400', '11949250759171700', '11949253619793500', '11949260676732100', '11949271498901000', '11949273243740200', '11949273405276200', '11949276569475300', '21949281665960600', '11949290458148500', '11949291654314700', '11949292330233500', '11949296068563500', '11949305866576400', '11949308740174100', '21949315880950200', '11949320747174000', '11949322171141200', '11949331953118100', '11949343774305300', '11949352371900400', '11949356237581200', '11949357938950000', '11949363992985500', '11949364033628300', '11949373565912400', '11949374492505001', '11949377452648700', '11949385824660600', '11949386424218500', '11949389439762400', '11949433790551700', '11949430927005500', '11949437210872000', '11949438907802100', '11949449677023000', '21949463784757400', '11949465373112000', '11949467788730400', '21949468524180400', '21949468524180401', '11949470604376500', '11949476937628900', '11949485451895000', '11949503182098700', '11949505996784400', '21949513099166400', '11949515547922900', '11949529844356800', '21949538057567600', '11949581069390900', '11949594735882600', '11949608155048000', '11949613684353300', '11949617854984100', '11949619845295400', '11949623822923600', '11949627858004500', '11949630630543000', '11949639056835600', '11949642618230700', '11949647447118200', '11949656310147300', '11949659645802600', '11949664896803900', '11949667075002300', '11949670657272100', '11949691934872000', '11949696873511100', '11949709625073300', '11949712184134600', '11949763960856200', '11949766355324700', '11949814093013400', '11949818452564100', '11949828352242200', '11949877187475500', '11949900790807300', '11949914599126900', '21949926539094700', '11949968241455201', '11950035374176300', '11950044394990200', '11950057879418700', '11950082743364100', '11950090710165800', '11950094106856800', '11950099418011100', '11950104024472300', '11950107565280700', '11950151227581100', '11950150961243600', '11950153922260500', '11950175467107800', '11950190101805600', '11950190639944200', '11950191613241800', '11950191626840000', '11950195441936100', '11950195688660100', '11950197110834600', '11950197248224600', '11950197503311200', '11950198063536000', '11950198605456000', '11950201264293800', '11950203661078900', '21950208469206800', '11950209338453900', '11950208692950100', '11950219001181900', '11950223918656900', '21950225539101300', '11950230389858001', '11950231314407200', '11950226878346100', '11950245484363800', '11950261175877100', '11950261909262500', '11950264969446200', '11950281869350600', '11950285245535100', '11950297203254600', '11950297116425600', '11950301224051600', '11950311626483300', '11950315957004100', '11950321975091900', '11950331132776600', '11950357458442100', '11950379569665500', '11950383330925600', '11950389994761400', '11950396649978500', '11950398910576100', '11950400333541800', '11950402521596800', '11950412721118100', '11950418855211300', '11950419955883800', '21950421601433000', '11950434330958100', '11950441197420400', '11950445088888300', '11950437376145900', '11950471812345500', '11950475949000800', '11950480757978500', '21950487859868400', '11950493813105400', '11950513402290500', '11950507226640500', '11950528427942500', '11950530108177400', '11950537156987600', '11950553177107700', '11950556362788400', '11950558380692800', '11950560042893700', '11950577659162500', '11950585630886900', '11950591476160200', '11950591476160201', '21950644444522202', '11950685192200001', '21950707871086900', '11950715052312400', '11950756275102800', '11950841791982200', '11950851359983500', '11950856236384300', '11950886340488800', '11950886257272600', '11950896781224200', '11950905699184200', '11950932987567700', '11950933972627100', '11950937235007600', '11950937281100701', '11950942443944500', '11950952464867100', '11951000217456000', '21951010842782800', '11951046908545300', '11951056294491800', '11951056760732700', '11951067771396500', '11951073778285200', '11951078813773800', '11951101734796700', '11951103170045400', '11951104203004600', '11951112407567700', '11951127675031600', '11951138802406900', '11951149266116000', '11951189276425900', '21951196235355600', '11951208502951700', '11951211059861800', '11951218133066200', '11951219287115000', '11951239744371000', '11951240155072700', '11951268527938800', '11951282569385400', '11951293655991100', '11951296113942400', '11951301994167400', '11951312462422600', '11951352556815500', '11951369845732600', '11951373957845300', '11951392580572600', '11951392654887400', '11951409382077600', '11951415417472000', '11951408527358200', '11951431316621200', '11951439978733200', '11951447281965300', '11951460146575100', '11951463508103200', '11951470414573800', '11951470536943900', '11951470320195300', '11951562927723600', '11951596833210500', '11951762150807400', '11951849113608500', '11951881162496900', '11951905145203300', '11951906165628900', '11951975555881600', '11951982592098700', '11952005452346200', '11952016545075600', '11952016739847200', '11952037497761700', '11952079097304400', '11952101984021800', '11952107947087900', '11952112497970400', '11952148791698100', '11952230307406900', '11952242612546700', '11952249388394300', '11952258310370200', '11952262272585500', '11952263911732200', '11952274994673700', '11952287936717000', '11952426853423400', '11952438418398100', '11952697564067401', '11952721909831000', '11952773479705500', '11952790774915000', '11952820088870000', '11952825891205700', '11952839832854300', '21952836305558200', '11952852749126600', '11952861072365500', '11952896218705600', '11952897039891100', '11952907641667400', '11952920313660500', '11952925290852700', '11952942069261600', '11952944973623900', '11953022771261500', '11953027510926500', '11953029502757000', '11953032264183400', '11953052256176700', '11953071421847600', '21953081336998600', '11953123623464200', '11953131675327600', '11953158562240500', '11953193148003200', '11953356760328500', '11953400009812800', '11953450377021700', '11953594365162300', '11953630576504200', '11953636262948100', '11953667015823000', '11953686001183600', '11953686767951100', '11953692595652100', '11953707331723800', '11953737197238500', '11953749543401500', '21953754816694000', '11953783449770000', '11953812742957400', '11953844580815600', '11953846683093100', '11953867270551700', '11953934747296400', '11953939275863700', '11953953258548200', '11953953705447300', '11953958668780000', '11953967727164900', '21953991067505900', '11953994713034100', '11953996524058800', '11950933972627103', '11954011354934200', '11954013630876300', '11954020490732500', '21954072452137400', '11954090140885900', '11954213823832400', '11954335645336200', '11954338767060001', '11954388768061300', '11954444232033600', '11954451401531600', '11954505801291400', '21954532588943900', '11954542842855800', '11954567686065900', '11954578985192000', '11954585432225900', '11954667371225500', '11954678186415700', '11954686967524000', '11954706633376300', '11954725650477100', '11954728495798000', '11954724548254200', '11954747378820100', '11944965933470901', '11954786465571700', '11954839894126200', '11954864943275500', '11954890678375400', '11954908884907200', '11954943298860202', '11954996689164800', '11955036054742800', '11955065874075200', '11955107914783900', '11955242498512300', '11955255469552800', '11955258240153100', '11955359799321900', '11955386900771000', '11955516235181900', '11955531421560200', '11955547396726900', '11955578356760400', '11955615178267400', '11955644920262600', '11955648288936700', '11955661743948000', '11955664885547100', '11955676378267500', '11955678989105900', '11955681407775000', '11955689375897900', '11955690711400900', '11955759810537400', '11955773631505100', '11955807116203700', '11949625788023101', '11956101332044000', '11956102228342500', '11956111358612900', '11956154034803000', '11956152168227500', '11956158016408700', '21956170507482200', '11956176027987000', '11956183968145500', '11956220037021200', '11956223209458900', '11956231903723100', '11956241246687100', '11956259385304800', '11956261719451900', '11956315310117400', '11956315305358400', '11956364373102500', '11956423103177100', '11956428186597400', '11956435155764800', '21956461597745600', '11956491108387900', '11956499333270700', '11956535335231200', '11956587743191600', '11953111129868701', '11956615192181200', '11956628505728500', '11956630568012300', '11956665473331700', '11956999387121300', '11957014762961100', '11950389994761401', '11957054964512100', '11957095683044000', '11957128756376301', '11954839894126201', '11957297047231200', '11957304347416100', '11957321594566700', '11957343443910700', '11957349322740400', '11957412361675600', '11957425314653300', '11948653376856801', '11957475344260000', '11957490420602600', '11957499822688600', '11957511201302600', '11957509570461700', '11952338478202801', '11951203140464501', '11950434330958101', '11955107914783902', '11950432577855601', '11951470320195301', '21951010842782801', '11953707331723801', '11949218538524401', '11955107914783903', '21948914082788401', '11949476937628901');
			if (in_array($order->orderItemId, $incentinve_rebat_7_5))
				$comm_rate['basic_rate'] = 7.5;

			$incentinve_rebat_10 = array('11940192594555700', '11940946668437100', '11942082799787300', '11945877819234900', '11947831687994300', '11949684323925100', '11950536750403900', '11951410882653700', '11954985181092000', '11946740161830000', '11947931612985300', '11947991491881300', '11948695580734400', '11950186711255900', '11950311369971800', '11952191093612200', '21953133559075700', '11953922425446800', '11953950196388500', '11954619569244600', '11955675883384201');
			if (in_array($order->orderItemId, $incentinve_rebat_10))
				$comm_rate['basic_rate'] = 10;

			$incentinve_rebat_10_5 = array('11912777582195500', '11912963356217900', '11913237747426700', '11913714626035400', '11913797677150500', '11914106656772600', '11914159672957601', '11914599386208507', '11914621195944400', '21914632447681400', '11914741353721400', '11914932482016600', '11915031239543800', '11915747350728000', '11915790921647900', '11915807593818700', '11915871491181900', '11915938301995909', '11916040612537900', '11916436962112100', '11916502765095100', '11916547490915600', '11916580310255600', '11916589636500000', '11916658053713200', '11916826620947600', '11917261350341500', '11917374555257200', '11917420525675800', '11917455645047500', '11917462615913400', '11917484911404100', '11917569579884700', '11917574784556300', '11917651865508600', '11917729649786400', '11917758369002100', '11917766813815100', '11918121844351200', '11918129896596300', '11918222421738000', '11918274111006102', '11918300434113300', '11918300988028800', '11918392092843900', '11918396440971200', '11918406392535000', '11918414541693000', '11918426292504201', '11918450880576900', '11918455331312600', '11918456253138300', '11918582361746900', '11918593253026200', '11918674759027100', '11918696003104800', '11918699173854500', '11918736871974900', '11918882949354800', '11918893969222500', '11918922116084600', '11918940066210000', '11918948308908900', '11918951814330300', '11918999886750100', '11919041667637900', '11919045217602100', '11919047843733200', '11919046796675900', '11919052703637800', '11919082654375600', '11919109367078700', '11919112286052000', '11919123926800300', '11919130910348400', '11919133615625100', '11919135450887200', '11919148610344600', '11919154060532400', '11919156098024900', '11919167027446200', '11919173106234300', '11919223058467600', '11919270472668600', '11916436962112101', '11919838682244400', '11919965735471400', '11920035251080300', '11920201956173900', '11920261675594300', '11920413162176200', '11920718252137000', '11920827225078200', '11915807593818701', '11920853269985600', '11921031651010900', '11921187348027000', '11921636106494800', '11921695332535600', '11921907312926600', '11922286959991900', '11922360410300802', '11922452317626400', '11922465863987200', '11922652206897300', '11922674331934900', '11922762552631400', '11922881982625400', '11923195937223900', '11923307695965700', '11923389217696400', '11923411555538400', '11923468120758000', '11923495419678800', '11923526253945700', '11923617268808200', '11923691052173700', '11923728204568600', '11923754396194900', '11923787334984501', '11924326858202501', '11925021497776500', '11925028534505600', '11925132380096400', '11925197644378900', '11925244459178100', '11925252812434800', '11925266594731800', '11925272586764300', '11925375391982300', '11925417971494600', '11925418850218700', '11925423990907600', '21925428139775300', '11925500779612000', '11925503646038100', '11925928453574400', '11925948324834800', '11925993554257500', '11925995697212800', '11925999805342900', '11926011381014300', '11926044770037300', '11926067211958200', '11926137322566700', '11926140907617100', '11926143095177700', '11926143028244200', '11926147262046200', '11926148001533800', '11926154375271900', '11926177422390800', '11926287221947600', '11926320947797000', '11926321712325300', '11926766369611600', '11926823817846200', '11926827107306500', '11926839046085000', '11926897999671600', '11926897638223900', '11926901554486200', '11926902123703600', '11926907300722400', '11926908934025800', '11927156871487800', '11927174057754000', '11927199819337900', '11927258483024200', '11927398891850900', '11927568180352001', '11927886860003100', '11927899059572400', '11927946077632200', '11928067037161400', '11928191760894100', '11928426961338200', '11928480760348100', '11928489422700600', '11928550924014300', '11928611470734800', '11928795943031503', '11928884532008000', '11928941146727200', '11928999551661000', '11929299772706300', '11929375446052200', '11929641994874500', '11929679941225900', '11929685258060600', '11929714865273800', '11929817863726800', '11929835246935000', '11929850942515900', '11930152796000500', '11930362545177900', '11930394081184700', '11930621544722200', '11930632752153600', '11931089998296400', '11931166012444900', '11931273213951702', '11931288925872300', '11931317057080700', '11931318484162300', '11931324678636900', '11931344591903200', '11931456526950100', '11931516330945600', '11931538895231100', '11927156871487801', '11932043495762400', '11932046812538600', '11932112098448600', '11932128379210600', '11932217519991500', '11932225085713500', '11932245274991200', '11932252507700100', '11932273143392700', '11932317037391400', '11932342772097500', '11932388374576100', '11932431229886600', '11932727712254500', '11932786096792300', '11932786903280200', '11932954901648100', '11933013105637300', '11933016855206600', '11933036616117500', '11933062323021600', '11933092350895400', '11933306932521900', '11933348558250700', '11933862752182500', '11933865163007900', '11934048434142200', '11934086121768100', '11934097568433800', '11934109940104200', '11934133702694400', '11934692318735100', '11934692426971400', '11934706339963400', '11934955279094201', '11935020155102100', '11935413239924300', '11935421630001700', '11935576221994300', '11935580459183000', '11930640550066201', '11937141086283900', '11937448527581500', '11937622942561800', '11937982672396900', '11938000999700800', '11938175329031600', '11939169748782200', '11922410698600101', '11912962139883400', '11912964027737100', '11913414821646500', '21914030990521900', '11914047974506400', '11914637098530500', '21914725476680600', '11915004197710200', '11915604812288900', '11915855507161703', '11916496472220000', '11916532764531200', '11916577938361000', '11916905363416400', '11917364402595700', '11917392263597300', '11914637098530501', '11917512660653000', '11917575402994000', '11917619165495400', '11917635259545500', '11917682037763900', '11917958486468000', '11918143544171600', '11918169942215400', '11918238402337700', '11918307945637800', '11918317045646600', '11918367275701500', '11918406961494300', '11918420295453400', '11918451646176500', '11918457575046400', '11918505142070500', '11918513002115900', '11918550259297200', '11918592909403100', '11919015896201700', '11919019301628900', '11919163588111200', '11919196485992100', '11919233007422500', '11919273317325700', '11919478110825201', '11919985333153000', '11920004662690000', '11920032432302800', '11920052386663600', '11920189794656400', '11920880042621300', '11921188582510900', '11921219274262700', '11921548905336600', '11921629249981100', '11921637671231100', '11921642386317200', '11921667002610100', '11921675745411900', '11921770846066400', '11921774763661900', '11921780367618100', '11921810441397800', '11921913955592900', '11922069268076200', '11922525665568200', '11922537811304400', '11922542557693600', '11922574674664000', '11922602388585800', '11922656771631200', '11922708165188300', '11922742470870300', '11922773274204800', '11922900234495000', '11922947439737100', '11922997958083900', '11923001542382900', '11923249572768500', '11923335025465400', '11923393618415400', '11923395851852200', '11923454662796101', '11923555410361200', '11923646113795600', '11923696424823800', '11923848901114800', '11924020457174900', '11924177907226500', '11924214276977900', '11924252838007400', '11924277887333300', '11924355972357300', '11924702816886800', '11924753030886700', '11925061423977100', '11925417123837500', '11925569837353800', '11925931796236700', '11926048164408700', '11926291261737200', '11926295794402900', '11926400893573000', '11926859417911500', '11926926854157900', '11926932316441800', '11927060180461500', '11927150018335800', '11927165599980900', '11927166940511400', '11927171386893000', '11922974308025601', '11927216275868400', '11927695688602700', '11927788127036600', '11927813828714000', '11928055935596000', '11928068553855600', '11928078730812700', '11928119045542300', '11928435996462400', '11928493639265100', '11928514734505100', '11928554915888400', '11928614130301200', '11928620041993100', '11926194500890801', '11928860042125000', '11928892899260900', '11929423439582400', '11929495815997200', '11929683576622700', '11929684237166000', '11929729884762000', '11929792964507600', '11929828271286900', '11930128117020300', '11930239216791400', '11930291252600500', '11930366089514400', '11930387509513100', '11930394317102401', '11930475632073000', '11930593534285600', '11930616325757701', '11930659338952500', '11930666331244000', '11930683969725900', '11930699489928200', '11930727697282500', '11930729139352300', '11930765636626301', '11930887150200600', '11930892687653800', '11931002976432900', '11931203770002100', '11931205613317900', '11931207280497800', '11931239874833901', '11931261739638000', '11931315043584200', '11931319454726500', '11931324656112600', '11931348014476900', '11931361064265100', '11931426602304300', '11931444944680100', '11931456046907000', '11931505072035400', '11931524626684100', '11931592659785700', '11931640030897000', '11932869046005900', '11932897004227100', '11933099496560200', '11933750351406200', '11935007654305200', '11935059997573700', '11935468696708000', '11935851585868900', '11935860859502400', '11937630956506300', '11938194168652800', '11938194168652801', '11938210448241900', '11938348500201300', '11938493988754700', '11922703025316007', '11938624872820400', '11938864955906300', '11939085423587700', '11939278030606801', '11932846008064500', '11933227160075700', '11915072005611400', '11919176275624600', '11925459994874200', '11932960579931900', '11932984734936200', '11933020940974900', '11933029148858800', '11933045396581100', '11938057646481500');
			if (in_array($order->orderItemId, $incentinve_rebat_10_5))
				$comm_rate['basic_rate'] = 10.5;

			$incentinve_rebat_12 = array('22069309647565400', '12071361811517200', '12065079263254800');
			if (in_array($order->orderItemId, $incentinve_rebat_12))
				$comm_rate['basic_rate'] = 12;

			$incentinve_rebat_13 = array('11912904278534900', '11912919150717600', '11912938062032000', '11912981747555100', '11913033537125200', '11913043401137600', '11913113961951100', '11913151311193500', '11913171110991100', '11913169604306000', '11913177290255900', '11913200141848900', '11913217824453000', '11913228607655300', '11913282767350700', '11913285061780300', '11913292634432100', '11913324323704800', '11913330176007200', '11913340800708900', '11913396166765000', '11913407478494000', '11913409653250000', '11913435747288500', '11913441507918200', '11913463823791100', '11913468122101000', '11913470809007600', '11913486584426200', '11913486562412900', '11913518273950600', '11913590364037400', '11913679142871500', '11913730584036300', '11913749682846500', '11913756579067000', '11913759972265300', '11913770275397000', '11913786122240600', '11913799630484300', '11913808921775900', '11913812780097000', '11913812554542600', '11913828713126300', '11913832674185600', '11913833464617500', '11913848008113300', '11913859775496700', '11913863736946900', '11913873128011200', '11913887631167600', '11913888275007800', '11913894128072800', '11913908835166600', '11913913744026800', '11913915112387500', '11913924752356500', '11913934700904800', '11913934721808900', '11913943937315500', '11913940903973400', '11913949160213800', '11913948365647900', '11913952657818700', '11913955846515300', '11913959019962800', '11913964425954401', '11913972094906100', '11913974080626300', '11913981168585300', '11913990433524000', '11913991177027500', '11914006284085900', '11914009354506800', '11914011687688900', '11914014321033500', '11914027170253100', '11914040223480000', '11914040618996000', '11914051944448900', '11914053748396807', '11914057621300500', '11914069751731400', '11914072768917400', '11914074290523000', '11914073922098300', '11914076967492100', '11914079079302500', '11914078209547900', '11914091062746500', '11914091992795400', '11914093991033500', '11914095410981900', '11914099062828900', '11914103625721600', '11914104441752100', '11914106370730200', '11914122024370500', '11914127140050300', '11914126975503900', '11914130409144100', '11914144499081000', '11914151756438100', '11914170132646000', '11914175591070500', '11914178867591900', '11914185049068400', '11914187590767200', '11914189961335500', '11914197285152800', '11914199487610700', '11914202526792800', '11914211352133200', '11914229986652900', '11914231442914300', '11914236785405100', '11914244423575300', '11914244254231700', '11914244956680100', '11914254588474400', '11914258074472000', '11914259063122800', '11914264285734900', '11914274691124900', '11914282555193200', '11914286161770700', '11914290821643100', '11914313543163400', '11914321658537000', '11914334414138300', '11914341266433000', '11914345358807800', '11914569568042900', '11914583376412300', '11914608785194800', '11914645001854300', '11914650752540400', '11914711886344000', '11914711479505300', '11914720476718800', '11914727177146200', '11914727019106800', '11914735866782200', '11914750930854800', '11914747282215800', '11914755175860500', '11914763345688900', '11914794202893500', '11914802455961700', '11914825976161500', '11914829919506000', '11914837792122000', '11914893205900200', '11914892702630900', '11914904316752400', '11914905385848000', '11914921195146400', '11914929050664900', '11914935178118500', '11914966747487500', '11914971925203000', '21914980983791600', '11914980061332400', '11914990377163900', '11914995245323300', '11915000564128000', '11915005875900300', '11915029079665900', '11915041293530100', '21915051724506900', '11915057059954700', '11915058558134800', '11915060735382800', '11915085026118200', '11915085339316700', '11915098508008700', '11915122984587200', '11915124142003700', '11915129068321700', '11915143548398100', '11915163451081400', '11915164020302800', '11915160156252800', '11915215756888300', '11915219048968300', '11915222406223200', '11915256399785800', '11915280618152800', '11915388408816200', '11915417819831600', '11915424588851600', '11915447833791300', '11915500850338400', '11915509598563000', '11915541035077500', '11915566820501500', '11915590949936600', '11915603433358500', '11915603774295200', '11915604716363400', '11915613277310900', '11915656260421200', '11915657650094700', '11915657796295600', '11915669339568000', '11915671259037700', '11915696424666700', '11915707626071100', '11915714775260000', '11915717974121500', '11915721008282300', '11915732445555700', '11915741334385800', '11915749863144900', '11915753885315400', '11915773772271900', '11915777715115000', '11915795059440000', '11915799074622800', '11915812094726900', '11915818590605300', '11915836661243600', '11915844723457401', '11915846281866800', '11915847602944100', '11915850440178100', '11915860935327400', '11915880418203400', '11915907955392500', '11915906387274800', '11915911037136300', '11915911352660100', '11915912343497800', '11915914623208300', '11915917587753500', '11915932124625500', '11915932452581900', '11915937016696700', '11915938091560100', '11915940007944300', '11915940993870300', '11915939340282700', '11915942815520300', '11915945942308000', '11915948478856400', '11915953996987500', '11915956754904400', '11915958736822200', '11915960150518800', '11915967143110400', '11915966997341600', '11915971046474900', '11915979342965600', '11915990954444700', '11915994814555200', '11915997952494300', '11916003299011300', '11916005273620800', '11916005549985700', '11916013088437400', '11916019517354100', '11916022608087400', '11916023377501600', '11916023361890700', '11916023761661300', '11916025777282000', '11916026437964400', '11916028335412500', '11916030055580900', '11916029596025400', '11916034134478200', '11916034817715700', '11916038524293400', '11916039477676300', '11916047233392400', '11916048847315500', '11916048842254500', '11916054091865400', '11916097560257400', '11916118158417800', '11916125199808600', '11916143323525900', '11916161846516900', '11916252943955400', '11916336664404100', '11916357988872800', '11916372529956700', '11916407824387100', '11916405377834400', '11916425951854000', '11916453694542202', '11916462560756600', '11916465119146700', '11916468008893100', '11916464448954700', '11916471663771900', '11916475613606000', '11916476228715700', '11916478315423800', '11916478055378800', '11916478423373100', '11916480715940300', '11916481582668200', '11916484272994500', '11916485938867500', '11916487453184500', '11916490122631000', '11916489250725100', '11916495490231800', '11916499536063801', '11916506066780500', '11916505514353300', '11916507594808500', '11916506966245800', '11916508819717900', '11916505671745400', '11916510009670900', '11916510311920500', '11916514074881401', '11916519836758200', '11916522518383100', '11916525058670000', '11916526986774300', '11916529003657300', '11916529396026400', '11916533857747601', '11916534794196900', '11916538900024300', '11916542904716900', '11916548094980600', '11916549370575500', '11916547826601600', '11916551157858100', '11916560229964000', '11916557087110400', '11916562597078100', '11916572986393400', '11916577938361001', '11916579657507300', '11916583739110500', '11916583673505000', '11916585629163700', '11916588453444400', '11916595453533600', '11916602834758800', '11916605669893900', '11916608499595102', '11916612031933100', '11916615060475000', '11916618030090000', '11916619037451902', '11916626298508500', '11916627597443900', '11916635357804500', '11916635749061900', '11916638959006300', '11916639047348700', '11916642910363500', '11916643637284900', '11916643416148300', '11916650738991600', '11916650791792000', '11916650246765300', '11916655439138600', '11916661542128700', '11916664475586400', '11916667797832400', '11916675320601500', '11916680713653600', '11916678995131600', '11916682835774000', '11916682821924000', '11916684853445700', '11916688890172600', '11916690802275502', '11916690291897600', '11916694671241000', '11916704957443900', '11916705211990900', '11916719457436600', '11916719844148600', '11916724798296600', '11916732164970200', '11916735146332100', '11916737234256500', '11916739170190200', '11916742436124500', '11916746530365700', '11916752553461700', '11916754284563400', '11916755125638400', '11916755489372900', '11916756128132200', '11916762942415500', '11916765540606800', '11916767073291900', '11916776942864100', '11916781851162500', '11916784837283100', '11916792788310801', '11916792809747900', '11916794872764900', '11916797821086200', '11916799904345900', '11916803893688100', '11916807727824800', '11916809788746300', '11916812665282600', '11916820395111100', '11916825923215704', '11916830033797600', '11916834072233400', '11916838410661900', '11916841892887500', '11916843249761400', '11916847630592200', '11916850153754400', '11916851086462300', '11916853385271100', '11916858341303900', '11916861446234100', '11916865776598307', '11916871495275400', '11916874727398201', '11916876046546900', '11916878348237900', '11916880931901601', '11916889971170900', '11916890624381500', '11916892036656100', '11916895852268100', '11916898376171900', '11916905057092000', '11916911576043800', '11916914878410100', '11916912946670500', '11916914848884200', '11916916252290300', '11916927940982800', '11916949310482900', '11916951573023300', '11916968591140700', '11917004979498000', '11917154367705900', '11917175412920400', '11917184616536400', '11917193621487700', '11917205678823100', '11917212212684401', '11917213686060900', '11917216487552100', '11917216763342300', '11917217165798300', '11917223564381300', '11917226257634800', '11917228207826900', '11917237685482700', '11917238447985600', '11917243350930200', '11917244039028200', '11917256471384700', '11917261989815300', '11917268370185000', '11917269004166900', '11917275326392600', '11917275761547000', '11917276855527200', '11917280993585800', '11917282784453900', '11917283014131400', '11917291040521600', '11917296519043300', '11917300957973900', '11917302746202200', '11917307654740600', '11917311922828900', '11917317493565900', '11917317918601100', '11917325909092900', '11917327865600600', '11917328193536000', '11917334567667300', '11917339332247500', '11917341777636102', '11917345818662100', '11917350252311300', '11917351221397600', '11917354134836900', '11917361491540900', '11917362949808800', '11917364925842000', '11917365974547800', '11917369970846600', '11917368229647700', '11917375769492100', '11917376733992500', '11917392224330601', '11917392722966700', '11917393668335300', '11917396135222000', '11917399588724300', '11917404273634500', '11917410559785600', '11917411277351200', '11917411694586001', '11917416573567000', '11917429578162200', '11917432987505500', '11917450707745100', '11917453095807900', '11917449793185700', '11917453692795800', '11917459431147000', '11917461235014200', '11917461977122800', '11917471594496900', '11917472120433700', '11917487347526000', '11917494417214000', '11917495132608900', '11917498528842100', '11917499221180600', '11917501377327300', '11917509282084300', '11917509408838700', '11917511790255600', '11917517133307500', '11917531202397500', '11917546603074200', '11917540780245900', '11917554432377600', '11917561181552600', '11917562429547700', '11917583082416700', '11917591437048700', '11917601649157502', '11917620586037000', '11917647639146201', '11917653185281800', '11917663068784400', '11917670078124700', '11917680211873700', '11917687972027800', '11917694769134300', '11917697059850102', '11917725337340400', '11917733091868900', '11917773027134001', '11917779224945201', '11917853687321800', '11918001064907400', '11918069540073400', '11918092883674400', '11918097579426700', '11918111364504600', '11918123281392500', '11918157978330900', '11918163756427700', '11918173172826300', '11918186307813700', '11918235414843200', '11918242314612200', '11918256767460200', '11918262050526400', '11918266794733500', '11918272689571300', '11918276030372500', '11918307513284200', '11918381229253200', '11918420246806002', '11918432213788800', '11918433313658400', '11918433776367000', '11918440803795900', '11918467473985600', '11918484558658900', '11918493239343200', '11918501592508000', '11918507168361800', '11918515374035401', '11918528294178600', '11918535845190600', '11918597206566300', '11918622548435603', '11918623760147900', '11918799672401800', '11918981226653300', '11919015623571800', '11919024429912300', '11919037331464900', '11914224353306701', '11919042156998100', '11919042466683200', '11919041972044200', '11919063860035300', '11919069307371600', '11919074995635600', '11919077456305700', '11919078467086600', '11914321160623501', '11919090914788200', '11919110481286000', '11919150959148000', '11919180424676400', '11919190921686500', '11919227914626300', '11919230022636800', '11919242118101800', '11919254980727000', '11919258382251200', '11919259430608100', '11919261537936700', '11919266892680500', '11919278736277900', '11919284716481800', '11919294415113900', '11919299606358002', '11919301741818700', '11919350513202600', '11919352998025300', '11919368046906100', '11919383007936500', '11919386812696600', '11919387405910300', '11919393660502400', '11919406960592700', '11919413800190100', '11919414572606200', '11917265742843701', '11919451110095100', '11919452390011800', '11919457409815800', '11919465192486300', '11919772450455600', '11919784275430001', '11919795646576000', '11919810180856000', '11919831883933500', '11919886621972300', '11919887437004900', '11919889461938800', '11919902018261300', '11919931481328000', '11919947798998400', '11919965501102700', '11919970131758600', '11919982633210800', '11920002962336800', '11920004144926800', '11920037225898300', '11920046555701200', '11920061265990500', '11920060045724400', '11920071189006100', '11920080440893502', '11920088601403500', '11920091821872000', '11920100334724800', '11920109468265800', '11920111325135700', '11920119097840400', '11920126264032800', '11920146817405600', '11920152422172700', '21920157313400800', '11920159973216200', '11920167473720000', '11920175601690100', '11920192545237000', '11920194683714700', '11920208655837600', '11920225607513400', '11920232232372900', '11920242022307600', '11920246886133700', '11920248771943800', '11920264489373000', '11920265302884900', '11920273480667900', '11920281479356000', '11920288838401900', '11920300336420200', '11920309616251200', '11920312145527600', '11920315115378800', '11920339722330800', '11920330814837900', '11920350147778900', '11920358914486500', '11920374024992700', '11920390871357300', '11920391427900000', '11920393148144000', '11920403697100400', '11920629541085100', '11920677770640300', '11920686008863900', '11920686711783000', '11920697026704000', '11920715549300200', '11920732582898300', '11920734085350900', '11920736917790400', '11920745721003600', '11920750307808201', '11920754804412200', '11920769826357400', '11920769957801900', '11920776832135500', '11920778052078300', '11920791119717700', '11920810766567200', '11920816347735400', '11920818664336000', '11920825746538500', '11920840661083900', '11920846870686600', '11920847596624300', '11920854967706200', '11920887229085800', '11920888221565000', '11920930414170300', '11920950234928100', '11920956648027100', '11920955836473300', '11920966417716200', '11920985211398700', '11921024639070700', '11921034172854800', '11921038763402100', '11921042059307100', '11921044917017100', '11921043830952300', '11921048550992800', '11921049351260000', '11921056102890000', '11921058612178700', '11921066873028800', '11921075701242500', '11921076676835600', '11921077621610600', '11921080671845600', '11921080524144900', '11921081334050100', '11921085436348400', '11921086529488502', '11921099868314200', '11921102163298500', '11921104026583800', '11921106528665500', '11921106382295100', '11921112089730100', '11921116892423300', '11921117459795300', '11921123032130400', '11921134671890800', '11921135118567000', '11921135530626200', '11921138208907100', '11921138368346900', '11921139959296800', '11921146889530900', '11921150298406600', '11921150348345500', '11921152139263700', '11921152973893400', '11921151501961700', '11921159492893500', '11921166411646300', '11921166039667000', '11921168828351600', '11921170390936100', '11921170519158500', '11921171870564500', '11921176936597700', '11921177972847500', '11921177955750300', '11921182232234600', '11921182241610400', '11921183058898600', '11921184315348700', '11921185809784000', '11921191975608500', '11921191678004300', '11921194011894800', '11921193931124900', '11921195663634000', '11921198610000900', '11921202758996900', '11921205347236800', '11921206817165300', '11921214278274400', '11921220642854500', '11921222546574000', '11921225528882000', '11921231169890400', '11921232184097500', '11921233966126900', '11921236101938200', '11921240919490500', '11921242180766800', '11921244646166900', '11921248191592800', '11921262305967300', '11921262113221000', '11921264572206301', '11921269036501300', '11921269242162600', '11921269785955900', '11921285730844100', '11921282746435000', '11921288137377100', '11921298582654700', '11921303634694700', '11921339445250601', '11921358840370500', '11921402156215000', '11921483159395800', '11921621543151001', '11921659062101600', '11921675556630800', '11921683096405700', '11921684075740900', '11921684394103500', '11921714090468600', '11921739806738100', '11921756771034200', '11921765654025400', '11921807897062300', '11921843818698400', '11921870302100000', '11921917268614900', '11921947234813000', '11921959577513400', '11921973056354400', '11921973951850300', '11921990650457900', '11921999166012000', '11922042246848700', '11922044255548900', '11922068887087600', '11922089676883900', '11922096604202200', '11922113083300800', '11922127640306500', '11922370637282400', '11922392580078000', '11922400375873200', '11922449632103000', '11922459466051300', '11922465614555200', '11922469823471500', '11922471669374600', '11922503479334600', '11922523409838700', '11922527000963700', '11922529495820100', '11922537742415900', '11922555869757200', '11922557327461800', '11922566040522000', '11922599127666600', '11922599761010600', '11922613600743701', '11922623762278200', '11922627031627500', '11922639678941400', '11922645372194900', '11922651461124900', '11922665430904700', '11922694798666800', '11922697543041000', '11922714430512900', '11922752789678600', '11922754015127400', '11922760527712300', '11922783063228700', '11922814520517400', '11922828988227500', '11922844376514100', '11922845820481000', '11922855813801400', '11922864577700201', '11922869965961100', '11922871391301800', '11922880004712100', '11922881681318800', '11922883830721400', '11922914957121100', '11923001003244500', '11923168477942000', '11923211128601600', '11923252749818300', '11923265844975300', '11923299199392200', '11923305248098100', '11923314595071000', '11923316486364800', '11923331249105700', '11923341473427600', '11923415375755400', '11923420446512400', '11923436338407402', '11923451632394800', '11923465081218500', '11923483807243100', '11923485630254400', '11923502173811300', '11923536988417000', '11923578699565600', '11923579927160000', '11923578044402800', '11923594247500800', '11923597713432500', '11923602937306200', '11923605923547400', '11923615099212700', '11923629427430200', '11923636579500800', '11923643439187200', '11923660043921200', '11923661680470300', '11923664382653500', '11923666165805600', '11923673479337200', '11923678805214700', '11923684669017300', '11923694036735700', '11923697366213500', '11923698756858900', '11923701278755100', '11923706677175800', '11923704810094900', '11923734007917100', '11923736665718600', '11923739855738500', '11923751966656100', '11923752086050100', '11923753037050600', '11923757207161900', '11923777031290400', '11923778975088600', '11923784559484900', '11923787334984500', '11923810579118500', '11921178707183101', '11924117277398200', '11918623760147901', '11924149028437601', '11924158081605800', '11924187171775100', '11924220915207000', '11924221535454300', '11924248388482801', '11924281993952000', '11924288339365200', '11924304151345700', '11924319866218300', '11924321246602600', '11924408350400100', '11924420243700900', '11924427881233400', '11924442022591800', '11924451098620000', '11924460567481300', '11924495366901600', '11924504979134600', '11924506900563700', '11924517245573400', '11924516333856300', '11924515524550100', '11924531600904900', '11924543854010900', '11924547138908600', '11924547921270200', '11924551608762600', '11924553264140300', '11924556811236500', '11924561667735500', '11924564077372700', '11924564368838600', '11924565835422000', '11924565467464800', '11924566750757100', '11924567018608000', '11924565728795900', '11924568205085000', '11924568046351500', '11924573278815700', '11924577502641200', '11924582627340600', '11924583137574000', '11924586427913900', '11924590916464700', '11924590849551900', '11924595052520200', '11924595136842100', '11924597045946500', '11924597146366400', '11924597413627100', '11924600877978700', '11924601576643200', '11924605634706600', '11924609898853300', '11924606659506900', '11924611452594700', '11924612344510700', '11924615434734100', '11924619028584500', '11924619682771001', '11924627337156700', '11924632632430300', '11924635641346000', '11924639366258300', '11924634784440900', '11924641397974800', '11924638040028700', '11924640115870800', '11924645843218500', '11924649120028700', '11924651944398800', '11924652270695900', '11924645651522000', '11924657791158400', '11924665876140800', '11924680745936400', '11924688784890700', '11924689108551100', '11924690650610300', '11924698894228700', '11924702909117000', '11924711508348000', '11924715622255000', '11924720508197900', '11924730853441900', '11924752135733800', '11924776168023500', '11924780425963400', '11924781154378500', '11924860661957200', '11924864080854400', '11924902082137900', '11924961750326200', '11924985006745600', '11924991251715400', '11924994364136500', '11925005283550800', '11925006045110100', '11925008768533801', '11925009302700500', '11925011638316100', '11925008245072900', '11925005479756000', '11925026749125600', '11925051594693300', '11925053460284300', '11925057456520800', '11925059215463200', '11925070072602800', '11925079731388002', '11925086119836500', '11925094648943100', '11925105342030600', '11925129558667300', '11925131454653000', '11925146757577501', '11925147780724400', '11925151772893300', '11925169151618900', '11925173523101600', '11925179054015000', '11925180327146200', '11925180142765700', '11925183388780600', '11925184696927400', '11925185952100200', '11925186177593800', '11925191021764300', '11925190730431700', '11925191404652200', '11925190577497000', '11925192430655400', '11925193682442700', '11925190949357500', '11925196668818000', '11925196725078400', '11925197138515600', '11925200824992800', '11925201344418700', '11925206885513200', '11925210762497800', '11925213538234100', '11925215322635900', '11925219284070700', '11925218646361700', '11925218888514200', '11925225157456900', '11925226367055800', '11925226408023300', '11925228905957600', '11925228729926000', '11925235905312600', '11925253653932400', '11925291926485200', '11925295224732300', '11925298330166000', '11925297535748000', '11925301025992800', '11925302484934900', '11925300194892300', '11925306307627800', '11925308984195900', '11925310874493400', '11925313704053400', '11925323960600000', '11925327033551600', '11925327873292900', '11925326888535000', '11925329925081300', '11925329781690500', '11925331922057700', '11925336193476602', '11925336627224900', '11925343475361600', '11925351734433500', '11925353851383900', '11925364920113900', '11925367031461600', '11925368031733000', '11925368577060500', '11925374267885800', '11925375932236800', '11925382667670600', '11925383005051400', '11925386517107900', '11925389594263700', '11925393692408300', '11925394733304600', '11925399335856400', '11925404727416600', '11925405991214000', '11925408205132600', '11925412536690500', '11925410710595600', '11925414242208800', '11925415436010900', '11925417064097600', '11925465730512700', '11925469469562900', '11925470605150900', '11925476686606900', '11925476933387700', '11925475677728600', '11925480288851100', '11925479977073600', '11925484024777000', '11925482852281000', '11925490945927200', '11925496463288501', '11925500501701400', '11925518391176900', '11925562351145900', '11925584506833300', '11925591513666800', '11925593986446100', '11925659556668200', '11925693846256500', '11925717578352500', '11925751020918600', '11925779612475800', '11925790418295500', '11925806866785700', '11925853337557000', '11925856224765400', '11925861390266300', '11925864865572800', '11925873044806500', '11925882824837200', '11925886822465200', '11925894087647200', '11925900564084000', '11925907343936300', '11925906137877500', '11925905803018900', '11925909373702600', '11925910424665200', '11925912998804800', '11925916991788500', '11925918128350800', '11925926952355000', '11925933843253100', '11925948135253000', '11925963868957000', '11925966594694700', '11925967691000401', '11925968887257900', '11925969019273700', '11925972059674101', '11925973928368600', '11925977781545000', '11925980677302400', '11925988774686100', '11925989039496200', '11925989706127100', '11925990737433900', '11925996359568600', '11926036273648500', '11926039893154600', '11926040919334700', '11926043004772300', '11926043210436300', '11926043637143100', '11926044684758300', '11926047343984600', '11926043278643400', '11926049987856000', '11926050457721500', '11926057308368500', '11926058263486600', '11926058477486000', '11926059138273800', '11926059662965600', '11926061208317500', '11926064660634600', '11926067486388200', '11926078549657400', '21926105875740200', '11926107646038600', '11926108510163300', '11926109424952100', '11926110772650600', '11926112121615600', '11926117086736200', '11926117715750100', '11926120459351900', '11926120355293100', '11926121034704600', '11926121208417400', '11926120801384500', '11926136065030100', '11926139370263401', '11926152465911000', '11926173733575600', '11926177205376700', '11926177719021800', '11926183146551200', '11926184866424700', '11926188262842600', '11926191010216900', '11926192021342500', '11926191781548000', '11926197355051800', '11926199465965103', '11926201132398100', '11926201390237300', '11926202029581604', '11926200861394700', '11926204090130600', '11926210231197000', '11926213138426500', '11926214388397500', '11926219640893801', '11926221700395900', '11926224117255300', '11926225603844500', '11926225884104200', '11926225960438500', '11926226370845100', '11926226823870600', '11926229373162800', '11926231486894900', '11926234489804200', '11926234808568700', '21926235570123000', '11926238999697300', '11926239295724900', '11926242762457900', '11926242533008400', '11926240111621800', '11926246929875001', '11926254061384700', '11926262044856500', '11926262163640100', '11926294214597100', '11926297518866500', '11926297501306900', '11926298673200800', '11926302729200400', '11926305737711400', '11926308048820100', '11926308352262100', '11926305853475200', '11926308767994500', '11926306838827900', '11926309055830500', '11926305011656200', '11926316476856500', '11926318461784900', '11926319487485500', '11926354550711700', '11926362068454000', '11926360919168900', '11926362757937000', '11926361962040200', '11926367696145700', '11926371532492000', '11926372898283800', '11926376058815700', '11926375478785300', '11926377952910000', '11926378507996801', '11926378943543700', '11926378571038500', '11926381203391600', '11926381791456000', '11926384398380100', '11926387518432800', '11926394419877300', '11926415592698701', '11926420712023600', '11926428591265500', '11926434842865400', '11926454450764800', '11926456944107600', '11926578188520800', '11926624197031500', '11926695026832900', '11926700161096800', '11926708965904700', '11926717099910500', '11926717094521700', '11926727889093700', '11926731723230600', '11926744632628000', '11926746139465900', '11926753588642700', '11926771875300300', '11926780802516900', '11926783593485400', '11926784059872200', '11926785733507100', '11926787695711500', '11926789373985800', '11926789319190000', '11926791140833500', '11926794622711900', '11926797039651800', '11926802871920601', '11926809629194100', '11926809027771300', '11926811356922000', '11926812604470800', '11926815143638800', '11926819462816100', '11926822365522800', '11926822569784400', '11926830015783800', '11926855789221000', '11926863695465300', '11926866443188000', '11926869485623200', '11926869944762700', '11926871748366400', '11926870689457800', '11926873276514300', '11926873544135100', '11926879235354700', '11926879307721200', '11926881004303500', '11926884326917300', '11926885850532900', '11926887876668300', '11926889038515400', '11926888343984400', '11926891086393700', '11926893759338900', '11926894563708000', '11918515374035402', '11926908191472700', '11926929978461001', '11926935173670500', '11926942519052100', '11926945455653500', '11926940231062700', '11926945607160200', '11926952284260700', '11926950893412100', '11926955010914800', '11926959140795500', '11926963149770902', '11926964444492300', '11926998441072201', '11927000732757100', '11927023223451300', '11927033299331200', '11927072941217800', '11927076128635100', '11927082247851100', '11927093744564800', '11927098856913000', '11927100091057700', '11927106632906100', '11927121563675700', '11927129552122400', '11927138219914300', '11927138063612900', '11927140607997400', '11927142229713300', '11927153116964900', '11927153567040000', '11927174286298700', '11927186319630000', '11927194321751100', '11927204130444800', '11927206493468000', '11927208084565000', '11927207601623100', '11927212011580200', '11927220263887900', '11927225482604700', '11927224516372600', '11927233006528500', '11927251948808401', '11927258124507300', '11927274682920700', '11927275524055000', '11927276040357800', '11927293128406500', '11927309784484700', '11927326885560800', '11927334587056700', '11927388993253100', '11927397758778200', '11927560232892700', '11927569371534700', '11927611663986500', '11927611097406800', '11927615400262700', '11927617612935700', '11927632062892500', '11927648843137900', '11927651729435500', '11927655410442300', '11927657595525200', '11927666504042000', '11927670310512800', '11927678611531200', '11927695943210400', '11927700280017200', '11927716216384900', '11927724235736700', '11927734331185300', '11927735278291400', '11927745847505700', '11927750523963100', '11927750084052000', '11927758779404800', '11927791357877800', '11927802438867603', '11927803262081300', '11927819114794600', '11927831132276000', '11927900765443700', '11927909053853500', '11927918806654000', '11927921284973600', '11927924583941100', '11927938557681000', '11927942544177200', '11927965937128100', '11927996170585400', '11928000447043400', '11928003295910703', '11928009530242700', '11928013873864300', '11928030766348700', '11928037157752300', '11928049049266400', '11928052874770300', '11928080865782900', '11916685923793601', '11928101098594200', '11928117449605000', '11928119757971200', '11928133546677600', '11928137701577400', '11928164042405300', '11928352721443900', '11928389785106100', '11928408465854600', '11928420162320500', '11928433108993700', '11928467287168500', '11928468123206400', '11928498984242200', '11928519949870800', '11928530708048100', '11928560884316200', '11928615373494300', '11928628056686900', '11928664563200200', '11928683460212000', '11928722647901000', '11928747678453800', '11928761221856600', '11928825992852700', '11928833944164300', '11928855457467400', '11928884814610800', '11928888853088300', '11928889006205101', '11928898228070400', '11928906357876300', '11928910153177700', '11928932484431600', '11928932827608000', '11928978270293700', '11928998982127900', '11929060630646900', '11929075214556000', '11929090770204200', '11929090670525300', '11929119623541800', '11929218027885800', '11929240644850600', '11929246119160700', '11929276573561200', '11929301524287400', '11929352251014100', '11929353853736800', '11929363018523100', '11929365843984300', '11929370589983500', '11929387294010200', '11929391064095800', '11929398695572900', '11929403213111101', '11929404641707400', '11929407071145800', '11929409825096200', '11929424642805600', '11929425501025700', '11921612208860001', '11929426713307700', '11929430643414300', '11929431482635900', '11929430466515400', '11929432801281900', '11929435950070200', '11929436627836600', '11929436577797700', '11929439768036800', '11929495214677000', '11929543380552800', '11929565995688800', '11929569729644500', '11929570611803000', '11929573389812400', '11929586015842600', '11929592450362500', '11929593814048500', '11929595615811200', '11929603819551900', '11929605049252700', '11929609030151000', '11929611415310600', '11929642877622200', '11929655149134000', '11929667142366000', '11929668642707900', '11929686694507100', '11929696347696100', '11929738987718900', '11929742960750300', '11929744279463000', '11929767130914101', '11929773652777000', '11929779768768300', '11929783468461900', '11929791658911100', '11929794079822800', '11929793558804300', '11929797206223500', '11929824215816000', '11929829175291000', '11929832462972700', '11929835038411802', '11929862468374200', '11929877637800200', '11929882712501700', '11929915212402700', '11929920211037700', '11930186811200200', '11930209920796900', '11930209325706200', '11930225456100700', '11930231030870100', '11930262385297403', '11930294992674500', '11930295550286400', '11930299669331000', '11930341817814400', '11930433752287500', '11930533496743902', '11930543771978700', '11930593522172200', '11930612365705800', '11930650912412501', '11930689505534100', '11926804303746501', '11930729093957800', '11930754043796600', '11931025802862300', '11931082080378100', '11931113709868500', '11931206423470000', '11931241607864700', '11931252497540200', '11931271047230200', '11931363260342800', '11931529738634900', '11931542806227000', '11931894951554600', '11924607622981201', '11932036875455800', '11932044567224700', '11932061710154200', '11932064595785301', '11932066471254600', '11932187709108600', '11932234420104300', '11932287515450700', '11932289850694600', '11932300313965500', '11932351654416600', '11932411149104800', '11932516022664900', '11932639489178500', '11932823004834300', '11932856327686300', '11932974251501100', '11933059152348300', '11933116729521600', '11933169190780600', '11933236790277700', '11933255524285100', '11933259699713300', '11933266188057100', '11933280679918600', '11933280620581400', '11933310176470700', '11933372172963700', '11933516590102901', '11933651654122800', '11933686891338800', '11933730975370000', '11933788986900800', '11933831350601200', '11933928471414100', '11933941998552900', '11933998708154400', '11934004691152300', '11934031683982800', '11934076896774800', '11934089076966000', '11934127764367200', '11934148782135800', '11934189074528300', '11934190509266200', '11925894087647201', '11934525593277300', '11934578330143200', '11934626548087500', '11934654946258400', '11934680308677000', '11934732248130900', '11934867719232800', '11934876099342100', '11934880066921200', '11934874233942800', '11934888967512800', '11924344019978201', '11934911878617500', '11934913030823000', '11934927737068600', '11934957831323500', '11934970931621300', '11935006743631500', '11935037035141903', '11935071387444600', '11935071699972100', '11935073080984700', '11935111165750600', '11935114172507100', '11935121780635704', '11935164837282500', '11930232776265401', '11935210127582700', '11935215018236200', '11935315967012800', '11935337557088100', '11935343183278800', '11935356964717900', '11935367194342000', '11935373841814300', '11935433548318500', '11935435934222700', '11935438533123301', '11935462861701801', '11935465858966100', '11935470117963700', '11935473282776000', '11935479155472800', '11935480422948900', '11935503796122000', '11935507348745800', '11935525557107200', '11935532601106200', '11935536681302500', '11935545573334500', '11935551716344400', '11935563838186200', '11935581350043500', '11935586319454100', '11930533496743903', '11935604465915400', '11935621834282300', '11935629214800000', '11916773603753201', '11935644036607800', '11935648386433200', '11935662737254200', '11935663153585300', '11935692450236803', '11935697364586500', '11935707846415400', '11935709495680200', '11935714497170800', '11935719128206500', '11935716608870700', '11935731518965400', '11935733869810100', '11935737694290200', '11935740171770100', '11935756066011800', '11935760007374403', '11935769706846601', '11935760120906900', '11935781895898500', '11935798621070700', '11935807079160800', '11935808100128200', '11935815660584600', '11935819514150700', '11935826835187700', '11935827532087500', '11935843851455701', '11935869841068200', '11935873547004500', '11935888619394900', '11935892666393800', '11936236479427400', '11936266828962500', '11936560115390500', '11936729604177500', '11936777324664100', '11937097490016800', '11937099654371700', '11937113337971600', '11937128014016001', '11937176326087600', '11937203940280800', '11937206475092300', '11937207507161800', '11937240321355300', '11937243158623500', '11937246620594400', '11937253836827100', '11937279382381800', '11937296437073700', '11937318410034200', '11937321457854500', '11937357414781800', '11937365808846200', '11937411210173600', '11937415219710500', '11929476713213901', '11937482704327800', '11937483635686600', '11937488452752600', '11937498901276500', '11937498389592100', '11937500021920401', '11937503550534700', '11937504279801800', '11937509884753600', '11937509501815400', '11937514983462200', '11937558156582500', '11937565579755800', '11937566652635400', '11937571222487800', '11937574714678201', '11937578281826400', '11937580760827600', '11937572005561100', '11937584528780300', '11937584838822700', '11937599477857300', '11937606157778300', '11937607847981000', '11937606778401301', '11937609834914200', '11937612309886000', '11937716499951400', '11937814926098800', '11937958557190800', '11937960923893900', '11937961694337300', '11937964143227900', '11937966998457900', '11937969908185300', '11937972816048700', '11937973977697000', '11938005348408400', '11938021875356100', '11938027898158300', '11938032505053900', '11938035693603300', '11938037452760701', '11938046502984600', '11938056027674400', '11938061036798100', '11938065712391301', '11938066431764303', '11938093873568601', '11938102530816400', '11938103371450100', '11938109928277600', '11938111816903300', '11938116653201800', '11938130402542100', '11938131418086600', '11938126082611300', '11938143764523900', '11938151734077900', '11938167275932700', '11938177884992000', '11938189568532800', '11938190171554900', '11938241224621400', '11938274868434500', '11938291866994300', '11938305156600400', '11938335935048400', '11938344723987500', '11938360166351300', '11938356713433300', '11938365185831600', '11938367566211100', '11938370291204200', '11938373899538000', '11938383018482100', '11938388439623400', '11938391195546000', '11938388721651500', '11938428975440700', '11938487132295500', '11938487499645500', '11938580030861100', '11938806035808100', '11938899622808800', '11938920495281000', '11938920956072500', '11938933242737200', '11938946254578900', '11938946861294300', '11938954123325900', '11938958472732400', '11938960371971500', '11939010265211500', '11939014941383400', '11939015360537800', '11939033791498200', '11939041753434500', '11939043384937000', '11939060508916800', '11939105589655102', '11939146609453900', '11939154716461400', '11939155459070700', '21939185932787401', '11939191360317100', '11939195403953700', '11939199325610600', '11939206346784300', '11939211224781800', '11939212468801903', '11939222003084200', '11939222982804300', '11939262989433900', '11939269012163400', '11939270719701200', '11939293973824000', '11939301673117500', '11939306073907300', '11939307336180700', '11939329521173500', '11939329584481400', '11939345526901800', '11939394230762700', '11926047207178501', '11937672250721501', '11937964143227901', '11926262163640101', '11926188262842601', '11917347754683201', '11927238540372503', '11926188262842602', '11951369948204900', '11956585759671500', '11912616310547500', '11912626061648400', '11912628433436700', '11912656746543700', '11912676747925000', '11912681020148601', '11912695959970000', '11912741325046300', '11912759946518200', '11912817980581100', '11912819108055800', '11912820779445500', '11912833267735100', '11912839388687900', '11912860685636600', '11912861751563200', '11912870846946300', '11912871841743200', '11912876556575300', '11912879637631200', '11912891401865100', '11912898769648000', '11912899334241700', '11912909907786800', '11912910284705700', '11912922071446500', '11912934302663902', '11912933277473500', '11912943184070700', '11912943052282300', '11912941964072400', '11912943419900900', '11912944826184700', '11912946029904400', '11912953630337700', '11912956684688700', '11912957636555100', '11912960148762100', '11912963618343400', '11912968765048300', '11912976717372700', '11912978479628800', '11912991085993000', '11912993232781700', '11912991992066200', '11912994387723900', '11912999822345900', '11913000818808900', '11913006228543400', '11913006571161100', '11913016771030600', '11913026346578400', '11913034336800100', '11913037497610800', '11913039250077600', '11913044270644800', '11913044215968400', '11913049982632600', '11913059879726700', '11913062283892800', '11913062215371400', '11913070263211600', '11913072238922200', '11913077121925000', '11913080282595200', '11913083262681701', '11913098128686000', '11913101525001400', '11913102777036100', '11913106552407000', '11913111755403000', '11913117053300800', '11913120993601500', '11913132800316400', '11913134193185000', '11913145918272000', '11913147301691700', '11913149627845400', '11913153808306900', '11913154569062700', '11913161636463800', '11913168838487100', '11913172524941302', '11913184979608800', '11913193135211700', '11913193226912000', '11913197044555900', '11913196571943100', '11913198108574700', '11913211045932701', '11913234452746000', '11913238714381700', '11913244223403500', '11913248397454100', '11913252011696700', '11913251801587600', '11913247774711000', '11913250783452700', '11913254240912900', '11913255582832500', '11913259527111500', '21913262940860100', '11913274485903700', '11913274838420300', '11913278700004202', '11913283137362500', '11913283576646101', '11913289683906800', '11913293025135700', '11913297077512400', '11913298058501700', '11913300255534300', '11913303037634000', '11913304998634200', '11913308241192200', '11913308914538000', '11913319508761700', '11913320421058700', '11913322201170000', '11913329413037301', '11913335061146800', '11913335797942000', '11913341953256100', '11913346093625400', '11913349911648800', '11913350616792900', '11913353136335100', '11913380055724800', '11913381731256900', '11913384019070300', '11913385400360200', '11913399530727400', '11913405822170900', '11913409809198900', '11913410874246300', '11913412987177000', '11913427814720300', '11913435318761600', '11913439237031900', '11913442465315801', '11913449869257000', '11913455862731200', '11913455984074401', '11913460731232600', '11913476153865200', '11913480221598900', '11913517289682400', '11913521462185102', '11913701639147500', '11913710688562000', '11913712615210800', '11913730199681200', '11913734648204600', '11913733857678900', '11913759701574700', '11913764293603900', '11913787864003600', '11913794197190000', '11913810629316300', '11913813509930500', '11913813857217700', '11913818194237703', '11913842517742500', '11913846521497800', '11913848940102600', '11913855191590700', '11913858914831800', '11913869453607400', '11913869908847400', '11913870909670000', '11913876376146000', '11913881092272600', '11913883268340800', '11913883672760500', '11913882155818900', '11913898416730700', '11913899371384600', '11913902409794700', '11913905132713100', '11913915437751700', '11913936355798900', '11913940533077700', '11913943191555200', '11913950952473500', '11913960591798300', '11913974898470400', '11913979939845400', '11913989742373800', '11914003129656700', '11914004602463600', '11914011129092600', '11914017948728000', '11914019106924000', '11914021522288700', '11914025660117500', '11914032105923700', '11914035638288400', '11914041191737400', '11914041350041200', '11914043108491500', '11914052838554100', '11914053490785100', '11914053213600900', '11914050455517800', '11914061559025800', '11914066633676300', '11914074156870600', '11914074596006600', '11914081580464000', '11914087001157000', '11914089841683400', '11914089915681600', '11914091087488100', '11914095946494100', '11914087418074800', '11914100210871100', '11914101693125500', '11914106543376800', '11914113245406300', '11914115887481000', '11914116851446700', '11914130097141800', '11914130848853800', '11914131150162800', '11914132171138800', '11914133204995500', '11914132299816200', '11914137961223000', '11914149796291700', '11914150055650500', '11914150678516300', '11914153472843300', '11914156961820400', '11914157937628000', '11914167043992200', '11914167964042400', '11914168553360600', '11914175469591900', '11914175645463500', '11914179143745100', '11914179947568600', '11914185654771600', '11914187309276900', '11914189227830000', '11914189545931600', '11914191322090200', '11914191592590800', '11914194770424701', '11914196342384400', '11914206334875200', '11914218280588600', '11914217301153606', '11914219509322700', '11914220319227300', '11914220761170900', '11914227223552000', '11914229724285700', '11914231545233100', '11914230724673000', '11914237044366700', '11914241753120200', '11914242396823700', '11914243646185800', '11914246356568900', '11914246694526400', '11914249975120600', '11914255968890600', '11914259330275600', '11914280471136000', '11914281078868700', '11914286248111100', '11914286998945000', '11914291256333900', '11914304397841600', '11914308420762300', '11914320698760700', '11914335547758002', '11914363750970400', '11914364039051300', '11914372115170503', '11914372748856000', '11914435859411200', '11914442311241101', '11914577355255600', '11914579984634000', '11914593042693500', '11914629030720000', '11914635620208700', '11914639062821800', '11914637908950700', '11914640137048200', '11914645648223300', '11914652018163300', '11914652510620500', '11914655395135100', '11914666374472500', '11914671218437600', '11914671603475400', '11914671657897500', '11914673209057100', '11914675372822300', '11914683085141200', '11914685931272100', '11914692589755200', '11914690841380200', '11914693928107800', '11914695766577900', '11914697760060400', '11914717592634900', '11914719362725600', '11914722348466600', '11914724342711400', '11914727907784700', '11914732202974600', '11914734975276800', '11914733690648200', '11914739246483700', '11914740374391100', '11914746710796700', '11914750933016700', '11914752603601500', '11914775609800100', '11914780760556700', '11914780649471800', '11914787711256500', '11914789449198400', '11914791990634600', '11914795942561700', '11914795880091400', '21914801733253800', '11914804090396300', '11914828121305500', '11914830527598100', '11914836879617500', '11914839207106800', '11914842003682400', '11914859983528400', '11914862027163800', '11914876958877400', '11914882580535500', '11914898826222500', '21914899038575300', '11914903776357400', '11914908569896600', '11914912702028500', '11914916524715800', '11914919377738701', '11914919808225902', '11914921698033100', '11914927160550500', '11914934507910800', '11914934520194000', '11914940912582400', '11914950918254300', '11914953223702500', '11914959624290000', '11914971094201400', '11914971546073200', '11914973047252800', '11914973284352100', '11914983465543700', '11914983132280100', '11914986465865801', '11914986666251100', '11914993918955700', '11915004465411900', '11915009464474500', '11915010944043000', '11915013212511001', '11915014762517000', '11915025445488000', '11915027983727300', '11915028799615500', '11915032683510000', '11915043240554500', '11915044906347501', '11915047129331600', '11915048173781000', '11915049486361501', '11915050290118800', '11915050876444600', '11915051840720500', '11915058498294000', '11915060892305300', '11915062469493200', '11915063338695200', '11915070626232700', '11915075184382200', '11915083124824401', '11915084958615100', '11915085407377403', '11915086954981000', '11915085157924000', '11915087201586600', '11915089006204000', '11915089798596500', '11915094179238800', '11915092471124100', '11915113023131200', '11915117008812000', '11915115610590300', '11915121067916000', '11915125053351600', '11915135895344900', '11915142237360000', '11915151499280500', '11915152885986800', '11915163844357700', '11915174850216100', '11915180496347800', '11915183970706600', '11915185206837600', '11915187691688600', '11915191863532400', '11915200029036900', '11915227326526400', '11915246511880000', '11915261983315500', '11915293220658600', '11915337089864700', '11915381938368000', '11915442618428200', '11915480996887000', '11915499910482000', '11915501572733300', '11915506513233700', '11915509881767000', '11915516666095000', '11915520041856100', '11915521497594400', '11915525517431300', '11915527561281500', '11915543893983000', '11915540156731100', '11915551019156000', '11915552434863400', '11915567187902300', '11915586193311900', '11915590603383400', '11915591074724500', '11915594001382600', '11915597572782700', '11915601504511200', '11915611917878800', '11915614868115700', '11915624341343000', '11915628731406200', '11915631523301000', '11915632924258100', '11915634131178000', '11915645222225600', '11915647973231700', '11915649038905300', '11915653291073300', '11915654596082500', '11915655340450600', '11915658380521600', '11915658968163600', '11915659106920101', '11915659373286600', '11915671723618200', '11915671987692400', '11915673170614700', '11915673188071300', '11915678154934900', '11915678335365700', '11915687721853700', '11915699183036700', '11915700325827600', '11915711454914700', '11915720135235800', '11915732664627900', '11915741564840600', '11915745949351900', '11915746124558900', '11915756753893800', '11915760163977601', '11915760495795900', '11915761194947300', '11915763686088200', '11915769713452900', '11915771996951300', '11915779589424700', '11915780262456000', '11915783593916701', '11915790243617300', '11915790512404000', '11915793632266200', '11915797282060700', '11915803355412300', '11915813684188500', '11915826140822700', '11915828899538900', '11915833926212100', '11915835131787700', '11915844224220000', '11915845154346000', '11915850566092200', '11915858853176200', '11915864871333300', '11915879814408600', '11915882188976900', '11915891889161400', '11915903326701500', '11915916000620300', '11915923368100600', '11915924195761200', '11915934166394400', '11915944177501500', '11915944819318600', '11915945000348000', '11915956785548000', '11916006987766800', '11916020001860702', '11916023799275700', '11916026036042400', '11916038048088100', '11916032309964200', '11916057368755100', '11916057875098600', '11916064943238200', '11916068367318100', '11916069293362600', '11916078044271700', '11916090733038300', '11916140807427000', '11916163208856800', '11916312922646800', '11916323628163600', '11916331381353900', '11916336953753500', '11916340051938000', '11916348707302600', '11916358884846000', '11916364899108200', '11916369380635000', '11916390261946500', '11916402600083300', '11916410562291403', '11916407653724800', '11916416126461800', '11916416072515400', '11916417683452800', '11916440697920600', '11916445202592600', '11916446752072500', '11916452851877200', '11916464660416400', '11916471655537900', '11916479974786401', '11916481167988300', '11916489936408200', '11916490207407000', '11916491580607400', '11916494573624500', '11916498007451000', '11916499136905100', '11916502445146400', '11916509613881800', '11916518418972500', '11916520783598600', '11916524539804700', '11916527877216900', '11916530337571400', '11916531072907000', '11916532480812600', '11916532955934700', '11916533113161300', '11916538311944100', '11916543343870200', '11916547075542700', '11916541419297800', '11916550509390400', '11916551138944700', '11916553088758200', '11916555779937000', '11916560037971700', '11916562155154200', '11916566580992900', '11916566576334500', '11916570734160100', '11916577244915800', '11916577494271000', '11916578045006900', '11916577807340000', '11916579517757500', '11916581940551500', '11916584347091100', '11916584655033200', '11916586681920900', '11916592070888000', '11916593143248300', '11916594770132300', '11916597373644400', '11916598497446500', '11916599397978200', '11916600569622000', '11916601318391300', '11916605923985200', '11916609633873700', '11916610908401600', '11916611271512900', '11916612870255300', '11916619968200100', '11916626842787300', '11916632322145200', '11916635119116700', '11916645744991800', '11916645599062400', '11916648877530800', '11916658355597600', '11916659775975300', '11916664723112200', '11916670443558600', '11916665131117500', '11916671544196100', '11916673057105600', '11916673460664300', '11916675574590900', '11916678283850401', '11916678126467300', '11916678626344200', '11916680449562100', '11916680928245800', '11916683328893000', '11916688137674000', '11916703590927900', '11916708343444602', '11916710301232900', '11916719887672400', '11916724908420101', '11916727204694400', '11916728047905400', '11916731178307401', '11916733154557400', '11916733611324500', '11916738989944000', '11916742190556800', '11916748500643700', '11916749720588300', '11916747707378400', '11916746046367100', '11916750878918600', '11916754512843300', '11916756065043100', '11916755596722600', '11916759278946500', '11916761367322300', '11916764874601500', '11916767008857000', '11916770809630900', '11916773224675700', '11916772550252600', '11916773798024900', '11916774008642100', '11916775815024200', '11916779187673800', '11916780098752300', '11916787904012300', '11916789893212800', '11916796345067400', '11916795310145000', '11916801770673500', '11916802104594900', '11916804282416400', '11916804493853400', '11916809550191300', '11916813085358000', '11916818299850100', '11916819818332800', '11916819960840300', '11916821580250900', '11916822902434500', '11916824742883900', '11916826677730100', '11916829858104200', '11916839450051100', '11916849127564600', '11916851477165700', '11916852253236000', '11916854910948700', '11916863708780000', '11916867443595800', '11916865275288300', '11916872906793700', '11916879569140100', '11916878402135100', '11916887031616800', '11916899755703100', '11916909121792700', '11916913344920500', '11916916819572600', '11916922760478200', '11916928806765600', '11916933172045101', '11916937624025700', '11916958591056200', '11916967563746000', '11916984154387300', '11916996415941300', '11917001384300000', '11917030572518600', '11917136008812300', '11917196255473200', '11917198193957000', '11917197100843600', '11917220895492700', '11917233243873100', '11917246164422900', '11917246394026500', '11917250558792300', '11917253416081700', '11917265931105400', '11917278422534300', '11917285936534200', '11917287581185700', '11917292044507900', '11917291154826300', '11917293791471100', '11917297541673700', '11917297762065900', '11917300597403500', '11917304705495800', '11917310643406800', '11917310815200900', '11917311128714100', '11917314501786600', '11917316389613400', '11917318845518300', '11917321357192300', '11917320861037800', '11917321993940800', '11917335383837100', '21917335837610500', '11917339210963100', '11917342040092800', '11917347855463800', '11917351907391200', '11917353128836600', '11917355590992001', '11917353726137200', '11917358106413100', '11917358712303200', '11917359750135400', '11917359957145400', '11917359807342600', '11917362631003300', '11917364282607900', '11917370597623200', '11917371027517000', '11917373089932400', '11917397990154000', '11917399466663200', '11917403763843600', '11917422377012500', '11917424195285500', '11917425702210400', '11917426696608700', '11917436597260100', '11917444713478000', '11917461232308300', '11917480425795200', '11917483725646400', '11917495663758000', '11917502327291500', '11917515412660000', '11917519921822800', '11917525419326100', '11917613766170400', '11917617183376300', '11917624607701600', '11917626226115300', '11917632976310200', '11917641619747100', '11917648848294200', '11917650672518700', '11917656220008000', '11917660171365100', '11917668809756100', '11917669138117100', '11917670067444300', '11917679212450500', '11917681950110300', '11917681877645800', '11917687405834200', '11917690354864900', '11917693021963600', '11917693610346200', '11917697128977400', '11917699247507500', '11917716742758200', '21917718464173400', '11917723750107800', '11917727811612300', '11917727902238000', '11917779615438000', '11917798930365200', '11917808815875700', '11917824214363600', '11917836385184000', '11918046816513100', '11918051802783000', '11918101640102700', '11918109573278800', '11918129471193700', '11918135142642400', '11918135954222900', '11918141168156500', '11918150513068900', '11918153613810800', '21918157237082300', '11918164942422200', '11918171876943300', '11918180369776700', '11918182416270600', '21918199165918700', '11918212465161600', '11918230541088900', '11918274111006101', '11918288587752500', '11918384347416601', '11918397966294000', '11918412025231000', '11918437173667400', '11918500662216300', '11918557162544300', '11918561729404600', '11918565905751600', '11918577841477000', '11918659118797400', '11918664467060500', '11918664435947100', '11918673347308700', '11918676149713200', '11918768634834900', '11918888272803800', '11918904567453300', '11918960324987600', '11918969079397400', '11918987972094100', '11918988196525800', '11918988727817800', '11918988980641800', '11918990202447600', '11918992908148200', '11918993501970800', '11918993962737900', '11918994762121900', '11918999208336300', '11919001894808200', '11919002627848100', '11919003448624500', '11919004485605900', '11919004612251900', '11919007191258000', '11919008024918600', '11919008847806700', '11919008578834300', '11919012717525801', '11919015814935300', '11919089388437000', '11919104141743300', '11919116875911000', '11919137051261400', '11919162687291600', '11919175368021800', '11915072339212703', '11919230432928200', '11914060549702202', '11919235745957400', '11919240396692400', '11919267699241000', '11919279657747600', '11919305653042700', '11919320523704301', '11919346403088600', '11919352646731200', '11919367692043601', '11919368892034400', '11919383322467200', '11919392412783700', '11919393706742601', '11919419144822600', '11919420935854300', '11919437419601700', '11919439370948600', '11919459678675400', '11919474638696300', '11919492185328900', '11919719665933400', '11919791681058500', '11919802802326200', '11919813048372000', '11919813508542200', '11919822928595800', '11919819939718200', '11919831871178600', '11919836725252900', '11919840082433600', '11919848006042300', '11919857232472900', '11919856786752000', '11919860490272200', '11919861632533700', '11919866273726800', '11919866165746301', '11919868959714400', '11919874232157700', '11919876586118500', '11919877156975600', '11919881474882800', '11919888217688900', '11919891553062300', '11919898065123700', '11919906940584700', '11919914416360400', '21919918206163400', '11919918650014000', '11919919027861300', '11919924338204900', '11919934912984500', '11919945237997500', '11919947411852400', '11919960908702600', '11919964139337000', '11919985441400400', '11919987745620300', '11920006090378000', '11920027632908800', '11920033208415200', '11920040597801600', '11920048582980200', '11920053808497900', '11920062012662500', '11920067230634500', '11920068040852500', '11920070599086300', '11920074335346200', '11920081241838200', '11920082043081300', '11920088359842100', '11920096710757800', '11920097364511400', '11920107989621600', '11920114879772400', '11920127019452300', '11920137869934200', '11920143712624400', '11920155916095600', '11920160639923600', '11920162419941800', '11920167534283200', '11920167614118100', '11920172008705100', '11920174778803000', '11920179869251900', '11920181241118900', '11920184493768000', '11920185499221900', '11920212865377800', '11920230346834700', '11920234466553700', '11920236783168000', '11920246575575800', '11920256460176800', '11920270207755300', '11920283874688700', '11920291472380500', '11920291613398100', '11920309506630800', '11920310426226800', '11920310739898800', '11920315266613900', '11920317020131302', '11920320213325400', '11920383909902400', '11920382831960600', '11920412429778000', '11920453800064800', '11920627748555000', '11920656708301400', '11920675909122701', '11920679904625700', '11920680659941500', '11920682839166400', '11920713421005300', '11920721733811600', '11920723816890700', '11920760026256400', '11920764645677100', '11920767171983200', '11920774877631300', '11920774917158900', '11920775818872700', '11920798861244000', '11920799598295300', '11920821050151900', '11920822104835200', '11920828665747600', '11920830606508000', '11920830491902000', '11920832146038800', '11920837747447900', '11920842662606700', '11920845478194300', '11920848701943700', '11920865492490000', '11920867403724100', '11920868764311900', '11920871055696800', '11920882743184200', '11920890195668500', '11920947174681500', '11920963856741600', '11920967909222100', '11920975423200400', '11920988223791300', '11920992417664600', '11920997024075400', '11920998647432900', '11921006469992900', '11921007494404900', '11921022634920300', '11921049882633101', '11921052914424200', '11921081819556600', '11921082823221300', '11921082820954400', '11921086708561000', '11921086999483402', '11921088446396000', '11921101115683700', '11921101186263100', '11921115252730600', '11921118172922000', '11921121747935900', '11921122504282500', '11921128107805300', '11921130255991802', '11921129387671000', '11921139669222900', '11921140739948200', '11921141499355100', '11921143567811100', '11921149364454000', '11921160962100400', '11921161835448100', '11921174033217800', '11921182257862200', '11921185192255500', '11921186293143400', '11921194889651000', '11921202514203000', '11921206146220500', '11921213969896300', '11921216075884901', '11921219587898300', '11921224584281600', '11921229240031700', '11921245804462700', '11921243791186400', '11921279744091600', '11921475421066800', '11921532361304200', '11921538720222700', '11921546321370800', '11921562002840600', '11921565672678800', '11921586342531300', '11921587108446500', '11921590268705600', '11921594707976000', '11921595975632100', '11921600380271100', '11921604159471700', '11921620449470100', '11921620773598300', '11921638731695100', '11921642562996700', '11921645809953700', '11917585178123301', '11921648878972800', '11921650605382300', '11921652880511300', '11921659747744100', '11921662800617300', '11921663661243100', '11921663641363400', '11921670112310500', '11921672423651800', '11921675109785000', '11921676400735300', '11921676285227700', '11921673498486400', '11921688266338800', '11921690273161900', '11921694746295100', '11921698115878700', '11921698255080700', '11921698908585600', '11921705015451900', '11921714915948100', '11921721755205900', '11921730920333500', '11921730887974700', '11921733162665400', '11921739203355600', '11921744985827500', '11921750978694900', '11921752122228800', '11921759217894300', '11921763152980000', '11921763102355500', '11921768499695600', '11921781496540700', '11921782465190000', '11921791227334600', '11921802212608600', '11921804698048200', '11921813787186600', '11921813666851900', '11921814035820500', '11921821912110100', '11921825500844900', '11921833418696500', '11921835957184700', '11921844541177300', '11921848698680200', '11921854059794300', '11921859569110300', '11921857616540600', '11921861497934500', '11921859700193000', '11921863393697500', '11921863412871700', '11921867706218600', '11921872584482400', '11921872606172200', '11921873215647200', '11921874802412201', '11921880147482400', '11921881177094300', '11921885787908400', '11921886819427900', '11921889448463200', '11921891754861200', '21921894098865200', '11921900361035800', '11921903656744800', '11921907147778100', '11921924139060000', '11921926886675600', '11921934867296900', '11921935886712600', '11921936832310300', '11921938206128300', '11921939179393500', '11921941345104700', '11921954408346800', '11921955093881900', '11921962678357000', '11921969222797800', '11921972787944400', '11921974137657500', '11921993857988700', '11921995446706300', '11922000789296000', '11922003426242500', '11922005434263201', '11922008092503700', '11922032017000700', '11922032596941600', '11922032444247700', '11922039423387400', '11922060879507000', '11922061587344800', '11922062407910600', '11922075985061200', '11922080339282401', '11922083089251400', '21922085226231800', '11922104659284100', '11922124706442500', '11922148241795800', '11922166178744500', '11922171957167700', '11922172672921200', '11922244179910300', '11922252114317502', '11922288727766300', '11922294950705200', '11922337369208900', '11922337689464500', '11922357101808300', '11922363534874804', '11922384197192900', '11922384825221800', '11922396942337400', '11922403785422300', '11922404369158600', '11922404815791300', '11922449196888900', '11922454365618100', '11922462414836100', '11922464641142200', '11922470715766301', '11922472273166900', '11922476827407400', '11922485994230600', '11922486143051600', '11922485827471403', '11922486702657800', '11922492437665400', '11922500520455000', '11922502928376800', '11922503696323600', '11922511162490900', '11922521730053800', '11922536362328800', '11922543696928300', '11922548389633200', '11922556011536300', '11922562748715600', '11922567161693500', '11922572971688900', '11922574309487700', '11922596065051000', '11922611839691300', '11922621472476200', '11922627206550900', '11922634765732300', '11922636342436100', '11922637371063400', '11922658752790801', '11922659838458001', '11922667633341100', '11922680886543700', '11922703860735001', '11922720106942400', '11922721916960700', '11922730554071600', '11922746381084000', '11922749657003300', '11922764944413800', '11922786490251200', '11922786640647400', '11922787764600700', '11922800284214900', '11922803409421200', '11922803846545200', '11922814166152200', '11922821433081200', '11922836623283000', '11922844359747900', '11922844599531000', '11922852048531900', '11922874135666101', '11922881958461800', '11922901117988000', '11922898728170800', '11922904598413000', '11922904402055200', '11922907306582900', '11922922289365700', '11922925908392800', '11922928212994400', '11922950451948800', '11922972005446702', '11923015255435900', '11923125966625600', '11923154286161200', '11923277109532400', '11923283452262600', '11923285803555100', '11923314077620700', '11923319986798100', '11923340933930800', '11923359909762800', '11923361268816100', '11923368332440600', '11923371059635000', '11923395221382500', '11923400057187900', '11923425305538900', '11923425810166900', '11923448628301900', '11923456630777500', '11923467359526000', '11923479645898700', '11923483203580000', '11923499091576800', '11923504791188400', '11923514886775100', '11923516049460300', '11923516431063200', '11923525165212300', '11923521040075400', '11923568016826800', '11923596977558600', '11923622326995800', '11923641699752000', '11923646586296100', '11923654741218700', '11923657328152700', '11923672505806300', '11923687466122300', '11923702666911500', '11923706024816200', '11923727643937300', '11923734307562200', '11923736258111800', '11923750210728500', '11923757571507300', '11923760126116000', '11923767624034700', '11923780874888300', '11923791282512500', '11923804277422400', '11923804988284800', '11923807726577400', '11923810413606100', '11923838737723300', '11923839586362200', '11923866178920800', '11923867489767800', '11923872053210400', '11923983865120900', '11924050800072900', '11924076073431900', '11924087474751000', '11924088512892700', '11924097977378300', '11924106820782300', '11924113294532400', '11924128703988700', '11924147992763700', '11924162878321500', '11924169069614700', '11924170323085500', '11924197207927800', '11924216047584600', '21924219044123700', '11924224325538300', '11924225896263000', '11924232666830000', '11924238877056000', '11924242665806200', '11924259800008100', '11924264634451000', '11924275003637300', '11924279833388300', '11924283568684200', '11924284197185500', '11924296028004200', '11924309645876900', '11924337475167100', '11924344405660300', '11924346821728400', '11924349815790800', '11924356588571100', '11924392226370401', '11924435444506700', '11924441197122601', '11924489199427500', '11924498438715300', '11924510399262000', '11924521040391000', '11924522941942000', '11924529899168400', '11924530263722800', '11924536425486900', '11924557317174500', '11924558461668700', '11924558795896300', '11924541683952500', '11924564045482900', '11924565368033100', '11924582346721500', '11924590226498200', '11924597370394500', '11924598612951900', '11924602719261100', '11924606002975100', '11924606760603400', '11924610437766801', '11924617635912800', '11924618913254600', '11924622328805600', '11924625498187800', '11924629476773400', '11924632306053500', '11924634589262600', '11924644356762200', '11924650112542401', '11924661183361400', '11924670270563000', '11924672412913401', '11924673518960900', '11924678957894600', '11924676565464800', '11924686857174500', '11924687708405800', '11924688276408900', '11924695930833700', '11924702358758100', '11924704030532000', '11924706446021800', '11924759404177100', '11924880592928100', '11924961971510800', '11924966769860000', '11924969959190900', '11924984818852500', '11925027146813300', '11925031679472600', '11925034073252600', '11925029961530500', '11925036390162800', '11925039919178300', '11925042593115000', '11925043117227900', '11925050962752600', '11925052337283900', '11925052856747200', '11925052957745800', '11925054223336200', '11925054537748600', '11925056257911902', '11925057969574100', '11925057998898100', '11925057537825200', '11925059233572700', '11925065021127000', '11925067389513900', '11925070190361100', '11925071883966300', '11925070920856700', '11925078170505600', '11925079771710100', '11925080994923100', '11925081918707900', '11925083114243300', '11925084196508900', '11925087711542600', '11925088596996300', '11925089135787400', '11925096308821100', '11925101733896600', '11925102513831100', '11925096632352900', '11925102342092600', '11925104389814900', '11925105422280600', '11925106367541200', '11925107171791800', '11925110029800700', '11925111200765500', '11925111659813300', '11925113561844700', '11925115144323702', '11925115299420700', '11925119528443100', '11925119864738600', '11925119819696900', '11925122407911400', '11925127384383200', '11925129975888700', '11925133204138800', '11925143988761600', '11925143162768100', '11925148445840900', '11925149112037300', '11925148756317601', '11925149058215600', '11925151765567600', '11925157345307300', '11925158954224100', '11925160874024500', '11925162160755600', '11925162207297601', '11925165603197400', '11925169489337800', '11925173735412000', '11925173461525902', '11925176992412900', '11925181929207200', '11925183018804900', '11925185393868400', '11925187204932400', '11925193781712200', '11925194064432200', '11925194276976300', '11925201582685000', '11925201872218700', '11925202832310100', '11925204418468300', '11925212667158100', '11925220337658400', '11925224656490200', '11925219532364600', '11925223139580000', '11925228284096600', '11925232326052800', '11925232423823100', '11925231934285700', '11925231762140300', '11925233164810400', '11925238376091100', '11925247544537800', '11925247425197700', '11925251026481500', '11925266866924500', '11925270120648400', '11925272115744500', '11925279183110300', '11925287488568700', '11925291163915000', '11925299278046400', '11925307183307500', '11925316701737300', '11925318697037300', '11925321649985301', '11925320606073400', '11925322276523600', '11925330865426400', '11925332458116800', '11925352997277100', '11925355216935800', '11925367298795600', '11925372959805500', '11925374980487000', '11925375610842000', '11925379029382400', '11925378428722800', '11925388082781600', '11925403512085900', '11925408988245800', '11925413699184000', '11925417084762900', '11925419259198600', '11925422308471700', '11925422528000000', '11925426786707600', '11925433763308201', '11925434992097100', '11925435783513300', '11925433985876300', '11925434784046700', '11925437302483600', '11925443921383200', '11925444741423200', '11925442313212200', '11925445315354300', '11925445057567400', '11925446915848600', '11925446355115900', '11925450804825600', '11925459396484700', '11925462324173500', '11925474332028800', '11925475903626000', '11925476278778400', '11925480913226000', '11925485722235300', '11925495233165300', '11925501066133200', '11925508324178100', '11925510011250200', '11925512437124404', '11925513052595600', '11925512669550400', '11925531134406000', '11925537878135700', '11925538554741300', '11925562879584900', '11925563198384200', '11925586193426400', '11925583993895800', '11925601020685900', '11925607553988200', '11925612770051102', '11925621376092000', '11925652271144501', '11925692183370000', '11925704836154700', '11925744832867700', '11925804375862300', '11925808890441800', '11925835077476100', '11925857183727900', '11925859092330200', '11925876921195400', '11925877341963900', '11925877408557200', '11925877937406500', '11925888564666200', '11925908572924800', '11925927099166800', '11925931497880700', '11925935441286800', '11925944032432900', '11925951361655200', '11925952104161002', '11925953201460100', '11925956445703600', '11925968004258700', '11925972318046900', '11925986177682600', '11925989208992300', '11925999167694100', '11926003637058100', '11926010624696900', '11926013272761100', '11926010735667800', '11926020760180100', '11926021801390300', '11926023578037400', '11926023884676300', '11926027686924500', '11926030688722700', '11926029959271300', '11926034884331000', '11926040168370300', '11926040695150100', '11926043256937800', '11926075181396000', '11926076402627400', '11926076721378000', '11926082241287502', '11926087299490700', '11926089625693000', '11926089302468200', '11926093641605800', '11926092531263100', '11926099890267800', '11926104888355900', '11926111305640600', '11926111867546700', '11926112471113100', '11926117832290900', '11926123457157200', '11926132247843101', '11926142889116400', '11926167504956100', '11926169086400300', '11926171685297100', '11926175458854700', '11926191515432400', '11926201420688100', '11926202467338300', '11926213128060600', '11926213999274800', '11926222764921600', '11926228148968400', '11926253776597800', '11926262409801000', '11926265426592600', '11926265817637400', '11926276364438000', '11926279480815200', '11926280169137400', '11926280976572700', '11926287354510500', '11926299376770300', '11926306801560100', '11926317850087700', '11926336342910600', '11926350296971900', '11926361136744600', '11926361759735500', '11926370011332600', '11926383423266900', '11926388563162200', '11926395716567000', '11926406557626100', '11926432887955300', '11926438587721700', '11926445931963100', '11926447515693900', '11926462059777800', '11926471646980700', '11926499854531900', '11926546934222801', '11926690886602700', '11926691109470200', '11926723223087700', '11926728121825301', '11926739965677800', '11926736235304200', '11926759750513300', '11926775062055100', '11926789803483600', '11926789665030700', '11926801704433500', '11926807066374600', '11926810961547200', '11926823089946500', '11926835264866200', '11926840120160000', '11926843340763800', '11926861677374700', '11926871280212600', '11926875686038700', '11926892760120100', '11926895221967200', '11926899325577500', '11926916031544500', '11926973976385700', '11926981397243100', '11926989105013900', '11922083089251403', '11927016887621400', '11927020106355201', '11927022388434000', '11927029085454700', '11927035573332500', '11927035370220800', '11927048984125100', '11927058477927000', '11927061261644300', '11927069029031700', '11927072873461200', '11927074859743000', '11927083188877200', '11927095594697300', '11927098966296900', '11927105819162700', '11927111020582800', '11927112817897100', '11927112046214300', '11927121713506300', '11927127209501500', '11927139782463900', '11927142474596400', '11927146797528900', '11927145050664003', '11927155846327800', '11927167018486800', '11927174178652500', '11927177131666500', '11927180945793400', '11927184156110000', '11927194217972500', '11927207681768200', '11927210237747501', '11927214557763700', '11927231053631800', '11927232865630200', '11927233868277401', '11927236296487100', '11927236430936700', '11927237905808900', '11927247601738400', '11927253162621300', '11927252882595600', '11927259722251100', '11927311336794700', '11927313621196400', '11927344158295200', '11927360899802300', '11927407953081400', '11927466696011700', '11927483821977600', '11927532649401900', '11927559152122500', '11927567008792100', '11927572244975900', '11927583855427800', '11927583771678300', '11927581917170500', '11927591492635000', '11927602888388700', '11927605249787100', '11927642206816900', '11927673774261400', '11927677366830600', '11927680778733500', '11927682956308600', '11927684901308800', '11927686269070100', '11927694009617101', '11927701702023900', '11927705809098601', '11927706785076300', '11927706329554900', '11927709542572800', '11927710185306101', '11927715160870600', '11923316862151001', '11927729436361701', '11927731005553300', '11927736672544400', '11927739284260500', '11927751726635200', '11927759109453200', '11927756662672400', '11927765164662700', '11927767898110900', '11927772801751000', '11927773299427700', '11927781962926300', '11927784267817700', '11927785955556100', '11927794591104400', '11927798785053200', '11927803995471100', '11927805794771100', '11927810776233700', '11927811332676500', '11927813610176700', '11927815921151500', '11927817855841700', '11927825863878800', '11927832415262200', '11927831334952000', '11927837323833100', '11927841784791300', '11927848538666100', '11927849717772700', '11927859770410500', '11927859096196200', '11927870381257300', '11927874518206500', '11927874713040400', '11927877045688100', '11927881475035400', '11927885551338700', '11927885947405800', '11927901270965500', '11927903795860100', '11927910263906800', '11927914981050500', '11927929526223100', '11927935874712900', '11927941758833800', '11927946028305500', '11927950317937200', '11927951315034700', '11927955414112000', '11927955545531200', '11927986213000100', '11927987657131800', '11927990824345800', '11927998741010400', '11928006221562100', '11928004845646400', '11928009530242701', '11928015696416100', '11928016312412801', '11928017210646000', '11928016256164000', '11928019634471100', '11928021399424800', '11928024138614800', '11928026022595100', '11928025679505200', '11928027477542800', '11928029709875600', '11928027756693000', '11928034713442800', '11928038066854100', '11928043250294101', '11928044031291800', '11928042596928000', '11928048847478300', '11928052549893300', '11928055245767300', '11928054525671200', '11928057148128901', '11928057146998800', '11928056872982000', '11928064133471700', '11928066551168800', '11928070571585400', '11928071839564700', '11928071909632800', '11928072733052600', '11928078752947800', '11928080043383700', '11928080248825200', '11928083009925200', '11928083531702101', '11928086389035700', '11928089267031800', '11928098257557000', '11928098462634400', '11928100667627300', '11928103659500500', '11928107145910500', '11928111818283100', '11928112131296900', '11928115144210200', '11928114235522100', '11928116051762900', '11928143257775501', '11928146526720400', '11928162812275400', '11928165698453900', '11928168489095900', '11928180539825300', '11928186299524300', '21928194356243000', '11928198775481400', '11928212127275600', '11928212872290200', '11928250366774000', '11928348525038400', '11928388511386200', '11928422055238400', '11928423544347500', '11928428668461000', '11928428902040600', '11928437290973200', '11928442028138100', '11928446194080300', '11928452519628000', '11928458267135600', '11928464079362600', '11928463899285000', '11928466973754900', '11928467961846200', '11928471061500800', '11928473448564200', '11928477027090200', '11928478486967101', '11928486674050700', '11928488419188100', '11928498263786000', '11928521108793400', '11928522772371700', '11928525697190400', '11928528662467300', '11928530494750400', '11928526950794300', '11928534605303000', '11928534964687400', '11928539577468500', '11928541876961500', '11928547654420300', '11928548721933000', '11928549063871800', '11928553207466700', '11928558678687100', '11928564920818200', '11928567320635300', '11928571662894000', '11928575903462300', '11928579785570400', '11928580563225300', '11928586984017700', '11928590848103200', '11928593846726100', '11928590096854700', '11928593671647000', '11928595034835100', '11928601541286600', '11928604081037000', '11928605464665800', '11928612509811600', '11928614034275600', '11928615297160600', '11928621044831200', '11928627590603300', '11928635684458800', '11928636594774400', '11928642815327700', '11928645153114100', '11928646858713300', '11928651334508700', '11928648059072700', '11928655577263600', '11928657878747000', '11928663037010900', '11928662016937800', '11928663749666300', '11928666920264200', '11928667073160100', '11928670420272000', '11928670899184600', '11928670062930500', '11928677460924500', '11928679968227500', '11928681904318800', '11928683951006100', '11928692873686004', '11928692777147003', '11928696938901100', '11928704904253300', '11928709457122600', '11928714972417000', '11928716452570600', '11928721928587000', '11928727530917700', '11928729437052000', '11928739618561201', '11928740282682300', '11928743431785200', '11928746317642800', '11928747639056700', '11928749617291900', '11928752082730800', '11928759801284900', '11928767977728500', '11928770515450200', '11928772520320900', '11928774325998600', '11928805895763500', '11928806665322600', '11928824386780800', '11928846853741900', '11928853335914700', '11928855112515800', '11928858937331600', '11928879836433800', '11928890462721701', '11928894029744900', '11928894454055000', '11928895287155600', '11928897491490500', '11928900447793800', '11928908254668300', '11928912083085700', '11928912352943800', '11928917362986300', '11928917870524600', '11928922421622500', '11928933658446700', '11928954371641300', '11928958655267200', '11928996248234900', '11929000386605700', '11929015067808401', '11929027452772500', '11929028366710200', '11929034243563500', '11929036428786900', '11929052272152300', '11929052309774600', '11929062253155000', '11929065973881000', '11929089523457300', '11929257822995100', '11929269138865900', '11929265453151300', '11929312855454700', '11929318330125500', '11929326545788600', '11929329384111200', '11929340000552500', '11929340041065100', '11929339988757100', '11929341748926500', '11929358842221600', '11929358841160700', '11929384392715600', '11929385713954500', '11929392096171700', '11929391593320800', '11929403281527400', '11929408605817900', '11929408889455800', '11929409475797900', '11929414415806500', '11929415208432300', '11929418596315100', '11929443095266700', '11929452015198400', '11929453897466700', '11929461615134600', '11929465155253500', '11929475453720502', '11929480713840800', '11929482029978300', '11929495196795900', '11929498725486200', '11929503107762800', '11929504951260100', '11929505165112500', '11929504753881800', '11929506543847800', '11929514695290700', '11929516178256200', '11929517614563800', '11929523030526400', '11929533244002600', '11929536247324100', '11929535730448200', '11929536742615800', '11929545459064900', '11929545767992500', '11929557763023400', '11929567381392600', '11929567387373701', '11929578641576800', '11929583840584600', '11926780343236601', '11929598209107500', '11929597408664700', '11929597862103900', '11929606440814000', '11929606642108400', '11929602884533700', '11929613325494000', '11929616269301200', '11929624288125900', '11929625991028801', '11929637755534100', '11929640674231500', '11929643417712600', '11929646524141600', '11929651673004700', '21929675841366100', '11929696907492900', '11929702432977600', '11929703982353800', '11929705927495800', '11929731080750400', '11929735803647100', '11929747746362800', '11929751850206800', '11929752822721600', '11929755042588600', '11929756039806300', '11929758126974100', '11929760442601900', '11929761757702700', '11929765101423600', '11929769345037600', '11929768414683500', '11929776152613200', '11929776120484300', '11929753752910900', '11929777172831100', '11929777192556600', '11929789006178900', '11929794151755901', '11929801064774500', '11929801229723600', '11929814668366502', '11929829957628100', '11929834537485500', '11929835375193700', '11929836295557000', '11929836474942300', '21929842043583900', '11929843297915300', '11929846461116000', '11929874693890100', '11929887882587000', '11929888966578600', '11929891791624000', '11929979584730600', '11930030153502100', '11930044161798200', '11930152030290900', '11930170363786400', '11930171750678400', '11930180877000100', '11930192265057600', '11930208857527300', '11930213738571400', '11930219164696700', '11930220221635600', '11930224946728800', '11930226303933000', '11930230173604800', '11930231908422800', '11930235046784400', '11930237192213300', '11930244514617300', '11930248467534600', '11930268479590401', '11930268733091500', '11930286066306700', '11930294448460200', '11930298669625200', '11930301010543600', '11930303720614100', '11930304235682200', '11930305407741200', '11930307769562300', '11930311054487900', '11930316337066000', '11930319407254700', '11930321444122800', '11930332607515800', '11930341407503700', '11930356403920800', '11930356754366900', '11930364401908800', '11930372662437900', '11930381685004100', '11930391526905700', '11930403906742800', '11930438248701100', '11930445105821700', '11930446432678500', '11930457540325100', '11930468829214100', '11930468063510400', '11930488306183400', '11930507880500000', '11930517647574800', '11930518637411900', '11930527377015100', '11930544787760100', '11930554512855200', '11930565193227300', '11930565496931700', '11930567137022900', '11930573799866800', '11930574556532300', '11930585570388800', '11930606487355700', '11930620762361000', '11930625958784700', '11930628417414300', '11930634276021700', '11930651915782700', '11930659928811600', '11930660955342100', '11930662151144500', '11930672425046500', '11930672675145600', '11930706835838400', '11930708878672000', '11927870381257301', '11926888486195001', '11930733775368500', '11930753500211500', '11930986116733000', '11931017177981400', '11931018234970000', '11931027057811801', '11931036780045101', '11931063135461800', '11931074537794000', '11931072308324500', '11931087362146000', '11931097528276400', '11931096995505601', '11917525419326101', '11931112497660700', '11931105679765200', '11931135616462900', '11931143157066400', '11931158971348400', '11931177454307200', '11931196186916400', '11931203604436800', '11931207653435700', '11931225233387100', '11931227395204800', '11931231805138000', '11931232082441900', '11931236344714100', '11931250760551300', '11931267096612600', '11931298809956100', '11931303009165200', '11931320642413900', '11931330448166600', '11931339609167100', '11931347741144600', '11931347647560100', '11931349315812400', '11931355454465600', '11931380369144700', '11931426824100800', '11931451358005700', '11931477739561800', '11931489683918700', '11931491239712500', '11931505784148600', '11931509138052900', '11931515374040000', '11931575945092200', '11931585620762800', '11931861040677500', '11931896856460100', '11931898358711000', '11931915489334200', '11931949797952900', '11932006737564000', '11932010310827600', '11932034234616600', '11932051269786800', '11932053388264000', '11932065829846700', '11932068211738900', '11932069522792900', '11932082828325700', '11932087092716500', '11932102149753800', '11932110241487100', '11932124937472100', '11932128906048900', '11932128457671800', '11932140847782900', '11932143303221700', '11932143726572800', '11932153627998300', '11932163289716600', '11932170378855100', '11932170843255500', '11932193542822900', '11932220257474000', '11932222511785600', '11932221237934300', '11932224199275600', '11932230285211700', '11932232744628700', '11932234152201000', '11932255308513300', '11932259553443000', '11932271771645900', '11932291253402201', '11932296978476900', '11932308298415900', '11932320272946700', '11932357625622000', '11932370522595200', '11932373897003500', '11932388960555600', '11932401153762400', '11932412390051800', '11932420369521500', '11932442266094200', '11932456557363400', '11932477998324900', '11932481124874500', '11932508779206000', '11932706346333100', '11932712565644500', '11932811628153900', '11932819326665800', '11932835644844400', '11932859402812600', '11932873653317200', '11932881790966800', '11932899507438500', '11932922658091804', '11932938034758400', '11932968709152101', '11932990468851700', '11933019201175000', '11933037126078300', '11933052249143500', '11933068068300100', '11933090314545800', '11933111728086000', '11933110016482100', '11933146194363001', '11933165885118400', '11933169029771900', '11933170945567300', '11933182083258600', '11916629192791201', '11933206850470700', '11933211708025900', '11933215631103600', '11933215541914200', '11933218845934200', '11933238483576400', '11933254192700000', '11933604809287900', '11933635104614200', '11933747605993800', '11933760920505400', '11933758997485000', '11933773746103200', '11933812733373800', '11933851069973000', '11933864145378800', '11933867838607200', '11933908213378500', '11933977421592300', '11933976823424700', '11933982496224000', '11933982103823800', '11933991439950000', '11933990124082900', '11934019315306700', '11934051695578300', '11934060207855400', '11934063167607000', '11934065497927300', '11934068401665500', '11934081338324200', '11934089072796000', '21934091562946700', '11934116234290900', '11934126161563200', '11934165155438500', '11934225601947200', '11934424216453900', '11934470616444300', '11934478866274700', '11934488167943800', '11934528333601300', '11934541832112000', '11934551457600000', '11934562735475700', '11934572263731900', '11934581423223900', '11934586728831600', '11934587818478400', '11934594567355800', '11934611018827500', '11934614537595300', '11934620122248000', '11926989105013901', '11934667324121800', '11934670988892700', '11934692665145000', '11934697102985200', '11934717360553900', '11934719502243000', '11934725335486500', '11934743084608800', '11934748007731000', '11934756391454200', '11934770342420400', '11934784109621600', '11934807293685800', '11934834000204200', '11934854124356700', '11934867692946900', '11934880283733200', '11934888596134300', '11934928443114000', '11934932254063401', '11934939676012400', '11934942935573300', '11934954039483800', '11934957886425700', '11934960789822800', '11934962317112000', '11934969566085900', '11934969423738500', '11934975416942000', '11934979124814000', '11934982795283500', '11934987025725300', '11934985094114000', '11934984125650400', '11934996136035200', '11934998598694700', '11935000590043900', '11935001480035600', '11935008619106600', '11935014724297401', '11935015644987000', '11935025774264000', '11935031749623200', '11935066983330200', '11935073719168900', '11935074124544800', '11935094424654800', '11935095188015000', '11935099096245101', '11935129069205301', '11935169983210900', '11935195817423300', '11935288113931200', '11935292560547600', '11935314041316900', '11935316078281400', '11935322013680800', '11935322484518600', '11935332957426700', '11935338037290900', '11935340222350800', '11935352972120800', '11935355861791500', '11935361193567100', '11935369208003400', '11935373775371400', '11935377470710900', '11935375890890300', '11935388156248700', '11935392603514100', '11935396775377201', '11935401527176500', '11935398466512400', '11935404827474800', '11935404837188100', '11935405685317700', '11935416679040700', '11935418269272900', '11935422180403900', '11935423861410700', '21935427791885900', '11935429534993900', '11935434850783400', '11935436100088700', '11935436298576800', '11935435994100700', '11935437749207500', '11935439873630201', '11935439867018200', '11935438894154500', '11935442854107500', '11935444759476501', '11935442497337701', '11935445867091600', '11935450723180600', '11935450796448000', '21935447730812300', '11935452200157200', '11935452659128600', '11935452694597800', '11935452590461000', '11935453758011400', '11935456295521200', '11935460610800600', '11935463392447100', '11935465985978100', '11935464824044900', '11935467942732500', '11935471397446100', '11935478160101200', '11935478807125000', '11935480363156000', '11935484013844003', '11935484154834900', '11935493314211700', '11935494023187000', '11935495480031400', '11935497110963100', '11935497536333700', '11935486880147600', '11935500052607300', '11935503715607900', '11935506821890500', '11935508038456000', '11935508508266800', '11935512936764200', '11935513375065400', '11935512234230400', '11935514976210700', '11935519831332800', '11935522689320900', '11935532978401501', '11935532566013000', '11935535706178600', '11935536409802400', '11935549922468101', '11935551271863200', '11935552594394500', '11935556245868500', '11935560448626800', '11935561223982000', '11935564345471100', '11935564506631400', '11935567989538400', '11935572911063302', '11935582532035900', '11935593392396200', '11935592253778000', '11935594455330000', '11935597823748200', '11935602257644000', '11935607492647800', '11935608346258600', '11935610953027600', '11935610660992600', '11935610952463900', '11935615610713800', '11935616999127600', '11935623279707400', '11935625440295800', '11935628270177400', '11935629939296700', '11935632609181600', '11935634901294800', '11935635788591800', '11935635889176500', '11935637010368700', '11935640506620100', '11935639540154900', '11935643183582900', '11935645926427800', '11935651119468500', '11935651375210801', '11935653900657400', '11935654559231700', '11935655848650201', '11935655415762400', '11935659189882901', '11935659646752700', '11935659618665600', '11935665820664700', '11935667835041500', '11935672886530500', '11935679723577700', '11935680907758400', '11935681899844800', '11935691348885500', '11935692609000800', '11935696844037200', '11935696707790100', '11935698553295600', '11935701154962400', '11935703347146000', '11935704970846500', '11935706330591400', '11935708316234700', '11935712393172300', '11935713748380400', '11935715121256600', '11935721263701000', '11935726278682700', '11935728900437800', '11935728910832700', '11935730618046400', '11935729987326900', '11935732023803901', '11935732329278200', '11935733884532300', '11935737447157504', '11935736528233100', '11935746700721900', '11935748732586400', '11935749705473800', '11935750265445600', '11935750083954000', '11935753597274200', '11935757354904100', '11935761329198500', '11935761737460700', '11935763298653100', '11935763793642800', '11935764716653400', '11935765884213600', '11935768127977700', '11935770421155800', '11935772746923600', '11935774960683300', '11935776863778100', '11935777872431000', '11935778841433900', '11935778661774100', '11935779270803100', '11935779978052501', '11935780717581201', '11935781919285500', '11935787979473500', '11935788252277900', '11935787617131500', '11935786898447600', '21935794802632900', '11935795092073001', '11935796177422800', '11935796076366100', '11935798602060300', '11935800306930600', '11935800676462100', '11935799164645200', '11935802196168100', '11935802680515101', '11935802674616600', '11935803261843500', '11935803499920100', '11935799331766603', '21935804589194400', '21935803463762900', '11935805616267600', '11935813410084000', '11935818771013300', '11935821366675301', '11935821735264900', '11935825024768600', '11935826454024300', '11935829294934400', '11935829178407200', '11935828234085700', '11935831728388600', '11935835705543500', '11935836569815600', '11935837816226900', '11935843083176600', '11935845947723900', '11935846878214400', '11935847997503000', '11935846033081800', '11935852313576200', '11935856990807900', '11935857019296100', '11935860195398700', '11935861896948800', '11935865699601600', '11935866611303200', '11935876294587600', '11935877799895300', '11935881742127600', '11935881520803500', '11935882534813500', '11935884817751800', '11935886854452800', '11935889949763801', '11935889285890101', '11935889502771900', '11935891681005800', '11935893157122400', '11935895550025200', '11935901282478700', '11935901135401600', '11935901685945000', '11935935914323700', '11935943953085700', '11936076247442600', '11936215081565800', '11936218588023900', '11936261411626700', '11936262510530900', '11936304420597501', '11936337790551300', '11936339273107400', '11936339369968200', '11936353491467200', '11936374808546100', '11936383613186400', '11936490915690600', '11936502560921900', '11936519327105900', '11936527616325200', '11936543180726600', '11936558240884200', '11936559715242600', '11936605527955300', '11914017948728001', '11936631876491702', '11936676779337500', '11936697321950400', '21936704948865100', '11936727501008002', '11936752690570500', '11936752251988900', '11936755583671400', '11936756118956800', '11936768704210500', '11937050110987200', '11937059372452000', '11937062357726500', '11937070838276400', '11937071154211100', '11937071578288600', '11937073839401100', '11937075978034200', '11937097133675300', '11937105907038500', '11937108509106500', '11937111979008100', '11937114427455700', '11937117826926800', '11937119901401200', '11937124152580400', '11937142431136900', '11937149564197100', '11937150276445600', '11937157246380900', '11937157267728101', '11937158668134300', '11937170657300200', '11937183592515900', '11937199342175200', '11937202261003500', '11937224312515100', '21937226168067900', '11937236917762200', '11937252716432400', '11937254323707000', '11937288537531300', '11937292600613602', '11937292447697307', '21937295926398900', '11937305480288200', '11937355798644700', '11937370861817100', '11937387633264000', '11937393810120801', '11937396133341500', '11937429046832300', '11937429349022200', '11937431182190800', '11937442028626300', '11937447213786801', '11937467459021500', '11937475857221800', '11937479137460500', '11937491241120200', '11937495921001902', '11937503640337200', '11937520653942900', '11937533287443300', '11937535009926100', '11937558506927400', '11937562350130300', '11937567756738502', '11937578828950000', '11937579404295800', '11937582347922700', '11937589849126500', '11937596425784800', '11937600460964900', '11937603914597400', '11937618497492700', '11937617453865600', '11937628702666100', '11937657296116800', '11937695123970400', '11937706697323100', '11937723412008900', '11937809052727500', '11937823166473503', '11937919597108800', '11937980488256700', '11937979524904200', '11937992469023500', '11937992435913700', '11937998368147500', '11938007959195800', '11938019540770100', '11938026011362400', '11938030235112800', '11938049516191200', '11938056947593600', '11938057430693500', '11938059390803900', '11938068043700000', '11938085425707600', '11938123719357600', '11938132099192900', '11938163609714600', '11938172799345500', '11938180287114700', '11938198010716402', '11938203117701900', '11938242298110000', '11938249433088000', '11938252460987500', '11938254600672600', '11938261599228600', '11938268247773300', '11938273018773500', '11938282926665000', '11938294510493600', '11938296329668000', '11938306204213201', '11938315800660000', '11938319242840000', '11938319680825802', '11938330497113300', '11938330731776700', '11938335107841200', '11938344764280600', '11938342412942700', '11938349535995100', '11938356638151200', '11938357013597400', '11938365870133400', '11938384195208301', '11938391524294500', '11938400704420600', '11938407871014701', '11938411984483100', '11938412762025804', '11938416076741400', '11938433668637200', '11938436146491700', '11938437456667100', '11938443835558000', '11938446404812200', '11938450170285600', '11938448996100800', '11938460880444600', '11938463524436201', '11938468493672300', '11938470449013800', '11938473443354200', '11938473390813200', '11938487044005300', '21938493042980100', '11938513185120800', '11938518878527300', '11938522396304500', '11938524867001800', '11938525731221500', '11938529833241102', '11938563018915600', '11938572874026600', '11938601241751200', '11938710216155100', '11938817724737900', '11938844756861600', '11938872563042500', '11938874918617400', '11938880855045100', '11938894900724501', '11938895175183700', '11938925118366200', '11938943001702900', '11938950327054200', '11938958328368300', '11938978577830100', '11938994698521103', '11938999734791000', '11939000243900400', '11939022948921900', '11939026840494800', '11939030216634100', '11939031185740000', '11939042949505801', '11939044654404000', '11939053291505300', '11939056693768900', '11939066908781700', '11939071065900400', '11939081953668900', '11939085487872000', '11939088167350001', '11939119689690100', '11939122125314500', '11939130253674000', '11939134393073500', '11939135510676300', '11939139087112400', '11939139087112402', '11939138346505400', '11929318330125501', '11939145844240900', '11939146292944402', '11939161829410300', '11939164399654502', '11939167053412600', '11939163369555900', '11939191754568001', '11939193982567200', '11939196373705000', '11939214422370302', '11939233234443500', '11939245848881501', '11939249929074900', '11939248211835800', '11939259470018900', '11939263982584100', '11939271603166600', '11939275641883500', '11939298168520300', '11939300347463701', '11939302329536002', '11939307824547500', '11939309822898200', '11939310826994000', '11939317407600100', '11939323204814000', '11939324097893102', '11939327319007600', '11939328372404000', '11939331854246300', '11939337767218900', '11939334882781600', '11939334114034100', '11939339770047700', '11939340400744500', '11939340832256700', '11939343710705500', '11939343677067301', '11939344299243200', '11939352156206300', '11939357838591600', '11939360513701500', '11939363848372900', '11929049039310101', '11939371558047900', '11939375869663500', '11939382045635500', '11939388016214500', '11939394386241110', '11929888966578601', '11927859096196201', '11924688276408901', '11935514256158302', '11918410020270201', '11930445105821701', '11935458425897401', '11914721999362001', '11917690354864901', '11935623279707401', '11925475903626001', '11927788017743702', '11948683949690200', '11948704380935900', '11948877081501200', '11948912311348400', '11948913056493400', '11949126656806900', '11949386381427800', '11949399935477200', '11950058808402300', '11950473005877000', '21951011351575200', '11951415576585800', '11952004695290300', '11952010505677500', '11952244557311300', '11952380609435300', '11952747432315300', '11952800436966400', '11952878166047701', '11952894537017300', '11952995556045200', '11953093259671600', '21953097781177300', '11953120428843000', '11953167510324700', '11953340140017000', '11953470233010800', '11953514415642500', '11953692031004200', '11953730925446700', '11953732559630300', '11953740516412501', '11953744591216500', '11953752963976800', '11953797785875700', '11953798000064700', '11953826636697900', '11953831811836500', '11953927834557100', '11953988742975200', '11954007748092500', '11954061456007000', '11954072228581400', '11954385480043102', '11954452223778500', '11954486793167200', '11954493629832200', '11954494534725200', '11954505874108900', '11954521248112800', '11954545132328100', '11954555499003700', '21954572243930402', '11954574012446300', '11954575079092100', '11954587145455300', '11954594352142700', '11954604388552100', '11954608007718900', '11954614613570400', '11954614625275600', '11954634239925500', '11954655012607800', '11954694327106900', '11954699571655400', '11954700889765700', '11954722454296800', '11954723486734800', '11954728481805100', '11954735580196500', '11954758119823202', '11954760811624300', '11954769961342802', '11954778808786400', '11954795046335000', '21954800721713600', '11954800012388200', '11932353209093201', '11954807401706500', '11932291253402202', '11939180239457901', '11939233234443501', '11935742585530400', '11933805858101800', '11936695134365200', '11937493929703400', '11937556182532300', '11938460053038300', '11916747866702000', '11919852439127700', '11919860169700600', '11919878362563700', '11931392614095500', '11932424166543700', '11933219298251900', '11934599759077800', '11934859635351500', '11934900015558600', '11934929904096200', '11934967202660100', '11935413793012600', '11935431884072901', '11935436860376803', '11935443598756700', '11935449862138700', '11935474753588502', '11935584106320300', '11935599698395501', '11935624283187800', '11936363605772400', '11936456552360700', '11936622977236100', '11936632988555300', '11936648302356600', '11937211283054002', '11937214171217900', '11937224563675800', '11937275016864700', '11937307991121600', '11937308687071300', '11937346200048500', '11937379419012600', '11937533547465400', '11937562842145800', '11937631338886100', '11937641931591200', '11937769020732302', '11937959612402000', '11937966217302900', '11938036051828100', '11938071976965300', '11938091089198200', '11938190522312900', '11938269136718100', '11938486248673700', '11938626708116900', '11938984167035800', '11939219615155603');
			if (in_array($order->orderItemId, $incentinve_rebat_13))
				$comm_rate['basic_rate'] = 13;

			$incentinve_rebat_14 = array('11934115386912200');
			if (in_array($order->orderItemId, $incentinve_rebat_14))
				$comm_rate['basic_rate'] = 14;

			$incentinve_rebat_14_5 = array('12051273576368300', '12067024575433200');
			if (in_array($order->orderItemId, $incentinve_rebat_14_5))
				$comm_rate['basic_rate'] = 14.5;

			$incentinve_rebat_15 = array('11944019439313300', '11946969130557700', '21961296918164500', '12007095540006700', '12007268105507000', '12007582063341300', '12008921933104500', '12021219005683100', '12034117511886800', '12034164203701300', '12034287662602500', '12038263555233800', '12043786275204600', '12134609006015400', '12140125446605201', '12143195469867600', '12156046776912700', '12172186617991500', '12178411132558700', '12180105203867000', '12190386287373900', '11949494145240000', '11949629465251900', '11954893306156800', '11980366683856500', '11980694678593501', '21988525208356600', '11989932340475500', '11990010807768700', '11991523933786100', '11992991047882900', '21993074659707300', '12004721610483700', '12006659214933900', '12007062651864800', '12007511448714200', '12008353808043100', '12038470087042400', '12038639030364900', '12036856345562901', '12066802005847100', '22125640513818400', '12131608373971700', '12131812447643900', '12131825178130200', '22131905451340700', '12134319756587000', '12140213919311800', '22152729263810901', '12156228495093100', '22169128877710100', '12169155054150600', '12170849177465700', '12178579900730400', '12180438404512300', '12189600249702000', '12189787255901300', '12203386578894700', '12203622853626800', '22204287785133000', '22205872856168200', '12206787490883700', '12207131880838000', '12207703082905100', '11947103311307600', '11949567874761400', '11957087204137900', '11961718330020900', '11968574164487100', '11968612685394500', '11989821839301000', '11990259433895000', '12004926307327700', '22103916945686800', '12115431564891200', '12125972866567900', '12131691276617900', '12131873799600900', '22134219408981500', '12152406869236900', '12203190040806200', '12203261446398800', '12203343886108400', '12204125880256800', '12204419016423100', '12205241619953100', '12206140561703600', '12206797031996000', '22208492947963600', '22210341180433400');
			if (in_array($order->orderItemId, $incentinve_rebat_15))
				$comm_rate['basic_rate'] = 15;

			$incentinve_rebat_16 = array('11914653135602400', '11914659672984200', '11914661820525700');
			if (in_array($order->orderItemId, $incentinve_rebat_16))
				$comm_rate['basic_rate'] = 16;

			if (isset($_GET['test']) && $_GET['test'] == "1") {
				echo '<pre>';
				var_dump($comm_rate);
				var_dump($order->commissionRate);
			}

			$order_item_value = ($order->totalPrice / $order->quantity);
			$commission_fees = ((($order_item_value * $order->quantity) * $comm_rate['basic_rate']) / 100) * -1;
			if ($order->is_flipkartPlus) {
				$commission_fees = ((($order->totalPrice) * $comm_rate['basic_rate']) / 100) * -1;
				$order_item_value = ($order->totalPrice) / $order->quantity;
				$gross_price = $order->totalPrice;
			}
			if ($order->order_type == "FBF") {
				$commission_fees = ((($order_item_value * $comm_rate['basic_rate']) / 100) * $order->quantity) * -1;
				// $gross_price = $order_item_value;
			}
			$shopsyMarketingFee = 0;
			if ($this->is_shopsy) {
				$shopsyMarketingFee = $order->shopsySellingPrice - $order->customerPrice;
				$shopsyMarketingFee = (float)number_format((($shopsyMarketingFee / ($this->service_gst + 100)) * 100), 2, '.', '') * -1;
				$gross_price = $order->shopsySellingPrice + $order->flipkartDiscount;
			}

			if (isset($_GET['test']) && $_GET['test'] == "1") {
				var_dump($order->order_type);
				var_dump($order->is_flipkartPlus);
				var_dump($order_item_value);
				var_dump($gross_price);
				var_dump($commission_fees);
			}

			$incentives = 0;
			if (isset($comm_rate['incentive']) && $comm_rate['incentive'] != (int)0 && /*!$order->replacementOrder &&*/ !$this->has_returns($order->orderItemId))
				$incentives = (($order->sellingPrice * $comm_rate['incentive']) / 100)
					/**$order->quantity*/
				;
			if ($incentives > $commission_fees * -1)
				$incentives = $commission_fees * -1;

			$this->product_gst = $this->get_gst_rate($order->hsn, $order->invoiceAmount / $order->quantity);
			$tcs = 0;
			if ($order->invoiceDate >= '2018-10-01 00:00:00')
				$tcs = (float)number_format((($gross_price - ($gross_price * ($this->product_gst / ($this->product_gst + 100)))) * 0.01), 2, '.', '') * -1;

			$tds = 0;
			if ($order->invoiceDate >= '2020-10-01 00:00:00' && $order->invoiceDate < '2021-04-01 00:00:00') {
				$tds = (float)number_format((($gross_price - ($gross_price * ($this->product_gst / ($this->product_gst + 100)))) * 0.0075), 2, '.', '') * -1;
			} else if ($order->invoiceDate >= '2021-04-01 00:00:00') {
				$tds = (float)number_format((($gross_price - ($gross_price * ($this->product_gst / ($this->product_gst + 100)))) * 0.01), 2, '.', '') * -1;
			}

			$commission_fee_waiver = 0;
			$shipping_fee_waiver = 0;
			if ($order->is_flipkartPlus && $this->order_date < "2019-01-01") {
				$shipping_fee_waiver = $this->get_shipping_fees($order_type, $shipping_zone, $shipping_slab, $order->orderDate, false, true);
				if ($shipping_fee_waiver != 40)
					$commission_fee_waiver = 40 - $shipping_fee_waiver;
			}

			$shipping_fees = ($this->get_shipping_fees($order_type, $shipping_zone, $shipping_slab)) * -1;
			$db->where('shipmentId', $order->shipmentId);
			$db->where('status', 'SHIPPED');
			$shipments = $db->getValue(TBL_FK_ORDERS, 'sum(quantity)');
			if ($shipments > 1)
				$shipping_fees = (($shipping_fees / $shipments) * $order->quantity);

			if ($order->order_type == "FBF")
				$shipping_fees = ($shipping_fees * $order->quantity);

			if (isset($_GET['test']) && $_GET['test'] == "1")
				var_dump($shipping_fees);

			$payout = array(
				'commission_rate' => $comm_rate['basic_rate'],
				'commission_fees' => $commission_fees,
				'commission_incentive' => $incentives,
				'fixed_fees' => $order->replacementOrder ? 0 : (($this->get_fixed_fees($order_type, $order_item_value) * $order->quantity) * -1),
				'collection_fees' => $order->replacementOrder ? 0 : ($this->get_collection_fees(($order->customerPrice / $order->quantity), $order->paymentType, $order->replacementOrder) * $order->quantity) * -1,
				'pick_and_pack_fee' => 0,
				'shipping_zone' => $shipping_zone,
				'shipping_slab' => $shipping_slab,
				'shipping_fee' => $shipping_fees,
				'refund_amount' => $order->refundAmount,
				'reverse_shipping_fee' => $order->reverseShippingFee,
				'shopsy_marketing_fee' => $shopsyMarketingFee,
				'shipping_fee_waiver' => $shipping_fee_waiver,
				'commission_fee_waiver' => $commission_fee_waiver,
				'refund_discount_amount' => $order->discountRefundAmount,
				'other_fees' => $order->otherFees,
				'tcs' => $tcs,
				'tds' => $tds,
			);

			if ($order_type == "FBF") {
				// $payout['fixed_fees'] = ($this->get_fixed_fees($order_type, $order_item_value) * $order->quantity) * -1;
				$payout['pick_and_pack_fee'] = ($this->get_pick_and_pack_fees($order_type, $shipping_slab) * $order->quantity) * -1;
			}

			// if (isset($_GET['test']) && $_GET['test'] == "1"){
			// 	echo 'tdscheck<br />';
			// 	var_dump($payout);
			// 	echo '</pre>';
			// }
			// echo '<pre>';
			// print_r($payout);
			// echo '</pre>';

			return $payout;
		}

		return false;
	}

	function get_brand_rate_card($gross_price, $category, $brand)
	{
		global $db;

		$date = date('Y-m-d', strtotime($this->order_date));
		$db->where('fee_name', 'platformFee');
		$db->where('fee_marketplace', ($this->is_shopsy ? 'shopsy' : 'flipkart'));
		$db->where('fee_tier', $this->tier);
		$db->where('fee_category', $category);
		$db->where('fee_brand', $brand);
		$db->where('(fee_attribute_min <= ? AND fee_attribute_max >= ? ) ', array($gross_price, $gross_price));
		$db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
		$rate_card = $db->getOne(TBL_MP_FEES);
		if ($rate_card)
			return $rate_card['fee_value'];
		else
			return;
	}

	function get_seller_tier()
	{

		$tiers = json_decode($this->account->account_tier);
		$date = date('Y-m-d', strtotime($this->order_date));
		$return = NULL;
		foreach ($tiers as $tier) {
			if (($date >= $tier->startDate) && ($date <= $tier->endDate)) {
				$return = $tier->tier;
			}
		}

		if ($return == NULL) {
			$tier = $tiers[0];
			$return = $tier->tier;
		}

		return $return;
	}

	function get_payment_cycle()
	{
		global $db;

		$date = date('Y-m-d', strtotime($this->order_date));
		$db->where('fee_name', 'paymentCycle');
		$db->where('fee_marketplace', ($this->is_shopsy ? 'shopsy' : 'flipkart'));
		$db->where('fee_tier', $this->tier);
		$db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
		$payment_cycle = $db->getValue(TBL_MP_FEES, 'fee_value');

		return $payment_cycle;
	}

	function get_platform_fees($gross_price, $category, $brand, $listingId = "", $is_flipkartPlus = false)
	{
		global $db;

		$category = str_replace(' ', '_', strtolower($category));
		$date = date('Y-m-d', strtotime($this->order_date));
		$db->where('fee_name', 'platformFee');
		$db->where('fee_marketplace', ($this->is_shopsy ? 'shopsy' : 'flipkart'));
		$db->where('fee_tier', $this->tier);
		$db->where('fee_category', $category);
		$db->where('(fee_attribute_min < ? AND fee_attribute_max >= ? ) ', array($gross_price, $gross_price));
		$db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
		$rate_card = $db->getOne(TBL_MP_FEES);

		if (isset($category) && empty($category))
			return;

		$platform_fee = $rate_card['fee_value'];
		$fsn = substr($listingId, 3, 16);
		$non_skmei_curren_brand_fsn = array("WATEYTA4CEUNHHGH", "WATFMJ7REBHZWZ5U", "WATFN3GWCCAHNR4Z", "WATFMGMAPNAUQZUK", "WATFMGMAGCF5QW32", "WATFN2NGJMH7U6ZJ");
		if (!$this->is_shopsy)
			$brand_rate = $this->get_brand_rate_card($gross_price, $category, $brand);
		if (!is_null($brand_rate) && !in_array($fsn, $non_skmei_curren_brand_fsn))
			$platform_fee = $brand_rate;

		$incentive = array();
		if (!empty($listingId)) {
			if (($this->order_date >= '2018-12-01' && $this->order_date <= '2018-12-31') && $brand == "Sylvi" && $category == "watches" && $this->account->account_name == "ShreehanEnterprise") {
				$incentive['rate'] = 5; // 5% incentive
				$incentive['offerId'] = "nb:mp:10d50c3e29";
			} else {
				$incentive = $this->get_incentive_rate($listingId, $is_flipkartPlus);
			}
		}

		if (($this->order_date >= '2017-11-16' && $this->order_date <= '2018-08-31') && ($brand == "Style Feathers" || $brand == "Sylvi") && $category == "watches" && $this->account->account_name != 'ShivanshEntp')
			$platform_fee = 10; // Strict 10%

		if (($this->order_date >= '2017-11-16' && $this->order_date <= '2019-11-30') && ($brand == "Style Feathers" || $brand == "Sylvi") && $category == "watches" && $this->account->account_name != 'ShivanshEntp')
			$platform_fee = 11; // Strict 11%

		return array('basic_rate' => (float)$platform_fee, 'incentive' => (float)$incentive['rate'], 'offerId' => $incentive['offerId'], 'promoEndDate' => $incentive['promoEndDate']);
	}

	function get_incentive_rate($lid, $is_flipkartPlus = false, $brand = "", $category = "watches")
	{
		global $db;

		$incentive['offerId'] = "";
		$incentive['rate'] = 0;
		$db->where('listingId', $lid);
		if ($is_flipkartPlus) {
			$db->where('(promoStartDate <= ? AND promoOptInDate <= ?)', array(date('Y-m-d H:i:s', strtotime($this->order_date . ' +4hours')), date('Y-m-d H:i:s', strtotime($this->order_date . ' +4hours'))));
			$db->where('promoEndDate', date('Y-m-d H:i:s', strtotime($this->order_date)), '>=');
		} else {
			$db->where('(promoStartDate <= ? AND promoOptInDate <= ?)', array($this->order_date, $this->order_date));
			$db->where('promoEndDate', $this->order_date, '>=');
		}
		$db->where('promoType', 'MP_INC');
		$mp_inc_promo = $db->objectBuilder()->getOne(TBL_FK_PROMOTIONS, 'promoMpInc, offerId, promoEndDate');
		$incentive['rate'] = 0;
		$incentive['offerId'] = "";
		$incentive['promoEndDate'] = "";

		if ($mp_inc_promo) {
			$offerIds = array();
			$incentive['rate'] = (int)$mp_inc_promo->promoMpInc;
			$incentive['offerId'] = $mp_inc_promo->offerId;
			$incentive['promoEndDate'] = $mp_inc_promo->promoEndDate;
		}

		if (($this->order_date >= '2018-12-01' && $this->order_date <= '2018-12-31') && $brand == "Sylvi" && $category == "watches" && $this->account->account_name == "ShreehanEnterprise") {
			$incentive['rate'] = 5; // 5% incentive
			$incentive['offerId'] = "nb:mp:10d50c3e29";
		}

		return $incentive;
	}

	function get_fixed_fees($type, $gross_price)
	{
		global $db;

		$date = date('Y-m-d', strtotime($this->order_date));
		if ($date <= '2020-12-31')
			$type = 'all';
		$db->where('fee_name', 'closingFee');
		$db->where('fee_marketplace', ($this->is_shopsy ? 'shopsy' : 'flipkart'));
		$db->where('fee_tier', $this->tier);
		$db->where('fee_fulfilmentType', $type);
		$db->where('(fee_attribute_min <= ? AND fee_attribute_max >= ? ) ', array($gross_price, $gross_price));
		$db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
		$fixed_fees = $db->getValue(TBL_MP_FEES, 'fee_value');

		return $fixed_fees;
	}

	function get_collection_fees($gross_price, $payment_type = "cod", $is_replacement_order = false)
	{
		global $db;

		$payment_type = $payment_type == 'cod' ? 'postpaid' : 'prepaid';
		$date = date('Y-m-d', strtotime($this->order_date));
		if ($date <= '2020-12-31')
			$type = 'all';
		$db->where('fee_name', 'collectionFee');
		$db->where('fee_marketplace', ($this->is_shopsy ? 'shopsy' : 'flipkart'));
		$db->where('fee_tier', $this->tier);
		$db->where('fee_column', $payment_type);
		$db->where('(fee_attribute_min <= ? AND fee_attribute_max >= ? ) ', array($gross_price, $gross_price));
		$db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
		$collection_fees = $db->getOne(TBL_MP_FEES, 'fee_value, fee_type');

		$definer = $collection_fees['fee_type'];
		if ($definer == 'fixed')
			$collection_fee = $collection_fees['fee_value'];
		else
			$collection_fee = number_format($gross_price * ($collection_fees['fee_value'] / 100), '2', '.', '');
		return (float)$collection_fee;
	}

	function get_pick_and_pack_fees($type, $weightSlab)
	{
		global $db;

		if (empty($weightSlab))
			$weightSlab = '0.0-1.0'; // Will set it to maximum if no details found

		$maxWeightSlab = number_format(ceil(explode("-", $weightSlab, 2)[1]), 1, '.', ',');

		if (!empty($date))
			$this->order_date = $date;

		$date = date('Y-m-d', strtotime($this->order_date));
		$db->where('fee_name', 'pickAndPackFee');
		$db->where('fee_marketplace', ($this->is_shopsy ? 'shopsy' : 'flipkart'));
		$db->where('fee_tier', $this->tier);
		$db->where('fee_fulfilmentType', $type);
		$db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
		$db->orderBy('fee_attribute_min', 'ASC');
		$pp_fees = $db->get(TBL_MP_FEES, NULL, 'fee_attribute_min, fee_attribute_max, fee_type, fee_value, fee_constant');
		$slab_rate_card = array();
		foreach ($pp_fees as $pp_fee) {
			if ($pp_fee['fee_constant'] != 0) {
				$slab_weights = range($pp_fee['fee_attribute_min'], $pp_fee['fee_attribute_max'], $pp_fee['fee_constant']);
				foreach ($slab_weights as $slab_weight) {
					$slab_weight = number_format($slab_weight, 1, '.', ',');
					if (!isset($slab_rate_card[$slab_weight]) && $slab_weight != 0)
						$slab_rate_card[$slab_weight] = $pp_fee['fee_value'];
				}
			} else {
				$slab_rate_card[number_format($pp_fee['fee_attribute_max'], 1, '.', '')] = $pp_fee['fee_value'];
			}
		}

		$fees = 0;
		$slab_count = 0;
		foreach ($slab_rate_card as $slab_weight => $slab_rate) {
			$fees += $slab_rate;
			$slab_count++;

			if ($slab_weight == $maxWeightSlab) {
				if ($slab_count > 1 && $this->order_date < "2020-07-01")
					$fees -= (float)$pp_fees[0]['fee_value'];
				break;
			}
		}

		return $fees;
	}

	function get_shipping_zone($from_code, $to_code)
	{
		$shipping_zones = json_decode(get_option('fk_shipping_zones'));
		$from_zone = "";
		$to_zone = "";
		foreach ($shipping_zones as $shipping_zone => $zones) {
			if (empty($from_zone)) {
				if (in_array($from_code, $zones))
					$from_zone = $shipping_zone;
			}

			if (empty($to_zone)) {
				if (in_array($to_code, $zones))
					$to_zone = $shipping_zone;
			}
		}

		$shippingZone = "national";
		if ($from_zone == $to_zone)
			$shippingZone = 'zonal';

		return $shippingZone;
	}

	function get_shipping_fees($type, $shippingZone = "", $shippingSlab = "", $date = "", $is_reverse = false, $is_waiver = false)
	{
		global $db;

		if (empty($shippingZone))
			$shippingZone = 'national';
		if (empty($shippingSlab))
			$shippingSlab = '0.0-0.5'; // Will set it to maximum if no details found

		$maxShippingSlab = number_format(explode("-", $shippingSlab, 2)[1], 1, '.', ',');

		$shippingType = 'shippingFee';
		if ($is_reverse)
			$shippingType = 'reverseShippingFee';

		if (!empty($date))
			$this->order_date = $date;

		$date = date('Y-m-d', strtotime($this->order_date));
		$db->where('fee_name', $shippingType);
		$db->where('fee_marketplace', ($this->is_shopsy ? 'shopsy' : 'flipkart'));
		$db->where('fee_tier', $this->tier);
		$db->where('fee_fulfilmentType', $type);
		$db->where('fee_column', $shippingZone);
		$db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
		$db->orderBy('fee_attribute_min', 'ASC');
		$shipping_fees = $db->get(TBL_MP_FEES, NULL, 'fee_attribute_min, fee_attribute_max, fee_type, fee_value, fee_constant');
		$slab_rate_card = array();
		foreach ($shipping_fees as $shipping_slab) {
			if ($shipping_slab['fee_constant'] != 0) {
				$slab_weights = range($shipping_slab['fee_attribute_min'], $shipping_slab['fee_attribute_max'], $shipping_slab['fee_constant']);
				foreach ($slab_weights as $slab_weight) {
					$slab_weight = number_format($slab_weight, 1, '.', ',');
					if (!isset($slab_rate_card[$slab_weight]) && $slab_weight != 0)
						$slab_rate_card[$slab_weight] = $shipping_slab['fee_value'];
				}
			} else {
				$slab_rate_card[number_format($shipping_slab['fee_attribute_max'], 1, '.', '')] = $shipping_slab['fee_value'];
			}
		}

		$fees = 0;
		$slab_count = 0;
		foreach ($slab_rate_card as $slab_weight => $slab_rate) {
			$fees += $slab_rate;
			$slab_count++;

			if ($slab_weight == $maxShippingSlab) {
				if ($slab_count > 1 && $this->order_date < "2020-07-01")
					$fees -= (float)$shipping_fees[0]['fee_value'];
				break;
			}
		}

		return $fees;
	}

	function get_due_date($orderItemId, $isIncentive = false)
	{
		global $db;

		$db->where('orderItemId', $orderItemId);
		$db->orderBy('paymentDate', 'DESC');
		$paymentDate = $db->getValue(TBL_FK_PAYMENTS, 'paymentDate');

		$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
		$db->where('o.orderItemId', $orderItemId);
		$date = $db->getOne(TBL_FK_ORDERS . ' o', 'orderDate, shippedDate, r_createdDate, r_deliveredDate, r_shipmentStatus');
		$orderDate = $date['orderDate'];
		$shippedDate = date('Y-m-d', strtotime($date['shippedDate']));

		$max_beach_date = date('Y-m-d', strtotime($orderDate . ' + 59 days'));
		if (!is_null($date['r_createdDate']) && is_null($date['r_deliveredDate']) && $date['r_shipmentStatus'] != 'return_cancelled') {
			return date('Y-m-d', strtotime($date['r_createdDate'] . ' + 59 days'));
		} else if (!is_null($date['r_createdDate']) && !is_null($date['r_deliveredDate']) && $date['r_shipmentStatus'] != 'return_cancelled') {
			return date('Y-m-d', strtotime($date['r_deliveredDate'] . ' + 29 days'));
		} else if ($max_beach_date < date('Y-m-d', strtotime($paymentDate . ' + 29 days'))) {
			return $max_beach_date;
		}

		if ($paymentDate) {
			if ($isIncentive) {
				return date('Y-m-d', strtotime(date('Y-m-t', strtotime($orderDate)) . ' + 45 days'));
			}
			return date('Y-m-d', strtotime($paymentDate . ' + 29 days'));
		} else {
			$this->order_date = date('Y-m-d H:i:s', strtotime($orderDate));
			$this->tier = $this->get_seller_tier(); // returns default gold.
			if ($isIncentive) {
				return date('Y-m-d', strtotime(date('Y-m-t', strtotime($orderDate)) . ' + 45 days'));
			}
			$start = new DateTime($shippedDate);
			$end = new DateTime(date('Y-m-d', strtotime($shippedDate . ' +' . $this->get_payment_cycle() . ' days')));
			$days = $start->diff($end, true)->days;
			$days += intval($days / 7) + ($start->format('N') + $days % 7 >= 7);

			$due_date = date('Y-m-d', strtotime($shippedDate . ' +' . $days . ' days'));
			if (!date('w', strtotime($due_date)))
				$due_date = date('Y-m-d', strtotime($due_date . ' +1 day'));

			return $due_date;
		}
	}

	function get_gst_rate($hsnCode, $invoiceAmount = 0)
	{
		global $db;

		$db->where('hsn_code', $hsnCode);
		$db->where('hsn_rate_start_date', $this->order_date, '<=');
		$db->where('hsn_rate_end_date', $this->order_date, '>=');
		$gst_rates = $db->get(TBL_HSN_RATE, null, 'hsn_rate, hsn_rate_band');
		$rate = 18;
		if ($gst_rates) {
			if ($db->count > 1) {
				foreach ($gst_rates as $gst_rate) {
					$min_amount = 0;
					$max_amount = 9999999999;
					if ($gst_rate['hsn_rate_band']) {
						@list($min_amount, $max_amount) = explode('-', $gst_rate['hsn_rate_band']);
						if ($max_amount == NULL)
							$max_amount = 9999999999;
					}

					if ((int)$invoiceAmount >= (int)$min_amount && (int)$invoiceAmount <= (int)$max_amount) {
						$rate = $gst_rate['hsn_rate'];
						break;
					}
				}
			} else {
				$rate = $gst_rates[0]['hsn_rate'];
			}
		}

		return $rate;
	}

	function has_returns($orderItemId)
	{
		global $db;

		$is_return = false;
		$db->where('orderItemId', $orderItemId);
		$returns = $db->get(TBL_FK_RETURNS, NULL, 'r_shipmentStatus');
		if ($returns) {
			$shipmentStatus = array('return_completed', 'in_transit', 'delivered', 'start', 'out_for_delivery', 'return_claimed_undelivered', 'return_claimed', 'return_claimed_missing', 'return_received');
			if (count(array_intersect(array_column($returns, 'r_shipmentStatus'), $shipmentStatus)) > 0) {
				$is_return = true;
			}
		}

		return $is_return;
	}

	function calculate_actual_payout($orderItemId)
	{
		global $db;

		$db->where('orderItemId', $orderItemId);
		$order = $db->objectBuilder()->getOne(TBL_FK_ORDERS);
		if ($order->status == "CANCELLED")
			return 0;

		$marketplaceFee = ((float)$order->commissionFee) + ((float)$order->collectionFee) + ((float)$order->pickPackFee) + ((float)$order->fixedFee) + ((float)$order->forwardShippingFee) + ((float)$order->reverseShippingFee + (float)$order->shopsyMarketingFee + (float)$order->otherFees) + (float)$order->shippingFeeWaiver + (float)$order->commissionFeeWaiver;
		$taxes = (float)number_format((($marketplaceFee * $this->service_gst) / 100), 2, '.', '');
		// var_dump($taxes);

		if (!is_null($order->shipmentId)) {
			// V3 updated the response to total order value instead of each item value - HARD FIX
			// $order->totalPrice = ($order->totalPrice-$order->flipkartDiscount)/$order->quantity;
			$order->flipkartDiscount = $order->flipkartDiscount / $order->quantity;
		}

		// $order->flipkartDiscount = (($order->is_flipkartPlus) ? 0 : (float)($order->flipkartDiscount));

		$sale_amount = $order->totalPrice - ($order->flipkartDiscount * $order->quantity);
		if ($order->is_shopsy)
			$sale_amount = $order->shopsySellingPrice;

		$payout = (float)($sale_amount) + ($marketplaceFee + $taxes + $order->tcs + $order->tds + (float)$order->refundAmount + (float)$order->protectionFund) + (($order->flipkartDiscount * $order->quantity) + ($order->discountRefundAmount) + $order->commissionIncentive);

		return $payout;
	}

	function calculate_net_settlement($orderItemId)
	{
		global $db;

		$db->where('orderItemId', $orderItemId);
		$settlement = $db->getValue(TBL_FK_PAYMENTS, 'sum(paymentValue)');
		if (is_null($settlement))
			$settlement = 0;

		return $settlement;
	}

	// 
	function get_difference_details($orderItemId)
	{
		global $db;

		// error_reporting(E_ALL);
		// ini_set('display_errors', '1');
		// echo '<pre>';

		// $orderItemId = '1961269903795403'; //1965582174156300
		$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
		$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "LEFT");
		$db->join(TBL_FK_INCIDENTS . " fi", "fi.referenceId LIKE CONCAT('%', o.orderItemId , '%')", "LEFT");
		$db->joinWhere(TBL_FK_INCIDENTS . " fi", "fi.issueType", "Payments");
		$db->joinWhere(TBL_FK_INCIDENTS . " fi", "fi.incidentStatus", "active");
		// $db->join(TBL_FK_INCIDENTS.' fi', 'JSON_CONTAINS_PATH(o.orderItemId, CAST(fi.referenceId as CHAR), "all", "$" )', 'LEFT');
		$db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
		$db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id", $this->account->account_id);
		$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
		$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
		$db->join(TBL_PRODUCTS_BRAND . " cb", "cb.brandid=pc.brand", "LEFT");
		$db->join(TBL_PRODUCTS_BRAND . " pb", "pb.brandid=pm.brand", "LEFT");

		$db->where('o.orderItemId', $orderItemId);
		$db->where('o.account_id', $this->account->account_id);
		$orders = $db->objectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.shipmentId, o.orderItemId, o.orderDate, o.status, o.fk_status, o.order_type, o.title, o.fsn, o.sku, o.listingId, o.quantity, o.sellingPrice, o.shopsySellingPrice, o.totalPrice, o.invoiceNumber, o.invoiceDate, o.flipkartDiscount, o.paymentType, o.dispatchByDate, o.shippedDate, o.shippingZone, o.shippingSlab, o.commissionRate, o.commissionFee, o.commissionIncentive, o.collectionFee, o.pickPackFee, o.fixedFee, o.forwardShippingFee, o.shippingFeeWaiver, o.commissionFeeWaiver, o.refundAmount, o.discountRefundAmount, o.shopsyMarketingFee, o.reverseShippingFee, o.is_flipkartPlus, o.is_shopsy, o.protectionFund, o.otherFees, o.tcs, o.tds, o.settlementNotes, o.settlementStatus, o.replacementOrder, r.returnId, r.r_quantity, r.r_source, r.r_shipmentStatus, r.r_reason, r.r_action, r.r_deliveredDate, r.r_receivedDate, r.r_completionDate, r.r_cancellationDate, r.r_approvedSPFId, c.claimID, c.claimDate, c.claimProductCondition, c.claimStatus, c.claimReimbursmentAmount, c.lastUpdateAt, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as productImage, pb.brandName, fi.incidentId');
		// if(isset($_GET['test']) && $_GET['test'] == "1"){
		// 	echo '<pre>';
		// 	echo $db->getLastQuery();
		// 	var_dump($orders);
		// }

		$order = $orders[0];
		if ($order->replacementOrder) {
			$db->join(TBL_FK_ORDERS . ' o', 'o.orderItemId=r.orderItemId');
			$db->where('r_replacementOrderItemId', $orderItemId);
			$this->order_date = $db->getValue(TBL_FK_RETURNS . ' r', 'orderDate');
		} else {
			$this->order_date = date('Y-m-d H:i:s', strtotime($order->orderDate));
		}
		$this->tier = $this->get_seller_tier(); // returns default gold.
		$incentiveDetails = $this->get_incentive_rate($order->listingId, $order->is_flipkartPlus, $order->brandName);
		// var_dump($incentiveDetails);
		$order->isMpInc = isset($incentiveDetails['offerId']) ? true : false;
		$order->offerId = $incentiveDetails['offerId'];
		$order->offerMpIncRate = $incentiveDetails['rate'];

		$marketplace_fees = (float)$order->commissionFee + (float)$order->collectionFee + (float)$order->pickPackFee + (float)$order->fixedFee + (float)$order->forwardShippingFee + (float)$order->reverseShippingFee + (float)$order->shopsyMarketingFee;
		$taxable_marketplace_fees = $marketplace_fees + (float)$order->shippingFeeWaiver + (float)$order->commissionFeeWaiver + (float)$order->otherFees;
		$fees_waiver = (float)$order->shippingFeeWaiver + (float)$order->commissionFeeWaiver;
		$mp_fees_gst = (float)number_format(($taxable_marketplace_fees * $this->service_gst) / 100, 2, '.', '');

		if (!is_null($order->shipmentId) && $order->quantity > 0) {
			// V3 updated the response to total order value instead of each item value - HARD FIX
			// $order->totalPrice = ($order->totalPrice-$order->flipkartDiscount)/$order->quantity;
			// $order->flipkartDiscount = $order->flipkartDiscount/$order->quantity;
		}
		$isIncentive = ((float)$order->commissionIncentive > 0) ? true : false;

		$sale_amount = (float)($order->totalPrice - $order->flipkartDiscount);
		if ($order->status == "CANCELLED")
			$sale_amount = 0;
		// if ($order->is_flipkartPlus)
		// 	$sale_amount = (float)($order->totalPrice);
		if ($order->is_shopsy)
			$sale_amount = (float)$order->shopsySellingPrice;

		$expected_payout = array(
			'settlement_date' => '<b>Order Date: </b>' . date('d M, Y', strtotime($order->orderDate)) . ' <br />Due Date: ' . date('d M, Y', strtotime($this->get_due_date($order->orderItemId, $isIncentive))), // 8th day from shipping date
			'sale_amount' => $sale_amount,
			'flipkart_discount' => (float)($order->flipkartDiscount) + ($order->discountRefundAmount),
			'refund_amount' => (float)$order->refundAmount,
			'marketplace_fees' => $marketplace_fees,
			'commission_rate' => (float)$order->commissionRate,
			'commission_fee' => (float)$order->commissionFee,
			'collection_fee' => (float)$order->collectionFee,
			'fixed_fee' => (float)$order->fixedFee,
			'shipping_fee' => (float)$order->forwardShippingFee,
			'reverse_shipping_fee' => (float)$order->reverseShippingFee,
			'shopsy_marketing_fee' => (float)$order->shopsyMarketingFee,
			'fees_waiver' => $fees_waiver,
			'commission_fee_waiver' => (float)$order->commissionFeeWaiver,
			'shipping_fee_waiver' => (float)$order->shippingFeeWaiver,
			'taxes' => (float)$order->tcs + (float)$order->tds + $mp_fees_gst,
			'tcs' => (float)$order->tcs,
			'tds' => (float)$order->tds,
			'gst' => $mp_fees_gst,
			'commission_incentive' => (float)$order->commissionIncentive,
			'protection_fund' => (float)$order->protectionFund,
			'other_fees' => (float)$order->otherFees,
			'total' => ($order->status == "CANCELLED") ? 0 : (float)($order->totalPrice + $order->discountRefundAmount + $marketplace_fees + $order->protectionFund + $fees_waiver + $order->refundAmount + $mp_fees_gst + $order->tcs + $order->tds + $order->commissionIncentive),
			'payment_type' => $order->paymentType,
			'shipping_zone' => ($order->shippingZone == NULL && $order->status == "CANCELLED") ? "NA" : $order->shippingZone,
			'shipping_slab' => ($order->shippingSlab == NULL && $order->status == "CANCELLED") ? "NA" : $order->shippingSlab,
		);
		if ($order->order_type == "FBF")
			$expected_payout['pick_and_pack_fee'] = (float)$order->pickPackFee;

		$db->where('orderItemId', $orderItemId);
		$payments = $db->objectBuilder()->get(TBL_FK_PAYMENTS);

		$settlements = array();
		foreach ($payments as $payment) {
			$commission_incentive = 0;
			if ((float)$payment->paymentValue == (float)$payment->commission) {
				$commission_incentive = (float)$payment->commission;
				$payment->commission = 0;
			}

			$order_settlement = array(
				'settlement_date' => $payment->paymentId . ' <br />' . date('d M, Y', strtotime($payment->paymentDate)),
				'sale_amount' => (float)$payment->sale_amount,
				'flipkart_discount' => ((float)$payment->total_offer_amount + (float)$payment->my_share),
				'refund_amount' => (float)$payment->refund,
				'marketplace_fees' => (float)$payment->marketplace_fee - (float)$payment->shipping_fee_waiver - (float)$payment->commission_fee_waiver - ($payment->product_cancellation_fee + $payment->service_cancellation_fee + $payment->fee_discount) - $commission_incentive,
				'commission_rate' => number_format(($payment->commission_rate), 2, '.', ''),
				'commission_fee' => (float)$payment->commission,
				'collection_fee' => (float)$payment->collection_fee,
				'fixed_fee' => (float)$payment->fixed_fee,
				'shipping_fee' => (float)$payment->shipping_fee,
				'reverse_shipping_fee' => (float)$payment->reverse_shipping_fee,
				'shopsy_marketing_fee' => (float)$payment->shopsy_marketing_fee,
				'taxes' => (float)$payment->tax_collected_at_source + (float)$payment->tds + (float)$payment->taxes,
				'tcs' => (float)$payment->tax_collected_at_source,
				'tds' => (float)$payment->tds,
				'gst' => (float)$payment->taxes,
				'commission_incentive' => $commission_incentive,
				'protection_fund' => (float)$payment->protection_fund,
				'fees_waiver' => (float)$payment->commission_fee_waiver + (float)$payment->shipping_fee_waiver,
				'commission_fee_waiver' => (float)$payment->commission_fee_waiver,
				'shipping_fee_waiver' => (float)$payment->shipping_fee_waiver,
				'other_fees' => $payment->product_cancellation_fee + $payment->service_cancellation_fee + $payment->fee_discount,
				'total' => (float)$payment->paymentValue,
				'payment_type' => ($payment->paymentType == "postpaid" ? "cod" : strtolower($payment->paymentType)),
				'shipping_zone' => strtolower($payment->shipping_zone),
				'shipping_slab' => $payment->chargeable_wt_slab,
			);

			if ($order->order_type == "FBF")
				$order_settlement['pick_and_pack_fee'] = (float)$payment->pick_and_pack_fee;

			$settlements[] = $order_settlement;
		}

		$total_settlement = array();
		foreach ($settlements as $k => $subArray) {
			foreach ($subArray as $id => $value) {
				if ($id == 'shipping_zone' || $id == 'shipping_slab' || $id == 'settlement_date' || $id == 'payment_type')
					continue;

				if ($id == 'commission_rate' && isset($total_settlement[$id]) && $total_settlement[$id] != 0)
					continue;

				@$total_settlement[$id] += $value;
			}
		}
		$total_settlement['payment_type'] = $settlements[0]['payment_type'];
		$total_settlement['shipping_zone'] = ($settlements[0]['shipping_zone'] == "" ? (isset($settlements[1]['shipping_zone']) ? $settlements[1]['shipping_zone'] : 'national') : $settlements[0]['shipping_zone']);
		$total_settlement['shipping_slab'] = ($settlements[0]['shipping_slab'] == "" ? (isset($settlements[1]['shipping_slab']) ? $settlements[1]['shipping_slab'] : '0.0-0.5') : $settlements[0]['shipping_slab']);

		$return['orders'] = $orders;
		$return['order'] = (array)$order;
		$return['expected_payout'] = $expected_payout;
		$return['settlements'] = $settlements;
		$return['total_settlement'] = $total_settlement;
		$return['difference'] = array();
		$return['difference'] = array_diff($total_settlement, $expected_payout); // string value difference
		foreach ($expected_payout as $ep_key => $ep_value) { // float value difference with summation
			if ($ep_key == 'settlement_date' || $ep_key == 'shipping_zone' || $ep_key == 'shipping_slab' || $ep_key == 'payment_type')
				continue;

			if (($ep_value - $total_settlement[$ep_key]) != 0)
				$return['difference'][$ep_key] = number_format(($ep_value - $total_settlement[$ep_key]), 2, '.', '');

			if ($ep_key == 'commission_rate' && $ep_value != $total_settlement[$ep_key])
				$return['difference'][$ep_key] = number_format($total_settlement[$ep_key], 2, '.', '');
		}
		$return['difference']['settlement_date'] = ''; // Leave it empty for adding default difference line

		// echo '<pre>';
		// print_r($return);
		// echo '</pre>';

		return $return;
	}

	function get_settlement_details($orderItemId, $settlementStatus = 'all')
	{
		global $db;

		$db->join(TBL_FK_PAYMENTS . " p", "p.orderItemId=o.orderItemId", "LEFT");
		$db->join(TBL_FK_ACCOUNTS . ' a', "a.account_id=o.account_id", "LEFT");
		$db->where('o.dispatchAfterDate', '2017-07-01 00:00:00', '>=');
		if ($settlementStatus != 'all')
			$db->where('o.settlementStatus', $settlementStatus);
		$db->where('o.orderItemId', $orderItemId);
		$db->orderBy('o.shippedDate', 'ASC');
		$db->groupBy('o.orderItemId');
		$settlements = $db->get(TBL_FK_ORDERS . ' o', NULL, 'p.paymentId, a.account_id, a.account_name, o.orderItemId, o.orderId, o.orderDate, o.shippedDate, p.paymentDate, o.dispatchByDate, SUM(p.paymentValue) AS paymentValue');

		$orders = array();
		$payout = 0;
		foreach ($settlements as $settlement) {
			$this->order_date = date('Y-m-d H:i:s', strtotime($settlement['orderDate']));
			$this->tier = $this->get_seller_tier();
			$net_settlement = (float)number_format($this->calculate_net_settlement($settlement['orderItemId']), 2, '.', '');
			$net_payout = (float)number_format($this->calculate_actual_payout($settlement['orderItemId']), 2, '.', '');
			$difference = $net_settlement - $net_payout;
			$difference = number_format($difference, 2, '.', '');
			$shippedDate = date('Y-m-d', ($settlement['shippedDate'] == NULL ? strtotime($settlement['dispatchByDate']) : strtotime($settlement['dispatchByDate'])));

			$orders[$settlement['orderItemId']] = array(
				'account_name' => $settlement['account_name'],
				'orderItemId' => $settlement['orderItemId'],
				'orderId' => $settlement['orderId'],
				'orderDate' => date('Y-m-d', strtotime($settlement['orderDate'])),
				'shippedDate' => $shippedDate,
				'settlementDate' => date('Y-m-d', strtotime($settlement['paymentDate'])),
				'dueDate' => $this->get_due_date($settlement['orderItemId']),
				'netPayout' => $net_payout,
				'netSettlement' => $net_settlement,
				'difference' => $difference,
				'accountId' => $settlement['account_id'],
			);
		}
		return $orders;
	}

	function fetch_payout($orderItemId)
	{
		global $db;
		// echo '<pre>';
		$this->update_fk_payout($orderItemId);

		$db->where('o.orderItemId', $orderItemId);
		// $db->where('o.account_id', $this->account->account_id);
		$db->join(TBL_FK_RETURNS . " r", "r.orderItemId=o.orderItemId", "LEFT");
		$db->join(TBL_FK_CLAIMS . " c", "r.returnId=c.returnId", "LEFT");
		$db->orderBy("r.r_createdDate", "ASC");
		$orders = $db->ObjectBuilder()->get(TBL_FK_ORDERS . ' o', NULL, 'o.*, r.*, c.claimProductCondition, o.orderItemId, r.returnId');

		$details = array();
		$details['commissionIncentive'] = (float)$orders[0]->commissionIncentive;
		$details['commissionFee'] = (float)$orders[0]->commissionFee;
		$details['fixedFee'] = (float)$orders[0]->fixedFee;
		$details['refundAmount'] = 0;
		$details['discountRefundAmount'] = 0;
		$details['reverseShippingFee'] = 0;
		$details['shopsyMarketingFee'] = $orders[0]->shopsyMarketingFee;
		$details['shippingFeeWaiver'] = (float)$orders[0]->shippingFeeWaiver;
		$details['commissionFeeWaiver'] = (float)$orders[0]->commissionFeeWaiver;
		$details['tcs'] = $orders[0]->tcs;
		$details['tds'] = $orders[0]->tds;
		/*if (isset($_GET['test']) && $_GET['test'] == "1"){
			echo '<pre>';
			var_dump($orders);
			echo '</pre>';
		}*/
		$return_ids = array();
		foreach ($orders as $order) {
			if ($order->status == 'CANCELLED' && is_null($order->returnId)) {
				$details['refundAmount'] += $order->refundAmount;
				$details['discountRefundAmount'] += $order->discountRefundAmount;
				$details['tcs'] += $orders[0]->tcs;
				$details['tds'] += $orders[0]->tds;
			}

			$is_partial = 0;
			if (!is_null($order->returnId) && ($order->r_shipmentStatus != 'cancelled' && (/*$order->r_shipmentStatus == 'return_received' || $order->r_shipmentStatus == 'return_claimed' ||*/$order->r_shipmentStatus == 'return_completed' || $order->r_shipmentStatus == 'completed') || $order->r_shipmentStatus == 'return_unexpected' /*|| $order->r_shipmentStatus == "return_claimed_undelivered" || ($order->r_shipmentStatus == "return_claimed" && $order->claimProductCondition == "wrong") || $order->r_shipmentStatus == "return_claimed_missing"*/)) {

				if (in_array($order->returnId, $return_ids))
					continue;
				$return_ids[] = $order->returnId;

				// echo 'in-ret';
				if (!is_null($order->shipmentId)) {
					// V3 updated the response to total order value instead of each item value - HARD FIX
					$order->totalPrice = ($order->totalPrice - $order->flipkartDiscount) / $order->quantity;
					$order->flipkartDiscount = $order->flipkartDiscount / $order->quantity;
				}

				$details['shippingFeeWaiver'] += 0;
				$details['commissionFeeWaiver'] += 0;
				$details['commissionIncentive'] += 0;

				if ($order->r_source == 'courier_return') {
					// echo 'in-rto';
					$multiplier = (1 / $order->quantity) * $order->r_quantity;
					$details['commissionFee'] = 0;
					$details['commissionIncentive'] = 0;
					$details['fixedFee'] = 0;
					$details['collectionFee'] = 0;
					$details['pickPackFee'] = 0;
					$details['forwardShippingFee'] = 0;
					$details['refundAmount'] += ((float)(($order->customerPrice) * $multiplier) * -1);
					$details['discountRefundAmount'] += (float)($order->flipkartDiscount * $order->r_quantity) * -1;
					$details['shippingFeeWaiver'] = 0;
					$details['commissionFeeWaiver'] = 0;
					$details['tcs'] = 0;
					$details['tds'] = 0;
				}
				if ($order->r_source == 'customer_return' && $order->r_shipmentStatus != 'cancelled') {
					// echo 'in-cr';
					if ($order->quantity == $order->r_quantity && (strpos($order->r_action, 'dnt_expect') !== FALSE || $order->r_reason != 'ITEM_SHIPPED_TOGETHER')) {
						// echo 'in-cr-single';
						// $reverse_rate = ($this->get_shipping_fees('NON_FBF', $order->shippingZone, $order->shippingSlab, $order->orderDate, true)) * -1; // All orders fulfilled by seller are classified as NON_FBF for payment calculation.
						$details['commissionFee'] = 0;
						$details['commissionIncentive'] = 0;
						if ($this->order_date < '2022-01-01')
							$details['fixedFee'] = 0;
						// $details['refundAmount'] += (float)((($order->totalPrice - $order->flipkartDiscount ) * $order->r_quantity) * -1);
						if ($this->is_shopsy)
							$details['refundAmount'] += (float)(($order->shopsySellingPrice) * -1);
						else
							$details['refundAmount'] += (float)(($order->customerPrice) * -1);
						// $details['refundAmount'] += (float)((($order->customerPrice ) * $order->r_quantity) * -1);
						$details['discountRefundAmount'] += (float)($order->flipkartDiscount * $order->r_quantity) * -1;
						// $details['reverseShippingFee'] += (float)$reverse_rate;
						$details['shippingFeeWaiver'] = 0;
						$details['commissionFeeWaiver'] = 0;
						$details['tcs'] = 0;
						$details['tds'] = 0;
					} else { // Partial Return
						// echo 'in-cr-partial';
						$is_partial = 1;
						$multiplier = (1 / $order->quantity) * $order->r_quantity;
						$payout = $this->get_fk_payout($order->orderItemId);

						$details['reverseShippingFee'] += 0;
						$details['shopsyMarketingFee'] += 0;
						$details['commissionFee'] -= (float)$payout['commission_fees'] * $multiplier;
						$details['commissionIncentive'] -= (float)$payout['commission_incentive'] * $multiplier;
						if ($this->order_date < '2022-01-01')
							$details['fixedFee'] -= (float)$payout['fixed_fees'] * $multiplier;
						// $details['refundAmount'] += (float)((($order->totalPrice * $order->quantity) * $multiplier) * -1)/* + ($order->flipkartDiscount * $order->r_quantity)*/;
						if ($this->is_shopsy)
							$details['refundAmount'] += (float)(((($order->shopsySellingPrice) * $order->r_quantity) * $multiplier) * -1);
						else
							$details['refundAmount'] += (float)(((($order->customerPrice) * $order->r_quantity) * $multiplier) * -1);
						$details['discountRefundAmount'] += (float)($order->flipkartDiscount * $order->r_quantity) * -1;
						$details['shippingFeeWaiver'] += $order->shippingFeeWaiver;
						$details['commissionFeeWaiver'] += $order->commissionFeeWaiver;
						$details['tcs'] -= (float)($payout['tcs'] * $multiplier);
						$details['tds'] -= (float)($payout['tds'] * $multiplier);
					}

					if (/*is_null($order->r_expectedDate) || */strpos($order->r_action, 'dnt_expect') !== FALSE) {
						// echo 'in-cr-dont-expect';
						// $payout = $this->get_fk_payout($order->orderItemId);
						$details['reverseShippingFee'] += 0;
						$details['shopsyMarketingFee'] += 0;
						// $details['commissionFee'] += (float)$payout['commission_fees'];
						// $details['fixedFee'] += (float)$payout['fixed_fees']; 
						// $details['refundAmount'] = 0;
						// $details['discountRefundAmount'] = (float)($order->flipkartDiscount * $order->r_quantity) * -1 ;
					} else {
						$order_type = (($order->order_type == "FBF_LITE"  || $order->order_type == "NON_FBF") ? "NON_FBF" : $order->order_type); // All order fulfilled by seller FBF_LITE and NON_FBF are termed as NON_FBF
						$reverse_rate = ($this->get_shipping_fees($order_type, $order->shippingZone, $order->shippingSlab, $order->orderDate, true)) * -1; // All orders fulfilled by seller are classified as NON_FBF for payment calculation.
						$details['reverseShippingFee'] += (float)$reverse_rate;
					}

					if (isset($details['refundAmount']) && ($details['refundAmount'] < (float)($order->invoiceAmount * -1) && (float)($order->invoiceAmount) != 0) && !$is_partial && ($order->status != 'CANCELLED')) {
						// echo 'in-par-ref';
						$details['refundAmount'] += (float)((($order->totalPrice - $order->flipkartDiscount) * $order->r_quantity) * -1);
					}
				}

				if (($details['refundAmount'] == (int)0) && ($details['refundAmount'] > ($order->customerPrice * -1)))
					$details['refundAmount'] = $order->customerPrice * -1;
			} else if ($order->r_shipmentStatus == 'cancelled' || $order->r_shipmentStatus == 'return_cancelled') {
				// echo 'in-cr-ret-can';
				$details['refundAmount'] += 0;
				$details['reverseShippingFee'] += 0;
				$details['shopsyMarketingFee'] += 0;
				// $details['shippingFeeWaiver'] = $order->shippingFeeWaiver;
				// $details['commissionFeeWaiver'] = $order->commissionFeeWaiver;

				// if ($details['refundAmount'] > $order->totalPrice && ($details['refundAmount'] != (int)0));
				// 	$details['refundAmount'] = $order->totalPrice*-1;
			}

			if (isset($_GET['test']) && $_GET['test'] == "1") {
				echo '<pre>';
				// print_r($order);
				var_dump($details);
				echo '</pre>';
			}

			if (!empty($details)) {
				$db->where('orderItemId', $order->orderItemId);
				if ($db->update(TBL_FK_ORDERS, $details)) {
					$this->update_settlement_status($order->orderItemId);
					$return[] = true;
				} else {
					$return[] = false;
				}
			}
		}

		if (in_array(false, $return))
			$return_s = 'Error updating returns payment for orderItemId ' . $order->orderItemId;
		else
			$return_s = 'Successfully updated payment details for orderItemId ' . $order->orderItemId;

		return $return_s;
	}

	// public static function getInstance($account){
	// 	if (!self::$instance) {
	// 		self::$instance = new flipkart_payments($account);
	// 	}
	// 	return self::$instance;
	// }
}
