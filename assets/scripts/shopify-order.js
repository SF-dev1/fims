"use_strict";

var ShopifyOrder = function () {

	function submitForm(formData, $type, hide_indicator, $url) {
		var getParams = "&";

		if ($type == "GET") {
			formData = Object.fromEntries(formData);
			getParams += new URLSearchParams(formData).toString();
		}
		if (typeof ($url) === 'undefined')
			$url = "ajax_load.php?token=" + new Date().getTime();

		return $.ajax({
			type: $type,
			contentType: false,
			processData: false,
			url: $url + getParams,
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
					try {
						ajaxObj = JSON.parse(data);
					} catch (e) {
						ajaxObj = data;
					}
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

	function ShopifyOrder_handleInit() {
		console.log('ShopifyOrder_handleInit');
		$('.view_address').off().on('click', function () {
			$('.update_address').modal('show');

			ShopifyAddress_handleView();
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
		ShopifyButton_handleAction();

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

	function ShopifyAddress_handleView() {
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

		$('.same_pickup_address').off().on('change', function () {
			if ($(this).is(":checked")) {
				$('.reassign-courier input.form-control[type=text]').attr('readonly', true);
			} else {
				$('.reassign-courier input.form-control[type=text]').attr('readonly', false);
			}
			App.updateUniform($(this));
		});

		ShopifyAddress_handleValidation();
	}

	function ShopifyAddress_handleValidation() {
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

	function ShopifyComments_handleValidation(type) {
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

				console.log(type);
				if (type == "confirm")
					ShopifyOrder_handleConfirmation(formComments);

				if (type == "cancel")
					ShopifyOrder_handleCancellation(formComments);

				if (type == "comment")
					ShopifyOrder_handleComments(formComments);
			}
		});
	}

	function ShopifyOrder_handleComments(form) {
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
				if (commentfor == "assured_delivery" || commentfor == "ndr_approved" || commentfor == "mark_delivered" || commentfor == "mark_undelivered")
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

	function ShopifyOrder_handleConfirmation(form) {
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
						formData3.set("orderType", 'self_ship');
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
							console.log(fulfillment.error.message);
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

	function ShopifyOrder_handleCancellation(form) {
		console.log('ShopifyOrder_handleCancellation');
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

	function ShopifyOrder_handleCall(button, button_type) {
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
				notificationData.append('identifierValue', button.data("identifiervalue"));
				notificationData.append('identifierType', button.data("identifiertype"));
				notificationData.append('templateId', button.data("templateid"));
				notificationData.append('accountId', button.data('accountid'));
				
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

	function ShopifyOrderDelivered_handleValidation() {
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
				console.log(other_data);
				var formData = new FormData();
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				$.when(
					submitForm(formData, "POST", true),
					ShopifyComments_handleValidation('comment'),
				).then(function (s) {
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Order Status Updated', s.message);
						if (s.type == "success") {
							$('#order_delivered').modal('hide');
							$('.add-comment').submit();
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

	function ShopifyButton_handleAction() {
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
				case 'auto_confirmed':
				case 'confirmation_call':
					$('form.add-comment .form-action').click(function () {
						ShopifyComments_handleValidation('confirm');
					});
					break;

				case 'call_attempted': // SEND NOTIFICATION VIA QR AND ADD COMMENT
					$('.order_comment').addClass('hide');
					$('.order_comment textarea').prop('disabled', true).prop('required', false);
					$('.call_attempted').removeClass('hide');
					$('.call_attempt_reason').prop('disabled', false).prop('required', true);

					$('form.add-comment .form-action').off().on('click', function () {
						ShopifyOrder_handleCall(button, button_type);
					});
					break;

				case 'request_address': // SEND NOTIFICATION VIA QR AND ADD COMMENT
					var notificationMedium = ["whatsapp"];
					var notificationData = new FormData();
					notificationData.append('action', 'send_notification_message');
					notificationData.append('notificationType', button_type);
					notificationData.append('medium', notificationMedium);
					notificationData.append('identifierValue', button.data("identifiervalue"));
					notificationData.append('identifierType', button.data("identifiertype"));
					notificationData.append('templateId', button.data("templateid"));
					notificationData.append('accountId', button.data('accountid'));
					notificationData.append('orderId', button.data('orderid'));

					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					$.when(
						submitForm(notificationData, "POST", true),
						ShopifyComments_handleValidation('comment'),
						$('.add-comment').submit()
					).then(function (notification, comment) {
						button.attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');
						UIToastr.init(notification.type, 'Notification Update', notification.message);
						UIToastr.init(comment.type, 'Order Update', comment.message);
					});
					break;

				case 'convert_to_self_pickup':
					var orderData = new FormData();
					orderData.append('action', 'update_to_self_pickup');
					orderData.append('orderId', button.data('orderid'));
					orderData.append('accountId', button.data('accountid'));

					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					$.when(
						submitForm(orderData, "POST", true), //
					).then(function (pickup) {
						orderData.set('action', 'add_order_to_logistic_aggregator'); // GENERATE INVOICE AND RETURN EMPTY
						$.when(
							submitForm(orderData, "POST", true),
						).then(function (invoice) {
							orderData.set("action", 'mark_orders'); // MARK AND MOVE TO PACKING
							orderData.append("type", 'self_approved');
							orderData.append("orderType", 'pickup');
							$.when(
								submitForm(orderData, "POST", true), //
								ShopifyComments_handleValidation('comment'),
								$('.add-comment').submit()
							).then(function (approved, comment) {
								UIToastr.init(approved.type, 'Order Update', approved.message);
								UIToastr.init(comment.type, 'Order Update', comment.message);
								button.attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');
								if (approved.type == "success")
									location.reload();
							});
						})
					});
					break;

				case 'convert_to_self_return':
					var orderData = new FormData();
					orderData.append('action', 'convert_to_self_return');
					orderData.append('orderId', button.data('orderid'));
					orderData.append('accountId', button.data('accountid'));
					orderData.append('returnId', button.data('returnid'));
					orderData.append('fulfillmentId', button.data('fulfillmentid'));

					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					$.when(
						submitForm(orderData, "POST", true), //
					).then(function (pickup) {
						UIToastr.init(pickup.type, 'Return Update', pickup.message);
						button.attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');

					});
					break;

				case 'mark_delivered':
					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					ShopifyOrderDelivered_handleValidation();
					break;

				case 'mark_undelivered':
					console.log('mark_undelivered');
					var orderStatusData = new FormData();
					orderStatusData.append('action', 'mark_orders');
					orderStatusData.append('type', 'undelivered');
					orderStatusData.append('orderId', button.data('orderid'));
					orderStatusData.append('accountId', button.data('accountid'));

					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					$.when(
						submitForm(orderStatusData, "POST", true),
						ShopifyComments_handleValidation('comment'),
					).then(function (orderStatus, comment) {
						$('.add-comment').submit();
						button.attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');
						if (orderStatus.includes("Successfully inserted")) {
							UIToastr.init('success', 'Order Status Update', 'Successfully marked order as RTO');
						}
						else
							UIToastr.init('error', 'Order Status Update', orderStatus);
					});
					break;

				case 'cancellation_request':
					if (confirm('Are sure you want to cancel the order?')) {
						ShopifyComments_handleValidation('cancel');
					}
					break;

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
						ShopifyComments_handleValidation('comment');
					});
					break;

				case 'reassign_courier':
					ShopifyAddress_handleView();
					ShopifyReassignCourier_handleValidation();
					break;

				case 'assured_delivery':
					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					$('form.add-comment .form-action').click(function () {
						ShopifyComments_handleValidation('comment');
						$('.add-comment').submit();
					});
					break;

				case 'create_refund/replacement':
					console.log('create_refund/replacement');
					button.attr('disabled', true).find('i').addClass('fa fa-sync fa-spin');
					ShopifyReturnReplacement_handleGet(button);

					$('#refund_replace').on('hidden.bs.modal', function () {
						button.attr('disabled', false).find('i').removeClass('fa fa-sync fa-spin');
					});
					break;
			}
		});
	}

	function ShopifyReassignCourier_handleValidation() {
		var form = $('.reassign-courier');
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

				$.when(
					submitForm(formData, "POST", true)
					// submitForm(commentData, "POST")
				).then(function (s) {
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Reassign Courier', s.message);
						if (s.type == "success") {
							commentData.append("comment", 'AWB Reassign');
							commentData.append("comment_for", 'awb_reassign');
							commentData.append("action", 'add_comment');
							$.when(
								submitForm(commentData, "POST")
							).then(function (c) {
								UIToastr.init(c.type, 'Address Updates', c.message);
								// $('.customer-address-block').html('<b>'+$('.name').val()+'</b><br />'+$('.address1').val()+'<br />'+$('.address2').val()+'<br />'+$('.city').val()+'<br />'+$('.province').val()+'<br />'+$('.zip').val());
								// $('.customer-contact').html($('.customer-contact').html().replaceAll(oldMobileNumber, mobileNumber));
								// $('.address-loading-block').addClass('hide');
								// $('.update-address').removeClass('hide');
								$('.reassign_courier').modal('hide');
							});
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

	function ShopifyReturnReplacement_handleGet(button) {
		var buttonType = button.attr('data-type');
		var ordernumber = button.attr('data-ordernumber');
		var accountId = button.attr('data-accountid');
		var formData = new FormData();
		formData.set('action', 'get_order_details');
		formData.set('ordernumber', ordernumber);
		formData.set('accountId', accountId);
		$.when(
			submitForm(formData, 'GET', true),
		).then(function (orderDetails) {
			if (orderDetails.type == "success" || orderDetails.type == "error") {
				if (orderDetails.type == "success") {
					var data = orderDetails.data;
					$('[name=marketplace]').val(data[0]['marketplace'].toLowerCase());
					$('[name=orderId]').val(data[0]['orderId']);
					// THIS IS WILL GO WITH THE UID SELECTION
					// $('[name=productId]').val(data[0]['productId']);
					// $('[name=sku]').val(data[0]['sku']);
					// $('[name=orderItemId]').val(data[0]['orderItemId']);
					$('[name=accountId]').val(data[0]['accountId']);
					$('[name=invoiceDate]').val(data[0]['invoiceDate']);
					$('[name=warrantyPeriod]').val(data[0]['warrantyPeriod']);

					// if(buttonType == 'create_replacement'){
					// 	$("#replacement_rdo").parent().addClass("checked");
					// 	$("#return_rdo").parent().removeClass("checked");
					// 	$("#replacement_rdo").attr("checked", true);
					// 	$("#return_rdo").attr("checked", false);
					// 	$("#replacement_rdo").prop("checked", true);
					// 	$("#return_rdo").prop("checked", false);
					// }else{
					// 	$("#return_rdo").parent().addClass("checked");
					// 	$("#replacement_rdo").parent().removeClass("checked");
					// 	$("#return_rdo").attr("checked", true);
					// 	$("#replacement_rdo").attr("checked", false);
					// 	$("#return_rdo").prop("checked", true);
					// 	$("#replacement_rdo").prop("checked", false);
					// }
					ShopifyReturnReplacement_handleInit();
				} else {
					UIToastr.init('error', 'Add User', "Error processing request. Please try again later.");
				}
			}
		});
	}

	function ShopifyReturnReplacement_handleInit() {
		console.log('ShopifyReturnReplacement_handleInit');
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
			var uid = $(this).val();
			var id = "#fieldset_" + uid + "_div";
			if ($(this).is(":checked")) {
				$(id).removeClass('hide');
				console.log('.item_details_' + uid);
				$(id + ' .radio-btn, ' + id + ' .replacement_reason, ' + id + ' .replacement_reason_dd, ' + id + ' .replacement_subreason, .item_details_' + uid).attr('required', true).attr('disabled', false);
				App.updateUniform($(id + ' .radio-btn, ' + id + ' .replacement_reason, ' + id + ' .replacement_reason_dd, ' + id + ' .replacement_subreason'));
			} else {
				$(id).addClass('hide');
				$(id + ' .radio-btn, ' + id + ' .replacement_reason, ' + id + ' .replacement_reason_dd, ' + id + ' .replacement_subreason, .item_details' + uid).attr('required', false).attr('disabled', true);
				App.updateUniform($(id + ' .radio-btn, ' + id + ' .replacement_reason, ' + id + ' .replacement_reason_dd, ' + id + ' .replacement_subreason'));
			}
		});

		$('body').on('change', '.replacement_reason', function () {
			var type = $(this).val();
			var uid = $(this).attr('data-uid');
			if (type == 'product_issue') {
				$('.product_issue_' + uid).removeClass('hide');
				$('.product_issue_' + uid + ' .replacement_reason_dd').attr('required', true).attr('disabled', false);

				$('.wrong_reason_' + uid).addClass('hide');
				$('.wrong_reason_' + uid + ' .replacement_reason_dd').attr('required', false).attr('disabled', true);
			} else {
				$('.wrong_reason_' + uid).removeClass('hide');
				$('.wrong_reason_' + uid + ' .replacement_reason_dd').attr('required', true).attr('disabled', false);

				$('.product_issue_' + uid).addClass('hide');
				$('.product_issue_' + uid + ' .replacement_reason_dd').attr('required', false).attr('disabled', true);
			}
		});

		$('body').on('click', '.btn-pincode-check', function () {
			accountId = $(this).data('accountid');
			var order_id = $(this).data('lporderid');
			var qc_enabled = $('[name=qc_enable]').is(':checked');
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
				// var formData = 'zipcode='+pincode+'&reverse=1&qc='+qc_enabled+'&orderId='+order_id+'&accountId='+accountId;
				var url = base_url + '/api/pincode_lookup/index.php?';
				var getData = new FormData();
				getData.set("zipcode", pincode);
				getData.set("reverse", "1");
				getData.set("qc", qc_enabled);
				getData.set("orderId", order_id);
				getData.set("accountId", accountId);

				window.setTimeout(function () {
					submitForm(getData, "GET", true, url)
						.then(function (getResponse) {
							console.log(getResponse);
							if (getResponse.type == "success" || getResponse.type == "error") {
								UIToastr.init(getResponse.type, 'Pickup Servicability', getResponse.message);
								if (!is_primary)
									$('.pincode_servicability_group .help-block').text(getResponse.message);

								if (getResponse.type == "success") {
									pincode_servicable = pincode;
									if (is_primary) {
										button.addClass('btn-success').removeClass('btn-info').find('i').removeClass('fa-sync fa-spin').addClass('fa-check');
										button.text(getResponse.message);
									} else {
										$('.pincode_servicability_group i').removeClass('fa-sync fa-spin').addClass('fa-check');
										button.attr('disabled', false);
									}
									$('[name=pincode_servicable]').val('1');

									// var locationData = new FormData();
									// locationData.set("action", 'get_pincode_details');
									// locationData.set("pincode", pincode);
									// window.setTimeout(function(){
									// 	var s = submitForm(locationData, 'GET', '');
									// 	if (s.type == "success"){
									$('.return_city').val(getResponse.zipdata.city);
									$('.return_state').val(getResponse.zipdata.state);
									// 	} else {
									// 		UIToastr.init('error', 'RMA Order Search', s.msg);
									// 		$('.return_state').attr('readonly', false);
									// 	}
									// }, 100);
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
						});
				}, 100);
			} else {
				$('.return_pincode').focus();
			}
		});

		$('body').on('change', '#return_address', function () {
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
			} else {
				$('#return_address').val("1");
				$('.return_address, [name=return_address_confirm]').parent().closest('div.form-group').removeClass('hide');
				$('.return_address_details').addClass('hide');
				$('.pickup-address-form').not('.return_address_2, .return_landmark').attr('required', false).prop('readonly', true);
				$('.return_landmark').attr('required', false).prop('readonly', true);
				if ($('[name=pickup_type]:checked').val() != "self_ship")
					$('.primary-pincode-check').removeClass('hide');
				$('.return_pincode').prop('readonly', true).inputmask('remove');
			}
			App.updateUniform($('#return_address'));
		});

		$('body').on('change', '.qc_enable', function () {
			$('[name=pincode_servicable]').val('');
			$('.btn-pincode-check').attr('disabled', false).removeClass('btn-success').addClass('btn-info').html('<i class="fa"></i> Check Servicability!');
			$('.pincode_servicability_group i').removeClass('fa-check');
		});

		$('body').on('change', '#default_to_wa', function () {
			$this = $(this);
			var checked = $this.prop('checked');
			if (checked) {
				$('input[name=whatsapp_number]').val($('input[name=mobile_number]').val()).prop('readonly', true).prop('required', false);
			}
			else
				$('input[name=whatsapp_number]').val('').prop('readonly', false).prop('required', true);
		});

		$('body').on('change', '[name=pickup_type]', function () {
			$('[name=pincode_servicable]').val('');
			if ($('[name=pickup_type]:checked').val() == "self_ship")
				$('[name=pincode_servicable]').val('1');
		});

		ShopifyReturnReplacement_handleValidation();
	}

	function ShopifyReturnReplacement_handleValidation() {
		var form = '.return-replace';
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);
		$(form).validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit              
				success.hide();
				error.show();
				App.scrollTo(error, -200);
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
				success.show();
				error.hide();
				$('.form-action .btn-submit', $(form)).attr('disabled', true);
				$('.form-action .btn-submit i', $(form)).addClass('fa fa-sync fa-spin');
				window.setTimeout(function () {
					var other_data = $(form).serializeArray();
					var formData = new FormData(),
						commentData = new FormData(),
						getData = new FormData();
					formData.set('action', 'create_refund_replace');
					$.each(other_data, function (key, input) {
						// if(input.name.indexOf("replacement_reason") != -1){
						// 	console.log($('input[name="'+input.name+'"]').val());
						// } else
						if (input.name.indexOf("uids") != -1) {
							var selectedUids = [];
							$("input[name='" + input.name + "']:checked").each(function () {
								selectedUids.push($(this).val());
							});
							formData.set(input.name, selectedUids);
						} else {
							formData.set(input.name, input.value);
							if (input.name == "orderId") {
								commentData.set(input.name, input.value);
								getData.set(input.name, input.value);
							}
						}
					});

					$.when(
						submitForm(formData, "POST", true),
					).then(function (formReturnValue) {
						if (formReturnValue.type == "success") {
							$('#refund_replace').modal('hide');
							// commentData.append("comment", 'Refund/Replacement Order Created');
							// commentData.append("comment_for", 'return_initiated');
							// commentData.append("action", 'add_comment');

							// $.when(
							// 	submitForm(commentData, "POST")
							// ).then(function(c) {
							// 	if (c.type == "success" || c.type == "error"){
							// RELOAD COMMENTS
							getData.set("action", 'get_comments');
							submitForm(getData, "GET", true)
								.then(function (gc) {
									$('.order_comments').html(gc.data);
									UIToastr.init(formReturnValue.type, 'Refund/Replacement', formReturnValue.message);
									$('.form-action .btn-submit', $(form)).attr('disabled', false);
									$('.form-action .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
								});
							// 	}
							// });
						} else {
							UIToastr.init(formReturnValue.type, 'Refund/Replacement', formReturnValue.message);
							$('.form-action .btn-submit', $(form)).attr('disabled', false);
							$('.form-action .btn-submit i', $(form)).removeClass('fa-sync fa-spin');
						}
					});
				});
			},
		});
	}

	function showIndicator() {
		$('.loading-block').removeClass('hide');
		$('.data-block').addClass('hide');
	}

	function hideIndicator() {
		$('.loading-block').addClass('hide');
		$('.data-block').removeClass('hide');
	}

	return {
		//main function to initiate the module
		init: function () {
			ShopifyOrder_handleInit();
		}
	};
}();
