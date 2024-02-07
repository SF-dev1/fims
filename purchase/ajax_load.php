<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action']) && trim($_REQUEST['action']) != "") {
	include(dirname(dirname(__FILE__)) . '/config.php');
	global $db;

	switch ($_REQUEST['action']) {
		case 'purchase_orders':
			$db->join(TBL_PARTIES . ' p', 'p.party_id=po.po_party_id', 'LEFT');
			$db->join(TBL_PURCHASE_ORDER_ITEMS . ' poi', 'poi.po_id=po.po_id', 'INNER');
			$db->groupBy('po.po_id');
			$db->orderBy('po.insertDate', 'DESC');
			$pos = $db->get(TBL_PURCHASE_ORDER . ' po', null, 'po.po_id, CONCAT("PO_", LPAD(po.po_id, 6, "0")) as "PO ID", p.party_name as "Supplier", SUM(item_qty) as "Ordered Quantity", po.po_total_amount as "Total Amount", po.po_status as "Status"');
			$return['data'] = $pos;

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'purchase_order_items':
			$db->join(TBL_PURCHASE_ORDER . ' po', 'poi.po_id=po.po_id', 'INNER');
			// $db->joinWhere(TBL_PURCHASE_ORDER.' po', 'po.po_status', 'created');
			$db->join(TBL_PARTIES . ' p', 'p.party_id=po.po_party_id', 'INNER');
			$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=poi.item_id', 'INNER');
			$db->orderBy('po.insertDate', 'DESC');
			$pos = $db->get(TBL_PURCHASE_ORDER_ITEMS . ' poi', null, 'poi.poi_id, poi.item_currency, CONCAT("PO_", LPAD(poi.po_id, 6, 0)) as "PO ID", p.party_name as "Supplier", pm.sku as "SKU", poi.item_qty as "Quantity", poi.item_received_qty as "Received Quantity", poi.item_price as "Rate", (poi.item_qty * poi.item_price) as "Total", poi.item_status as "Status", poi.po_id');
			$pos = array_map(
				function ($str) {
					return str_replace(array('CNY', 'INR'), array('&#165;', '&#8377;'), $str);
				},
				$pos
			);
			$return['data'] = $pos;

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'get_po':
			$po_id = $_GET['po_id'];
			$db->join(TBL_PARTIES . ' p', 'p.party_id=po.po_party_id', 'LEFT');
			$db->where('po_id', $po_id);
			$party = $db->getOne(TBL_PURCHASE_ORDER . ' po', 'p.party_id, p.party_name');

			$db->where('po_id', $po_id);
			$pos['items'] = $db->get(TBL_PURCHASE_ORDER_ITEMS . ' poi', null, 'poi.po_id, poi.item_id, poi.item_price, poi.item_qty, poi.item_qty as item_shipped_qty, poi.item_currency');
			$return = array_merge(array('po_id' => $po_id), $party, $pos);
			echo json_encode($return);
			break;

		case 'save_po':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$lineitems = $_POST['lineitems'];

			$status = ($_POST['type'] == "create") ? 'created' : 'draft';
			$details = array(
				'po_party_id' => $_POST['party_id'],
				'po_status' => $status,
			);

			if (!empty($_POST['po_id'])) {
				$po_id = $_POST['po_id'];
				$db->where('po_id', $po_id);

				$poi_status = $status;
				if ($status == 'created') {
					$details['createdDate'] = $db->now();
					$poi_status = "ordered";
				}

				$db->update(TBL_PURCHASE_ORDER, $details);

				$db->where('po_id', $po_id);
				if ($db->delete(TBL_PURCHASE_ORDER_ITEMS)) {
					$items = array();
					foreach ($lineitems as $lineitem) {
						$items[] = array(
							'po_id' => $po_id,
							'item_id' => $lineitem['sku'],
							'item_qty' => $lineitem['qty'],
							'item_status' => $poi_status
						);
					}

					if ($db->insertMulti(TBL_PURCHASE_ORDER_ITEMS, $items)) {
						$msg = ($status == 'draft' ? 'PO draft Successfull updated' : 'PO created Successfully');
						echo json_encode(array('type' => 'success', 'msg' => $msg, 'po_id' => $po_id));
					} else {
						$msg = ($status == 'draft' ? 'Unable to update PO Draft' : 'Unable to update PO');
						echo json_encode(array('type' => 'error', 'msg' => $msg, 'po_id' => $po_id));
					}
				} else {
					$msg = ($status == 'draft' ? 'Unable to update PO Draft' : 'Unable to update PO');
					echo json_encode(array('type' => 'error', 'msg' => $msg, 'po_id' => $po_id));
				}
			} else {
				$details['insertDate'] = $db->now();

				$poi_status = $status;
				if ($status == 'created') {
					$details['createdDate'] = $db->now();
					$poi_status = "ordered";
				}

				if ($po_id = $db->insert(TBL_PURCHASE_ORDER, $details)) {
					$items = array();
					foreach ($lineitems as $lineitem) {
						$items[] = array(
							'po_id' => $po_id,
							'item_id' => $lineitem['sku'],
							'item_qty' => $lineitem['qty'],
							'item_status' => $poi_status
						);
					}

					if ($db->insertMulti(TBL_PURCHASE_ORDER_ITEMS, $items)) {
						echo json_encode(array('type' => 'success', 'msg' => 'PO Successfully created with ' . $status . ' status', 'po_id' => $po_id));
					} else {
						echo json_encode(array('type' => 'error', 'msg' => 'Unable to create PO', 'po_id' => $po_id));
					}
				} else {
					echo json_encode(array('type' => 'error', 'msg' => 'Unable to create PO'));
				}
			}
			break;

		case 'delete_po':
			$return = "";
			$db->where('po_id', $_REQUEST['po_id']);
			$count = $db->getValue(TBL_PURCHASE_ORDER_ITEMS_LOT, "count(*)");
			if ($count < 0) {
				$db->where('po_id', $_REQUEST['po_id']);
				if ($db->delete(TBL_PURCHASE_ORDER_ITEMS)) {
					$db->where('po_id', $_REQUEST['po_id']);
					if ($db->delete(TBL_PURCHASE_ORDER)) {
						$return = array('type' => 'success', 'msg' => 'Purchase Order Successfully deleted.');
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to delete Purchase Order.');
					}
				} else {
					$return = array('type' => 'error', 'msg' => 'Unable to delete Purchase Order Items', 'error' => $db->getLastError());
				}
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to delete Purchase Order Items<br /><i>Lot Number Already Assigned</i>');
			}

			echo json_encode($return);
			break;

		case 'add_lot':
			$details = array(
				"lot_number" => $_POST['lot_number'],
				"lot_carrierCode" => $_POST['carrier_code'],
				"lot_carrier" => $_POST['lot_carrier'],
				"lot_carriageType" => $_POST['carrier_type'],
				"lot_carriageValue" => $_POST['carrier_value'],
				"lot_ex" => $_POST['carrier_ex'],
				"lot_status" => 'created',
				"lot_createdDate" => date('Y-m-d H:i:s'),
			);

			$table = TBL_PURCHASE_LOT;
			if (isset($_POST['is_local']) && $_POST['is_local'])
				$table = TBL_PURCHASE_LOT_LOCAL;

			if ($db->insert($table, $details)) {
				$return = array('type' => 'success', 'msg' => 'Successfully created new lot.');
			} else {
				$return = array('type' => 'error', 'msg' => 'Error creating new lot.', 'error' => $db->getLastError());
			}

			echo json_encode($return);
			break;

		case 'get_lot_details':
			$db->join(TBL_PARTIES . ' p', "p.party_id=pl.lot_carrier");
			$db->where('lot_id', $_GET['lot_id']);
			$is_local = $_GET['is_local'];
			$table = TBL_PURCHASE_LOT;
			if ($is_local)
				$table = TBL_PURCHASE_LOT_LOCAL;
			$lot_details['lot_details'] = $db->objectBuilder()->getOne($table . ' pl', array('lot_id', 'lot_number', 'lot_carrierCode', 'party_name as lot_carrier', 'lot_carriageType', 'lot_carriageValue', 'lot_ex'));
			$lot_details['lot_details']->is_local = $is_local;

			$fields = array('poil.po_id', 'poil.item_id', 'poi.item_qty', 'IFNULL(poil.item_shipped_qty, 0) as item_shipped_qty, CONCAT(poi.item_currency, ":", poi.item_price) as item_price');
			if (isset($_GET['grn_id']) && !empty($_GET['grn_id'])) {
				$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . " poil", "poil.item_id=pgi.item_id", "LEFT");
				$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . " poil", "poil.po_id=pgi.po_id");
				$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . " poil", "poil.lot_local", $is_local);
			}
			$db->join(TBL_PURCHASE_ORDER_ITEMS . ' poi', "poi.item_id=poil.item_id", "LEFT");
			$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS . " poi", "poi.po_id=poil.po_id");
			$db->join(TBL_PRODUCTS_MASTER . ' pm', "pm.pid=poi.item_id", "LEFT");

			if (isset($_GET['grn_id']) && !empty($_GET['grn_id'])) {
				$db->where('grn_id', $_GET['grn_id']);
				$fields[0] = 'pgi.po_id';
				$fields[1] = 'pgi.item_id';
				$fields[] = 'pgi.item_qty as grn_qty';
				$fields[] = 'pgi.item_box as grn_ctn';
				$db->orderBy('pm.sku', 'ASC');
				$lot_details['items'] = $db->objectBuilder()->get(TBL_PURCHASE_GRN_ITEMS . " pgi", NULL, $fields);
			} else {
				if (isset($_GET['lot_id']) && !empty($_GET['lot_id']))
					$db->where('lot_id', $_GET['lot_id']);

				$db->where("poil.lot_local", $is_local);
				$db->orderBy('pm.sku', 'ASC');
				$lot_details['items'] = $db->objectBuilder()->get(TBL_PURCHASE_ORDER_ITEMS_LOT . " poil", NULL, $fields);
			}

			echo json_encode($lot_details);
			break;

		case 'mark_lot_shipped':
			// if (!isset($_POST('lot_id')) || empty($_POST('lot_id')))
			// 	continue;
			$lot_id = $_REQUEST['lot_id'];
			$is_local = (int)$_REQUEST['is_local'];
			$table = TBL_PURCHASE_LOT;
			if ($is_local)
				$table = TBL_PURCHASE_LOT_LOCAL;

			// UPDATE POL
			$db->where('lot_id', $lot_id);
			if ($db->update($table, array('lot_status' => 'shipped', 'lot_shippedDate' => date('Y-m-d')))) {
				// UPDATE POIL
				$db->where('lot_id', $lot_id);
				$db->where('lot_local', $is_local);
				if ($db->update(TBL_PURCHASE_ORDER_ITEMS_LOT, array('item_status' => 'shipped'))) {
					$db->where('lot_id', $lot_id);
					$db->where('lot_local', $is_local);
					$db->join(TBL_PURCHASE_ORDER_ITEMS . ' poi', "poi.item_id=poil.item_id");
					$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS . " poi", "poi.po_id=poil.po_id");
					$shipped_products = $db->get(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', NULL, array("poil.item_id", "poil.po_id", "poi.item_qty", "poil.item_shipped_qty", "poil.lot_local"));
					foreach ($shipped_products as $key => $shipped_product) {
						$db->where('item_id', $shipped_product['item_id']);
						$db->where('po_id', $shipped_product['po_id']);
						$db->where('lot_local', $shipped_product['lot_local']);
						$db->where('item_status', 'shipped');
						$shipped_qty = $db->getOne(TBL_PURCHASE_ORDER_ITEMS_LOT, "CONVERT(IFNULL(SUM(item_shipped_qty), 0), UNSIGNED INTEGER) as total_shipped_qty");

						if ($shipped_qty['total_shipped_qty'] == $shipped_product['item_qty'])
							$poi_status = 'shipped';
						else
							$poi_status = 'partial_shipped';

						// UPDATE POI
						$db->where('item_id', $shipped_product['item_id']);
						$db->where('po_id', $shipped_product['po_id']);
						if ($db->update(TBL_PURCHASE_ORDER_ITEMS, array('item_status' => $poi_status))) {

							$db->where('po_id', $shipped_product['po_id']);
							$statuses = $db->objectBuilder()->get(TBL_PURCHASE_ORDER_ITEMS, NULL, 'item_status');
							if (in_array('ordered', array_column($statuses, 'item_status')))
								$ordered = true;
							else
								$ordered = false;

							if (in_array('RTS', array_column($statuses, 'item_status')))
								$rts = true;
							else
								$rts = false;

							if (in_array('partial_shipped', array_column($statuses, 'item_status')))
								$partial_shipped = true;
							else
								$partial_shipped = false;

							if (!$ordered && !$rts && !$partial_shipped)
								$po_status = "shipped";
							else
								$po_status = "partial_shipped";

							// UPDATE PO
							$db->where('po_id', $shipped_product['po_id']);
							if ($db->update(TBL_PURCHASE_ORDER, array('po_status' => $po_status)))
								$return = array('type' => 'success', 'msg' => 'Successfully marked lot shipped');
							else
								$return = array('type' => 'error', 'msg' => 'Failed to mark lot shipped', 'msg' => 'PO STATUS UPDATE: ' . $db->getLastError());
						} else {
							$return = array('type' => 'error', 'msg' => 'Failed to mark lot shipped', 'msg' => 'POI STATUS UPDATE: ' . $db->getLastError());
						}
					}
				} else {
					$return = array('type' => 'error', 'msg' => 'Failed to mark lot shipped', 'msg' => 'POIL STATUS UPDATE: ' . $db->getLastError());
				}
			} else {
				$return = array('type' => 'error', 'msg' => 'Failed to mark lot shipped', 'msg' => 'PL STATUS UPDATE: ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'get_po_items':
			if (isset($_GET['lot_id']) && !empty($_GET['lot_id'])) {
				$db->where('lot_id', $_GET['lot_id']);
				$current_lot = $db->objectBuilder()->get(TBL_PURCHASE_ORDER_ITEMS_LOT, NULL, array('item_id', 'po_id', 'item_shipped_qty', 'CONCAT("lineitems_", po_id, "_", item_id) as itemName'));
			}

			$db->join(TBL_PRODUCTS_MASTER . ' pm', "pm.pid=poi.item_id");
			$db->join(TBL_PURCHASE_ORDER . ' po', "po.po_id=poi.po_id");
			$db->join(TBL_PARTIES . ' p', "p.party_id=po.po_party_id");
			$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . " poil", "poil.item_id=poi.item_id", "LEFT");
			$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . " poil", "poil.po_id=poi.po_id");
			$db->where('(poi.item_status = ? OR poi.item_status = ?)', array('ordered', 'partial_shipped'));
			$db->orderBy('po_id', 'ASC');
			$products_details = $db->objectBuilder()->get(TBL_PURCHASE_ORDER_ITEMS . ' poi', NULL, array('po.po_id', 'poi.item_id', 'p.party_name', 'pm.sku', 'pm.thumb_image_url', 'poi.item_qty', 'poi.item_received_qty', 'IFNULL(poil.item_shipped_qty, 0) as item_shipped_qty'));
			// var_dump($products_details);

			// if ($products_details){
			$products = array();
			foreach ($products_details as $product_key => $product) {
				if (!isset($products[$product->po_id . '_' . $product->item_id])) {
					$products[$product->po_id . '_' . $product->item_id]['item_qty'] = $product->item_qty;
					$products[$product->po_id . '_' . $product->item_id]['item_qty'] -= $product->item_shipped_qty;
					$products[$product->po_id . '_' . $product->item_id]['indexes'][] = $product_key;
					$products[$product->po_id . '_' . $product->item_id]['item_details'] = $product;
				} else {
					$products[$product->po_id . '_' . $product->item_id]['item_qty'] -= $product->item_shipped_qty;
					$products[$product->po_id . '_' . $product->item_id]['indexes'][] = $product_key;
					$products[$product->po_id . '_' . $product->item_id]['item_details'] = $product;
				}
			}

			$distrubuted_data = array();
			foreach ($products as $product) {
				if ($product['item_qty'] > 0)
					$distrubuted_data[$product['item_details']->party_name][] = $product['item_details'];
			}

			$content = '';
			$j = 0;
			foreach ($distrubuted_data as $po => $po_products) {
				$content .= '<div class="well well-lg">';
				$content .= '<div class="row">';
				$content .= '<h4>' . $po . '</h4>';
				$i = 0;
				foreach ($po_products as $product) {
					$item_name = 'lineitems_' . $product->po_id . '_' . $product->item_id;
					$item_qty = "";
					$checked_checkbox = "checked";
					$inactive = "inactive";
					if (!is_null($current_lot) && in_array($item_name, array_column($current_lot, 'itemName'))) { // search value in the array
						$key = array_search($item_name, array_column($current_lot, 'itemName'));
						$item_qty = $current_lot[$key]->item_shipped_qty;
						// $checked_checkbox = "checked";
						$inactive = "";
					}

					$available_qty = ((int)$product->item_qty - (int)$product->item_received_qty - (int)$product->item_shipped_qty);
					$content .= '<div class="col-sm-12 col-md-2" id="lineitems_' . $product->po_id . '_' . $product->item_id . '">
						<div class="thumbnail">
							<img onerror="this.onerror=null;this.src=\'https://via.placeholder.com/150x100\';" src="' . IMAGE_URL . '/uploads/products/' . $product->thumb_image_url . '" alt="" style="width: auto; height: 100px;">
							<div>
								<h6>' . $product->sku . '</h6>
								<h6>PO_' . sprintf('%06d', $product->po_id) . '</h6>
								<div class="qty_details">
									<p> Avl:' . $available_qty . '/' . $product->item_qty . '</p>
									<div class="input-group input-small">
										<input id="lineItem_' . $j . '" data-item_id="' . $product->item_id . '" data-po_id="' . $product->po_id . '" data-qty="' . $available_qty . '" data-sku="' . $product->sku . '" type="number" name="lineitems_' . $product->po_id . '_' . $product->item_id . '" class="form-control input-text lineitem-value ' . $inactive . '" min="0" max="' . $available_qty . '" placeholder="' . $available_qty . '" value="' . $available_qty . '">
										<span class="input-group-addon checkbox_' . $j . '">
											<input type="checkbox" ' . $checked_checkbox . ' class="checkerbox item_checkbox" data-inputitem="lineItem_' . $j . '" />
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>';
					$i++;
					$j++;
					if ($i % 6 == 0)
						$content .= '</div><div class="row">';
				}
				$content .= '</div>';
				$content .= '</div>';
			}

			echo '<form action="#" class="form-horizontal" id="lineItem_Model" role="form">
				<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
						<h4 class="modal-title">Select Product</h4>
					</div>
					<div class="modal-body">
						' . $content . '
					</div>
					<div class="modal-footer">
						<div class="form-action">
							<button type="reset" data-dismiss="modal" class="btn btn-default">Close</button>
							<input type="submit" class="btn btn-success" value="Save changes" />
						</div>
					</div>
				</form>';
			break;

		case 'add_pl_items':
			$lineitems = $_POST['lineitems'];
			if ($_POST['update_lot_id']) {
				$lot_id = $_POST['update_lot_id'];

				$db->where('lot_id', $lot_id);
				$db->delete(TBL_PURCHASE_ORDER_ITEMS_LOT);
			} else {
				$lot_id = $_POST['lot_id'];
			}

			$details = array();
			foreach ($lineitems as $lineitem) {
				$details[] = array(
					'lot_id' => $lot_id,
					'item_id' => $lineitem['sku'],
					'po_id' => $lineitem['po_id'],
					'item_shipped_qty' => $lineitem['qty'],
					'lot_local' => $_POST['is_local'],
					'item_status' => 'rts',
					'insertDate' => date('Y-m-d H:i:s'),
				);
			}

			if ($db->insertMulti(TBL_PURCHASE_ORDER_ITEMS_LOT, $details)) {
				$return = array('type' => 'success', 'msg' => 'Successfully created new Pack List');
			} else {
				$return = array('type' => 'success', 'msg' => 'Error creating new Pack List', 'error' => $db->getLastError());
			}

			echo (json_encode($return));
			break;

		case 'get_packing_list':
			$db->join(TBL_PARTIES . ' p', "p.party_id=pl.lot_carrier");
			// $db->join(TBL_PURCHASE_ORDER_ITEMS_LOT.' poil', 'poil.lot_id=pl.lot_id');
			// $db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT.' poil', 'poil.lot_local', 0);
			// $db->join(TBL_PURCHASE_ORDER. ' po', "po.po_id=poil.po_id");
			// $db->join(TBL_PARTIES. ' pp', "pp.party_id=pl.po_party_id");
			// $db->join(TBL_PURCHASE_ORDER_ITEMS. ' poi', "poi.item_id=poil.item_id");
			// $db->joinWhere(TBL_PURCHASE_ORDER_ITEMS ." poi", "poi.po_id=poil.po_id");
			$lot_products_details = $db->objectBuilder()->get(TBL_PURCHASE_LOT . ' pl', NULL, array('pl.lot_id', 'pl.lot_number', 'p.party_name', 'pl.lot_carrierCode', 'IFNULL(pl.lot_shippedDate, "Not Shipped") as lot_shippedDate', 'IFNULL(pl.lot_receivedDate, "Not Received") as lot_receivedDate', 'pl.lot_status', '0 as lot_local'));

			$db->join(TBL_PARTIES . ' p', "p.party_id=pll.lot_carrier");
			// $db->join(TBL_PURCHASE_ORDER_ITEMS_LOT.' poil', 'poil.lot_id=pll.lot_id');
			// $db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT.' poil', 'poil.lot_local', 1);
			// $db->join(TBL_PURCHASE_ORDER. ' po', "po.po_id=poil.po_id");
			// $db->join(TBL_PARTIES. ' pp', "pp.party_id=po.po_party_id");
			// $db->join(TBL_PURCHASE_ORDER_ITEMS. ' poi', "poi.item_id=poil.item_id");
			// $db->joinWhere(TBL_PURCHASE_ORDER_ITEMS ." poi", "poi.po_id=poil.po_id");
			$local_products_details = $db->objectBuilder()->get(TBL_PURCHASE_LOT_LOCAL . ' pll', NULL, array('pll.lot_id', 'pll.lot_number', 'p.party_name', 'pll.lot_carrierCode', 'IFNULL(pll.lot_shippedDate, "Not Shipped") as lot_shippedDate', 'IFNULL(pll.lot_receivedDate, "Not Received") as lot_receivedDate', 'pll.lot_status', '1 as lot_local'));
			$lot_details = array_merge($lot_products_details, $local_products_details);

			$i = 1;
			foreach ($lot_details as $lot_detail) {
				if ($lot_detail->lot_shippedDate != "Not Shipped")
					$lot_detail->lot_shippedDate = date('Y-m-d', strtotime($lot_detail->lot_shippedDate));
				if ($lot_detail->lot_receivedDate != "Not Received")
					$lot_detail->lot_receivedDate = date('Y-m-d', strtotime($lot_detail->lot_shippedDate));
				$i++;
				$return['data'][] = $lot_detail;
			}

			// $return['data'] = $products_details;

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'get_packing_list_items':
			$db->join(TBL_PARTIES . ' p', "p.party_id=pl.lot_carrier");
			$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.lot_id=pl.lot_id');
			$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.lot_local', 0);
			$db->join(TBL_PURCHASE_ORDER . ' po', "po.po_id=poil.po_id");
			$db->join(TBL_PARTIES . ' pp', "pp.party_id=po.po_party_id");
			$db->join(TBL_PRODUCTS_MASTER . ' pm', "pm.pid=poil.item_id");
			$db->join(TBL_PURCHASE_ORDER_ITEMS . ' poi', "poi.item_id=poil.item_id");
			$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS . " poi", "poi.po_id=poil.po_id");
			$lot_products_details = $db->objectBuilder()->get(TBL_PURCHASE_LOT . ' pl', NULL, array('pl.lot_id', 'pl.lot_number', 'p.party_name', 'pl.lot_carrierCode', 'pp.party_name', 'pm.sku', 'poil.item_shipped_qty', 'poil.item_received_qty', 'IFNULL(pl.lot_shippedDate, "Not Shipped") as lot_shippedDate', 'IFNULL(pl.lot_receivedDate, "Not Received") as lot_receivedDate', 'poil.lot_local', 'poil.item_status'));

			$db->join(TBL_PARTIES . ' p', "p.party_id=pll.lot_carrier");
			$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.lot_id=pll.lot_id');
			$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.lot_local', 1);
			$db->join(TBL_PURCHASE_ORDER . ' po', "po.po_id=poil.po_id");
			$db->join(TBL_PARTIES . ' pp', "pp.party_id=po.po_party_id");
			$db->join(TBL_PRODUCTS_MASTER . ' pm', "pm.pid=poil.item_id");
			$db->join(TBL_PURCHASE_ORDER_ITEMS . ' poi', "poi.item_id=poil.item_id");
			$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS . " poi", "poi.po_id=poil.po_id");
			$local_products_details = $db->objectBuilder()->get(TBL_PURCHASE_LOT_LOCAL . ' pll', NULL, array('pll.lot_id', 'pll.lot_number', 'p.party_name', 'pll.lot_carrierCode', 'pp.party_name', 'pm.sku', 'poil.item_shipped_qty', 'poil.item_received_qty', 'IFNULL(pll.lot_shippedDate, "Not Shipped") as lot_shippedDate', 'IFNULL(pll.lot_receivedDate, "Not Received") as lot_receivedDate', 'poil.lot_local', 'poil.item_status'));
			$products_details = array_merge($lot_products_details, $local_products_details);

			$i = 1;
			foreach ($products_details as $products_detail) {
				if ($products_detail->lot_shippedDate != "Not Shipped")
					$products_detail->lot_shippedDate = date('Y-m-d', strtotime($products_detail->lot_shippedDate . ' + 1 day'));
				if ($products_detail->lot_receivedDate != "Not Received")
					$products_detail->lot_receivedDate = date('Y-m-d', strtotime($products_detail->lot_shippedDate . ' + 31 days'));
				$i++;
				$return['data'][] = $products_detail;
			}

			// $return['data'] = $products_details;

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'save_grn':
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
			echo '<pre>';
			$lineitems = $_POST['lineitems'];
			$status = ($_POST['type'] == "create") ? 'created' : 'draft';
			$lot_id = $_POST['lot_id'];
			$lot_details = $_POST['lot_details'];
			$lot_details['lot_id'] = $lot_id;

			$details = array(
				'lot_id' => $lot_id,
				'grn_total_qty' => $_POST['total_qty'],
				'grn_total_box' => $_POST['total_ctn'],
				'grn_status' => 'draft',
				'grn_local' => (int)$lot_details['is_local'],
			);

			if ($lot_details['lot_carriage_type'] == "fixed")
				$lot_details['lot_carriage_value'] = (int)$lot_details['lot_carriage_value'] / (int)$_POST['total_qty'];

			$po_id = $lineitems[0]["po_id"];
			$db->join(TBL_PURCHASE_ORDER . ' po', 'po.po_party_id=p.party_id');
			$db->joinWhere(TBL_PURCHASE_ORDER . ' po', 'po.po_id', $po_id);
			$party_details = $db->getOne(TBL_PARTIES . ' p', 'p.party_ams, p.party_firm_id');
			$lot_details['is_ams_inv'] = $party_details['party_ams'];
			$lot_details['firm_id'] = $party_details['party_firm_id'];
			// var_dump($lot_details);
			// exit;

			if (!empty($_POST['grn_id'])) {
				$grn_id = $_POST['grn_id'];
				$db->where('grn_id', $grn_id);

				if ($status == 'created') {
					$details['grn_status'] = $status;
					$details['createdDate'] = $db->now();
				}

				$db->update(TBL_PURCHASE_GRN, $details);

				$db->where('grn_id', $grn_id);
				if ($db->delete(TBL_PURCHASE_GRN_ITEMS)) {
					$items = array();
					foreach ($lineitems as $lineitem) {
						$items[] = array(
							"grn_id" => $grn_id,
							"po_id" => $lineitem["po_id"],
							"item_id" => $lineitem["item_id"],
							"item_qty" => $lineitem["received_qty"],
							"item_box" => $lineitem["received_box"],
							'insertDate' => $db->now()
						);
					}

					if ($db->insertMulti(TBL_PURCHASE_GRN_ITEMS, $items)) {
						$msg = ($status == 'draft' ? 'GRN draft Successfull updated' : 'GRN created Successfully');
						$return = array('type' => 'success', 'msg' => $msg, 'grn_id' => $grn_id);
						if ($status == 'created') {
							if (update_on_grn_creation($grn_id, $lineitems, $lot_details)) {
								$return = array('type' => 'success', 'msg' => $msg, 'grn_id' => $grn_id, 'lot_id' => $lot_id, 'lot_details' => $lot_details);
							} else {
								$return = array('type' => 'error', 'msg' => $msg, 'grn_id' => $grn_id, 'lot_id' => $lot_id, 'error' => $db->getLastError());
							}
						}
					} else {
						$msg = ($status == 'draft' ? 'Unable to update GRN Draft' : 'Unable to update GRN');
						$return = array('type' => 'error', 'msg' => $msg, 'grn_id' => $grn_id, 'lot_id' => $lot_id, 'error' => $db->getLastError());
					}
				} else {
					$msg = ($status == 'draft' ? 'Unable to update GRN Draft' : 'Unable to update GRN');
					$return = array('type' => 'error', 'msg' => $msg, 'grn_id' => $grn_id, 'lot_id' => $lot_id, 'error' => $db->getLastError());
				}
			} else {
				$details['insertDate'] = $db->now();

				if ($status == 'created') {
					$details['grn_status'] = $status;
					$details['createdDate'] = $db->now();
				}

				if ($grn_id = $db->insert(TBL_PURCHASE_GRN, $details)) {
					$items = array();
					foreach ($lineitems as $lineitem) {
						$po_id = $lineitem["po_id"] ? $lineitem["po_id"] : 0;
						$items[] = array(
							"grn_id" => $grn_id,
							"po_id" => $po_id,
							"item_id" => $lineitem["item_id"],
							"item_qty" => $lineitem["received_qty"],
							"item_box" => $lineitem["received_box"],
							'insertDate' => date('Y-m-d H:i:s'),
						);
					}

					if ($db->insertMulti(TBL_PURCHASE_GRN_ITEMS, $items)) {
						if ($status == 'created') {
							update_on_grn_creation($grn_id, $lineitems, $lot_details);
						}
						$return = array('type' => 'success', 'msg' => 'GRN Successfully created with ' . $status . ' status', 'grn_id' => $grn_id, 'lot_details' => $lot_details);
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to create GRN', 'grn_id' => $grn_id, 'error' => $db->getLastError());
					}
				} else {
					$return = array('type' => 'error', 'msg' => 'Unable to create GRN', 'error' => $db->getLastError());
				}
			}

			echo (json_encode($return));
			break;

		case 'get_grn':
			$db->join(TBL_PURCHASE_LOT . ' pl', 'pl.lot_id=pg.lot_id');
			$db->where('grn_local', '0');
			$grn = $db->get(TBL_PURCHASE_GRN . ' pg', NULL, array('CONCAT("GRN_", LPAD(grn_id, 6, "0")) as grn_no', 'pl.lot_number', 'grn_total_qty', 'grn_total_box', 'grn_status', 'grn_id', 'pg.lot_id'));

			$db->join(TBL_PURCHASE_LOT_LOCAL . ' pl', 'pl.lot_id=pg.lot_id', 'INNER');
			$db->where('grn_local', '1');
			$grn_local = $db->get(TBL_PURCHASE_GRN . ' pg', NULL, array('CONCAT("GRN_", LPAD(grn_id, 6, "0")) as grn_no', 'pl.lot_number', 'grn_total_qty', 'grn_total_box', 'grn_status', 'grn_id', 'pg.lot_id'));
			$return['data'] = array_merge($grn, $grn_local);

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'view_grn_items':
			$grn = $_GET['grn_id'];
			break;

			/*case 'manual_product_add_to_lot':
			$grn_id = 7;
			$lineItems = array(
				'item_id' => '770',
				'received_qty' => 240, // additional
				'price' => 'CNY:8.50',
			);

			$lot_details = array(
				'lot_number' => 'SF04AD',
				'lot_id' => 4
			);

			echo '<pre>';
			$stockist->new_grn_inbound($grn_id, $lineItems, $lot_details, 10101);
		break;*/
	}
} else {
	exit('hmmmm... trying to hack in ahh!');
}

