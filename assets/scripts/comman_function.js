function ajaxcall(url, data, callback) {
    //  App.startPageLoading();

    $.ajax({
        type: 'POST',
        url: url,
        data: data,
        async: false,
        success: function (result) {
            //   App.stopPageLoading();
            callback(result);
        }
    })
}

function handleAjaxFormSubmit(form, type) {

    if (typeof type === 'undefined') {
        ajaxcall($(form).attr('action'), $(form).serialize(), function (output) {
            handleAjaxResponse(output);
        });
    } else if (type === true) {
        // App.startPageLoading();
        var options = {
            resetForm: false, // reset the form after successful submit
            success: function (output) {
                //   App.stopPageLoading();
                handleAjaxResponse(output);
            }
        };
        $(form).ajaxSubmit(options);
    }
    return false;
}

function handleAjaxFormMySubmit(form, type) {
    $(".submitbtn:visible").attr("disabled", "disabled");
    $("#loader").show();

    if (typeof type === 'undefined') {
        ajaxcall($(form).attr('action'), $(form).serialize(), function (output) {
            handleAjaxResponse(output);
        });
    } else if (type === true) {
        // App.startPageLoading();
        var options = {
            resetForm: false, // reset the form after successful submit
            success: function (output) {
                //   App.stopPageLoading();
                handleAjaxResponse(output);
            }
        };
        $(form).ajaxSubmit(options);
    }
    return false;
}

function showToster(status, message) {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 2500
    };
    if (status == 'success') {
        toastr.success(message, 'Success');
    }
    if (status == 'error') {
        toastr.error(message, 'Fail');

    }
    if (status == 'warning') {
        toastr.warning(message, 'Warning');

    }
}

function handleAjaxResponse(output) {

    output = JSON.parse(output);
    $('#deleteModel').removeClass('show');
    $("#deleteModel").css({ 'display': 'none' });
    var html = "";




    if (output.status == 'success') {
        html = '<div class="alert alert-custom alert-notice alert-light-success fade show mb-5" role="alert">' +
            '<div class="alert-icon">' +
            '<i class="fa fa-check-circle-o"></i>' +
            '</div>' +
            '<div class="alert-text">' + output.message + '</div>' +

            '</div>';
    }
    if (output.status == 'error') {
        html = '<div class="alert alert-custom alert-notice alert-light-danger fade show mb-5" role="alert">' +
            '<div class="alert-icon">' +
            '<i class="fa fa-close"></i>' +
            '</div>' +
            '<div class="alert-text">' + output.message + '</div>' +
            '</div>';
    }
    if (output.status == 'warning') {
        html = '<div class="alert alert-custom alert-notice alert-light-warning fade show mb-5" role="alert">' +
            '<div class="alert-icon">' +
            '<i class="fa fa-exclamation-triangle"></i>' +
            '</div>' +
            '<div class="alert-text">A simple warning alert—check it out!</div>' +
            '</div>';
    }
    $("#alertDiv").html(html);
    if (typeof output.redirect !== 'undefined' && output.redirect != '') {
        setTimeout(function () {
            window.location.href = output.redirect;
        }, 2000);
    }

    if (typeof output.jscode !== 'undefined' && output.jscode != '') {
        eval(output.jscode);
    }

}

function handleAjaxResponse(output) {

    output = JSON.parse(output);

    if (output.message != '') {
        showToster(output.status, output.message, '');
    }
    if (typeof output.redirect !== 'undefined' && output.redirect != '') {
        setTimeout(function () {
            window.location.href = output.redirect;
        }, 4000);
    }
    if (typeof output.reload !== 'undefined' && output.reload != '') {
        window.location.href = location.reload();
    }
    if (typeof output.jscode !== 'undefined' && output.jscode != '') {
        eval(output.jscode);
    }
}

