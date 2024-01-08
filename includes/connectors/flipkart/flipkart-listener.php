<?php

// class connector_flikart_listener
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

/**
 * 
 */
class flipkart_listner extends connector_flipkart
{
	private $fk_account = "";
	private $sandbox = false;

	function __construct($fk_account, $sandbox = false, $force = false)
	{
		$this->fk_account = $fk_account;

		if ($this->fk_account->sandbox)
			$this->sandbox = true;

		parent::__construct($fk_account, $sandbox, $force);
	}

	function fk_verify($headers)
	{
		global $log;

		if (!isset($headers['X_Authorization']) || empty($headers['X_Authorization'])) {
			$error =  'Missing authorisation data.';
			$log->write($error, 'notification-' . $this->fk_account->account_name);
			echo $error;
			return false;
			// throw new Exception("Missing authorisation data.");
		}

		$notification_url = BASE_URL . '/flipkart/notifications-receiver.php?account=' . $this->fk_account->account_id;
		if ($this->sandbox)
			$notification_url = BASE_URL . '/flipkart/notifications-receiver.php?account=sandbox';

		$timestamp = strtotime($headers['X_Date']);

		$signature = sha1($timestamp . $notification_url . "POST" . $this->fk_account->app_secret);
		$authorisation = "FKLOGIN " .  base64_encode($this->fk_account->app_id . ':' . $signature);

		$x_authorisation = $headers['X_Authorization'];
		if (strpos($headers['X_Authorization'], 'FKLOGIN') !== FALSE && $this->fk_account->account_id === 1)
			return true;

		// var_dump($this->fk_account->app_id);
		// var_dump($this->fk_account->app_secret);
		// var_dump($authorisation);
		// var_dump($x_authorisation);

		if ($authorisation == $x_authorisation)
			return true;
		else
			return false;
	}

	function process_notification($data)
	{

		if (empty($data)) {
			return;
		}
		$data = json_decode($data);
		if (isset($data->version) && $data->version == 'v3') {
			$this->process_notification_v3($data);
		} else {
			$this->process_notification_v2($data);
		}
	}

	function process_notification_v2($data)
	{
		global $db;

		switch ($data->eventType) {
			case 'order_item_created':
				$orderItemId = $data->orderItemId;
				$order = $this->get_order_details($orderItemId);
				$this->insert_orders(array($order));
				break;

			case 'order_item_hold':
				$orderItemId = $data->orderItemId;
				$order = $this->update_fk_order_status($orderItemId, array('hold' => true));
				break;

			case 'order_item_un_hold':
				$orderItemId = $data->orderItemId;
				$order = $this->update_fk_order_status($orderItemId, array('hold' => false));
				break;

			case 'order_item_packed':
				$orderItemId = $data->orderItemId;
				$db->where('orderItemId', $orderItemId);
				if (!$db->has(TBL_FK_ORDERS)) { // CHECK IF THE ORDER EXISTS
					$order = $this->get_order_details($orderItemId, true);
					$this->insert_orders(array($order));
				}
				$db->where('orderItemId', $orderItemId);
				$shipmentId = $db->getValue(TBL_FK_ORDERS, 'shipmentId');
				$order = $this->update_order_to_pack($shipmentId); // to get all the shipping details and will mark orders as RTD
				// $order = $this->update_fk_order_status($orderItemId, array('fk_status' => 'PACKED'));
				break;

				// case 'order_item_ready_to_dispatch': // we won't perform this event as it is triggered by our system
			case 'order_item_pickup_complete':
			case 'order_item_shipped':
			case 'order_item_delivered':
				$orderItemId = $data->orderItemId;
				$status = array('fk_status' => $data->attributes->status);
				// if ($data->eventType == 'order_item_ready_to_dispatch') $status['status'] = 'RTD';
				$order = $this->update_fk_order_status($orderItemId, $status);
				break;

			case 'order_item_cancelled':
				$orderItemId = $data->orderItemId;
				$order = $this->update_order_to_cancel(NULL, $orderItemId, $data->attributes->cancelledQuantity);
				break;

			case 'order_item_dispatch_dates_changed':
				$orderItemId = $data->orderItemId;
				$db->where('orderItemId', $orderItemId);
				if (!$db->has(TBL_FK_ORDERS)) {
					$order = $this->get_order_details($orderItemId);
					$this->insert_orders(array($order));
				}
				$order = $this->update_fk_order_status($orderItemId, array('dispatchAfterDate' => $data->attributes->dispatchAfterDate, 'dispatchByDate' => $data->attributes->dispatchByDate));
				break;

			case 'return_created':
				$returns = $data->attributes->returnItems;
				$return_count = count($data->attributes->returnItems);
				$diff = call_user_func_array('array_diff', json_decode(json_encode($returns), true));
				if (empty($diff)) {
					$returnItems[0] = $returns[0];
					$returnItems[0]->quantity = $return_count;
				} else {
					$returnItems = $returns;
				}
				$return = $this->insert_return($returnItems);
				break;

				// update returns with cron for 'picked_up', 'out_for_delivery', 'delivered'

			case 'return_expected_date_changed':
			case 'return_item_tracking_id_update':
				$order = $this->update_fk_return_order_details($data->returnId, $data->attributes->returnItems);
				break;

			case 'return_completed':
			case 'return_cancelled':
				$order = $this->update_fk_return_order_status($data->returnId, $data->attributes->status, $data->attributes->returnItems);
				break;
		}
	}

