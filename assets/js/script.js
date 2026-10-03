document.addEventListener('DOMContentLoaded', function () {

    let voorodak_ajax = true;
    let voorodak_error = true;
    const voorodak_messages = document.querySelector('.voorodak__wrapper-messages');
    const voorodak_security = voorodak_data.security;
    const voorodak_otp_length = voorodak_data.otp_length;
    const voorodak_password_length = voorodak_data.password_length;
    const voorodak_login_url = voorodak_data.login_url;
    const voorodak_backurl = voorodak_data.backurl;
    const voorodak_login_type = voorodak_data.login_type;
    const voorodak_family_name_persian_only = String(voorodak_data.family_name_persian_only || '');
    const voorodak_national_code_field = String(voorodak_data.national_code_field || '');
    const voorodak_national_code_force = String(voorodak_data.national_code_force || '');
    const voorodak_password_login_priority = String(voorodak_data.password_login_priority || '');
    const voorodak_mobile_regex = /^09[0-9]{9}$/;
    const voorodak_email_regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const voorodak_number_regex = /^-?\d+$/;
    const voorodak_persian_name_regex = /^[\u0600-\u06FF\s\u200c]+$/;
    const voorodak_english_or_digit_regex = /[A-Za-z0-9]/;
    let voorodak_otp_request = false;
    let voorodak_otp_request_link = null;

    function voorodakQuery(selector, context) {
        return (context || document).querySelector(selector);
    }

    function voorodakQueryAll(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    // All authentication forms are AJAX-driven. Prevent native submits so
    // button clicks and Enter-key submissions cannot cause a duplicate request
    // or a full page reload behind the AJAX flow.
    document.addEventListener('click', function (e) {
        if (e.target.closest('.voorodak__wrapper-main button[id^="voorodak__submit-"]')) {
            e.preventDefault();
        }
    });

    voorodakQueryAll('.voorodak__wrapper-main-box').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const submitButton = form.querySelector('button[id^="voorodak__submit-"]');
            if (submitButton && !submitButton.disabled) {
                submitButton.click();
            }
        });
    });

    voorodakQueryAll('.voorodak-password-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const wrapper = toggle.closest('.voorodak-password-control');
            const input = wrapper ? wrapper.querySelector('input') : null;
            if (!input) return;

            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
            toggle.setAttribute('aria-label', showing ? ((voorodak_data.i18n && voorodak_data.i18n.show_password) || 'نمایش رمز عبور') : ((voorodak_data.i18n && voorodak_data.i18n.hide_password) || 'پنهان کردن رمز عبور'));
            toggle.classList.toggle('is-visible', !showing);
            input.focus();
        });
    });

    function voorodakFieldValue(selector, context) {
        const element = typeof selector === 'string' ? voorodakQuery(selector, context) : selector;
        return element ? element.value : '';
    }

    function voorodakSetValue(selector, val, context) {
        const element = typeof selector === 'string' ? voorodakQuery(selector, context) : selector;
        if (element) {
            element.value = val;
        }
    }

    function voorodakSetHtml(element, html) {
        if (element) {
            element.innerHTML = html || '';
        }
    }

    function voorodakSetText(element, text) {
        if (element) {
            element.textContent = text || '';
        }
    }

    function voorodakHide(selector, context) {
        voorodakQueryAll(selector, context).forEach(function (element) {
            element.style.display = 'none';
        });
    }

    function voorodakShow(selector, display, context) {
        voorodakQueryAll(selector, context).forEach(function (element) {
            element.style.display = display || 'block';
        });
    }

    function voorodakTriggerClick(selector, context) {
        const element = typeof selector === 'string' ? voorodakQuery(selector, context) : selector;
        if (element) {
            element.click();
        }
    }

    function getResponseMessage(response) {
        if (!response || typeof response.data === 'undefined') {
            return '';
        }

        if (typeof response.data === 'string') {
            return response.data;
        }

        return response.data.message || '';
    }

    function autoFillOTP(selector) {
        if (!('OTPCredential' in window)) return;

        const otpInput = document.querySelector(selector);
        if (!otpInput) return;

        const otpForm = otpInput.closest('form');
        if (!otpForm) return;

        const submitBtn = otpForm.querySelector('button');
        const abortController = new AbortController();

        otpForm.addEventListener('submit', function (e) {
            e.preventDefault();
            abortController.abort();
        });

        navigator.credentials.get({
            otp: { transport: ['sms'] },
            signal: abortController.signal
        }).then(function (otp) {
            otpInput.value = otp.code;
            if (submitBtn) {
                submitBtn.click();
            }
        }).catch(function () {});
    }

    function normalizePersianDigits(input) {
        var pn = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        var en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        var cache = input.value;

        for (var i = 0; i < 10; i++) {
            cache = cache.replace(new RegExp(pn[i], 'g'), en[i]);
        }

        input.value = cache;
    }

    document.addEventListener('input', function (e) {
        if (e.target.matches('.voorodak__wrapper-main-box-field input')) {
            normalizePersianDigits(e.target);
        }
    });

    voorodakQueryAll('.voorodak__wrapper-main-box-action a').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var target = link.getAttribute('href');
            var username = voorodakFieldValue('input[name=voorodak__username]');

            if (
                target === '#voorodak__wrapper-main-otp' &&
                voorodak_password_login_priority === '1' &&
                voorodak_mobile_regex.test(username)
            ) {
                if (!voorodak_ajax) {
                    return;
                }
                voorodak_otp_request = true;
                voorodak_otp_request_link = link;
                voorodakSetHtml(voorodak_messages, '');
                voorodakTriggerClick('#voorodak__submit-username');
                return;
            }

            voorodakHide('.voorodak__wrapper-main-box');
            voorodakShow(target);
            voorodakSetHtml(voorodak_messages, '');
        });
    });

    voorodakQueryAll('.voorodak__wrapper-main-head svg').forEach(function (icon) {
        icon.addEventListener('click', function () {
            icon.style.display = 'none';
            voorodakHide('.voorodak__wrapper-main-box');
            voorodakShow('#voorodak__wrapper-main-username');
        });
    });

    function removeValidationMessages() {
        voorodakQueryAll('.voorodak__wrapper-main-box-field-invalid').forEach(function (element) {
            const next = element.nextElementSibling;
            if (next && next.tagName && next.tagName.toLowerCase() === 'span') {
                next.remove();
            }
            element.classList.remove('voorodak__wrapper-main-box-field-invalid');
            element.removeAttribute('aria-invalid');
        });
    }

    function addValidationMessage(element, message) {
        if (!element) {
            return;
        }

        element.classList.add('voorodak__wrapper-main-box-field-invalid');
        element.setAttribute('aria-invalid', 'true');
        const validationMessage = document.createElement('span');
        validationMessage.textContent = message;
        element.insertAdjacentElement('afterend', validationMessage);
    }

    function getOtpValidationElement(element) {
        const field = element ? element.closest('.voorodak__wrapper-main-box-field') : null;
        const boxes = field ? field.querySelector('.voorodak__otp-boxes') : null;
        return boxes || element;
    }

    function syncOtpBoxes(boxes) {
        var target = boxes.dataset.target;
        var otp = '';

        voorodakQueryAll('.voorodak__otp-box', boxes).forEach(function (input) {
            otp += input.value.trim();
        });

        voorodakSetValue("input[name='" + target + "']", otp, boxes.closest('.voorodak__wrapper-main-box-field'));

        return otp;
    }

    function fillOtpBoxes(boxes, digits) {
        digits = digits.replace(/\D/g, '').slice(0, parseInt(voorodak_otp_length, 10)).split('');

        voorodakQueryAll('.voorodak__otp-box', boxes).forEach(function (input, index) {
            input.value = digits[index] || '';
        });

        return syncOtpBoxes(boxes);
    }

    function validateUsername(element) {
        var fieldValue = element ? element.value.trim() : '';
        removeValidationMessages();

        if (fieldValue.length < 1) {
            addValidationMessage(element, (voorodak_data.i18n && voorodak_data.i18n.field_required) || 'لطفا این قسمت را خالی نگذارید');
            voorodak_error = true;
            return;
        }

        if (voorodak_login_type === 'mobile' && !voorodak_mobile_regex.test(fieldValue)) {
            addValidationMessage(element, (voorodak_data.i18n && voorodak_data.i18n.invalid_mobile) || 'شماره موبایل صحیح نمی‌باشد.');
            voorodak_error = true;
            return;
        } else if (voorodak_login_type === 'mobile-email' && !voorodak_mobile_regex.test(fieldValue) && !voorodak_email_regex.test(fieldValue)) {
            addValidationMessage(element, (voorodak_data.i18n && voorodak_data.i18n.invalid_mobile_email) || 'شماره موبایل یا ایمیل صحیح نمی‌باشد.');
            voorodak_error = true;
            return;
        }

        voorodak_error = false;
    }

    function validateCaptcha(form) {
        if (voorodak_data.captcha_enabled !== '1') {
            return {
                captcha: '',
                captcha_token: '',
                website: voorodakFieldValue('input[name=website]', form)
            };
        }

        var captchaElement = voorodakQuery('input[name=voorodak__captcha]', form);
        var captchaValue = captchaElement ? captchaElement.value.trim() : '';

        if (captchaValue.length < 1) {
            addValidationMessage(captchaElement, (voorodak_data.i18n && voorodak_data.i18n.enter_captcha) || 'لطفا کد امنیتی را وارد کنید');
            voorodak_error = true;
        }

        return {
            captcha: captchaValue,
            captcha_token: voorodakFieldValue('input[name=voorodak__captcha-token]', form),
            website: voorodakFieldValue('input[name=website]', form)
        };
    }

    function validatePersianName(element) {
        if (voorodak_family_name_persian_only !== '1' || !element) {
            return true;
        }

        var fieldValue = element.value.trim();
        if (!fieldValue) {
            return true;
        }

        if (voorodak_english_or_digit_regex.test(fieldValue) || !voorodak_persian_name_regex.test(fieldValue)) {
            addValidationMessage(element, (voorodak_data.i18n && voorodak_data.i18n.persian_name_only) || 'نام و نام خانوادگی باید فقط با حروف فارسی وارد شود');
            voorodak_error = true;
            return false;
        }

        return true;
    }

    function isValidNationalCode(nationalCode) {
        nationalCode = String(nationalCode || '').replace(/\D/g, '');

        if (!/^\d{10}$/.test(nationalCode) || /^(\d)\1{9}$/.test(nationalCode)) {
            return false;
        }

        var sum = 0;
        for (var i = 0; i < 9; i++) {
            sum += parseInt(nationalCode.charAt(i), 10) * (10 - i);
        }

        var remainder = sum % 11;
        var checkDigit = parseInt(nationalCode.charAt(9), 10);

        return remainder < 2 ? checkDigit === remainder : checkDigit === 11 - remainder;
    }

    function validateNationalCode(element) {
        if (voorodak_national_code_field !== '1' || !element || element.offsetParent === null) {
            return true;
        }

        var fieldValue = element.value.trim();
        if (!fieldValue) {
            if (voorodak_national_code_force === '1') {
                addValidationMessage(element, (voorodak_data.i18n && voorodak_data.i18n.national_code_required) || 'کد ملی الزامی میباشد');
                voorodak_error = true;
                return false;
            }
            return true;
        }

        if (!isValidNationalCode(fieldValue)) {
            addValidationMessage(element, (voorodak_data.i18n && voorodak_data.i18n.invalid_national_code) || 'کد ملی معتبر نمیباشد');
            voorodak_error = true;
            return false;
        }

        return true;
    }

    function validateOTP(element) {
        var fieldValue = element ? element.value.trim() : '';
        var validationElement = getOtpValidationElement(element);
        removeValidationMessages();

        if (fieldValue.length !== parseInt(voorodak_otp_length, 10)) {
            addValidationMessage(validationElement, ((voorodak_data.i18n && voorodak_data.i18n.otp_length_error) || 'کد تایید باید %s رقم باشد').replace('%s', voorodak_otp_length));
            voorodak_error = true;
        } else if (!voorodak_number_regex.test(fieldValue)) {
            addValidationMessage(validationElement, (voorodak_data.i18n && voorodak_data.i18n.otp_numeric) || 'کد تایید باید عددی باشد');
            voorodak_error = true;
        } else {
            voorodak_error = false;
        }
    }

    function validateLoginPassword(element) {
        var fieldValue = element ? element.value : '';
        removeValidationMessages();

        if (!fieldValue) {
            addValidationMessage(element, (voorodak_data.i18n && voorodak_data.i18n.enter_password) || 'لطفا رمز عبور را وارد کنید');
            voorodak_error = true;
            return false;
        }

        voorodak_error = false;
        return true;
    }

    function validatePasswordPolicy(element) {
        var fieldValue = element ? element.value : '';
        removeValidationMessages();

        if (fieldValue.length < parseInt(voorodak_password_length, 10)) {
            addValidationMessage(element, ((voorodak_data.i18n && voorodak_data.i18n.password_min_length) || 'رمز عبور باید حداقل %s کاراکتر باشد').replace('%s', voorodak_password_length));
            voorodak_error = true;
            return false;
        }

        if (!/[A-Za-z]/.test(fieldValue) || !/[0-9]/.test(fieldValue)) {
            addValidationMessage(element, (voorodak_data.i18n && voorodak_data.i18n.password_strength) || 'رمز عبور باید شامل حداقل یک حرف انگلیسی و یک عدد باشد');
            voorodak_error = true;
            return false;
        }

        voorodak_error = false;
        return true;
    }

    function validatePasswordConfirmation(passwordElement, confirmationElement) {
        if (!passwordElement || !confirmationElement) {
            return true;
        }

        if (passwordElement.value !== confirmationElement.value) {
            addValidationMessage(confirmationElement, (voorodak_data.i18n && voorodak_data.i18n.password_mismatch) || 'تکرار رمز عبور با رمز جدید مطابقت ندارد');
            voorodak_error = true;
            return false;
        } else {
            voorodak_error = false;
            return true;
        }
    }

    voorodakQueryAll('input[name=voorodak__username],input[name=voorodak__username-forget]').forEach(function (input) {
        input.addEventListener('focusout', function () {
            validateUsername(input);
        });
    });

    voorodakQueryAll('input[name=voorodak__otp],input[name=voorodak__otp-reset]').forEach(function (input) {
        input.addEventListener('focusout', function () {
            validateOTP(input);
        });

        input.addEventListener('input', function () {
            if (input.value.trim().length >= parseInt(voorodak_otp_length, 10)) {
                const form = input.closest('form');
                voorodakTriggerClick(form ? form.querySelector('button') : null);
            }
        });
    });

    voorodakQueryAll('input[name=voorodak__first_name],input[name=voorodak__last_name]').forEach(function (input) {
        input.addEventListener('focusout', function () {
            removeValidationMessages();
            validatePersianName(input);
        });
    });

    voorodakQueryAll('input[name=voorodak__national_code]').forEach(function (input) {
        input.addEventListener('focusout', function () {
            removeValidationMessages();
            validateNationalCode(input);
        });
    });

    document.addEventListener('input', function (e) {
        if (!e.target.matches('.voorodak__otp-box')) {
            return;
        }

        var input = e.target;
        var boxes = input.closest('.voorodak__otp-boxes');
        var rawValue = input.value.replace(/\D/g, '');
        var otp;

        if (rawValue.length > 1) {
            otp = fillOtpBoxes(boxes, rawValue);
        } else {
            input.value = rawValue;
            otp = syncOtpBoxes(boxes);
        }

        if (rawValue.length === 1 && input.nextElementSibling && input.nextElementSibling.classList.contains('voorodak__otp-box')) {
            input.nextElementSibling.focus();
        }

        if (otp.length >= parseInt(voorodak_otp_length, 10)) {
            voorodakTriggerClick(boxes.closest('form').querySelector('button'));
        }
    });

    document.addEventListener('keydown', function (e) {
        if (!e.target.matches('.voorodak__otp-box')) {
            return;
        }

        var input = e.target;
        var previous = input.previousElementSibling;
        if (e.key === 'Backspace' && !input.value && previous && previous.classList.contains('voorodak__otp-box')) {
            previous.focus();
        }
    });

    document.addEventListener('paste', function (e) {
        if (!e.target.matches('.voorodak__otp-box')) {
            return;
        }

        e.preventDefault();
        var text = (e.clipboardData || window.clipboardData).getData('text');
        var boxes = e.target.closest('.voorodak__otp-boxes');
        var otp = fillOtpBoxes(boxes, text);
        var nextInput = voorodakQueryAll('.voorodak__otp-box', boxes)[otp.length];

        if (otp.length >= parseInt(voorodak_otp_length, 10)) {
            voorodakTriggerClick(boxes.closest('form').querySelector('button'));
        } else if (nextInput) {
            nextInput.focus();
        }
    });

    const loginPasswordInput = voorodakQuery('input[name=voorodak__password]');
    if (loginPasswordInput) {
        loginPasswordInput.addEventListener('focusout', function () {
            validateLoginPassword(loginPasswordInput);
        });
    }

    voorodakQueryAll('input[name=voorodak__password_register],input[name=voorodak__new-password]').forEach(function (input) {
        input.addEventListener('focusout', function () {
            // Registration password is optional unless its field is visible and
            // configured by the server. Empty optional values are left to the
            // authoritative server-side rules.
            if (input.name === 'voorodak__password_register' && input.offsetParent === null) {
                return;
            }
            if (input.value || input.name === 'voorodak__new-password') {
                validatePasswordPolicy(input);
            }
        });
    });

    voorodakQueryAll('.voorodak__wrapper-main form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
        });
    });

    var intervalId;
    let duration = 120;
    const timerKey = 'savedTimer';
    const startTimeKey = 'startTime';

    function setTimerText(text) {
        voorodakQueryAll('.voorodak__wrapper-main-box-timer-countdown span').forEach(function (timer) {
            timer.textContent = text;
        });
    }

    function startTimer(durationValue) {
        var timer = durationValue;
        var minutes;
        var seconds;

        intervalId = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            setTimerText(minutes + ':' + seconds);

            if (--timer < 0) {
                clearInterval(intervalId);
                localStorage.removeItem(timerKey);
                localStorage.removeItem(startTimeKey);
                voorodakShow('.voorodak__wrapper-main-box-timer-resend');
                voorodakHide('.voorodak__wrapper-main-box-timer-countdown');
            } else {
                voorodakHide('.voorodak__wrapper-main-box-timer-resend');
                voorodakShow('.voorodak__wrapper-main-box-timer-countdown');
            }
        }, 1000);
    }

    function restartOtpTimer() {
        if (intervalId) {
            clearInterval(intervalId);
        }

        setTimerText('02:00');
        voorodakHide('.voorodak__wrapper-main-box-timer-resend');
        voorodakShow('.voorodak__wrapper-main-box-timer-countdown');
        localStorage.setItem(startTimeKey, Date.now());
        localStorage.setItem(timerKey, duration);
        startTimer(duration);
    }

    function toggleOtpRequestLinkLoading(isLoading) {
        if (!voorodak_otp_request_link) {
            return;
        }

        if (isLoading) {
            if (!voorodak_otp_request_link.dataset.voorodakOriginalHtml) {
                voorodak_otp_request_link.dataset.voorodakOriginalHtml = voorodak_otp_request_link.innerHTML;
            }
            voorodak_otp_request_link.innerHTML = (voorodak_data.i18n && voorodak_data.i18n.sending) || 'در حال ارسال ...';
            voorodak_otp_request_link.classList.add('voorodak__link-loading');
            return;
        }

        var originalHtml = voorodak_otp_request_link.dataset.voorodakOriginalHtml;
        if (originalHtml) {
            voorodak_otp_request_link.innerHTML = originalHtml;
        }
        voorodak_otp_request_link.classList.remove('voorodak__link-loading');
        voorodak_otp_request_link = null;
    }

    function resumeTimer() {
        const savedTimer = localStorage.getItem(timerKey);
        const savedStartTime = localStorage.getItem(startTimeKey);

        if (savedTimer && savedStartTime) {
            const elapsedTime = Math.floor((Date.now() - savedStartTime) / 1000);
            const remainingTime = savedTimer - elapsedTime;

            if (remainingTime > 0) {
                startTimer(remainingTime);
            } else {
                localStorage.removeItem(timerKey);
                localStorage.removeItem(startTimeKey);
            }
        }
    }
    resumeTimer();

    function ajaxRequest(data, beforeSendCallback, successCallback, errorCallback) {
        if (!voorodak_ajax || voorodak_error) {
            return;
        }

        var controller = new AbortController();
        var timeout = setTimeout(function () {
            controller.abort();
        }, 20000);

        beforeSendCallback();

        fetch(voorodak_data.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams(data).toString(),
            signal: controller.signal
        }).then(function (response) {
            return response.json();
        }).then(function (response) {
            clearTimeout(timeout);
            successCallback(response);
        }).catch(function () {
            clearTimeout(timeout);
            errorCallback();
        });
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#voorodak__submit-username')) {
            return;
        }

        var button = e.target.closest('#voorodak__submit-username');
        var buttonInit = button.innerHTML;
        var action = button.getAttribute('id');
        var form = button.closest('form');
        var usernameElement = voorodakQuery('input[name=voorodak__username]');
        var username = voorodakFieldValue(usernameElement);

        validateUsername(usernameElement);
        var captchaData = validateCaptcha(form);

        ajaxRequest(
            {
                action: action,
                username: username,
                captcha: captchaData.captcha,
                captcha_token: captchaData.captcha_token,
                website: captchaData.website,
                otp_request: voorodak_otp_request ? '1' : '',
                security: voorodak_security
            },
            function () {
                voorodak_ajax = false;
                voorodakSetHtml(voorodak_messages, '');
                if (voorodak_otp_request) {
                    toggleOtpRequestLinkLoading(true);
                }
                button.innerHTML = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
            },
            function (response) {
                voorodak_ajax = true;
                toggleOtpRequestLinkLoading(false);
                voorodak_otp_request = false;
                button.innerHTML = buttonInit;
                voorodakSetHtml(voorodak_messages, getResponseMessage(response));

                if (response.success) {
                    voorodakHide('.voorodak__wrapper-main-box');
                    voorodakShow('.voorodak__wrapper-main-box-timer');
                    voorodakHide('#voorodak__wrapper-main-username');
                    voorodakShow('.voorodak__wrapper-main-head svg');

                    if (voorodak_mobile_regex.test(username) || response.data.email_register) {
                        if (response.data.password_priority) {
                            voorodakShow("a[href='#voorodak__wrapper-main-otp']", 'inline-flex');
                            voorodakShow('#voorodak__wrapper-main-password');
                            return;
                        }

                        if (response.data.sent) {
                            restartOtpTimer();
                        }

                        voorodakSetText(voorodakQuery('#voorodak__wrapper-main-otp .voorodak__wrapper-main-box-description'), response.data.description);

                        if (response.data.register) {
                            voorodakHide('.voorodak__wrapper-main-box-action');
                            voorodakShow('input[name=voorodak__first_name],input[name=voorodak__last_name],input[name=voorodak__national_code],input[name=voorodak__email],input[name=voorodak__password_register]');
                            if (response.data.email_register) {
                                voorodakHide('input[name=voorodak__email]');
                            }
                            if (voorodak_data.autofill === '1') {
                                autoFillOTP('.otp-field1');
                            }
                        } else {
                            voorodakHide('input[name=voorodak__first_name],input[name=voorodak__last_name],input[name=voorodak__national_code],input[name=voorodak__email],input[name=voorodak__password_register]');
                            voorodakShow('.voorodak__wrapper-main-box-action', 'flex');
                            voorodakShow("a[href='#voorodak__wrapper-main-otp']", 'inline-flex');
                            if (voorodak_data.autofill === '1') {
                                autoFillOTP('.otp-field1');
                            }
                        }

                        voorodakShow('#voorodak__wrapper-main-otp');
                    } else {
                        voorodakHide("a[href='#voorodak__wrapper-main-otp']");
                        voorodakShow('#voorodak__wrapper-main-password');
                    }
                }
            },
            function () {
                voorodak_ajax = true;
                toggleOtpRequestLinkLoading(false);
                voorodak_otp_request = false;
                button.innerHTML = buttonInit;
            }
        );
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#voorodak__submit-otp')) {
            return;
        }

        var button = e.target.closest('#voorodak__submit-otp');
        var buttonInit = button.innerHTML;
        var action = button.getAttribute('id');
        var username = voorodakFieldValue('input[name=voorodak__username]');
        var firstName = voorodakFieldValue('input[name=voorodak__first_name]');
        var lastName = voorodakFieldValue('input[name=voorodak__last_name]');
        var nationalCode = voorodakFieldValue('input[name=voorodak__national_code]');
        var email = voorodakFieldValue('input[name=voorodak__email]');
        var password = voorodakFieldValue('input[name=voorodak__password_register]');
        var otpElement = voorodakQuery('input[name=voorodak__otp]');
        var otp = voorodakFieldValue(otpElement);
        var registerPasswordElement = voorodakQuery('input[name=voorodak__password_register]');

        validateOTP(otpElement);
        validatePersianName(voorodakQuery('input[name=voorodak__first_name]'));
        validatePersianName(voorodakQuery('input[name=voorodak__last_name]'));
        validateNationalCode(voorodakQuery('input[name=voorodak__national_code]'));
        if (registerPasswordElement && registerPasswordElement.offsetParent !== null && registerPasswordElement.value) {
            validatePasswordPolicy(registerPasswordElement);
        }

        ajaxRequest(
            {
                action: action,
                username: username,
                first_name: firstName,
                last_name: lastName,
                national_code: nationalCode,
                email: email,
                password: password,
                otp: otp,
                security: voorodak_security
            },
            function () {
                voorodak_ajax = false;
                voorodakSetHtml(voorodak_messages, '');
                button.innerHTML = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
            },
            function (response) {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
                voorodakSetHtml(voorodak_messages, getResponseMessage(response));
                if (response.success) {
                    voorodak_ajax = false;
                    window.location = voorodak_backurl;
                }
            },
            function () {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
            }
        );
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#voorodak__submit-password')) {
            return;
        }

        var button = e.target.closest('#voorodak__submit-password');
        var buttonInit = button.innerHTML;
        var action = button.getAttribute('id');
        var username = voorodakFieldValue('input[name=voorodak__username]');
        var passwordElement = voorodakQuery('input[name=voorodak__password]');
        var password = voorodakFieldValue(passwordElement);

        validateLoginPassword(passwordElement);

        ajaxRequest(
            {
                action: action,
                username: username,
                password: password,
                security: voorodak_security
            },
            function () {
                voorodak_ajax = false;
                voorodakSetHtml(voorodak_messages, '');
                button.innerHTML = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
            },
            function (response) {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
                voorodakSetHtml(voorodak_messages, getResponseMessage(response));
                if (response.success) {
                    voorodak_ajax = false;
                    window.location = voorodak_backurl;
                }
            },
            function () {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
            }
        );
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#voorodak__submit-forget')) {
            return;
        }

        var button = e.target.closest('#voorodak__submit-forget');
        var buttonInit = button.innerHTML;
        var action = button.getAttribute('id');
        var form = button.closest('form');
        var usernameElement = voorodakQuery('input[name=voorodak__username-forget]');
        var username = voorodakFieldValue(usernameElement);

        validateUsername(usernameElement);
        var captchaData = validateCaptcha(form);

        ajaxRequest(
            {
                action: action,
                username: username,
                login_url: voorodak_login_url,
                captcha: captchaData.captcha,
                captcha_token: captchaData.captcha_token,
                website: captchaData.website,
                security: voorodak_security
            },
            function () {
                voorodak_ajax = false;
                voorodakSetHtml(voorodak_messages, '');
                button.innerHTML = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
            },
            function (response) {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
                voorodakSetHtml(voorodak_messages, getResponseMessage(response));

                if (response.success && voorodak_mobile_regex.test(username)) {
                    if (response.data.sent) {
                        restartOtpTimer();
                    }
                    voorodakShow('#voorodak__wrapper-main-otp-reset');
                    voorodakHide('#voorodak__wrapper-main-forget');
                    if (voorodak_data.autofill === '1') {
                        autoFillOTP('.otp-field2');
                    }
                }
            },
            function () {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
            }
        );
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#voorodak__submit-otp-reset')) {
            return;
        }

        var button = e.target.closest('#voorodak__submit-otp-reset');
        var buttonInit = button.innerHTML;
        var action = button.getAttribute('id');
        var username = voorodakFieldValue('input[name=voorodak__username-forget]');
        var otpElement = voorodakQuery('input[name=voorodak__otp-reset]');
        var otp = voorodakFieldValue(otpElement);

        validateOTP(otpElement);

        ajaxRequest(
            {
                action: action,
                username: username,
                otp: otp,
                security: voorodak_security
            },
            function () {
                voorodak_ajax = false;
                voorodakSetHtml(voorodak_messages, '');
                button.innerHTML = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
            },
            function (response) {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
                voorodakSetHtml(voorodak_messages, getResponseMessage(response));

                if (response.success && voorodak_mobile_regex.test(username)) {
                    voorodakHide('#voorodak__wrapper-main-otp-reset');
                    voorodakShow('#voorodak__wrapper-main-reset');
                    voorodakSetValue('input[name=voorodak__reset-token]', response.data.reset_token);
                }
            },
            function () {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
            }
        );
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#voorodak__submit-reset')) {
            return;
        }

        var button = e.target.closest('#voorodak__submit-reset');
        var buttonInit = button.innerHTML;
        var action = button.getAttribute('id');
        var newPasswordElement = voorodakQuery('input[name=voorodak__new-password]');
        var newPassword = voorodakFieldValue(newPasswordElement);
        var newPassword2Element = voorodakQuery('input[name=voorodak__new-password2]');
        var newPassword2 = voorodakFieldValue(newPassword2Element);
        var resetToken = voorodakFieldValue('input[name=voorodak__reset-token]');

        var passwordValid = validatePasswordPolicy(newPasswordElement);
        if (passwordValid) {
            validatePasswordConfirmation(newPasswordElement, newPassword2Element);
        }

        ajaxRequest(
            {
                action: action,
                new_password: newPassword,
                new_password2: newPassword2,
                reset_token: resetToken,
                security: voorodak_security
            },
            function () {
                voorodak_ajax = false;
                voorodakSetHtml(voorodak_messages, '');
                button.innerHTML = '<div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>';
            },
            function (response) {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
                voorodakSetHtml(voorodak_messages, getResponseMessage(response));
                if (response.success) {
                    voorodak_ajax = false;
                    window.location = voorodak_backurl;
                }
            },
            function () {
                voorodak_ajax = true;
                button.innerHTML = buttonInit;
            }
        );
    });

    if (voorodakQuery('.voorodak__wrapper-main-box-timer')) {
        voorodakQueryAll('.voorodak__wrapper-main-box-timer-resend').forEach(function (button) {
            button.addEventListener('click', function () {
                button.style.display = 'none';
            });
            button.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    button.click();
                }
            });
        });

        const otpResend = voorodakQuery('#voorodak__wrapper-main-otp .voorodak__wrapper-main-box-timer-resend');
        if (otpResend) {
            otpResend.addEventListener('click', function () {
                voorodakTriggerClick('#voorodak__submit-username');
            });
        }

        const otpResetResend = voorodakQuery('#voorodak__wrapper-main-otp-reset .voorodak__wrapper-main-box-timer-resend');
        if (otpResetResend) {
            otpResetResend.addEventListener('click', function () {
                voorodakTriggerClick('#voorodak__submit-forget');
            });
        }
    }

});