function _fn_getQueryStringValue(name) {
    var regex = new RegExp("[\\?&]" + name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]") + "=([^&#]*)"),
        results = regex.exec(window.location.search);
    return results ? decodeURIComponent(results[1].replace(/\+/g, " ")) : '';
}

function handleFormValidate(form, rules, submitCallback, showToaster) {

    var error = $('.alert-danger', form);
    var success = $('.alert-success', form);
    form.validate({
        errorElement: 'span', //default input error message container
        errorClass: 'help-block', // default input error message class
        focusInvalid: false, // do not focus the last invalid input
        ignore: ":hidden",
        rules: rules,
        invalidHandler: function (event, validator) { //display error alert on form submit
            success.hide();
            error.show();
            //            App.scrollTo(error, -200);
            if (typeof showToaster !== 'undefined' && showToaster) {
                Toastr.init('warning', 'Some fields are missing!.', '');
            }
        },
        showErrors: function (errorMap, errorList) {
            if (typeof errorList[0] != "undefined") {
                var position = $(errorList[0].element).offset().top - 70;
                $('html, body').animate({
                    scrollTop: position
                }, 300);
            }
            this.defaultShowErrors(); // keep error messages next to each input element
        },

        highlight: function (element) { // hightlight error inputs

            $(element)
                .closest('.c-input, .form-control').addClass('has-error'); // set error class to the control group

            $(element).parent().parent().find('.select2').addClass('has-error');

        },
        unhighlight: function (element) { // revert the change done by hightlight
            $(element)
                .closest('.c-input, .form-control').removeClass('has-error'); // set error class to the control group
            $(element)
                .closest('.c-input, .form-control').addClass('is-valid'); // set error class to the control group
        },
        success: function (label) {
            label.closest('.c-input, .form-control').removeClass('has-error'); // set success class to the control group
            label.closest('.c-input, .form-control').addClass('is-valid'); // set error class to the control group
        },
        errorPlacement: function (error, element) {
            return true;
        },

        submitHandler: function (form) {
            $(".submitbtn:visible").attr("disabled", "disabled");
            $("#loader").show();
            // form.submit();
            if (typeof submitCallback !== 'undefined' && typeof submitCallback == 'function') {
                submitCallback(form);
            } else {
                handleAjaxFormSubmit(form);
            }
            return false;
        }
    });

    $('.select2me', form).change(function () {
        form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
    });
    $('.date-picker .form-control').change(function () {
        form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
    })
}

function handleFormValidateWithMsg(form, rules, messages, submitCallback, showToaster) {

    var error = $('.alert-danger', form);
    var success = $('.alert-success', form);
    form.validate({
        errorElement: 'span', //default input error message container
        errorClass: 'help-block', // default input error message class
        focusInvalid: true, // do not focus the last invalid input
        ignore: ":hidden",
        rules: rules,
        invalidHandler: function (event, validator) { //display error alert on form submit
            success.hide();
            error.show();

            //            App.scrollTo(error, -200);
            if (typeof showToaster !== 'undefined' && showToaster) {
                Toastr.init('warning', 'Some fields are missing!.', '');
            }
            //            Toastr.init('warning', 'Some fields are missing!.', '');
        },
        highlight: function (element) { // hightlight error inputs
            // $(element)
            //     .closest('.form-control').addClass('has-error'); // set error class to the control group
            // $(element).parent().find('.select2').addClass('has-error');
        },
        unhighlight: function (element) { // revert the change done by hightlight
            // $(element).parent().find('.select2').removeClass('has-error');
            // $(element)
            //     .closest('.form-control').removeClass('has-error'); // set error class to the control group
        },
        success: function (label) {
            // label.closest('.form-control').removeClass('has-error'); // set success class to the control group
            // label.parent().find('.select2').removeClass('has-error');
        },
        messages: messages,

        submitHandler: function (form) {

            $(".submitbtn:visible").attr("disabled", "disabled");
            $("#loader").show();
            if (typeof submitCallback !== 'undefined' && typeof submitCallback == 'function') {
                submitCallback(form);
            } else {
                handleAjaxFormSubmit(form);
            }
            return false;
        },

        errorPlacement: function (error, element) {
            var elem = $(element);
            if (elem.hasClass("select2-hidden-accessible")) {
                element = $("#select2-" + elem.attr("id") + "-container").parent();
                error.insertAfter(element);
            } else {
                if (elem.hasClass("radio-btn")) {
                    element = elem.parent().parent();
                    error.insertAfter(element);
                } else {
                    error.insertAfter(element);
                }
            }
        },
    });

    $('.select2me', form).change(function () {
        form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
    });
    $('.date-picker .form-control').change(function () {
        form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
    })
}

function gritter(title, text, sticky, time) {
    $.gritter.add({
        // (string | mandatory) the heading of the notification
        title: title,
        // (string | mandatory) the text inside the notification
        text: text,
        // (string | optional) the image to display on the left
        //                    image1: './assets/img/avatar1.jpg',
        // (bool | optional) if you want it to fade out on its own or just sit there
        sticky: sticky,
        // (int | optional) the time you want it to be alive for before fading out
        time: time,
        // (string | optional) the class name you want to apply to that specific message
        class_name: 'my-sticky-class'
    });

}

var Toastr = function () {

    return {
        //main function to initiate the module
        init: function (type, title, message) {
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "positionClass": "toast-top-center",
                "onclick": null,
                "showDuration": "1000",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"

            }
            toastr[type](message, title);
        }

    };

}();

function handleDelete() {

    $('body').on('click', '#btndelete', function () {
        var data = '';
        var thumb = $(this).attr('data-thumb');
        if (thumb) {
            data = { 'id': $(this).attr('data-id'), 'thumb': thumb };
        } else {
            data = { 'id': $(this).attr('data-id'), '_token': $("input[name=_token]").val() };
        }
        ajaxcall($(this).attr('data-url'), data, function (output) {
            $('#myModal_autocomplete').modal('hide');
            handleAjaxResponse(output);
        });
    });
}

function handleDeleteData() {

    var delete_records_value = '';
    var delete_model_name = '';
    $('body').on('click', '.delete_confirmation_btn', function () {

        var checked_value = $('input[type="checkbox"].delete_checkbox_id:checked');
        if (checked_value.length > 0) {
            delete_model_name = $(this).attr('data-model-open');
            $(delete_model_name).modal('show');
            for (var i = 0; i < checked_value.length; i++) {
                delete_records_value += $(checked_value[i]).attr('data-id');
                if (i != checked_value.length - 1) {
                    delete_records_value += ",";
                }
            }
        } else {
            Toastr.init('warning', 'Please select atleast one record', '');
        }
    });
    $('body').on('click', '#multiple_delete_btn', function () {
        var data = { 'id': delete_records_value };
        ajaxcall($(this).attr('data-url'), data, function (output) {
            $(delete_model_name).modal('hide');
            var temp_array = delete_records_value.split(',');
            for (var i = 0; i < temp_array.length; i++) {
                $('input[type="checkbox"].delete_checkbox_id[data-id="' + temp_array[i] + '"]').parents('tr').hide();
            }
            handleAjaxResponse(output);
        });
    });
}

function handleTimePickers() {

    if (jQuery().timepicker) {
        $('.timepicker-default').timepicker({
            autoclose: true,
            //showSeconds: true,
            minuteStep: 1
        });

        $('.timepicker-no-seconds').timepicker({
            autoclose: true,
            minuteStep: 5
        });

        $('.timepicker-24').timepicker({
            autoclose: true,
            minuteStep: 5,
            showSeconds: true,
            showMeridian: false
        });

        // handle input group button click
        $('.timepicker').parent('.input-group').on('click', '.input-group-btn', function (e) {
            e.preventDefault();
            $(this).parent('.input-group').find('.timepicker').timepicker('showWidget');
        });
    }
}

// Handle Checkall Table
$('body').on('click', '.checkall', function () {
    if ($(this).prop('checked')) {
        $(this).closest('.groupcheckboxes').find('.checkallone').prop('checked', true);
        $.uniform.update(".checkallone");
    } else {
        $(this).closest('.groupcheckboxes').find('.checkallone').prop('checked', false);
        $.uniform.update(".checkallone");
    }
});
// Handle Checkall Table


function checkNonWorkingDate(field) {
    var send = true;
    $(field).datepicker({
        format: date_formate,
        calendarWeeks: true,
        autoclose: true,
        todayHighlight: true
    }).on("changeDate", function (e) {
        if (send) {
            var date = $(this).val();
            $.ajax({
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': $('input[name="_token"]').val(),
                },
                url: baseurl + "company/task-ajaxAction",
                data: { 'action': 'checkDate', 'date': date },
                success: function (output) {
                    handleAjaxResponse(output);
                    var output = JSON.parse(output);
                    if (typeof output.counts != 'undefined' && output.counts != null && output.counts > 0) {
                        $(field).val('');
                        $(field).focus();
                    }
                }
            });
            send = false;
        }
        setTimeout(function () { send = true; }, 200);
    });
}

