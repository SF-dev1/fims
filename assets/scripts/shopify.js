"use_strict";

var Shopify = function () {
	var currentRequest = null,
		is_queued = false,
		handler = "order",
		tab = "",
		shopify_tags = "",
		start_date = '',
		end_date = '';

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
			tab = (tab != '') ? tab : "#portlet_new";
			shopify_tags = tags.shopify;
		} else if (handler == "return") {
			var type = typeof type !== 'undefined' ? type : "start";
			tab = tab != '' ? tab : "#portlet_start";
		}
		currentRequest = typeof currentRequest !== 'null' ? currentRequest : null;
		comments_handleInit(type);
		viewOrder_handeleTable(type, tab);
		refreshCount(handler);
		orderStatus_handleUpdate();

		// Order Status Tabs
		$(".order_type a").click(function () {
			if ($(this).parent().attr('class') == "active") {
				return;
			}
			tab = $(this).attr('href');
			type = tab.substr(tab.indexOf("_") + 1);
			comments_handleInit(type);
			comments_handleScroll();
			viewOrder_handeleTable(type, tab);
			refreshCount(handler);
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
			]
		};

		var targets = {
			"order": [2, 3, 4, 5, 6, 7, 8],
			"return": [2, 3, 4, 5, 6, 7, 8],
		}

		var sorting_order = [6, 'asc'];
		if (handler == "order" && (type == "in_transit" || type == "pickup_pending"))
			sorting_order = [7, 'asc'];
		if (handler == "return")
			var sorting_order = [7, 'asc'];

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

			if (handler == "return") {
				orders_handleApproval();
				return_handleClaims();
			}

			orders_handleBookmarks();
			comments_handleSidebar(type);
			comments_handleValidation(type);
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
						var shipmentId = $(this).data('shipmentid').toString();

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

	const refreshCount = async (handler) => {
		const data = await fetch("ajax_load.php?action=get_orders_count&handler=" + handler, {
			method: 'GET',
			headers: { 'Content-Type': 'application/json' }
		});
		const jsondata = await data.json();
		$.each(jsondata.orders, function (e, count) {
			$(".portlet_" + e + ".count").text("(" + count + ")");
		});
	};

	// ORDER UPDATES
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
				},
				error: function () {
					console.log('Error Processing your Request!!');
				}
			});
		});
	}

	function clear_model_container() {
		$(".list-container").hide();
		$('.scan-passed').html('0<span class="scan-passed-ok"><i class="icon icon-ok-sign" aria-hidden="true"></i></span>');
		$('.scan-failed-count').html('0<span class="cancel-icon"><i class="icon icon-remove-sign" aria-hidden="true"></i></span>');
		$(".list-container ul").html("");
	}

	function orders_handleApproval() {
		var forms = $('.selfship_order');
		$.each(forms, function (key, r_form) {
			var form = $(r_form);
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

					// ADD APPROVED TAG ON SHOPIFY (OPTIONAL)
					// UPLOAD ORDERS DATA TO SHIPROCKET
					// ASSIGN AWB
					// REQUEST LABEL
					window.setTimeout(function () {
						var s = submitForm(formData, "POST");
						if (s.type == 'success') {
							UIToastr.init(s.type, 'Order Update', s.message);
							$('#' + btn_id, form).html('<i class="fa fa-check-circle"></i>');
							$('#' + btn_id, form).closest('tr').remove();
							refreshCount(handler);
						} else if (s.type == 'error') {
							if (update_type == "delivered")
								$('#' + btn_id, form).html('Mark Delivered').attr('disabled', false);
							else
								$('#' + btn_id, form).html('Update to In-Transit').attr('disabled', false);
							UIToastr.init(s.type, 'Order Update', s.error.message);
						} else {
							if (update_type == "delivered")
								$('#' + btn_id, form).html('Mark Delivered').attr('disabled', false);
							else
								$('#' + btn_id, form).html('Update to In-Transit').attr('disabled', false);
							UIToastr.init('info', 'Order Update', 'Error Processing Request! Please try again later.');
						}
					}, 500);
				}
			});
		});
	}

	function orders_handleBookmarks() {
		// BOOKMARK
		$(".flag").off().on('click', function (e) {
			console.log('flag');
			e.preventDefault();
			var flag = $(this);
			var is_active = flag.hasClass('active');
			var orderItemId = flag.data('orderitemid');
			var currentReq = null;
			flag.prop('disabled', true);
			currentReq = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'POST',
				data: "action=set_flag&orderItemId=" + orderItemId + "&flag=" + !is_active,
				beforeSend: function () {
					if (currentRequest != null) {
						currentRequest.abort();
					} else {
						currentRequest = currentReq;
					}
				},
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == 'success') {
						if (!is_active) {
							flag.addClass('active');
						} else {
							flag.removeClass('active');
						}
					}
					flag.prop('disabled', false);
				},
				error: function () {
					flag.prop('disabled', false);
					alert('Error Processing your Request!!');
				}
			});
		});
	}

	function comments_handleInit(type) {
		const commentsFor = $('#commentsFor');
		var for_options = "<option></option>";
		$.each(shopify_tags[type], function (key, value) {
			for_options += "<option value=" + value.split(' ').join('_') + ">" + value + "</option>";
		});
		commentsFor.empty().append(for_options);
		commentsFor.select2();
		commentsFor.select2('val', '');

		if (type == "in_transit" || type == "ndr") {
			$('.comment_for').change(function () {
				if (this.value == "NDR_Approved") {
					$('.ndr_approval').removeClass('hide');
					$('.ndr_approved_reason').prop('required', true);

					$('.add-comment .form-action').addClass('col-md-12').removeClass('col-md-6');
					$('.add-comment .assured_delivery').addClass('hide');
					$('.add-comment [name="assured_delivery"]').attr('disabled', true);
				} else {
					$('.ndr_approval').addClass('hide');
					$('.ndr_approved_reason').prop('required', false);;

					$('.add-comment .form-action').removeClass('col-md-12').addClass('col-md-6');
					$('.add-comment .assured_delivery').removeClass('hide');
					$('.add-comment [name="assured_delivery"]').attr('disabled', false);
				}
			});
		} else {
			$('.add-comment .form-action').addClass('col-md-12').removeClass('col-md-6');
			$('.add-comment .assured_delivery').addClass('hide');
			$('.add-comment [name="assured_delivery"]').attr('disabled', true);
		}

		if (type == "in_transit") {
			$('.add-comment .form-action').removeClass('col-md-12').addClass('col-md-6');
			$('.add-comment .assured_delivery').removeClass('hide');
			$('.add-comment [name="assured_delivery"]').attr('disabled', false);
		}
	}

	function comments_handleValidation(type) {
		var form = $('.add-comment');
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
				var orderId = "",
					call_tag = "",
					comment_for = "",
					assured_tag = "";
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
					if (input.name == "orderId")
						orderId = input.value;
					if (input.name == "comment_for") {
						comment_for = input.value;
						if (comment_for.indexOf('Call') != -1) {
							call_tag = comment_for.replace(/_/g, ' ');
						}
					}
					if (input.name == "assured_delivery") {
						if (input.value == "1")
							assured_tag = 'Assured Delivery';
					}
				});

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Comment Updates', s.message);
						if (s.type == "success") {
							// RESET THE FORM
							$('[name="comment"]').val('');
							$('#commentsFor').select2('val', '');
							$('[name="assured_delivery"]').prop('checked', false);
							App.updateUniform($('[name="assured_delivery"]'));
							// $('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');

							// REMOVE THE ORDER ROW ON RTO APPROVAL 
							if (comment_for == "NDR_Approved") {
								$('.ndr_approval').addClass('hide');
								$('.select2').val("").trigger('change');
								$('.view_comments').modal('hide');
								$('.order-content-' + orderId).closest('tr').remove();
								refreshCount(handler);
								$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
							} else if (comment_for == "Confirmation_Call" || comment_for == "Auto_Confirmed") {
								var formData1 = new FormData();
								$.each(other_data, function (key, input) {
									formData1.append(input.name, input.value);
								});
								formData1.set("action", 'add_order_to_logistic_aggregator');
								formData1.delete('comment');
								formData1.delete('comment_for');

								// ADD APPROVED TAG ON SHOPIFY (OPTIONAL)
								// UPLOAD ORDERS DATA TO SHIPROCKET
								// ASSIGN AWB
								// REQUEST LABEL
								window.setTimeout(function () {
									var s = submitForm(formData1, "POST");
									if (s.type == 'success' && typeof (s.is_multiQuantity) === 'undefined') {
										UIToastr.init(s.type, 'Order Update', s.message);

										var formData2 = new FormData();
										$.each(other_data, function (key, input) {
											formData2.append(input.name, input.value);
										});
										formData2.set("action", 'mark_orders');
										formData2.set("type", 'approved');
										formData2.delete('comment');
										formData2.delete('comment_for');
										window.setTimeout(function () {
											var s = submitForm(formData2, "POST");
											UIToastr.init(s.type, 'Order Update', s.message);
											$('.view_comments').modal('hide');
											$('.order-content-' + orderId).closest('tr').remove();
											refreshCount(handler);
											$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
										}, 200);
									} else if (s.type == 'success' && s.is_multiQuantity) {
										UIToastr.init(s.type, 'Order Update', s.message);
										$('.view_comments').modal('hide');
										$('.order-content-' + orderId).closest('tr').remove();
										refreshCount(handler);
										$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
									} else if (s.type == 'info') {
										if (typeof (s.enableSelfShip) === 'undefined') {
											if (typeof (s.error) === 'undefined')
												UIToastr.init(s.type, 'Order Update', s.message);
											else
												UIToastr.init(s.type, 'Order Update', s.error.message);
											$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
										} else if (s.enableSelfShip) {
											// $('.approve-actions-'+id).addClass('hide');
											// $('.selfShip-actions-'+id).removeClass('hide');
											UIToastr.init(s.type, 'Order Update', s.message);
											var formData3 = new FormData();
											$.each(other_data, function (key, input) {
												formData3.append(input.name, input.value);
											});
											formData3.set("action", 'mark_orders');
											formData3.set("type", 'self_approved');
											formData3.delete('comment');
											formData3.delete('comment_for');
											window.setTimeout(function () {
												var s = submitForm(formData3, "POST");
												UIToastr.init(s.type, 'Order Update', s.message);
												$('.view_comments').modal('hide');
												$('.order-content-' + orderId).closest('tr').remove();
												refreshCount(handler);
												$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
											}, 200);
										}
									} else {
										if (typeof (s.error) != 'undefined') {
											if (typeof (s.error.message) != 'undefined') {
												console.log(s.error.message);
												UIToastr.init('info', 'Order Update', s.error.message);
											} else if (typeof (s.error.response.data) != 'undefined') {
												UIToastr.init('info', 'Order Update', s.error.response.data);
												if (s.error.response.data.includes('not servicable')) {
													formData.set("comment", s.error.response.data);
													formData.set("comment_for", "General_Comment");
													window.setTimeout(function () {
														var s = submitForm(formData, "POST");
														if (s.type == "success" || s.type == "error") {
															UIToastr.init(s.type, 'Comment Updates', s.message);
														}
													}, 100);
												}
											}
										}
										else if (typeof (s.message) != 'undefined')
											UIToastr.init('info', 'Order Update', s.message);
										else
											UIToastr.init('info', 'Order Update', 'Error Processing Request! Please try again later.');
										$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
									}
								}, 200);
							} else {
								// RELOAD COMMENTS SECTION
								comment_handleView(orderId, type);
								$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');

								// ADD CALL TAG TO THE ORDER
								if (call_tag != "") {
									if ($('.order-content-' + orderId + ' .ordered-product-name').find('span.badge-call-tag').length !== 0) {
										$('.order-content-' + orderId + ' .ordered-product-name .badge-call-tag').text(call_tag);
									} else {
										$('.order-content-' + orderId + ' .ordered-product-name').append('<span class="badge badge-default badge-call-tag">' + call_tag + ' </span>');
									}
								}

								// ADD ASSURED DELIVERY TAG TO THE ORDER
								if (assured_tag != "")
									$('.order-content-' + orderId + ' .ordered-product-name').append('<span class="badge badge-success">' + assured_tag + '</span>')
							}
						}
					} else {
						UIToastr.init('info', 'Comment Updates', 'Error Processing Request! Please try again later.');
						$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
					}
				}, 100);
			}
		});
	}

	function comment_handleView(orderId, type) {
		$('.comments-body').empty().html('<span class="comments-loading-block"><i class="fa fa-sync fa-spin"></i></span>');
		var formData = 'action=view_comments&orderId=' + orderId;
		window.setTimeout(function () {
			var s = submitForm(formData, "GET");
			var output = "";
			if (s.type == "success") {
				if (s.data != "") {
					$.each(s.data, function (k, v) {
						var assured_delivery = "";
						if (v.assured_delivery == 1) {
							assured_delivery = '<span class="badge badge-success text-right">Assured Delivery</span>';
						}
						output += '<div class="comment-block">' +
							'<a href="javascript:void(0)">' + v.created_by + '</a>' +
							'<em>' + v.comment_for + '</em>' +
							assured_delivery +
							'<p>' + v.comment + '</p>' +
							'<span>' + v.created_on + '</span>' +
							'</div>';
					});
					var checkbox = $('.add-comment [name="assured_delivery"]');
					if (type == "in_transit") {
						if (s.assuredDelivery) {
							checkbox.attr('disabled', true);
							App.updateUniform(checkbox);
						} else {
							checkbox.attr('disabled', false);;
							App.updateUniform(checkbox);
						}
					}
					$('.comments-body').empty().html(output);
				} else {
					$('.comments-body').empty().html('<span class="comments-loading-block">No Comments Available</span>');
					if (type == "in_transit") {
						var checkbox = $('.add-comment [name="assured_delivery"]');
						checkbox.attr('disabled', false);;
						App.updateUniform(checkbox);
					}
				}
			}
		}, 500);

		// $('.customer-body').empty().html('<span class="customer-loading-block"><i class="fa fa-sync fa-spin"></i></span>');
		// var formData2 = 'action=get_customer_orders&orderId='+orderId;
		// window.setTimeout(function(){
		// 	var s = submitForm(formData2, "GET");
		// 	var output = "";
		// 	if (s.type == "success"){
		// 		if (s.data != ""){
		// 			$.each(s.data, function(k, v){
		// 				var replace = "";
		// 				if (v.r_returnType == "replace")
		// 					replace = '<span class="badge badge-success">R</span>';

		// 				output += '<tr><td><a href="//'+v.account_store_url+'/admin/orders/'+v.orderId+'" target="_blank">'+v.orderNumberFormated+'</a>'+replace+'</td><td>'+v.status+'</td><td>'+v.shipmentStatus+'</td><td>'+v.r_shipmentStatus+'</td></tr>';
		// 			});
		// 			var table = '<table class="table"><tr><th>Order#</th><th>Order</th><th>Shipment</th><th>Return</th></tr>'+output+'</table>';
		// 			$('.customer-body').empty().html(table);
		// 		} else {
		// 			$('.customer-body').empty().html('<span class="customer-loading-block">Hurray!! Its the first order.</span>');
		// 		}
		// 	}
		// }, 500);
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

	function comments_handleScroll() {
		if ($('.modal').hasClass('in')) {
			$('body').addClass('modal-open-noscroll');
		} else {
			$('body').removeClass('modal-open-noscroll');
		}
	}

	function address_handleView(orderId, accountId) {
		$('.zip').off().on('change', function () {
			var $this = $(this);
			$this.prop('disabled', true);
			if ($this.val().length == 6) {
				$this.addClass('spinner');
				var formData = 'action=get_pincode_details&pincode=' + $this.val() + '&accountId=' + accountId;
				window.setTimeout(function () {
					var s = submitForm(formData, "GET");
					if (s.type == "success") {
						$('.city').val(s.data.city);
						$('.province').val(s.data.state);
					}
					$this.removeClass('spinner');
					$this.prop('disabled', false);
				}, 100);
			}
			return;
		});

		$(".zip").inputmask({
			"mask": "999999",
			"greedy": false
		});

		var formData = 'action=get_address&orderId=' + orderId;
		window.setTimeout(function () {
			var s = submitForm(formData, "GET");
			var output = "";
			$('.address-loading-block').addClass('hide');
			$('.update-address').removeClass('hide');
			if (s.type == "success" && s.data != "") {
				$('.address1').val(s.data.address1);
				$('.address2').val(s.data.address2);
				$('.city').val(s.data.city);
				$('.province').val(s.data.province);
				$('.zip').val(s.data.zip);
			}
		}, 500);

		address_handleValidation();
	}

	function address_handleValidation() {
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

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						window.setTimeout(function () {
							UIToastr.init(s.type, 'Address Updates', s.message);
							if (s.type == "success") {
								$('.address-loading-block').addClass('hide');
								$('.update-address').removeClass('hide');
								$('.update_address').modal('hide');
								viewOrder_handeleTable("new", "#portlet_start");
							}
							$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
						}, 1000);
					} else {
						$('.btn-success', form).attr('disabled', false).find('i').removeClass('fa-sync fa-spin');
						UIToastr.init('info', 'Address Updates', 'Error Processing Request! Please try again later.');
					}
				}, 100);
			}
		});
	}

	// SCAN SHIP
	function scanShip_handleInit() {
		var ship_track = {},
			success_ship = {},
			audio = document.getElementById('chatAudio'),
			orderId = "",
			lpProvider = "",
			accountId = "",
			fulfillable_quantity = 0;
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
				if (s.type == "success") {
					$('.product_details .item_groups').html(s.content);
					$('.product_details').removeClass('hide');
					fulfillable_quantity = parseInt(s.fulfillable_quantity);
					orderId = s.orderId;
					accountId = s.accountId;
					lpProvider = s.lpProvider;
					ship_track = s.items;
					success_ship = {};
					$('#tracking_id').removeClass('spinner');
					$('#uid').focus();

					$('#sidelineProduct').prop("disabled", false);
					buttonClicked = false;

				} else {
					UIToastr.init(s.type, 'Scan Pack Ship', s.message);
					$('.product_details').addClass('hide');
					$('#tracking_id').removeClass('spinner').attr('disabled', false).val("").focus();
					audio.play();
				}
			}, 10);
		});

		$('#sidelineProduct').click(function () {
			$("#sidelineProduct i").addClass("fa-spin fa-spinner");
			buttonClicked = true;
			endProcess(orderId);
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
					$.each(ship_track, function (k, item) {
						console.log(item);
						if (item.item_id === s.item_id) {
							if (item.scan)
								return;

							const v = item;
							if (v.scan) {
								return false;
							}

							if (v.item_id == s.item_id && v.scan) {
								UIToastr.init('info', 'Scan Pack Ship', 'All items of this SKU already packed');
								$('#uid').attr('disabled', false).val("").focus();
								audio.play();
								return false;
							}

							if (v.item_id == s.item_id && v.scanned_qty != v.quantity && !v.scan) {
								if (!Array.isArray(success_ship[v.orderItemId])) {
									success_ship[v.orderItemId] = new Array();
								}
								$('.item_' + v.item_id + ' .uids').removeClass('hide');
								success_ship[v.orderItemId].push(uid);
								item.uids.push(uid);
								item.scanned_qty += 1;
								$('.item_' + v.item_id + ' .uids .uid').text(item.uids.toString().replace(/,/g, ", "));

								if (item.scanned_qty == item.quantity) {
									item.scan = true;
									$('.item_' + v.item_id + ' .scanned').removeClass('hide');
								}
								fulfillable_quantity = fulfillable_quantity - 1;
								if (fulfillable_quantity > 0) {
									$('#uid').attr('disabled', false).val("").focus();
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
					console.log(success_ship);
					// const pending_scan = ship_track.some(el => el.scan === false);
					if (fulfillable_quantity === 0) {
						var formData = new FormData();
						formData.append('action', 'save_scan_ship');
						formData.append('scanned_items', JSON.stringify(success_ship));
						formData.append('orderId', orderId);
						window.setTimeout(function () {
							var s = submitForm(formData, "POST");
							if (s.type == "success" || s.type == "error") {
								if (s.type == "success") {
									UIToastr.init(s.type, 'Scan Pack Ship', s.message);
									$('.product_details .item_groups').html("");
									$('.product_details').addClass('hide');
									$('#tracking_id').val("").focus();
									// schedule_pickup
									if (lpProvider == "ShipRocket") {
										var formData2 = new FormData();
										formData2.append('action', 'schedule_pickup');
										formData2.append('type', 'single');
										// formData2.append('lpProvider', lpProvider);
										formData2.append('shipmentIds', s.shipmentId);
										formData2.append('accountId', accountId);
										NProgress.configure({ trickle: false });
										window.setTimeout(function () {
											var s = submitForm(formData2, 'POST');
											NProgress.done(true);
										}, 10);
									}
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
					UIToastr.init(s.type, 'Scan Pack Ship', s.message);
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
			window.setTimeout(function () {
				var s = submitForm(formdata, "GET");
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
			}, 10);
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
				logistic_provider: {
					required: true,
				},
			},

			messages: { // custom messages for radio buttons and checkboxes
				returns_csv: {
					required: "Please select a valid CSV File"
				},
				logistic_provider: {
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
				formData.append('logistic_provider', $('.logistic_provider option:selected', form).val());

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						if (s.error == "") {
							$('#return-import').modal('hide');
							UIToastr.init(s.type, 'Returns Import', "Successfull imported returns");
						} else {
							UIToastr.init(s.type, 'Returns Import', "Successfull imported returns with few errors");
							error.html("Successfull added: " + s.insert + "<br /> Update: " + s.update + " <br /> Existing: " + s.existing + " <br />Error: " + s.error).show();
						}
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

		$('.claimId').inputmask({ "mask": "9", "repeat": 10 });

		var forms = $('.update-claim-status');
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
						var s = submitForm(formData, "POST");
						if (s.type == 'success') {
							UIToastr.init(s.type, 'Claim Status Update', s.message);
							$('#' + btn_id, form).html('<i class="fa fa-check-circle success"></i>');
							var ele_id = btn_id.replace('claim_approved_submit_', '');
							$('#claim_status_' + ele_id).text('Approved').removeClass('claim_staus label-alert');
							$('#claim_status_' + ele_id).parent().append("&nbsp;<a href='https://app.shiprocket.in/passbook?page=1&per_page=15&from=&to=&awb_code=" + s.trackingId + "&category=&type=' target='_blank'><i class='fa fa-book' style='line-height: 7px;'></i></a>");
							$('#update_claim_status_button_' + ele_id).remove();
						} else if (s.type == 'error') {
							$('#' + btn_id, form).html('Approved!').attr('disabled', false);
							UIToastr.init(s.type, 'Claim Status Update', s.message);
						} else {
							$('#' + btn_id, form).html('Approved!').attr('disabled', false);
							UIToastr.init('info', 'Claim Status Update', 'Error Processing Request! Please try again later.');
						}
					}, 500);
				}
			});
		});
	}

	// ORDER SEARCH
	function order_search() {
		$('.search_orders').off().on('shown.bs.modal', function (e) {
			comments_handleScroll();
		});
		$('.search_orders').off().on('hidden.bs.modal', function (e) {
			comments_handleScroll();
		});

		var form = $('#search_orders');

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit
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
				$('#search_order_key', form).attr('readonly', true);
				$('.modal-body-loading').removeClass('hide');
				$(".modal-body-details").hide();

				var formData = $.param($(form).serializeArray());

				window.setTimeout(function () {
					var s = submitForm(formData, "GET");
					if (s.type == "success" || s.type == "error") {
						if (s.type == "error") {
							UIToastr.init(s.type, 'Order Search', s.message);
							$('.modal-body-loading').addClass('hide');
						} else {
							$(".modal-body-details").html(s.content);
							$(".modal-body-loading").addClass('hide');
							$(".modal-body-details").show();
							orders_handleBookmarks();
							comments_handleInit(s.orderType);
							comments_handleSidebar(s.orderType);
							comments_handleValidation(s.orderType);
							comments_handleScroll();
						}
					} else {
						UIToastr.init('error', 'Order Search', 'Error Processing Request! Please try again later.');
						$('.modal-body-loading').addClass('hide');
					}
					$('#search_order_key', form).attr('readonly', false);
				}, 100);
			}
		});
	}

	// OVERVIEW
	function dashboardDaterange() {
		$('#dashboard-report-range').daterangepicker({
			autoApply: true,
			opens: (App.isRTL() ? 'right' : 'left'),
			minDate: moment().subtract(365, 'days'),
			maxDate: moment(),
			dateLimit: {
				months: 4
			},
			ranges: {
				'Last 7 Days': [moment().subtract(7, 'days'), moment()],
				'Last 15 Days': [moment().subtract(15, 'days'), moment()],
				'This Week': [moment().startOf('week'), moment()],
				'This Month': [moment().startOf('month'), moment()],
				'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
			},
		},

			function (start, end) {
				console.log("Date change Callback initiated!");
				$('#dashboard-report-range span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
				start_date = start.format('YYYY-MM-DD');
				end_date = end.format('YYYY-MM-DD');

				$('#orders_statistics_loading').show();
				$('#orders_statistics_content').hide();
				$('#regions_statistics_loading').show();
				$('#regions_statistics_content').hide();
				$('#sales_statistics_loading').show();
				$('#sales_statistics_content').hide();
				$('#returns_statistics_loading').show();
				$('#returns_statistics_content').hide();

				$('.stats-overview .numbers').text("");
				$('.stats-overview .percent').text("");
				$('.stats-overview .progress .progress-bar').css('width', "0");

				// ON CHANGE
				dashboardStatisticInit();
			});


		start_date = moment().subtract(1, 'month').format('YYYY-MM-DD');
		end_date = moment().format('YYYY-MM-DD');
		$('#dashboard-report-range span').html(moment().subtract(1, 'month').format('MMMM D, YYYY') + ' - ' + moment().format('MMMM D, YYYY'));
		$('#dashboard-report-range').show();

		dashboardStatisticInit();
	}

	function dashboardStatisticInit() {
		dashboardOrderStatistic();
		dashboardShipmentStatistic();
		dashboadrGenerateOverviewReport();
	}

	function dashboadrGenerateOverviewReport() {
		$('.generate_overview_report').off().on('click', function () {
			var status = $(this).data('status');
			var formData = "ajax_load.php?action=export_orders&order_type=" + status + "&start_date=" + start_date + "&end_date=" + end_date;
			window.setTimeout(function () {
				// var s = submitForm(formData, 'GET');
				window.open(formData);
			}, 10);
		});
	}

	function dashboardOrderStatistic() {
		console.log('dashboardOrderStatistic');

		var formData = 'action=overview&type=order_statistic&start_date=' + start_date + '&end_date=' + end_date;
		window.setTimeout(function () {
			var s = submitForm(formData, "GET");
			$.each(s, function (k, v) {
				$('.order_mx .' + k + ' .numbers').text(v.count);
				$('.order_mx .' + k + ' .percent').text(v.percentage + '%');
				$('.order_mx .' + k + ' .progress .progress-bar').css('width', v.percentage + "%");
			});
		}, 600);
	}

	function dashboardShipmentStatistic() {
		var formData = 'action=overview&type=shipment_statistic&start_date=' + start_date + '&end_date=' + end_date;
		window.setTimeout(function () {
			var s = submitForm(formData, "GET");
			$.each(s, function (k, v) {
				$('.shipment_mx .' + k + ' .numbers').text(v.count);
				$('.shipment_mx .' + k + ' .percent').text(v.percentage + '%');
				$('.shipment_mx .' + k + ' .progress .progress-bar').css('width', v.percentage + "%");
			});
		}, 600);
	}

	function payments_handleInit() {
		var a_options = "<option value=''>Please select shopify account</option>";
		var accountMenu = $('.shopify_account');
		// Append Marketplace and Account details 
		$.each(accounts, function (account_k, account) {
			if (account_k == 'shopify') {
				$.each(account, function (k, v) {
					a_options += "<option value=" + v.account_id + ">" + v.account_name + "</option>";
				});
			}
		});
		accountMenu.empty().append(a_options);
		if (jQuery().select2) {
			$('select.select2').select2('destroy').select2({
				placeholder: "Select",
				allowClear: true
			});
		}

		function initTable_payments() {
			if (!jQuery().dataTable) {
				return;
			}

			// loadAjaxData('settlements');
			search_payments();

			// Order Status Tabs
			$(".settlement_type a").click(function () {
				if ($(this).parent().attr('class') == "active") {
					return;
				}
				var tab = $(this).attr('href');
				var tab_type = tab.substr(tab.indexOf("_") + 1);
				if (tab_type == "search_payment") {
					search_payments();
				} else {
					// loadAjaxData($type);
					payments_handleTable(tab_type);
				}
			});
		}

		function payments_handleTable(tab_type, $query = "") {
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

			var statusFilters, tableFormat = {};
			switch (tab_type) {
				case 'settlements':
					tableFormat = {
						'columns': [
							{
								title: "Settlement ID",
								columnFilter: 'inputFilter'
							}, {
								title: "Settlement Date",
								columnFilter: 'dateFilter'
								// },{
								// 	title: "Account",
								// 	columnFilter: 'selectFilter'
							}, {
								title: "Settlement Amount",
								columnFilter: 'rangeFilter'
							}
						],
						'order': [1, 'desc'],
						'columnDefs': [
							{
								targets: -1,
								render: function (a) {
									return a;
								}
							}
						]
					};
					break;

				case 'disputed':
					tableFormat = {
						'columns': [
							{
								title: "Account Name",
								columnFilter: 'selectFilter'
							}, {
								title: "Order Item ID",
								columnFilter: 'inputFilter'
							}, {
								title: "Order Id",
								columnFilter: 'inputFilter'
							}, {
								title: "Due Date",
								className: "due_date",
								columnFilter: 'dateFilter'
							}, {
								title: "Expected Payout",
								columnFilter: 'rangeFilter'
							}, {
								title: "Amount Settled",
								columnFilter: 'rangeFilter'
							}, {
								title: "Difference",
								columnFilter: 'rangeFilter'
							}, {
								title: "Tags",
								className: "td-x-scroll",
								columnFilter: 'multiSelectFilter'
							}, {
								title: "Actions",
								className: "return_hide_column",
								columnFilter: 'actionFilter'
							}, {
								className: "return_hide_column"
							}
						],
						'order': [9, 'asc'],
						'columnDefs': [
							{
								targets: 3,
								render: function (a, t, e, s) {
									if (tab_type == "disputed")
										return (new Date(a).getTime() <= new Date().getTime()) ? a + ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>' : a;
									else
										return a;
								}
							}, {
								targets: 5,
								type: "date",
								render: function (a, t, e, s) {
									if (tab_type != "disputed" && tab_type != "settlements")
										return (new Date(a).getTime() <= new Date().getTime()) ? a + ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>' : a;
									else
										return a;
								}
							}, {
								targets: [6, 8],
								render: function (a, t, e, s) {
									if (tab_type == "settlements") {
										return;
									}
									if ((tab_type == "disputed" && s.col == 6) || (tab_type != "settlements" && s.col == 8))
										return (a > 0) ? '<span class="label label-sm label-success">' + a + ' </span>' : '<span class="label label-sm label-warning">' + a + ' </span>';
									else
										return a;
								}
							}, {
								targets: -2,
								render: function (a, t, e, s) {
									if (tab_type == "disputed") {
										var tags = "";
										$.each(a, function (k, v) {
											tags += '<span class="label label-default label-tags">' + v + '</span> ';
										});
										return tags;
									}
								}
							}
						]
					};
					break;

				default:
					tableFormat = {
						'columns': [
							{
								title: "Account Name",
								columnFilter: 'selectFilter'
							}, {
								title: "Order Item ID",
								columnFilter: 'inputFilter'
							}, {
								title: "Order ID",
								columnFilter: 'inputFilter'
							}, {
								title: "Order Date",
								columnFilter: 'dateFilter'
							}, {
								title: "Shipped Date",
								columnFilter: 'dateFilter'
							}, {
								title: "Due Date",
								columnFilter: 'dateFilter'
							}, {
								title: "Expected Payout",
								columnFilter: 'rangeFilter'
							}, {
								title: "Amount Settled",
								columnFilter: 'rangeFilter'
							}, {
								title: "Difference",
								columnFilter: 'rangeFilter'
							}, {
								title: "Actions",
								className: "return_hide_column",
								columnFilter: 'actionFilter'
							}, {
								className: "return_hide_column",
							}
						],
						'order': [10, 'asc'],
						'columnDefs': [
							{
								targets: 3,
								render: function (a, t, e, s) {
									if (tab_type == "disputed")
										return (new Date(a).getTime() <= new Date().getTime()) ? a + ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>' : a;
									else
										return a;
								}
							}, {
								targets: 5,
								type: "date",
								render: function (a, t, e, s) {
									if (tab_type != "disputed" && tab_type != "settlements")
										return (new Date(a).getTime() <= new Date().getTime()) ? a + ' <div style="font-size: 0.5rem; float: right;"><i style="color: #ff0000;" class="fa fa-fw fa-exclamation-circle fa-xs"></i></div>' : a;
									else
										return a;
								}
							}, {
								targets: [6, 8],
								render: function (a, t, e, s) {
									if (tab_type == "settlements") {
										return;
									}
									if ((tab_type == "disputed" && s.col == 6) || (tab_type != "settlements" && s.col == 8))
										return (a > 0) ? '<span class="label label-sm label-success">' + a + ' </span>' : '<span class="label label-sm label-success">' + a + ' </span>';
									else
										return a;
								}
							}, {
								targets: -2,
								render: function (a, t, e, s) {
									if (tab_type == "disputed") {
										var tags = "";
										$.each(a, function (k, v) {
											tags += '<span class="label label-default label-tags">' + v + '</span> ';
										});
										return tags;
									} else {
										return a < 0 ? '<span class="label label-sm label-warning">' + a + '</span>' : '<span class="label label-sm label-success">' + a + '</span>';
									}
								}
							}
						]
					};
					break;
			}

			var table = $('#payment_' + tab_type);
			table.empty();
			var oTable = table.DataTable({
				responsive: true,
				dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12 table-filter'><'col-sm-12' <'table-scrollable table-payments' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
				lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
				pageLength: 20,
				debug: true,
				language: {
					lengthMenu: "Display _MENU_"
				},
				searchDelay: 500,
				processing: !0,
				// serverSide: !0,
				destroy: true,
				fixedHeader: {
					"headerOffset": 40
				},
				ajax: {
					url: "ajax_load.php?action=get_all_" + tab_type + $query + "&token=" + new Date().getTime(),
					cache: false,
					type: "GET",
				},
				columns: tableFormat['columns'],
				order: [
					tableFormat['order'],
				],
				columnDefs: tableFormat['columnDefs'],
				createdRow: function (row, data, dataIndex) {
					if (tab_type == 'unsettled' || tab_type == 'disputed' || tab_type == 'upcoming' || tab_type == 'search_payments' || tab_type == 'to_claim') {
						$(row).attr('id', data[1]).addClass('clickable');
						if (tab_type == "disputed") {
							// $(row).find('td:eq(7)').addClass('td-x-scroll');
							// $(row).find('td:eq(8)').addClass('return_hide_column');
						}
						// $( row ).find('td:eq(9)').addClass('return_hide_column');
					}
				},
				"fnDrawCallback": function () {
					loadPaymentsDetails();
				},
				initComplete: function () {
					loadFilters(this),
						afterInitDataTable();
				}
			});

			function loadFilters(t) {
				var parseDateValue = function (rawDate) {
					var d = moment(rawDate).format('YYYY-MM-DD');
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

				var isMultiSelectFilter = function (e) {
					if (typeof (oTable.settings()[0].aoColumns[e]) !== "undefined")
						if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "multiSelectFilter")
							return true;

					return false;
				};

				// FILTERING
				var f = $('<ul class="filter"></ul>').appendTo('.table-filter');
				var tags = [];
				t.api().columns().every(function () {
					var s;
					switch (this.getColumnFilter()) {
						case 'inputFilter':
							s = $('<input type="text" class="form-control form-control-sm form-filter filter-input input-medium margin-bottom-5" placeholder="' + this.title() + '" data-col-index="' + this.index() + '"/>');
							break;

						case 'rangeFilter':
							s = $('<div class="input-range input-medium margin-bottom-5"><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="' + this.title() + ' From" data-col-index="' + this.index() + '"/><input type="text" class="form-control form-control-sm form-filter filter-input range-filter" placeholder="' + this.title() + ' To" data-col-index="' + this.index() + '"/></div>');
							break;

						case 'dateFilter':
							s = $('<div class="input-daterange input-medium"><div class="input-group input-group-sm date date-range-picker margin-bottom-5"><input type="text" class="form-control form-filter date-filter filter-input" readonly placeholder="' + this.title() + '" data-col-index="' + this.index() + '" /><span class="input-group-btn"><button class="btn btn-default" type="button"><i class="fa fa-calendar"></i></button></span></div></div>');
							break;

						case 'selectFilter':
							s = $('<select class="form-control form-control-sm form-filter filter-input select2 margin-bottom-5" data-placeholder="' + this.title() + '" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, f) {
								$(s).append('<option value="' + t + '">' + t + "</option>");
							});
							break;

						case 'multiSelectFilter':
							s = $('<select class="form-control form-control-sm form-filter filter-input select2 margin-bottom-5" title="Select" multiple="multiple" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, f) {
								$(t).each(function (k, tag) {
									if (tags.indexOf(tag) === -1) {
										tags.push(tag);
										$(s).append('<option value="' + tag + '">' + tag + "</option>");
									}
								});
							});
							break;

						case 'statusFilter':
							s = $('<select class="form-control form-control-sm form-filter filter-input select2  margin-bottom-5" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function (t, a) {
								$(s).append('<option value="' + t + '">' + statusFilters[t].title + "</option>")
							});
							break;

						case 'actionFilter':
							var i = $('<button class="btn btn-sm btn-warning filter-submit margin-bottom-5" title="Search"><i class="fa fa-search"></i></button>'),
								r = $('<button class="btn btn-sm btn-danger filter-cancel margin-bottom-5" title="Reset"><i class="fa fa-times"></i></button>');
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
										} else if (isMultiSelectFilter(e)) { // MULTI SELECT FILTER
											if (a == "")
												return;
											oTable.column(e).search(a.join('|') || + "$", 1, !1, !1, !1);
										} else { // DEFAULT FILTER
											oTable.column(e).search(a || "", !1, !1);
										}
									}),
									oTable.table().draw(),
									afterInitDataTable();
							}),
								$(r).on("click", function (ev) {
									ev.preventDefault(), $(f).find(".filter-input").each(function () {
										$(this).val(""), oTable.column($(this).data("col-index")).search("", !1, !1)
										$('.select2').val(null).trigger('change');
									}),
										oTable.table().draw(),
										afterInitDataTable();
								});
							break;

						default:
							s = "";
							break;
					}
					"" !== this.title() && $(s).appendTo($("<li>").appendTo(f));
				});
				var n = function () {
					t.api().columns().every(function () {
						this.visible() ? $(f).find("li").eq(this.index()).show() : $(f).find("li").eq(this.index()).hide();
					});
				};
				n(), window.onresize = n;

				// DEFAULT FUNCTIONS
				if (jQuery().daterangepicker) {
					var start = moment().subtract(3, 'years');
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
					$('select.select2, .dataTables_length select').select2({
						placeholder: "Select",
						allowClear: true,
						debug: true
					});
				}

				// FILTER TOGGLE
				$('.btn-advance').html('<button class="btn btn-default"><i class="fa fa-filter"></i></button>');
				$('.filter').hide();
				$('.btn-advance').bind('click', function () {
					$('.filter').slideToggle();
				});
			}

			function afterInitDataTable() {
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

			function loadPaymentsDetails() {
				// Array to track the ids of the details displayed rows
				var detailRows = [];
				$('#payment_' + tab_type + ' tbody').off('click', 'tr.clickable').on('click', 'tr.clickable', function () {
					var tr = $(this);
					var row = oTable.row(tr);
					var idx = $.inArray(tr.attr('id'), detailRows);

					if (row.child.isShown()) {
						tr.removeClass('parent');
						row.child.hide('slow');

						// Remove from the 'open' array
						detailRows.splice(idx, 1);
					} else {
						tr.addClass('parent');
						row.child('<center><i class="fa fa-sync fa-spin"></i></center>').show('slow');
						window.setTimeout(function () {
							var content = getTransactionHistory(tr.attr('id'), tr.find('td:last').prev().text(), tab_type);
							row.child(content);
							App.scrollTo(tr, -83);
							App.initTooltips();

							// /*// Add to the 'open' array
							if (idx === -1) {
								detailRows.push(tr.attr('id'));
							}

							$(".update_difference").off('click').on('click', function () {
								var tr = $('tr#' + $(this).attr('data-itemid'));
								row = oTable.row(tr);
								row.child('<center><i class="fa fa-sync fa-spin"></i></center>').show('slow');
								var el = $(this);
								window.setTimeout(function () {
									updateDifference(el);
									var details = getTransactionDetails(tr.attr('id'), tr.find('td:last').prev().text());
									if (tab_type == "disputed") {
										tr.find('td:eq(4)').html(details['netPayout']);
										tr.find('td:eq(5)').html(details['netSettlement']);
										tr.find('td:eq(6)').html(details['difference']);
									} else {
										tr.find('td:eq(6)').html(details['netPayout']);
										tr.find('td:eq(7)').html(details['netSettlement']);
										tr.find('td:eq(8)').html(details['difference']);
									}
								}, 10);

								// Refetch
								tr.removeClass('parent');
								row.child.hide('slow');

								// Remove from the 'open' array
								detailRows.splice(tr.attr('id'), 1);
								tr.trigger("click");// Open
							});

							$(".reload").off('click').on('click', function () {
								tr = $('tr#' + $(this).attr('data-itemid'));
								row = oTable.row(tr);
								row.child('<center><i class="fa fa-sync fa-spin"></i></center>').show('slow');
								window.setTimeout(function () {
									getPaymentsTransactionDetails(tr.attr('id'), tr.find('td:last').prev().text());
									var details = getTransactionDetails(tr.attr('id'), tr.find('td:last').prev().text());
									if (tab_type == "disputed") {
										tr.find('td:eq(4)').html(details['netPayout']);
										tr.find('td:eq(5)').html(details['netSettlement']);
										tr.find('td:eq(6)').html(details['difference']);
									} else {
										tr.find('td:eq(6)').html(details['netPayout']);
										tr.find('td:eq(7)').html(details['netSettlement']);
										tr.find('td:eq(8)').html(details['difference']);
									}
								}, 10);

								// // Refetch
								tr.removeClass('parent');
								row.child.hide('slow');

								// // Remove from the 'open' array
								detailRows.splice(tr.attr('id'), 1);
								tr.trigger("click");// Open
							});

							$(".refresh_payout").off('click').on('click', function () {
								tr = $('tr#' + $(this).attr('data-itemid'));
								row = oTable.row(tr);
								row.child('<center><i class="fa fa-sync fa-spin"></i></center>').show('slow');
								window.setTimeout(function () {
									refreshPayout(tr.attr('id'), tr.find('td:last').prev().text());
									var details = getTransactionDetails(tr.attr('id'), tr.find('td:last').prev().text());
									if (tab_type == "disputed") {
										tr.find('td:eq(4)').html(details['netPayout']);
										tr.find('td:eq(5)').html(details['netSettlement']);
										tr.find('td:eq(6)').html(details['difference']);
									} else {
										tr.find('td:eq(6)').html(details['netPayout']);
										tr.find('td:eq(7)').html(details['netSettlement']);
										tr.find('td:eq(8)').html(details['difference']);
									}
								}, 10);

								// Refetch
								tr.removeClass('parent');
								row.child.hide('slow');

								// Remove from the 'open' array
								detailRows.splice(tr.attr('id'), 1);
								tr.trigger("click");// Open
							});

							$(".refetch_billing").off('click').on('click', function () {
								tr = $('tr#' + $(this).attr('data-itemid'));
								row = oTable.row(tr);
								row.child('<center><i class="fa fa-sync fa-spin"></i></center>').show('slow');
								window.setTimeout(function () {
									refetchBillingDetails(tr.attr('id'), tr.find('td:last').prev().text());
									var details = getTransactionDetails(tr.attr('id'), tr.find('td:last').prev().text());

									if (tab_type == "disputed") {
										tr.find('td:eq(4)').html(details['netPayout']);
										tr.find('td:eq(5)').html(details['netSettlement']);
										tr.find('td:eq(6)').html(details['difference']);
									} else {
										tr.find('td:eq(6)').html(details['netPayout']);
										tr.find('td:eq(7)').html(details['netSettlement']);
										tr.find('td:eq(8)').html(details['difference']);
									}
								}, 10);

								// Refetch
								tr.removeClass('parent');
								row.child.hide('slow');

								// Remove from the 'open' array
								detailRows.splice(tr.attr('id'), 1);
								tr.trigger("click");// Open
							});

							$(".mark_settled").off('click').on('click', function () {
								if (confirm("Order will be marked settled. \nContinue?")) {
									window.setTimeout(function () {
										var details = markOrderSettled(tr.attr('id'), tr.find('td:last').prev().text());
										if (details == 'success') {
											if (tab_type != 'search_payments') {
												tr.removeClass('parent');
												row.child.hide('slow');

												// Remove from the 'open' array
												detailRows.splice(tr.attr('id'), 1);
												tr.remove();
												row.remove();
											}
											UIToastr.init('success', 'Order Settlement Status', 'Order successfully marked settled.');
										}
									}, 10);
								}
							});

							$(".update_notes").off('click').on('click', function (e) {
								e.preventDefault();

								var form = $(this).closest('form');
								var orderItemId = $(form).find("input[name='orderItemId']").val();
								var incidentId = $(form).find("input[name='incidentId']").val();
								var account_id = $(form).find("input[name='account_id']").val();
								var notes = $(form).find("input[name='settlementNotes']").val().toUpperCase();

								$(form).find("input[name='settlementNotes']").closest('.form-group').removeClass('has-error');
								$(this).attr('disabled', true);
								window.setTimeout(function () {
									if (update_notes(orderItemId, notes, incidentId, account_id)) {
										$(form).find("input[name='incidentId']").attr('disabled', true);
									}
								}, 10);
								$(form).find("input[name='settlementNotes']").val(notes);
								$(form).find("input[name='incidentId']").val(incidentId);
							});

							$(".shopsy_order").off('click').on('click', function () {
								tr = $('tr#' + $(this).attr('data-itemid'));
								is_shopsy = $(this).attr('data-isshopsy');
								row = oTable.row(tr);
								row.child('<center><i class="fa fa-sync fa-spin"></i></center>').show('slow');
								window.setTimeout(function () {
									updateOrderMarketplace(tr.attr('id'), 'is_shopsy', is_shopsy);
									refreshPayout(tr.attr('id'), tr.find('td:last').prev().text());
									var details = getTransactionDetails(tr.attr('id'), tr.find('td:last').prev().text());
									if (tab_type == "disputed") {
										tr.find('td:eq(4)').html(details['netPayout']);
										tr.find('td:eq(5)').html(details['netSettlement']);
										tr.find('td:eq(6)').html(details['difference']);
									} else {
										tr.find('td:eq(6)').html(details['netPayout']);
										tr.find('td:eq(7)').html(details['netSettlement']);
										tr.find('td:eq(8)').html(details['difference']);
									}
								}, 10);

								// Refetch
								tr.removeClass('parent');
								row.child.hide('slow');

								// Remove from the 'open' array
								detailRows.splice(tr.attr('id'), 1);
								tr.trigger("click");// Open
							});
							$(".marketplace-fee-row").off('click').on('click', function () {
								$(".marketplace-fee-row i").toggleClass("fa-chevron-up");
								$(".marketplace-fee-child").toggle();
							});

							$(".pg-row").off('click').on('click', function () {
								$(".pg-row i").toggleClass("fa-chevron-up");
								$(".pg-child").toggle();
							});

							$(".waiver-fee-row").off('click').on('click', function () {
								$(".waiver-fee-row i").toggleClass("fa-chevron-up");
								$(".waiver-fee-child").toggle();
							});

							$(".taxes-row").off('click').on('click', function () {
								$(".taxes-row i").toggleClass("fa-chevron-up");
								$(".taxes-child").toggle();
							});

							$("#settlementNotes").select2({
								tags: payments_issues,
							});
						}, 10);
					}
				});
			}
		}

		function getTransactionDetails(orderItemId, account_id) {
			var $return = "";
			var formData = "action=get_transaction_details&orderItemId=" + orderItemId + "&account_id=" + account_id;
			var s = submitForm(formData, 'GET');
			if (s.type == "success") {
				$return = s.data[orderItemId];
			} else {
				UIToastr.init('error', 'Transaction Details', 'Error fetching transaction details!! Please retry later.');
			}
			return $return;
		}

		function getPaymentsTransactionDetails(orderItemId, account_id) {
			var $return = "";
			var formData = "action=get_payment_transactions_details&orderItemId=" + orderItemId + "&account_id=" + account_id;
			var s = submitForm(formData, 'GET');
			if (s.type == "success") {
				$return = s.data[orderItemId];
			} else {
				UIToastr.init('error', 'Transaction Details', 'Error fetching transaction details!! Please retry later.');
			}
			return $return;
		}

		function getTransactionHistory(orderItemId, account_id, settlement_type) {
			var $return = "";
			var formData = "action=get_difference_details&orderItemId=" + orderItemId + "&account_id=" + account_id + "&type=" + settlement_type;
			var s = submitForm(formData, 'GET');
			if (s.type == "success") {
				$return = s.data;
			} else {
				UIToastr.init('error', 'Transaction History', 'Error fetching transaction history!! Please retry later.');
			}
			return $return;
		}

		function updateDifference(element) {
			var orderItemId = $(element).attr("data-itemId");
			var parent_tr = $(element).closest('tr').closest('tr');
			var account_id = $(element).attr("data-accountId");
			var key = $(element).attr("data-key");
			var value = $(element).attr("data-value");
			var formData = "action=update_settlement_difference&orderItemId=" + orderItemId + "&account_id=" + account_id + "&key=" + key + "&value=" + value;
			var s = submitForm(formData, 'POST');
			if (s.type == "success") {
				UIToastr.init(s.type, 'Update Difference', s.msg);
			} else {
				UIToastr.init('error', 'Update Difference', 'Error updating difference details!! Please retry later.');
			}
		}

		function updateOrderMarketplace(orderItemId, key, value) {
			var $return = "";
			var formData = "action=update_settlement_difference&orderItemId=" + orderItemId + "&key=" + key + "&value=" + value;
			var s = submitForm(formData, 'POST');
			if (s.type == "success") {
				$return = s.type;
			} else {
				UIToastr.init('error', 'Update Order Sales Channel', 'Error updating sales channel!! Please retry later.');
			}
			return $return;
		}

		function markOrderSettled(orderItemId, account_id) {
			var $return = "";
			var formData = "action=mark_order_settled&orderItemId=" + orderItemId + "&account_id=" + account_id
			var s = submitForm(formData, 'POST');
			if (s.type == "success") {
				$return = s.type;
			} else {
				UIToastr.init('error', 'Mark Order Settled', 'Error marking order settled!! Please retry later.');
			}
			return $return;
		}

		function refreshPayout(orderItemId, account_id) {
			var $return = "";
			var formData = "action=refresh_payout&orderItemId=" + orderItemId + "&account_id=" + account_id
			var s = submitForm(formData, 'GET');
			if (s.type == "success") {
				$return = s.type;
			} else {
				UIToastr.init('error', 'Refresh Payout Details', 'Error refreshing payout details!! Please retry later.');
			}
			return $return;
		}

		function refetchBillingDetails(orderItemId, account_id) {
			var $return = "";
			var formData = "action=refetch_billing_details&orderItemId=" + orderItemId + "&account_id=" + account_id
			var s = submitForm(formData, 'GET');
			if (s.type == "success") {
				UIToastr.init(s.type, 'Refresh Billing Details', s.msg);
				$return = s.type;
			} else {
				UIToastr.init('error', 'Refresh Billing Details', 'Error refreshing payout details!! Please retry later.');
			}
			return $return;
		}

		function update_notes(orderItemId, notes, incidentId, account_id) {
			var formData = "action=update_settlement_notes&orderItemId=" + orderItemId + "&settlementNotes=" + notes;
			if (typeof incidentId !== 'undefined' || $incidentId != "")
				formData += "&incidentId=" + incidentId + "&accountId=" + account_id;
			var s = submitForm(formData, 'POST');
			if (s.type == "success") {
				UIToastr.init(s.type, 'Update Notes', s.msg);
			} else {
				UIToastr.init('error', 'Update Notes', 'Error updating transaction notes!! Please retry later.');
			}
		}

		function order_import_payments_handleValidation() {
			var form1 = $('#payment-import');
			var error1 = $('.alert-danger', form1);
			var success1 = $('.alert-success', form1);

			form1.validate({
				errorElement: 'span', //default input error message container
				errorClass: 'help-block', // default input error message class
				focusInvalid: false, // do not focus the last invalid input
				ignore: "",
				rules: {
					orders_csv: {
						required: true,
					},
					account_id: {
						required: true,
					},
					shopify_account: {
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
					if (element.attr("name") == "orders_csv") {
						error.appendTo("#orders_csv_error");
					} else {
						error.appendTo(element.parent("div"));
					}
				},

				submitHandler: function (form) {
					error1.hide();

					$('.form-actions .btn-success', form1).attr('disabled', true);
					$('.form-actions i', form1).addClass('fa fa-sync fa-spin');
					var account_id = $('#account_id option:selected', form1).val();
					var shopify_account = $('#shopify_account option:selected', form1).val();

					var formData = new FormData();
					formData.append('action', 'import_payment');
					formData.append('orders_csv', $('#orders_csv')[0].files[0]);
					formData.append('account_id', account_id);
					formData.append('shopify_account', shopify_account);
					setTimeout(function () {
						$.ajax({
							url: "ajax_load.php?token=" + new Date().getTime(),
							cache: false,
							type: 'POST',
							data: formData,
							contentType: false,
							processData: false,
							mimeType: "multipart/form-data",
							async: false,
							success: function (s) {
								s = $.parseJSON(s);
								var string = s.total + " Total Orders. ";
								if (s.success != 0) {
									string += s.success + ' Orders Successfully added.';
								}
								if (s.existing != 0) {
									string += s.existing + ' Orders already exists.';
								}
								if (s.existing != 0) {
									string += s.updated + ' Orders Successfully updated.';
								}
								if (s.skipped != 0) {
									string += s.skipped + ' Orders skipped.';
								}
								success1.show().text(string);
								setTimeout(function () {
									$('#import_payment_sheet').modal('hide');
									// reset the form and alerts
									$('#account_id', form1).select2("val", "");
									$(form1)[0].reset();
									success1.hide().text("");
									error1.hide().text("");
								}, 2000);
								$('.form-actions .btn-success', form1).attr('disabled', false);
								$('.form-actions i', form1).removeClass('fa fa-sync fa-spin');
							},
							error: function () {
								// NProgress.done(true);
								UIToastr.init('error', 'Import Payments', 'Error importing payments details!! Please retry later.');
							}
						});
					}, 100);
				}
			});
		}

		function order_import_payments_charge_handleValidation() {
			var form1 = $('#payment-charge-import');
			var error1 = $('.alert-danger', form1);
			var success1 = $('.alert-success', form1);

			console.log(form1);
			form1.validate({
				errorElement: 'span', //default input error message container
				errorClass: 'help-block', // default input error message class
				focusInvalid: false, // do not focus the last invalid input
				ignore: "",
				rules: {
					charge_csv: {
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
					if (element.attr("name") == "charge_csv") {
						error.appendTo("#charge_csv_error");
					} else {
						error.appendTo(element.parent("div"));
					}
				},

				submitHandler: function (form) {
					error1.hide();

					$('.form-actions .btn-success', form1).attr('disabled', true);
					$('.form-actions i', form1).addClass('fa fa-sync fa-spin');

					var formData = new FormData();
					formData.append('action', 'import_payment_charge');
					formData.append('charge_csv', $('#charge_csv')[0].files[0]);

					setTimeout(function () {
						$.ajax({
							url: "ajax_load.php?token=" + new Date().getTime(),
							cache: false,
							type: 'POST',
							data: formData,
							contentType: false,
							processData: false,
							mimeType: "multipart/form-data",
							async: false,
							success: function (s) {
								s = $.parseJSON(s);
								var string = s.total + " Total Orders. ";
								if (s.success != 0) {
									string += s.success + ' Orders Successfully added.';
								}
								if (s.existing != 0) {
									string += s.existing + ' Orders already exists.';
								}
								if (s.existing != 0) {
									string += s.updated + ' Orders Successfully updated.';
								}
								if (s.skipped != 0) {
									string += s.skipped + ' Orders skipped.';
								}
								success1.show().text(string);
								setTimeout(function () {
									$('#import_payment_sheet').modal('hide');
									// reset the form and alerts
									// $('#account_id', form1).select2("val", "");
									$(form1)[0].reset();
									success1.hide().text("");
									error1.hide().text("");
								}, 2000);
								$('.form-actions .btn-success', form1).attr('disabled', false);
								$('.form-actions i', form1).removeClass('fa fa-sync fa-spin');
							},
							error: function () {
								// NProgress.done(true);
								UIToastr.init('error', 'Import Payments', 'Error importing payments details!! Please retry later.');
							}
						});
					}, 100);
				}
			});
		}

		function search_payments() {
			$("#payment_search_payments").addClass('hide');
			$(".search_payments").submit(function (e) {
				e.preventDefault();
				if ($('.search_value').val() == "" || $('.search_by :selected').val() == "")
					return;
				var data = $(this).serialize();
				payments_handleTable('search_payments', '&' + data);
				$("#payment_search_payments").removeClass('hide');
			});

			var search_by = App.getURLParameter('search_by');
			var search_value = App.getURLParameter('search_value');
			if (typeof search_by !== 'undefined' && typeof search_value !== 'undefined') {
				$('.search_payments .search_value').val(search_value);
				$('.search_payments').submit();
			}
		}

		// IMPORT PAYMENTS SHEET
		order_import_payments_handleValidation();
		order_import_payments_charge_handleValidation();


		// Init Table
		initTable_payments();
	}

	return {
		//main function to initiate the module
		init: function (type) {
			switch (type) {
				case 'overview':
					dashboardDaterange();
					break;

				case 'orders':
					order_search();
					tabChange_handleTable('order');
					break;

				case 'returns':
					order_search();
					tabChange_handleTable('return');
					return_handleImport();
					break;

				case 'scan_ship':
					scanShip_handleInit();
					break;

				case 'payments':
					payments_handleInit();
			}
		}
	};
}();
