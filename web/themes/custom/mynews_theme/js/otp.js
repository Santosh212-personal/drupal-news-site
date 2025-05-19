(function ($, Drupal) {
    $(document).ready(function () {

        var errorMessage = '';
        var successMessage = '';
        // Show the sign-in button with animation on page load
        setTimeout(function() {
            $('.fab-container').addClass('display');
            setTimeout(function() {
                $('.fab-container').addClass('pulse');
            }, 2000);
        }, 3000); // Adjust the delay time (2000ms = 2s) as needed
        
        // Handle click on "Sign In" button to show registration form
        $('#fab-signin').click(function() {
            $(this).hide();
            $('.back').fadeIn();
            $('.overlay').removeClass('exiting').addClass('active');
            $('body').css('overflow', 'hidden'); // Disable scrolling on body
        });

        $('#fab-signin').click(function() {
            $(this).hide();
            $('.back').fadeIn();
            $('.overlay').removeClass('exiting').addClass('active');
            $('body').css('overflow', 'hidden'); // Disable scrolling on body
        });

        // Handle login button clicks
        $('.otp_login, .wallet_login').click(function() {
            $('.otp_login, .wallet_login').removeClass('active'); // Remove active class from both buttons
            $(this).addClass('active'); // Add active class to the clicked button
            if ($(this).hasClass('otp_login')) {
                $('#otp-form').show(); // Show OTP form
            } else if ($(this).hasClass('wallet_login')) {
                $('#otp-form').hide(); // Hide OTP form
            }
        });

        //hide signbutton
        if ($('body').hasClass('user-logged-in')) {
            $('.fab-container').removeClass('display').hide();
            $('.lg_out').css('display', 'block');
        }
        $('.lg_out').click(function() {
            sessionStorage.setItem('welcomeShown', 'false');           
            $.ajax({
                url:  Drupal.url('otp/logout'), // Your logout endpoint URL
                method: 'POST',
                success: function(response) {
                    if (response.status === 'success') {
                        $('.lg_out').addClass('nodisplay');
                        $('.fab-container').addClass('display');
                        setTimeout(function() {
                            $(location).prop('href', '/');
                        }, 1000);
                    }
                    else {
                        errorMessage =  'Please try again.';
                        alert(errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Logout failed:', error);
                }
            });
        });
        
        // Set OTP Login as active by default
        $('.otp_login').addClass('active');
    
        // Handle click on close button to hide registration form
        $('.close-button').click(function() {
            $('#fab-signin').show();
            $('.overlay').removeClass('active').addClass('exiting').fadeOut;
            $('body').css('overflow', 'auto'); // Enable scrolling on body
            // Delay hiding the background to allow for the slide-out transition
            setTimeout(function() {
                $('.back').css('display', 'none');
                $('.overlay').removeClass('exiting').addClass('overlay');
            }, 1000); // Match the transition duration
        });
    
        // Handle form interactions (animations and transitions)
        $('.mobile_number').on("change keyup paste", function() {
            if ($(this).val()) {
                $('.icon-paper-plane').addClass("next");
            } else {
                $('.icon-paper-plane').removeClass("next");
            }
        });
    
        // Function to send OTP
        var resendOtpShown = false;
        function sendOtp(phoneNumber) {
            $.ajax({
                url: Drupal.url('otp-login/send-otp'),
                type: 'POST',
                data: { phone_number: phoneNumber },
                success: function(response) {
                    if (response.status === 'success') {
                        successMessage = 'OTP sent Successfully to your Mobile.';
                        $('#success-message').text(successMessage).show().fadeOut(4000);
                        startOtpTimer(30); // Start a 60-second timer
                    } else {
                        $('#error-message').text(response.message).show().fadeOut(6000);
                    }
                },
                error: function() {
                    errorMessage = 'An error occurred while sending OTP.';
                    $('#error-message').text(errorMessage).show().fadeOut(6000);
                }
            });
        }

        // Start OTP timer
        function startOtpTimer(duration) {
            var timer = duration, minutes, seconds;
            //var otp_timer = $('.otp-timer');
            var display = $('#timer-display');
            var resendButton = $('.resend-otp-button');
            
            //otp_timer.show();
            display.css('display', 'block');
            if (!resendOtpShown) {
                resendButton.hide();
            }


            var countdown = setInterval(function() {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.html("<span class='otp_timer_seconds'>OTP Expires in " + '<span class="timer_seconds">'+seconds+'</span>' + ' seconds</span>');

                if (--timer < 0) {
                    clearInterval(countdown);
                    display.hide();
                    if (!resendOtpShown) {
                        resendButton.show();
                        resendOtpShown = true; // Set the flag to true
                    }
                }
            }, 1000);
        }

        // Send OTP button click event
        $('.next-button.mobile').click(function() {
            $('p.info').hide();
            var countryCode = $('#country_code').val().trim();
            var phoneNumber = $('#phone_number').val().trim();
            var fullPhoneNumber = countryCode + phoneNumber;

            // Regex pattern for a basic phone number format validation
            var phoneRegex =  /^\d{3}[-\s]?\d{3}[-\s]?\d{4}$/;

            if (phoneNumber === '' || phoneNumber === null) {
                errorMessage = 'Mobile Number Cannot be empty.';
                $('#error-message').text(errorMessage).show().fadeOut(6000);
            } else if (!phoneRegex.test(phoneNumber)) {
                errorMessage = 'Please enter a valid phone number.';
                $('#error-message').text(errorMessage).show().fadeOut(6000);
            } else {
                $('#error-message').hide().text('');
                sendOtp(fullPhoneNumber);
                setTimeout(function() {
                    $('.phone-section').addClass("fold-up");
                    $('.otp-section').removeClass("folded");
                    var $otpInputs = $('.otp-input');
                    $otpInputs.each(function (index, input) {
                        $(input).on('input', function () {
                            if ($(this).val().length === 1) {
                                // Move focus to the next input field
                                if (index < $otpInputs.length - 1) {
                                    $($otpInputs[index + 1]).focus();
                                }
                            } else if ($(this).val().length === 0) {
                                // Move focus to the previous input field on delete
                                if (index > 0) {
                                    $($otpInputs[index - 1]).focus();
                                }
                            }
                        });

                        $(input).on('keydown', function (event) {
                            if (event.key === 'Backspace' && $(this).val().length === 0 && index > 0) {
                                // Move focus to the previous input field on backspace
                                $($otpInputs[index - 1]).focus();
                            }
                        });
                    });
                }, 2000);
            }
        });

        // Resend OTP button click event
        $('.resend-otp-button').click(function() {
            $(this).hide();
            var countryCode = $('#country_code').val().trim();
            var phoneNumber = $('#phone_number').val().trim();
            var fullPhoneNumber = countryCode + phoneNumber;
    
           // Regex pattern for a basic phone number format validation
            var phoneRegex = /^\d{3}[-\s]?\d{3}[-\s]?\d{4}$/;

            if (phoneNumber === '' || phoneNumber === null) {
                errorMessage = 'Mobile Number Cannot be empty.';
                $('#error-message').text(errorMessage).show().fadeOut(6000);
            } else if (!phoneRegex.test(phoneNumber)) {
                errorMessage = 'Please enter a valid phone number.';
                $('#error-message').text(errorMessage).show().fadeOut(6000);
            } else {
                $('#error-message').hide().text('');
                sendOtp(fullPhoneNumber);
                //startOtpTimer(60); // Restart the timer for resend OTP
            }
        });

        $('.otp-input').on("change keyup paste", function() {
            if ($(this).val()) {
                $('.icon-lock').addClass("next");
            } else {
                $('.icon-lock').removeClass("next");
            }
        });
    
        //varify OTP
        $('.next-button.otp').click(function() {
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
                $('#error-message').text(errorMessage).show().fadeOut(6000);
            } else {
                $('#error-message').hide().text('');
                // Proceed with OTP verification AJAX call
                $.ajax({
                    url: Drupal.url('otp-login/verify-otp'),
                    type: 'POST',
                    data: { otp: otp, phone_number: fullPhoneNumber},
                    success: function (response) {
                        if (response.status === 'success') {
                            successMessage = 'OTP is verified successfully. Logging in...';
                            $('#success-message').text(successMessage).show().fadeOut(6000);
                            setTimeout(function() {
                                if(response.redirect === 'already-user'){                   
                                    setTimeout(function() {
                                        $(location).prop('href', '/');
                                        var welcm = $('#welcome-message');
                                        var originalText = welcm.text();
                                        var newText = originalText.replace('Welcome', 'Welcome back');
                                        welcm.text(newText); 
                                    }, 1000); // Adjust the delay
                                }
                                else{
                                    $('.otp-section').addClass("fold-up");
                                    $('.details-section').removeClass("folded");
                                }
                            },  3000);
                            // Handle successful login action
                        } else {
                            errorMessage = 'Invalid OTP!. Please try again.';
                            $('#error-message').text(errorMessage).show().fadeOut(6000);
                        }
                    },
                    error: function () {
                        errorMessage = 'An error occurred while verifying OTP.';
                        $('#error-message').text(errorMessage).show().fadeOut(6000);
                    }
                });
            }
        });
    
        $('.details_section').on("change keyup paste", function() {
            if ($(this).val()) {
                $('.icon-repeat-lock').addClass("next");
            } else {
                $('.icon-repeat-lock').removeClass("next");
            }
        });
    
        $('.next-button.details').click(function() {
            var uname = $('#uname').val().trim();
            var uemail = $('#uemail').val().trim();
            if(uname != '' && uemail != ''){
                $.ajax({
                    url: Drupal.url('user-details/submit'),
                    type: 'POST',
                    data: { name: uname, mail: uemail},
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {                
                            $('#success-message').text(response.message).fadeOut(6000);
                            $('.details-section').addClass("fold-up");
                            $('.account_success').css("marginTop", 0);
                            setTimeout(function() {
                                $(location).prop('href', '/');
                            }, 3000);
                        } else {
                            $('#error-message').text(response.message).addClass('error');
                        }
                    },
                    error: function () {
                        $('#error-message').text('An error occurred while updating your details.').addClass('error');
                    }
                });
            }
        });

        // Check if the welcome message has been shown for this session
        var welcm = $('#welcome-message');
        if (sessionStorage.getItem('welcomeShown') !== 'true') {
            if (welcm.length) {
                welcm.fadeIn(1000); // Fade in the welcome message
                setTimeout(function() {
                    welcm.fadeOut(9000, function() {
                        // Set the flag in session storage after fading out
                        sessionStorage.setItem('welcomeShown', 'true');
                    });
                }, 3000); // Show the welcome message for 3 seconds before starting to fade out
            }
        } else {
            welcm.css('display', 'none'); // Hide the welcome message if the flag is set
        }
    });
})(jQuery, Drupal);