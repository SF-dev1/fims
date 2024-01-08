var Brand = function () {

    // Submit Form
    var submitForm = function (formData, $type) {
        $ret = "";
        $.ajax({
            url: "ajax_load.php?token=" + new Date().getTime(),
            cache: false,
            type: $type,
            data: formData,
            contentType: false,
            processData: false,
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

    var sellersAuthorisation_handleForm = function () {

        // // INIT debugger
        // debugger;

        $("[name='auth_code']").inputmask({
            "mask": "AA-9999-******",
            definitions: {
                "*": {
                    casing: "upper"
                }
            },
            placeholder: "XX-XXXX-XXXXXX",
        });

        var form = $('#authorisation-verify');
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

                $('#authorisation-verify-response').addClass('hide');
                $('.certificate_details_valid').addClass('hide');
                $('.badge-danger-dot').addClass('hide');
                $('.badge-success-dot').addClass('hide');

                var other_data = $(form).serializeArray();
                var formData = new FormData();
                formData.append('action', 'verify_certificate');
                $.each(other_data, function (key, input) {
                    formData.append(input.name, input.value);
                });

                $('.form-actions .btn-submit', form).attr('disabled', true);
                $('.form-actions .btn-submit i', form).addClass('fa fa-sync fa-spin');

                var s = submitForm(formData, "POST");
                if (s.type == 'success' || s.type == 'error') {
                    $('.form-actions .btn', form).attr('disabled', false);
                    $('.form-actions i', form).removeClass('fa-sync fa-spin');
                    if (s.type == 'success') {
                        $('.certificate_details_valid').removeClass('hide');
                        if (s.data.status == "Active") {
                            $('.badge-success-dot').removeClass('hide');
                        } else {
                            $('.badge-danger-dot').removeClass('hide');
                        }

                        $('.auth_code').html(s.data.serial_no);
                        $('.brand_name').html(s.data.brand_name);
                        $('.seller_name').html(s.data.seller_name);
                        $('.seller_gst').html(s.data.seller_gst);
                        $('.valid_from').html(s.data.from_date);
                        $('.valid_till').html(s.data.to_date);

                        $('#authorisation-verify-response').removeClass('hide');
                    } else {
                        UIToastr.init(s.type, 'Brand Certificate Verification', s.msg);
                    }
                } else {
                    UIToastr.init('error', 'Brand Certificate Verification', 'Unable to process request. Please try again later');
                }
            }
        });
    };

    return {
        //main function to initiate the module
        init: function () {
            sellersAuthorisation_handleForm();
        }
    };
}();