/* START FOR LANGUAGE SET USING COOKIE */

/* END FOR LANGUAGE SET USING COOKIE */



/* Start manage datatable with Ajax & hide/show column dynamic */


function getDataTablenew(arr) {

    var dataTable = $(arr.tableID).DataTable({
        "scrollX": true,
        "processing": true,
        "serverSide": true,
        "bAutoWidth": false,
        "bLengthChange": false,
        "bInfo": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "order": [
            [(arr.defaultSortColumn) ? arr.defaultSortColumn : '0', (arr.defaultSortOrder) ? arr.defaultSortOrder : 'desc']
        ],
        "columnDefs": [{
            "targets": arr.hideColumnList,
            "visible": false
        },
        {
            "targets": arr.noSortingApply,
            "orderable": false
        },
        {
            "targets": arr.noSearchApply,
            "searchable": false
        },
        (arr.setColumnWidth) ? arr.setColumnWidth : ''
        ],
        "ajax": {
            url: arr.ajaxURL,
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            data: { 'action': arr.ajaxAction, 'arraydata': arr.data, 'data': arr.postData },
            error: function () { // error handling
                $(".row-list-error").html("");
                $(arr.tableID).append('<tbody class="row-list-error"><tr><td colspan="4" style="text-align: center;"><p style="color:red;">Sorry, No Record Found</p></td></tr></tbody>');
                $(arr.tableID + "processing").css("display", "none");
            }
        }
    });

    //    onLoadDefaultColumnSet(dataTable);
    //    hideShowDatatableColumn(dataTable);
}

