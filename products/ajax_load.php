<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
if (isset($_REQUEST['action'])) {
	include(dirname(dirname(__FILE__)) . '/config.php');
	include(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
	include(ROOT_PATH . '/includes/vendor/autoload.php');
	global $db, $accounts, $log, $stockist;

	switch ($_REQUEST['action']) {
		case 'products':
			$client_id = $_GET['client_id'];
			$db->where('p.client_id', $client_id);
			$db->join(TBL_PRODUCTS_CATEGORY . " c", "p.category=c.catid", "INNER");
			$db->join(TBL_PRODUCTS_BRAND . " b", "p.brand=b.brandid", "LEFT");
			$db->where('is_active', 1);
			$db->orderBy('sku', 'ASC');
			$products = $db->get(TBL_PRODUCTS_MASTER . ' p', null, array('p.pid', 'c.catid', 'b.brandid', 'p.thumb_image_url', 'p.sku', 'p.selling_price', 'p.retail_price', 'p.family_price', 'c.categoryName', 'b.brandName', 'p.is_stock_offline', 'p.in_stock_rtl', 'p.in_stock', 'p.is_active'));

			$return = new stdClass();
			$i = 0;
			if ($products) {
				$index = 0;
				foreach ($products as $product) {
					foreach ($product as $p_value) {
						$return->data[$i][] = $p_value;
					}

					if ($return->data[$i][3] != "") {
						$return->data[$i][3] = '<img src="' . loadImage(IMAGE_URL . '/uploads/products/' . $return->data[$i][3], '1024', '1024') . '" height="100" width="100" />';
					}

					if ($return->data[$i][9] == NULL) {
						$return->data[$i][9] = 'No Brand';
						$return->data[$i][2] = 0;
					}

					if ($return->data[$i][10] == "1") {
						$return->data[$i][10] = '<input type="checkbox" checked class="is_stock_offline" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
					} else {
						$return->data[$i][8] = '<input type="checkbox" class="is_stock_offline" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
					}

					if ($return->data[$i][11] == "1") {
						$return->data[$i][11] = '<input type="checkbox" checked class="in_stock_rtl" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
					} else {
						$return->data[$i][11] = '<input type="checkbox" class="in_stock_rtl" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
					}

					if ($return->data[$i][12] == "1") {
						$return->data[$i][12] = '<input type="checkbox" checked class="in_stock" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
					} else {
						$return->data[$i][12] = '<input type="checkbox" class="in_stock" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
					}

					if ($return->data[$i][13] == "1") {
						$return->data[$i][13] = '<input type="checkbox" checked class="is_active" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
					} else {
						$return->data[$i][13] = '<input type="checkbox" class="is_active" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
					}

					$return->data[$i][] = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-edit"></i> Edit</a>';
					$return->data[$i][] = '<a class="delete btn btn-default btn-xs purple" href=""><i class="fa fa-trash-o"></i> Delete</a>';
					if ($client_id != 0) {
						$return->data[$i] = array_values($return->data[$i]);
					}
					$i++;
				}
			}
			echo json_encode($return);
			break;

		case 'get_products':
			$cp = $db->subQuery("cp");
			$cp->where('(inv_status = ? OR inv_status = ? OR inv_status = ?)', array('qc_pending', 'qc_failed', 'qc_verified'));
			$cp->groupBy('item_id');
			$cp->get(TBL_INVENTORY, null, "item_id, CAST(AVG(item_price) as UNSIGNED) as cost_price");

			$db->join($cp, "p.pid=cp.item_id", "LEFT");
			$db->where('p.client_id', $_GET['client_id']);
			$db->join(TBL_PRODUCTS_CATEGORY . " c", "p.category=c.catid", "INNER");
			$db->join(TBL_PRODUCTS_BRAND . " b", "p.brand=b.brandid", "LEFT");
			$db->where('is_active', 1);
			$db->orderBy('sku', 'ASC');
			$return['data'] = $db->get(TBL_PRODUCTS_MASTER . ' p', null, array('p.thumb_image_url', 'p.sku', 'p.selling_price', 'p.retail_price', 'p.family_price', 'c.categoryName', 'b.brandName', 'p.is_stock_offline', 'p.in_stock_rtl', 'p.in_stock', 'p.is_active', 'p.category', 'p.brand', 'p.pid', 'COALESCE(cp.cost_price, 0) as cost_price'));

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'edit_product':
			foreach ($_POST as $p_key => $p_value) {
				if ($p_key == "action" || $p_key == "pid") {
					continue;
				} else {
					$content = $db->escape($p_value);
					$details[$p_key] = trim($content);
				}
			}

			$pid = $_POST['pid'];
			$db->where('pid', $pid);
			$item_details = $db->getOne(TBL_PRODUCTS_MASTER, 'selling_price, is_stock_offline');

			if (isset($_FILES['thumb_image_url']) && !empty($_FILES['thumb_image_url'])) {

				$db->where('pid', $pid);
				$file = $db->getOne(TBL_PRODUCTS_MASTER, 'thumb_image_url');
				$details['thumb_image_url'] = $file['thumb_image_url'];

				$handle = new \Verot\Upload\Upload($_FILES['thumb_image_url']);
				if ($handle->uploaded) {
					// Remove old file
					$db->where('pid', $pid);
					$file = $db->getOne(TBL_PRODUCTS_MASTER, 'thumb_image_url');
					$upload_path = ROOT_PATH . "/uploads/products/";
					unlink($upload_path . $file['thumb_image_url']);

					// resize tool
					$handle->image_resize = true;
					$handle->image_x = 1000;
					$handle->image_y = 1000;
					$handle->image_ratio = true;
					$handle->image_convert = 'jpg';
					$handle->file_overwrite = true;
					$handle->dir_auto_create = true;
					$handle->allowed = array('image/*');
					$handle->file_new_name_body = 'product-' . $pid;
					$handle->process(ROOT_PATH . "/uploads/products/");
					$handle->clean();
					$details['thumb_image_url'] = 'product-' . $pid . '.jpg';
				}
			}

			if (isset($_POST['is_active']))
				$details['is_active'] = (int)($_POST['is_active']);

			if (isset($_POST['selling_price']) && (int)$item_details['selling_price'] != (int)$_POST['selling_price']) {
				$details['sp_updated_on'] = $db->now();
				if ($item_details['is_stock_offline']) {
					$log_details = ((int)$_POST['selling_price'] > (int)$item_details['selling_price'] ? 'Up' : 'Down');
					$log_details = array(
						'pid' => $pid,
						'user_id' => $current_user['userID'],
						'log_type' => 'Selling Price Change',
						'log_details' => 'Selling Price ' . $log_details
					);
					$db->insert(TBL_PRODUCTS_LOG, $log_details);
				}
			}

			if (isset($_POST['retail_price']) && (int)$item_details['retail_price'] != (int)$_POST['retail_price']) {
				$details['sp_updated_on'] = $db->now();
				if ($item_details['is_stock_offline']) {
					$log_details = ((int)$_POST['retail_price'] > (int)$item_details['retail_price'] ? 'Up' : 'Down');
					$log_details = array(
						'pid' => $pid,
						'user_id' => $current_user['userID'],
						'log_type' => 'Retail Price Change',
						'log_details' => 'Retail Price ' . $log_details
					);
					$db->insert(TBL_PRODUCTS_LOG, $log_details);
				}
			}

			if (isset($_POST['family_price']) && (int)$item_details['family_price'] != (int)$_POST['family_price']) {
				$details['sp_updated_on'] = $db->now();
				if ($item_details['is_stock_offline']) {
					$log_details = ((int)$_POST['family_price'] > (int)$item_details['family_price'] ? 'Up' : 'Down');
					$log_details = array(
						'pid' => $pid,
						'user_id' => $current_user['userID'],
						'log_type' => 'Family Price Change',
						'log_details' => 'Family Price ' . $log_details
					);
					$db->insert(TBL_PRODUCTS_LOG, $log_details);
				}
			}

			if (isset($details['in_stock_rtl'])) {
				$log_details = $details['in_stock_rtl'] ? 'In Stock' : 'Out of Stock';
				$log_details = array(
					'pid' => $pid,
					'user_id' => $current_user['userID'],
					'log_type' => 'Status Change',
					'log_details' => $log_details
				);
				$db->insert(TBL_PRODUCTS_LOG, $log_details);
			}

			if (isset($details['is_stock_offline'])) {
				$log_details = $details['is_stock_offline'] ? 'New Product added' : 'Product Discontinued';
				$log_details = array(
					'pid' => $pid,
					'user_id' => $current_user['userID'],
					'log_type' => 'Product Update',
					'log_details' => $log_details
				);
				$db->insert(TBL_PRODUCTS_LOG, $log_details);
			}

			$log->logfile = TODAYS_LOG_PATH . '/product-update.log';

			$db->where('pid', $pid);
			if ($db->update(TBL_PRODUCTS_MASTER, $details)) {
				$log->write('Successfull updated product with pid ' . $pid . json_encode($details));
				$return = array('type' => 'success', 'msg' => 'Successfull updated product with pid ' . $pid);
			} else {
				$log->write('Unable updated product with pid ' . $pid . ' ' . $db->getLastError());
				$return = array('type' => 'error', 'msg' => 'Unable updated product with pid ' . $pid);
			}
			echo json_encode($return);
			break;

		case 'add_product':
			$details['sku'] = $_POST['sku'];
			$details['category'] = $_POST['category'];
			$details['brand'] = $_POST['brand'];
			$details['selling_price'] = $_POST['selling_price'];
			$details['retail_price'] = $_POST['retail_price'];
			$details['family_price'] = $_POST['family_price'];
			$details['in_stock'] = (int)$_POST['in_stock'];
			$details['in_stock_rtl'] = isset($_POST['in_stock_rtl']) ? $_POST['in_stock_rtl'] : 0;
			$details['is_stock_offline'] = isset($_POST['is_stock_offline']) ? $_POST['is_stock_offline'] : 0;
			$details['sp_updated_on'] = $db->now();
			$details['client_id'] = $_POST['client_id'];

			$db->where('client_id', $_POST['client_id']);
			$db->where('sku', $_POST['sku']);
			if (!$db->has(TBL_PRODUCTS_MASTER)) {
				if ($id = $db->insert(TBL_PRODUCTS_MASTER, $details)) {
					$handle = new \Verot\Upload\Upload($_FILES['thumb_image_url']);
					if ($handle->uploaded) {
						// resize tool
						$handle->image_resize = true;
						$handle->image_x = 1000;
						$handle->image_y = 1000;
						$handle->image_ratio = true;
						$handle->image_convert = 'jpg';
						$handle->file_new_name_body = 'product-' . $id;
						$handle->file_overwrite = true;
						$handle->dir_auto_create = true;
						$handle->allowed = array('image/*');
						$handle->process(ROOT_PATH . "/uploads/products/");
						$handle->clean();
						$details['thumb_image_url'] = 'product-' . $id . '.jpg';
						$db->where('pid', $id);
						$db->update(TBL_PRODUCTS_MASTER, $details);
					}

					$return['type'] = "success";
					$return['msg'] = "Successfull added new product";
					echo json_encode($return);
				} else {
					$return['type'] = "error";
					$return['msg'] = "Error adding new product. Error :: " . $db->getLastError() . ' Q:: ' . $db->getLastQuery();
					echo json_encode($return);
				}
			} else {
				$return['type'] = "error";
				$return['message'] = "Duplicate SKU";
				echo json_encode($return);
			}
			break;

		case 'delete_product': // NO ACTION PERFORMED YET. @TODO: CHECK ACTIVE ALIAS AND COMBO BEFORE DELETION
			$db->where('pid', $_POST['pid']);
			if ($db->delete(TBL_PRODUCTS_MASTER)) {
				$return = array('type' => 'success', 'msg' => 'Successfull deleted product');
				$log->write('Successfull deleted brand.');
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to delete product');
				$log->write('Unable delete brand. :: ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'update_stock':
			if (isset($_POST['pid']) && isset($_POST['in_stock']) && !empty($_POST['pid'])) {

				$status = array();
				$error_fsns = array();

				if ($client_id == 0) {
					foreach ($accounts['flipkart'] as $account) {

						$current_account = $account;
						$account_id = (int)$account->account_id;

						$logfile = 'listing-' . $account->fk_account_name;
						$log->write("::" . $account->fk_account_name . "::", $logfile);

						$pid = $_POST['pid'];
						$in_stock = $_POST['in_stock'];

						$stock = $stockist->get_stock($pid, 'qc_verified');
						if ($stock > 100)
							$stock = 100;

						if ($in_stock) {
							$listing_status = 'ACTIVE';
							$and_query = "";
							$auto_update = 1;
						} else {
							$listing_status = 'INACTIVE';
							$and_query = ' AND fulfilled = 0';
							$auto_update = 0;
						}

						$db->where('pid', '%' . $pid . '%', 'LIKE');
						$query = $db->get(TBL_PRODUCTS_COMBO, null, array('cid', 'pid'));
						$cids = array();
						$pids = '';
						$qr = '';

						foreach ($query as $rows) {
							$cid_d = json_decode($rows['pid']);
							if (in_array($pid, $cid_d)) {
								$pids .= $rows['pid'];
								$cids[$rows['cid']] = json_decode($rows['pid']);
							}
						}

						if ($pids) {
							if ($in_stock && strpos($pids, $pid) !== FALSE) {
								$pids = json_decode(str_replace('"]["', '","', $pids));
								unset($pids[array_search($pid, $pids)]);
								$pids = json_encode($pids);
							}
							$a_pids = array_unique(json_decode(str_replace('"]["', '","', $pids), true));

							if ($in_stock) {
								$os_pids = array();
								$db->where('pid', $a_pids, 'IN');
								$db->where('in_stock', '0');
								$out_stock_pids = $db->get(TBL_PRODUCTS_MASTER, NULL, 'pid');

								foreach ($out_stock_pids as $out_stock_pid) {
									$os_pids[] = $out_stock_pid['pid'];
								}

								if ($os_pids) {
									foreach ($cids as $ckey => $cid) {
										if (count(array_intersect($cid, $os_pids)) != (int)0) {
											unset($cids[$ckey]);
										}
									}
								}
							}
						}

						if ($cids) {
							$cids = implode(',', array_keys($cids));
							$qr = "SELECT alias_id, mp_id, sku, price_now FROM `" . TBL_PRODUCTS_ALIAS . "` WHERE `cid` IN ($cids) AND marketplace = 'flipkart' AND account_id = " . $account_id . " UNION ALL ";
						}

						$listings = $db->rawQuery($qr . "SELECT alias_id, mp_id, sku, price_now FROM `" . TBL_PRODUCTS_ALIAS . "` WHERE `pid` IN ($pid) AND marketplace = 'flipkart' AND account_id = " . $account_id . $and_query);

						if ($listings) {
							$log->write(json_encode($listings), $logfile);
							$fk = new connector_flipkart($account);
							if (!in_array(ROOT_PATH . '/flipkart/functions.php', get_included_files()))
								include_once(ROOT_PATH . '/flipkart/functions.php');
							foreach ($listings as $listing) {
								extract($listing);
								$product = "";
								$ssp = get_selling_price($mp_id, $price_now + get_max_shipping_charges(), 1);

								$attributeValues = array();
								$attributeValues['listing_status'] = $listing_status;
								$attributeValues['selling_price'] = strval((int)$ssp['price']);
								if ($listing_status == "ACTIVE")
									$attributeValues["stock_count"] = strval($stock);

								// $attributeValues["national_shipping_charge"] = "52";
								// $attributeValues["zonal_shipping_charge"] = "36";
								// $attributeValues["local_shipping_charge"] = "28";
								// $attributeValues["package_length"] = "25";
								// $attributeValues["package_breadth"] = "17";
								// $attributeValues["package_height"] = "3";
								// $attributeValues["package_weight"] = "0.200";
								// $attributeValues["hsn"] = "91149091";
								// $attributeValues["selling_price"] = "620";

								$response = $fk->update_product(trim($sku), trim($mp_id), $attributeValues, false);
								$response_j = json_decode($response);
								if (isset($response_j->{$sku}->status) && ($response_j->{$sku}->status == 'SUCCESS')) {
									$log->write("Updated status of FSN " . $mp_id . " with SKU " . $sku . " to " . $listing_status . "\nAttribute: " . json_encode($attributeValues) . "\nResponse: " . $response, $logfile);
									$status[] = 'success';
								} else {
									$log->write("UNABLE to updated status of FSN " . $mp_id . " with SKU " . $sku . " to " . $listing_status . "\nAttribute: " . json_encode($attributeValues) . "\Response: " . $response, $logfile);
									$error_fsns[$account->account_name][] = $mp_id;
									$status[] = 'error';
								}
							}
							$details['in_stock'] = ($listing_status == "ACTIVE" ? 1 : 0);
							$db->where('pid', $pid);
							$db->update(TBL_PRODUCTS_MASTER, $details);
						} else {
							$status[] = 'error';
							$error_fsns[] = 'No Alias found to update.';
						}
					}

					if (in_array('error', $status)) {
						$return = array('type' => 'error', 'msg' => "There was error Processing one/more of your Request! \n Error in: \n" . json_encode($error_fsns));
					} else {
						$return = array('type' => 'success', 'msg' => 'Successfull updated stock status');
					}
				} else {
					$details['in_stock'] = $_POST['in_stock'];
					$db->where('pid', $_POST['pid']);
					if ($db->update(TBL_PRODUCTS_MASTER, $details)) {
						$return = array('type' => 'success', 'msg' => 'Successfull updated stock status');
					} else {
						$return = array('type' => 'error', 'msg' => 'Unable to updated stock status :: ' . $db->getLastError());
					}
				}
				echo json_encode($return);
			}
			break;

		case 'get_inactive_products':
			$db->where('is_active', 0);
			$db->orderBy('sku', 'ASC');
			$return = $db->get(TBL_PRODUCTS_MASTER, NULL, 'pid, sku');
			echo json_encode($return);
			break;

		case 'activate_product':
			$db->where('pid', $_POST['pid']);
			if ($db->update(TBL_PRODUCTS_MASTER, array('is_active' => 1)))
				$return = array('type' => 'success', 'msg' => 'Successfully activated product');
			else
				$return = array('type' => 'error', 'msg' => 'Error activating product');

			echo json_encode($return);
			break;

		case 'products_combo':
			$db->orderBy('sku', 'ASC');
			$products_array = $db->get(TBL_PRODUCTS_COMBO, null, array('thumb_image_url', 'sku', 'pid', 'cid'));

			$return = array();
			$i = 0;
			foreach ($products_array as $product) {
				foreach ($product as $p_key => $p_value) {
					if ($p_key == "pid") {
						$pids = json_decode($p_value);
						$skus = array();
						foreach ($pids as $pid) {
							$db->where("pid", (int)$pid);
							$p_sku = $db->getOne(TBL_PRODUCTS_MASTER);
							$skus[] = $p_sku['sku'];
						}
						$cid = $product['cid'];
						unset($products_array[$i]['pid']);
						unset($products_array[$i]['cid']);
						$products_array[$i]['inner_sku'] = implode(', ', $skus);
						$products_array[$i]['cid'] = $cid;
					}
				}
				$i++;
			}
			$return['data'] = $products_array;

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'add_product_combo':
			$details['sku'] = $_POST['sku'];
			$details['pid'] = json_encode(explode(',', $_POST['inner_sku']));
			$details['is_active'] = 1;

			$db->where('sku', $_POST['sku']);
			if (!$db->has(TBL_PRODUCTS_COMBO)) {
				if ($id = $db->insert(TBL_PRODUCTS_COMBO, $details)) {
					$handle = new \Verot\Upload\Upload($_FILES['thumb_image_url']);
					if ($handle->uploaded) {
						// resize tool
						$handle->image_resize = true;
						$handle->image_x = 100;
						$handle->image_y = 100;
						$handle->image_ratio = true;
						$handle->image_convert = 'jpg';
						$handle->file_overwrite = true;
						$handle->dir_auto_create = true;
						$handle->allowed = array('image/*');
						$handle->file_new_name_body = 'combo-' . $id;
						$handle->process(ROOT_PATH . "/uploads/products/");
						$handle->clean();
						$details['thumb_image_url'] = 'combo-' . $id . '.jpg';
						$db->where('cid', $id);
						$db->update(TBL_PRODUCTS_COMBO, $details);
					}

					$return['type'] = "Success";
					echo json_encode($return);
				} else {
					$return['type'] = "Error";
					$return['message'] = "Error adding new product " . $db->getLastError();
					echo json_encode($return);
				}
			} else {
				$return['type'] = "Error";
				$return['message'] = "Duplicate SKU";
				echo json_encode($return);
			}
			break;

		case 'edit_product_combo':
			$cid = $_POST['cid'];
			$details['sku'] = $_POST['sku'];

			if (isset($_FILES['thumb_image_url']) && !empty($_FILES['thumb_image_url'])) {

				$db->where('cid', $cid);
				$file = $db->getOne(TBL_PRODUCTS_COMBO, 'thumb_image_url');
				$details['thumb_image_url'] = $file['thumb_image_url'];

				$handle = new \Verot\Upload\Upload($_FILES['thumb_image_url']);
				if ($handle->uploaded) {
					// Remove old file
					$db->where('cid', $cid);
					$file = $db->getOne(TBL_PRODUCTS_COMBO, 'thumb_image_url');
					$upload_path = ROOT_PATH . "/uploads/products/";
					unlink($upload_path . $file['thumb_image_url']);

					// resize tool
					$handle->image_resize = true;
					$handle->image_x = 100;
					$handle->image_y = 100;
					$handle->image_ratio = true;
					$handle->image_convert = 'jpg';
					$handle->file_overwrite = true;
					$handle->dir_auto_create = true;
					$handle->allowed = array('image/*');
					$handle->file_new_name_body = 'combo-' . $cid;
					$handle->process(ROOT_PATH . "/uploads/products/");
					$handle->clean();
					$details['thumb_image_url'] = 'combo-' . $cid . '.jpg';
				}
			}

			$log->logfile = TODAYS_LOG_PATH . '/product-update.log';

			$db->where('cid', $cid);
			if ($db->update('bas_products_combo', $details)) {
				$return = array('type' => 'success', 'msg' => 'Successfull updated combo');
				$log->write('Successfull updated combo product with SKU ' . $_POST['sku'] . json_encode($details));
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to updated combo', 'error' => $db->getLastError());
				$log->write('Unable updated product with SKU ' . $_POST['sku'] . ' ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'delete_product_combo':
			$cid = $_POST['cid'];
			$db->where('cid', $cid);
			if ($db->delete(TBL_PRODUCTS_COMBO)) {
				$return = array('type' => 'success', 'msg' => 'Successfull deleted Combo Products');
				$log->write('Successfull deleted Combo Products.');
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to delete Combo Products');
				$log->write('Unable delete Combo Products. :: ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'products_brand':
			$db->where('client_id', $_GET['client_id']);
			$db->orderBy('brandName', 'ASC');
			$brands = $db->get(TBL_PRODUCTS_BRAND, null, array('brandName as "brandName"', 'brandid'));
			$return['data'] = $brands;

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);

			break;

		case 'add_product_brand':
			$details['brandName'] = $_POST['brandName'];
			$details['client_id'] = $_POST['client_id'];

			$db->where('brandName', $_POST['brandName']);
			$db->where('client_id', $_POST['client_id']);
			if (!$db->has(TBL_PRODUCTS_BRAND)) {
				if ($id = $db->insert(TBL_PRODUCTS_BRAND, $details)) {
					$return['type'] = "Success";
					$return = array('type' => 'success', 'msg' => 'Successfull added new brand');
				} else {
					$return = array('type' => 'error', 'msg' => 'Error adding new product :: ' . $db->getLastError());
				}
			} else {
				$return['type'] = "Error";
				$return['message'] = "Duplicate Category Name";
				$return = array('type' => 'error', 'msg' => 'Duplicate Category Name');
			}
			echo json_encode($return);
			break;

		case 'edit_product_brand':
			$brandid = $_POST['brandid'];
			$details['brandName'] = $_POST['brandName'];

			$log->logfile = TODAYS_LOG_PATH . '/products-brand.log';

			$db->where('brandid', $brandid);
			if ($db->update(TBL_PRODUCTS_BRAND, $details)) {
				$return = array('type' => 'success', 'msg' => 'Successfull updated brand');
				$log->write('Successfull updated brand with name ' . $_POST['brandName'] . json_encode($details) . ' Query::' . $db->getLastQuery());
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to updated brand');
				$log->write('Unable updated brand with name ' . $_POST['brandName'] . ' ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'delete_brand':
			$brandid = $_POST['brandid'];
			$db->where('brandid', $brandid);
			if ($db->delete(TBL_PRODUCTS_BRAND)) {
				$return = array('type' => 'success', 'msg' => 'Successfull deleted brand');
				$log->write('Successfull deleted brand.');
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to delete brand');
				$log->write('Unable delete brand. :: ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'products_category':
			$db->where('client_id', $_GET['client_id']);
			$db->orderBy('categoryName', 'ASC');
			$categories = $db->get(TBL_PRODUCTS_CATEGORY, null, array('categoryName', 'catid'));

			$return['data'] = $categories;

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'add_product_category':
			$details['categoryName'] = $_POST['categoryName'];
			$details['client_id'] = $_POST['client_id'];

			$db->where('categoryName', $_POST['categoryName']);
			$db->where('client_id', $_POST['client_id']);
			if (!$db->has(TBL_PRODUCTS_CATEGORY)) {
				if ($id = $db->insert(TBL_PRODUCTS_CATEGORY, $details)) {
					$return['type'] = "Success";
					$return = array('type' => 'success', 'msg' => 'Successfull added new category');
				} else {
					$return = array('type' => 'error', 'msg' => 'Error adding new product :: ' . $db->getLastError());
				}
			} else {
				$return['type'] = "Error";
				$return['message'] = "Duplicate Category Name";
				$return = array('type' => 'error', 'msg' => 'Duplicate Category Name');
			}
			echo json_encode($return);
			break;

		case 'edit_product_category':
			$catid = $_POST['catid'];
			$details['categoryName'] = $_POST['categoryName'];

			$log->logfile = TODAYS_LOG_PATH . '/products-category.log';

			$db->where('catid', $catid);
			if ($db->update(TBL_PRODUCTS_CATEGORY, $details)) {
				$return = array('type' => 'success', 'msg' => 'Successfull updated category');
				$log->write('Successfull updated category with name ' . $_POST['categoryName'] . json_encode($details));
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to updated category');
				$log->write('Unable updated category with name ' . $_POST['categoryName'] . ' ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'delete_category':
			$catid = $_POST['catid'];
			$db->where('catid', $catid);
			if ($db->delete(TBL_PRODUCTS_CATEGORY)) {
				$return = array('type' => 'success', 'msg' => 'Successfull deleted category');
				$log->write('Successfull deleted category.');
			} else {
				$return = array('type' => 'error', 'msg' => 'Unable to updated category');
				$log->write('Unable delete category. :: ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'products_alias':
			// error_reporting(E_ALL);
			// ini_set('display_errors', '1');
			// echo '<pre>';

			$db->join(TBL_PRODUCTS_CATEGORY . " c", "p.catID=c.catid", "LEFT");
			$db->join(TBL_FK_ACCOUNTS . " fk", "p.account_id=fk.account_id", "LEFT");
			$db->joinWhere(TBL_FK_ACCOUNTS . " fk", "p.marketplace", "flipkart");
			$db->join(TBL_AZ_ACCOUNTS . " az", "p.account_id=az.account_id", "LEFT");
			$db->joinWhere(TBL_AZ_ACCOUNTS . " az", "p.marketplace", "amazon");
			$db->join(TBL_PRODUCTS_MASTER . " pm", "pm.pid=p.pid", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_MASTER . " pm", "pm.is_active", 1);
			$db->join(TBL_PRODUCTS_COMBO . " pc", "pc.cid=p.cid", "LEFT");
			$db->joinWhere(TBL_PRODUCTS_COMBO . " pc", "pc.is_active", 1);
			$db->where('COALESCE(pc.sku, pm.sku)', NULL, 'IS NOT');
			$db->orderBy('parentSku', 'ASC');
			$db->orderBy('mp_id', 'ASC');
			$products = $db->get(TBL_PRODUCTS_ALIAS . " p", NULL, 'COALESCE(pc.sku, pm.sku) as parentSku, p.sku, p.mp_id, c.categoryName, p.marketplace, COALESCE(fk.account_name, az.account_name) as account_name, p.auto_update, p.fulfilled, p.price_now, p.alias_id');
			$return = array();
			$i = 0;
			foreach ($products as $product_k => $product) {
				foreach ($product as $p_key => $p_value) {
					if ($p_key == "marketplace") {
						$return['data'][$i][$p_key] = ucfirst($p_value);
					} else {
						$return['data'][$i][$p_key] = $p_value;
					}
				}
				$i++;
			}

			if (empty($return))
				$return['data'] = array();

			header('Content-Type: application/json');
			echo json_encode($return);
			break;

		case 'edit_product_alias':
			if (empty($_POST['alias_id']))
				return;

			$details['alias_id'] = $_POST['alias_id'];
			$product = explode('-', $_POST['parent_sku']);
			$details['pid'] = 0;
			$details['cid'] = 0;
			$details[$product[0]] = $product[1];
			$details['sku'] = $_POST['sku'];
			$details['mp_id'] = $_POST['pm_id'];
			$details['marketplace'] = $_POST['marketplace'];
			$details['account_id'] = $_POST['account_id'];

			$log->logfile = TODAYS_LOG_PATH . '/products-alias.log';

			$db->where('alias_id', $_POST['alias_id']);
			if ($db->update(TBL_PRODUCTS_ALIAS, $details)) {
				$log->write('Successfull updated alias with ' . $_POST['marketplace'] . ' ID ' . $_POST['pm_id'] . ' :: ' . json_encode($details));
				$return = array('type' => 'success', 'msg' => 'Successfull updated alias with ' . $_POST['marketplace'] . ' ID ' . $_POST['pm_id']);
			} else {
				$log->write('Unable updated alias with alias ID ' . $_POST['alias_id'] . ' ' . $db->getLastError());
				$return = array('type' => 'error', 'msg' => 'Unable updated alias with alias ID ' . $_POST['alias_id'] . ' ' . $db->getLastError());
			}
			echo json_encode($return);
			break;

		case 'add_product_alias':

			$product = explode('-', $_POST['parent_sku']);
			$details['pid'] = 0;
			$details['cid'] = 0;
			$details[$product[0]] = $product[1];
			$details['catID'] = get_category_by_id($product[0], $product[1]);
			$details['sku'] = trim($_POST['sku']);
			$details['mp_id'] = trim($_POST['mp_id']);
			$details['marketplace'] = $_POST['marketplace'];
			$details['account_id'] = $_POST['account_id'];

			$log->logfile = TODAYS_LOG_PATH . '/products-alias.log';

			$db->where($product[0], $product[1]);
			$db->where('sku', trim($_POST['sku']));
			$db->where('mp_id', trim($_POST['mp_id']));
			$db->where('marketplace', $_POST['marketplace']);
			$db->where('account_id', $_POST['account_id']);

			if (!$db->has(TBL_PRODUCTS_ALIAS)) {
				if ($db->insert(TBL_PRODUCTS_ALIAS, $details)) {
					$log->write('Successfull added new alias for ' . $_POST['marketplace'] . ' ID ' . $_POST['mp_id'] . ' :: ' . json_encode($details));
					$return['type'] = "success";
					echo json_encode($return);
				} else {
					$log->write('Unable add new alias with alias ID ' . $alias_id . ' ' . $db->getLastError());
					$return['type'] = "error";
					$return['message'] = "Error adding new alias  for " . $_POST['marketplace'] . " ID " . $_POST['mp_id'] . " :: " . $db->getLastError();
					echo json_encode($return);
				}
			} else {
				$return['type'] = "error";
				$return['message'] = "Listing already exists with same data.";
				echo json_encode($return);
			}
			break;

		case 'add_product_alias_bulk':
			$account_id = $_POST['account_id'];
			$marketplace = $_POST['marketplace'];
			$handle = new \Verot\Upload\Upload($_FILES['alias_file']);
			// $account_key = "";
			// foreach ($accounts[$marketplace] as $a_key => $account) {
			// 	if ($account_id == $account->account_id)
			// 		$account_key = $a_key;
			// }
			// $fk = new connector_flipkart($accounts[$marketplace][$account_key]);

			if ($handle->uploaded) {
				$handle->file_overwrite = true;
				$handle->dir_auto_create = true;
				$handle->process(ROOT_PATH . "/uploads/bulk_alias/");
				$handle->clean();
				$file = ROOT_PATH . "/uploads/bulk_alias/" . $handle->file_dst_name;
				try {
					$inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
					$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
					$objPHPExcel = $objReader->load($file);
				} catch (Exception $e) {
					die('Error loading file "' . pathinfo($file, PATHINFO_BASENAME) . '": ' . $e->getMessage());
				}
				$objWorksheet = $objPHPExcel->getSheet(0); // First Sheet
				$objReader->setReadDataOnly(true);

				$highestRow = $objWorksheet->getHighestRow();
				$highestColumn = $objWorksheet->getHighestColumn();

				$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

				$rows = array();
				$error = 0;
				$exists = 0;
				$success = 0;
				$return = array();
				for ($row = 4; $row <= $highestRow; ++$row) {
					$line = array();
					$parent_id = NULL;
					for ($col = 1; $col < $highestColumnIndex + 1; ++$col) {
						$line[] = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue(); //getFormattedValue();
					}

					$line[3] = "";

					if ($line[3] != "Success") {
						// CHECK IF PARENT SKU EXISTS
						$parent_sku = $line[2];
						$db->where('sku', $parent_sku);
						$pid = $db->getOne(TBL_PRODUCTS_MASTER, 'pid');
						if (NULL === $pid) {
							$db->where('sku', $parent_sku);
							$cid = $db->getOne(TBL_PRODUCTS_COMBO, 'cid');
							// var_dump($cid);
							if (NULL === $cid) {
								$error++;
								$line[3] = 'Parent SKU Not Found.';
							} else {
								$parent_id = array('cid', $cid['cid']);
							}
						} else {
							$parent_id = array('pid', $pid['pid']);
						}

						if ($parent_id) {
							// SAME FSN SAME ACCOUNT
							// SAME SKU SAME FSN 
							$db->where('mp_id', $line[0]);
							$db->where('sku', $line[1]);
							$db->where($parent_id[0], $parent_id[1]);
							$db->where('marketplace', $marketplace);
							$db->where('account_id', $account_id);
							if ($db->has(TBL_PRODUCTS_ALIAS)) {
								echo $db->getLastError();
								$exists++;
								$line[3] = 'Listing already exists';
							} else {
								$details = array(
									'mp_id' 		=> $line[0],
									'sku'			=> $line[1],
									'catID' 		=> get_category_by_id($parent_id[0], $parent_id[1]),
									$parent_id[0]	=> $parent_id[1],
									'marketplace'	=> $marketplace,
									'account_id'	=> $account_id
								);
								if ($db->insert(TBL_PRODUCTS_ALIAS, $details)) {
									$success++;
									$line[3] = 'Success';
								} else {
									echo $db->getLastError();
									$error++;
									$line[3] = 'Error! Try again later';
								}
							}
						}
					}
					$return[] = $line;
				}

				$response = array(
					'type'		=> 'success',
					'success' 	=> $success,
					'error'		=> $error,
					'existing'	=> $exists,
				);
				if ($error != 0 || $exists != 0) {
					$response['file'] = create_file(false, $return, $file);
				}
			} else {
				$response = array(
					'type'		=> 'error',
					'error' 	=> 'Unable to upload the file.',
				);
			}

			echo json_encode($response);
			break;

		case 'update_alias':
			$db->where('alias_id', $_POST['alias_id']);
			$keys = array_keys($_POST);
			if ($db->update(TBL_PRODUCTS_ALIAS, array($keys[2] => $_POST[$keys[2]]))) {
				echo 'success';
				return;
			}
			echo 'error';
			break;

		case 'create_sample_file':
			create_file(true);
			break;
	}
} else {
	exit('hmmmm... trying to hack in ahh!');
}

function create_file($output = false, $details = array(), $filename = "")
{
	global $db;

	// Create new PHPExcel object
	$objPHPExcel = new PhpOffice\PhpSpreadsheet\Spreadsheet();
	// Set document properties
	$objPHPExcel->getProperties()->setCreator("Ishan Kukadia")
		->setLastModifiedBy("Ishan Kukadia")
		->setTitle("Product Alias Bulk Upload Sample File")
		->setSubject("Product Alias Bulk Upload Sample File")
		->setDescription("Product Alias Bulk Upload Sample File");
	// Add some data
	$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '**** Instructions: Enter Details from row 3 (Don\'t edit the first 2 rows).  ****')
		->setCellValue('A2', '**** Look for PARENT SKU\'s in Sheet 2  ****')
		->setCellValue('A3', 'FSN/ASIN')
		->setCellValue('B3', 'SKU')
		->setCellValue('C3', 'Parent SKU');

	$objPHPExcel->getActiveSheet()->getCell('C3')
		->getHyperlink()
		->setUrl("sheet://'Parent SKU'!A1")
		->setTooltip('Click here to view Parent SKU\'s');

	if (!empty($details)) {
		$row = 4;
		$objPHPExcel->getActiveSheet()->setCellValue('D3', 'Status');
		foreach ($details as $detail) {
			$objPHPExcel->getActiveSheet()
				->setCellValue('A' . $row, $detail[0])
				->setCellValue('B' . $row, $detail[1])
				->setCellValue('C' . $row, $detail[2])
				->setCellValue('D' . $row, $detail[3]);
			$row++;
		}
	}

	// $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
	// $objPHPExcel->getActiveSheet()->getStyle('A4:C50000')->getProtection()->setLocked(PHPExcel_Style_Protection::PROTECTION_UNPROTECTED);
	// $objPHPExcel->getActiveSheet()->getProtection()->setPassword('fimsfimsfims');

	// Rename worksheet
	$objPHPExcel->getActiveSheet()->setTitle('Sample File');

	// Create a new worksheet, after the default sheet
	$objPHPExcel->createSheet();

	// Add some data to the second sheet, resembling some different data types
	$objPHPExcel->setActiveSheetIndex(1);

	// Rename 2nd sheet
	$objPHPExcel->getActiveSheet()->setTitle('Parent SKU');

	$objPHPExcel->getActiveSheet()->setCellValue('A1', 'Parent-SKU');
	$objPHPExcel->getActiveSheet()->getCell('A1')
		->getHyperlink()
		->setUrl("sheet://'Sample File'!C3")
		->setTooltip('Click here to go back');

	// GET SKU LIST
	$db->orderBy('sku', 'ASC');
	$products = $db->get(TBL_PRODUCTS_MASTER);
	$parent_skus = array();
	foreach ($products as $product) {
		$parent_skus[] = $product['sku'];
	}

	$products = $db->get(TBL_PRODUCTS_COMBO);
	$options_combo = array();
	foreach ($products as $product) {
		$parent_skus[] = $product['sku'];
	}

	asort($parent_skus);

	$i = 2;
	foreach ($parent_skus as $sku) {
		$objPHPExcel->getActiveSheet()->setCellValue('A' . $i, $sku);
		$i++;
	}

	$objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
	$objPHPExcel->getActiveSheet()->getProtection()->setPassword('fimsfimsfims');

	// Set active sheet index to the first sheet, so Excel opens this as the first sheet
	$objPHPExcel->setActiveSheetIndex(0);

	if ($output) {
		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="bulk_alias_upload.xlsx"');
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
		exit;
	} else {
		// output
		$objWriter = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
		$objWriter->save($filename);
		$filename = str_replace(ROOT_PATH, BASE_URL, $filename);
		return $filename;
	}
}
