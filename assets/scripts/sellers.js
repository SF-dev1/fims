var Sellers = function () {

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

    var sellersRegistration_handleForm = function () {

        // // INIT debugger
        // debugger;

        $("[name='party_gst']").inputmask({
            "mask": "99AAAAA9999A9**",
            definitions: {
                "*": {
                    casing: "upper"
                }
            },
            placeholder: "",
        });

        $("[name='party_mobile']").inputmask({
            "mask": "9999999999",
        });

        // Input Tags
        if (!jQuery().tagsInput) {
            return;
        }

        $('.party_brands').tagsInput({
            width: 'auto',
            trimValue: true,
            defaultText: 'Add Brand',
        });

        $('.party_categories').tagsInput({
            width: 'auto',
            trimValue: true,
            defaultText: 'Add Categories',
        });

        $(".tagsinput").addClass('form-control');

        // Dynamic addtion of marketplace additional info
        $('.marketplace').click(function () {
            $marketplace = $(this).val();
            if ($(this).is(":checked")) {
                addMarketplaceAdditionalInfo($marketplace);
            } else {
                removeMarketplaceAdditionalInfo($marketplace);
            }
        });

        $('.other_marketplace').change(function () {
            if ($(this).is(':checked')) {
                $('.other_marketplace_input').empty().val("");
                $('.other_marketplace_input').removeClass('hide');
                $('.other_marketplace_input').removeAttr('disabled');
            } else {
                $('.other_marketplace_input').empty().val("");
                $('.other_marketplace_input').addClass('hide');
                $('.other_marketplace_input').attr('disabled', 'disabled');
            }
        });
    };

    var addMarketplaceAdditionalInfo = function ($marketplace) {
        if ($marketplace == "other") {
            var $other_marketplaces = "";
            $('.other_marketplace_input').off('blur').on('blur', function () {
                $other_marketplaces = $(this).val().split(',');
                $.each($other_marketplaces, function (index, marketplace) {
                    $marketplace = $.trim(marketplace);
                    addMarketplaceDisplayNameInput($marketplace);
                });
            });
        } else {
            addMarketplaceDisplayNameInput($marketplace);
        }

        $('.qty_' + $marketplace).change(function () {
            $units = $(this).val();
            $(this).parent('div').find('.marketplace_displayname_inputs').html("");
            for (var i = 0; i < $units; i++) {
                $displayName = '<div class="col-md-6 display_name_field display_name_' + $marketplace + '_' + i + '">' +
                    '<input type="text" class="form-control col-md-6 display_name_field_' + $marketplace + '_' + i + '" data-marketplace="' + $marketplace + '" placeholder="Display Name" name="party_marketplace[' + $marketplace + '][' + i + ']" autocomplete="off" required/>' +
                    '</div>';
                $(this).parent('div').find('.marketplace_displayname_inputs').append($displayName);
            }

            $('.display_name_field input').each(function () {
                $(this).rules("add", {
                    required: true,
                });
            });
        });
    };

    var removeMarketplaceAdditionalInfo = function ($marketplace) {
        if ($marketplace == "other") {
            $other_marketplaces = $('.other_marketplace_input').val().split(',');
            $($other_marketplaces).each(function (index, $marketplace) {
                $marketplace = $.trim($marketplace);
                $('.marketplace_' + $marketplace).remove();
            });
        } else {
            $('.marketplaces_additional_info').find('.marketplace_' + $marketplace).remove();
        }
    };

    var addMarketplaceDisplayNameInput = function ($marketplace) {
        $additional_mp = '<div class="form-group marketplace marketplace_' + $marketplace + '">' +
            '<div class="col-md-12">' +
            '<label class="control-label col-md-3">No. of account on ' + $marketplace.charAt(0).toUpperCase() + $marketplace.slice(1) + ' <span class="required">* </span></label>' +
            '<div class="col-md-4">' +
            '<input type="number" class="form-control input-medium marketplace_qty qty_' + $marketplace + '" min="1" max="10" placeholder="No. of accounts" value="1" autocomplete="off" required/><br />' +
            '<div class="row form-group marketplace_displayname_inputs">' +
            '<div class="col-md-6 display_name_field display_name_' + $marketplace + '_0">' +
            '<input type="text" class="form-control col-md-6 display_name_field_' + $marketplace + '_0" data-marketplace="' + $marketplace + '" placeholder="Display Name" name="party_marketplace[' + $marketplace + '][0]" autocomplete="off" required/>' +
            '</div>'
        '</div>' +
            '</div>' +
            '</div>' +
            '</div>';
        $('.marketplaces_additional_info').append($additional_mp);
    };

    var sellersRegistration_handleValidation = function () {
        if (!jQuery().bootstrapWizard) {
            return;
        }

        var form = $('#new-seller-registration');
        var error = $('.alert-danger', form);
        var success = $('.alert-success', form);

        jQuery.validator.addMethod("checkTags", function (value, element) { //add custom method
            return (value != "");
        }, "Please add brand name");

        $.validator.addMethod("requiredIfChecked", function (val, ele, arg) {
            if ($(".other_marketplace").is(":checked") && ($.trim(val) == '')) { return false; }
            return true;
        }, "Please enter other marketplaces name you are currently selling on");

        form.validate({
            doNotHideMessage: true, //this option enables to show the error/success messages on tab switch.
            errorElement: 'span', //default input error message container
            errorClass: 'help-block', // default input error message class
            focusInvalid: false, // do not focus the last invalid input
            rules: {
                party_name: {
                    required: true,
                },
                party_gst: {
                    required: true,
                    minlength: 15,
                    maxlength: 15,
                    remote: 'ajax_load.php?action=check_exists&type=gst',
                },
                party_address: {
                    required: true
                },
                party_categories: {
                    required: true,
                },
                party_brands: {
                    required: true,
                },
                party_brands_others: {
                    required: true,
                },
                'marketplace_checkbox[]': {
                    required: true,
                    minlength: 1
                },
                'brand_checkbox[]': {
                    required: true,
                    minlength: 1
                },
                other_marketplace_input: {
                    requiredIfChecked: true,
                },
                party_email: {
                    required: true,
                    email: true,
                    remote: 'ajax_load.php?action=check_exists&type=email',
                },
                party_mobile: {
                    required: true,
                    minlength: 10,
                    maxlength: 10,
                    digits: true,
                    remote: 'ajax_load.php?action=check_exists&type=mobile',
                }
            },

            messages: { // custom messages for radio buttons and checkboxes
                party_name: {
                    required: "Please provide your Firm/Company Name"
                },
                party_gst: {
                    required: "Please provide your Firm/Company GST Registration Number",
                    remote: "GST number is already registered"
                },
                party_address: {
                    required: "Please provide your current address"
                },
                'marketplace_checkbox[]': {
                    required: "Please select marketplaces you are currently selling on",
                    minlength: $.validator.format("Please select at least one marketplace")
                },
                'brand_checkbox[]': {
                    required: "Please select brands you are currently selling on",
                    minlength: $.validator.format("Please select at least one brand")
                },
                party_categories: {
                    required: "Please add currently selling categories names"
                },
                party_brands: {
                    required: "Please add your brand names"
                },
                party_brands_others: {
                    required: "Please add currently selling other brand names"
                },
                party_email: {
                    required: "Please provide your email address",
                    remote: "Email ID is already registered"
                },
                party_mobile: {
                    required: "Please provide your mobile address",
                    remote: "Mobile number is already registered"
                }
            },

            errorPlacement: function (error, element) { // render error placement for each input type
                if (element.attr("name") == "party_brands") { // for uniform radio buttons, insert the after the given container
                    error.insertAfter("#brand_tags_tagsinput");
                } else if (element.attr("name") == "party_brands_others") { // for uniform radio buttons, insert the after the given container
                    error.insertAfter("#brand_tags_others_tagsinput");
                } else if (element.attr("name") == "party_categories") { // for uniform radio buttons, insert the after the given container
                    error.insertAfter("#party_categories_tagsinput");
                } else if (element.attr("name") == "marketplace_checkbox[]") { // for uniform radio buttons, insert the after the given container
                    error.insertAfter("#form_marketplace_error");
                } else if (element.attr("name") == "brand_checkbox[]") { // for uniform radio buttons, insert the after the given container
                    error.insertAfter("#form_brand_error");
                } else if (element.attr("name") == "other_marketplace_input") {
                    error.insertAfter(element.parent('label'));
                } else if (element.parent('div').hasClass("display_name_field")) {
                    error.insertAfter(element)
                } else {
                    error.insertAfter(element); // for other inputs, just perform default behavior
                }
            },

            invalidHandler: function (event, validator) { //display error alert on form submit   
                success.hide();
                error.show();
                // console.log(error);
                App.scrollTo(error.parent('div'), -245);
            },

            highlight: function (element) { // hightlight error inputs
                if ($(element).parent('div').hasClass("display_name_field")) {
                    $(element)
                        .closest('.display_name_field').removeClass('has-success').addClass('has-error'); // set error class to the control group
                } else if ($(element).hasClass("other_marketplace_input")) {
                    $(element)
                        .closest('div').removeClass('has-success').addClass('has-error'); // set error class to the control group
                } else {
                    $(element)
                        .closest('.form-group').removeClass('has-success').addClass('has-error'); // set error class to the control group
                }
            },

            unhighlight: function (element) { // revert the change done by hightlight
                if ($(element).parent('div').hasClass("display_name_field")) {
                    $(element)
                        .closest('.display_name_field').removeClass('has-error'); // set error class to the control group
                } else if ($(element).hasClass("other_marketplace_input")) {
                    $(element)
                        .closest('div').removeClass('has-error'); // set error class to the control group
                } else {
                    $(element)
                        .closest('.form-group').removeClass('has-error'); // set error class to the control group
                }
            },

            success: function (label) {
                if (label.attr("for") == "gender" || label.attr("for") == "payment[]") { // for checkboxes and radio buttons, no need to show OK icon
                    label
                        .closest('.form-group').removeClass('has-error').addClass('has-success');
                    label.remove(); // remove error label here
                } else { // display success icon for other inputs
                    label
                        .addClass('valid') // mark the current input as valid and display OK icon
                        .closest('.form-group').removeClass('has-error').addClass('has-success'); // set success class to the control group
                }
            },

            submitHandler: function (form) {
                success.show();
                error.hide();
                //add here some ajax code to submit your form or just call form.submit() if you want to submit the form without ajax
            }
        });

        var displayConfirm = function () {
            $('#tab4 .form-control-static', form).each(function () {
                var input = $('[name="' + $(this).attr("data-display") + '"]', form);
                if (input.is(":text") || input.is("textarea")) {
                    $(this).html(input.val());
                } else if (input.is("select")) {
                    $(this).html(input.find('option:selected').text());
                } else if (input.is(":radio") && input.is(":checked")) {
                    $(this).html(input.attr("data-title"));
                } else if ($(this).attr("data-display") == 'marketplace') {
                    var marketplace = [];
                    $("input[name^='party_marketplace[']").each(function () {
                        $marketplace_name = $(this).attr('data-marketplace').charAt(0).toUpperCase() + $(this).attr('data-marketplace').slice(1)
                        marketplace.push($marketplace_name + ' - ' + $(this).val());
                    });
                    $(this).html(marketplace.join("<br>"));
                } else if ($(this).attr("data-display") == 'brand_checkbox') {
                    brands = [];
                    $("input[name^='brand_checkbox[']").each(function () {
                        if ($(this).is(":checked"))
                            brands.push($(this).attr('data-title'));
                    });
                    $(this).html(brands.join("<br>"));
                }
            });
        }

        var handleTitle = function (tab, navigation, index) {
            var total = navigation.find('li').length;
            var current = index + 1;
            // set wizard title
            $('.step-title', $('#form_wizard_1')).text('Step ' + (index + 1) + ' of ' + total);
            // set done steps
            jQuery('li', $('#form_wizard_1')).removeClass("done");
            var li_list = navigation.find('li');
            for (var i = 0; i < index; i++) {
                jQuery(li_list[i]).addClass("done");
            }

            if (current == 1) {
                $('#form_wizard_1').find('.button-previous').hide();
            } else {
                $('#form_wizard_1').find('.button-previous').show();
            }

            if (current >= total) {
                $('#form_wizard_1').find('.button-next').hide();
                $('#form_wizard_1').find('.button-submit').show();
                displayConfirm();
            } else {
                $('#form_wizard_1').find('.button-next').show();
                $('#form_wizard_1').find('.button-submit').hide();
            }
            App.scrollTo($('.page-title'));
        }

        // default form wizard
        $('#form_wizard_1').bootstrapWizard({
            'nextSelector': '.button-next',
            'previousSelector': '.button-previous',
            onTabClick: function (tab, navigation, index, clickedIndex) {
                success.hide();
                error.hide();
                if (form.valid() == false) {
                    return false;
                }
                handleTitle(tab, navigation, clickedIndex);
            },
            onNext: function (tab, navigation, index) {
                success.hide();
                error.hide();

                if (!form.valid()) {
                    return false;
                }

                if (index == 1) {
                    form.validate().settings.ignore = ":hidden:not(.party_brands)";
                    form.validate().settings.ignore = ":hidden:not(.party_categories)";
                }

                handleTitle(tab, navigation, index);
            },
            onPrevious: function (tab, navigation, index) {
                success.hide();
                error.hide();

                if (index != 1) {
                    form.validate().settings.ignore = "";
                }

                handleTitle(tab, navigation, index);
            },
            onTabShow: function (tab, navigation, index) {
                var total = navigation.find('li').length;
                var current = index + 1;
                var $percent = (current / total) * 100;
                $('#form_wizard_1').find('.progress-bar').css({
                    width: $percent + '%'
                });
            }
        });

        $('#form_wizard_1').find('.button-previous').hide();
        $('#form_wizard_1 .button-submit').click(function () {
            $('.form-actions .btn-success', form).attr('disabled', true);
            $('.form-actions .btn-success i', form).addClass('fa fa-sync fa-spin');
            var el = jQuery(this).closest(".portlet").children(".portlet-body");
            App.blockUI({ target: el });

            var other_data = $(form).serializeArray();
            var formData = new FormData();
            $.each(other_data, function (key, input) {
                formData.append(input.name, input.value);
            });

            window.setTimeout(function () {
                var s = submitForm(formData, "POST");
                if (s.type == 'success' || s.type == 'error') {
                    UIToastr.init(s.type, 'New Seller', s.msg);
                    if (s.type == 'success') {
                        window.setTimeout(function () {
                            window.location.replace('seller_view.php');
                        }, 3 * 1000); // x second * 1000
                    } else {
                        App.unblockUI(el);
                        $('.form-actions .button-submit', form).attr('disabled', false);
                        $('.form-actions .button-submit i', form).removeClass('fa-sync fa-spin');
                    }
                } else {
                    App.unblockUI(el);
                    $('.form-actions .button-submit', form).attr('disabled', false);
                    $('.form-actions .button-submit i', form).removeClass('fa-sync fa-spin');
                    UIToastr.init('error', 'New Seller', s.msg);
                }
            }, 100);
        }).hide();
    };

    var sellers_handleTable = function () {
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

        var table = $('#view_sellers');
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
                url: "ajax_load.php?action=view_sellers&client_id=" + client_id + "&token=" + new Date().getTime(),
                cache: false,
                type: "POST",
            },
            columns: [
                {
                    data: "party_name",
                    title: "Seller Name",
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
                    title: "GSTIN",
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

        function sellerRenewal_handleProcess() {
        };

        function drawCallBackDataTable() {
            $(".upload_docs").click(function () {
                $sellerId = $(this).data('sellerid');
                $sellerName = $(this).data('sellername');
                $sellerGST = $(this).data('sellergst');
                $("#party_name_gst").text($sellerName + ' - [' + $sellerGST + ']');
                $("#party_name").val($sellerName);
                $("#party_id").val($sellerId);
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

    var sellerDocs_handleValidation = function () {
        var form = $('#upload-docs');
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

                var other_data = $(form).serializeArray();
                var formData = new FormData();
                formData.append('action', 'upload_docs');
                formData.append('party_gst_certificate', $("#party_gst_certificate")[0].files[0]);
                formData.append('party_signed_tnc', $("#party_signed_tnc")[0].files[0]);
                $.each(other_data, function (key, input) {
                    formData.append(input.name, input.value);
                });

                $('.form-actions .btn-submit', form).attr('disabled', true);
                $('.form-actions .btn-submit i', form).addClass('fa fa-sync fa-spin');

                var s = submitForm(formData, "POST", "multipart/form-data");
                if (s.type == 'success' || s.type == 'error') {
                    UIToastr.init(s.type, 'Seller Documents Upload', s.msg);
                    $('.form-actions .btn', form).attr('disabled', false);
                    $('.form-actions i', form).removeClass('fa-sync fa-spin');
                    if (s.type == 'success') {
                        $("#upload_docs").modal('toggle');
                        $(".reload").trigger('click');
                    }
                } else {
                    UIToastr.init('error', 'Sale Order', 'Unable to process request. Please try again later');
                }
            }
        });
    };

    var sellerAddListing_handleForm = function () {
        var s = "";
        var $marketplace = $('#marketplace');
        var $accountMenu = $('#account');
        $marketplace.select2("val", "").prop('disabled', true);
        $accountMenu.select2("val", "").prop('disabled', true);
        $("#seller_name").change(function () {
            $seller_id = $("#seller_name option:selected").val();
            if ($seller_id == "") {
                $marketplace.select2("val", "").prop('disabled', true).trigger("change");
                $accountMenu.select2("val", "").prop('disabled', true).trigger("change");
                return;
            }

            var formData = "action=get_seller_marketplaces&seller_id=" + $seller_id + "&client_id=" + client_id;
            s = submitForm(formData, "GET");

            $('[name="seller_id"]').val($seller_id);
            $marketplace.select2("val", "").prop('disabled', false);
            $m_options = "<option value=''></option>";
            $.each(s, function (marketplace) {
                $m_options += "<option value=" + marketplace + ">" + marketplace.charAt(0).toUpperCase() + marketplace.slice(1) + "</option>";
            });
            $marketplace.empty().append($m_options);
        });

        $marketplace.bind('change', function () {
            if ($(this).val() == "") {
                $accountMenu.select2("val", "").prop('disabled', true);
                return;
            }
            $accountMenu.select2("val", "").prop('disabled', false);
            $a_options = "<option value=''></option>";
            $.each(s[$(this).val()], function (display_names, account) {
                $a_options += "<option value=" + account + ">" + account + "</option>";
            });
            $accountMenu.empty().append($a_options);
        });
    };

    var sellerAddListing_handleValidation = function () {
        console.log('sellerAddListing_handleValidation');
        var form = $('#add-listing');
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
                // error.show();
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

                var other_data = $(form).serializeArray();
                var formData = new FormData();
                formData.append('action', 'add_seller_listing');
                $.each(other_data, function (key, input) {
                    formData.append(input.name, input.value);
                });

                $('.form-actions .btn-submit', form).attr('disabled', true);
                $('.form-actions .btn-submit i', form).addClass('fa fa-sync fa-spin');

                var s = submitForm(formData, "POST", "multipart/form-data");
                if (s.type == 'success' || s.type == 'error') {
                    UIToastr.init(s.type, 'Sellers Listing', s.msg);
                    $('.form-actions .btn', form).attr('disabled', false);
                    $('.form-actions i', form).removeClass('fa-sync fa-spin');
                    if (s.type == 'success') {
                        $("#add_listing").modal('toggle');
                        $(".reload").trigger('click');
                    }
                } else {
                    UIToastr.init('error', 'Sellers Listing', 'Unable to process request. Please try again later');
                }
            }
        });
    };

    var sellerListing_handleTable = function () {
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

        var table = $('#editable_sellers_listings');
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
                url: "ajax_load.php?action=get_sellers_listings&client_id=" + client_id + "&token=" + new Date().getTime(),
                cache: false,
                type: "GET",
            },
            columns: [
                {
                    data: "party_name",
                    title: "Seller Name",
                    columnFilter: 'selectFilter'
                }, {
                    data: "marketplace",
                    title: "Marketplace",
                    columnFilter: 'selectFilter'
                }, {
                    data: "account",
                    title: "Account",
                    columnFilter: 'selectFilter'
                }, {
                    data: "sku",
                    title: "SKU",
                    columnFilter: 'inputFilter'
                }, {
                    data: "mp_id",
                    title: "FSN/ASIN",
                    columnFilter: 'inputFilter'
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
                    targets: [4],
                    width: '170px',
                    render: function (a, t, e, s) {
                        if (e.marketplace == "flipkart" || e.marketplace == "amazon") {
                            var url = e.marketplace == "flipkart" ? fk_url.replace('FSN', a) : az_url.replace('ASIN', a);
                            $return = '<span class="mp_id">' + a + '</span><a class="fa fa-external-link-alt pull-right" href="' + url + '" target="_blank"></a>';
                            return $return;
                        }
                        return '<span class="mp_id">' + a + '</span>';
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
                // $("[class='auto_update']").bootstrapSwitch();
                // $("[class='fulfilled']").bootstrapSwitch();
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
                var cData = oTable.row(nRow).data();
                var jqTds = $('>td', nRow);
                var aData = [];
                for (var i in cData) {
                    aData.push(cData[i]);
                }

                // jqTds[1].innerHTML = '<select id="marketplaceMenu" class="input-medium selection form-control"></select>';
                // jqTds[2].innerHTML = '<select id="accountMenu" class="input-medium selection form-control"></select>';
                jqTds[3].innerHTML = '<select id="all_skus" class="input-medium selection form-control"></select>';
                jqTds[4].innerHTML = '<input type="text" class="form-control input-medium" value="' + aData[4] + '">';
                jqTds[5].innerHTML = '<a class="edit btn btn-default btn-xs purple" href=""><i class="fa fa-check"></i></a><a class="cancel btn btn-default btn-xs purple" href=""><i class="fa fa-times"></i></a>';

                var skus = all_skus;
                var s_options = '';
                $.each(skus, function (k, v) {
                    var selected = "";
                    if (aData[3] == v.value) {
                        selected = "selected = 'selected'";
                    }
                    s_options += "<option " + selected + "value=" + v.key + ">" + v.value + "</option>";
                });
                $('#all_skus').append(s_options);

                /* // MARKETPLACE AND ACCOUNT OPTIONS
                 var a_options = new Array();
                 var m_options = ''; 
                 var current_marketplace = aData[4].toLowerCase();
                 $.each(accounts, function (account_k, account) {
                     a_options[account_k] = "";
                     var selected = "";
                     if (current_marketplace == account_k){
                         selected = "selected = 'selected'";
                     }
                     m_options += "<option " + selected + "value="+account_k+">"+account_k.charAt(0).toUpperCase() + account_k.slice(1)+"</option>";
 
                     $.each(account, function(k, v){
                         var selected = "";
                         if (aData[5] == v.account_name){
                             selected = "selected = 'selected'";
                         }
                         a_options[account_k] += "<option " + selected + "value="+v.account_id+">"+v.account_name+"</option>";
                     });
                 });
                 $('#accountMenu').append(a_options[current_marketplace]);
                 $('#marketplaceMenu').append(m_options);
 
                 var $marketplace = $('#marketplaceMenu');
                 var $accountMenu = $('#accountMenu');
                 $marketplace.change(function() {
                     $accountMenu.select2("val", "");
                     $accountMenu.empty().append(a_options[$(this).val()]);
                 });*/

                // Initialize select2me
                $('.selection').select2({
                    placeholder: "Select",
                    allowClear: true
                });
            }

            function saveRow(oTable, nRow) {
                var aData = oTable.row(nRow).data();
                var jqInputs = $('input:not([class^=select2])', nRow);

                var jqTextarea = $('textarea', nRow);
                var jqSelect = $('select', nRow);
                var parent_sku = jqSelect[0].value;
                var parent_sku_value = $('#all_skus option:selected').text();

                aData = { // SET NEW DATA TO TABLE
                    'party_name': aData.party_name,
                    'marketplace': aData.marketplace,
                    'account': aData.account,
                    'sku': parent_sku_value,
                    'mp_id': jqInputs[0].value,
                    'seller_listing_id': aData.seller_listing_id,
                };
                oTable.row(nRow).data(aData);

                var formData = new FormData();
                formData.append('action', 'update_seller_listing');
                formData.append('seller_listing_id', aData.seller_listing_id);
                formData.append('party_name', aData.party_name);
                formData.append('marketplace', aData.marketplace);
                formData.append('account', aData.account);
                formData.append('pid', parent_sku);
                formData.append('mp_id', aData.mp_id);

                var s = submitForm(formData, "POST");
                if (s.type == "success" || s.type == "error") {
                    UIToastr.init(s.type, 'Sellers Lisitng Update', s.msg);
                } else {
                    UIToastr.init('error', 'Sellers Lisitng Update', "Error processing request. Please try again later.");
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

                if (confirm("Are you sure to delete this listing?")) {
                    var nRow = $(this).parents('tr')[0];
                    var cData = oTable.row(nRow).data();

                    var formData = new FormData();
                    formData.append('action', 'delete_seller_listing');
                    formData.append('seller_listing_id', aData.seller_listing_id);
                    var s = submitForm(formData, "POST");
                    if (s.type == "success" || s.type == "error") {
                        UIToastr.init(s.type, 'Sellers Lisitng Delete', s.msg);
                    } else {
                        UIToastr.init('error', 'Sellers Lisitng Delete', "Error processing request. Please try again later.");
                    }
                    oTable.api().ajax.reload();
                    return;
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

    return {
        //main function to initiate the module
        init: function ($type) {
            if ($type == "new") {
                sellersRegistration_handleForm();
                sellersRegistration_handleValidation();
            } else if ($type == "view") {
                sellers_handleTable();
                sellerDocs_handleValidation();
            } else if ($type == "listing") {
                sellerListing_handleTable();
                sellerAddListing_handleForm();
                sellerAddListing_handleValidation();
            }
        }
    };
}();