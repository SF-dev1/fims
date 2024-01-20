<?php

/**
 * 
 */
class Stockist
{
	/*** Declare instance ***/
	private static $instance = NULL;

	private function __construct()
	{
	}

	public static function get_stock($pid, $type)
	{
		global $db;

		$db->where('item_id', $pid);
		$db->where('inv_status', $type);
		$db->get(TBL_INVENTORY);

		return $db->count;
	}

	/**
	 * if $method = "adjust" $to_type is required
	 */
	public function search_update_pid_by_mp_pid($account_id, $mp_id, $qty, $method, $type, $marketplace, $to_type = "", $order = array())
	{
		global $db;

		// $db->where('marketplace', $marketplace);
		$db->where('account_id', $account_id);
		$db->where('mp_id', $mp_id);
		$product = $db->getOne(TBL_PRODUCTS_ALIAS, array('pid', 'cid'));

		switch ($method) {
			case 'increase':
				if ($product['pid'] != 0) {
					$this->add_stock($product['pid'], $qty, $type);
				} else if ($product['cid'] != 0) {
					$db->where('cid', (int)$product['cid']);
					$products = $db->getOne(TBL_PRODUCTS_COMBO, array('pid'));
					$pids = json_decode($products['pid']);
					foreach ($pids as $pid) {
						$this->add_stock($pid, $qty, $type);
					}
				}
				break;

			case 'reduce':
				if ($product['pid'] != 0) {
					$this->reduce_stock($product['pid'], $qty, $type);
				} else if ($product['cid'] != 0) {
					$db->where('cid', (int)$product['cid']);
					$products = $db->getOne(TBL_PRODUCTS_COMBO, array('pid'));
					$pids = json_decode($products['pid']);
					foreach ($pids as $pid) {
						$this->reduce_stock($pid, $qty, $type);
					}
				}
				break;

			case 'adjust':
				if ($product['pid'] != 0) {
					$this->adjust_stock($product['pid'], $qty, $type, $to_type);
				} else if ($product['cid'] != 0) {
					$db->where('cid', (int)$product['cid']);
					$products = $db->getOne(TBL_PRODUCTS_COMBO, array('pid'));
					$pids = json_decode($products['pid']);
					foreach ($pids as $pid) {
						$this->adjust_stock($pid, $qty, $type, $to_type);
					}
				}
				break;
		}
	}

	/**/
	public function add_inventory_log($inv_id, $log_type, $log_details)
	{
		global $current_user, $db, $log;
		$user_id = $current_user['userID'];

		if ($inv_id == "" || is_null($inv_id) || $user_id == "" || is_null($user_id)) {
			$log->write(json_encode(debug_backtrace()), 'inventory-log');
			return false;
		}

		$log_details = array(
			'inv_id' => $inv_id,
			'log_type' => $log_type,
			'log_details' => $log_details,
			'user_id' => $user_id
		);
		if ($db->insert(TBL_INVENTORY_LOG, $log_details))
			return true;

		return false;
	}

	public function new_grn_inbound($grn_id, $lineItems, $lot_details, $uid = 1)
	{
		global $db, $log, $current_user;
		$capacity = count($lineItems);
		$expectedLocation = $this->get_expected_location_status("inbound", $capacity);
		$return = [];
		foreach ($lineItems as $lineItem) {
			$item_price = $this->calculate_item_price($lineItem['price'], $lot_details);
			for ($i = 0; $i < (int)$lineItem['received_qty']; $i++) {
				$inv_id = sprintf($lot_details["lot_number"] . '%06d', $uid);
				if ($lot_details['is_local'] === "1")
					$inv_id = sprintf($lot_details["lot_number"] . '%04d', $uid);
				$details = array(
					'inv_id' => $inv_id,
					'item_id' => $lineItem['item_id'],
					'item_price' => $item_price,
					'grn_id' => $grn_id,
					'lot_id' => $lot_details['lot_id'],
					'firm_id' => $lot_details['firm_id'],
					'is_ams_inv' => $lot_details['is_ams_inv'],
					'expectedLocation' => $expectedLocation,
					'inv_status' => 'inbound'
				);
				if ($db->insert(TBL_INVENTORY, $details)) {
					$this->add_inventory_log($inv_id, 'inbound', 'Inbound Inventory :: Lot# ' . $lot_details['lot_number'] . ' :: ' . sprintf('GRN_%06d', $grn_id));
					$log->write('Inventory added ' . $inv_id . ' Inbound Inventory :: Lot# ' . $lot_details['lot_number'] . ' :: ' . sprintf('GRN_%06d', $grn_id), 'inventory-inbound');
					$return[$inv_id] = 'success';
					$db->where("user_role", "administrator");
					$ids = $db->get(TBL_USERS, "userID");
					$task = array(
						"createdBy" => $current_user["userID"],
						"title" => "Products Ready For Tagging",
						"userRoles" => json_encode($ids),
						"status" => "0"
					);
					$db->insert(TBL_TASKS, $task);
				} else {
					$return[$inv_id] = 'unable to insert inv#: ' . $inv_id . ':: Error: ' . $db->getLastError() . '<br />';
					$log->write('Unable to insert inv ' . $inv_id . ' :: Error: ' . $db->getLastError(), 'inventory-inbound');
				}
				$uid++;
			}
		}

		return $return;
	}

