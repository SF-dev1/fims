var Distributor = function () {

    // Submit Form
    var submitForm = function (formData, $type, $mime) {
        $ret = "";
        $.ajax({
            url: "ajax_load.php?token=" + new Date().getTime(),
            cache: false,
            type: $type,
            data: formData,
            contentType: false,
            processData: false,
            mimeType: $mime,
            async: false,
            success: function (s) {
                $ret = $.parseJSON(s);
                // $ret = s;
            },
            error: function (e) {
                alert('Error Processing your Request!!');
            }
        });
        return $ret;
    };

    var sellerApproval_handleTable = function () {
        var table = $('#view_unapproved_sellers');

        var oTable = table.dataTable({
            "lengthMenu": [
                [20, 25, 50, 100, -1],
                [20, 25, 50, 100, "All"] // change per page values here
            ],
            // set the initial value
            "pageLength": 20,
            "language": {
                "lengthMenu": " _MENU_ records",
            },
            "bDestroy": true,
            "bSort": false,
            "ordering": false,
            "processing": true,
            "deferRender": true,
            "ajax": {
                url: "ajax_load.php?action=view_unapproved_sellers&token=" + new Date().getTime(),
                type: "GET",
                cache: false,
            },
            "columnDefs": [
                {
                    className: ["return_hide_column"], "targets": [0],
                },
            ],
            "fnDrawCallback": function () {
                sellerApproval_handleView();
                // Initialize events after the table is loaded
                // $( ".upload_docs" ).click(function(){
                //     $sellerId = $(this).data('sellerid');
                //     $sellerName = $(this).data('sellername');
                //     $sellerGST = $(this).data('sellergst');
                //     $("#party_name_gst").text($sellerName+' - ['+$sellerGST+']');
                //     $("#party_name").val($sellerName);
                //     $("#party_id").val($sellerId);
                // });
            },
        });

        // Reload Parties
        $('.reload').bind('click', function (e) {
            e.preventDefault();
            var el = jQuery(this).closest(".portlet").children(".portlet-body");
            App.blockUI({ target: el });
            oTable.api().ajax.reload();
            window.setTimeout(function () {
                App.unblockUI(el);
            }, 500);
        });
    };

    var sellerApproval_handleView = function () {

        $.fn.modalmanager.defaults.resize = true;

        var $modal = $('#view-docs');
        $('.view').off('click').on('click', function (e) {
            e.preventDefault();

            // create the backdrop and wait for next modal to be triggered
            $location = $(this).attr('data-href');
            $("#view_pdf").attr('src', $location);
            $modal.modal();

            $party_id = $(this).data('partyid');
            $party_doc = $(this).data('partydoc');
            $checked_gst = $(this).data('checkedgst');
            $checked_tnc = $(this).data('checkedtnc');
            if ($party_doc == "gst")
                $checked_gst = "1";

            if ($party_doc == "tnc")
                $checked_tnc = "1";

            $(".approve", $modal).data('partyid', $party_id).data('partydoc', $party_doc).data('checkedgst', $checked_gst).data('checkedtnc', $checked_tnc);
            $(".disapprove", $modal).data('partyid', $party_id).data('partydoc', $party_doc).data('checkedgst', $checked_gst).data('checkedtnc', $checked_tnc);
        });

        $('.approve').off('click').on('click', function () {
            $party_id = $(this).data('partyid');
            $party_doc = $(this).data('partydoc');
            $gst_checked = $(this).data('checkedgst');
            $tnc_checked = $(this).data('checkedtnc');

            var formData = new FormData();
            formData.append('action', 'update_doc_status');
            formData.append('party_id', $party_id);
            formData.append('party_doc', $party_doc);
            formData.append('doc_staus', 'approved');
            formData.append('gst_checked', $gst_checked);
            formData.append('tnc_checked', $tnc_checked);

            $(this).attr('disabled', true);
            $(this).find('i').addClass('fa fa-sync fa-spin');
            $('.disapprove').attr('disabled', true);

            var s = submitForm(formData, "POST");
            if (s.type == 'success' || s.type == 'error') {
                UIToastr.init(s.type, 'Doc Status Update', s.msg);
                if (s.type == 'success') {
                    $modal.modal('toggle');
                    $('.reload').trigger('click');
                    $(this).attr('disabled', false);
                    $(this).find('i').removeClass('fa-sync fa-spin');
                    $('.disapprove').attr('disabled', false);
                } else {
                    $(this).attr('disabled', false);
                    $(this).find('i').removeClass('fa-sync fa-spin');
                    $('.disapprove').attr('disabled', false);
                }
            } else {
                $(this).attr('disabled', false);
                $(this).find('i').removeClass('fa-sync fa-spin');
                $('.disapprove').attr('disabled', false);
                UIToastr.init('error', 'Doc Status Update', 'Unable to process request. Please try again later');
            }
        });

        $('.disapprove').off('click').on('click', function () {
            $party_id = $(this).data('partyid');
            $party_doc = $(this).data('partydoc');
            $gst_checked = $(this).data('checkedgst');
            $tnc_checked = $(this).data('checkedtnc');

            $confirm_modal = $('#confirm-disapprove');
            $('.confirmed-disapprove').off('click').on('click', function () {
                $('.btn', $confirm_modal).attr('disabled', true);
                $(this).find('i').addClass('fa-sync fa-spin');

                var formData = new FormData();
                formData.append('action', 'update_doc_status');
                formData.append('party_id', $party_id);
                formData.append('party_doc', $party_doc);
                formData.append('doc_staus', $('#disapprove_reason').val());
                formData.append('gst_checked', $gst_checked);
                formData.append('tnc_checked', $tnc_checked);

                var s = submitForm(formData, "POST");
                if (s.type == 'success' || s.type == 'error') {
                    UIToastr.init(s.type, 'Doc Status Update', s.msg);
                    if (s.type == 'success') {
                        $modal.modal('toggle');
                        $confirm_modal.modal('toggle');
                        $('.reload').trigger('click');
                        $('.btn', $confirm_modal).attr('disabled', false);
                        $(this).find('i').removeClass('fa-sync fa-spin');
                    } else {
                        $('.btn', $confirm_modal).attr('disabled', false);
                        $(this).find('i').removeClass('fa-sync fa-spin');
                    }
                } else {
                    $(this).attr('disabled', false);
                    $(this).find('i').removeClass('fa-sync fa-spin');
                    $('.disapprove').attr('disabled', false);
                    UIToastr.init('error', 'Doc Status Update', 'Unable to process request. Please try again later');
                }
            });
        });

        $('.upload_certificate').off('click').on('click', function () {
            $('.party_signed_certificate, .brand_file').remove();
            var brands_applied = $(this).data('brands_applied').split(',');
            var party_id = $(this).data('partyid');
            var is_renewal = $(this).data('is_renewal');
            var content = "";
            $.each(brands_applied, function (k, v) {
                content += '<div class="form-group party_signed_certificate brand_' + k + '"><label class="control-label col-sm-4">Signed Certificate ' + v + '<span class="required"> * </span></label><div class="col-sm-8"><button class="btn btn-default btn-small generate_certificate" data-key="' + k + '" data-partyid="' + party_id + '" data-brandname="' + v + '" data-is_renewal="' + is_renewal + '" role="button"><i></i> Generate Certificate</button> <button class="btn btn-default btn-small view_certificate" disabled data-partyid="' + party_id + '" data-brandname="' + v + '" data-is_renewal="' + is_renewal + '" data-view="1" role="button">View</button></div></div>';
                $('#upload-signed-certificate .form-actions').prepend('<input type="hidden" class="brand_file" required id="brand_file_' + k + '" name="file[' + k + ']" value="" />')
            });
            $(content).insertAfter('.party_signed_certificate_sample');
            $("[name='party_id']").val(party_id);
            $("[name='is_renewal']").val(is_renewal);
            handleCertificateGeneration();
        });
    };

    var sellerApproval_handleValidation = function () {
        var form = $('#upload-signed-certificate');
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
                } else if (element.parent('span').parent('.fileinput')) {
                    error.appendTo(element.parent('span').parent('.fileinput'));
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
                $('.form-actions .btn-submit', form).attr('disabled', true);
                $('.form-actions .btn-submit i', form).addClass('fa fa-sync fa-spin');

                window.setTimeout(function () {
                    var other_data = $(form).serializeArray();
                    var formData = new FormData();
                    formData.append('action', 'upload_certificate');
                    $.each(other_data, function (key, input) {
                        formData.append(input.name, input.value);
                    });

                    var s = submitForm(formData, "POST");
                    if (s.type == 'success' || s.type == 'error') {
                        UIToastr.init(s.type, 'Seller Approval', s.msg);
                        $('.form-actions .btn-success', form).attr('disabled', false);
                        $('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');
                        $(form)[0].reset();
                        if (s.type == 'success') {
                            $("#upload-certificate").modal('toggle');
                            $(".reload").trigger('click');
                        }
                    } else {
                        UIToastr.init('error', 'Seller Approval', 'Unable to process request. Please try again later');
                        $('.form-actions .btn-success', form).attr('disabled', false);
                        $('.form-actions .btn-success i', form).removeClass('fa-sync fa-spin');
                    }
                }, 500);
            }
        });
    };

    var sellerApproved_handleTable = function () {

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
            "active": {
                title: "Active",
                class: " badge badge-success"
            },
            "expired": {
                title: "Expired",
                class: " badge badge-warning"
            },
            "renewal_requested": {
                title: "Renewal Requested",
                class: " badge badge-info"
            },
        };

        var table = $('#editable_approved_sellers');
        var oTable;
        oTable = table.DataTable({
            responsive: true,
            dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12 table-filter'><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
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
                url: "ajax_load.php?action=view_approved_sellers&token=" + new Date().getTime(),
                cache: false,
                type: "GET",
            },
            columns: [
                {
                    title: "Seller",
                    data: "party_name",
                    columnFilter: 'inputFilter'
                }, {
                    title: "Seller POC",
                    data: "party_poc",
                    columnFilter: 'inputFilter'
                }, {
                    title: "Seller Email",
                    data: "party_email",
                    columnFilter: 'inputFilter'
                }, {
                    title: "Seller Mobile",
                    data: "party_mobile",
                    columnFilter: 'inputFilter'
                }, {
                    title: "Distributor",
                    data: "distributor_name",
                    columnFilter: 'inputFilter'
                }, {
                    title: "Distributor Email",
                    data: "distributor_email",
                    columnFilter: 'inputFilter'
                }, {
                    title: "Distributor Mobile",
                    data: "distributor_mobile",
                    columnFilter: 'inputFilter'
                }, {
                    title: "Approved Brands",
                    data: "party_approved_brands",
                    columnFilter: 'inputFilter'
                }, {
                    title: "Valid Till",
                    data: "expiry_date",
                    columnFilter: 'dateFilter'
                }, {
                    title: "Status",
                    data: "status",
                    columnFilter: 'statusFilters',
                }, {
                    title: "Action",
                    data: "action",
                    columnFilter: "actionFilter",
                    responsivePriority: -1
                }
            ],
            order: [
                [8, 'asc']
            ],
            columnDefs: [
                {
                    targets: [],
                    className: 'return_hide_column',
                }, {
                    targets: 9,
                    render: function (a, t, e, s) {
                        return void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
                    }
                }
            ],
            fnDrawCallback: function () {
                // Initialize events after the table is loaded
                drawCallBackDataTable();
            },
            initComplete: function () {
                loadFilters(this),
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
                        s = $('<input type="text" class="form-control form-control-sm form-filter filter-input" placeholder="' + this.title() + '" data-col-index="' + this.index() + '"/>');
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

        function drawCallBackDataTable() {
            $('.request_renewal').click(function () {
                var button = $(this);
                button.attr('disabled', true);
                button.find('i').addClass('fa fa-sync fa-spin');

                var formData = new FormData();
                formData.append('action', 'request_renewal');
                formData.append('seller_id', $(this).data('sellerid'));
                window.setTimeout(function () {
                    var s = submitForm(formData, "POST");
                    if (s.type == 'success' || s.type == 'error') {
                        UIToastr.init(s.type, 'Renew Request', s.msg);
                        if (s.type == 'success') {
                            oTable.ajax.reload();
                            button.attr('disabled', false);
                            button.find('i').removeClass('fa fa-sync fa-spin');
                        } else {
                            button.attr('disabled', false);
                            button.find('i').removeClass('fa fa-sync fa-spin');
                        }
                    } else {
                        button.attr('disabled', false);
                        button.find('i').removeClass('fa fa-sync fa-spin');
                        UIToastr.init('error', 'Renew Request', s.msg);
                    }
                }, 10);
            });
        }
    };

    /*var distributorOnboard_handleTable_v1 = function(){
        var table = $('#editable_distributor_view');

        var oTable = table.dataTable({
            "lengthMenu": [
                [20, 25, 50, 100, -1],
                [20, 25, 50, 100, "All"] // change per page values here
            ],
            // set the initial value
            "pageLength": 20,
            "language": {
                "lengthMenu": " _MENU_ records",
            },
            "bDestroy": true, 
            "bSort": false,
            "ordering": false,
            "processing": true,
            "deferRender": true,
            "ajax": {
                url : "ajax_load.php?action=view_distributors&token="+ new Date().getTime(),
                type: "GET",
                cache: false,
            },
            "columnDefs": [
                { 
                    className: ["return_hide_column"], "targets": [ 0 ],
                },
            ],
            "fnDrawCallback": function() {
                distributorOnboard_handleView();
                // Initialize events after the table is loaded
                // $( ".upload_docs" ).click(function(){
                //     $sellerId = $(this).data('sellerid');
                //     $sellerName = $(this).data('sellername');
                //     $sellerGST = $(this).data('sellergst');
                //     $("#party_name_gst").text($sellerName+' - ['+$sellerGST+']');
                //     $("#party_name").val($sellerName);
                //     $("#party_id").val($sellerId);
                // });
            },
        });

        // Reload Parties
        $('.reload').bind('click', function(e){
            e.preventDefault();
            var el = jQuery(this).closest(".portlet").children(".portlet-body");
            App.blockUI({target: el});
            oTable.api().ajax.reload();
            window.setTimeout(function () {
                App.unblockUI(el);
            }, 500);
        });
    };

    var distributorOnboard_handleTable = function(){
        $.fn.dataTable.Api.register("column().title()", function() {
            return $(this.header()).text().trim()
        });

        $.fn.dataTable.Api.register("column().getColumnFilter()", function() {
            e = this.index();
            if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter'))
                return oTable.settings()[0].aoColumns[e].columnFilter;
            else 
                return '';
        });

        var statusFilters = {
            // "draft": {
            //     title: "Approved",
            //     class: " badge badge-default"
            // },
            "pending_docs": {
                title: "Pending Docs",
                class: " badge badge-info"
            },
            // "shipped": {
            //     title: "Shipped",
            //     class: " badge badge-primary"
            // },
            // "partial_shipped": {
            //     title: "Partial Shipped",
            //     class: " badge badge-warning"
            // },
            // "partial_shipped_received": {
            //     title: "Partial Shipped Received",
            //     class: " badge badge-warning"
            // },
            "approved": {
                title: "Approved",
                class: " badge badge-success"
            },
        };

        var table = $('#editable_distributor_view');
        var oTable;
        oTable = table.DataTable({
            responsive: true,
            dom: "<'row' <'col-md-6 col-sm-12'l><'col-md-6 col-sm-12' <'btn-advance'> f><'col-sm-12' <'table-scrollable' tr>>\t\t\t<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 dataTables_pager'p>>",
            lengthMenu: [[20, 50, 100, -1],[20, 50, 100, "All"]], // change per page values here
            pageLength: 20,
            debug: true,
            language: {
                lengthMenu: "Display _MENU_"
            },
            searchDelay: 500,
            processing: !0,
            // serverSide: !0,
            ajax: {
                url: "ajax_load.php?action=view_distributors&token="+ new Date().getTime(),
                cache: false,
                type: "POST",
            },
            columns: [
                {
                    data: "Distributor Name",
                    columnFilter: 'selectFilter'
                }, {
                    data: "Brands",
                    columnFilter: 'selectFilter'
                }, {
                    data: "Documnets",
                    columnFilter: 'rangeFilter'
                }, {
                    data: "Status",
                    columnFilter: 'statusFilter'
                }, {
                    data: "Actions",
                    columnFilter: 'actionFilter',
                    responsivePriority: -1
                },
            ],
            order: [
                [0, 'desc' ]
            ],
            columnDefs: [
                {
                    targets: -1,
                    title: "Actions",
                    orderable: !1,
                    width: "10%",
                    render: function(a, t, e, s) {
                        if (e.Status == "draft"){
                            $return =   '<a class="btn btn-default btn-xs create_grn" href="purchase_new.php?po_id='+e.po_id+'&type='+e.Status+'" title="Edit PO"><i class="fa fa-edit"></i></a> '+
                                        '<a title"Cancel PO" class="delete btn btn-default btn-xs" data-po_id='+e.po_id+' title="Cancel PO" ><i class="fa fa-trash-alt"></i></a>';
                        } else if (e.Status == "created"){
                            $return = '<a title="View PO" class="view btn btn-default btn-xs print_preview" data-toggle="modal" data-href="/print.php?type=purchase_order&id='+e.po_id+'" ><i class="fa fa-eye"></i></a>';
                        } else {
                            $return = "No Action Available";
                        }
                        return $return;
                    }
                }, {
                    targets: -2,
                    width: "10%",
                    render: function(a, t, e, s) {
                        $return = void 0 === statusFilters[a] ? a : '<span class="' + statusFilters[a].class + '">' + statusFilters[a].title + "</span>";
                        return $return;
                    }
                }, {
                    targets: -3,
                    width: "10%",
                }
            ],
            initComplete: function() {
                loadFilters(this),
                afterInitDataTable();
            }
        });

        var loadFilters = function(t){
            var parseDateValue =function(rawDate) {
                var d = moment(rawDate, "YYYY-MM-DD").format('YYYY-MM-DD');
                var dateArray = d.split("-");
                var parsedDate = dateArray[2] + dateArray[1] + dateArray[0];
                return parsedDate;
            };

            var isRangeFilter = function(e){
                if (typeof(oTable.settings()[0].aoColumns[e]) !== "undefined")
                    if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "rangeFilter")
                        return true;
                return false;
            };

            var isDateFilter = function(e){
                if (typeof(oTable.settings()[0].aoColumns[e]) !== "undefined")
                    if (oTable.settings()[0].aoColumns[e].hasOwnProperty('columnFilter') && oTable.settings()[0].aoColumns[e].columnFilter === "dateFilter")
                        return true;

                return false;
            };
            // FILTERING
            var f = $('<tr class="filter"></tr>').appendTo($(oTable.table().header()));
            t.api().columns().every(function() {
                var s;
                switch (this.getColumnFilter()){
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
                        s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function(t, f) {
                            $(s).append('<option value="' + t + '">' + t + "</option>");
                        });
                        break;

                    case 'statusFilter':
                        s = $('<select class="form-control form-control-sm form-filter filter-input select2" title="Select" data-col-index="' + this.index() + '">\t\t\t\t\t\t\t\t\t\t<option value=""></option></select>'), this.data().unique().sort().each(function(t, a) {
                            $(s).append('<option value="' + t + '">' + statusFilters[t].title + "</option>")
                        });
                        break;

                    case 'actionFilter':
                        var i = $('<button class="btn btn-sm btn-warning filter-submit margin-bottom-5" title="Search"><i class="fa fa-search"></i></button>'),
                            r = $('<button class="btn btn-sm btn-danger filter-cancel margin-bottom-5" title="Reset"><i class="fa fa-times"></i></button>');
                        $("<th>").append(i).append(r).appendTo(f);
                        var sD, eD, minR, maxR;
                        $(i).on("click", function(ev) {
                            ev.preventDefault();
                            var n = {};
                            $(function() {}).find(".filter-input").each(function() {
                                var t = $(this).data("col-index");
                                n[t] ? n[t] += "|" + $(this).val() : n[t] = $(this).val()
                            }), 
                            $.each(n, function(e, a) {
                                if (isRangeFilter(e)){ // RANGE FILTER
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
                                            if  (( isNaN( minR ) && isNaN( maxR ) ) ||
                                                ( isNaN( minR ) && evalRange <= maxR ) ||
                                                ( minR <= evalRange   && isNaN( maxR ) ) ||
                                                ( minR <= evalRange   && evalRange <= maxR ) ){
                                                    return true;
                                            }
                                            return false;
                                        });

                                    var r = "";
                                    for (var count = 0; count < fR.length; count++) {
                                        r += fR[count] + "|";
                                    }
                                    r = r.slice(0, -1);
                                    oTable.column(e).search("^" + r + "$" , 1, 1, 1);
                                } else if (isDateFilter(e)){ // DATE FILTER
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
                        $(r).on("click", function(ev) {
                            ev.preventDefault(), $(f).find(".filter-input").each(function() {
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
            var n = function() {
                t.api().columns().every(function() {
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
            $('.btn-advance').bind('click', function(){
                $('.filter').toggle();
            });
        };

        var afterInitDataTable = function(){
            // Print Preview
            purchaseOrder_printPreview();

            // Delete draft and created PO's
            table.on('click', '.delete', function (e) {
                e.preventDefault();
                if (confirm("Are you sure to cancel this PO ?") === true) {
                    var po_id = $(this).data('po_id');
                    var formData = new FormData();
                        formData.append('action', 'delete_po');
                        formData.append('po_id', po_id);
                    var s = submitForm(formData, "POST");
                    oTable.ajax.reload();
                    UIToastr.init(s.type, 'Purchase Order', s.msg);
                    return;
                }
            });
            
            // Reload DataTable
            $('.reload').bind('click', function(e){
                e.preventDefault();
                var el = jQuery(this).closest(".portlet").children(".portlet-body");
                App.blockUI({target: el});
                $('.select2').val("").trigger('change');
                $('.filter-cancel').trigger('click');
                oTable.ajax.reload();
                window.setTimeout(function () {
                    App.unblockUI(el);
                }, 500);
            });
        };
    };*/

    var distributorOnboard_handleTable = function () {
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
            "onboard": {
                title: "Onboard",
                class: " badge badge-info"
            },
            "signed_docs_pending": {
                title: "Signed Docs Pending",
                class: " badge badge-info"
            },
            "reupload_documents": {
                title: "Reupload Docs",
                class: " badge badge-info"
            },
            "awaiting_approval": {
                title: "Awaiting Approval",
                class: " badge badge-default"
            },
            "approved": {
                title: "Approved",
                class: " badge badge-success"
            },
            "renewal_requested": {
                title: "Renewal Requested",
                class: " badge badge-primary"
            },
            "expired": {
                title: "Expired",
                class: " badge badge-warning",
            },
            "suspended": {
                title: "Suspended",
                class: " badge badge-danger"
            },
            "blacklisted": {
                title: "Blacklisted",
                class: " badge badge-inverse"
            },
        };

        var table = $('#view_distributors');
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
                url: "ajax_load.php?action=view_distributors&token=" + new Date().getTime(),
                cache: false,
                type: "GET",
            },
            columns: [
                {
                    data: "party_name",
                    title: "Distributor Name",
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
                    data: "status",
                    title: "Status",
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
                    targets: -1,
                    orderable: !1,
                    width: "10%",
                }, {
                    targets: -2,
                    orderable: !1,
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
            fnDrawCallback: function () {
                // Initialize events after the table is loaded
                drawCallBackDataTable();
                distributorOnboard_handleView();
            },
            initComplete: function () {
                loadFilters(this),
                    afterInitDataTable();
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
                            $(s).append('<option value="' + statusFilters[t].title + '">' + statusFilters[t].title + "</option>")
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

        function afterInitDataTable() {
            // Reload DataTable
            $('.reload').off().on('click', function (e) {
                e.preventDefault();
                var el = jQuery(this).closest(".portlet").children(".portlet-body");
                App.blockUI({ target: el });
                $('.select2').val("").trigger('change');
                $('.filter-cancel').trigger('click');
                // table.empty();
                oTable.ajax.reload();
                window.setTimeout(function () {
                    App.unblockUI(el);
                }, 500);
            });
        };

        function drawCallBackDataTable() {
            $(".upload_docs").click(function () {
                $sellerId = $(this).data('sellerid');
                $sellerName = $(this).data('sellername');
                $("#party_name").val($sellerName);
                $("#party_id").val($sellerId);
            });

            $('.upload_certificate').off('click').on('click', function () {
                $('.party_signed_certificate, .brand_file').remove();
                var brands_applied = $(this).data('brands_applied').split(',');
                var party_id = $(this).data('partyid');
                var is_renewal = $(this).data('is_renewal');
                var content = "";
                $.each(brands_applied, function (k, v) {
                    content += '<div class="form-group party_signed_certificate brand_' + k + '"><label class="control-label col-sm-4">Signed Certificate ' + v + '<span class="required"> * </span></label><div class="col-sm-8"><button class="btn btn-default btn-small generate_certificate" data-key="' + k + '" data-partyid="' + party_id + '" data-brandname="' + v + '" data-is_renewal="' + is_renewal + '" role="button"><i></i> Generate Certificate</button> <button class="btn btn-default btn-small view_certificate" disabled data-partyid="' + party_id + '" data-brandname="' + v + '" data-is_renewal="' + is_renewal + '" data-view="1" role="button">View</button></div></div>';
                    $('#upload-signed-certificate .form-actions').prepend('<input type="hidden" class="brand_file" required id="brand_file_' + k + '" name="file[' + k + ']" value="" />')
                });
                $(content).insertAfter('.party_signed_certificate_sample');
                $("[name='party_id']").val(party_id);
                $("[name='is_renewal']").val(is_renewal);
                handleCertificateGeneration();
            });

            $(".reupload_docs").click(function () {
                $(".party_signed_tnc").show();
                $(".party_signed_gst").show();
                $sellerId = $(this).data('sellerid');
                $sellerName = $(this).data('sellername');
                $sellerGST = $(this).data('sellergst');
                $approvedtnc = $(this).data('approvedtnc');
                $approvedgst = $(this).data('approvedgst');
                $reupload = [];

                $("#party_name_gst").text($sellerName + ' - [' + $sellerGST + ']');
                $("#party_name").val($sellerName);
                $("#party_id").val($sellerId);
                if ($approvedtnc == "1") {
                    $(".party_signed_tnc").hide();
                    $("[name='party_signed_tnc']").removeAttr('required');
                    $reupload.push("tnc");
                }
                if ($approvedgst == "1") {
                    $(".party_gst_certificate").hide();
                    $("[name='party_gst_certificate']").removeAttr('required');
                    $reupload.push("gst");
                }
                $("#is_reupload").val($reupload);
            });

            $('.request_renewal').click(function () {
                var button = $(this);
                button.attr('disabled', true);
                button.find('i').addClass('fa fa-sync fa-spin');

                var formData = new FormData();
                formData.append('action', 'request_renewal');
                formData.append('seller_id', $(this).data('sellerid'));
                if (typeof ($(this).data('isdistributor')) !== "undefined")
                    formData.append('is_distributor', $(this).data('isdistributor'));
                window.setTimeout(function () {
                    var s = submitForm(formData, "POST");
                    if (s.type == 'success' || s.type == 'error') {
                        UIToastr.init(s.type, 'Renew Request', s.msg);
                        if (s.type == 'success') {
                            oTable.ajax.reload();
                            button.attr('disabled', false);
                            button.find('i').removeClass('fa fa-sync fa-spin');
                        } else {
                            button.attr('disabled', false);
                            button.find('i').removeClass('fa fa-sync fa-spin');
                        }
                    } else {
                        button.attr('disabled', false);
                        button.find('i').removeClass('fa fa-sync fa-spin');
                        UIToastr.init('error', 'Renew Request', s.msg);
                    }
                }, 10);
            });

            $('.concent').click(function () {
                var button = $(this);
                button.attr('disabled', true);
                button.find('i').addClass('fa fa-sync fa-spin');

                var formData = new FormData();
                formData.append('action', 'update_distributor_concent');
                formData.append('party_id', $(this).data('sellerid'));
                formData.append('concent', $(this).data('concent'));
                window.setTimeout(function () {
                    var s = submitForm(formData, "POST");
                    if (s.type == 'success' || s.type == 'error') {
                        UIToastr.init(s.type, 'Concent Update', s.msg);
                        if (s.type == 'success') {
                            oTable.ajax.reload();
                            button.attr('disabled', false);
                            button.find('i').removeClass('fa fa-sync fa-spin');
                        } else {
                            button.attr('disabled', false);
                            button.find('i').removeClass('fa fa-sync fa-spin');
                        }
                    } else {
                        button.attr('disabled', false);
                        button.find('i').removeClass('fa fa-sync fa-spin');
                        UIToastr.init('error', 'Concent Update', s.msg);
                    }
                }, 10);
            });

            $('.resend_tnc').click(function () {
                var button = $(this);
                button.attr('disabled', true);
                button.find('i').addClass('fa fa-sync fa-spin');

                var formData = new FormData();
                formData.append('action', 'resend_tnc');
                formData.append('seller_id', $(this).data('sellerid'));
                window.setTimeout(function () {
                    var s = submitForm(formData, "POST");
                    if (s.type == 'success' || s.type == 'error') {
                        UIToastr.init(s.type, 'Resend Terms & Condition', s.msg);
                        if (s.type == 'success') {
                            oTable.ajax.reload();
                            button.attr('disabled', false);
                            button.find('i').removeClass('fa fa-sync fa-spin');
                        } else {
                            button.attr('disabled', false);
                            button.find('i').removeClass('fa fa-sync fa-spin');
                        }
                    } else {
                        button.attr('disabled', false);
                        button.find('i').removeClass('fa fa-sync fa-spin');
                        UIToastr.init('error', 'Resend Terms & Condition', s.msg);
                    }
                }, 10);
            });
        }
    };

    var distributorOnboard_handleView = function () {
        $.fn.modalmanager.defaults.resize = true;

        var $modal = $('#view-docs');
        $('.view').off('click').on('click', function (e) {
            e.preventDefault();

            // create the backdrop and wait for next modal to be triggered
            $location = $(this).attr('data-href');
            $("#view_pdf").attr('src', $location);
            $modal.modal();

            $party_id = $(this).data('partyid');
            $partydoc = $(this).data('partydoc');
            $(".approve", $modal).data('partyid', $party_id);
            $(".approve", $modal).data('partydoc', $partydoc);
            $(".disapprove", $modal).data('partyid', $party_id);
            $(".disapprove", $modal).data('partydoc', $partydoc);
        });

        $('.approve').off('click').on('click', function () {
            $party_id = $(this).data('partyid');
            $party_doc = $(this).data('partydoc');
            var formData = new FormData();
            formData.append('action', 'update_doc_status');
            formData.append('party_id', $party_id);
            formData.append('party_doc', $party_doc);
            formData.append('doc_staus', 'approved');

            $(this).attr('disabled', true);
            $(this).find('i').addClass('fa fa-sync fa-spin');
            $('.disapprove').attr('disabled', true);

            var s = submitForm(formData, "POST");
            if (s.type == 'success' || s.type == 'error') {
                UIToastr.init(s.type, 'Doc Status Update', s.msg);
                if (s.type == 'success') {
                    $modal.modal('toggle');
                    $('.reload').trigger('click');
                    $(this).attr('disabled', false);
                    $(this).find('i').removeClass('fa-sync fa-spin');
                    $('.disapprove').attr('disabled', false);
                } else {
                    $(this).attr('disabled', false);
                    $(this).find('i').removeClass('fa-sync fa-spin');
                    $('.disapprove').attr('disabled', false);
                }
            } else {
                $(this).attr('disabled', false);
                $(this).find('i').removeClass('fa-sync fa-spin');
                $('.disapprove').attr('disabled', false);
                UIToastr.init('error', 'Doc Status Update', 'Unable to process request. Please try again later');
            }
        });

        $('.disapprove').off('click').on('click', function () {
            $party_id = $(this).data('partyid');
            $party_doc = $(this).data('partydoc');

            $confirm_modal = $('#confirm-disapprove');
            $('.confirmed-disapprove').off('click').on('click', function () {
                $('.btn', $confirm_modal).attr('disabled', true);
                $(this).find('i').addClass('fa-sync fa-spin');

                var formData = new FormData();
                formData.append('action', 'update_doc_status');
                formData.append('party_id', $party_id);
                formData.append('party_doc', $party_doc);
                formData.append('doc_staus', $('#disapprove_reason').val());

                var s = submitForm(formData, "POST");
                if (s.type == 'success' || s.type == 'error') {
                    UIToastr.init(s.type, 'Doc Status Update', s.msg);
                    if (s.type == 'success') {
                        $modal.modal('toggle');
                        $confirm_modal.modal('toggle');
                        $('.reload').trigger('click');
                        $('.btn', $confirm_modal).attr('disabled', false);
                        $(this).find('i').removeClass('fa-sync fa-spin');
                    } else {
                        $('.btn', $confirm_modal).attr('disabled', false);
                        $(this).find('i').removeClass('fa-sync fa-spin');
                    }
                } else {
                    $(this).attr('disabled', false);
                    $(this).find('i').removeClass('fa-sync fa-spin');
                    $('.disapprove').attr('disabled', false);
                    UIToastr.init('error', 'Doc Status Update', 'Unable to process request. Please try again later');
                }
            });
        });

        // $('.upload_certificate').off('click').on('click', function(){
        //     $("[name='party_id']").val($(this).data('partyid'));
        //     $("[name='party_name']").val($(this).data('partyname'));
        //     $("[name='is_renewal']").val($(this).data('is_renewal'));
        //     $("#upload-certificate .generate_certificate").attr('data-partyid', $(this).data('partyid'));
        //     $("#upload-certificate .generate_certificate").attr('data-party_name', $(this).data('party_name'));
        //     $("#upload-certificate .generate_certificate").attr('data-is_renewal', $(this).data('is_renewal'));
        // });
    };

    var distributorOnboard_handleValidation = function () {
        var form = $('#upload-signed-docs');
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
                } else if (element.parent('span').parent('.fileinput')) {
                    error.appendTo(element.parent('span').parent('.fileinput'));
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

                $('.form-actions .btn-success').attr('disabled', true);
                $('.form-actions .btn-success i').addClass('fa fa-sync fa-spin');

                window.setTimeout(function () {
                    var other_data = $(form).serializeArray();
                    var formData = new FormData();
                    formData.append('action', 'upload_docs');
                    formData.append('dist_signed_tnc', $("#dist_signed_tnc")[0].files[0]);
                    $.each(other_data, function (key, input) {
                        formData.append(input.name, input.value);
                    });

                    var s = submitForm(formData, "POST", "multipart/form-data");
                    if (s.type == 'success' || s.type == 'error') {
                        UIToastr.init(s.type, 'Distributor Approval', s.msg);
                        $('.form-actions .btn-success').attr('disabled', false);
                        $('.form-actions .btn-success i').removeClass('fa-sync fa-spin');
                        if (s.type == 'success') {
                            $("#upload-docs").modal('toggle');
                            $(".reload").trigger('click');
                        }
                    } else {
                        UIToastr.init('error', 'Distributor Approval', 'Unable to process request. Please try again later');
                        $('.form-actions .btn-success').attr('disabled', false);
                        $('.form-actions .btn-success i').removeClass('fa-sync fa-spin');
                    }
                }, 100);
            }
        });
    };

    // COMMAN FUNCTION
    var handleCertificateGeneration = function () {
        $('.generate_certificate').off('click').on('click', function (e) {
            e.preventDefault();
            var button = $(this);

            var formData = new FormData();
            formData.append('action', 'generate_certificate');
            formData.append('party_id', button.data('partyid'));
            formData.append('brand_name', button.data('brandname'));
            formData.append('is_renewal', button.data('is_renewal'));

            var brand_key = button.data('key');

            button.attr('disabled', true);
            button.find('i').addClass('fa fa-sync fa-spin');
            window.setTimeout(function () {
                var s = submitForm(formData, "POST");
                if (s.type == 'success' || s.type == 'error') {
                    UIToastr.init(s.type, 'Generate Certificate', s.msg);
                    if (s.type == 'success') {
                        button.addClass('btn-success');
                        button.find('i').removeClass('fa-sync fa-spin');
                        button.next('.view_certificate').attr('disabled', false);
                        $('#brand_file_' + brand_key).val(s.file);
                    } else {
                        button.attr('disabled', false);
                        button.find('i').removeClass('fa-sync fa-spin');
                    }
                } else {
                    button.attr('disabled', false);
                    button.find('i').removeClass('fa-sync fa-spin');
                    UIToastr.init('error', 'Generate Certificate', 'Unable to process request. Please try again later');
                }
            }, 10);
        });

        $('.view_certificate').off('click').on('click', function (e) {
            e.preventDefault();

            var form = document.createElement("form");
            form.setAttribute("method", "post");
            form.setAttribute("action", "ajax_load.php");
            form.setAttribute("target", "_blank");

            var params = {
                "action": "generate_certificate",
                "party_id": $(this).data('partyid'),
                "brand_name": $(this).data('brandname'),
                "view": $(this).data('view'),
                "is_renewal": $(this).data('is_renewal'),
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
    };

    return {
        //main function to initiate the module
        init: function ($type) {
            if ($type == "seller_approval") {
                sellerApproval_handleTable();
                sellerApproval_handleValidation();
            } else if ($type == "distributor_approval") {
                // // distributorOnboard_handleTable();
                // distributorOnboard_handleTable_v1();
                // distributorOnboard_handleValidation();
                distributorOnboard_handleTable();
                distributorOnboard_handleValidation();
                sellerApproval_handleValidation();
            } else if ($type == "approved_sellers") {
                sellerApproved_handleTable();
            }
        }
    };
}();