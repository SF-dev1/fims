<?php 
// https://shopify.dev/docs/admin-api/rest/reference/orders/order

/**
 * 
 */
if (!class_exists('shopify_dashboard')){
	class shopify_dashboard extends shopify{

		private $account;
		public $shopify;

		function __construct($account){
			$this->account = $account;
			// if (!empty($account->account_api_token)){
			// 	$config = array(
			// 		'ShopUrl' => $account->account_store_url,
			// 		'AccessToken' => $account->account_api_token,
			// 	);
			// } else {
				$config = array(
					'ShopUrl' => $account->account_store_url,
					'ApiKey' => $account->account_api_key,
					'Password' => $account->account_api_pass,
				);
			// }

			$this->shopify = new PHPShopify\ShopifySDK($config);
		}

		public function update_order_sp($orderID, $updateInfo){
			// Update an order's tags
			// PUT /admin/api/2021-04/orders/450789469.json
			try {
				$response = $this->shopify->Order($orderID)->put($updateInfo);
			} catch (PHPShopify\Exception\ApiException $e){
				return $e->getMessage();
			}

			// Add a note to order
			// PUT /admin/api/2021-04/orders/450789469.json

			return ($response);
		}

		public function create_replacement_order_sp($order_data){
			global $log;
			// CREATE DRAFT ORDER
			$order = array (
				"shipping_lines" => [
					"title" => "Free shipping",
					"custom" => true,
					"handle" => null,
					"price" => "0.00"
				],
				"customer" => [
					"id" => $order_data->customerId
				],
				"shipping_address" => $order_data->deliveryAddress,
				"billing_address" => $order_data->billingAddress,
				"use_customer_default_address" => false,
				"line_items" => $order_data->lineItems,
				"applied_discount" => [
					"description" => "Custom discount",
					"value_type" => "percentage",
					"value" => "100",
					"title" => "Replacement Order Discount"
				]
			);
			
			// CREATE DRAFT ORDER
			$draft_response = $this->shopify->DraftOrder->post($order);
			sleep(3); // SHOPIFY NEEDS TIME TO COMPLETE THE BACKEND PROCESS. Ref:https://community.shopify.com/c/graphql-basics-and/graphql-api-error-when-using-draftordercomplete-directly-after/m-p/562203
			// MARK DRAFT ORDER COMPLETE
			$complete_response = $this->shopify->DraftOrder($draft_response['id'])->complete();
			$tag_response = $this->update_order_sp($complete_response['order_id'], array('tags' => array('Replacement')));
			$log->write('Draft Creation:'.json_encode($draft_response)."\nOrder Response". json_encode($complete_response)."\nTag Response:". json_encode($tag_response), 'shopify-order-creation');

			return $complete_response['order_id'];
		}

		// $order_id = '3679891587281';
		// $tracking_details = array(
		// 	"tracking_number" => "FMPC1243664513",
		// 	"tracking_company" => "Ekart Logistics",
		// 	"tracking_url" => "https://www.ekartlogistics.com/shipmenttrack/FMPC1243664513"
		// );
		// $sp->fulfill_order_sp($order_id, $tracking_details);
		public function fulfill_order_sp($orderID, $logistic_details, $update = false){
			global $log;
			if ($update){
				try {
					$fulfillment = $this->shopify->Order($orderID)->Fulfillment()->get();
					$current_fulfillment = end($fulfillment);
					// $fulfillmentID = $fulfillment[count($fulfillment)-1]['id'];
					// $updateInfo = array (
					// 	"location_id" => $this->account->locationId,
					// 	"tracking_number" => $logistic_details["tracking_number"],
					// 	"tracking_company" => $logistic_details["tracking_company"],
					// 	"tracking_urls" => array(
					// 		$logistic_details["tracking_url"]
					// 	),
					// 	"notify_customer" => true
					// );
					if ($current_fulfillment && $current_fulfillment['status'] != "cancelled")
						$response_cancel = $this->shopify->Fulfillment($current_fulfillment['id'])->cancel();
					// $response = $this->shopify->Order($orderID)->Fulfillment->post($updateInfo);
					// $this->update_fulfilment_status_sp($orderID, 'label_printed');
				} catch (PHPShopify\Exception\ApiException $e){
					return $e->getMessage();
				}
			}

			$FulfillmentOrders = $this->shopify->Order($orderID)->FulfillmentOrder->get();
			if(!empty($FulfillmentOrders)){
				foreach($FulfillmentOrders as $FulfillmentOrder){
					foreach($FulfillmentOrder['line_items'] as $item){
						//Fulfill only items not yet fulfilled
						if($FulfillmentOrder['status'] == "open" && $item['fulfillable_quantity'] > 0){
							try{
								$data = array(
									'location_id' => $FulfillmentOrder['assigned_location_id'],
									"tracking_info" => array(
										"number" => $logistic_details["tracking_number"],
										"company" => $logistic_details["tracking_company"],
										"url" => $logistic_details["tracking_url"]
									),
									'notify_customer' => true,
									'line_items_by_fulfillment_order' => [
										array(
											'fulfillment_order_id' => $item['fulfillment_order_id'],
											// 'fulfillment_order_line_items' => [
											// 	array(
											// 		'id' => $item['id'],
											// 		'quantity' => $item['quantity']
											// 	)
											// ]
										)
									]
								);
								$response = $this->shopify->Fulfillment->post($data);
								$log->write("Fulfilled\r\nOrder ID: ".$orderID."\r\nLogistic Details: ".json_encode($logistic_details)."\r\nShopify Response: ".json_encode($response), 'shopify-order-fulfilled');
								$this->update_fulfilment_status_sp($orderID, 'label_printed');
							} catch(Exception $e) {
								return $e->getMessage();
							}
						}
					}
				}
			}

			return ($response);
		}

		public function get_current_order_status($orderID){
			try {
				$fulfillment = $this->shopify->Order($orderID)->Fulfillment()->get();
				return $fulfillment[count($fulfillment)-1]['shipment_status'];
			} catch (PHPShopify\Exception\ApiException $e){
				return $e->getMessage();
			}
		}

		public function update_fulfilment_status_sp($orderID, $status){

			$fulfillment = $this->shopify->Order($orderID)->Fulfillment()->get();
			$fulfillmentID = $fulfillment[count($fulfillment)-1]['id'];
			// label_printed, ready_for_pickup (SELF PICKUP ONLY), picked_up (SELF PICKUP ONLY), in_transit, out_for_delivery, attempted_delivery, delivered, failure
			$fulfillmentEvent = array(
				"status" => $status
			);

			try {
				return $this->shopify->Order($orderID)->Fulfillment($fulfillmentID)->Event->post($fulfillmentEvent);
			} catch (PHPShopify\Exception\ApiException $e){
				return $e->getMessage();
			}
		}

		public function get_order_transactions($orderID){
			try {
				return $this->shopify->Order($orderID)->Transaction()->get();
			} catch (PHPShopify\Exception\ApiException $e){
				return $e->getMessage();
			}
		}
	}
}

?>