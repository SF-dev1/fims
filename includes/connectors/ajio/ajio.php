<?php

class ajio
{

	private $account;

	function __construct($account, $sandbox = false)
	{
		$this->account = $account;
	}

	function insertOrder($order)
	{
		global $db, $log;

		$data = array(
			"orderNo" => $order->orderNo,
			"orderDate" => $order->orderDate,
			"orderStatus" => $order->orderStatus,
			"purchaseOrderNumber" => $order->purchaseOrderNumber,
			"purchaseOrderDate" => $order->purchaseOrderDate,
			"sellerInvoiceNo" => $order->sellerInvoiceNo,
			"sellerInvoiceDate" => $order->sellerInvoiceDate,
			"invoiceValue" => $order->invoiceValue,
			"customerInvoiceNo" => $order->customerInvoiceNo,
			"customerInvoiceDate" => $order->customerInvoiceDate,
			"itemCode" => $order->itemCode,
			"hsn" => $order->hsn,
			"sellerSKU" => $order->sellerSKU,
			"styleCode" => $order->styleCode,
			"ean" => $order->ean,
			"description" => $order->description,
			"quantity" => $order->quantity,
			"shipmentId" => $order->shipmentId,
			"shipmentStatus" => $order->shipmentStatus,
			"fwdShipmentId" => $order->fwdShipmentId,
			"shipmentDate" => $order->shipmentDate,
			"carrier" => $order->carrier,
			"awbNumber" => $order->awbNumber,
			"shippedQuantity" => $order->shippedQuantity,
			"dispatchDate" => $order->dispatchDate,
			"cancelledQuantity" => $order->cancelledQuantity,
			"custCancelledQuantity" => $order->custCancelledQuantity,
			"sellerCancelledQuantity" => $order->sellerCancelledQuantity,
			"listingMrp" => $order->listingMrp,
			"sellerTd" => $order->sellerTd,
			"mrp" => $order->mrp,
			"basePrice" => $order->basePrice,
			"totalPrice" => $order->totalPrice,
			"cgstPercentage" => $order->cgstPercentage,
			"cgstAmount" => $order->cgstAmount,
			"sgstPercentage" => $order->sgstPercentage,
			"sgstAmount" => $order->sgstAmount,
			"igstPercentage" => $order->igstPercentage,
			"igstAmount" => $order->igstAmount,
			"totalValue" => $order->totalValue,
			"fulfillmentType" => $order->fulfillmentType,
			"createdDate" => $db->now(),
			"updatedDate" => $db->now()
		);

		$db->where('orderNo', $order->orderNo);
		$db->where('itemCode', $order->itemCode);
		if (!$db->has(TBL_AJ_ORDERS)) {
			if ($db->insert(TBL_AJ_ORDERS, $data)) {
				$return = array('type' => 'success', 'message' => 'Successfully inserted order.');
				$log->write(json_encode($return), 'ajio-orders-' . str_replace(' ', '-', strtolower($this->account->account_name)));
				return $return;
			} else {
				$return = array('type' => 'error', 'message' => 'Error inserting order.', 'error' => $db->getLastError());
				$log->write(json_encode($return), 'ajio-orders-' . str_replace(' ', '-', strtolower($this->account->account_name)));
				return $return;
			}
		} else {
			$return = array('type' => 'error', 'message' => 'Order already exist.');
			$log->write(json_encode($return), 'ajio-orders-' . str_replace(' ', '-', strtolower($this->account->account_name)));
			return $return;
		}
	}