function onLoadDefaultColumnSet(dataTable) {
    $('.custom-column').each(function () {
        var column = dataTable.column($(this).attr('data-column'));
        var status = $(this).attr('data-default-status');

        if ($(this).is(":checked")) {
            column.visible(!column.visible());
        } else {
            column.visible(column.visible());
        }
        if (status == 'true') {
            column.visible(!column.visible());
        }
    });
}

function hideShowDatatableColumn(dataTable) {
    $('body').on('click', '.custom-column', function () {
        // Get the column API object
        var column = dataTable.column($(this).attr('data-column'));
        // Toggle the visibility
        column.visible(!column.visible());
    });
}






$(".onlyNumber").keypress(function (e) {
    //if the letter is not digit then display error and don't type anything
    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
        //display error message
        //    $("#errmsg").html("Digits Only").show().fadeOut("slow");
        return false;
    }
});
$('body').on("keydown", ".onlyDigit", function (event) {

    if (event.shiftKey == true) {
        event.preventDefault();
    }

    if ((event.keyCode >= 48 && event.keyCode <= 57) ||
        (event.keyCode >= 96 && event.keyCode <= 105) ||
        event.keyCode == 8 || event.keyCode == 110 || event.keyCode == 9 || event.keyCode == 37 ||
        event.keyCode == 39 || event.keyCode == 46 || event.keyCode == 190) {

    } else {
        event.preventDefault();
    }

    if ($(this).val().indexOf('.') !== -1 && event.keyCode == 190)
        event.preventDefault();
});




$("body").on("change", "#empCountry", function () {
    var id = $(this).val();
    var data = { id: id, _token: $('#_token').val() };

    $.ajax({
        type: "POST",
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val(),
        },
        url: baseurl + "employee-ajaxaction",
        data: { 'action': 'changeCountry', 'data': data },
        success: function (data) {
            var output = JSON.parse(data);
            var temp_html = '';
            var html = '<option value="">Please select employee state</option>';


            for (var i = 0; i < output.length; i++) {
                temp_html = '<option value="' + output[i].id + '">' + output[i].name + '</option>';
                html = html + temp_html;
            }

            $('#empState').html(html);
            $('#empCity').html('<option value="">Please select employee city</option>');
        }
    });
});

$("body").on("change", "#empState", function () {
    var id = $(this).val();
    var data = { id: id, _token: $('#_token').val() };

    $.ajax({
        type: "POST",
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val(),
        },
        url: baseurl + "employee-ajaxaction",
        data: { 'action': 'changeState', 'data': data },
        success: function (data) {
            var output = JSON.parse(data);
            // console.log(output);
            // exit;
            var temp_html = '';
            var html = '<option value="">Please select employee city</option>';


            for (var i = 0; i < output.length; i++) {
                temp_html = '<option value="' + output[i].id + '">' + output[i].name + '</option>';
                html = html + temp_html;
            }

            $('#empCity').html(html);
        }
    });
});
$("body").on("change", "#empDepartment", function () {
    var id = $(this).val();
    var data = { id: id, _token: $('#_token').val() };

    $.ajax({
        type: "POST",
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val(),
        },
        url: baseurl + "employee-ajaxaction",
        data: { 'action': 'changeDepartment', 'data': data },
        success: function (data) {
            var output = JSON.parse(data);
            var temp_html = '';
            var html = '<option  value="">Select employee designation</option>';


            for (var i = 0; i < output.length; i++) {
                temp_html = '<option value="' + output[i].id + '">' + output[i].designation + '</option>';
                html = html + temp_html;
            }

            $('#empDesignation').html(html);
        }
    });
});


