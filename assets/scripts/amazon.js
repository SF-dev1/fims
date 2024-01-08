"use_strict";

var Amazon = function () {
	var currentRequest = null;
	var is_queued = false;
	var handler = "order";
	-1 !== window.location.href.indexOf("returns") && (handler = "return");
	var fulfillmentChannel = "MFN";
	var tab = "";

	var submitForm = function (formData, $type) {
		var currentReq = null;
		var $ret = "";
		currentReq = $.ajax({
			url: "ajax_load.php?token=" + new Date().getTime(),
			cache: true,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			showLoader: true,
			beforeSend: function () {
				if (currentRequest != null) {
					currentRequest.abort();
				} else {
					currentRequest = currentReq;
				}
			},
			success: function (s) {
				if (s != "") {
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
	};

	function tabChange_handleTable(handler) {
		if (handler == "order") {
			var type = typeof type === 'undefined' ? "new" : type;
			tab = (tab != '') ? tab : "#portlet_new";
		} else if (handler == "return") {
			var type = typeof type !== 'undefined' ? type : "start";
			tab = tab != '' ? tab : "#portlet_start";
		}
		currentRequest = typeof currentRequest !== 'null' ? currentRequest : null;
		refreshCount(handler);
		viewOrder_handeleTable(type, tab);
		orderStatus_handleUpdate();

		// Order Status Tabs
		$(".order_type a").click(function () {
			if ($(this).parent().attr('class') == "active") {
				return;
			}
			tab = $(this).attr('href');
			type = tab.substr(tab.indexOf("_") + 1);
			refreshCount(handler);
			viewOrder_handeleTable(type, tab);
		});
	}

	function viewOrder_handeleTable(type, tab) {
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
					data: "fulfillment_channel",
					title: "Fulfillment Channel",
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
			]
		};

		var targets = {
			"order": [2, 3, 4, 5, 6, 7],
			"return": [2, 3, 4, 5, 6, 7, 8, 9],
		}

		var sorting_order = [6, 'asc'];
		if (handler == "return")
			var sorting_order = [7, 'asc'];

		var table = $('#selectable_amazon_' + type + '_' + handler);
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12 table-filter'><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
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
				url: "ajax_load.php?action=view_orders&type=" + type + "&handler=" + handler + "&fulfillmentChannel=" + fulfillmentChannel + "&token=" + new Date().getTime(),
				// dataSrc: 'data',
				cache: false,
				type: "GET",
				beforeSend: function () {
					if (currentRequest != null) {
						currentRequest && currentRequest.readyState != 4 && currentRequest.abort();
						currentRequest = null;
					}

					// if (oTable && oTable.hasOwnProperty('settings')) {
					// console.log('sett');
					// oTable.settings()[0].jqXHR.abort();
					// oTable.context[0].jqXHR.abort();
					// }
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
		}

		// Initiate DrawBack before table init
		function afterDrawDataTable() {
			$('.dataTables_paginate a').bind('click', function () {
				App.scrollTop();
			}),
				App.initUniform(),
				update_checked_count();

			if (handler == "return")
				return_handleClaims();
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
			$('.schedule_pickup').off().on('click', function () {
				var button = $(this);
				var allChecked = $('.checkboxes:checked');
				is_queued = true;
				if (allChecked.length > 0) {
					$(".btn-select").attr('disabled', true);
					button.find("i").addClass("fa fa-sync fa-spin");

					var orderIds = {};
					$(allChecked).each(function (index, element) {
						var account = $(this).data('account');
						var group = $(this).data('group');
						var orderId = $(this).val();

						if (typeof orderIds[account] === 'undefined')
							orderIds[account] = [];

						var glength = orderIds[account].length;
						orderIds[account][glength] = orderId;
					});
					var orders = JSON.stringify(orderIds);

					var formData = new FormData();
					formData.append('action', 'schedule_pickup');
					formData.append('orders', orders);

					window.setTimeout(function () {
						var s = submitForm(formData, 'POST');
						if (s.success != 0) {
							UIToastr.init('success', 'Schedule Pickup', 'Succesfully scheduled pickup for orders');
							window.setTimeout(function () {
								$.each(s.accounts, function (k, v) {
									var formData = new FormData();
									formData.append('action', 'requestTrackingId');
									formData.append('account', v);
									var s = submitForm(formData, 'POST');
								});
								UIToastr.init(s.type, 'Request Tracking ID', s.msg);
								refreshCount(handler);
								viewOrder_handeleTable(type, tab);
							}, 100);
						} else {
							UIToastr.init('error', 'Schedule Pickup', 'Error scheduling pickup for ' + s.error + '/' + allChecked.length + ' order(s)!!');
						}

						$(".btn-select").attr('disabled', false);
						button.find("i").removeClass("fa fa-sync fa-spin");
						is_queued = false;
					}, 100);
				} else {
					UIToastr.init('info', 'Schedule Pickup', 'No orders selected');
				}
			});

			$('.create_labels').off().on('click', function () {
				var button = $(this);
				var allChecked = $('.checkboxes:checked');
				if (allChecked.length > 0) {
					is_queued = true;
					$(".btn-select").attr('disabled', true);
					button.find("i").addClass("fa fa-sync fa-spin");

					var orderIds = {};
					$(allChecked).each(function (index, element) {
						var account = $(this).data('account');
						var group = $(this).data('group');
						var orderId = $(this).val();

						if (typeof orderIds[account] === 'undefined')
							orderIds[account] = [];

						var glength = orderIds[account].length;
						orderIds[account][glength] = orderId;
					});
					var orders = JSON.stringify(orderIds);

					var formData = new FormData();
					formData.append('action', 'get_labels');
					formData.append('orders', orders);

					window.setTimeout(function () {
						var s = submitForm(formData, 'POST');
						if (typeof (s.success) !== "undefined") {
							UIToastr.init('success', 'Create Labels', 'Succesfully generated labels for orders');
							// getTrackingId
							window.setTimeout(function () {
								$.each(s.accounts, function (k, v) {
									var formData = 'action=getTrackingId&account=' + v;
									var s = submitForm(formData, 'GET');
								});
								UIToastr.init(s.type, 'Request Tracking ID', s.msg);

								window.setTimeout(function () {
									refreshCount(handler);
									viewOrder_handeleTable(type, tab);
								}, 100);
							}, 100);

							// getLabels
							window.setTimeout(function () {
								$.each(s.success, function (account, orders) {
									if (orders.length != 0) {
										var form = document.createElement("form");
										form.setAttribute("method", "post");
										form.setAttribute("action", "ajax_load.php");
										form.setAttribute("target", "_blank");

										var params = {
											"action": "generate_labels",
											"order_ids": orders, //s.success.toString(),
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
							UIToastr.init('error', 'Create Labels', 'Error creating labels');
						}

						$(".btn-select").attr('disabled', false);
						button.find("i").removeClass("fa fa-sync fa-spin");
						is_queued = false;
					}, 100);
				} else {
					UIToastr.init('info', 'Create Labels', 'No orders selected');
				}
			});

			$('.update_status').off().on('click', function () {
				var button = $(this);
				var allChecked = $('.checkboxes:checked');
				if (allChecked.length > 0) {
					is_queued = true;
					$(".btn-select").attr('disabled', true);
					button.find("i").addClass("fa fa-sync fa-spin");

					var orderIds = {};
					$(allChecked).each(function (index, element) {
						var account = $(this).data('account');
						var group = $(this).data('group');
						var orderId = $(this).val();

						if (typeof orderIds[account] === 'undefined')
							orderIds[account] = [];

						var glength = orderIds[account].length;
						orderIds[account][glength] = orderId;
					});
					var orders = JSON.stringify(orderIds);

					var formData = new FormData();
					formData.append('action', 'update_orders');
					formData.append('orders', orders);

					window.setTimeout(function () {
						var s = submitForm(formData, 'POST');
						if (typeof (s.success) !== "undefined") {
							UIToastr.init('success', 'Update Order', 'Succesfully updated orders');
							refreshCount(handler);
							viewOrder_handeleTable(type, tab);
						}

						$(".btn-select").attr('disabled', false);
						button.find("i").removeClass("fa fa-sync fa-spin");
						is_queued = false;
					}, 100);
				} else {
					UIToastr.init('info', 'Create Labels', 'No orders selected');
				}
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
			all && (checked ? $(".checkboxes").prop("checked", true) : $(".checkboxes").prop("checked", false));

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
		var formData = "action=get_orders_count&handler=" + handler + "&fulfillmentChannel=" + fulfillmentChannel;
		window.setTimeout(function () {
			var s = submitForm(formData, 'GET');
			if (handler == "order") {
				$(".portlet_pending.count").text("(" + (s.orders.pending) + ")");
				$(".portlet_new.count").text("(" + (s.orders.new) + ")");
				$(".portlet_packing.count").text("(" + (s.orders.packing) + ")");
				$(".portlet_rtd.count").text("(" + (s.orders.rtd) + ")");
				$(".portlet_shipped.count").text("(" + (s.orders.shipped) + ")");
				$(".portlet_cancelled.count").text("(" + (s.orders.cancelled) + ")");
			} else if (handler == "return") {
				$(".portlet_start.count").text("(" + (s.orders.start) + ")");
				$(".portlet_in_transit.count").text("(" + (s.orders.in_transit) + ")");
				$(".portlet_out_for_delivery.count").text("(" + (s.orders.out_for_delivery) + ")");
				$(".portlet_delivered.count").text("(" + (s.orders.delivered) + ")");
				$(".portlet_received.count").text("(" + (s.orders.received) + ")");
				$(".portlet_claimed.count").text("(" + (s.orders.claimed) + ")");
				$(".portlet_completed.count").text("(" + (s.orders.completed) + ")");
			}
		}, 1);
	}

	// ORDER STATUS UPDATE
	function orderStatus_handleUpdate() {
		console.log('orderStatus_handleUpdate');
		// ORDER STATUS MODAL
		var type = "";
		$("#order_status_update").on("show.bs.modal", function (e) {
			$('#update_type').val($(e.relatedTarget).data('updatetype'));

			$("body").addClass("modal-open");
			// RESET
			count_s = 0;
			count_e = 0;
			trackingIds = [];
			clearModelContainer();
			$('#trackin_id').val('');
			$('#account_id').val('').trigger('change');
		}).on('hidden.bs.modal', function (e) {
			$("body").removeClass("modal-open");
			type = tab.substr(tab.indexOf("_") + 1);
			console.log(tab);
			console.log(type);
			refreshCount(handler);
			viewOrder_handeleTable(type, tab);
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

			if (trackin_id == "") {
				return;
			}

			$('#trackin_id').val('');

			if (trackingIds.indexOf(trackin_id) === 0 && handler == "order") {
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
					// console.log(rtd_trackingIds);
				},
				error: function () {
					console.log('Error Processing your Request!!');
				}
			});
		});
	}

	function clearModelContainer() {
		$(".list-container").hide();
		$('.scan-passed').html('0<span class="scan-passed-ok"><i class="icon icon-ok-sign" aria-hidden="true"></i></span>');
		$('.scan-failed-count').html('0<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>');
		$(".list-container ul").html("");
	}

	// SCAN SHIP
	function scanShip_handleInit() {
		var ship_track, success_ship = {};
		var audio = document.getElementById('chatAudio');
		var fulfillable_quantity = 0;
		var buttonClicked = true;
		$('#sidelineProduct').prop("disabled", true);

		function disableF5(e) {
			if ((e.which || e.keyCode) == 116 || (e.which || e.keyCode) == 82) {
				// console.log(e);
				if (!buttonClicked) {
					e.preventDefault();
					if (confirm("Are You sure?")) {
						endProcess(orderId);
						window.location.reload();
					}
				}
			}
		};

		$(document).on("keydown", disableF5);
		// var success_ship = [];
		$('form#get-product').submit(function (e) {
			e.preventDefault();
			$('.product_details').addClass('hide');
			var tracking_id = $('#tracking_id').val().toUpperCase();
			$('#tracking_id').addClass('spinner').attr('disabled', true);
			window.setTimeout(function () {
				var s = submitForm("action=scan_ship&tracking_id=" + tracking_id, 'GET');
				fulfillable_quantity = parseInt(s.fulfillable_quantity);
				if (s.type == "success") {
					$('.product_details .item_groups').html(s.content);
					$('.product_details').removeClass('hide');
					ship_track = s.items;
					success_ship = {};
					$('#tracking_id').removeClass('spinner').attr('disabled', false);
					$('#uid').focus();
					$('#sidelineProduct').prop("disabled", false);
					buttonClicked = false;

				} else {
					UIToastr.init(s.type, 'Scan Pack Ship', s.msg);
					$('.product_details').addClass('hide');
					$('#tracking_id').removeClass('spinner').attr('disabled', false).val("").focus();
					audio.play();
				}
			}, 10);
		});

		$('#sidelineProduct').click(function () {
			$("#sidelineProduct i").addClass("fa-spin fa-spinner");
			endProcess(orderId);
			buttonClicked = true;
			$('#tracking_id').removeClass('spinner').attr('disabled', false);
		});

		$('form#uid-scanship').submit(function (e) {
			e.preventDefault();
			var uid = $('#uid').val().toUpperCase();

			const exists = $.map(success_ship, function (v) { return v; }).includes(uid);
			if (exists) {
				UIToastr.init('info', 'Scan Pack Ship', 'Inventory with id ' + uid + ' is already scanned');
				$('#uid').val("").focus();
				return;
			}

			$('#uid').attr('disabled', true);
			window.setTimeout(function () {
				var s = submitForm("action=get_uid_details&uid=" + uid, 'GET');
				if (s.type == "success") {
					var found = false;
					$.each(ship_track, function (orderId, item) {
						const index = Object.keys(item).find(key => item[key].item_id === s.item_id);
						if (typeof (index) !== "undefined") {
							found = true;
							if (item[index].scan)
								return;
							const k = Object.keys(item).find(key => item[key].item_id === s.item_id && !item[key].scan);
							if (typeof (k) !== "undefined") {
								const v = item[k];
								if (v.scan)
									return false;

								if (v.item_id == s.item_id && v.scan) {
									UIToastr.init('info', 'Scan Pack Ship', 'All items of this SKU already packed');
									$('#uid').attr('disabled', false).val("").focus();
									audio.play();
									return false;
								}

								if (v.item_id == s.item_id && v.scanned_qty != v.quantity && !v.scan) {
									if (!Array.isArray(success_ship[orderId])) {
										success_ship[orderId] = new Array();
									}
									$('.item_' + v.item_id + '_' + orderId + ' .uids').removeClass('hide');
									success_ship[orderId].push(uid);
									item[k].uids.push(uid);
									item[k].scanned_qty += 1;
									$('.item_' + v.item_id + '_' + orderId + ' .uids .uid').text(item[k].uids.toString().replace(/,/g, ", "));

									if (item[k].scanned_qty == item[k].quantity) {
										item[k].scan = true;
										$('.item_' + v.item_id + '_' + orderId + ' .scanned').removeClass('hide');
									}
									fulfillable_quantity = fulfillable_quantity - 1;
									if (fulfillable_quantity > 0)
										return false;
								} else {
									UIToastr.init('error', 'Scan Pack Ship', 'Incorrect Product');
									$('#uid').attr('disabled', false).val("").focus();
									audio.play();
									return false;
								}
							} else {
								UIToastr.init('error', 'Scan Pack Ship', 'Incorrect Product');
								$('#uid').attr('disabled', false).val("").focus();
								audio.play();
								return false;
							}
						}
					});

					if (!found) {
						UIToastr.init('error', 'Scan Pack Ship', 'Incorrect Product');
						$('#uid').attr('disabled', false).val("").focus();
						audio.play();
						return false;
					}

					// const pending_scan = item.some(el => el.scan === false);
					if (fulfillable_quantity === 0) {
						var formData = new FormData();
						formData.append('action', 'save_scan_ship');
						formData.append('scanned_items', JSON.stringify(success_ship));
						window.setTimeout(function () {
							var s = submitForm(formData, "POST");
							if (s.type == "success" || s.type == "error") {
								if (s.type == "success") {
									$('.product_details .item_groups').html("");
									$('.product_details').addClass('hide');
									$('#tracking_id').val("").focus();
								} else {
									audio.play();
								}
							} else {
								audio.play();
								UIToastr.init('error', 'Scan Pack Ship', 'Error Processing Request. Please try again later.');
							}
						}, 10);
					}
				} else if (s.type == "error") {
					audio.play();
					UIToastr.init(s.type, 'Scan Pack Ship', s.msg);
				} else {
					audio.play();
					UIToastr.init('error', 'Scan Pack Ship', 'Error Processing Request. Please try again later.');
				}
				$('#uid').attr('disabled', false).val("").focus();
				$('#tracking_id').removeClass('spinner').attr('disabled', false);
			}, 10);
		});

		function endProcess(orderId) {
			$('#sidelineProduct').prop("disabled", true);
			var formdata = "action=sideline_product&orderId=" + orderId;
			$.when(
				submitForm(formdata, "GET"),
			).then(function (s) {
				if (s.type == "success" || s.type == "error") {
					if (s.type == "success") {
						UIToastr.init(s.type, 'Sideline Order', s.message);
						$('.product_details .item_groups').html("");
						$('.product_details').addClass('hide');
						$('#tracking_id').val("").focus();
						$("#sidelineProduct i").removeClass("fa-spin fa-spinner");
						return true;
					} else {
						audio.play();
						$("#sidelineProduct i").removeClass("fa-spin fa-spinner");
						return false;
					}
				} else {
					audio.play();
					UIToastr.init('error', 'Sideline Order', 'Error Processing Request. Please try again later.');
					$("#sidelineProduct i").removeClass("fa-spin fa-spinner");
					return false;
				}
			});
		}

	}

	// RETURNS IMPORT
	function return_handleImport() {
		var form = $('#import-returns');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				returns_csv: {
					required: true,
				},
				account_id: {
					required: true,
				},
			},

			messages: { // custom messages for radio buttons and checkboxes
				returns_csv: {
					required: "Please select a valid CSV File"
				},
				account_id: {
					required: "Please select a Account"
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
				label.closest('.form-group').removeClass('has-error'); // set success class to the control group
			},

			errorPlacement: function (error, element) {
				if (element.attr("name") == "alias_file") {
					error.appendTo("#alias_file_error");
				} else {
					error.appendTo(element.parent("div"));
				}
			},

			submitHandler: function (form) {
				error.hide();

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions i', form).addClass('fa fa-sync fa-spin');

				var formData = new FormData();
				formData.append('action', 'import_returns');
				formData.append('returns_csv', $('.returns_csv')[0].files[0]);
				formData.append('account_id', $('.account_id option:selected', form).val());
				formData.append('is_flex', $('input[name="is_flex"]:checked', form).val());

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						// if (s.error == 0 && s.existing == 0){
						$('#return-import').modal('hide');
						UIToastr.init(s.type, 'Flex Returns Import', "Successfull imported returns");
						// } else {
						// 	UIToastr.init(s.type, 'Flex Returns Import', "Successfull imported returns with few errors");
						// 	error.html("Successfull added: "+s.success+"<br /> Error: "+s.error+" <br /> Existing: "+s.existing+" <br />Error File: <a href='"+s.file+"' target='_blank'>Download Processed file.</a>").show();
						// }
						$('.form-actions .btn-success', form).attr('disabled', false);
						$('.form-actions i', form).removeClass('fa fa-sync fa-spin');
					} else if (s.type == 'error') {
						error.html(s.message).show();
					} else {
						error.text('Error Processing your Request!! ' + s.message).show();
					}
				}, 500);
			}
		});
	}

	function return_handleClaims() {
		var forms = $('.update-refund-date');
		$.each(forms, function (key, r_form) {
			var form = $(r_form);
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
						.closest('.input-group').addClass('has-error'); // set error class to the control group
				},

				unhighlight: function (element) { // revert the change done by hightlight
					$(element)
						.closest('.input-group').removeClass('has-error'); // set error class to the control group
				},

				success: function (label) {
					label.closest('.input-group').removeClass('has-error'); // set success class to the control group
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
					$.each(other_data, function (key, input) {
						formData.append(input.name, input.value);
					});

					window.setTimeout(function () {
						var s = submitForm(formData, "POST");
						if (s.type == 'success') {
							UIToastr.init(s.type, 'Refund Date', s.message);
							$('#' + btn_id, form).html('<i class="fa fa-check-circle"></i>');
							// $('#'+btn_id, form).closest('tr').remove();
						} else if (s.type == 'error') {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init(s.type, 'Refund Date', s.message);
						} else {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init('info', 'Refund Date', 'Error Processing Request! Please try again later.');
						}
					}, 500);
				}
			});
		});

		var forms = $('.cancel-return');
		$.each(forms, function (key, r_form) {
			var form = $(r_form);
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
						.closest('.input-group').addClass('has-error'); // set error class to the control group
				},

				unhighlight: function (element) { // revert the change done by hightlight
					$(element)
						.closest('.input-group').removeClass('has-error'); // set error class to the control group
				},

				success: function (label) {
					label.closest('.input-group').removeClass('has-error'); // set success class to the control group
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
					$.each(other_data, function (key, input) {
						formData.append(input.name, input.value);
					});

					window.setTimeout(function () {
						var s = submitForm(formData, "POST");
						if (s.type == 'success') {
							UIToastr.init(s.type, 'Return Cancellation', s.message);
							$('#' + btn_id, form).html('<i class="fa fa-check-circle"></i>');
							$('#' + btn_id, form).closest('tr').remove();
							refreshCount(handler);
						} else if (s.type == 'error') {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init(s.type, 'Return Cancellation', s.message);
						} else {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init('info', 'Return Cancellation', 'Error Processing Request! Please try again later.');
						}
					}, 500);
				}
			});
		});

		var forms = $('.add-claim-details');
		$.each(forms, function (key, r_form) {
			var form = $(r_form);
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
						.closest('.input-group').addClass('has-error'); // set error class to the control group
				},

				unhighlight: function (element) { // revert the change done by hightlight
					$(element)
						.closest('.input-group').removeClass('has-error'); // set error class to the control group
				},

				success: function (label) {
					label.closest('.input-group').removeClass('has-error'); // set success class to the control group
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
					$.each(other_data, function (key, input) {
						formData.append(input.name, input.value);
					});

					window.setTimeout(function () {
						var s = submitForm(formData, "POST");
						if (s.type == 'success') {
							UIToastr.init(s.type, 'Returns Claim', s.message);
							$('#' + btn_id, form).html('<i class="fa fa-check-circle"></i>');
							$('#' + btn_id, form).closest('tr').remove();
							refreshCount(handler);
						} else if (s.type == 'error') {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init(s.type, 'Returns Claim', s.message);
						} else {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init('info', 'Returns Claim', 'Error Processing Request! Please try again later.');
						}
					}, 500);
				}
			});
		});

		$('.is_claim').off().on('click', function () {
			var claim_input = $(this).data('claimbox');
			App.updateUniform($(this));
			if ($(this).prop("checked")) {
				$('#' + claim_input).prop("disabled", false);
			} else {
				$('#' + claim_input).prop("disabled", true).val("");
				$('#' + claim_input).closest('.input-group').removeClass('has-error'); // set error class to the control group;
			}
		});

		var forms = $('.add-reimbursement-complete');
		$.each(forms, function (key, r_form) {
			var form = $(r_form);
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
						.closest('.input-group, .form-group').addClass('has-error'); // set error class to the control group
				},

				unhighlight: function (element) { // revert the change done by hightlight
					$(element)
						.closest('.input-group, .form-group').removeClass('has-error'); // set error class to the control group
				},

				success: function (label) {
					label.closest('.input-group, .form-group').removeClass('has-error'); // set success class to the control group
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
					$.each(other_data, function (key, input) {
						formData.append(input.name, input.value);
					});

					window.setTimeout(function () {
						console.log(other_data);
						var s = submitForm(formData, "POST");
						if (s.type == 'success') {
							UIToastr.init(s.type, 'Returns Claim', s.message);
							$('#' + btn_id, form).html('<i class="fa fa-check-circle success"></i>');
							$('#' + btn_id, form).closest('tr').remove();
							refreshCount(handler);
						} else if (s.type == 'error') {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init(s.type, 'Returns Claim', s.message);
						} else {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init('info', 'Returns Claim', 'Error Processing Request! Please try again later.');
						}
					}, 500);
				}
			});
		});

		$('.is_reimbursed').off().on('click', function () {
			var claim_input = $(this).data('claimbox');
			App.updateUniform($(this));
			if ($(this).prop("checked")) {
				$('#' + claim_input).prop("disabled", false);
			} else {
				$('#' + claim_input).prop("disabled", true).val("");
				$('#' + claim_input).closest('.input-group').removeClass('has-error'); // set error class to the control group;
			}
		});

		var forms = $('.update-claim-id');
		$.each(forms, function (key, r_form) {
			var form = $(r_form);
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
						.closest('.input-group, .form-group').addClass('has-error'); // set error class to the control group
				},

				unhighlight: function (element) { // revert the change done by hightlight
					$(element)
						.closest('.input-group, .form-group').removeClass('has-error'); // set error class to the control group
				},

				success: function (label) {
					label.closest('.input-group, .form-group').removeClass('has-error'); // set success class to the control group
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
					var claimId, newClaimId;
					$.each(other_data, function (key, input) {
						formData.append(input.name, input.value);
						if (input.name == "newClaimId")
							newClaimId = input.value;

						if (input.name == "claimId")
							claimId = input.value;
					});

					if (claimId == newClaimId) {
						$('#' + btn_id, form).html('Go!').attr('disabled', false);
						UIToastr.init('info', 'Claim Update', 'New Claim ID is same as Current Claim ID');
						return;
					}

					window.setTimeout(function () {
						var s = submitForm(formData, "POST");
						if (s.type == 'success') {
							UIToastr.init(s.type, 'Claim Update', s.message);
							$('#' + btn_id, form).html('<i class="fa fa-check-circle success"></i>');
							var ele_id = btn_id.replace('update_claim_submit_', '');
							$('#claim_id_' + ele_id).html($('#claim_id_' + ele_id).html().replaceAll(claimId, newClaimId));
							$('#form_claim_id_' + ele_id).val(newClaimId);
							$('#claim_status_' + ele_id).html('pending');
						} else if (s.type == 'error') {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init(s.type, 'Claim Update', s.message);
						} else {
							$('#' + btn_id, form).html('Go!').attr('disabled', false);
							UIToastr.init('info', 'Claim Update', 'Error Processing Request! Please try again later.');
						}
					}, 500);
				}
			});
		});

		$('.claimId').inputmask({ "mask": "99999-99999-9999999" });
	}

	return {
		//main function to initiate the module
		init: function (type) {
			switch (type) {
				case 'orders':
					tabChange_handleTable('order');
					break;

				case 'flex_orders':
					fulfillmentChannel = "AFN";
					tabChange_handleTable('order');
					break;

				case 'scan_ship':
					scanShip_handleInit();
					break;

				case 'returns':
					fulfillmentChannel = "";
					tabChange_handleTable('return');
					return_handleImport();
					break;
			}
		}
	};
}();
