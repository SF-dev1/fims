<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action']) && trim($_REQUEST['action']) != "") {
	include(dirname(dirname(__FILE__)) . '/config.php');
	global $db, $log, $client_id;

	switch ($_REQUEST['action']) {
		case 'sales_orders':
			// echo '<pre>';
			$client_id = $_GET['client_id'];
			$client_table = $client_id == 0 ? "" : '_' . $client_id;

			$db->join(TBL_PARTIES . ' p', 'p.party_id=so.so_party_id', 'LEFT');
			$db->join(TBL_SALE_ORDER_ITEMS . ' soi', 'soi.so_id=so.so_id', 'INNER');
			$db->joinWhere(TBL_SALE_ORDER_ITEMS . ' soi', 'soi.client_id', $client_id);
			$db->groupBy('so.so_id');
			$db->orderBy('so.insertDate', 'DESC');
			$sos = $db->get(TBL_SALE_ORDER . $client_table . ' so', NULL, 'so.insertDate, so.so_id, p.party_name, SUM(item_qty) as quantity, so.so_total_amount, so.so_status, p.party_id, so.so_notes');
			$i = 0;
			$return['data'] = array();
			foreach ($sos as $so) {
				$return['data'][$i] = $so;
				if ($so['party_id'] === 0) { // CASH SALE
					$so_notes = json_decode($so['so_notes'], true);
					$return['data'][$i]['party_name'] .= ' - ' . strtoupper($so_notes['customer_name']);
				}
				$return['data'][$i]['insertDate'] = date('d M, Y H:i:s', strtotime($so['insertDate']));
				$return['data'][$i]['so_id'] = sprintf('SO_%06d', $so['so_id']);
				$url = '';
				$action = '<a title="View SO" class="view btn btn-default btn-xs purple print_preview" data-toggle="modal" data-href="' . BASE_URL . '/sales/print.php?action=view_sales_order&id=' . $so['so_id'] . '&client_id=' . $client_id . '"><i class="fa fa-eye"></i></a>&nbsp;';
				if ($so['so_status'] == 'draft') {
					$return['data'][$i]['status'] = '<button type="button" class="btn btn-warning btn-xs">' . strtoupper($so['so_status']) . '</button>';
					$url = BASE_URL . '/sales/sales_new.php?so_id=' . $so['so_id'] . '&type=' . $so['so_status'];
					$action .= '<a title="Edit SO" class="edit btn btn-default btn-xs purple" href="' . $url . '"><i class="fa fa-edit"></i></a>&nbsp;';
					$action .= '<a title"Cancel SO" class="delete btn btn-default btn-xs purple so_id_' . $so['so_id'] . '"><i class="fa fa-trash-alt"></i></a>';
				} else if ($so['so_status'] == 'created') {
					$return['data'][$i]['status'] = '<button type="button" class="btn btn-success btn-xs">' . strtoupper($so['so_status']) . '</button>';
					$url = BASE_URL . '/sales/sales_new.php?so_id=' . $so['so_id'] . '&type=' . $so['so_status'] . '&so_edit=1';
					$action .= '<a title="Print SO" class="print btn btn-default btn-xs purple" data-so_id="' . $so['so_id'] . '" data-client_id="' . $client_id . '"><i class="fa fa-print"></i></a>&nbsp;';
					// $action .= '<a title="Email SO" class="email btn btn-default btn-xs purple" data-so_id="'.$so['so_id'].'" data-client_id="'.$client_id.'" '.$disabled_v.'><i class="far fa-envelope"></i></a>&nbsp;';
				} else {
					$return['data'][$i]['status'] = '<button type="button" class="btn btn-info btn-xs">' . strtoupper($so['so_status']) . '</button>';
				}
				$return['data'][$i]['action'] = $action;
				$i++;
			}
			// header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'get_selling_price_pid_party':
			$client_id = $_GET['client_id'];
			$client_table = $client_id == 0 ? "" : '_' . $client_id;
			$db->join(TBL_SALE_ORDER . ' so', 'so.so_id=soi.so_id', 'INNER');
			$db->joinWhere(TBL_SALE_ORDER . $client_table . ' so', 'so.so_party_id', $_GET['party_id']);
			$db->where('soi.item_id', $_GET['pid']);
			$db->orderBy('so.so_id', 'DESC');
			$sales_item = $db->getOne(TBL_SALE_ORDER_ITEMS . ' soi', 'soi.item_price, so.createdDate');

			$db->where('pid', $_GET['pid']);
			$item = $db->getOne(TBL_PRODUCTS_MASTER, 'selling_price as item_price, sp_updated_on');
			if (is_null($sales_item)) {
				$item_price = $item['item_price'];
			} else {
				$item_price = $sales_item['item_price'];
				if ($item['sp_updated_on'] > $sales_item['createdDate'])
					$item_price = $item['item_price'];
			}
			echo $item_price;
			break;

		case 'save_so':
			$po_total_amount = 0;
			$lineitems = $_POST['lineitems'];
			foreach ($lineitems as $lineitem) {
				$po_total_amount += (float)$lineitem['price'] * (int)$lineitem['qty'];
			}
			$so_edit = (isset($_POST['so_edit']) && $_POST['so_edit'] == "1" ? 1 : 0);
			$status = (isset($_POST['type']) && $_POST['type'] == "draft" ? "draft" : "created");
			$_POST['so_notes']['notes'] = htmlspecialchars(nl2br(strip_tags($_POST['so_notes']['notes'])));
			$details = array(
				'so_party_id' => $_POST['party_id'],
				'so_total_amount' => $po_total_amount,
				'so_status' => $status,
				'so_notes' => json_encode($_POST['so_notes'])
			);
			$client_id = $_POST['client_id'];
			$client_table = $client_id == 0 ? "" : '_' . $client_id;

			if (!empty($_POST['so_id'])) {
				$so_id = $_POST['so_id'];
				$db->where('so_id', $so_id);
				if ($status == 'created')
					$details['createdDate'] = $db->now();

				$db->update(TBL_SALE_ORDER . $client_table, $details);

				$db->where('so_id', $so_id);
				if ($db->delete(TBL_SALE_ORDER_ITEMS)) {
					$items = array();
					foreach ($lineitems as $lineitem) {
						$cost_price = get_cost_price('pid', $lineitem['sku']);
						$items[] = array(
							'so_id' => $so_id,
							'item_id' => $lineitem['sku'],
							'item_price' => $lineitem['price'],
							'item_qty' => $lineitem['qty'],
							'item_cp' => $cost_price['cost_price'],
							'uid' => json_encode(explode(',', $lineitem['uid'])),
							'client_id' => $client_id,
						);

						if ($client_id == 0) {
							if ($status == 'created') {
								$itemuids = explode(',', $lineitem['uid']);
								foreach ($itemuids as $itemuid) {
									$stockist->update_inventory_status(trim($itemuid), 'sales');
									$stockist->add_inventory_log(trim($itemuid), 'sales', 'Offline Sales :: ' . sprintf('SO_%06d', $so_id));
								}
							}
						}
					}

					if ($db->insertMulti(TBL_SALE_ORDER_ITEMS, $items)) {
						$msg = 'SO ' . $status . ' successfull updated with ID ' . sprintf('SO_%06d', $so_id);
						echo json_encode(array('type' => 'success', 'msg' => $msg, 'so_id' => $so_id));
						// if ($status == "create"){
						// 	session_write_close(); 
						// 	header('Location: '.BASE_URL.'/sales/sales_view.php');
						// }
					} else {
						$msg = ($status == 'draft' ? 'Unable to update SO Draft' : 'Unable to update SO with ID ' . sprintf('SO_%06d', $so_id));
						echo json_encode(array('type' => 'error', 'msg' => $msg, 'so_id' => $so_id));
					}
				} else {
					$msg = ($status == 'draft' ? 'Unable to update SO Draft' : 'Unable to update SO with ID ' . sprintf('SO_%06d', $so_id));
					echo json_encode(array('type' => 'error', 'msg' => $msg, 'so_id' => $so_id));
				}
			} else {
				$details['insertDate'] = $db->now();

				if ($status == 'created')
					$details['createdDate'] = $db->now();

				if ($so_id = $db->insert(TBL_SALE_ORDER . $client_table, $details)) {
					$items = array();
					foreach ($lineitems as $lineitem) {
						$cost_price = get_cost_price('pid', $lineitem['sku']);
						$items[] = array(
							'so_id' => $so_id,
							'item_id' => $lineitem['sku'],
							'item_price' => $lineitem['price'],
							'item_qty' => $lineitem['qty'],
							'item_cp' => $cost_price['cost_price'],
							'uid' => json_encode(explode(',', $lineitem['uid'])),
							'client_id' => $client_id,
						);
						if ($client_id == 0) {
							if ($status == 'created') {
								$itemuids = explode(',', $lineitem['uid']);
								foreach ($itemuids as $itemuid) {
									$stockist->update_inventory_status(trim($itemuid), 'sales');
									$stockist->add_inventory_log(trim($itemuid), 'sales', 'Offline Sales :: ' . sprintf('SO_%06d', $so_id));
								}
							}
						}
					}

					if ($db->insertMulti(TBL_SALE_ORDER_ITEMS, $items)) {
						echo json_encode(array('type' => 'success', 'msg' => 'SO Successfully created with ' . $status . ' status', 'so_id' => $so_id));
					} else {
						echo json_encode(array('type' => 'error', 'msg' => 'Unable to create SO', 'so_id' => $so_id, 'error' => $db->getLastError()));
					}
				} else {
					echo json_encode(array('type' => 'error', 'msg' => 'Unable to create SO'));
				}
			}
			break;

		case 'delete_so':
			$po_total_amount = 0;
			$so_id = $_POST['so_id'];
			$client_id = $_POST['client_id'];
			$client_table = $client_id == 0 ? "" : '_' . $client_id;
			$db->where('so_id', $so_id);
			$status = $db->getValue(TBL_SALE_ORDER, "so_status");
			if ($status === "draft") {
				$db->where('so_id', $so_id);
				if ($db->delete(TBL_SALE_ORDER_ITEMS)) {
					$details = array(
						'so_total_amount' => 0,
						'so_status' => 'cancelled',
					);
					$db->where('so_id', $so_id);
					if ($db->update(TBL_SALE_ORDER . $client_table, $details)) {
						echo json_encode(array('type' => 'success', 'msg' => 'SO Deleted Successfully with ID ' . $so_id, 'so_id' => $so_id));
					} else {
						echo json_encode(array('type' => 'error', 'msg' => 'Unable to Delete SO with ID ' . $so_id, 'so_id' => $so_id));
					}
				} else {
					echo json_encode(array('type' => 'error', 'msg' => 'Unable to Delete SO Items with ID ' . $so_id, 'so_id' => $so_id));
				}
			} else {
				echo json_encode(array('type' => 'error', 'msg' => 'Unable to Delete SO Items with ID ' . $so_id, 'so_id' => $so_id));
			}
			break;

		case 'get_so':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';
			$so_id = $_REQUEST['so_id'];
			$client_id = $_REQUEST['client_id'];
			$client_table = $client_id == 0 ? "" : '_' . $client_id;
			$db->join(TBL_PARTIES . ' p', 'p.party_id=so.so_party_id', 'LEFT');
			$db->where('so_id', $so_id);
			$party = $db->getOne(TBL_SALE_ORDER . $client_table . ' so', 'p.party_id, p.party_name, so.so_notes, so.so_status');
			$party['so_notes'] = json_decode($party['so_notes'], true);
			$party['so_notes']['notes'] = strip_tags(htmlspecialchars_decode(preg_replace('#<br\s*/?>#i', "\n", $party['so_notes']['notes'])));

			$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=soi.item_id');
			$db->where('soi.so_id', $so_id);
			$db->where('soi.client_id', $client_id);
			$sos['items'] = $db->get(TBL_SALE_ORDER_ITEMS . ' soi', null, 'soi.item_id, soi.item_price, soi.item_qty, soi.uid, pm.sku');
			$return = array_merge(array('so_id' => $so_id), $party, $sos);
			echo json_encode($return);
			break;

		case 'get_cash_so':
			$client_id = $_GET['client_id'];
			$client_table = $client_id == 0 ? "" : '_' . $client_id;
			$db->where('so_party_id', '0');
			$db->where('so_status', 'created');
			// $db->where('(JSON_EXTRACT(so_notes, "$.payment_status") = ? OR JSON_EXTRACT(so_notes, "$.payment_status") = ?)', array('pending', 'partial_paid'));
			$db->where('(so_notes LIKE ? OR so_notes LIKE ?)', array('%pending%', '%partial_paid%'));
			$sos = $db->get(TBL_SALE_ORDER . $client_table, NULL, 'so_id, CONCAT("SO_", LPAD(so_id, 6, 0), " - ", TRIM(JSON_UNQUOTE(JSON_EXTRACT(so_notes, "$.customer_name")))) as reference, so_total_amount');
			$return = array('type' => 'success', 'data' => $sos);
			echo json_encode($return);
			break;

		case 'save_sor':
			$so_id = $_POST["so_id"];
			$party_id = $_POST["party_id"];
			$uids = $_POST["uids"];

			if (empty($so_id) || (empty($party_id) && $party_id !== '0') || empty($uids))
				return;

			$uids = explode(',', $uids);
			$sr_total = 0;
			$return = array();
			$update_uids = array();
			foreach ($uids as $uid) {
				$db->where('inv_id', $uid);
				$db->where('inv_status', 'sales');
				if (!$db->has(TBL_INVENTORY)) {
					$return[$uid] = 'not_sales';
					continue;
				}
				$update_uids[] = $uid;
			}

			if (count($update_uids) != count($uids)) {
				echo json_encode(array('type' => 'error', 'msg' => 'Not all product are sold.', 'error' => $return));
			} else {
				foreach ($uids as $uid) {
					$db->where('so_id', $so_id);
					$db->where('client_id', $client_id);
					$db->where("uid", '%' . $uid . '%', 'LIKE');
					$item_price = $db->getValue(TBL_SALE_ORDER_ITEMS, 'item_price');
					if ($item_price > 0) {
						$sr_total += $item_price;
						$stockist->update_inventory_status(trim($uid), 'qc_pending');
						$stockist->add_inventory_log(trim($uid), 'sales_return', 'Sales Return :: ' . sprintf('SO_%06d', $so_id));
						$return[$uid] = 'success';
					} else {
						$return[$uid] = 'error';
					}
				}

				if (in_array('error', $return) || in_array('not_sales', $return)) {
					echo json_encode(array('type' => 'error', 'msg' => 'Error updating', 'error' => $return));
				} else {
					$details = array(
						'so_id' => $so_id,
						'party_id' => $party_id,
						'uid' => json_encode($uids),
						'return_amount' => $sr_total,
						'client_id' => $client_id
					);
					if ($sor_id = $db->insert(TBL_SALE_RETURN_ITEMS, $details))
						echo json_encode(array('type' => 'success', 'msg' => 'Successfully creted sales return ' . sprintf('SR_%06d', $sor_id)));
					else
						echo json_encode(array('type' => 'error', 'msg' => 'Error creating Sales Return. Please try again later.'));
				}
			}
			break;

		case 'save_payment':
			$details['client_id'] = $_POST['client_id'];
			$details['party_id'] = $_POST['party_id'];
			$details['payment_amount'] = $_POST['payment_amount'];
			$details['insertDate'] = date('Y-m-d ', strtotime($_POST['payment_date'])) . date('H:i:s', time());
			$details['payment_mode'] = $_POST['payment_mode'];
			$details['payment_reference'] = $_POST['payment_reference'];
			$details['payment_notes'] = $_POST['payment_remarks'];
			if ($_POST['party_id'] == 0) {
				$client_id = $_POST['client_id'];
				$client_table = $client_id == 0 ? "" : '_' . $client_id;

				$db->where('so_id', $_POST['payment_remarks']);
				$client_id = $client_id;
				$client_table = $client_id == 0 ? "" : '_' . $client_id;
				$order_details = $db->getOne(TBL_SALE_ORDER . $client_table, 'so_total_amount, so_notes');

				$payment_status = "paid";
				if ((float)$_POST['payment_amount'] < (float)$order_details['so_total_amount'])
					$payment_status = "partial_paid";

				$order_so_notes = json_decode($order_details['so_notes'], true);
				$order_so_notes['payment_status'] = $payment_status;
				$so_details = array(
					'so_notes' => json_encode($order_so_notes),
				);
				$db->where('so_id', $_POST['payment_remarks']);
				$db->update(TBL_SALE_ORDER . $client_table, $so_details);

				$details['payment_notes'] = sprintf('SO_%06d', $_POST['payment_remarks']);
			}
			$payment_id = $_POST['payment_id'];
			if ($payment_id != "") {
				$db->where('sales_payment_id', $payment_id);
				if ($db->update(TBL_SALE_PAYMENTS, $details))
					echo json_encode(array('type' => 'success', 'msg' => 'Successfully updated payment'));
				else
					echo json_encode(array('type' => 'error', 'msg' => 'Unable to update payment'));
			} else {
				if ($db->insert(TBL_SALE_PAYMENTS, $details))
					echo json_encode(array('type' => 'success', 'msg' => 'Successfully added new payment'));
				else
					echo json_encode(array('type' => 'error', 'msg' => 'Unable to added new payment'));
			}
			break;

		case 'delete_payment':
			$payment_id = $_POST['payment_id'];
			$db->where('sales_payment_id', $payment_id);
			if ($db->delete(TBL_SALE_PAYMENTS)) {
				echo json_encode(array('type' => 'success', 'msg' => 'Successfully deleted transaction.'));
			} else {
				echo json_encode(array('type' => 'success', 'msg' => 'Error deleting transaction.'));
			}
			break;

		case 'get_customer_ledger':
			$party_id = $_GET['party_id'];
			$start_date = strtotime($_GET['start_date'] . ' 00:00:00');
			$end_date = strtotime($_GET['end_date'] . ' 23:59:59');

			$ledger = $accountant->get_party_leadger("customer", $party_id, NULL, array('start_date' => $start_date, 'end_date' => $end_date));
			$transactions_details = "";
			foreach ($ledger as $ledger_item) {
				$transactions_details .= '<tr>
									<td>
										' . $ledger_item["date"] . '
									</td>
									<td>
										' . $ledger_item["description"] . '
									</td>
									<td>
										' . $ledger_item["amount_dr"] . '
									</td>
									<td>
										' . $ledger_item["amount_cr"] . '
									</td>
									<td>
										' . $ledger_item["balance"] . '
									</td>
								</tr>';
			}
			// echo '<table cellpadding=5 border=1>'.$transactions_details.'</table>';
			$return['transactions'] = $transactions_details;
			echo json_encode($return);
			break;

		case 'get_cash_ledger':
			$so_id = $_GET['so_id'];

			$db->where('so_id', $so_id);
			$total_sales = $db->getValue(TBL_SALE_ORDER, 'so_total_amount');

			$db->where('payment_notes', sprintf('SO_%06d', $so_id));
			$ledger = $db->get(TBL_SALE_PAYMENTS, NULL, array('insertDate AS date, CONCAT(UPPER(payment_mode), " PAYMENT #PR_", LPAD(sales_payment_id, 6, 0)) AS description, payment_amount AS amount_dr, payment_notes as notes, payment_reference as reference'));

			$transactions_details = "";
			foreach ($ledger as $ledger_item) {
				$paymentId = (int) filter_var($ledger_item["description"], FILTER_SANITIZE_NUMBER_INT);
				$paymentMode = strtolower(strtok($ledger_item["description"], ' '));
				$transactions_details .= '<tr>
									<td>
										' . $ledger_item["date"] . '
									</td>
									<td>
										' . $ledger_item["description"] . '
									</td>
									<td>
										' . $ledger_item["amount_dr"] . '
									</td>
									<td>
										<a href="#" data-paymentid="' . $paymentId . '" data-amount="' . $ledger_item["amount"] . '" data-date="' . $ledger_item["date"] . '" data-mode="' . ucfirst($paymentMode) . '" data-reference="' . $ledger_item["reference"] . '" data-notes="' . $ledger_item["motes"] . '" class="btn btn-default btn-xs black payment_edit"><i class="fa fa-edit"></i> Edit</a>
										<a href="#" data-paymentid="' . $paymentId . '" class="btn btn-default btn-xs black payment_delete"><i class="fa fa-trash"></i> Delete</a>
									</td>
								</tr>';

				$total_sales -= (float)$ledger_item["amount_dr"];
			}
			$return['due'] = number_format($total_sales, 2, '.', ',');
			$return['transactions'] = $transactions_details;
			echo json_encode($return);
			break;

		case 'get_payments_details':
			$return['due'] = $accountant->get_current_dues('customer', $_GET['party_id']);
			$ledger = $accountant->get_all_transactions('sales_payment', $_GET['party_id'], 10);
			$transactions_details = "";
			foreach ($ledger as $ledger_item) {
				$paymentId = (int) filter_var($ledger_item["description"], FILTER_SANITIZE_NUMBER_INT);
				$paymentMode = strtolower(strtok($ledger_item["description"], ' '));
				$transactions_details .= '<tr>
									<td>
										' . $ledger_item["date"] . '
									</td>
									<td>
										' . $ledger_item["description"] . '
									</td>
									<td>
										' . $ledger_item["amount_dr"] . '
									</td>
									<td>
										<a href="#" data-paymentid="' . $paymentId . '" data-amount="' . $ledger_item["amount"] . '" data-date="' . $ledger_item["date"] . '" data-mode="' . ucfirst($paymentMode) . '" data-reference="' . $ledger_item["reference"] . '" data-notes="' . $ledger_item["motes"] . '" class="btn btn-default btn-xs black payment_edit"><i class="fa fa-edit"></i> Edit</a>
										<a href="#" data-paymentid="' . $paymentId . '" class="btn btn-default btn-xs black payment_delete"><i class="fa fa-trash"></i> Delete</a>
									</td>
								</tr>';
			}
			// echo '<table cellpadding=5 border=1>'.$transactions_details.'</table>';
			$return['transactions'] = $transactions_details;
			echo json_encode($return);
			break;

		case 'get_current_dues':
			$return['due'] = "&#8377;" . number_format($accountant->get_current_dues('customer', $_GET['party_id']), 2, '.', ',');
			echo json_encode($return);
			break;

		case 'get_uid_details':
			$uid = $_GET['uid'];
			$isBoxCtn = false;
			$box = substr($uid, 0, 3) === "BOX";
			$ctn = substr($uid, 0, 3) === "CTN";
			if ($box || $ctn) {
				$isBoxCtn = true;
				$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
				$uid = (int) filter_var($uid, FILTER_SANITIZE_NUMBER_INT);
				if ($box)
					$db->where('i.box_id', (int)$uid);
				if ($ctn)
					$db->where('i.ctn_id', (int)$uid);
				$db->where('(inv_status = ? OR inv_status = ? OR inv_status = ?)', array('inbound', 'qc_pending', 'qc_verified'));
				$return = $db->get(TBL_INVENTORY . ' i', NULL, 'i.inv_id as uid, i.item_id, p.sku');
				$count = $db->count;
			} else {
				$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=i.item_id');
				$db->where('i.inv_id', $uid);
				$db->where('(inv_status = ? OR inv_status = ? OR inv_status = ?)', array('inbound', 'qc_pending', 'qc_verified'));
				$return = $db->getOne(TBL_INVENTORY . ' i', 'i.inv_id as uid, i.item_id, p.sku');
				$count = 1;
			}

			if ($return)
				echo json_encode(array('type' => 'success', 'item' => $return, 'isBoxCtn' => $isBoxCtn, 'count' => $count));
			else
				echo json_encode(array('type' => 'info', 'msg' => 'No sellable product found'));
			break;
	}
}
