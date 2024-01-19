<?php

include(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
// ----------------------------------------------------------------------------------------------------
// Un-comment bellow lines if you get Error code 500
// 
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
// ----------------------------------------------------------------------------------------------------

class AmazonPayments
{
    private $account;
    private $orderDate;
    private $orderTier;
    private $serviceGst;
    private $productGst;

    public function __construct($account)
    {
        $this->account = $account;
    }

    public function get_seller_tier()
    {
        $tiers = $this->account["account_tier"];
        $date = $this->orderDate;
        $return = null;

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

    public function get_fixed_fees($type, $grossPrice)
    {
        global $db;

        $date = date('Y-m-d', strtotime($this->orderDate));
        $db->where('fee_name', 'closingFee');
        $db->where('fee_marketplace', 'amazon');
        $db->where('fee_tier', $this->tier);
        $db->where('fee_fulfilmentType', $type);
        $db->where('(fee_attribute_min <= ? AND fee_attribute_max >= ? ) ', array($grossPrice, $grossPrice));
        $db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
        $fixedFees = $db->getValue(TBL_MP_FEES, 'fee_value');
        return $fixedFees;
    }

    public function get_pick_pack_fees()
    {
        global $db;

        $db->where("fee_marketplace", "amazon");
        $db->where("fee_name", "pickPackFee");

        $fee = (float) $db->getValue(TBL_MP_FEES, "fee_value");
        return $fee;
    }

    public function get_shipping_zone($warehouse, $to)
    {
        global $db;

        $db->where("warehouseName", $warehouse);
        $from = json_decode($db->getValue(TBL_AZ_WAREHOUSES, "warehouseAddress"), true);

        if (strtolower($from["state"]) == strtolower($to["StateOrRegion"])) {
            if (strtolower($from["city"]) == strtolower($to["City"])) {
                $zone = "local";
            } else {
                $zone = "regional";
            }
        } else {
            $zone = "national";
        }

        return $zone;
    }

    public function get_handling_fee($region, $category)
    {
        global $db;

        $db->where("fee_marketplace", "amazon");
        $db->where("fee_column", $region);
        $db->where("fee_category", $category);
        $db->where("fee_name", "handlingFee");
        $db->where("(fee_tier=? OR fee_tier=?)", array($this->orderTier, "all"));
        $fees = $db->get(TBL_MP_FEES, null, "*");

        $feeValue = 0;
        foreach ($fees as $fee) {
            $feeValue += (float) $fee["fee_value"];
        }
        return $feeValue;
    }

    public function get_commission_fee($category, $gross_price, $brand = null)
    {
        global $db;

        $category = str_replace(' ', '_', strtolower($category));
        $date = date('Y-m-d', strtotime($this->orderDate));
        $db->where('fee_name', 'commissionFee');
        $db->where('fee_brand', ($brand == null ? "all" : $brand));
        $db->where('fee_marketplace', 'amazon');
        $db->where('fee_tier', "all");
        $db->where('fee_category', $category);
        $db->where('(fee_attribute_min < ? AND fee_attribute_max >= ? ) ', array($gross_price, $gross_price));
        $db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
        $rate = $db->getOne(TBL_MP_FEES);

        if ($rate["fee_type"] == "percentage")
            $fee = $gross_price * (float)$rate["fee_value"] / 100;
        else
            $fee = (float)$rate["fee_value"];
        return $fee;
    }

    public function get_closing_fee($gross_price, $category, $type = null)
    {
        global $db;

        $date = date('Y-m-d', strtotime($this->orderDate));
        $category = str_replace(' ', '_', strtolower($category));
        $db->where('fee_name', 'closingFee');
        $db->where('fee_category', $category);
        $db->where('fee_fulfilmentType', ($type == null ? "all" : $type));
        $db->where('(fee_attribute_min < ? AND fee_attribute_max >= ? ) ', array($gross_price, $gross_price));
        $db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
        $rate = $db->getOne(TBL_MP_FEES, "*");

        if ($rate["fee_type"] == "percentage")
            $fee = $gross_price * (float)$rate["fee_value"] / 100;
        else
            $fee = (float)$rate["fee_value"];
        return $fee;
    }

    public function calculate_net_settlement($orderItemId)
    {
        global $db;

        $db->where('itemId', $orderItemId);
        $settlements = $db->get(TBL_AZ_PAYMENTS, null, '*');

        $finalSettlement = (float) $settlements[0]["salesAmount"] + (float) $settlements[0]["salesGst"];

        for ($i = 0; $i < count($settlements); $i++) {
            foreach ($settlements[$i] as $key => $settlement) {
                switch ($key) {
                    case 'tcs':
                    case 'tds':
                    case 'shippingCharge':
                    case 'shippingTax':
                    case 'shippingDiscount':
                    case 'shippingTaxDiscount':
                    case 'handlingCharge':
                    case 'handlingTax':
                    case 'pickPackCharge':
                    case 'pickPackTax':
                    case 'commission':
                    case 'commissionTax':
                    case 'closingFee':
                    case 'closingFeeTax':
                        $finalSettlement += (float) $settlement;
                        break;
                    default:
                        continue;
                }
            }
        }

        return $finalSettlement;
    }

    private function set_order_tier($date)
    {
        global $db;
        $db->where("account_id", $order->account_id);
        $account = $db->getOne(TBL_AZ_ACCOUNTS);
        $accountTiers = json_decode($account["account_tier"], true);

        foreach ($accountTiers as $accountTier) {
            if ((strtotime($date) > strtotime($accountTier["startDate"])) && (strtotime($date) <= strtotime($accountTier["endDate"]))) {
                $this->orderTier = $accountTier["tier"];
            }
        }
    }

    public function calculate_actual_payout($orderItemId)
    {
        global $db;

        $db->where('orderItemId', $orderItemId);
        $order = $db->objectBuilder()->getOne(TBL_AZ_ORDERS);
        $this->orderDate = $order->orderDate;

        if ($order->status == "CANCELLED")
            return 0;

        $finalShipping = (float) $order->shippingPrice + (float) $order->shippingTax + (float) $order->shippingDiscount + (float) $order->shippingDiscountTax;

        // TCS & TDS
        $tcs = (float) $order->itemPrice * 0.01;
        $tds = (float) $order->itemPrice * 0.01;

        // set basic details
        $this->set_order_tier($order->orderDate);
        $zone = $this->get_shipping_zone($order->fulfilmentSite, json_decode($order->shippingAddress, true));

        // calculate charges
        $handlingCharge = $this->get_handling_fee($zone, $order->fulfillmentChannel);
        $pickPackFee = $this->get_pick_pack_fees();
        $commissionFee = round($this->get_commission_fee("watches", $order->itemPrice), 2);
        $closingFee = $this->get_closing_fee($order->itemPrice, $order->fulfillmentChannel);

        $tax = ($handlingCharge + $pickPackFee + $commissionFee + $closingFee) * 0.18;

        $payout = (float)$order->itemPrice + (float)$finalShipping - ($tcs + $tds + $pickPackFee + $handlingCharge + $commissionFee + $closingFee + $tax);

        return round($payout, 2);
    }

    function get_difference_details($orderItemId)
    {
        global $db;

        $db->join(TBL_AZ_RETURNS . " r", "r.orderId=o.orderId", "LEFT");
        $db->join(TBL_AZ_CLAIMS . " c", "c.orderId=o.orderId", "LEFT");
        $db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.asin", "LEFT");
        $db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
        $db->join(TBL_AZ_INCIDENTS . " ai", "ai.itemId = o.orderItemId", "LEFT");
        $db->where('o.orderItemId', $orderItemId);
        $db->where('o.account_id', $this->account["account_id"]);
        $orders = $db->objectBuilder()->get(TBL_AZ_ORDERS . ' o', NULL, 'o.trackingID, o.fulfillmentChannel, o.orderItemId, o.orderDate, o.status, o.az_status, o.orderType, o.title, o.asin, o.sku, o.quantity, o.itemPrice, o.orderTotal, o.promotionDiscount, o.dispatchAfterDate, o.shippedDate, o.shippingAddress, o.shippingPrice, o.replacementOrder, r.orderId, r.rQty, r.rSource, r.rShipmentStatus, r.rReason, r.rDeliveredDate, r.rReceivedDate, r.rCompletionDate, r.shipmentId, c.claimId, c.createdDate, c.claimProductCondition, c.claimStatus, c.claimReimbursmentAmount, c.updatedDate, pm.thumb_image_url, ai.caseId as incidentId, ai.reason as claimReason');

        $order = $orders[0];
        if ($order->replacementOrder) {
            $db->join(TBL_AZ_ORDERS . ' o', 'o.orderId=r.orderId');
            $db->where('orderId', $order->orderId);
            $this->orderDate = $db->getValue(TBL_AZ_RETURNS . ' r', 'r.rCreatedDate');
        } else {
            $this->orderDate = date('Y-m-d H:i:s', strtotime($order->orderDate));
        }
        $this->orderTier = $this->get_seller_tier(); // returns default gold.

        // set basic details
        $this->set_order_tier($order->orderDate);
        $zone = $this->get_shipping_zone($order->fulfilmentSite, json_decode($order->shippingAddress, true));

        // calculate charges
        $handlingCharge = $this->get_handling_fee($zone, $order->fulfillmentChannel);
        $pickPackFee = $this->get_pick_pack_fees();
        $commissionFee = round($this->get_commission_fee("watches", $order->itemPrice), 2);
        $closingFee = $this->get_closing_fee($order->itemPrice, $order->fulfillmentChannel);

        $marketplace_fees = (float)$commissionFee + (float)$pickPackFee + (float)$closingFee + (float)$handlingCharge;
        $mp_fees_gst = (float)number_format(($marketplace_fees * 0.18) / 100, 2, '.', '');

        $sale_amount = (float)($order->totalPrice - $order->promotionDiscount);
        if ($order->status == "CANCELLED")
            $sale_amount = 0;

        // TCS & TDS
        $tcs = (float) $order->itemPrice * 0.01;
        $tds = (float) $order->itemPrice * 0.01;

        $expected_payout = array(
            'settlement_date' => '<b>Order Date: </b>' . date('d M, Y', strtotime($order->orderDate)) . ' <br />Due Date: ' . date('d M, Y', strtotime($order->shippedDate . " +8 days")), // 8th day from shipping date
            'sale_amount' => $sale_amount,
            'amazon_discount' => (float)($order->promotionDiscount),
            'marketplace_fees' => $marketplace_fees,
            'commission_rate' => (float)$order->commissionRate,
            'commission_fee' => (float)$commissionFee,
            'fixed_fee' => (float)$closingFee,
            'shipping_fee' => (float)$order->shippingPrice,
            'taxes' => (float)$tcs + (float)$tds + $mp_fees_gst,
            'tcs' => (float)$tcs,
            'tds' => (float)$tds,
            'gst' => $mp_fees_gst,
            'total' => ($order->status == "CANCELLED") ? 0 : (float)($order->totalPrice + $marketplace_fees + $mp_fees_gst + $order->tcs + $order->tds + $order->commissionIncentive),
            'shipping_zone' => $zone,
        );

        $db->where('itemId', $orderItemId);
        $payments = $db->objectBuilder()->get(TBL_AZ_PAYMENTS);

        $settlements = array();
        foreach ($payments as $payment) {
            $commission_incentive = 0;
            if ((float)$payment->paymentValue == (float)$payment->commission) {
                $commission_incentive = (float)$payment->commission;
                $payment->commission = 0;
            }

            $order_settlement = array(
                'settlement_date' => $payment->settlementId . ' <br />' . date('d M, Y', strtotime($payment->settlementDate)),
                'sale_amount' => (float)$payment->salesAmount,
                'marketplace_fees' => (float)$payment->handlingCharge + (float)$payment->pickPackCharge + (float)$payment->commission + (float)$payment->closingFee,
                'commission_fee' => (float)$payment->commission,
                'fixed_fee' => (float)$payment->closingFee,
                'shipping_fee' => (float)$payment->shippingCharge,
                'taxes' => (float)$payment->tcs + (float)$payment->tds + (float)$payment->shippingTax + (float)$payment->handlingTax + (float)$payment->pickPackTax + (float)$payment->commissionTax + (float)$payment->closingFeeTax,
                'tcs' => (float)$payment->tax_collected_at_source,
                'tds' => (float)$payment->tds,
                'gst' => (float)$payment->handlingTax + (float)$payment->pickPackTax + (float)$payment->commissionTax + (float)$payment->closingFeeTax,
                'total' => (float)$payment->paymentValue,
                'shipping_zone' => strtolower($zone)
            );

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
        $total_settlement['shipping_zone'] = $zone;

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

        return $return;
    }
}
