<?php
$definer = array(
	//TABELS
	'TBL_AMS_INVOICES' => 'bas_ams_invoices',
	'TBL_AMS_PAYMENTS' => 'bas_ams_payments',
	'TBL_AMS_RATE_SLABS' => 'bas_ams_rate_slabs',
	'TBL_API_USERS' => 'bas_api_users',
	'TBL_AJ_ACCOUNTS' => 'bas_aj_accounts',
	'TBL_AJ_ORDERS' => 'bas_aj_orders',
	'TBL_AJ_RETURNS' => 'bas_aj_returns',
	'TBL_AJ_PAYMENTS' => 'bas_aj_payments',
	'TBL_AZ_ACCOUNTS' => 'bas_az_account',
	'TBL_AZ_CLAIMS' => 'bas_az_claims',
	'TBL_AZ_ORDERS' => 'bas_az_orders',
	'TBL_AZ_PAYMENTS' => 'bas_az_payments',
	'TBL_AZ_INCIDENTS' => 'bas_az_incidents',
	'TBL_AZ_RETURNS' => 'bas_az_returns',
	'TBL_AZ_REPORT' => 'bas_az_reports',
	'TBL_AZ_SETTLEMENTS' => 'bas_az_settlements',
	'TBL_AZ_WAREHOUSES' => 'bas_az_warehouses',
	'TBL_BRAND_AUTHORISATION' => 'bas_brand_authorisation',
	'TBL_CLIENTS_EMAIL' => 'bas_clients_email',
	'TBL_CLIENTS_SMS' => 'bas_clients_sms',
	'TBL_CLIENTS_WHATSAPP' => 'bas_clients_whatsapp',
	'TBL_FIRMS' => 'bas_firms',
	'TBL_FK_ACCOUNTS' => 'bas_fk_account',
	'TBL_FK_ACCOUNT_LOCATIONS' => 'bas_fk_account_locations',
	'TBL_FK_CLAIMS' => 'bas_fk_claims',
	'TBL_FK_INCIDENTS' => 'bas_fk_incidents',
	'TBL_FK_ORDERS' => 'bas_fk_orders',
	'TBL_FK_PAYMENTS' => 'bas_fk_payments',
	'TBL_FK_PROMOTIONS' => 'bas_fk_promotions',
	'TBL_FK_REPORTS' => 'bas_fk_reports',
	'TBL_FK_RETURNS' => 'bas_fk_returns',
	'TBL_FK_WAREHOUSES' => 'bas_fk_warehouses',
	'TBL_FULFILLMENT' => 'bas_fulfillment',
	'TBL_HSN_RATE' => 'bas_hsn_tax',
	'TBL_INVENTORY_LOG' => 'bas_inventory_log',
	'TBL_INVENTORY' => 'bas_inventory',
	'TBL_INVENTORY_BOX' => 'bas_inventory_box',
	'TBL_INVENTORY_CTN' => 'bas_inventory_carton',
	'TBL_INVENTORY_QC_ISSUES' => 'bas_inventory_qc_issues',
	'TBL_INVENTORY_STATE' => 'bas_inventory_state',
	'TBL_INVENTORY_COMPONENTS' => 'bas_inventory_components',
	'TBL_INVENTORY_LOCATIONS' => 'bas_inventory_locations',
	'TBL_LOCATION_ASSIGNMENTS' => 'bas_location_assignments',
	'TBL_LOGIN_ATTEMPTS' => 'bas_login_attempts',
	'TBL_MP_FEES' => 'bas_mp_fees',
	'TBL_MS_ACCOUNTS' => 'bas_ms_accounts',
	'TBL_MS_ORDERS' => 'bas_ms_orders',
	'TBL_MS_RETURNS' => 'bas_ms_returns',
	'TBL_OPTIONS' => 'bas_options',
	'TBL_PARTIES' => 'bas_parties',
	'TBL_PARTS_DETAILS' => 'bas_parts_details',
	'TBL_PAYMENT_GATEWAYS' => 'bas_payment_gateways',
	'TBL_PINCODE_SERVICEABILITY' => 'bas_pincode_serviceability',
	'TBL_PROCESS_LOG' => 'bas_process_log',
	'TBL_PRODUCTS_ALIAS' => 'bas_products_alias',
	'TBL_PRODUCTS_BRAND' => 'bas_products_brand',
	'TBL_PRODUCTS_CATEGORY' => 'bas_products_category',
	'TBL_PRODUCTS_COMBO' => 'bas_products_combo',
	'TBL_PRODUCTS_LOG' => 'bas_products_log',
	'TBL_PRODUCTS_MASTER' => 'bas_products_master',
	'TBL_PT_ACCOUNTS' => 'bas_pt_account',
	'TBL_PT_ORDERS' => 'bas_pt_orders',
	'TBL_PURCHASE_GRN_ITEMS' => 'bas_purchase_grn_items',
	'TBL_PURCHASE_GRN' => 'bas_purchase_grn',
	'TBL_PURCHASE_LOT' => 'bas_purchase_lot',
	'TBL_PURCHASE_LOT_LOCAL' => 'bas_purchase_lot_local',
	'TBL_PURCHASE_ORDER_ITEMS_LOT' => 'bas_purchase_order_item_lot',
	'TBL_PURCHASE_ORDER_ITEMS' => 'bas_purchase_order_items',
	'TBL_PURCHASE_ORDER' => 'bas_purchase_order',
	'TBL_PROTOTYPE_REVIEW' => 'bas_prototype_review',
	'TBL_REGISTERED_WARRANTY' => 'bas_registered_warranty',
	'TBL_RMA' => 'bas_rma',
	'TBL_RMA_PAYMENTS' => 'bas_rma_payments',
    'TBL_RMA_COMMENTS' => 'bas_rma_comments',
	// 'TBL_PURCHASE_PAYMENTS' => 'bas_purchase_payments',
	'TBL_SALE_ORDER_ITEMS' => 'bas_sale_order_items',
	'TBL_SALE_RETURN_ITEMS' => 'bas_sale_return_items',
	'TBL_SALE_ORDER' => 'bas_sale_order',
	'TBL_SALE_PAYMENTS' => 'bas_sale_payments',
	'TBL_SELLERS_LISTINGS' => 'bas_sellers_listings',
	'TBL_SP_ACCOUNTS' => 'bas_sp_account',
	'TBL_SP_ACCOUNTS_LOCATIONS' => 'bas_sp_account_locations',
	'TBL_SP_CLAIMS' => 'bas_sp_claims',
	'TBL_SP_COMMENTS' => 'bas_sp_comments',
	'TBL_SP_LOGISTIC' => 'bas_sp_logistic',
	'TBL_SP_ORDERS' => 'bas_sp_orders',
	'TBL_SP_PAYMENTS_GATEWAY' => 'bas_sp_payments_gateway',
	'TBL_SP_PRODUCTS' => 'bas_sp_products',
	'TBL_SP_REFUNDS' => 'bas_sp_refunds',
	'TBL_SP_RETURNS' => 'bas_sp_returns',
	'TBL_SP_REMITTANCE' => 'bas_sp_remittance',
	'TBL_SP_CHARGE' => 'bas_sp_charge',
	'TBL_TEMPLATE_EMAIL' => 'bas_template_email',
	'TBL_TEMPLATE_SMS' => 'bas_template_sms',
	'TBL_TEMPLATE_WHATSAPP' => 'bas_template_whatsapp',
	'TBL_TEMPLATE_MASTER' => 'bas_template_master',
	'TBL_TEMPLATE_ELEMENTS' => 'bas_template_element',
	'TBL_USERS' => 'bas_users',
	'TBL_USER_DEVICES' => 'bas_user_devices',
	'TBL_USER_LOCATIONS' => 'bas_user_locations',
	'TBL_TASKS' => 'bas_tasks',

	// MARKETPLACE PRODUCT URLS
	'FK_PRODUCT_URL' => 'https://dl.flipkart.com/dl/product/p/itme?pid=FSN',
	'AZ_PRODUCT_URL' => 'http://amazon.in/dp/ASIN',
);

foreach ($definer as $key => $value) {
	if (!defined($key))
		define($key, $value);
}