function getDataTableNoAjax(tableID, extraOption) {
    if (typeof extraOption === 'undefined') {
        extraOption = {};
    }
    var grid = new Datatable();
    var options = {
        src: $(tableID),
        loadingMessage: 'Loading...',
        dataTable: {
            "dom": "<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'f<'table-group-actions pull-right'>>r>t<'row'<'col-md-8 col-sm-12'pli><'col-md-4 col-sm-12'>>",
            //            "bStateSave": true,
            "lengthMenu": [
                [10, 20, 50, 100, 150, -1],
                [10, 20, 50, 100, 150, "All"]
            ],
            "pageLength": 50,
            "order": [
                [0, "asc"]
            ],
            "aoColumnDefs": [{
                'bSortable': false,
                'aTargets': [2, 3]
            }],
            "serverSide": false,
            "ajax": null
        }
    };
    options = $.extend(true, options, extraOption);
    grid.init(options);
    return grid;
}

function getQueryString(field, url) {
    var href = url ? url : window.location.href;
    var reg = new RegExp('[?&]' + field + '=([^&#]*)', 'i');
    var string = reg.exec(href);
    return string ? string[1] : null;
}


function CKupdate() {
    for (instance in CKEDITOR.instances)
        CKEDITOR.instances[instance].updateElement();
}
if (typeof CKEDITOR !== 'undefined') {
    CKEDITOR.on('instanceCreated', function (ev) {
        CKEDITOR.dtd.$removeEmpty['a'] = 0;
    })
}

function ajaxcall(url, data, callback) {
    //  App.startPageLoading();

    $.ajax({
        type: 'POST',
        url: url,
        data: data,
        async: false,
        success: function (result) {
            //   App.stopPageLoading();
            callback(result);
        }
    })
}

function handleAjaxFormSubmit(form, type) {

    if (typeof type === 'undefined') {
        ajaxcall($(form).attr('action'), $(form).serialize(), function (output) {
            handleAjaxResponse(output);
        });
    } else if (type === true) {
        // App.startPageLoading();
        var options = {
            resetForm: false, // reset the form after successful submit
            success: function (output) {
                //   App.stopPageLoading();
                handleAjaxResponse(output);
            }
        };
        $(form).ajaxSubmit(options);
    }
    return false;
}

function handleAjaxFormMySubmit(form, type) {
    $(".submitbtn:visible").attr("disabled", "disabled");
    $("#loader").show();

    if (typeof type === 'undefined') {
        ajaxcall($(form).attr('action'), $(form).serialize(), function (output) {
            handleAjaxResponse(output);
        });
    } else if (type === true) {
        // App.startPageLoading();
        var options = {
            resetForm: false, // reset the form after successful submit
            success: function (output) {
                //   App.stopPageLoading();
                handleAjaxResponse(output);
            }
        };
        $(form).ajaxSubmit(options);
    }
    return false;
}

function showToster(status, message) {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 2500
    };
    if (status == 'success') {
        toastr.success(message, 'Success');
    }
    if (status == 'error') {
        toastr.error(message, 'Fail');

    }
    if (status == 'warning') {
        toastr.warning(message, 'Warning');

    }
}

