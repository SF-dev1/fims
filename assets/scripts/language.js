var Language = function () {

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


    var language_handleTable = function () {
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

        var table = $('#editable_language');
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
                url: "ajax_load.php?action=language&client_id=" + client_id + "&token=" + new Date().getTime(),
                cache: false,
                type: "GET",
            },
            columns: [
                {
                    data: "lang_name",
                    title: "Language",
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
                formData.append('action', 'edit_language');
                for (var k in aData) {
                    formData.append(k, aData[k]);
                }
                var s = submitForm(formData, "POST");

                if (s.type == "success" || s.type == "error") {
                    UIToastr.init(s.type, 'Language Update', s.msg);
                } else {
                    UIToastr.init('error', 'Language Update', 'Error Processing Request. Please try again later.');
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
                    var formData = new FormData();
                    formData.append('action', 'delete_language');
                    formData.append('langid', aData['lang_id']);

                    var s = submitForm(formData, "POST");
                    if (s.type == "success" || s.type == "error") {
                        oTable.row($(this).parents('tr')).remove().draw();
                        UIToastr.init(s.type, 'Language', s.msg);
                    } else {
                        UIToastr.init('error', 'Language', 'Error Processing Request! Please try again later');
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

    var language_handleValidation = function () {
        var form = $('#add-language');
        var error = $('.alert-danger', form);

        form.validate({
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            ignore: "",
            rules: {
                languageName: {
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
                var languageName = $("#languageName").val();
                var formData = new FormData();
                formData.append('action', 'add_language');
                formData.append('languageName', languageName);

                var s = submitForm(formData, "POST");
                if (s.type == "success" || s.type == "error") {
                    UIToastr.init(s.type, 'Language', s.msg);
                    if (s.type == "success") {
                        $('#add_language').modal('hide');
                        $('#reload-language').trigger('click');
                    }
                } else {
                    UIToastr.init('error', 'Language', 'Error Processing Request. Please try again later.');
                }
            }
        });
    };

    return {
        //main function to initiate the module
        init: function ($type) {
            if ($type == "language") {
                language_handleTable();
                language_handleValidation();
            }
        }
    };
}();
