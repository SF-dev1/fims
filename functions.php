<?php
// Global Functions
include_once(ROOT_PATH . '/includes/class-debug.php');
include_once(ROOT_PATH . '/includes/class-stockist.php');
include_once(ROOT_PATH . '/includes/class-accountant.php');
include_once(ROOT_PATH . '/includes/class-notification.php');

$GLOBALS['js_version'] = '1701667995';
$GLOBALS['debug'] = new debug();
$GLOBALS['stockist'] = Stockist::getInstance();
$GLOBALS['accountant'] = accountant::getInstance();
$GLOBALS['notification'] = Notification::getInstance();

$GLOBALS['accounts'] = get_all_accounts();
$GLOBALS['brands'] = get_all_brands();

$GLOBALS['breadcrumps'] = array();
$GLOBALS['page_title'] = "";
$GLOBALS['header'] = "";
$GLOBALS['footer'] = "";
$GLOBALS['only_body'] = false;
$GLOBALS['client_id'] = @$current_user['party_id'];
if (isset($current_user['party']) && $current_user['party']['party_distributor'])
	$client_id = $current_user['party']['party_id'];
$GLOBALS['menu'] = get_menu();
$GLOBALS['access_levels'] = array();

function ccd($data)
{
	echo "<pre>";
	print_r($data);
	die();
}

