(function ($, Drupal) {
    Drupal.behaviors.otpLogin = {
      attach: function (context, settings) {
        if (!$('#send-otp', context).data('otpLoginAttached')) {
            $('#send-otp', context).data('otpLoginAttached', true);
  
            $('#send-otp').click(function () {
                var countryCode = $('#country_code').val();
                var phoneNumber = $('#phone_number').val().trim();
                var fullPhoneNumber = countryCode + phoneNumber;
                var errorMessage = '';
                successMessage = '';
  
                // Regex pattern for a basic phone number format validation
                var phoneRegex =  /^\d{3}[-\s]?\d{3}[-\s]?\d{4}$/;
    
                if (phoneNumber === '' || phoneNumber === null) {
                    errorMessage = 'Mobile Number Cannot be empty.';
                    $('#phone-number-error-message').addClass('error').text(errorMessage).show().fadeOut(8000);
                } else if (!phoneRegex.test(phoneNumber)) {
                    errorMessage = 'Please enter a valid phone number.';
                    $('#phone-number-error-message').addClass('error').text(errorMessage).show().fadeOut(8000);
                } else {
                    $('#phone-number-error-message').removeClass('error').hide().text('');
                    //alert(fullPhoneNumber);
                    // Perform the AJAX request to send OTP
                    $.ajax({
                        url: Drupal.url('otp-login/send-otp'),
                        type: 'POST',
                        data: { phone_number: fullPhoneNumber },
                        success: function (response) {
                            if (response.status === 'success') {
                                successMessage = 'OTP sent Successfully to your Mobile.';
                                $('#phone-number-success-message').addClass('success').text(successMessage).show().fadeOut(8000);
                                $('#otp-submission-form').show();
                                 // Attach OTP input event listeners
                                var otpInputs = document.querySelectorAll('.otp-input');
                                otpInputs.forEach(function (input, index) {
                                    input.addEventListener('input', function () {
                                    if (this.value.length === 1) {
                                        // Move focus to the next input field
                                        if (index < otpInputs.length - 1) {
                                            otpInputs[index + 1].focus();
                                        }
                                    } else if (this.value.length === 0) {
                                        // Move focus to the previous input field on delete
                                        if (index > 0) {
                                            otpInputs[index - 1].focus();
                                        }
                                    }
                                    });

                                    input.addEventListener('keydown', function (event) {
                                    if (event.key === 'Backspace' && this.value.length === 0 && index > 0) {
                                        // Move focus to the previous input field on backspace
                                        otpInputs[index - 1].focus();
                                    }
                                    });
                                });
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function () {
                            errorMessage = 'An error occurred while sending OTP.';
                            $('#phone-number-error-message').addClass('error').text(errorMessage).show().fadeOut(8000);
                        }
                    });
                }
            });

             // Handle OTP submission form submission
            $('#submit-otp').click(function () {
                var otp1 = $('#otp1').val().trim();
                var otp2 = $('#otp2').val().trim();
                var otp3 = $('#otp3').val().trim();
                var otp4 = $('#otp4').val().trim();
                var otp5 = $('#otp5').val().trim();
                var otp6 = $('#otp6').val().trim();
                
                var otp = otp1 + otp2 + otp3 + otp4 + otp5 + otp6;

                //get the full phonenumber
                var countryCode = $('#country_code').val();
                var phoneNumber = $('#phone_number').val().trim();
                var fullPhoneNumber = countryCode + phoneNumber;

                if (otp === '' || otp.length !== 6 || isNaN(otp) || !Number.isInteger(Number(otp))) {
                    errorMessage = 'Please enter a valid 6-digit OTP.';
                    $('#otp-error-message').addClass('error').text(errorMessage).show().fadeOut(8000);
                } else {
                    // Proceed with OTP verification AJAX call
                    $.ajax({
                        url: Drupal.url('otp-login/verify-otp'),
                        type: 'POST',
                        data: { otp: otp, phone_number: fullPhoneNumber},
                        success: function (response) {
                            if (response.status === 'success') {
                                successMessage = 'OTP verified successfully. Logging in...';
                                $('#otp-success-message').addClass('success').text(successMessage).show().fadeOut(8000);
                                setTimeout(function() {
                                    window.location.href = response.redirect;
                                },  3000);
                                // Handle successful login action
                            } else {
                                errorMessage = 'OTP verification failed. Please try again.';
                                $('#otp-error-message').addClass('error').text(errorMessage).show().fadeOut(8000);
                            }
                        },
                        error: function () {
                            errorMessage = 'An error occurred while verifying OTP.';
                            $('#otp-error-message').addClass('error').text(errorMessage).show().fadeOut(8000);
                        }
                    });
                }
            });
        }
      }
    };
})(jQuery, Drupal);
  