function handleAjaxResponse(output) {

    output = JSON.parse(output);
    $('#deleteModel').removeClass('show');
    $("#deleteModel").css({ 'display': 'none' });
    var html = "";




    if (output.status == 'success') {
        html = '<div class="alert alert-custom alert-notice alert-light-success fade show mb-5" role="alert">' +
            '<div class="alert-icon">' +
            '<i class="fa fa-check-circle-o"></i>' +
            '</div>' +
            '<div class="alert-text">' + output.message + '</div>' +

            '</div>';
    }
    if (output.status == 'error') {
        html = '<div class="alert alert-custom alert-notice alert-light-danger fade show mb-5" role="alert">' +
            '<div class="alert-icon">' +
            '<i class="fa fa-close"></i>' +
            '</div>' +
            '<div class="alert-text">' + output.message + '</div>' +
            '</div>';
    }
    if (output.status == 'warning') {
        html = '<div class="alert alert-custom alert-notice alert-light-warning fade show mb-5" role="alert">' +
            '<div class="alert-icon">' +
            '<i class="fa fa-exclamation-triangle"></i>' +
            '</div>' +
            '<div class="alert-text">A simple warning alert—check it out!</div>' +
            '</div>';
    }
    $("#alertDiv").html(html);
    if (typeof output.redirect !== 'undefined' && output.redirect != '') {
        setTimeout(function () {
            window.location.href = output.redirect;
        }, 2000);
    }

    if (typeof output.jscode !== 'undefined' && output.jscode != '') {
        eval(output.jscode);
    }

}

function handleAjaxResponse(output) {

    output = JSON.parse(output);

    if (output.message != '') {
        showToster(output.status, output.message, '');
    }
    if (typeof output.redirect !== 'undefined' && output.redirect != '') {
        setTimeout(function () {
            window.location.href = output.redirect;
        }, 4000);
    }
    if (typeof output.reload !== 'undefined' && output.reload != '') {
        window.location.href = location.reload();
    }
    if (typeof output.jscode !== 'undefined' && output.jscode != '') {
        eval(output.jscode);
    }
}

