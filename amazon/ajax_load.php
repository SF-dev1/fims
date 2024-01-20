<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

if (isset($_REQUEST['action'])) {
    include_once(dirname(dirname(__FILE__)) . '/config.php');
    include_once(ROOT_PATH . '/includes/connectors/amazon/amazon.php');
    include_once(ROOT_PATH . '/includes/connectors/amazon/amazon-payments.php');
    include_once(ROOT_PATH . '/amazon/functions.php');
    include_once(ROOT_PATH . '/includes/vendor/autoload.php');
    global $db, $accounts, $log, $currentUser;

    $accounts = $accounts['amazon'];

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : "";
    switch ($action) {
        case 'get_orders':
            $fulfillmentChannel = array("MFN", "AFN");
            if (isset($_GET['channel']))
                $fulfillmentChannel = explode(',', strtoupper($_GET['channel']));
            $multiInsert = array();
            $updated = 0;
            foreach ($accounts as $account) {
                if (!$account->account_status)
                    continue;

                $sp_api = new amazon($account);
                $orders = $sp_api->GetOrders(
                    [
                        'LastUpdatedAfter' => gmdate("Y-m-d\TH:i:s\Z", strtotime('-16 mins')),  // every 16 mins. cron at every 15 mins. Always use GMT timezone for all dates
                        // 'CreatedAfter' => gmdate('Y-m-d\TH:i:s', strtotime('-10 days')),
                        'MarketplaceIds' => $sp_api->marketplace,
                        'fulfillmentChannel' => implode(',', $fulfillmentChannel),
                        "OrderStatuses" => 'Pending,Unshipped,PartiallyShipped,Shipped,Canceled',
                        "MaxResultsPerPage" => 100
                    ]
                );

                foreach ($orders as $order) {
                    $db->where('orderId', $order["AmazonOrderId"]);
                    $odr = $db->getOne(TBL_AZ_ORDERS, 'az_status, status, shipmentStatus, uid, fulfilmentSite');

                    $details = array(
                        "orderId" => $order["AmazonOrderId"], // REQUIRED
                        "account_id" => $account->account_id,

                        "az_status" => $order["OrderStatus"],
                        "orderType" => $order["OrderType"],
                        "fulfillmentChannel" => $order["FulfillmentChannel"],

                        "orderDate" => date('Y-m-d H:i:s', strtotime($order["PurchaseDate"])), // REQUIRED
                        "updateDate" => date('Y-m-d H:i:s', strtotime($order["LastUpdateDate"])), // REQUIRED
                        "dispatchAfterDate" => date('Y-m-d H:i:s', strtotime($order["EarliestShipDate"])), // REQUIRED
                        "shipByDate" => date('Y-m-d H:i:s', strtotime($order["LatestShipDate"] . ' - 12 hours')), // REQUIRED

                        "replacementOrder" => filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN),

                        "shipmentStatus" => isset($order["EasyShipShipmentStatus"]) ? $order["EasyShipShipmentStatus"] : 0, // PendingPickUp, LabelCanceled, PickedUp, OutForDelivery, Damaged, Delivered, RejectedByBuyer, Undeliverable, ReturnedToSeller, ReturningToSeller
                        "shipServiceCategory" => $order["ShipmentServiceLevelCategory"],
                        "shipServiceLevel" => $order["ShipServiceLevel"],

                        "isBusinessOrder" => filter_var($order["IsBusinessOrder"], FILTER_VALIDATE_BOOLEAN),
                        "isPrime" => filter_var($order["IsPrime"], FILTER_VALIDATE_BOOLEAN),
                        "isPremiumOrder" => filter_var($order["IsPremiumOrder"], FILTER_VALIDATE_BOOLEAN),
                        "isSoldByAB" => filter_var($order["IsSoldByAB"], FILTER_VALIDATE_BOOLEAN),
                        "isGlobalExpressEnabled" => filter_var($order["IsGlobalExpressEnabled"], FILTER_VALIDATE_BOOLEAN),

                        "buyerName" => "",
                        "buyerEmail" => "",
                        "orderTotal" => isset($order["OrderTotal"]["Amount"]) ? $order["OrderTotal"]["Amount"] : 0,
                    );

                    if ($order["FulfillmentChannel"] == "MFN") {
                        $details["deliveryByDate"] = date('Y-m-d H:i:s', strtotime($order["LatestDeliveryDate"])); // MFN ONLY
                    }

                    if ($order["OrderStatus"] != "Pending" && $order["OrderStatus"] != "Canceled") {
                        $details["shippingAddress"] = json_encode($order["ShippingAddress"]);
                        $details["paymentMethod"] = ($order["PaymentMethod"] == "other") ? $order["PaymentExecutionDetail"]["PaymentExecutionDetailItem"]["PaymentMethod"] : $order["PaymentMethod"];
                    }

                    if (filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN))
                        $details["replacedOrderId"] = $order["ReplacedOrderId"];  // AVAILABLE ONLY IS IsReplacementOrder is true

                    $items = ($sp_api->ListOrderItems($order["AmazonOrderId"]));
                    foreach ($items as $item) {
                        if (!is_null($odr)) {
                            if ($order['OrderStatus'] == "Canceled")
                                $details['status'] = "cancelled";

                            $cancel_status = true;
                            if ($odr['status'] == "shipped" && $odr['shipmentStatus'] == "PENDINGPICKUP")
                                $cancel_status = update_order_inventory_status($order["AmazonOrderId"], $item["OrderItemId"], $odr['uid'], $order['OrderStatus']);

                            if (is_null($odr['fulfilmentSite']) && ($odr['status'] == "new" && $order["OrderStatus"] == "Shipped")) {
                                $details['fulfilmentSite'] = 'AMD2';
                                $details['status'] = 'shipped';
                                $details['rtdDate'] = $db->now();
                                $details['shippedDate'] = $db->now();
                            }

                            if ($cancel_status) {
                                $db->where('orderItemId', $item["OrderItemId"]);
                                $db->where('orderId', $order["AmazonOrderId"]);
                                if ($db->update(TBL_AZ_ORDERS, $details)) {
                                    $updated++;
                                }
                            }
                            continue;
                        }

                        $costPrice = get_cost_price_by_asin($account->account_id, $item['ASIN']);
                        if ($costPrice == 'Parent SKU not found') {
                            $return = 'Parent SKU not found for order ID ' . $item['AmazonOrderId'] . ' & Item ID ' . $item['OrderItemId'] . ' :: Data: ' . json_encode($item) . ' :: BACK TRACE: ' . json_encode(debug_backtrace());
                            $log->write($return, 'amazon-orders');
                            continue;
                        }

                        $item_details = array(
                            // ITEMS DETAILS
                            "orderItemId" => $item["OrderItemId"], // REQUIRED
                            "quantity" => $item["QuantityOrdered"], // REQUIRED
                            "asin" => $item["ASIN"], // REQUIRED
                            "sku" => $item["SellerSKU"],
                            "title" => $item["Title"],

                            "isGift" => filter_var($item["IsGift"], FILTER_VALIDATE_BOOLEAN),
                            "isTransparency" => filter_var($item["IsTransparency"], FILTER_VALIDATE_BOOLEAN),
                            "costPrice" => $costPrice,
                            "insertDate" => $db->now()
                        );

                        if (filter_var($item["IsGift"], FILTER_VALIDATE_BOOLEAN)) {
                            $item_details["giftMessageText"] = $item["GiftMessageText"];
                            $item_details["giftWrapLevel"] = $item["GiftWrapLevel"];
                        }

                        if (!filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN)) {
                            $item_details["itemPrice"] = isset($item["ItemPrice"]["Amount"]) ? $item["ItemPrice"]["Amount"] : 0;
                            $item_details["itemTax"] = isset($item["ItemTax"]["Amount"]) ? $item["ItemTax"]["Amount"] : 0;
                            $item_details["shippingPrice"] = isset($item["ShippingPrice"]) ? $item["ShippingPrice"]["Amount"] : 0;
                            $item_details["shippingTax"] = isset($item["ShippingTax"]) ? $item["ShippingTax"]["Amount"] : 0;
                            $item_details["shippingDiscount"] = isset($item["ShippingDiscount"]) ? $item["ShippingDiscount"]["Amount"] : 0;
                            $item_details["shippingDiscountTax"] = isset($item["ShippingDiscountTax"]) ? $item["ShippingDiscountTax"]["Amount"] : 0;
                            $item_details["giftWrapPrice"] = isset($item["GiftWrapPrice"]) ? $item["GiftWrapPrice"]["Amount"] : 0;
                            $item_details["giftWrapTax"] = isset($item["GiftWrapTax"]) ? $item["GiftWrapTax"]["Amount"] : 0;
                            $item_details["promotionDiscount"] = isset($item["PromotionDiscount"]) ? $item["PromotionDiscount"]["Amount"] : 0;
                            $item_details["promotionDiscountTax"] = isset($item["PromotionDiscountTax"]) ? $item["PromotionDiscountTax"]["Amount"] : 0;
                            $item_details["codFee"] = isset($item["CODFee"]["Amount"]) ? $item["CODFee"]["Amount"] : 0;
                            $item_details["codFeeDiscount"] = isset($item["CODFeeDiscount"]["Amount"]) ? $item["CODFeeDiscount"]["Amount"] : 0;
                        }

                        if (filter_var($order["IsBusinessOrder"], FILTER_VALIDATE_BOOLEAN))
                            $item_details["priceDesignation"] = $item["PriceDesignation"];

                        if (count($items) > 1)
                            $details['isMultiItems'] = true;

                        if ($order["OrderStatus"] != "Canceled") {
                            $details["status"] = "new";
                            $multiInsert[] = array_merge($details, $item_details);
                        }
                    }
                }
            }

            if (!empty($multiInsert)) {
                if ($db->insertMulti(TBL_AZ_ORDERS, $multiInsert)) {
                    echo 'Successfully inserted ' . count($multiInsert) . ' orders and updated ' . $updated . ' orders';
                } else {
                    echo 'Error adding orders ' . $db->getLastError();
                }
            } else if ($updated !== 0) {
                echo 'Succesfully updated ' . $updated . ' orders';
            } else {
                echo 'No orders found';
            }
            break;

        case 'get_order':
            $order_ids = explode(',', str_replace(' ', '', $_GET['order_ids']));
            $account_id = $_GET['account_id'];

            $account = get_current_account($account_id);
            $sp_api = new amazon($account);

            $multiInsert = array();
            $updated = 0;
            foreach ($order_ids as $order_id) {
                $db->where('orderId', $order_id);
                if ($db->has(TBL_AZ_ORDERS))
                    continue;

                $order = $sp_api->GetOrder($order_id);
                if ($order === false)
                    break;
                if ($order && !isset($order['type'])) {
                    $details = array(
                        "orderId" => $order["AmazonOrderId"], // REQUIRED
                        "account_id" => $account->account_id,

                        "az_status" => $order["OrderStatus"],
                        "orderType" => $order["OrderType"],
                        "fulfillmentChannel" => $order["FulfillmentChannel"],

                        "orderDate" => date('Y-m-d H:i:s', strtotime($order["PurchaseDate"])), // REQUIRED
                        "updateDate" => date('Y-m-d H:i:s', strtotime($order["LastUpdateDate"])), // REQUIRED
                        "dispatchAfterDate" => date('Y-m-d H:i:s', strtotime($order["EarliestShipDate"])), // REQUIRED
                        "shipByDate" => date('Y-m-d H:i:s', strtotime($order["LatestShipDate"])), // REQUIRED

                        "replacementOrder" => filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN),

                        "shipmentStatus" => isset($order["EasyShipShipmentStatus"]) ? $order["EasyShipShipmentStatus"] : 0, // PendingPickUp, LabelCanceled, PickedUp, OutForDelivery, Damaged, Delivered, RejectedByBuyer, Undeliverable, ReturnedToSeller, ReturningToSeller
                        "shipServiceCategory" => $order["ShipmentServiceLevelCategory"],
                        "shipServiceLevel" => $order["ShipServiceLevel"],

                        "isBusinessOrder" => filter_var($order["IsBusinessOrder"], FILTER_VALIDATE_BOOLEAN),
                        "isPrime" => filter_var($order["IsPrime"], FILTER_VALIDATE_BOOLEAN),
                        "isPremiumOrder" => filter_var($order["IsPremiumOrder"], FILTER_VALIDATE_BOOLEAN),
                        "isSoldByAB" => filter_var($order["IsSoldByAB"], FILTER_VALIDATE_BOOLEAN),
                        "isGlobalExpressEnabled" => filter_var($order["IsGlobalExpressEnabled"], FILTER_VALIDATE_BOOLEAN),

                        "buyerName" => "",
                        "buyerEmail" => "",
                        "orderTotal" => isset($order["OrderTotal"]["Amount"]) ? $order["OrderTotal"]["Amount"] : 0,
                    );

                    if ($order["FulfillmentChannel"] == "MFN") {
                        $details["deliveryByDate"] = date('Y-m-d H:i:s', strtotime($order["LatestDeliveryDate"])); // MFN ONLY
                    }

                    if ($order["OrderStatus"] != "Pending" && $order["OrderStatus"] != "Canceled") {
                        $details["shippingAddress"] = json_encode($order["ShippingAddress"]);
                        $details["paymentMethod"] = ($order["PaymentMethod"] == "other") ? $order["PaymentExecutionDetail"]["PaymentExecutionDetailItem"]["PaymentMethod"] : $order["PaymentMethod"];
                    }

                    if (filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN))
                        $details["replacedOrderId"] = $order["ReplacedOrderId"];  // AVAILABLE ONLY IS IsReplacementOrder is true

                    $items = $sp_api->ListOrderItems($order["AmazonOrderId"]);
                    foreach ($items as $item) {
                        $db->where('orderItemId', $item["OrderItemId"]);
                        $db->where('orderId', $order["AmazonOrderId"]);
                        $odr = $db->getOne(TBL_AZ_ORDERS, 'az_status, status, shipmentStatus, uid');
                        if (!is_null($odr)) {
                            if ($order['OrderStatus'] == "Canceled")
                                $details['status'] = "cancelled";

                            $cancel_status = true;
                            if ($odr['status'] == "shipped" && $odr['shipmentStatus'] == "PENDINGPICKUP")
                                $cancel_status = update_order_inventory_status($order["AmazonOrderId"], $item["OrderItemId"], $odr['uid'], $order['OrderStatus']);

                            if ($cancel_status) {
                                $db->where('orderItemId', $item["OrderItemId"]);
                                $db->where('orderId', $order["AmazonOrderId"]);
                                if ($db->update(TBL_AZ_ORDERS, $details)) {
                                    $log->write($details, 'amazon-orders');
                                    $updated++;
                                }
                            }
                            continue;
                        }

                        $costPrice = get_cost_price_by_asin($account->account_id, $item['ASIN']);
                        if ($costPrice == 'Parent SKU not found') {
                            $return = 'Parent SKU not found for order ID ' . $order['AmazonOrderId'] . ' & Item ID ' . $item['OrderItemId'] . ' :: Account: ' . $account->account_id . ' :: Data: ' . json_encode($item) . ' :: BACK TRACE: ' . json_encode(debug_backtrace());
                            $log->write($return, 'amazon-orders');
                            continue;
                        }

                        $item_details = array(
                            // ITEMS DETAILS
                            "orderItemId" => $item["OrderItemId"], // REQUIRED
                            "quantity" => $item["QuantityOrdered"], // REQUIRED
                            "asin" => $item["ASIN"], // REQUIRED
                            "sku" => $item["SellerSKU"],
                            "title" => $item["Title"],

                            "isGift" => filter_var($item["IsGift"], FILTER_VALIDATE_BOOLEAN),
                            "isTransparency" => filter_var($item["IsTransparency"], FILTER_VALIDATE_BOOLEAN),
                            "costPrice" => $costPrice,
                            "insertDate" => $db->now()
                        );

                        if (filter_var($item["IsGift"], FILTER_VALIDATE_BOOLEAN)) {
                            $item_details["giftMessageText"] = $item["GiftMessageText"];
                            $item_details["giftWrapLevel"] = $item["GiftWrapLevel"];
                        }

                        if (!filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN)) {
                            $item_details["itemPrice"] = isset($item["ItemPrice"]["Amount"]) ? $item["ItemPrice"]["Amount"] : 0;
                            $item_details["itemTax"] = isset($item["ItemTax"]["Amount"]) ? $item["ItemTax"]["Amount"] : 0;
                            $item_details["shippingPrice"] = isset($item["ShippingPrice"]) ? $item["ShippingPrice"]["Amount"] : 0;
                            $item_details["shippingTax"] = isset($item["ShippingTax"]) ? $item["ShippingTax"]["Amount"] : 0;
                            $item_details["shippingDiscount"] = isset($item["ShippingDiscount"]) ? $item["ShippingDiscount"]["Amount"] : 0;
                            $item_details["shippingDiscountTax"] = isset($item["ShippingDiscountTax"]) ? $item["ShippingDiscountTax"]["Amount"] : 0;
                            $item_details["giftWrapPrice"] = isset($item["GiftWrapPrice"]) ? $item["GiftWrapPrice"]["Amount"] : 0;
                            $item_details["giftWrapTax"] = isset($item["GiftWrapTax"]) ? $item["GiftWrapTax"]["Amount"] : 0;
                            $item_details["promotionDiscount"] = isset($item["PromotionDiscount"]) ? $item["PromotionDiscount"]["Amount"] : 0;
                            $item_details["promotionDiscountTax"] = isset($item["PromotionDiscountTax"]) ? $item["PromotionDiscountTax"]["Amount"] : 0;
                            $item_details["codFee"] = isset($item["CODFee"]["Amount"]) ? $item["CODFee"]["Amount"] : 0;
                            $item_details["codFeeDiscount"] = isset($item["CODFeeDiscount"]["Amount"]) ? $item["CODFeeDiscount"]["Amount"] : 0;
                        }

                        if (filter_var($order["IsBusinessOrder"], FILTER_VALIDATE_BOOLEAN))
                            $item_details["priceDesignation"] = $item["PriceDesignation"];

                        if (count($items) > 1)
                            $details['isMultiItems'] = true;

                        if ($order["OrderStatus"] != "Canceled") {
                            $details["status"] = "new";
                            $multiInsert[] = array_merge($details, $item_details);
                        }
                    }
                }
            }

            if (!empty($multiInsert)) {
                if ($db->insertMulti(TBL_AZ_ORDERS, $multiInsert)) {
                    $log->write($multiInsert, 'amazon-orders');
                    echo 'Successfully inserted ' . count($multiInsert) . ' orders and updated ' . $updated . ' orders';
                } else {
                    echo 'Error adding orders ' . $db->getLastError();
                }
            } else if ($updated != 0) {
                echo 'Succesfully updated ' . $updated . ' orders';
            } else {
                echo 'No orders found';
            }
            break;

        case 'update_orders':
            if (isset($_POST['orders'])) {
                $account_orders = json_decode($_POST['orders'], 1);
            } else {
                $db->where('status', 'new');
                $db->where('fulfillmentChannel', 'AFN');
                // $db->where("dispatchAfterDate", date('Y-m-d H:i:s', time()), '<=');
                $orders = $db->get(TBL_AZ_ORDERS, NULL, 'account_id, orderId');
                foreach ($orders as $order) {
                    $account_orders[$order['account_id']][] = $order['orderId'];
                }
            }
            $return = array();
            $response_accounts = array();
            $success_count = 0;

            foreach ($account_orders as $account_id => $order_ids) {
                if (!in_array($account_id, $response_accounts))
                    $response_accounts[] = $account_id;

                $account = get_current_account($account_id);

                $sp_api = new amazon($account);
                $orders = $sp_api->GetOrders([
                    'OrderIds' => $order_ids,
                    'MarketplaceIds' => $sp_api->marketplace
                ]);

                $multiInsert = array();
                foreach ($orders as $order) {
                    $return['orders'][$account_id][] = $order["AmazonOrderId"];
                    if ($order) {
                        $details = array(
                            "orderId" => $order["AmazonOrderId"], // REQUIRED
                            "account_id" => $account->account_id,

                            "az_status" => $order["OrderStatus"],
                            "orderType" => $order["OrderType"],
                            "fulfillmentChannel" => $order["FulfillmentChannel"],

                            "orderDate" => date('Y-m-d H:i:s', strtotime($order["PurchaseDate"])), // REQUIRED
                            "updateDate" => date('Y-m-d H:i:s', strtotime($order["LastUpdateDate"])), // REQUIRED
                            "dispatchAfterDate" => date('Y-m-d H:i:s', strtotime($order["EarliestShipDate"])), // REQUIRED
                            "shipByDate" => date('Y-m-d H:i:s', strtotime($order["LatestShipDate"])), // REQUIRED

                            "replacementOrder" => filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN),

                            "shipmentStatus" => isset($order["EasyShipShipmentStatus"]) ? $order["EasyShipShipmentStatus"] : 0, // PendingPickUp, LabelCanceled, PickedUp, OutForDelivery, Damaged, Delivered, RejectedByBuyer, Undeliverable, ReturnedToSeller, ReturningToSeller
                            "shipServiceCategory" => $order["ShipmentServiceLevelCategory"],
                            "shipServiceLevel" => $order["ShipServiceLevel"],

                            "isBusinessOrder" => filter_var($order["IsBusinessOrder"], FILTER_VALIDATE_BOOLEAN),
                            "isPrime" => filter_var($order["IsPrime"], FILTER_VALIDATE_BOOLEAN),
                            "isPremiumOrder" => filter_var($order["IsPremiumOrder"], FILTER_VALIDATE_BOOLEAN),
                            "isSoldByAB" => filter_var($order["IsSoldByAB"], FILTER_VALIDATE_BOOLEAN),
                            "isGlobalExpressEnabled" => filter_var($order["IsGlobalExpressEnabled"], FILTER_VALIDATE_BOOLEAN),

                            "buyerName" => "",
                            "buyerEmail" => "",
                            "orderTotal" => isset($order["OrderTotal"]["Amount"]) ? $order["OrderTotal"]["Amount"] : 0,
                        );

                        if ($order["FulfillmentChannel"] == "MFN") {
                            $details["deliveryByDate"] = date('Y-m-d H:i:s', strtotime($order["LatestDeliveryDate"])); // MFN ONLY
                        }

                        if ($order["OrderStatus"] != "Pending" && $order["OrderStatus"] != "Canceled") {
                            $details["shippingAddress"] = json_encode($order["ShippingAddress"]);
                            $details["paymentMethod"] = ($order["PaymentMethod"] == "other") ? $order["PaymentExecutionDetail"]["PaymentExecutionDetailItem"]["PaymentMethod"] : $order["PaymentMethod"];
                        }

                        if (filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN))
                            $details["replacedOrderId"] = $order["ReplacedOrderId"];  // AVAILABLE ONLY IS IsReplacementOrder is true

                        $items = $mws->ListOrderItems($order["AmazonOrderId"]);
                        foreach ($items as $item) {
                            $db->where('orderItemId', $item["OrderItemId"]);
                            $db->where('orderId', $order["AmazonOrderId"]);
                            $odr = $db->getOne(TBL_AZ_ORDERS, 'az_status, status, shipmentStatus, uid');
                            if (!is_null($odr)) {
                                if (filter_var($item["IsGift"], FILTER_VALIDATE_BOOLEAN)) {
                                    $item_details["giftMessageText"] = $item["GiftMessageText"];
                                    $item_details["giftWrapLevel"] = $item["GiftWrapLevel"];
                                }

                                if (!filter_var($order["IsReplacementOrder"], FILTER_VALIDATE_BOOLEAN)) {
                                    $item_details["itemPrice"] = isset($item["ItemPrice"]["Amount"]) ? $item["ItemPrice"]["Amount"] : 0;
                                    $item_details["itemTax"] = isset($item["ItemTax"]["Amount"]) ? $item["ItemTax"]["Amount"] : 0;
                                    $item_details["shippingPrice"] = isset($item["ShippingPrice"]) ? $item["ShippingPrice"]["Amount"] : 0;
                                    $item_details["shippingTax"] = isset($item["ShippingTax"]) ? $item["ShippingTax"]["Amount"] : 0;
                                    $item_details["shippingDiscount"] = isset($item["ShippingDiscount"]) ? $item["ShippingDiscount"]["Amount"] : 0;
                                    $item_details["shippingDiscountTax"] = isset($item["ShippingDiscountTax"]) ? $item["ShippingDiscountTax"]["Amount"] : 0;
                                    $item_details["giftWrapPrice"] = isset($item["GiftWrapPrice"]) ? $item["GiftWrapPrice"]["Amount"] : 0;
                                    $item_details["giftWrapTax"] = isset($item["GiftWrapTax"]) ? $item["GiftWrapTax"]["Amount"] : 0;
                                    $item_details["promotionDiscount"] = isset($item["PromotionDiscount"]) ? $item["PromotionDiscount"]["Amount"] : 0;
                                    $item_details["promotionDiscountTax"] = isset($item["PromotionDiscountTax"]) ? $item["PromotionDiscountTax"]["Amount"] : 0;
                                    $item_details["codFee"] = isset($item["CODFee"]["Amount"]) ? $item["CODFee"]["Amount"] : 0;
                                    $item_details["codFeeDiscount"] = isset($item["CODFeeDiscount"]["Amount"]) ? $item["CODFeeDiscount"]["Amount"] : 0;
                                }

                                if (filter_var($order["IsBusinessOrder"], FILTER_VALIDATE_BOOLEAN))
                                    $item_details["priceDesignation"] = $item["PriceDesignation"];

                                if (count($items) > 1)
                                    $details['isMultiItems'] = true;

                                if ($order['OrderStatus'] == "Canceled")
                                    $details['status'] = "cancelled";

                                if (is_null($odr['fulfilmentSite']) && $order["FulfillmentChannel"] == "AFN" && ($odr['status'] == "new" && $order["OrderStatus"] == "Shipped")) {
                                    $details['fulfilmentSite'] = 'AMD2';
                                    $details['status'] = 'shipped';
                                    $details['rtdDate'] = $db->now();
                                    $details['shippedDate'] = $db->now();
                                }

                                $cancel_status = true;
                                if ($odr['status'] == "shipped" && strtolower($odr['shipmentStatus']) == "pendingpickup")
                                    $cancel_status = update_order_inventory_status($order["AmazonOrderId"], $item["OrderItemId"], $odr['uid'], $order['OrderStatus']);

                                if ($cancel_status) {
                                    $db->where('orderItemId', $item["OrderItemId"]);
                                    $db->where('orderId', $order["AmazonOrderId"]);
                                    $details = array_merge($details, $item_details);
                                    if ($db->update(TBL_AZ_ORDERS, $details)) {
                                        $log->write($details, 'amazon-orders-update');
                                        $success_count++;
                                        $return['success'][$account_id][] = $order["AmazonOrderId"];
                                    } else {
                                        $return['error'][$account_id][$order_id] = 'Unable to update order :: Error: ' . $db->getLastError();
                                    }
                                }
                            }
                        }
                    } else {
                        $return['error'][$account_id][$order_id] = 'Unable to get order details :: Error: ' . $order;
                    }
                }
            }
            $return['success_count'] = $success_count;
            echo json_encode($return);
            break;

        case 'update_afn_shipped_orders_via_csv':
            $account = get_current_account($_GET['account']);
            $sp_api = new amazon($account);

            $db->where('report_type', '_GET_AMAZON_FULFILLED_SHIPMENTS_DATA_');
            $db->where('report_status', 'submitted');
            $db->where('account_id', $account->account_id);
            $report_id = $db->getValue(TBL_AZ_REPORT, 'report_id');

            $return = array();
            $success_count = 0;
            if ($report_id) {
                $orders = $mws->GetReport($report_id);
                foreach ($orders as $order) {
                    $db->where('orderItemId', $order["amazon-order-item-id"]);
                    $db->where('orderId', $order["amazon-order-id"]);
                    $odr = $db->getOne(TBL_AZ_ORDERS, 'az_status, status, shipmentStatus');

                    if (!$odr) {
                        $return['error'][$order['amazon-order-id']] = 'Unable to find order';
                        continue;
                    }

                    if ($odr['status'] == "shipped")
                        continue;

                    $details = array(
                        "az_status" => 'shipped',
                        "fulfillmentChannel" => $order["fulfillment-channel"],
                        "fulfilmentSite" => $order['fulfillment-center-id'],

                        "shipServiceLevel" => $order["ship-service-level"],

                        "buyerName" => "",
                        "buyerEmail" => "",

                        "deliveryByDate" => date('Y-m-d H:i:s', strtotime($order["estimated-arrival-date"])),

                        "itemPrice" => $order['item-price'],
                        "itemTax" => $order['item-tax'],
                        "shippingPrice" => $order['shipping-price'],
                        "shippingTax" => $order['shipping-tax'],
                        "shippingDiscount" => $order['ship-promotion-discount'],
                        "shippingDiscountTax" => number_format(($order['ship-promotion-discount'] * 0.18), 2, '.', ''),
                        "giftWrapPrice" => $order['gift-wrap-price'],
                        "giftWrapTax" => $order['gift-wrap-tax'],
                        "promotionDiscount" => $order['item-promotion-discount'],
                        "promotionDiscountTax" => number_format(($order['item-promotion-discount'] * 0.18), 2, '.', ''),
                        "codFee" => '0.00',
                        "codFeeDiscount" => '0.00',

                        "status" => 'shipped',
                        "rtdDate" => date('Y-m-d H:i:s', strtotime($order['shipment-date'])),
                        "shippedDate" => date('Y-m-d H:i:s', strtotime($order['shipment-date'])),
                    );

                    $details['orderTotal'] = (int)($details["itemPrice"] + $details["itemTax"] + $details["shippingPrice"] + $details["shippingTax"] + $details["shippingDiscount"] + $details["shippingDiscountTax"] + $details["giftWrapPrice"] + $details["giftWrapTax"] + $details["promotionDiscount"] + $details["promotionDiscountTax"] + $details["codFee"] + $details["codFeeDiscount"]);

                    $db->where('orderItemId', $order["amazon-order-item-id"]);
                    $db->where('orderId', $order["amazon-order-id"]);
                    if ($db->update(TBL_AZ_ORDERS, $details)) {
                        $log->write($details, 'amazon-orders-update');
                        $success_count++;
                        $return['success'][] = $order["amazon-order-id"];
                    } else {
                        $return['error'][$order['amazon-order-id']] = 'Unable to update order :: Error: ' . $db->getLastError();
                    }
                }

                if (empty($return['error'])) {
                    $db->where('report_id', $report_id);
                    $db->update(TBL_AZ_REPORT, array('report_status' => 'import_completed'));
                    $return = array('type' => 'success', 'msg' => 'Successfully Updated Order Status Details');
                } else {
                    $return = array('type' => 'error', 'msg' => 'Error updating some orders', 'error' => $return);
                }
            } else {
                $return = array('type' => 'info', 'msg' => 'Invalid report request');
            }

            echo json_encode($return);
            break;

        case 'get_orders_count':
            $handler = $_GET['handler'];
            $fulfillmentChannel = $_GET['fulfillmentChannel'];
            $order_type = array("pending", "new", "packing", "rtd", "shipped", "cancelled");
            $is_returns = false;
            if ($handler == "return") {
                $order_type = array("start", "in_transit", "out_for_delivery", "delivered", "received", "claimed", "completed");
                $is_returns = true;
            }
            $orders_count = view_orders_count($order_type, $fulfillmentChannel, $is_returns);
            echo json_encode(array('orders' => $orders_count));
            break;

        case 'view_orders':
            $type = $_GET['type'];
            $fulfillmentChannel = $_GET['fulfillmentChannel'];
            $is_returns = ($_GET['handler'] == "return") ? true : false;
            $orders = view_orders($type, $fulfillmentChannel, $is_returns);

            $output = array('data' => array());
            foreach ($orders as $order) {
                $output['data'][] = create_order_view($order, $type, $is_returns);
            }
            echo json_encode($output);
            break;

        case 'schedule_pickup':
            $acc_orders = json_decode($_REQUEST['orders']);

            $response = array();
            $response_accounts = array();
            $success = 0;
            $error = 0;
            foreach ($acc_orders as $account_id => $orders) {
                $account = get_current_account($account_id);
                if (!in_array($account_id, $response_accounts))
                    $response_accounts[] = $account_id;

                $sp_api = new amazon($account);

                foreach ($orders as $order_id) {
                    $packageDetails = array(
                        'packageDimensions' => array(
                            'length' => 25,
                            'width' => 15,
                            'height' => 5,
                            'units' => 'cm'
                        ),
                        'packageWeight' => array(
                            'value' => 200,
                            'units' => 'g'
                        )
                    );

                    // ORDER WITH MULTI ITEMS
                    $db->where('orderId', $order_id);
                    $products = $db->get(TBL_AZ_ORDERS, NULL, 'sku, quantity, orderItemId');
                    $first_product = $products[0];
                    $sku = $first_product['sku'] . ' x ' . $first_product['quantity'];
                    $start = strlen($sku) < 25 ? 0 : (strlen($sku) - 25);
                    $sku = substr($sku, $start);

                    $order = $sp_api->GetOrder($order_id);
                    // var_dump($order);
                    // exit;
                    if ($order['OrderStatus'] == "Unshipped") {
                        // GET AVAILABLE SLOTS
                        $availablePickupSlots = $sp_api->ListPickupSlots($order_id, $packageDetails);
                        $packageSlot = "";
                        $prefered_slot = '2PM'; //TODO: ADD FEATURE OPTION TO CHOOSE TIME SLOT. [10AM, 1PM]
                        $prefered_slot_string = date("\TH:i:00\Z", strtotime('today ' . $prefered_slot) - 19800); // 5 hrs 30 mins OFFSET TO GMT
                        if ($availablePickupSlots) {
                            if (isset($availablePickupSlots['PickupTimeStart'])) // WHEN WE HAVE SINGLE PICKUP SLOT
                                $availablePickupSlots = array($availablePickupSlots);

                            foreach ($availablePickupSlots as $availablePickupSlot) {
                                if (strpos($availablePickupSlot['PickupTimeStart'], $prefered_slot_string) !== FALSE) { //T10:30:00Z [1PM to 4PM] || T07:30:00Z [10AM to 1PM]
                                    $packageSlot = $availablePickupSlot;
                                    $response[$order_id]['pickup_slot']['msg'] = 'Successfully got pickup slot for ' . $order_id;
                                    break;
                                }
                            }
                        } else {
                            $response[$order_id]['pickup_slot']['error'] = 'Error getting pickup slot for ' . $order_id;
                            $error++;
                            continue;
                        }

                        if (count($products) > 1) {
                            foreach ($products as $product) {
                                $packageDetails['orderItemIds'][]['orderItemId'] = $product['orderItemId'];
                                $sku = "====== MULTI ITEMS ======";
                            }
                        } else {
                            $packageDetails['orderItemIds'][]['orderItemId'] = $first_product['orderItemId'];
                        }

                        var_dump($packageDetails);
                        exit;

                        /*// SCHEDULE ORDER TO PREFERED SLOT
						$scheduleOrder = $mws->CreateScheduledPackage($order_id, $packageSlot, $sku, $packageDetails);
						if ($scheduleOrder) {
							if ($scheduleOrder['PackageStatus'] == "ReadyForPickup") {
								$details = array(
									'packageId' => $scheduleOrder['ScheduledPackageId']['PackageId'],
									'rtdDate' => $db->now(),
								);
								$db->where('orderId', $order_id);
								if ($db->update(TBL_AZ_ORDERS, $details)) {
									$response[$order_id]['schedule_pickup']['msg'] = 'Successfully scheduled pickup for ' . $order_id;
								}
							} else {
								$response[$order_id]['schedule_pickup']['error'] = 'Error scheduling pickup for ' . $order_id;
								$error++;
								continue;
							}
						} else {
							$response[$order_id]['schedule_pickup']['error'] = 'Empty response scheduling pickup for ' . $order_id;
							$error++;
							continue;
						}*/
                    } else {
                        $response[$order_id]['schedule_pickup']['msg'] = 'Pickup already scheduled for ' . $order_id;
                    }

                    // REQUEST LABEL
                    $feed = $mws->RequestOrderLabel($order_id);
                    if (isset($feed['FeedSubmissionId']) || isset($feed['message']['SubmitFeedResult']['FeedSubmissionInfo']['FeedSubmissionId'])) {
                        $feedSubmissionId = isset($feed['FeedSubmissionId']) ? $feed['FeedSubmissionId'] : $feed['message']['SubmitFeedResult']['FeedSubmissionInfo']['FeedSubmissionId'];
                        $db->where('orderId', $order_id);
                        $db->update(TBL_AZ_ORDERS, array('status' => 'packing', 'labelFeedId' => $feedSubmissionId));
                        $response[$order_id]['label_request']['msg'] = 'Successfully requested label for ' . $order_id;
                        $success++;
                    } else {
                        $response[$order_id]['label_request']['error'] = 'Empty response requesting label for ' . $order_id;
                        $error++;
                    }
                }
            }

            $response['accounts'] = $response_accounts;
            $response['success'] = $success;
            $response['error'] = $error;

            echo json_encode($response);
            break;

        case 'request_labels':
            // REQUEST LABEL
            $acc_orders = json_decode($_REQUEST['orders']);

            $response = array();
            $response_accounts = array();
            $success = 0;
            $error = 0;
            foreach ($acc_orders as $account_id => $orders) {
                $account = get_current_account($account_id);
                if (!in_array($account_id, $response_accounts))
                    $response_accounts[] = $account_id;

                $mws = new MCS\MWSClient([
                    'Marketplace_Id' => $account->marketplace_id,
                    'Seller_Id' => $account->seller_id,
                    'Access_Key_ID' => MWS_ACCESS_KEY,
                    'Secret_Access_Key' => MWS_ACCESS_SECRET,
                    'MWSAuthToken' => $account->mws_authorisation_token
                ]);

                foreach ($orders as $order_id) {
                    $db->where('orderId', $order_id);
                    $labelFeedId = $db->getValue(TBL_AZ_ORDERS, 'labelFeedId');
                    if (!$labelFeedId) {
                        $feed = $mws->RequestOrderLabel($order_id);
                        if (isset($feed['FeedSubmissionId'])) {
                            $db->where('orderId', $order_id);
                            $db->update(TBL_AZ_ORDERS, array('status' => 'packing', 'labelFeedId' => $feed['FeedSubmissionId']));
                            $response[$order_id]['label_request']['msg'] = 'Successfully requested label for ' . $order_id;
                            $success++;
                        } else {
                            $response[$order_id]['label_request']['error'] = 'Error response requesting label for ' . $order_id;
                            $response[$order_id]['label_request']['error_message'] = $feed['error_message'];
                            $error++;
                        }
                    } else {
                        $response[$order_id]['label_request']['msg'] = 'Order Label already requested for ' . $order_id;
                        $success++;
                    }
                }
            }

            $response['accounts'] = $response_accounts;
            $response['success'] = $success;
            $response['error'] = $error;

            echo json_encode($response);
            break;

        case 'get_labels':
            $acc_orders = json_decode($_REQUEST['orders']);

            $return = array();
            $response_accounts = array();
            $success = 0;
            $error = 0;
            foreach ($acc_orders as $account_id => $orders) {
                if (!in_array($account_id, $response_accounts))
                    $response_accounts[] = $account_id;

                $account = get_current_account($account_id);
                $mws = new MCS\MWSClient([
                    'Marketplace_Id' => $account->marketplace_id,
                    'Seller_Id' => $account->seller_id,
                    'Access_Key_ID' => MWS_ACCESS_KEY,
                    'Secret_Access_Key' => MWS_ACCESS_SECRET,
                    'MWSAuthToken' => $account->mws_authorisation_token
                ]);

                foreach ($orders as $order_id) {
                    $label_path = ROOT_PATH . '/labels/single/FullLabel-' . $order['orderId'] . '.pdf';
                    if (file_exists($label_path) && check_valid_pdf($label_path)) {
                        $return['success'][$account_id][] = $order_id;
                    } else {
                        $db->where('orderId', $order_id);
                        $order = $db->getOne(TBL_AZ_ORDERS, 'labelFeedId, orderId');
                        $labelFeed = $mws->GetFeedSubmissionResult($order['labelFeedId']);
                        $report_id = $labelFeed['DocumentReportReferenceID'];
                        $report = $mws->GetReport($report_id);
                        if (strncmp($report, "%PDF-", 4) === 0) {
                            $path = ROOT_PATH . '/labels/single/';
                            $filename = 'FullLabel-' . $order['orderId'] . '.pdf';
                            if (file_put_contents($path . $filename, $report)) {
                                $db->where('orderId', $order_id);
                                $db->update(TBL_AZ_ORDERS, array('labelGeneratedDate' => $db->now()));
                                $return['success'][$account_id][] = $order_id;
                            } else {
                                $return['error'][$order_id] = 'unable to save file';
                            }
                        } else {
                            $return['error'][$order_id] = 'invalid response for ' . $order['orderId'];
                        }
                    }
                }
            }
            $return['accounts'] = $response_accounts;

            echo json_encode($return);
            break;

        case 'requestTrackingId': // REQUEST REPORT FOT TRACKING ID VIA REPORT
            $account = get_current_account($_REQUEST['account']);
            $mws = new MCS\MWSClient([
                'Marketplace_Id' => $account->marketplace_id,
                'Seller_Id' => $account->seller_id,
                'Access_Key_ID' => MWS_ACCESS_KEY,
                'Secret_Access_Key' => MWS_ACCESS_SECRET,
                'MWSAuthToken' => $account->mws_authorisation_token
            ]);

            $repStartDate = new DateTime("today -1 day 01 PM");
            $repEndDate = new DateTime("tomorrow + 10days  12:59 PM");
            if (date('H', time()) < '13') {
                $repStartDate = new DateTime("yesterday -1 day 01 PM");
                $repEndDate = new DateTime("today + 10days 12:59 PM");
            }
            $report = $mws->RequestReport('_GET_EASYSHIP_WAITING_FOR_PICKUP_', $repStartDate, $repEndDate);
            if ($report) {
                $details = array(
                    'report_type' => '_GET_EASYSHIP_WAITING_FOR_PICKUP_',
                    'report_id' => $report,
                    'report_status' => 'submitted',
                    'account_id' => $account->account_id,
                    'createdDate' => $db->now()
                );
                if ($db->insert(TBL_AZ_REPORT, $details))
                    $return = array('type' => 'success', 'msg' => 'Tracking Report requested successfuly. Report ID ' . $report);
                else
                    $return = array('type' => 'error', 'msg' => 'Unable to add report details');
            }

            echo json_encode($return);
            break;

        case 'getTrackingId':
            $account = get_current_account($_GET['account']);
            $mws = new MCS\MWSClient([
                'Marketplace_Id' => $account->marketplace_id,
                'Seller_Id' => $account->seller_id,
                'Access_Key_ID' => MWS_ACCESS_KEY,
                'Secret_Access_Key' => MWS_ACCESS_SECRET,
                'MWSAuthToken' => $account->mws_authorisation_token
            ]);

            $db->where('report_type', '_GET_EASYSHIP_WAITING_FOR_PICKUP_');
            $db->where('report_status', 'submitted');
            $db->where('account_id', $account->account_id);
            $report_id = $db->getValue(TBL_AZ_REPORT, 'report_id');
            if ($report_id) {
                $shipments = $mws->GetReport($report_id);
                if ($shipments && !empty($shipments)) {
                    foreach ($shipments as $shipment) {
                        $details = array(
                            'trackingID' => $shipment['tracking-id'],
                            'courierName' => $shipment['carrier']
                        );
                        $db->where('orderId', $shipment['order-id']);
                        $db->update(TBL_AZ_ORDERS, $details);
                    }
                    $db->where('report_id', $report_id);
                    $db->update(TBL_AZ_REPORT, array('report_status' => 'import_completed'));
                    $return = array('type' => 'success', 'msg' => 'Successfully Updated Tracking Details');
                } else if (empty($shipments)) {
                    $return = array('type' => 'info', 'msg' => 'No data returned or report not yet ready');
                } else {
                    $return = array('type' => 'info', 'msg' => 'Tracking details not generated');
                }
            } else {
                $return = array('type' => 'info', 'msg' => 'Invalid report request');
            }

            echo json_encode($return);
            break;

        case 'generate_labels':
            $account = get_current_account($_REQUEST['account']);
            $orders = explode(',', $_REQUEST['order_ids']);
            $path = ROOT_PATH . '/labels/';
            $single_label_path = $path . 'single/';
            $filename = 'Amazon-Labels-' . date('d') . '-' . date('M') . '-' . date('Y') . '-' . str_replace(' ', '', $account->account_name);

            $mpdf = new \Mpdf\Mpdf();
            foreach ($orders as $order) {
                $shipping_label = $single_label_path . 'FullLabel-' . $order . '.pdf';
                // if (check_valid_pdf()){
                // }
                $mpdf->AddPage();
                $mpdf->setSourceFile($shipping_label);
                $tplId = $mpdf->ImportPage(1);
                $mpdf->UseTemplate($tplId);
            }
            $mpdf->SetTitle($filename);
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->Output($filename . '.pdf', 'I');
            break;

        case 'mark_orders':
            switch ($_REQUEST['type']) {
                case 'rtd':
                    $account_id = $_POST['account_id'];
                    $tracking_id = $_POST['tracking_id'];
                    $response = update_orders_to_rtd($account_id, $tracking_id);

                    $return[0]['type'] = "error";
                    if ($response && $response['type'] == "success") {
                        $return[0]['type'] = "success";
                    }
                    $title = ($response && $response["title"] != "" ? $response["title"] : "Title Not Found");
                    $order_item_id = ($response && $response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
                    $message = ($response && $response["order_item_id"] != "" ? $response["message"] : "Not Found");
                    $return[0]['content'] = '
						<li class="order-item-card">
							<div class="order-title">' . $title . '</div>
							<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $tracking_id . '</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $message . '</span></div>
						</li>';

                    echo json_encode($return);
                    break;

                case 'shipped':
                    $account_id = $_POST['account_id'];
                    $tracking_id = $_POST['tracking_id'];
                    $response = update_orders_to_shipped($account_id, $tracking_id);

                    $return[0]['type'] = "error";
                    if ($response && $response['type'] == "success") {
                        $return[0]['type'] = "success";
                    }
                    $title = ($response && $response["title"] != "" ? $response["title"] : "Title Not Found");
                    $order_item_id = ($response && $response["order_item_id"] != "" ? $response["order_item_id"] : "Not Found");
                    $message = ($response && $response["order_item_id"] != "" ? $response["message"] : "Not Found");
                    $return[0]['content'] = '
						<li class="order-item-card">
							<div class="order-title">' . $title . '</div>
							<div class="title"><div class="field">ITEM ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $tracking_id . '</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $message . '</span></div>
						</li>';

                    echo json_encode($return);
                    break;

                case 'return_received':
                    $tracking_id = $_POST['tracking_id'];
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
							<div class="title"><div class="field">ORDER ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $tracking_id . '</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
						</li>';
                        $i++;
                    }
                    echo json_encode($return);
                    break;

                case 'return_completed':
                    $tracking_id = $_REQUEST['tracking_id'];
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
							<div class="title"><div class="field">ORDER ID</div> <span class="title-value2">' . $order_item_id . '</span></div>
							<div class="title"><div class="field">AWB NO.</div> <span class="title-value2">' . $tracking_id . '</span></div>
							<div class="title"><div class="field">REASON</div> <span class="title-value2">' . $response["message"] . '</span></div>
						</li>';
                        $i++;
                    }
                    echo json_encode($return);
            }
            break;

            // SCAN & SHIP
        case 'scan_ship':
            $tracking_id = $_GET['tracking_id'];

            // MULTI ITEMS + MULTI QTY
            // MULTI ITEMS	[FMPC1448559273]
            // MULTI QTY	[]
            // MULTI ITEMS + MULTI QTY (DIFFERENT SKU SAME PARENT SKU)
            // MULTI ITEMS (DIFFERENT SKU SAME PARENT SKU)	[2703436082]
            // COMBO + MULTI ITEMS + MULTI QTY
            // COMBO + MULTI ITEMS
            // COMBO + MULTI QTY
            // COMBO + MULTI ITEMS + MULTI QTY (DIFFERENT SKU SAME PARENT SKU)
            // COMBO + MULTI ITEMS (DIFFERENT SKU SAME PARENT SKU)

            $db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.asin", "LEFT");
            $db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
            $db->where('o.trackingID', $tracking_id);
            $db->where('o.status', "RTD");
            $orders = $db->ObjectBuilder()->get(TBL_AZ_ORDERS . ' o', null, "o.orderId, o.account_id, o.quantity, o.uid, o.sku, p.corrected_sku, p.alias_id");
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
                $multi_items = ($db->count > 1) ? true : false;
                $fulfillable_quantity = 0;
                foreach ($orders as $order) {
                    $db->where('marketplace', 'amazon');
                    $db->where('account_id', $order->account_id);
                    $db->where('alias_id', $order->alias_id);
                    $products = $db->getOne(TBL_PRODUCTS_ALIAS, array('pid, cid'));
                    if ($products['cid']) {
                        $db->where('cid', $products['cid']);
                        $products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
                        $products = json_decode($products['pid']);
                        foreach ($products as $product) {
                            $uids = json_decode($order->uid, true);
                            $uids_count = is_null($uids) ? 0 : count($uids);
                            $db->where('pid', $product);
                            $item = $db->getOne(TBL_PRODUCTS_MASTER, 'pid as item_id, sku as parent_sku');
                            $item['orderId'] = $order->orderId;
                            $item['uids'] = []; //is_null($uids) ? [] : $uids;
                            $item['quantity'] = $order->quantity;
                            $item['scanned_qty'] = $uids_count;
                            $item['scan'] = ($order->quantity == $uids_count) ? true : false;
                            $item_index = isset($items[$order->orderId]) ? array_search($item['item_id'], array_column($items[$order->orderId], 'item_id')) : null;
                            $corrected_sku = "";
                            if (!is_null($order->corrected_sku)) {
                                $corrected_sku = '<div class="form-group item-info corrected_sku">
													<div class="col-md-3">Corrected SKU:</div>
													<div class="col-md-9 sku">' . $order->corrected_sku . '</div>
												</div>';
                            }
                            $all_uids = "";
                            if (!is_null($uids)) {
                                $all_uids = implode(', ', $uids);
                            }
                            $all_success = "hide";
                            if ($order->quantity == $uids_count)
                                $all_success = "";

                            if (is_null($item_index) || $item_index === FALSE) {
                                $items[$order->orderId][] = $item;
                                $fulfillable_quantity += $order->quantity;
                                $content[$order->orderId][$item['item_id']] = '<div class="item_group item_' . $item["item_id"] . '_' . $order->orderItemId . '" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg" width="100" />
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
                                $items[$order->orderId][$item_index]['quantity'] += $order->quantity;
                                $content[$order->orderId][$item['item_id']] = '<div class="item_group item_' . $item["item_id"] . '_' . $order->orderId . '" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg" width="100"/>
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
														<div class="col-md-9">' . ($items[$order->orderId][$item_index]['quantity'] > 1 ? "<sapn class='label label-danger'>" . $items[$order->orderId][$item_index]['quantity'] . "</span>" : $items[$order->orderId][$item_index]['quantity']) . '</div>
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
                        $fulfillable_quantity = count(json_decode($order->subItems)[0]->packages);
                        if ($order->ordered_quantity != $order->quantity)
                            $fulfillable_quantity = $order->quantity;
                        $uids = json_decode($order->uid, true);
                        $uids_count = is_null($uids) ? 0 : count($uids);
                        $db->where('pid', $products['pid']);
                        $item = $db->getOne(TBL_PRODUCTS_MASTER, 'pid as item_id, sku as parent_sku');
                        $item['orderId'] = $order->orderId;
                        $item['uids'] = []; //is_null($uids) ? [] : $uids;
                        $item['quantity'] = $order->quantity;
                        $item['scanned_qty'] = $uids_count;
                        $item['scan'] = ($order->quantity == $uids_count) ? true : false;
                        // $item_index = array_search($item['item_id'], array_column($items, 'item_id'));
                        // $order_index = array_search($item['orderId'], array_column($items, 'orderId'));
                        $item_index = isset($items[$order->orderId]) ? array_search($item['item_id'], array_column($items[$order->orderId], 'item_id')) : null;

                        $corrected_sku = "";
                        if (!is_null($order->corrected_sku)) {
                            $corrected_sku = '<div class="form-group item-info corrected_sku">
												<div class="col-md-3">Corrected SKU:</div>
												<div class="col-md-9 sku">' . $order->corrected_sku . '</div>
											</div>';
                        }
                        $all_uids = "";
                        if (!is_null($uids)) {
                            $all_uids = implode(', ', $uids);
                        }
                        $all_success = "hide";
                        if ($order->quantity == $uids_count)
                            $all_success = "";

                        // if ($item_index === FALSE || $order_index === FALSE){
                        if (is_null($item_index) || $item_index === FALSE) {
                            $items[$order->orderId][] = $item;
                            $content[$order->orderId][$item['item_id']] = '<div class="item_group item_' . $item['item_id'] . '_' . $item['orderId'] . '" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg" width="100"/>
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
                            $items[$order->orderId][$item_index]['quantity'] += $order->quantity;
                            $content[$order->orderId][$item['item_id']] = '<div class="item_group item_' . $item['item_id'] . '_' . $item['orderId'] . '" data-scanned="false">
											<div class="form-group">
												<div class="col-md-2">
													<div class="product_image">
														<img src="' . IMAGE_URL . '/uploads/products/product-' . $item["item_id"] . '.jpg" width="100"/>
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
														<div class="col-md-9">' . ($items[$order->orderId][$item_index]['quantity'] > 1 ? "<sapn class='label label-danger'>" . $items[$order->orderItemId][$item_index]['quantity'] . "</span>" : $items[$item_index]['quantity']) . '</div>
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
                }
                foreach ($content as $html) {
                    $content_html .= implode('', $html);
                }
                $response = array("type" => "success", "items" => $items, "fulfillable_quantity" => $fulfillable_quantity, "content" => preg_replace('/\>\s+\</m', '><', $content_html));
            } else {
                $db->where('trackingID', $tracking_id);
                $order = $db->ObjectBuilder()->getOne(TBL_AZ_ORDERS, "status, az_status");
                if ($order) {
                    if ($order->az_status == "RETURN_REQUESTED" || $order->az_status == "RETURNED" || $order->az_status == "CANCELLED" || $order->status == "CANCELLED") {
                        $response = array("type" => "info", "msg" => "Order Cancelled<br />DO NOT PROCESS");
                    } else {
                        $response = array("type" => "error", "msg" => "Order status is " . strtoupper($order->status));
                    }
                } else {
                    $response = array("type" => "error", "msg" => "Invalid Tracking ID");
                }
            }
            echo json_encode($response);
            break;

        case 'get_uid_details':
            $uid = $_GET['uid'];
            $db->where('inv_status', 'qc_verified');
            $db->where('inv_id', $uid);
            $item_id = $db->getValue(TBL_INVENTORY, 'item_id');
            if ($item_id)
                echo json_encode(array('type' => 'success', 'item_id' => $item_id));
            else {
                $db->where('inv_id', $uid);
                $status = $db->getValue(TBL_INVENTORY, 'inv_status');
                echo json_encode(array('type' => 'error', 'msg' => 'Product status is ' . strtoupper($status)));
            }
            break;

        case 'sideline_product':
            $data  = array(
                "logType" => "Order Process Sidelined",
                "identifier" => $_REQUEST["orderId"],
                "userId" => $current_user['userID']
            );

            if ($db->insert(TBL_PROCESS_LOG, $data))
                echo json_encode(array('type' => 'success', 'message' => 'Order successfully sidelined'));
            else
                echo json_encode(array('type' => 'error', 'message' => 'Log Error : ' . $db->getLastError()));
            break;

        case 'save_scan_ship':
            $scanned_orders = json_decode($_REQUEST['scanned_items']);
            $update_status = array();
            foreach ($scanned_orders as $orderId => $uids) {
                $db->where('orderId', $orderId);
                $order_detail = array(
                    'uid' => json_encode($uids),
                    'status' => 'shipped',
                    'shippedDate' => date('Y-m-d H:i:s'),
                );
                if ($db->update(TBL_AZ_ORDERS, $order_detail)) {
                    foreach ($uids as $uid) {
                        // CHANGE UID STATUS
                        $inv_status = $stockist->update_inventory_status($uid, 'sales');
                        // ADD TO LOG
                        $log_status = $stockist->add_inventory_log($uid, 'sales', 'Amazon Sales :: ' . $orderId);
                        // ADD PROCESS LOG
                        $data  = array(
                            "logType" => "Order Process End",
                            "identifier" => $orderId,
                            "userId" => $current_user['userID']
                        );
                        $log_status = $db->insert(TBL_PROCESS_LOG, $data);
                        if ($inv_status && $log_status)
                            $update_status[$orderId] = true;
                        else
                            $update_status[$orderId] = false;
                    }
                } else {
                    $update_status[$orderId] = false;
                }
            }

            if (in_array(false, $update_status))
                $return = array("type" => "error", "msg" => "Error Updating order", "error" => $update_status, "error_msg" => $db->getLastError());
            else
                $return = array("type" => "success", "msg" => "Order successfull shipped");

            echo json_encode($return);
            break;

            // SEARCH ORDER
        case 'search_order':
            $search_key = $_REQUEST['key'];
            $search_by = $_REQUEST['by'];

            // $db->join(TBL_FK_RETURNS ." r", "o.orderItemId=r.orderItemId", "LEFT");
            $db->joinWhere('r.r_expectedDate', NULL, 'IS NOT'); // Delivery not expected. Promised qty not delivered/
            // $db->join(TBL_FK_PAYMENTS ." pa", "pa.orderItemId=o.orderItemId", "LEFT");
            // $db->join(TBL_FK_CLAIMS." c", "r.returnId=c.returnId", "LEFT");
            $db->join(TBL_PRODUCTS_ALIAS . " p", "p.mp_id=o.fsn", "LEFT");
            $db->joinWhere(TBL_PRODUCTS_ALIAS . " p", "p.account_id=o.account_id");
            $db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
            $db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");

            if ($search_by == "uid") {
                $db->where('o.uid', '%' . $search_key . '%', 'LIKE');
            } else if ($search_by == 'trackingId') {
                $db->where('o.pickupDetails', '%' . $search_key . '%', 'LIKE');
                $db->orWhere('o.deliveryDetails', '%' . $search_key . '%', 'LIKE');
                $db->orWhere('r.r_trackingId', '%' . $search_key . '%', 'LIKE');
            } else if ($search_by == 'returnId') {
                $db->where('r.' . $search_by, $search_key);
            } else if ($search_by == 'claimID') {
                $db->where('c.' . $search_by, '%' . $search_key . '%', 'LIKE');
                $db->orWhere('c.claimNotes', '%' . $search_key . '%', 'LIKE');
                $db->orWhere('o.settlementNotes', '%' . $search_key . '%', 'LIKE');
            } else {
                $db->where('o.' . $search_by, $search_key);
            }

            $db->orderBy('o.insertDate', "DESC");
            $db->orderBy('r.insertDate', "DESC");
            // $db->groupBy('OID');
            $orders = $db->ObjectBuilder()->get(TBL_AZ_ORDERS . ' o', null, "o.*, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as produtImage, COALESCE(p.corrected_sku, p.sku) as sku");
            // $orders = $db->ObjectBuilder()->get(TBL_AZ_ORDERS .' o', null, "o.orderItemId as OID, r.returnId as RID, COALESCE(pc.thumb_image_url, pm.thumb_image_url) as produtImage, o.*, r.*, c.*, COALESCE(p.corrected_sku, p.sku) as sku");
            // echo $db->getLastQuery();
            // exit;
            // var_dump($orders);

            $return['type'] = "success";
            $return['content'] = "";
            $collapse_id = 1;
            foreach ($orders as $order) {
                // $order_type = $order->replacementOrder ? 'Replacement Order' : strtoupper($order->paymentType);
                $address = json_decode($order->deliveryAddress);
                $pickupDetails = json_decode($order->pickupDetails);
                $deliveryDetails = json_decode($order->deliveryDetails);
                $on_hold = ($order->hold ? '<i class="fas fa-hand-paper"></i>' : '');
                $flag_class = ($order->is_flagged == true ? ' active' : '');

                // var_dump($accounts);

                $account = findObjectById($accounts, 'account_id', $order->account_id);

                if (strpos($deliveryDetails->vendorName, 'E-Kart') !== FALSE)
                    $delivery_link = 'https://www.ekartlogistics.com/shipmenttrack/' . $deliveryDetails->trackingId;
                elseif (strpos($deliveryDetails->vendorName, 'Delhivery') !== FALSE)
                    $delivery_link = 'https://www.delhivery.com/track/package/' . $deliveryDetails->trackingId;
                elseif (strpos($deliveryDetails->vendorName, 'Ecom') !== FALSE)
                    $delivery_link = 'https://ecomexpress.in/tracking/?awb_field=' . $deliveryDetails->trackingId;
                elseif (strpos($deliveryDetails->vendorName, 'Dotzot') !== FALSE)
                    $delivery_link = 'https://instacom.dotzot.in/GUI/Tracking/Track.aspx?AwbNos=' . $deliveryDetails->trackingId;

                if (strtolower($order->status) != "new") {
                    $logistic_details = '
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Logistic Partner:&nbsp;</span>
																		<span class="order_date">' . $deliveryDetails->vendorName . '</span>
																		<span class="card-label">Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="' . $delivery_link . '" target="_blank">' . $deliveryDetails->trackingId . '</a></span>
																	</div>';

                    if ($pickupDetails->trackingId != $deliveryDetails->trackingId) {
                        if (strpos($pickupDetails->vendorName, 'E-Kart') !== FALSE)
                            $pickup_link = 'https://www.ekartlogistics.com/shipmenttrack/' . $pickupDetails->trackingId;
                        elseif (strpos($pickupDetails->vendorName, 'Delhivery') !== FALSE)
                            $pickup_link = 'https://www.delhivery.com/track/package/' . $pickupDetails->trackingId;
                        elseif (strpos($pickupDetails->vendorName, 'Ecom') !== FALSE)
                            $pickup_link = 'https://ecomexpress.in/tracking/?awb_field=' . $pickupDetails->trackingId;
                        elseif (strpos($deliveryDetails->vendorName, 'Dotzot') !== FALSE)
                            $pickup_link = 'https://instacom.dotzot.in/GUI/Tracking/Track.aspx?AwbNos=' . $pickupDetails->trackingId;

                        $logistic_details = '
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Pickup Logistic Partner:&nbsp;</span>
																		<span class="order_date">' . $pickupDetails->vendorName . '</span>
																		<span class="card-label">Pickup Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="' . $pickup_link . '" target="_blank">' . $pickupDetails->trackingId . '</a></span>
																	</div>
																	<div class="col-md-12 sub-header">
																		<span class="card-label">Logistic Partner:&nbsp;</span>
																		<span class="order_date">' . $deliveryDetails->vendorName . '</span>
																		<span class="card-label">Tracking ID:&nbsp;</span>
																		<span class="order_date"><a class="font-rblue" href="' . $delivery_link . '" target="_blank">' . $deliveryDetails->trackingId . '</a></span>
																	</div>';
                    }

                    $delivery_details = '<div class="col-md-12 sub-header">
																		<span class="card-label">Delivery Details:&nbsp;</span>
																		<span class="order_date">' . $address->firstName . ' ' . $address->lastName . ', ' . $address->city . ', ' . $address->state . '</span>
																		<span class="card-label">Contact No.:&nbsp;</span>
																		<span class="order_date">+91' . $address->contactNumber . '</span>
																	</div>';
                }
                $order_returns = '<dt class="font-rlight font-rgrey">No Return Details Found</dt>';
                if ($order->RID != NULL) {
                    $order_returns = '
																	<dl><dt class="font-rlight font-rgrey">Return Status</dt>
																	<dd class="r_status">' . ($order->r_shipmentStatus == "" ? "NA" : strtoupper($order->r_shipmentStatus)) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return Action</dt>
																	<dd class="r_sub_reason">' . ($order->r_action == "" ? "NA" : strtoupper($order->r_action)) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Tracking ID</dt>
																	<dd class="r_sub_reason">' . ($order->r_trackingId == "" ? "NA" : $order->r_trackingId) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return ID</dt>
																	<dd class="r_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/return_orders/' . $account->locationId . '/search?type=return_id&id=' . $order->RID . '">' . $order->RID . '</a></dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Created Date</dt>
																	<dd class="r_sub_reason">' . ($order->r_createdDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->r_createdDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Delivered Date</dt>
																	<dd class="r_sub_reason">' . ($order->r_deliveredDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->r_deliveredDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Received Date</dt>
																	<dd class="r_sub_reason">' . ($order->r_receivedDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->r_receivedDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Completed Date</dt>
																	<dd class="r_sub_reason">' . ($order->r_completionDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->r_completionDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Return Type</dt>
																	<dd class="r_status">' . $order->r_source . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Reason</dt>
																	<dd class="r_reason">' . $order->r_reason . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Sub-Reason</dt>
																	<dd class="r_sub_reason">' . $order->r_subReason . '</dd></dl>
																	<dl><dt class="font-rblue comment-label" data-placement="left" data-original-title="" title="">Buyer Comment</dt>
																	<dd class="r_comment">' . str_replace('\n', "<br />", $order->r_comments) . '</dd></dl>';
                }

                $order_claims = '<dt class="font-rlight font-rgrey">No Claim Details Found</dt>';
                $claim_status = "";
                $approved_claim = "";
                if (!is_null($order->r_approvedSPFId)) {
                    $claim_status = "_INCOMPLETE_SPF";
                    $approved_claim = '<dl><dt class="font-rlight font-rgrey">Approved Claim ID</dt><dd class="c_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/' . $order->r_approvedSPFId . '/SELLER_SELF_SERVE/">' . $order->r_approvedSPFId . '</a></dd></dl>';
                }
                if (isset($order->claimID) && $order->claimID != NULL) {
                    $order_claims = '
																	<dl><dt class="font-rlight font-rgrey">Claim Status</dt>
																	<dd class="c_status">' . strtoupper($order->claimStatus) . $claim_status . '</dd></dl>
																	' . $approved_claim . '
																	<dl><dt class="font-rlight font-rgrey">Claim ID</dt>
																	<dd class="c_id"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/viewIssueManagementTicket/' . $order->claimID . '/SELLER_SELF_SERVE/">' . $order->claimID . '</a></dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Date</dt>
																	<dd class="c_date">' . ($order->claimDate == "" ? "NA" : date("M d, Y h:i A", strtotime($order->claimDate))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Updated</dt>
																	<dd class="c_date">' . ($order->lastUpdateAt == "" ? "NA" : date("M d, Y h:i A", strtotime($order->lastUpdateAt))) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Reason</dt>
																	<dd class="c_reason">' . $order->claimProductCondition . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Reimbursement</dt>
																	<dd class="c_reimbursement">&#x20b9; ' . (($order->claimReimbursmentAmount == "" || is_null($order->claimReimbursmentAmount)) ? "0.00" : $order->claimReimbursmentAmount) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Comments</dt>
																	<dd class="c_comments">' . ($order->claimComments == "" ? "NA" : $order->claimComments) . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Claim Notes</dt>
																	<dd class="c_comments">' . ($order->claimNotes == "" ? "NA" : str_replace("\n", "<br />", $order->claimNotes)) . '</dd></dl>';
                }

                $fk = new connector_flipkart($account);
                $payment_details = $fk->payments->get_difference_details($order->OID);
                $settlement_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
                foreach ($payment_details['expected_payout'] as $key => $value) {
                    if ($key == "settlement_date")
                        continue;

                    if ($key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
                        $settlement_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p>' . strtoupper($value) . '</span>';
                    else
                        $settlement_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p> &#x20b9;' . $value . '</span>';
                }
                $received_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
                foreach ($payment_details['settlements'] as $settlement) {
                    foreach ($settlement as $key => $value) {
                        // if ($key == "settlement_date")
                        // 	continue;

                        if ($key == "settlement_date" || $key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
                            $received_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p>' . strtoupper($value) . '</span>';
                        else
                            $received_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p> &#x20b9;' . $value . '</span>';
                    }
                }
                $difference_details = '<strong><span><p>Fees Type</p>Amount</span></strong>';
                foreach ($payment_details['difference'] as $key => $value) {
                    if ($key == "settlement_date")
                        continue;

                    if ($key == 'payment_type' || $key == 'shipping_zone' || $key == 'shipping_slab')
                        $difference_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p>' . strtoupper($value) . '</span>';
                    else
                        $difference_details .= '<span><p>' . ucwords(str_replace('_', ' ', $key)) . '</p> &#x20b9;' . $value . '</span>';
                }
                $order_payments = '
																	<dl><dt class="font-rlight font-rgrey">Payment Status</dt>
																	<dd class="p_status">' . ($order->settlementStatus == 0 ? "PENDING" : "SETTLED") . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Expected Payout</dt>
																	<dd class="p_settlement">&#x20b9; ' . $fk->payments->calculate_actual_payout($order->OID) . ' <a data-target="#p_settlement_details_' . $collapse_id . '" data-toggle="collapse">(View Details)</a></dd>
																	<dd id="p_settlement_details_' . $collapse_id . '" class="accounts_details collapse">' . $settlement_details . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Amount Settled</dt>
																	<dd class="p_received">&#x20b9; ' . $fk->payments->calculate_net_settlement($order->OID) . ' <a data-target="#p_received_details_' . $collapse_id . '" data-toggle="collapse">(View Details)</a></dd><!-- SHOW DROPDWON BREAKUP -->
																	<dd id="p_received_details_' . $collapse_id . '" class="accounts_details collapse">' . $received_details . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Difference</dt>
																	<dd class="p_difference">&#x20b9; ' . $payment_details['difference']['total'] . ' <a data-target="#p_difference_details_' . $collapse_id . '" data-toggle="collapse">(View Details)</a></dd>
																	<dd id="p_difference_details_' . $collapse_id . '" class="accounts_details collapse">' . $difference_details . '</dd></dl>
																	<dl><dt class="font-rlight font-rgrey">Settlement Notes</dt>
																	<dd class="p_notes">' . (empty($order->settlementNotes) ? "NA" : $order->settlementNotes) . '</dd></dl>';



                $return['content'] .= '
													<article class="return-order-card clearfix detail-view">
														<div class="col-md-12 order-details-header">
															<div class="col-md-2 product-image"><div class="bookmark"><a class="flag' . $flag_class . '" data-itemid="' . $order->OID . '" href="#"><i class="fa fa-bookmark"></i></a></div><img src="' . IMAGE_URL . '/uploads/products/' . $order->produtImage . '" onerror="this.src=\'https://via.placeholder.com/150x150\'"></div>
															<div class="col-md-8 order-details">
																<header class="col-md-7 return-card-header font-rlight">
																	<div>
																		<h4>
																			<div class="article-title font-rblue font-rreguler">' . $order->title . '&nbsp;&nbsp;<span class="label label-success">' . $account->account_name . '</span></div>
																		</h4>
																	</div>
																</header>
																<div class="col-md-12 sub-header">
																	<span class="card-label">Shipment ID:&nbsp;</span>
																	<span class="order_id">' . $order->shipmentId . '</span>
																	<span class="card-label">Rating URL:&nbsp;</span>
																	<input id="p2" class="cpy_text" alt="Click to Copy Rating URL" value="https://www.flipkart.com/products/write-review/itme?pid=' . $order->fsn . '&lid=' . $order->listingId . '&source=od_sum&otracker=orders" />
																	<span class="order_id rating_url"><button title="Click to Copy Rating URL" class="cpy_btn btn btn-xs btn-default" data-clipboard-target="#p2"><i class="fa fa-copy"></i></button></span>
																</div>
																<div class="col-md-12 sub-header">
																	<span class="card-label">SKU:&nbsp;</span>
																	<span class="order_id">' . $order->sku . '</span>
																	<span class="card-label">FSN:&nbsp;</span>
																	<span class="order_item_id"><a class="font-rblue" href="https://www.flipkart.com/product/p/itme?pid=' . $order->fsn . '" target="_blank">' . $order->fsn . '</a></span>
																	<span class="card-label">HSN:&nbsp;</span>
																	<span class="order_type">' . $order->hsn . '</span>
																	<span class="card-label">UID:&nbsp;</span>
																	<span class="order_type">' . (is_null($order->uid) ? "NA" : $order->uid) . '</span>
																</div>
																<div class="col-md-12 sub-header">
																	<span class="card-label">Quantity:&nbsp;</span>
																	<span class="quantity">' . $order->quantity . '/' . $order->ordered_quantity . '</span>
																	<span class="card-label">Price:&nbsp;</span>
																	<span class="sellingPrice">' . $order->sellingPrice . '</span>
																	<span class="card-label">Shipping:&nbsp;</span>
																	<span class="shippingCharge">' . $order->shippingCharge . '</span>
																	<span class="card-label">Total:&nbsp;</span>
																	<span class="totalPrice">' . $order->totalPrice . '</span>
																	<span class="card-label">Fulfilment Type:&nbsp;</span>
																	<span class="order_type">' . $order->order_type . '</span>
																</div>
																' . $logistic_details . '
																' . $delivery_details . '
															</div>
															<div class="col-md-2 order-incidents">
																<dl class="status">
																	<dd class="font-rlight font-rgrey">Order Incidents:&nbsp;</dd>
																	<dd class="amount">' . strtoupper($order->status) . ' ' . $on_hold . '</dd>
																</dl>
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
																	<dd class="amount"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/' . $account->locationId . '/main/search?type=order_id&id=' . $order->orderId . '">' . $order->orderId . '</a></dd>
																</dl>
																<dl class="orderItemId">
																	<dt class="font-rlight font-rgrey">Order Item ID:&nbsp;</dt>
																	<dd class="amount"><a class="font-rblue" target="_blank" href="https://seller.flipkart.com/index.html#dashboard/shipment/activeV2/' . $account->locationId . '/main/search?type=order_item_id&id=' . $order->OID . '">' . $order->OID . '</a></dd>
																</dl>
																<dl class="price">
																	<dt class="font-rlight font-rgrey">Order Type:&nbsp;</dt>
																	<dd class="amount">' . $order_type . '</dd>
																</dl>
																<dl class="quantity">
																	<dt class="font-rlight font-rgrey">Order Date:&nbsp;</dt>
																	<dd class="qty">' . date("M d, Y h:i A", strtotime($order->orderDate)) . '</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">Label Generated:&nbsp;</dt>
																	<dd class="amount">' . ($order->labelGeneratedDate == "0000-00-00 00:00:00" ? "NA" : date("M d, Y h:i A", strtotime($order->labelGeneratedDate))) . '</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">RTD Date:&nbsp;</dt>
																	<dd class="amount">' . ($order->rtdDate == "0000-00-00 00:00:00" ? "NA" : date("M d, Y h:i A", strtotime($order->rtdDate))) . '</dd>
																</dl>
																<dl class="fsn">
																	<dt class="font-rlight font-rgrey">Shipped Date:&nbsp;</dt>
																	<dd class="amount">' . ($order->shippedDate == "0000-00-00 00:00:00" ? "NA" : date("M d, Y h:i A", strtotime($order->shippedDate))) . '</dd>
																</dl>
																<dl class="sku">
																	<dt class="font-rlight font-rgrey">Invoice Date:&nbsp;</dt>
																	<dd class="amount">' . ($order->invoiceDate == "0000-00-00 00:00:00" ? "Not Generated" : date("M d, Y h:i A", strtotime($order->invoiceDate))) . '</dd>
																</dl>
																<dl class="sku">
																	<dt class="font-rlight font-rgrey">Invoice Number:&nbsp;</dt>
																	<dd class="amount">' . ($order->invoiceNumber == "" ? "Not Generated" : $order->invoiceNumber) . '</dd>
																</dl>
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

                $collapse_id++;
            }

            echo json_encode($return);
            break;

            // RETURNS
        case 'import_returns':
            // var_dump($_POST);
            // var_dump($_FILES);
            $handle = new \Verot\Upload\Upload($_FILES['returns_csv']);
            $is_flex = (int)$_REQUEST['is_flex'];
            if ($handle->uploaded) {
                $handle->file_overwrite = true;
                $handle->dir_auto_create = true;
                $handle->file_new_name_ext = "";
                $handle->process(ROOT_PATH . "/uploads/amazon_returns/");
                $handle->clean();
                $file = ROOT_PATH . "/uploads/amazon_returns/" . $handle->file_dst_name;

                $returns = csv_to_array($file);
                $insert = 0;
                $exists = 0;
                $update = 0;
                $error = array();
                foreach ($returns as $return) {
                    $deliveredDate = NULL;
                    if ($is_flex) {
                        $orderId = $return['Customer Order ID'];
                        $rmaId = $return['RMA ID'];
                        $shipmentStatus = 'start';
                        $status = $return['Return Status'];
                        if ($status == "Successfully completed return" || $status == "Successfully performed handover (POD)") {
                            $shipmentStatus = "delivered";
                            $deliveredDate = date('Y-m-d H:i:s', strtotime($return['Last Updated On']));
                        }
                        if ($status == "Stop and return request by Amazon" || $status == "Return initiated")
                            $shipmentStatus = "start";
                        if ($status == "In-transit to Seller Flex node")
                            $shipmentStatus = "in_transit";
                        if ($status == "Out for delivery to Seller Flex node")
                            $shipmentStatus = "out_for_delivery";
                        if ($status == "Customer cancelled pick-up")
                            $shipmentStatus = "cancelled";
                    } else {
                        $orderId = $return['Order ID'];
                        $rmaId = $return['Amazon RMA ID'];
                        $shipmentStatus = 'start';
                        if (!empty(trim($return['Return delivery date']))) {
                            $shipmentStatus = "delivered";
                            $deliveredDate = date('Y-m-d H:i:s', strtotime($return['Return delivery date']));
                        }

                        $return_type = "";
                        if ($return['Return type'] == "C-Returns")
                            $return_type = "CUSTOMER_RETURN";
                        else if ($return['Return type'] == "Rejected")
                            $return_type = "UNDELIVERED";

                        $return['Shipment ID'] = "";
                        $return['Units'] = $return['Return quantity'];
                        $return['Return Status'] = $return['Return request status'];
                        $return['Return Type'] = $return_type;
                        $return['Units'] = (int)$return['Return quantity'];
                        $return['Created On'] = $return['Return request date'];
                        $return['Reverse Leg Tracking ID'] = trim($return['Tracking ID']);
                        $return['Carrier'] = $return['Return carrier'];
                        $return['Last Updated On'] = $db->now();
                    }

                    $db->where('orderId', $orderId);
                    $db->where('rRMAId', $rmaId);
                    if ($db->has(TBL_AZ_RETURNS)) { // UPDATE
                        $exists++;
                        $db->where('orderId', $orderId);
                        $db->where('(rShipmentStatus = ? OR rShipmentStatus = ? OR rShipmentStatus = ? )', array('return_received', 'return_completed', 'return_claimed'));
                        if ($db->getOne(TBL_AZ_RETURNS))
                            continue;

                        $details = array(
                            "rShipmentStatus"            => $shipmentStatus,
                            "rTrackingId"                => trim($return['Reverse Leg Tracking ID']),
                            "rCourierName"                => $return['Carrier'],
                            "rDeliveredDate"             => $deliveredDate,
                            "rUpdatedDate"                => date('Y-m-d', strtotime($return['Last Updated On'])),
                        );
                        $db->where('orderId', $orderId);
                        $db->where('rRMAId', $rmaId);
                        if ($db->update(TBL_AZ_RETURNS, $details))
                            $update++;
                        else
                            $error[$orderId] = "Unable to update the return details. Error: " . $db->getLastError();
                    } else { // INSERT
                        $details = array(
                            "orderId"                    => $orderId,
                            "shipmentId"                => $return['Shipment ID'],
                            "rRMAId"                    => $rmaId,
                            "rStatus"                    => $return['Return Status'],
                            "rSource"                    => $return['Return Type'],
                            "rQty"                        => $return['Units'],
                            "rReason"                    => $return['Return reason'],
                            "rShipmentStatus"            => $shipmentStatus,
                            "rTrackingId"                => trim($return['Reverse Leg Tracking ID']),
                            "rCourierName"                => $return['Carrier'],
                            "rASIN"                        => $return['ASIN'],
                            "rDeliveredDate"             => $deliveredDate,
                            "rCreatedDate"                => $db->now(), //date('Y-m-d', strtotime($return['Created On'])),
                            "rUpdatedDate"                => date('Y-m-d', strtotime($return['Last Updated On'])),
                            "rExpectedDate"                => date('Y-m-d', strtotime($return['Created On'] . '+ 2 months')),
                            "insertDate"                => $db->now()
                        );
                        // var_dump($details);
                        if ($db->insert(TBL_AZ_RETURNS, $details)) {
                            $insert++;
                        } else {
                            $error[$orderId] = 'Unable to insert return. Error: ' . $db->getLastError();
                        }
                    }
                }

                $response = array(
                    'type'        => 'success',
                    'total'        => count($returns),
                    'insert'     => $insert,
                    'update'    => $update,
                    'exists'    => $exists,
                    'error'        => $error,
                );
            } else {
                $response = array(
                    'type'        => 'error',
                    'error'     => 'Unable to upload the file.',
                );
            }

            echo json_encode($response);
            break;

        case 'update_returns_status':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $orderId = $_POST['orderId'];
            $rmaId = $_POST['rmaId'];
            $uids = isset($_POST['uids']) ? $_POST['uids'] : NULL;
            $productCondition = $_POST['productCondition'];
            if (isset($_POST['claimId']) && !empty(trim($_POST['claimId']))) {
                $claimId = trim($_POST['claimId']);
                $db->where('orderId', $orderId);
                $db->where('rmaId', $rmaId);
                $db->where('claimId', $claimId);
                $claim = $db->get(TBL_AZ_CLAIMS);
                if (!$claim) {
                    $details = array(
                        'orderId' => $orderId,
                        'claimId' => $claimId,
                        'rmaId' => $rmaId,
                        'claimStatus' => 'pending',
                        'claimStatusAZ' => 'pending',
                        'claimProductCondition' => $productCondition,
                        'createdBy' => $current_user['userID'],
                        'createdDate' => $db->now(),
                    );
                    if ($db->insert(TBL_AZ_CLAIMS, $details)) {
                        $details = array(
                            'rShipmentStatus' => 'return_claimed',
                            'rUid' => is_null($uids) ? $uids : json_encode($uids)
                        );

                        $db->where('orderId', $orderId);
                        $db->where('rRMAId', $rmaId);
                        if ($db->update(TBL_AZ_RETURNS, $details))
                            $return = array('type' => 'success', 'message' => 'Claim details successfull added');
                        else
                            $return = array('type' => 'error', 'message' => 'Claim details added but status not updated');
                    } else
                        $return = array('type' => 'error', 'message' => 'Unable to add claim details. Please try again later', 'error' => $db->getLastError());
                } else {
                    $return = array('type' => 'error', 'message' => 'Claim already exist for this return');
                } // CREATE CLAIM
            } elseif (isset($_POST['cancelInit']) && !empty(trim($_POST['cancelInit']))) {
                $responses = update_returns_status($orderId, 'cancelled', true, true, $rmaId);
                if (count($responses) === 1) {
                    $return = $responses[0];
                    if ($responses[0]['type'] == "success") {
                        $return = $responses[0];
                        $return["message"] = "Return marked cancelled with order ID " . $orderId;
                    }
                } else {
                    $return["type"] = "success";
                    $return["message"] = "Multiple returns marked cancelled with order ID " . $orderId;
                }
            } else {
                $details = array('rUid' => json_encode($uids));
                $db->where('orderId', $orderId);
                $db->where('rRMAId', $rmaId);
                if ($db->update(TBL_AZ_RETURNS, $details)) {
                    $responses = update_returns_status($orderId, 'completed', true, true);
                    if (count($responses) === 1) {
                        $return = $responses[0];
                        if ($responses[0]['type'] == "success") {
                            $return = $responses[0];
                            $return["message"] = "Return marked completed with order ID " . $orderId;
                        }
                    } else {
                        $return["type"] = "success";
                        $return["message"] = "Multiple returns marked completed with order ID " . $orderId;
                    }
                }
            }

            echo json_encode($return);
            break;

        case 'update_refund_date':
            $orderId = $_POST['orderId'];
            $rmaId = $_POST['rmaId'];
            $refundDate = $_POST['refundDate'];
            $details = array(
                'rRefundDate' => date('Y-m-d', strtotime($refundDate))
            );
            $db->where('orderId', $orderId);
            $db->where('rRMAId', $rmaId);
            if ($db->update(TBL_AZ_RETURNS, $details)) {
                $return["type"] = "success";
                $return["message"] = "Successfully update refund date for order ID " . $orderId;
            } else {
                $return["type"] = "error";
                $return["message"] = "Unable to update refund date for order ID " . $orderId;
                $return["error"] = $db->getLastError();
            }

            echo json_encode($return);
            break;

            // CLAIMS
        case 'update_claim_details':
            $orderId = $_REQUEST['orderId'];
            $rRMAId = $_REQUEST['rRMAId'];
            $claimId = $_REQUEST['claimId'];
            $claimNotes = $_REQUEST['claimNotes'];
            $claimProductCondition = $_REQUEST['claimProductCondition'];
            $claimReimbursmentAmount = isset($_REQUEST['claimReimbursmentAmount']) ? $_REQUEST['claimReimbursmentAmount'] : NULL;
            $return = array(
                "type" => "error",
                "msg" => "Error updating reimbursement details to order ID " . $orderId
            );

            $details = array(
                'claimStatus' => 'resolved',
                'claimStatusAZ' => 'resolved',
                'claimComments' => trim($claimNotes) . ' (' . $current_user['user_nickname'] . ')',
                'closedBy' => $current_user['userID'],
                'closedDate' => $db->now()
            );

            if (!is_null($claimReimbursmentAmount)) {
                $details['claimReimbursmentAmount'] = $claimReimbursmentAmount;
            }

            if (update_claim($orderId, $rRMAId, $claimId, $details)) {
                $responses = update_returns_status($orderId, 'completed', true, true, $rRMAId, $claimProductCondition);
                if (count($responses) === 1) {
                    $return = $responses[0];
                    if ($responses[0]['type'] == "success") {
                        $return = $responses[0];
                        $return["message"] = "Return reimbursement added and marked completed with order ID " . $orderId;
                    }
                } else {
                    $return["type"] = "success";
                    $return["message"] = "Multiple returns marked completed with order ID " . $orderId;
                }
            }

            echo json_encode($return);
            break;

        case 'update_claim_id':
            $orderId = $_REQUEST['orderId'];
            $rmaId = $_REQUEST['rmaId'];
            $claimId = $_REQUEST['claimId'];
            $newClaimId = $_REQUEST['newClaimId'];

            $db->where('orderId', $orderId);
            $db->where('rmaId', $rmaId);
            $db->where('claimId', $claimId);
            $old_notes = $db->getValue(TBL_AZ_CLAIMS, 'claimNotes');
            $details = array(
                'claimId' => $newClaimId,
                'claimNotes' => 'Claim ID updated from ' . $claimId . ' to ' . $newClaimId . ' - ' . date('d-M-Y H:i:s', time()) . "\n\n" . $old_notes
            );

            if (update_claim($orderId, $rmaId, $claimId, $details)) {
                $return = array("type" => "success", "message" => "Claim details successfully updated", "data" => $db->getLastQuery());
            } else {
                $return = array("type" => "success", "message" => "Unable to update claim details", "error" => $db->getLastError());
            }

            echo json_encode($return);
            break;

            // REPORTS
        case 'request_report_v1':
            // _GET_AMAZON_FULFILLED_SHIPMENTS_DATA_GENERAL_ - Amazon Fulfilled Shipments

            $account = get_current_account($_GET['account']);
            $report_type = $_GET['report_type'];
            $startDate = $_GET['start_date']; // today -1 month
            $endDate = $_GET['end_date']; // now

            $mws = new MCS\MWSClient([
                'Marketplace_Id' => $account->marketplace_id,
                'Seller_Id' => $account->seller_id,
                'Access_Key_ID' => MWS_ACCESS_KEY,
                'Secret_Access_Key' => MWS_ACCESS_SECRET,
                'MWSAuthToken' => $account->mws_authorisation_token
            ]);

            $repStartDate = new DateTime($startDate);
            $repEndDate = new DateTime($endDate);

            $report = $mws->RequestReport($report_type, $repStartDate, $repEndDate);
            if ($report) {
                $details = array(
                    'report_type' => $report_type,
                    'report_id' => $report,
                    'report_status' => 'submitted',
                    'account_id' => $account->account_id,
                    'createdDate' => $db->now()
                );
                if ($db->insert(TBL_AZ_REPORT, $details))
                    $return = array('type' => 'success', 'msg' => 'Report requested successfuly. Report ID ' . $report);
                else
                    $return = array('type' => 'error', 'msg' => 'Unable to add report details', 'response' => $report);
            }

            echo json_encode($return);
            break;
        case 'request_report':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';

            $report_type = $_GET['report_type'];
            $startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date("Y-m-d"); // 2023-12-31
            $enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date("Y-m-d"); // 2023-12-31

            if (isset($_GET["account"]))
                $accounts = get_current_account($_GET['account']);
            foreach ($accounts as $account) {
                $azSpObj = new amazon($account);
                $reports = $azSpObj->createReport($report_type, $startdate, $enddate);
                if (!is_array($reports)) {
                    $reports = json_decode($reports, true);
                } else {
                    $return = array('type' => 'error', 'msg' => 'Unable to add report details', 'response' => json_encode($reports));
                    echo json_encode($return);
                    exit();
                }

                $details = array(
                    'report_type' => $report_type,
                    'report_id' => $reports["reportId"],
                    'report_status' => 'submitted',
                    'account_id' => $account->account_id,
                    'createdDate' => date("Y-m-d H:i:s")
                );
                if ($db->insert(TBL_AZ_REPORT, $details))
                    $return[$account->account_id] = array('type' => 'success', 'msg' => 'Report requested successfuly. Report ID ' . json_encode($reports));
                else
                    $return[$account->account_id] = array('type' => 'error', 'msg' => 'Unable to add report details', 'response' => json_encode($reports));
            }
            echo json_encode($return);
            break;
        case 'insert_details':

            if (isset($_GET["account"]))
                $accounts = get_current_account($_GET['account']);
            foreach ($accounts as $account) {
                $db->where("account_id", $account->account_id);
                $db->where("report_status", "submitted");
                $reportsData = $db->get(TBL_AZ_REPORT, null, "report_id, report_type");
                try {
                    if ($reportsData) {
                        $azSpObj = new amazon($account);
                        $processFunction = function ($text) {
                            return strtolower(str_replace([' ', '-', '"', '﻿'], ['_', '_', '', ''], trim($text)));
                        };
                        foreach ($reportsData as $rid) {
                            $reports = $azSpObj->getReportDocumentById($rid["report_id"]);
                            $fileData = $azSpObj->downloadReports($reports["reportDocumentId"]);
                            $data = array();
                            if (isset($fileData["compressionAlgorithm"])) {
                                if ($fileData["compressionAlgorithm"] == "GZIP") {

                                    $compressedData = file_get_contents($fileData["url"]);
                                    $uncompressedData = gzdecode($compressedData);

                                    $orders = array();
                                    $rows = explode(PHP_EOL, $uncompressedData);
                                    foreach ($rows as $row) {
                                        $orders[] = str_getcsv($row);
                                    }
                                    $keys = $orders[0];

                                    $keys = array_map($processFunction, $keys);
                                    for ($i = 1; $i < count($orders); $i++) {
                                        for ($j = 0; $j < count($orders[$i]); $j++) {
                                            $data[$i - 1][$keys[$j]] = $orders[$i][$j];
                                        }
                                    }
                                }
                            } else {
                                if (isset($fileData["url"])) {
                                    $rows = array();
                                    if (getUrlMimeType($fileData["url"]) == "text/plain") {
                                        $fileStr = file_get_contents($fileData["url"]);
                                        $rows = explode(PHP_EOL, $fileStr);

                                        $keys = explode("	", $rows[0]);
                                        $keys = array_map($processFunction, $keys);

                                        for ($i = 1; $i < count($rows); $i++) {
                                            $values = explode("	", $rows[$i]);
                                            for ($j = 0; $j < count($keys); $j++) {
                                                $data[$i - 1][$keys[$j]] = $values[$j];
                                            }
                                        }
                                    } else {
                                        $file = fopen($fileData["url"], 'r');
                                        while (($rows[] = fgetcsv($file)) != false) {
                                        }
                                        $keys = array_map($processFunction, $rows[0]);

                                        for ($i = 1; $i < count($rows); $i++) {
                                            for ($j = 0; $j < count($keys); $j++) {
                                                $data[$i - 1][$keys[$j]] = $rows[$i][$j];
                                            }
                                        }
                                    }
                                }
                            }

                            if ($rid["report_type"] == "GET_AMAZON_FULFILLED_SHIPMENTS_DATA_GENERAL") {
                                for ($i = 0; $i < count($data); $i++) {
                                    $updateData = array(
                                        "packageId" => $data[$i]["shipment_id"],
                                        "fulfillmentChannel" => $data[$i]["fulfillment_channel"],
                                        "fulfilmentSite" => (isset($data[$i]["fc"]) ? $data[$i]["fc"] : (isset($data[$i]["fulfillment_site"]) ? $data[$i]["fulfillment_site"] : "ZWCI")),
                                        "orderDate" => date("Y-m-d H:i:s", strtotime($data[$i]["purchase_date"])),
                                        "shippedDate" => date("Y-m-d H:i:s", strtotime($data[$i]["shipment_date"])),
                                        "itemPrice" => $data[$i]["item_price"],
                                        "itemTax" => (isset($ordedata[$i]["item_tax"]) ? $ordedata[$i]["item_tax"] : ($data[$i]["item_price"] * 0.18)),
                                        "shippingPrice" => $data[$i]["shipping_price"],
                                        "shippingTax" => $data[$i]["shipping_tax"],
                                        "shippingDiscount" => $data[$i]["ship_promotion_discount"],
                                        "shippingDiscountTax" => ($data[$i]["ship_promotion_discount"] * 0.18),
                                        "giftWrapPrice" => $data[$i]["gift_wrap_price"],
                                        "giftWrapTax" => $data[$i]["gift_wrap_tax"],
                                        "promotionDiscount" => $data[$i]["item_promotion_discount"],
                                        "promotionDiscountTax" => ($data[$i]["item_promotion_discount"] * 0.18),
                                        "trackingID" => $data[$i]["tracking_number"],
                                        "shippingAddress" => json_encode(array("City" => $data[$i]["ship_city"], "StateOrRegion" => $data[$i]["ship_state"], "PostalCode" => $data[$i]["ship_postal_code"], "CountryCode" => $data[$i]["ship_country"])),
                                        "courierName" => $data[$i]["carrier"],
                                        "quantity" => $data[$i]["quantity_shipped"],
                                        "orderItemId" => $data[$i]["amazon_order_item_id"],
                                        "shipServiceLevel" => $data[$i]["ship_service_level"],
                                    );
                                    $db->where("orderId", $data[$i]["amazon_order_id"]);
                                    if ($db->has(TBL_AZ_ORDERS)) {
                                        $db->where("orderId", $data[$i]["amazon_order_id"]);
                                        if ($db->update(TBL_AZ_ORDERS, $updateData)) {
                                            $db->where("report_id", $rid["report_id"]);
                                            $db->update(TBL_AZ_REPORT, ["report_status" => "insert_completed"]);
                                        } else {
                                            $log->write("::Data update failed :: Data: " . $updateData, "amazon-orders");
                                        }
                                    } else {
                                        echo "Else Part";
                                    }
                                }
                            } else if ($rid["report_type"] == "GET_FBA_FULFILLMENT_CUSTOMER_RETURNS_DATA") {
                                for ($i = 0; $i < count($data); $i++) {
                                    $rSource = "CUSTOMER_RETURN";
                                    if ($data[$i]["reason"] = "UNDELIVERABLE_REFUSED" || $data[$i]["reason"] = "UNDELIVERABLE_UNKNOWN" || $data[$i]["reason"] = "NEVER_ARRIVED")
                                        $rSource = "UNDELIVERED";
                                    $updateData = array(
                                        "orderId" => $data[$i]["order_id"],
                                        "shipmentId" => $data[$i]["license_plate_number"],
                                        "rSource" => $rSource,
                                        "rStatus" => "return_completed",
                                        "rReason" => $data[$i]["reason"],
                                        "rQty" => $data[$i]["quantity"],
                                        "rShipmentStatus" => "return_completed",
                                        "rCourierName" => "ATSIN",
                                        "rASIN" => $data[$i]["asin"],
                                        "rCreatedDate" => date("Y-m-d H:i:s", strtotime("yesterday")),
                                        "rUpdatedDate" => date("Y-m-d H:i:s", strtotime($data[$i]["return_date"])),
                                        "rExpectedDate" => date("Y-m-d H:i:s", strtotime($data[$i]["return_date"])),
                                        "rDeliveredDate" => date("Y-m-d H:i:s", strtotime($data[$i]["return_date"])),
                                        "rCreatedDate" => date("Y-m-d H:i:s", strtotime($data[$i]["return_date"]))
                                    );
                                    $db->where("orderId", $data[$i]["amazon_order_id"]);
                                    if ($db->has(TBL_AZ_RETURNS)) {
                                        $db->where("orderId", $data[$i]["amazon_order_id"]);
                                        if ($db->update(TBL_AZ_RETURNS, $updateData)) {
                                            echo $data[$i]["amazon_order_id"] . "<br>";
                                            $db->where("report_id", $rid["report_id"]);
                                            $db->update(TBL_AZ_REPORT, ["report_status" => "insert_completed"]);
                                        } else {
                                            $log->write("::Data update failed :: Data: " . $updateData, "amazon-orders");
                                        }
                                    } else {
                                        $db->insert(TBL_AZ_RETURNS, $updateData);
                                    }
                                }
                            } else if ($rid["report_type"] == "GET_FBA_REIMBURSEMENTS_DATA") {
                                for ($i = 0; $i < count($data); $i++) {
                                    $updateData = array(
                                        "orderId" => $data[$i]["amazon_order_id"],
                                        "claimId" => $data[$i]["case_id"],
                                        "claimStatus" => "resolved",
                                        "claimStatusAZ" => "resolved",
                                        "claimComments" => "FBA Reimbursement Id: " . $data[$i]["reimbursement_id"] . " (System)",
                                        "claimReimbursmentAmount" => $data[$i]["amount_total"],
                                        "claimNotes" => $data[$i]["reason"],
                                        "claimProductCondition" => $data[$i]["condition"],
                                        "createdBy" => "00",
                                        "createdBy" => "00",
                                        "createdDate" => date("Y-m-d H:i:s", strtotime("approval_date")),
                                        "closedDate" => date("Y-m-d H:i:s", strtotime($data[$i]["approval_date"])),
                                        "updatedDate" => date("Y-m-d H:i:s", strtotime($data[$i]["approval_date"]))
                                    );
                                    $db->insert(TBL_AZ_CLAIMS, $updateData);
                                }
                            }
                        }
                    }
                    echo json_encode(["type" => "success"]);
                } catch (Exception $e) {
                    echo json_encode(["type" => "error"]);
                    $log->write("::Amazon data update error: " . $e);
                }
            }
            break;
        case 'insert_past_data':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $account = get_current_account($_GET['account']);
            $azSpObj = new amazon($account);
            $report_type = $_GET['report_type'];
            for ($year = 2021; $year < 2022; $year++) {
                $startdate = date("Y-m-d\TH:i:s.v\Z", strtotime($year . "-01-1 00:00:00"));
                $enddate = date("Y-m-d\TH:i:s.v\Z", strtotime($year . "-01-31 23:59:59"));

                $reports = $azSpObj->createReport($report_type, $startdate, $enddate);
                if (is_array($reports)) {
                    echo json_encode(array('type' => 'error', 'msg' => 'Unable to add report details', 'response' => $reports));
                    exit;
                }
                $reports = json_decode($reports, true);

                $details = array(
                    'report_type' => $report_type,
                    'report_id' => $reports["reportId"],
                    'report_status' => 'submitted',
                    'account_id' => $account->account_id,
                    'createdDate' => date("Y-m-d H:i:s")
                );
                if ($db->insert(TBL_AZ_REPORT, $details))
                    $return = array('type' => 'success', 'msg' => 'Report requested successfuly. Report ID ' . $reports);
                else
                    $return = array('type' => 'error', 'msg' => 'Unable to add report details', 'response' => $reports);
            }
            echo json_encode($return);
            break;
        case 'insert_past_payments':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            function processFunction($text)
            {
                return strtolower(str_replace([' ', '-', '"', '﻿'], ['_', '_', '', ''], trim($text)));
            };
            $handle = new \Verot\Upload\Upload($_FILES['payments_file']);
            $account = $_REQUEST["account_id"];

            if ($handle->uploaded) {
                $handle->file_overwrite = true;
                $handle->dir_auto_create = true;
                $handle->process(ROOT_PATH . "/uploads/payment_sheets/");
                $handle->clean();
                $filePath = ROOT_PATH . "/uploads/payment_sheets/" . $handle->file_dst_name;
            } else {
                echo json_encode(array('error' => 'Unable to upload file'));
            }

            $data = array();
            if (isset($filePath)) {
                $rows = array();
                if (getUrlMimeType($filePath) == "text/plain") {
                    $fileStr = file_get_contents($filePath);
                    $rows = explode(PHP_EOL, $fileStr);

                    $keys = explode("	", $rows[0]);
                    $keys = array_map("processFunction", $keys);

                    for ($i = 1; $i < count($rows); $i++) {
                        $values = explode("	", $rows[$i]);
                        for ($j = 0; $j < count($keys); $j++) {
                            $data[$i - 1][$keys[$j]] = $values[$j];
                        }
                    }
                } else {
                    $file = fopen($filePath, 'r');
                    while (($rows[] = fgetcsv($file)) != false) {
                    }
                    $keys = array_map("processFunction", $rows[0]);

                    for ($i = 1; $i < count($rows); $i++) {
                        for ($j = 0; $j < count($keys); $j++) {
                            $data[$i - 1][$keys[$j]] = $rows[$i][$j];
                        }
                    }
                }
            }

            $settlementDate = str_replace(".", "-", $data[0]["deposit_date"]);
            $settlementId = $data[0]["settlement_id"];

            $payments = array();
            $insertData = array();

            for ($i = 1; $i < count($data); $i++) {
                $orderId = $data[$i]["order_id"];
                $itemCode = $data[$i]["order_item_code"];

                $index = $i;
                while ($orderId == $data[$i]["order_id"]) {
                    $payments[$index][$orderId][$itemCode]["gst"] = 0;
                    foreach ($data[$i] as $key => $value) {

                        if ($key == "amount_description") {
                            $payments[$index][$orderId][$itemCode][processFunction($value)] = $data[$i]["amount"];
                        } else if ($key == "item_related_fee_type") {
                            $payments[$index][$orderId][$itemCode][processFunction($value)] = $data[$i]["item_related_fee_amount"];
                        } else if ($key == "promotion_type") {
                            $payments[$index][$orderId][$itemCode][processFunction($value)] = $data[$i]["promotion_amount"];
                        } else {
                            $payments[$index][$orderId][$itemCode][$key] = $value;
                        }
                    }
                    $i++;
                }
                // ccd($payments);
                $insertData[] = array(
                    "settlementId" => $settlementId,
                    "accountId" => $account,
                    "orderId" => $orderId,
                    "itemId" => $itemCode,
                    "settlementDate" => date("Y-m-d H:i:s", strtotime($settlementDate)),
                    "salesAmount" => (is_null($payments[$index][$orderId][$itemCode]["principal"]) ? 0 : $payments[$index][$orderId][$itemCode]["principal"]),
                    "salesGst" => (is_null($payments[$index][$orderId][$itemCode]["product_tax"]) ? 0 : $payments[$index][$orderId][$itemCode]["product_tax"]),
                    "tcs" => (is_null($payments[$index][$orderId][$itemCode]["tcs_igst"]) ? 0 : $payments[$index][$orderId][$itemCode]["tcs_igst"]),
                    "tds" => ((is_null($payments[$index][$orderId][$itemCode]["tds_(section_194_o)"]) ? 0 : $payments[$index][$orderId][$itemCode]["tds_(section_194_o)"])),
                    "shippingCharge" => (is_null($payments[$index][$orderId][$itemCode]["shipping"]) ? 0 : $payments[$index][$orderId][$itemCode]["shipping"]),
                    "shippingTax" => (is_null($payments[$index][$orderId][$itemCode]["shipping_tax"]) ? 0 : $payments[$index][$orderId][$itemCode]["shipping_tax"]),
                    "shippingDiscount" => (is_null($payments[$index][$orderId][$itemCode]["shipping_discount"]) ? 0 : $payments[$index][$orderId][$itemCode]["shipping_discount"]),
                    "shippingTaxDiscount" => (is_null($payments[$index][$orderId][$itemCode]["shipping_tax_discount"]) ? 0 : $payments[$index][$orderId][$itemCode]["shipping_tax_discount"]),
                    "handlingCharge" => (is_null($payments[$index][$orderId][$itemCode]["fba_weight_handling_fee"]) ? 0 : $payments[$index][$orderId][$itemCode]["fba_weight_handling_fee"]),
                    "handlingTax" => $payments[$index][$orderId][$itemCode]["fba_weight_handling_fee_cgst"] + $payments[$index][$orderId][$itemCode]["fba_weight_handling_fee_sgst"],
                    "pickPackCharge" => (is_null($payments[$index][$orderId][$itemCode]["fba_pick_pack_fee"]) ? 0 : $payments[$index][$orderId][$itemCode]["fba_pick_pack_fee"]),
                    "pickPackTax" => $payments[$index][$orderId][$itemCode]["fba_pick_pack_fee_cgst"] + $payments[$index][$orderId][$itemCode]["fba_pick_pack_fee_sgst"],
                    "commission" => (is_null($payments[$index][$orderId][$itemCode]["commission"]) ? 0 : $payments[$index][$orderId][$itemCode]["commission"]),
                    "commissionTax" => (is_null($payments[$index][$orderId][$itemCode]["commission_igst"]) ? 0 : $payments[$index][$orderId][$itemCode]["commission_igst"]),
                    "closingFee" => (is_null($payments[$index][$orderId][$itemCode]["fixed_closing_fee"]) ? 0 : $payments[$index][$orderId][$itemCode]["fixed_closing_fee"]),
                    "closingFeeTax" => (is_null($payments[$index][$orderId][$itemCode]["fixed_closing_fee_igst"]) ? 0 : $payments[$index][$orderId][$itemCode]["fixed_closing_fee_igst"]),
                );
            }

            if ($db->insertMulti(TBL_AZ_PAYMENTS, $insertData)) {
                $return = array("type" => "success", "message" => "payments data successfully inserted!");
            } else {
                $return = array("type" => "Error", "message" => "Unable to insert", "error" => $db->getLastError());
            }
            // $return = insert_payments($account, $data);

            echo json_encode($return);
            break;
        case 'insert_payments':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            if (isset($_GET["account"]))
                $accounts = get_current_account($_GET['account']);
            foreach ($accounts as $account) {
                $azSpObj = new amazon($account);
                $report_type = "GET_V2_SETTLEMENT_REPORT_DATA_FLAT_FILE";

                function processFunction($text)
                {
                    return strtolower(str_replace([' ', '-', '"', '﻿', '_&'], ['_', '_', '', '', ''], trim($text)));
                };

                $reports = $azSpObj->requestReports($report_type, "100");
                try {
                    foreach ($reports["reports"] as $rpt) {
                        $fileData = $azSpObj->downloadReports($rpt["reportDocumentId"]);

                        $data = array();
                        if (isset($fileData["compressionAlgorithm"])) {
                            if ($fileData["compressionAlgorithm"] == "GZIP") {

                                $compressedData = file_get_contents($fileData["url"]);
                                $uncompressedData = gzdecode($compressedData);

                                $orders = array();
                                $rows = explode(PHP_EOL, $uncompressedData);
                                foreach ($rows as $row) {
                                    $orders[] = str_getcsv($row);
                                }
                                $keys = $orders[0];

                                $keys = array_map("processFunction", $keys);
                                for ($i = 1; $i < count($orders); $i++) {
                                    for ($j = 0; $j < count($orders[$i]); $j++) {
                                        $data[$i - 1][$keys[$j]] = $orders[$i][$j];
                                    }
                                }
                            }
                        } else {
                            if (isset($fileData["url"])) {
                                $rows = array();
                                if (getUrlMimeType($fileData["url"]) == "text/plain") {
                                    $fileStr = file_get_contents($fileData["url"]);
                                    $rows = explode(PHP_EOL, $fileStr);

                                    $keys = explode("	", $rows[0]);
                                    $keys = array_map("processFunction", $keys);

                                    for ($i = 1; $i < count($rows); $i++) {
                                        $values = explode("	", $rows[$i]);
                                        for ($j = 0; $j < count($keys); $j++) {
                                            $data[$i - 1][$keys[$j]] = $values[$j];
                                        }
                                    }
                                } else {
                                    $file = fopen($fileData["url"], 'r');
                                    while (($rows[] = fgetcsv($file)) != false) {
                                    }
                                    $keys = array_map("processFunction", $rows[0]);

                                    for ($i = 1; $i < count($rows); $i++) {
                                        for ($j = 0; $j < count($keys); $j++) {
                                            $data[$i - 1][$keys[$j]] = $rows[$i][$j];
                                        }
                                    }
                                }
                            }
                        }

                        $return[$account->account_id] = insert_payments($account->account_id, $data);
                    }
                } catch (Exception $e) {
                    $log->write("::Insert Error: " . $e, "sp_api_request_insert_db");
                    $return[$account->account_id] = array("type" => "error", "message" => "something went wrong");
                }
            }

            echo json_encode($return);
            break;

        case 'get_all_settlements':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $db->join(TBL_AZ_ACCOUNTS . ' a', "a.account_id=p.accountId");
            $db->orderBy('p.settlementDate', 'DESC');
            $db->groupBy('p.settlementId');
            $settlements = $db->get(TBL_AZ_PAYMENTS . ' p', null, 'settlementId,  settlementDate, a.account_name, SUM(salesAmount) as paymentTotal');

            $return = array();
            $i = 0;

            foreach ($settlements as $settlement) {
                $j = 0;
                $return["data"][$i][] = '<a target="_blank" href="' . BASE_URL . '/flipkart/payments.php?search_by=neft_id&search_value=' . $settlement["azSettlementId"] . '">' . $settlement["settlementId"] . '<a>';
                $return["data"][$i][] = date("d M, Y h:i a", strtotime($settlement["settlementDate"]));
                $return["data"][$i][] = $return['data'][$i][] = '&#8377 ' . number_format((float)$settlement["paymentTotal"], 2, '.', ',');
                $i++;
            }

            if (empty($return))
                $return['data'] = array();

            echo json_encode($return);
            break;
        case 'get_all_upcoming':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';

            $data = $db->rawQuery("SELECT bas_az_orders.*, bas_az_account.account_name FROM bas_az_orders LEFT JOIN bas_az_payments ON bas_az_orders.orderId = bas_az_payments.orderId JOIN bas_az_account ON bas_az_orders.account_id = bas_az_account.account_id WHERE bas_az_payments.orderId IS NULL LIMIT 1000");
            // ccd($data);
            $return  = array();
            for ($i = 0; $i < count($data); $i++) {
                $expAmount = $data[$i]["itemPrice"] + $data[$i]["itemTax"] - $data[$i]["shippingPrice"] - $data[$i]["shippingTax"] + $data[$i]["shippingDiscount"] + $data[$i]["shippingDiscountTax"];

                $return["data"][$i] = array(
                    $data[$i]["account_name"],
                    $data[$i]["orderItemId"],
                    $data[$i]["orderId"],
                    date("d M, Y h:i a", strtotime($data[$i]["orderDate"])),
                    date("d M, Y h:i a", strtotime($data[$i]["shippedDate"])),
                    date("d M, Y h:i a", strtotime($data[$i]["lastUpdateAt"])),
                    $expAmount,
                );
            }
            echo json_encode($return);
            break;
        case 'get_all_unsettled':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $db->join(TBL_AZ_ACCOUNTS . " a", "a.account_id = p.accountId");
            $db->join(TBL_AZ_ORDERS . " o", "o.orderId = p.orderId");
            $db->where("o.isSettled", "0");
            $data = $db->get(TBL_AZ_PAYMENTS . " p", null, "a.account_id, a.account_name, p.*, o.orderDate, o.orderItemId, o.fulfillmentChannel, o.shippingAddress, o.fulfilmentSite, o.quantity, o.shippedDate, o.lastUpdateAt, o.itemPrice as orderItemPrice, o.itemTax as orderItemTax, o.shippingPrice as orderShipping, o.shippingTax as orderShippingTax, o.shippingDiscount as orderShippingDiscount, o.shippingDiscountTax as orderShippingDiscountTax");

            $return  = array();

            for ($i = 0; $i < count($data); $i++) {
                $amazon = new AmazonPayments($data[$i]["a.account_id"]);

                $expectedPayout = $amazon->calculate_actual_payout($data[$i]["orderItemId"]);
                $settlemet = $amazon->calculate_net_settlement($data[$i]["orderItemId"]);
                // ccd($settlemet);
                $db->where("account_id", $data[$i]["accountId"]);
                $accountTiers = json_decode($db->getValue(TBL_AZ_ACCOUNTS, "account_tier"), true);

                $return["data"][$i] = array(
                    $data[$i]["account_name"],
                    $data[$i]["itemId"],
                    $data[$i]["orderId"],
                    date("d M, Y h:i a", strtotime($data[$i]["orderDate"])),
                    date("d M, Y h:i a", strtotime($data[$i]["shippedDate"])),
                    date("d M, Y h:i a", strtotime($data[$i]["lastUpdateAt"])),
                    round($expectedPayout, 2),
                    round($settlemet, 2),
                    round(($settlemet - $expectedPayout), 2),
                    $data[$i]["accountId"]
                );
            }
            echo json_encode($return);
            break;
        case 'get_difference_details':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $itemId = $_REQUEST["orderItemId"];
            $accountId = $_REQUEST["account_id"];
            $db->where("account_id", $accountId);
            $account = $db->getOne(TBL_AZ_ACCOUNTS, "*");
            $amazon = new AmazonPayments($account);

            $inner_content = '';
            $content = "";

            $return = $amazon->get_difference_details($itemId);
            $orders = $return['orders'];
            $order = (object)$return['order'];

            $settlements = array_merge(array($return['expected_payout']), $return['settlements'], array($return['difference']));
            $key_order = array('settlement_date', 'sale_amount', 'marketplace_fees', 'commission_fee', 'fixed_fee', 'closingFee', 'pickPackCharge', 'shipping_zone', 'shipping_fee', 'taxes', 'tcs', 'tds', 'gst', 'total');

            $output_content = array();
            foreach ($settlements as $settlement) {
                uksort($settlement, function ($key1, $key2) use ($key_order) {
                    return ((array_search($key1, $key_order) > array_search($key2, $key_order)) ? 1 : -1);
                });
                foreach ($settlement as $s_key => $s_value) {
                    $output_content[$s_key][] = $s_value;
                }
            }

            $j = 0;
            $last = count($output_content['settlement_date']) - 1;

            unset($output_content["commission_rate"]);
            $inner_content = '<tbody>';
            foreach ($output_content as $level_key => $level_value) {
                $editables = array('settlement_date', 'sale_amount', 'commission_fee', 'commission_incentive', 'collection_fee', 'pick_and_pack_fee', 'fixed_fee', 'shipping_zone', 'shipping_fee');
                $i = 0;
                if ($level_key == 'settlement_date')
                    $inner_content .= '<tr class="accordion-title-container">';
                else if ($level_key == 'total')
                    $inner_content .= '<tr class="net-earnings-row">';
                else if ($level_key == 'commission_fee' || $level_key == 'fixed_fee' || $level_key == 'pick_and_pack_fee' || $level_key == 'shipping_zone' || $level_key == 'shipping_fee')
                    $inner_content .= '<tr class="marketplace-fee-child">';
                else if ($j == 6) // Marketplace Fees Row - Fixed at 5th position
                    $inner_content .= '<tr class="grey-highlight marketplace-fee-row">';
                else if ($level_key == 'tds' || $level_key == 'tcs' || $level_key == 'gst')
                    $inner_content .= '<tr class="taxes-child">';
                else if ($j == 10) // Taxes Fees Row - Fixed at 20th position
                    $inner_content .= '<tr class="grey-highlight taxes-row">';
                else
                    $inner_content .= '<tr class="grey-highlight">';


                $available_width = 100;
                $width = ($available_width / ($last + 1)) >= 15 ? 15 : $available_width / ($last + 1);
                foreach ($level_value as $value) {
                    if ($level_key == 'settlement_date') {
                        if ($i == 0) {
                            $payment_type = 'Expected Payout';
                            $available_width -= $width;
                        } elseif ($i == $last) {
                            $payment_type = 'Difference';
                            $width = $available_width;
                        } else {
                            $payment_type = 'Settlement';
                            $available_width -= $width;
                        }

                        $inner_content .= '
            						<td class="transaction-data-neft" style="width:' . $width . '%">
            							<span class="transaction-neft-value">' . $payment_type . '</span>
            							<div class="transaction-date">
            								' . $value . '
            							</div>
            						</td>';
                    } else {
                        if ($i == $last && !empty($value))
                            $inner_content .= '<td>' . $value . '</td>';
                        elseif ($i != $last)
                            $inner_content .= '<td>' . $value . '</td>';
                    }
                    $i++;
                }
                $inner_content .= '</tr>';
                $j++;
            }
            $inner_content .= '</tbody>';
            $order = (array)$order;
            // ccd($order);
            $content = '<div class="product-details-transaction-container">
	                        <div class="product-details-container">
	                        	<div class="row product-container clearfix">
	                        		<div class="col-md-1 product-image-container">
	                        			<div class="product-img-holder">
	                        				<img src="' . IMAGE_URL . '/uploads/products/' . $order["thumb_image_url"] . '" onerror="this.src=\'https://via.placeholder.com/100x100\'">
	                        			</div>
	                        			<div class="cod-prepaid">' . strtoupper("prepaid") . '</div>
	                        		</div>
	                        		<div class="col-md-10 details-holder">
	                        			<div class="product-title">
	                        				<a target="_blank" href="">' . $order["title"] . '</a>
	                        			</div>
	                        			<div class="product-order-details">
	                        				<div class="order-details-container">
	                        					<div class="details-col-title">
	                        						<div class="order-title">Item ID </div>
	                        						<div class="order-title">Status</div>
	                        						<div class="order-title">SKU</div>
	                        						<div class="order-title">Shipped Date </div>
	                        					</div>
	                        					<div class="details-col-value">
	                        						<div class="heading-value"><a href="" target="_black">' . $order["orderItemId"] . '</a></div>
	                        						<div class="heading-value">' . $order["az_status"] . '</div>
	                        						<div class="heading-value">' . $order["quantity"] . ' x ' . $order["sku"] . '</div>
	                        						<div class="heading-value">' . date('d M, Y', ($order["shipByDate"] == NULL ? strtotime($order["dispatchAfterDate"]) : strtotime($order["shipByDate"]))) . '</div>
	                        						<div class="heading-value"></div>
	                        						<div class="heading-value"></div>
	                        					</div>
	                        				</div>
	                        			</div>
	                        		</div>
	                        		<div class="col-md-1 product-container-buttons text-right">
	                        			<a data-itemid="' . $order["itemId"] . '" class="mark_settled btn btn-default btn-xs" title="Mark Settled"><i class="fa fa-check" aria-hidden="true"></i></a><br />
	                        		</div>
	                        	</div>
	                        	<div class="settlement-notes form clearfix">
	                        		<form class="form-horizontal form-row-seperated">
	                        			<div class="form-group">
	                        				<label class="control-label col-md-1">IncidentID: </label>
	                        				<div class="col-md-3">
	                        					<input class="form-control incidentId" name="incidentId" value="' . $order["incidentId"] . '" />
	                        					<input type="hidden" name="account_id" value="' . $accountId . '" />
	                        				</div>
	                        				<div class="col-md-7">
	                        					<input type="hidden" id="settlementNotes" name="settlementNotes" class="form-control select2" value="' . $order["claimReason"] . '">
	                        					<input type="hidden" name="orderItemId" value="' . $order["orderItemId"] . '" />
	                        				</div>
	                        				<div class="col-md-1">
	                        					<button type="submit" class="btn btn-default update_notes">Update</button>
	                        				</div>
	                        			</div>
	                        		</form>
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
	                        							<tr class="grey-highlight">
	                        								<td class="title" style="width:120px;">Sale Amount</td>
	                        							</tr>
	                        							<tr class="grey-highlight">
	                        								<td class="title" style="width:120px;">Amazon Discount</td>
	                        							</tr>
	                        							<tr class="grey-highlight marketplace-fee-row">
	                        								<td class="title" style="width:120px;">Marketplace Fees <i class="fa fa-chevron-down"></i></td>
	                        							</tr>
	                        							<tr class="marketplace-fee-child">
	                        								<td class="title" style="width:120px;">Commission</td>
	                        							</tr>
	                        							<tr class="marketplace-fee-child">
	                        								<td class="title" style="width:120px;">Fixed Fee</td>
	                        							</tr>
	                        							<tr class="marketplace-fee-child">
	                        								<td class="title" style="width:120px;">Shipping Zone</td>
	                        							</tr>
	                        							<tr class="marketplace-fee-child">
	                        								<td class="title" style="width:120px;">Shipping Fee</td>
                                                        </tr>
	                        							<tr class="grey-highlight taxes-row">
                                                            <td class="title" style="width:120px;">Taxes <i class="fa fa-chevron-down"></i></td>
                                                        </tr>
	                        							</tr>
	                        							<tr class="taxes-child">
	                        								<td class="title" style="width:120px;">TCS</td>
	                        							</tr>
	                        							<tr class="taxes-child">
	                        								<td class="title" style="width:120px;">TDS</td>
	                        							</tr>
	                        							<tr class="taxes-child">
	                        								<td class="title" style="width:120px;">GST</td>
	                        							</tr>
	                        							<tr class="net-earnings-row">
	                        								<td class="title">Net Earnings</td>
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

        case 'update_settlement_notes':

            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            if (empty($_REQUEST["incidentId"])) {
                $details = array(
                    'itemId' => $_REQUEST["orderItemId"],
                    'status' => "pending",
                    'reason' => $_REQUEST['settlementNotes'],
                    'createdBy' => $_SESSION["user_id"],
                    'accountId' => $_REQUEST['accountId'],
                );

                if ($db->insert(TBL_AZ_INCIDENTS, $details)) {
                    echo json_encode(array('type' => 'success', 'msg' => 'Successfully updated transaction notes'));
                } else {
                    echo json_encode(array('type' => 'error', 'msg' => 'Unable to update details. Please try later.'));
                }
            } else {
                $details = array(
                    'caseId' => $_REQUEST["incidentId"],
                    'itemId' => $_REQUEST["orderItemId"],
                    'status' => "active",
                    'reason' => $_REQUEST['settlementNotes'],
                    'updatedBy' => $_SESSION["user_id"],
                );

                $db->where('itemId', $_REQUEST["orderItemId"]);
                $caseId = $db->getValue(TBL_AZ_INCIDENTS, "caseId");
                if ($caseId == null || $caseId == "") {
                    $details['incidentNotes'] = "New Claim Id: " . $_REQUEST["incidentId"];
                } else {
                    $details['incidentNotes'] = "Claim Id Updated To: " . $_REQUEST["incidentId"] . " From: " . $caseId;
                }

                $db->where('itemId', $_REQUEST["orderItemId"]);
                if ($db->update(TBL_AZ_INCIDENTS, $details)) {
                    echo json_encode(array('type' => 'success', 'msg' => 'Successfully updated transaction notes'));
                } else {
                    echo json_encode(array('type' => 'error', 'msg' => 'Unable to update details. Please try later.'));
                }
            }
            break;
        case 'get_all_to_claim':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $db->join(TBL_AZ_ACCOUNTS . " a", "a.account_id = ai.accountId", "LEFT");
            $db->join(TBL_USERS . " u", "u.userID = ai.createdBy");
            $db->where("ai.status", "pending");
            $settlements = $db->get(TBL_AZ_INCIDENTS . " ai", null, "ai.*, u.display_name, a.account_name");

            $return = array();
            foreach ($settlements as $settlement) {
                $return[] = array(
                    $settlement["account_name"],
                    $settlement["itemId"],
                    $settlement["createdDate"],
                    $settlement["reason"],
                    $settlement["status"],
                    $settlement["display_name"],
                    $settlement["accountId"],
                );
            }

            echo json_encode(array("type" => "success", "data" => $return));
            break;
        case 'get_all_disputed':
            // error_reporting(E_ALL);
            // ini_set('display_errors', '1');
            // echo '<pre>';
            $db->join(TBL_AZ_ACCOUNTS . " a", "a.account_id = ai.accountId", "LEFT");
            $db->join(TBL_USERS . " u", "u.userID = ai.createdBy", "LEFT");
            $db->join(TBL_USERS . " up", "up.userID = ai.updatedBy", "LEFT");
            $db->join(TBL_AZ_ORDERS . " o", "o.orderItemId = ai.itemId", "LEFT");
            $db->where("ai.status", "active");
            $db->where("o.isSettled", "0");
            $settlements = $db->get(TBL_AZ_INCIDENTS . " ai", null, "ai.*, u.display_name as createdName, up.display_name as updatedName, a.account_name");

            $return = array();
            foreach ($settlements as $settlement) {
                $return[] = array(
                    $settlement["account_name"],
                    $settlement["itemId"],
                    $settlement["caseId"],
                    $settlement["reason"],
                    $settlement["incidentNotes"],
                    $settlement["status"],
                    $settlement["createdName"],
                    $settlement["updatedName"],
                    $settlement["createdDate"],
                    $settlement["updatedDate"],
                    $settlement["closedDate"],
                    $settlement["accountId"],
                );
            }

            echo json_encode(array("type" => "success", "data" => $return));
            break;
        case 'mark_order_settled':
            $itemId = $_REQUEST["orderItemId"];
            $accountId = $_REQUEST["account_id"];

            $details = array("isSettled" => "1");
            $db->where("orderItemId", $itemId);
            $db->where("account_id", $accountId);
            if ($db->update(TBL_AZ_ORDERS, $details)) {
                $return = array("type" => "success");
            } else {
                $return = array("type" => "error");
            }

            echo json_encode($return);
            break;
    }
} else {
    exit('Invalid request!');
}
