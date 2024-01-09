<?php
// ------------------------------------------------------------------------------------------------
// uncomment the following line if you are getting 500 error messages
// 
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
// ------------------------------------------------------------------------------------------------

// create html view for orders
function create_order_view($order, $isReturn)
{
	if (!$isReturn) {
		// view for new orders

		// Total Selling Amount for order
		$amt = ($order->discountedPrice) ? ($order->discountedPrice) : ('0.00');

		// main content for table
		$content = '<div class="order-content">
			
			<div class="ordered-product-image"> <img src="' . IMAGE_URL . '/uploads/products/' . $order->thumb_image_url . '"
					onerror="this.onerror=null;this.src=\'https://via.placeholder.com/100x100\';"> </div>
			<div class="order-content-container">
				<div class="ordered-product-name"> ' . $order->productName . ' <span class="label label-success">Sylvi</span>
				</div>
				<div class="order-approval-container">
					<div class="ordered-approval"> Order Date: ' . date("M d, Y", strtotime($order->orderDate)) . ' </div>
					<div class="ordered-status"> Ajio Status: <span class="bold-font ordered-status-value">' .
			$order->orderStatus . '</span>
					</div>
				</div>
				<div class="order-complete-details">
					<div class="order-details">
						<div class="order-item-block-title">ORDER DETAIL</div>
						
						<div class="order-item-block">
							<div class="order-item-field order-item-padding">ID </div>
							<div class="order-item-field order-item-value">' . trim($order->meeshoOrderID) . '</div>
						</div>
						
						<div class="order-item-block">
							<div class="order-item-field order-item-padding">SKU </div>
							<div class="order-item-field order-item-value">' . $order->sku . '</div>
						</div>
					</div>
					<div class="order-price-qty order-price-qty-329351397250381100">
						<div class="order-item-block-title">PRICE &amp; QTY</div>
						<div class="order-item-block">
							<div class="order-item-field order-item-padding">Quantity </div>
							<div class="order-item-field order-item-value ">' . $order->quantity . ' unit</div>
						</div>
						<div class="order-item-block">
							<div class="order-item-field order-item-padding">Value </div>
							<div class="order-item-field order-item-value ">Rs. ' . $amt . '</div>
						</div>
						<div class="order-item-block">
							<div class="order-item-field order-item-padding">Shipping </div>
							<div class="order-item-field order-item-value ">Rs. 0.00</div>
						</div>
						<div class="order-item-block">
							<div class="order-item-field order-item-padding">Total </div>
							<div class="order-item-field order-item-value">Rs. ' . $amt . '</div>
						</div>
					</div>
					<div class="order-dispatch">
						<div class="order-item-block-title">DISPATCH</div>
						<div class="order-item-block">
							<div class="order-item-field order-item-padding">After</div>
							<div class="order-item-field order-item-value">' . date("M d, Y", strtotime($order->orderDate)) . '</div>
						</div>
						<div class="order-item-block"> <b>
								<div class="order-item-field order-item-padding">By</div>
								<div class="order-item-field order-item-value order-item-confirm-by-date">' .
			date("M d, Y", strtotime($order->orderDate)) . '
								</div>
							</b> </div>
					</div>
				</div>
			</div>
		</div>';

		// checkbox to select order
		$return['checkbox'] = "<input type='checkbox' class='checkboxes' data-group='grp_" . $order->orderId . "' value='" . $order->orderId . "' />";
		$return['content'] = preg_replace('/\>\s+\</m', '><', $content);
	} else {
		// View for return orders

		// total return amount
		$amt = ($order->returnValue) ? ($order->returnValue) : ('0.00');
		// main content for table
		$content = '<div class="order-content">
				<div class="bookmark"><a class="flag" data-itemid="' . $order->itemCode . '" href="#"><i
							class="fa fa-bookmark"></i></a>
				</div>
				<div class="ordered-product-image"> <img src="' . IMAGE_URL . '/uploads/products/' . $order->thumb_image_url . '"
						onerror="this.onerror=null;this.src=\'https://via.placeholder.com/100x100\';"> </div>
				<div class="order-content-container">
					<div class="ordered-product-name"> ' . $order->description . ' <span class="label label-success">Sylvi</span>
					</div>
					<div class="order-approval-container">
						<div class="ordered-approval"> Return Date: ' . date("M d, Y", strtotime($order->returnCreateDate)) . ' </div>
						<div class="ordered-status"> Return Status: <span class="bold-font ordered-status-value">' .
			$order->returnStatus . '</span>
						</div>
					</div>
					<div class="order-complete-details">
						<div class="order-details">
							<div class="order-item-block-title">RETURN DETAIL</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">Item ID </div>
								<div class="order-item-field order-item-value">' . trim($order->itemCode) . '</div>
							</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">Return ID </div>
								<div class="order-item-field order-item-value">' . trim($order->returnOrderNo) . '</div>
							</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">Order ID </div>
								<div class="order-item-field order-item-value">' . $order->custOrderNo . '</div>
							</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">SKU </div>
								<div class="order-item-field order-item-value">' . $order->sku . '</div>
							</div>
						</div>
						<div class="order-price-qty order-price-qty-329351397250381100">
							<div class="order-item-block-title">PRICE &amp; QTY</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">Quantity </div>
								<div class="order-item-field order-item-value ">' . $order->returnQty . ' unit</div>
							</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">Value </div>
								<div class="order-item-field order-item-value ">Rs. ' . $amt . '</div>
							</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">Shipping </div>
								<div class="order-item-field order-item-value ">Rs. 0.00</div>
							</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">Total </div>
								<div class="order-item-field order-item-value">Rs. ' . $amt . '</div>
							</div>
						</div>
						<div class="order-dispatch">
							<div class="order-item-block-title">DISPATCH</div>
							<div class="order-item-block">
								<div class="order-item-field order-item-padding">After</div>
								<div class="order-item-field order-item-value">' . date("M d, Y", strtotime($order->creditNoteGenerationDate)) . '</div>
							</div>
							<div class="order-item-block"> <b>
									<div class="order-item-field order-item-padding">By</div>
									<div class="order-item-field order-item-value order-item-confirm-by-date">' .
			date("M d, Y", strtotime($order->creditNoteAcceptanceDate)) . '
									</div>
								</b> </div>
						</div>
					</div>
				</div>
			</div>';

		// checkbox to select order
		$return['checkbox'] = "<input type='checkbox' class='checkboxes' data-group='grp_" . $order->orderId . "' value='" . $order->orderId . "' />";
		$return['content'] = preg_replace('/\>\s+\</m', '><', $content);
	}
	return $return;
}

