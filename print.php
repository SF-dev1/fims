<?php
include(dirname(__FILE__) . '/config.php');
?>
<?php
$type = $_REQUEST['type'];
if ($type == 'purchase_order') {
	$po_id = $_REQUEST['id'];
	$db->join(TBL_PARTIES . ' p', 'p.party_id=po.po_party_id', 'LEFT');
	$db->where('po_id', $po_id);
	$party = $db->getOne(TBL_PURCHASE_ORDER . ' po', 'p.party_id, p.party_name, po.createdDate');

	$db->where('po_id', $po_id);
	// $db->join(TBL_PRODUCTS_MASTER.' pm', '(CONCAT("pid-", pm.pid))=poi.item_id');
	$db->join(TBL_PRODUCTS_MASTER . ' pm', 'pm.pid=poi.item_id');
	$pos['items'] = $db->get(TBL_PURCHASE_ORDER_ITEMS . ' poi', null, 'poi.item_id, pm.sku as item_name, poi.item_price, poi.item_qty, poi.lot_no, poi.item_currency');
	$details = array_merge(array('po_id' => $po_id), $party, $pos);
?>
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
		<h4 class="modal-title">View Purchase Order</h4>
	</div>
	<div class="modal-body printable-content invoice">
		<div class="row invoice-logo">
			<div class="col-xs-6 invoice-logo-space">
				Purchase <small>Order</small><br />
				<div class="vendor_details">Vendor:
					<span> <?php echo $details['party_name']; ?></span>
				</div>
			</div>
			<div class="col-xs-6">
				<p>
					#<?php echo sprintf('PO_%06d', $details['po_id']); ?> / <?php echo date('d M Y', strtotime($details['createdDate'])); ?>
					<span class="barcode"><?php echo sprintf('PO_%06d', $details['po_id']); ?></span>
				</p>
			</div>
		</div>
		<hr />
		<div class="row">
			<div class="col-xs-12">
				<table class="table table-striped table-hover">
					<thead>
						<tr>
							<th>
								#
							</th>
							<th>
								Item
							</th>
							<th class="hidden-480">
								Quantity
							</th>
							<th class="hidden-480">
								Unit Cost
							</th>
							<th>
								Total
							</th>
							<th>
								Lot No.
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 1;
						$total = $total_qty = 0;
						foreach ($details['items'] as $item) {
							$currency_symbol = "&#8377;";
							if ($item['item_currency'] == "CNY")
								$currency_symbol = "&#165;";
						?>
							<tr>
								<td>
									<?php echo $i; ?>
								</td>
								<td>
									<?php echo $item['item_name']; ?>
								</td>
								<td class="hidden-480">
									<?php echo $sum_qty = $item['item_qty'];
									$total_qty += $sum_qty ?>
								</td>
								<td class="hidden-480">
									<?php echo $currency_symbol . $item['item_price']; ?>
								</td>
								<td>
									<?php echo $currency_symbol . $sum_total = (int)$item['item_qty'] * (float)$item['item_price'];
									$total += $sum_total; ?>
								</td>
								<td>
									<?php echo (empty($item['lot_no']) ? "No Lot No. Assigned" : $item['lot_no']); ?>
								</td>
							</tr>
						<?php
							$i++;
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-4">
				<!-- <div class="well">
						<address>
						<strong>Loop, Inc.</strong><br/>
						795 Park Ave, Suite 120<br/>
						San Francisco, CA 94107<br/>
						<abbr title="Phone">P:</abbr> (234) 145-1810 </address>
						<address>
						<strong>Full Name</strong><br/>
						<a href="mailto:#">first.last@email.com</a>
						</address>
					</div> -->
			</div>
			<div class="col-xs-8 invoice-block">
				<ul class="list-unstyled amounts">
					<!-- <li>
							<strong>Sub - Total amount:</strong> $9265
						</li>
						<li>
							<strong>Discount:</strong> 12.9%
						</li>
						<li>
							<strong>VAT:</strong> -----
						</li> -->
					<li>
						<strong>Total Qty:</strong> <?php echo $total_qty; ?> pcs
					</li>
					<li>
						<strong>Grand Total:</strong> <?php echo $currency_symbol . $total; ?>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-info hidden-print" onclick="javascript:window.print();">Print <i class="fa fa-print"></i></button>
		<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	</div>
<?php } ?>