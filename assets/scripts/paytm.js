"use_strict";

var Paytm = function () {
	var currentRequest = null;
	var is_queued = false;
	var handler = "order";
	-1 !== window.location.href.indexOf("returns") && (handler = "return");

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
			var tab = typeof tab !== 'undefined' ? tab : "#portlet_new";
		} else if (handler == "return") {
			var type = typeof type !== 'undefined' ? type : "start";
			var tab = typeof tab !== 'undefined' ? tab : "#portlet_start";
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
		}

		var table = $('#selectable_paytm_' + type + '_orders');
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
				url: "ajax_load.php?action=view_orders&type=" + type + "&token=" + new Date().getTime(),
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
			columns: [
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
					title: "",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[6, 'desc']
			],
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
					targets: [2, 3, 4, 5, 6, 7],
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
							}, 100);

							refreshCount(handler);
							viewOrder_handeleTable(type, tab);

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
		var formData = "action=get_orders_count&handler=" + handler;
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
				$(".portlet_received.count").text("(" + (s.orders.return_received) + ")");
				$(".portlet_claimed.count").text("(" + (s.orders.return_claimed) + ")");
				$(".portlet_return_completed.count").text("(" + (s.orders.return_completed) + ")");
				$(".portlet_return_unexpected.count").text("(" + (s.orders.return_unexpected) + ")");
			}
		}, 1);
	}

	// ORDER STATUS UPDATE
	function orderStatus_handleUpdate() {
		// ORDER STATUS MODAL
		var tab, type = "";
		$("#order_status_update").on("show.bs.modal", function (e) {
			if ($(e.relatedTarget).hasClass('mark_rtd')) {
				$('#update_type').val('rtd');
				tab = "#portlet_packing";
				type = "packing";
			} else {
				$('#update_type').val('shipped');
				tab = "#portlet_handover";
				type = "rtd";
			}

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
			refreshCount();
			viewOrder_handeleTable(type, tab);
		});

		$("#mark_orders").submit(function (e) {
			e.preventDefault();
			$(".form-group").removeClass("has-error");

			var trackin_id = $.trim($('#trackin_id').val().toUpperCase());
			var account_id = $('#account_id').val();
			var update_type = $('#update_type').val();

			if (account_id == "") {
				$(".form-group").addClass("has-error");
				return;
			}
			if (trackin_id == "") {
				return;
			}

			$('#trackin_id').val('');

			if (trackingIds.indexOf(trackin_id) === 0) {
				return;
			}

			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=mark_orders&type=" + update_type + "&tracking_id=" + trackin_id + "&account_id=" + account_id,
				success: function (s) {
					s = $.parseJSON(s);
					$('.list-container').show();
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
					// console.log(rtd_trackingIds);
				},
				error: function () {
					console.log('Error Processing your Request!!');
				}
			});
		});

		/*// MARK SHIPPED			
		$("#mark-shipped").submit(function(e){
			e.preventDefault();
			$(".control-group").removeClass("error");

			$trackin_id = $.trim($('#ship_trackin_id').val().toUpperCase());
			$account = $('#ship_account_name').val();
			$account = typeof $account !== 'undefined' ? $account : "";

			if ($account == ""){
				$(".control-group").addClass("error");
				return;
			}
			if ($trackin_id == ""){
				return;
			}

			$('#ship_trackin_id').val('');

			if (shipped_trackingIds.indexOf($trackin_id) === 0){
				return;
			} else {
				shipped_trackingIds.push($trackin_id);
			}

			$.ajax({
				url: "ajax_load.php?token="+ new Date().getTime(),
				type: 'POST',
				data: "action=mark_shipped&trackin_id="+$trackin_id+"&account="+$account,
				success: function(s){
					s = $.parseJSON(s);
					$('.list-container').show();
					if(s.type == 'success'){
						$('.success-container ul').append(s.content);
						s_count_s = s_count_s+1;
						$('.scan-passed').html(s_count_s+'<span class="scan-passed-ok"><i class="icon icon-check-circle" aria-hidden="true"></i></span>');
						if (shipped_trackingIds.indexOf($trackin_id) === -1){
							shipped_trackingIds.push($trackin_id);
						}
					} else {
						$('.failed-container ul').append(s.content);
						s_count_e = s_count_e+1;
						$('.scan-failed-count').html(s_count_e+'<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>');
						if (shipped_trackingIds.indexOf($trackin_id) === -1){
							shipped_trackingIds.push($trackin_id);
						}
					}
				},
				error: function(){
					console.log('Error Processing your Request!!');
				}
			});
		});*/
	}

	function clear_model_container() {
		$(".list-container").hide();
		$('.scan-passed').html('0<span class="scan-passed-ok"><i class="icon icon-ok-sign" aria-hidden="true"></i></span>');
		$('.scan-failed-count').html('0<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>');
		$(".list-container ul").html("");
	}

	// SCAN SHIP
	function scanShip_handleInit() {
		var ship_track, success_ship = {};
		var audio = document.getElementById('chatAudio');
		// var success_ship = [];
		$('form#get-product').submit(function (e) {
			e.preventDefault();
			$('.product_details').addClass('hide');
			var tracking_id = $('#tracking_id').val().toUpperCase();
			$('#tracking_id').addClass('spinner').attr('disabled', true);
			window.setTimeout(function () {
				var s = submitForm("action=scan_ship&tracking_id=" + tracking_id, 'GET');
				if (s.type == "success") {
					$('.product_details .item_groups').html(s.content);
					$('.product_details').removeClass('hide');
					ship_track = s.items;
					success_ship = {};
					$('#tracking_id').removeClass('spinner').attr('disabled', false);
					$('#uid').focus();
				} else {
					UIToastr.init(s.type, 'Scan Pack Ship', s.msg);
					$('.product_details').addClass('hide');
					$('#tracking_id').removeClass('spinner').attr('disabled', false).val("").focus();
					audio.play();
				}
			}, 10);
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
					$.each(ship_track, function (k, v) {
						if (v.item_id == s.item_id && v.scan) {
							UIToastr.init('info', 'Scan Pack Ship', 'All items of this SKU already packed');
							$('#uid').attr('disabled', false).val("").focus();
							audio.play();
							return;
						}

						if (v.item_id == s.item_id && v.scanned_qty != v.quantity && !v.scan) {
							if (!Array.isArray(success_ship[v.orderId])) {
								success_ship[v.orderId] = new Array();
							}
							$('.item_' + v.item_id + ' .uids').removeClass('hide');
							success_ship[v.orderId].push(uid);
							ship_track[k].uids.push(uid);
							ship_track[k].scanned_qty += 1;
							$('.item_' + v.item_id + ' .uids .uid').text(ship_track[k].uids.toString().replace(/,/g, ", "));

							if (ship_track[k].scanned_qty == ship_track[k].quantity) {
								ship_track[k].scan = true;
								$('.item_' + v.item_id + ' .scanned').removeClass('hide');
							}
						}
					});

					const pending_scan = ship_track.some(el => el.scan === false);
					if (!pending_scan) {
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
			}, 10);
		});
	}

	return {
		//main function to initiate the module
		init: function (type) {
			switch (type) {
				case 'orders':
					tabChange_handleTable('order');
					break;

				case 'scan_ship':
					scanShip_handleInit();
					break;
			}
		}
	};
}();