function update_on_grn_creation($grn_id, $lineItems, $lot_details)
{
	global $stockist, $db;

	// GET ITEMS TO INVENTORY TABLE
	$stockist->new_grn_inbound($grn_id, $lineItems, $lot_details);

	// UPDATE LOT STATUS
	$return = array();
	$db->where('lot_id', $lot_details['lot_id']);
	$table = TBL_PURCHASE_LOT;
	if ($lot_details['is_local'])
		$table = TBL_PURCHASE_LOT_LOCAL;
	if ($db->update($table, array('lot_status' => 'received', 'lot_receivedDate' => date('Y-m-d')))) {
		// UPDATE STATUSES AND RECEIVED QUANTITY
		foreach ($lineItems as $lineItem) {
			// UPDATE PO ITEM LOT RECEIVED QTY AND STATUS
			$db->where('item_id', $lineItem['item_id']);
			$db->where('po_id', $lineItem['po_id']);
			$poil_item_received_qty = $db->getValue(TBL_PURCHASE_ORDER_ITEMS_LOT, 'item_received_qty');
			$db->where('item_id', $lineItem['item_id']);
			$db->where('po_id', $lineItem['po_id']);
			if ($db->update(TBL_PURCHASE_ORDER_ITEMS_LOT, array('item_status' => 'received', 'item_received_qty' => (int)$poil_item_received_qty + (int)$lineItem['received_qty'], 'item_received_date' => date('Y-m-d H:i:s')))) {
				$db->where('item_id', $lineItem['item_id']);
				$db->where('po_id', $lineItem['po_id']);
				// $db->where('item_status', 'shipped');
				$shipped_qty = $db->getOne(TBL_PURCHASE_ORDER_ITEMS_LOT, "CONVERT(IFNULL(SUM(item_shipped_qty), 0), UNSIGNED INTEGER) as total_shipped_qty");

				if ($shipped_qty['total_shipped_qty'] == $lineItem['received_qty'])
					$poi_status = 'received';
				else
					$poi_status = 'partial_shipped_received';

				// UPDATE PO ITEM RECEIVED QTY AND STATUS
				$db->where('item_id', $lineItem['item_id']);
				$db->where('po_id', $lineItem['po_id']);
				$poi_item_received_qty = $db->getValue(TBL_PURCHASE_ORDER_ITEMS, 'item_received_qty');
				$db->where('item_id', $lineItem['item_id']);
				$db->where('po_id', $lineItem['po_id']);
				if ($db->update(TBL_PURCHASE_ORDER_ITEMS, array('item_status' => $poi_status, 'item_received_qty' => (int)$poi_item_received_qty + (int)$lineItem['received_qty']))) {
					$db->where('po_id', $lineItem['po_id']);
					$statuses = $db->objectBuilder()->get(TBL_PURCHASE_ORDER_ITEMS, NULL, 'item_status');
					if (in_array('shipped', array_column($statuses, 'item_status')))
						$shipped = true;
					else
						$shipped = false;

					if (in_array('partial_shipped', array_column($statuses, 'item_status')))
						$partial_shipped = true;
					else
						$partial_shipped = false;

					if (!$shipped && !$partial_shipped)
						$po_status = "received";
					else
						$po_status = "partial_shipped_received";

					// UPDATE PO STATUS
					$db->where('po_id', $lineItem['po_id']);
					if ($db->update(TBL_PURCHASE_ORDER, array('po_status' => $po_status))) {
						$return[] = true;
					}
				} else {
					$return[] = false;
				}
			} else {
				$return[] = false;
			}
		}
	} else {
		$return[] = false;
	}

	if (in_array(false, $return))
		return false;

	return true;
}
