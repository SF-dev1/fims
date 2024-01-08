"use_strict";

var RateCard = function () {
    var tab = "";

    function tabChange_handleTable() {

        viewOrder_handeleTable();

        // Order Status Tabs
        $(".order_type a").click(function () {
            if ($(this).parent().attr('class') == "active") {
                return;
            }
            tab = $(this).attr('href');
            type = tab.substr(tab.indexOf("_") + 1);
            viewOrder_handeleTable(type, tab);
        });
    }

    function viewOrder_handeleTable() {
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

        var columns = [
            {
                data: "fee_name",
                title: "Fee Name",
                columnFilter: 'selectFilter'
            }, {
                data: "fee_tier",
                title: "Fee Tier",
                columnFilter: 'selectFilter'
            }, {
                data: "fee_type",
                title: "Fee Type",
                columnFilter: 'selectFilter'
            }, {
                data: "fee_category",
                title: "Fee Category",
                columnFilter: 'selectFilter'
            }, {
                data: "fee_marketplace",
                title: "Fee Marketplace",
                columnFilter: "selectFilter"
            }, {
                data: "fee_attribute_min",
                title: "Fee Attribute Min",
                columnFilter: "selectFilter"
            }, {
                data: "fee_attribute_max",
                title: "Fee Attribute Max",
                columnFilter: "selectFilter"
            }, {
                data: "start_date",
                title: "Start Date",
                columnFilter: "dateFilter"
            },
            {
                data: "end_date",
                title: "End Date",
                columnFilter: "dateFilter"
            }, {
                data: "status",
                title: "status",
                columnFilter: "selectFilter"
            }, {
                data: "action",
                columnFilter: 'actionFilter',
                responsivePriority: -1
            }
        ]
            ;


        var sorting_order = [7, 'asc'];

        var table = $('#editable_template_element');
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
            ajax: {
                url: "ajax_load.php?action=mp_data&token=" + new Date().getTime(),
                cache: false,
                type: "GET",
            },
            columns: columns,
            order: [sorting_order],
            columnDefs: [
                {
                    targets: 8,
                }
            ],
            drawCallback: function () {
                afterDrawDataTable();
            },
            initComplete: function () {
                loadFilters(this);
                afterInitDataTable();
                edtableTable();
                copyRateCard();
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
            if (jQuery().datepicker) {
                $('.date-picker').datepicker({
                    format: 'yyyy-mm-dd',
                    autoclose: true,
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
                App.initUniform();
        }

        function afterInitDataTable() {
            // Button Actions

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

        function edtableTable() {
            table.on('click', '.edit', function (e) {
                e.preventDefault();

                var nRow = $(this).parents('tr')[0];

                var aData = oTable.row(nRow).data();
                var cData = [];
                for (var i in aData) {
                    cData.push(aData[i]);
                }
                $('#add_rate_card').modal('toggle');
                $("#fee_name").val(aData.fee_name).trigger('change');
                $("#fee_tier").val(aData.fee_tier).trigger('change');
                $("#fee_type").val(aData.fee_type).trigger('change');
                $("#fee_fulfilmentType").val(aData.fee_fulfilmentType).trigger('change');
                $("#fee_brand").val(aData.fee_brand).trigger('change');
                $("#fee_category").val(aData.fee_category).trigger('change');
                $("#fee_column").val(aData.fee_column).trigger('change');
                $("#fee_marketplace").val(aData.fee_marketplace).trigger('change');
                $('#fee_attribute_min').val(aData.fee_attribute_min);
                $('#fee_attribute_max').val(aData.fee_attribute_max);
                $('#fee_value').val(aData.fee_value);
                $('#fee_constant').val(aData.fee_constant);
                $('#start_date').val(aData.start_date);
                $('#end_date').val(aData.end_date);
                $('#fee_id').val(aData.fee_id);
                $('#rate_card_type').val("edit_rate");
            });

        }
        function copyRateCard() {
            table.on('click', '.clone', function (e) {
                e.preventDefault();

                var nRow = $(this).parents('tr')[0];

                var aData = oTable.row(nRow).data();
                var cData = [];
                for (var i in aData) {
                    cData.push(aData[i]);
                }
                $('#add_rate_card').modal('toggle');
                $("#fee_name").val(aData.fee_name).trigger('change');
                $("#fee_tier").val(aData.fee_tier).trigger('change');
                $("#fee_type").val(aData.fee_type).trigger('change');
                $("#fee_fulfilmentType").val(aData.fee_fulfilmentType).trigger('change');
                $("#fee_brand").val(aData.fee_brand).trigger('change');
                $("#fee_category").val(aData.fee_category).trigger('change');
                $("#fee_column").val(aData.fee_column).trigger('change');
                $("#fee_marketplace").val(aData.fee_marketplace).trigger('change');
                $('#fee_attribute_min').val(aData.fee_attribute_min);
                $('#fee_attribute_max').val(aData.fee_attribute_max);
                $('#fee_value').val(aData.fee_value);
                $('#fee_constant').val(aData.fee_constant);
                $('#start_date').val(aData.start_date);
                $('#end_date').val(aData.end_date);
                $('#fee_id').val(aData.fee_id);
                $('#rate_card_type').val("clone_rate");
            });


        }
    }

    return {
        //main function to initiate the module0
        init: function (type) {
            switch (type) {
                case 'rate-card':
                    tabChange_handleTable();
                    break;
            }
        }
    };
}();
