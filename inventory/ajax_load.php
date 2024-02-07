<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
	include(dirname(dirname(__FILE__)) . '/config.php');
	include(ROOT_PATH . '/includes/vendor/autoload.php');
	global $db, $current_user, $log, $stockist;

	switch ($_REQUEST['action']) {
		case 'lookup_item':
			$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
			$db->where("i.inv_id", $_POST["uid"]);
			$product = $db->getOne(TBL_INVENTORY . ' i', "i.inv_status, p.sku");
			if ($product) {
				if (in_array($product['inv_status'], ["components_requested", "qc_failed", "qc_pending", "qc_verified", "qc_cooling", "inbound"]))
					echo json_encode(array("type" => "success", "msg" => "The item status is " . $product['inv_status'], "sku" => $product['sku']));
				else
					echo json_encode(array("type" => "error", "msg" => "The item status is " . $product['inv_status']));
			} else {
				echo json_encode(array("type" => "error", "msg" => ("Item not found")));
			}
			break;

		case 'create_box':
			$items = json_decode($_POST["items"]);
			$insert_id = $db->insert(TBL_INVENTORY_BOX, array("quantity" => count($items), "createdDate" => date("Y-m-d H:i:s")));
			if ($insert_id) {
				$state_id = $db->insert(TBL_INVENTORY_STATE, array("boxId" => $insert_id, "invId" => implode(",", $items), "userId" => $current_user["userID"]));
				if ($state_id) {
					echo json_encode(array(
						'type' => 'success', 'box_id' => sprintf('%09d', $insert_id),
						'print' => get_option('print_box_label')
					));
				} else
					echo json_encode(array('type' => 'error', 'msg' => 'Unable to save state', 'error' => $db->getLastError()));
			} else {
				echo json_encode(array('type' => 'error', 'msg' => 'Unable to create cartoon', 'error' => $db->getLastError()));
			}
			break;

		case 'create_ctn':
			$size = $_POST["size"];
			$ctnId = $db->insert(TBL_INVENTORY_CTN, array("quantity" => $size, "createdDate" => date("Y-m-d H:i:s")));
			$db->where("userId", $current_user["userID"]);
			$box = $db->get(TBL_INVENTORY_STATE, null, "boxId, invId");
			if ($box) {
				$resultArray = [];
				foreach ($box as $row) {
					$invIds = explode(",", $row["invId"]);
					$db->where("inv_id", $invIds, "IN");
					$db->update(TBL_INVENTORY, array("ctn_id" => $ctnId, "box_id" => $row["boxId"]));

					$resultArray = array_map(function ($item) use ($ctnId, $current_user, $row) {
						return [
							"inv_id" => $item,
							"log_type" => "moved", "log_details" => "Moved Inventory :: Carton# CTN" . sprintf('%09d', $ctnId) . " :: Box# BOX" . sprintf('%09d', $row["boxId"]), "user_id" => $current_user["userID"]
						];
					}, $invIds);
				}
				if ($resultArray) {
					$db->insertMulti(TBL_INVENTORY_LOG, $resultArray);
				}
				$db->where("userId", $current_user["userID"]);
				$db->delete(TBL_INVENTORY_STATE);
				echo json_encode(array("type" => "success", "ctn_id" => sprintf('%09d', $ctnId), "print" => get_option("print_ctn_label")));
			} else {
				echo json_encode(array('type' => 'error', 'msg' => 'No active box found'));
			}
			break;

		case 'get_box':
			$db->where("userId", $current_user["userID"]);
			$box = $db->get(TBL_INVENTORY_STATE, null, "boxId, invId");
			if ($box) {
				echo json_encode(array("type" => "success", "data" => $box));
			} else {
				echo json_encode(array("type" => "error", "msg" => "No active box found"));
			}
			break;
		case 'move_item':
			$db->where('inv_id', $_POST['uid']);
			$data = $db->get(TBL_INVENTORY, null, 'box_id, ctn_id');
			$log = "Moved Inventory :: To Box# " . $_POST["box_id"] . " of Cartoon# " . $_POST["ctn_id"] . " :: From Box# " . $data[0]["box_id"] . " of Carton# " . $data[0]["ctn_id"] . " <br />";
			$ins = array(
				"inv_id" => $_POST["uid"],
				"log_type" => "moved",
				"log_details" => $log,
				"user_id" => $current_user["userID"],
			);
			$db->insert(TBL_INVENTORY_LOG, $ins);
			$upd = array(
				"box_id" => $_POST["box_id"],
				"ctn_id" => $_POST["ctn_id"],
			);
			$db->where('inv_id', $_POST['uid']);
			return $db->update(TBL_INVENTORY, $upd);
			break;

		case 'create_ctn_id':
			$insert_id = $db->insert(TBL_INVENTORY_CTN, array('quantity' => $_POST['value'], 'createdDate' => date('Y-m-d H:i:s')));
			if ($insert_id) {
				echo json_encode(array('type' => 'success', 'ctn_id' => sprintf('%09d', $insert_id), 'quantity' => $_POST['value'], 'print' => get_option('print_ctn_label')));
			} else {
				echo json_encode(array('type' => 'error', 'msg' => 'Unable to get carton number', 'error' => $db->getLastError()));
			}
			break;

		case 'create_box_id':
			$insert_id = $db->insert(TBL_INVENTORY_BOX, array('quantity' => $_POST['value'], 'createdDate' => date('Y-m-d H:i:s')));
			if ($insert_id) {
				echo json_encode(array('type' => 'success', 'box_id' => sprintf('%09d', $insert_id), 'quantity' => $_POST['value'], 'print' => get_option('print_box_label')));
			} else {
				echo json_encode(array('type' => 'error', 'msg' => 'Unable to get carton number', 'error' => $db->getLastError()));
			}
			break;

		case 'get_inbound_state':
			$grn_id = $_REQUEST['grn_id'];
			$item_id = $_REQUEST['item_id'];
			$option_key = 'ibs_' . $grn_id . '_' . $item_id;
			$db->where('item_id', $item_id);
			$db->where('grn_id', $grn_id);
			$inboundState = $db->getValue(TBL_PURCHASE_GRN_ITEMS, 'grn_itemInboundState');
			if ($inboundState)
				echo $inboundState;

			break;

		case 'update_inbound_state':
			$data = json_decode($_POST['data'], true);
			$grn_id = $data['grn_id'];
			$item_id = $data['item_id'];

			$option_value = trim($_POST['data']);
			$db->where('item_id', $item_id);
			$db->where('grn_id', $grn_id);
			if ($db->update(TBL_PURCHASE_GRN_ITEMS, array('grn_itemInboundState' => $option_value)))
				echo json_encode(array('type' => 'success'));
			else
				echo json_encode(array('type' => 'error', 'msg' => 'Unable to update current state.', 'error' => $db->getLastQuery()));

			break;

		case 'get_inventory_id':
			$grn_id = $_GET['grn_id'];
			$item_id = $_GET['item_id'];
			$limit = $_GET['limit'];
			$db->where('item_id', $item_id);
			$db->where('grn_id', $grn_id);
			$db->where('inv_status', 'inbound');
			$db->where('(box_id IS NULL AND ctn_id IS NULL)', array());
			$inventory_id = $db->get(TBL_INVENTORY, array(0, $limit), 'inv_id as uid');
			if ($inventory_id)
				echo json_encode(array("type" => "success", "uids" => $inventory_id));
			else
				echo json_encode(array("type" => "error", "msg" => "Unable to get the Product Number. Please try again", "error" => $db->getLastQuery()));
			break;

		case 'update_inventory_id':
			$inv_ids = $_POST['inv_ids'];
			$ctn_id = $_POST['ctn_id'];
			$box_id = $_POST['box_id'];

			$inv_ids = json_decode($inv_ids, true);
			$capacity = count($inv_ids);
			$expectedLocation = $stockist->get_expected_location_by_status("printing", $capacity);
			$details = array(
				"box_id" => $box_id,
				"ctn_id" => $ctn_id,
				"inv_status" => 'qc_pending',
				"expectedLocation" => $expectedLocation
			);

			$response_i = $response[] = array();
			for ($i = 0; $i < count($inv_ids); $i++) {
				$inv_id = $inv_ids[$i]['uid'];
				if ($stockist->update_inventory_details($inv_id, $details)) {
					if ($stockist->add_inventory_log($inv_id, 'tagging', "Label Tagging :: Carton# " . $ctn_id . " :: Box# " . $box_id)) {
						$response[] = true;
						$db->where("user_role", "printing");
						$db->orWhere("user_role", "super_admin");
						$ids = array_map(function ($innerArray) {
							return $innerArray["userID"];
						}, $db->get(TBL_USERS, null, "userID"));
						$task = array(
							"createdBy" => $current_user["userID"],
							"title" => "Products Ready For Printing",
							"ctnId" => $ctn_id,
							"category" => "printing",
							"userRoles" => json_encode($ids),
							"status" => "0"
						);
						$db->insert(TBL_TASKS, $task);
					} else {
						$response[] = false;
					}
				} else {
					$response_i[] = false;
				}
			}

			if (in_array(false, $response))
				$return = array('type' => 'success');
			else
				$return = array('type' => 'error', 'msg' => 'Unable to update inventory log');

			if (in_array(false, $response_i))
				$return = array('type' => 'error', 'msg' => 'Unable to update inventory');

			echo json_encode($return);
			break;

		case 'get_inwarded_count':
			$grn_id = $_GET['grn_id'];
			$item_id = $_GET['item_id'];
			$db->where('item_id', $item_id);
			$db->where('grn_id', $grn_id);
			$db->where('inv_status', 'inbound', '!=');
			$count = $db->getOne(TBL_INVENTORY, 'count(*) as inwarded');
			if ($count)
				echo json_encode(array("type" => "success", "count" => $count['inwarded']));
			else
				echo json_encode(array("type" => "error", "msg" => "Unable to get the Inwarded Quantity. Please try again", "error" => $db->getLastQuery()));
			break;

		case 'update_grn_status':
			$grn_id = $_POST['grn_id'];
			$item_id = $_POST['item_id'];
			$status = $_POST['status'];

			$db->where('grn_id', $grn_id);
			$item_statuses = $db->get(TBL_PURCHASE_GRN_ITEMS, null, 'item_status');
			$statuses = array();
			foreach ($item_statuses as $item_status) {
				$statuses[] = $item_status['item_status'];
			}

			$db->where('grn_id', $grn_id);
			$db->where('item_id', $item_id);
			if ($db->update(TBL_PURCHASE_GRN_ITEMS, array('item_status' => $status)))
				$return = array('type' => 'success', 'msg' => 'GRN Inventory status updated');
			else
				$return = array('type' => 'error', 'msg' => 'Unable to update status', 'error' => $db->getLastError());

			if (in_array(NULL, $statuses, true) || in_array('receiving', $statuses)) {
				$grn_status = $db->getValue(TBL_PURCHASE_GRN, 'grn_status');
				if ($grn_status == "created" || $status == "receiving") {
					$db->where('grn_id', $grn_id);
					$db->update(TBL_PURCHASE_GRN, array('grn_status' => 'receiving'));
				}
			} else {
				$db->where('grn_id', $grn_id);
				$db->update(TBL_PURCHASE_GRN, array('grn_status' => 'completed'));
			}

			echo json_encode($return);
			break;

		case 'get_product_label_pdf':
			include(ROOT_PATH . '/includes/vendor/autoload.php');
			$a_uids = $_GET['uids'];
			$labelFor = $_GET['labelFor'];
			$qty = $_GET['qty'];
			$item_sku = $_GET['sku'];
			$j_uids = json_decode($a_uids, true);
			$uids = $j_uids;

			$print_details = get_printer_settings("printer_product_label");
			$sizes = explode('x', $print_details["product_label"]["size"]);
			$mpdf = new \Mpdf\Mpdf([
				// 'mode' => 'c',
				'format' => [$sizes[0], $sizes[1]],
				'margin_left' => 1,
				'margin_right' => 1,
				'margin_top' => 1,
				'margin_bottom' => 1,
				'margin_header' => 0,
				'margin_footer' => 0,
			]);

			for ($i = 0; $i < count($uids); $i++) {
				$uid = $uids[$i]['uid'];
				if ($labelFor == "ctn") {
					$uid = (int)substr($uid, 3);
					$db->where('ctn_id', $uid, 'LIKE');
				} elseif ($labelFor == "box") {
					$uid = (int)substr($uid, 3);
					$db->where('box_id', $uid, 'LIKE');
				} else {
					$db->where('inv_id', $uid);
				}
				$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
				$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=p.brand');
				$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'pc.catid=p.category');
				$db->join(TBL_PURCHASE_LOT . ' pl', 'pl.lot_id=i.lot_id', 'LEFT');
				$db->join(TBL_PURCHASE_LOT_LOCAL . ' pll', 'pll.lot_id=i.lot_id', 'LEFT');
				// $db->join(TBL_PARTIES. ' pc', "pc.party_id=pl.lot_carrier");
				$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.item_id=i.item_id', 'LEFT');
				$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.lot_id=i.lot_id', 'LEFT');
				$db->join(TBL_PURCHASE_ORDER . ' po', "po.po_id=poil.po_id", 'LEFT');
				$db->join(TBL_PARTIES . ' pp', "pp.party_id=po.po_party_id", 'LEFT');
				$product = $db->get(TBL_INVENTORY . ' i', NULL, 'p.sku, pl.lot_number, CONCAT(UPPER(LEFT(pb.brandName, 2)), UPPER(LEFT(pp.party_name, 2)), UPPER(LEFT(pc.categoryName, 2))) as backTrace');
				if (empty($qty)) {
					$qty = count($product);
				}
				$product = $product[0];
				if ($labelFor == "ctn")
					$uid = 'CTN' . sprintf('%09d', $uid);
				else if ($labelFor == "box")
					$uid = 'BOX' . sprintf('%09d', $uid);
				$sku = (empty($product['sku'])) ? $item_sku : $product['sku'];
				// $sku = "SYLVI-EFR556-BLUEDIAL-BLUESILVER-LEATHERBLUE";
				// $sku = "SYLVI-YD-3022-BROWN";
				$product_backTrace = ($product['backTrace'] ? $product['backTrace'] : "") . ' - [' . $current_user["userID"] . ']'; // BRAND::SUPPLIER::CARRIER
				$packed_date = date('dmY');
				$disclaimers = "QUANTITY: " . $qty;
				if (empty($labelFor)) {
					$disclaimers = "NOTE: THIS TAG IS REQUIRED TO CLAIM WARRANTY";
				}

				try {
					$mpdf->AddPage();
					$mpdf->SetXY(10, 1);
					$mpdf->WriteHTML('<barcode class="barcodecell" code="' . $uid . '" type="QR" size="0.65" disableBorder="1" padding="0"/>');

					$mpdf->SetFont('Arial', 'B');

					// UID - BOX/CTN NUMBER
					$mpdf->SetFontSize(10);
					$mpdf->SetXY(27, 0);
					$mpdf->WriteCell(37, 5, $uid, 0, 1, 'C');

					// SKU
					$mpdf->SetFontSize(8);
					$mpdf->SetXY(27, 5);
					$mpdf->MultiCell(37, 3, $sku, 0, 'L');

					// BACKTRACE
					$mpdf->SetFont('Arial');
					$mpdf->SetFontSize(6);
					$mpdf->SetXY(27, 14);
					$mpdf->WriteCell(37, 5, $product_backTrace, 0, 1, 'L');

					// PACKED DATE
					$mpdf->SetXY(27, 14);
					$mpdf->WriteCell(37, 5, $packed_date, 0, 1, 'R');

					// QTY
					$mpdf->SetFont('Arial', 'B');
					$mpdf->SetFontSize(5.5);
					$mpdf->SetXY(10, 18.5);
					$mpdf->MultiCell(54, 3, $disclaimers, 1, 'C');
					// $mpdf->WriteHTML($html);
					// if ($i > count($uids))
					// 	$mpdf->AddPage();
				} catch (\Mpdf\MpdfException $e) {
					die($e->getMessage());
				}
			}

			$title = 'Label';
			$mpdf->SetProtection(array('print'));
			$mpdf->SetTitle($title);
			$mpdf->SetAuthor('FIMS');
			$mpdf->SetDisplayMode('fullpage');
			if (isset($_GET['test']) && $_GET['test'] == "1") {
				$mpdf->Output($title . '.pdf', 'I');
				exit;
			}
			echo chunk_split(base64_encode($mpdf->Output($title . '.pdf', 'S')));
			break;

		case 'get_ctn_box_label_pdf':
			include(ROOT_PATH . '/includes/vendor/autoload.php');
			$a_uids = $_GET['uids'];
			$labelFor = $_GET['labelFor'];
			$qty = $_GET['qty'];
			$item_sku = $_GET['sku'];
			$j_uids = json_decode($a_uids, true);
			$uids = $j_uids;

			$print_details = get_printer_settings("printer_ctn_box_label");
			$sizes = explode('x', $print_details["ctn_box_label"]["size"]);
			$mpdf = new \Mpdf\Mpdf([
				// 'mode' => 'c',
				'format' => [$sizes[0], $sizes[1]],
				// 'format' => 'A4',
				'margin_left' => 1,
				'margin_right' => 1,
				'margin_top' => 1,
				'margin_bottom' => 0,
				'margin_header' => 0,
				'margin_footer' => 0,
			]);
			$mpdf->SetTextColor(0, 0, 0);

			for ($i = 0; $i < count($uids); $i++) {
				$uid = $uids[$i]['uid'];
				if ($labelFor == "ctn") {
					$uid = (int)substr($uid, 3);
					$db->where('ctn_id', $uid, 'LIKE');
				} elseif ($labelFor == "box") {
					$uid = (int)substr($uid, 3);
					$db->where('box_id', $uid, 'LIKE');
				} else {
					$db->where('inv_id', $uid);
				}
				$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
				$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=p.brand');
				$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'pc.catid=p.category');
				$db->join(TBL_PURCHASE_LOT . ' pl', 'pl.lot_id=i.lot_id', 'LEFT');
				$db->join(TBL_PURCHASE_LOT_LOCAL . ' pll', 'pll.lot_id=i.lot_id', 'LEFT');
				// $db->join(TBL_PARTIES. ' pc', "pc.party_id=pl.lot_carrier");
				$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.item_id=i.item_id', 'LEFT');
				$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.lot_id=i.lot_id');
				$db->join(TBL_PURCHASE_ORDER . ' po', "po.po_id=poil.po_id", 'LEFT');
				$db->join(TBL_PARTIES . ' pp', "pp.party_id=po.po_party_id", 'LEFT');
				// $product = $db->get(TBL_INVENTORY.' i', NULL, 'i.*');
				$product = $db->get(TBL_INVENTORY . ' i', NULL, 'p.sku, pl.lot_number, CONCAT(UPPER(LEFT(pb.brandName, 2)), UPPER(LEFT(pp.party_name, 2)), UPPER(LEFT(pc.categoryName, 2))) as backTrace');
				if (empty($qty)) {
					$qty = count($product);
				}
				$product = $product[0];
				if ($labelFor == "ctn")
					$uid = 'CTN' . sprintf('%09d', $uid);
				else if ($labelFor == "box")
					$uid = 'BOX' . sprintf('%09d', $uid);
				$sku = (empty($product['sku'])) ? $item_sku : $product['sku'];
				$sku = (!empty($item_sku) ? strtoupper($item_sku) : $sku);
				$product_backTrace = ($product['backTrace'] ? $product['backTrace'] : "") . ' - [' . $current_user["userID"] . ']'; // BRAND::SUPPLIER::CARRIER
				$packed_date = date('dmY');
				$disclaimers = "QUANTITY: " . $qty;

				try {
					$mpdf->AddPage();
					$mpdf->text_input_as_HTML = true;
					$mpdf->SetXY(1, 1);
					$mpdf->WriteHTML('<barcode class="barcodecell" code="' . $uid . '" type="QR" size="0.72" disableBorder="1" padding="0"/>');

					$mpdf->SetFont('Arial', 'B');

					// UID - BOX/CTN NUMBER
					$mpdf->SetFontSize(8);
					$mpdf->SetXY(20, 0);
					$mpdf->WriteCell(29, 5, $uid, 0, 1, 'C');

					// SKU
					$mpdf->SetFontSize(7);
					$mpdf->SetXY(20, 4);
					$mpdf->MultiCell(29, 3, $sku, 0, 'L');

					// BACKTRACE
					$mpdf->SetFont('Arial');
					$mpdf->SetFontSize(6);
					$mpdf->SetXY(20, 12);
					$mpdf->WriteCell(28, 5, $product_backTrace, 0, 1, 'L');

					// PACKED DATE
					$mpdf->SetXY(20, 12);
					$mpdf->WriteCell(28, 5, $packed_date, 0, 1, 'R');

					// QTY
					$mpdf->SetFont('Arial', 'B');
					$mpdf->SetXY(20, 16);
					$mpdf->MultiCell(29, 3, $disclaimers, 1, 'C');
					// $mpdf->WriteCell(28, 5, $disclaimers, 0, 1, 'C');

					// if ($i > count($uids))
					// 	$mpdf->AddPage();
				} catch (\Mpdf\MpdfException $e) {
					die($e->getMessage());
				}
			}

			$title = 'Label';
			$mpdf->SetProtection(array('print'));
			$mpdf->SetTitle($title);
			$mpdf->SetAuthor('FIMS');
			$mpdf->SetDisplayMode('fullpage');
			if (isset($_GET['test']) && $_GET['test'] == "1") {
				$mpdf->Output($title . '.pdf', 'I');
				exit;
			}
			echo chunk_split(base64_encode($mpdf->Output($title . '.pdf', 'S')));
			break;

		case 'get_weighted_product_label_pdf':
			include(ROOT_PATH . '/includes/vendor/autoload.php');
			$a_uids = $_GET['uids'];
			$labelFor = $_GET['labelFor'];
			$qty = $_GET['qty'];
			$item_sku = $_GET['sku'];
			$weight = $_GET['weight'];
			$j_uids = json_decode($a_uids, true);
			$uids = $j_uids;

			$print_details = get_printer_settings("printer_weighted_product_label");
			$sizes = explode('x', $print_details["weighted_product_label"]["size"]);
			$mpdf = new \Mpdf\Mpdf([
				// 'mode' => 'c',
				'format' => [$sizes[0], $sizes[1]],
				'margin_left' => "4mm",
				'margin_right' => "2mm",
				'margin_top' => "2mm",
				'margin_bottom' => "0mm",
				'margin_header' => 0,
				'margin_footer' => 0,
			]);

			for ($i = 0; $i < count($uids); $i++) {
				$uid = $uids[$i]['uid'];
				$db->where('inv_id', $uid);
				$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
				$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=p.brand');
				$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'pc.catid=p.category');
				$db->join(TBL_PURCHASE_LOT . ' pl', 'pl.lot_id=i.lot_id', 'LEFT');
				$db->join(TBL_PURCHASE_LOT_LOCAL . ' pll', 'pll.lot_id=i.lot_id', 'LEFT');
				// $db->join(TBL_PARTIES. ' pc', "pc.party_id=pl.lot_carrier");
				$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.item_id=i.item_id');
				$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.lot_id=i.lot_id');
				$db->join(TBL_PURCHASE_ORDER . ' po', "po.po_id=poil.po_id");
				$db->join(TBL_PARTIES . ' pp', "pp.party_id=po.po_party_id");
				$product = $db->get(TBL_INVENTORY . ' i', NULL, 'p.sku, COALESCE(pl.lot_number, pll.lot_number) as lot_number, CONCAT(UPPER(LEFT(pb.brandName, 2)), UPPER(LEFT(pp.party_name, 2)), UPPER(LEFT(pc.categoryName, 2))) as backTrace');
				if (empty($qty)) {
					$qty = count($product);
				}
				$product = $product[0];
				$sku = (empty($product['sku'])) ? $item_sku : $product['sku'];
				$product_backTrace = $product['backTrace'] . ' - [' . $current_user["userID"] . ']'; // BRAND::SUPPLIER::CARRIER
				$packed_date = date('dmY');
				$disclaimers = "NOTE: THIS TAG IS REQUIRED TO CLAIM WARRANTY";

				$html = '
					<style type="text/css">
						.tg {
							border-collapse: collapse;
							border-spacing: 0;
							margin: 0;
							width: 64mm; /* printable area */
							height: 13mm;
							table-layout:fixed;
							page-break-inside:avoid;
							/*rotate=: -90;
							border: 1px solid #000;*/
						}

						.tg td {
							border-color: black;
							border-style: solid;
							border-width: 0px;
							font-family: sans-serif;
							font-size: 8pt;
							overflow: hidden;
							padding: 0;
							vertical-align: middle;
							word-break: normal;
							white-space: nowrap
						}

						.tg .tg-baqh { 
							text-align:center;
							vertical-align:middle;
						}

						.tg .tg-7zrl{
							font-weight: bold;
						}

						.tg .tg-uog8 {
							border-color: inherit;
							text-align: left;
							vertical-align: middle;
						}

						.tg .tg-jpnm {
							font-size: 7pt;
							vertical-align: middle;
						}

						.tg .tg-fwc9 {
							font-size: 4pt;
						}

						.tg .tg-9wq8{
							font-size: 3.5pt;
							text-align: center;
							vertical-align: middle;
						}

						.barcodecell {
							text-align: left;
							vertical-align: middle;
						}
					</style>
					<table class="tg" cellpadding="0" cellspacing="0" >
						<tbody>
							<tr>
								<td class="tg-uog8" rowspan="5" width="13mm" height="13mm">
									<barcode class="barcodecell" code="' . $uid . '" type="QR" size="0.5" disableBorder="1" padding="0"/>
								</td>
								<td class="tg-uog8" rowspan="5" width="17mm" height="13mm" style="vertical-align:top;">
									' . wordwrap($sku, 8, "<br>\n", true) . '
								</td>
								<td class="tg-0lax" rowspan="5" width="2mm" height="13mm"></td>
								<td class="tg-uog8 tg-7zrl" colspan="2" width="30mm" height="4mm">' . $uid . '</td>
							</tr>
							<tr>
								<td class="tg-uog8 tg-jpnm tg-7zrl" colspan="2" width="30mm" height="3mm">' . wordwrap($sku, 27, "<br>\n", true) . '</td>
							</tr>
							<tr>
								<td class="tg-uog8 tg-fwc9" width="15mm" height="2mm">' . $product_backTrace . '</td>
								<td class="tg-uog8 tg-fwc9" width="15mm" height="2mm">' . $packed_date . '.' . $weight . '</td>
							</tr>
							<tr>
								<td class="tg-uog8 tg-jpnm tg-7zrl" colspan="2" width="30mm" height="2mm"></td>
							</tr>
							<tr>
								<td class="tg-uog8 tg-9wq8" width="30mm" height="2mm" colspan="2">' . $disclaimers . '</td>
							</tr>
						</tbody>
					</table>';

				try {
					$mpdf->WriteHTML($html);
					if ($i > count($uids))
						$mpdf->AddPage();
				} catch (\Mpdf\MpdfException $e) {
					die($e->getMessage());
				}
			}

			$title = 'Label';
			$mpdf->SetProtection(array('print'));
			$mpdf->SetTitle($title);
			$mpdf->SetAuthor('FIMS');
			$mpdf->SetDisplayMode('fullpage');
			// $mpdf->Output($title.'.pdf', 'I');
			echo chunk_split(base64_encode($mpdf->Output($title . '.pdf', 'S')));

			break;

		case 'get_product_label_pdf_bulk':
			include(ROOT_PATH . '/includes/vendor/autoload.php');
			$a_uids = $_GET['uids'];
			$labelFor = $_GET['labelFor'];
			$qty = $_GET['qty'];
			$item_sku = $_GET['sku'];
			$j_uids = json_decode($a_uids, true);
			$uids = $j_uids;
			$uids = array("SF51SK003444", "SF51SK003445", "SF51SK003446", "SF51SK003447", "SF51SK003448", "SF51SK003449", "SF51SK003450", "SF51SK003451", "SF51SK003452", "SF51SK003453", "SF51SK003454", "SF51SK003455", "SF51SK003456", "SF51SK003457", "SF51SK003458", "SF51SK003459", "SF51SK003460", "SF51SK003461", "SF51SK003462", "SF51SK003463", "SF51SK003464", "SF51SK003465", "SF51SK003466", "SF51SK003467", "SF51SK003468", "SF51SK003469", "SF51SK003470", "SF51SK003471", "SF51SK003472", "SF51SK003473", "SF51SK003474", "SF51SK003475", "SF51SK003476", "SF51SK003477", "SF51SK003478", "SF51SK003479", "SF51SK003480", "SF51SK003481", "SF51SK003482", "SF51SK003483", "SF51SK003484", "SF51SK003485", "SF51SK003486", "SF51SK003487", "SF51SK003488", "SF51SK003489", "SF51SK003490", "SF51SK003491", "SF51SK003492", "SF51SK003493", "SF51SK003494", "SF51SK003495", "SF51SK003496", "SF51SK003497", "SF51SK003498", "SF51SK003499", "SF51SK003500", "SF51SK003501", "SF51SK003502", "SF51SK003503", "SF51SK003504", "SF51SK003505", "SF51SK003506", "SF51SK003507", "SF51SK003508", "SF51SK003509", "SF51SK003510", "SF51SK003511", "SF51SK003512", "SF51SK003513");

			$print_details = get_printer_settings("printer_product_label");
			$sizes = explode('x', $print_details["product_label"]["size"]);
			$mpdf = new \Mpdf\Mpdf([
				// 'mode' => 'c',
				'format' => [$sizes[0], $sizes[1]],
				'margin_left' => 1,
				'margin_right' => 2,
				'margin_top' => 1,
				'margin_bottom' => 1,
				'margin_header' => 0,
				'margin_footer' => 0,
			]);

			for ($i = 0; $i < count($uids); $i++) {
				$uid = $uids[$i];
				if ($labelFor == "ctn") {
					$uid = (int)substr($uid, 3);
					$db->where('ctn_id', $uid, 'LIKE');
				} elseif ($labelFor == "box") {
					$uid = (int)substr($uid, 3);
					$db->where('box_id', $uid, 'LIKE');
				} else {
					$db->where('inv_id', $uid);
				}
				$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
				$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=p.brand');
				$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'pc.catid=p.category');
				$db->join(TBL_PURCHASE_LOT . ' pl', 'pl.lot_id=i.lot_id');
				// $db->join(TBL_PARTIES. ' pc', "pc.party_id=pl.lot_carrier");
				$db->join(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.item_id=i.item_id', 'LEFT');
				$db->joinWhere(TBL_PURCHASE_ORDER_ITEMS_LOT . ' poil', 'poil.lot_id=i.lot_id', 'LEFT');
				$db->join(TBL_PURCHASE_ORDER . ' po', "po.po_id=poil.po_id", 'LEFT');
				$db->join(TBL_PARTIES . ' pp', "pp.party_id=po.po_party_id", 'LEFT');
				$product = $db->get(TBL_INVENTORY . ' i', NULL, 'p.sku, pl.lot_number, CONCAT(UPPER(LEFT(pb.brandName, 2)), UPPER(LEFT(pp.party_name, 2)), UPPER(LEFT(pc.categoryName, 2))) as backTrace');
				if (empty($qty)) {
					$qty = count($product);
				}
				$product = $product[0];
				if ($labelFor == "ctn")
					$uid = 'CTN' . sprintf('%09d', $uid);
				else if ($labelFor == "box")
					$uid = 'BOX' . sprintf('%09d', $uid);
				$sku = (empty($product['sku'])) ? $item_sku : $product['sku'];

				// $sku = "SYLVI-YD-3022-COFFEE";
				$product_backTrace = $product['backTrace'] . ' - [' . $current_user["userID"] . ']'; // BRAND::SUPPLIER::CARRIER
				// $product_backTrace = "SYFLWA";
				$packed_date = date('dmY');
				// $packed_date = "11072023";
				$disclaimers = "QUANTITY: " . $qty;
				if (empty($labelFor)) {
					$disclaimers = "NOTE: THIS TAG IS REQUIRED TO CLAIM WARRANTY";
				}

				$html = '
					<style type="text/css">
						table {
							border-collapse: collapse;
							border-spacing: 0;
							margin: 0 auto 0;
							width: 50mm;
							height: 20mm;
							table-layout:fixed;
							page-break-inside:avoid;
							/*rotate=: -90;
							border: 1px solid #000;*/
						}

						table td {
							border-color: black;
							border-style: solid;
							border-width: 0px;
							font-family: sans-serif;
							font-size: 10pt;
							font-weight:bold;
							overflow: hidden;
							padding: 0;
							margin: 0;
							word-break: normal;
						}

						table .tg-cly1 {
							text-align: left;
							vertical-align: middle;
						}

						table .tg-0lax {
							text-align: left;
							vertical-align: top
						}

						table .tg-7zrl {
							text-align: right;
							vertical-align: bottom;
						}

						.barcodecell {
							text-align: left;
							vertical-align: middle;
						}
					</style>
					<table class="tg">
						<tr>
							<td class="tg-cly1" colspan="3" style="font-size:14pt;">' . $uid . '</td>
							<td class="tg-7zrl" rowspan="2" text-rotate="90" style="font-size:6pt;">' . $product_backTrace . '</td>
							<td class="tg-7zrl" rowspan="2" text-rotate="90" style="font-size:6pt;">' . $packed_date . '</td>
						</tr>
						<tr>
							<td class="tg-0lax" colspan="3">
								<barcode class="barcodecell" code="' . $uid . '" type="C128B" quiet_zone_left="5" quiet_zone_right="0" pr="0.335" height="1" padding="0"/>
							</td>
						</tr>
						<tr>
							<td height="8" class="tg-0lax" colspan="5" style="font-size:14pt; vertical-align: middle;">' . wordwrap($sku, 27, "<br>\n", true) . '</td>
						</tr>
						<tr>
							<td class="tg-0lax" colspan="5" align="center" style="font-size:8pt; border: 1px dotted #000;">' . $disclaimers . '</td>
						</tr>
					</table>';

				try {
					$mpdf->WriteHTML($html);
					if ($i > count($uids))
						$mpdf->AddPage();
				} catch (\Mpdf\MpdfException $e) {
					die($e->getMessage());
				}
			}

			$title = 'Label';
			$mpdf->SetProtection(array('print'));
			$mpdf->SetTitle($title);
			$mpdf->SetAuthor('FIMS');
			$mpdf->SetDisplayMode('fullpage');
			// $mpdf->Output($title.'.pdf', 'I');
			echo chunk_split(base64_encode($mpdf->Output($title . '.pdf', 'S')));
			break;

		case 'add_issue':
			$details = array(
				'issue' => $_POST['issue'],
				'issue_key' => $_POST['issue_key'],
				'issue_fix' => $_POST['issue_fix'],
				'issue_category' => $_POST['issue_category'],
				'issue_group' => !empty($_POST['issue_group']) ? $_POST['issue_group'] : NULL,
				'createdDate' => $db->now(),
			);

			if ($db->insert(TBL_INVENTORY_QC_ISSUES, $details))
				$return = json_encode(array('type' => 'success', 'msg' => 'Successfully added new Issue'));
			else
				$return = json_encode(array('type' => 'error', 'msg' => 'Unable to added new Issue', 'error' => $db->getLastError()));

			echo $return;

			break;

		case 'update_issue':
			$details = array(
				'issue' => $_POST['issue'],
				'issue_key' => $_POST['issue_key'],
				'issue_category' => $_POST['issue_category'],
				'issue_group' => isset($_POST['issue_group']) ? $_POST['issue_group'] : NULL,
			);

			$db->where('issue_id', $_POST['issue_id']);
			if ($db->update(TBL_INVENTORY_QC_ISSUES, $details))
				$return = json_encode(array('type' => 'success', 'msg' => 'Successfully added new Issue'));
			else
				$return = json_encode(array('type' => 'error', 'msg' => 'Unable to added new Issue', 'error' => $db->getLastError()));

			echo $return;
			break;

		case 'get_uid_details':
			$uid = $_GET['uid'];
			$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
			$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'p.category=pc.catid');
			$db->where('i.inv_id', $uid);
			$db->where('(inv_status = ? OR inv_status = ? OR inv_status = ?)', array('qc_pending', 'qc_cooling', 'qc_verified'));
			$return = $db->getOne(TBL_INVENTORY . ' i', 'i.inv_id as uid, i.ctn_id, i.inv_status, p.sku, p.thumb_image_url, pc.categoryName as category, pc.catid as issue_category');
			if ($return) {
				if ($return['inv_status'] == "qc_cooling" || $return['inv_status'] == "qc_verified") {
					$cooling_time_start = $stockist->get_qc_inprogress_time($uid);
					if (!is_null($cooling_time_start) && strtotime($cooling_time_start . ' +72 hours') > time()) {
						echo json_encode(array('type' => 'info', 'msg' => 'Product is in cooling period'));
					} else {
						if ($stockist->add_inventory_log($uid, 'qc_started', 'QC Started'))
							echo json_encode(array_merge(array('type' => 'success'), $return));
					}
				} else {
					if ($stockist->add_inventory_log($uid, 'qc_started', 'QC Started'))
						echo json_encode(array_merge(array('type' => 'success'), $return));
				}

				$data  = array(
					"logType" => "Product QC Start",
					"identifier" => $uid,
					"userId" => $current_user['userID']
				);
				$log_status = $db->insert(TBL_PROCESS_LOG, $data);
			} else {
				$db->where('inv_id', $uid);
				$status = $db->getValue(TBL_INVENTORY, 'inv_status');
				echo json_encode(array('type' => 'info', 'msg' => 'Product status is ' . strtoupper($status)));
			}

			break;

		case 'update_uid':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$inv_id = $_POST['inv_id'];
			$category = $_POST['category'];
			$current_status = $_POST['current_status'];
			$issues = json_decode(str_replace('null,', '', $_POST['identified_issues']));
			$flag = false;

			$db->where("category", $_POST["current_status"]);
			$expCtnData = $db->getOne(TBL_INVENTORY_STATE, "ctnId, count, id");

			$db->where("inv_id", $inv_id);
			$ctnId = $db->getValue(TBL_INVENTORY, "ctn_id");
			if ($expCtnData["ctnId"]) {
				if ($expCtnData["count"] != 0) {
					if ($ctnId && $ctnId == $expCtnData["ctnId"]) {
						// Any Cartoon Is inProgress for the same status
						// Cartoon Is not Finished yet
						$db->where("ctnId", $expCtnData["ctnId"]);
						$db->update(TBL_INVENTORY_STATE, ["count" => ($expCtnData["count"] - 1)]);
						$flag = true;
					} else {
						// Any Cartoon Is inProgress for the same status and selected uid is not in cartoon
						$flag = false;
					}
				} else {
					// Cartoon Is completed
					$db->where("id", $expCtnData["id"]);
					$details = array(
						"category" => $current_status,
						"ctnId" => $ctnId,
						"count" => $count
					);
					$db->update(TBL_INVENTORY_STATE, $details);
					$flag = true;
				}
			} else {
				$db->where("ctn_id", $ctnId);
				$count = $db->getValue(TBL_INVENTORY, "count(inv_id)");
				$details = array(
					"category" => $current_status,
					"ctnId" => $ctnId,
					"count" => $count
				);
				$db->insert(TBL_INVENTORY_STATE, $details);

				switch ($current_status) {
					case 'qc_pending':
						// Add task with the startdate (current_time + 72 hours)
						$details = array(
							"title" => "Cartoon is ready to QC Verification!",
							"ctnId" => $ctnId,
							"category" => "qc_cooling",
							"userRoles" => "qc",
							"status" => "0",
							"createdBy" => $current_user["userID"],
							"createdDate" => date("Y-m-d H:i:s", strtotime("now + 72 hours"))
						);
						$db->insert(TBL_TASKS, $details);
						break;
					case 'qc_cooling':
						// Add task to move inventory to stock room
						$details = array(
							"title" => "Cartoon is ready for sales! Move cartoon to Stock Room",
							"ctnId" => $ctnId,
							"category" => "qc_verified",
							"userRoles" => "warehouse_operations",
							"status" => "0",
							"createdBy" => $current_user["userID"]
						);
						$db->insert(TBL_TASKS, $details);
						break;
				}
				$flag = true;
			}

			if ($flag) {
				if (!empty($issues)) {
					$inv_status = 'qc_failed';
					$issue_ids = array();
					foreach ($issues as $issue) {
						$issue_ids[$issue->issue_id] = false;
					}
					$issue_ids = json_encode($issue_ids);
					$details = array(
						'inv_status' => 'qc_failed',
					);
					$log_details = $issue_ids;
				} else {
					if ($current_status == "qc_cooling") {
						$inv_status = 'qc_verified';
						$log_details = 'QC Verified';
					} else if ($category != "Watches") {
						$inv_status = 'qc_verified';
						$log_details = 'QC Verified';
						$cooling_period = "";
					} else {
						$inv_status = 'qc_cooling';
						$cooling_period = "";
						if ($category == "Watches") {
							$expectedLocation = $stockist->get_expected_location_by_status("qc_cooling");
							$details = array(
								"expectedLocation" => $expectedLocation,
								"currentLocation" => null,
							);
							$db->where("inv_id", $inv_id);
							$db->update(TBL_INVENTORY, $details);

							$cooling_period = '. Cooling Period enabled.::Cooling Period End: ' . date("d M, Y H:i:s", strtotime("+72 hours"));
						}
						$log_details = 'QC In Progress' . $cooling_period;
					}
				}

				$status = $stockist->get_inventory_status($inv_id);
				if ($inv_status == 'qc_verified' && $status == "qc_verified") {
					$return = array('type' => 'info', 'msg' => 'QC Status is already verified');
				} else {
					if ($stockist->update_inventory_status($inv_id, $inv_status)) {
						if ($stockist->add_inventory_log($inv_id, $inv_status, $log_details))
							$return = array('type' => 'success', 'msg' => 'Successfully updated QC Status');
						else
							$return = array('type' => 'error', 'msg' => 'Unable to updated Inventory log.');
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to updated QC Status');
					}
				}

				$data  = array(
					"logType" => "Product QC End",
					"identifier" => $inv_id,
					"userId" => $current_user['userID']
				);
				$log_status = $db->insert(TBL_PROCESS_LOG, $data);

				echo json_encode($return);
			} else {
				$return = array(
					"type" => "error",
					"msg" => "Can't process item outside of cartoon before completing the cartoon!"
				);
				echo json_encode($return);
			}

			break;

		case 'qc_reject':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';

			$uid = $_REQUEST["uid"];
			$location = $stockist->get_expected_location_by_status("qc_reject");
			$inv_status = "qc_pending";
			$log_details = "Soft QC Rejected!";
			$details = array(
				"ctn_id" => "7",
				"box_id" => null,
				"inv_status" => $inv_status,
				"expectedLocation" => $location
			);
			$stockist->add_inventory_log($uid, $inv_status, $log_details);
			$db->where("inv_id", $uid);
			$db->update(TBL_INVENTORY, $details);
			echo json_encode(["type" => "success"]);
			break;

		case 'update_print_option':
			if (set_option($_POST['option'], $_POST['value']))
				$return = array('type' => 'success', 'msg' => 'Successfully updated print option');
			else
				$return = array('type' => 'error', 'msg' => 'Unable to updated print option', 'error' => $db->getLastError());

			echo json_encode($return);
			break;

			// REPAIRS
		case 'get_uid_repairs':
			$uid = $_GET['uid'];
			// Process start
			$data  = array(
				"logType" => "Product Repair Start",
				"identifier" => $uid,
				"userId" => $current_user['userID']
			);
			$db->insert(TBL_PROCESS_LOG, $data);

			$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
			$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'p.category=pc.catid');
			$db->join(TBL_INVENTORY_LOG . ' il', 'il.inv_id=i.inv_id');
			$db->joinWhere(TBL_INVENTORY_LOG . ' il', '(il.log_type = ? OR il.log_type = ?)', array('qc_failed', 'repairs'));
			$db->where('(i.inv_status = ? OR i.inv_status = ?)', array('qc_failed', 'components_requested'));
			$db->where('i.inv_id', $uid);
			$db->orderBy('il.log_date', 'ASC');
			$return = $db->get(TBL_INVENTORY . ' i', NULL, 'i.inv_id as uid, il.log_type, p.sku, p.thumb_image_url, pc.categoryName as category, pc.catid as issue_category, il.log_details as issues, il.log_date');
			if ($return) {
				$current_count = count($return) - 1;
				$latest = $return[$current_count];
				unset($return[$current_count]);
				$history = array('history' => $return, 'historical_repairs' => $current_count);
				$db->where('inv_id', $uid);
				$db->where('log_type', 'components_requested');
				$db->orderBy('log_date', 'DESC');
				$components_requested = $db->getValue(TBL_INVENTORY_LOG, 'log_details');
				$return = array_merge(array('type' => 'success'), $latest, array('components_requested' => $components_requested), $history);
				echo json_encode($return);
			} else {
				$db->where('inv_id', $uid);
				$status = $db->getValue(TBL_INVENTORY, 'inv_status');
				echo json_encode(array('type' => 'info', 'msg' => 'Product status is ' . strtoupper($status)));
			}
			break;

		case 'sideline_product':
			$data  = array(
				"logType" => "Product " . $_REQUEST["type"] . " Sidelined",
				"identifier" => $_REQUEST["uid"],
				"userId" => $current_user['userID']
			);
			if ($db->insert(TBL_PROCESS_LOG, $data))
				echo json_encode(array('type' => 'success', 'message' => "Product successfully sidelined"));
			else
				echo json_encode(array('type' => 'error', 'message' => 'Log Error : ' . $db->getLastError()));
			break;
			break;
		case 'update_uid_repairs':
			$inv_id = $_POST['inv_id'];
			$fixed_issues = json_decode($_POST['fixed_issues']);
			$components_requested = $_POST['components_requested'];

			// $inv_id = 'SF01AD004027';
			// $fixed_issues = json_decode('[{"issue_id":26,"issue":"Display Damage","issue_type":"Digital Issue","issue_group":1,"issue_fix":"Replace Display"},{"issue_id":8,"issue":"Needle","issue_type":"Analogue Issue","issue_group":2,"issue_fix":"Fix Needle"}]');
			// $components_requested = json_decode('');

			$db->where('inv_id', $inv_id);
			$db->where('(log_type = ? OR log_type = ?)', array('qc_failed', 'repairs'));
			$db->orderBy('log_date', 'DESC');
			$details = $db->getOne(TBL_INVENTORY_LOG, 'invi_id, log_details');
			$log_details = json_decode($details['log_details'], true);
			foreach ($fixed_issues as $fixed_issue) {
				$log_details[$fixed_issue->issue_id] = true;
			}

			$status = "qc_pending";
			$issues_found = in_array(false, $log_details);
			if ($issues_found) {
				$status = 'qc_failed';
			} else {
				$components_requested = "";
			}

			if (!empty($components_requested)) {
				$stockist->add_inventory_log($inv_id, 'components_requested', $components_requested);
				$status = "components_requested";
			}

			$stockist->add_inventory_log($inv_id, 'repairs', json_encode($log_details));
			$data  = array(
				"logType" => "Repair Product End",
				"identifier" => $inv_id,
				"userId" => $current_user['userID']
			);
			$log_status = $db->insert(TBL_PROCESS_LOG, $data);

			if ($stockist->update_inventory_status($inv_id, $status)) {
				$return = array('type' => 'success', 'msg' => 'Successfully updated inventory status');
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to updated inventory status', 'error' => $db->getLastError());
			}

			echo json_encode($return);

			break;

			// COMPONENTS
		case 'add_components': // @TODO: ADD COMPONENT MANAGEMENT
			break;

		case 'update_components': // @TODO: ADD COMPONENT MANAGEMENT
			break;

			// HISTORY
		case 'product_timeline':
			$uid = $_GET['uid'];

			// PRODUCT
			$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
			$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=p.brand');
			$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'pc.catid=p.category');
			$db->where('i.inv_id', $uid);
			$product = $db->getOne(TBL_INVENTORY . ' i', 'i.inv_id, p.sku, pb.brandName, pc.catid, pc.categoryName, i.item_price, i.inv_status');
			if (empty($product)) {
				echo json_encode(array('type' => 'error', 'content' => '', 'msg' => 'No such product found.'));
				return;
			}

			// LOG
			$db->join(TBL_USERS . ' u', 'u.userID=il.user_id');
			$db->where('inv_id', $uid);
			// $db->orderBy('log_date', 'DESC');
			$timeline = $db->get(TBL_INVENTORY_LOG . ' il', NULL, 'il.log_type, il.log_details, u.display_name, il.log_date');

			// ISSUES
			$db->where('issue_category', $product['catid']);
			$issues = $db->get(TBL_INVENTORY_QC_ISSUES, NULL, 'issue_id, issue');

			$status_type = array(
				'inbound' => array(
					'name' => 'Inbound',
					'icon' => 'fa-plane-arrival',
					'color' => 'dark'
				),
				'outbound' => array(
					'name' => 'Outbound',
					'icon' => 'fa-plane-departure',
					'color' => 'dark'
				),
				'tagging' => array(
					'name' => 'Tagging',
					'icon' => 'fa-tags',
					'color' => 'primary'
				),
				'qc_pending' => array(
					'name' => 'Soft QC Rejected',
					'icon' => 'fa-times-circle',
					'color' => 'danger'
				),
				'qc_failed' => array(
					'name' => 'QC Failed',
					'icon' => 'fa-times-circle',
					'color' => 'danger'
				),
				'qc_verified' => array(
					'name' => 'QC Verified',
					'icon' => 'fa-thumbs-up',
					'color' => 'success'
				),
				'qc_cooling' => array(
					'name' => 'Under Cooling',
					'icon' => 'fa-snowflake',
					'color' => 'info'
				),
				'components_requested' => array(
					'name' => 'Components Requested',
					'icon' => 'fa-hand-paper',
					'color' => 'info'
				),
				'repairs' => array(
					'name' => 'Repairs',
					'icon' => 'fa-tools',
					'color' => 'info'
				),
				'moved' => array(
					'name' => 'Moved',
					'icon' => 'fa-people-carry',
					'color' => 'info'
				),
				'alteration' => array(
					'name' => 'Alteration',
					'icon' => 'fa-exchange-alt',
					'color' => 'info'
				),
				'sales' => array(
					'name' => 'Sales',
					'icon' => 'fa-award',
					'color' => 'success'
				),
				'sales_return' => array(
					'name' => 'Sales Return',
					'icon' => 'fa-reply-all',
					'color' => 'warning'
				),
				'audit' => array(
					'name'	=> 'Audit',
					'icon' => 'fa-check-double',
					'color' => 'info'
				),
				'lost' => array(
					'name' => 'Lost',
					'icon' => 'fa-window-close',
					'color' => 'warning',
				),
				'dead_stock' => array(
					'name' => 'Dead Stock',
					'icon' => 'fa-skull-crossbones',
					'color' => 'danger'
				),
				'liquidated' => array(
					'name' => 'Liquidated',
					'icon' => 'fa-trash',
					'color' => 'info'
				)
			);

			$html = '<hr /><div class="row">
			<div class="col-md-12 text-center">
				<span class="label label-xl label-outline-dark label-pill label-inline bold margin-right-10 margin-bottom-20">
					Inv Id: <span class="label label-xl label-nested label-inline label-pill label-light-dark bold">' . $product["inv_id"] . '</span>
				</span>
				<span class="label label-xl label-outline-dark label-pill label-inline bold margin-right-10 margin-bottom-20">
					SKU: <span class="label label-xl label-nested label-light-dark label-inline bold uppercase">' . $product["sku"] . '</span>
				</span>
				<span class="label label-xl label-outline-dark label-pill label-inline bold margin-right-10 margin-bottom-20">
					Brand: <span class="label label-xl label-nested label-inline label-pill label-light-dark bold">' . $product["brandName"] . '</span>
				</span>
				<span class="label label-xl label-outline-dark label-pill label-inline bold margin-right-10 margin-bottom-20">
					Category: <span class="label label-xl label-nested label-inline label-pill label-light-dark bold">' . $product["categoryName"] . '</span>
				</span>
				<span class="label label-xl label-outline-dark label-pill label-inline bold margin-right-10 margin-bottom-20">
					Value: <span class="label label-xl label-nested label-inline label-pill label-light-dark bold">&#8377;' . $product["item_price"] . '</span>
				</span>
				<span class="label label-xl label-outline-dark label-pill label-inline bold margin-right-10 margin-bottom-20">
					Status: <span class="label label-xl label-nested label-inline label-pill label-light-dark bold uppercase">' . $product["inv_status"] . '</span>
				</span>
			</div>
			<div class="col-md-12">
				<div class="timeline timeline-1">
					<div class="timeline-sep bg-primary-opacity-20"></div>';

			$lot_no = "";
			foreach ($timeline as $line) {
				if (empty($line['log_details']))
					continue;

				$log_type = $status_type[$line['log_type']];
				$icon = 'fa-genderless';
				$color = 'default';
				if ($log_type) {
					$icon = $log_type['icon'];
					$color = $log_type['color'];
					$name = $log_type['name'];
				}

				$lot = strpos($line['log_details'], 'Lot#');
				if ($lot) {
					$lot_no = substr($line['log_details'], $lot + 5, 6);
				}

				if ($line['log_type'] == "tagging") {
					$line['log_details'] = str_replace(array('Carton# ', 'Box#'), array('Carton# CTN', 'Box# BOX'), $line['log_details']);
				}

				if ($line['log_type'] == "repairs" || $line['log_type'] == "qc_failed") {
					$repair_issues = json_decode($line['log_details'], true);
					// $repair_issues = array_merge($repair_issues, $repair_issues, $repair_issues, $repair_issues, $repair_issues, $repair_issues, $repair_issues, $repair_issues);
					$log_details = "";
					$i = 0;
					foreach ($repair_issues as $issue_key => $issues_status) {
						$bottom_class = "";
						if ($i % 4 == 0 && $i > 0) {
							$log_details .= '<br />';
							$bottom_class = 'margin-top-10';
						}
						$i++;
						$key = array_search($issue_key, array_column($issues, 'issue_id'));
						$issue_name = $issues[$key]['issue'];
						if ($issues_status)
							$log_details .= '<span class="issue_label label label-outline-success label-inline margin-right-10 ' . $bottom_class . '">' . $issue_name . '</span>';
						else
							$log_details .= '<span class="issue_label label label-outline-danger label-inline margin-right-10 ' . $bottom_class . '">' . $issue_name . '</span>';
					}
					$line['log_details'] = $log_details;
				}

				if ($line['log_type'] == "components_requested") {
					$components = json_decode($line['log_details'], true);
					$log_details = "";
					$i = 0;
					foreach ($components as $component) {
						if ($i % 5 == 0 && $i > 0)
							$log_details .= '<br class="margin-top-5" />';
						$i++;
						$log_details .= '<span class="issue_label label label-outline-warning label-inline margin-right-10">' . $component['component_name'] . '</span>';
					}
					$line['log_details'] = $log_details;
				}

				$html .= '
					<div class="timeline-items">
						<div class="timeline-label">' . date('d M, Y<\b\r>h:i:s a', strtotime($line["log_date"])) . '</div>
						<div class="timeline-badge">
							<i class="fa ' . $icon . ' text-' . $color . '"></i>
						</div>
						<div class="timeline-status">
							<span class="label label-outline-' . $color . ' label-inline bold">' . $name . '</span>
						</div>
						<div class="timeline-item timeline-content text-muted font-weight-normal">
							' . str_replace('::', '<br />', $line["log_details"]) . '
						</div>
						<div class="timeline-user col-md-1 text-right">
							<span class="badge badge-info">' . $line["display_name"] . '</span>
						</div>
					</div>';
			}

			$html .= '</div>
				</div>
			</div>';

			echo json_encode(array('type' => 'success', 'content' => $html));
			break;

			// MANAGE
		case 'manage_inventory':
			$manage_type = $_POST['manage_type'];
			if ($manage_type == "move_to") {
				$from = $_POST['uid'];
				$to = $_POST['update_uid'];
				// $from = "BOX000000001";
				// $to = "CTN000000003";
				if ($from == $to) {
					$return = array('type' => 'error', 'msg' => 'Both barcode cannot be same');
				} else {
					$from_bx_ctn = (strpos($from, 'BOX') === FALSE) ? ((strpos($from, 'CTN') === FALSE) ? '' : 'CTN') : 'BOX';
					$to_bx_ctn = (strpos($to, 'BOX') === FALSE) ? ((strpos($to, 'CTN') === FALSE) ? '' : 'CTN') : 'BOX';
					$box_id = $ctn_id = str_replace('0', '', substr($from, -9));
					$t_box_id = $t_ctn_id = str_replace('0', '', substr($to, -9));

					if (empty($to_bx_ctn)) {
						$return = array('type' => 'error', 'msg' => 'Invalid move request');
					} else if (empty($from_bx_ctn) && $to_bx_ctn == "CTN") {
						$return = array('type' => 'error', 'msg' => 'Cannot move product directly to carton. Move product to box');
					} else if ($from_bx_ctn == "CTN" && $to_bx_ctn == "BOX") {
						$return = array('type' => 'error', 'msg' => 'Cannot move carton to box');
					} else if ($from_bx_ctn == "CTN" && $to_bx_ctn == "CTN") {
						$return = array('type' => 'error', 'msg' => 'Cannot move carton to caron. Move box to carton');
					} else {
						$lot_no = substr($uid, 6);
						if (empty($from_bx_ctn)) {
							$db->where('inv_id', $from);
							$products = $db->get(TBL_INVENTORY, NULL, 'inv_id, box_id, ctn_id');
						} else {
							$db->where('box_id', $box_id);
							$products = $db->get(TBL_INVENTORY, NULL, 'inv_id, box_id, ctn_id');
						}

						$update = array();
						foreach ($products as $product) {
							if (empty($from_bx_ctn)) {
								$m_from = 'BOX' . sprintf('%09d', $product['box_id']);
								$c_from = 'BOX';
							}

							if ($from_bx_ctn == "BOX") {
								$m_from = 'BOX' . sprintf('%09d', $product['box_id']);
								$c_from = 'Box';
							}

							if ($from_bx_ctn == "CTN") {
								$m_from = 'CTN' . sprintf('%09d', $product['ctn_id']);
								$c_from = 'Carton';
							}

							$details = array();
							if ($to_bx_ctn == "BOX") {
								$details['box_id'] = $t_box_id;
								$m_from .= ' of Carton# CTN' . sprintf('%09d', $product['ctn_id']);
								$m_to = 'Box';

								$db->where('box_id', $t_box_id);
								$ctn = $db->getValue(TBL_INVENTORY, 'ctn_id');
								if ($product['ctn_id'] != $ctn) {
									$to_ext = ' of Carton# CTN' . sprintf('%09d', $ctn);
								}
							}

							if ($to_bx_ctn == "CTN") {
								$details['ctn_id'] = $t_ctn_id;
								$m_from .= ' of Carton# CTN' . sprintf('%09d', $product['ctn_id']);
								$m_to = 'Carton';
							}

							if ($to == $m_from)
								continue;

							$log_details = "Moved Inventory :: To " . $m_to . "# " . $to . $to_ext . " :: From " . $c_from . "# " . $m_from . $from_box . " <br />";
							if ($stockist->update_inventory_details($product['inv_id'], $details)) {
								if ($stockist->add_inventory_log($product['inv_id'], 'moved', $log_details)) {
									$update[$product['inv_id']] = true;
								} else {
									$update[$product['inv_id']] = false;
								}
							} else {
								$update[$product['inv_id']] = false;
							}
						}

						if (in_array(false, $update)) {
							$return = array('type' => 'error', 'msg' => 'Update done with error', 'error' => $update);
						} else {
							$return = array('type' => 'success', 'msg' => 'Successfully updated inventory');
						}
					}
				}
			}

			if ($manage_type == "status") {
				$inv_id = $_POST['uid'];
				$inv_status = $_POST['status'];
				$db->where('inv_id', $inv_id);
				$status = $db->getValue(TBL_INVENTORY, 'inv_status');
				if ($inv_status == $status) {
					$return = array('type' => 'error', 'msg' => 'Current status is as same status');
				} else if ($status == "lost" || $status == "liquidated" || $status == "outbound") {
					$return = array('type' => 'error', 'msg' => 'Lost/Liquidated Product. No status change allowed.');
				} else {
					if ($stockist->update_inventory_status($inv_id, $inv_status)) {
						if ($stockist->add_inventory_log($inv_id, $inv_status, 'Item ' . ucfirst($inv_status))) {
							$return = array('type' => 'success', 'msg' => 'Successfully updated inventory status');
						} else {
							$return = array('type' => 'error', 'msg' => 'Unable to updated inventory status log', 'error' => $db->getLastError());
						}
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to updated inventory status', 'error' => $db->getLastError());
					}
				}
			}

			echo json_encode($return);
			break;

		case 'get_content_details':
			try {
				$number = $_GET["box_number"];
				$ctn = (str_contains($number, "CTN") ? (int) str_replace("CTN", "", $number) : "");
				$box = (str_contains($number, "BOX") ? (int) str_replace("BOX", "", $number) : "");

				if ($ctn != "") {
					$db->where("ctn_id", $ctn);
					$db->orderBy("box_id");
				} else {
					$db->where("box_id", $box);
				}
				$data = $db->get(TBL_INVENTORY, null, "box_id, inv_id, inv_audit_tag, inv_status");
				$boxContent = '';
				$current_audit_tag = get_option("current_audit_tag");
				$ctnCount = 0;
				for ($i = 0; $i < count($data); $i++) {
					$box = $data[$i]["box_id"];
					$count = 0;
					$contentTag = "";
					while ($box == $data[$i]["box_id"]) {
						$tags = (isset($data[$i]['inv_audit_tag']) ? json_decode($data[$i]['inv_audit_tag'], true) : array());
						$badge = 'outline';
						if (in_array($current_audit_tag, $tags)) {
							$badge = 'success';
						}
						if ($data[$i]["inv_status"] == "qc_cooling") {
							$badge = 'danger';
						}
						$contentTag .= '<span class="badge badge-' . $badge . '">' . $data[$i]["inv_id"] . '</span>';
						$i++;
						$count++;
						$ctnCount++;
					}
					$i--;

					$boxContent .= '<div class="panel panel-default">
					<div class="panel-heading">BOX' . sprintf("%09d", $box) . ' <span>&nbsp;&nbsp;&nbsp;[' . $count . ']</span></div><div class="panel-body">' . $contentTag . '</div></div>';
				}
				$content = '<div class="panel panel-default">
				<div class="panel-heading"><strong>' . $number . ' &nbsp;&nbsp;&nbsp;[' . $ctnCount . ']</strong></div></div>' . $boxContent;


				echo json_encode(["type" => "success", "content" => $content, "ctnCount" => $ctnCount]);
			} catch (Exception $e) {
				echo json_encode(["type" => "error", "error" => $e->getMessage()]);
			}
			break;
			// SWAP
		case 'get_swap_gnr_items':
			$items = get_grn_items(array($_GET['grn_id']));
			$item_data = array();
			if ($items) {
				foreach ($items['GRN_' . $_GET['grn_id']] as $item) {
					$db->where('item_id', $item->item_id);
					$db->where('grn_id', $_GET['grn_id']);
					$db->where('(box_id IS NULL AND ctn_id IS NULL)', array());
					$inv = $db->get(TBL_INVENTORY, NULL, 'count(*) as inv_qty');

					$item_details = array(
						'id' => $item->item_id,
						'sku' => $item->sku,
						'qty' => $inv[0]['inv_qty'],
					);
					$item_data[] = $item_details;
				}
			}
			$return = array('type' => 'success', 'items' => $item_data);
			echo json_encode($return);
			break;

		case 'swap_inventory':
			$grn_id = $_POST['grn_id'];
			$grn_sku = $_POST['grn_sku'];
			$new_sku = $_POST['new_sku'];
			$qty = $_POST['qty'];

			$db->where('grn_id', $grn_id);
			$db->where('item_id', $grn_sku);
			$db->where('(box_id IS NULL AND ctn_id IS NULL)', array());
			$db->orderBy('inv_id', 'DESC');
			if ($db->update(TBL_INVENTORY, array('item_id' => $new_sku), $qty)) {
				$db->where('grn_id', $grn_id);
				$db->where('item_id', $grn_sku);
				$old_details = $db->getOne(TBL_PURCHASE_GRN_ITEMS);

				// UPDATE SKU FROM STOCK
				$db->where('grn_id', $grn_id);
				$db->where('item_id', $grn_sku);
				$db->update(TBL_PURCHASE_GRN_ITEMS, array("item_qty" => (int)$old_details['item_qty'] - (int)$qty));

				$db->where('grn_id', $grn_id);
				$db->where('item_id', $new_sku);
				$inv_details = $db->getOne(TBL_PURCHASE_GRN_ITEMS);
				if ($inv_details) {
					// UPDATE SKU TO STOCK
					$details = array(
						"item_qty" => (int)$inv_details['item_qty'] + (int)$qty
					);
					if ($inv_details['item_status'] == "received")
						$details['item_status'] = "receiving";

					$db->where('grn_id', $grn_id);
					$db->where('item_id', $new_sku);
					if ($db->update(TBL_PURCHASE_GRN_ITEMS, $details)) {
						$return = array('type' => 'success', 'msg' => 'Successfully Swaped SKU Details');
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to swaped SKU details', 'error' => $db->getLastError());
					}
				} else {
					$details = array(
						"grn_id" => $grn_id,
						"po_id" => 0,
						"item_id" => $new_sku,
						"item_qty" => $qty,
						"item_box" => 1,
						"insertDate" => date('Y-m-d H:i:s'),
					);
					if ($db->insert(TBL_PURCHASE_GRN_ITEMS, $details)) {
						$return = array('type' => 'success', 'msg' => 'Successfully Swaped SKU Details');
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to swaped SKU details', 'error' => $db->getLastError());
					}
				}
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to update inventory details', 'error' => $db->getLastError());
			}
			echo json_encode($return);
			break;

			// ALTER
		case 'alter_inventory':
			$product_ids = preg_split('/\r\n|\r|\n/', $_POST['product_ids']);
			$alter_to = $_POST['alter_to'];
			$alter_reason = $_POST['alter_reason'];
			$sku_from = $_POST['sku_from'];
			$sku_to = $_POST['sku_to'];

			foreach ($product_ids as $uid) {
				$details = array('item_id' => $alter_to, 'inv_status' => 'qc_pending');
				if ($stockist->update_inventory_details(trim($uid), $details)) {
					$response_i[$uid] = true;

					if ($stockist->add_inventory_log($uid, 'alteration', $alter_reason . ' :: ' . $sku_from . ' to ' . $sku_to))
						$response[$uid] = true;
					else
						$response[$uid] = false;
				} else
					$response_i[$uid] = false;
			}

			if (in_array(false, $response))
				$return = array('type' => 'info', 'msg' => 'Unable to update inventory log', 'data' => $response);
			else
				$return = array('type' => 'success', 'msg' => 'Successfully updated UID\'s');


			if (in_array(false, $response_i))
				$return = array('type' => 'info', 'msg' => 'Unable to update inventory', 'data' => $response_i);

			echo json_encode($return);
			break;

			// STOCK
		case 'inventory_stock':
			$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
			$db->groupBy('item_id');
			$db->orderBy('sku', 'ASC');
			$inventories = $db->get(TBL_INVENTORY . ' i', NULL, "	CONCAT('<a href=\"./reconciliation.php?sku=', TRIM(p.sku), '\" target=\"_blank\">', TRIM(p.sku), '</a>') as sku,
											SUM(IF(i.inv_status = 'inbound', 1, 0)) AS inbound,
											SUM(IF((i.inv_status = 'qc_pending' OR i.inv_status = 'qc_verified' OR i.inv_status = 'qc_cooling'), 1, 0)) AS saleable,
											SUM(IF(i.inv_status = 'qc_pending', 1, 0)) AS qc_pending,
											SUM(IF(i.inv_status = 'qc_cooling', 1, 0)) AS qc_cooling,
											SUM(IF(i.inv_status = 'qc_verified', 1, 0)) AS qc_verified,
											SUM(IF(i.inv_status = 'qc_failed', 1, 0)) AS qc_failed,
											SUM(IF(i.inv_status = 'components_requested', 1, 0)) AS components_requested,
											SUM(IF((i.inv_status = 'inbound' OR i.inv_status = 'components_requested' OR i.inv_status = 'qc_failed' OR i.inv_status = 'qc_pending' OR i.inv_status = 'qc_verified' OR i.inv_status = 'qc_cooling'), 1, 0)) AS stock, i.item_id");

			echo json_encode(array('data' => $inventories));
			break;

			// TRANSFER
		case 'transfer_stock':
			$product_ids = preg_split('/\r\n|\r|\n/', $_POST['product_ids']);
			$marketplace = $_POST['marketplace'];
			$account = $_POST['account'];
			$shipment_id = $_POST['shipment_id'];
			$shipped_to = $_POST['shipped_to'];
			$is_return = (isset($_POST['is_return']) && $_POST['is_return'] == "on") ? true : false;

			$response_i = $response = array();
			foreach ($product_ids as $product_id) {
				if (!empty(trim($product_id))) {
					$response_i[$product_id] = false;
					if ($is_return)
						$db->where('inv_status', 'sales');
					else
						$db->where('inv_status', 'qc_verified');
					$db->where('inv_id', $product_id);
					if ($db->has(TBL_INVENTORY)) {
						$log_details = "Moved Inventory :: To " . $m_to . "# " . $to . $to_ext . " :: From " . $c_from . "# " . $m_from . $from_box . " <br />";
						$inv_status = 'sales';
						if ($is_return)
							$inv_status = 'qc_pending';

						if ($stockist->update_inventory_details($product_id, array('inv_status' => $inv_status))) {
							$response_i[$product_id] = true;
							$response[$product_id] = false;
							if ($is_return) {
								if ($stockist->add_inventory_log($product_id, 'sales_return', ucwords($marketplace) . ' Stock Return :: FC: ' . $account . ' - ' . $shipped_to . ' :: Return Consignment ID: ' . $shipment_id))
									$response[$product_id] = true;
							} else {
								if ($stockist->add_inventory_log($product_id, 'sales', ucwords($marketplace) . ' Stock Transferred :: FC: ' . $account . ' - ' . $shipped_to . ' :: Consignment ID: ' . $shipment_id))
									$response[$product_id] = true;
							}
						}
					}
				}
			}

			if (in_array(false, $response))
				$return = array('type' => 'info', 'message' => 'Unable to update inventory log', 'data' => $response);
			else
				$return = array('type' => 'success', 'message' => 'Successfully updated inventory status');

			if (in_array(false, $response_i))
				$return = array('type' => 'info', 'message' => 'Unable to update inventory', 'data' => $response_i);

			echo json_encode($return);
			break;

		case 'vendor_return':
			$product_ids = preg_split('/\r\n|\r|\n/', $_POST['product_ids']);
			$vendor = $_POST['vendor'];
			$return_note_id = $_POST['return_note_id'];
			// $product_ids = array("LP0002ME0199","LP0002ME0072","LP0002ME0326");
			// $vendor = 33;

			$errors = $response = array();
			foreach ($product_ids as $product_id) {
				if (!empty(trim($product_id))) {
					$db->where('inv_id', $product_id);
					$inventory = $db->getOne(TBL_INVENTORY, 'item_id, grn_id, is_ams_inv, inv_status');
					if ($inventory['inv_status'] != "sales" || $inventory['inv_status'] != "outbound") {
						if ($inventory['is_ams_inv']) {
							$db->join(TBL_PURCHASE_GRN_ITEMS . ' pgi', 'pgi.po_id=po.po_id');
							$db->joinWhere(TBL_PURCHASE_GRN_ITEMS . ' pgi', 'pgi.grn_id', $inventory['grn_id']);
							$db->joinWhere(TBL_PURCHASE_GRN_ITEMS . ' pgi', 'pgi.item_id', $inventory['item_id']);
							$party_id = $db->getValue(TBL_PURCHASE_ORDER . ' po', 'po.po_party_id');
							if ($party_id == $vendor) {
								$log_details = "";
								if ($stockist->update_inventory_details($product_id, array('inv_status' => 'outbound'))) {
									$response[$product_id] = false;
									if ($stockist->add_inventory_log($product_id, 'outbound', 'Stock returned to Vendor :: Return Note# ' . $return_note_id))
										$response[$product_id] = true;
								}
							} else {
								$response[$product_id] = false;
								$errors[$product_id] = 'Inventory Vendor Mismatch';
							}
						} else {
							$response[$product_id] = false;
							$errors[$product_id] = 'Not an AMS Inventory';
						}
					} else {
						$response[$product_id] = false;
						$errors[$product_id] = 'Inventory is Sales/Outbound';
					}
				}
			}

			if (in_array(false, $response))
				$return = array('type' => 'info', 'message' => 'Unable to update inventory log', 'data' => $response);
			else
				$return = array('type' => 'success', 'message' => 'Successfully updated inventory status');

			if (!empty($errors))
				$return = array('type' => 'info', 'message' => 'Unable to update inventory', 'data' => $errors);

			echo json_encode($return);
			break;

			// RECONCILIATION
		case 'get_reconciliation_details':

			$statuses = array("inbound", "qc_pending", "qc_cooling", "qc_verified", "qc_failed", "components_requested", "dead_stock");
			$uids = array();
			$found_count = 0;
			$current_audit_tag = get_option('current_audit_tag');
			foreach ($statuses as $status) {
				$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
				$db->joinWhere(TBL_PRODUCTS_MASTER . ' p', 'p.sku', trim($_GET['sku']));
				$db->where('i.inv_status', $status);
				$db->orderBy('insertDate', 'ASC');
				$status_uids = $db->get(TBL_INVENTORY . ' i', NULL, "inv_id, inv_audit_tag");
				$response = null;
				if ($status_uids) {
					$response = array();
					foreach ($status_uids as $status_uid) {
						$tags = empty(json_decode($status_uid['inv_audit_tag'], true)) ? [] : json_decode($status_uid['inv_audit_tag'], true);
						$badge = 'outline';
						if (in_array($current_audit_tag, $tags))
							$badge = 'success';
						$response[] = '<span class="badge badge-' . $badge . '">' . $status_uid['inv_id'] . '</span>';
					}
				}

				$uids[$status] = (is_null($response) ? "" : $response);
				$found_count += $db->count;
			}

			if ($found_count > 0)
				echo json_encode(array('type' => 'success', 'content' => $uids, 'count' => $found_count));
			else
				echo json_encode(array('type' => 'error', 'msg' => 'No data found for SKU ' . $_GET["sku"]));
			break;

		case 'export_reconciliation_details':
			$statuses = array("inbound", "qc_pending", "qc_cooling", "qc_verified", "qc_failed", "components_requested", "dead_stock");
			$current_audit_tag = get_option('current_audit_tag');

			$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
			$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
				->setLastModifiedBy("Ishan Kukadia")
				->setTitle("Status Level Product UIDs")
				->setSubject("Status Level Product UIDs")
				->setDescription("Status Level Product UIDs");

			$objPHPExcel->setActiveSheetIndex(0);
			$objActiveSheet = $objPHPExcel->getActiveSheet();

			$column = 'A';
			foreach ($statuses as $status) {
				$row = 1;
				$objActiveSheet->setCellValue($column . $row++, strtoupper(str_replace('_', ' ', $status)));
				$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
				$db->joinWhere(TBL_PRODUCTS_MASTER . ' p', 'p.sku', trim($_GET['sku']));
				$db->where('i.inv_status', $status);
				$db->orderBy('insertDate', 'ASC');
				$response = $db->get(TBL_INVENTORY . ' i', NULL, "inv_id, inv_audit_tag");
				if (!is_null($response)) {
					foreach ($response as $uid_details) {
						$tags = json_decode($uid_details['inv_audit_tag'], true);
						if (in_array($current_audit_tag, $tags))
							$objActiveSheet->getStyle($column . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('92d050');

						$objActiveSheet->setCellValue($column . $row, strtoupper($uid_details['inv_id']));
						$row++;
					}
				}
				$objPHPExcel->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
				$column++;
			}

			// Rename worksheet
			$objPHPExcel->getActiveSheet()->setTitle('Sheet1');
			// Freeze panes
			$objPHPExcel->getActiveSheet()->freezePane('A2');
			// Set Active worksheet
			$objPHPExcel->setActiveSheetIndex(0);
			// Set Selected Cell
			$objPHPExcel->getActiveSheet()->setSelectedCell('A1');

			// Redirect output to a client's web browser (Xlsx)
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="' . $_GET["sku"] . '-' . date('Ymd') . '.xlsx"');
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

			// AUDIT
		case 'audit_uid':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$inv_id = trim(strtoupper($_REQUEST['uid']));
			$audit_id = $_REQUEST['audit_id'];
			$locationId = (isset($_REQUEST['locationId']) ? $_REQUEST['locationId'] : false);

			if (substr($inv_id, 0, 3) === "CTN") {
				$db->where('ctn_id', (int)substr($inv_id, 3));
				$inventories = $db->get(TBL_INVENTORY, NULL, 'inv_id, inv_status, inv_audit_tag');
				$uids = array_column($inventories, 'inv_id');
			} else if (substr($inv_id, 0, 3) === "BOX") {
				$db->where('box_id', (int)substr($inv_id, 3));
				$inventories = $db->get(TBL_INVENTORY, NULL, 'inv_id, inv_status, inv_audit_tag');
				$uids = array_column($inventories, 'inv_id');
			} else {
				$db->where('inv_id', $inv_id);
				$inventories = $db->get(TBL_INVENTORY, NULL, 'inv_id, inv_status, inv_audit_tag, location_audit_tag');
				$uids = array_column($inventories, 'inv_id');
			}

			$return = array();
			if ($uids) {
				if ($locationId) {
					$i = 0;
					foreach ($uids as $uid) {
						if ($inventories[$i]['inv_status'] == "sales" || $inventories[$i]['inv_status'] == "outbound")
							$return[ucwords(str_replace('_', ' ', $inventories[$i]['inv_status']))][] = $uid;
						else {
							$location_audit_tags = json_decode($inventories[$i]['location_audit_tag'], true);
							if (is_null($location_audit_tags))
								$location_audit_tags = array();

							if (!in_array($audit_id, $location_audit_tags)) {
								$audit_tag = json_encode(array_merge(array($audit_id), $location_audit_tags));
								$db->where('inv_id', $uid);
								if ($db->update(TBL_INVENTORY, array('location_audit_tag' => $audit_tag))) {
									if ($stockist->add_inventory_log($uid, 'audit', "Audited :: " . strtoupper($audit_id))) {
										$return['status'] = "Success";
									} else {
										$return['status'] = "Audit Log Unsuccessful";
									}
								} else {
									$return['status'] = "Audit Unsuccessful";
								}
							} else {
								$return['status'] = "Already Audited";
							}
						}
						$i++;
					}
				} else {
					$i = 0;
					foreach ($uids as $uid) {
						if ($inventories[$i]['inv_status'] == "sales" || $inventories[$i]['inv_status'] == "outbound")
							$return[ucwords(str_replace('_', ' ', $inventories[$i]['inv_status']))][] = $uid;
						else {
							$audit_tags = json_decode($inventories[$i]['inv_audit_tag'], true);
							if (is_null($audit_tags))
								$audit_tags = array();

							if (!in_array($audit_id, $audit_tags)) {
								$audit_tag = json_encode(array_merge(array($audit_id), $audit_tags));
								$db->where('inv_id', $uid);
								if ($db->update(TBL_INVENTORY, array('inv_audit_tag' => $audit_tag))) {
									if ($stockist->add_inventory_log($uid, 'audit', "Audited :: " . strtoupper($audit_id))) {
										$return['Success'][] = $uid;
									} else {
										$return['Audit Log Unsuccessful'][] = $uid;
									}
								} else {
									$return['Audit Unsuccessful'][] = $uid;
								}
							} else {
								$return['Already Audited'][] = $uid;
							}
						}
						$i++;
					}
				}
			} else {
				$return['not_found'][] = $uid;
			}
			echo json_encode(array('type' => 'success', 'msg' => $inv_id . ' scanned successfully', 'status' => $return));
			break;

			// ANALYTICS
		case 'analytics':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$date_range = explode('-', $_GET['dateRange']);
			$start_date = trim($date_range[0]);
			$end_date = trim($date_range[1]);
			$diffDays = round(1 + (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
			$daysCover = $_GET['daysCover'];
			$growthSpike = $_GET['growthSpike'];

			$db->join(TBL_INVENTORY . ' i', 'i.inv_id=il.inv_id');
			$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
			$db->join(TBL_PRODUCTS_BRAND . ' pb', 'pb.brandid=p.brand');
			$db->where('log_date', array(date("Y-m-j 00:00:00", strtotime($start_date)), date("Y-m-j 23:59:59", strtotime($end_date))), 'BETWEEN');
			// $db->where('(log_type = ? OR log_type = ?)', array('sales', 'sales_return'));
			$db->groupBy('p.sku');
			$db->orderBy('total_sales', 'DESC');
			$inventory = $db->get(
				TBL_INVENTORY_LOG . ' il',
				NULL,
				"p.pid as item_id, sku, pb.brandName,
				(SUM(CASE WHEN log_details LIKE '%Ajio Sales%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE '%Ajio Sales Return%' AND log_type = 'sales_return' THEN 1 ELSE 0 END)) as ajio,
				(SUM(CASE WHEN log_details LIKE '%Amazon Sales%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE '%Amozon Return%' AND log_type = 'sales_return' THEN 1 ELSE 0 END) + SUM(CASE WHEN log_details LIKE '%Amazon Stock Transferred%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE '%Amazon Stock Return%' AND log_type = 'sales_return' THEN 1 ELSE 0 END)) AS amazon,
				(SUM(CASE WHEN log_details LIKE '%Flipkart Sales%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE '%Flipkart Return%' AND log_type = 'sales_return' THEN 1 ELSE 0 END) + SUM(CASE WHEN log_details LIKE '%Flipkart Stock Transferred%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE '%Flipkart Stock Return%' AND log_type = 'sales_return' THEN 1 ELSE 0 END)) AS flipkart,
				(SUM(CASE WHEN log_details LIKE '%Jiomart Sales%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE '%Jiomart%' AND log_type = 'sales_return' THEN 1 ELSE 0 END)) as jiomart,
				(SUM(CASE WHEN log_details LIKE '%Meesho Sales%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE '%Meesho%' AND log_type = 'sales_return' THEN 1 ELSE 0 END)) as meesho,
				(SUM(CASE WHEN log_details LIKE '%Shopify Sales%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE '%Shopify Return%' AND log_type = 'sales_return' THEN 1 ELSE 0 END)) as website,
				(SUM(CASE WHEN log_details LIKE '%Offline Sales%' AND log_type = 'sales' THEN 1 ELSE 0 END) - SUM(CASE WHEN log_details LIKE 'Sales Return%' AND log_type = 'sales_return' THEN 1 ELSE 0 END)) as offline,
				(SUM(IF(log_type = 'sales', 1, 0)) - SUM(IF(log_type = 'sales_return', 1, 0))) as total_sales,
				(ROUND(((SUM(IF(log_type = 'sales', 1, 0)) - SUM(IF(log_type = 'sales_return', 1, 0)))/" . $diffDays . "))) as per_day_sales,
				((ROUND(((SUM(IF(log_type = 'sales', 1, 0)) - SUM(IF(log_type = 'sales_return', 1, 0)))/" . $diffDays . ")*" . $daysCover . "*" . $growthSpike . "))) as delta,
				'' as actions"
			);

			$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
			$db->where("(inv_status = ? OR inv_status = ?)", array('qc_verified', 'qc_pending'));
			$db->groupBy('sku');
			$stock_inventory = $db->get(TBL_INVENTORY . ' i', NULL, "p.pid as item_id, sku, COUNT(*) as stock");
			$data = array();
			foreach ($inventory as $list) {
				$stock = array_search($list['item_id'], array_column($stock_inventory, "item_id"));
				$stock = $stock_inventory[$stock]['stock'];
				$list['delta'] = $list['delta'] - $stock;
				$list['stock'] = $stock;
				$list['oos_day'] = ($list['per_day_sales'] < 1 ? 0 : round($stock / $list['per_day_sales']));
				$data[] = $list;
			}

			// echo $db->getLastQuery();
			// exit;
			// var_dump($data);
			echo json_encode(array('data' => $data));
			break;

		case 'setLocationSku':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$sku = $_REQUEST["sku"];
			$location = $_REQUEST["locationId"];

			$details = array(
				"sku" => $sku,
				"locationId" => $location
			);
			$id = $db->insert(TBL_LOCATION_ASSIGNMENTS, $details);

			if ($id) {
				$return = array("type" => "success", "message" => "Location mapped successfully!");
			} else {
				$return = array("type" => "error", "message" => "Error in Location Location Mapping!");
			}
			echo json_encode($return);
			break;

		case 'getLocations':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$db->join(TBL_LOCATION_ASSIGNMENTS . " la", "la.locationId = l.locationId", "LEFT");
			$db->where("l.locationId", "CMPREQ_%", "LIKE");
			$db->orWhere("l.locationId", "FAILED_%", "LIKE");
			$db->orderBy("l.locationId", "ASC");
			$data = $db->get(TBL_INVENTORY_LOCATIONS . " l", null, "l.locationId, la.sku");
			echo json_encode(["type" => "success", "data" => $data]);
			break;
		case 'MoveToCtn':
			$uid = $_REQUEST["uid"];
			$db->where("inv_id", $uid);
			$status = $db->getValue(TBL_INVENTORY, "inv_status");
			switch ($status) {
				case "qc_cooling":
					$details = array(
						"ctn_id" => "4004",
						"box_id" => "8371"
					);
					$type = "info";
					break;
				case "qc_pending":
					$details = array(
						"ctn_id" => "4005",
						"box_id" => "8372"
					);
					$type = "error";
					break;
				case "qc_verified":
					$details = array(
						"ctn_id" => "4006",
						"box_id" => "8373"
					);
					$type = "success";
					break;
				case "qc_failed":
					$details = array(
						"ctn_id" => "4007",
						"box_id" => "8374"
					);
					$type = "warning";
					break;
			}

			$db->where("inv_id", $uid);
			$db->update(TBL_INVENTORY, $details);
			$details = array(
				"inv_id" => $uid,
				"log_type" => "moved",
				"log_details" => "Moved To #CTN00000" . $details["ctn_id"] . " AND #BOX00000" . $details["box_id"],
				"user_id" => $current_user["userID"]
			);
			$db->insert(TBL_INVENTORY_LOG, $details);
			echo json_encode(["type" => "success", "notificationType" => $type]);
			break;

		case 'getLocationDetails':
			$locationId = $_REQUEST["location"];
			$db->where("currentLocation", $locationId);
			$uids = $db->get(TBL_INVENTIRY, null, "inv_id");
			break;

		case 'getSKU':
			$data = $db->get(TBL_PRODUCTS_MASTER, null, "sku");
			echo json_encode(["type" => "success", "data" => $data]);
			break;

		case 'add_location':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$category = $_REQUEST["category"];
			$capacity = $_REQUEST["capacity"];
			$locationId = "";

			switch ($category) {
				case 'components_requested';
					$db->where("locationId", "CMPREQ%", "LIKE");
					$count = (int) $db->getValue(TBL_INVENTORY_LOCATIONS, "count(id)");
					$nextId = ($count + 1);
					$title = "Components Requested Product Area " . sprintf("%03d", $nextId);
					$locationId = "CMPREQ_" . sprintf("%05d", $nextId);
					$details = array(
						"locationId" => $locationId,
						"locationTitle" => $title,
						"capacity" => $capacity,
						"availability" => $capacity,
						"createdBy" => $current_user["userID"]
					);

					$db->insert(TBL_INVENTORY_LOCATIONS, $details);
					break;

				case 'qc_failed';
					$db->where("locationId", "FAILED%", "LIKE");
					$count = (int) $db->getValue(TBL_INVENTORY_LOCATIONS, "count(id)");
					$nextId = ($count + 1);
					$title = "Repairable Inventory Area " . sprintf("%03d", $nextId);
					$locationId = "FAILED_" . sprintf("%05d", $nextId);
					$details = array(
						"locationId" => $locationId,
						"locationTitle" => $title,
						"capacity" => $capacity,
						"availability" => $capacity,
						"createdBy" => $current_user["userID"]
					);

					$db->insert(TBL_INVENTORY_LOCATIONS, $details);
					break;
			}

			echo json_encode(["type" => "success", "message" => "Location successfully added.\nLocation ID: " . $locationId]);
			break;

		case 'addTask':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$type = $_REQUEST["type"];
			$ctnId = (int) $_REQUEST["ctnId"];

			switch ($type) {
				case "printing":
					$db->where("ctn_id", $ctnId);
					$db->update(TBL_INVENTORY, ["expectedLocation" => "PRAREA_00001"]);
					$details = array(
						"title" => "Cartoon Is Ready For Printing!",
						"ctnId" => $ctnId,
						"createdBy" => $current_user["userID"],
						"category" => "printing",
						"userRoles" => "printing",
						"status" => "0",
					);
					$db->insert(TBL_TASK, $details);

					echo json_encode(["type" => "success", "msg" => "Move the cartoon to location : PRAREA_00001"]);
					break;
			}

		case 'getLocationInventory':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$location = $_REQUEST["locationId"];
			$auditId = $_REQUEST["audit_id"];
			$db->where("currentLocation", $location);
			$db->orderBy("ctn_id", "asc");
			$db->orderBy("box_id", "asc");
			$invData = $db->get(TBL_INVENTORY, null, "inv_id, ctn_id, box_id, inv_status, inv_audit_tag, location_audit_tag");
			$return = array();
			if ($invData) {
				for ($i = 0; $i < count($invData); $i++) {
					$ctn = $invData[$i]["ctn_id"];
					if ($ctn) {
						while ($ctn == $invData[$i]["ctn_id"]) {
							$box = $invData[$i]["box_id"];
							$count = 0;
							if ($box) {
								while ($box == $invData[$i]["box_id"]) {
									$return["data"][$ctn][$box][$count]["invId"] = $invData[$i]["inv_id"];
									$return["data"][$ctn][$box][$count]["status"] = $invData[$i]["inv_status"];
									$return["data"][$ctn][$box][$count]["auditStatus"] = (in_array($auditId, json_decode((empty($invData[$i]["location_audit_tag"]) ? "[]" : $invData[$i]["location_audit_tag"]), true)));
									$i++;
									$count++;
								}
							}
						}
					}
					$i--;
				}
				$return["type"] = "success";
			} else {
				$return = array(
					"type" => "error",
					"message" => "No inventory found on this location!"
				);
			}
			echo json_encode($return);
			break;
	}
} else {
	exit('hmmmm... trying to hack in ahh!');
}