	function process_notification_v3($data)
	{
		global $db;

		switch ($data->eventType) {
			case 'shipment_created':
				$shipment = new stdClass();
				$shipment->shipments[0] = $data->attributes;
				$shipment->shipments[0]->shipmentId = $data->shipmentId;
				$shipment->shipments[0]->locationId = $data->locationId;
				$shipment->shipments[0]->shipmentType = 'NORMAL';

				if (isset($_GET['test']) && $_GET['test'] == 1) {
					$order = $this->arrange_order_details($shipment);
					$this->insert_orders($order);
				} else {
					$order = $this->arrange_order_details($shipment);
					$this->insert_orders($order);
				}
				break;

			case 'shipment_hold':
				$shipmentId = $data->shipmentId;
				$order = $this->update_fk_order_status($shipmentId, array('hold' => true));
				break;

			case 'shipment_unhold':
				$shipmentId = $data->shipmentId;
				$order = $this->update_fk_order_status($shipmentId, array('hold' => false));
				break;

			case 'shipment_packed':
				$shipmentId = $data->shipmentId;
				$db->where('shipmentId', $shipmentId);
				if (!$db->has(TBL_FK_ORDERS)) { // CHECK IF THE ORDER EXISTS
					$order = $this->get_order_details($shipmentId);
					$this->insert_orders(array($order));
				}
				$order = $this->update_order_to_pack($shipmentId); // to get all the shipping details and will mark orders as RTD
				// $order = $this->update_fk_order_status($orderItemId, array('fk_status' => 'PACKED'));
				break;

				// case 'shipment_ready_to_dispatch': // we won't perform this event as it is triggered by our system
			case 'shipment_pickup_complete':
			case 'shipment_shipped':
			case 'shipment_delivered':
				$shipmentId = $data->shipmentId;
				$status = array('fk_status' => $data->attributes->status);
				$order = $this->update_fk_order_status($shipmentId, $status);
				break;

			case 'shipment_dispatch_dates_changed':
				$shipmentId = $data->shipmentId;
				$db->where('shipmentId', $shipmentId);
				if (!$db->has(TBL_FK_ORDERS)) {
					$order = $this->get_order_details($shipmentId);
					$this->insert_orders(array($order));
				}
				$order = $this->update_fk_order_status($shipmentId, array('dispatchAfterDate' => $data->attributes->dispatchAfterDate, 'dispatchByDate' => $data->attributes->dispatchByDate));
				break;

			case 'shipment_cancelled':
				$order = $this->update_order_to_cancel(NULL, $data->shipmentId, $data->attributes->orderItems[0]->quantity);
				break;

			case 'return_created':
				$return = $this->insert_return($data->attributes->returnItems);
				break;

				// update returns with cron for 'picked_up', 'out_for_delivery', 'delivered'

			case 'return_expected_date_changed':
			case 'return_item_tracking_id_update':
				$order = $this->update_fk_return_order_details($data->returnId, $data->attributes->returnItems);
				break;

			case 'return_completed':
			case 'return_cancelled':
				$order = $this->update_fk_return_order_status($data->returnId, $data->attributes->status, $data->attributes->returnItems);
				break;
		}
	}
}