function menu_items()
{
	global $client_id, $js_version;

	$menu_items = array(
		"home" => array(
			"title" => "Dashboard",
			"icon" => "icon-home",
			"href" => BASE_URL . '/index.php',
			"plugins" => array(),
			"scripts" => array(),
			"css" => array(),
			"function_int" => array(),
		),
		"tasks" => array(
			"title" => "Tasks",
			"icon" => "icon-home",
			"href" => "javascript:;",
			"sub" => array(
				"my_tasks" => array(
					"title" => "My Tasks",
					"href" => BASE_URL . "/tasks/tasks.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"datatables/extensions/fixedHeader/css/fixedHeader.dataTables.min.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
						),
						"scripts" => array(
							"tasks.js?v=" . $js_version
						),
						"init" => array(
							"Tasks.init('my_tasks');",
						),
					),
				),
				"approvals" => array(
					"title" => "Approvals",
					"href" => BASE_URL . "/tasks/approvals.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-fileinput/bootstrap-fileinput.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"bootstrap-touchspin/bootstrap.touchspin.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"tasks.js?v=" . $js_version
						),
						"init" => array(
							"Tasks.init('approvals');",
						),
					),
				),

			),
		),
		"products" => array(
			"title" => "Products",
			"icon" => "fa fa-barcode",
			"href" => "javascript:;",
			"sub" => array(
				"product" => array(
					"title" => "All Products",
					"href" => BASE_URL . "/products/product.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"datatables/extensions/fixedHeader/css/fixedHeader.dataTables.min.css",
							"bootstrap-fileinput/bootstrap-fileinput.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
						),
						"scripts" => array(
							"products.js?v=" . $js_version => array(
								'var image_url = "' . IMAGE_URL . '";',
								'var client_id = ' . $client_id . ';',
								'var categories = ' . json_encode(get_all_categories()) . ';',
								'var brands = ' . json_encode(get_all_brands()) . ';',
							)
						),
						"init" => array(
							"Products.init('product');",
						),
					),
				),
				"product_combo" => array(
					"title" => "Product Combo",
					"href" => BASE_URL . "/products/product_combo.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-fileinput/bootstrap-fileinput.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"bootstrap-touchspin/bootstrap.touchspin.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"products.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
								'var brands = ' . json_encode(get_all_brands($client_id)) . ';',
								'var all_skus = ' . json_encode(get_all_parent_sku()) . ';',
							)
						),
						"init" => array(
							"Products.init('product_combo');",
						),
					),
				),
				"product_brand" => array(
					"title" => "Product Brand",
					"href" => BASE_URL . "/products/product_brand.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"products.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							)
						),
						"init" => array(
							"Products.init('product_brand');",
						),
					),
				),
				"product_category" => array(
					"title" => "Product Category",
					"href" => BASE_URL . "/products/product_category.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"products.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							)
						),
						"init" => array(
							"Products.init('product_category');",
						),
					),
				),
				"product_alias" => array(
					"title" => "Product Alias",
					"href" => BASE_URL . "/products/product_alias.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-fileinput/bootstrap-fileinput.css",
							"bootstrap-switch/css/bootstrap-switch.min.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
							"datatables/extensions/rowGroup/dataTables.rowGroup.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"bootstrap-touchspin/bootstrap.touchspin.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version
						),
						"scripts" => array(
							"products.js?v=" . $js_version => array(
								'var all_skus = ' . json_encode(get_all_sku()) . ';',
								'var options = ' . json_encode(get_all_parent_sku("pid-")) . ';',
								'var categories = ' . json_encode(get_all_categories()) . ';',
								'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
								'var brands = ' . json_encode(get_all_brands()) . ';',
								'var fk_url = "' . FK_PRODUCT_URL . '";',
								'var az_url = "' . AZ_PRODUCT_URL . '";',
							)
						),
						"init" => array(
							"Products.init('product_alias');",
						),
					),
				),
			),
		),
		"inventory" => array(
			"title" => "Inventory",
			"icon" => "fa fa-warehouse",
			"href" => "javascript:;",
			"sub" => array(
				"inv_analytics" => array(
					"title" => "Analytics",
					"href" => BASE_URL . "/inventory/analytics.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-daterangepicker/daterangepicker-bs3.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-datepicker-validation/jquery.ui.datepicker.validation.min.js?v=" . $js_version
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version,
						),
						"init" => array(
							'Inventory.init("analytics");'
						),
					),
				),
				"content" => array(
					"title" => "Content",
					"href" => BASE_URL . "/inventory/content.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"jquery-multi-select/css/multi-select.css",
							"bootstrap-editable/bootstrap-editable/css/bootstrap-editable.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"bootstrap-editable/bootstrap-editable/js/bootstrap-editable.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version
						),
						"init" => array(
							'Inventory.init("content");'
						),
					),
				),
				"manage" => array(
					"title" => "Manage Inventory",
					"href" => BASE_URL . "/inventory/manage.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"jquery-multi-select/css/multi-select.css",
							"bootstrap-editable/bootstrap-editable/css/bootstrap-editable.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"bootstrap-editable/bootstrap-editable/js/bootstrap-editable.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"qz/qz-tray.js?v=" . $js_version,
							"qz/rsvp-3.1.0.min.js?v=" . $js_version,
							"qz/sha-256.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"qz-settings.js?v=" . $js_version,
							"inventory.js?v=" . $js_version => array(
								'var image_url = "' . IMAGE_URL . '";',
								'var printer_settings = ' . json_encode(get_printer_settings()) . ';',
							),
						),
						"init" => array(
							'Inventory.init("manage_inv");'
						),
					),
				),
				"stock" => array(
					"title" => "Stock",
					"href" => BASE_URL . "/inventory/stock.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version,
						),
						"init" => array(
							'Inventory.init("stock");'
						),
					),
				),
				"history" => array(
					"title" => "History",
					"href" => BASE_URL . "/inventory/history.php",
					"header" => array(
						"css" => array(
							"timeline/timeline.css",
						),
					),
					"footer" => array(
						"plugins" => array(),
						"scripts" => array(
							"inventory.js?v=" . $js_version,
						),
						"init" => array(
							'Inventory.init("history");'
						),
					),
				),
				"audit" => array(
					"title" => "Audit",
					"href" => BASE_URL . "/inventory/audit.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version,
						),
						"init" => array(
							'Inventory.init("audit");'
						),
					),
				),
				"reconciliation" => array(
					"title" => "Reconciliation",
					"href" => BASE_URL . "/inventory/reconciliation.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version,
						),
						"init" => array(
							'Inventory.init("reconciliation");'
						),
					),
				),
				"inbound" => array(
					"title" => "Inbound",
					"href" => BASE_URL . "/inventory/inbound.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"jquery-multi-select/css/multi-select.css",
							"bootstrap-editable/bootstrap-editable/css/bootstrap-editable.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"bootstrap-editable/bootstrap-editable/js/bootstrap-editable.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
							"qz/rsvp-3.1.0.min.js?v=" . $js_version,
							"qz/sha-256.min.js?v=" . $js_version,
							"qz/qz-tray.js?v=" . $js_version,
						),
						"scripts" => array(
							"qz-settings.js?v=" . $js_version,
							"inventory.js?v=" . $js_version => array(
								'var image_url = "' . IMAGE_URL . '";',
								'var grn = ' . json_encode(get_grn(array("created", "receiving"))) . ';',
								'var grn_items = ' . json_encode(get_grn_items(get_grn(array("created", "receiving")))) . ';',
								'var printer_settings = ' . json_encode(get_printer_settings()) . ';',
							),
						),
						"init" => array(
							'Inventory.init("inbound");'
						),
					),
				),
				"quality_check" => array(
					"title" => "Quality Check",
					"href" => BASE_URL . "/inventory/quality_check.php",
					"header" => array(
						"css" => array(
							"jquery-multi-select/css/multi-select.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"hotkeys/hotkeys.js?v=" . $js_version
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version => array(
								'var image_url = "' . IMAGE_URL . '";',
								'var issues = ' . json_encode(get_qc_issues()) . ';',
							),
						),
						"init" => array(
							'Inventory.init("quality_check");'
						),
					),
				),
				"repairs" => array(
					"title" => "Repairs",
					"href" => BASE_URL . "/inventory/repairs.php",
					"header" => array(
						"css" => array(
							"jquery-multi-select/css/multi-select.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"hotkeys/hotkeys.js?v=" . $js_version
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version => array(
								'var image_url = "' . IMAGE_URL . '";',
								'var issues = ' . json_encode(get_qc_issues()) . ';',
								'var issue_group = ' . json_encode(get_qc_issues("parent")) . ';',
								'var inventory_components = ' . json_encode(get_inventory_components()) . ';',
							),
						),
						"init" => array(
							'Inventory.init("repairs");'
						),
					),
				),
				"transfer" => array(
					"title" => "Transfer",
					"href" => BASE_URL . "/inventory/transfer.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version => array(
								'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
							),
						),
						"init" => array(
							'Inventory.init("transfer");'
						),
					),
				),
				"inv_manage" => array(
					"title" => "Manage",
					"href" => BASE_URL . "/inventory/inv_manage.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
							"qz/qz-tray.js?v=" . $js_version,
							"qz/rsvp-3.1.0.min.js?v=" . $js_version,
							"qz/sha-256.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"qz-settings.js?v=" . $js_version,
							"inventory.js?v=" . $js_version => array(
								'var printer_settings = ' . json_encode(get_printer_settings()) . ';',
								'var all_parent_sku = ' . json_encode(get_all_parent_sku()) . ';',
							),
						),
						"init" => array(
							'Inventory.init("manage");',
						),
					),
				),
				"qc_issues" => array(
					"title" => "QC Issues",
					"href" => BASE_URL . "/inventory/qc_issues.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"jquery-nestable2/nestable.min.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-nestable2/jquery.nestable.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"inventory.js?v=" . $js_version => array(
								// 'var issue = '.json_encode(get_qc_issues()).';',
								'var issue_group = ' . json_encode(get_qc_issues("parent")) . ';',
							),
						),
						"init" => array(
							'Inventory.init("qc_issues");',
						),
					),
				),
			),
		),
		"parties" => array(
			"title" => "Parties",
			"icon" => "fa fa-users",
			"href" => "javascript:;",
			"sub" => array(
				"customers" => array(
					"title" => "Customers",
					"href" => BASE_URL . "/parties/customers.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"parties.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							),
						),
						"init" => array(
							'Parties.init("customers");'
						),
					),
				),
				"suppliers" => array(
					"title" => "Suppliers",
					"href" => BASE_URL . "/parties/suppliers.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"parties.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							),
						),
						"init" => array(
							'Parties.init("suppliers");'
						),
					),
				),
				"vendors" => array(
					"title" => "AMS Vendors",
					"href" => BASE_URL . "/parties/vendors.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"parties.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							),
						),
						"init" => array(
							'Parties.init("vendors");'
						),
					),
				),
				"client_ledger" => array(
					"title" => "Parties Ledger",
					"href" => BASE_URL . "/parties/ledger.php",
				),
			),
		),
		"purchase" => array(
			"title" => "Purchase",
			"icon" => "fa fa-store",
			"href" => "javascript:;",
			"sub" => array(
				"purchase_orders" => array(
					"title" => "Purchase Orders",
					"href" => "javascript:;",
					"sub-menu" => array(
						"purchase_new" => array(
							"title" => "Create PO",
							"icon" => "fa fa-plus",
							"href" => BASE_URL . "/purchase/purchase_new.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"purchase.js?v=" . $js_version => array(
										'var suppliers = ' . json_encode(get_all_suppliers()) . ';',
										'var all_parent_sku = ' . json_encode(get_all_parent_sku()) . ';',
									),
								),
								"init" => array(
									'Purchase.init("new");'
								),
							),
						),
						"purchase_view" => array(
							"title" => "View PO",
							"icon" => "fa fa-eye",
							"href" => BASE_URL . "/purchase/purchase_view.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"../css/pages/invoice.css",
									"bootstrap-modal/css/bootstrap-modal-bs3patch.css",
									"bootstrap-modal/css/bootstrap-modal.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modalmanager.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modal.js?v=" . $js_version,
								),
								"scripts" => array(
									"purchase.js?v=" . $js_version => array(
										'var suppliers = ' . json_encode(get_all_suppliers()) . ';',
										'var all_parent_sku = ' . json_encode(get_all_parent_sku()) . ';',
									),
								),
								"init" => array(
									'Purchase.init("view");'
								),
							),
						),
						"purchase_view_item" => array(
							"title" => "View PO Item",
							"icon" => "fa fa-eye",
							"href" => BASE_URL . "/purchase/purchase_view_item.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"../css/pages/invoice.css",
									"bootstrap-modal/css/bootstrap-modal-bs3patch.css",
									"bootstrap-modal/css/bootstrap-modal.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modalmanager.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modal.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
								),
								"scripts" => array(
									"purchase.js?v=" . $js_version => array(
										'var suppliers = ' . json_encode(get_all_suppliers()) . ';',
										'var all_parent_sku = ' . json_encode(get_all_parent_sku()) . ';',
									),
								),
								"init" => array(
									'Purchase.init("view_item");'
								),
							),
						),
					),
				),
				"packing_list" => array(
					"title" => "Packing List",
					"href" => "javascript:;",
					"sub-menu" => array(
						"packing_list_new" => array(
							"title" => "Create Packing List",
							"icon" => "fa fa-plus",
							"href" => BASE_URL . "/purchase/packing_list_new.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-modal/css/bootstrap-modal-bs3patch.css",
									"bootstrap-modal/css/bootstrap-modal.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modalmanager.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modal.js?v=" . $js_version,
								),
								"scripts" => array(
									"purchase.js?v=" . $js_version => array(
										// 'var lot_no = '.json_encode(get_all_lot_no()).';',
										// 'var suppliers = '.json_encode(get_all_suppliers()).';',
										'var all_parent_sku = ' . json_encode(get_all_parent_sku()) . ';',
									),
								),
								"init" => array(
									'Purchase.init("pack_new");'
								),
							),
						),
						"packing_list_view" => array(
							"title" => "View Packing List",
							"icon" => "fa fa-eye",
							"href" => BASE_URL . "/purchase/packing_list_view.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-modal/css/bootstrap-modal-bs3patch.css",
									"bootstrap-modal/css/bootstrap-modal.css",
									"bootstrap-datepicker/css/datepicker.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modalmanager.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modal.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version
								),
								"scripts" => array(
									"purchase.js?v=" . $js_version => array(
										// 'var suppliers = '.json_encode(get_all_suppliers()).';',
										// 'var carriers = '.json_encode(get_all_carriers()).';',
									),
								),
								"init" => array(
									'Purchase.init("pack_view");'
								),
							),
						),
					),
				),
				"purchase_grn" => array(
					"title" => "Purchase GRN",
					"href" => "javascript:;",
					"sub-menu" => array(
						"grn_new" => array(
							"title" => "Create GRN",
							"icon" => "fa fa-plus",
							"href" => BASE_URL . "/purchase/grn_new.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"purchase.js?v=" . $js_version => array(
										'var all_parent_sku = ' . json_encode(get_all_parent_sku()) . ';',
									),
								),
								"init" => array(
									'Purchase.init("grn_new");'
								),
							),
						),
						"grn_view" => array(
							"title" => "View GRN",
							"icon" => "fa fa-eye",
							"href" => BASE_URL . "/purchase/grn_view.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									// "../css/pages/invoice.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
								),
								"scripts" => array(
									"purchase.js?v=" . $js_version => array(
										// 'var suppliers = '.json_encode(get_all_suppliers()).';',
										// 'var all_parent_sku = '.json_encode(get_all_parent_sku()).';',
									),
								),
								"init" => array(
									'Purchase.init("grn_view");'
								),
							),
						),
					),
				),
				"purchase_return" => array(
					"title" => "Purchase Return",
					"href" => BASE_URL . "/purchase/purchase_return.php",
				),
				"purchase_payment" => array(
					"title" => "Purchase Payment",
					"href" => BASE_URL . "/purchase/purchase_payment.php",
				),
				"purchase_ledger" => array(
					"title" => "Purchase Ledger",
					"href" => BASE_URL . "/purchase/purchase_ledger.php",
				),
				"print" => array(
					"type" => "hidden",
					"title" => "Print Preview",
					"href" => BASE_URL . "/print.php",
					"only_body" => true,
					"header" => array(
						"css" => array(
							// "../css/pages/invoice.css",
						),
					),
					"footer" => array(
						"plugins" => array(),
						"scripts" => array(),
						"init" => array(),
					),
				),
			),
		),
		"distributor" => array(
			"title" => "Distributor",
			"icon" => "fa fa-project-diagram",
			"href" => "javascript:;",
			"sub" => array(
				"distributor_management" => array(
					"title" => "Dist. Management",
					"href" => "javascript:;",
					"sub-menu" => array(
						"distributor_view" => array(
							"title" => "All Distributor",
							"href" => BASE_URL . "/distributor/distributor_view.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modalmanager.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
								),
								"scripts" => array(
									"distributor.js?v=" . $js_version
								),
								"init" => array(
									'Distributor.init("distributor_approval");'
								),
							),
						),
						"seller_approval" => array(
							"title" => "Approve Seller",
							"href" => BASE_URL . "/distributor/seller_approval.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modalmanager.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
								),
								"scripts" => array(
									"distributor.js?v=" . $js_version
								),
								"init" => array(
									'Distributor.init("seller_approval");'
								),
							),
						),
						"approved_sellers" => array(
							"title" => "Approved Seller",
							"href" => BASE_URL . "/distributor/approved_sellers.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
								),
								"scripts" => array(
									"distributor.js?v=" . $js_version
								),
								"init" => array(
									'Distributor.init("approved_sellers");'
								),
							),
						),
					),
				),
				"sellers" => array(
					"title" => "Sellers",
					"href" => "javascript:;",
					"sub-menu" => array(
						"seller_new" => array(
							"title" => "Add Sellers",
							"href" => BASE_URL . "/distributor/seller_new.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"jquery-tags-input/jquery.tagsinput.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"jquery-tags-input/jquery.tagsinput.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-validation/js/additional-methods.min.js?v=" . $js_version,
									"bootstrap-wizard/jquery.bootstrap.wizard.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"sellers.js?v=" . $js_version,
								),
								"init" => array(
									'Sellers.init("new");'
								),
							),
						),
						"seller_view" => array(
							"title" => "View Sellers",
							"href" => BASE_URL . "/distributor/seller_view.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"sellers.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
									),
								),
								"init" => array(
									'Sellers.init("view");'
								),
							),
						),
						"seller_listings" => array(
							"title" => "Sellers Listings",
							"href" => BASE_URL . "/distributor/seller_listings.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"sellers.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
										'var all_skus = ' . json_encode(get_all_parent_sku()) . ';',
									),
								),
								"init" => array(
									'Sellers.init("listing");'
								),
							),
						),
					),
				),
			),
		),
		"sales" => array(
			"title" => "Sales",
			"icon" => "fa fa-cash-register",
			"href" => "javascript:;",
			"sub" => array(
				"sales_order" => array(
					"title" => "Sales Orders",
					"href" => "javascript:;",
					"sub-menu" => array(
						"sales_new" => array(
							"title" => "New Sales",
							"href" => BASE_URL . "/sales/sales_new.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version
								),
								"scripts" => array(
									"sales.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
										'var customers = ' . json_encode(get_all_customers()) . ';',
										'var all_parent_sku = ' . json_encode(get_all_parent_sku()) . ';',
									),
								),
								"init" => array(
									'Sales.init("new");'
								),
							),
						),
						"sales_view" => array(
							"title" => "View SO",
							"href" => BASE_URL . "/sales/sales_view.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"../css/pages/invoice.css",
									"bootstrap-modal/css/bootstrap-modal-bs3patch.css",
									"bootstrap-modal/css/bootstrap-modal.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modalmanager.js?v=" . $js_version,
									"bootstrap-modal/js/bootstrap-modal.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version
								),
								"scripts" => array(
									"sales.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
										'var customers = ' . json_encode(get_all_customers()) . ';',
									),
								),
								"init" => array(
									'Sales.init("view");'
								),
							),
						),
					),
				),
				"sales_return" => array(
					"title" => "Sales Return",
					"href" => BASE_URL . "/sales/sales_return.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"jquery-multi-select/css/multi-select.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-multi-select/js/jquery.multi-select.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"sales.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							),
						),
						"init" => array(
							'Sales.init("return");'
						),
					)
				),
				// "sales_disposal" => array(
				// 	"title" => "Sales Disposal",
				// 	"href" => BASE_URL."/sales_disposal.php" // MANILY TO REMOVE DAMAGED INVENTORY
				// ),
				"sales_payment" => array(
					"title" => "Sales Payment",
					"href" => BASE_URL . "/sales/sales_payment.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"bootstrap-datepicker/css/datepicker.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version
						),
						"scripts" => array(
							"sales.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
								'var customers = ' . json_encode(get_all_customers()) . ';',
							),
						),
						"init" => array(
							'Sales.init("payments");'
						),
					),
				),
				"sales_ledger" => array(
					"title" => "Sales Ledger",
					"href" => BASE_URL . "/sales/sales_ledger.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"bootstrap-daterangepicker/daterangepicker-bs3.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
						),
						"scripts" => array(
							"sales.js?v=" . $js_version => array(
								'var customers = ' . json_encode(get_all_customers()) . ';',
							),
						),
						"init" => array(
							'Sales.init("ledger");'
						),
					),
				),
				"print" => array(
					"type" => "hidden",
					"title" => "Print Preview",
					"href" => BASE_URL . "/print.php",
					"only_body" => true,
					"header" => array(
						"css" => array(
							"../css/pages/invoice.css",
						),
					),
					"footer" => array(
						"plugins" => array(),
						"scripts" => array(),
						"init" => array(),
					),
				),
			),
		),
		"vendors" => array(
			"title" => "Vendors",
			"icon" => "fa fa-hands-helping",
			"href" => "javascript:;",
			"sub" => array(
				"vendor_dashboard" => array(
					"title" => "Dashboard",
					"href" => BASE_URL . "/vendors/dashboard.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"jquery-multi-select/css/multi-select.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-multi-select/js/jquery.multi-select.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"vendors.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							),
						),
						"init" => array(
							'Vendors.init("inventory");'
						),
					)
				),
				"vendor_inventory" => array(
					"title" => "Inventory",
					"href" => BASE_URL . "/vendors/inventory.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"jquery-multi-select/css/multi-select.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-multi-select/js/jquery.multi-select.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"vendors.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							),
						),
						"init" => array(
							'Vendors.init("inventory");'
						),
					)
				),
				"vendor_purchase" => array(
					"title" => "Purchase Order",
					"href" => BASE_URL . "/vendors/purchase_order.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"jquery-multi-select/css/multi-select.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-multi-select/js/jquery.multi-select.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"vendors.js?v=" . $js_version => array(
								'var client_id = ' . $client_id . ';',
							),
						),
						"init" => array(
							'Vendors.init("purchase");'
						),
					)
				),
				"vendor_ledger" => array(
					"title" => "Vendor Ledger",
					"href" => BASE_URL . "/vendors/vendor_ledger.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"bootstrap-daterangepicker/daterangepicker-bs3.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
						),
						"scripts" => array(
							"vendor.js?v=" . $js_version => array(
								'var customers = ' . json_encode(get_all_customers()) . ';',
							),
						),
						"init" => array(
							'Vendor.init("ledger");'
						),
					),
				),
				"print" => array(
					"type" => "hidden",
					"title" => "Print Preview",
					"href" => BASE_URL . "/print.php",
					"only_body" => true,
					"header" => array(
						"css" => array(
							"../css/pages/invoice.css",
						),
					),
					"footer" => array(
						"plugins" => array(),
						"scripts" => array(),
						"init" => array(),
					),
				),
			),
		),
		"ams" => array(
			"title" => "AMS",
			"icon" => "fa fa-store-alt",
			"href" => "javascript:;",
			"sub" => array(
				"ams_invoices" => array(
					"title" => "Invoices",
					"href" => BASE_URL . "/ams/invoices.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-datepicker/css/datepicker.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
						),
						"scripts" => array(
							"ams.js?v=" . $js_version => array(),
						),
						"init" => array(
							'AMS.init("invoices");'
						),
					),
				),
				"ams_payment" => array(
					"title" => "Payments",
					"href" => BASE_URL . "/ams/payment.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"bootstrap-datepicker/css/datepicker.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version
						),
						"scripts" => array(
							"ams.js?v=" . $js_version => array(
								'var vendors = ' . json_encode(get_all_vendors()) . ';'
							),
						),
						"init" => array(
							'AMS.init("payments");'
						),
					),
				),
				"ams_ledger" => array(
					"title" => "Ledger",
					"href" => BASE_URL . "/ams/ledger.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"bootstrap-daterangepicker/daterangepicker-bs3.css"
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
						),
						"scripts" => array(
							"ams.js?v=" . $js_version => array(
								'var vendors = ' . json_encode(get_all_vendors()) . ';'
							),
						),
						"init" => array(
							'AMS.init("ledger");'
						),
					),
				),
				"ams_rate" => array(
					"title" => "Rate Slab",
					"href" => BASE_URL . "/ams/rate_slab.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"datatables/extensions/fixedHeader/css/fixedHeader.dataTables.min.css",
							"bootstrap-datepicker/css/datepicker.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
							"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"ams.js?v=" . $js_version => array(
								'var vendors = ' . json_encode(get_all_vendors(false, array('party_ams_rate_slab'))) . ';',
							),
						),
						"init" => array(
							'AMS.init("rate_slab");'
						),
					),
				),
			),
		),
		"online" => array(
			"title" => "Online",
			"icon" => "fa fa-info",
			"href" => "javascript:;",
			"sub" => array(
				"flipkart" => array(
					"title" => "Flipkart",
					"icon" => "fab fa-facebook-f",
					"href" => "javascript:;",
					"sub-menu" => array(
						"fk_overview" => array(
							"title" => "Overview",
							"icon" => "fa fa-eye",
							"href" => BASE_URL . "/flipkart/overview.php",
							"header" => array(
								"css" => array(
									"jqvmap/jqvmap/jqvmap.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"fullcalendar/fullcalendar/fullcalendar.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"flot/jquery.flot.js?v=" . $js_version,
									"flot/jquery.flot.tooltip.min.js?v=" . $js_version,
									"flot/jquery.flot.resize.js?v=" . $js_version,
									"flot/jquery.flot.time.js?v=" . $js_version,
									"jqvmap/jqvmap/jquery.vmap.js?v=" . $js_version,
									"jqvmap/jqvmap/maps/jquery.vmap.india.js?v=" . $js_version,
									"jquery.blockui.min.js?v=" . $js_version,
									"uniform/jquery.uniform.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version
								),
								"scripts" => array(
									"overview.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
										'var brands = ' . json_encode(get_all_brands()) . ';'
									)
								),
								"init" => array(
									"Overview.init('flipkart')",
								),
							),
						),
						"fk_orders" => array(
							"title" => "Orders",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/flipkart/orders.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"bootstrap-datepicker/css/datepicker.css"
									// "bootstrap-switch/css/bootstrap-switch.min.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"bootstrap-editable/bootstrap-editable/js/bootstrap-editable.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"flipkart-orders.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
									)
								),
								"init" => array(),
							),
						),
						"fk_returns" => array(
							"title" => "Returns",
							"icon" => "fa fa-reply-all",
							"href" => BASE_URL . "/flipkart/returns.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"bootstrap-datepicker/css/datepicker.css",
									"dropzone/css/dropzone.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"bootstrap-editable/bootstrap-editable/js/bootstrap-editable.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"dropzone/dropzone.js?v=" . $js_version
								),
								"scripts" => array(
									"flipkart-orders.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
									)
								),
								"init" => array(),
							),
						),
						"fk_scan_ship" => array(
							"title" => "Scan & Ship",
							"icon" => "fa fa-barcode",
							"href" => BASE_URL . "/flipkart/fk_scan_ship.php",
							"header" => array(
								"css" => array(),
							),
							"footer" => array(
								"plugins" => array(),
								"scripts" => array(
									"flipkart-orders.js?v=" . $js_version,
								),
								"init" => array(
									"Flipkart.init('fk_scan_ship');",
								),
							),
						),
						"fk_payments" => array(
							"title" => "Payments",
							"icon" => "fa fa-rupee-sign",
							"href" => BASE_URL . "/flipkart/payments.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"datatables/extensions/fixedHeader/css/fixedHeader.dataTables.min.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"jquery-tags-input/jquery.tagsinput.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-tags-input/jquery.tagsinput.min.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
								),
								"scripts" => array(
									"flipkart-orders.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
										'var payments_issues = ' . get_all_payments_issues("flipkart") . ';',
									)
								),
								"init" => array(
									"Flipkart.init('payments');",
								),
							),
						),
						"fk_incidents" => array(
							"title" => "Incidents",
							"icon" => "fa fa-tty",
							"href" => BASE_URL . "/flipkart/fk_incidents.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"datatables/extensions/fixedHeader/css/fixedHeader.dataTables.min.css",
									"jquery-tags-input/jquery.tagsinput.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-tags-input/jquery.tagsinput.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"flipkart-incidents.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
									)
								),
								"init" => array(),
							),
						),
						"fk_dashboard" => array(
							"title" => "FK Dashboard",
							"icon" => "fa fa-desktop",
							"href" => BASE_URL . "/flipkart/fk_dashboard.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"datatables/extensions/fixedHeader/css/fixedHeader.dataTables.min.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"bootstrap-datetimepicker/css/datetimepicker.css",
									// FULLCALENDER
									'fullcalendar/packages/core/main.min.css',
									'fullcalendar/packages/bootstrap/main.min.css',
									'fullcalendar/packages/timeline/main.min.css',
									'fullcalendar/packages/daygrid/main.min.css',
									'fullcalendar/packages/timegrid/main.min.css',
									'fullcalendar/packages/resource-timeline/main.min.css',
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-datepicker-validation/jquery.ui.datepicker.validation.min.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"bootstrap-datetimepicker/js/bootstrap-datetimepicker.js?v=" . $js_version,
									// FULLCALENDER
									"fullcalendar/packages/core/main.min.js?v=" . $js_version,
									"fullcalendar/packages/bootstrap/main.min.js?v=" . $js_version,
									"fullcalendar/packages/timeline/main.min.js?v=" . $js_version,
									"fullcalendar/packages/timegrid/main.min.js?v=" . $js_version,
									"fullcalendar/packages/resource-common/main.min.js?v=" . $js_version,
									"fullcalendar/packages/resource-timeline/main.min.js?v=" . $js_version,
									"fullcalendar/packages/umd/popper.min.js?v=" . $js_version,
									"fullcalendar/packages/umd/tooltip.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"flipkart-orders.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
									)
								),
								"init" => array(),
							),
						),
						"fk_wrong_skus" => array(
							"title" => "Wrong SKU's",
							"icon" => "fa fa-ban",
							"href" => BASE_URL . "/flipkart/fk_wrong_skus.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"select2/select2-cascade.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"flipkart-orders.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
										// 'var all_alias = '.json_encode(get_all_mp_id()).';',
									)
								),
								"init" => array(
									// "wrongSKU.init();",
								),
							),
						),
					),
				),
				"amazon" => array(
					"title" => "Amazon",
					"icon" => "fab fa-amazon",
					"href" => "javascript:;",
					"sub-menu" => array(
						"az_orders" => array(
							"title" => "Orders",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/amazon/orders.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
								),
								"scripts" => array(
									"amazon.js?v=" . $js_version
								),
								"init" => array(
									"Amazon.init('orders');",
								),
							),
						),
						"az_flex_orders" => array(
							"title" => "Flex Orders",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/amazon/flex_orders.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
								),
								"scripts" => array(
									"amazon.js?v=" . $js_version
								),
								"init" => array(
									"Amazon.init('flex_orders');",
								),
							),
						),
						"az_returns" => array(
							"title" => "Returns",
							"icon" => "fa fa-reply-all",
							"href" => BASE_URL . "/amazon/returns.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version
								),
								"scripts" => array(
									"amazon.js?v=" . $js_version
								),
								"init" => array(
									"Amazon.init('returns');",
								),
							),
						),
						"az_scan_ship" => array(
							"title" => "Scan & Ship",
							"icon" => "fa fa-barcode",
							"href" => BASE_URL . "/amazon/az_scan_ship.php",
							"header" => array(
								"css" => array(),
							),
							"footer" => array(
								"plugins" => array(),
								"scripts" => array(
									"amazon.js?v=" . $js_version,
								),
								"init" => array(
									"Amazon.init('scan_ship');",
								),
							),
						),
						"az_payments" => array(
							"title" => "Payments",
							"icon" => "fa fa-rupee-sign",
							"href" => BASE_URL . "/amazon/payments.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"datatables/extensions/fixedHeader/css/fixedHeader.dataTables.min.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"jquery-tags-input/jquery.tagsinput.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-tags-input/jquery.tagsinput.min.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
								),
								"scripts" => array(
									"amazon.js?v=" . $js_version => array(
										// 'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
										'var payments_issues = ' . get_all_payments_issues("amazon") . ';',
									)
								),
								"init" => array(
									"Amazon.init('payments');",
								),
							),
						),
						"az_manuals" => array(
							"type" => "hidden",
							"title" => "Manual Process",
							"icon" => "fa fa-wrench",
							"href" => BASE_URL . "/amazon/az_manual_generator.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"bootstrap-datepicker/css/datepicker.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-datepicker-validation/jquery.ui.datepicker.validation.min.js?v=" . $js_version
								),
								"scripts" => array(
									"amazon-orders.js?v=" . $js_version,
								),
								"init" => array(
									"AmazonOrders.init();",
								),
							),
						),
					),
				),
				"meesho" => array(
					"title" => "Meesho",
					"icon" => "fab fa-maxcdn",
					"href" => "javascript:;",
					"sub-menu" => array(
						"ms_orders" => array(
							"title" => "Orders",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/meesho/orders.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-tags-input/jquery.tagsinput.min.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
								),
								"scripts" => array(
									"meesho.js?v=" . $js_version
								),
								"init" => array(
									"Meesho.init('orders');",
								),
							),
						),
						"ms_returns" => array(
							"title" => "Returns",
							"icon" => "fa fa-reply-all",
							"href" => BASE_URL . "/meesho/returns.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version
								),
								"scripts" => array(
									"meesho.js?v=" . $js_version
								),
								"init" => array(
									"Meesho.init('returns');",
								),
							),
						),
						"ms_scan_ship" => array(
							"title" => "Scan & Ship",
							"icon" => "fa fa-barcode",
							"href" => BASE_URL . "/amazon/az_scan_ship.php",
							"header" => array(
								"css" => array(),
							),
							"footer" => array(
								"plugins" => array(),
								"scripts" => array(
									"meesho.js?v=" . $js_version,
								),
								"init" => array(
									"Meesho.init('scan_ship');",
								),
							),
						),
					),
				),
				"shopify" => array(
					"title" => "Shopify",
					"icon" => "fab fa-shopify",
					"href" => "javascript:;",
					"sub-menu" => array(
						"sp_overview" => array(
							"title" => "Overview",
							"icon" => "fa fa-eye",
							"href" => BASE_URL . "/shopify/overview.php",
							"header" => array(
								"css" => array(
									"jqvmap/jqvmap/jqvmap.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"fullcalendar/fullcalendar/fullcalendar.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"flot/jquery.flot.js?v=" . $js_version,
									"flot/jquery.flot.tooltip.min.js?v=" . $js_version,
									"flot/jquery.flot.resize.js?v=" . $js_version,
									"flot/jquery.flot.time.js?v=" . $js_version,
									"jqvmap/jqvmap/jquery.vmap.js?v=" . $js_version,
									"jqvmap/jqvmap/maps/jquery.vmap.india.js?v=" . $js_version,
									"jquery.blockui.min.js?v=" . $js_version,
									"uniform/jquery.uniform.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"shopify.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
										'var brands = ' . json_encode(get_all_brands()) . ';'
									)
								),
								"init" => array(
									"Shopify.init('overview')",
								),
							),
						),
						"sp_orders" => array(
							"title" => "Orders",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/shopify/orders.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"shopify.js?v=" . $js_version => array(
										'var tags = ' . get_option('order_tags') . ';',
									)
								),
								"init" => array(
									"Shopify.init('orders');",
								),
							),
						),
						"sp_order" => array(
							"view" => "hide",
							"title" => "Order",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/shopify/order.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"clipboard/clipboard.min.js?v=" . $js_version
								),
								"scripts" => array(
									'ajaxfileupload.js?v=' . $js_version,
									'jquery.form.min.js?v=' . $js_version,
									'comman_function.js?v=' . $js_version,
									"shopify-order.js?v=" . $js_version
								),
								"init" => array(
									"ShopifyOrder.init();",
								),
							),
						),
						"sp_returns" => array(
							"title" => "Returns",
							"icon" => "fa fa-reply-all",
							"href" => BASE_URL . "/shopify/returns.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version
								),
								"scripts" => array(
									"shopify.js?v=" . $js_version
								),
								"init" => array(
									"Shopify.init('returns');",
								),
							),
						),
						"sp_scan_ship" => array(
							"title" => "Scan & Ship",
							"icon" => "fa fa-barcode",
							"href" => BASE_URL . "/shopify/scan_ship.php",
							"header" => array(
								"css" => array(),
							),
							"footer" => array(
								"plugins" => array(),
								"scripts" => array(
									"shopify.js?v=" . $js_version,
								),
								"init" => array(
									"Shopify.init('scan_ship');",
								),
							),
						),
						"sp_payments" => array(
							"title" => "Payments",
							"icon" => "fa fa-rupee-sign",
							"href" => BASE_URL . "/shopify/payments.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"datatables/extensions/fixedHeader/css/fixedHeader.dataTables.min.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"jquery-tags-input/jquery.tagsinput.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"select2/select2.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/extensions/fixedHeader/js/dataTables.fixedHeader.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-tags-input/jquery.tagsinput.min.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
								),
								"scripts" => array(
									"shopify.js?v=" . $js_version => array(
										'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
										'var payments_issues = ' . get_all_payments_issues("flipkart") . ';',
									)
								),
								"init" => array(
									"Shopify.init('payments');",
								),
							),
						),
					),
				),
				"rma" => array(
					"title" => "RMA",
					"icon" => "fa fa-cogs",
					"href" => "javascript:;",
					"sub-menu" => array(
						"rma_create" => array(
							"title" => "Create",
							"icon" => "fa fa-plus",
							"href" => BASE_URL . "/rma/create.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
									"bootstrap-editable/bootstrap-editable/css/bootstrap-editable.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-validation/js/additional-methods.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-editable/bootstrap-editable/js/bootstrap-editable.min.js?v=" . $js_version,
									"jquery.pulsate.min.js?v=" . $js_version
								),
								"scripts" => array(
									"rma.js?v=" . $js_version => array(
										'var base_url = "' . BASE_URL . '";',
										'var roles = ' . json_encode(get_all_roles()) . ';',
									),
								),
								"init" => array(
									"RMA.init('create');",
								),
							),
						),
						"rma_orders" => array(
							"title" => "Orders",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/rma/orders.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version
								),
								"scripts" => array(
									"rma.js?v=" . $js_version
								),
								"init" => array(
									"RMA.init('orders');",
								),
							),
						),
						"repairs" => array(
							"title" => "Repairs",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/rma/repairs.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version
								),
								"scripts" => array(
									"rma.js?v=" . $js_version
								),
								"init" => array(
									"RMA.init('repairs');",
								),
							),
						),
						"reship" => array(
							"title" => "Reship",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/rma/reship.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version
								),
								"scripts" => array(
									"rma.js?v=" . $js_version
								),
								"init" => array(
									"RMA.init('reship');",
								),
							),
						),
						"rma_order" => array(
							"view" => "hide",
							"title" => "Order",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/rma/order.php",
							"header" => array(
								"css" => array(
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"bootstrap-fileinput/bootstrap-fileinput.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"clipboard/clipboard.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"jquery-validation/js/additional-methods.min.js?v=" . $js_version,
									"bootstrap-datepicker/js/bootstrap-datepicker.js?v=" . $js_version,
								),
								"scripts" => array(
									// 'ajaxfileupload.js?v='.$js_version,
									// 'jquery.form.min.js?v='.$js_version,
									// 'comman_function.js?v='.$js_version,
									"rma-order.js?v=" . $js_version,
									"rma.js?v=" . $js_version
								),
								"init" => array(
									"RmaOrders.init();",
								),
							),
						),
					),
				),
				"paytm" => array(
					"title" => "Paytm",
					"icon" => "fab fa-pinterest-p",
					"href" => "javascript:;",
					"sub-menu" => array(
						"pt_orders" => array(
							"title" => "Orders",
							"icon" => "fa fa-shopping-cart",
							"href" => BASE_URL . "/paytm/orders.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
									"bootstrap-datepicker/css/datepicker.css",
									"bootstrap-daterangepicker/daterangepicker-bs3.css",
									"bootstrap-fileinput/bootstrap-fileinput.css"
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
									"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
									"bootstrap-fileinput/bootstrap-fileinput.js?v=" . $js_version
								),
								"scripts" => array(
									"paytm.js?v=" . $js_version
								),
								"init" => array(
									"Paytm.init('orders');",
								),
							),
						),
						"pt_scan_ship" => array(
							"title" => "Scan & Ship",
							"icon" => "fa fa-barcode",
							"href" => BASE_URL . "/paytm/scan_ship.php",
							"header" => array(
								"css" => array(),
							),
							"footer" => array(
								"plugins" => array(),
								"scripts" => array(
									"paytm.js?v=" . $js_version,
								),
								"init" => array(
									"Paytm.init('scan_ship');",
								),
							),
						),
					),
				),
			),
		),
		"users" => array(
			"title" => "Users",
			"icon" => "fa fa-user",
			"href" => "javascript:;",
			"sub" => array(
				"users" => array(
					"title" => "All Users",
					"icon" => "fa fa-users",
					"href" => BASE_URL . "/users/all_user.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"select2/select2.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
						)
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"users.js?v=" . $js_version => array(
								'var roles = ' . json_encode(get_all_roles()) . ';',
							),
						),
						"init" => array(
							'Users.init("user");'
						)
					),
				),
				"user_role" => array(
					"title" => "User Roles",
					"icon" => "fa fa-user-shield",
					"href" => BASE_URL . "/users/user_role.php",
					"header" => array(
						"css" => array()
					),
					"footer" => array(
						"plugins" => array(
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"users.js?v=" . $js_version,
						),
						"init" => array(
							'Users.init("roles");'
						)
					),
				),
				"users_api" => array(
					"title" => "API Users",
					"icon" => "fa fa-user-secret",
					"href" => BASE_URL . "/users/api_user.php",
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"select2/select2.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
						)
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"users.js?v=" . $js_version => array(
								'var roles = ' . json_encode(get_all_roles()) . ';',
							),
						),
						"init" => array(
							'Users.init("user_api");'
						)
					),
				),
			),
		),
		"tools" => array(
			"title" => "Tools",
			"icon" => "fa fa-cogs",
			"href" => "javascript:;",
			"sub" => array(
				"marketplaces" => array(
					"title" => "Marketplace",
					"icon" => "fa fa-landmark",
					"href" => BASE_URL . "/settings/marketplaces.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-switch/css/bootstrap-switch.min.css",
							// "bootstrap-fileinput/bootstrap-fileinput.css",
							// "bootstrap-daterangepicker/daterangepicker-bs3.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"/ 'bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
							"/ 'bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
							"/ 'jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"/ 'bootstrap-touchspin/bootstrap.touchspin.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"jquery-validation/js/additional-methods.min.js?v=" . $js_version,
							"jquery-datepicker-validation/jquery.ui.datepicker.validation.min.js?v=" . $js_version
						),
						"scripts" => array(
							"/ 'form-components.js?v=" . $js_version,
							"tools.js?v=" . $js_version => array(
								'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
							)
						),
						"init" => array(
							'Tools.init("marketplaces");',
						),
					),
				),
				"reports" => array(
					"title" => "Reports",
					"icon" => "fa fa-chart-bar",
					"href" => BASE_URL . "/settings/reports.php",
					"header" => array(
						"css" => array(
							"select2/select2.css",
							// "datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"bootstrap-fileinput/bootstrap-fileinput.css",
							// "bootstrap-datepicker/css/datepicker.css",
							"bootstrap-daterangepicker/daterangepicker-bs3.css",
							// "fullcalendar/fullcalendar/fullcalendar.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"select2/select2.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/daterangepicker.js?v=" . $js_version,
							"/ 'jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
							"/ 'bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"/ 'bootstrap-touchspin/bootstrap.touchspin.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"/ 'jquery-validation/js/additional-methods.min.js?v=" . $js_version,
							"jquery-datepicker-validation/jquery.ui.datepicker.validation.min.js?v=" . $js_version
						),
						"scripts" => array(
							"form-components.js?v=" . $js_version,
							"tools.js?v=" . $js_version => array(
								'var accounts = ' . json_encode(get_all_accounts(array('account_id', 'account_name', 'fk_account_name', 'account_status', 'seller_id'))) . ';',
							)
						),
						"init" => array(
							"Tools.init('report');",
						),
					),
				),
				"3ps" => array(
					"title" => "3rd Party Services",
					"icon" => "fa fa-external-link-square-alt",
					"href" => "javascript:;",
					"sub-menu" => array(
						"sms" => array(
							"title" => "SMS",
							"icon" => "fa fa-sms",
							"href" => BASE_URL . "/settings/sms.php",
							"header" => array(
								"css" => array(
									"bootstrap-switch/css/bootstrap-switch.min.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version,
								),
								"init" => array(
									'Tools.init("sms");',
								),
							),
						),
						"email" => array(
							"title" => "Email",
							"icon" => "fa fa-envelope",
							"href" => BASE_URL . "/settings/email.php",
							"header" => array(
								"css" => array(
									"bootstrap-switch/css/bootstrap-switch.min.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version,
								),
								"init" => array(
									'Tools.init("email");',
								),
							),
						),
						"whatsapp" => array(
							"title" => "WhatsApp",
							"icon" => "fab fa-whatsapp",
							"href" => BASE_URL . "/settings/whatsapp.php",
							"header" => array(
								"css" => array(
									"bootstrap-switch/css/bootstrap-switch.min.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version,
								),
								"init" => array(
									'Tools.init("whatsapp");',
								),
							),
						),
						"logistic" => array(
							"title" => "Logistic Partner",
							"icon" => "fa fa-truck",
							"href" => BASE_URL . "/settings/logistic.php",
							"header" => array(
								"css" => array(
									"bootstrap-switch/css/bootstrap-switch.min.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
									"datatables/media/js/jquery.dataTables.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version,
								),
								"init" => array(
									'Tools.init("logistic");',
								),
							),
						),
					),
				),
				"templates" => array(
					"title" => "Templates",
					"icon" => "fa fa-layer-group",
					"href" => "javascript:;",
					"sub-menu" => array(
						"template_master" => array(
							"title" => "Template Master",
							"icon" => "fa fa-file",
							"href" => BASE_URL . "/settings/template.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
									)
								),
								"init" => array(
									"Tools.init('template');",
								),
							),
						),
						"sms_template" => array(
							"title" => "SMS Templates",
							"icon" => "fa fa-sms",
							"href" => BASE_URL . "/settings/sms_templates.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
										'var getAllSmsClient = ' . json_encode(get_all_sms_client()) . ';',
										'var getAllFirms = ' . json_encode(get_all_firms()) . ';',
										'var getMasterTemplate = ' . json_encode(get_master_template()) . ';',
									)
								),
								"external_scripts" => array(
									'//cdn.ckeditor.com/4.20.1/standard/ckeditor.js',
								),
								"init" => array(
									"Tools.init('sms_template');",
								),
							),
						),
						"email_template" => array(
							"title" => "Email Templates",
							"icon" => "fa fa-inbox",
							"href" => BASE_URL . "/settings/email_templates.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
										'var getAllEmailClient = ' . json_encode(get_all_email_client()) . ';',
										'var getAllFirms = ' . json_encode(get_all_firms()) . ';',
										'var getMasterTemplate = ' . json_encode(get_master_template()) . ';',
									)
								),
								"external_scripts" => array(
									'//cdn.ckeditor.com/4.20.1/standard/ckeditor.js',
								),
								"init" => array(
									"Tools.init('email_template');",
								),
							),
						),
						"whatsapp_template" => array(
							"title" => "WhatsApp Templates",
							"icon" => "fab fa-whatsapp",
							"href" => BASE_URL . "/settings/whatsapp_templates.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
										'var getAllWhatsappClient = ' . json_encode(get_all_whatsapp_client()) . ';',
										'var getAllFirms = ' . json_encode(get_all_firms()) . ';',
										'var getMasterTemplate = ' . json_encode(get_master_template()) . ';',
									)
								),
								"external_scripts" => array(
									'//cdn.ckeditor.com/4.20.1/standard/ckeditor.js',
								),
								"init" => array(
									"Tools.init('whatsapp_template');",
								),
							),
						),
						"template_element" => array(
							"title" => "Template Elements",
							"icon" => "fab fa-elementor",
							"href" => BASE_URL . "/settings/template_element.php",
							"header" => array(
								"css" => array(
									"datatables/plugins/bootstrap/dataTables.bootstrap.css",
									"select2/select2.css",
								),
							),
							"footer" => array(
								"plugins" => array(
									"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
									"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
									"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
									"select2/select2.min.js?v=" . $js_version,
								),
								"scripts" => array(
									"tools.js?v=" . $js_version => array(
										'var client_id = ' . $client_id . ';',
										'var getAllWhatsappClient = ' . json_encode(get_all_whatsapp_client()) . ';',
										'var getAllFirms = ' . json_encode(get_all_firms()) . ';',
										'var getMasterTemplate = ' . json_encode(get_master_template()) . ';',
									)
								),
								"external_scripts" => array(
									'//cdn.ckeditor.com/4.20.1/standard/ckeditor.js',
								),
								"init" => array(
									"Tools.init('template_element');",
								),
							),
						),
					),
				),
				"qz_settings" => array(
					"title" => "QZ Settings",
					"icon" => "fa fa-print",
					"href" => BASE_URL . "/settings/qz_settings.php",
					"header" => array(
						"css" => array(
							"bootstrap-switch/css/bootstrap-switch.min.css",
							"select2/select2.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"bootstrap-switch/js/bootstrap-switch.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"qz/rsvp-3.1.0.min.js?v=" . $js_version,
							"qz/sha-256.min.js?v=" . $js_version,
							"qz/qz-tray.js?v=" . $js_version,
						),
						"scripts" => array(
							"qz-settings.js?v=" . $js_version,
							"tools.js?v=" . $js_version => array(
								'var printer_settings = ' . json_encode(get_printer_settings()) . ';',
							),
						),
						"init" => array(
							'Tools.init("qz_settings");',
						),
					),
				),
				"rate" => array(
					"title" => "Rate Cards",
					"icon" => "fa fa-credit-card",
					"href" => BASE_URL . '/settings/rate_card.php',
					"header" => array(
						"css" => array(
							"datatables/plugins/bootstrap/dataTables.bootstrap.css",
							"select2/select2.css",
							"bootstrap-datepicker/css/datepicker.css",
						),
					),
					"footer" => array(
						"plugins" => array(
							"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=" . $js_version,
							"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=" . $js_version,
							"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
							"select2/select2.min.js?v=" . $js_version,
							"bootstrap-daterangepicker/moment.min.js?v=" . $js_version,
						),
						"scripts" => array(
							"rate-card.js?v=" . $js_version
						),
						"external_scripts" => array(
							'//cdn.ckeditor.com/4.20.1/standard/ckeditor.js',
						),
						"init" => array(
							"RateCard.init('rate-card')",
						),
					),
				),
				"cron" => array(
					"title" => "Cron",
					"icon" => "fa fa-sync",
					"href" => BASE_URL . "/settings/cron.php",
				),
				// "product_brand" => array(
				// 	"title" => "Language",
				// 	"icon" => "fa fa-language",
				// 	"href" => BASE_URL."/language/language.php",
				// 	"header" => array(
				// 		"css" => array(
				// 			"datatables/plugins/bootstrap/dataTables.bootstrap.css",
				// 			"select2/select2.css",
				// 		),
				// 	),
				// 	"footer" => array(
				// 		"plugins" => array(
				// 			"datatables/media/js/jquery.dataTables-1.10.20.min.js?v=".$js_version,
				// 			"datatables/plugins/bootstrap/dataTables.bootstrap.js?v=".$js_version,
				// 			"jquery-validation/js/jquery.validate.min.js?v=".$js_version,
				// 			"select2/select2.min.js?v=".$js_version,
				// 		),
				// 		"scripts" => array(
				// 			"language.js?v=".$js_version => array(
				// 				'var client_id = '.$client_id.';',
				// 			)
				// 		),
				// 		"init" => array(
				// 			"Language.init('language');",
				// 		),
				// 	),
				// ),
			),
		),
		"print" => array(
			"type" => "hidden",
			"title" => "Print Preview",
			"href" => BASE_URL . "/print.php",
			"only_body" => true,
			"header" => array(
				"css" => array(
					"../css/pages/invoice.css",
				),
			),
			"footer" => array(
				"plugins" => array(),
				"scripts" => array(),
				"init" => array(),
			),
		),
		"verify" => array(
			"title" => "User Email Verification",
			"href" => BASE_URL . "/recover/verify.php",
			"type" => "hidden",
			"only_body" => true,
			"header" => array(
				"css" => array(
					"../css/pages/login.css",
				),
			),
			"footer" => array(
				"plugins" => array(
					"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
					"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
				),
				"scripts" => array(
					"login.js?v=" . $js_version,
				),
				"init" => array(
					'Login.initVerify();',
				),
			),
		),
		"reset_password" => array(
			"title" => "Reset Password",
			"href" => BASE_URL . "/recover/reset_password.php",
			"only_body" => true,
			"type" => "hidden",
			"header" => array(
				"css" => array(
					"../css/pages/login.css",
				),
			),
			"footer" => array(
				"plugins" => array(
					"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
					"bootstrap-pwstrength/pwstrength-bootstrap.min.js?v=" . $js_version,
					"sha512/sha512.js?v=" . $js_version,
				),
				"scripts" => array(
					"login.js?v=" . $js_version,
				),
				"init" => array(
					'Login.initReset();',
				),
			),
		),
		"profile" => array(
			"type" => "hidden",
			"title" => "My Profile",
			"href" => BASE_URL . "/users/profile.php",
			"header" => array(
				"css" => array(
					"../../css/pages/profile.css",
				),
			),
			"footer" => array(
				"plugins" => array(
					"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
					"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
					"bootstrap-pwstrength/pwstrength-bootstrap.min.js?v=" . $js_version,
					"sha512/sha512.js?v=" . $js_version,
				),
				"scripts" => array(
					"users.js?v=" . $js_version,
				),
				"init" => array(
					'Users.init("profile");'
				),
			),
		),
		"brand-authorisation" => array(
			"title" => "Brand Authorization Validation",
			"href" => BASE_URL . "/brand-authorisation/",
			"type" => "hidden",
			"only_body" => true,
			"header" => array(
				"css" => array(
					"../css/pages/login.css",
				),
			),
			"footer" => array(
				"plugins" => array(
					"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
					"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
				),
				"scripts" => array(
					"brand_authorisation.js?v=" . $js_version,
				),
				"init" => array(
					'Brand.init();',
				),
			),
		),
		"two-factor-authentication" => array(
			"title" => "Brand Authorization Validation",
			"href" => BASE_URL . "/login/two-factor-auth.php",
			"type" => "hidden",
			"only_body" => true,
			"header" => array(
				"css" => array(
					"../../css/pages/login.css",
				),
			),
			"footer" => array(
				"plugins" => array(
					"jquery-inputmask/jquery.inputmask.bundle.min.js?v=" . $js_version,
					"jquery-validation/js/jquery.validate.min.js?v=" . $js_version,
				),
				"scripts" => array(
					"brand_authorisation.js?v=" . $js_version,
				),
				"init" => array(
					'Brand.init();',
				),
			),
		)
	);
	return $menu_items;
}

