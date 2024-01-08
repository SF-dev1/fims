"use_strict";

var RmaOrders = function () {
	var marketplace,
		pincode_servicable,
		in_warranty,
		is_returnable,
		product_condition,
		estimateCart;

	function submitForm(formData, $type, hide_indicator) {
		var getParams = "&";


		if ($type == "GET") {
			formData = Object.fromEntries(formData);
			getParams += new URLSearchParams(formData).toString();
		}

		return $.ajax({
			type: $type,
			contentType: false,
			processData: false,
			url: "ajax_load.php?token=" + new Date().getTime() + getParams,
			data: formData,
			beforeSend: function () {
				if (!hide_indicator) showIndicator();
			}
		})
			.then(function (data) {
				if (!hide_indicator) {
					hideIndicator();
				}
				if (data) {

					ajaxObj = JSON.parse(data);
					if (ajaxObj.redirectUrl) {
						window.location.href = ajaxObj.redirectUrl;
					}
					return ajaxObj;
				}

				UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
				return false;
			}, function () {
				if (!hide_indicator) {
					hideIndicator();
				}

				UIToastr.init('error', 'Request Error', 'Error Processing your Request!!');
				return false;
			});
	}


	function RmaOrder_handleInit() {
		$('.view_address').off().on('click', function () {
			$('.update_address').modal('show');

			rmaAddress_handleView($(this).data('orderid'));
		});

		// ALL SELECT2
		$('select.select2').select2({
			placeholder: "Select",
			allowClear: true
		});

		// DATE PICKER
		$('.date-picker').datepicker({
			autoclose: true
		});

		// ALL INIT


		RmaButton_handleAction();

		// CLIPBOARD
		var clipboard = new ClipboardJS('.copy_text');
		clipboard.on('success', function (e) {
			UIToastr.init('success', 'Copy to Clipboard', 'Successfully copied to clipboard');
			e.clearSelection();
		});
		clipboard.on('error', function (e) {
			UIToastr.init('error', 'Copy to Clipboard', 'Error coping to clipboard');
		});
	}

	function RmaAddress_handleView(orderId) {
		$('.zip').off().on('change', function () {
			var $this = $(this);
			if ($this.val().replace(/_/g, "").length == 6) {
				$('.zip, .city, .province, .btn-address-submit').prop('disabled', true);
				$('.zip, .city, .province').parent('div').find('i').addClass('fa fa-sync fa-spin')
				var getData = new FormData();
				getData.set("action", 'get_pincode_details');
				getData.set("pincode", $this.val());
				submitForm(getData, "GET", true)
					.then(function (s) {
						if (s.type == "success") {
							$('.city').val(s.data.city);
							$('.province').val(s.data.state);
						}
						$('.zip, .city, .province, .btn-address-submit').prop('disabled', false);
						$('.zip, .city, .province').parent('div').find('i').removeClass('fa fa-sync fa-spin')
					});
			}
			return;
		});

		$(".zip").inputmask({
			"mask": "999999",
			"greedy": false
		});

		rmaAddress_handleValidation();
	}

	function RmaAddress_handleValidation() {
		var form = $('.update-address');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				error.show();
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
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) {
				// error.appendTo( element.parent("div") );
			},

			submitHandler: function (form) {
				error.hide();
				$('.btn-success', form).attr('disabled', true).find('i').addClass('fa-sync fa-spin');

				var oldMobileNumber = $('.oldMobileNumber').val();
				var mobileNumber = $('.phone').val();
				var other_data = $(form).serializeArray();
				var formData = new FormData(),
					commentData = new FormData(),
					getData = new FormData();
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
					if (input.name == "orderId") {
						commentData.append(input.name, input.value);
						getData.append(input.name, input.value);
					}
				});
				commentData.append("comment", 'Address updated');
				commentData.append("comment_for", 'address_updated');
				commentData.append("action", 'add_comment');

				$.when(
					submitForm(formData, "POST", true),
					submitForm(commentData, "POST")
				).then(function (s, c) {
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Address Updates', s.message);
						if (s.type == "success") {
							UIToastr.init(c.type, 'Address Updates', c.message);
							$('.customer-address-block').html('<b>' + $('.name').val() + '</b><br />' + $('.address1').val() + '<br />' + $('.address2').val() + '<br />' + $('.city').val() + '<br />' + $('.province').val() + '<br />' + $('.zip').val());
							$('.customer-contact').html($('.customer-contact').html().replaceAll(oldMobileNumber, mobileNumber));
							$('.address-loading-block').addClass('hide');
							$('.update-address').removeClass('hide');
							$('.update_address').modal('hide');
						}
						$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');

						// RELOAD COMMENTS
						getData.set("action", 'get_comments');
						submitForm(getData, "GET", true)
							.then(function (s) {
								$('.order_comments').html(s.data);
							});
					} else {
						$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
						UIToastr.init('info', 'Address Updates', 'Error Processing Request! Please try again later.');
					}
				});
			}
		});
	}

	function RmaComments_handleValidation(type) {
		var formComments = $('.add-comment');
		var error = $('.alert-danger', formComments);

		formComments.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				error.show();
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
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) {
				// error.appendTo( element.parent("div") );
			},

			submitHandler: function (formComments) {
				error.hide();
				$('.btn-success', formComments).attr('disabled', true).find('i').addClass('fa-sync fa-spin');

				if (type == "confirm")
					RmaOrder_handleConfirmation(formComments);

				if (type == "cancel")
					RmaOrder_handleCancellation(formComments);

				if (type == "comment")
					RmaOrder_handleComments(formComments);
			}
		});
	}

	function RmaOrder_handleComments(form) {
		var other_data = $(form).serializeArray();

		var commentData = new FormData(),
			getData = new FormData();
		$.each(other_data, function (key, input) {
			commentData.append(input.name, input.value);
			if (input.name != "comment" && input.name != "comment_for" && input.name != "ndr_approved_reason") {
				getData.append(input.name, input.value);
			}
		});

		$.when(
			submitForm(commentData, "POST", true)
		).then(function (comment) {
			if (comment.type == 'success') {
				UIToastr.init(comment.type, 'Order Update', comment.message);
				$('.view_comments').modal('hide');
				$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
				// $('.order-actions .btn-action').attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');
				var commentfor = $('input[name="comment_for"]').val();
				if (commentfor == "assured_delivery" || commentfor == "ndr_approved")
					location.reload();

				// RELOAD COMMENTS
				getData.set("action", 'get_comments');
				submitForm(getData, "GET", true)
					.then(function (s) {
						$('.order_comments').html(s.data);
					});
			} else {
				UIToastr.init(comment.type, 'Order Update', comment.message);
			}
		});
	}

	function RmaOrder_handleConfirmation(form) {
		var other_data = $(form).serializeArray();
		var getData = new FormData(),
			formData = new FormData(),
			formData1 = new FormData(),
			formData2 = new FormData(),
			formData3 = new FormData();
		$.each(other_data, function (key, input) {
			formData.append(input.name, input.value);
			if (input.name != "comment" && input.name != "comment_for" && input.name != "ndr_approved_reason") {
				getData.append(input.name, input.value);
				formData1.append(input.name, input.value);
				formData2.append(input.name, input.value);
				formData3.append(input.name, input.value);
			}
		});

		formData1.set("action", 'add_order_to_logistic_aggregator');

		$.when(
			submitForm(formData, "POST", true),
			submitForm(formData1, "POST", true)
		)
			.then(function (comment, fulfillment) {
				if (fulfillment.type == 'success' && typeof (fulfillment.is_multiQuantity) === 'undefined') {
					UIToastr.init(fulfillment.type, 'Order Update', fulfillment.message);

					formData2.set("action", 'mark_orders');
					formData2.set("type", 'approved');
					submitForm(formData2, "POST")
						.then(function (s) {
							UIToastr.init(s.type, 'Order Update', s.message);
							$('.view_comments').modal('hide');
							$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
							if (s.type == "success")
								location.reload();
						});
				} else if (fulfillment.type == 'success' && fulfillment.is_multiQuantity) {
					UIToastr.init(fulfillment.type, 'Order Update', fulfillment.message);
					$('.view_comments').modal('hide');
					$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
				} else if (fulfillment.type == 'info') {
					if (typeof (fulfillment.enableSelfShip) === 'undefined') {
						if (typeof (fulfillment.error) === 'undefined')
							UIToastr.init(fulfillment.type, 'Order Update', fulfillment.message);
						else
							UIToastr.init(fulfillment.type, 'Order Update', fulfillment.error.message);
						$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
					} else if (fulfillment.enableSelfShip) {
						// $('.approve-actions-'+id).addClass('hide');
						// $('.selfShip-actions-'+id).removeClass('hide');
						UIToastr.init(fulfillment.type, 'Order Update', fulfillment.message);

						$.each(other_data, function (key, input) {
							formData3.append(input.name, input.value);
						});
						formData3.set("action", 'mark_orders');
						formData3.set("type", 'self_approved');
						submitForm(formData3, "POST")
							.then(function (s) {
								UIToastr.init(s.type, 'Order Update', s.message);
								$('.view_comments').modal('hide');
								$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
								location.reload();
							});
					}
				} else {
					if (typeof (fulfillment.error) != 'undefined') {
						if (typeof (fulfillment.error.message) != 'undefined') {
							UIToastr.init('info', 'Order Update', fulfillment.error.message);
						} else if (typeof (fulfillment.error.response.data) != 'undefined') {
							UIToastr.init('info', 'Order Update', fulfillment.error.response.data.awb_assign_error);
							// if (fulfillment.error.response.data.includes('not servicable')){
							formData.set("comment", fulfillment.error.response.data.awb_assign_error);
							formData.set("comment_for", "error");
							submitForm(formData, "POST")
								.then(function (s) {
									if (s.type == "success" || s.type == "error") {
										UIToastr.init(s.type, 'Comment Updates', s.message);
									}
								});
							// }
						}
					} else if (typeof (fulfillment.message) != 'undefined') {
						UIToastr.init('info', 'Order Update', fulfillment.message);
						formData.set("comment", fulfillment.message);
						formData.set("comment_for", "error");
						submitForm(formData, "POST")
							.then(function (s) {
								if (s.type == "success" || s.type == "error") {
									UIToastr.init(s.type, 'Comment Updates', s.message);
								}
							});
					}
					else
						UIToastr.init('info', 'Order Update', 'Error Processing Request! Please try again later.');

					$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
				}

				// RELOAD COMMENTS
				getData.set("action", 'get_comments');
				submitForm(getData, "GET", true)
					.then(function (s) {
						$('.order_comments').html(s.data);
					});
			});
	}

	function RmaOrder_handleCancellation(form) {
		var other_data = $(form).serializeArray();
		var cancelData = new FormData(),
			commentData = new FormData();

		$.each(other_data, function (key, input) {
			commentData.append(input.name, input.value);
			cancelData.append(input.name, input.value);
		});
		cancelData.set("action", 'cancel_order');

		$.when(
			submitForm(cancelData, "POST", true),
			submitForm(commentData, "POST", true)
		)
			.then(function (cancel, comment) {
				$('.view_comments').modal('hide');
				$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');

				// RELOAD COMMENTS
				getData.set("action", 'get_comments');
				submitForm(getData, "GET", true)
					.then(function (s) {
						$('.order_comments').html(s.data);
					});
			});
	}

	function RmaOrder_handleCall(button, button_type) {
		$('.order_comment').addClass('hide');
		$('.order_comment textarea').prop('disabled', true).prop('required', false);
		$('.call_attempted').removeClass('hide');
		$('.call_attempt_reason').prop('disabled', false).prop('required', true);

		var formCall = $('.add-comment');
		var error = $('.alert-danger', formCall);

		formCall.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				error.show();
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
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) {
				// error.appendTo( element.parent("div") );
			},

			submitHandler: function (formCall) {
				error.hide();
				$('.btn-success', formCall).attr('disabled', true).find('i').addClass('fa-sync fa-spin');

				var other_data = $(formCall).serializeArray();
				var commentData = new FormData(),
					getData = new FormData();

				$.each(other_data, function (key, input) {
					commentData.append(input.name, input.value);
					if (input.name != "comment" && input.name != "comment_for" && input.name != "ndr_approved_reason") {
						getData.append(input.name, input.value);
					}
				});

				var notificationMedium = ["whatsapp"];
				var notificationData = new FormData();
				notificationData.append('action', 'send_notification_message');
				notificationData.append('notificationType', button_type);
				notificationData.append('medium', notificationMedium);
				notificationData.append('mobileNumber', button.data('mobilenumber'));
				notificationData.append('eMail', button.data('email'));
				notificationData.append('customerName', button.data('customername'));
				notificationData.append('orderNumber', button.data('ordernumber'));
				notificationData.append('accountActiveWhatsappId', button.data('whatappaccountid'));

				$.when(
					submitForm(notificationData, "POST", true),
					submitForm(commentData, "POST", true)
				).then(function (notification, comment) {
					UIToastr.init(notification.type, 'Notification Update', notification.message);
					UIToastr.init(comment.type, 'Order Update', comment.message);
					$('.order_comment').removeClass('hide');
					$('.order_comment textarea').prop('disabled', false).prop('required', true);
					$('.call_attempted').addClass('hide');
					$('.call_attempt_reason').prop('disabled', true).prop('required', false);
					$('.view_comments').modal('hide');
					$('.btn-success', formCall).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');

					// RELOAD COMMENTS
					getData.set("action", 'get_comments');
					submitForm(getData, "GET", true)
						.then(function (s) {
							$('.order_comments').html(s.data);
						});
				});
			}
		});
	}

	function RmaOrderDelivered_handleValidation() {
		var form = $('.order-delivered');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				error.show();
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
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) {
				// error.appendTo( element.parent("div") );
			},

			submitHandler: function (form) {
				error.hide();
				$('.btn-success', form).attr('disabled', true).find('i').addClass('fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				$.when(
					submitForm(formData, "POST", true),
					// 	submitForm(commentData, "POST")
				).then(function (s, c) {
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Order Status Updated', s.message);
						if (s.type == "success") {
							// $('.order_delivered').modal('hide');
							// RELOAD COMMENTS
							location.reload();
						}
						$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');

					} else {
						$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
						UIToastr.init('info', 'Address Updates', 'Error Processing Request! Please try again later.');
					}
				});
			}
		});
	}

	function RmaButton_handleAction() {

		$('.btn-action').click(function () {

			var button = $(this),
				button_name = button.data('name'),
				button_type = button.data('type'),
				orderId = "",
				orderItemId = "",
				accountId = "",
				comment = button.data('comment'),
				commentfor = button.data('commentfor');


			$('.order_comment').removeClass('hide');
			$('.order_comment .form-control').prop('disabled', false);
			$('.order_cancellation, .replacement_product, .ndr_approval, .call_attempted').addClass('hide');
			$('.order_cancellation .form-control, .replacement_product .form-control, .ndr_approval .form-control, .call_attempted .form-control').prop('disabled', true);

			$('[name="comment"]').val(comment);
			$('[name="comment_for"]').val(commentfor);

			switch (button_type) {
				case 'general_comment':
				case 'ndr_approved':
				case 'refund_escalation':
					if (button_type == "ndr_approved") {
						$('.order_comment').addClass('hide');
						$('.order_comment textarea').prop('disabled', true).prop('required', false);
						$('.ndr_approval').removeClass('hide');
						$('.ndr_approved_reason').prop('disabled', false).prop('required', true);
					}
					$('form.add-comment .form-action').click(function () {
						RmaComments_handleValidation('comment');
					});
					break;

				case 'update_tracking_details':
					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					$('#refund_replace').on('hidden.bs.modal', function () {
						button.attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');
					});
					break;


				case 'add_additional_job':
					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					RmaReturnReplacement_handleGet(button);

					$('#refund_replace').on('hidden.bs.modal', function () {
						button.attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');
					});
					break;
				case 'mark_delivered':
					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					RmaOrderDelivered_handleValidation();

					$('#order_delivered').on('hidden.bs.modal', function () {
						button.attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');
					});
					break;
			}
		});

		$('.selfship').click(function () {
			var form = $('.selfship_tracking');
			var error = $('.alert-danger', form);

			form.validate({
				errorElement: 'span', //default input error message container
				errorClass: 'help-block', // default input error message class
				focusInvalid: false, // do not focus the last invalid input
				ignore: "",
				rules: {
					trackingLink: {
						required: true,
						url: true
					}
				},

				invalidHandler: function (event, validator) { //display error alert on form submit
					error.show();
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
					label.closest('.form-group').removeClass('has-error'); // set success class to the control group
				},

				errorPlacement: function (error, element) {
					// error.appendTo( element.parent("div") );
				},

				submitHandler: function (form) {
					error.hide();

					var btn_id = $('.btn', form).attr('id');
					$('#' + btn_id, form).html('<i class="fa fa-sync fa-spin"></i>').attr('disabled', true);

					var other_data = $(form).serializeArray();
					var formData = new FormData();
					var update_type = "";
					$.each(other_data, function (key, input) {
						formData.append(input.name, input.value);
						if (input.name == "type")
							update_type = input.value;
					});

					formData.set('action', 'self_shipped');

					// ADD APPROVED TAG ON SHOPIFY (OPTIONAL)
					// UPLOAD ORDERS DATA TO SHIPROCKET
					// ASSIGN AWB
					// REQUEST LABEL
					$.when(
						submitForm(formData, "POST", true),
					).then(function (formReturnValue) {
						if (formReturnValue.type == "success") {
							UIToastr.init(formReturnValue.type, 'Order Update', formReturnValue.message);
							$('.update_tracking_details').modal('hide');
							window.location.reload();

						}
						else if (formReturnValue.type == 'error') {
							if (update_type == "delivered")
								$('#' + btn_id, form).html('Mark Delivered').attr('disabled', false);
							else
								$('#' + btn_id, form).html('Update to In-Transit').attr('disabled', false);
							UIToastr.init(s.type, 'Order Update', s.error.message);
						}
					});
				}
			});
		});
	}

	function RmaReturnReplacement_handleGet(button) {
		var buttonType = button.attr('data-type');
		var ordernumber = button.attr('data-ordernumber');
		var marketplace = button.attr('data-marketplace');
		var formData = new FormData();
		estimateCart = Array();

		var existingEstimate = JSON.parse($('[name=estimate]').val());


		existingEstimate.forEach(function (element) {
			estimateCart.push(element);
		});

		formData.set('action', 'get_order_details');
		formData.set('order_key', ordernumber);
		formData.set('marketplace', marketplace);
		$.when(
			submitForm(formData, 'GET', true),
		).then(function (orderDetails) {
			if (orderDetails.type == "success" || orderDetails.type == "error") {
				if (orderDetails.type == "success") {
					var data = orderDetails.data;
					$('[name=marketplace]').val(data[0]['marketplace'].toLowerCase());
					$('[name=orderId]').val(data[0]['orderId']); $('[name=accountId]').val(data[0]['accountId']);
					$('[name=invoiceDate]').val(data[0]['invoiceDate']);
					$('[name=warrantyPeriod]').val(data[0]['warrantyPeriod']);

					if (buttonType == 'create_replacement') {
						$("#replacement_rdo").parent().addClass("checked");
						$("#return_rdo").parent().removeClass("checked");
						$("#replacement_rdo").attr("checked", true);
						$("#return_rdo").attr("checked", false);
						$("#replacement_rdo").prop("checked", true);
						$("#return_rdo").prop("checked", false);
					} else {
						$("#return_rdo").parent().addClass("checked");
						$("#replacement_rdo").parent().removeClass("checked");
						$("#return_rdo").attr("checked", true);
						$("#replacement_rdo").attr("checked", false);
						$("#return_rdo").prop("checked", true);
						$("#replacement_rdo").prop("checked", false);
					}
					RmaReturnReplacement_handleInit();

				} else {
					// UIToastr.init('error', 'Add User', "Error processing request. Please try again later.");
				}
			}
		});
	}

	function RmaReturnReplacement_handleInit() {
		$("[name='whatsapp_number']").inputmask({
			"mask": "9999999999",
		});

		$("[name='mobile_number']").inputmask({
			"mask": "9999999999",
			onKeyDown: function (buffer, opts) {
				if ($("#default_to_wa").prop('checked')) {
					$('input[name=whatsapp_number]').val($('input[name=mobile_number]').val());
				}
			}
		});

		$('body').on('change', '.product-uid', function () {
			var value = $(this).val();
			var id = "#fieldset_" + value + "_div";
			if ($(this).prop("checked") == true) {
				$(id).removeClass('hide');
			} else {
				$('.issue_checkboxes').each(function () {
					var elem = $(this);
					if (elem.data('group') == value) {
						elem.closest('.checked').removeClass('checked');
						elem.prop("checked", false);
					}
				});
				$('.estimateSectionDetails').html('');
				estimateCart = Array();
				$(id).addClass('hide');
			}
		});

		$('.issue_checkboxes').off().on('click', function () {
			$this = $(this);
			$(this).closest('span').addClass('checked');
			var checked = $this.prop('checked');
			var uid = $this.data('group');
			var part = $this.data('partname');
			var quantity_editable = false;
			if (part == "Button")
				quantity_editable = true;
			var part_value = 0;

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
			else {
				$(this).closest('span').removeClass('checked');
				rmaCreate_handleEstimate(estimate_product, 'remove');
			}

		});

		$('.other_issue').off().on('click', function () {
			$this = $(this);
			var checked = $this.prop('checked');
			if (checked)
				$('.issue_textbox').removeClass('hide');
			else
				$('.issue_textbox').addClass('hide');
		});

		$('body').on('click', '.alt-pincode-check', function () {
			accountId = $("#accountId").val();
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
				var formData = 'zipcode=' + pincode + '&reverse=1&accountId=' + accountId;
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

		$('body').on('change', '#return_address', function () {
			if ($(this).prop("checked") == true) {
				$(".return_address_details").addClass('hide');
				$(".confirmed_address").removeClass('hide');
				$(".return_address_name").prop('readonly', true);
				$(".return_address_1").prop('readonly', true);
				$(".return_address_2").prop('readonly', true);
				$(".return_landmark").prop('readonly', true);
				$(".return_pincode").prop('readonly', true);
				$(".return_city").prop('readonly', true);
				$(".return_state").prop('readonly', true);
				$('.alt-pincode-check').attr('disabled', true);
			} else {
				$(".return_address_details").removeClass('hide');
				$(".confirmed_address").addClass('hide');

				$(".return_address_name").prop('readonly', false);
				$(".return_address_1").prop('readonly', false);
				$(".return_address_2").prop('readonly', false);
				$(".return_landmark").prop('readonly', false);
				$(".return_pincode").prop('readonly', false);
				$(".return_city").prop('readonly', false);
				$(".return_state").prop('readonly', false);
				$('.alt-pincode-check').attr('disabled', false);

			}
		});

		$('body').on('change', '#default_to_wa', function () {
			$this = $(this);
			var checked = $this.prop('checked');
			if (checked) {
				$('input[name=whatsapp_number]').val($('input[name=mobile_number]').val()).prop('readonly', true);
			}
			else
				$('input[name=whatsapp_number]').val('').prop('readonly', false);
		});

		RmaReturnReplacement_handleValidation();
	}

	function rmaCreate_handleEstimate(part_object, event, value = null) {

		if (event == "add") {
			estimateCart.push(part_object);
		}

		if (event == "remove") {
			let find = estimateCart.findIndex(el => el.part === part_object.part);
			if (find >= 0)
				estimateCart.splice(find, 1);
		}

		if (event == "remove_all") {
			estimateCart = part_object;
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
			if (item.uid) {
				html += "<td>" + item.uid + "</td>";
			}
			else {
				html += "<td>" + JSON.parse($('[name=productUId]').val())[0] + "</td>";
			}


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


	function RmaReturnReplacement_handleValidation() {
		var validateTrip = true;
		var customValid = true;
		var form = '#return-replace-form';
		$(form).validate({
			// focusInvalid: true,
			ignore: ":hidden",
			debug: true,
			errorElement: 'span',
			errorClass: 'help-block',
			rules: {
				return_replacement: { required: true },
				return_address: { required: true },
				mobile_number: { required: true },
				whatsapp_number: { required: true },
				email_address: { required: true, email: true },
			},
			messages: {
				return_replacement: { required: "Please select return/replacement type" },
				return_address: { required: "Please confirm return address" },
				mobile_number: { required: "Please enter mobile number" },
				whatsapp_number: { required: "Please enter whatsapp number" },
				email_address: {
					required: "Please enter your email",
					email: "Please enter valid email address"
				},
			},

			invalidHandler: function (event, validator) {
				validateTrip = false;
				rustomValid = RmaCustomerInfo_handleValidation();
			},

			submitHandler: function (form) {
				// customValid = RmaCustomerInfo_handleValidation();
				// if (customValid) {
				$('.form-actions .btn-submit', $(form)).attr('disabled', true);
				$('.form-actions .btn-submit i', $(form)).addClass('fa fa-sync fa-spin');
				window.setTimeout(function () {
					var other_data = $(form).serializeArray();
					var formData = new FormData(),
						commentData = new FormData(),
						getData = new FormData();
					formData.set('action', 'create_rma');
					$.each(other_data, function (key, input) {
						formData.set(input.name, input.value);
						if (input.name == "orderId")
							commentData.set(input.name, input.value);
					});
					$.when(
						submitForm(formData, "POST", true),
					).then(function (formReturnValue) {
						if (formReturnValue.type == "success") {
							$('#refund_replace').modal('hide');
							window.location.reload();
							commentData.append("comment", 'Return/Replacement Created');
							commentData.append("comment_for", 'return_created');
							commentData.append("action", 'add_comment');

							$.when(
								submitForm(commentData, "POST")
							).then(function (c) {
								if (c.type == "success" || c.type == "error") {
									// RELOAD COMMENTS
									getData.set("action", 'get_comments');
									submitForm(getData, "GET", true)
										.then(function (gc) {
											$('.order_comments').html(gc.data);
											UIToastr.init(formReturnValue.type, 'Refund/Replacement', formReturnValue.message);
										});
								}
							});
						}
					});
				});
			},

			errorPlacement: function (error, element) {
				rustomValid = RmaCustomerInfo_handleValidation();
				var elem = $(element);

				if (elem.hasClass("radio-btn")) {
					element = elem.parent().parent().parent().parent().parent().parent();
				} else {
					if (elem.hasClass("select")) {
						element = elem.parent().parent();
					} else {
						if (elem.hasClass("has-checkbox")) {
							element = elem.parent().parent().parent().parent();
						} else {
							if (elem.hasClass("has-whatsapp-number")) {
								element = elem.parent();
							}
						}
					}
				}
				error.insertAfter(element);
			},
		});
	}

	function RmaCustomerInfo_handleValidation() {
		return true;
	}

	function showIndicator() {
		$('.loading-block').removeClass('hide');
		$('.data-block').addClass('hide');
	}

	function hideIndicator() {
		$('.loading-block').addClass('hide');
		$('.data-block').removeClass('hide');
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

		$(".uid").on('click', function () {
			var uid = $('#uidSelection').val();
			var orderKey = $('.orderItemId').text();
			var marketPlace = $('[name=\'marketplace\']').val();
			var warrantyPeriod = $('[name=\'warrantyPeriod\']').val();

			rmaCreate_handleEstimate([], 'remove_all');
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
					UIToastr.init('error', 'Add User', "Error processing request. Please try again later.");
				}
			}
		})

		$('body').on('change', '.product-uid', function () {
			var value = $(this).val();
			var id = "#fieldset_" + value + "_div";
			if ($(this).prop("checked") == true) {
				$("#form_uid_error").text("");
				$(id).removeClass('hide');
			} else {
				$('.issue_checkboxes').each(function () {
					var elem = $(this);
					if (elem.data('group') == value) {
						elem.closest('.checked').removeClass('checked');
						elem.prop("checked", false);
					}
				});
				$('.estimateSectionDetails').html('');
				rmaCreate_handleEstimate([], 'remove_specific', value);
				$(id).addClass('hide');
			}
		});

		$(".uidSelection").on('change', function () {
			group = $(this).val();

			// $('.rma_pid_fieldset').addClass('hide');
			$('.rma_pid_fieldset .radio').attr('disabled', true);
			$("[name='pickup_type'][value='self_ship']").prop("checked", true);

			rmaCreate_handleEstimate([], 'remove_specific', value);
			// rmaCreate_handleEstimate([], 'remove_all');
			$('.fieldset_' + group).removeClass('hide');
			$('[name="productCondition"]').val('damaged');

			$('.product_condition_list_group_' + group + ' .radio').attr('disabled', false);
			if (!is_returnable)
				$('.product_condition_list_group_' + group + ' .product_condition_saleable, .product_condition_list_group_' + group + ' .product_condition_wrong').attr('disabled', true);
			App.updateUniform($('[name="pickup_type"]'));
			App.updateUniform($('.product_condition_list_group_' + group + ' .radio'));
		});

		$("#rmaCreation .product_condition_list .radio").off().on('change', function () {
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

			if (product_condition == "wrong" || product_condition == "saleable") {
				$('.return_replace_product_list_group_' + group).removeClass('hide')
				$('.return_replace_product_list_group_' + group + ' .radio').attr('disabled', false).attr('required', true);
				if (product_condition == "saleable") {
					$('.issue_product_list_group_' + group + ' .return_reason').prop('required', true).prop('disabled', false);
				}

				$('.' + product_condition + '_product_list_group_' + group + ' .checkboxes').prop('checked', false);
				rmaCreate_handleEstimate([], 'remove_all');
			}

			App.updateUniform($('.issue_checkboxes'));
			App.updateUniform($('.issue_checkboxes_action'));
			$("." + product_condition + "_product_list_group_" + group).removeClass('hide');
			$("." + product_condition + "_product_list_group_" + group + " .checkboxes, ." + product_condition + "_product_list_group_" + group + " .radio").attr('disabled', false).attr('required', true);
			App.updateUniform($('.' + product_condition + '_product_list_group_' + group + ' .checkboxes, .' + product_condition + '_product_list_group_' + group + ' .radio'));
		});

		function repoFormatResult(variant) {
			var originalOption = variant.element;
			var note = "";
			if (typeof ($(originalOption).data('note')) != "undefined")
				note = '<div class="col-md-12">' + $(originalOption).data('note') + '</div>';

			var markup = '<div class="row">' +
				'<div class="col-md-3"><img width="85px" src="' + $(originalOption).data('image') + '" /></div>' +
				'<div class="col-md-9">' +
				'<div class="row">' +
				'<div class="col-md-12 bold">' + variant.text + '</div>' +
				'<div class="col-md-12">&#8377;' + $(originalOption).data('sp') + '</div>' +
				note +
				'</div>' +
				'</div>' +
				'</div>';
			return markup;
		}

		$(".replacement_product").select2({
			formatResult: repoFormatResult,
			escapeMarkup: function (m) { return m; },
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

		$('#return_address').off().on('click', function () {
			var original_state = $('.alt-pincode-check').attr('disabled');
			if (!$('#return_address').prop('checked')) {
				$('#return_address').val("");
				$('.return_address, [name=return_address]').parent().closest('div.checkbox-list').addClass('hide');
				$('[name=return_address]').attr('required', false);
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
				$('.return_address, [name=return_address]').parent().closest('div.checkbox-list').removeClass('hide');
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

		$('.issue_checkboxes').off().on('click', function () {
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

		$('.other_issue').off().on('click', function () {
			$this = $(this);
			var checked = $this.prop('checked');
			if (checked)
				$('.issue_textbox').removeClass('hide');
			else
				$('.issue_textbox').addClass('hide');

		});

		$('.issue_checkboxes_action').off().on('click', function () {
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

		$('[name="pickup_type"]').off().on('change', function () {
			var estimate_product = {
				'uid': 'Shipping',
				'part': 'Postage & Packaging',
				'quantity': '1',
				'quantity_editable': false,
				'price': 100
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
					estimate_product.price = 100;
				}
				rmaCreate_handleEstimate(estimate_product, 'add');
				$('.primary-pincode-check').prop('disabled', false).removeClass('hide');
			}
			App.updateUniform($('[name="pickup_type"]'));
		});

		$('#default_to_wa').off().on('click', function () {
			$this = $(this);
			App.updateUniform($('#default_to_wa'));
			var checked = $this.prop('checked');
			if (checked) {
				$('input[name=whatsapp_number]').val($('input[name=mobile_number]').val()).prop('readonly', true);
			}
			else
				$('input[name=whatsapp_number]').val('').prop('readonly', false);
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
		var customValid = true;
		var form = $('#raise_rma_request');
		// var error1 = $('.alert-danger', form);

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
			rules: {
				mobile_number: {
					required: true,
					digits: true,
					minlength: 10,
					maxlength: 10
				},
				whatsapp_number: {
					required: true,
					digits: true,
					minlength: 10,
					maxlength: 10
				},
				email_address: {
					required: true,
					email: true
				},
				return_address: {
					required: function (element) {
						return $("#return_address").val();
					},
				}
			},

			messages: { // custom messages for radio buttons and checkboxes
				pincode_servicable: {
					required: "Pincode is not entered/serviceable"
				},
			},

			invalidHandler: function (event, validator) { //display error alert on form submit
				customValid = customerInfoValid();
				customReturnValid = customerReturnInfoValid();

				// error1.show();
				// App.scrollTo(error1, -200);
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
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) {
				customValid = customerInfoValid();
				var elem = $(element);
				if (elem.hasClass("checkBox")) {
					error.insertAfter(elem.closest('.form-group').find('.checkbox-list'));
				} else {
					error.insertAfter(element);
				}

			},

			submitHandler: function (form) {
				customValid = customerInfoValid();
				customReturnValid = customerReturnInfoValid();
				// error1.hide();
				if (customValid && customReturnValid) {
					$('.form-actions .btn-submit', $(form)).attr('disabled', true);
					$('.form-actions .btn-submit i', $(form)).addClass('fa fa-sync fa-spin');

					window.setTimeout(function () {
						var other_data = $(form).serializeArray();
						var formData = new FormData();
						formData.set('action', 'create_rma');
						$.each(other_data, function (key, input) {
							formData.set(input.name, input.value);
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

								window.setTimeout(function () {
									location.reload(true);
								}, 10000);
							}
						} else {
							UIToastr.init('error', 'Add User', "Error processing request. Please try again later.");
						}
						$('.form-actions .btn-submit', $(form)).attr('disabled', false);
						$('.form-actions .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
					}, 100);
				}
			}
		});

		function customerInfoValid() {
			var customValid = false;
			var issueValid = false;
			var testval = [];

			$('.product-uid').each(function () {
				var elem = $(this);
				if ($(this).is(':visible')) {
					if ($(this).prop("checked") == true) {
						customValid = true;
						testval.push($(this).val());
						$(".form_product_uid_error_" + $(this).val()).text("");
						$("#form_uid_error").text("");
					}
				}
			});


			$('.issue_checkboxes').each(function () {
				var elem = $(this).data('group');
				if (testval.includes(elem)) {
					if ($(this).prop("checked") == true) {
						issueValid = true;
						testval.shift();
					}
				}
			});


			testval.forEach(function (item, index) {
				$(".form_product_uid_error_" + item).text("Please select atleast one Damage Type");
			});



			if (customValid) {
				$("#form_uid_error").text("");
			} else {
				$("#form_uid_error").text("Please select product UID");
			}

			if (issueValid && customValid) {
				return customValid;
			}
		}

		function customerReturnInfoValid() {
			var customReturnValid = false;

			if ($("#return_address").prop('checked') == true) {
				if ($("#default_address").prop('checked') == true) {
					customReturnValid = true;
					$("#form_return_address_error").text("");
				}
				else {
					$("#form_return_address_error").text("Please select return address");
				}
			}
			else {
				customReturnValid = true;
				$("#form_return_address_error").text("");
			}

			return customReturnValid;
		}
	}

	return {
		//main function to initiate the module
		init: function (type) {

			RmaOrder_handleInit();
		}
	};
}();