function _fn_getQueryStringValue(name) {
    var regex = new RegExp("[\\?&]" + name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]") + "=([^&#]*)"),
        results = regex.exec(window.location.search);
    return results ? decodeURIComponent(results[1].replace(/\+/g, " ")) : '';
}

function handleFormValidate(form, rules, submitCallback, showToaster) {

    var error = $('.alert-danger', form);
    var success = $('.alert-success', form);
    form.validate({
        errorElement: 'span', //default input error message container
        errorClass: 'help-block', // default input error message class
        focusInvalid: false, // do not focus the last invalid input
        ignore: ":hidden",
        rules: rules,
        invalidHandler: function (event, validator) { //display error alert on form submit
            success.hide();
            error.show();
            //            App.scrollTo(error, -200);
            if (typeof showToaster !== 'undefined' && showToaster) {
                Toastr.init('warning', 'Some fields are missing!.', '');
            }
        },
        showErrors: function (errorMap, errorList) {
            if (typeof errorList[0] != "undefined") {
                var position = $(errorList[0].element).offset().top - 70;
                $('html, body').animate({
                    scrollTop: position
                }, 300);
            }
            this.defaultShowErrors(); // keep error messages next to each input element
        },

        highlight: function (element) { // hightlight error inputs

            $(element)
                .closest('.c-input, .form-control').addClass('has-error'); // set error class to the control group

            $(element).parent().parent().find('.select2').addClass('has-error');

        },
        unhighlight: function (element) { // revert the change done by hightlight
            $(element)
                .closest('.c-input, .form-control').removeClass('has-error'); // set error class to the control group
            $(element)
                .closest('.c-input, .form-control').addClass('is-valid'); // set error class to the control group
        },
        success: function (label) {
            label.closest('.c-input, .form-control').removeClass('has-error'); // set success class to the control group
            label.closest('.c-input, .form-control').addClass('is-valid'); // set error class to the control group
        },
        errorPlacement: function (error, element) {
            return true;
        },

        submitHandler: function (form) {
            $(".submitbtn:visible").attr("disabled", "disabled");
            $("#loader").show();
            // form.submit();
            if (typeof submitCallback !== 'undefined' && typeof submitCallback == 'function') {
                submitCallback(form);
            } else {
                handleAjaxFormSubmit(form);
            }
            return false;
        }
    });

    $('.select2me', form).change(function () {
        form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
    });
    $('.date-picker .form-control').change(function () {
        form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
    })
}

function handleFormValidateWithMsg(form, rules, messages, submitCallback, showToaster) {

    var error = $('.alert-danger', form);
    var success = $('.alert-success', form);
    form.validate({
        errorElement: 'span', //default input error message container
        errorClass: 'help-block', // default input error message class
        focusInvalid: true, // do not focus the last invalid input
        ignore: ":hidden",
        rules: rules,
        invalidHandler: function (event, validator) { //display error alert on form submit
            success.hide();
            error.show();

            //            App.scrollTo(error, -200);
            if (typeof showToaster !== 'undefined' && showToaster) {
                Toastr.init('warning', 'Some fields are missing!.', '');
            }
            //            Toastr.init('warning', 'Some fields are missing!.', '');
        },
        highlight: function (element) { // hightlight error inputs
            // $(element)
            //     .closest('.form-control').addClass('has-error'); // set error class to the control group
            // $(element).parent().find('.select2').addClass('has-error');
        },
        unhighlight: function (element) { // revert the change done by hightlight
            // $(element).parent().find('.select2').removeClass('has-error');
            // $(element)
            //     .closest('.form-control').removeClass('has-error'); // set error class to the control group
        },
        success: function (label) {
            // label.closest('.form-control').removeClass('has-error'); // set success class to the control group
            // label.parent().find('.select2').removeClass('has-error');
        },
        messages: messages,

        submitHandler: function (form) {

            $(".submitbtn:visible").attr("disabled", "disabled");
            $("#loader").show();
            if (typeof submitCallback !== 'undefined' && typeof submitCallback == 'function') {
                submitCallback(form);
            } else {
                handleAjaxFormSubmit(form);
            }
            return false;
        },

        errorPlacement: function (error, element) {
            var elem = $(element);
            if (elem.hasClass("select2-hidden-accessible")) {
                element = $("#select2-" + elem.attr("id") + "-container").parent();
                error.insertAfter(element);
            } else {
                if (elem.hasClass("radio-btn")) {
                    element = elem.parent().parent().parent().parent().parent().parent();
                    error.insertAfter(element);
                } else {

                    if (elem.hasClass("select")) {
                        element = elem.parent().parent();
                        error.insertAfter(element);
                    } else {
                        error.insertAfter(element);
                    }

                    error.insertAfter(element);
                }
            }
        },
    });

    $('.select2me', form).change(function () {
        form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
    });
    $('.date-picker .form-control').change(function () {
        form.validate().element($(this)); //revalidate the chosen dropdown value and show error or success message for the input
    })
}

function gritter(title, text, sticky, time) {
    $.gritter.add({
        // (string | mandatory) the heading of the notification
        title: title,
        // (string | mandatory) the text inside the notification
        text: text,
        // (string | optional) the image to display on the left
        //                    image1: './assets/img/avatar1.jpg',
        // (bool | optional) if you want it to fade out on its own or just sit there
        sticky: sticky,
        // (int | optional) the time you want it to be alive for before fading out
        time: time,
        // (string | optional) the class name you want to apply to that specific message
        class_name: 'my-sticky-class'
    });

}

var Toastr = function () {

    return {
        //main function to initiate the module
        init: function (type, title, message) {
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "positionClass": "toast-top-center",
                "onclick": null,
                "showDuration": "1000",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"

            }
            toastr[type](message, title);
        }

    };

}();

function getDataTable(arr) {

    var pageLength = 0
    if (arr.pageLength) {
        pageLength = arr.pageLength;
    } else {
        pageLength = 10;
    }
    var dataTable = $(arr.tableID).DataTable({
        "scrollX": true,
        "processing": true,
        "responsive": true,
        "lengthMenu": [5, 10, 25, 50, 100],
        "pageLength": pageLength,
        "serverSide": true,
        "bAutoWidth": false,
        "searching": true,
        "bLengthChange": true,
        "bInfo": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "order": [
            [(arr.defaultSortColumn) ? arr.defaultSortColumn : '0', (arr.defaultSortOrder) ? arr.defaultSortOrder : 'desc']
        ],
        "columnDefs": [{
            "targets": arr.hideColumnList,
            "visible": false
        },
        {
            "targets": arr.noSortingApply,
            "orderable": false
        },
        {
            "targets": arr.noSearchApply,
            "searchable": false
        },
        (arr.setColumnWidth) ? arr.setColumnWidth : ''
        ],
        "ajax": {
            url: arr.ajaxURL,
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            data: { 'action': arr.ajaxAction, 'data': arr.postData },
            error: function () { // error handling
                $(".row-list-error").html("");
                $(arr.tableID).append('<tbody class="row-list-error"><tr><td colspan="4" style="text-align: center;"><p style="color:red;">Sorry, No Record Found</p></td></tr></tbody>');
                $(arr.tableID + "processing").css("display", "none");
            }
        }
    });
}

function getDataTablenew(arr) {

    var dataTable = $(arr.tableID).DataTable({
        "scrollX": true,
        "processing": true,
        "serverSide": true,
        "bAutoWidth": false,
        "bLengthChange": false,
        "bInfo": true,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search..."
        },
        "order": [
            [(arr.defaultSortColumn) ? arr.defaultSortColumn : '0', (arr.defaultSortOrder) ? arr.defaultSortOrder : 'desc']
        ],
        "columnDefs": [{
            "targets": arr.hideColumnList,
            "visible": false
        },
        {
            "targets": arr.noSortingApply,
            "orderable": false
        },
        {
            "targets": arr.noSearchApply,
            "searchable": false
        },
        (arr.setColumnWidth) ? arr.setColumnWidth : ''
        ],
        "ajax": {
            url: arr.ajaxURL,
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            data: { 'action': arr.ajaxAction, 'arraydata': arr.data, 'data': arr.postData },
            error: function () { // error handling
                $(".row-list-error").html("");
                $(arr.tableID).append('<tbody class="row-list-error"><tr><td colspan="4" style="text-align: center;"><p style="color:red;">Sorry, No Record Found</p></td></tr></tbody>');
                $(arr.tableID + "processing").css("display", "none");
            }
        }
    });

    //    onLoadDefaultColumnSet(dataTable);
    //    hideShowDatatableColumn(dataTable);
}