function get_menu()
{
	global $db, $current_page, $breadcrumps, $header, $footer, $page_title, $only_body, $userAccess, $access_levels, $current_url;

	$menu_items = menu_items();

	$menu = "<ul class='page-sidebar-menu'>\r\n";
	$menu .= "<li class='sidebar-toggler-wrapper'>
				<!-- BEGIN SIDEBAR TOGGLER BUTTON -->
				<div class='sidebar-toggler'>
				</div>
				<div class='clearfix'>
				</div>
				<!-- BEGIN SIDEBAR TOGGLER BUTTON -->
			</li>
			<li class='sidebar-search-wrapper'>
				<form class='search-form' role='form' action='#' method='get'>
					<div class='input-icon right'>
						<i class='fa fa-search'></i>
						<input type='text' class='form-control input-sm' name='query' placeholder='Search...'>
					</div>
				</form>
			</li>";

	$i = 0;
	foreach ($menu_items as $menu_key => $menu_item) {

		$classes = array();
		if ($i == 0) {
			$classes[] = "start";
		}

		if (strpos($current_url, $menu_item["href"]) !== false) {
			// $page_title = $menu_items[$menu_key]['title'] .' - '. $menu_item['title'];
			$page_title = $menu_item['title'];
			$header = isset($menu_item['header']) ? $menu_item['header'] : "";
			$footer = isset($menu_item['footer']) ? $menu_item['footer'] : "";
			$only_body = isset($menu_item['only_body']) ? $menu_item['only_body'] : false;
			$access_levels[$menu_key] = (isset($menu_item['sub']) ? array() : false);

			$current_page = $menu_key;
			if (isset($menu_item['type']) && $menu_item['type'] == 'hidden')
				continue;

			if (!$userAccess->checkCapability($menu_key, true)) {
				$breadcrumps[] = array(
					'title' => 'Access Denied',
					'href' => '',
				);
				continue;
			}

			if (!in_array('active', $classes))
				$classes[] = "active";
			if (!in_array('open', $classes))
				$classes[] = "open";
		}

		$arrow = "<span class='arrow'></span>";
		$submenu = "";

		if (isset($menu_item['sub'])) {
			$submenu .= "<ul class='sub-menu'>\r\n";
			foreach ($menu_item['sub'] as $sub_key => $sub_item) {
				$access_levels[$menu_key][$sub_key] = (isset($sub_item['sub-menu']) ? array() : false);

				if (isset($sub_item['type']) && $sub_item['type'] == 'hidden')
					continue;

				$inner_submenu = "";
				$icon = "";
				if (isset($sub_item["icon"]))
					$icon = "<i class='" . $sub_item["icon"] . "'></i>";

				if (!isset($sub_item["sub-menu"])) {
					$class = "";
					$inner_class = "";


					if (strpos($current_url, $sub_item["href"]) !== false) {
						$page_title = $menu_item['title'] . ' - ' . $sub_item['title'];
						$header = $sub_item['header'];
						$footer = $sub_item['footer'];

						$current_page = $sub_key;
						if (!$userAccess->checkCapability($sub_key, true)) {
							$breadcrumps[] = array(
								'title' => 'Access Denied',
								'href' => '',
							);
							continue;
						}

						if (!in_array('active', $classes))
							$classes[] = "active";
						if (!in_array('open', $classes))
							$classes[] = "open";
						$class = " class = 'active'";

						// Parent
						$breadcrumps[] = array(
							'title' => $menu_item['title'],
							'href' => $menu_item['href'],
						);
						// Ancestor
						$breadcrumps[] = array(
							'title' => $sub_item['title'],
							'href' => $sub_item['href'],
						);
					} else {
						if (!$userAccess->checkCapability($sub_key, true)) {
							continue;
						}
					}
					$submenu .= "<li" . $class . "><a href='" . $sub_item["href"] . "'>" . $icon . " " . $sub_item["title"] . "</a>";
				} else {
					$inner_submenu .= "\r\n<ul class='sub-menu'>\r\n";
					$inner_arrow_class = array('arrow');
					$class = "";
					$sub_menu_list = array();
					foreach ($sub_item["sub-menu"] as $sub_menu_key => $sub_menu_item) {
						$access_levels[$menu_key][$sub_key][$sub_menu_key] = false;
						if (isset($sub_menu_item['type']) && $sub_menu_item['type'] == 'hidden')
							continue;

						$inner_class = "";
						if (strpos($current_url, $sub_menu_item["href"]) !== false) {
							$current_page = $sub_menu_key;
							if (!$userAccess->checkCapability($sub_menu_key, true)) {
								$breadcrumps[] = array(
									'title' => 'Access Denied',
									'href' => '',
								);
								continue;
							}
							$page_title = $menu_item['sub'][$sub_key]['title'] . ' - ' . $sub_menu_item['title'];
							if (!in_array('active', $classes))
								$classes[] = "active";

							if (!in_array('open', $classes))
								$classes[] = "open";

							$inner_class = $class = "active";
							$inner_arrow_class[] = "open";

							$header = $sub_menu_item['header'];
							$footer = $sub_menu_item['footer'];

							// Parent
							$breadcrumps[] = array(
								'title' => $menu_item['title'],
								'href' => $menu_item['href'],
								'base' => 'submenu',
							);
							// Ancestor
							$breadcrumps[] = array(
								'title' => $sub_item['title'],
								'href' => $sub_item['href'],
								'base' => 'submenu',
							);
							// Child
							$breadcrumps[] = array(
								'title' => $sub_menu_item['title'],
								'href' => $sub_menu_item['href'],
								'base' => 'submenu',
							);
						} else {
							if (!$userAccess->checkCapability($sub_menu_key, true)) {
								continue;
							}
						}

						$sub_menu_list[] = $sub_menu_key;

						$inner_icon = "";
						if (isset($sub_menu_item["icon"]))
							$inner_icon = "<i class='" . $sub_menu_item["icon"] . "'></i>";

						$inner_class = (isset($sub_menu_item['view']) && $sub_menu_item['view'] == "hide") ? $inner_class . " hide" : $inner_class;
						$arrow = "<span class='" . implode(" ", $inner_arrow_class) . "'></span>";
						$inner_submenu .= "<li class='" . $inner_class . "'><a href='" . $sub_menu_item["href"] . "'>" . $inner_icon . " " . $sub_menu_item["title"] . "</a></li>\r\n";
					}
					$inner_submenu .= "</ul>\r\n";

					if (!empty($sub_menu_list))
						$submenu .= "<li class='" . $class . "''><a href='" . $sub_item["href"] . "'>" . $icon . " " . $sub_item["title"] . $arrow . "</a>";
				}

				$submenu .= $inner_submenu;
				$submenu .= "</li>\r\n";
			}
			$submenu .= "</ul>\r\n";
		} else {
			$arrow = "";
			$access_levels[$menu_key] = (isset($menu_key['sub']) ? array() : true);
		}

		if (!$userAccess->checkCapability($menu_key, true))
			continue;

		if (isset($menu_item['type']) && $menu_item['type'] == 'hidden')
			continue;

		$icon = isset($menu_item["icon"]) ? $menu_item["icon"] : '';

		$menu .= "<li class='" . implode(" ", $classes) . "'>\r\n";
		$menu .= "<a href='" . $menu_item["href"] . "'>\r\n";
		$menu .= "<i class='" . $icon . "'></i>\r\n";
		$menu .= "<span class='title'>" . $menu_item["title"] . "</span>\r\n" . $arrow . "\r\n</a>\r\n";
		$menu .= $submenu;
		$menu .= "</li>\r\n";
		$i++;
	}
	$menu .= "</ul>";

	breadcrumps();

	$db->where('option_key', 'access_levels');
	$db->update(TBL_OPTIONS, array('option_value' => json_encode($access_levels)));

	return $menu;
}

