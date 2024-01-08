var Products = function () {
	// Add Inner SKU - Combo
	var count = 2;
	$('#add-inner-sku').click(function () {
		count++;
		var s_options = '<option value=""></option>';
		var skus = all_skus.reverse();
		$.each(skus, function (k, v) {
			s_options += "<option value=" + v.key + ">" + v.value.replace('pid-', '') + "</option>";
		});
		$select = '<div class="form-group inner_sku_container"><label class="col-sm-4 control-label">Inner SKU<span class="required"> * </span></label><div class="col-sm-8"><div class="input-group"><select class="form-control select2me input-medium inner_sku" id="inner_sku_' + count + '" name="inner_sku_' + count + '">' + s_options + '</select></div></div></div>'
		$($select).insertAfter("div.inner_sku_container:last");
		$("#inner_sku_" + count).select2({
			placeholder: "Select",
			allowClear: true
		});
		$("#inner_sku_" + count).rules("add", "required");
	});

	// Remove Inner SKU - Combo
	$('#remove-inner-sku').click(function () {
		if (count > 2) {
			$("div.inner_sku_container:last").remove();
			count--;
		}
	});

	// Create Sample File
	$('#create_sample_file').click(function (e) {
		e.preventDefault();
		var form = document.createElement("form");
		form.setAttribute("method", "post");
		form.setAttribute("action", "ajax_load.php");
		form.setAttribute("target", "_blank");

		form._submit_function_ = form.submit;

		var params = {
			"action": "create_sample_file",
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
		form._submit_function_();
		form.submit();
	});

	// Submit for with mutlipart data containing Image
	function submitForm(formData, $type) {
		$ret = "";
		$.ajax({
			url: "ajax_load.php",
			cache: false,
			type: $type,
			data: formData,
			contentType: false,
			processData: false,
			mimeType: "multipart/form-data",
			async: false,
			success: function (s) {
				$ret = $.parseJSON(s);
			},
			error: function (e) {
				// NProgress.done(true);
				alert('Error Processing your Request!!');
			}
		});
		return $ret;
	};

	var product_handleTable = function () {
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

		var table = $('#editable_products');
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
				url: "ajax_load.php?action=get_products&client_id=" + client_id,
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "thumb_image_url",
					title: "Image",
					columnFilter: ''
				}, {
					data: "sku",
					title: "SKU",
					columnFilter: 'inputFilter'
				}, {
					data: "cost_price",
					title: "Average Price",
					columnFilter: 'rangeFilter'
				}, {
					data: "selling_price",
					title: "Selling Price",
					columnFilter: 'rangeFilter'
				}, {
					data: "retail_price",
					title: "Retail Price",
					columnFilter: 'rangeFilter'
				}, {
					data: "family_price",
					title: "Family Price",
					columnFilter: 'rangeFilter'
				}, {
					data: "categoryName",
					title: "Category",
					columnFilter: 'selectFilter'
				}, {
					data: "brandName",
					title: "Brand",
					columnFilter: 'selectFilter'
					/*}, {
						data: "current_stock",
						title: "Current Stock",
						columnFilter: 'rangeFilter'
					}, {
						data: "saleable_stock",
						title: "Saleble Stock",
						columnFilter: 'rangeFilter'
					}, {
						data: "reserved_stock",
						title: "Reserved Stock",
						columnFilter: 'rangeFilter'
					}, {
						data: "returning_stock",
						title: "Returning Stock",
						columnFilter: 'rangeFilter'
					}, {
						data: "qcReject_stock",
						title: "QC Reject Stock",
						columnFilter: 'rangeFilter'
					}, {
						data: "damaged_stock",
						title: "Damaged Stock",
						columnFilter: 'rangeFilter'*/
				}, {
					data: "is_stock_offline",
					title: "Sold Offline?",
					className: 'in_stock_rtl',
					columnFilter: ''
				}, {
					data: "in_stock_rtl",
					title: "In Stock Offline?",
					className: 'in_stock_rtl',
					columnFilter: ''
				}, {
					data: "in_stock",
					title: "In Stock?",
					columnFilter: ''
				}, {
					data: "is_active",
					title: "Is Active?",
					columnFilter: ''
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[1, 'asc']
			],
			columnDefs: [
				{
					targets: 0,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						$return = '<img src="' + image_url + '/uploads/products/' + e.thumb_image_url + '" onerror="this.onerror=null;this.src=\'https://via.placeholder.com/100x100\';" height="100" width="100" />';
						return $return;
					}
				}, {
					// targets: [11],
					targets: [8],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="is_stock_offline" data-pid="' + e.pid + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					// targets: [11],
					targets: [9],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="in_stock_rtl" data-pid="' + e.pid + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					// targets: [12],
					targets: [10],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="in_stock" data-pid="' + e.pid + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					// targets: [13],
					targets: [11],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="is_active" data-pid="' + e.pid + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function (oSettings) {
				// Initialize checkbos for enable/disable user
				$("[class='in_stock']").bootstrapSwitch();
				$("[class='is_active']").bootstrapSwitch();
				$("[class='in_stock_rtl']").bootstrapSwitch();
				$("[class='is_stock_offline']").bootstrapSwitch();
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

		var editableTable = function () {

			function restoreRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				oTable.row(nRow).data(aData);
				oTable.draw();
			}

			function editRow(oTable, nRow) {
				var cData = oTable.row(nRow).data();
				var jqTds = $('>td', nRow);
				var image = "";
				var index_offset = 7;
				var aData = [];
				for (var i in cData) {
					aData.push(cData[i]);
				}

				jqTds[0].innerHTML = '<img src="../uploads/products/' + aData[0] + '" onerror="this.onerror=null;this.src=\'https://via.placeholder.com/100x100\';" height="100" width="100" /><input type="file" class="form-control" value="" accept="image/*">';
				jqTds[1].innerHTML = '<input type="text" class="form-control" value="' + aData[1] + '">';
				jqTds[3].innerHTML = '<input type="text" class="form-control" value="' + aData[2] + '">';
				jqTds[4].innerHTML = '<input type="text" class="form-control" value="' + aData[3] + '">';
				jqTds[5].innerHTML = '<input type="text" class="form-control" value="' + aData[4] + '">';
				jqTds[6].innerHTML = '<select id="categories_selectMenu" class="selection form-control"></select>';
				jqTds[7].innerHTML = '<select id="brands_selectMenu" class="selection form-control"></select>';
				jqTds[12].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a><a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';

				$('#categories_selectMenu').append('<option value=""></option');
				for (i = 0; i < categories.opt.length; i++) {
					var selected = "";
					if (aData[5] == categories.opt[i].categoryName) {
						selected = "selected = 'selected'";
					}
					$('#categories_selectMenu').append('<option ' + selected + ' value=' + categories.opt[i].catid + '>' + categories.opt[i].categoryName + '</option>');
				}

				$('#brands_selectMenu').append('<option value=""></option');
				for (i = 0; i < brands.opt.length; i++) {
					var selected = "";
					if (aData[6] == brands.opt[i].brandName) {
						selected = "selected = 'selected'";
					}
					$('#brands_selectMenu').append('<option ' + selected + ' value=' + brands.opt[i].brandid + '>' + brands.opt[i].brandName + '</option>');
				}

				// Initialize select2me
				$('.selection').select2({
					placeholder: "Select",
					allowClear: true
				});

				$("[class='in_stock']").bootstrapSwitch('disabled', true);
				$("[class='is_active']").bootstrapSwitch('disabled', true);
				$("[class='in_stock_rtl']").bootstrapSwitch('disabled', true);
				$("[class='is_stock_offline']").bootstrapSwitch('disabled', true);
			}

			function saveRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var jqInputs = $('input', nRow);
				var jqSelect = $('select', nRow);

				var formData = new FormData();
				formData.append('action', 'edit_product');
				formData.append('pid', aData.pid);

				if (jqInputs.prop('files').length) {
					formData.append('thumb_image_url', jqInputs[0].files[0]);
					extension = jqInputs[0].files[0].name.replace(/^.*\./, '');
					aData.thumb_image_url = "product-" + aData.pid + "." + extension;
				}

				formData.append('category', jqSelect[0].value);
				aData.categoryName = $(jqSelect[0]).find('option:selected').text();
				if (jqSelect[1].value != "") {
					formData.append('brand', jqSelect[1].value);
					aData.brandName = $(jqSelect[1]).find('option:selected').text();
				} else {
					formData.append('brand', 0);
					aData.brandName = "";
				}

				formData.append('sku', jqInputs[1].value);
				aData.sku = jqInputs[1].value;
				formData.append('selling_price', jqInputs[2].value);
				formData.append('retail_price', jqInputs[3].value);
				formData.append('family_price', jqInputs[4].value);
				aData.selling_price = jqInputs[2].value;
				aData.retail_price = jqInputs[3].value;
				aData.family_price = jqInputs[4].value;

				oTable.row(nRow).data(aData); // SET NEW DATA TO TABLE

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Product Update', s.msg);
				} else {
					UIToastr.init('error', 'Product Update', 'Error Processing Request. Please try again later.');
				}
			}

			function cancelEditRow(oTable, nRow) {
				var jqInputs = $('input', nRow);
				oTable.fnUpdate(jqInputs[0].value, nRow, 0, false);
				oTable.fnUpdate(jqInputs[1].value, nRow, 1, false);
				oTable.fnUpdate(jqInputs[2].value, nRow, 2, false);
				oTable.fnUpdate(jqInputs[3].value, nRow, 3, false);
				oTable.fnUpdate(jqInputs[4].value, nRow, 4, false);
				oTable.fnUpdate('<a class="edit btn btn-default btn-xs purple" href="">Edit</a>', nRow, 3, false);
				oTable.fnDraw();
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
					UIToastr.init("success", 'Product', "Successfull deleted product");
					// UIToastr.init(s.type, 'Product brand', s.msg);
					// } else {
					// 	UIToastr.init('error', 'Product brand', 'Error Processing Request! Please try again later');
					// }
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

	var product_handleValidation = function () {
		var form = $('#add-products');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				thumb_image_url: {
					required: true,
				},
				sku: {
					required: true,
				},
				selling_price: {
					required: true,
				},
				retail_price: {
					required: true,
				},
				family_price: {
					required: true,
				},
				category: {
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
				if (element.attr("name") == "thumb_image_url") {
					error.appendTo("#thumb_image_url_error");
				} else {
					error.appendTo(element.parent("div"));
				}
			},

			submitHandler: function (form) {
				error.hide();
				$('.form-actions .btn-submit', form).attr('disabled', true);
				$('.form-actions .btn-submit i', form).addClass('fa fa-sync fa-spin');

				var sku = $("#sku").val();
				var selling_price = $("#selling_price").val();
				var retail_price = $("#retail_price").val();
				var family_price = $("#family_price").val();
				var brand = $("#brand").val();
				var category = $("#category").val();
				var in_stock = $('#in_stock').bootstrapSwitch('state');
				var is_stock_manged = $('#is_stock_manged').bootstrapSwitch('state');

				var formData = new FormData();
				formData.append('action', 'add_product');
				formData.append('client_id', client_id);
				formData.append('thumb_image_url', $('#thumb_image_url')[0].files[0]);
				formData.append('sku', sku);
				formData.append('selling_price', selling_price);
				formData.append('retail_price', retail_price);
				formData.append('family_price', family_price);
				formData.append('brand', brand);
				formData.append('category', category);
				formData.append('in_stock', in_stock);
				formData.append('is_stock_manged', is_stock_manged);

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Add Product', s.msg);
					if (s.type == "success") {
						$('#add_product').modal('hide');
						$('.reload').trigger('click');
					}
				} else {
					UIToastr.init('error', 'Add Product', 'Error Processing Request. Please try again later.');
				}
				$('.form-actions .btn-success', form).attr('disabled', false);
				$('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');
			}
		});
	};

	var productInactive_handleActivation = function () {
		$('#view_inacitive').on('show.bs.modal', function (e) {
			var el = $('#inactive-products');
			App.blockUI({ target: el });

			$.ajax({
				url: "ajax_load.php?action=get_inactive_products",
				cache: false,
				type: "GET",
				contentType: false,
				processData: false,
				async: true,
				success: function (s) {
					var s = $.parseJSON(s);
					$('.inactive_skus').empty().append('<option value=""></option');
					$.each(s, function (k, v) {
						$('.inactive_skus').append('<option value=' + v.pid + '>' + v.sku + '</option>');
					});

					// Initialize select2me
					$('.inactive_skus').select2("destroy").select2({
						placeholder: "Select Inactive SKU",
						allowClear: true
					});

					App.unblockUI(el);
				},
				error: function (e) {
					UIToastr.init('error', 'Inactive Product', 'Error Processing Request. Please try again later.');
				}
			});
		});
	};

	var productInactive_handleValidation = function () {
		var form = $('#inactive-products');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				inactive_sku: {
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
				$('.form-actions .btn-submit', form).attr('disabled', true);
				$('.form-actions .btn-submit i', form).addClass('fa fa-sync fa-spin');

				var formData = new FormData();
				formData.append('action', 'activate_product');
				formData.append('pid', $('.inactive_skus option:selected').val());

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						UIToastr.init(s.type, 'Inactive Product', s.msg);
						if (s.type == "success") {
							$('#view_inacitive').modal('hide');
							$('.reload').trigger('click');
						}
					} else {
						UIToastr.init('error', 'Inactive Product', 'Error Processing Request. Please try again later.');
					}
					$('.form-actions .btn-success', form).attr('disabled', false);
					$('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');
				}, 50);
			}
		});
	};

	var productCombo_handleTable = function () {
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

		var table = $('#editable_products_combo');
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
				url: "ajax_load.php?action=products_combo&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "thumb_image_url",
					title: "Image",
					columnFilter: ''
				}, {
					data: "sku",
					title: "SKU",
					columnFilter: 'inputFilter'
				}, {
					data: "inner_sku",
					title: "Inner SKU",
					columnFilter: 'inputFilter'
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [
				[1, 'asc']
			],
			columnDefs: [
				{
					targets: 0,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						$return = '<img src="../uploads/products/' + e.thumb_image_url + '" onerror="this.onerror=null;this.src=\'https://via.placeholder.com/100x100\';" height="100" width="100" />';
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
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

		var editableTable = function () {

			function restoreRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				oTable.row(nRow).data(aData);
				oTable.draw();
			}

			function editRow(oTable, nRow) {
				var cData = oTable.row(nRow).data();
				var jqTds = $('>td', nRow);
				var aData = [];
				for (var i in cData) {
					aData.push(cData[i]);
				}

				jqTds[0].innerHTML = '<img src="../uploads/products/' + aData[0] + '" onerror="this.onerror=null;this.src=\'https://via.placeholder.com/100x100\';" height="100" width="100" /><input type="file" class="form-control" value="" accept="image/*">';
				jqTds[1].innerHTML = '<input type="text" class="form-control" value="' + aData[1] + '">';
				// jqTds[2].innerHTML = aData[2];
				jqTds[3].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a><a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';
			}

			function saveRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var jqInputs = $('input', nRow);

				var formData = new FormData();
				formData.append('action', 'edit_product_combo');
				formData.append('cid', aData.cid);

				if (jqInputs.prop('files').length) {
					formData.append('thumb_image_url', jqInputs[0].files[0]);
					extension = jqInputs[0].files[0].name.replace(/^.*\./, '');
					aData.thumb_image_url = "product-" + aData.pid + "." + extension;
				}

				formData.append('sku', jqInputs[1].value);
				aData.sku = jqInputs[1].value;

				oTable.row(nRow).data(aData); // SET NEW DATA TO TABLE

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Product Update', s.msg);
				} else {
					UIToastr.init('error', 'Product Update', 'Error Processing Request. Please try again later.');
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

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();

					// var formData = new FormData();
					// formData.append('action', 'delete_product_combo');
					// formData.append('cid', aData.cid);

					// var s = submitForm(formData);
					// if (s.type == "success" || s.type == "error"){
					oTable.row($(this).parents('tr')).remove().draw();
					UIToastr.init("success", 'Products Combo', "Successfull deleted Combo Products");
					// UIToastr.init(s.type, 'Product brand', s.msg);
					// } else {
					// 	UIToastr.init('error', 'Product brand', 'Error Processing Request! Please try again later');
					// }
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

	var productCombo_handleValidation = function () {
		var form = $('#add-products-combo');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				thumb_image_url: {
					required: true,
				},
				sku: {
					required: true,
				},
				inner_sku_1: {
					required: true,
				},
				inner_sku_2: {
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
				if (element.attr("name") == "thumb_image_url") {
					error.appendTo("#thumb_image_url_error");
				} else {
					error.appendTo(element.parent("div"));
				}
			},

			submitHandler: function (form) {
				error.hide();
				var sku = $("#sku").val();
				var formData = new FormData();
				formData.append('action', 'add_product_combo');
				formData.append('thumb_image_url', $('#thumb_image_url')[0].files[0]);
				formData.append('sku', sku);
				var innerSkus = [];
				for (var i = 0; i < count; i++) {
					selector = i + 1;
					innerSkus.push($('select#inner_sku_' + selector + ' option:selected').val());
				};
				formData.append('inner_sku', innerSkus);

				var s = submitForm(formData, "POST");
				if (s.type == 'Success') {
					$('#add_product_combo').modal('hide');
					$('.reload').trigger('click');
				} else {
					error.text('Error Processing your Request!! ' + s.message).show();
				}
			}
		});
	};

	var productCategory_handleTable = function () {
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

		var table = $('#editable_products_category');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=products_category&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "categoryName",
					title: "Category Name",
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
			columnDefs: [
				{
					targets: -1,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
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
				$('.select2me').val("").trigger('change');
				$('.filter-cancel').trigger('click');
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
				formData.append('action', 'edit_product_category');
				for (var k in aData) {
					formData.append(k, aData[k]);
				}

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Category Update', s.msg);
				} else {
					UIToastr.init('error', 'Category Update', 'Error Processing Request. Please try again later.');
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

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.row(nRow).data();

					// var formData = new FormData();
					// formData.append('action', 'delete_category');
					// formData.append('catid', aData.catid);

					// var s = submitForm(formData);
					// if (s.type == "success" || s.type == "error"){
					oTable.row($(this).parents('tr')).remove().draw();
					UIToastr.init("success", 'Product Category', "Successfull deleted Category");
					// UIToastr.init(s.type, 'Product brand', s.msg);
					// } else {
					// 	UIToastr.init('error', 'Product brand', 'Error Processing Request! Please try again later');
					// }
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

	var productCategory_handleValidation = function () {
		var form = $('#add-products-category');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				categoryName: {
					required: true,
				},
				fk_categoryCom: {
					required: true,
				},
				az_categoryCom: {
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
				if (element.attr("name") == "fk_categoryCom" || element.attr("name") == "az_categoryCom") {
					error.appendTo(element.parent("div").parent("div"));
				} else {
					error.appendTo(element.parent("div"));
				}
			},

			submitHandler: function (form) {
				error.hide();
				var categoryName = $("#categoryName").val();
				var formData = new FormData();
				formData.append('action', 'add_product_category');
				formData.append('categoryName', categoryName);
				formData.append('client_id', client_id);

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Product Category', s.msg);
					if (s.type == "success") {
						$('#add_product_category').modal('hide');
						$('#reload-products-category').trigger('click');
					}
				} else {
					UIToastr.init('error', 'Product Category', 'Error Processing Request. Please try again later.');
				}
			}
		});
	};

	var productBrand_handleTable = function () {
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

		var table = $('#editable_products_brand');
		var oTable;
		oTable = table.DataTable({
			responsive: true,
			dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
			lengthMenu: [[20, 50, 100, -1], [20, 50, 100, "All"]], // change per page values here
			pageLength: 50,
			language: {
				lengthMenu: "Display _MENU_"
			},
			searchDelay: 500,
			processing: !0,
			// serverSide: !0,
			ajax: {
				url: "ajax_load.php?action=products_brand&client_id=" + client_id + "&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "brandName",
					title: "Brand Name",
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
			columnDefs: [
				{
					targets: -1,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						// $return = 	'<a class="btn btn-default btn-xs create_grn" href="purchase_new.php?po_id='+e.po_id+'&type='+e.Status+'" title="Edit PO"><i class="fa fa-edit"></i></a> '+
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
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
				formData.append('action', 'edit_product_brand');
				for (var k in aData) {
					formData.append(k, aData[k]);
				}

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Brand Update', s.msg);
				} else {
					UIToastr.init('error', 'Brand Update', 'Error Processing Request. Please try again later.');
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

			table.on('click', '.delete', function (e) {
				e.preventDefault();

				if (confirm("Are you sure to delete this row ?")) {
					var nRow = $(this).parents('tr')[0];
					var aData = oTable.fnGetData(nRow);

					var formData = new FormData();
					formData.append('action', 'delete_brand');
					formData.append('brandid', aData[0]);

					var s = submitForm(formData, "POST");
					if (s.type == "success" || s.type == "error") {
						oTable.fnDeleteRow(nRow);
						UIToastr.init(s.type, 'Product brand', s.msg);
					} else {
						UIToastr.init('error', 'Product brand', 'Error Processing Request! Please try again later');
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

	var productBrand_handleValidation = function () {
		var form = $('#add-products-brand');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				brandName: {
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
				var brandName = $("#brandName").val();
				var formData = new FormData();
				formData.append('action', 'add_product_brand');
				formData.append('brandName', brandName);
				formData.append('client_id', client_id);

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Product brand', s.msg);
					if (s.type == "success") {
						$('#add_product_brand').modal('hide');
						$('#reload-products-brand').trigger('click');
					}
				} else {
					UIToastr.init('error', 'Product brand', 'Error Processing Request. Please try again later.');
				}
			}
		});
	};

	var productAlias_handleTable = function () {
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

		var table = $('#editable_products_alias');
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
				url: "ajax_load.php?action=products_alias&token=" + new Date().getTime(),
				cache: false,
				type: "GET",
			},
			columns: [
				{
					data: "parentSku",
					title: "Parent SKU",
					columnFilter: 'inputFilter'
				}, {
					data: "sku",
					title: "SKU",
					columnFilter: 'inputFilter'
				}, {
					data: "mp_id",
					title: "FSN/ASIN",
					columnFilter: 'selectFilter'
				}, {
					data: "categoryName",
					title: "Category",
					columnFilter: 'selectFilter'
				}, {
					data: "marketplace",
					title: "Marketplace",
					columnFilter: 'selectFilter'
				}, {
					data: "account_name",
					title: "Account",
					columnFilter: 'selectFilter'
				}, {
					data: "auto_update",
					title: "Auto Update",
					columnFilter: ''
				}, {
					data: "fulfilled",
					title: "Fulfilled",
					columnFilter: ''
				}, {
					data: "Actions",
					title: "Actions",
					columnFilter: 'actionFilter',
					responsivePriority: -1
				},
			],
			order: [[0, 'asc']],
			// orderFixed: [[0, 'asc']],
			rowGroup: {
				dataSrc: 'parentSku'
			},
			columnDefs: [
				{
					targets: [2],
					width: '170px',
					render: function (a, t, e, s) {
						var url = e.marketplace.toLowerCase() == "flipkart" ? fk_url.replace('FSN', a) : az_url.replace('ASIN', a);
						$return = '<span class="mp_id">' + a + '</span><a class="fa fa-external-link-alt pull-right" href="' + url + '" target="_blank"></a>';
						return $return;
					}
				}, {
					targets: [6],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="auto_update" disabled data-pid="' + e.pid + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: [7],
					orderable: !1,
					render: function (a, t, e, s) {
						var checked = a ? "checked" : "";
						$return = '<input type="checkbox" ' + checked + ' class="fulfilled" data-pid="' + e.pid + '" data-size="small" data-on-text="&nbsp;Yes&nbsp;&nbsp;" data-off-text="&nbsp;No&nbsp;">';
						return $return;
					}
				}, {
					targets: -1,
					orderable: !1,
					width: "10%",
					render: function (a, t, e, s) {
						$return = '<a class="edit btn btn-default btn-xs purple" href="" title="Edit"><i class="fa fa-edit"></i></a> ' +
							'<a class="delete btn btn-default btn-xs purple" href="" title="Delete"><i class="fa fa-trash"></i></a>';
						return $return;
					}
				}
			],
			fnDrawCallback: function (oSettings) {
				// Initialize checkbox for enable/disable user
				$("[class='auto_update']").bootstrapSwitch();
				$("[class='fulfilled']").bootstrapSwitch();
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
			// ROW GROUPING
			var asc = true;
			table.find('tbody').on('click', 'tr.group-start', function () {
				console.log('tbody');
				asc = !asc;
				oTable.column('parentSku').order(asc === true ? 'asc' : 'desc').draw();
			});

			// Reload DataTable
			$('.reload').off().on('click', function (e) {
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
				$("[class='auto_update']").bootstrapSwitch('disabled', true);
				$("[class='fulfilled']").bootstrapSwitch('disabled', true);
				$('select.selection').select2('destroy');
				var cData = oTable.row(nRow).data();
				var jqTds = $('>td', nRow);
				var aData = [];
				for (var i in cData) {
					aData.push(cData[i]);
				}

				jqTds[0].innerHTML = '<select id="all_skus" class="input-medium selection form-control"></select>';
				jqTds[1].innerHTML = '<input type="text" class="form-control input-medium" value="' + aData[1] + '">';
				jqTds[2].innerHTML = '<input type="text" class="form-control input-medium" value="' + aData[2].replace(/<[^>]*>/g, "") + '">';
				// 3 - Category
				jqTds[4].innerHTML = '<select id="marketplaceMenu" class="input-small selection form-control"></select>'
				jqTds[5].innerHTML = '<select id="accountMenu" class="input-small selection form-control"></select>';
				// 6, 7 - Auto Update, Fulfilled
				jqTds[8].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a><a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';

				// SKU OPTIONS
				var skus = all_skus.reverse();
				var s_options = '';
				$.each(skus, function (k, v) {
					var selected = "";
					if (aData[0] == v.value) {
						selected = "selected = 'selected'";
					}
					s_options += "<option " + selected + "value=" + v.key + ">" + v.value + "</option>";
				});
				$('#all_skus').append(s_options);

				// MARKETPLACE AND ACCOUNT OPTIONS
				var a_options = new Array();
				var m_options = '';
				var current_marketplace = aData[4].toLowerCase();
				$.each(accounts, function (account_k, account) {
					a_options[account_k] = "";
					var selected = "";
					if (current_marketplace == account_k) {
						selected = "selected = 'selected'";
					}
					m_options += "<option " + selected + "value=" + account_k + ">" + account_k.charAt(0).toUpperCase() + account_k.slice(1) + "</option>";

					$.each(account, function (k, v) {
						var selected = "";
						if (aData[5] == v.account_name) {
							selected = "selected = 'selected'";
						}
						a_options[account_k] += "<option " + selected + "value=" + v.account_id + ">" + v.account_name + "</option>";
					});
				});
				$('#accountMenu').append(a_options[current_marketplace]);
				$('#marketplaceMenu').append(m_options);

				var $marketplace = $('#marketplaceMenu');
				var $accountMenu = $('#accountMenu');
				$marketplace.change(function () {
					$accountMenu.select2("val", "");
					$accountMenu.empty().append(a_options[$(this).val()]);
				});

				// Initialize select2me
				$('.selection').select2({
					placeholder: "Select",
					allowClear: true
				});

				$("[class='auto_update']").bootstrapSwitch('disabled', true);
				$("[class='fulfilled']").bootstrapSwitch('disabled', true);

				// productAlias_handleSelect();
			}

			function saveRow(oTable, nRow) {
				var aData = oTable.row(nRow).data();
				var jqInputs = $('input:not([class^=select2])', nRow);
				var jqSelect = $('select', nRow);

				$parent_sku = jqSelect[0].value;
				$parent_sku_value = $('#all_skus option:selected').text();
				$sku = jqInputs[0].value;
				$pm_id = jqInputs[1].value;
				$marketplace = jqSelect[1].value;
				$marketplace_value = $('#marketplaceMenu option:selected').text();
				$account = jqSelect[2].value;
				$account_value = $('#accountMenu option:selected').text();
				$alias_id = aData.alias_id;

				aData = { // SET NEW DATA TO TABLE
					'parentSku': $parent_sku_value,
					'sku': $sku,
					'mp_id': $pm_id,
					'categoryName': aData.categoryName,
					'marketplace': $marketplace_value,
					'account_name': $account_value,
					'auto_update': aData.auto_update,
					'fulfilled': aData.fulfilled,
					'price_now': aData.price_now,
					'alias_id': $alias_id,
				};
				oTable.row(nRow).data(aData);

				// // Submit from and display alert but no refresh
				var formData = new FormData();
				formData.append('action', 'edit_product_alias');
				formData.append('alias_id', $alias_id);
				formData.append('parent_sku', $parent_sku);
				formData.append('sku', $sku);
				formData.append('pm_id', $pm_id);
				formData.append('marketplace', $marketplace);
				formData.append('account_id', $account);

				var s = submitForm(formData, "POST");
				if (s.type == "success" || s.type == "error") {
					UIToastr.init(s.type, 'Alias Update', s.msg);
				} else {
					UIToastr.init('error', 'Alias Update', 'Error Processing Request. Please try again later.');
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
					UIToastr.init("success", 'Product', "Successfull deleted product");
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

	var productAlias_handleValidation = function () {
		var form = $('#add-products-alias');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				parent_sku: {
					required: true,
				},
				sku: {
					required: true,
				},
				mp_id: {
					required: true,
				},
				marketplace: {
					required: true,
				},
				account_id: {
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
				var parent_sku = $("#parent_sku").val();
				var sku = $("#sku").val();
				var mp_id = $("#mp_id").val();
				var marketplace = $(".marketplace").find(':selected').val();
				var account_id = $(".account_id").find(':selected').val();

				var formData = new FormData();
				formData.append('action', 'add_product_alias');
				formData.append('parent_sku', parent_sku);
				formData.append('sku', sku);
				formData.append('mp_id', mp_id);
				formData.append('marketplace', marketplace);
				formData.append('account_id', account_id);

				var s = submitForm(formData, "POST");

				if (s.type == 'success') {
					$('#add_product_alias').modal('hide');
					$('#reload-products-alias').trigger('click');
				} else {
					error.text('Error Processing your Request!! ' + s.message).show();
				}
			}
		});
	};

	var productAlias_handleSelect = function () {
		var a_options = new Array();
		var m_options = '';
		$.each(accounts, function (account_k, account) {
			a_options[account_k] = "<option value=''></option>";
			m_options += "<option value=" + account_k + ">" + account_k.charAt(0).toUpperCase() + account_k.slice(1) + "</option>";

			$.each(account, function (k, v) {
				a_options[account_k] += "<option value=" + v.account_id + ">" + v.account_name + "</option>";
			});
		});
		$('.marketplace').append(m_options);

		var $marketplace = $('.marketplace');
		var $accountMenu = $('.account_id');
		$marketplace.change(function () {
			$accountMenu.select2("val", "");
			$accountMenu.empty().append(a_options[$(this).val()]);
		});

		if (jQuery().select2) {
			$('.marketplace, .account_id').select2({
				placeholder: "Select",
				allowClear: true
			});
		}
	};

	var productAliasBulk_handleValidation = function () {
		var form = $('#add-products-alias-bulk');
		var error = $('.alert-danger', form);

		form.validate({
			errorElement: 'span', //default input error message container
			errorClass: 'help-block', // default input error message class
			focusInvalid: false, // do not focus the last invalid input
			ignore: "",
			rules: {
				alias_file: {
					required: true,
				},
				marketplace: {
					required: true,
				},
				account_id: {
					required: true,
				},
			},

			messages: { // custom messages for radio buttons and checkboxes
				alias_file: {
					required: "Please select a valid Alias File"
				},
				marketplace: {
					required: "Please select a Marketplace"
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
				formData.append('action', 'add_product_alias_bulk');
				formData.append('alias_file', $('#alias_file')[0].files[0]);
				formData.append('marketplace', $('.marketplace option:selected', form).val());
				formData.append('account_id', $('.account_id option:selected', form).val());
				// var other_data = $(form).serializeArray();
				// $.each(other_data,function(key,input){
				// 	formData.append(input.name,input.value);
				// });

				window.setTimeout(function () {
					var s = submitForm(formData, "POST");
					// console.log(s);
					// return;
					if (s.type == 'success') {
						if (s.error == 0 && s.existing == 0) {
							$('#add_product_alias_bulk').modal('hide');
							UIToastr.init(s.type, 'Bulk Product Upload', "Successfull uploaded product");
							$('.form-control', form).val("").trigger('change');
							$('.reload').trigger('click');
						} else {
							UIToastr.init('info', 'Bulk Product Upload', "Successfull uploaded product with few errors.");
							error.html("Successfull added: " + s.success + "<br /> Error: " + s.error + " <br /> Existing: " + s.existing + " <br />Error File: <a href='" + s.file + "' target='_blank'>Download Processed file.</a>").show();
						}
						$('.btn-success', form).attr('disabled', false);
						$('.btn-success i', form).removeClass('fa fa-sync fa-spin');
					} else if (s.type == 'error') {
						error.html(s.message).show();
					} else {
						error.text('Error Processing your Request!! ' + s.message).show();
					}
				}, 500);
			}
		});
	};

	var product_handleBootstrapSwitch = function () {
		if (!$().bootstrapSwitch) {
			return;
		}
		// $('.in_stock').bootstrapSwitch();
		// $('.is_active').bootstrapSwitch();
		$('.auto_update').bootstrapSwitch();
		$('.fulfilled').bootstrapSwitch();

		$('#editable_products').on('switchChange.bootstrapSwitch', 'input[class="in_stock"]', function (e, data) {
			NProgress.start();
			// var pid = $(this).closest('tr').find('td:first').text();
			var pid = $(this).data('pid');

			if ($(this).is(":checked")) {
				in_stock = 1;
			} else {
				in_stock = 0;
			}
			$("[class='in_stock']").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_stock&pid=" + pid + "&in_stock=" + in_stock,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'Product Stock Status', s);
					} else {
						UIToastr.init('success', 'Product Stock Status', 'Product stock status successfully updated.');
					}
					$("[class='in_stock']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'Product Stock Status', 'Error Processing your Request!!! Please try again later.');
				}
			});
		});

		$('#editable_products').on('switchChange.bootstrapSwitch', 'input[class="is_active"]', function (e, data) {
			NProgress.start();
			// var pid = $(this).closest('tr').find('td:first').text();
			var pid = $(this).data('pid');

			if ($(this).is(":checked")) {
				is_active = 1;
			} else {
				is_active = 0;
			}
			$("[class='is_active']").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=edit_product&pid=" + pid + "&is_active=" + is_active,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'Product Status', 'Error Processing your Request!!! Please retry later.');
					} else {
						UIToastr.init('success', 'Product Status', 'Product status successfully updated.');
					}
					$("[class='is_active']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'Product Status', 'Error Processing your Request!!! Please retry later.');
				}
			});
		});

		$('#editable_products').on('switchChange.bootstrapSwitch', 'input[class="in_stock_rtl"]', function (e, data) {
			NProgress.start();
			// var pid = $(this).closest('tr').find('td:first').text();
			var pid = $(this).data('pid');

			if ($(this).is(":checked")) {
				in_stock_rtl = 1;
			} else {
				in_stock_rtl = 0;
			}
			$("[class='in_stock_rtl']").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=edit_product&pid=" + pid + "&in_stock_rtl=" + in_stock_rtl,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'Product Status', 'Error Processing your Request!!! Please retry later.');
					} else {
						UIToastr.init('success', 'Product Status', 'Product status successfully updated.');
					}
					$("[class='in_stock_rtl']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'Product Status', 'Error Processing your Request!!! Please retry later.');
				}
			});
		});

		$('#editable_products').on('switchChange.bootstrapSwitch', 'input[class="is_stock_offline"]', function (e, data) {
			NProgress.start();
			// var pid = $(this).closest('tr').find('td:first').text();
			var pid = $(this).data('pid');

			if ($(this).is(":checked")) {
				is_stock_offline = 1;
			} else {
				is_stock_offline = 0;
			}
			$("[class='is_stock_offline']").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=edit_product&pid=" + pid + "&is_stock_offline=" + is_stock_offline,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'Product Status', 'Error Processing your Request!!! Please retry later.');
					} else {
						UIToastr.init('success', 'Product Status', 'Product status successfully updated.');
					}
					$("[class='is_stock_offline']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'Product Status', 'Error Processing your Request!!! Please retry later.');
				}
			});
		});

		$('#editable_products_alias').on('switchChange.bootstrapSwitch', 'input[class="auto_update"]', function (e, data) {
			NProgress.start();
			var pid = $(this).closest('tr').find('td:first').text();

			if ($(this).is(":checked")) {
				auto_update = 1;
			} else {
				auto_update = 0;
			}
			$("[class='auto_update']").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_alias&alias_id=" + pid + "&auto_update=" + auto_update,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'Listing Upadte Status', 'Error Processing your Request!!! Please retry later.');
					} else {
						UIToastr.init('success', 'Listing Upadte Status', 'Listing upadte status successfully updated.');
					}
					$("[class='auto_update']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'Listing Upadte Status', 'Error Processing your Request!!! Please retry later.');
				}
			});
		});

		$('#editable_products_alias').on('switchChange.bootstrapSwitch', 'input[class="fulfilled"]', function (e, data) {
			NProgress.start();
			var pid = $(this).closest('tr').find('td:first').text();

			if ($(this).is(":checked")) {
				fulfilled = 1;
			} else {
				fulfilled = 0;
			}
			$("[class='fulfilled']").bootstrapSwitch('disabled', true);
			var xhr = null;
			xhr = $.ajax({
				url: "ajax_load.php?token=" + new Date().getTime(),
				type: 'POST',
				data: "action=update_alias&alias_id=" + pid + "&fulfilled=" + fulfilled,
				beforeSend: function (b) {
					if (xhr != null) {
						xhr.abort();
						$('.btn').attr("disabled", false);
						NProgress.done(true);
					}
				},
				success: function (s) {
					if (s.indexOf('error') !== -1) {
						UIToastr.init('error', 'Listing Fulfilment Status', 'Error Processing your Request!!! Please retry later.');
					} else {
						UIToastr.init('success', 'Listing Fulfilment Status', 'Listing fulfilment status successfully updated.');
					}
					$("[class='fulfilled']").bootstrapSwitch('disabled', false);
					NProgress.done(true);
				},
				error: function (e) {
					NProgress.done(true);
					UIToastr.init('error', 'Listing Fulfilment Status', 'Error Processing your Request!!! Please retry later.');
				}
			});
		});
	};

	return {
		//main function to initiate the module
		init: function ($type) {
			if ($type == 'product') {
				product_handleTable();
				product_handleValidation();
				productInactive_handleActivation();
				productInactive_handleValidation();
				product_handleBootstrapSwitch();
			} else if ($type == "product_combo") {
				productCombo_handleTable();
				productCombo_handleValidation();
			} else if ($type == "product_category") {
				productCategory_handleTable();
				productCategory_handleValidation();
			} else if ($type == "product_brand") {
				productBrand_handleTable();
				productBrand_handleValidation();
			} else if ($type == "product_alias") {
				productAlias_handleTable();
				productAlias_handleValidation();
				productAliasBulk_handleValidation();
				product_handleBootstrapSwitch();
				productAlias_handleSelect();
			}
		}
	};
}();
