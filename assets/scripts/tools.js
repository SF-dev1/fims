'use_strict'

var Tools = function () {

	$('#report_marketplace').change(function () {
		var marketplace = $(this).val();
		var options = '<option value=""></option><option value="all">All</option>';
		if (marketplace == 'flipkart') {
			for (var i = 0; i < accounts.flipkart.length; i++) {
				options += '<option value="' + accounts.flipkart[i].account_id + '">' + accounts.flipkart[i].account_name + '</option>';
			}
		}

		$('#report_account').html(options);
	});

	var submitForm = function (formData, $type) {
		var ret = {};
		$.ajax({
			url: "ajax_load.php?token=" + new Date().getTime(),
			cache: false,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			async: false,
			success: function (s) {
				ret = $.parseJSON(s);
			},
			error: function () {
				alert('Error Processing your Request!!');
			}
		});
		return ret;
	};

	// FLIPKART
	function fk_handleTable() {
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
			"active": {
				title: "Active",
				class: " badge badge-success"
			},
			"inactive": {
				title: "Inactive",
				class: " badge badge-default"
			},
		};

		var table = $('#editable_flipkart');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 20,
			language: {
				lengthMenu: "Display _MENU_"
			},
			fixedHeader: {
				"headerOffset": 40
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=get_fk_accounts",
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "account_name",
					title: "Display Name",
					columnFilter: 'inputFilter'
				}, {
					data: "account_email",
					title: "Email Address",
					columnFilter: 'inputFilter'
				}, {
					data: "account_mobile",
					title: "Reg. Mobile#",
					columnFilter: 'inputFilter'
					// }, {
					// 	data: "is_ams",
					// 	title: "AMS?",
					// 	columnFilter: 'selectFilter'
				}, {
					data: "party_name",
					title: "AMS for?",
					columnFilter: 'selectFilter'
				}, {
					data: "account_status",
					title: "Status",
					columnFilter: 'statusFilter'
				}, {
					data: "login_status",
					title: "Login Status",
					columnFilter: 'statusFilter'
				}, {
					data: "api_status",
					title: "API Status",
					columnFilter: 'statusFilter'
				}, {
					data: "actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[0, 'asc']
			],
			columnDefs: [
				{
					targets: [-2, -3, -4],
					width: "10%",
					render: function (a, t, e, s) {
						$return = void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						$return = '<a href="#portlet-edit-account-flipkart" data-marketplace="flipkart" data-accountid="' + e.account_id + '" class="edit btn btn-default btn-xs purple" title="Update"><i class="fa fa-cog"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function (oSettings) {
			},
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					editableTable();
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
			$('#reload-mp-fk').off().on('click', function (e) {
				e.preventDefault();
				console.log('reload');
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
		}

		var editableTable = function () {
			table.on('click', '.edit', function (e) {
				e.preventDefault();
				var btn = $(this);
				btn.attr('disabled', true);
				btn.find('i').addClass('fa-spin');
				var account_id = $(this).data('accountid');
				var marketplace = $(this).data('marketplace');
				var form = $('#update-account-flipkart');
				$.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: 'GET',
					data: "action=get_account_details&marketplace=" + marketplace + "&account_id=" + account_id,
					success: function (s) {
						s = $.parseJSON(s);
						if (s.type == "success") {
							var acccount_details = s.data;
							// RESET
							$('.is_sellerSmart, .is_marketplaceFulfilled, .is_ams').bootstrapSwitch('state', false);
							$('.fa_warehouses').prop('checked', false);
							$('.ams_vendor').val("").trigger('change');
							// SET
							$.each(acccount_details, function (k, v) {
								if ((k == "is_sellerSmart" || k == "is_ams")) {
									if (v == 1)
										form.find('.' + k).bootstrapSwitch('state', true);
									else
										form.find('.' + k).bootstrapSwitch('state', false);
								} else if (k == "party_id") {
									form.find('.ams_vendor').val(v).trigger('change');
								} else if (k == "mp_warehouses" && (v != "" && v != null)) {
									form.find('.is_marketplaceFulfilled').bootstrapSwitch('state', true);
									$.each($.parseJSON(v), function (k, warehouse) {
										form.find('.fa_warehouses.' + warehouse).prop('checked', true);
									})
								} else {
									form.find('.' + k).val(v);
								}
							});
							$('.marketplaces').val(marketplace);
							App.updateUniform($('.fa_warehouses'));
							$("#portlet-edit-account-flipkart").modal('show');
							btn.attr('disabled', false);
							btn.find('i').removeClass('fa-spin');
						} else {
							UIToastr.init('error', 'Account Details', 'Error fetching account details. Please retry again later.');
							btn.attr('disabled', false);
							btn.find('i').removeClass('fa-spin');
						}
					},
					error: function (e) {
						UIToastr.init('error', 'Account Details Collection', 'Error Processing your Request!!');
					}
				});
			});
		}
	}

	function fk_handleValidation() {
		$('.is_ams').on('switchChange.bootstrapSwitch', function (e, data) {
			if (data) {
				$('.ams_vendor').prop('disabled', false);
				$('.if_ams').removeClass('hide');
			} else {
				$('.ams_vendor').prop('disabled', true);
				$('.if_ams').addClass('hide');
			}
		});

		$('.is_marketplaceFulfilled').on('switchChange.bootstrapSwitch', function (e, data) {
			if (data) {
				$('.fa_warehouses').prop('disabled', false);
				$('.if_fa_warehouses').removeClass('hide');
			} else {
				$('.fa_warehouses').prop('disabled', true);
				$('.if_fa_warehouses').addClass('hide');
			}
			App.updateUniform($('.fa_warehouses'));
		});

		var form = $('#add-fk-marketplace');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);
		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				fk_account_email: {
					required: true,
				},
				fk_account_password: {
					required: true,
				},
			},

			invalidHandler: function (event, validator) { //display error alert on form submit              
				success.hide();
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

			submitHandler: function (form) {
				success.show();
				error.hide();
				$('.form-actions .btn-success', $(form)).attr('disabled', true);
				$('.form-actions .btn-success i', $(form)).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'add_fk');
				$.each(other_data, function (key, input) {
					if (input.name == "is_ams" && input.value == "on")
						formData.append(input.name, 1);
					else
						formData.append(input.name, input.value);
				});

				var s = submitForm(formData, "POST");

				if (s.type == 'success') {
					$('#portlet-config-flipkart').modal('toggle');
					handleAccountDetailsCollection_fk(s.account_id);
					$('#reload-mp-fk').trigger('click');
					// TableEditable.init();
				} else {
					alert('Error Processing your Request!!' + s);
				}

				$('.form-actions .btn-success', $(form)).attr('disabled', false);
				$('.form-actions .btn-success i', $(form)).removeClass('fa-sync fa-spin');
			}
		});

		var form = $('#update-account');
		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",

			invalidHandler: function (event, validator) { //display error alert on form submit              
				// success.hide();
				// error.show();
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

			submitHandler: function (form) {
				// success.show();
				// error.hide();
				$('.form-actions .btn-success', $(form)).attr('disabled', true);
				$('.form-actions .btn-success i', $(form)).addClass('fa fa-sync fa-spin');

				var other_data = $(form).serializeArray();
				console.log(other_data);
				var formData = new FormData();
				$.each(other_data, function (key, input) {
					if ((input.name == "is_sellerSmart" || input.name == "is_ams") && input.value == "on")
						formData.append(input.name, 1);
					else
						formData.append(input.name, input.value);
				});

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == 'success') {
						var modal = $(form).data('modal');
						if (modal == "portlet-config-flipkart" || modal == "portlet-edit-account-flipkart") {
							if (s.fetch)
								handleAccountLoginStatus(s.account_id);
							if (s.api_fetch)
								handleAccountAPIRefreshToken(s.account_id)

							$('#' + modal).modal('toggle');
							$('#reload-mp-fk').trigger('click');
						}
					} else {
						alert('Error Processing your Request!!' + s.message);
					}
					$('.form-actions .btn-success', $(form)).attr('disabled', false);
					$('.form-actions .btn-success i', $(form)).removeClass('fa-sync fa-spin');
				}, 100);

			}
		});
	}

	function handleAccountDetailsCollection_fk(account_id) {
		UIToastr.init('info', 'Account Details Collection', 'Fetching Account details');
		window.setTimeout(function () {
			$.ajax({
				url: "../flipkart/ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'GET',
				data: "action=get_details_fk&account_id=" + account_id,
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == "success") {
						UIToastr.init('success', 'Account Details Collection', s.msg);
						handleAccountAPIDetailsCollection(account_id);
						$('#reload-mp-fk').trigger('click');
					} else {
						UIToastr.init('error', 'Account Details Collection', s.msg);
					}
				},
				error: function (e) {
					UIToastr.init('error', 'Account Details Collection', 'Error Processing your Request!!');
				}
			});
		}, 100);
	}

	function handleAccountAPIDetailsCollection(account_id) {
		UIToastr.init('info', 'API Details Collection', 'Fetching API details');
		window.setTimeout(function () {
			$.ajax({
				url: "../flipkart/ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'GET',
				data: "action=get_api_details_fk&account_id=" + account_id,
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == "success") {
						UIToastr.init('success', 'API Details Collection', s.msg);
						$('#reload-mp-fk').trigger('click');
					} else {
						UIToastr.init('error', 'API Details Collection', s.msg);
					}
				},
				error: function (e) {
					UIToastr.init('error', 'API Details Collection', 'Error Processing your Request!!');
				}
			});
		}, 100);
	}

	function handleAccountLoginStatus(account_id) {
		UIToastr.init('info', 'Flipkart Login', 'Confirming Login details');
		window.setTimeout(function () {
			$.ajax({
				url: "../flipkart/ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'GET',
				data: "action=get_login_status&account_id=" + account_id,
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == "success") {
						UIToastr.init('success', 'Flipkart Login', s.msg);
						$('#reload-mp-fk').trigger('click');
					} else {
						UIToastr.init('error', 'Flipkart Login Update', s.msg);
					}
				},
				error: function (e) {
					UIToastr.init('error', 'Flipkart Login', 'Error Processing your Request!!');
				}
			});
		}, 100);
	}

	function handleAccountAPIRefreshToken(account_id) {
		UIToastr.init('info', 'API Details Update', 'Confirming API details');
		window.setTimeout(function () {
			$.ajax({
				url: "../flipkart/ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'GET',
				data: "action=get_api_status&account_id=" + account_id,
				success: function (s) {
					s = $.parseJSON(s);
					if (s.type == "success") {
						UIToastr.init('success', 'API Details Update', s.msg);
						$('#reload-mp-fk').trigger('click');
					} else {
						UIToastr.init('error', 'API Details Update', s.msg);
					}
				},
				error: function (e) {
					UIToastr.init('error', 'API Details Update', 'Error Processing your Request!!');
				}
			});
		}, 100);
	}

	// AMAZON
	function handleTable_az_1() {

		function restoreRow(oTable, nRow) {
			var aData = oTable.fnGetData(nRow);
			var jqTds = $('>td', nRow);

			for (var i = 0, iLen = jqTds.length; i < iLen; i++) {
				oTable.fnUpdate(aData[i], nRow, i, false);
			}

			oTable.fnDraw();
		}

		function editRow(oTable, nRow) {
			var aData = oTable.fnGetData(nRow);
			var jqTds = $('>td', nRow);
			jqTds[0].innerHTML = aData[0];
			jqTds[1].innerHTML = '<input type="text" class="form-control input-small" value="' + aData[1] + '">';
			jqTds[2].innerHTML = '<input type="text" class="form-control input-small" value="' + aData[2] + '">';
			jqTds[3].innerHTML = '<textarea class="form-control input-medium" rows="2"> ' + aData[3].replace(/\n/g, '<br/>') + '</textarea>';
			jqTds[4].innerHTML = '<input type="text" class="form-control input-medium" value="' + aData[4] + '">';
			jqTds[5].innerHTML = '<input type="text" class="form-control input-medium" value="' + aData[5] + '">';
			jqTds[6].innerHTML = '<select id="selMenu" class="input-sm"></select>';
			jqTds[7].innerHTML = '<a class="edit" href=""><i class="fa fa-check"></i></a>';
			jqTds[8].innerHTML = '<a class="cancel" href=""><i class="fa fa-times"></i></a>';
			jsonSelects = $.parseJSON('{"menu":[{"num_status":"1","wrd_status":"Active"},{"num_status":"0","wrd_status":"Inactive"}]}');

			for (i = 0; i < jsonSelects.menu.length; i++) {
				var selected = "";
				if (aData[6].replace(/<[\/]{0,1}(i|I)[^><]*>/g, "").replace(/\s/g, '') == jsonSelects.menu[i].wrd_status) {
					selected = "selected = 'selected'";
				}
				$('#selMenu').append('<option ' + selected + ' value=' + jsonSelects.menu[i].num_status + '>' + jsonSelects.menu[i].wrd_status + '</option>');
			}
		}

		function saveRow(oTable, nRow) {
			var jqInputs = $('input', nRow);
			var status = "<i class='fa fa-circle grey'></i> Inactive";
			var jqTextarea = $('textarea', nRow);
			var jqSelect = $('select', nRow);
			var aData = oTable.fnGetData(nRow);
			if (jqSelect[0].value == 1) {
				status = "<i class='fa fa-circle green'></i> Active";
			}

			// oTable.fnUpdate(jqInputs[0].value, nRow, 0, false);
			oTable.fnUpdate(jqInputs[0].value, nRow, 1, false);
			oTable.fnUpdate(jqInputs[1].value, nRow, 2, false);
			oTable.fnUpdate(jqTextarea[0].value.replace(/\n/g, '<br/>'), nRow, 3, false);
			oTable.fnUpdate(jqInputs[2].value, nRow, 4, false);
			oTable.fnUpdate(jqInputs[3].value, nRow, 5, false);
			oTable.fnUpdate(status, nRow, 6, false);
			oTable.fnUpdate('<a class="edit" href=""><i class="fa fa-edit"></i></a>', nRow, 7, false);
			oTable.fnUpdate('<a class="delete" href=""><i class="fa fa-trash-o"></i></a>', nRow, 8, false);
			$.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				cache: false,
				type: 'POST',
				data: "action=update_az&account_id=" + aData[0] + "&account_name=" + jqInputs[0].value + "&az_account_name=" + jqInputs[1].value + "&account_mobile=" + jqTextarea[0].value + "&account_status=" + jqSelect[0].value + "&app_id=" + jqInputs[2].value + "&app_secret=" + jqInputs[3].value,
				success: function (s) {
					console.log(s);
					// s = $.parseJSON(s);
					if (s == "success") {
						console.log('update');
					} else if (s == "error") {
						console.log('error');
					}
				},
				error: function (e) {
					// NProgress.done(true);
					alert('Error Processing your Request!!');
				}
			});

			oTable.fnDraw();
		}

		function cancelEditRow(oTable, nRow) {
			var jqInputs = $('input', nRow);
			var jqSelect = $('select', nRow);
			oTable.fnUpdate(jqInputs[1].value, nRow, 0, false);
			oTable.fnUpdate(jqInputs[2].value, nRow, 1, false);
			oTable.fnUpdate(jqSelect[0].value, nRow, 2, false);
			oTable.fnUpdate(jqInputs[3].value, nRow, 3, false);
			oTable.fnUpdate(jqInputs[4].value, nRow, 4, false);
			oTable.fnUpdate('<a class="edit" href=""><i class="fa fa-edit"></i></a>', nRow, 5, false);
			oTable.fnDraw();
		}

		var table = $('#editable_amazon');

		var oTable = table.DataTable({
			"filter": false,
			"bDestroy": true,
			"bLengthChange": false,
			"bPaginate": false,
			"bInfo": false,
			"bSort": false,
			"processing": true,
			"serverSide": true,
			"ajax": {
				"url": "ajax_load.php?action=accounts_az",
				// "type": "POST",
			},
		});

		var tableWrapper = $("#editable_amazon_wrapper");

		tableWrapper.find(".dataTables_length select").select2({
			showSearchInput: false //hide search box with special css class
		}); // initialize select2 dropdown

		var nEditing = null;
		var nNew = false;

		$('#editable_amazon_new').click(function (e) {
			e.preventDefault();

			if (nNew && nEditing) {
				if (confirm("Previose row not saved. Do you want to save it ?")) {
					saveRow(oTable, nEditing); // save
					$(nEditing).find("td:first").html("Untitled");
					nEditing = null;
					nNew = false;

				} else {
					oTable.fnDeleteRow(nEditing); // cancel
					nEditing = null;
					nNew = false;

					return;
				}
			}

			var aiNew = oTable.fnAddData(['', '', '', '', '', '']);
			var nRow = oTable.fnGetNodes(aiNew[0]);
			editRow(oTable, nRow);
			nEditing = nRow;
			nNew = true;
		});

		table.on('click', '.delete', function (e) {
			e.preventDefault();

			if (confirm("Are you sure to delete this row ?") === false) {
				return;
			}

			var nRow = $(this).parents('tr')[0];
			oTable.fnDeleteRow(nRow);
			alert("Delete! Ask the admin to get it done. :)");
			/*$.ajax({
				url: "ajax_load.php?token="+ new Date().getTime(),
				cache: false,
				type: 'POST',
				data: "action=delete_az&account_id="+aData[0],
				success: function(s){
					console.log(s);
					// s = $.parseJSON(s);
					if (s == "success" ){
						console.log('update');
					} else if (s == "error"){
						console.log('error');
					}
				},
				error: function(e){
					// NProgress.done(true);
					alert('Error Processing your Request!!');
				}
			});*/
		});

		table.on('click', '.cancel', function (e) {
			e.preventDefault();

			if (nNew) {
				oTable.fnDeleteRow(nEditing);
				nNew = false;
			} else {
				restoreRow(oTable, nEditing);
				nEditing = null;
			}
		});

		table.on('click', '.edit', function (e) {
			e.preventDefault();

			/* Get the row as a parent of the link that was clicked on */
			var nRow = $(this).parents('tr')[0];

			if (nEditing !== null && nEditing != nRow) {
				/* Currently editing - but not this row - restore the old before continuing to edit mode */
				restoreRow(oTable, nEditing);
				editRow(oTable, nRow);
				nEditing = nRow;
			} else if (nEditing == nRow && this.innerHTML == '<i class="fa fa-check"></i>') {
				/* Editing this row and want to save it */
				saveRow(oTable, nEditing);
				nEditing = null;
			} else {
				/* No edit in progress - let's start one */
				editRow(oTable, nRow);
				nEditing = nRow;
			}
		});
	}

	function handleTable_az() {
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
			"active": {
				title: "Active",
				class: " badge badge-success"
			},
			"inactive": {
				title: "Inactive",
				class: " badge badge-default"
			},
		};

		var table = $('#editable_amazon');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 20,
			language: {
				lengthMenu: "Display _MENU_"
			},
			fixedHeader: {
				"headerOffset": 40
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=get_az_accounts",
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "account_name",
					title: "Display Name",
					columnFilter: 'inputFilter'
				}, {
					data: "az_account_name",
					title: "Account Name",
					columnFilter: 'inputFilter'
				}, {
					data: "account_mobile",
					title: "Reg. Mobile#",
					columnFilter: 'inputFilter'
				}, {
					data: "party_name",
					title: "AMS for?",
					columnFilter: 'selectFilter'
				}, {
					data: "seller_id",
					title: "Seller ID",
					columnFilter: 'inputFilter'
				}, {
					data: "marketplace_id",
					title: "Marketplace",
					columnFilter: 'selectFilter'
					// }, {
					// 	data: "login_status",
					// 	title: "Login Status",
					// 	columnFilter: 'statusFilter'
					// }, {
					// 	data: "api_status",
					// 	title: "API Status",
					// 	columnFilter: 'statusFilter'
					// }, {
					// 	data: "account_status",
					// 	title: "Status",
					// 	columnFilter: 'statusFilter'
				}, {
					data: "actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				}
			],
			order: [
				[0, 'asc']
			],
			columnDefs: [
				{
					targets: [-2, -3, -4],
					width: "10%",
					render: function (a, t, e, s) {
						$return = void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						$return = '<a href="#portlet-edit-account-amazon" data-marketplace="amazon" data-accountid="' + e.account_id + '" class="edit btn btn-default btn-xs purple" title="Update"><i class="fa fa-cog"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function (oSettings) {
			},
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					editableTable();
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
			$('#reload-mp-az').off().on('click', function (e) {
				e.preventDefault();
				console.log('reload');
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
		}

		var editableTable = function () {
			table.on('click', '.edit', function (e) {
				e.preventDefault();
				var btn = $(this);
				btn.attr('disabled', true);
				btn.find('i').addClass('fa-spin');
				var account_id = $(this).data('accountid');
				var marketplace = $(this).data('marketplace');
				var form = $('#update-account');
				$.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: 'GET',
					data: "action=get_account_details&marketplace=" + marketplace + "&account_id=" + account_id,
					success: function (s) {
						s = $.parseJSON(s);
						if (s.type == "success") {
							var acccount_details = s.data;
							// RESET
							$('.is_sellerSmart, .is_marketplaceFulfilled, .is_ams').bootstrapSwitch('state', false);
							$('.fa_warehouses').prop('checked', false);
							$('.ams_vendor').val("").trigger('change');
							// SET
							$.each(acccount_details, function (k, v) {
								if ((k == "is_sellerSmart" || k == "is_ams")) {
									if (v == 1)
										form.find('.' + k).bootstrapSwitch('state', true);
									else
										form.find('.' + k).bootstrapSwitch('state', false);
								} else if (k == "party_id") {
									form.find('.ams_vendor').val(v).trigger('change');
								} else if (k == "mp_warehouses" && (v != "" && v != null)) {
									form.find('.is_marketplaceFulfilled').bootstrapSwitch('state', true);
									$.each($.parseJSON(v), function (k, warehouse) {
										form.find('.fa_warehouses.' + warehouse).prop('checked', true);
									})
								} else {
									form.find('.' + k).val(v);
								}
							});
							$('.marketplaces').val(marketplace);
							App.updateUniform($('.fa_warehouses'));
							$("#portlet-edit-account-amazon").modal('show');
							btn.attr('disabled', false);
							btn.find('i').removeClass('fa-spin');
						} else {
							UIToastr.init('error', 'Account Details', 'Error fetching account details. Please retry again later.');
							btn.attr('disabled', false);
							btn.find('i').removeClass('fa-spin');
						}
					},
					error: function (e) {
						UIToastr.init('error', 'Account Details Collection', 'Error Processing your Request!!');
					}
				});
			});
		}
	}

	function handleValidation_az() {
		var form = $('#add-az-marketplace');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				az_display_name: {
					required: true,
				},
				az_company_name: {
					required: true,
				},
				az_company_address: {
					required: true,
				},
				az_app_id: {
					required: true,
				},
				az_app_secret: {
					required: true,
				}
			},

			invalidHandler: function (event, validator) { //display error alert on form submit              
				success.hide();
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

			submitHandler: function (form) {
				success.show();
				error.hide();
				// var az_display_name = $("#az_display_name").val();
				// var az_company_name = $("#az_company_name").val();
				// var az_company_address = $("#az_company_address").val();
				// var az_app_id = $("#az_app_id").val();
				// var az_app_secret = $("#az_app_secret").val();
				// var az_account_status = $('#az_account_status').bootstrapSwitch('state');

				console.log("FORM SUBMIT TRIGGERED. CODING PENDING");
				// var s = submitForm('./ajax_load.php', "action=add_az&account_name="+az_display_name+"&az_account_name="+az_company_name+"&account_mobile="+az_company_address+"&app_id="+az_app_id+"&app_secret="+az_app_secret+"&account_status="+az_account_status, "");
				// if (s == 'success'){
				// 	$('#portlet-config-amazon').modal('toggle');
				// 	TableEditable.init();
				// } else {
				// 	alert('Error Processing your Request!!'+ s);
				// }
			}
		});
	}

	// SHOPIFY
	function handleTable_sp() {
	}

	// Report
	function handleReportGeneration() {
		var form2 = $('#report_generator');
		var error2 = $('.alert-danger', form2);

		form2.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			focusCleanup: true,
			ignore: "",
			rules: {
				report_marketplace: {
					required: true,
				},
				report_account: {
					required: true,
				},
				report_type: {
					required: true,
				},
				report_daterange: {
					required: true,
				},
			},

			messages: { // custom messages for radio buttons and checkboxes
				report_marketplace: {
					required: "Select Marketplace"
				},
				report_account: {
					required: "Select Account"
				},
				report_type: {
					required: "Select Report Type"
				},
				report_daterange: {
					required: "Select Date Range"
				},
			},

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

				var report_marketplace = $('#report_marketplace').val();
				var report_account = $('#report_account').val();
				var report_type = $('#report_type').val();
				var report_daterange = $('#report_daterange input').val();

				var form = document.createElement("form");
				form.setAttribute("method", "post");
				form.setAttribute("action", "../" + report_marketplace + "/ajax_load.php?token=" + new Date().getTime());
				form.setAttribute("target", "_blank");

				// form._submit_function_ = form.submit;

				var params = {
					"action": "generate",
					"type": 'report',
					"reportAccount": report_account,
					"reportType": report_type,
					"daterange": report_daterange
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
				// form._submit_function_();
				form.submit();
			}
		});
	}

	function handleDateRangePickers() {
		if (!jQuery().daterangepicker) {
			return;
		}

		$('#report_daterange').daterangepicker({
			opens: (App.isRTL() ? 'left' : 'right'),
			autoApply: true,
			format: 'MM/DD/YYYY',
			separator: ' to ',
			startDate: moment().subtract('month', 1),
			endDate: moment(),
			ranges: {
				'Today': [moment(), moment()],
				'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
				'Last 7 Days': [moment().subtract(6, 'days'), moment()],
				'Last 30 Days': [moment().subtract(29, 'days'), moment()],
				'This Month': [moment().startOf('month'), moment().endOf('month')],
				'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
			},
			minDate: moment().subtract('days', 365),
			maxDate: moment(),
		},
			function (start, end) {
				$('#report_daterange input').val(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
			}
		).on('change', function () {
			$(this).valid();  // triggers the validation test
		});
	}

	// SMS Tools
	function handleSmsActivation() {
		if (!$().bootstrapSwitch) {
			return;
		}
		$('.sms_api_status').bootstrapSwitch();

		$('.sms_api_status').on('switchChange.bootstrapSwitch', function (e, data) {
			NProgress.start();

			var el = jQuery(this).closest(".portlet").children(".portlet-body");
			if ($(this).is(":checked")) {
				jQuery(this).removeClass("expand").addClass("collapse");
				el.slideDown(200);
				is_active = 1;
			} else {
				jQuery(this).removeClass("collapse").addClass("expand");
				el.slideUp(200);
				is_active = 0;
			}
			$("[class='sms_api_status']").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_sms_service_status&is_active=" + is_active,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'SMS API Activation', 'Error updating details!! Please retry later.');
					}
					$("[class='in_stock']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'SMS API Activation', 'Error Processing your Request!! Please retry later.');
				}
			});
		});
	}

	function handleSmsSettingsReload() {
		console.log("RELOAD");
	}

	function handleSmsSettings() {
		var form = $('.sms_api_settings form');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				sms_url: {
					required: true,
				},
				sms_username: {
					required: true,
				},
				sms_password: {
					required: true,
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
				error.appendTo(element.parent("div"));
			},

			submitHandler: function (form) {
				error.hide();

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions i', form).addClass('fa fa-sync fa-spin');

				var sms_url = $("#sms_url").val();
				var sms_username = $("#sms_username").val();
				var sms_password = $("#sms_password").val();
				var formData = new FormData();
				formData.append('action', 'update_sms_settings');
				formData.append('sms_url', sms_url);
				formData.append('sms_username', sms_username);
				formData.append('sms_password', sms_password);
				NProgress.configure({ trickle: false });
				NProgress.start();

				$.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: 'POST',
					data: formData,
					contentType: false,
					processData: false,
					// mimeType: "multipart/form-data", 
					async: false,
					success: function (s) {
						NProgress.done(true);
						if (s == 'success') {
							UIToastr.init('success', 'SMS API Settings', 'Settings successfully update.');
							$('#portlet-config-flipkart').modal('toggle');
							handleSmsSettingsReload();
						} else {
							UIToastr.init('error', 'SMS API Settings', 'Error updating details!! Please retry later. <br />Error: ' + s);
							// alert('Error Processing your Request!!'+ s);
						}
						success.hide().text("");
						error.hide().text("");
						$('.form-actions .btn-success', form).attr('disabled', false);
						$('.form-actions i', form).removeClass('fa-sync fa-spin');
					},
					error: function (e) {
						NProgress.done(true);
						UIToastr.init('error', 'SMS API Settings', 'Error Processing your Request!! Please retry later. <br />Error: ' + s);
					}
				});
			}
		});
	}

	// EMAIL Tools
	function handleEmailActivation() {
		if (!$().bootstrapSwitch) {
			return;
		}
		$('.email_api_status').bootstrapSwitch();

		$('.email_api_status').on('switchChange.bootstrapSwitch', function (e, data) {
			NProgress.start();

			var el = jQuery(this).closest(".portlet").children(".portlet-body");
			if ($(this).is(":checked")) {
				jQuery(this).removeClass("expand").addClass("collapse");
				el.slideDown(200);
				is_active = 1;
			} else {
				jQuery(this).removeClass("collapse").addClass("expand");
				el.slideUp(200);
				is_active = 0;
			}
			$("[class='email_api_status']").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_email_service_status&is_active=" + is_active,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'EMAIL API Activation', 'Error updating details!! Please retry later.');
					}
					$("[class='in_stock']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'EMAIL API Activation', 'Error Processing your Request!! Please retry later.');
				}
			});
		});
	}

	function handleEmailSettingsReload() {
		console.log("RELOAD");
	}

	function handleEmailSettings() {
		var form = $('.email_api_settings form');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				email_url: {
					required: true,
				},
				email_username: {
					required: true,
				},
				email_password: {
					required: true,
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
				error.appendTo(element.parent("div"));
			},

			submitHandler: function (form) {
				error.hide();

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions i', form).addClass('fa fa-sync fa-spin');

				var email_url = $("#email_url").val();
				var email_username = $("#email_username").val();
				var email_password = $("#email_password").val();
				var formData = new FormData();
				formData.append('action', 'update_email_settings');
				formData.append('email_url', email_url);
				formData.append('email_username', email_username);
				formData.append('email_password', email_password);
				NProgress.configure({ trickle: false });
				NProgress.start();

				$.ajax({
					url: "ajax_load.php?token=" + new Date().getTime(),
					cache: false,
					type: 'POST',
					data: formData,
					contentType: false,
					processData: false,
					// mimeType: "multipart/form-data", 
					async: false,
					success: function (s) {
						NProgress.done(true);
						if (s == 'success') {
							UIToastr.init('success', 'EMAIL API Settings', 'Settings successfully update.');
							$('#portlet-config-flipkart').modal('toggle');
							handleEmailSettingsReload();
						} else {
							UIToastr.init('error', 'EMAIL API Settings', 'Error updating details!! Please retry later. <br />Error: ' + s);
							// alert('Error Processing your Request!!'+ s);
						}
						success.hide().text("");
						error.hide().text("");
						$('.form-actions .btn-success', form).attr('disabled', false);
						$('.form-actions i', form).removeClass('fa-sync fa-spin');
					},
					error: function (e) {
						NProgress.done(true);
						UIToastr.init('error', 'EMAIL API Settings', 'Error Processing your Request!! Please retry later. <br />Error: ' + s);
					}
				});
			}
		});
	}

	// QZ
	function qzSettings_handleActivation() {
		if (!$().bootstrapSwitch) {
			return;
		}

		$('#qz_status').bootstrapSwitch();

		var is_active = 0;
		$('#qz_status').on('switchChange.bootstrapSwitch', function (e, data) {
			NProgress.start();

			var el = jQuery(this).closest(".portlet").children(".portlet-body");
			if ($(this).is(":checked")) {
				jQuery(this).removeClass("expand").addClass("collapse");
				el.slideDown(200);
				is_active = 1;
			} else {
				jQuery(this).removeClass("collapse").addClass("expand");
				el.slideUp(200);
				is_active = 0;
			}

			$("[class='qz_status']").bootstrapSwitch('disabled', true);
			var formData = new FormData();
			formData.append('action', 'qz_status');
			formData.append('is_active', is_active);
			var s = submitForm(formData, "POST");
			if (s.type == "success" || s.type == "error") {
				$("[class='qz_status']").bootstrapSwitch('disabled', false);
				NProgress.done(true);
				UIToastr.init(s.type, 'QZ Settings Update', s.msg);
			} else {
				UIToastr.init('error', 'QZ Settings Update', 'Error Processing Request. Please try again later.');
			}
		});
	}

	function qzSettings_HandleSelect(printers) {
		var printer_options = "<option></option>";
		$.each(printers, function (e, a) {
			printer_options += "<option value='" + a + "'>" + a + "</option>";
		});

		$('.printers').append(printer_options);
		if (jQuery().select2) {
			$('.printers').select2({
				placeholder: "Select",
				allowClear: true
			});
		}

		$('select.printers').each(function () {
			var id = $(this).attr('id');
			var set = id.replace('printer_', '');
			$('#' + id).val(printer_settings[set]['printer']).trigger('change');
			$('#' + id + '_size').val(printer_settings[set]['size']);
		})
	}

	function qzSettings_handleValidation() {
		var form = $('#qz-settings');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				printer_product_label: {
					required: true,
				},
				printer_weighted_product_label: {
					required: true,
				},
				printer_ctn_box_label: {
					required: true,
				},
				printer_mrp_label: {
					required: true,
				},
				printer_shipping_label: {
					required: true,
				},
				printer_invoice: {
					required: true,
				}
			},

			invalidHandler: function (event, validator) { //display error alert on form submit              
				success.hide();
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

			submitHandler: function (form) {
				success.show();
				error.hide();

				var other_data = $(form).serializeArray();
				var formData = new FormData();
				formData.append('action', 'save_qz_settings');
				$.each(other_data, function (key, input) {
					formData.append(input.name, input.value);
				});

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'QZ Settings Update', s.msg);
				} else {
					UIToastr.init('error', 'QZ Settings Update', 'Error Processing Request. Please try again later.');
				}

				$('.form-actions .btn-success', form).attr('disabled', false);
				$('.form-actions .btn-success i', form).removeClass('fa fa-sync fa-spin');
			}
		});
	}

	// TEMPLATES
	function template_handleTable() {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return 'default';
		});

		var statusFilters = {};

		var table = $('#editable_template');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [
				[20, 50, 100, -1],
				[20, 50, 100, "All"]
			], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=get_template&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "TemplateName",
					title: "Template Name",
					columnFilter: 'selectFilter'
				}, {
					data: "status",
					title: "Status",
					columnFilter: ''
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[0, 'asc']
			],
			columnDefs: [{
				targets: [1],
				orderable: !1,
				render: function (a, t, e, s) {
					if (e.status) {
						$return = '<span class="badge badge-md badge-success">Active</span> ';
					} else {
						$return = '<span class="badge badge-md badge-danger">Inactive</span> ';
					}
					return $return;
				}
			}, {
				targets: -1,
				orderable: !1,
				width: "10%",
				render: function (a, t, e, s) {

					if (e.status) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="btn btn-icon deactive-template" data-toggle="modal" data-target="#deactiveModal" data-templateId="' + e.templateId + '"><i class="fa fa-times text-danger"></i></a> ';
					} else {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="btn btn-icon  active-template"  data-toggle="modal" data-target="#activeModal" data-templateId="' + e.templateId + '"><i class="fa fa-check text-success"></i></a> ';
					}
					// $return = 	'<a class="btn btn-default btn-xs create_grn" href="purchase_new.php?po_id='+e.po_id+'&type='+e.Status+'" title="Edit PO"><i class="fa fa-edit"></i></a> '+
					return $return;

				}

			},],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					edtableTable();
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
				oTable.ajax.reload();
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		}

		var edtableTable = function () {
			function restoreRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				oTable.row(nRow).data(aData);
				oTable.draw();
			}

			function editRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var cData = [];
				for (var i in aData) {
					cData.push(aData[i]);
				}

				var jqTds = $('>td', nRow);
				var jqTds_c = jqTds.length;
				$.each(jqTds, function (e, a) {
					jqTds[e].innerHTML = '<input type="text" class="form-control input-large" value="' + cData[e] + '">';
				});
				jqTds[jqTds_c - 1].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a> <a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';
			}

			function saveRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var jqInputs = $('input', nRow);

				var i = 0;
				for (var k in aData) {
					if (typeof (jqInputs[i]) !== "undefined") {
						aData[k] = jqInputs[i].value;
						i++;
					}
				}

				oTable.row(nRow).data(aData);

				var formData = new FormData();
				formData.append('action', 'edit_template');
				for (var k in aData) {
					formData.append(k, aData[k]);
				}

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Template Update', s.msg);
				} else {
					UIToastr.init('error', 'Template Update', 'Error Processing Request. Please try again later.');
				}
			}

			function cancelEditRow(oTable, nRow) {
				var jqInputs = $('input', nRow);
				oTable.fnUpdate(jqInputs[0].value, nRow, 0, false);
				oTable.fnUpdate(jqInputs[1].value, nRow, 1, false);
				oTable.fnUpdate(jqInputs[2].value, nRow, 2, false);
				oTable.fnUpdate('<a class="edit btn btn-default btn-xs purple" href="">Edit</a>', nRow, 3, false);
				oTable.fnDraw();
			}

			var nEditing = null;
			var nNew = false;

			table.on('click', '.delete', function (e, oTable, nRow) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.fnGetData(nRow);

					var formData = new FormData();
					formData.append('action', 'delete_template');
					formData.append('templateId', aData[0]);

					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						oTable.fnDeleteRow(nRow);
						UIToastr.init(s.type, 'Template', s.msg);
					} else {
						UIToastr.init('error', 'Template', 'Error Processing Request! Please try again later');
					}
				}
			});

			table.on('click', '.cancel', function (e) {
				e.preventDefault();

				if (nNew) {
					oTable.fnDeleteRow(nEditing);
					nNew = false;
				} else {
					restoreRow(oTable, nEditing);
					nEditing = null;
				}
			});


			table.on('click', '.edit', function (e) {
				e.preventDefault();

				/* Get the row as a parent of the link that was clicked on */
				var nRow = $(this).parents('tr')[0];

				if (nEditing !== null && nEditing != nRow) {
					/* Currently editing - but not this row - restore the old before continuing to edit mode */
					restoreRow(oTable, nEditing);
					editRow(oTable, nRow);
					nEditing = nRow;
				} else if (nEditing == nRow && this.innerHTML == '<i class="fa fa-check"></i>') {
					/* Editing this row and want to save it */
					saveRow(oTable, nEditing);
					nEditing = null;
					// oTable.ajax.reload();
					oTable.draw();
					// alert("Updated! Do not forget to do some ajax to sync with backend :)");
				} else {
					/* No edit in progress - let's start one */
					editRow(oTable, nRow);
					nEditing = nRow;
				}
			});
		}
	};

	function template_handleValidation() {
		var form = $('#add-template');
		// console.log("hello");
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				templateName: {
					required: true,
				},
				fk_brandCom: {
					required: true,
				},
				az_brandCom: {
					required: true,
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
				if (element.attr("name") == "fk_brandCom" || element.attr("name") == "az_brandCom") {
					error.appendTo(element.parent("div").parent("div"));
				} else {
					error.appendTo(element.parent("div"));
				}
			},

			submitHandler: function (form) {
				error.hide();
				var templateName = $("#templateName").val();
				var formData = new FormData();
				formData.append('action', 'add_template');
				formData.append('templateName', templateName);

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Add Template', s.msg);
					if (s.type == "success") {
						$('#add_template').modal('hide');
						$("#templateName").val("");
						$('#reload-template').trigger('click');
					}
				} else {
					UIToastr.init('error', 'Template', 'Error Processing Request. Please try again later.');
				}
			}
		});
	};

	function template_handleInit() {
		$('body').on('click', '.deactive-template', function () {
			var templateId = $(this).attr('data-templateid');
			setTimeout(function () {
				$('.yes-sure-deactive:visible').attr('data-templateId', templateId);
			}, 500);
		});

		$('body').on('click', '.yes-sure-deactive', function () {
			var templateId = $(this).attr('data-templateid');
			$.ajax({
				type: "POST",
				url: "ajax_load.php",
				data: {
					'action': 'deactive-template',
					'templateId': templateId
				},
				success: function (data) {
					$('#deactiveModal').modal('hide');

					location.reload();

				}
			});
		});

		$('body').on('click', '.active-template', function () {
			var templateId = $(this).attr('data-templateid');
			setTimeout(function () {
				$('.yes-sure-active:visible').attr('data-templateId', templateId);
			}, 500);
		});

		$('body').on('click', '.yes-sure-active', function () {
			var templateId = $(this).attr('data-templateid');
			$.ajax({
				type: "POST",
				url: "ajax_load.php",
				data: { 'action': 'active-template', 'templateId': templateId },
				success: function (data) {
					location.reload();
				}
			});
		});
	}

	// SMS TEMPLATE
	function smsTemplate_handleTable() {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return 'default';
		});

		var statusFilters = {};

		var table = $('#editable_sms_template');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [
				[20, 50, 100, -1],
				[20, 50, 100, "All"]
			], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=sms_template&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "templateName",
					title: "Template Name",
					columnFilter: 'selectFilter'
				}, {
					data: "templateContent",
					title: "Template Content",
					columnFilter: 'selectFilter'
				}, {
					data: "templateLanguage",
					title: "Template Language",
					columnFilter: 'selectFilter'
				}, {
					data: "smsClientName",
					title: "Client",
					columnFilter: 'selectFilter'
				}, {
					data: "firm_name",
					title: "Firm",
					columnFilter: 'selectFilter'
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[0, 'asc']
			],
			columnDefs: [{
				targets: -1,
				orderable: !1,
				width: "10%",
				render: function (a, t, e, s) {
					$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ';
					return $return;
				}
			}],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					edtableTable();
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
				oTable.ajax.reload();
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		}

		var edtableTable = function () {

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();
					var formData = new FormData();
					formData.append('action', 'delete_sms_template');
					formData.append('smsTemplateId', aData['smsTemplateId']);

					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						oTable.row($(this).parents('tr')).remove().draw();
						UIToastr.init(s.type, 'Language', s.msg);
					} else {
						UIToastr.init('error', 'SMS', 'Error Processing Request! Please try again later');
					}
				}
			});

			table.on('click', '.edit', function (e) {
				e.preventDefault();

				/* Get the row as a parent of the link that was clicked on */
				var nRow = $(this).parents('tr')[0];
				var aData = oTable.row(nRow).data();
				var cData = [];
				for (var i in aData) {
					cData.push(aData[i]);
				}

				$('#add_sms_template').modal('toggle');
				$("#smsTemplateId").val(aData.smsTemplateId);
				$('#masterTemplateId').val(cData[1]).trigger('change');
				CKEDITOR.instances['templateContents'].setData(cData[2]);
				$("#templateLanguage").val(cData[3]);
				$('#clientId').val(cData[4]).trigger('change');
				$('#firmId').val(cData[5]).trigger('change');
			});

			$('.modal-clear').on('click', function () {
				$("#smsTemplateId").val('');
				$('#masterTemplateId').val('').trigger('change');
				CKEDITOR.instances['templateContents'].setData('');
				$("#templateLanguage").val('');
				$('#clientId').val('').trigger('change');
				$('#firmId').val('').trigger('change');
			})
		}
	};

	function smsTemplate_handleValidation() {
		var form = $('#add-sms-template');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				templateName: {
					required: true,
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


			submitHandler: function (form) {
				for (instance in CKEDITOR.instances) {
					CKEDITOR.instances[instance].updateElement();
				}
				error.hide();
				var masterTemplateId = $("#masterTemplateId").val();
				var templateLanguage = $("#templateLanguage").val();
				var templateContent = $("#templateContents").val();
				var clientId = $("#clientId").val();
				var firmId = $("#firmId").val();
				var smsTemplateId = $("#smsTemplateId").val();
				var formData = new FormData();
				if (smsTemplateId) {
					formData.append('action', 'edit_sms_template');
					formData.append('smsTemplateId', smsTemplateId);
				}
				else {
					formData.append('action', 'add_sms_template');
				}
				formData.append('masterTemplateId', masterTemplateId);
				formData.append('templateLanguage', templateLanguage);
				formData.append('templateContent', templateContent);
				formData.append('clientId', clientId);
				formData.append('firmId', firmId);
				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'SMS', s.msg);
					if (s.type == "success") {
						$('#add_sms_template').modal('hide');
						$('#reload-sms-template').trigger('click');
						$("#smsTemplateId").val('');
						$('#masterTemplateId').val('').trigger('change');
						CKEDITOR.instances['templateContents'].setData('');
						$("#templateLanguage").val('');
						$('#clientId').val('').trigger('change');
						$('#firmId').val('').trigger('change');
					}
				} else {
					UIToastr.init('error', 'SMS', 'Error Processing Request. Please try again later.');
				}
			}
		});
	};

	// EMAIL TEMPLATE
	function emailTemplate_handleTable() {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return 'default';
		});

		var statusFilters = {};

		var table = $('#editable_emails_template');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [
				[20, 50, 100, -1],
				[20, 50, 100, "All"]
			], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=emails_template&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [{
				data: "templateName",
				title: "Template Name",
				columnFilter: 'selectFilter'
			}, {
				data: "templateContent",
				title: "Template Content",
				columnFilter: 'selectFilter'
			}, {
				data: "templateLanguage",
				title: "Template Language",
				columnFilter: 'selectFilter'
			}, {
				data: "emailClientName",
				title: "Client",
				columnFilter: 'selectFilter'
			}, {
				data: "firm_name",
				title: "Firms",
				columnFilter: 'selectFilter'
			}, {
				data: "Actions",
				title: "Actions",
				columnFilter: 'actionFilter',
				responsivePriority: -1
			},],
			order: [
				[0, 'asc']
			],
			columnDefs: [{
				targets: -1,
				orderable: !1,
				width: "10%",
				render: function (a, t, e, s) {
					$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ';
					// + '<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
					return $return;
				}
			}],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					edtableTable();
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
				oTable.ajax.reload();
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		}

		var edtableTable = function () {

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();
					var formData = new FormData();
					formData.append('action', 'delete_emails_template');
					formData.append('emailTemplateId', aData['emailTemplateId']);

					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						oTable.row($(this).parents('tr')).remove().draw();
						UIToastr.init(s.type, 'Language', s.msg);
						if (s.type == "success") {
							$('#reload-emails-template').trigger('click');
						}
					} else {
						UIToastr.init('error', 'Language', 'Error Processing Request! Please try again later');
					}
				}
			});

			table.on('click', '.edit', function (e) {
				e.preventDefault();

				var nRow = $(this).parents('tr')[0];
				var aData = oTable.row(nRow).data();
				var cData = [];
				for (var i in aData) {
					cData.push(aData[i]);
				}

				$('#add_emails_template').modal('toggle');
				$("#emailTemplateId").val(aData.emailTemplateId);
				$('#masterTemplateId').val(cData[1]).trigger('change');
				$("#emailTemplateContents").val(cData[2]);
				CKEDITOR.replace('emailTemplateContents', {
					toolbarGroups: [],
					startupMode: 'source'
				});
				$("#templateLanguage").val(cData[3]);
				$('#clientId').val(cData[4]).trigger('change');
				$('#firmId').val(cData[5]).trigger('change');
			});

			$('.modal-clear').on('click', function () {
				$("#emailTemplateId").val('');
				$('#masterTemplateId').val('').trigger('change');
				$("#emailTemplateContents").val('');
				$("#templateLanguage").val('');
				$('#clientId').val('').trigger('change');
				$('#firmId').val('').trigger('change');
			});
		}
	};

	function emailTemplate_handleValidation() {
		var form = $('#add-emails-template');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				templateName: {
					required: true,
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


			submitHandler: function (form) {
				for (instance in CKEDITOR.instances) {
					CKEDITOR.instances[instance].updateElement();
				}
				error.hide();
				var masterTemplateId = $("#masterTemplateId").val();
				var templateLanguage = $("#templateLanguage").val();
				var templateContent = $("#emailTemplateContents").val();
				var clientId = $("#clientId").val();
				var firmId = $("#firmId").val();
				var emailTemplateId = $("#emailTemplateId").val();
				var formData = new FormData();
				if (emailTemplateId) {
					formData.append('action', 'edit_emails_template');
					formData.append('emailTemplateId', emailTemplateId);
				}
				else {
					formData.append('action', 'add_emails_template');
				}
				formData.append('masterTemplateId', masterTemplateId);
				formData.append('templateLanguage', templateLanguage);
				formData.append('templateContent', templateContent);
				formData.append('clientId', clientId);
				formData.append('firmId', firmId);

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Language', s.msg);
					if (s.type == "success") {
						$('#add_emails_template').modal('hide');
						$('#reload-emails-template').trigger('click');
						$("#emailTemplateId").val('');
						$('#masterTemplateId').val('').trigger('change');
						$("#emailTemplateContents").val('');
						$("#templateLanguage").val('');
						$('#clientId').val('').trigger('change');
						$('#firmId').val('').trigger('change');
					}
				} else {
					UIToastr.init('error', 'Email', 'Error Processing Request. Please try again later.');
				}
			}
		});
	};

	// WHATSAPP TEMPLATE
	function whatsappTemplate_handleTable() {
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return 'default';
		});

		var statusFilters = {};

		var table = $('#editable_whatsapp_template');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [
				[20, 50, 100, -1],
				[20, 50, 100, "All"]
			], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=whatsapp_template&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [{
				data: "templateName",
				title: "Template Identifier",
				columnFilter: 'selectFilter'
			}, {
				data: "templateContent",
				title: "Template Content",
				columnFilter: 'selectFilter'
			}, {
				data: "templateLanguage",
				title: "Template Language",
				columnFilter: 'selectFilter'
			}, {
				data: "whatsappClientName",
				title: "Client",
				columnFilter: 'selectFilter'
			}, {
				data: "firm_name",
				title: "Firms",
				columnFilter: 'selectFilter'
			}, {
				data: "Actions",
				title: "Actions",
				columnFilter: 'actionFilter',
				responsivePriority: -1
			},],
			order: [
				[0, 'asc']
			],
			columnDefs: [{
				targets: -1,
				orderable: !1,
				width: "10%",
				render: function (a, t, e, s) {
					$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ';
					// + '<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
					return $return;
				}
			}],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					edtableTable();
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
				oTable.ajax.reload();
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		}

		var edtableTable = function () {
			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();
					var formData = new FormData();
					formData.append('action', 'delete_whatsapp_template');
					formData.append('whatsappTemplateId', aData['whatsappTemplateId']);

					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						oTable.row($(this).parents('tr')).remove().draw();
						if (s.type == "success") {
							$('#reload-whatsapp-template').trigger('click');
						}
						UIToastr.init(s.type, 'Whatsapp', s.msg);
					} else {
						UIToastr.init('error', 'Whatsapp', 'Error Processing Request! Please try again later');
					}
				}
			});

			table.on('click', '.edit', function (e) {
				e.preventDefault();
				var nRow = $(this).parents('tr')[0];
				var aData = oTable.row(nRow).data();
				var cData = [];
				$('#add_whatsapp_template').modal('toggle');
				$("#whatsappTemplateId").val(aData.whatsappTemplateId);
				$('#masterTemplateId').val(aData.masterTemplateId).trigger('change');
				$("#template_name").val(aData.templateName).prop('readonly', true);
				CKEDITOR.instances['templateContents'].setData(aData.templateContent);
				$("#templateLanguage").val(aData.templateLanguage);
				$('#clientId').val(aData.clientId).trigger('change');
				$('#firmId').val(aData.firmId).trigger('change');
			});

			$('.modal-clear').on('click', function () {
				$("#whatsappTemplateId").val('');
				$('#masterTemplateId').val('').trigger('change');
				CKEDITOR.instances['templateContents'].setData('');
				$("#templateLanguage").val('');
				$("#template_name").val('').prop('readonly', false);
				$('#clientId').val('').trigger('change');
				$('#firmId').val('').trigger('change');
			});
		}
	}

	function whatsappTemplate_handleValidation() {
		var form = $('#add-whatsapp-template');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				templateName: {
					required: true,
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


			submitHandler: function (form) {
				for (instance in CKEDITOR.instances) {
					CKEDITOR.instances[instance].updateElement();
				}
				error.hide();
				var template_name = $("#template_name").val();
				var masterTemplateId = $("#masterTemplateId").val();
				var templateLanguage = $("#templateLanguage").val();
				var templateContent = $("#templateContents").val();
				var clientId = $("#clientId").val();
				var firmId = $("#firmId").val();
				var whatsappTemplateId = $("#whatsappTemplateId").val();
				var formData = new FormData();
				if (whatsappTemplateId) {
					formData.append('action', 'edit_whatsapp_template');
					formData.append('whatsappTemplateId', whatsappTemplateId);
				}
				else {
					formData.append('action', 'add_whatsapp_template');
				}
				formData.append('masterTemplateId', masterTemplateId);
				formData.append('template_name', template_name);
				formData.append('templateLanguage', templateLanguage);
				formData.append('templateContent', templateContent);
				formData.append('clientId', clientId);
				formData.append('firmId', firmId);

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Whatsapp', s.msg);
					if (s.type == "success") {
						$('#add_whatsapp_template').modal('hide');
						$('#reload-whatsapp-template').trigger('click');
						$("#whatsappTemplateId").val('');
						$('#masterTemplateId').val('').trigger('change');
						CKEDITOR.instances['templateContents'].setData('');
						$("#template_name").val('');
						$("#templateLanguage").val('');
						$('#clientId').val('').trigger('change');
						$('#firmId').val('').trigger('change');
					}
				} else {
					UIToastr.init('error', 'Whatsapp', 'Error Processing Request. Please try again later.');
				}
			}
		});
	}

	function templateElement_handleTable() {

		$('#tableName').on('change', function (e) {
			var tableName = $(this).val();
			$.ajax({
				url: "ajax_load.php?&token=" + new Date().getTime(),
				type: 'GET',
				data: "action=template_field_get&tableName=" + tableName,
				success: function (s) {
					var as = $.parseJSON(s);

					$('#fieldName').empty();
					$.each(as, function (key, value) {
						var select = $('#field').val();
						var field = value.Field;
						if (field == select) {
							// $("#fieldName option[value=3]").attr('selected', 'selected');
							$('#fieldName').append('<option value="' + value.Field + '" "selected="selected">' + value.Field + '</option>');
						}
						else {
							$('#fieldName').append('<option value="' + value.Field + '" >' + value.Field + '</option>');
						}
					});
					$('#fieldName').val($('#field').val()).trigger('change');
				},
				error: function () {
					console.log('Error Processing your Request!!');
				}
			});
		});
		$.fn.dataTable.Api.register("column().title()", function () {
			return $(this.header()).text().trim()
		});

		$.fn.dataTable.Api.register("column().getColumnFilter()", function () {
			e = this.index();
			if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
				return oTable.settings()[0].aoColumns[e].columnFilter;
			else
				return 'default';
		});

		var statusFilters = {};

		var table = $('#editable_template_element');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [
				[20, 50, 100, -1],
				[20, 50, 100, "All"]
			], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=template_element&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [{
				data: "element_table_name",
				title: "Table Name",
				columnFilter: 'selectFilter'
			}, {
				data: "element_field_name",
				title: "Field Name",
				columnFilter: 'selectFilter'
			}, {
				data: "element_status",
				title: "status",
				columnFilter: 'selectFilter'
			}, {
				data: "Actions",
				title: "Actions",
				columnFilter: 'actionFilter',
				responsivePriority: -1
			},],
			order: [
				[0, 'asc']
			],
			columnDefs: [{
				targets: -1,
				orderable: !1,
				width: "10%",
				render: function (a, t, e, s) {
					$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> '
						+ '<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
					return $return;
				}
			}],
			initComplete: function () {
				loadFilters(this),
					afterInitDataTable(),
					edtableTable();
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
				oTable.ajax.reload();
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		}

		var edtableTable = function () {
			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();

					var formData = new FormData();
					formData.append('action', 'delete_template_element');
					formData.append('TemplateElementId', aData['element_id']);

					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						oTable.row($(this).parents('tr')).remove().draw();
						if (s.type == "success") {
							$('#reload-whatsapp-template').trigger('click');
						}
						UIToastr.init(s.type, 'Element', s.msg);
					} else {
						UIToastr.init('error', 'Whatsapp', 'Error Processing Request! Please try again later');
					}
				}
			});

			table.on('click', '.edit', function (e) {
				e.preventDefault();
				var nRow = $(this).parents('tr')[0];
				var aData = oTable.row(nRow).data();
				var cData = [];
				for (var i in aData) {
					cData.push(aData[i]);
				}
				$('#modal-clear').click();
				$('#add_template_element').modal('toggle');
				$("#elementId").val(aData.element_id);
				$("#field").val(aData.element_field_name);
				$('#tableName').val(aData.element_table_name).trigger('change');
				// $('#fieldName').val(aData.element_field_name).trigger('change');
				$("#status").val(aData.element_status).trigger('change');
			});

			$('.modal-clear').on('click', function () {
				$("#elementId").val('');
				$('#tableName').val('').trigger('change');
				$("#field").val('');
				$('#fieldName').val('').trigger('change');
				$("#status").val('').trigger('change');
			});
		}
	}

	function templateElement_handleValidation() {
		var form = $('#add-template-element');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				templateName: {
					required: true,
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


			submitHandler: function (form) {
				error.hide();
				var tableName = $("#tableName").val();
				var fieldName = $("#fieldName").val();
				var status = $("#status").val();
				var elementId = $("#elementId").val();
				var formData = new FormData();

				if (elementId) {
					formData.append('action', 'edit_template_element');
					formData.append('elementId', elementId);
				}
				else {
					formData.append('action', 'add_template_element');
				}

				formData.append('tableName', tableName);
				formData.append('fieldName', fieldName);
				formData.append('status', status);
				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Element', s.msg);
					if (s.type == "success") {
						$('#add_template_element').modal('hide');
						$('#reload-template-element').trigger('click');
						$("#elementId").val('');
						$("#status").val('').trigger('change');
						$("#field").val('');
						$('#fieldName').val('').trigger('change');
						$('#tableName').val('').trigger('change');

					}
				} else {
					UIToastr.init('error', 'Element', 'Error Processing Request. Please try again later.');
				}
			}
		});
	}

	function PrototypeReview_handleTable() {
		var formData = new FormData();
		var tableBody = $('#reviewBody');

		formData.append("action", "getReviews");
		window.setTimeout(function () {
			var response = submitForm(formData, "POST");
			var content = "";
			var count = 0;
			if (response.type == "success") {
				response.data.forEach(row => {
					count++;
					content += '<tr>';
					content += '<td>' + count + '</td>';
					content += '<td>' + row.firstname + " " + row.lastname + '</td>';
					content += '<td>' + row.orderNumberFormated + '</td>';
					content += '<td>' + row.sku + '</td>';
					content += '<td>' + row.design + '</td>';
					content += '<td>' + row.valueForMoney + '</td>';
					content += '<td>' + row.strap + '</td>';
					content += '<td>' + row.buckle + '</td>';
					content += '<td>' + row.durability + '</td>';
					content += '<td>' + row.functionality + '</td>';
					content += '<td>' + row.support + '</td>';
					content += '<td>' + row.comfort + '</td>';
					content += '<td>' + (((row.design + row.valueForMoney + row.strap + row.buckle + row.durability + row.functionality + row.support + row.comfort) / 8).toFixed(1)) + '</td>';
					content += '<td>' + row.review + '</td>';
					content += '</tr>';
				});
				tableBody.html(content);
				TableAdvanced.init();
			}
		}, 100);
	}

	return {
		//main function to initiate the module
		init: function (type) {
			switch (type) {
				case "marketplaces":
					fk_handleTable();
					fk_handleValidation();
					handleTable_az();
					handleValidation_az();
					// handleDateRangePickers();
					break;

				case "report":
					handleReportGeneration();
					handleDateRangePickers();
					break;

				case "sms":
					handleSmsActivation();
					handleSmsSettings();
					break;

				case "email":
					handleEmailActivation();
					handleEmailSettings();
					break;

				case "qz_settings":
					qzSettings_handleActivation();
					qzSettings_handleValidation();
					Qz.init().then(function () {
						qzSettings_HandleSelect(Qz.getAvailablePrinters());
					}, function (e) {
						UIToastr.init('error', 'QZ Tray', "QZ not available!!!");
						console.log(e)
					});
					break;

				case "template":
					template_handleTable();
					template_handleValidation();
					template_handleInit();
					break;

				case "sms_template":
					smsTemplate_handleTable();
					smsTemplate_handleValidation();
					break;

				case "email_template":
					emailTemplate_handleTable();
					emailTemplate_handleValidation();
					break;

				case "whatsapp_template":
					whatsappTemplate_handleTable();
					whatsappTemplate_handleValidation();
					break;

				case "template_element":
					templateElement_handleTable();
					templateElement_handleValidation();
					break;

				case "prototype_review":
					PrototypeReview_handleTable();
					break;
			}
		}
	};
}();
