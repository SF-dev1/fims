"use_strict";

var RMA = function () {

	var marketplace,
		pincode_servicable,
		in_warranty,
		is_returnable,
		product_condition,
		product_condition_group = [],
		estimateCart,
		pickup_type,
		accountId,
		currentRequest = null,
		is_queued = false,
		handler = "return",
		tab = "",
		shopify_tags = "",
		start_date = '',
		end_date = '';

	-1 !== window.location.href.indexOf("returns") && (handler = "return");

	function submitForm(formData, $type, $url) {
		if ($url == "")
			$url = "ajax_load.php?token=" + new Date().getTime();

		var $ret = "";
		$.ajax({
			url: $url,
			cache: true,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			showLoader: true,
			success: function (s) {
				if (s != "") {
					if ($url.includes('api'))
						$ret = s;
					else
						$ret = $.parseJSON(s);

					if ($ret.redirectUrl) {
						window.location.href = $ret.redirectUrl;
					}
				}
			},
			error: function (e) {
				UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
			}
		});
		return $ret;
	}

	function rmaCreate_handlePreFilled() {
		var order_id = App.getURLParameter('order_id');
		marketplace = App.getURLParameter('marketplace');

		if ((order_id != null && order_id != "") && (marketplace != null && marketplace != "")) {
			$('.search_order').click();
		}
	}

	function rmaCreate_handleSearch() {
		var form = $('#get_order_details');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit              
				success.hide();
				error.show();
				App.scrollTo(error, -200);
			},

			errorPlacement: function (error, element) { // render error placement for each input type
				if (element.parent(".input-group").size() > 0) {
					error.insertAfter(element.parent(".input-group"));
				} else if (element.attr("data-error-container")) {
					error.appendTo(element.attr("data-error-container"));
				} else if (element.parents('.radio-list').size() > 0) {
					error.appendTo(element.parents('.radio-list').attr("data-error-container"));
				} else if (element.parents('.radio-inline').size() > 0) {
					error.appendTo(element.parents('.radio-inline').attr("data-error-container"));
				} else if (element.parents('.checkbox-list').size() > 0) {
					error.appendTo(element.parents('.checkbox-list').attr("data-error-container"));
				} else if (element.parents('.checkbox-inline').size() > 0) {
					error.appendTo(element.parents('.checkbox-inline').attr("data-error-container"));
				} else {
					error.insertAfter(element); // for other inputs, just perform default behavior
				}
			},

			highlight: function (element) { // hightlight error inputs
				$(element).closest('.form-group').addClass('has-error'); // set error class to the control group
			},

			unhighlight: function (element) { // revert the change done by hightlight
				$(element).closest('.form-group').removeClass('has-error'); // set error class to the control group
			},

			success: function (label) {
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			submitHandler: function (form) {
				success.show();
				error.hide();
				// $('.multi_order_selection').html('').addClass('hide');
				$('.rma_order_details').addClass('hide');
				$('.search_order').prop('disabled', true).find('i').removeClass('fa-search').addClass('fa-sync fa-spin');
				// $('.estimateSection').addClass('hide');
				$('.estimateSectionDetails').html('');
				// $(form)[0].reset();

				// var formData = "action=get_orders_details&handler="+handler;
				var formData = $(form).serialize();
				window.setTimeout(function () {
					var s = submitForm(formData, 'GET', '');

					if (s.type == "success") {
						if (s.data.length > 1) {
							rmaCreate_handleMultiOrders(s.data);
						} else {
							rmaCreate_handleOrder(s.data[0]);
						}
					} else {
						UIToastr.init('error', 'RMA Order Search', s.msg);
					}

					$('.search_order').prop('disabled', false).find('i').removeClass('fa-sync fa-spin').addClass('fa-search');
				}, 100);
			}
		});

		// CHECK FOR THE PRIFILLED DATA TO SEACH
		rmaCreate_handlePreFilled();
	}

	function rmaCreate_handleMultiOrders(orders) {
		var html = "<h4 class='block'>Select one of the order from below orders:</h4><div class='table-responsive'><table class='table table-bordered table-striped'><thead><th>Order ID</th><th>Order Item ID</th><th>Title</th><th>SKU</th><th>Account</th><th>Invoice Date</th><th></th></thead><tbody>";
		$.each(orders, function (k, order) {
			html += "<tr><td><label for='" + order.orderItemId + "'>" + order.orderId + "</label></td><td><label for='" + order.orderItemId + "'>" + order.orderItemId + "</label></td><td><label for='" + order.orderItemId + "'>" + order.title + "</label></td><td><label for='" + order.orderItemId + "'>" + order.sku + "</label></td><td><label for='" + order.orderItemId + "'>" + order.accountName + "</label></td><td><label for='" + order.orderItemId + "'>" + order.invoiceDate + "</label></td><td><input class='order_options' type='radio' id='" + order.orderItemId + "' name='order_options' value='" + order.orderItemId + "'></td></tr>";
		});
		html += "</tbody><tfoot><td colspan='7' align='right'><button type='button' disabled class='btn btn-small btn-success rma_order'>Confirm & Proceed</button></td></tfoot></table></div>";

		$('.multi_order_selection').html(html).removeClass('hide');
		// App.initUniform($('.order_options'));
		// App.updateUniform($('.order_options'));
		// 
		// $('.multi_order_selection table tr').off().on('click', function() {
		// 	$(this).find('.order_options').prop('checked', true);
		// })

		var selected_order = "";
		$('.order_options').off().on('click', function () {
			var orderItemId = $(this).val();
			const k = Object.keys(orders).find(key => orders[key].orderItemId === orderItemId);
			selected_order = orders[k];
			$('.rma_order').prop('disabled', false);
		});

		$('.rma_order').off().on('click', function () {
			if (selected_order != "")
				rmaCreate_handleOrder(selected_order);
		});
	}

	function rmaCreate_handleOrder(selected_order) {
		$('.multi_order_selection').html('').addClass('hide');
		$('.rma_order_details').removeClass('hide');

		$('.marketplace').text(selected_order.marketplace);
		$('[name=marketplace]').val(selected_order.marketplace.toLowerCase());
		marketplace = (selected_order.marketplace).toLowerCase();
		$('.orderId').text(selected_order.orderId);
		$('[name=orderId]').val(selected_order.orderId);
		$('.orderItemId').text(selected_order.orderItemId);
		$('[name=orderItemId]').val(selected_order.orderItemId);
		accountId = selected_order.accountId;
		$('[name=accountId]').val(selected_order.accountId);
		$('.accountName').text(selected_order.accountName);
		$('.locationId').text(selected_order.location);
		$('.orderDate').text(selected_order.orderDate);
		$('.quantity').text(selected_order.quantity);
		$('.productTitle').text(selected_order.title);
		if (selected_order.mpLink)
			$('.mpId').html(selected_order.mpId + ' <a href="' + selected_order.mpLink + '" target="_blank"><i class="fa fa-external-link-alt"></a>');
		else
			$('.mpId').html('');
		$('.sku').text(selected_order.sku);
		$('.invoiceAmount').text(selected_order.invoiceAmount);
		$('.invoiceDate').text(selected_order.invoiceDate);
		$('[name=invoiceDate]').val(selected_order.invoiceDate);
		$('.deliveredDate').text(selected_order.deliveredDate);
		$('.warrantyPeriod').html('<a href="#" id="warrantyPeriod" data-type="number" data-pk="1" data-placement="left" data-placeholder="Required" data-original-title="Enter number of months"></a>');
		$('[name=warrantyPeriod]').val(selected_order.warrantyPeriod);
		$('.uidSelection').html(selected_order.uid_select);
		$('.issueSelection').html(selected_order.product_uids);
		if (marketplace != "amazon") {
			$('.invoiceNumber').text(selected_order.invoiceNumber);
			$('.customerName').text(selected_order.deliveryAddress.firstName + ' ' + selected_order.deliveryAddress.lastName);
			$('.current_address').html('<label>Name</label>' + selected_order.deliveryAddress.firstName + " " + selected_order.deliveryAddress.lastName + "<br /><label>Address Line 1:</label> " + selected_order.deliveryAddress.addressLine1 + "<br /><label>Address Line 2:</label> " + selected_order.deliveryAddress.addressLine2 + "<br /><label>Landmark:</label> " + selected_order.deliveryAddress.landmark + "<br /><label>City:</label> " + selected_order.deliveryAddress.city + "<br /><label>State:</label> " + selected_order.deliveryAddress.state + "<br /><label>Pincode:</label> " + selected_order.deliveryAddress.pinCode + " <button class='btn btn-info btn-xs btn-pincode-check primary-pincode-check hide' disabled data-orderid='" + selected_order.lpOrderId + "' type='button'><i class='fa'></i> Check Servicability!</button>");
			$('[name="mobile_number"], [name="whatsapp_number"]').val(selected_order.deliveryAddress.contactNumber);
			$(".return_address_name").val(selected_order.deliveryAddress.firstName + " " + selected_order.deliveryAddress.lastName);
			$(".return_address_1").val(selected_order.deliveryAddress.addressLine1);
			$(".return_address_2").val(selected_order.deliveryAddress.addressLine2);
			$(".return_landmark").val(selected_order.deliveryAddress.landmark);
			$(".return_pincode").val(selected_order.deliveryAddress.pinCode);
			// $(".return_pincode").inputmask("setvalue", selected_order.deliveryAddress.pincode);
			$(".return_city").val(selected_order.deliveryAddress.city);
			$(".return_state").val(selected_order.deliveryAddress.state);
		}

		$('.return_reason').select2();

		$('#free_pickup').prop('disabled', true);
		is_returnable = selected_order.is_returnable;
		if (marketplace == "shopify") {
			$('#free_pickup').prop('disabled', false);
			$('[name=email_address]').val(selected_order.deliveryAddress.email);
			$('.is-returnable-block').removeClass('hide');
			if (is_returnable)
				$('.is-returnable-block .help-block-content').addClass('label-success').removeClass('label-danger').text('Yes');
			else
				$('.is-returnable-block .help-block-content').addClass('label-danger').removeClass('label-success').text('No');
		}
		estimateCart = Array();

		if (selected_order.warrantyPeriod) {
			$('.warrantyPeriod').text(selected_order.warrantyPeriod);
			$('.warrantyExpiry').text(selected_order.warrantyExpiry);
			in_warranty = selected_order.warrantyActive;
			$('.inWarranty').addClass('label label-danger').text('Expired');
			$('#free_pickup').prop('disable');
			if (in_warranty)
				$('.inWarranty').addClass('label-success').removeClass('label-danger').text('Active');
		} else {
			$('#warrantyPeriod').editable({
				inputclass: 'form-control',
				showbuttons: 'bottom',
				validate: function (value) {
					if ($.trim(value) == '') return 'This field is required';
					else {
						var invDate = selected_order.invoiceDate
						var warrantyExpiry = moment(invDate, "YYYY-MM-DD").add(parseInt(value), 'months').format('YYYY-MM-DD');
						$('.warrantyExpiry').text(warrantyExpiry);

						in_warranty = moment().isBefore(warrantyExpiry);
						$('.inWarranty').addClass('label label-danger').text('Expired');
						$('#free_pickup').prop('disable');
						if (in_warranty)
							$('.inWarranty').addClass('label-success').removeClass('label-danger').text('Active');

						$('[name=warrantyPeriod]').val(parseInt(value));

						App.unblockUI('#rmaCreation');
						if (jQuery().pulsate) {
							jQuery('.warrantyPeriod').pulsate({
								repeat: false
							});
						}
					}
				}
			});
		}

		$('#uidSelection').editable({
			inputclass: 'form-control',
			showbuttons: 'bottom',
			validate: function (value) {
				if ($.trim(value) == '') return 'This field is required';
				else {
					// var invDate = selected_order.invoiceDate
					// var warrantyExpiry = moment(invDate, "YYYY-MM-DD").add(parseInt(value), 'months').format('YYYY-MM-DD');
					// $('.warrantyExpiry').text(warrantyExpiry);
					//
					// in_warranty = moment().isBefore(warrantyExpiry);
					// $('.inWarranty').addClass('label label-danger').text('Expired');
					// $('#free_pickup').prop('disable');
					// if (in_warranty)
					// 	$('.inWarranty').addClass('label-success').removeClass('label-danger').text('Active');
					//
					$('#uidSelection').val(value);

					App.unblockUI('#rmaCreation');
					if (jQuery().pulsate) {
						jQuery('.uidSelection').pulsate({
							repeat: false
						});
					}
				}
			}
		});

		$("body").on('click', '.uid', function () {
			var uid = $('#uidSelection').val();
			var orderKey = $('.orderItemId').text();
			var marketPlace = $('[name=\'marketplace\']').val();
			var warrantyPeriod = $('[name=\'warrantyPeriod\']').val();
			// console.log(orderKey);
			var formData = new FormData();
			formData.set('action', 'get_ui_details');
			formData.set('uid', uid);
			formData.set('orderKey', orderKey);
			formData.set('marketPlace', marketPlace);
			formData.set('warrantyPeriod', warrantyPeriod);
			var s = submitForm(formData, 'POST', '');

			if (s.type == "success" || s.type == "error") {
				if (s.type == "success") {
					var order = s.data;
					rmaCreate_handleOrder(order[0]);
				} else {
					UIToastr.init(s.type, 'UID Update', s.msg);
				}
			}
		})

		$('body').on('change', '.product-uid', function () {
			var uid = $(this).val();
			var id = "#fieldset_" + uid + "_div";
			if ($(this).prop("checked") == true) {
				$(id).removeClass('hide');
				$(id + " .issue_checkboxes").attr('disabled', false).attr('required', true);
			} else {
				$(id).addClass('hide');
				$(id + " .issue_checkboxes").prop('checked', false).attr('disabled', true).attr('required', false).trigger('click');
				$(id + " .issue_textbox").addClass('hide');
			}
			App.updateUniform($(id + " .issue_checkboxes"));
		});

		$("body").on('change', '.uidSelection', function () {
			group = $(this).val();

			// $('.rma_pid_fieldset').addClass('hide');
			$('.rma_pid_fieldset .radio').attr('disabled', true);
			$("[name='pickup_type'][value='self_ship']").prop("checked", true);
			// rmaCreate_handleEstimate([], 'remove_all');
			$('.fieldset_' + group).removeClass('hide');
			$('[name="productCondition"]').val('damaged');

			$('.product_condition_list_group_' + group + ' .radio').attr('disabled', false);
			if (!is_returnable)
				$('.product_condition_list_group_' + group + ' .product_condition_saleable, .product_condition_list_group_' + group + ' .product_condition_wrong').attr('disabled', true);
			App.updateUniform($('[name="pickup_type"]'));
			App.updateUniform($('.product_condition_list_group_' + group + ' .radio'));
		});

		$("body").on('change', '#rmaCreation .product_condition_list .radio', function () {
			group = $(this).data('group');
			product_condition = $(this).val();
			$('[name="productCondition"]').val(product_condition);
			// if (typeof(product_condition_group[group]) == 'undefined')
			// 	product_condition_group = [];
			product_condition_group[group] = $(this).val();
			// $(".issue_product_list_group_"+group+", .product_rto_claim_details").addClass('hide');
			$(".issue_product_list_group_" + group + " .checkboxes, .issue_product_list_group_" + group + " .radio").attr('required', false);
			$(".issue_checkboxes_action").prop("checked", false);
			$('[name="rmaAction"]').val("");

			// RETURN & REPLACEMENT
			$('.return_replace_product_list_group_' + group).addClass('hide')
			$('.return_replace_product_list_group_' + group + ' .radio').attr('disabled', true).attr('required', false);
			$('.issue_product_list_group_' + group + ' .return_reason').prop('required', false);
			// if (product_condition == "wrong"){
			// 	$('.return_replace_product_list_group_'+group).removeClass('hide')
			// 	$('.return_replace_product_list_group_'+group+' .radio').attr('disabled', false).attr('required', true);
			// }

			App.updateUniform($('.issue_checkboxes'));
			App.updateUniform($('.issue_checkboxes_action'));
			$("." + product_condition + "_product_list_group_" + group).removeClass('hide');
			$("." + product_condition + "_product_list_group_" + group + " .checkboxes, ." + product_condition + "_product_list_group_" + group + " .radio").attr('disabled', false).attr('required', true);
			App.updateUniform($('.' + product_condition + '_product_list_group_' + group + ' .checkboxes, .' + product_condition + '_product_list_group_' + group + ' .radio'));
		});

		if (marketplace == "shopify" && (is_returnable || in_warranty)) {
			if (selected_order.warrantyPeriod) {
				$("[name='pickup_type']").prop("disabled", false);
				$("[name='pickup_type'][value=free_pickup]").prop("disabled", false);
			} else {
				$("[name='pickup_type']").prop('disabled', false);
				$("[name='pickup_type'][value=paid_pickup]").prop("disabled", true);
			}
		} else {
			$("[name='pickup_type']").prop("disabled", false);
			$("[name='pickup_type'][value=free_pickup]").prop("disabled", true);
		}

		$('body').on('click', '#return_address', function () {
			var original_state = $('.alt-pincode-check').attr('disabled');
			if (!$('#return_address').prop('checked')) {
				$('#return_address').val("");
				$('.return_address, [name=return_address_confirm]').parent().closest('div.form-group').addClass('hide');
				$('[name=return_address_confirm]').attr('required', false);
				$('.return_address_details').removeClass('hide');
				$('.primary-pincode-check').addClass('hide');
				$('.pickup-address-form').val('').attr('required', true).prop('readonly', false);
				$('.alt-pincode-check').attr('disabled', false);
				$('.return_state').val('').prop('readonly', true);
				$('.return_pincode').prop('readonly', false).inputmask({
					"mask": "999999",
					"keepStatic": true
				});
				if (selected_order.marketplace != "Amazon")
					$(".return_address_name").val(selected_order.deliveryAddress.firstName + " " + selected_order.deliveryAddress.lastName);
			} else {
				$('#return_address').val("1");
				$('.return_address, [name=return_address_confirm]').parent().closest('div.form-group').removeClass('hide');
				$('.return_address_details').addClass('hide');
				$('.pickup-address-form').not('.return_address_2, .return_landmark').attr('required', false).prop('readonly', true);
				$('.return_landmark').attr('required', false).prop('readonly', true);
				if ($('[name=pickup_type]:checked').val() != "self_ship")
					$('.primary-pincode-check').removeClass('hide');
				if (selected_order.marketplace != "Amazon") {
					$('[name=return_address]').attr('required', true);
					$(".return_address_name").val(selected_order.deliveryAddress.firstName + " " + selected_order.deliveryAddress.lastName);
					$(".return_address_1").val(selected_order.deliveryAddress.addressLine1);
					$(".return_address_2").val(selected_order.deliveryAddress.addressLine2);
					$(".return_landmark").val(selected_order.deliveryAddress.landmark);
					$(".return_pincode").val(selected_order.deliveryAddress.pinCode);
					$(".return_city").val(selected_order.deliveryAddress.city);
					$(".return_state").val(selected_order.deliveryAddress.state);
				}
				$('.return_pincode').prop('readonly', true).inputmask('remove');
			}
			App.updateUniform($('#return_address'));
		});

		$('body').on('change', '.qc_enable', function () {
			$('[name=pincode_servicable]').val('');
			$('.btn-pincode-check').attr('disabled', false).removeClass('btn-success').addClass('btn-info').html('<i class="fa"></i> Check Servicability!');
			$('.pincode_servicability_group i').removeClass('fa-check');
		});

		$('body').on('click', '.issue_checkboxes', function () {
			$this = $(this);
			var checked = $this.prop('checked');
			var uid = $this.data('group');
			var part = $this.data('partname');
			var quantity_editable = false;
			if (part == "Button")
				quantity_editable = true;
			var part_value = 0;
			if (in_warranty)
				part_value = $this.data('inwarrantyprice');
			else
				part_value = $this.data('outwarrantyprice');

			var estimate_product = {
				'uid': uid,
				'part': part,
				'quantity': '1',
				'quantity_editable': quantity_editable,
				'price': part_value
			}

			if (checked) {
				$(".form_product_uid_error_" + uid).text("");
				rmaCreate_handleEstimate(estimate_product, 'add');
			}
			else
				rmaCreate_handleEstimate(estimate_product, 'remove');
		});

		$('body').on('click', '.other_issue', function () {
			var textbox = $(this).data('textbox');
			if ($(this).is(':checked'))
				$(textbox).removeClass('hide').attr('required', true).prop('disabled', false);
			else
				$(textbox).addClass('hide').attr('required', false).prop('disabled', true);

			console.log(textbox);
			return;
		});

		$('body').on('click', '.issue_checkboxes_action', function () {
			var action = $(this).val();
			$('[name="rmaAction"]').val(action);
			if (action == "replace") {
				$("[name='pickup_type'][value=free_pickup]").prop("checked", true).trigger('change');
				$('.return_replacement_product_list').removeClass('hide');
				$('.return_replacement_product_list .form-control').prop('required', true);
			} else {
				$("[name='pickup_type'][value=self_ship]").prop("checked", true).trigger('change');
				$('.return_replacement_product_list').addClass('hide');
				$('.return_replacement_product_list .form-control').prop('required', false);
			}
			App.updateUniform($('.issue_checkboxes_action'));
		});

		$('body').on('change', '[name="pickup_type"]', function () {
			var estimate_product = {
				'uid': 'Shipping',
				'part': 'Postage & Packaging',
				'quantity': '1',
				'quantity_editable': false,
				'price': 180
			}
			var pickup_type = $(this).val();
			if (pickup_type == "self_ship") {
				$('.label-address').text('Return Address');
				$('[name=pincode_servicable]').attr('required', false);
				rmaCreate_handleEstimate(estimate_product, 'remove');
				$('.primary-pincode-check').prop('disabled', true).addClass('hide');
			} else {
				// $('.pincode_servicability_details').removeClass('hide');
				$('.label-address').text('Return & Pickup Address');
				$('[name=pincode_servicable]').attr('required', true);
				if (pickup_type == "free_pickup") {
					rmaCreate_handleEstimate(estimate_product, 'remove');
					estimate_product.price = 0;
				}
				else {
					rmaCreate_handleEstimate(estimate_product, 'remove');
					estimate_product.price = 180;
				}
				rmaCreate_handleEstimate(estimate_product, 'add');
				$('.primary-pincode-check').prop('disabled', false).removeClass('hide');
			}
			App.updateUniform($('[name="pickup_type"]'));
		});

		$('body').on('click', '#default_to_wa', function () {
			$this = $(this);
			App.updateUniform($('#default_to_wa'));
			var checked = $this.prop('checked');
			if (checked) {
				$('input[name=whatsapp_number]').val($('input[name=mobile_number]').val()).prop('readonly', true).prop('required', false);
			}
			else
				$('input[name=whatsapp_number]').val('').prop('readonly', false).prop('required', true);
		});

		if (selected_order.marketplace == "Amazon") {
			$('.invoiceNumber').parent().addClass('hide');
			$('.customer_address').addClass('hide');
			$('.return_address_details').removeClass('hide');
			setTimeout(function () {
				$('#return_address').attr('checked', false).trigger('click');
			}, 150);
		}

		rmaCreate_handlePicodeServiceability();
		$('.return_pincode').inputmask({
			"mask": "999999"
		});
		$('.rma-tooltip').tooltip();
		App.initUniform();
		if (typeof in_warranty === "undefined") {
			App.blockUI({ target: '#rmaCreation' });
			if (jQuery().pulsate) {
				jQuery('.warrantyPeriod').pulsate({
					color: "#bf1c56"
				});
			}
		}
	}

	function rmaCreate_handleValidation() {
		// var customValid = true;
		var form = $('#raise_rma_request');
		// var error = $('.alert-danger', form);
		// var success = $('.alert-success', form);

		$("[name='whatsapp_number']").inputmask({
			"mask": "9999999999",
		});
		$("[name='mobile_number']").inputmask({
			"mask": "9999999999",
			onKeyDown: function (buffer, opts) {
				$('input[name=whatsapp_number]').val($('input[name=mobile_number]').val());
			}
		});

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit              
				// success.hide();
				// error.show();
				// App.scrollTo(error, -200);
			},

			highlight: function (element) { // hightlight error inputs
				$(element).closest('.form-group').addClass('has-error'); // set error class to the control group
			},

			unhighlight: function (element) { // revert the change done by hightlight
				$(element).closest('.form-group').removeClass('has-error'); // set error class to the control group
			},

			success: function (label) {
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) { // render error placement for each input type
				if (element.parent(".input-group").size() > 0) {
					error.insertAfter(element.parent(".input-group"));
				} else if (element.attr("data-error-container")) {
					error.appendTo(element.attr("data-error-container"));
				} else if (element.parents('.radio-list').size() > 0) {
					error.appendTo(element.parents('.radio-list').attr("data-error-container"));
				} else if (element.parents('.radio-inline').size() > 0) {
					error.appendTo(element.parents('.radio-inline').attr("data-error-container"));
				} else if (element.parents('.checkbox-list').size() > 0) {
					error.appendTo(element.parents('.checkbox-list').attr("data-error-container"));
				} else if (element.parents('.checkbox-inline').size() > 0) {
					error.appendTo(element.parents('.checkbox-inline').attr("data-error-container"));
				} else {
					error.insertAfter(element); // for other inputs, just perform default behavior
				}
			},

			submitHandler: function (form) {
				// customValid = customerInfoValid();
				// customReturnValid = customerReturnInfoValid();
				// error1.hide();
				// if (customValid && customReturnValid) {
				$('.form-actions .btn-submit', $(form)).attr('disabled', true);
				$('.form-actions .btn-submit i', $(form)).addClass('fa fa-sync fa-spin');

				window.setTimeout(function () {
					var other_data = $(form).serializeArray();
					console.log(other_data);
					var formData = new FormData();
					formData.set('action', 'create_rma');

					$.each(other_data, function (key, input) {
						var selectedUids = [];
						var selectedIssues = [];
						if (input.name.indexOf("uids") != -1) {
							var j = 0;
							$("input[name='" + input.name + "']:checked").each(function () {
								formData.set(input.name + '[' + j++ + ']', $(this).val());
							});
						} else if (input.name.indexOf("damaged") != -1) {
							var i = 0;
							$("input[name='" + input.name + "']:checked").each(function () {
								formData.set(input.name + '[' + i + ']', $(this).val());
								i++;
							});
						} else {
							formData.set(input.name, input.value);
						}
					});

					var s = submitForm(formData, "POST", '');
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'RMA Creation', s.message);
						if (s.type == "success") {
							var data = s.data;
							$('.rma_order_details').addClass('hide');
							$('.confirm_rma_order_details').removeClass('hide');
							$('.rmaCreateLoading').removeAttr('style').find('.checkmark__check').removeClass('hide');

							var timeout = 100;
							// GENERATE PAYMENT LINK
							if (data.paymentRequest) {
								$('.rmaId').text(data.rmaNumber);
								$('.payment_status').removeClass('hide');
								var paymentFormData = new FormData();
								paymentFormData.set('action', 'create_payment_link');
								paymentFormData.set('rmaId', data.rmaId);
								timeout += 1200;
								window.setTimeout(function () {
									var pr = submitForm(paymentFormData, "POST", '');
									if (pr.type == "success" || pr.type == "error") {
										UIToastr.init(pr.type, 'RMA Payment Link', pr.message);
										if (pr.type == "success") {
											$('.payment_status .loadingmark').removeAttr('style').addClass('checkmark').find('.checkmark__check').removeClass('hide');
										} else {
											$('.payment_status .loadingmark').removeAttr('style').addClass('crossmark').find('.checkmark__cross').removeClass('hide');
										}
										$('.payment_status p').text(pr.message);
									}
								}, timeout);
							}

							// GENERATE PICKUP
							if (data.pickupRequest) {
								$('.rmaId').text(data.rmaNumber);
								$('.pickup_status').removeClass('hide');
								var pickupFormData = new FormData();
								pickupFormData.set('action', 'create_pickup');
								pickupFormData.set('rmaId', data.rmaId);
								window.setTimeout(function () {
									var s = submitForm(pickupFormData, "POST", '');
								}, timeout);
							}

							// // FREE PICKUP
							// if (data.returnId){
							// 	$('.rmaId').text(data.returnId);
							// 	$('.pickup_status').removeClass('hide');
							// 	$('.pickup_status p').text(s.message);
							// 	UIToastr.init(s.type, 'RMA Creation', s.message);
							// 	$('.pickup_status .loadingmark').removeAttr('style').addClass('checkmark').find('.checkmark__check').removeClass('hide');
							// }

							// SEND NOTIFICATION MSG'S
							window.setTimeout(function () {
								rmaCreate_handleNotification(data.rmaId, data.returnId);
							}, timeout);

							// window.setTimeout(function(){
							// 	location.reload(true);
							// }, 10000);
						}
					} else {
						UIToastr.init('error', 'Add User', "Error processing request. Please try again later.");
					}
					$('.form-actions .btn-submit', $(form)).attr('disabled', false);
					$('.form-actions .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
				}, 100);
				// }
			}
		});
	}

	function rmaCreate_handleNotification(rmaId, returnId) {
		$('.notification_status').removeClass('hide');
		var formData = new FormData();
		formData.set('action', 'send_notification_message');
		if (rmaId != "") {
			formData.set('rmaId', rmaId);
		} else {
			formData.set('returnId', returnId);
		}
		window.setTimeout(function () {
			var s = submitForm(formData, "POST", "");
			if (s.type == "success" || s.type == "error") {
				UIToastr.init(s.type, 'RMA Notification', s.message);
				$.each(s.data, function (mode, status) {
					if (status.sent)
						$('.' + mode + '_notification_status .loadingmark').removeAttr('style').addClass('checkmark').find('.checkmark__check').removeClass('hide');
					else
						$('.' + mode + '_notification_status .loadingmark').removeAttr('style').addClass('crossmark').find('.checkmark__cross').removeClass('hide');

					$('.' + mode + '_notification_status .payment_status p').text(status.message);
				});
			}
		}, 100);
	}

	function rmaCreate_handleEstimate(part_object, event, value = null) {
		if (event == "add") {
			estimateCart.push(part_object);
		}

		if (event == "remove") {
			let find = estimateCart.findIndex(el => (el.uid === part_object.uid && el.part === part_object.part));
			if (find >= 0)
				estimateCart.splice(find, 1);
		}

		if (event == "remove_all") {
			estimateCart = part_object;
			// $(estimateCart).each(function(k, item){
			// 	if (item.uid == part_object.uid){
			// 		estimateCart.splice(k, 1);
			// 	}
			// });
		}

		if (event == "remove_specific") {
			estimateValue = $('[name=estimate]').val();
			if (estimateValue) {
				estimateCart = JSON.parse($('[name=estimate]').val());
				$(estimateCart).each(function (k, item) {
					if (item.uid == value) {
						estimateCart.splice(k);
					}
				});
			}
		}

		var html = "<table class='table table-bordered table-striped'><thead><tr><th>UID</th><th>Part Name</th><th>Quanity</th><th>Part Value</th><th>Part Total</th></tr></thead><tbody>";
		var total = 0;
		$(estimateCart).each(function (k, item) {
			html += "<tr>";
			html += "<td>" + item.uid + "</td>";
			html += "<td>" + item.part + "</td>";
			if (item.quantity_editable)
				html += "<td><span class='edit_qty'>" + item.quantity + "</span> <i class='fa fa-pen editable' data-group='" + item.uid + "'></i></td>";
			else
				html += "<td>" + item.quantity + "</td>";
			html += "<td>" + item.price + "</td>";
			html += "<td>" + item.quantity * item.price + "</td>";
			total += item.quantity * item.price;
			html += "</tr>";
		});
		html += "<tr><th colspan='4'>Total Estimate</th><th>" + total + "</th></tr></tbody></table>";
		$('.estimateSectionDetails').html(html);
		$('[name=estimate]').val(JSON.stringify(estimateCart));
		if (estimateCart.length == 0) {
			$('.estimateSection').addClass('hide');
		} else {
			$('.estimateSection').removeClass('hide');
		}

		$('.editable').off().on('click', function () {
			$this = $(this);
			var uid = $this.data('group');
			var td = $this.parent('td');
			var qty_span = $this.parent('td').find('.edit_qty');
			var current_qty = qty_span.val();

			td.html('<input type="number" class="new_qty" min="1" />&nbsp;<button type="button" class="btn btn-success btn-xs update_qty"><i class="fa fa-check"></i></button>&nbsp;<button type="button" class="btn btn-danger btn-xs"><i class="fa fa-ban"></i></button>');

			$('.update_qty').off().on('click', function () {
				var new_qty = $(this).prev('.new_qty').val();
				let find = estimateCart.findIndex(el => (el.uid === uid && el.part === 'Button'));
				estimateCart[find]['quantity'] = new_qty;
				rmaCreate_handleEstimate('', 'update');
				td.html = "<td><span class='edit_qty'>" + new_qty + "</span> <i class='fa fa-pen editable'></i></td>";
			});

		});
	}

	function rmaCreate_handlePicodeServiceability() {
		$('.return_pincode').off().on('blur', function () {
			if (pincode_servicable != $(this).inputmask('unmaskedvalue')) {
				$('[name=pincode_servicable]').val('');
			} else {
				$('[name=pincode_servicable]').val(1);
			}
		});

		$('.btn-pincode-check').off().on('click', function () {
			if (typeof (accountId) == 'undefined') {
				UIToastr.init('error', 'Pickup Servicability', 'Logistic Partner not configured.');
				return false;
			}
			var is_primary = $(this).hasClass('primary-pincode-check');
			var button = $('.primary-pincode-check');
			if (!is_primary)
				button = $('.alt-pincode-check');
			button.removeClass('btn-danger').addClass('btn-info');
			var pincode = $('.return_pincode').inputmask('unmaskedvalue');
			var qc_enabled = $('.qc_enable').is(':checked');
			var order_id = $(this).data('orderid');
			var alt_address = !$('#return_address').prop('checked');
			if (pincode.length == 6) {
				// $('.return_pincode').attr('readonly', true);
				if (is_primary)
					button.attr('disabled', true).find('i').addClass('fa-sync fa-spin').removeClass('fa-check fa-times');
				else {
					button.attr('disabled', true);
					// $('.pincode_servicability_group').removeClass('input-group');
					$('.pincode_servicability_group i').addClass('fa-sync fa-spin').removeClass('fa-check fa-times');
					$('.pincode_servicability_group .help-block').text("");
					$('.pincode_servicable .help-block').text("");
				}
				$('[name=pincode_servicable]').val('');
				var formData = 'zipcode=' + pincode + '&reverse=1&qc=' + qc_enabled + '&accountId=' + accountId;
				var url = base_url + '/api/pincode_lookup/';
				window.setTimeout(function () {
					var s = submitForm(formData, "GET", url);
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Pickup Servicability', s.message);
						if (!is_primary)
							$('.pincode_servicability_group .help-block').text(s.message);

						if (s.type == "success") {
							pincode_servicable = pincode;
							if (is_primary) {
								button.addClass('btn-success').removeClass('btn-info').find('i').removeClass('fa-sync fa-spin').addClass('fa-check');
								button.text(s.message);
							} else {
								$('.pincode_servicability_group i').removeClass('fa-sync fa-spin').addClass('fa-check');
								button.attr('disabled', false);
							}
							$('[name=pincode_servicable]').val('1');

							var locationData = "action=get_pincode_details&pincode=" + pincode;
							window.setTimeout(function () {
								var s = submitForm(locationData, 'GET', '');
								if (s.type == "success") {
									$('.return_city').val(s.city);
									$('.return_state').val(s.state);
								} else {
									UIToastr.init('error', 'RMA Order Search', s.msg);
									$('.return_state').attr('readonly', false);
								}
							}, 100);
						} else {
							if (is_primary) {
								button.addClass('btn-danger').attr('disabled', false).find('i').removeClass('fa-sync fa-spin').addClass('fa-times');
								// button.text(s.message);
							} else {
								$('.pincode_servicability_group i').removeClass('fa-sync fa-spin').addClass('fa-times');
								button.attr('disabled', false);
							}
							$('[name=pincode_servicable]').val('');
						}
					} else {
						UIToastr.init('error', 'Pickup Servicability', "Error processing request. Please try again later.");
						if (is_primary)
							button.find('i').removeClass('fa-sync fa-spin');
						else {
							button.attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
						}
					}
				}, 100);
			} else {
				$('.return_pincode').focus();
			}
		});
	}

	function tabChange_handleTable(handler) {
		if (handler == "order") {
			var type = typeof type === 'undefined' ? "new" : type;
			tab = (tab != '') ? tab : "#portlet_new";
			shopify_tags = tags.shopify;
		} else if (handler == "return") {
			var type = typeof type !== 'undefined' ? type : "start";
			tab = tab != '' ? tab : "#portlet_start";
		} else if (handler == "reship") {
			var type = typeof type !== 'undefined' ? type : "pending_pickup";
			tab = tab != '' ? tab : "#portlet_pending_pickup";
		} else if (handler == "repairs") {
			var type = typeof type !== 'undefined' ? type : "received";
			tab = tab != '' ? tab : "#portlet_received";
		}
		currentRequest = typeof currentRequest !== 'null' ? currentRequest : null;
		// comments_handleInit(type);
		viewOrder_handeleTable(type, tab, handler);
		refreshCount(handler);
		orderStatus_handleUpdate();

		// Order Status Tabs
		$(".order_type a").click(function () {
			if ($(this).parent().attr('class') == "active") {
				return;
			}
			tab = $(this).attr('href');
			type = tab.substr(tab.indexOf("_") + 1);
			viewOrder_handeleTable(type, tab, handler);
			refreshCount(handler);
		});
	}

	function viewOrder_handeleTable(type, tab, handler) {
		// DESTROY BEFORE REINITIATE
		if (jQuery().dataTable) {
			var tables = $.fn.dataTable.fnTables(true);
			$(tables).each(function () {
				$(this).dataTable().fnDestroy();
				$(this).empty();
			});
		}

		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			var e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return '';
		});

		var statusFilters = {
		};

		var columns = {
			"order": [
				{
					data: "checkbox",
					title: '<input type="checkbox" class="group-checkable" data-set=".checkboxes" />',
				}, {
					data: "content",
					title: "Order Details",
				}, {
					data: "account",
					title: "Account",
					columnFilter: 'selectFilter'
				}, {
					data: "multi_items",
					title: "Multi Items?",
					columnFilter: 'selectFilter'
				}, {
					data: "logistic_provider",
					title: "Logistic Provider",
					columnFilter: 'selectFilter'
				}, {
					data: "payment_type",
					title: "Payment Type",
					columnFilter: "selectFilter"
				}, {
					data: "order_date",
					title: "Order Date",
					columnFilter: "dateFilter"
				}, {
					data: "ship_date",
					title: "Ship By Date",
					columnFilter: "dateFilter"
				}, {
					data: "action",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			"return": [
				{
					data: "checkbox",
					title: '<input type="checkbox" class="group-checkable" data-set=".checkboxes" />',
				}, {
					data: "content",
					title: "Order Details",
				}, {
					data: "account",
					title: "Account",
					columnFilter: 'selectFilter'
				}, {
					data: "multi_items",
					title: "Multi Items?",
					columnFilter: 'selectFilter'
				}, {
					data: "return_type",
					title: "Return Type",
					columnFilter: "selectFilter"
				}, {
					data: "breached",
					title: "Breached?",
					columnFilter: "selectFilter"
				}, {
					data: "sort_date",
					title: "Sort Date",
					// columnFilter: "dateFilter"
				}, {
					data: "delivered_date",
					title: "Delivered Date",
					columnFilter: "selectFilter"
				}, {
					data: "action",
					title: "",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			"reship": [
				{
					data: "checkbox",
					title: '<input type="checkbox" class="group-checkable" data-set=".checkboxes" />',
				}, {
					data: "content",
					title: "Order Details",
				}, {
					data: "account",
					title: "Account",
					columnFilter: 'selectFilter'
				}, {
					data: "multi_items",
					title: "Multi Items?",
					columnFilter: 'selectFilter'
				}, {
					data: "action",
					title: "",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			"repairs": [
				{
					data: "checkbox",
					title: '<input type="checkbox" class="group-checkable" data-set=".checkboxes" />',
				}, {
					data: "content",
					title: "Order Details",
				}, {
					data: "account",
					title: "Account",
					columnFilter: 'selectFilter'
				}, {
					data: "multi_items",
					title: "Multi Items?",
					columnFilter: 'selectFilter'
				}, {
					data: "action",
					title: "",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			]
		};

		var targets = {
			"order": [2, 3, 4, 5, 6, 7, 8],
			"return": [2, 3, 4, 5, 6, 7, 8],
			"reship": [2, 3, 4],
			"repairs": [2, 3, 4],
		}

		var sorting_order = [5, 'asc'];
		if (handler == "return")
			var sorting_order = [7, 'asc'];
		if (handler == "reship" || handler == "repairs")
			var sorting_order = [4, 'asc'];
		var table = $('#selectable_shopify_' + type + '_' + handler);
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-download'> <'btn-advance'> f><'col-sm-12 table-filter'><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 20,
			debug: false,
			language: {
				lengthMenu: "_MENU_ orders per page <div class='selected_checkbox hide'>| <span class='selected_checkbox_count'>0</span> selected</div>",
				emptyTable: "No orders with this status"
			},
			searchDelay: 500,
			// processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=view_orders&type=" + type + "&handler=" + handler + "&token=" + new Date().getTime(),
				// dataSrc: 'data',
				cache: false,
				type: "GET",
				beforeSend: function () {
					if (currentRequest != null) {
						currentRequest && currentRequest.readyState != 4 && currentRequest.abort();
						currentRequest = null;
					}
				},
			},
			columns: columns[handler],
			order: [sorting_order],
			columnDefs: [
				{
					targets: 0,
					orderable: !1,
					width: '3%',
					className: 'orders-checkbox'
				}, {
					targets: 1,
					orderable: !1,
				}, {
					targets: targets[handler],
					className: 'return_hide_column',
				}
			],
			drawCallback: function () {
				afterDrawDataTable();
			},
			initComplete: function () {
				loadFilters(this);
				afterInitDataTable();
			}
		});

		function loadFilters(t) {
			var parseDateValue = function (rawDate) {
				var d = moment(rawDate, 'MMMM D, YYYY').format('YYYY-MM-DD');
				var dateArray = d.split("-");
				var parsedDate = dateArray[0] + dateArray[1] + dateArray[2];
				return parseInt(parsedDate);
			};

			var isRangeFilter = function (e) {
				if (typeof (oTable.settings()[0].aoColumns[e]) !== "undefined")
					if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "rangeFilter")
						return true;
				return false;
			};

			var isDateFilter = function (e) {
				if (typeof (oTable.settings()[0].aoColumns[e]) !== "undefined")
					if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "dateFilter")
						return true;

				return false;
			};
			// FILTERING
			var f = $('<ul class="filter"></ul>').appendTo('.table-filter');
			t.api().columns().every(function () {
				var s;
				switch (this.getColumnFilter()) {
					case 'inputFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input input-medium" data-col-index="' + this.index() + '"/>');
						break;

					case 'rangeFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="From" data-col-index="' + this.index() + '"/><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="To" data-col-index="' + this.index() + '"/>');
						break;

					case 'dateFilter':
						s = $('<div class="input-daterange input-medium"><div class="input-group input-group-sm date date-range-picker margin-bottom-5"><input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="' + this.title() + '" data-col-index="' + this.index() + '" /><span class="input-group-btn"><button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button></span></div></div>');
						break;

					case 'selectFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" data-placeholder="' + this.title() + '" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, f) {
							$(s).append('<option value="' + t + '">' + t + "</option>");
						});
						break;

					case 'statusFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, a) {
							$(s).append('<option value="' + t + '">' + statusFilters[t].title + "</option>")
						});
						break;

					case 'actionFilter':
						var i = $('<button class="btn btn-sm btn-warning filter-submit" title="Search"><i class="fa fa-search"></i></button>'),
							r = $('<button class="btn btn-sm btn-danger filter-cancel" title="Reset"><i class="fa fa-times"></i></button>');
						$("<li class='filter-buttons'>").append(i).append(r).appendTo(f);
						var sD, eD, minR, maxR;
						$(i).on("click", function (ev) {
							ev.preventDefault();
							var n = {};
							$(function () { }).find(".filter-input").each(function () {
								var t = $(this).data("col-index");
								n[t] ? n[t] += "|" + $(this).val() : n[t] = $(this).val()
							}),
								$.each(n, function (e, a) {
									if (isRangeFilter(e)) { // RANGE FILTER
										if (a == "")
											return;

										var range = a.split('|', 2);
										minR = range[0];
										maxR = range[1];

										var fR = oTable
											.column(e)
											.data()
											.filter(function (v, i) {
												var evalRange = v === "" ? 0 : v;
												// if ((isNaN(minR) && isNaN(maxR)) || (evalRange >= minR && evalRange <= maxR)) {
												if ((isNaN(minR) && isNaN(maxR)) ||
													(isNaN(minR) && evalRange <= maxR) ||
													(minR <= evalRange && isNaN(maxR)) ||
													(minR <= evalRange && evalRange <= maxR)) {
													return true;
												}
												return false;
											});

										var r = "";
										for (var count = 0; count < fR.length; count++) {
											r += fR[count] + "|";
										}
										r = r.slice(0, -1);
										oTable.column(e).search("^" + r + "$", 1, 1, 1);
									} else if (isDateFilter(e)) { // DATE FILTER
										if (a == "")
											return;

										var dates = a.split(' - ', 2);
										sD = dates[0];
										eD = dates[1];
										var dS = parseDateValue(sD);
										var dE = parseDateValue(eD);

										var fD = oTable
											.column(e)
											.data()
											.filter(function (v, i) {
												var evalDate = v === "" ? 0 : parseDateValue(v);
												if ((isNaN(dS) && isNaN(dE)) || (evalDate >= dS && evalDate <= dE))
													return true;

												return false;
											});

										var d = "";
										for (var count = 0; count < fD.length; count++) {
											d += fD[count] + "|";
										}

										d = d.slice(0, -1);
										oTable.column(e).search(d ? "^" + d + "$" : "^" + "-" + "$", 1, !1, 1);
										// oTable.column(e).search("^" + d + "$" , 1, 1, 1);
									} else { // DEFAULT FILTER
										oTable.column(e).search(a || "", !1, !1);
									}
								}),
								oTable.table().draw();
							afterInitDataTable();
						}),
							$(r).on("click", function (ev) {
								ev.preventDefault(), $(f).find(".filter-input").each(function () {
									$(this).val(""), oTable.column($(this).data("col-index")).search("", !1, !1)
									$('.select2').val("").trigger('change');
								}), oTable.table().draw();
								afterInitDataTable();
							});
						break;

					default:
						s = "";
						break;
				}
				"" !== this.title() && "Order Details" !== this.title() && $(s).appendTo($("<li>").appendTo(f));
			});
			var n = function () {
				t.api().columns().every(function () {
					this.visible() ? $(f).find("li").eq(this.index()).show() : $(f).find("li").eq(this.index()).hide();
				});
			};
			n(), window.onresize = n;

			// DEFAULT FUNCTIONS
			if (jQuery().daterangepicker) {
				var start = moment().subtract(7, 'days');
				var end = moment().add(59, 'days');

				$('.date-range-picker').daterangepicker({
					autoApply: true,
					ranges: {
						'Today': [moment(), moment()],
						'Tomorrow': [moment().add(1, 'days'), moment().add(1, 'days')],
						'Next 7 Days': [moment(), moment().add(6, 'days')],
						'Next 30 Days': [moment(), moment().add(29, 'days')],
						'This Month': [moment().startOf('month'), moment().endOf('month')],
					},
					alwaysShowCalendars: true,
					minDate: start,
					maxDate: end,
				});

				$('.date-range-picker').on('apply.daterangepicker', function (ev, picker) {
					$(this).find('input').val(picker.startDate.format('MMM DD, YYYY') + ' - ' + picker.endDate.format('MMM DD, YYYY'));
				});
			}

			if (jQuery().select2) {
				$('select.select2, .dataTables_length select').select2('destroy').select2({
					placeholder: "Select",
					allowClear: true
				});
			}

			// FILTER TOGGLE
			$('.btn-advance').html('<button class="btn btn-default"><i class="fa fa-filter"></i></button>');
			$('.filter').hide();
			$('.btn-advance').bind('click', function () {
				$('.filter').slideToggle();
			});

			// DOWNLOAD
			if (tab == "#portlet_in_transit") {
				$('.btn-download').html('<button class="btn btn-default download-in-transit"><i class="fa fa-file-export"></i></button>');
				var order_type = tab.replace('#portlet_', '');
				$('.download-in-transit').off().on('click', function () {
					var formData = "ajax_load.php?action=export_orders&order_type=" + order_type + "&handler=" + handler;
					window.setTimeout(function () {
						// var s = submitForm(formData, 'GET');
						window.open(formData);
					}, 10);
				});
			}
		}

		// Initiate DrawBack before table init
		function afterDrawDataTable() {
			$('.dataTables_paginate a').bind('click', function () {
				App.scrollTop();
			}),
				App.initUniform(),
				update_checked_count();

			if (handler == "order") {
				orders_handleApproval();
			}

			// if (handler == "return"){
			// 	return_handleClaims();
			// }

			// orders_handleBookmarks();
			comments_handleSidebar(type);
			// comments_handleValidation(type);
		}

		function afterInitDataTable() {
			// Button Actions
			button_handleProcess();

			// Reload DataTable
			$('.reload').off().on('click', function (e) {
				e.preventDefault();
				var el = jQuery(this).closest(".portlet").children(".portlet-body");
				App.blockUI({ target: el });
				$('.select2').val("").trigger('change');
				$('.filter-cancel').trigger('click');
				oTable.ajax.reload();
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		}

		// BUTTON PROCESS FUNCTION
		function button_handleProcess() {
			$('.generate_label').off().on('click', function () { // ORDER UPLOADED LABEL PENDING
				var button = $(this),
					orderId = button.data('orderid'),
					accountId = button.data('accountid'),
					lpProvider = button.data('lpprovider'),
					currentReq = null;
				button.prop('disabled', true);
				button.find('i').addClass('fa-spin');
				currentReq = $.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: 'POST',
					data: "action=generate_label&orderId=" + orderId + "&lpProvider=" + lpProvider + "&accountId=" + accountId,
					beforeSend: function () {
						if (currentRequest != null) {
							currentRequest.abort();
						} else {
							currentRequest = currentReq;
						}
					},
					success: function (s) {
						s = $.parseJSON(s);
						button.find('i').removeClass('fa-spin');
						button.prop('disabled', false);
						if (s.type == 'success') {
							button.parent().html("Label: <button class='btn tooltips ok' data-placement='right' data-original-title='Label Ok'><i class='fa fa-xs fa-circle'></i></button>");
							$('.grp_' + orderId).prop('disabled', false);
							App.updateUniform($('.grp_' + orderId));
						}
						UIToastr.init(s.type, 'Re-Generate Label', s.message);
					},
					error: function () {
						button.prop('disabled', false);
						alert('Error Processing your Request!!');
					}
				});
			});

			$('.generate_selfship_label').off().on('click', function () { // ORDER UPLOADED LABEL PENDING
				var button = $(this),
					orderId = button.data('orderid'),
					accountId = button.data('accountid'),
					currentReq = null;
				button.prop('disabled', true);
				button.find('i').addClass('fa-spin');
				currentReq = $.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: 'POST',
					data: "action=generate_selfship_label&orderId=" + orderId + "&accountId=" + accountId,
					beforeSend: function () {
						if (currentRequest != null) {
							currentRequest.abort();
						} else {
							currentRequest = currentReq;
						}
					},
					success: function (s) {
						s = $.parseJSON(s);
						button.find('i').removeClass('fa-spin');
						button.prop('disabled', false);
						if (s.type == 'success') {
							button.parent().html("Label: <button class='btn tooltips ok' data-placement='right' data-original-title='Label Ok'><i class='fa fa-xs fa-circle'></i></button>");
							$('.grp_' + orderId).prop('disabled', false);
							App.updateUniform($('.grp_' + orderId));
						}
						UIToastr.init(s.type, 'Re-Generate Label', s.message);
					},
					error: function () {
						button.prop('disabled', false);
						alert('Error Processing your Request!!');
					}
				});
			});

			$('.generate_packing_list').off().on('click', function () {
				var form = document.createElement("form");
				form.setAttribute("method", "post");
				form.setAttribute("action", "ajax_load.php");
				form.setAttribute("target", "_blank");

				var params = {
					"action": "generate_packing_list",
				};

				for (var key in params) {
					if (params.hasOwnProperty(key)) {
						var hiddenField = document.createElement("input");
						hiddenField.setAttribute("type", "hidden");
						hiddenField.setAttribute("name", key);
						hiddenField.setAttribute("value", params[key]);

						form.appendChild(hiddenField);
					}
				}
				document.body.appendChild(form);
				form.submit();
			});

			$('.get_labels').off().on('click', function () {
				var button = $(this);
				var allChecked = $('.checkboxes:checked');
				if (allChecked.length > 0) {
					is_queued = true;
					$(".btn-select").attr('disabled', true);
					button.find("i").addClass("fa fa-sync fa-spin");

					var shipmentIds = {};
					$(allChecked).each(function (index, element) {
						var account = $(this).data('account');
						var shipmentId = $(this).val();

						if (typeof shipmentIds[account] === 'undefined')
							shipmentIds[account] = [];

						var glength = shipmentIds[account].length;
						shipmentIds[account][glength] = shipmentId;
					});

					// getLabels
					window.setTimeout(function () {
						$.each(shipmentIds, function (account, shipments) {
							if (shipments.length != 0) {
								var form = document.createElement("form");
								form.setAttribute("method", "post");
								form.setAttribute("action", "ajax_load.php");
								form.setAttribute("target", "_blank");

								var params = {
									"action": "get_labels",
									"shipmentIds": shipments, //s.success.toString(),
									"account": account
								};

								for (var key in params) {
									if (params.hasOwnProperty(key)) {
										var hiddenField = document.createElement("input");
										hiddenField.setAttribute("type", "hidden");
										hiddenField.setAttribute("name", key);
										hiddenField.setAttribute("value", params[key]);

										form.appendChild(hiddenField);
									}
								}
								document.body.appendChild(form);
								form.submit();
							}
						});
					}, 100);
				} else {
					UIToastr.init('info', 'Create Labels', 'No orders selected');
				}
				$(".btn-select").attr('disabled', false);
				button.find("i").removeClass("fa fa-sync fa-spin");
				is_queued = false;
			});

			$('.get_manifest').off().on('click', function () {
				var button = $(this);
				var allChecked = $('.checkboxes:checked');
				is_queued = true;
				if (allChecked.length > 0) {
					$(".btn-select").attr('disabled', true);
					button.find("i").addClass("fa fa-sync fa-spin");

					var shipmentIds = {};
					$(allChecked).each(function (index, element) {
						var account = $(this).data('account');
						var shipmentId = $(this).data('shipmentid');

						if (typeof shipmentIds[account] === 'undefined')
							shipmentIds[account] = [];

						var glength = shipmentIds[account].length;
						shipmentIds[account][glength] = shipmentId;
					});

					$.each(shipmentIds, function (account, shipments) {
						shipments = JSON.stringify(shipments);
						window.setTimeout(function () {
							var form = document.createElement("form");
							form.setAttribute("method", "post");
							form.setAttribute("action", "ajax_load.php");
							form.setAttribute("target", "_blank");

							var params = {
								"action": "get_manifest",
								"accountId": account,
								"shipmentIds": shipments,
							};

							for (var key in params) {
								if (params.hasOwnProperty(key)) {
									var hiddenField = document.createElement("input");
									hiddenField.setAttribute("type", "hidden");
									hiddenField.setAttribute("name", key);
									hiddenField.setAttribute("value", params[key]);

									form.appendChild(hiddenField);
								}
							}
							document.body.appendChild(form);
							form.submit();
						}, 100);
					});

					$(".btn-select").attr('disabled', false);
					button.find("i").removeClass("fa fa-sync fa-spin");
					is_queued = false;
				} else {
					UIToastr.init('info', 'Generate Manifest', 'No orders selected');
				}
			});
		}

		function comments_handleSidebar(type) {
			var orderId = "",
				orderItemId = "",
				accountId = "";
			$('.view_comment').off().on('click', function () {
				orderId = $(this).data('orderid');
				orderItemId = $(this).data('orderitemid');
				accountId = $(this).data('accountid');
				type = $(this).data('type');
				console.log(type);
				$('.view_comments').modal('show');

				$('[name="orderId"]').val(orderId);
				$('[name="orderItemId"]').val(orderItemId);
				$('[name="accountId"]').val(accountId);
				comments_handleInit(type);
				comment_handleView($(this).data('orderid'), type);
				comments_handleScroll();
				$('.order-content-' + orderId).closest('tr').addClass('trborder');
			});

			$('.view_comments').on('hidden.bs.modal', function () {
				window.setTimeout(function () {
					$('tr').removeClass('trborder');
				}, 200)
			});

			$('.view_address').off().on('click', function () {
				orderId = $(this).data('orderid');
				orderItemId = $(this).data('orderitemid');
				accountId = $(this).data('accountid');
				$('.address-loading-block').removeClass('hide');
				$('.update-address').addClass('hide');
				$('.update_address').modal('show');

				$('[name="orderId"]').val(orderId);
				$('[name="orderItemId"]').val(orderItemId);
				$('[name="accountId"]').val(accountId);
				address_handleView($(this).data('orderid'), accountId);
			});
		}
		// CHECKBOX COUNT FUNCTION
		function update_checked_count() {
			resetCheckbox();
			$('.checkboxes').click(function () {
				var checked = jQuery(this).is(":checked");
				$('.group-checkable').prop("checked", false);
				update_checked_chckbox(false, checked);
			});

			/*$('.dataTables_filter input').bind('change', function(){
				$('.checkboxes').bind('change', function(){
					update_checked_chckbox();
				});
			});

			$('.dataTables_length select').bind('change', function(){
				$('.checkboxes').bind('change', function(){
					update_checked_chckbox();
				});
			});*/

			// Update selected count
			$('.group-checkable').click(function () {
				var tab = $(this).attr("data-set");
				var checked = $(this).is(":checked");
				update_checked_chckbox(true, checked);
			});
		}

		var last = 0;
		function update_checked_chckbox(all, checked) {
			all && (checked ? $(".checkboxes:not(':disabled')").prop("checked", true) : $(".checkboxes").prop("checked", false));

			var totalCheckboxes = $('.checkboxes').length;
			var numberOfChecked = $('.checkboxes:checked').length;
			var numberNotChecked = totalCheckboxes - numberOfChecked;
			numberOfChecked == totalCheckboxes ? $('.group-checkable').prop("checked", true) : "";

			App.updateUniform($('.checkboxes'));

			if (numberOfChecked != 0) {
				$('.selected_checkbox').removeClass('hide');
				if (!is_queued)
					$('.btn-select').attr('disabled', false);
			} else {
				$('.selected_checkbox').addClass('hide');
				$('.btn-select').attr('disabled', true);
			}
			$('.selected_checkbox_count').text(numberOfChecked);
		}

		function resetCheckbox() {
			$('.selected_checkbox_count').empty();
			$('.selected_checkbox').addClass('hide');
			$('.group-checkable').prop("checked", false);
			$('.checkboxes').prop("checked", false);
			App.initUniform();
		}
	}

	function refreshCount(handler) {
		var formData = "action=get_orders_count&handler=" + handler;
		window.setTimeout(function () {
			var s = submitForm(formData, 'GET', '');
			if (handler == "order") {
				$(".portlet_pending.count").text("(" + (s.orders.pending) + ")");
				$(".portlet_new.count").text("(" + (s.orders.new) + ")");
				$(".portlet_packing.count").text("(" + (s.orders.packing) + ")");
				$(".portlet_rtd.count").text("(" + (s.orders.rtd) + ")");
				$(".portlet_shipped.count").text("(" + (s.orders.shipped) + ")");
				$(".portlet_cancelled.count").text("(" + (s.orders.cancelled) + ")");
				$(".portlet_pickup_pending.count").text("(" + (s.orders.pickup_pending) + ")");
				$(".portlet_in_transit.count").text("(" + (s.orders.in_transit) + ")");
				$(".portlet_undelivered.count").text("(" + (s.orders.undelivered) + ")");
				$(".portlet_ndr.count").text("(" + (s.orders.ndr) + ")");
				$(".portlet_follow_up.count").text("(" + (s.orders.follow_up) + ")");
			} else if (handler == "return") {
				$(".portlet_start.count").text("(" + (s.orders.start) + ")");
				$(".portlet_in_transit.count").text("(" + (s.orders.in_transit) + ")");
				$(".portlet_out_for_delivery.count").text("(" + (s.orders.out_for_delivery) + ")");
				$(".portlet_delivered.count").text("(" + (s.orders.delivered) + ")");
				$(".portlet_cancelled.count").text("(" + (s.orders.cancelled) + ")");
				$(".portlet_pending_pickup.count").text("(" + (s.orders.pending_pickup) + ")");
				$(".portlet_payment_pending.count").text("(" + (s.orders.payment_pending) + ")");
			} else if (handler == "reship") {
				$(".portlet_packing.count").text("(" + (s.orders.packing) + ")");
				$(".portlet_ready_to_dispatch.count").text("(" + (s.orders.ready_to_dispatch) + ")");
				$(".portlet_shipped.count").text("(" + (s.orders.shipped) + ")");
				$(".portlet_pending_pickup.count").text("(" + (s.orders.pending_pickup) + ")");
				$(".portlet_in_transit.count").text("(" + (s.orders.in_transit) + ")");
				$(".portlet_delivered.count").text("(" + (s.orders.delivered) + ")");
			} else if (handler == "repairs") {
				$(".portlet_received.count").text("(" + (s.orders.received) + ")");
				$(".portlet_repair_payment_pending.count").text("(" + (s.orders.repair_payment_pending) + ")");
				$(".portlet_repair_in_progress.count").text("(" + (s.orders.repair_in_progress) + ")");
				$(".portlet_completed.count").text("(" + (s.orders.completed) + ")");
				$(".portlet_pending_estimate_approval.count").text("(" + (s.orders.pending_estimate_approval) + ")");
				$(".portlet_qc_in_progress.count").text("(" + (s.orders.qc_in_progress) + ")");
			}
		}, 1);
	}

	function orderStatus_handleUpdate() {
		// console.log('orderStatus_handleUpdate');
		// ORDER STATUS MODAL
		var type = "";
		$("#order_status_update").on("show.bs.modal", function (e) {
			$('#update_type').val($(e.relatedTarget).data('updatetype'));

			$("body").addClass("modal-open");
			// RESET
			count_s = 0;
			count_e = 0;
			trackingIds = [];
			clear_model_container();
			$('#trackin_id').val('');
			$('#account_id').val('').trigger('change');
		}).on('hidden.bs.modal', function (e) {
			$("body").removeClass("modal-open");
			type = tab.substr(tab.indexOf("_") + 1);
			refreshCount(handler);
			viewOrder_handeleTable(type, tab, handler);
		});

		$("#mark_orders").submit(function (e) {
			e.preventDefault();
			$(".form-group").removeClass("has-error");

			var trackin_id = $.trim($('#trackin_id').val().toUpperCase());
			var account_id = $('#account_id').val();
			var update_type = $('#update_type').val();

			if (account_id == "" && handler == "order") {
				$(".form-group").addClass("has-error");
				return;
			}


			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=mark_orders&type=" + update_type + "&tracking_id=" + trackin_id + "&account_id=" + account_id,
				success: function (s) {
					var as = $.parseJSON(s);
					$('.list-container').show();
					$.each(as, function (k, s) {
						if (s.type == 'success') {
							$('ul.success-list').append(s.content);
							count_s = count_s + 1
							$('.scan-passed').html(count_s + '<span class="scan-passed-ok"><i class="fa fa-check-circle" aria-hidden="true"></i></span>');
							if (trackingIds.indexOf(trackin_id) === -1) {
								trackingIds.push(trackin_id);
							}
						} else {
							$('ul.failed-list').append(s.content);
							count_e = count_e + 1
							$('.scan-failed-count').html(count_e + '<span class="cancel-icon"><i class="fa fa-times-circle" aria-hidden="true"></i></span>');
							if (trackingIds.indexOf(trackin_id) === -1) {
								trackingIds.push(trackin_id);
							}
						}
					});
				},
				error: function () {
					console.log('Error Processing your Request!!');
				}
			});
		});
	}

	return {
		//main function to initiate the module
		init: function (type) {
			switch (type) {
				case 'create':
					rmaCreate_handleSearch();
					rmaCreate_handleValidation();
					break;

				case 'orders':
					tabChange_handleTable('return');
					break;

				case 'repairs':
					tabChange_handleTable('repairs');
					break;

				case 'reship':
					tabChange_handleTable('reship');
					break;
			}
		}
	};

}();
