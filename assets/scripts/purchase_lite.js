var PurchaseLite = function () {

	// Submit Form
	var submitForm = function (formData, $type) {
		$ret = "";
		$.ajax({
			url: "ajax_load.php?token=" + new Date().getTime(),
			cache: false,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			success: function (s) {
				$ret = $.parseJSON(s);
			},
			error: function (e) {
				alert('Error Processing your Request!!');
			}
		});
		return $ret;
	};

	var current_lineItem = 0;
	var lineitems = [0];
	var tabOrder = 6;

	var purchaseOrder_handleTable = function () {
		var table = $('#editable_purchase_orders');

		var oTable = table.dataTable({
			"lengthMenu": [
				[20, 50, 100, 200, 500, -1],
				[20, 50, 100, 200, 500, "All"] // change per page values here
			],
			// set the initial value
			"pageLength": 20,
			"language": {
				"lengthMenu": " _MENU_ records",
			},
			"bDestroy": true,
			"bSort": false,
			"ordering": false,
			"processing": true,
			"deferRender": true,
			// "serverSide": true,
			"ajax": {
				url: "ajax_load.php?action=purchase_orders&token=" + new Date().getTime(),
				type: "GET",
				cache: false,
			},
			"columnDefs": [
				{ "width": "10%", "targets": [0, 4, 5] },
			],
			"fnDrawCallback": function () {
				$('.dataTables_length select').select2({
					placeholder: "Select",
					allowClear: true
				});

				purchaseOrder_printPreview();
			},
		});

		// Delete draft and created PO's
		table.on('click', '.delete', function (e) {
			e.preventDefault();
			if (confirm("Are you sure to cancel this PO ?") === true) {
				var po_id = $(this).attr('class').split(' ').pop().split('_').pop();
				var formData = new FormData();
				formData.append('action', 'delete_po');
				formData.append('po_id', po_id);
				var s = submitForm(formData, "POST");
				oTable.api().ajax.reload();
				UIToastr.init(s.type, 'Purchase Order', s.msg);
				return;
			}
		});

		// Reload Purchase Orders
		$('#reload-purchase-orders').bind('click', function (e) {
			e.preventDefault();
			var el = jQuery(this).closest(".portlet").children(".portlet-body");
			App.blockUI({ target: el });
			oTable.api().ajax.reload();
			window.setTimeout(function () {
				App.unblockUI(el);
			}, 500);
		});
	};

	var purchaseOrder_handleSuppliers = function () {

		var s_options = '<option></option>';
		var selected = "";
		$.each(suppliers, function (k, v) {
			s_options += "<option " + selected + " value=" + v.party_id + ">" + v.party_name + "</option>";
		});
		$('.suppliers_list').append(s_options);

		// Initialize select2me
		$('.suppliers_list').select2({
			placeholder: "Select Supplier",
			allowClear: true
		});
	};

	var purchaseOrder_handleProducts = function () {
		var products_list = '<option></option>';
		var selected = "";
		$.each(all_parent_sku, function (k, v) {
			products_list += "<option " + selected + " value=" + v.key + ">" + v.value + "</option>";
		});
		$('.products_list').append(products_list);

		$('.products_list').select2({
			placeholder: "Product SKU",
			allowClear: true
		});
	};

	var purchaseOrder_lineItemClick = function (itemNumber) {
		$('.btn_add_' + itemNumber).on('click', function (e) {
			// $('#price_'+itemNumber).parent().removeClass('has-error');
			// $('#qty_'+itemNumber).parent().removeClass('has-error');
			// if($('#price_'+itemNumber).val() == "" && $('#qty_'+itemNumber).val() == ""){
			// 	$('#price_'+itemNumber).parent().addClass('has-error');
			// 	$('#qty_'+itemNumber).parent().addClass('has-error');
			// 	return;
			// }
			// if($('#price_'+itemNumber).val() == ""){
			// 	$('#price_'+itemNumber).parent().addClass('has-error');
			// 	return;
			// }
			// if($('#qty_'+itemNumber).val() == ""){
			// 	$('#qty_'+itemNumber).parent().addClass('has-error');
			// 	return;
			// }
			e.preventDefault();
			if ($('#add-purchase-order').validate().form()) {
				$(this).attr('disabled', true);
				current_lineItem = itemNumber + 1
				lineitems.push(current_lineItem);
				purchaseOrder_lineItem(current_lineItem);
			} else {
				return;
			}
		});

		$('.btn_minus_' + itemNumber).on('click', function () {
			if (confirm('Remove line item?')) {
				$('.lineitem_' + itemNumber).remove();
				if (itemNumber == current_lineItem)
					current_lineItem = current_lineItem - 1;

				lineitems.splice($.inArray(itemNumber, lineitems), 1);
				if (lineitems.length === parseInt('1')) {
					$('.btn_minus_' + lineitems[0]).attr('disabled', true);
				} else {
					$('.btn_minus_' + lineitems[0]).attr('disabled', false);
				}
				$('.btn_add_' + (current_lineItem)).attr('disabled', false);
			}
		});

		if (lineitems.length === parseInt('1')) {
			$('.btn_minus_' + lineitems[0]).attr('disabled', true);
		} else {
			$('.btn_minus_' + lineitems[0]).attr('disabled', false);
		}
	};

	var purchaseOrder_lineItem = function (itemNumber) {
		$content = '<div class="form-group lineitems lineitem_' + itemNumber + '">' +
			'<div class="col-md-5">' +
			'<select class="products_list_' + itemNumber + ' form-control" name="lineitems[' + itemNumber + '][sku]" id="sku_' + itemNumber + '" tabindex="' + (tabOrder++) + '" required></select>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control price" name="lineitems[' + itemNumber + '][price]" placeholder="Price" id="price_' + itemNumber + '" tabindex="' + (tabOrder++) + '" type="text" required>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control qty" name="lineitems[' + itemNumber + '][qty]" placeholder="Quantity" id="qty_' + itemNumber + '" tabindex="' + (tabOrder++) + '" type="text" required>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control amount" placeholder="Amount" readonly="true" type="text">' +
			'</div>' +
			'<div class="col-md-1">' +
			'<div class="spinner-buttons input-group-btn">' +
			'<button type="button" class="btn add_lineItem btn_add_' + itemNumber + '" tabindex="' + (tabOrder++) + '">' +
			'<i class="fa fa-plus"></i>' +
			'</button>' +
			'<button type="button" class="btn remove_lineItem btn_minus_' + itemNumber + '" tabindex="' + (tabOrder++) + '">' +
			'<i class="fa fa-trash"></i>' +
			'</button>' +
			'</div>' +
			'</div>' +
			'</div>';

		$('.lineitem_' + (itemNumber - 1)).after($content);

		var products_list = '<option></option>';
		var selected = "";
		$.each(all_parent_sku, function (k, v) {
			products_list += "<option " + selected + " value=" + v.key + ">" + v.value + "</option>";
		});
		$('.products_list_' + itemNumber).append(products_list);
		$('.products_list_' + itemNumber).select2({
			placeholder: "Product SKU",
			allowClear: true
		});

		$('.products_list_' + itemNumber).select2('open');
		purchaseOrder_lineItemClick(itemNumber);
		purchaseOrder_lineItemCalculation();

		$("#price_" + itemNumber).inputmask("decimal", {
			"rightAlign": false,
			"numericInput": true,
			"placeholder": "0",
			"greedy": false,
			onUnMask: function (maskedValue, unmaskedValue) {
				return unmaskedValue;
			}
		});

		$(".qty").inputmask({
			"mask": "9",
			"repeat": 5,
			"greedy": false
		});
	};

	var purchaseOrder_lineItemCalculation = function () {
		$('.price, .qty, .add_lineItem, .remove_lineItem').on('focusout change changeInput input', function () {
			lineitem = $(this).parent().parent().attr('class').split(' ').pop();
			if (lineitem == 'col-md-1')
				lineitem = $(this).parent().parent().parent().attr('class').split(' ').pop();
			price = $('.' + lineitem + ' .price').val();
			qty = $('.' + lineitem + ' .qty').val();

			$('.' + lineitem + ' .amount').val(price * qty);

			var sum = 0;
			$('.amount').each(function () {
				sum += Number($(this).val());
			});
			$('.total_amount').val(sum);

			var units = 0;
			$('.qty').each(function () {
				units += Number($(this).val());
			});
			$('.total_qty').val(units);
		});
	};

	var purchaseOrder_handleValidation = function () {
		var form = $('#add-purchase-order');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			errorPlacement: function (error, element) { // render error placement for each input type
				if (element.parent(".input-group").size() > 0) {
					error.insertAfter(element.parent(".input-group"));
				} else if (element.attr("data-error-container")) {
					error.appendTo(element.attr("data-error-container"));
				} else if (element.parents('.checkbox-inline').size() > 0) {
					error.appendTo(element.parents('.checkbox-inline').attr("data-error-container"));
				} else {
					error.insertAfter(element); // for other inputs, just perform default behavior
				}
			},

			invalidHandler: function (event, validator) { //display error alert on form submit   
				success.hide();
				error.show();
				// App.scrollTo(error, 0);
			},

			highlight: function (element) { // hightlight error inputs
				$(element)
					.closest('.form-group').addClass('has-error'); // set error class to the control group
			},

			unhighlight: function (element) { // revert the change done by hightlight
				$(element)
					.closest('.form-group').removeClass('has-error'); // set error class to the control group
			},

			success: function (label) {
				label
					.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			submitHandler: function (form) {
				// success.show();
				error.hide();
				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'add_purchase');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				$('.form-actions .btn', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');

				var s = submitForm(formData, "POST");
				if (s.type == 'success') {
					UIToastr.init('success', 'New Purchase', s.msg);
					$('.form-actions .btn', form).attr('disabled', false);
					$('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');

					window.setTimeout(function () {
						window.location.reload();
					}, 500);
					// $(form)[0].reset();
				} else if (s.type == 'error') {
					UIToastr.init('error', 'New Purchase', s.msg);
				} else {
					UIToastr.init('error', 'New Purchase', s.msg);
				}
			}
		});
	};

	return {
		init: function ($type) {
			if ($type == "new") {
				purchaseOrder_handleSuppliers();
				purchaseOrder_handleProducts();
				purchaseOrder_lineItem(0);
				purchaseOrder_handleValidation();
			}
		}
	}
}();