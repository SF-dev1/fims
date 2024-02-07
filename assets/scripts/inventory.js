"use strict";

var Inventory = function () {

	var item, reported_issues, identified_issues, repairs_issues, fixed_issues, requested_componenets = {};
	var limit = 1;
	var items = new Array();
	var box_items_sku = new Array();
	var ctn_items_sku = new Array();
	var boxIds = new Array();
	var boxItems = new Array();
	var current_state;
	var box_qty_count = 0;
	var ctn_qty_count = 0;
	var weight_enable_categories = ['Silver Bracellete', 'Silver Earrings', 'Silver Necklace', 'Silver Anklets', 'Silver Rings'];

	// SUBMIT FORM
	var submitForm = function (formData, $type) {
		var $ret = "";
		$.ajax({
			url: "ajax_load.php?token=" + new Date().getTime(),
			cache: true,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			showLoader: true,
			success: function (s) {
				if (s != "") {
					$ret = $.parseJSON(s);

					if ($ret.redirectUrl) {
						window.location.href = $ret.redirectUrl;
					}
				}
			},
			error: function (e) {
				alert('Error Processing your Request!!');
			}
		});
		return $ret;
	};

	// PROCESS LOG
	function endProcess(uid, type) {
		var formdata = "action=sideline_product&type=" + type + "&uid=" + uid;
		$.when(
			submitForm(formdata, "GET"),
		).then(function (s) {
			if (s.type == "success" || s.type == "error") {
				if (s.type == "success") {
					UIToastr.init(s.type, 'Sideline Order', s.message);
					$('.product_details .item_groups').html("");
					$('.product_details').addClass('hide');
					$('#tracking_id').val("").focus();
					return true;
				} else {
					audio.play();
					return false;
				}
			} else {
				audio.play();
				UIToastr.init('error', 'Request Error', 'Error Processing Request. Please try again later.');
				return false;
			}
		});
	}

	// INBOUND
	function inboundGrn_handlePrintOption() {
		$('.print_label').click(function () {
			var label_for = $(this).data('label_for');
			var formData = new FormData();
			formData.append('action', 'update_print_option');
			formData.append('option', label_for);
			formData.append('value', $(this).prop('checked'));
			var s = submitForm(formData, "POST");
			if (s.type == "success") {
				UIToastr.init(s.type, 'Print Label', 'Successfully updated print setting');
			} else {
				UIToastr.init(s.type, 'Print Label', s.msg);
			}
		})
	}

	function inboundGrn_handleSelect() {
		// MARKETPLACE AND ACCOUNT OPTIONS
		var current_grn = App.getURLParameter('grn_id');
		var grn_menu = $('.grn_select');
		var grn_item_menu = $('.grn_item_select');
		var grn_options = "<option value=''></option>";
		var item_options = new Array();
		var item_details = new Array();
		$.each(grn, function (grn_k, grn) {
			var selected = "";
			if (current_grn == grn.grn_id) {
				selected = "selected = 'selected'";
			}
			grn_options += "<option " + selected + "value='" + grn.grn_id + "'>GRN_" + grn.grn_id + "</option>";

			item_options['GRN_' + grn.grn_id] = "<option value=''></option>";
			item_details['GRN_' + grn.grn_id] = new Array();
			$.each(grn_items['GRN_' + grn.grn_id], function (item_k, item_v) {
				item_options['GRN_' + grn.grn_id] += "<option value='" + item_v.item_id + "'>" + item_v.sku + "</option>";
				item_details['GRN_' + grn.grn_id][item_v.item_id] = item_v;
			});
		});
		grn_menu.empty().append(grn_options);
		if (jQuery().select2) {
			grn_menu.select2({
				placeholder: "Select",
				allowClear: true
			});
		}
		grn_item_menu.prop('disabled', true);
		if (current_grn !== null) {
			grn_menu.prop('disabled', true);
			grn_item_menu.empty().append(item_options['GRN_' + current_grn]);
			grn_item_menu.prop('disabled', false);
		}

		grn_menu.off().on('change', function () {
			if ($(this).val() === "") {
				grn_item_menu.prop('disabled', true);
				grn_item_menu.select2("val", "");
			} else {
				current_grn = $(this).val();
				grn_item_menu.prop("disabled", false);
				grn_item_menu.select2("val", "");
				grn_item_menu.empty().append(item_options['GRN_' + current_grn]);
			}
			$('.inbound-details, .machine_on').addClass('hide');
			$('.machine_off').removeClass('hide');
		});

		if (jQuery().select2) {
			grn_item_menu.select2({
				placeholder: "Select",
				allowClear: true
			});
		}

		grn_item_menu.off().on('change', function () {
			$('.inbound-details').addClass('hide');
			var item_value = $(this).val();
			if (item_value !== "") {
				item = item_details['GRN_' + current_grn][item_value];
				current_state = "";
				inboundSku_handleItemDetails();
				$('.inbound-details, .machine_on').removeClass('hide');
				$('.machine_off').addClass('hide');
			}
		});
	}

	function inboundSku_handleItemDetails() {
		// RESET
		$('.product_sku').text(item.sku);
		$('.product_image').attr('src', image_url + '/uploads/products/' + item.thumb_image_url);
		$('.qty_received .count').text(item.item_qty);
		$('.ctn_number').val("");
		$('.box_number').val("");
		$('.product_number').val("");
		$('.current_box_items').html("");
		$('.current_carton_box').html("");
		$('.add_ctn').prop('disabled', true);
		$('.add_product').prop('disabled', true);
		$('.add_product_all').prop('disabled', true);
		$('.add_box').prop('disabled', true);
		$('.product_weight').prop('readonly', true).prop('required', false);

		if (item.item_status === 'received') {
			$('.machine_on').addClass('hide');
			$('.machine_off').removeClass('hide');
			$('.qty_inward .count').html(item.item_qty);
			$('.qty_pending .count').html(0);
			return;
		}

		// GET CURRENT GRN STATE
		current_state = getInbound_state();
		// console.log(item);
		var box_qty_count, ctn_qty_count, inward_count, pending_count, inwarded_count = 0;
		$(".add_ctn").editable("destroy");
		$(".add_box").editable("destroy");

		$('.weight-group').addClass('hide');
		$('.add_product_all').removeClass('hide');
		if ($.inArray(item.category_name, weight_enable_categories) !== -1) {
			$('.weight-group').removeClass('hide');
			$('.add_product_all').addClass('hide');
		}
		if (current_state != "") {
			// console.log(item.lot_number+'CTN'+current_state.ctn_id);
			$('.ctn_number').val('CTN' + current_state.ctn_id);
			$('.add_ctn').prop('disabled', true);
			$('.add_box').prop('disabled', false);

			// console.log(item.lot_number+'BX'+current_state.box_id);
			$('.box_number').val('BOX' + current_state.box_id);
			$('.add_box').prop('disabled', true);
			$('.add_product').prop('disabled', false);
			$('.add_product_all').prop('disabled', false);
			if ($.inArray(item.category_name, weight_enable_categories) !== -1) {
				$('.product_weight').prop('readonly', false).prop('required', true);
			}

			if (current_state.box_id == "") {
				$('.box_number').val("");
				$('.add_box').prop('disabled', false);
				$('.add_product').prop('disabled', true);
				$('.add_product_all').prop('disabled', true);
			}

			var inwarded_count = getInwardedCount();
			ctn_qty_count = current_state.ctn_items;
			box_qty_count = current_state.box_items.length;

			$('.box_count').html(box_qty_count + '/' + current_state.box_qty);
			$('.ctn_count').html(current_state.ctn_items + '/' + current_state.ctn_qty);

			if (current_state.box_items.length > 0) {
				$.each(current_state.box_items, function (item_k, item_v) {
					$('.current_box_items').prepend('<li class="ms-elem-selectable" id="' + item_v + '-selectable"><span>' + item_v + '</span></li>');
					$('.product_number').val(item_v);

					if (box_qty_count == current_state.box_qty) {
						$('.add_product').prop('disabled', true);
						$('.add_product_all').prop('disabled', true);
						$('.add_box').prop('disabled', false);
						current_state.box_items = new Array();
						current_state.box_id = "";
						current_state.box_qty = "";
						box_qty_count = 0;
					}

					if (ctn_qty_count == current_state.ctn_qty) {
						// assign task to printing
						var formData = new FormData();
						formData.append("action", "addTask");
						formData.append("type", "printing");
						formData.append("ctnId", current_state.ctn_id);

						window.setTimeout(function () {
							var response = submitForm(formData, "POST");
							UIToastr.init(response.type, 'Task Allocation', response.msg);
						});

						$('.add_box').prop('disabled', true);
						$('.add_ctn').prop('disabled', false);
						current_state.ctn_id = "";
						current_state.ctn_qty = "";
						current_state.ctn_box = new Array();
						current_state.ctn_items = 0;
						ctn_qty_count = 0;
					}

					if (item.item_qty == current_state.ctn_items) {
						$('.add_ctn').prop('disabled', true);
						$('.add_box').prop('disabled', true);
						$('.add_product').prop('disabled', true);
						$('.add_product_all').prop('disabled', true);
					}
				});
			}

			$.each(current_state.ctn_box, function (item_k, item_v) {
				$('.current_carton_box').prepend('<li class="ms-elem-selectable" id="BOX' + item_v + '-selectable"><span>BOX' + item_v + '</span></li>');
			});

			/*if (ctn_qty_count == current_state.ctn_qty){
				$('.add_box').prop('disabled', true);
				$('.add_ctn').prop('disabled', false);
				current_state.ctn_id = "";
				current_state.ctn_qty = "";
				current_state.ctn_box = new Array();
				current_state.ctn_items = new Array();
				ctn_qty_count = 0;
			}*/
		} else {
			// DEFAULT STATE
			current_state = {
				"ctn_id": "",
				"ctn_qty": "",
				"ctn_box": new Array(),
				"ctn_items": 0,
				"box_id": "",
				"box_qty": "",
				"box_items": new Array(),
			};
			$('.add_ctn').prop('disabled', false);
			$('.add_box').prop('disabled', true);
			$('.add_product').prop('disabled', true);
			$('.add_product_all').prop('disabled', true);
			$('.box_count').html("");
			$('.ctn_count').html("");
			box_qty_count = 0;
			ctn_qty_count = 0;
		}
		inward_count = inwarded_count || 0;
		pending_count = item.item_qty - inward_count;
		$('.qty_inward .count').text(inward_count);
		$('.qty_pending .count').text(pending_count);

		if (pending_count === 0) {
			$('.machine_on').addClass('hide');
			$('.machine_off').removeClass('hide');
			update_grn_status({ 'grn_id': item.grn_id, 'item_id': item.item_id }, 'received');
		}

		$.fn.editable.defaults.inputclass = 'form-control input-small';

		// SAVE CTN
		$('.add_ctn').on('hidden', function (e, reason) {
			$('.add_ctn').prop('disabled', false);
			if (reason === 'save') {
				//auto-open next editable
				$(this).closest('div.form-group').next().find('.editable').editable('show');
				$('.add_ctn').editable('setValue', null);
				$('.add_ctn').prop('disabled', true);
				$('.add_box').prop('disabled', false);
			}
		});

		// OPEN CTN
		$('.add_ctn').on('shown', function (e, editable) {
			$('.add_ctn').prop('disabled', true);
		});

		// MAIN CTN EVENT
		$('.add_ctn').editable({
			url: 'ajax_load.php?action=create_ctn_id',
			title: 'Carton Qty',
			placement: 'right',
			errorLabelContainer: 'help-block-add-ctn',
			validate: function (value) {
				value = parseInt($.trim(value));
				// var pending_units = parseInt(current_state.ctn_qty)-parseInt(current_state.ctn_items);
				if (value == '') return 'This field is required';
				if (!$.isNumeric(value)) return 'Number values only';
				if (value > parseInt(item.item_qty)) return 'Qty cannot me more then received units ' + item.item_qty;
				if (value > parseInt(pending_count)) return 'Qty cannot me more then pending units ' + pending_count;
			},
			display: function (value) {
				$(this).html('<i class="fa fa-plus"></i>');
			},
			success: function (response, newValue) {
				var s = $.parseJSON(response);
				if (s.type == "success") {
					if (item.item_status === null) {
						update_grn_status({ 'grn_id': item.grn_id, 'item_id': item.item_id }, 'receiving');
					}
					var code = 'CTN' + s.ctn_id;
					var j_code = [{ "uid": code }];
					if (s.print == "true") {
						var data = createLabelData(j_code, 'ctn_box_label', 'ctn', s.quantity);
						data.copies = "1";
						qz_print(data);
					}
					$('.ctn_number').val(code);
					$('.current_carton_box').html("");
					$('.current_box_items').html("");
					current_state.ctn_id = s.ctn_id;
					current_state.ctn_qty = s.quantity;
					current_state.ctn_box = new Array();
					current_state.ctn_items = 0;
					ctn_qty_count = 0;
					saveInbound_state();
					$('.ctn_count').html('0/' + current_state.ctn_qty);
					$('.box_count').html('');
				} else {
					return false;
				}
			},
			error: function (errors) {
				var e = $.parseJSON(errors);
				UIToastr.init(e.type, 'Create New Carton', e.msg);
			}
		});

		// SAVE BOX
		$('.add_box').on('hidden', function (e, reason) {
			if (reason === 'save') {
				$('.add_box').editable('setValue', null);
				$('.add_box').prop('disabled', true);
				$('.add_product').prop('disabled', false);
				$('.add_product_all').prop('disabled', false);
			}
			$('.add_ctn').prop('disabled', true);
		});

		// OPEN BOX
		$('.add_box').on('shown', function (e, editable) {
			$('.add_box').prop('disabled', true);
		});

		// MAIN BOX EVENT
		$('.add_box').editable({
			url: 'ajax_load.php?action=create_box_id',
			title: 'Box Qty',
			placement: 'right',
			validate: function (value) {
				value = parseInt($.trim(value));
				var pending_units = parseInt(current_state.ctn_qty) - parseInt(current_state.ctn_items);
				if (value == '') return 'This field is required';
				if (!$.isNumeric(value)) return 'Number values only';
				if (value > parseInt(item.item_qty)) return 'Qty cannot me more then received units ' + item.item_qty;
				if (value > pending_units) return 'Qty cannot me more then pending units ' + pending_units;
				if (value > parseInt(current_state.ctn_qty)) return 'Qty cannot me more then carton units ' + current_state.ctn_qty;
			},
			display: function (value) {
				$(this).html('<i class="fa fa-plus"></i>');
			},
			success: function (response, newValue) {
				var s = $.parseJSON(response);
				if (s.type == "success") {
					var code = 'BOX' + s.box_id;
					var j_code = [{ "uid": code }];
					if (s.print == "true") {
						var data = createLabelData(j_code, 'ctn_box_label', 'box', s.quantity);
						data.copies = "5";
						qz_print(data);
					}
					$('.box_number').val(code);
					$('.current_box_items').html("");
					$('.current_carton_box').prepend('<li class="ms-elem-selectable" id="' + code + '-selectable"><span>' + code + '</span></li>');
					$('.add_product').prop('disabled', false);
					$('.add_product_all').prop('disabled', false);
					$('.add_box').prop('disabled', false);
					$('.add_box').editable('setValue', null);
					current_state.ctn_box.push(s.box_id);
					current_state.box_id = s.box_id;
					current_state.box_qty = s.quantity;
					current_state.box_items = new Array();
					box_qty_count = 0;
					saveInbound_state();
					$('.box_count').html('0/' + current_state.box_qty);
					box_qty_count = 0;
					if ($.inArray(item.category_name, weight_enable_categories) !== -1) {
						$('.product_weight').prop('readonly', false);
					}
				} else {
					return false;
				}
			},
			error: function (errors) {
				var e = $.parseJSON(errors);
				UIToastr.init(e.type, 'Create New Box', e.msg);
			}
		});

		$('.add_product').off().on('click', function (e, wasTriggered) {
			var label_type = 'product_label';
			var additionalData = {};
			if ($.inArray(item.category_name, weight_enable_categories) !== -1) {
				label_type = 'weighted_product_label';
				additionalData.weight = $('.product_weight').val();
				$('.product_weight').parent().closest('div').removeClass('has-error');
				if ($('.product_weight').val() == "") {
					$('.product_weight').parent().closest('div').addClass('has-error');
					return;
				}
			}

			if (!wasTriggered) {
				$('.add_product').prop('disabled', true);
				$('.add_product_all').prop('disabled', true);
			}

			var r = getProductId();
			// implement the loop for multiple labels
			if (r.type == "success") {
				var data = createLabelData(r.uids, label_type, '', '', additionalData); //
				data.copies = "1";
				qz_print(data);
				updateProductId(r.uids); //
				$.each(r.uids, function (k, s) {
					// window.setTimeout(function() {
					$('.product_number').val(s.uid);
					$('.current_box_items').prepend('<li class="ms-elem-selectable" id="' + s.uid + '-selectable"><span>' + s.uid + '</span></li>');
					current_state.ctn_items++;
					current_state.box_items.push(s.uid);
					box_qty_count++;
					ctn_qty_count++;
					$('.box_count').html(box_qty_count + '/' + current_state.box_qty);
					$('.ctn_count').html(ctn_qty_count + '/' + current_state.ctn_qty);
					inwarded_count++;
					// }, 100);
				});
				saveInbound_state();
				window.setTimeout(function () {
					if (box_qty_count == current_state.box_qty) {
						$('.add_product').prop('disabled', true);
						$('.add_product_all').prop('disabled', true);
						$('.add_box').prop('disabled', false).focus();
						// current_state.box_items = new Array();
						// current_state.box_id = "";
						// current_state.box_qty = "";
						// box_qty_count = 0;
					}

					if (ctn_qty_count == current_state.ctn_qty) {
						$('.add_box').prop('disabled', true);
						$('.add_ctn').prop('disabled', false).focus();
						// current_state.ctn_id = "";
						// current_state.ctn_qty = "";
						// current_state.ctn_box = new Array();
						// current_state.ctn_items= 0;
						// ctn_qty_count = 0;
					}

					pending_count = item.item_qty - inwarded_count;
					$('.qty_inward .count').text(inwarded_count);
					$('.qty_pending .count').text(pending_count);

					if (pending_count === 0) {
						update_grn_status({ 'grn_id': item.grn_id, 'item_id': item.item_id }, 'received');
						$('.add_ctn').prop('disabled', true);
						$('.add_box').prop('disabled', true);
						$('.add_product').prop('disabled', true);
						$('.add_product_all').prop('disabled', true);
						$('.machine_on').addClass('hide');
						$('.machine_off').removeClass('hide');
					}

					if (!wasTriggered) {
						$('.add_product').prop('disabled', false);
						$('.add_product_all').prop('disabled', false);
					}
				}, 100);
			} else {
				UIToastr.init(r.type, 'New Product Number', r.msg);
			}
		});

		$('.add_product_all').off().on('click', function (e) {
			e.preventDefault();
			$('.add_product_all').prop('disabled', true);
			$('.add_product').prop('disabled', true);
			limit = current_state.box_qty - box_qty_count;
			$('.add_product').trigger('click', true);
		});
	}

	function saveInbound_state() {
		var data = {};
		// console.log(current_state);
		data.item_id = item.item_id;
		data.grn_id = item.grn_id;
		data.box_id = current_state.box_id;
		data.box_items = current_state.box_items;
		data.box_qty = current_state.box_qty;
		data.ctn_box = current_state.ctn_box;
		data.ctn_id = current_state.ctn_id;
		data.ctn_items = current_state.ctn_items;
		data.ctn_qty = current_state.ctn_qty;
		data.category_name = item.category_name;

		var formData = new FormData();
		formData.append('action', 'update_inbound_state');
		formData.append('data', JSON.stringify(data));
		var s = submitForm(formData, "POST");
	}

	function getInbound_state() {
		var formData = 'action=get_inbound_state&item_id=' + item.item_id + '&grn_id=' + item.grn_id;
		var s = submitForm(formData, "GET");
		return s;
	}

	function update_grn_status(data, status) {
		data.action = 'update_grn_status';
		data.status = status;
		var formData = new FormData();
		for (var key in data) {
			formData.append(key, data[key]);
		}
		var s = submitForm(formData, "POST");
		if (s.type == "success") {
			UIToastr.init(s.type, 'Inbound Inventory Status', 'GRN Inventory status changed');
		} else {
			UIToastr.init(s.type, 'Inbound Inventory Status', s.msg);
		}
	}

	function getProductId() {
		var formData = 'action=get_inventory_id&item_id=' + item.item_id + '&grn_id=' + item.grn_id + '&limit=' + limit;
		var s = submitForm(formData, "GET");
		return s;
	}

	function updateProductId(uids) {
		var data = {};
		data.action = 'update_inventory_id';
		data.inv_ids = JSON.stringify(uids);
		data.ctn_id = current_state.ctn_id;
		data.box_id = current_state.box_id;

		var formData = new FormData();
		for (var key in data) {
			formData.append(key, data[key]);
		}
		var s = submitForm(formData, "POST");
		if (s.type == "success") {
			UIToastr.init(s.type, 'Inventory Inbound', 'New Product Number added');
		} else {
			UIToastr.init(s.type, 'Inventory Inbound', s.msg);
		}
	}

	function getInwardedCount() {
		var formData = 'action=get_inwarded_count&item_id=' + item.item_id + '&grn_id=' + item.grn_id;
		var s = submitForm(formData, "GET");
		return s.count;
	}

	function disableF5(e) {
		if ((e.which || e.keyCode) == 116 || (e.which || e.keyCode) == 82) {
			console.log(e);
			e.preventDefault();
			if (confirm("Are You sure?")) {
				endProcess(uid, "QC");
				window.location.reload();
			}
		}
	};
	// QC
	function qc_handleItem() {
		var uid = "";
		// var s = {"type":"success","sku":"SKMEI-1251-BLUE","thumb_image_url":"product-832.jpg","category":"Watches","issue_category":"1"};

		// var s = "";
		$('form#get-uid').submit(function (e) {
			e.preventDefault();
			uid = $('#uid').val().toUpperCase();
			$('#uid').addClass('spinner').attr('disabled', true);
			$('.product_details').addClass('hide');
			window.setTimeout(function () {
				var s = submitForm("action=get_uid_details&uid=" + uid, 'GET');
				if (s.type == "success") {
					$(document).on("keydown", disableF5);
					$('#sidelineProduct').click(function () {
						endProcess(uid, "QC");
						$('#uid').val("");
					});
					$('.ctn_id').addClass("input-group-addon").text("CTN" + String(s.ctn_id).padStart(9, '0'));
					$('.product_sku').text(s.sku);
					$('.product_image').attr('src', image_url + '/uploads/products/' + s.thumb_image_url);
					$('.product_details').removeClass('hide');
					$('.qc_uid').val(uid);
					$('.qc_category').val(s.category);
					$('.current_status').val(s.inv_status);
					// RESET
					$('.identified_issues').val('');
					$('.issue_type, .issue_identified').addClass('hide');
					$('.qc_status .btn-default').removeClass('btn-default').addClass('btn-success');
					$('.qc_status .btn').attr('disabled', false);
					// INIT
					reported_issues = Array();
					qcHotkey_handleRegistration('-,=,num_add,num_subtract', 'pass_fail', s); // PASS FAIL
					$('.btn-success').focus();

				} else {
					UIToastr.init(s.type, 'QC Check', s.msg);
				}
				$('#uid').removeClass('spinner').attr('disabled', false);
			}, 10);
		});


		$('form.product_details').submit(function (e) {
			e.preventDefault();
			var data = {};
			data.action = 'update_uid';
			data.category = $('.qc_category').val();
			data.inv_id = $('.qc_uid').val();
			data.identified_issues = $('.identified_issues').val();
			data.current_status = $('.current_status').val();

			var el = $('.product_details');
			App.blockUI({ target: el });

			var formData = new FormData();
			for (var key in data) {
				formData.append(key, data[key]);
			}
			window.setTimeout(function () {
				var s = submitForm(formData, 'POST');
				if (s.type == "success") {
					UIToastr.init(s.type, 'QC Check', s.msg);
					$('.product_details').addClass('hide');
					$('#uid').val("");
					window.setTimeout(function () {
						$('#uid').focus();
						App.unblockUI(el);
					}, 100);
				} else {
					App.unblockUI(el);
					UIToastr.init(s.type, 'QC Check', s.msg);
				}
			}, 10);
		});
	}

	function qcLoad_handleIssues(s) {
		// console.log('qcLoad_handleIssues');
		var cat_issues = issues[s.category];

		var issue_type = '';
		var issue_keys = 'enter,num_enter,0,num_0,';
		$.each(cat_issues, function (k, v) {
			issue_type += '<button class="icon-btn" data-issue_id="' + v.issue_id + '">' +
				'<span class="badge badge-important"> ' + v.issue_key + ' </span>' +
				'<div>' + v.issue + '</div>' +
				'</button>';
			issue_keys += 'num_' + v.issue_key + ',' + v.issue_key + ',';
		});
		issue_type += '<button class="icon-btn">' +
			'<span class="badge badge-important"> 0 </span>' +
			'<div><i class="fa fa-chevron-circle-left"></i> Back</div>' +
			'</button>';
		$('.issue_type').html(issue_type);
		$('.issue_type').removeClass('hide');
		$('.issues').addClass('hide');
		issue_keys = issue_keys.slice(0, -1);
		qcHotkey_handleRegistration(issue_keys, 'issues', s); // ISSUES
	}

	function qcLoad_handleSubIssues(s, issue_type) {
		// console.log('qcLoad_handleSubIssues');
		var cat_sub_issues = issues[s.category][issue_type].issues;
		var current_issue_type = issues[s.category][issue_type].issue;

		var sub_issue_type = '';
		var issue_keys = '0, num_0,';
		$.each(cat_sub_issues, function (k, v) {
			sub_issue_type += '<button id="issue_id_' + v.issue_key + '" class="icon-btn" data-issue_group="' + v.issue_group + '" data-issue_type="' + current_issue_type + '" data-issue="' + v.issue + '" data-issue_id="' + v.issue_id + '">' +
				'<span class="badge badge-important"> ' + v.issue_key + ' </span>' +
				'<div>' + v.issue + '</div>' +
				'</button>';
			issue_keys += 'num_' + v.issue_key + ',' + v.issue_key + ',';
		});
		sub_issue_type += '<button class="icon-btn">' +
			'<span class="badge badge-important"> 0 </span>' +
			'<div><i class="fa fa-chevron-circle-left"></i> Back</div>' +
			'</button>';
		$('.issues').html(sub_issue_type);
		$('.issues').removeClass('hide');
		$('.issue_type').addClass('hide');
		issue_keys = issue_keys.slice(0, -1);
		qcHotkey_handleRegistration(issue_keys, 'sub_issues', s); // ISSUES
	}

	function qcHotkey_handleRegistration(keys, k_scope, s) {
		// console.log('qcHotkey_handleRegistration');
		hotkeys.deleteScope(hotkeys.getScope());
		hotkeys(keys, { scope: k_scope, splitKey: '+' }, function (event, handler) {
			switch (k_scope) {
				case 'pass_fail':
					qcHotkey_handleStatus(handler, s);
					reported_issues = Array();
					manage_reported_issues('', '', '', '', '');
					break;
				case 'issues':
					qcHotkey_handleIssue(handler, s);
					break;
				case 'sub_issues':
					qcHotkey_handleSubIssue(handler, s);
					break;
			}
		});
		hotkeys.setScope(k_scope);
		$('.btn:not(#sidelineProduct), .icon-btn').off().on('click', function (e) {
			e.preventDefault();
		})
	}

	function qcHotkey_handleStatus(handler, s) {
		// console.log('qcHotkey_handleStatus');
		var handler_key = (handler.key.includes('num') ? handler.key.replace("num_", "") : handler.key);
		switch (handler_key) {
			case '-':
			case 'subtract':
				$('.issue_type, .issue_identified').removeClass('hide');
				$('.qc_status .btn-success').removeClass('btn-success').addClass('btn-default');
				$('.qc_status .btn').attr('disabled', true);
				qcLoad_handleIssues(s);
				break;
			case '=':
			case '+':
			case 'add':
				// UPDATE UID WITH QC_APPROVED UNDER COOLING PERIOD
				// console.log('QC PASS');
				hotkeys.deleteScope('pass_fail');
				$('form.product_details').trigger('submit');
				break;
		}
	}

	function qcHotkey_handleIssue(handler, s) {
		// console.log('qcHotkey_handleIssue');
		var handler_key = (handler.key.includes('num') ? handler.key.replace("num_", "") : handler.key);
		switch (handler_key) {
			case '0': // BACK
				// if all reported issues is empty
				$('.issue_type, .issue_identified').addClass('hide');
				$('.qc_status .btn-default').removeClass('btn-default').addClass('btn-success');
				$('.qc_status .btn').attr('disabled', false);
				qcHotkey_handleRegistration('-,=,num_add,num_subtract', 'pass_fail', s); // PASS FAIL
				reported_issues = Array();
				break;

			case 'enter':
				// https://stackoverflow.com/questions/1707650/detect-double-ctrl-keypress-in-js
				// ENTER KEY keyCode = 13
				// var dblCtrlKey = 0;
				// Event.observe(document, 'keydown', function(event) {
				// 	if (dblCtrlKey != 0 && event.keyCode == 17) {
				// 		alert("Ok double ctrl");
				// 	} else {
				// 		dblCtrlKey = setTimeout('dblCtrlKey = 0;', 300); // 1000 = 1 SECOND
				// 	}
				// });
				var identified_issues = JSON.stringify(reported_issues);
				if (identified_issues == "[]" || identified_issues == "{}") {
					UIToastr.init('info', 'Quality Check', 'No issues identifed.');
					return;
				} else {
					$('.identified_issues').val(identified_issues);
				}
				var el = $("form.product_details");
				App.blockUI({ target: el });
				window.setTimeout(function () {
					$('form.product_details').trigger('submit');
				}, 100);
				App.unblockUI({ target: el });
				// console.log('save and exit');
				break;

			default:
				qcLoad_handleSubIssues(s, handler_key);
				break;
		}
	}

	function qcHotkey_handleSubIssue(handler, s) {
		// console.log('qcHotkey_handleSubIssue');
		var handler_key = (handler.key.includes('num') ? handler.key.replace("num_", "") : handler.key);
		switch (handler_key) {
			case '0': // BACK
				qcLoad_handleIssues(s);
				break;

			default:
				// LOAD DATA TO ISSUE IDENTIFIED
				var btn_id = '#issue_id_' + handler_key;
				var issue_group = $(btn_id).data('issue_group');
				var issue_type = $(btn_id).data('issue_type');
				var issue = $(btn_id).data('issue');
				var issue_id = $(btn_id).data('issue_id');
				manage_reported_issues('add', issue, issue_id, issue_type, issue_group);
				qcLoad_handleIssues(s);
				break;
		}
	}

	function manage_reported_issues(type, issue, issue_id, issue_type, issue_group) {
		if (type == "add") {
			const exists = reported_issues.some(el => el.issue_id === issue_id);
			if (!exists)
				reported_issues.push({ 'issue_id': issue_id, 'issue': issue, 'issue_type': issue_type, 'issue_group': issue_group });
		}

		if (type == "delete") {
			var found_key = "";
			$.each(reported_issues, function (issue_key, issues) {
				if (issues.issue_id == issue_id)
					found_key = issue_key;
			});
			reported_issues.splice(found_key, 1);
		}

		var list = "";
		var identified_issues = {};
		$.each(reported_issues, function (key, issues) {
			var issue_type = issues.issue_type;
			if (Array.isArray(identified_issues[issue_type])) {
				identified_issues[issue_type].push(issues);
			} else {
				identified_issues[issue_type] = Array();
				identified_issues[issue_type].push(issues);
			}
		});

		$.each(identified_issues, function (issue_type, issues) {
			var i = 0;
			$.each(issues, function (issue_key, issue) {
				if (i == 0) {
					list += '<li class="ms-optgroup-label"><span>' + issue.issue_type + '</span></li>';
					i++;
				}
				list += '<li id="issue_' + issue.issue_id + '">' + issue.issue + '<span class="li_manage" data-group="' + issue.issue_type + '" data-id="issue_' + issue.issue_id + '"><i class="fa fa-times"></i></span></li>';
			});
		});
		$('.product_issues').html(list);

		// REMOVE ISSUES
		$('.li_manage').off().on('click', function () {
			var group = $(this).data('group');
			var id = $(this).data('id').replace('issue_', '');
			manage_reported_issues('delete', '', id, '', group);
		});
	}

	// QC ISSUES
	function qcIssues_handleItem() {
		$('.dd').nestable();
		// console.log(el.nestable('asNestedSet'));

		if (jQuery().select2) {
			$('select.select2').select2({
				placeholder: "Select",
				allowClear: true
			});
		}
		$('#category').change(function () {
			$('#group').html("");
			var group_option = "<option value=''></option>";
			if ($(this).select2('data') == null) {
				$('#group').append(group_option).trigger('change');
				return;
			}
			var category = $(this).select2('data').text;
			var options = issue_group[category];
			var group_option = "<option value=''></option>";
			$(options).each(function (k, v) {
				group_option += '<option value="' + options[k]['issue_id'] + '">' + options[k]['issue'] + '</option>';
			});
			$('#group').append(group_option);
		});

		// RESET MODAL FORM ON CLOSE
		$('#add_issue').on('show.bs.modal', function (e) {
			var form = $(this).find('form');
			$(form)[0].reset();
			$('#category').val('').trigger('change');
			$('#update_issue').val("0");
			$('#issue_id').val("");
		});

		//update_issue
		$('.edit').click(function () {
			var id = $(this).closest('li').data('id');
			var issue = $(this).closest('li').data('issue');
			var key = $(this).closest('li').data('keystroke');
			var group = $(this).closest('li').data('group');
			var category = $(this).closest('li').data('category');
			var categoryName = $(this).closest('li').data('categoryName');

			$('#add_issue').modal('show');
			$('#add_issue #issue').val(issue);
			$('#add_issue #issue_key').val(key);
			$('#add_issue #category').val(category).trigger('change');
			$('#add_issue #group').val(group).trigger('change');
			$('#add_issue #update_issue').val("1");
			$('#add_issue #issue_id').val(id);
		});
	}

	function qcIssues_handleValidation() {
		var form = $('#add-issue');
		var error1 = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				issue: {
					required: true,
				},
				issue_fix: {
					required: true,
				},
				issue_key: {
					required: true,
				},
				issue_category: {
					required: true,
				},
			},

			invalidHandler: function (event, validator) { //display error alert on form submit
				error1.show();
				App.scrollTo(error1, -200);
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
				if (element.attr("name") == "thumb_image_url") {
					error.appendTo("#thumb_image_url_error");
				} else {
					error.appendTo(element.parent("div"));
				}
			},

			submitHandler: function (form) {
				error1.hide();
				$('.form-actions .btn-submit', form).attr('disabled', true);
				$('.form-actions .btn-submit i', form).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				var is_update = 0;
				$.each(other_data, function (key, input) {
					if (input.name == "update" && input.value == "1")
						is_update = 1;
					formData.append(input.name, input.value);
				});

				if (is_update)
					formData.append('action', 'update_issue');
				else
					formData.append('action', 'add_issue');

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');
				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Add Product', s.msg);
						if (s.type == "success") {
							$('#add_issue').modal('hide');
							window.location.reload();
						}
					} else {
						UIToastr.init('error', 'Add Product', 'Error Processing Request. Please try again later.');
					}
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');
				}, 100);
			}
		});
	}

	// QC REPAIRS
	function repairs_handleItem() {
		var uid = "";
		$('form#get-uid').submit(function (e) {
			e.preventDefault();
			$('.product_details').addClass('hide');
			uid = $('#uid').val().toUpperCase();
			$('#uid').addClass('spinner').attr('disabled', true);
			window.setTimeout(function () {
				var s = submitForm("action=get_uid_repairs&uid=" + uid, 'GET');
				if (s.type == "success") {
					$('.product_sku').text(s.sku);
					$('.product_image').attr('src', image_url + '/uploads/products/' + s.thumb_image_url);
					$('.product_details').removeClass('hide');
					$('.uid').val(uid);
					$('.category').val(s.category);
					// RESET
					$('.fixed_issues, .requested_parts').empty();
					$('.issue_type, .issue_identified').addClass('hide');
					$('.qc_status .btn-default').removeClass('btn-default').addClass('btn-success');
					$('.qc_status .btn').attr('disabled', false);
					$('.previous_history').addClass('hide');
					if (s.historical_repairs) {
						$('.previous_history').removeClass('hide');
						var content = "";
						$(s.history).each(function (k, log) {
							var issue_list = "";
							var product_issues = $.parseJSON(log.issues);

							$.each(product_issues, function (issue_id, issue_status) {
								// GET DETAIL OF IDENTIFED ISSUES
								$.each(issues[s.category], function (issue_key, issues) {
									$.each(issues.issues, function (sub_issue_key, sub_issue) {
										if (sub_issue.issue_id == issue_id) {
											issue_list += issues.issue + ' :: ' + sub_issue.issue + '<br />';
										}
									});
								});
							});

							var repair_class = 'success';
							if (log.log_type == "qc_failed")
								repair_class = 'danger';

							content += '<div class="timeline-items">' +
								'<div class="timeline-label">' + log.log_date + '</div>' +
								'<div class="timeline-status">' +
								'<span class="label label-outline-' + repair_class + ' label-inline bold">' + log.log_type + '</span>' +
								'</div>' +
								'<div class="timeline-item timeline-content text-muted font-weight-normal">' +
								issue_list +
								'</div>' +
								'</div>';
						});
						$('.timeline').empty().append(content);
					}
					// INIT
					repairs_issues = Array();
					requested_componenets = Array();
					repairs_handleIssue(s);
					$('.fixed_issues').focus();

					function disableF5(e) {
						if ((e.which || e.keyCode) == 116 || (e.which || e.keyCode) == 82) {
							console.log(e);
							e.preventDefault();
							if (confirm("Are You sure?")) {
								endProcess(uid, "Repair");
								window.location.reload();
							}
						}
					};

					$(document).ready(function () {
						$(document).on("keydown", disableF5);
					});

				} else {
					UIToastr.init(s.type, 'QC Check', s.msg);
				}
				$('#uid').removeClass('spinner').attr('disabled', false);
			}, 20);
		});

		$('#sidelineProduct').click(function () {
			endProcess(uid, "Repair");
		});

		$('form.product_details').submit(function (e) {
			e.preventDefault();
			var data = {};
			data.action = 'update_uid_repairs';
			data.inv_id = $('.uid').val();
			data.fixed_issues = $('.issues_fixed').val();
			data.components_requested = $('.components_requested').val();

			var el = $('.product_details');
			App.blockUI({ target: el });

			var formData = new FormData();
			for (var key in data) {
				formData.append(key, data[key]);
			}
			window.setTimeout(function () {
				var s = submitForm(formData, 'POST');
				if (s.type == "success") {
					UIToastr.init(s.type, 'Repairs', s.msg);
					$('.product_details').addClass('hide');
					$('#uid').val("");
					window.setTimeout(function () {
						$('#uid').focus();
						App.unblockUI(el);
					}, 100);
				} else {
					App.unblockUI(el);
					UIToastr.init(s.type, 'Repairs', s.msg);
				}
			}, 20);
		});
	}

	var grouped_issues = [], offset = 0, page_size = 9, issue_type = "";;
	function repairs_handleIssue(s) {
		identified_issues = $.parseJSON(s.issues);
		var found_issues = Array();
		// FILTERING IDENTIFED ISSUES
		$.each(identified_issues, function (issue_id, issue_status) {
			if (!issue_status) {
				// GET DETAIL OF IDENTIFED ISSUES
				$.each(issues[s.category], function (issue_key, issues) {
					$.each(issues.issues, function (sub_issue_key, sub_issue) {
						if (sub_issue.issue_id == issue_id) {
							found_issues.push(sub_issue);
						}
					});
				});
			}
		});

		// ISSUES GROUPING
		var grouped = found_issues.reduce(function (r, a) {
			(r[a.issue_group] = r[a.issue_group] || []).push(a);
			return r;
		}, {});

		// GROUPED ISSUE WITH PARENTS DETAILS
		grouped_issues = [];
		$.each(issue_group[s.category], function (k, v_issue) {
			var issue = v_issue;
			issue.issues = grouped[v_issue['issue_id']];
			if (typeof (issue.issues) !== "undefined")
				grouped_issues[v_issue['issue_id']] = issue;
		});

		// REQUESTED COMPONENTS
		if (s.components_requested) {
			requested_componenets = JSON.parse(s.components_requested);
			$.each(requested_componenets, function (k, component) {
				manage_components_request('add', component.component_id, component.component_name);
			});
		}

		repairLoad_handleIssues(s);
	}

	function repairLoad_handleIssues(s) {
		var issue_type = '';
		var issue_keys = 'enter, 9, num_9,';
		$.each(grouped_issues, function (k, v) {
			if (typeof (v) != "undefined") {
				issue_type += '<button class="icon-btn" data-issue_id="' + v.issue_id + '">' +
					'<span class="badge badge-important"> ' + v.issue_key + ' </span>' +
					'<div>' + v.issue + '</div>' +
					'</button>';
				issue_keys += 'num_' + v.issue_key + ',' + v.issue_key + ',';
			}
		});
		issue_type += '<button class="icon-btn pull-right">' +
			'<span class="badge badge-important"> 9 </span>' +
			'<div><i class="fa fa-toolbox"></i> Request Component</div>' +
			'</button>';
		$('.issue_type').html(issue_type);
		$('.issue_type').removeClass('hide');
		$('.issues').addClass('hide');
		issue_keys = issue_keys.slice(0, -1);
		repairHotkey_handleRegistration(issue_keys, 'issues', s); // ISSUES	
	}

	function repairLoad_handleSubIssues(s, issue_type) {
		// console.log('qcLoad_handleSubIssues');
		var cat_sub_issues = grouped_issues[issue_type].issues;

		var issue_fixes = [];
		var fix_id = 1;
		$.each(cat_sub_issues, function (k, sub_issues) {
			var fixes = sub_issues.issue_fix.split(", ");
			$.each(fixes, function (k, v) {
				var issue_f = {
					'issue_key': sub_issues.issue_key,
					'issue_group': sub_issues.issue_group,
					'issue_type': issues[s.category][issue_type].issue,
					'issue': sub_issues.issue,
					'issue_id': sub_issues.issue_id,
					'fix': v,
					'fix_id': fix_id++,
				};
				issue_fixes.push(issue_f);
			});
		});
		function chunkArray(myArray, chunk_size) {
			var results = [];
			while (myArray.length) {
				results.push(myArray.splice(0, chunk_size));
			}
			return results;
		}
		var issue_chunck = chunkArray(issue_fixes, page_size); // MAX 9 PER PAGE :: LIMITATION OF HOTKEYS.JS KEYCODE. CANNOT HAVE 2 DIGIT KEYCODE

		var sub_issue_type = '';
		var issue_keys = '-,num_subtract,=,+,num_add,0,num_0,';
		var key_code = 1;
		var issue_fix_count = issue_chunck.length;
		$.each(issue_chunck[offset], function (k, fix) {
			if (k <= 9) {
				sub_issue_type += '<button id="fix_id_' + fix.fix_id + '" class="icon-btn" data-fix="' + fix.fix + '" data-issue_group="' + fix.issue_group + '" data-issue_type="' + fix.issue_type + '" data-issue="' + fix.issue + '" data-issue_id="' + fix.issue_id + '">' +
					'<span class="badge badge-important"> ' + key_code + ' </span>' +
					'<div><b>' + fix.issue + "</b><br />" + fix.fix + '</div>' +
					'</button>';
				issue_keys += key_code + ',';
				issue_keys += 'num_' + key_code + ',' + key_code + ',';
				key_code++;
			}
		});
		sub_issue_type += '<button class="icon-btn pull-right">' +
			'<span class="badge badge-important"> 0 </span>' +
			'<div><i class="fa fa-chevron-circle-left"></i> Back</div>' +
			'</button>';

		if (offset == 0 && issue_fix_count > 1) {
			sub_issue_type += '<button class="icon-btn pull-right">' +
				'<span class="badge badge-important"> + </span>' +
				'<div>Next <i class="fa fa-chevron-right"></i></div>' +
				'</button>';
		}
		if (offset == issue_fix_count) {
			sub_issue_type += '<button class="icon-btn">' +
				'<span class="badge badge-important"> - </span>' +
				'<div><i class="fa fa-chevron-left"></i> Previous</div>' +
				'</button>';
		}
		if (offset != 0 && offset != issue_fix_count) {
			sub_issue_type += '<button class="icon-btn">' +
				'<span class="badge badge-important"> - </span>' +
				'<div><i class="fa fa-chevron-left"></i> Previous</div>' +
				'</button>'
			'<button class="icon-btn pull-right">' +
				'<span class="badge badge-important"> + </span>' +
				'<div>Next <i class="fa fa-chevron-right"></i></div>' +
				'</button>';
		}

		$('.issues').html(sub_issue_type);
		$('.issues').removeClass('hide');
		$('.issue_type').addClass('hide');
		issue_keys = issue_keys.slice(0, -1);
		repairHotkey_handleRegistration(issue_keys, 'sub_issues', s); // ISSUES
	}

	function repairHotkey_handleRegistration(keys, k_scope, s) {
		// console.log('qcHotkey_handleRegistration');
		hotkeys.deleteScope(hotkeys.getScope());
		hotkeys(keys, { scope: k_scope, splitKey: '+' }, function (event, handler) {
			switch (k_scope) {
				case 'issues':
					repairHotkey_handleIssue(handler, s);
					break;
				case 'sub_issues':
					repairHotkey_handleSubIssue(handler, s);
					break;
				case 'components':
					repairHotkey_handleComponents(handler, s);
					break;
			}
		});
		hotkeys.setScope(k_scope);
		$('.btn, .icon-btn').off().on('click', function (e) {
			e.preventDefault();
		})
	}

	function repairHotkey_handleIssue(handler, s) {
		// console.log('qcHotkey_handleIssue');
		var handler_key = (handler.key.includes('num') ? handler.key.replace("num_", "") : handler.key);
		switch (handler_key) {
			case '9': // COMPONENT REQUEST
				// console.log('requestCompontent_handleItems');
				requestCompontent_handleItems(s);
				break;

			case 'enter': // SAVE & EXIT
			case 'num_enter': // SAVE & EXIT
				// console.log('SAVE & EXIT');
				var issues_fixed = JSON.stringify(repairs_issues);
				var components_requested = "";
				if (requested_componenets != "")
					components_requested = JSON.stringify(requested_componenets);
				if (issues_fixed == "[]" && requested_componenets == "") {
					UIToastr.init('info', 'Product Repairs', 'No issues fixed or component requested.');
					return;
				} else {
					$('.issues_fixed').val(issues_fixed);
					$('.components_requested').val(components_requested);
				}
				$('form.product_details').trigger('submit');
				break;

			default:
				issue_type = handler_key
				repairLoad_handleSubIssues(s, issue_type);
				break;
		}
	}

	function repairHotkey_handleSubIssue(handler, s) {
		// console.log('qcHotkey_handleSubIssue');
		var handler_key = (handler.key.includes('num') ? handler.key.replace("num_", "") : handler.key);
		switch (handler_key) {
			case '0': // BACK
			case 'num_0': // BACK
				offset = 0;
				repairLoad_handleIssues(s);
				break;

			case '+': // NEXT
			case '=':
			case 'num_add':
				offset++;
				repairLoad_handleSubIssues(s, issue_type);
				break;

			case '-': // PREVIOUS
			case 'num_subtract':
				offset--;
				repairLoad_handleSubIssues(s, issue_type);
				break;

			default:
				// LOAD DATA TO ISSUE IDENTIFIED
				var id = parseInt(offset * page_size) + parseInt(handler_key);
				var btn_id = '#fix_id_' + (id);
				var issue_fix = $(btn_id).data('fix');
				var issue_typ = $(btn_id).data('issue_type'); // GLOBAL
				var issue_group = $(btn_id).data('issue_group');
				var issue = $(btn_id).data('issue');
				var issue_id = $(btn_id).data('issue_id');
				manage_repairs_issues('add', issue, issue_id, issue_typ, issue_group, issue_fix);

				offset = 0;
				repairLoad_handleIssues(s);
				break;
		}
	}

	function repairHotkey_handleComponents(handler, s) {
		var handler_key = (handler.key.includes('num') ? handler.key.replace("num_", "") : handler.key);
		switch (handler_key) {
			case '0': // BACK
				offset = 0;
				repairLoad_handleIssues(s);
				break;

			case '+': // NEXT
			case '=':
			case 'add':
				offset++;
				requestCompontent_handleItems(s);
				break;

			case '-': // PREVIOUS
			case 'subtract':
				offset--;
				requestCompontent_handleItems(s);
				break;

			default:
				// LOAD DATA TO ISSUE IDENTIFIED
				var id = parseInt(offset * page_size) + parseInt(handler_key);
				var btn_id = '#component_id_' + (id);
				var component_id = $(btn_id).data('component_id');
				var component_name = $(btn_id).data('component_name');
				manage_components_request('add', component_id, component_name);
				offset = 0; // RESET
				repairLoad_handleIssues(s);
				break;
		}
	}

	function requestCompontent_handleItems(s) {
		var cat_components = inventory_components[s.category];
		function array_chunk(input, size) {
			for (var x, i = 0, c = -1, l = input.length, n = []; i < l; i++) {
				(x = i % size) ? n[c][x] = input[i] : n[++c] = [input[i]];
			}
			return n;
		}
		var components_chunck = array_chunk(cat_components, page_size); // MAX 9 PER PAGE :: LIMITATION OF HOTKEYS.JS KEYCODE. CANNOT HAVE 2 DIGIT KEYCODE

		var available_components = '';
		var components_keys = 'num_subtract,num_add,num_0,-,=,+,0,';
		var key_code = 1;
		var component_count = components_chunck.length - 1;
		$.each(components_chunck[offset], function (k, component) {
			if (k <= 9) {
				available_components += '<button id="component_id_' + component.component_id + '" class="icon-btn" data-component_name="' + component.component_name + '" data-component_id="' + component.component_id + '">' +
					'<span class="badge badge-important"> ' + key_code + ' </span>' +
					'<div>' + component.component_name + '</div>' +
					'</button>';
				components_keys += 'num_' + key_code + ',' + key_code + ',';
				key_code++;
			}
		});
		available_components += '<button class="icon-btn pull-right">' +
			'<span class="badge badge-important"> 0 </span>' +
			'<div><i class="fa fa-chevron-circle-left"></i> Back</div>' +
			'</button>';

		if (offset == 0) {
			available_components += '<button class="icon-btn pull-right">' +
				'<span class="badge badge-important"> + </span>' +
				'<div>Next <i class="fa fa-chevron-right"></i></div>' +
				'</button>';
		}
		if (offset == component_count) {
			available_components += '<button class="icon-btn">' +
				'<span class="badge badge-important"> - </span>' +
				'<div><i class="fa fa-chevron-left"></i> Previous</div>' +
				'</button>';
		}

		if (offset != 0 && offset != component_count) {
			available_components += '<button class="icon-btn">' +
				'<span class="badge badge-important"> - </span>' +
				'<div><i class="fa fa-chevron-left"></i> Previous</div>' +
				'</button>' +
				'<button class="icon-btn pull-right">' +
				'<span class="badge badge-important"> + </span>' +
				'<div>Next <i class="fa fa-chevron-right"></i></div>' +
				'</button>';
		}

		$('.issues').html(available_components);
		$('.issues').removeClass('hide');
		$('.issue_type').addClass('hide');
		components_keys = components_keys.slice(0, -1);
		repairHotkey_handleRegistration(components_keys, 'components', s); // ISSUES
	}

	function manage_repairs_issues(type, issue, issue_id, issue_type, issue_group, issue_fix) {
		if (type == "add") {
			const exists = repairs_issues.some(el => el.issue_fix === issue_fix);
			if (!exists)
				repairs_issues.push({ 'issue_id': issue_id, 'issue': issue, 'issue_type': issue_type, 'issue_group': issue_group, 'issue_fix': issue_fix });
		}

		if (type == "delete") {
			var found_key = "";
			$.each(repairs_issues, function (issue_key, issues) {
				if (issues.issue_id == issue_id && issues.issue_fix == issue_fix)
					found_key = issue_key;
			});
			repairs_issues.splice(found_key, 1);
		}

		var list = "";
		var fixed_issues = {};
		$.each(repairs_issues, function (key, issues) {
			var issue_type = issues.issue_type;
			if (Array.isArray(fixed_issues[issue_type])) {
				fixed_issues[issue_type].push(issues);
			} else {
				fixed_issues[issue_type] = Array();
				fixed_issues[issue_type].push(issues);
			}
		});

		$.each(fixed_issues, function (issue_type, issues) {
			var i = 0;
			$.each(issues, function (issue_key, issue) {
				if (i == 0) {
					list += '<li class="ms-optgroup-label"><span>' + issue.issue_type + '</span></li>';
					i++;
				}
				list += '<li id="issue_' + issue.issue_id + '">' + issue.issue_fix + '<span class="li_manage" data-group="' + issue.issue_type + '" data-fix="' + issue.issue_fix + '" data-id="issue_' + issue.issue_id + '"><i class="fa fa-times"></i></span></li>';
			});
		});
		$('.fixed_issues').html(list);

		// REMOVE ISSUES
		$('.li_manage').off().on('click', function () {
			var group = $(this).data('group');
			var fix = $(this).data('fix');
			var id = $(this).data('id').replace('issue_', '');
			manage_repairs_issues('delete', '', id, '', group, fix);
		});
	}

	function manage_components_request(type, component_id, component_name) {
		if (type == "add") {
			const exists = requested_componenets.some(el => el.component_id === component_id);
			if (!exists)
				requested_componenets.push({ 'component_id': component_id, 'component_name': component_name });
		}

		if (type == "delete") {
			var found_key = "";
			$.each(requested_componenets, function (component_key, component) {
				if (component.component_id == component_id)
					found_key = component_key;
			});
			requested_componenets.splice(found_key, 1);
		}

		var list = "";
		$.each(requested_componenets, function (component_key, component) {
			list += '<li id="component_id_' + component.component_id + '">' + component.component_name + '<span class="li_manage" data-id="component_id_' + component.component_id + '"><i class="fa fa-times"></i></span></li>';
		});
		$('.requested_parts').html(list);

		// REMOVE ISSUES
		$('.li_manage').off().on('click', function () {
			var id = $(this).data('id').replace('component_id_', '');
			manage_components_request('delete', id);
		});
	}

	// HISTORY
	function history_handleTimeline() {
		$('form#get-uid').submit(function (e) {
			e.preventDefault();
			$('.product_history').addClass('hide');
			$('.product_history').empty();
			var uid = $('#uid').val();
			$('#uid').addClass('spinner').attr('disabled', true);
			window.setTimeout(function () {
				var s = submitForm("action=product_timeline&uid=" + uid, 'GET');
				if (s.type == "success") {
					$('.product_history').removeClass('hide');
					$('.product_history').html(s.content);
				} else {
					UIToastr.init(s.type, 'Inventory History', s.msg);
					$('.product_history').addClass('hide');
				}
				$('#uid').removeClass('spinner').attr('disabled', false);
			}, 10);
		});
	}

	function createLabelData(uids, labelType, labelFor, units, additionalData) {
		var sku = "";
		if (typeof item !== 'undefined')
			sku = item.sku;
		var size = printer_settings[labelType].size.split('x');
		if (labelType == "weighted_product_label") {
			var formData = "action=get_weighted_product_label_pdf&uids=" + JSON.stringify(uids) + "&labelFor=" + labelFor + "&qty=" + units + "&sku=" + sku + "&weight=" + additionalData.weight
		} else if (labelType == "ctn_box_label") {
			var formData = "action=get_ctn_box_label_pdf&uids=" + JSON.stringify(uids) + "&labelFor=" + labelFor + "&qty=" + units + "&sku=" + sku;
		} else {
			var formData = "action=get_product_label_pdf&uids=" + JSON.stringify(uids) + "&labelFor=" + labelFor + "&qty=" + units + "&sku=" + sku;
		}

		var data = Array();
		data['printer'] = printer_settings[labelType].printer;
		data['sizes'] = { "width": size[0], "height": size[1] };
		data['code'] = 'Label';
		$.ajax({
			url: "ajax_load.php?token=" + new Date().getTime(),
			cache: false,
			type: "GET",
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			success: function (s) {
				// data['content'] = [{type: 'raw', format: 'base64', data: s}];
				data['content'] = [{ type: 'pixel', format: 'pdf', flavor: 'base64', data: s }];
				// data['content'] = [{type: 'pixel', format: 'pdf', flavor: 'file', data: s}];
			},
			error: function (e) {
				console.log('Error Processing your Request!!');
			}
		});

		return data;
	}

	function qz_print(data) {
		// Qz.init()
		// .then(function() {
		var printer_response = Qz.printWithPrinter(data['printer'], data['content'], data['copies'], data['sizes'], data['code']);
		// console.log(printer_response);
		// return printer_response;
		// }, function(e) {
		// 	UIToastr.init('error', 'QZ Tray', "QZ not available!!!");
		// 	console.log(e);
		// 	return e;			
		// });
	}

	function manage_handleSelect() {
		$('.manage_type').change(function () {
			var manage_type = $(this).val();
			$('.type_manage, .reprint, .move_to, .status').addClass('hide');
			$('.units, .qty, .update_uid, .btn-success').attr('disabled', true);
			$('.status_select').val("").trigger('change');
			switch (manage_type) {
				case 'reprint':
				case 'reprint_ctn_box':
					$('.type_manage, .reprint').removeClass('hide');
					$('.units, .qty, .btn-success').attr('disabled', false);
					break;

				case 'move_to':
					$('.type_manage, .move_to').removeClass('hide');
					$('.update_uid, .btn-success').attr('disabled', false);
					break;

				case 'status':
					$('.type_manage, .status').removeClass('hide');
					$('.status_select, .btn-success').attr('disabled', false);
					break;
			}
		});
	}

	function manage_handleValidation() {
		var form = $('#update-inventory');
		var error1 = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				error1.show();
				App.scrollTo(error1, -200);
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
				if (element.parent(".input-group").size() > 0) {
					error.insertAfter(element.parent(".input-group"));
				} else {
					error.insertAfter(element); // for other inputs, just perform default behavior
				}
			},

			submitHandler: function (form) {
				// error1.hide();
				$('.btn-success', form).attr('disabled', true);
				$('.btn-success i', form).addClass('fa fa-sync fa-spin');

				var manage_type = false;
				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'manage_inventory');
				$.each(other_data, function (key, input) {
					if (input.name == "manage_type" && (input.value == "reprint" || input.value == "reprint_ctn_box"))
						manage_type = true;
					formData.append(input.name, input.value);
				});

				$('.btn-success', form).attr('disabled', true);
				$('.btn-success i', form).addClass('fa fa-sync fa-spin');

				if (manage_type) {
					var uid = $('.uid').val();
					var units = $('.units').val();
					var qty = $('.qty').val();
					var label_type = ""; -1 != uid.indexOf("CTN") && (label_type = "ctn"), -1 != uid.indexOf("BOX") && (label_type = "box");
					uid = [{ 'uid': uid }];
					if (label_type == "ctn" || label_type == "box") {
						var data = createLabelData(uid, 'ctn_box_label', label_type, units);
					} else {
						var data = createLabelData(uid, 'product_label', label_type, units);
					}

					data.copies = qty;
					qz_print(data);
					$('.manage_type').val("").trigger('change');
					$('.uid').val("");
				} else {
					window.setTimeout(function () {
						var s = submitForm(formData, "POST");
						if (s.type == "success" || s.type == "error") {
							UIToastr.init(s.type, 'Manage Inventory', s.msg);
							if (s.type == "success") {
								$('.uid').val("");
								$('.manage_type').val("").trigger('change');
							}
						} else {
							UIToastr.init('error', 'Manage Inventory', 'Error Processing Request. Please try again later.');
						}
					}, 500);
				}
				$('.btn-success', form).attr('disabled', false);
				$('.btn-success i', form).removeClass('fa-sync fa-spin');
			}
		});
	}

	function manage_handleRePrint() {
		var form = $('#reprint-inventory');
		var error1 = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				error1.show();
				App.scrollTo(error1, -200);
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
				if (element.parent(".input-group").size() > 0) {
					error.insertAfter(element.parent(".input-group"));
				} else {
					error.insertAfter(element); // for other inputs, just perform default behavior
				}
			},

			submitHandler: function (form) {
				// error1.hide();
				$('.btn-success', form).attr('disabled', true);
				$('.btn-success i', form).addClass('fa fa-sync fa-spin');

				var weighted = false;
				$('.btn-success', form).attr('disabled', true);
				$('.btn-success i', form).addClass('fa fa-sync fa-spin');

				var uid = $('.uid', form).val();
				uid = [{ 'uid': uid }];
				var additional_data = {};
				additional_data.weight = $('.product_weight', form).val();
				var reprint_type = 'product_label';
				if (additional_data.weight != "")
					reprint_type = 'weighted_product_label';
				var data = createLabelData(uid, reprint_type, "", "", additional_data);
				data.copies = 1;
				qz_print(data);
				// $('.uid').val("");
				// $('.product_weight').val("");

				$('.btn-success', form).attr('disabled', false);
				$('.btn-success i', form).removeClass('fa-sync fa-spin');
			}
		});
	}

	function swap_handleSelect() {
		$('.grn_id').change(function () {
			// GET GRN
			var grn_id = $(this).val();
			if (grn_id != "") {
				// GET GRN SKUs
				var formData = 'action=get_swap_gnr_items&grn_id=' + grn_id;
				var s = submitForm(formData, 'GET');
				if (s.type == "success") {
					var grn_sku_options = '<option></option>';
					var qty_obj = {};
					$.each(s.items, function (k, item) {
						grn_sku_options += "<option data-qty='" + item.qty + "' value='" + item.id + "'>" + item.sku + "</option>";
						qty_obj[item.id] = item.qty;
					});
					$('.grn_sku').select2('destroy');
					$('.grn_sku').empty().append(grn_sku_options);

					// Initialize select2me
					$('.grn_sku').select2({
						placeholder: "Select SKU",
						allowClear: true
					}).attr('disabled', false);

					$('.grn_sku').change(function () {
						var products_list = '<option></option>';
						$.each(all_parent_sku, function (k, v) {
							products_list += "<option value=" + v.key + ">" + v.value + "</option>";
						});

						$('.new_sku').select2('destroy');
						$('.new_sku').empty().append(products_list);
						$('.new_sku').select2({
							placeholder: "New SKU",
							allowClear: true
						}).attr('disabled', false);

						$('.qty').attr('max', qty_obj[$(this).val()]);
						$('.qty').attr('placeholder', qty_obj[$(this).val()]);

						$('.qty').attr('disabled', false);
						$('.btn-submit').attr('disabled', false);
					});
				}
			}

		});
	}

	function swap_handleValidation() {
		var form = $('#swap-inventory');
		var error1 = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				error1.show();
				App.scrollTo(error1, -200);
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
				if (element.parent(".input-group").size() > 0) {
					error.insertAfter(element.parent(".input-group"));
				} else {
					error.insertAfter(element); // for other inputs, just perform default behavior
				}
			},

			submitHandler: function (form) {
				// error1.hide();
				$('.btn-success', form).attr('disabled', true);
				$('.btn-success i', form).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'swap_inventory');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Swap Inventory', s.msg);
						if (s.type == "success") {
							$('.grn_id', form).val("").trigger('change');
							$('.grn_sku', form).val("").trigger('change').attr('disabled', true);
							$('.new_sku', form).val("").trigger('change').attr('disabled', true);
							$('.qty', form).val("").attr('disabled', true).attr('placeholder', 'Qty');
							$('.btn-submit', form).attr('disabled', true);
						}
					} else {
						UIToastr.init('error', 'Manage Inventory', 'Error Processing Request. Please try again later.');
					}
				}, 500);

				$('.btn-success', form).attr('disabled', false);
				$('.btn-success i', form).removeClass('fa-sync fa-spin');
			}
		});
	}

	function alter_handleSelect() {
		var products_list = '<option></option>';
		$.each(all_parent_sku, function (k, v) {
			products_list += "<option value=" + v.key + ">" + v.value + "</option>";
		});

		$('.alter_from').select2('destroy');
		$('.alter_to').select2('destroy');
		$('.alter_from, .alter_to').empty().append(products_list);
		$('.alter_from').select2({
			allowClear: true
		});
		$('.alter_to').select2({
			allowClear: true
		});
	}

	function alter_handleValidation() {
		var form = $('#alter-inventory');

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				// error1.show();
				App.scrollTo(form, -200);
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
				if (element.parent(".input-group").size() > 0) {
					error.insertAfter(element.parent(".input-group"));
				} else {
					error.insertAfter(element); // for other inputs, just perform default behavior
				}
			},

			submitHandler: function (form) {
				// error1.hide();
				$('.btn-success', form).attr('disabled', true);
				$('.btn-success i', form).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'alter_inventory');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});
				var _from = $('.alter_from').select2('data');
				formData.append('sku_from', _from.text);
				var _to = $('.alter_to').select2('data');
				formData.append('sku_to', _to.text);

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Alter Inventory', s.msg);
						if (s.type == "success") {
							$('.alter_from, .alter_to, .alter_reason, .product_ids', form).val("").trigger('change');
						}
					} else {
						UIToastr.init('error', 'Alter Inventory', 'Error Processing Request. Please try again later.');
					}

					$('.btn-success', form).attr('disabled', false);
					$('.btn-success i', form).removeClass('fa fa-sync fa-spin');
				}, 100);
			}
		});
	}

	// STOCK
	function stock_handleTable() {
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

		var table = $('#editable_stock');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 20,
			debug: true,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=inventory_stock",
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "sku",
					title: "SKU",
					columnFilter: 'selectFilter'
				}, {
					data: "inbound",
					title: "Inbound",
					columnFilter: 'rangeFilter'
				}, {
					data: "saleable",
					title: "Total Saleable",
					columnFilter: 'rangeFilter'
				}, {
					data: "qc_pending",
					title: "QC Pending",
					columnFilter: 'rangeFilter'
				}, {
					data: "qc_cooling",
					title: "QC Cooling",
					columnFilter: 'rangeFilter'
				}, {
					data: "qc_verified",
					title: "QC Verified",
					columnFilter: 'rangeFilter'
				}, {
					data: "qc_failed",
					title: "QC Failed",
					columnFilter: 'rangeFilter'
				}, {
					data: "components_requested",
					title: "Parts Request",
					columnFilter: 'rangeFilter'
				}, {
					data: "stock",
					title: "Total Stock",
					columnFilter: 'rangeFilter'
				},
			],
			order: [
				[0, 'asc']
			],
			columnDefs: [
				{
					targets: 0,
					width: "30%",
				}
			],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable();
			}
		});

		function loadFilters(t) {
			var parseDateValue = function (rawDate) {
				var d = moment(rawDate, "YYYY-MM-DD").format('YYYY-MM-DD');
				var dateArray = d.split("-");
				var parsedDate = dateArray[2] + dateArray[1] + dateArray[0];
				return parsedDate;
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
			var f = $('<tr class="filter"></tr>').appendTo($(oTable.table().header()));
			t.api().columns().every(function () {
				var s;
				switch (this.getColumnFilter()) {
					case 'inputFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input" data-col-index="' + this.index() + '"/>');
						break;

					case 'rangeFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="From" data-col-index="' + this.index() + '"/><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="To" data-col-index="' + this.index() + '"/>');
						break;

					case 'dateFilter':
						s = $('\t\t\t\t\t\t\t<div class="input-daterange"><div class="input-group input-group-sm date date-picker margin-bottom-5">\t\t\t\t\t\t\t\t<input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="From" \t\t\t\t\t\t\t\t data-col-index="' + this.index() + '"/>\t\t\t\t\t\t\t\t<span class="input-group-btn">\t\t\t\t\t\t\t\t\t<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t<div class="input-group input-group-sm date date-picker">\t\t\t\t\t\t\t\t<input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="To"\t\t\t\t\t\t\t\t data-col-index="' + this.index() + '"/>\t\t\t\t\t\t\t\t<span class="input-group-btn">\t\t\t\t\t\t\t\t\t<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\t\t</div></div>');
						break;

					case 'selectFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, f) {
							$(s).append('<option value="' + t + '">' + t + "</option>");
						});
						break;

					case 'statusFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, a) {
							$(s).append('<option value="' + t + '">' + statusFilters[t].title + "</option>")
						});
						break;

					case 'actionFilter':
						var i = $('<button class="btn btn-sm btn-warning filter-submit margin-bottom-5" title="Search"><i class="fa fa-search"></i></button>'),
							r = $('<button class="btn btn-sm btn-danger filter-cancel margin-bottom-5" title="Reset"><i class="fa fa-times"></i></button>');
						$("<th>").append(i).append(r).appendTo(f);
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

										var dates = a.split('|', 2);
										sD = dates[0];
										eD = dates[1];
										var dS = parseDateValue(sD);
										var dE = parseDateValue(eD);

										var fD = oTable
											.column(e)
											.data()
											.filter(function (v, i) {
												var evalDate = v === "" ? 0 : parseDateValue(v);
												if ((isNaN(dS) && isNaN(dE)) || (evalDate >= dS && evalDate <= dE)) {
													return true;
												}
												return false;
											});

										var d = "";
										for (var count = 0; count < fD.length; count++) {
											d += fD[count] + "|";
										}

										d = d.slice(0, -1);
										oTable.column(e).search(d ? "^" + d + "$" : "^" + "-" + "$", 1, !1, 1);
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
				"Actions" !== this.title() && $(s).appendTo($("<th>").appendTo(f));
			});
			var n = function () {
				t.api().columns().every(function () {
					this.visible() ? $(f).find("th").eq(this.index()).show() : $(f).find("th").eq(this.index()).hide();
				});
			};
			n(), window.onresize = n;

			// DEFAULT FUNCTIONS
			if (jQuery().datepicker) {
				$('.date-picker').datepicker({
					format: 'yyyy-mm-dd',
					autoclose: true,
				});
			}

			if (jQuery().select2) {
				$('select.select2, .dataTables_length select').select2({
					placeholder: "Select",
					allowClear: true
				});
			}

			// FILTER TOGGLE
			$('.btn-advance').html('<button class="btn btn-default"><i class="fa fa-filter"></i></button>');
			$('.filter').hide();
			$('.btn-advance').bind('click', function () {
				$('.filter').toggle();
			});
		};

		function afterInitDataTable() {
			// Reload DataTable
			$('.reload').bind('click', function (e) {
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
		};
	}

	function stockTransfer_handleForm() {
		// console.log('stockTransfer_handleForm');
		var marketplace = $(this).val();
		$('#marketplace').change(function () {
			marketplace = $(this).val();
			var options = '<option value=""></option>';
			if (marketplace == 'flipkart' || marketplace == 'amazon') {
				for (var i = 0; i < accounts[marketplace].length; i++) {
					options += '<option value="' + accounts[marketplace][i].account_name + '">' + accounts[marketplace][i].account_name + '</option>';
				}
			}
			$('#account').html(options);
		});

		$('#account').change(function () {
			var account_name = $(this).val();
			// console.log(account_name);
			let account = accounts[marketplace].find(o => o.account_name === account_name);

			var s_options = '<option value=""></option>';
			var current_warehouses = $.parseJSON(account.mp_warehouses);
			$(current_warehouses).each(function (k, warehouse) {
				s_options += '<option value="' + warehouse + '">' + warehouse + '</option>';
			});

			$('#shipped_to').html(s_options);
		});
	}

	function stockTransfer_handleValidation() {
		// console.log('stockTransfer_handleValidation');
		var form = $('#stock_transfer');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			focusCleanup: true,
			ignore: "",
			messages: { // custom messages for radio buttons and checkboxes
				product_ids: {
					required: "Add Product IDs"
				},
				marketplace: {
					required: "Select Marketplace"
				},
				account: {
					required: "Select Account"
				},
				shipment_id: {
					required: "Add Shipment/Consignment ID"
				},
				shipped_to: {
					required: "Select Shipped To"
				},
			},

			invalidHandler: function (event, validator) { //display error alert on form submit
				error.show();
				App.scrollTo(error, -200);
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

			errorPlacement: function (error, element) {
				if (element.attr("name") == "report_daterange") {
					error.appendTo(element.parent("div").parent("div"));
				} else {
					error.appendTo(element.parent("div"));
				}
			},

			submitHandler: function (form) {
				error.hide();

				$('.btn-success', form).attr('disabled', true);
				$('.btn-success i', form).addClass('fa fa-sync fa-spin');
				$('.error_data').addClass('hide');

				var data = $(form).serializeArray();
				var formData = new FormData();
				$.each(data, function (key, input) {
					formData.append(input.name, input.value);
				});

				setTimeout(function () {
					$.ajax({
						url: "ajax_load.php?token=" + new Date().getTime(),
						cache: true,
						type: 'POST',
						data: formData,
						contentType: false,
						processData: false,
						async: true,
						success: function (s) {
							s = $.parseJSON(s);
							if (s.redirectUrl) {
								window.location.href = s.redirectUrl;
							}
							if (s.data) {
								var error_uids = "Error with UID(s): ";
								$.each(s.data, function (uid, value) {
									error_uids += uid + ", ";
								});
								error_uids = error_uids.slice(0, -2);
								$('.error_data').text(error_uids);
								$('.error_data').removeClass('hide');
							}
							if (s.type == "success") {
								$('.form-control', form).val("").trigger('change');
							}
							UIToastr.init(s.type, 'Stock Transfer', s.message);
						},
						error: function () {
							UIToastr.init(s.type, 'Stock Transfer', s.message);
							// UIToastr.init('error', 'Return Reconciliation', 'Error processing request!! Please retry later.');
						}
					});
					$('.btn-success', form).attr('disabled', false);
					$('.btn-success i', form).removeClass('fa fa-sync fa-spin');
				}, 500);
			}
		});
	}

	function vendorReturn_handleValidation() {
		// console.log('vendorReturn_handleValidation');
		var form = $('#vendor_return');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			focusCleanup: true,
			ignore: "",
			messages: { // custom messages for radio buttons and checkboxes
				product_ids: {
					required: "Add Product IDs"
				},
				vendor: {
					required: "Select Vendor"
				},
				return_note_id: {
					required: "Add Return Note ID"
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
				label
					.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) {
				error.appendTo(element.parent("div"));
			},

			submitHandler: function (form) {
				error.hide();

				$('.btn-success', form).attr('disabled', true);
				$('.btn-success i', form).addClass('fa fa-sync fa-spin');
				$('.error_data').addClass('hide');

				var data = $(form).serializeArray();
				var formData = new FormData();
				$.each(data, function (key, input) {
					formData.append(input.name, input.value);
				});

				setTimeout(function () {
					$.ajax({
						url: "ajax_load.php?token=" + new Date().getTime(),
						cache: true,
						type: 'POST',
						data: formData,
						contentType: false,
						processData: false,
						async: false,
						success: function (s) {
							s = $.parseJSON(s);
							if (s.redirectUrl) {
								window.location.href = s.redirectUrl;
							}
							if (s.data) {
								var error_uids = "Error with UID(s): ";
								$.each(s.data, function (uid, value) {
									error_uids += uid + ", ";
								});
								error_uids = error_uids.slice(0, -2);
								$('.error_data').text(error_uids);
								$('.error_data').removeClass('hide');
							}
							if (s.type == "success") {
								$('.form-control', form).val("").trigger('change');
							}
							UIToastr.init(s.type, 'Vendor Return', s.message);
						},
						error: function () {
							UIToastr.init(s.type, 'Vendor Return', s.message);
						}
					});
					$('.btn-success', form).attr('disabled', false);
					$('.btn-success i', form).removeClass('fa fa-sync fa-spin');
				}, 500);
			}
		});
	}

	function stockReconciliation_handleForm() {
		// console.log('stockReconciliation_handleForm');
		$('form#reconciliation-sku').submit(function (e) {
			e.preventDefault();
			$('.reconciliation_details').addClass('hide');
			var sku = $('.sku').val();
			if (sku == "") {
				$('.sku').closest('div').addClass('has-error');
				return;
			}

			var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?sku=' + sku;
			window.history.pushState({ path: newurl }, '', newurl);
			stockReconciliation_handleValidation(sku);
			$('.export_reconciliation_details').attr('href', './ajax_load.php?action=export_reconciliation_details&sku=' + sku);
		});

		var para_sku = App.getURLParameter('sku');
		if (para_sku != "" && para_sku != null) {
			$('form#reconciliation-sku').submit();
		}
	}

	function stockReconciliation_handleValidation(sku) {
		// console.log('stockReconciliation_handleValidation');

		$('.btn-success').attr('disabled', true);
		$('.btn-success i').removeClass('fa-search').addClass('fa-sync fa-spin');

		window.setTimeout(function () {
			var s = submitForm("action=get_reconciliation_details&sku=" + sku, 'GET');
			if (s.type == "success" && s.count > 0) {
				$('.reconciliation_details').removeClass('hide');
				$.each(s.content, function (status, uids) {
					if (uids != "") {
						$('.' + status + '_uids').html(uids.join(' '));
						$('.' + status + '_count').text('[' + uids.length + ']');
					} else {
						$('.' + status + '_count').text('[0]');
					}
				});
			} else {
				UIToastr.init(s.type, 'SKU Reconciliation Report', s.msg);
				$('.reconciliation_details').addClass('hide');
			}
			$('.btn-success').attr('disabled', false);
			$('.btn-success i').removeClass('fa-sync fa-spin').addClass('fa-search');
		}, 10);
	}

	function stockAudit_handleForm() {
		$('#audit-uid').on('paste', function (event) {
			// Block the paste event
			event.preventDefault();
			alert('Pasting is not allowed!\nType the number manually OR Use Barcode Scanner');
		});
		var audio = document.getElementById('chatAudio');
		$('form#audit-uid').submit(function (e) {
			e.preventDefault();
			var form = $(this);
			$('.btn-success').attr('disabled', true);
			$('.btn-success i').addClass('fa fa-sync fa-spin');

			window.setTimeout(function () {
				var other_data = $(form).serializeArray();
				// console.log(other_data);
				var formData = new FormData();
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});
				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error" || s.type == "info") {
					$('.uid').val("");
					UIToastr.init(s.type, 'Audit', s.msg);
					if (s.type != "success")
						audio.play();

					var audit_response = '<hr />';
					$.each(s.status, function (status, uids) {
						var label_tag = 'default';
						if (status == "Sales")
							label_tag = 'danger';
						if (status == "Success")
							label_tag = 'success';
						if (status == "Audit Log Unsuccessful" || status == "Audit Unsuccessful")
							label_tag = 'warning';
						if (status == "Already Audited")
							label_tag = 'info';

						audit_response += '<p class=""class="help-block"><span class="label label-' + label_tag + '">' + status + ' (' + uids.length + ')</span> ' + uids.join(", ") + '</p>';
					});
					$('.audit_response').html(audit_response).removeClass('hide');
				} else {
					UIToastr.init('error', 'Audit', 'Error processing request!! Please retry later.');
				}

				$('.btn-success').attr('disabled', false);
				$('.btn-success i').removeClass('fa fa-sync fa-spin');
			}, 10);
		});
	}

	// CONTENT
	function stockContent_handleForm() {
		// console.log('stockReconciliation_handleForm');
		$('form#content').submit(function (e) {
			e.preventDefault();
			$('.content_details').addClass('hide');
			var box_number = $('.box_number').val();
			if (box_number == "") {
				$('.box_number').closest('div').addClass('has-error');
				return;
			}

			var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?box_number=' + box_number;
			window.history.pushState({ path: newurl }, '', newurl);
			stockContent_handleValidation(box_number);
			$('.export_content_details').attr('href', './ajax_load.php?action=export_content_details&box_number=' + box_number);
		});

		var para_sku = App.getURLParameter('box_number');
		if (para_sku != "" && para_sku != null) {
			$('form#content').submit();
		}
	}

	function stockContent_handleValidation(box_number) {

		$('.btn-success').attr('disabled', true);
		$('.btn-success i').removeClass('fa-search').addClass('fa-sync fa-spin');

		window.setTimeout(function () {
			var s = submitForm("action=get_content_details&box_number=" + box_number, 'GET');
			console.log(s);
			if (s.type == "success") {
				$('.content_details').removeClass('hide');
				$('.content_data').html(s.content);
			} else {
				UIToastr.init(s.type, 'box_number Reconciliation Report', s.msg);
				$('.content_details').addClass('hide');
			}
			$('.btn-success').attr('disabled', false);
			$('.btn-success i').removeClass('fa-sync fa-spin').addClass('fa-search');
		}, 10);
	}

	// ANALYTICS
	var startDate, endDate, daysCover, growthSpike;
	function stockAnalytics_handleInit() {
		startDate = moment().subtract(60, 'days');
		endDate = moment().subtract(1, 'days');
		daysCover = 30;
		growthSpike = 1;

		handleDateRangePickers();
		handleReportGeneration();
	}

	function handleDateRangePickers() {
		console.log('handleDateRangePickers');
		if (!jQuery().daterangepicker) {
			return;
		}

		function cb(start, end) {
			$('#report_daterange input').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
		}

		$('#report_daterange').daterangepicker({
			opens: (App.isRTL() ? 'left' : 'right'),
			autoApply: true,
			format: 'MM/DD/YYYY',
			separator: ' to ',
			startDate: moment().subtract(1, 'month'),
			endDate: moment(),
			ranges: {
				'Today': [moment(), moment()],
				'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
				'Last 7 Days': [moment().subtract(7, 'days'), moment().subtract(1, 'days')],
				'Last 30 Days': [moment().subtract(30, 'days'), moment().subtract(1, 'days')],
				'Last 60 Days': [moment().subtract(60, 'days'), moment().subtract(1, 'days')],
				'This Month': [moment().startOf('month'), moment().endOf('month')],
				'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
			},
			minDate: moment().subtract(365, 'days'),
			maxDate: moment().subtract(1, 'days'),
		}, cb);

		cb(startDate, endDate);
	}

	function handleReportGeneration() {
		var form2 = $('#analytics_details');
		var error2 = $('.alert-danger', form2);

		form2.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			focusCleanup: true,
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
				error2.show();
				App.scrollTo(error2, -200);
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

			errorPlacement: function (error, element) {
				if (element.attr("name") == "report_daterange") {
					error.appendTo(element.parent("div").parent("div"));
				} else {
					error.appendTo(element.parent("div"));
				}
			},

			submitHandler: function (form) {
				error2.hide();
				$('.inventory_analytics').addClass('hide');
				$('.btn-success').prop('disabled', true);

				var dateRange = $('input[name=report_daterange]', form).val();
				var daysCover = $('input[name=report_dayscover]', form).val();
				var growthSpike = $('input[name=report_growthspike]', form).val();
				stockAnalytics_handleTable(dateRange, daysCover, growthSpike);
				$('.inventory_analytics').removeClass('hide');
			}
		});
	}

	// stockAnalytics_handleTable(startDate, endDate, daysCover, growthSpike);
	function stockAnalytics_handleTable(dateRange, daysCover, growthSpike) {
		console.log('stockAnalytics_handleTable');

		if ($.fn.DataTable.isDataTable('#inventory_analytics')) {
			$('#inventory_analytics').DataTable().clear().draw();
			$('#inventory_analytics').DataTable().destroy();
			$('#inventory_analytics thead, #inventory_analytics tbody').remove();
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

		var table = $('#inventory_analytics');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 20,
			debug: false,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=analytics&dateRange=" + dateRange + "&daysCover=" + daysCover + "&growthSpike=" + growthSpike,
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "sku",
					title: "SKU",
					columnFilter: 'selectFilter'
				}, {
					data: "brandName",
					title: "Brand",
					columnFilter: 'selectFilter'
				}, {
					data: "website",
					title: "Website",
					columnFilter: 'rangeFilter'
				}, {
					data: "amazon",
					title: "Amazon",
					columnFilter: 'rangeFilter'
				}, {
					data: "flipkart",
					title: "Flipkart",
					columnFilter: 'rangeFilter'
				}, {
					data: "ajio",
					title: "Ajio",
					columnFilter: 'rangeFilter'
				}, {
					data: "jiomart",
					title: "Jiomart",
					columnFilter: 'rangeFilter'
				}, {
					data: "offline",
					title: "Offline",
					columnFilter: 'rangeFilter'
				}, {
					data: "total_sales",
					title: "Sales",
					columnFilter: 'rangeFilter'
				}, {
					data: "per_day_sales",
					title: "Sales/day",
					columnFilter: 'rangeFilter'
				}, {
					data: "oos_day",
					title: "Stock Out In",
					columnFilter: 'rangeFilter'
				}, {
					data: "stock",
					title: "Stock",
					columnFilter: 'rangeFilter'
				}, {
					data: "delta",
					title: "Shortage",
					columnFilter: 'rangeFilter'
				}, {
					data: "actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[8, 'desc']
			],
			columnDefs: [
				{
					targets: 0,
					width: "30%",
				}
			],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable();
			}
		});

		function loadFilters(t) {
			var parseDateValue = function (rawDate) {
				var d = moment(rawDate, "YYYY-MM-DD").format('YYYY-MM-DD');
				var dateArray = d.split("-");
				var parsedDate = dateArray[2] + dateArray[1] + dateArray[0];
				return parsedDate;
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
			var f = $('<tr class="filter"></tr>').appendTo($(oTable.table().header()));
			t.api().columns().every(function () {
				var s;
				switch (this.getColumnFilter()) {
					case 'inputFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input" data-col-index="' + this.index() + '"/>');
						break;

					case 'rangeFilter':
						s = $('<input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="From" data-col-index="' + this.index() + '"/><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="To" data-col-index="' + this.index() + '"/>');
						break;

					case 'dateFilter':
						s = $('\t\t\t\t\t\t\t<div class="input-daterange"><div class="input-group input-group-sm date date-picker margin-bottom-5">\t\t\t\t\t\t\t\t<input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="From" \t\t\t\t\t\t\t\t data-col-index="' + this.index() + '"/>\t\t\t\t\t\t\t\t<span class="input-group-btn">\t\t\t\t\t\t\t\t\t<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\t\t</div>\t\t\t\t\t\t\t<div class="input-group input-group-sm date date-picker">\t\t\t\t\t\t\t\t<input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="To"\t\t\t\t\t\t\t\t data-col-index="' + this.index() + '"/>\t\t\t\t\t\t\t\t<span class="input-group-btn">\t\t\t\t\t\t\t\t\t<button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button>\t\t\t\t\t\t\t\t</span>\t\t\t\t\t\t\t</div></div>');
						break;

					case 'selectFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, f) {
							$(s).append('<option value="' + t + '">' + t + "</option>");
						});
						break;

					case 'statusFilter':
						s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, a) {
							$(s).append('<option value="' + t + '">' + statusFilters[t].title + "</option>")
						});
						break;

					case 'actionFilter':
						var i = $('<button class="btn btn-sm btn-warning filter-submit margin-bottom-5" title="Search"><i class="fa fa-search"></i></button>'),
							r = $('<button class="btn btn-sm btn-danger filter-cancel margin-bottom-5" title="Reset"><i class="fa fa-times"></i></button>');
						$("<th>").append(i).append(r).appendTo(f);
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

										var dates = a.split('|', 2);
										sD = dates[0];
										eD = dates[1];
										var dS = parseDateValue(sD);
										var dE = parseDateValue(eD);

										var fD = oTable
											.column(e)
											.data()
											.filter(function (v, i) {
												var evalDate = v === "" ? 0 : parseDateValue(v);
												if ((isNaN(dS) && isNaN(dE)) || (evalDate >= dS && evalDate <= dE)) {
													return true;
												}
												return false;
											});

										var d = "";
										for (var count = 0; count < fD.length; count++) {
											d += fD[count] + "|";
										}

										d = d.slice(0, -1);
										oTable.column(e).search(d ? "^" + d + "$" : "^" + "-" + "$", 1, !1, 1);
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
				"Actions" !== this.title() && $(s).appendTo($("<th>").appendTo(f));
			});
			var n = function () {
				t.api().columns().every(function () {
					this.visible() ? $(f).find("th").eq(this.index()).show() : $(f).find("th").eq(this.index()).hide();
				});
			};
			n(), window.onresize = n;

			// DEFAULT FUNCTIONS
			if (jQuery().datepicker) {
				$('.date-picker').datepicker({
					format: 'yyyy-mm-dd',
					autoclose: true,
				});
			}

			if (jQuery().select2) {
				$('select.select2, .dataTables_length select').select2({
					placeholder: "Select",
					allowClear: true
				});
			}

			// FILTER TOGGLE
			$('.btn-advance').html('<button class="btn btn-default"><i class="fa fa-filter"></i></button>');
			$('.filter').hide();
			$('.btn-advance').bind('click', function () {
				$('.filter').toggle();
			});
		};

		function afterInitDataTable() {
			// Reload DataTable
			$('.reload').bind('click', function (e) {
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

			$('.btn-success').prop('disabled', false);
		};
	}

	// TEMP - MANAGE INVENTORY TO BOX AND CTN
	function getBoxDetails() {
		var formData = new FormData();
		formData.append('action', 'get_box');
		var r = submitForm(formData, "POST");
		if (r.type == "success") {
			$('.current_carton_box').empty();
			$.each(r.data, function (i, box) {
				$('.current_carton_box').prepend('<li class="ms-elem-selectable" id="' + box.boxId + '-selectable"><span><strong>BOX' + String(box.boxId).padStart(9, '0') + ' : </strong>' + box.invId + '</span></li>');
				console.log(boxItems);
				boxItems = Array.from(new Set(boxItems.concat(box.invId.split(','))));
				$('.ctn_count').html('Boxes Count# ' + r.data.length + ' <br />Units# ' + boxItems.length);
			});
		}
		else {
			UIToastr.init("info", 'No Active Box', r.msg);
		}
	}

	function addItem() {
		$(".product_number").on("paste", function (e) {
			e.preventDefault();
			alert("Pasting Is blocked!!!");
		})
		$(".product_number").keypress(function (event) {
			if (event.which === 13) {
				var item = $(".product_number").val();
				console.log(item);
				event.preventDefault();
				if (item.length == 12) {
					$('.product_number').val("").focus();
					var formData = new FormData();
					formData.append('action', 'lookup_item');
					formData.append('uid', item);
					$.when(
						submitForm(formData, "POST", true),
					).then(function (s) {
						if (s.type == "success" || s.type == "error") {
							if (s.type == "error") {
								UIToastr.init("error", 'Invalid Item ID', s.msg);
							}
							else {
								if (($.inArray(item, boxItems) !== -1) || ($.inArray(item, items) !== -1)) {
									UIToastr.init("error", 'Duplicate Item', "Item " + item + " is already in box");
								}
								else {
									if (items.length < 200) {
										items.push(item);
										box_items_sku.push(s.sku);
										ctn_items_sku.push(s.sku);
										$(".current_box_items").prepend('<li class="ms-elem-selectable" id="' + item + '-selectable"><span>' + item + '</span></li>');
										$('.box_count').html(items.length);
									}
									if (items.length > 199) {
										$(".product_number").prop('disabled', true);
										UIToastr.init("info", 'Box full', "Create New Box. Max count of 20 units reached");
									}
								}
							}
						}
					});
				}
				else {
					UIToastr.init("error", 'Invalid Item Id', "Please Enter Valid Item Id");
				}
			}
		});
	}

	function addBox() {
		$(".add_box").click(function () {
			$(".add_box").prop('disabled', true);
			$(".add_box i").removeClass("fa-plus").addClass("fa-spin fa-spinner");
			if (items.length > 0) {
				var formData = new FormData();
				formData.append('action', 'create_box');
				formData.append('items', JSON.stringify(items));
				$.when(
					submitForm(formData, "POST", true),
				).then(function (s) {
					if (s.type == "error") {
						UIToastr.init("error", 'Error', s.msg);
					}
					else {
						getBoxDetails();
						if (s.print == "true") {
							var code = 'BOX' + s.box_id;
							var j_code = [{ "uid": code }];
							item = {};
							var allEqual = box_items_sku => box_items_sku.every(v => v === box_items_sku[0]);
							if (allEqual(box_items_sku))
								item = { "sku": box_items_sku[0] };
							else
								item = { "sku": '--- Multi SKUs ---' };
							var data = createLabelData(j_code, 'ctn_box_label', 'box', items.length);
							data.copies = "1";
							qz_print(data);
						}
						UIToastr.init("success", 'Success', "Box Created : " + s.box_id);
						$('.current_box_items').html("");
						$('.box_count').html("");
						$('.product_number').prop('disabled', false);
						items = [];
						box_items_sku = [];
					}
					$(".add_box").prop('disabled', false);
					$(".add_box i").removeClass("fa-spin fa-spinner").addClass("fa-plus");
				});
			}
			else {
				$(".add_box").prop('disabled', false);
				$(".add_box i").removeClass("fa-spin fa-spinner").addClass("fa-plus");
				UIToastr.init("error", 'Error', "No Items to create box");
			}
		});
	}

	function addCartoon() {
		$(".add_ctn").click(function () {
			$('.product_number').prop('readonly', true);
			$(".add_box, .add_ctn").prop('disabled', true);
			$(".add_ctn i").removeClass("fa-plus").addClass("fa-spin fa-spinner");
			if (boxItems.length > 0) {
				var formData = new FormData();
				formData.append('action', 'create_ctn');
				formData.append('size', boxItems.length);
				$.when(
					submitForm(formData, "POST", true),
				).then(function (s) {
					if (s.type == "error") {
						UIToastr.init("error", 'Error', s.msg);
					}
					else {
						if (s.print == "true") {
							var code = 'CTN' + s.ctn_id;
							var j_code = [{ "uid": code }];
							item = {};
							const allEqual = ctn_items_sku => ctn_items_sku.every(v => v === ctn_items_sku[0]);
							if (allEqual(ctn_items_sku))
								item = { "sku": ctn_items_sku[0] };
							else
								item = { "sku": '--- Multi SKUs ---' };
							var data = createLabelData(j_code, 'ctn_box_label', 'ctn', boxItems.length);
							data.copies = "5";
							qz_print(data);
						}
						UIToastr.init(s.type, 'Success', "Carton Created : " + s.ctn_id);
						$('.box_count, .current_box_items, .current_carton_box, .ctn_count').empty();
						$('.product_number').prop('disabled', false);
						items = [];
						boxItems = [];
						ctn_items_sku = [];
					}
					$('.product_number').prop('readonly', false);
					$(".add_box, .add_ctn").prop('disabled', false);
					$(".add_ctn i").removeClass("fa-spin fa-spinner").addClass("fa-plus");
				});
			}
			else {
				$('.product_number').prop('readonly', false);
				$(".add_box, .add_ctn").prop('disabled', false);
				$(".add_ctn i").removeClass("fa-spin fa-spinner").addClass("fa-plus");
				UIToastr.init("error", 'Error', "No Items to create box");
			}
		});
	}

	function mapLocation_handleInit() {

		var tableData = new FormData();
		tableData.append('action', 'getLocations');

		var response = submitForm(tableData, "POST");
		if (response.type == "success") {
			var content = "";
			var count = 0;
			console.log(response);
			response.data.forEach(element => {
				++count;
				content += "<tr id='row_" + count + "'>";
				content += "<td>" + count + "</td>";
				content += "<td>" + element.locationId + "</td>";
				content += "<td><div class='sku" + count + "'>" + (element.sku == null ? "<span class='badge badge-dabner'>Not Assigned</span>" : element.sku) + "<div></td>";
				content += "<td><div class='skuAction" + count + "'><button data-rowid='" + count + "' class=\"edit btn btn-default btn-xs purple\" title=\"Edit\"><i class=\"fa fa-edit\"></i></button></div></td>";
				content += "</tr>";
			});
		}
		$("#locationBody").html(content);

		$(".edit").click(function () {
			var button = $(this);
			var row = $(this).data("rowid");
			var locationId = $("#row_" + row).find("td:eq(1)").text();
			var defaultSku = $('.sku' + row).html();

			var buttons = "<button class=\"btn btn-default btn-xs purple skuActionSuccess" + row + "\" title=\"save\"><i class=\"fa fa-check\"></i></button><button class=\"btn btn-default btn-xs purple skuActionCancel" + row + "\" title=\"cancel\"><i class=\"fa fa-times\"></i></button>";
			$(this).hide();
			$(".skuAction" + row).html(buttons);
			var formData = new FormData();
			formData.append("action", "getSKU");
			var response = submitForm(formData, "POST");
			if (response.type == "success") {
				var options = "";
				response.data.forEach(element => {
					options += "<option value='" + element.sku + "'>" + element.sku + "</option>";
				});
				var select = "<select class=\"skuValue form-control select2me\">" + options + "</select>"

				$('.sku' + row).html(select);
			}
			$(".skuActionSuccess" + row).click(function () {
				var sku = $(".skuValue").val();
				var updateData = new FormData();
				updateData.append("action", "setLocationSku");
				updateData.append("locationId", locationId);
				updateData.append("sku", sku);

				var response = submitForm(updateData, "POST");
				if (response.type == "success") {
					UIToastr.init('success', 'Mapping Completed', response.message);
					$('.sku' + row).html(sku);
					$(".skuAction" + row).html("<button data-rowid='" + row + "' class=\"edit btn btn-default btn-xs purple\" title=\"Edit\"><i class=\"fa fa-edit\"></i></button>");
					button.show();
				} else {
					UIToastr.init('error', 'Opps! Something went wrong.', response.message);
				}
			});
			$(".skuActionCancel" + row).click(function () {
				$('.sku' + row).html(defaultSku);
				$(".skuAction" + row).html("<button data-rowid='" + row + "' class=\"edit btn btn-default btn-xs purple\" title=\"Edit\"><i class=\"fa fa-edit\"></i></button>");
			});
		});

		/*
		$("#map_location").submit(function (e) {
			e.preventDefault();

			var category = $("#category").val();
			var sku = $("#sku").val();
			var location = $("#location").val();

			var formData = new FormData();
			formData.append("action", "map_location");
			formData.append("category", category);
			formData.append("sku", sku);
			formData.append("location", location);

			var response = submitForm(formData, "POST");
			if (response.type == "success") {
				UIToastr.init('success', 'mapping Completed', response.message);
			} else {
				UIToastr.init('error', 'Something went wrong', response.message);
			}
		});*/

		$("#add_location").submit(function (event) {
			event.preventDefault();
			$("#add_location .btn-success").html("<i class='fa fa-sync fa-spin'></i>").prop("disabled", true);

			var category = $("#category").val();
			var capacity = $("#capacity").val();

			var formData = new FormData();
			formData.append("action", "add_location");
			formData.append("category", category);
			formData.append("capacity", capacity);
			window.setTimeout(function () {
				var response = submitForm(formData, "POST");
				if (response.type == "success") {
					// window.setTimeout(function () {

					$("#add-location").modal("hide");
					$("#add_location .btn-success").html("Submit").prop("disabled", false);
					UIToastr.init('success', 'mapping Completed', response.message);
					// }, 200);
				} else {
					UIToastr.init('error', 'Something went wrong', response.message);
				}
			}, 100);
			window.location.reload();
			window.setTimeout(function () {
				var content = "";
				var tableData = new FormData();
				tableData.append('action', 'getLocations');
				var response = submitForm(tableData, "POST");
				if (response.type == "success") {
					var count = 0;
					console.log(response);
					response.data.forEach(element => {
						++count;
						content += "<tr id='row_" + count + "'>";
						content += "<td>" + count + "updated</td>";
						content += "<td>" + element.locationId + "</td>";
						content += "<td><div class='sku" + count + "'>" + (element.sku == null ? "<span class='badge badge-danger'>Not Assigned</span>" : element.sku) + "<div></td>";
						content += "<td><div class='skuAction" + count + "'><button data-rowid='" + count + "' class=\"edit btn btn-default btn-xs purple\" title=\"Edit\"><i class=\"fa fa-edit\"></i></button></div></td>";
						content += "</tr>";
					});
				}
				$("#locationBody").html(content);
				TableAdvanced.init();

			}, 100);
		});
	}

	function qcReject_handleItem() {
		// function disableF5(e) {
		// 	if ((e.which || e.keyCode) == 116 || (e.which || e.keyCode) == 82) {
		// 		console.log(e);
		// 		e.preventDefault();
		// 		if (confirm("Are You Sure?")) {
		// 			window.location.reload();
		// 		}
		// 	}
		// };

		$("#get-uid").submit(function (event) {
			event.preventDefault();
			// disableF5();
			var uid = $("#uid").val();

			var formData = new FormData();
			formData.append("action", "qc_reject");
			formData.append("uid", uid);

			window.setTimeout(function () {
				var response = submitForm(formData, "POST");
				if (response.type == "success") {
					UIToastr.init('success', 'QC Rejected.', 'Status for the UID: ' + uid + ' updated as qc_rejected');
				} else {
					UIToastr.init('error', 'Error processing your request', 'Opps! Something went wrong!!!');
				}
			}, 10);
		});
	}

	function manage_handleLocation() {
		$("#update-location").submit(function (event) {
			$("#uid_move_submit").prop("disabled", true);
			$("#uid_move").prop("disabled", true);
			event.preventDefault();

			let uid = $("#uid_move").val();
			var formData = new FormData();
			formData.append("action", "MoveToCtn");
			formData.append("uid", uid);

			window.setTimeout(function () {
				var response = submitForm(formData, "POST");
				if (response.type == "success") {
					UIToastr.init(response.notificationType, "Inv Moved", "Inventory Successfully moved");
					$("#uid_move_submit").prop("disabled", false);
					$("#uid_move").prop("disabled", false);
					$("#uid_move").val("");
				}
			}, 10);
		});
	}

	return {
		init: function (type) {
			switch (type) {
				case "inbound":
					inboundGrn_handlePrintOption();
					inboundGrn_handleSelect();
					Qz.init();
					$(window).bind('beforeunload', function () {
						Qz.disconnect();
					});
					break;

				case "quality_check":
					qc_handleItem();
					break;

				case "quality_reject":
					qcReject_handleItem();
					break;

				case "qc_issues":
					qcIssues_handleItem();
					qcIssues_handleValidation();
					break;

				case "repairs":
					repairs_handleItem();
					break;

				case "history":
					history_handleTimeline();
					break;

				case "manage":
					// Qz.init();
					// $(window).bind('beforeunload', function () {
					// 	Qz.disconnect();
					// });
					manage_handleSelect();
					manage_handleValidation();
					manage_handleRePrint();

					swap_handleSelect();
					swap_handleValidation();

					alter_handleSelect();
					alter_handleValidation();

					manage_handleLocation();
					break;

				case "stock":
					stock_handleTable();
					break;

				case "manage_inv":
					inboundGrn_handlePrintOption();
					getBoxDetails();
					addItem();
					addBox();
					addCartoon();
					Qz.init();
					$(window).bind('beforeunload', function () {
						Qz.disconnect();
					});
					break;

				case "transfer":
					stockTransfer_handleForm();
					stockTransfer_handleValidation();
					vendorReturn_handleValidation();
					break;

				case "reconciliation":
					stockReconciliation_handleForm();
					break;

				case "content":
					stockContent_handleForm();
					break;

				case "audit":
					stockAudit_handleForm();
					break;

				case "analytics":
					stockAnalytics_handleInit();
					break;

				case "map_location":
					mapLocation_handleInit();
					break;
			}
		}
	}
}();