// getting order count
function view_orders_count($order_types, $is_return = false)
{
	global $db;

	foreach ($order_types as $order_type) {
		// Type of order
		if ($is_return) {
			// return status
			switch ($order_type) {
				case 'start':
					$db->where("ajr.return3plDeliveryStatus", "Shipment Picked");
					break;
				case 'out_for_delivery':
					$db->where("ajr.return3plDeliveryStatus", "Shipment Out For Delivery");
					break;
				case 'received':
					$db->where("ajr.return3plDeliveryStatus", "Shipment Returned");
					break;
				case 'in_transit':
					$db->where("ajr.return3plDeliveryStatus", "Return Intransit");
					break;
				case 'delivered':
					$db->where("ajr.return3plDeliveryStatus", "Return Delivered");
					break;
			}
			$orders = $db->ObjectBuilder()->get(TBL_AJ_RETURNS . ' ajr', null, "COUNT(ajr.returnOrderNo) as orders_count");
		} else {
			// order status
			$db->where("ms.orderState", $order_type);
			$orders = $db->ObjectBuilder()->get(TBL_MS_ORDERS . ' ms', null, "COUNT(ms.meeshoOrderID) as orders_count");
		}
		$count[$order_type] = $orders[0]->orders_count;
	}

	return $count;
}
