var Sales = function () {

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
	}

	var current_lineItem = 0;
	var lineitems = [0];
	var tabOrder = 7;
	var party_id = "";
	var scanned_uids = {};

	function saleOrder_handleTable() {
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
				class: " badge badge-success"
			},
		};

		var table = $('#editable_sales_orders');
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
				url: "ajax_load.php?action=sales_orders&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "insertDate",
					title: "SO Date",
					columnFilter: 'dateFilter'
				}, {
					data: "so_id",
					title: "SO ID",
					columnFilter: 'selectFilter'
				}, {
					data: "party_name",
					title: "Customer Name",
					columnFilter: 'rangeFilter'
				}, {
					data: "quantity",
					title: "Quantity",
					columnFilter: 'rangeFilter'
				}, {
					data: "so_total_amount",
					title: "Amount",
					columnFilter: 'rangeFilter'
				}, {
					data: "so_status",
					title: "Status",
					columnFilter: 'statusFilter'
				}, {
					data: "action",
					title: "Action",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[1, 'desc']
			],
			columnDefs: [
				{
					targets: -1,
					orderable: !1,
					width: "10%",
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
			"fnDrawCallback": function () {
				// Print Preview & Print
				saleOrder_printPreview();
				saleOrder_printSO();
			},
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable();
			}
		});

		function loadFilters(t) {
			var parseDateValue = function (rawDate) {
				var d = moment(rawDate, "DD MMM, YYYY").format('YYYY-MM-DD');
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
												if ((isNaN(dS) && isNaN(dE)) || (evalDate >= dS && evalDate <= dE))
													return true;

												return false;
											});

										console.log(fD.length);

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
					format: 'dd M, yyyy',
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
		}

		function afterInitDataTable() {
			// Delete draft and created
			table.on('click', '.delete', function (e) {
				e.preventDefault();
				if (confirm("Are you sure to cancel this Sales Order?") === true) {
					var so_id = $(this).attr('class').split(' ').pop().split('_').pop();
					var formData = new FormData();
					formData.append('action', 'delete_so');
					formData.append('so_id', so_id);
					formData.append('client_id', client_id);
					var s = submitForm(formData, 'POST');
					oTable.api().ajax.reload();
					UIToastr.init(s.type, 'Sales Order', s.msg);
					return;
				}
			});

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
	}

	function saleOrder_lineItemClick(itemNumber) {
		$('.btn_add_' + itemNumber).on('click', function (e) {
			e.preventDefault();
			if ($('#add-sales-order').validate().form()) {
				$(this).attr('disabled', true);
				current_lineItem = itemNumber + 1
				lineitems.push(current_lineItem);
				saleOrder_lineItem(current_lineItem);
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
				var item_id = $(this).data('item_id');
				if (item_id != "") {
					var item = {
						'item_id': item_id,
					};
					console.log('saleOrder_manageItems');
					saleOrder_manageItems(item, 'remove');
				}
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

	function saleOrder_lineItem(itemNumber) {
		$content = '<div class="form-group lineitems lineitem_' + itemNumber + '">' +
			'<div class="col-md-5">' +
			'<select class="products_list_' + itemNumber + ' form-control" name="lineitems[' + itemNumber + '][sku]" id="sku_' + itemNumber + '" tabindex="' + (tabOrder++) + '" data-placeholder="Product SKU" data-allow-clear="true" required></select>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control price" name="lineitems[' + itemNumber + '][price]" placeholder="Price" id="price_' + itemNumber + '" tabindex="' + (tabOrder++) + '" type="text" required>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control qty" name="lineitems[' + itemNumber + '][qty]" placeholder="Quantity" id="qty_' + itemNumber + '" tabindex="' + (tabOrder++) + '" type="text" required>' +
			'</div>' +
			'<div class="col-md-2">' +
			'<input class="form-control amount" placeholder="Amount" readonly="true" type="text">' +
			'<input class="form-control uid" name="lineitems[' + itemNumber + '][uid]" id="uid_' + itemNumber + '" type="hidden">' +
			'</div>' +
			'<div class="col-md-1">' +
			'<div class="spinner-buttons input-group-btn">' +
			'<button type="button" class="btn add_lineItem btn_add_' + itemNumber + '" tabindex="' + (tabOrder++) + '">' +
			'<i class="fa fa-plus"></i>' +
			'</button>' +
			'<button type="button" class="btn remove_lineItem btn_minus_' + itemNumber + '" data-item_id="" tabindex="' + (tabOrder++) + '">' +
			'<i class="fa fa-trash"></i>' +
			'</button>' +
			'</div>' +
			'</div>' +
			'</div>';

		if (itemNumber == 0) {
			$('.sales_product_items').prepend($content);
		} else {
			$('.lineitem_' + (itemNumber - 1)).after($content);
		}

		var products_list = '<option></option>';
		var selected = "";
		$.each(all_parent_sku, function (k, v) {
			products_list += "<option " + selected + " value=" + v.key + ">" + v.value + "</option>";
		});
		$('.products_list_' + itemNumber).append(products_list);
		$('.products_list_' + itemNumber).select2('destroy').select2({
			placeholder: 'Product SKU',
			allowClear: 'true'
		});

		if (itemNumber !== 0) {
			$('.products_list_' + itemNumber).select2('open');
		}
		// $('.products_list_'+itemNumber).on('select2-selecting', function(e){
		$('.products_list_' + itemNumber).change(function (e) {
			var pid = $(this).val();
			if (pid == "")
				return;

			var formData = "action=get_selling_price_pid_party&pid=" + pid + "&party_id=" + party_id + "&client_id=" + client_id;
			$item_price = submitForm(formData, "GET");
			$price_input = $(this).parent().next().find('.price');
			$price_input = $('.lineitem_' + itemNumber + ' .price').val($item_price).focus();
		});

		saleOrder_lineItemClick(itemNumber);
		saleOrder_lineItemCalculation();

		$("#price_" + itemNumber).inputmask("decimal", {
			"rightAlign": false,
			"numericInput": true,
			"placeholder": "",
			"greedy": false,
			onUnMask: function (maskedValue, unmaskedValue) {
				return unmaskedValue;
			}
		});
		$("#price_" + itemNumber).focus(function () { $(this).select(); });

		$(".qty").inputmask({
			"mask": "9",
			"repeat": 5,
			"greedy": false
		});
	}

	function saleOrder_lineItemCalculation() {
		$('.price, .qty, .add_lineItem, .remove_lineItem').on('focusout change changeInput input click', function () {
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

	function saleOrder_handleValidation() {
		var form = $('#add-sales-order');
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
				formData.append('action', 'save_so');
				var so_type = 'draft';
				var button = 'info';
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
					if (input.name == "type" && input.value == "create") {
						so_type = 'create';
						button = 'success';
					}
				});

				$('.form-actions .btn-' + button, form).attr('disabled', true);
				$('.form-actions .btn-' + button + ' i', form).addClass('fa fa-sync fa-spin');
				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						$('#so_id').val(s.so_id);
						UIToastr.init('success', 'Sales Order', s.msg);
						$('.form-actions .btn', form).attr('disabled', false);
						$('.form-actions i', form).removeClass('fa-sync fa-spin');
						if (so_type == 'create') {
							var form = document.createElement("form");
							form.setAttribute("method", "post");
							form.setAttribute("action", "print.php");
							form.setAttribute("target", "_blank");

							var params = {
								"action": "print_sales_order",
								"id": s.so_id,
								"client_id": client_id
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

							window.setTimeout(function () {
								window.location.href = 'sales_view.php';
							}, 1000);

						} else {
							window.history.replaceState(null, null, "?so_id=" + s.so_id + "&type=" + so_type);
						}
					} else if (s.type == 'error') {
						UIToastr.init('error', 'Sale Order', s.msg);
					} else {
						UIToastr.init('error', 'Sale Order', s.msg);
					}
				}, 20);
			}
		});
	}

	function saleOrder_handleFormData($so_id) {
		var el = ".portlet-body";
		var formData = 'action=get_so&so_id=' + $so_id + '&client_id=' + client_id;
		var s = submitForm(formData, "GET");

		$('.customers_list').val(s.party_id).trigger('change');
		$(".customer_name").attr('required', false);
		if (s.party_id == 0) { // CASH SALE
			$('.customer_name').val(s.so_notes['customer_name']);
			$(".customer_name").attr('required', true);
		}
		$('.so_notes').text(s.so_notes['notes']);

		for (var i = 0; i < s.items.length; i++) {
			$('#sku_' + i).val(s.items[i].item_id).trigger('change');
			$('#price_' + i).val(s.items[i].item_price);
			$('#qty_' + i).val(s.items[i].item_qty).blur().focus();
			if (s.items[i].uid != "") {
				var uids = $.parseJSON(s.items[i].uid);
				$('#uid_' + i).val(uids);
				$.each(uids, function (k, uid) {
					var item = {
						"sku": s.items[i].sku,
						"item_id": s.items[i].item_id,
						"uid": uid
					};
					saleOrder_manageItems(item, 'add');
				});
				$('.scanner-container').removeClass('hide');
			}
			if (i != s.items.length - 1) {
				$('.btn_add_' + i).trigger('click');
			}
		}

		window.setTimeout(function () {
			App.unblockUI(el);
		}, 500);
	}

	function saleOrder_handleCustomers() {

		var s_options = '<option></option>';
		var selected = "";
		$.each(customers, function (k, v) {
			s_options += "<option " + selected + " value=" + v.party_id + ">" + v.party_name + "</option>";
		});
		$('.customers_list').append(s_options);

		// Initialize select2me
		$('.customers_list').select2({
			placeholder: "Select Customer",
			allowClear: true
		});

		$('.customers_list').on('change', function () {
			party_id = $(this).val();

			if (party_id == "") {
				$('.customers_name').addClass('hide');
				$('.current_due').addClass('hide');
				$('.due_amount').html("");
			} else if (party_id == 0) { // CASH
				$('.current_due').addClass('hide');
				$('.due_amount').html("");
				$('.customers_name').removeClass('hide');
				$('.sales_product').removeClass('hide');
				$('.sales_action').removeClass('hide');
				$('.scan_to_add').attr('disabled', false);
			} else {
				var pageContent = $('.form-body');
				App.blockUI({ target: pageContent });
				var formData = 'action=get_current_dues&party_id=' + party_id
				window.setTimeout(function () {
					var s = submitForm(formData, "GET");
					$('.due_amount').html(s.due);
					$('.current_due').removeClass('hide');
					$('.sales_product').removeClass('hide');
					$('.sales_action').removeClass('hide');
					$('.scan_to_add').attr('disabled', false);
					App.unblockUI(pageContent);
				}, 100);
			}
		});
	}

	function saleOrder_handleProducts() {
		var products_list = '<option></option>';
		var selected = "";
		$.each(all_parent_sku, function (k, v) {
			products_list += "<option " + selected + " value=" + v.key + ">" + v.value + "</option>";
		});
		$('.products_list').append(products_list);

		$('.products_list').select2({
			placeholder: "Product SKU",
			allowClear: true
		}).addClass('form-control');
		$('.lineitems span.select2-container').addClass('form-control');

		// $('.products_list').on('select2-selecting', function(e){
		$('.products_list').change(function (e) {
			var pid = $(this).val();
			if (pid == "")
				return;
			var formData = "action=get_selling_price_pid_party&pid=" + pid + "&party_id=" + party_id + "&client_id=" + client_id;
			$item_price = submitForm(formData, "GET");
			$price_input = $('.lineitem_0 .price').val($item_price).focus();
			// $price_input = $(this).parent().next().find('.price');
			// $price_input.select().focus();
			// $price_input.val($item_price);
		});
	}

	function saleOrder_handleScan() {
		$('.scan_to_add').click(function () {
			$('#uid').focus();
		});

		$('#quick-add').submit(function (e) {
			e.preventDefault();
			var uid = $('#uid').val();
			$('#uid').val("");

			if (uid == "")
				return;

			$('#uid').addClass('spinner').attr('disabled', true);
			window.setTimeout(function () {
				var formData = "action=get_uid_details&uid=" + uid;
				var s = submitForm(formData, "GET");
				if (s.type == "success") {
					$('.scanner-container').removeClass('hide');
					if (s.isBoxCtn) {
						$.each(s.item, function (k, itm) {
							saleOrder_manageItems(itm, 'add');
						});
					} else {
						saleOrder_manageItems(s.item, 'add');
					}
				} else {
					UIToastr.init('error', 'Quick Add', s.msg);
				}
				$('#uid').removeClass('spinner').attr('disabled', false).focus();
			}, 50);
		});

		$('.btn-add-items').click(function (e) {
			e.preventDefault();
			$('#quick_load').modal('hide');
			$('#add-sales-order .lineitems').remove();
			App.blockUI({ target: ".sales_product" });

			var lineitem = 0;
			window.setTimeout(function () {
				$.each(scanned_uids, function (k, item) {
					lineitems.push(lineitem);
					saleOrder_lineItem(lineitem);
					$('#sku_' + lineitem).val(item.item_id).attr('readonly', true).trigger('change');
					$('#qty_' + lineitem).val(item.uids.length).attr('readonly', true);
					$('#uid_' + lineitem).val(item.uids);
					$('.btn_minus_' + lineitem).attr('data-item_id', item.item_id).attr('disabled', false);
					if (lineitem != 0)
						$('.btn_add_' + (lineitem - 1)).attr('disabled', true);
					lineitem++;

				});
				App.unblockUI('.sales_product');
			}, 200);
		});
	}

	function saleOrder_manageItems(item, event) {
		if (event == "add") {
			var item_id = item.item_id
			if (typeof (scanned_uids[item_id]) === "undefined") {
				scanned_uids[item_id] = {
					"sku": item.sku,
					"item_id": item_id,
					"uids": []
				};
			}

			if (scanned_uids[item_id].uids.includes(item.uid)) {
				UIToastr.init('info', 'Quick Add', 'Item already added');
				return;
			}

			scanned_uids[item_id].uids.push(item.uid);

			if ($('.' + item_id).length) {
				var newItemLi = '<li class="' + item.uid + '">' + item.uid + ' <i class="fa fa-times remove_uid" data-item_id="' + item_id + '" data-uid="' + item.uid + '"></i></li>';
				$('.quick_load .success-container .' + item_id + ' .success-list').append(newItemLi);
			} else {
				var newItem = '<div class="' + item_id + '">' +
					'<label class="success-sku">' + item.sku + ' <i class="fa fa-times remove_uid" data-item_id="' + item_id + '"></i></label>' +
					'<ul class="success-list">' +
					'<li class="' + item.uid + '">' + item.uid + ' <i class="fa fa-times remove_uid" data-item_id="' + item_id + '" data-uid="' + item.uid + '"></i></li>' +
					'</ul>' +
					'</div>';
				$('.quick_load .success-container').append(newItem);
			}
		}

		if (event == "remove") {
			if (typeof (item.uid) === "undefined") {
				delete scanned_uids[item.item_id];
				$('.' + item.item_id).remove();
				return;
			}

			const index = scanned_uids[item.item_id].uids.indexOf(item.uid)
			if (index > -1) {
				scanned_uids[item.item_id].uids.splice(index, 1)
				if (scanned_uids[item.item_id].uids.length === 0)
					delete scanned_uids[item.item_id];
			}
		}

		$('.remove_uid').off().on('click', function () {
			if (confirm('Remove this item?')) {
				var item = {
					'item_id': $(this).data('item_id'),
					'uid': $(this).data('uid')
				};
				saleOrder_manageItems(item, 'remove');
				$(this).parent('li').remove();
				if ($('.' + item.item_id + ' li').length == 0)
					$('.' + item.item_id).remove();
			}
		});
	}

	function saleOrder_printPreview() {
		// general settings
		$.fn.modal.defaults.spinner = $.fn.modalmanager.defaults.spinner =
			'<div class="loading-spinner" style="width: 200px; margin-left: -100px;">' +
			'<div class="progress progress-striped active">' +
			'<div class="progress-bar" style="width: 100%;"></div>' +
			'</div>' +
			'</div>';

		$.fn.modalmanager.defaults.resize = true;

		var $modal = $('#modal-print-preview');
		$('.print_preview').off('click').on('click', function (e) {
			e.preventDefault();

			// create the backdrop and wait for next modal to be triggered
			$('body').modalmanager('loading');
			$location = $(this).attr('data-href');

			setTimeout(function () {
				$modal.load($location, '', function () {
					$modal.modal();
					saleOrder_printSO();
				});
			}, 1000);
		});
	}

	function saleOrder_printSO() {
		$('.print, .preview-print').off('click').on('click', function (e) {
			e.preventDefault();
			var form = document.createElement("form");
			form.setAttribute("method", "post");
			form.setAttribute("action", "print.php");
			form.setAttribute("target", "_blank");

			var params = {
				"action": "print_sales_order",
				"id": $(this).data('so_id'),
				"client_id": $(this).data('client_id'),
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
	}

	function salePayments_handleFormData() {
		var s_options = '<option></option>';
		var selected = "";
		$.each(customers, function (k, v) {
			s_options += "<option " + selected + " value=" + v.party_id + ">" + v.party_name + "</option>";
		});
		$('.customers_list').append(s_options);

		// Initialize select2me
		$('.customers_list').select2({
			placeholder: "Select Customer",
			allowClear: true
		});

		$('.customers_list').on('change', function () {
			var this_select = $(this);
			var party_id = $(this).val();
			$(".payment_due").val("");
			$(".recent-payment-details").hide();
			if (party_id) {
				this_select.attr('disabled', true);
				if (party_id == 0) {
					$('.payment_remarks').addClass('hide').attr('disabled', true);
					$('.payment_remarks_select').removeClass('hide').attr('disabled', false);

					var reference_options = "<option></option>";
					window.setTimeout(function () {
						var formData = 'action=get_cash_so&client_id=' + client_id;
						var s = submitForm(formData, "GET");
						if (s.data != "") {
							$.each(s.data, function (k, v) {
								reference_options += "<option value='" + v.so_id + "'>" + v.reference + "</option>"
							});

							$('.payment_remarks_select').empty().append(reference_options);
							$('.payment_remarks_select').select2('destroy');
							$('.payment_remarks_select').select2({
								placeholder: 'Select Sales Order',
								allowClear: true
							});

							$('.payment_remarks_select').change(function () {
								var so_id = $(this).val();
								if (so_id != "") {
									window.setTimeout(function (k, v) {
										var formData = 'action=get_cash_ledger&so_id=' + so_id + '&client_id=' + client_id;
										var s = submitForm(formData, "GET");
										$(".payment_due").val(s.due);
										$(".recent-payment-details").show().removeClass('hide');
										$(".table-recent-payment-details tbody").html(s.transactions);
										$('.payment_amount').focus();
										salePayments_handleUpdates();
									}, 10);
								} else {
									$(".payment_due").val("");
								}
							});
						}
						this_select.attr('disabled', false);
					}, 10);
				} else {
					$('.payment_remarks').removeClass('hide').attr('disabled', false);
					$('.payment_remarks_select').addClass('hide').attr('disabled', true);
					window.setTimeout(function () {
						var formData = 'action=get_payments_details&party_id=' + party_id;
						var s = submitForm(formData, "GET");

						$(".payment_due").val(s.due);
						$(".recent-payment-details").show().removeClass('hide');
						$(".table-recent-payment-details tbody").html(s.transactions);
						this_select.attr('disabled', false);
						$('.payment_amount').focus();
						salePayments_handleUpdates();
					}, 10);
				}
			}
		});

		$('.payment_amount').inputmask("decimal", {
			"rightAlign": false,
			"numericInput": true,
			"placeholder": "",
			"greedy": false,
			onUnMask: function (maskedValue, unmaskedValue) {
				return unmaskedValue;
			}
		});
		$('.payment_amount').focus(function () { $(this).select(); });

		if (jQuery().datepicker) {
			$('.date-picker').datepicker({
				setDate: new Date(),
				todayHighlight: true,
				defaultViewDate: 'today',
				autoclose: true,
			});
		}
	}

	function salePayments_handleValidation() {
		var form = $('#new-payment');
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
				formData.append('action', 'save_payment');
				$is_edit = false;
				$.each(other_data, function (key, input) {
					if (input.name == "payment_id" && input.value == "")
						$is_edit = true;
					formData.append(input.name, input.value);
				});

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');

				var s = submitForm(formData, "POST");
				if (s.type == 'success') {
					UIToastr.init('success', 'Sales Payment', s.msg);
					$('.form-actions .btn', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
					$(form)[0].reset();
					$('.customers_list').select2("val", "");
					$('.payment_mode').select2("val", "");
					$('.recent-payment-details').hide();
				} else {
					UIToastr.init('error', 'Sale Order', s.msg);
				}
			}
		});
	}

	function salePayments_handleUpdates() {
		$('.payment_edit').off().on('click', function (e) {
			e.preventDefault();
			$('.payment_id').val($(this).data('paymentid'));
			$('.payment_mode').select2('data', { id: $(this).data('mode').toLowerCase(), text: $(this).data('mode') });
			$('.payment_amount').val($(this).data('amount'));
			$('.payment_date').val($(this).data('date'));
			$('.payment_reference').val($(this).data('reference'));
			$('.payment_remarks').val($(this).data('notes'));
		});

		$('.payment_delete').off().on('click', function (e) {
			e.preventDefault();
			if (confirm('Are you sure you want to delete this payment transaction?')) {
				var formData = new FormData();
				formData.append('action', 'delete_payment');
				formData.append('payment_id', $(this).data('paymentid'));
				var s = submitForm(formData, "POST");
				UIToastr.init(s.type, 'Delete Transaction', s.msg);
				if (s.type == "success") {
					$(this).closest('tr').remove();
					$('.customers_list').trigger('change');
				}
			}
		})
	}

	function saleLedger_handleFormData() {
		var s_options = '<option></option>';
		var selected = "";
		$.each(customers, function (k, v) {
			s_options += "<option " + selected + " value=" + v.party_id + ">" + v.party_name + "</option>";
		});
		$('.customers_list').append(s_options);

		// Initialize select2me
		$('.customers_list').select2({
			placeholder: "Select Customer",
			allowClear: true
		});

		$('.customers_list').on('change', function () {
			party_id = $(this).val();
		});

		if (!jQuery().daterangepicker) {
			return;
		}

		$('#defaultrange').daterangepicker({
			opens: (App.isRTL() ? 'left' : 'right'),
			autoApply: true,
			minDate: moment().subtract('years', 2),
			maxDate: moment(),
			ranges: {
				'Last 7 Days': [moment().subtract('days', 6), moment()],
				'Last 30 Days': [moment().subtract('days', 30), moment()],
				'This Month': [moment().startOf('month'), moment().endOf('month')],
				'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
			},
			buttonClasses: ['btn'],
			format: 'MM/DD/YYYY',
			separator: ' to ',
			locale: {
				applyLabel: 'Apply',
				fromLabel: 'From',
				toLabel: 'To',
				customRangeLabel: 'Custom Range',
				daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
				monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
				firstDay: 1
			}
		},
			function (start, end) {
				$('#defaultrange input').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
			});

		$('.customer_ledger_print').click(function () {
		});
	}

	function saleLedger_handleValidation() {
		console.log('saleLedger_handleValidation');
		var form = $('#sales_ledger');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			debug: true,
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
				var dates = $('#defaultrange input').val().split(" - ");;
				// success.show();
				error.hide();
				$('.form-group .btn-success', form).attr('disabled', true);
				$('.form-group .btn-success i', form).addClass('fa fa-sync fa-spin');
				$(".table-sales-ledger tbody").html("");
				$(".table-sales-ledger tbody").html('<tr><td colspan="5"><center><i class="fa fa-sync fa-spin"></i></center></td></tr>');

				var formData = "action=get_customer_ledger&party_id=" + party_id + "&start_date=" + dates[0] + "&end_date=" + dates[1];
				var s = submitForm(formData, "GET");
				// if (s.type == 'success'){
				$('.form-group .btn-success', form).attr('disabled', false);
				$('.form-group .btn-success i', form).removeClass('fa-sync fa-spin');
				$("#portlet_sales_ledger_details").show().removeClass('hide');
				$(".table-sales-ledger tbody").html("");
				$(".table-sales-ledger tbody").html(s.transactions);
				$(".customer_ledger_print").attr("href", "./print.php?action=customer_ledger_print&party_id=" + party_id + "&start_date=" + dates[0] + "&end_date=" + dates[1]);
				// } else {
				// 	UIToastr.init('error', 'Sale Order', s.msg);
				// }
			}
		});
	}

	function salesRetrurn_handleInit() {
		$("#so_id").inputmask({
			"mask": "SO_999999"
		});

		var items, remove_uids = [];
		$('.search_so').click(function () {
			$('.btn-sales-return').attr('disabled', true);
			$('.select_so_items').html("<option value=''></option>").attr('disabled', true);
			$('.select_so_items').val(null).trigger('change');
			$(".select_so_items").select2("destroy");
			var so_id = $('#so_id').val().replace("SO_", "");
			var formData = 'action=get_so&so_id=' + so_id + '&client_id=' + client_id;
			var s = submitForm(formData, "GET");
			if (s.so_status != "created") {
				UIToastr.init('error', 'SO Status', 'SO Status is ' + s.so_status);
			} else {
				items = [];
				var skus = [];
				for (var i = 0; i < s.items.length; i++) {
					if (s.items[i].uid != "") {
						skus.push(s.items[i].sku);
						items[s.items[i].sku] = $.parseJSON(s.items[i].uid);
					}
				}

				for (var i = 0; i < skus.length; i++) {
					var newOption = new Option(skus[i], skus[i], false, false);
					$('.select_so_items').append(newOption);
				}
				$('.select_so_items').select2({ 'allowClear': true }).attr('disabled', false);
				$('.so_id').val(so_id);
				$('.party_id').val(s.party_id);
			}
		});

		$('.select_so_items').change(function () {
			var sku = $(this).val();
			if (sku != "") {
				$('.product_uids').empty();
				$.each(items[sku], function (k, uid) {
					var selected = "";
					if ($.inArray(uid, remove_uids) != -1)
						selected = " selected ";
					$('.multi-select').append('<option ' + selected + '>' + uid + '</option>');
				})
				$('.product_uids').multiSelect('refresh');

			} else {
				$('.product_uids').empty();
				$('.product_uids').multiSelect('refresh');
			}
		});

		$('.product_uids').multiSelect({
			afterSelect: function (values) {
				remove_uids.push(values[0]);
				if (!$('.return_uids option[value=' + values[0] + ']').length > 0);
				$('.return_uids').append('<option id="' + values[0] + '" selected>' + values[0] + '</option>');

				$('#so_id, .search_so').attr('disabled', true);
				$('.btn-sales-return').attr('disabled', false);
				$('.selected_uids_count').html(remove_uids.length + ' Item(s)');
			},
			afterDeselect: function (values) {
				const index = remove_uids.indexOf(values[0]);
				if (index > -1) {
					remove_uids.splice(index, 1);
				}
				if (!$('.return_uids option[value=' + values[0] + ']').length > 0);
				$('.return_uids option[id="' + values[0] + '"]').remove();

				if (remove_uids.length === 0) {
					$('#so_id, .search_so').attr('disabled', false);
					$('.btn-sales-return').attr('disabled', true);
				}

				$('.selected_uids_count').html(remove_uids.length + ' Item(s)');
			}
		});
	}

	function saleReturn_handleValidation() {
		console.log('saleLedger_handleValidation');
		var form = $('#sales_return');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			debug: true,
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
				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');
				$(".table-sales-ledger tbody").html("");
				$(".table-sales-ledger tbody").html('<tr><td colspan="5"><center><i class="fa fa-sync fa-spin"></i></center></td></tr>');
				var uids = $('.return_uids').val();
				var so_id = $('.so_id').val();
				var party_id = $('.party_id').val();
				window.setTimeout(function () {
					var formData = new FormData();
					formData.append('action', 'save_sor');
					formData.append('so_id', so_id);
					formData.append('party_id', party_id);
					formData.append('uids', uids);
					var s = submitForm(formData, 'POST');
					if (s.type == 'success') {
						$('.form-actions .btn-success', form).attr('disabled', false);
						$('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');
						UIToastr.init('success', 'Sale Return', s.msg);
						window.setTimeout(function () {
							window.location.reload();
						}, 300);
					} else {
						$('.form-actions .btn-success', form).attr('disabled', false);
						$('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');
						UIToastr.init('error', 'Sale Return', s.msg);
					}
				}, 10);
			}
		});


		var el = ".portlet-body";
		// var formData = 'action=get_so&so_id='+$so_id+'&client_id='+client_id;
		// var s = submitForm(formData, "GET");

		// for (var i = 0; i < s.items.length; i++) {
		// 	$('#sku_'+i).val(s.items[i].item_id).trigger('change');
		// 	if (s.items[i].uid != ""){
		// 		var uids = $.parseJSON(s.items[i].uid);
		// 		$('#uid_'+i).val(uids);
		// 		$.each(uids, function(k, uid){
		// 			var item = {
		// 				"sku": s.items[i].sku,
		// 				"item_id": s.items[i].item_id,
		// 				"uid": uid
		// 			};
		// 			saleReturn_manageItems(item, 'add');
		// 		});
		// 		$('.scanner-container').removeClass('hide');
		// 	}
		// 	if (i != s.items.length-1){
		// 		$('.btn_add_'+i).trigger('click');
		// 	}
		// }

		window.setTimeout(function () {
			App.unblockUI(el);
		}, 500);
	}

	function saleReturn_manageItems() {

	}

	return {
		init: function ($type) {
			if ($type == "new") {
				saleOrder_handleCustomers();
				saleOrder_handleProducts();
				saleOrder_lineItem(0);
				saleOrder_handleValidation();
				saleOrder_handleScan();
				$so_id = "";
				if (window.location.href.indexOf("so_id") !== -1) {
					App.blockUI({ target: ".portlet-body" });
					$so_id = App.getURLParameter('so_id');
					$so_type = App.getURLParameter('type');
					if ($so_id != '' /*&& $so_type == 'draft'*/) {
						saleOrder_handleFormData($so_id);
						// } else {
						// 	App.blockUI({target: ".portlet-body"});
						// 	$('.form-actions .btn').attr('disabled', 'true');
						// 	UIToastr.init('info', 'sale Order', 'This sale order cannot be edited. Redirecting now...');
						// 	setTimeout(function(){ window.location.href = './sales_view.php'; }, 3000);
					}
				}
			} else if ($type == "view") {
				saleOrder_handleTable();
			} else if ($type == "payments") {
				// $('.recent-payment-details').hide();
				salePayments_handleFormData();
				salePayments_handleValidation();
			} else if ($type == "ledger") {
				saleLedger_handleFormData();
				saleLedger_handleValidation();
			} else if ($type == "return") {
				salesRetrurn_handleInit();
				saleReturn_handleValidation();
			}
		}
	}
}();