	public function get_inventory_status($inv_id)
	{
		global $db;

		$db->where('inv_id', $inv_id);
		return $db->getValue(TBL_INVENTORY, 'inv_status');
	}

	public function get_qc_inprogress_time($inv_id)
	{
		global $db;

		$db->where('inv_id', $inv_id);
		$db->where('log_type', 'qc_cooling');
		$db->orderBy('log_date', 'DESC');
		return $db->getValue(TBL_INVENTORY_LOG, 'log_date');
	}

	public function update_inventory_details($inv_id, $details)
	{
		global $db, $log, $current_user;

		if ($inv_id) {
			$db->where('inv_id', $inv_id);
			if ($db->update(TBL_INVENTORY, $details)) {
				$db->where('inv_id', $inv_id);
				$item_id = $db->getValue(TBL_INVENTORY, 'item_id');

				$db->where("item_id", $item_id);
				$count = (int)$db->getValue(TBL_INVENTORY . ' i', "SUM(IF((i.inv_status = 'inbound' OR i.inv_status = 'qc_pending' OR i.inv_status = 'qc_verified'), 1, 0))");

				if ($count === 0) { // STOCK OUT LOG IF COUNT IS 0
					$inv_details = array(
						'pid' => $item_id,
						'user_id' => 0,
						'log_type' => 'IMS Inventory Status Change',
						'log_details' => 'Out of Stock'
					);
					$db->insert(TBL_PRODUCTS_LOG, $inv_details);
					$log->write("\n:: UID: " . $item_id . "\n:: Stock updated: " . json_encode($inv_details), 'stock-update');
				} else { // STOCK IN LOG IF THE LAST LOG IS STOCK OUT AND CURRENT COUNT IS >= 1
					$db->where('pid', $item_id);
					$db->where('log_type', 'IMS Inventory Status Change');
					$db->orderBy('log_date', 'DESC');
					// $db->where('log_details', 'Out of Stock');
					$status = $db->get(TBL_PRODUCTS_LOG, 2, 'log_details');
					if ($status[0]['log_details'] == "Out of Stock") {
						$inv_details = array(
							'pid' => $item_id,
							'user_id' => 0,
							'log_type' => 'IMS Inventory Status Change',
							'log_details' => 'In Stock'
						);
						$db->insert(TBL_PRODUCTS_LOG, $inv_details);
						$log->write("\n:: UID: " . $item_id . "\n:: Stock updated: " . json_encode($inv_details), 'stock-update');
					}
				}

				$log->write("\n:: UID: " . $inv_id . "\n:: Details updated: " . json_encode($details) . "\n:: User: " . $current_user['userID'] . "\n:: Back Trace: " . json_encode(debug_backtrace()), 'inventory-update');
				return true;
			}
		}
		return false;
	}

	public function update_inventory_status($inv_id, $status)
	{
		$details = array("inv_status" => trim($status));
		return $this->update_inventory_details($inv_id, $details);
	}

	private function calculate_item_price($item_price, $lot_details)
	{

		list($currency, $price) = explode(':', $item_price);

		if ($currency == "CNY")
			$base_price = floatval($price) * floatval($lot_details['lot_exchange']);
		else
			$base_price = floatval($price);

		$base_price += ($base_price * floatval(get_option('dead_stock_percentage')));
		if (!$lot_details['is_local']) {
			if ($lot_details['lot_carriage_type'] != "fixed")
				$base_price += floatval(get_option('trip_cost'));
		}

		if ($lot_details['lot_carriage_type'] == "flat" || $lot_details['lot_carriage_type'] == "fixed")
			$base_price += $lot_details['lot_carriage_value'];
		else
			$base_price += ($price * $lot_details['lot_carriage_value']);

		return round($base_price);
	}

	public function get_current_acp($item_id)
	{
		global $db;

		$db->where('item_id', $item_id);
		$db->where('(inv_status = ? OR inv_status = ? OR inv_status = ?)', array('qc_pending', 'qc_failed', 'qc_verified'));
		return number_format($db->getValue(TBL_INVENTORY, "AVG(item_price)"), 0, '.', '');
	}

	public function get_expected_location_status($status, $capacity)
	{
		global $db;
		$locationTitle = "";
		switch ($status) {
			case 'inbound':
				$locationTitle = "Warehouse Location";
				$db->where("locationTitle", $locationTitle, "LIKE");
				$db->where("status", "0"); // 0 => empty location
				$db->where("capacity", $capacity, ">="); // storage capacity of the location checking
				$locationId = $db->getValue(TBL_INVENTORY_LOCATIONS, "locationId");
				break;
			case 'printing':
				$locationTitle = "Printing Area";
				$db->where("locationTitle", $locationTitle, "LIKE");
				$db->where("status", ["0", "1"], "IN"); // 0 => empty location, 1 => partially used location
				$db->where("capacity", $capacity, ">="); // storage capacity of the location checking
				$db->where("availability", $capacity, ">="); // available storage space at the location
				$locationId = $db->getValue(TBL_INVENTORY_LOCATIONS, "locationId");
				break;
			case 'qc_pending':
				break;
			case 'qc_cooling':
				break;
			case 'qc_verified':
				break;
			case 'qc_failed':
				break;
			case 'components_requested':
				break;
		}

		return $locationId;
	}

	public static function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new Stockist;
		}
		return self::$instance;
	}
}