function breadcrumps()
{
	global $breadcrumps;

	$return = "<ul class='page-breadcrumb'>\r\n
		<li>
			<i class='fa fa-home'></i>
			<a href='" . BASE_URL . "'>Home</a>
			<i class='fa fa-angle-right'></i>
		</li>";

	$i = count($breadcrumps) - 1;
	foreach ($breadcrumps as $breadcrump) {
		if ($i != 0)
			$return .= "<li><a href='" . $breadcrump['href'] . "'>" . $breadcrump['title'] . "</a><i class='fa fa-angle-right'></i></li>";
		else
			$return .= "<li><a href='javascript:;'>" . $breadcrump['title'] . "</a></li>";
		$i--;
	}

	$return .= "</ul>";

	$breadcrumps = $return;
}

function get_page_title()
{
	global $page_title;

	return $page_title;
}

function get_header()
{
	global $header;

	$header_item = '';
	foreach ($header as $key => $assets) {
		if ($key == "css") {
			$header_item .= "\t<!-- BEGIN PAGE LEVEL CSS -->\n";
			foreach ($assets as $a_key => $a_value) {
				$header_item .= "\t<link async rel='stylesheet' type='text/css' href='" . BASE_URL . "/assets/plugins/" . $a_value . "' />\n";
			}
			$header_item .= "\t<!-- END PAGE LEVEL CSS -->\n";
		}
		if ($key == "plugins") {
			$header_item .= "\t<!-- BEGIN PAGE LEVEL PLUGINS -->\n";
			foreach ($assets as $a_key => $a_value) {
				$header_item .= "\t<script async type='text/javascript' src='" . BASE_URL . "/assets/plugins/" . $a_value . "'></script>\n";
			}
			$header_item .= "\t<!-- END PAGE LEVEL PLUGINS -->\n";
		}
		if ($key == "scripts") {
			$header_item .= "\t<!-- BEGIN PAGE LEVEL SCRIPTS -->\n";
			foreach ($assets as $a_key => $a_value) {
				if (is_array($a_value)) {
					for ($i = 0; $i < count($a_value); $i++) {
						$header_item .= "\t<script defer type='text/javascript'>" . $a_value[$i] . "</script>\n";
					}
					$header_item .= "\t<script defer type='text/javascript' src='" . BASE_URL . "/assets/scripts/" . $a_key . "'></script>\n";
				} else {
					$header_item .= "\t<script defer type='text/javascript' src='" . BASE_URL . "/assets/scripts/" . $a_value . "'></script>\n";
				}
			}
			$header_item .= "\t<!-- END PAGE LEVEL SCRIPTS -->\n";
		}
		if ($key == "external_css") {
			$header_item .= "\t<!-- BEGIN PAGE LEVEL EXTERNAL CSS -->\n";
			foreach ($assets as $a_key => $a_value) {
				$header_item .= "\t<link async rel='stylesheet' type='text/css' href='" . $a_value . "' />\n";
			}
			$header_item .= "\t<!-- END PAGE LEVEL EXTERNAL CSS -->\n";
		}
		if ($key == "external_scripts") {
			$header_item .= "\t<!-- BEGIN PAGE LEVEL EXTERNAL SCRIPTS -->\n";
			foreach ($assets as $a_key => $a_value) {
				$header_item .= "\t<script defer type='text/javascript' src='" . $a_value . "'></script>\n";
			}
			$header_item .= "\t<!-- END PAGE LEVEL EXTERNAL SCRIPTS -->\n";
		}
		if ($key == "init") {
			$header_item .= "\t<script defer type='text/javascript'>\n\t\tjQuery(document).ready(function() {";
			foreach ($assets as $a_key => $a_value) {
				$header_item .= "\n\t\t\t" . $a_value;
			}
			$header .= "\n\t\t});\n\t</script>\n";
		}
	}

	return $header_item;
}

