(function($) {
    function getCookieValue(cname) {
        var name = cname + "=";
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    function start_countdown(seconds, elementId) {
        const countdownElement = $("#" + elementId);
        const interval = setInterval(function() {
            if (seconds <= 0) {
                clearInterval(interval);
                countdownElement.text("點此重新發送");
                countdownElement.prop("disabled", false);
            } else {
                countdownElement.text(seconds + " 秒後，重新發送");
                countdownElement.prop("disabled", true);
                seconds--;
            }
        }, 1000); // 1000 milliseconds = 1 second
    }
    $(document).ready(function() {
        $('form.register').submit(function(e) {
            var err = '';
            var username = $('#reg_username').val();
            var email = $('#reg_email').val();
            var password = $('#reg_password').val();
            var fullname = $('#fullname').val();
            var nickname = $('#nickname').val();
            var birthday = $('#birthday').val();
            var billing_state = $('#billing_state').val();
            var billing_city = $('#billing_city').val();
            var billing_postcode = $('#billing_postcode').val();
            var billing_address_1 = $('#billing_address_1').val();
            if (username == '' || email == '' || password == '' || fullname == '' || nickname == '' || birthday == '' || billing_state == '' || billing_city == '' || billing_postcode == '' || billing_address_1 == '') {
                err = '請完整填寫必填欄位與驗證手機號碼。';
            }
            if (err != '') {
                alert(err);
                e.preventDefault();
            }
        });
        $('<div id="valid_phone_msg"></div>').insertAfter($('#reg_username'));
        $('#reg_username').blur(function() {
            var mobile = $(this).val();
            if (!/^09\d{8}$/.test($(this).val())) {
                $(this).focus().select();
                $('#valid_phone_msg').text('錯誤的手機號碼，範例格式：0911222333。');
            } else {
                $('#valid_phone_msg').html('<input type="text" placeholder="請輸入手機簡訊中 6 碼驗證碼" style="display:none;" class="woocommerce-Input woocommerce-Input--text input-text" name="otp_code" id="otp_code" value=""/><button type="button" class="button" id="get_otp">取得驗證碼</button><input type="hidden" class="woocommerce-Input woocommerce-Input--text input-text" name="otp_timestamp" id="otp_timestamp" value=""/>');
                $('#get_otp').click(function() {
                    // $('#otp_timestamp').val(1695823437);
                    // $('#otp_code').show();
                    var formData = {
                        action: 'mxp_mitake_get_otp_sms',
                        mobile: mobile,
                        nonce: MXP.nonce
                    };
                    $.ajax({
                        url: MXP.ajaxurl,
                        type: 'POST',
                        data: formData,
                        dataType: "json",
                        cache: false,
                        // processData: false,
                        // contentType: false,
                        success: function(res) {
                            console.log(res);
                            if (res.success) {
                                //成功
                                $('#otp_timestamp').val(res.data.data);
                                $('#otp_code').show();
                                start_countdown(120, 'get_otp');
                            } else {
                                alert(res.data.msg);
                                if (res.data.data !== undefined) {
                                    $('#otp_code').show();
                                    start_countdown(res.data.data, 'get_otp');
                                }
                            }
                        },
                        error: function(res) {
                            // 請求失敗的處理
                            console.error('請求失敗。', res);
                        }
                    });
                });
            }
        });
        if (getCookieValue('otp_code_error') != '') {
            // 有驗證過，但錯誤，重新觸發驗證機會
            var c = getCookieValue('otp_code_error').split('|');
            $('#reg_username').trigger('blur');
            $('#otp_code').val(c[0]);
            $('#otp_code').show();
            $('#otp_timestamp').val(c[1]);
        }

        function selectChangeCallback() {
            //callback func
        }
        $('<div>').attr({
            id: 'billing-zipcode-fields'
        }).insertBefore('#billing_address_1_field');
        var billingAddress = $('input[name="billing_address_1"]').val();
        var $billingZipcodeFields = $('#billing-zipcode-fields'),
            $billingStateField = $('#billing_state_field'),
            $billingCityField = $('#billing_city_field'),
            $billingPostcodeField = $('#billing_postcode_field');
        var billingState = $('input[name="billing_state"]').val(),
            billingCity = $('input[name="billing_city"]').val(),
            billingPostcode = $('input[name="billing_postcode"]').val();
        $billingZipcodeFields.twzipcode({
            countyName: 'billing_state',
            districtName: 'billing_city',
            zipcodeName: 'billing_postcode',
            readonly: true,
            detect: false,
            onCountySelect: selectChangeCallback,
            onDistrictSelect: selectChangeCallback
        });
        $billingStateField.find('input[name="billing_state"]').remove();
        $billingCityField.find('input[name="billing_city"]').remove();
        $billingPostcodeField.find('input[name="billing_postcode"]').remove();
        $billingStateField.append($billingZipcodeFields.find('select[name="billing_state"]'));
        $billingCityField.append($billingZipcodeFields.find('select[name="billing_city"]'));
        $billingPostcodeField.append($billingZipcodeFields.find('input[name="billing_postcode"]').addClass('input-text').attr('id', 'billing_postcode'));
        $billingZipcodeFields.twzipcode('set', {
            'county': billingState,
            'district': billingCity,
            'zipcode': billingPostcode
        });
    });
})(jQuery);