	function insertReturn($order)
	{
		global $db, $log;

		$data = array(
			"returnOrderNo" => $order->returnOrderNo,
			"shipmentId" => $order->fwdShipmentId,
			"custOrderNo" => $order->custOrderNo,
			"itemCode" => $order->jioCode,
			"sellerSku" => $order->sellerSku,
			"returnCreateDate" => $order->returnCreateDate,
			"returnStatus" => $order->returnStatus,
			"returnQty" => $order->returnQty,
			"returnShipmentId" => $order->returnShipmentId,
			"returnCarrierName" => $order->returnCarrierName,
			"returnAwbNo" => $order->returnAwbNo,
			"return3plDeliveryStatus" => $order->return3plDeliveryStatus,
			"returnDeliveredDate" => $order->returnDeliveredDate,
			"deliveryChallanNo" => $order->deliveryChallanNo,
			"deliveryChallanDate" => $order->deliveryChallanDate,
			"deliveryChallanPostingSap" => $order->deliveryChallanPostingSap,
			"returnValue" => $order->returnValue,
			"qcCompletionDate" => $order->qcCompletionDate,
			"disposition" => $order->disposition,
			"qcReasonCoding" => $order->qcReasonCoding,
			"returnType" => $order->returnType,
			"retDocNo" => $order->retDocNo,
			"custReturnReason" => $order->custReturnReason,
			"creditNoteNo" => $order->creditNoteNo,
			"creditNoteGenerationDate" => $order->creditNoteGenerationDate,
			"creditNoteAcceptanceDate" => $order->creditNoteAcceptanceDate,
			"creditNoteValue" => $order->creditNoteValue,
			"creditNotePreTaxValue" => $order->creditNotePreTaxValue,
			"creditNoteTaxValue" => $order->creditNoteTaxValue,
			"creditNotePostingStatus" => $order->creditNotePostingStatus,
			"fulfillmentType" => $order->fulfillmentType
		);

		$db->where('returnOrderNo', $order->returnOrderNo);
		$db->where('itemCode', $order->jioCode);
		if (!$db->has(TBL_AJ_RETURNS)) {
			if ($db->insert(TBL_AJ_RETURNS, $data)) {
				$return = array('type' => 'success', 'message' => 'Successfully inserted order.');
				$log->write(json_encode($return), 'ajio-returns-' . str_replace(' ', '-', strtolower($this->account->account_name)));
				return $return;
			} else {
				$return = array('type' => 'error', 'message' => 'Error inserting order.', 'error' => $db->getLastError() . ' for Return Order No ' . $order->returnOrderNo);
				$log->write(json_encode($return), 'ajio-returns-' . str_replace(' ', '-', strtolower($this->account->account_name)));
				return $return;
			}
		} else {
			$return = array('type' => 'error', 'message' => 'Order already exist.', 'error' => 'Return Order No ' . $order->returnOrderNo);
			$log->write(json_encode($return), 'ajio-returns-' . str_replace(' ', '-', strtolower($this->account->account_name)));
			return $return;
		}
	}

	function insertSettlement($order)
	{
		global $db, $log;

		$data = array(
			"clearingDocNo" => $order->clearingDocNo,
			"clearingDate" => $order->clearingDate,
			"journeyType" => $order->journeyType,
			"expectedSettlementDate" => $order->expectedSettlementDate,
			"invoiceNumber" => $order->invoiceNumber,
			"custOrderNo" => $order->custOrderNo,
			"awbNumber" => $order->awbNumber,
			"value" => $order->value,
			"status" => $order->status,
		);

		$db->where('awbNumber', $order->awbNumber);
		$db->where('custOrderNo', $order->custOrderNo);
		$settlement = $db->objectBuilder()->getOne(TBL_AJ_PAYMENTS);
		if (!$settlement) {
			if ($db->insert(TBL_AJ_PAYMENTS, $data)) {
				$return = array('type' => 'success', 'message' => 'Successfully inserted payment.', 'data' => json_encode($data));
				$log->write(json_encode($return), 'ajio-payments-' . str_replace(' ', '-', strtolower($this->account->account_name)));
				return $return;
			} else {
				$return = array('type' => 'error', 'message' => 'Error inserting payment.', 'error' => $db->getLastError() . ' for Payment No ' . $order->custOrderNo);
				$log->write(json_encode($return), 'ajio-payments-' . str_replace(' ', '-', strtolower($this->account->account_name)));
				return $return;
			}
		} else if ($settlement && $settlement->status != "PAID") {
			$db->where('awbNumber', $order->awbNumber);
			$db->where('custOrderNo', $order->custOrderNo);
			if ($db->update(TBL_AJ_PAYMENTS, $data)) {
				$return = array('type' => 'success', 'message' => 'Successfully updated payment.', 'data' => json_encode($data));
				$log->write(json_encode($return), 'ajio-payments-' . str_replace(' ', '-', strtolower($this->account->account_name)));
				return $return;
			} else {
				$return = array('type' => 'error', 'message' => 'Error updating payment.', 'error' => $db->getLastError() . ' for Payment No ' . $order->custOrderNo);
				$log->write(json_encode($return), 'ajio-payments-' . str_replace(' ', '-', strtolower($this->account->account_name)));
				return $return;
			}
		} else {
			$return = array('type' => 'error', 'message' => 'Payment already exist.', 'error' => 'Payment No ' . $order->custOrderNo);
			$log->write(json_encode($return), 'ajio-payments-' . str_replace(' ', '-', strtolower($this->account->account_name)));
			return $return;
		}
	}
}