function get_footer()
{
	global $footer;

	$footer_item = '';
	foreach ($footer as $key => $assets) {
		if ($key == "plugins") {
			$footer_item .= "\t<!-- BEGIN PAGE LEVEL PLUGINS -->\n";
			foreach ($assets as $a_key => $a_value) {
				$footer_item .= "\t<script defer type='text/javascript' src='" . BASE_URL . "/assets/plugins/" . $a_value . "'></script>\n";
			}
			$footer_item .= "\t<!-- END PAGE LEVEL PLUGINS -->\n";
		}
		if ($key == "scripts") {
			$footer_item .= "\t<!-- BEGIN PAGE LEVEL SCRIPTS -->\n";
			foreach ($assets as $a_key => $a_value) {
				if (is_array($a_value)) {
					for ($i = 0; $i < count($a_value); $i++) {
						$footer_item .= "\t<script defer type='text/javascript'>" . $a_value[$i] . "</script>\n";
					}
					$footer_item .= "\t<script defer type='text/javascript' src='" . BASE_URL . "/assets/scripts/" . $a_key . "'></script>\n";
				} else {
					$footer_item .= "\t<script defer type='text/javascript' src='" . BASE_URL . "/assets/scripts/" . $a_value . "'></script>\n";
				}
			}
			$footer_item .= "\t<!-- END PAGE LEVEL SCRIPTS -->\n";
		}
		/*if ($key == "external_css"){
			$header_item .= "\t<!-- BEGIN PAGE LEVEL EXTERNAL CSS -->\n";
			foreach ($assets as $a_key => $a_value) {
				$header_item .= "\t<link rel='stylesheet' type='text/css' href='".$a_value."' />\n";
			}
			$header_item .= "\t<!-- END PAGE LEVEL EXTERNAL CSS -->\n";	
		}*/
		if ($key == "external_scripts") {
			$footer_item .= "\t<!-- BEGIN PAGE LEVEL EXTERNAL SCRIPTS -->\n";
			foreach ($assets as $a_key => $a_value) {
				$footer_item .= "\t<script defer type='text/javascript' src='" . $a_value . "'></script>\n";
			}
			$footer_item .= "\t<!-- END PAGE LEVEL EXTERNAL SCRIPTS -->\n";
		}
		if ($key == "init") {
			$footer_item .= "\t<script defer type='text/javascript'>\n\t\tjQuery(document).ready(function() {";
			foreach ($assets as $a_key => $a_value) {
				$footer_item .= "\n\t\t\t" . $a_value;
			}
			$footer_item .= "\n\t\t});\n\t</script>\n";
		}
	}

	return $footer_item;
}