function onLoadDefaultColumnSet(dataTable) {
    $('.custom-column').each(function () {
        var column = dataTable.column($(this).attr('data-column'));
        var status = $(this).attr('data-default-status');

        if ($(this).is(":checked")) {
            column.visible(!column.visible());
        } else {
            column.visible(column.visible());
        }
        if (status == 'true') {
            column.visible(!column.visible());
        }
    });
}

function hideShowDatatableColumn(dataTable) {
    $('body').on('click', '.custom-column', function () {
        // Get the column API object
        var column = dataTable.column($(this).attr('data-column'));
        // Toggle the visibility
        column.visible(!column.visible());
    });
}


$(".onlyNumber").keypress(function (e) {
    //if the letter is not digit then display error and don't type anything
    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
        //display error message
        //    $("#errmsg").html("Digits Only").show().fadeOut("slow");
        return false;
    }
});


$('body').on("keydown", ".onlyDigit", function (event) {

    if (event.shiftKey == true) {
        event.preventDefault();
    }

    if ((event.keyCode >= 48 && event.keyCode <= 57) ||
        (event.keyCode >= 96 && event.keyCode <= 105) ||
        event.keyCode == 8 || event.keyCode == 110 || event.keyCode == 9 || event.keyCode == 37 ||
        event.keyCode == 39 || event.keyCode == 46 || event.keyCode == 190) {

    } else {
        event.preventDefault();
    }

    if ($(this).val().indexOf('.') !== -1 && event.keyCode == 190)
        event.preventDefault();
});

function validateEmail(mail) {
    if (/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/.test(mail)) {
        return true;
    } else {

        return false;
    }

}


$('body').on('change', '#item_code', function () {
    var id = $(this).val();
    var data = { id: id, _token: $('#_token').val() };
    $.ajax({
        type: "POST",
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val(),
        },
        url: baseurl + "admin/common-ajaxcall",

        data: { 'action': 'change-item-code', 'data': data },
        success: function (data) {
            $("#item").html(data);
        }
    });
});

$('body').on('change', '#item', function () {
    var id = $(this).val();
    var data = { id: id, _token: $('#_token').val() };
    $.ajax({
        type: "POST",
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val(),
        },
        url: baseurl + "admin/common-ajaxcall",
        data: { 'action': 'change-item', 'data': data },
        success: function (data) {
            $("#item_code").html(data);
        }
    });
});


$('body').on('change', '#branch_new', function () {
    var branch_id = $('#branch_new').val();
    var data = { branch_id: branch_id, _token: $('#_token').val() };
    $.ajax({
        type: "POST",
        headers: {
            'X-CSRF-TOKEN': $('input[name="_token"]').val(),
        },
        url: baseurl + "admin/common-ajaxcall",

        data: { 'action': 'change-item-by-branch', 'data': data },
        success: function (data) {
            $("#item-div").html(data);
            $('.select2').select2();
        }
    });
});



function convert_float(number) {
    return (parseFloat(number).toFixed(decimal_point));
}

