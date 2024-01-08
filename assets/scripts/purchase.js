var Purchase = function () {
	// Submit Form
	var submitForm = function (formData, $type) {
		var $ret = "";
		$.ajax({
			url: "ajax_load.php?token=" + new Date().getTime(),
			cache: false,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
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

	var block_enter_key = function (form) {
		$(form).on('keyup keypress', function (e) {
			var keyCode = e.keyCode || e.which;
			if (keyCode === 13) {
				e.preventDefault();
				return false;
			}
		});
	};

	var current_lineItem = 0;
	var lineitems = [0];
	var tabOrder = 6;
	var grn_tabOrder = 2;
	var current_po_items = [];

	function purchaseOrder_handleTable() {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return '';
		});

		var statusFilters = {
			"draft": {
				title: "Draft",
				class: " badge badge-default"
			},
			"created": {
				title: "Created",
				class: " badge badge-info"
			},
			"shipped": {
				title: "Shipped",
				class: " badge badge-primary"
			},
			"partial_shipped": {
				title: "Partial Shipped",
				class: " badge badge-warning"
			},
			"partial_shipped_received": {
				title: "Partial Shipped Received",
				class: " badge badge-warning"
			},
			"received": {
				title: "Received",
				class: " badge badge-success"
			},
		};

		var table = $('#editable_purchase_orders');
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
				url: "ajax_load.php?action=purchase_orders&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "PO ID",
					columnFilter: 'selectFilter'
				}, {
					data: "Supplier",
					columnFilter: 'selectFilter'
				}, {
					data: "Ordered Quantity",
					columnFilter: 'rangeFilter'
				}, {
					data: "Total Amount",
					columnFilter: 'rangeFilter'
				}, {
					data: "Status",
					columnFilter: 'statusFilter'
				}, {
					data: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[0, 'desc']
			],
			columnDefs: [
				{
					targets: -1,
					title: "Actions",
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						if (e.Status == "draft") {
							$return = '<a class="btn btn-default btn-xs create_grn" href="purchase_new.php?po_id=' + e.po_id + '&type=' + e.Status + '" title="Edit PO"><i class="fa fa-edit"></i></a> ' +
								'<a title"Cancel PO" class="delete btn btn-default btn-xs" data-po_id=' + e.po_id + ' title="Cancel PO" ><i class="fa fa-trash-alt"></i></a>';
						} else if (e.Status == "created") {
							$return = '<a title="View PO" class="view btn btn-default btn-xs print_preview" data-toggle="modal" data-href="print.php?action=purchase_order&po_id=' + e.po_id + '" ><i class="fa fa-eye"></i></a> ' +
								'<a title="Print PO" target="_blank" class="btn btn-default btn-xs print" href="print.php?action=purchase_order&po_id=' + e.po_id + '" ><i class="fa fa-print"></i></a>';
						} else {
							$return = '<a title="View PO" class="btn btn-default btn-xs view print_preview" data-toggle="modal" data-href="print.php?action=purchase_order&po_id=' + e.po_id + '" ><i class="fa fa-eye"></i></a> ' +
								'<a title="Print PO" target="_blank" class="btn btn-default btn-xs print" href="print.php?action=purchase_order&po_id=' + e.po_id + '" ><i class="fa fa-print"></i></a>';
						}
						return $return;
					}
				}, {
					targets: -2,
					width: "10%",
					render: function (a, t, e, s) {
						$return = void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
						return $return;
					}
				}, {
					targets: -3,
					width: "10%",
				}
			],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable();
			}
		});

		var loadFilters = function (t) {
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

		var afterInitDataTable = function () {
			// Print Preview
			purchaseOrder_printPreview();

			// Delete draft and created PO's
			table.on('click', '.delete', function (e) {
				e.preventDefault();
				if (confirm("Are you sure to cancel this PO ?") === true) {
					var po_id = $(this).data('po_id');
					var formData = new FormData();
					formData.append('action', 'delete_po');
					formData.append('po_id', po_id);
					var s = submitForm(formData, "POST");
					oTable.ajax.reload();
					UIToastr.init(s.type, 'Purchase Order', s.msg);
					return;
				}
			});

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

	function purchaseOrder_handleSuppliers() {

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
	}

	function purchaseOrder_handleProducts() {
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
	}

	function purchaseOrder_lineItemClick(itemNumber) {
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
	}

	function purchaseOrder_lineItem(itemNumber) {
		$content = '<div class="form-group lineitems lineitem_' + itemNumber + '">' +
			'<div class="col-md-4">' +
			'<select class="products_list_' + itemNumber + ' form-control" name="lineitems[' + itemNumber + '][sku]" id="sku_' + itemNumber + '" tabindex="' + (tabOrder++) + '" required></select>' +
			'</div>' +
			'<div class="col-md-1">' +
			'<select class="form-control currency" name="lineitems[' + itemNumber + '][currency]" id="currency_' + itemNumber + '" tabindex="' + (tabOrder++) + '" required><option value="INR">INR</option><option value="CNY">CNY</option></select>' +
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
	}

	function purchaseOrder_lineItemCalculation() {
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
	}

	function purchaseOrder_handleValidation() {
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
				block_enter_key(form);
				// success.show();
				error.hide();
				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'save_po');
				$po_type = 'draft';
				$button = 'info';
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
					if (input.name == "type" && input.value == "create") {
						$po_type = 'create';
						$button = 'success';
					}
				});

				$('.form-actions .btn-' + $button, form).attr('disabled', true);
				$('.form-actions .btn-' + $button + ' i', form).addClass('fa fa-sync fa-spin');

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						$('#po_id').val(s.po_id);
						UIToastr.init('success', 'Purchase Order', s.msg);
						$('.form-actions .btn', form).attr('disabled', false);
						$('.form-actions i', form).removeClass('fa-sync fa-spin');
						window.history.replaceState(null, null, "?po_id=" + s.po_id + "&type=" + $po_type);
						if ($po_type == 'create') {
							window.location.href = 'purchase_view.php';
						}
					} else if (s.type == 'error') {
						UIToastr.init('error', 'Purchase Order', s.msg);
					} else {
						UIToastr.init('error', 'Purchase Order', s.msg);
					}
				}, 50);
			}
		});
	}

	function purchaseOrder_handleFormData($po_id) {
		var el = ".portlet-body";
		var formData = new FormData();
		var s = submitForm('action=get_po&po_id=' + $po_id, "GET");

		$('.suppliers_list').val(s.party_id).trigger('change');
		for (var i = 0; i < s.items.length; i++) {
			$('#sku_' + i).val(s.items[i].item_id).trigger('change');
			$('#price_' + i).val(s.items[i].item_price);
			$('#qty_' + i).val(s.items[i].item_qty).blur().focus();
			$('#currency_' + i).val(s.items[i].item_currency).blur().focus();
			if (i != s.items.length - 1) {
				$('.btn_add_' + i).trigger('click');
			}
		}

		window.setTimeout(function () {
			App.unblockUI(el);
		}, 500);
	}

	function purchaseOrder_printPreview() {
		// general settings
		$.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner =
			'<div class="loading-spinner" style="width: 200px; margin-left: -100px;">' +
			'<div class="progress progress-striped active">' +
			'<div class="progress-bar" style="width: 100%;"></div>' +
			'</div>' +
			'</div>';

		$.fn.modalmanager.defaults.resize = true;

		var $modal = $('#modal-po-print-preview');
		$('.print_preview').bind('click', function (e) {
			e.preventDefault();
			// create the backdrop and wait for next modal to be triggered
			$('body').modalmanager('loading');
			$location = $(this).attr('data-href');
			$("#view_pdf").attr('src', $location);
			setTimeout(function () {
				$modal.modal();
			}, 1000);
		});
	}

	function purchaseOrderItem_handleTable() {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim();
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return '';
		});

		var statusFilters = {
			"draft": {
				title: "Draft",
				class: " badge badge-default"
			},
			"ordered": {
				title: "Ordered",
				class: " badge badge-default"
			},
			"shipped": {
				title: "Shipped",
				class: " badge badge-primary"
			},
			"partial_shipped": {
				title: "Partial Shipped",
				class: " badge badge-info"
			},
			"partial_shipped_received": {
				title: "Partial Shipped Received",
				class: " badge badge-warning"
			},
			"received": {
				title: "Received",
				class: " badge badge-success"
			},
		};

		var table = $('#editable_purchase_order_items');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 20,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=purchase_order_items&token=" + new Date().getTime(),
				cache: false,
				type: "POST",
			},
			columns: [
				{
					data: "PO ID",
					columnFilter: "selectFilter",
				}, {
					data: "Supplier",
					columnFilter: "selectFilter",
				}, {
					data: "SKU",
					columnFilter: "selectFilter",
				}, {
					data: "Quantity",
					columnFilter: "rangeFilter",
				}, {
					data: "Received Quantity",
					columnFilter: "rangeFilter",
				}, {
					data: "Rate",
					columnFilter: "rangeFilter",
				}, {
					data: "Total",
					columnFilter: "rangeFilter",
				}, {
					data: "Status",
					columnFilter: "statusFilter",
				}, {
					data: "Actions",
					columnFilter: "actionFilter",
					responsivePriority: -1
				},
			],
			order: [
				[0, 'desc']
			],
			columnDefs: [
				{
					targets: -1,
					title: "Actions",
					orderable: !1,
					width: "100px",
					render: function (a, t, e, s) {
						return '<a title="View PO" class="view btn btn-default btn-xs print_preview" data-toggle="modal" data-href="print.php?action=purchase_order&po_id=' + e.po_id + '"><i class="fa fa-eye"></i></a>';
					}
				}, {
					targets: -2,
					render: function (a, t, e, s) {
						$return = void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
						return $return;
					}
				}, {
					targets: 2,
					width: "300px",
				}
			],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable();
			}
		});

		var loadFilters = function (t) {
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

		var afterInitDataTable = function () {

			purchaseOrder_printPreview();

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

	function grnNew_handleTable() {

		// ON LOT CHANGE FUNCTION
		$('.lot_number').on('change', function () {
			$('.grn_products_details').addClass('hide');
			$('.form-buttons').addClass('hide');
			$('.grn_products').html("");
			$lot_no = $(this).val();
			$('#lot_id').val($lot_no);
			if ($lot_no != "") {
				var is_local = App.getURLParameter('is_local');
				if (is_local === null)
					is_local = $('.lot_number').select2().find(":selected").data("is_local");

				var grn_id = App.getURLParameter('grn_id');
				$url = "action=get_lot_details&lot_id=" + $lot_no + "&is_local=" + is_local;
				if (grn_id !== null)
					$url = "action=get_lot_details&lot_id=" + $lot_no + "&grn_id=" + grn_id + "&is_local=" + is_local;

				var formData = $url;
				var lot_details = submitForm(formData, "GET");
				var $content = "";
				var c = lot_details['items'].length;
				lineitems = [];
				$.each(lot_details['items'], function (k, v) {
					lineitems.push(k);
					grn_lineItem(k, v, c);
				});
				$('.grn_products_details').removeClass('hide');
				$('.form-buttons').removeClass('hide');

				// GRN PREVIEW LOT DETAILS
				$('#lot_number').val(lot_details['lot_details']['lot_number']);
				$('#lot_carrier').text(lot_details['lot_details']['lot_carrier']);
				$('#lot_carrier_code').text(lot_details['lot_details']['lot_carrierCode']);
				$('#lot_carriage_type').val(lot_details['lot_details']['lot_carriageType']);
				$('#lot_carriage_value').val(lot_details['lot_details']['lot_carriageValue']);
				$('#lot_exchange').val(lot_details['lot_details']['lot_ex']);
				$('#lot_local').val(lot_details['lot_details']['is_local']);
			}
		});


		if (window.location.href.indexOf("lot_id") !== -1) {
			var el = ".portlet-body";
			App.blockUI({ target: el });
			$lot_id = App.getURLParameter('lot_id');
			$(".lot_number").select2("destroy");
			$('.lot_number').val($lot_id).trigger('change');
			$(".lot_number").attr("disabled", true);
			window.setTimeout(function () {
				App.unblockUI(el);
			}, 500);
		}
	}

	function grn_lineItem(itemNumber, item, count) {
		var products_list = "",
			selected = "",
			add_button = "",
			grn_qty = "",
			grn_ctn = "",
			autofocus = "";

		$.each(all_parent_sku, function (k, v) {
			if (v.key == item.item_id) {
				selected = "selected";
				products_list += "<option " + selected + " value=" + v.key + ">" + v.value + "</option>";
			}
		});

		if (itemNumber == count - 1) {
			current_order = grn_tabOrder + 2;
			add_button = '<div class="spinner-buttons input-group-btn">' +
				'<button type="button" class="btn add_lineItem btn_add_' + itemNumber + '" tabindex="' + (current_order) + '">' +
				'<i class="fa fa-plus"></i>' +
				'</button>' +
				'</div>';
			grn_tabOrder = current_order - 2;
			autofocus = "autofocus";
		}

		if (item.grn_qty !== null)
			grn_qty = item.grn_qty;

		if (grn_ctn !== null)
			grn_ctn = item.grn_ctn;


		$content = '<div class="form-group lineitems lineitem_' + itemNumber + '">' +
			'<div class="col-md-4">' +
			'<select class="products_list_' + itemNumber + ' form-control product_sku" name="lineitems[' + itemNumber + '][item_id]" id="sku_' + itemNumber + '" tabindex="-1" readonly required>' + products_list + '</select>' +
			'</div>' +
			'<div class="col-md-1">' +
			'<input class="form-control po_id" name="lineitems[' + itemNumber + '][po_id]" placeholder="PO" id="po_' + itemNumber + '" tabindex="-1" type="text" value="' + item.po_id + '" readonly>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control qty" placeholder="Quantity" id="qty_' + itemNumber + '" tabindex="-1" type="text" value="' + item.item_shipped_qty + '" disabled>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control received_qty" name="lineitems[' + itemNumber + '][received_qty]" id="received_qty_' + itemNumber + '" tabindex="' + (grn_tabOrder++) + '" placeholder="Quantity Received" type="text" value="' + item.item_shipped_qty + '" required>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control received_ctn" name="lineitems[' + itemNumber + '][received_box]" id="received_ctn_' + itemNumber + '" tabindex="' + (grn_tabOrder++) + '" placeholder="No. of Cartoons" type="text" value=' + grn_ctn + ' ' + autofocus + ' required>' +
			'</div>' +
			'<div class="col-md-1">' +
			'<input class="item_price hide" data-itemid="' + itemNumber + '" id="item_price_' + itemNumber + '" name="lineitems[' + itemNumber + '][price]" type="hidden" value=' + item.item_price + ' required>' +
			add_button +
			'</div>' +
			'</div>';

		if (item.item_id == 0) {
			grn_tabOrder++;
			$content = '<div class="form-group lineitems lineitem_' + itemNumber + '">' +
				'<div class="col-md-4">' +
				'<select class="products_list_' + itemNumber + ' form-control product_sku" name="lineitems[' + itemNumber + '][item_id]" id="sku_' + itemNumber + '" tabindex="-1" required>' + products_list + '</select>' +
				'</div>' +
				'<div class="col-md-1">' +
				'<input class="form-control po_id" name="lineitems[' + itemNumber + '][po_id]" placeholder="PO" id="po_' + itemNumber + '" tabindex="-1" type="text" value="" readonly>' +
				'</div>' +
				'<div class="col-md-2">' +
				'<input class="form-control qty" name="lineitems[' + itemNumber + '][qty]" placeholder="Quantity" id="qty_' + itemNumber + '" tabindex="-1" type="text" value="' + item.item_shipped_qty + '" disabled>' +
				'</div>' +
				'<div class="col-md-2">' +
				'<input class="form-control received_qty" name="lineitems[' + itemNumber + '][received_qty]" id="received_qty_' + itemNumber + '" tabindex="' + (grn_tabOrder++) + '" placeholder="Quantity Received" type="text" required>' +
				'</div>' +
				'<div class="col-md-2">' +
				'<input class="form-control received_ctn" name="lineitems[' + itemNumber + '][received_box]" id="received_ctn_' + itemNumber + '" tabindex="' + (grn_tabOrder++) + '" placeholder="No. of Cartoons" type="text" required>' +
				'</div>' +
				'<div class="col-md-1">' +
				'<input class="item_price hide" data-itemid="' + itemNumber + '" id="item_price_' + itemNumber + '" name="lineitems[' + itemNumber + '][price]" type="hidden" value=' + item.item_price + ' required>' +
				'<div class="spinner-buttons input-group-btn">' +
				'<button type="button" class="btn add_lineItem btn_add_' + itemNumber + '" tabindex="' + (grn_tabOrder++) + '">' +
				'<i class="fa fa-plus"></i>' +
				'</button>' +
				'<button type="button" class="btn remove_lineItem btn_minus_' + itemNumber + '" tabindex="' + (grn_tabOrder++) + '">' +
				'<i class="fa fa-trash"></i>' +
				'</button>' +
				'</div>' +
				'</div>' +
				'</div>';

			$('.grn_products').append($content);
			var products_list = '';
			$.each(all_parent_sku, function (k, v) {
				products_list += "<option value=" + v.key + ">" + v.value + "</option>";
			});
			$('.products_list_' + itemNumber).append(products_list);
			$('.products_list_' + itemNumber).select2({
				placeholder: "Product SKU",
				allowClear: true
			});
			$('.products_list_' + itemNumber).select2('open');
		} else {
			$('.grn_products').append($content);
		}

		grn_lineItemCalculation();
		grn_lineItemClick(itemNumber);

		$(".received_ctn, .received_qty").inputmask({
			"mask": "9",
			"repeat": 5,
			"greedy": false
		});
	}

	function grn_lineItemClick(itemNumber) {
		$('.btn_add_' + itemNumber).on('click', function (e) {
			e.preventDefault();
			if ($('#add-grn').validate().form()) {
				$(this).attr('disabled', true);
				current_lineItem = itemNumber + 1;
				lineitems.push(current_lineItem);
				item = JSON.parse('{"po_id":0, "item_id":0, "item_qty":0, "item_shipped_qty":0}');
				grn_lineItem(current_lineItem, item);
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
	}

	function grn_lineItemCalculation() {
		$('.received_ctn, .received_qty .add_lineItem, .remove_lineItem').on('focusout focusin change changeInput input', function () {
			lineitem = $(this).parent().parent().attr('class').split(' ').pop();
			if (lineitem == 'col-md-1')
				lineitem = $(this).parent().parent().parent().attr('class').split(' ').pop();
			price = $('.' + lineitem + ' .price').val();
			qty = $('.' + lineitem + ' .qty').val();

			$('.' + lineitem + ' .amount').val(price * qty);

			var sum = 0;
			$('.received_qty').each(function () {
				sum += Number($(this).val());
			});
			$('.total_qty').val(sum);

			var units = 0;
			$('.received_ctn').each(function () {
				units += Number($(this).val());
			});
			$('.total_ctn').val(units);
		});
	}

	function grnNew_handleValidation() {
		var form = $('#add-grn');
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
				block_enter_key(form);
				// success.show();
				error.hide();

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'save_grn');
				$grn_type = 'draft';
				$button = 'info';
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
					if (input.name == "type" && input.value == "create") {
						$grn_type = 'create';
						$button = 'success';
					}
				});

				formData.append('json_data', JSON.stringify(other_data));

				if ($grn_type == "create" && !confirm("Editing will not be allowed after GRN is created. Proceed?"))
					return

				$('.form-actions .btn-' + $button, form).attr('disabled', true);
				$('.form-actions .btn-' + $button + ' i', form).addClass('fa fa-sync fa-spin');
				UIToastr.init('info', 'Purchase GRN', 'Please wait while GRN is been created. Please don\'t close the window.');

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						$('#grn_id').val(s.grn_id);
						UIToastr.init('success', 'Purchase GRN', s.msg);
						$('.form-actions .btn', form).attr('disabled', false);
						$('.form-actions i', form).removeClass('fa-sync fa-spin');
						if ($grn_type == 'create') {
							window.location.href = 'grn_view.php';
						} else {
							window.history.replaceState(null, null, "?lot_id=" + s.lot_id + "&grn_id=" + s.grn_id + "&type=" + $grn_type);
						}
					} else if (s.type == 'error') {
						UIToastr.init('error', 'Purchase GRN', s.msg);
					} else {
						UIToastr.init('error', 'Purchase GRN', s.msg);
					}
				}, 50);
			}
		});
	}

	function grnNew_handlePreview() {
		$('.btn-next').click(function () {
			if (!$('#add-grn').validate().form()) {
				return;
			}
			var null_price_items = [];
			var null_price_line_items = "";
			$('.line_item_price').html("");
			$('.item_price').each(function () {
				if ($(this).val() === "null" || $(this).val() === "undefined") {
					var item_id = $(this).data('itemid');
					null_price_items.push(item_id);
					var data = $(".products_list_" + item_id + " option:selected").text();
					grnNoPriceLineItem_handleView(item_id, data);
				}
			});
			if (null_price_items.length > 0) {
				$('#set_item_price').modal('show');
				grnNoPriceModel_handleSubmit();
				return;
			}

			var itemNumber = 0;
			$('.lineitems').each(function () {
				$preview_content = '<tr class="preview_lineitem_' + itemNumber + '">' +
					'<td>' +
					$(".products_list_" + itemNumber + " option:selected").text() +
					'</td>' +
					'<td>' +
					$(this).find('.po_id').val() +
					'</td>' +
					'<td>' +
					$(this).find('.received_qty').val() +
					'</td>' +
					'<td>' +
					$(this).find('.received_ctn').val() +
					'</td>' +
					'<td>' +
					$(this).find('.item_price').val() +
					'</td>' +
					'</tr>';

				$('.preview_item_details').append($preview_content);
				itemNumber++;
			});

			$('#main_grn_tab, .btn-draft, .btn-next').addClass('hide');
			$('#confirm_grn_tab, .btn-submit, .btn-back').removeClass('hide');
			$('.btn-draft, .btn-next').attr('disabled', true);
			$('.btn-submit, .btn-back').attr('disabled', false);
		});

		$('.btn-back').click(function () {
			$('.preview_item_details').html("");
			$('#main_grn_tab, .btn-draft, .btn-next').removeClass('hide');
			$('#confirm_grn_tab, .btn-submit, .btn-back').addClass('hide');
			$('.btn-draft, .btn-next').attr('disabled', false);
			$('.btn-submit, .btn-back').attr('disabled', true);
		});
	}

	function grnView_handleTable() {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return '';
		});

		var statusFilters = {
			"draft": {
				title: "Draft",
				class: " badge badge-default"
			},
			"created": {
				title: "Created",
				class: " badge badge-info"
			},
			"receiving": {
				title: "Receiving",
				class: " badge badge-warning"
			},
			"completed": {
				title: "Completed",
				class: " badge badge-success"
			},
		};

		var table = $('#grn_view');
		var oTable;
		oTable = table.DataTable({
			responsive: !0,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 20,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=get_grn",
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "grn_no",
					title: "GRN No",
					columnFilter: "selectFilter",
				}, {
					data: "lot_number",
					title: "Lot No",
					columnFilter: "selectFilter",
				}, {
					data: "grn_total_qty",
					title: "Quantity",
					columnFilter: "rangeFilter",
				}, {
					data: "grn_total_box",
					title: "Boxes",
					columnFilter: "rangeFilter",
				}, {
					data: "grn_status",
					title: "Status",
					columnFilter: "statusFilter",
				}, {
					title: "Actions",
					columnFilter: "actionFilter",
					responsivePriority: -1
				},
			],
			order: [
				[0, 'desc']
			],
			columnDefs: [
				{
					targets: -1,
					title: "Actions",
					orderable: !1,
					width: "180px",
					render: function (a, t, e, s) {
						if (e.grn_status == "draft") {
							$return = '<a class="btn btn-default btn-xs create_grn" href="grn_new.php?lot_id=' + e.lot_id + '&grn_id=' + e.grn_id + '&type=' + e.grn_status + '"><i class="fa fa-edit"></i> Edit GRN</a>';
						} else if (e.grn_status == "created" || e.grn_status == "receiving") {
							$return = '<a class="btn btn-default btn-xs create_grn" href="../inventory/inbound.php?grn_id=' + e.grn_id + '"><i class="fa fa-forward"></i> Inward</a>';
						} else {
							$return = "";
						}
						return $return;
					}
				}, {
					targets: -2,
					render: function (a, t, e, s) {
						$return = void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
						return $return;
					}
					// }, {
					// 	targets: 5,
					// 	width: "150px"
				}
			],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable();
			}
		});

		var loadFilters = function (t) {
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

		var afterInitDataTable = function () {
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

	function grnNoPriceLineItem_handleView($item_id, $sku) {
		var content = '<div class="form-group">' +
			'<div class="col-sm-8">' +
			'<div class="input-group">' +
			'<select class="form-control input-large no_sku" id="no_sku_' + $item_id + '">' +
			'<option value="' + $sku + '">' + $sku + '</option>' +
			'</select>' +
			'</div>' +
			'</div>' +
			'<div class="col-sm-2">' +
			'<div class="input-group">' +
			'<select class="form-control no_currency" id="no_currency_' + $item_id + '" required>' +
			'<option value=""></option>' +
			'<option value="INR">INR</option>' +
			'<option value="CNY">CNY</option>' +
			'</select>' +
			'</div>' +
			'</div>' +
			'<div class="col-sm-2">' +
			'<div class="input-group">' +
			'<input class="form-control price no_price" id="no_price_' + $item_id + '" type="text" required>' +
			'</div>' +
			'</div>' +
			'<input class="item_id" value="' + $item_id + '" type="hidden">' +
			'</div>';
		$('.line_item_price').append(content);
		$(".no_price").inputmask("decimal", {
			"rightAlign": false,
			"numericInput": true,
			"placeholder": "0",
			"greedy": false,
			onUnMask: function (maskedValue, unmaskedValue) {
				return unmaskedValue;
			}
		});
		if (jQuery().select2) {
			$('#no_sku_' + $item_id + ', #no_currency_' + $item_id + '').select2({
				placeholder: "Select",
				allowClear: true
			});
		}
	}

	function grnNoPriceModel_handleSubmit() {
		$('.update_line_item_prices').on('click', function () {
			has_error = false;
			$('.item_id').each(function () {
				var item_id = $(this).val();
				$('#no_currency_' + item_id).closest('.input-group').removeClass('has-error');
				$('#no_price_' + item_id).closest('.input-group').removeClass('has-error');

				// item_price_9
				if ($('#no_currency_' + item_id).val() == "") {
					$('#no_currency_' + item_id).closest('.input-group').addClass('has-error'); // set error class to the control group
					has_error = true;
				}

				if ($('#no_price_' + item_id).val() == "") {
					$('#no_price_' + item_id).closest('.input-group').addClass('has-error'); // set error class to the control group
					has_error = true;
				}

				if (!has_error) {
					var currency = $('#no_currency_' + item_id).val();
					var price = $('#no_price_' + item_id).val();
					$('#item_price_' + item_id).val(currency + ":" + price);
				}

			});
			if (has_error) {
				return;
			} else {
				$('#set_item_price').modal('hide');
			}
		});
	}

	function lotCreate_handleView() {
		$baseLoteNo = $('#lot_number').val();
		// ON CARRIER CHANGE FUNCTION
		$('#lot_carrier').on('change', function () {
			$data = $(this).select2('data');
			if ($data) {
				if ($('#lot_number').val() == $('#carrier_code').val()) {
					$('#carrier_code').val($baseLoteNo + $data.text);
				}
				$('#lot_number').val($baseLoteNo + $data.text);
			} else {
				if ($('#lot_number').val() == $('#carrier_code').val()) {
					$('#carrier_code').val($baseLoteNo);
				}
				$('#lot_number').val($baseLoteNo);
			}
		});

		$loaclBaseLoteNo = $('#local_lot_number').val();
		$('#local_lot_carrier').on('change', function () {
			$data = $(this).select2('data');
			if ($data) {
				if ($('#local_lot_number').val() == $('#local_carrier_code').val()) {
					$('#local_carrier_code').val($loaclBaseLoteNo + $data.text);
				}
				$('#local_lot_number').val($loaclBaseLoteNo + $data.text);
			} else {
				if ($('#local_lot_number').val() == $('#local_carrier_code').val()) {
					$('#local_carrier_code').val($loaclBaseLoteNo);
				}
				$('#local_lot_number').val($loaclBaseLoteNo);
			}
		});
	}

	function lotCreate_handleValidation() {
		var form = $('#add-lot');
		// var error = $('.alert-danger', form);
		// var success = $('.alert-success', form);

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
				// success.hide();
				// error.show();
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
				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'add_lot');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						UIToastr.init('success', 'Lot Creation', s.msg);
						$('#add_lot').modal('hide');
						window.location.reload();
					} else if (s.type == 'error') {
						UIToastr.init('error', 'Lot Creation', s.msg);
					} else {
						UIToastr.init('error', 'Lot Creation', s.msg);
					}
					$('.form-actions .btn', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
				}, 50);
			}
		});
	}

	function lotLocalCreate_handleValidation() {
		var form = $('#add-local-lot');
		// var error = $('.alert-danger', form);
		// var success = $('.alert-success', form);

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
				// success.hide();
				// error.show();
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
				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'add_lot');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						UIToastr.init('success', 'Lot Creation', s.msg);
						$('#add_local_lot').modal('hide');
						window.location.reload();
					} else if (s.type == 'error') {
						UIToastr.init('error', 'Lot Creation', s.msg);
					} else {
						UIToastr.init('error', 'Lot Creation', s.msg);
					}
					$('.form-actions .btn', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
				}, 50);
			}
		});
	}

	function packNew_handleTable($lot_id, $local) {
		var el = ".portlet-body";
		var formData = "action=get_lot_details&lot_id=" + $lot_id + "&is_local=" + $local;
		var lot_details = submitForm(formData, "GET");

		$('.lot_number').val($lot_id).trigger('change');
		// $('.lot_number').select2({readonly:true});
		// $(".lot_number").attr("disabled", true);

		itemNumber = 0;
		if (lot_details['items'].length > 0) {
			var $content = "";
			$.each(lot_details['items'], function (k, v) {
				$current_item = { 'item_id': v.item_id, 'po_id': v.po_id, 'ordered_qty': v.item_qty, 'qty': v.item_shipped_qty, 'item_name': 'lineitems_' + v.po_id + '_' + v.item_id };
				current_po_items['lineitems_' + v.po_id + '_' + v.item_id + ''] = $current_item;
			});
			packList_handleTable();
		}

		window.setTimeout(function () {
			App.unblockUI(el);
		}, 500);
	}

	function packNew_handleLots() {
		// ON LOT CHANGE FUNCTION
		$('.lot_number').on('change', function () {
			var lot_id = $(this).val();
			var is_local = App.getURLParameter('is_local');
			if (is_local === null)
				is_local = $(this).select2().find(":selected").data("is_local")
			if (lot_id) {
				$('.quick_add').attr('disabled', false);
				$('.add_lot').attr('disabled', true);
				$('.packing_products_details').addClass('hide');
				$('.packing_products').html("");
			} else {
				$('.quick_add').attr('disabled', true);
				$('.add_lot').attr('disabled', false);
			}
			$('.lot_is_local').val(is_local);
		});
	}

	function packList_handleLineItem(itemNumber, item) {
		var products_list = '<option></option>';
		var selected = "";
		$.each(all_parent_sku, function (k, v) {
			if (v.key == item.item_id) {
				selected = "selected";
				products_list += "<option " + selected + " value=" + v.key + ">" + v.value + "</option>";
			}
		});
		var pending_qty = parseInt(item.ordered_qty) - parseInt(item.qty);
		$content = '<div class="form-group lineitems lineitem_' + itemNumber + '">' +
			'<div class="col-md-3">' +
			'<select class="products_list_' + itemNumber + ' form-control product_sku" required name="lineitems[' + itemNumber + '][sku]" id="sku_' + itemNumber + '" readonly>' + products_list + '</select>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control po_id" required name="lineitems[' + itemNumber + '][po_id]" placeholder="Quantity" id="po_id_' + itemNumber + '" type="text" value="' + item.po_id + '" readonly>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control ordered_qty" placeholder="Ordered Quantity" type="number" value="' + item.ordered_qty + '" readonly>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control pending_qty" placeholder="Pending Quantity" type="number" value="' + pending_qty + '" readonly>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control qty" required name="lineitems[' + itemNumber + '][qty]" placeholder="Quantity" id="qty_' + itemNumber + '" type="number" value="' + item.qty + '" readonly>' +
			'</div>' +
			'<div class="col-md-1">' +
			'<div class="spinner-buttons input-group-btn">' +
			'<button type="button" class="btn remove_lineItem btn_minus_' + itemNumber + '" data-line_item="' + itemNumber + '" data-item_name="' + item.item_name + '" >' +
			'<i class="fa fa-trash"></i>' +
			'</button>' +
			'</div>' +
			'</div>' +
			'</div>';

		return $content;
	}

	function packList_handleLineItems($lot_id) {
		var $modal = $('#add_lineItem_model');
		$('.quick_add').on('click', function () {
			// create the backdrop and wait for next modal to be triggered
			App.startPageLoading();

			if ($lot_id != "") {
				$url = 'ajax_load.php?action=get_po_items&lot_id=' + $lot_id;
			} else {
				$url = 'ajax_load.php?action=get_po_items';
			}
			$modal.load($url, function () {
				$modal.modal();
				App.stopPageLoading();
				$('body').addClass('page-overflow');
				for (const current_po_item in current_po_items) {
					$item = current_po_items[current_po_item];
					$('#' + current_po_item + ' .checkerbox').attr('checked', true);
					$('#' + current_po_item + ' .lineitem-value').removeClass('inactive').val($item.qty);
				}
				App.initUniform();

				var hoverOrClick = function () {
					$input_elemet = $(this).data('inputitem');
					if ($(this).prop("checked") == true) {
						$('#' + $input_elemet).removeClass('inactive');
						$('#' + $input_elemet).focus();
					} else {
						$('#' + $input_elemet).addClass('inactive');
						$('#' + $input_elemet).val("");
						$element_name = $('#' + $input_elemet).attr('name');
						delete current_po_items[$element_name];
					}
				}
				$('.item_checkbox').click(hoverOrClick).hover(hoverOrClick);

				$('#lineItem_Model').submit(function (e) {
					e.preventDefault();
					$("#lineItem_Model input[type='number']").each(function (index, obj) {
						var lintItem = [];
						if ($(obj).val() != "") {
							$current_item = { 'item_id': $(obj).data('item_id'), 'po_id': $(obj).data('po_id'), 'sku': $(obj).data('sku'), 'ordered_qty': $(obj).data('qty'), 'qty': $(obj).val(), 'item_name': $(obj).attr('name') };
							current_po_items[$(obj).attr('name')] = $current_item;
						}
					});

					$modal.modal('hide');
					packList_handleTable();
				});
			});
		});
	}

	function packList_handleTable() {
		$('.packing_products').html("");
		$content = "";
		itemNumber = 0;
		for (const current_po_item in current_po_items) {
			$item = current_po_items[current_po_item];
			$content += packList_handleLineItem(itemNumber, $item);
			itemNumber++;
		}
		$('.packing_products_details').removeClass('hide');
		$('.packing_products').append($content);

		$('.remove_lineItem').on('click', function () {
			if (confirm('Remove line item?')) {
				$line_item = $(this).data('line_item');
				$item_name = $(this).data('item_name');
				$('.lineitem_' + $line_item).remove();
				delete current_po_items[$item_name];
			}
		});

		var units = 0;
		$('.qty').each(function () {
			units += Number($(this).val());
		});
		$('.total_qty').val(units);
	}

	function packList_handleValidation() {
		var form = $('#pack_list');
		// var error = $('.alert-danger', form);
		// var success = $('.alert-success', form);

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
				// success.hide();
				// error.show();
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
				// error.hide();
				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'add_pl_items');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						UIToastr.init('success', 'Pack List Creation', s.msg);
						window.setTimeout(function () {
							window.location.href = 'packing_list_view.php';
						}, 1000);
						// 
					} else {
						UIToastr.init('error', 'Pack List Creation', s.msg);
					}
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');
				}, 50);
			}
		});
	}

	function packView_handleTable() {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return '';
		});

		var statusFilters = {
			"created": {
				title: "Created",
				class: " badge badge-inverse"
			},
			"rts": {
				title: "RTS",
				class: " badge badge-default"
			},
			"shipped": {
				title: "Shipped",
				class: " badge badge-info"
			},
			"received": {
				title: "Received",
				class: " badge badge-primary"
			},
			"completed": {
				title: "Completed",
				class: " badge badge-success"
			},
		};

		var table = $('#packing_list_view');
		var oTable;
		oTable = table.DataTable({
			responsive: !0,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 20,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=get_packing_list",
				type: "POST",
				// data: {
				// 	columnsDef: ["LotNumber", "Carrier", "CarrierCode", "Supplier", "SKU", "Quantity", "Received Quantity", "Shipped Date", "Received Date", "Status", "Actions"]
				// }
			},
			columns: [
				{
					data: "lot_number",
					title: "Lot Number",
					columnFilter: "selectFilter"
				}, {
					data: "party_name",
					title: "Carrier",
					columnFilter: "selectFilter"
				}, {
					data: "lot_carrierCode",
					title: "Carrier Code",
					columnFilter: "selectFilter"
				}, {
					/*data: "party_name",
					title: "Supplier",
					columnFilter: "selectFilter"
				}, {
					data: "sku",
					title: "SKU",
					columnFilter: "selectFilter"
				}, {
					data: "item_shipped_qty",
					title: "Quantity",
					columnFilter: "rangeFilter"
				}, {
					data: "item_received_qty",
					title: "Received Quantity",
					columnFilter: "rangeFilter"
				}, {*/
					data: "lot_shippedDate",
					title: "Shipped Date",
					columnFilter: "dateFilter"
				}, {
					data: "lot_receivedDate",
					title: "Received Date",
					columnFilter: "dateFilter"
				}, {
					data: "lot_status",
					title: "Status",
					columnFilter: "statusFilter"
				}, {
					title: "Actions",
					columnFilter: "actionFilter",
					responsivePriority: -1,
				},
			],
			order: [
				[3, 'desc'], [0, 'asc']
			],
			columnDefs: [
				{
					targets: -1,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						var $return = "";
						if (e.lot_status == "rts" || e.lot_status == "created") {
							$return = '<button class="btn btn-default btn-xs mark_lot_shipped" data-lot_id="' + e.lot_id + '" data-lot_number="' + e.lot_number + '" data-is_local="' + e.lot_local + '" ><i class="fa fa-check"></i> Mark Shipped</button>' +
								'<a class="btn btn-default btn-xs edit_lot" href="packing_list_new.php?lot_id=' + e.lot_id + '&is_local=' + e.lot_local + '"><i class="fa fa-edit"></i> Edit Lot</a>';
						} else if (e.lot_status == "shipped") {
							$return = '<a class="btn btn-default btn-xs create_grn" target="_blank" href="grn_new.php?lot_id=' + e.lot_id + '&is_local=' + e.lot_local + '"><i class="fa fa-download"></i> Create GRN</a>';
						}
						return $return;
					}
				}, {
					targets: -2,
					render: function (a, t, e, s) {
						$return = void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
						return $return;
					}
				}
			],
			drawCallback: function () {
			},
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					actionCallBack();
			}
		});

		var loadFilters = function (t) {
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

		var afterInitDataTable = function () {
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
		};

		var actionCallBack = function () {
			$(document).off().on('click', '.mark_lot_shipped', function (e) {
				e.preventDefault();
				var btn = $(this);
				var $lot_id = btn.data('lot_id');
				var $lot_number = btn.data('lot_number');
				var $is_local = btn.data('is_local');
				if (confirm("Mark lot " + $lot_number + " shipped?")) {
					var formData = new FormData();
					formData.append('action', 'mark_lot_shipped');
					formData.append('lot_id', $lot_id);
					formData.append('is_local', $is_local);

					btn.attr('disabled', true);

					window.setTimeout(function () {
						var s = submitForm(formData, "POST");
						if (s.type == 'success') {
							UIToastr.init('success', 'Pack List Creation', s.msg);
							window.setTimeout(function () {
								window.location.href = 'packing_list_view.php';
							}, 1000);
						} else {
							UIToastr.init('error', 'Pack List Creation', s.msg);
						}
						btn.attr('disabled', false);
					}, 50);

					$('#reload-dataTable').trigger('click');
				}
			});
		};
	}

	return {
		init: function ($type) {
			if ($type == "new") {
				purchaseOrder_handleSuppliers();
				purchaseOrder_handleProducts();
				purchaseOrder_lineItem(0);
				purchaseOrder_handleValidation();
				$po_id = "";
				if (window.location.href.indexOf("po_id") !== -1) {
					App.blockUI({ target: ".portlet-body" });
					$po_id = App.getURLParameter('po_id');
					$po_type = App.getURLParameter('type');
					if ($po_id != '' && $po_type == 'draft') {
						purchaseOrder_handleFormData($po_id);
					} else {
						App.blockUI({ target: ".portlet-body" });
						$('.form-actions .btn').attr('disabled', 'true');
						UIToastr.init('info', 'Purchase Order', 'This purchase order cannot be edited. Redirecting now...');
						setTimeout(function () { window.location.href = './purchase_view.php'; }, 3000);
					}
				}
			} else if ($type == "view") {
				purchaseOrder_handleTable();
			} else if ($type == "view_item") {
				purchaseOrderItem_handleTable();
			} else if ($type == "grn_new") {
				grnNew_handleTable();
				grnNew_handleValidation();
				grnNew_handlePreview();
				$grn_id = "";
				if (window.location.href.indexOf("grn_id") !== -1) {
					App.blockUI({ target: ".portlet-body" });
					$grn_id = App.getURLParameter('grn_id');
					$grn_type = App.getURLParameter('type');
					if ($grn_id != '' && $grn_type == 'draft') {
						// console.log($grn_id);
						// purchaseOrder_handleFormData($grn_id);
					} else {
						App.blockUI({ target: ".portlet-body" });
						$('.form-actions .btn').attr('disabled', 'true');
						UIToastr.init('info', 'Purchase GRN', 'This GRN cannot be edited. Redirecting now...');
						setTimeout(function () { window.location.href = './grn_view.php'; }, 3000);
					}
				}
			} else if ($type == "grn_view") {
				grnView_handleTable();
			} else if ($type == "pack_view") {
				packView_handleTable();
			} else if ($type == "pack_new") {
				packNew_handleLots();
				var $lot_id = "";
				var $is_local = 0;
				if (window.location.href.indexOf("lot_id") !== -1) {
					App.blockUI({ target: ".portlet-body" });
					$lot_id = App.getURLParameter('lot_id');
					$is_local = App.getURLParameter('is_local');
					if ($lot_id != '' /*&& $so_type == 'draft'*/) {
						packNew_handleTable($lot_id, $is_local);
						// } else {
						// 	App.blockUI({target: ".portlet-body"});
						// 	$('.form-actions .btn').attr('disabled', 'true');
						// 	UIToastr.init('info', 'sale Order', 'This sale order cannot be edited. Redirecting now...');
						// 	setTimeout(function(){ window.location.href = './sales_view.php'; }, 3000);
					}
				}
				packList_handleLineItems($lot_id);
				packList_handleValidation();
				// LOT CREATION
				lotCreate_handleView();
				lotCreate_handleValidation();
				lotLocalCreate_handleValidation();
			} else if ($type == "grn_local_new") {
				grnNew_handlePreview()
			}
		}
	}
}();