function get_all_accounts($fk_fields = array(), $fields = array())
{
	global $db;

	$db->where('account_status', 1);
	// $db->where('client_id', $client_id);
	$return['flipkart'] = $db->ObjectBuilder()->get(TBL_FK_ACCOUNTS, NULL, $fields);
	$return['amazon'] = $db->ObjectBuilder()->get(TBL_AZ_ACCOUNTS, NULL, $fields);
	// $return['paytm'] = $db->ObjectBuilder()->get(TBL_PT_ACCOUNTS, NULL, $fields);
	$return['shopify'] = $db->ObjectBuilder()->get(TBL_SP_ACCOUNTS, NULL, $fields);
	$return['ajio'] = $db->ObjectBuilder()->get(TBL_AJ_ACCOUNTS, NULL, $fields);
	$return['meesho'] = $db->ObjectBuilder()->get(TBL_MS_ACCOUNTS, NULL, $fields);

	return $return;
}

function get_all_brands()
{
	global $db, $client_id;

	$db->where('client_id', $client_id);
	$return['opt'] = $db->get(TBL_PRODUCTS_BRAND, NULL, array('brandid', 'brandName'));

	return $return;
}

function get_brand_details_by_name($brand_name, $client_id)
{
	global $db;

	$db->where('client_id', $client_id);
	$db->where('brandName', $brand_name);
	$return = $db->getOne(TBL_PRODUCTS_BRAND);

	return $return;
}

function get_all_categories()
{
	global $db, $client_id;

	$db->where('client_id', $client_id);
	$return['opt'] = $db->get(TBL_PRODUCTS_CATEGORY, null, array('catid', 'categoryName'));

	return $return;
}

function get_category_by_id($class, $id)
{
	global $db;

	if ($class == 'cid') {
		$db->where($class, $id);
		$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
		$product = json_decode($products['pid']);

		$db->where('pid', $product[0]);
		$category = $db->getOne(TBL_PRODUCTS_MASTER, 'category');
	} else {
		$db->where('pid', $id);
		$category = $db->getOne(TBL_PRODUCTS_MASTER, 'category');
	}

	return $category['category'];
}

function get_all_parent_sku($suffix = "", $item_ids = array())
{
	global $db, $client_id;

	$db->where('client_id', $client_id);
	if (!empty($item_ids))
		$db->where('pid', $item_ids, 'IN');
	$db->orderBy('sku', 'ASC');
	$products = $db->get(TBL_PRODUCTS_MASTER);
	$options = array();
	foreach ($products as $product) {
		$options[] = array('key' => $suffix . $product['pid'], 'value' => trim($product['sku']));
	}

	return $options;
}

