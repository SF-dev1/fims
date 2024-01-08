"use strict";

var AMS = function () {

	// Submit for with mutlipart data containing Image
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

	function vendorInvoice_handleTable() {
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
			"pending": {
				title: "Pending Payment",
				class: " badge badge-default"
			},
			"partial_paid": {
				title: "Partial Paid",
				class: " badge badge-info"
			},
			"paid": {
				title: "Paid",
				class: " badge badge-success"
			},
		};

		var table = $('#ams_invoices');
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
				url: "ajax_load.php?action=get_invoices",
				type: "GET",
			},
			columns: [
				{
					title: 'Invoice Date',
					data: 'ams_invoice_date',
					columnFilter: 'dateFilter',
				}, {
					title: 'Invoice Number',
					data: "ams_invoice_number",
					columnFilter: "selectFilter",
				}, {
					data: "ams_invoice_total",
					title: "Amount",
					columnFilter: "rangeFilter",
				}, {
					title: "Vendor",
					data: "party_name",
					columnFilter: "selectFilter",
				}, {
					data: "ams_invoice_status",
					title: "Status",
					columnFilter: "statusFilter",
				}, {
					data: "actions",
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
					orderable: !1,
					// width: "100px",
					// render: function(a, t, e, s) {
					// 	$return = "";
					// 	return $return;
					// }
				}, {
					targets: -2,
					render: function (a, t, e, s) {
						return void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
					}
					// }, {
					// 	targets: 5,
					// 	width: "150px"
				}
			],
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
			$('.reload').bind('click', function (e) {
				e.preventDefault();
				var el = jQuery(this).closest(".portlet").children(".portlet-body");
				App.blockUI({ target: el });
				$('.select2').val("").trigger('change');
				$('.filter-cancel').trigger('click');
				table.find('tbody').html("");
				oTable.ajax.reload();
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		};

		var actionCallBack = function () {
			$(document).off().on('click', '.send-mail', function (e) {
				e.preventDefault();
				var btn = $(this);
				var invoice_id = $(this).data('invoice_id');
				btn.attr('disabled', true);
				btn.find('i').removeClass('fa-envelope').addClass('fa-sync fa-spin');
				var formData = 'action=email_invoice_pdf_csv&invoice_id=' + invoice_id;
				window.setTimeout(function () {
					var s = submitForm(formData, 'GET');
					s = s[invoice_id];
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Resend Invoice & Report', s.msg);
					} else {
						UIToastr.init('error', 'Resend Invoice & Report', 'Error Processing Request. Please try again later.');
					}
					btn.attr('disabled', false);
					btn.find('i').removeClass('fa-sync fa-spin').addClass('fa-envelope');
				}, 10);
			});
		};
	}

	function vendorPayments_handleData() {

		var s_options = '<option></option>';
		var selected = "";
		$.each(vendors, function (k, v) {
			s_options += "<option " + selected + " value=" + v.party_id + ">" + v.party_name + "</option>";
		});
		$('.vendors_list').append(s_options);

		// Initialize select2me
		$('.vendors_list').select2({
			placeholder: "Select Vendor",
			allowClear: true
		});

		$('.vendors_list').on('change', function () {
			var party_id = $(this).val();
			$(".payment_due").val("");
			$(".recent-payment-details").hide();
			if (party_id) {
				var formData = 'action=get_payments_details&party_id=' + party_id;
				var s = submitForm(formData, "GET");

				$(".payment_due").val(s.due);
				$(".recent-payment-details").show().removeClass('hide');
				$(".table-recent-payment-details tbody").html(s.transactions);
				vendorPayments_handleUpdates();
			}
		});

		$('.invoice_id').change(function () {
			var invoice_id = $(this).val();
			$('.payment_due').val(invoices_data[invoice_id]);
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
				todayHighlight: true,
				defaultViewDate: 'today',
				autoclose: true,
			});
		}
	}

	function vendorPayments_handleUpdates() {
		$('.payment_edit').click(function (e) {
			e.preventDefault();
			$('.payment_id').val($(this).data('paymentid'));
			$('.payment_mode').select2('data', { id: $(this).data('mode').toLowerCase(), text: $(this).data('mode') });
			$('.payment_amount').val($(this).data('amount'));
			$('.payment_date').val($(this).data('date'));
			$('.payment_reference').val($(this).data('reference'));
			$('.payment_remarks').val($(this).data('notes'));
		});

		$('.payment_delete').click(function (e) {
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

	function vendorPayments_handleValidation() {
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
				var is_edit = false;
				$.each(other_data, function (key, input) {
					if (input.name == "payment_id" && input.value == "")
						is_edit = true;
					formData.append(input.name, input.value);
				});

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						UIToastr.init('success', 'Sales Payment', s.msg);
						$('.form-actions .btn', form).attr('disabled', false);
						$('.form-actions i', form).removeClass('fa-sync fa-spin');
						$(form)[0].reset();
						$('.vendors_list').select2("val", "");
						$('.payment_mode').select2("val", "");
						$('.recent-payment-details').hide();
					} else {
						UIToastr.init('error', 'Sale Order', s.msg);
					}
				}, 10);
			}
		});
	}

	var party_id = "";
	function vendorLedger_handleFormData() {
		var s_options = '<option></option>';
		var selected = "";
		$.each(vendors, function (k, v) {
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
			autoApply: true,
			opens: (App.isRTL() ? 'left' : 'right'),
			minDate: moment().subtract('years', 2),
			maxDate: moment().add('days', 1),
			showDropdowns: false,
			ranges: {
				'Last 7 Days': [moment().subtract('days', 6), moment()],
				'Last 30 Days': [moment().subtract('days', 30), moment()],
				'This Month': [moment().startOf('month'), moment().endOf('month')],
				'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
			},
		},
			function (start, end) {
				$('#defaultrange input').val(start.format('MMMM DD, YYYY') + ' - ' + end.format('MMMM DD, YYYY'));
			});

		$('.ledger_print').click(function () {
		});
	}

	function vendorLedger_handleValidation() {
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
				// var party_id = 
				var dates = $('#defaultrange input').val().split(" - ");;
				// success.show();
				error.hide();
				$('.form-group .btn-success', form).attr('disabled', true);
				$('.form-group .btn-success i', form).addClass('fa fa-sync fa-spin');
				$(".table-sales-ledger tbody").html("");
				$(".table-sales-ledger tbody").html('<tr><td colspan="5"><center><i class="fa fa-sync fa-spin"></i></center></td></tr>');

				window.setTimeout(function () {
					var formData = "action=get_vendor_ledger&party_id=" + party_id + "&start_date=" + dates[0] + "&end_date=" + dates[1];
					var s = submitForm(formData, "GET");
					$('.form-group .btn-success', form).attr('disabled', false);
					$('.form-group .btn-success i', form).removeClass('fa-sync fa-spin');
					$("#portlet_sales_ledger_details").show().removeClass('hide');
					$(".table-sales-ledger tbody").html("");
					$(".table-sales-ledger tbody").html(s.transactions);
					$(".ledger_print").attr("href", "./ajax_load.php?action=ledger_print&party_id=" + party_id + "&start_date=" + dates[0] + "&end_date=" + dates[1]);
				}, 10);
			}
		});
	}

	function vendorSlabRate_handleTable() {
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

		var statusFilters = {};
		var table = $('#ams_rate_slabs');
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
				url: "ajax_load.php?action=get_ams_rate_slab",
				type: "GET",
			},
			columns: [
				{
					title: 'Slab Name',
					data: 'name',
					columnFilter: 'selectFilter',
				}, {
					// 	title: 'Start Date',
					// 	data: "start_date",
					// 	columnFilter: "dateFilter",
					// }, {
					// 	title: 'End Date',
					// 	data: "end_date",
					// 	columnFilter: "dateFilter",
					// }, {
					title: "Commission Fees",
					data: "commission_fees",
					columnFilter: "",
				}, {
					title: "Fixed Fees",
					data: "fixed_fees",
					columnFilter: "",
				}, {
					data: "actions",
					title: "Actions",
					columnFilter: "actionFilter",
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
					// width: "100px",
					// render: function(a, t, e, s) {
					// 	$return = "";
					// 	return $return;
					// }
				}, {
					targets: -2,
					render: function (a, t, e, s) {
						return void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
					}
					// }, {
					// 	targets: 5,
					// 	width: "150px"
				}
			],
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
				table.find('tbody').html("");
				oTable.ajax.reload();
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		};

		var actionCallBack = function () { };
	}

	function vendorSlabRate_handleNew() {
		if (jQuery().datepicker) {
			$(".start_date").datepicker({
				minDate: 0,
				autoclose: true,
				format: "dd-M-yyyy",
			});
			$(".start_date").on('changeDate', function () {
				var startDate = $(this).datepicker('getDate');
				var minDate = $(this).datepicker('getDate');
				minDate.setDate(minDate.getDate() + 1);
				$(".end_date").datepicker("setStartDate", minDate);
			});

			$(".end_date").datepicker({
				maxDate: 0,
				autoclose: true,
				format: "dd-M-yyyy",
			});
			$(".end_date").on('changeDate', function () {
				var endDate = $(this).datepicker('getDate');
				var maxDate = $(this).datepicker('getDate');
				maxDate.setDate(maxDate.getDate() - 1);
				$(".start_date").datepicker("setEndDate", maxDate);
			});
		}

		$("#add-rateSlab [name='fixed_fees_type']").bind('change', function () {
			if ($(this).val() == "variable") {
				$('.fixed_fee_flat').addClass('hide');
				$('.fixed_fee_variable').removeClass('hide');
				$('.fixed_fee_flat input[type="number"]').attr('required', false).attr('disabled', true);
				$('.fixed_fee_variable input[type="number"]').attr('required', true).attr('disabled', false);
				$('.fixed_fee_variable input[type="checkbox"]').attr('disabled', false);
				$('.checkbox').attr('disabled', false);
				App.updateUniform('.checkbox');
			} else {
				$('.fixed_fee_flat').removeClass('hide');
				$('.fixed_fee_variable').addClass('hide');
				$('.fixed_fee_flat input[type="number"]').attr('required', true).attr('disabled', false);
				$('.fixed_fee_variable input[type="number"]').attr('required', false).attr('disabled', true);
				$('.fixed_fee_variable input[type="checkbox"]').attr('disabled', true);
				$('.checkbox').attr('disabled', true);
				App.updateUniform('.checkbox');
			}
		});

		var i = 0;
		$('.add_variable_slot').click(function () {
			i++;
			var checkbox_checked = "";
			if ($('.variable_fixed_fees').is(":checked"))
				checkbox_checked = "checked";
			$('.variable_slots').append('<div class="row fixed_fee_variable" id="variable_' + i + '"><label class="control-label col-md-12"><u>Fixed Fees Slot:</u></label><div class="col-md-3"><div class="form-group"><label class="control-label">Orders:</label><input type="number" name="fixed_fees[' + i + '][order][min]" class="form-control col-md-6" placeholder="Min Orders" step="1" min="0" max="1001"><input type="number" name="fixed_fees[' + i + '][order][max]" class="form-control col-md-6" placeholder="Max Orders" step="1" min="100" max="10000"></div></div><div class="col-md-4"><div class="form-group"><label class="control-label">Selling Price:</label><input type="number" name="fixed_fees[' + i + '][order][selling_price][min]" class="form-control col-md-6" placeholder="Min Selling Price" step="1" min="100" max="1001"><input type="number" name="fixed_fees[' + i + '][order][selling_price][max]" class="form-control col-md-6" placeholder="Max Selling Price" step="1" min="100" max="10000"></div></div><div class="col-md-5"><div class="form-group"><label class="control-label">Fees:</label><div class="input-group"><input type="number" name="fixed_fees[' + i + '][order][selling_price][rate]" class="form-control col-md-6" placeholder="Slab Rate" step="1" min="1" max="100"><span class="input-group-addon">With GST?<input type="checkbox" class="checkbox" ' + checkbox_checked + '></span></div></div><button type="button" class="btn btn-xs pull-right delete_variable_slab" data-variableslab="variable_' + i + '"><i class="fa fa-trash"></i></button></div></div>');
			App.initUniform();
		})

		$(document).on('click', '.delete_variable_slab', function () {
			var variable_slab = $(this).data('variableslab');
			if (confirm('Delete current fixed fee slab?')) {
				$('#' + variable_slab).remove();
			}
			// $('#'+variable_slab).toggleClass('fixed_fee_variable_hover');
		});

		$(document).on('click', '.checkbox', function () {
			if ($(this).is(":checked")) {
				$('.variable_fixed_fees').prop('checked', true);
				$('.checkbox').prop('checked', true);
				App.updateUniform('.checkbox, .variable_fixed_fees');
			} else {
				$('.variable_fixed_fees').prop('checked', false);
				$('.checkbox').prop('checked', false);
				App.updateUniform('.checkbox, .variable_fixed_fees');
			}
		});
	}

	function vendorSlabRate_handleValidation() {
		var form = $('#add_rateSlab');
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
				error.hide();

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'add_ams_rate_slab');
				var is_edit = false;
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				$('.modal-footer .btn-success', form).attr('disabled', true);
				$('.modal-footer .btn-success i', form).addClass('fa fa-sync fa-spin');
				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					$('.modal-footer .btn-success', form).attr('disabled', false);
					$('.modal-footer .btn-success i', form).removeClass('fa-sync fa-spin');
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Rate Slabs', s.msg);
						if (s.type == "success") {
							$('#add-rateSlab').modal('toggle');
							$('#reload-dataTable').trigger('click');
							$('.reset_form').trigger('click');
						}
					} else {
						UIToastr.init('error', 'Rate Slabs', 'Error Processing Request. Please try again later.');
					}
				}, 10);
			}
		});
	}

	function vendorSlabRate_handleApply() {
		$(document).on('click', '.apply_slab', function () {
			$('.slab_id').val($(this).data('slabid'));
			$('.slab_name').val($(this).data('slabname'));
		});

		// SET DEFAULT DATES
		$(".start_date").datepicker("setStartDate", '-30d');
		$(".end_date").datepicker("setStartDate", '-29d');
		$(".end_date").datepicker("setEndDate", '+730d');

		$('.vendor').change(function () {
			$(".start_date, .end_date").datepicker("update", '');
			var vendor_id = $(this).val();
			$.each(vendors, function (k, v) {
				if (v.party_id == vendor_id) {
					var slab = JSON.parse(v.party_ams_rate_slab);
					if (slab) {
						var last = slab.length - 1;
						var start_date = new Date(slab[last].endDate);
						start_date = new Date(start_date.getTime() + 1000); // added 1 second to get next date
						$(".start_date").datepicker("setStartDate", start_date);
						$(".end_date").datepicker("setStartDate", start_date);
					}
					return; // end the loop at first find
				}
			});
		});
	}

	function vendorSlabRate_handleApplyValidation() {
		var form = $('#apply_rateSlab');
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
				error.hide();

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'set_ams_rate_slab');
				var is_edit = false;
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				$('.modal-footer .btn-success', form).attr('disabled', true);
				$('.modal-footer .btn-success i', form).addClass('fa fa-sync fa-spin');
				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					$('.modal-footer .btn-success', form).attr('disabled', false);
					$('.modal-footer .btn-success i', form).removeClass('fa-sync fa-spin');
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Apply Slabs', s.msg);
						if (s.type == "success") {
							window.location.reload();
						}
					} else {
						UIToastr.init('error', 'Apply Slabs', 'Error Processing Request. Please try again later.');
					}
				}, 10);
			}
		});
	}

	return {
		//main function to initiate the module
		init: function ($type) {
			switch ($type) {
				case 'invoices':
					vendorInvoice_handleTable();
					break

				case 'payments':
					vendorPayments_handleData();
					vendorPayments_handleValidation();
					break

				case 'ledger':
					vendorLedger_handleFormData();
					vendorLedger_handleValidation();
					break

				case 'rate_slab':
					vendorSlabRate_handleTable();
					vendorSlabRate_handleNew();
					vendorSlabRate_handleValidation();
					vendorSlabRate_handleApply();
					vendorSlabRate_handleApplyValidation();
					break
			}
		}
	};
}();