var Parties = function () {

	// Submit for with mutlipart data containing Image
	function submitForm(formData, a_sync = false) {
		$ret = "";
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
				$ret = $.parseJSON(s);

				if ($ret.redirectUrl) {
					window.location.href = $ret.redirectUrl;
				}
			},
			error: function (e) {
				// NProgress.done(true);
				alert('Error Processing your Request!!');
			}
		});
		return $ret;
	};

	var customers_handleTable = function () {
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

		var statusFilters = {};

		var table = $('#editable_customers');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 25, 50, 100, -1], [20, 25, 50, 100, "All"]], // change per page values here
			pageLength: 50,
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
				url: "ajax_load.php?action=get_customers&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "party_name",
					title: "Party Name",
					columnFilter: 'selectFilter'
				}, {
					data: "party_gst",
					title: "GSTIN",
					columnFilter: 'selectFilter'
				}, {
					data: "party_address",
					title: "Address",
					columnFilter: 'inputFilter'
				}, {
					data: "party_poc",
					title: "POC",
					columnFilter: 'selectFilter'
				}, {
					data: "party_email",
					title: "Email",
					columnFilter: 'inputFilter'
				}, {
					data: "party_mobile",
					title: "Mobile",
					columnFilter: 'inputFilter'
				}, {
					data: "party_distributor",
					title: "Distributor",
					columnFilter: ''
				}, {
					data: "party_customer",
					title: "Customer",
					columnFilter: ''
				}, {
					data: "is_active",
					title: "Active?",
					columnFilter: ''
				}, {
					data: "pending_amount",
					title: "Pending Amount",
					columnFilter: 'rangeFilter'
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [[0, 'asc']],
			columnDefs: [
				{
					targets: [2],
					orderable: !1,
				}, {
					targets: [6],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="party_distributor" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: [7],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="party_customer" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: [8],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="is_active" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: '8%',
					render: function (a, t, e, s) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function (oSettings) {
				// Initialize checkbox for enable/disable user
				$(".bsSwitch").bootstrapSwitch();
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
			$('.reload').bind('click', function (e) {
				e.preventDefault();
				var el = jQuery(this).closest(".portlet").children(".portlet-body");
				App.blockUI({ target: el });
				$('.select2').val("").trigger('change');
				$('.filter-cancel').trigger('click');
				table.find('tbody').html("");
				oTable.ajax.reload(afterInitDataTable, false);
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		};

		var editableTable = function () {

			function restoreRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				oTable.row(nRow).data(aData);
				oTable.draw();
				afterInitDataTable();
			}

			function editRow(oTable, nRow) {
				$(".bsSwitch").bootstrapSwitch('disabled', true);
				var cData = oTable.row(nRow).data();
				var jqTds = $('>td', nRow);
				var aData = [];
				for (var i in cData) {
					aData.push(cData[i]);
				}

				jqTds[0].innerHTML = '<input type="text" class="form-control input-small" value="' + aData[0] + '">';
				jqTds[1].innerHTML = '<input type="text" class="form-control input-small party_gst" value="' + aData[1] + '">';
				jqTds[2].innerHTML = '<textarea class="form-control input-large" rows="2"> ' + aData[2]/*.replace(/\n/g, '<br/>')*/ + '</textarea>';
				jqTds[3].innerHTML = '<input type="text" class="form-control input-small" value="' + aData[3] + '">';
				jqTds[4].innerHTML = '<input type="text" class="form-control input-medium" value="' + aData[4] + '">';
				jqTds[5].innerHTML = '<input type="text" class="form-control input-small party_mobile" value="' + aData[5] + '">';
				jqTds[10].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a><a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';

				parties_handleInputMask();
			}

			function saveRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var jqInputs = $('input', nRow);
				var jqTextarea = $('textarea', nRow);

				$party_name = jqInputs[0].value;
				$party_gst = jqInputs[1].value;
				$party_address = jqTextarea[0].value;
				$party_poc = jqInputs[2].value;
				$party_email = jqInputs[3].value;
				$party_mobile = jqInputs[4].value;

				var formData = new FormData();
				formData.append('action', 'update_parties');
				formData.append('party_id', aData.party_id);
				formData.append('party_name', $party_name);
				formData.append('party_gst', $party_gst);
				formData.append('party_address', $party_address.trim());
				formData.append('party_poc', $party_poc);
				formData.append('party_email', $party_email);
				formData.append('party_mobile', $party_mobile);

				aData = { // SET NEW DATA TO TABLE
					'party_name': $party_name,
					'party_gst': $party_gst,
					'party_address': $party_address.trim(),
					'party_poc': $party_poc,
					'party_email': $party_email,
					'party_mobile': $party_mobile,
					'party_distributor': aData.party_distributor,
					'party_customer': aData.party_customer,
					'is_active': aData.is_active,
					'party_id': aData.party_id,
					'pending_amount': aData.pending_amount,
				};
				oTable.row(nRow).data(aData);
				$(".bsSwitch").bootstrapSwitch('disable', false);

				// Submit from and display alert but no refresh
				var s = submitForm(formData);
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Customer Update', s.msg);
				} else {
					UIToastr.init('error', 'Customer Update', 'Error Processing Request. Please try again later.');
				}
			}

			function cancelEditRow(oTable, nRow) {
				// var jqInputs = $('input', nRow);
				// oTable.fnUpdate(jqInputs[0].value, nRow, 0, false);
				// oTable.fnUpdate(jqInputs[1].value, nRow, 1, false);
				// oTable.fnUpdate(jqInputs[2].value, nRow, 2, false);
				// oTable.fnUpdate('<a class="edit btn btn-default btn-xs purple" href="">Edit</a>', nRow, 3, false);
				oTable.draw();
			}

			var nEditing = null;
			var nNew = false;

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();

					// var formData = new FormData();
					// formData.append('action', 'delete_product');
					// formData.append('pid', aData.pid);

					// var s = submitForm(formData);
					// if (s.type == "success" || s.type == "error"){
					oTable.row($(this).parents('tr')).remove().draw();
					UIToastr.init("success", 'Customer Update', "Successfull deleted customer");
					// UIToastr.init(s.type, 'Product brand', s.msg);
					// } else {
					// 	UIToastr.init('error', 'Product brand', 'Error Processing Request! Please try again later');
					// }
				}
			});

			table.on('click', '.cancel', function (e) {
				e.preventDefault();

				if (nNew) {
					oTable.row(nEditing).remove().draw();
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
		};
	};

	var customers_handleValidation = function () {
		var form = $('#add-party');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				party_name: {
					minlength: 2,
					required: true
				},
				party_gst: {
					minlength: 15,
					maxlength: 15,
					// required: true
				},
				party_address: {
					required: true
				},
				party_poc: {
					required: true
				},
				party_email: {
					required: true,
					email: true
				},
				party_mobile: {
					minlength: 10,
					maxlength: 10,
					required: true,
					digits: true
				},
				party_type: {
					required: true,
					minlength: 1
				},
			},

			messages: { // custom messages for radio buttons and checkboxes
				party_type: {
					required: "Please select a Firm type",
					minlength: $.validator.format("Please select at least {0} types of Firm")
				}
			},

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

			submitHandler: function (form) {
				// success.show();
				error.hide();

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions i', form).addClass('fa fa-sync fa-spin');

				$party_distributor = $('input[name="party_type_distributor"]:checked', form).val()
				$party_distributor = typeof $party_distributor !== 'undefined' ? $party_distributor : 0;
				$party_customer = $('input[name="party_type_customer"]:checked', form).val()
				$party_customer = typeof $party_customer !== 'undefined' ? $party_customer : 0;

				var formData = new FormData();
				formData.append('action', 'add_parties');
				formData.append('party_name', $('#party_name', form).val());
				formData.append('party_gst', $('#party_gst', form).val());
				formData.append('party_address', $('#party_address', form).val());
				formData.append('party_poc', $('#party_poc', form).val());
				formData.append('party_email', $('#party_email', form).val());
				formData.append('party_mobile', $('#party_mobile', form).val());
				formData.append('party_customer', $party_customer);
				formData.append('party_distributor', $party_distributor);
				formData.append('party_carrier', 0);
				formData.append('party_supplier', 0);
				formData.append('party_ams', 0);
				formData.append('client_id', client_id);

				var s = submitForm(formData, true);
				if (s.type == 'success') {
					$('#add_parties').modal('hide');
					UIToastr.init('success', 'Add Party', 'Successfull added new party.');
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
					$('#reload-parties').trigger('click');
				} else if (s.type == 'error') {
					UIToastr.init('error', 'Add Party', 'Error adding new party: ' + s.message);
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
				} else {
					UIToastr.init('error', 'Add Party', 'Error Processing your Request. Please try again later!!! ' + s.message);
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
				}
			}
		});
	};

	var suppliers_handleTable = function () {
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

		var statusFilters = {};

		var table = $('#editable_suppliers');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 25, 50, 100, -1], [20, 25, 50, 100, "All"]], // change per page values here
			pageLength: 50,
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
				url: "ajax_load.php?action=get_suppliers&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "party_name",
					title: "Party Name",
					columnFilter: 'selectFilter'
				}, {
					data: "party_gst",
					title: "GSTIN",
					columnFilter: 'selectFilter'
				}, {
					data: "party_address",
					title: "Address",
					columnFilter: 'inputFilter'
				}, {
					data: "party_poc",
					title: "POC",
					columnFilter: 'selectFilter'
				}, {
					data: "party_email",
					title: "Email",
					columnFilter: 'inputFilter'
				}, {
					data: "party_mobile",
					title: "Mobile",
					columnFilter: 'inputFilter'
				}, {
					data: "party_supplier",
					title: "Supplier",
					columnFilter: ''
				}, {
					data: "party_carrier",
					title: "Carrier",
					columnFilter: ''
				}, {
					data: "is_active",
					title: "Active?",
					columnFilter: ''
				}, {
					data: "pending_amount",
					title: "Pending Amount",
					columnFilter: 'rangeFilter'
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [[0, 'asc']],
			columnDefs: [
				{
					targets: [2],
					orderable: !1,
				}, {
					targets: [6],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="party_distributor" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: [7],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="party_carrier" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: [8],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="is_active" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: '8%',
					render: function (a, t, e, s) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function (oSettings) {
				// Initialize checkbox for enable/disable user
				$(".bsSwitch").bootstrapSwitch();
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
			$('.reload').bind('click', function (e) {
				e.preventDefault();
				var el = jQuery(this).closest(".portlet").children(".portlet-body");
				App.blockUI({ target: el });
				$('.select2').val("").trigger('change');
				$('.filter-cancel').trigger('click');
				table.find('tbody').html("");
				oTable.ajax.reload(afterInitDataTable, false);
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		}

		var editableTable = function () {

			function restoreRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				oTable.row(nRow).data(aData);
				oTable.draw();
				afterInitDataTable();
			}

			function editRow(oTable, nRow) {
				$(".bsSwitch").bootstrapSwitch('disabled', true);
				var cData = oTable.row(nRow).data();
				var jqTds = $('>td', nRow);
				var aData = [];
				for (var i in cData) {
					aData.push(cData[i]);
				}

				jqTds[0].innerHTML = '<input type="text" class="form-control input-small" value="' + aData[0] + '">';
				jqTds[1].innerHTML = '<input type="text" class="form-control input-small party_gst" value="' + aData[1] + '">';
				jqTds[2].innerHTML = '<textarea class="form-control input-large" rows="2"> ' + aData[2]/*.replace(/\n/g, '<br/>')*/ + '</textarea>';
				jqTds[3].innerHTML = '<input type="text" class="form-control input-small" value="' + aData[3] + '">';
				jqTds[4].innerHTML = '<input type="text" class="form-control input-medium" value="' + aData[4] + '">';
				jqTds[5].innerHTML = '<input type="text" class="form-control input-small party_mobile" value="' + aData[5] + '">';
				jqTds[10].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a><a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';

				parties_handleInputMask();
			}

			function saveRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var jqInputs = $('input', nRow);
				var jqTextarea = $('textarea', nRow);

				$party_name = jqInputs[0].value;
				$party_gst = jqInputs[1].value;
				$party_address = jqTextarea[0].value;
				$party_poc = jqInputs[2].value;
				$party_email = jqInputs[3].value;
				$party_mobile = jqInputs[4].value;

				var formData = new FormData();
				formData.append('action', 'update_parties');
				formData.append('party_id', aData.party_id);
				formData.append('party_name', $party_name);
				formData.append('party_gst', $party_gst);
				formData.append('party_address', $party_address.trim());
				formData.append('party_poc', $party_poc);
				formData.append('party_email', $party_email);
				formData.append('party_mobile', $party_mobile);

				aData = { // SET NEW DATA TO TABLE
					'party_name': $party_name,
					'party_gst': $party_gst,
					'party_address': $party_address.trim(),
					'party_poc': $party_poc,
					'party_email': $party_email,
					'party_mobile': $party_mobile,
					'party_distributor': aData.party_distributor,
					'party_customer': aData.party_customer,
					'is_active': aData.is_active,
					'party_id': aData.party_id,
					'pending_amount': aData.pending_amount,
				};
				oTable.row(nRow).data(aData);
				$(".bsSwitch").bootstrapSwitch('disable', false);

				// Submit from and display alert but no refresh
				var s = submitForm(formData);
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Customer Update', s.msg);
				} else {
					UIToastr.init('error', 'Customer Update', 'Error Processing Request. Please try again later.');
				}
			}

			function cancelEditRow(oTable, nRow) {
				// var jqInputs = $('input', nRow);
				// oTable.fnUpdate(jqInputs[0].value, nRow, 0, false);
				// oTable.fnUpdate(jqInputs[1].value, nRow, 1, false);
				// oTable.fnUpdate(jqInputs[2].value, nRow, 2, false);
				// oTable.fnUpdate('<a class="edit btn btn-default btn-xs purple" href="">Edit</a>', nRow, 3, false);
				oTable.draw();
			}

			var nEditing = null;
			var nNew = false;

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();

					// var formData = new FormData();
					// formData.append('action', 'delete_product');
					// formData.append('pid', aData.pid);

					// var s = submitForm(formData);
					// if (s.type == "success" || s.type == "error"){
					oTable.row($(this).parents('tr')).remove().draw();
					UIToastr.init("success", 'Customer Update', "Successfull deleted customer");
					// UIToastr.init(s.type, 'Product brand', s.msg);
					// } else {
					// 	UIToastr.init('error', 'Product brand', 'Error Processing Request! Please try again later');
					// }
				}
			});

			table.on('click', '.cancel', function (e) {
				e.preventDefault();

				if (nNew) {
					oTable.row(nEditing).remove().draw();
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

	var suppliers_handleValidation = function () {
		var form = $('#add-party');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				party_name: {
					minlength: 2,
					required: true
				},
				party_gst: {
					minlength: 15,
					maxlength: 15,
					// required: true
				},
				party_address: {
					required: true
				},
				party_poc: {
					required: true
				},
				party_email: {
					required: true,
					email: true
				},
				party_mobile: {
					minlength: 10,
					maxlength: 10,
					required: true,
					digits: true
				},
				party_type: {
					required: true,
					minlength: 1
				},
			},

			messages: { // custom messages for radio buttons and checkboxes
				party_type: {
					required: "Please select a Firm type",
					minlength: $.validator.format("Please select at least {0} types of Firm")
				}
			},

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

			submitHandler: function (form) {
				// success.show();
				error.hide();

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions i', form).addClass('fa fa-sync fa-spin');

				$party_supplier = $('input[name="party_type_supplier"]:checked', form).val()
				$party_supplier = typeof $party_supplier !== 'undefined' ? $party_supplier : 0;
				$party_carrier = $('input[name="party_type_carrier"]:checked', form).val()
				$party_carrier = typeof $party_carrier !== 'undefined' ? $party_carrier : 0;

				var formData = new FormData();
				formData.append('action', 'add_parties');
				formData.append('party_name', $('#party_name', form).val());
				formData.append('party_gst', $('#party_gst', form).val());
				formData.append('party_address', $('#party_address', form).val());
				formData.append('party_poc', $('#party_poc', form).val());
				formData.append('party_email', $('#party_email', form).val());
				formData.append('party_mobile', $('#party_mobile', form).val());
				formData.append('party_customer', 0);
				formData.append('party_distributor', 0);
				formData.append('party_supplier', $party_supplier);
				formData.append('party_carrier', $party_carrier);
				formData.append('party_ams', 0);
				formData.append('client_id', client_id);

				var s = submitForm(formData, true);
				if (s.type == 'success') {
					$('#add_parties').modal('hide');
					UIToastr.init('success', 'Add Party', 'Successfull added new party.');
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
					$('#reload-parties').trigger('click');
				} else if (s.type == 'error') {
					UIToastr.init('error', 'Add Party', 'Error adding new party: ' + s.message);
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
				} else {
					UIToastr.init('error', 'Add Party', 'Error Processing your Request. Please try again later!!! ' + s.message);
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
				}
			}
		});
	};

	var vendors_handleTable = function () {
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

		var statusFilters = {};

		var table = $('#editable_vendors');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 25, 50, 100, -1], [20, 25, 50, 100, "All"]], // change per page values here
			pageLength: 50,
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
				url: "ajax_load.php?action=get_vendors&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "party_name",
					title: "Party Name",
					columnFilter: 'selectFilter'
				}, {
					data: "party_gst",
					title: "GSTIN",
					columnFilter: 'selectFilter'
				}, {
					data: "party_address",
					title: "Address",
					columnFilter: 'inputFilter'
				}, {
					data: "party_poc",
					title: "POC",
					columnFilter: 'selectFilter'
				}, {
					data: "party_email",
					title: "Email",
					columnFilter: 'inputFilter'
				}, {
					data: "party_mobile",
					title: "Mobile",
					columnFilter: 'inputFilter'
				}, {
					data: "party_supplier",
					title: "Supplier",
					columnFilter: ''
				}, {
					data: "party_ams",
					title: "AMS",
					columnFilter: ''
				}, {
					data: "is_active",
					title: "Active?",
					columnFilter: ''
				}, {
					data: "pending_amount",
					title: "Pending Amount",
					columnFilter: 'rangeFilter'
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [[0, 'asc']],
			columnDefs: [
				{
					targets: [2],
					orderable: !1,
				}, {
					targets: [6],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="party_supplier" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: [7],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="party_ams" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: [8],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="bsSwitch" data-type="is_active" data-party_id="' + e.party_id + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: '8%',
					render: function (a, t, e, s) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function (oSettings) {
				// Initialize checkbox for enable/disable user
				$(".bsSwitch").bootstrapSwitch();
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
			$('.reload').bind('click', function (e) {
				e.preventDefault();
				var el = jQuery(this).closest(".portlet").children(".portlet-body");
				App.blockUI({ target: el });
				$('.select2').val("").trigger('change');
				$('.filter-cancel').trigger('click');
				table.find('tbody').html("");
				oTable.ajax.reload(afterInitDataTable, false);
				window.setTimeout(function () {
					App.unblockUI(el);
				}, 500);
			});
		}

		var editableTable = function () {

			function restoreRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				oTable.row(nRow).data(aData);
				oTable.draw();
				afterInitDataTable();
			}

			function editRow(oTable, nRow) {
				$(".bsSwitch").bootstrapSwitch('disabled', true);
				var cData = oTable.row(nRow).data();
				var jqTds = $('>td', nRow);
				var aData = [];
				for (var i in cData) {
					aData.push(cData[i]);
				}

				jqTds[0].innerHTML = '<input type="text" class="form-control input-small" value="' + aData[0] + '">';
				jqTds[1].innerHTML = '<input type="text" class="form-control input-small party_gst" value="' + aData[1] + '">';
				jqTds[2].innerHTML = '<textarea class="form-control input-large" rows="2"> ' + aData[2]/*.replace(/\n/g, '<br/>')*/ + '</textarea>';
				jqTds[3].innerHTML = '<input type="text" class="form-control input-small" value="' + aData[3] + '">';
				jqTds[4].innerHTML = '<input type="text" class="form-control input-medium" value="' + aData[4] + '">';
				jqTds[5].innerHTML = '<input type="text" class="form-control input-small party_mobile" value="' + aData[5] + '">';
				jqTds[10].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a><a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';

				vendors_handleInputMask();
			}

			function saveRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var jqInputs = $('input', nRow);
				var jqTextarea = $('textarea', nRow);

				$party_name = jqInputs[0].value;
				$party_gst = jqInputs[1].value;
				$party_address = jqTextarea[0].value;
				$party_poc = jqInputs[2].value;
				$party_email = jqInputs[3].value;
				$party_mobile = jqInputs[4].value;

				var formData = new FormData();
				formData.append('action', 'update_parties');
				formData.append('party_id', aData.party_id);
				formData.append('party_name', $party_name);
				formData.append('party_gst', $party_gst);
				formData.append('party_address', $party_address.trim());
				formData.append('party_poc', $party_poc);
				formData.append('party_email', $party_email);
				formData.append('party_mobile', $party_mobile);

				aData = { // SET NEW DATA TO TABLE
					'party_name': $party_name,
					'party_gst': $party_gst,
					'party_address': $party_address.trim(),
					'party_poc': $party_poc,
					'party_email': $party_email,
					'party_mobile': $party_mobile,
					'party_supplier': aData.party_supplier,
					'party_ams': aData.party_ams,
					'is_active': aData.is_active,
					'party_id': aData.party_id,
					'pending_amount': aData.pending_amount,
				};
				oTable.row(nRow).data(aData);
				$(".bsSwitch").bootstrapSwitch('disabled', false);

				// Submit from and display alert but no refresh
				var s = submitForm(formData);
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Customer Update', s.msg);
				} else {
					UIToastr.init('error', 'Customer Update', 'Error Processing Request. Please try again later.');
				}
			}

			function cancelEditRow(oTable, nRow) {
				// var jqInputs = $('input', nRow);
				// oTable.fnUpdate(jqInputs[0].value, nRow, 0, false);
				// oTable.fnUpdate(jqInputs[1].value, nRow, 1, false);
				// oTable.fnUpdate(jqInputs[2].value, nRow, 2, false);
				// oTable.fnUpdate('<a class="edit btn btn-default btn-xs purple" href="">Edit</a>', nRow, 3, false);
				oTable.draw();
			}

			var nEditing = null;
			var nNew = false;

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();

					// var formData = new FormData();
					// formData.append('action', 'delete_product');
					// formData.append('pid', aData.pid);

					// var s = submitForm(formData);
					// if (s.type == "success" || s.type == "error"){
					oTable.row($(this).parents('tr')).remove().draw();
					UIToastr.init("success", 'Customer Update', "Successfull deleted customer");
					// UIToastr.init(s.type, 'Product brand', s.msg);
					// } else {
					// 	UIToastr.init('error', 'Product brand', 'Error Processing Request! Please try again later');
					// }
				}
			});

			table.on('click', '.cancel', function (e) {
				e.preventDefault();

				if (nNew) {
					oTable.row(nEditing).remove().draw();
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

	var vendors_handleValidation = function () {
		var form = $('#add-party');
		var error = $('.alert-danger', form);
		var success = $('.alert-success', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				party_name: {
					minlength: 2,
					required: true
				},
				party_gst: {
					minlength: 15,
					maxlength: 15,
					required: true
				},
				party_address: {
					required: true
				},
				party_poc: {
					required: true
				},
				party_email: {
					required: true,
					email: true
				},
				party_mobile: {
					minlength: 10,
					maxlength: 10,
					required: true,
					digits: true
				},
				party_ams_rates_por: {
					required: true,
					number: true
				},
				party_ams_rates_sor: {
					required: true,
					digits: true
				},
				party_type: {
					required: true,
					minlength: 1
				},
			},

			messages: { // custom messages for radio buttons and checkboxes
				party_type: {
					required: "Please select a Firm type",
					minlength: $.validator.format("Please select at least {0} types of Firm")
				}
			},

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

			submitHandler: function (form) {
				// success.show();
				error.hide();

				$('.form-actions .btn-success', form).attr('disabled', true);
				$('.form-actions i', form).addClass('fa fa-sync fa-spin');

				$party_supplier = $('input[name="party_type_supplier"]:checked', form).val()
				$party_supplier = typeof $party_supplier !== 'undefined' ? $party_supplier : 0;
				$party_ams = $('input[name="party_type_ams"]:checked', form).val()
				$party_ams = typeof $party_ams !== 'undefined' ? $party_ams : 0;
				$party_ams_rates_por = $('input[name="party_ams_rates_por"]', form).val()
				$party_ams_rates_sor = $('input[name="party_ams_rates_sor"]', form).val()
				$party_ams_rates = JSON.stringify([{ startDate: 'start_date', endDate: '2099-12-31', por: $party_ams_rates_por, sor: $party_ams_rates_sor }]);

				var formData = new FormData();
				formData.append('action', 'add_parties');
				formData.append('party_name', $('#party_name', form).val());
				formData.append('party_gst', $('#party_gst', form).val());
				formData.append('party_address', $('#party_address', form).val());
				formData.append('party_poc', $('#party_poc', form).val());
				formData.append('party_email', $('#party_email', form).val());
				formData.append('party_mobile', $('#party_mobile', form).val());
				formData.append('party_customer', 0);
				formData.append('party_distributor', 0);
				formData.append('party_carrier', 0);
				formData.append('party_supplier', $party_supplier);
				formData.append('party_ams_rates', $party_ams_rates)
				formData.append('party_ams', $party_ams);
				formData.append('client_id', client_id);

				var s = submitForm(formData, true);
				if (s.type == 'success') {
					$('#add_parties').modal('hide');
					UIToastr.init('success', 'Add Party', 'Successfull added new party.');
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
					$('#reload-parties').trigger('click');
				} else if (s.type == 'error') {
					UIToastr.init('error', 'Add Party', 'Error adding new party: ' + s.message);
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
				} else {
					UIToastr.init('error', 'Add Party', 'Error Processing your Request. Please try again later!!! ' + s.message);
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions i', form).removeClass('fa-sync fa-spin');
				}
			}
		});
	};

	var vendors_handleBootstrapSwitch = function () {
		if (!$().bootstrapSwitch) {
			return;
		}
		// $('.distributor').bootstrapSwitch();
		// $('.customer').bootstrapSwitch();
		// $('.supplier').bootstrapSwitch();
		// $('.is_active').bootstrapSwitch();
		$(".bsSwitch").bootstrapSwitch();

		$('.dataTable').on('switchChange.bootstrapSwitch', '.bsSwitch', function (e, data) {
			NProgress.start();
			var party_id = $(this).data('party_id');
			var key = $(this).data('type');

			if ($(this).is(":checked")) {
				is_checked = 1;
			} else {
				is_checked = 0;
			}
			$(".bsSwitch").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_parties&party_id=" + party_id + "&" + key + "=" + is_checked,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'Party Type', 'Error Processing your Request!!! Please retry later.');
					} else {
						UIToastr.init('success', 'Party Type', 'Party type successfully updated.');
					}
					$(".bsSwitch").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'Party Type Status', 'Error Processing your Request!!! Please retry later.');
				}
			});
		});
	};

	var vendors_handleInputMask = function () {
		$(".party_gst").inputmask({
			"mask": "99AAAAA9999A9**",
			definitions: {
				"*": {
					casing: "upper"
				}
			}
		});

		$(".party_mobile").inputmask({
			"mask": "9999999999",
		});
	};

	var parties_handleBootstrapSwitch = function () {
		if (!$().bootstrapSwitch) {
			return;
		}
		// $('.distributor').bootstrapSwitch();
		// $('.customer').bootstrapSwitch();
		// $('.supplier').bootstrapSwitch();
		// $('.is_active').bootstrapSwitch();
		$(".bsSwitch").bootstrapSwitch();

		$('.dataTable').on('switchChange.bootstrapSwitch', '.bsSwitch', function (e, data) {
			NProgress.start();
			var party_id = $(this).data('party_id');
			var key = $(this).data('type');

			if ($(this).is(":checked")) {
				is_checked = 1;
			} else {
				is_checked = 0;
			}
			$(".bsSwitch").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_parties&party_id=" + party_id + "&" + key + "=" + is_checked,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'Party Type', 'Error Processing your Request!!! Please retry later.');
					} else {
						UIToastr.init('success', 'Party Type', 'Party type successfully updated.');
					}
					$(".bsSwitch").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'Party Type Status', 'Error Processing your Request!!! Please retry later.');
				}
			});
		});
	};

	var parties_handleInputMask = function () {
		$(".party_gst").inputmask({
			"mask": "99AAAAA9999A9**",
			definitions: {
				"*": {
					casing: "upper"
				}
			}
		});

		$(".party_mobile").inputmask({
			"mask": "9999999999",
		});
	};

	return {
		//main function to initiate the module
		init: function ($type) {
			switch ($type) {
				case 'customers':
					customers_handleTable();
					customers_handleValidation();
					break;

				case 'suppliers':
					suppliers_handleTable();
					suppliers_handleValidation();
					break;

				case 'vendors':
					vendors_handleTable();
					vendors_handleValidation();
					vendors_handleBootstrapSwitch();
					vendors_handleInputMask();
					break;
			}
			parties_handleBootstrapSwitch();
			parties_handleInputMask();
		}
	};
}();