function get_all_sku()
{
	global $db, $client_id;

	$db->where('client_id', $client_id);
	$db->orderBy('sku', 'ASC');
	$products = $db->get(TBL_PRODUCTS_MASTER);
	$options = array();
	foreach ($products as $product) {
		$options[] = array('key' => 'pid-' . $product['pid'], 'value' => trim($product['sku']));
	}

	// $db->where('client_id', $client_id);
	$products = $db->get(TBL_PRODUCTS_COMBO);
	$options_combo = array();
	foreach ($products as $product) {
		$options_combo[] = array('key' => 'cid-' . $product['cid'], 'value' => trim($product['sku']));
	}

	$all_skus = array_merge($options, $options_combo);
	usort(
		$all_skus,
		function ($a, $b) {
			if ($a['value'] == $b['value']) {
				return 0;
			}
			return ($a['value'] > $b['value']) ? -1 : 1;
		}
	);

	return $all_skus;
}

function get_all_mp_id($marketplace = 'all', $account_id = 'all')
{
	global $db;

	if ($marketplace != 'all')
		$db->where('marketplace', $marketplace);

	if ($account_id != 'all')
		$db->where('account_id', (int)$account_id);

	$aliases = $db->get(TBL_PRODUCTS_ALIAS);
	$all_aliases = array();
	foreach ($aliases as $alias) {
		$all_aliases[] = array('key' => $alias['alias_id'], 'value' => trim($alias['mp_id']), 'sku' => trim($alias['sku']));
	}

	usort(
		$all_aliases,
		function ($a, $b) {
			if ($a['value'] == $b['value']) {
				return 0;
			}
			return ($a['value'] > $b['value']) ? -1 : 1;
		}
	);

	return $all_aliases;
}

function get_all_suppliers($only_active = 0)
{
	global $db;

	$db->where('party_supplier', 1);
	$db->orderBy('party_name', 'ASC');
	if ($only_active)
		$db->where('is_active', 1);
	$suppliers = $db->ObjectBuilder()->get(TBL_PARTIES, NULL, 'party_id, party_name');

	return $suppliers;
}

function get_all_customers($only_active = 0)
{
	global $db, $client_id;

	$db->where('client_id', $client_id);

	if ($client_id == 0)
		$db->where('party_customer', 1);
	else {
		$db->where('party_reseller', 1);
		$db->where('JSON_EXTRACT(party_status, "$.seller")', 'approved');
	}
	if ($only_active)
		$db->where('is_active', 1);
	$customers = $db->ObjectBuilder()->get(TBL_PARTIES, NULL, 'party_id, party_name');

	return $customers;
}

function get_all_carriers($only_active = 0)
{
	global $db;

	$db->where('party_carrier', 1);
	if ($only_active)
		$db->where('is_active', 1);
	$carriers = $db->ObjectBuilder()->get(TBL_PARTIES, NULL, 'party_id, party_name');

	return $carriers;
}

function get_all_vendors($only_active = 0, $additional_fields = array())
{
	global $db;

	$db->where('party_ams', 1);
	if ($only_active)
		$db->where('is_active', 1);

	$fields = array('party_id', 'party_name');
	if (!empty($additional_fields))
		$fields = array_merge($fields, $additional_fields);

	$ams_sellers = $db->ObjectBuilder()->get(TBL_PARTIES, NULL, $fields);

	return $ams_sellers;
}

function get_all_lot_no($status = "")
{
	global $db;

	if (!empty($status)) {
		$db->where('lot_status', $status);
	}
	$lot = $db->objectBuilder()->get(TBL_PURCHASE_LOT, NULL, 'lot_id, lot_number, 0 as lot_local');

	if (!empty($status)) {
		$db->where('lot_status', $status);
	}
	$local_lot = $db->objectBuilder()->get(TBL_PURCHASE_LOT_LOCAL, NULL, 'lot_id, lot_number, 1 as lot_local');

	$lot = array_merge($lot, $local_lot);

	return $lot;
}

function get_all_payments_issues($marketplace)
{
	return get_option('payments_issues_' . $marketplace);
}

function get_parent_sku($marketplace, $account_id, $key, $value)
{
	global $db;

	$db->where('marketplace', $marketplace);
	$db->where('account_id', $account_id);
	$db->where($key, $value);
	$products = $db->getOne(TBL_PRODUCTS_ALIAS, array('pid, cid'));
	if ($products['cid']) {
		$db->where('cid', $products['cid']);
		$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
		$products = json_decode($products['pid']);
		foreach ($products as $product) {
			$db->where('pid', $product);
			$sku[] = $db->getValue(TBL_PRODUCTS_MASTER, 'sku');
		}
	} else {
		$db->where('pid', $products['pid']);
		$sku = $db->getValue(TBL_PRODUCTS_MASTER, 'sku');
	}

	return $sku;
}

function get_cost_price($class, $id)
{
	global $db, $stockist;

	if ($class == 'cid') {
		$db->where($class, $id);
		$products = $db->getOne(TBL_PRODUCTS_COMBO, 'pid');
		$products = json_decode($products['pid']);
		$units = count($products);

		$selling_price = 0;
		foreach ($products as $pro) {
			// $db->where('pid', $pro);
			// $price = $db->getOne(TBL_PRODUCTS_MASTER, 'selling_price');
			$selling_price += $stockist->get_current_acp($pro);
			// $selling_price += $price['selling_price'];
		}

		$return['cost_price'] = $selling_price / $units;
		$return['units'] = $units;
	} else {
		// $db->where('pid', $id);
		// $price = $db->getOne(TBL_PRODUCTS_MASTER, 'selling_price');
		$return['cost_price'] = $stockist->get_current_acp($id);

		// $return['cost_price'] = $price['selling_price'];
		$return['units'] = 1;
	}

	return $return;
}

function get_printer_settings($options = "")
{
	global $db, $current_user;

	if (empty($options))
		$options = array("printer_invoice", "printer_mrp_label", "printer_product_label", "printer_weighted_product_label", "printer_ctn_box_label", "printer_shipping_label");
	else
		$options = array($options);

	if (!isset($current_user['userID']) || !$current_user['userID'])
		return "";

	$db->where('userID', $current_user['userID']);
	$user_settings = json_decode($db->getValue(TBL_USERS, 'JSON_UNQUOTE(JSON_EXTRACT(user_settings,"$.printers"))'), true);

	$settings = array();
	foreach ($options as $option) {
		$settings[str_replace('printer_', '', $option)]['printer'] = $user_settings[$option];
		$settings[str_replace('printer_', '', $option)]['size'] = get_option($option . '_size');
	}

	return $settings;
}

function get_grn($type = null)
{
	global $db;

	if (!is_null($type)) {
		if (is_array($type)) {
			foreach ($type as $status) {
				$db->orWhere('grn_status', $status);
			}
		} else {
			$db->where('grn_status', $type);
		}
	}
	$grn = $db->objectBuilder()->get(TBL_PURCHASE_GRN, NULL, 'grn_id');

	return $grn;
}

function get_grn_items($grns)
{
	global $db;

	if (is_array($grns)) {
		foreach ($grns as $grn) {
			if (isset($grn->grn_id))
				$grn_id = $grn->grn_id; // OBJECT
			else
				$grn_id = $grn; // ARRAY

			$grn_local = false;
			$db->where('grn_id', $grn_id);
			$grn_local = $db->getValue(TBL_PURCHASE_GRN, 'grn_local');

			$db->join(TBL_PURCHASE_GRN . ' g', 'g.grn_id = gi.grn_id');
			$db->joinWhere(TBL_PURCHASE_GRN . ' g', 'g.grn_id', $grn_id);
			$db->joinWhere(TBL_PURCHASE_GRN . ' g', 'g.grn_local', $grn_local);
			if (!$grn_local)
				$db->join(TBL_PURCHASE_LOT . ' l', 'l.lot_id=g.lot_id');
			else
				$db->join(TBL_PURCHASE_LOT_LOCAL . ' l', 'l.lot_id=g.lot_id');
			$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=gi.item_id');
			$db->join(TBL_PRODUCTS_CATEGORY . ' c', 'c.catid=p.category');
			$db->where('item_qty', '0', '>');
			// $db->groupBy('sku');
			$grn_items['GRN_' . $grn_id] = $db->objectBuilder()->get(TBL_PURCHASE_GRN_ITEMS . ' gi', NULL, 'gi.grn_id, gi.item_id, p.sku, c.categoryName as category_name, p.thumb_image_url, gi.item_qty, gi.item_box, gi.item_status, l.lot_number');
		}
	} else {
		$grn_id = $grns;
		$grn_local = false;
		$db->where('grn_id', $grn_id);
		$grn_local = $db->getValue(TBL_PURCHASE_GRN, 'grn_local');

		$db->join(TBL_PURCHASE_GRN . ' g', 'g.grn_id = gi.grn_id');
		$db->joinWhere(TBL_PURCHASE_GRN . ' g', 'g.grn_id', $grn_id);
		$db->joinWhere(TBL_PURCHASE_GRN . ' g', 'g.grn_local', $grn_local);
		if (!$grn_local)
			$db->join(TBL_PURCHASE_LOT . ' l', 'l.lot_id=g.lot_id');
		else
			$db->join(TBL_PURCHASE_LOT_LOCAL . ' l', 'l.lot_id=g.lot_id');
		$db->join(TBL_PRODUCTS_MASTER . ' p', 'p.pid=gi.item_id');
		$db->where('item_qty', '0', '>');
		$grn_items['GRN_' . $grn_id] = $db->objectBuilder()->get(TBL_PURCHASE_GRN_ITEMS . ' gi', NULL, 'gi.grn_id, gi.item_id, p.sku, p.thumb_image_url, gi.item_qty, gi.item_box, gi.item_status, l.lot_number');
	}

	return $grn_items;
}

function get_qc_issues($type = "")
{
	global $db;

	if ($type == "parent")
		$db->where('qci.issue_group', NULL, 'IS');

	$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'pc.catid=qci.issue_category');
	$db->orderBy('issue_group', 'ASC');
	$issues = $db->get(TBL_INVENTORY_QC_ISSUES . ' qci', NULL, 'qci.issue_id, qci.issue, pc.categoryName, qci.issue_key, qci.issue_group, qci.issue_category, qci.issue_status, qci.issue_fix');

	$return = array();
	foreach ($issues as $issue) {
		if ($type == "parent") {
			$return[$issue['categoryName']][] = array('issue_id' => $issue['issue_id'], 'issue' => $issue['issue'], 'issue_key' => $issue['issue_key'], 'issue_status' => $issue['issue_status']);
		} else {
			if (is_null($issue['issue_group'])) {
				$return[$issue['categoryName']][$issue['issue_id']] = array('issue_id' => $issue['issue_id'], 'issue' => $issue['issue'], 'issue_fix' => $issue['issue_fix'], 'issue_key' => $issue['issue_key'], 'issue_group' => $issue['issue_group'], 'issue_category' => $issue['issue_category'], 'issue_status' => $issue['issue_status']);
			} else {
				$return[$issue['categoryName']][$issue['issue_group']]['issues'][] = array('issue_id' => $issue['issue_id'], 'issue' => $issue['issue'], 'issue_fix' => $issue['issue_fix'], 'issue_key' => $issue['issue_key'], 'issue_group' => $issue['issue_group'], 'issue_category' => $issue['issue_category'], 'issue_status' => $issue['issue_status']);
			}
		}
	}

	return $return;
}

function get_inventory_components()
{
	global $db;

	$db->join(TBL_PRODUCTS_CATEGORY . ' pc', 'pc.catid=ic.component_category');
	// $db->orderBy('component_name', 'ASC');
	$components = $db->get(TBL_INVENTORY_COMPONENTS . ' ic', NULL, 'ic.component_id, ic.component_name, pc.categoryName, ic.component_category, ic.component_status');

	$return = array();
	foreach ($components as $component) {
		$return[$component['categoryName']][] = array('component_id' => $component['component_id'], 'component_name' => $component['component_name'], 'component_categoryName' => $component['categoryName'], 'component_category' => $component['component_category'], 'component_status' => $component['component_status']);
	}

	return $return;
}

function set_option($key, $value)
{
	global $db;

	$db->where('option_key', $key);
	if ($db->has(TBL_OPTIONS)) {
		$db->where('option_key', $key);
		$return = $db->update(TBL_OPTIONS, array('option_value' => $value));
	} else {
		$return = $db->insert(TBL_OPTIONS, array('option_key' => $key, 'option_value' => $value));
	}

	return $return;
}

function get_short_url($long_url)
{
	$msg = '';
	$flag = false;
	try {
		if (!empty($long_url)) {
			$referer = '*.skmienterprise.com/*';
			$apiKey = "AIzaSyBFbh1gXt1Q3g4aucE5hYXyh9iwabOL150";
			$post_data = json_encode(array('longUrl' => $long_url));
			$ch = curl_init();
			$arr = array();
			array_push($arr, 'Content-Type: application/json; charset=utf-8');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $arr);
			curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/urlshortener/v1/url?key=" . $apiKey);
			curl_setopt($ch, CURLOPT_REFERER, $referer);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// we are doing a POST request
			curl_setopt($ch, CURLOPT_POST, 1);
			// adding the post variables to the request
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			$output = curl_exec($ch);

			if (curl_errno($ch)) {
				throw new Exception(curl_error($ch));
			}
			$short_url = json_decode($output);
			if (isset($short_url->error)) {
				throw new Exception($short_url->error->message);
			}
			$msg = $short_url->id;
			$flag = true;
			curl_close($ch);
		}
	} catch (Exception $e) {
		$msg = $e->getMessage();
		$flag = true;
	}
	return $msg;
}

function get_all_roles()
{
	$user_roles = json_decode(get_option('user_roles'));
	$roles = array();
	foreach ($user_roles as $key => $value) {
		$roles[] = $key;
	}
	return $roles;
}

function get_short_url_fk($long_url)
{
	$msg = '';
	try {
		if (!empty($long_url)) {
			$curl = curl_init();
			$url = "https://affiliate.flipkart.com/a_url_shorten?url=" . urlencode($long_url);

			curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_POSTFIELDS => "",
				CURLOPT_HTTPHEADER => array(
					"cache-control: no-cache",
					"content-type: application/json"
				),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);
			$short_url = json_decode($response);
			if ($short_url->status != "OK" && $short_url->response->response_code != "200") {
				$msg = "";
				throw new Exception($err);
			} else {
				$msg = str_replace('http://', '', $short_url->response->shortened_url);
			}
		}
	} catch (Exception $e) {
		$msg = "";
		throw new Exception($e->getMessage);
	}
	return $msg;
}

function csv_to_array($filename = '', $sorting = FALSE, $delimiter = ',')
{

	ini_set('auto_detect_line_endings', TRUE);
	if (!file_exists($filename) || !is_readable($filename))
		return FALSE;

	$header = NULL;
	$data = array();
	if (($handle = fopen($filename, 'r')) !== FALSE) {
		while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
			if (!$header) {
				$header = $row;
			} else {
				if (count($header) > count($row)) {
					$difference = count($header) - count($row);
					for ($i = 1; $i <= $difference; $i++) {
						$row[count($row) + 1] = $delimiter;
					}
				}
				$data[] = array_combine($header, $row);
			}
		}
		fclose($handle);
	}

	if ($sorting) {
		$sort = array();
		foreach ($data as $k => $v) {
			$sort['qty'][$k] = $v['Quantity'];
			if (in_array($v['SKU'], $wrong_sku)) {
				$sort['sku'][$k] = $correct_sku[array_search($v['SKU'], $wrong_sku)];
			} else {
				$sort['sku'][$k] = $v['SKU'];
			}
		}
		# sort by event_type desc and then title asc
		array_multisort($sort['qty'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $sort['sku'], SORT_ASC | SORT_NATURAL | SORT_FLAG_CASE, $data);
	}

	return $data;
}

function array_to_csv()
{
}

function check_valid_pdf($file_path)
{ // FULL FILE PATH WITH NAME
	if (file_exists($file_path)) {
		$fp = fopen($file_path, 'r');
		fseek($fp, 0); // move to the 0th byte
		$data = fread($fp, 5);   // read 5 bytes from byte 0
		if (strcmp($data, "%PDF-") == 0) {
			$return = true;
		} else {
			$return = false;
		}
		fclose($fp);
	} else {
		$return = false;
	}

	return $return;
}

function strpos_all($haystack, $needle)
{
	$offset = 0;
	$allpos = array();
	while (($pos = strpos($haystack, $needle, $offset)) !== FALSE) {
		$offset   = $pos + 1;
		$allpos[] = $pos;
	}
	return $allpos;
}

function check_key_exist_multi($array, $keySearch)
{
	foreach ($array as $key => $item) {
		if ($key == $keySearch) {
			return true;
		} elseif (is_array($item) && check_key_exist_multi($item, $keySearch)) {
			return true;
		}
	}
	return false;
}

// PDF DELETE. CHECK DUMMY AND SUPPORT FOR FILE TYPE
function delete_old_files($path)
{
	$interval = 86400;
	if ($handle = opendir(dirname($path . 'dummy'))) {
		while (false !== ($file = readdir($handle))) {
			if (((filemtime($path . $file) + $interval) < time()) && ($file != "..") && ($file != ".") && substr($file, -3) == 'pdf') { //
				unlink($path . $file);
			}
		}
		closedir($handle);
	}
}

// UNUSED FUNCTIONS
function gen_uuid($prefix = '')
{
	$chars = md5(uniqid(mt_rand(), true));
	$parts = [substr($chars, 0, 8), substr($chars, 8, 4), substr($chars, 12, 4), substr($chars, 16, 4), substr($chars, 20, 12)];

	return $prefix . implode('-', $parts);
}

function check_diff_multi($array1, $array2)
{
	$result = array();
	foreach ($array1 as $key => $val) {
		if (isset($array2[$key])) {
			if (is_array($val) && $array2[$key]) {
				$result[$key] = check_diff_multi($val, $array2[$key]);
			}
		} else {
			$result[$key] = $val;
		}
	}

	return $result;
}

function sendPushMessage($message, $title = "FIMS | Dashboard")
{

	$content = array(
		"en" => $message
	);

	$fields = array(
		'app_id' => "6f3ed6e5-2339-433d-a6c2-080c16ac78b0",
		'included_segments' => array('All'),
		'headings' => array("en" => $title),
		'contents' => $content,
		'android_group' => "FK_PRICING"
	);

	$fields = json_encode($fields);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Authorization: Basic MWE0ZThiZGEtOGU4OS00ZmI4LWIyNzItNWVmMGEzMGM5YjQ4'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}

function get_stateCode($state)
{
	$state_codes = array(
		"Andaman and Nicobar Islands" => "IN-AN",
		"Andaman & Nicobar Islands" => "IN-AN",
		"Andhra Pradesh" => "IN-AP",
		"Arunachal Pradesh" => "IN-AR",
		"Assam" => "IN-AS",
		"Bihar" => "IN-BR",
		"Chandigarh" => "IN-CH",
		"Chhattisgarh" => "IN-CT",
		"Daman & Diu" => "IN-DD",
		"Daman and Diu" => "IN-DD",
		"Delhi" => "IN-DL",
		"Dadra & Nagar Haveli" => "IN-DN",
		"Dadra and Nagar Haveli" => "IN-DN",
		"Dadra & Nagar Haveli & Daman & Diu" => "IN-DNDD",
		"Goa" => "IN-GA",
		"Gujarat" => "IN-GJ",
		"Himachal Pradesh" => "IN-HP",
		"Haryana" => "IN-HR",
		"Jharkhand" => "IN-JH",
		"Jammu & Kashmir" => "IN-JK",
		"Jammu and Kashmir" => "IN-JK",
		"Karnataka" => "IN-KA",
		"Kerala" => "IN-KL",
		"Ladakh" => "IN-LA",
		"Maharashtra" => "IN-MH",
		"Meghalaya" => "IN-ML",
		"Madhya Pradesh" => "IN-MP",
		"Manipur" => "IN-MN",
		"Meghalaya" => "IN-ML",
		"Mizoram" => "IN-MZ",
		"Nagaland" => "IN-NL",
		"Odisha" => "IN-OR",
		"Punjab" => "IN-PB",
		"Pondicherry" => "IN-PY",
		"Puducherry" => "IN-PY",
		"Rajasthan" => "IN-RJ",
		"Sikkim" => "IN-SK",
		"Tamil Nadu" => "IN-TN",
		"Tripura" => "IN-TR",
		"Telangana" => "IN-TS",
		"Uttar Pradesh" => "IN-UP",
		"Uttarakhand" => "IN-UT",
		"Uttrakhand" => "IN-UT",
		"West Bengal" => "IN-WB",
	);

	return $state_codes[$state];
}

function get_all_sms_client()
{
	global $db;
	$smsClient = $db->get(TBL_CLIENTS_SMS, NULL, array('smsClientId', 'smsClientName'));
	return $smsClient;
}

function get_all_email_client()
{
	global $db;
	$emailClient = $db->get(TBL_CLIENTS_EMAIL, NULL, array('emailClientId', 'emailClientName'));
	return $emailClient;
}

function get_all_whatsapp_client()
{
	global $db;
	$emailClient = $db->get(TBL_CLIENTS_WHATSAPP, NULL, array('whatsappClientId', 'whatsappClientName'));
	return $emailClient;
}

function get_all_firms()
{
	global $db;
	$getFirms = $db->get(TBL_FIRMS, NULL, array('firm_id', 'firm_name'));
	return $getFirms;
}

function get_master_template()
{
	global $db;
	$getFirms = $db->get(TBL_TEMPLATE_MASTER, NULL, array('templateId', 'templateName'));
	return $getFirms;
}

function get_all_table()
{
	global $db;
	$getFirms = $db->query('show tables');
	return $getFirms;
}

function get_all_field_table($tableName = Null)
{
	global $db;
	if (isset($tableName) && !empty($tableName)) {
		$getFirms = $db->query('SHOW columns FROM ' . $tableName);
		return $getFirms;
	}
}

function get_template_element($tableName = Null)
{
	global $db;
	$db->where('element_status', 'Enable');
	$getFirms = $db->get(TBL_TEMPLATE_ELEMENTS, NULL, array('element_id', 'element_table_name', 'element_field_name'));
	return $getFirms;
}

function get_template_id_by_name($templateName)
{
	global $db;

	$db->where('templateName', $templateName);
	return $db->getValue(TBL_TEMPLATE_MASTER, 'templateId');
}

function get_rate_card_details()
{

	$rate_details = [];

	$fee_type  = ['N/A' => '', 'All' => 'all', 'Fixed' => 'fixed', 'Percentage' => 'percentage', 'Days' => 'days'];

	$rate_details['fee_type'] = $fee_type;

	$fee_marketplace = ['Flipkart' => 'flipkart', 'Shopify' => 'shopify'];
	$rate_details['fee_marketplace'] = $fee_marketplace;

	$fee_column = [
		'N/A' => '', 'All' => 'all', 'Postpaid' => 'postpaid', 'Prepaid' => 'prepaid', 'Local' => 'local', 'Zonal' => 'zonal',
		'National' => 'national', 'Longterm-lt1' => 'longterm-lt1', 'Longterm-lt2' => 'longterm-lt2', 'regular' => 'Regular', 'Razorpay' => 'razorpay'
	];
	$rate_details['fee_column'] = $fee_column;

	$fee_fulfilmentType = ['N/A' => '', 'All' => 'all', 'NON_FBF' => 'NON_FBF', 'FBF' => 'FBF', 'Unsellable' => 'unsellable', 'Sellable' => 'sellable', 'Card' => 'card'];
	$rate_details['fee_fulfilmentType'] = $fee_fulfilmentType;

	$fee_tier = ['All' => 'all', 'Gold' => 'gold', 'Silver' => 'silver', 'Bronze' => 'bronze'];
	$rate_details['fee_tier'] = $fee_tier;

	$fee_name = [
		'N/A' => '', 'All' => 'all', 'ClosingFee' => 'closingFee', 'CollectionFee' => 'collectionFee', 'PlatformFee' => 'platformFee',
		'ReverseShippingFee' => 'reverseShippingFee', 'ShippingFee' => 'shippingFee', 'PickAndPackFee' => 'pickAndPackFee',
		'StorageFee' => 'storageFee', 'StorageRemovalFee' => 'storageRemovalFee', 'PaymentCycle' => 'paymentCycle', 'GST' => 'GST'
	];
	$rate_details['fee_name'] = $fee_name;

	return $rate_details;
}

function loadImage($url, $width, $height)
{
	return 'image.php?url=' . urlencode($url) .
		'&w=' . $width .
		'&h=' . $height;
}
