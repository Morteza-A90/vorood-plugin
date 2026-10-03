<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;
$settings = get_option(VOORODAK_OPTION);
$gateway = $settings['gateway'] ?? 'melipayamakrest_pattern';
$gateway_username = $settings['gateway_username'] ?? '';
$gateway_password = $settings['gateway_password'] ?? '';
$gateway_from = $settings['gateway_from'] ?? '';
$gateway_pattern_otp = $settings['gateway_pattern_otp'] ?? '';
$gateway_message = $settings['gateway_message'] ?? '';
$gateway_timeout = (int) ($settings['gateway_timeout'] ?? 20);
$variable = $settings['variable'] ?? 'otp';
$template = $settings['template'] ?? 'default';
$logo = $settings['logo'] ?? '';
$cover = $settings['cover'] ?? '';
$bg_color = $settings['bg_color'] ?? '#ffffff';
$btnback = $settings['btnback'] ?? '';
$btnbackprev = $settings['btnbackprev'] ?? '';
$button_color = $settings['button_color'] ?? '#5498fa';
$button_color_hover = $settings['button_color_hover'] ?? '#2c61a6';
$login_page_id = $settings['login_page_id'] ?? '';
$backurl = $settings['backurl'] ?? 'prev';
$backurl_custom = $settings['backurl_custom'] ?? '';
$panelurl_custom = $settings['panelurl_custom'] ?? '';
$logouturl = $settings['logouturl'] ?? '';
$woocommerce_default = function_exists('voorodak_is_woocommerce_active') && voorodak_is_woocommerce_active() ? '1' : '';
$woocommerce_login = array_key_exists('woocommerce_login', (array) $settings) ? $settings['woocommerce_login'] : $woocommerce_default;
$woocommerce_checkout = array_key_exists('woocommerce_checkout', (array) $settings) ? $settings['woocommerce_checkout'] : $woocommerce_default;
$digits = $settings['digits'] ?? '';
$digits_meta = $settings['digits_meta'] ?? 'digits_phone';
$date_register = $settings['date_register'] ?? '';
$use_shortcode = $settings['use_shortcode'] ?? '';
$sms_login_admin = $settings['sms_login_admin'] ?? '';
$register_allow = $settings['register_allow'] ?? '';
$autofill = $settings['autofill'] ?? '';
$baleapi = $settings['baleapi'] ?? '';
$balebotid = $settings['balebotid'] ?? '';
$balebottoken = $settings['balebottoken'] ?? '';
$baleotp = $settings['baleotp'] ?? '';
$balechatids = $settings['balechatids'] ?? '';
$family_name = $settings['family_name'] ?? '';
$family_name_persian_only = $settings['family_name_persian_only'] ?? '';
$national_code_field = $settings['national_code_field'] ?? '';
$national_code_force = $settings['national_code_force'] ?? '';
$email_field = $settings['email_field'] ?? '';
$email_register = $settings['email_register'] ?? '';
$disable_random_email = $settings['disable_random_email'] ?? '';
$bale_comment_new = $settings['bale_comment_new'] ?? '';
$bale_user_login = $settings['bale_user_login'] ?? '';
$bale_user_login_admin = $settings['bale_user_login_admin'] ?? '';
$bale_user_register = $settings['bale_user_register'] ?? '';
$bale_order_completed = $settings['bale_order_completed'] ?? '';
$password_field = $settings['password_field'] ?? '';
$password_login_priority = $settings['password_login_priority'] ?? '';
$remember_me = array_key_exists('remember_me', (array) $settings) ? $settings['remember_me'] : '1';
$family_name_force = $settings['family_name_force'] ?? '';
$email_field_force = $settings['email_field_force'] ?? '';
$disable_admin_login = $settings['disable_admin_login'] ?? '';
$username_format = $settings['username_format'] ?? 'with-zero';
$user_field_meta = $settings['user_field_meta'] ?? 'billing_phone';
$user_field_meta2 = $settings['user_field_meta2'] ?? '';
$otp_length = $settings['otp_length'] ?? '6';
$otp_boxed = $settings['otp_boxed'] ?? '';
$password_length = $settings['password_length'] ?? '8';
$login_type = $settings['login_type'] ?? 'mobile-email';
$form_name = $settings['form_name'] ?? 'ورود / ثبت نام';
$term_editor = $settings['term_editor'] ?? '';
$sms_admins = $settings['sms_admins'] ?? '';
$block_ip = $settings['block_ip'] ?? '';
$block_mobile = $settings['block_mobile'] ?? '';
$captcha_enabled = $settings['captcha_enabled'] ?? '';
$sms_login_admin = $settings['sms_login_admin'] ?? '';
$sms_login_roleadmin_admin = $settings['sms_login_roleadmin_admin'] ?? '';
$sms_register_admin = $settings['sms_register_admin'] ?? '';
$sms_login = $settings['sms_login'] ?? '';
$sms_register = $settings['sms_register'] ?? '';
$sms_comment_new_admin = $settings['sms_comment_new_admin'] ?? '';
$sms_comment_reply_user = $settings['sms_comment_reply_user'] ?? '';
$sms_login_admin_pattern = $settings['sms_login_admin_pattern'] ?? '';
$sms_login_roleadmin_admin_pattern = $settings['sms_login_roleadmin_admin_pattern'] ?? '';
$sms_register_admin_pattern = $settings['sms_register_admin_pattern'] ?? '';
$sms_login_pattern = $settings['sms_login_pattern'] ?? '';
$sms_register_pattern = $settings['sms_register_pattern'] ?? '';
$sms_comment_new_admin_pattern = $settings['sms_comment_new_admin_pattern'] ?? '';
$sms_comment_reply_user_pattern = $settings['sms_comment_reply_user_pattern'] ?? '';

$sms_user_event_variables_options = 'username:نام کاربری,name:نام,userid:شناسه عددی کاربر,date:تاریخ,time:ساعت';
$sms_comment_variables_options = 'title:عنوان مطلب/صفحه,postlink:لینک مطلب/صفحه,date:تاریخ,time:ساعت';

?>
<div class="wrap voorodak">
    <h1 class="screen-reader-text">تنظیمات ورودک</h1>
    <header class="voorodak-admin-hero">
        <div class="voorodak-admin-hero__identity">
            <div class="voorodak-admin-hero__mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 3 4.5 6.5v5.1c0 4.2 2.8 7.7 7.5 9.4 4.7-1.7 7.5-5.2 7.5-9.4V6.5L12 3Z" stroke-linejoin="round"/>
                    <path d="m8.7 12 2.1 2.1 4.7-4.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div>
                <div class="voorodak-admin-hero__eyebrow">ورود و ثبت‌نام وردپرس</div>
                <h2>تنظیمات ورودک</h2>
                <p>مدیریت احراز هویت، رمز یک‌بارمصرف، اعلان‌ها، امنیت و ظاهر فرم‌ها.</p>
            </div>
        </div>
        <div class="voorodak-admin-hero__meta">
            <span class="voorodak-admin-badge">نسخه <?php echo esc_html(VOORODAK_VERSION); ?></span>
            <span class="voorodak-admin-badge voorodak-admin-badge--success">نسخه متن‌باز</span>
        </div>
    </header>
    <div class="voorodak-admin-toolbar">
        <label class="voorodak-admin-search" for="voorodak-settings-search">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.2-3.2"></path></svg>
            <span class="screen-reader-text">جستجو در تنظیمات</span>
            <input id="voorodak-settings-search" type="search" placeholder="جستجو در تنظیمات این بخش…" autocomplete="off">
        </label>
        <div class="voorodak-admin-toolbar__hint">برای ذخیره تغییرات از دکمه پایین فرم استفاده کنید.</div>
    </div>
    <div class="voorodak__body">
        <nav class="voorodak__body-tab" aria-label="بخش‌های تنظیمات" role="tablist">
            <a id="voorodak-tab-gateway" href="#gateway" role="tab" aria-controls="gateway" aria-selected="true" class="active">
                <svg width="24px"  height="24px"  viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <g id="Iconly/Bulk/Message" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g id="Group" transform="translate(1.999900, 2.999600)" fill="currentColor"  fill-rule="nonzero">
                            <path d="M20,12.9406 C20,15.7306 17.76,17.9906 14.97,18.0006 L14.96,18.0006 L5.05,18.0006 C2.27,18.0006 0,15.7506 0,12.9606 L0,12.9506 C0,12.9506 0.006,8.5246 0.014,6.2986 C0.015,5.8806 0.495,5.6466 0.822,5.9066 C3.198,7.7916 7.447,11.2286 7.5,11.2736 C8.21,11.8426 9.11,12.1636 10.03,12.1636 C10.95,12.1636 11.85,11.8426 12.56,11.2626 C12.613,11.2276 16.767,7.8936 19.179,5.9776 C19.507,5.7166 19.989,5.9506 19.99,6.3676 C20,8.5766 20,12.9406 20,12.9406" id="Fill-1" opacity="0.400000006"></path>
                            <path d="M19.4761,2.674 C18.6101,1.042 16.9061,3.55271368e-15 15.0301,3.55271368e-15 L5.0501,3.55271368e-15 C3.1741,3.55271368e-15 1.4701,1.042 0.6041,2.674 C0.4101,3.039 0.5021,3.494 0.8251,3.752 L8.2501,9.691 C8.7701,10.111 9.4001,10.32 10.0301,10.32 C10.0341,10.32 10.0371,10.32 10.0401,10.32 C10.0431,10.32 10.0471,10.32 10.0501,10.32 C10.6801,10.32 11.3101,10.111 11.8301,9.691 L19.2551,3.752 C19.5781,3.494 19.6701,3.039 19.4761,2.674" id="Fill-4"></path>
                        </g>
                    </g>
                </svg>
                سامانه پیامکی</a>
            <a id="voorodak-tab-performance" href="#performance" role="tab" aria-controls="performance" aria-selected="false">
                <svg width="24px"  height="24px"  viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <g id="Iconly/Bulk/Setting" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g id="Setting" transform="translate(2.499897, 2.000100)" fill="currentColor"  fill-rule="nonzero">
                            <path d="M9.51207539,12.83 C7.9076023,12.83 6.60971643,11.58 6.60971643,10.01 C6.60971643,8.44 7.9076023,7.18 9.51207539,7.18 C11.1165485,7.18 12.3837756,8.44 12.3837756,10.01 C12.3837756,11.58 11.1165485,12.83 9.51207539,12.83" id="Path"></path>
                            <path d="M18.730131,12.37 C18.5359591,12.07 18.2600306,11.77 17.9023455,11.58 C17.6161974,11.44 17.4322451,11.21 17.2687319,10.94 C16.7475337,10.08 17.0541209,8.95 17.9227847,8.44 C18.944742,7.87 19.2717684,6.6 18.6790331,5.61 L17.9943217,4.43 C17.411806,3.44 16.1343592,3.09 15.1226214,3.67 C14.2232989,4.15 13.0684871,3.83 12.5472888,2.98 C12.3837756,2.7 12.2917995,2.4 12.3122386,2.1 C12.3428973,1.71 12.2202625,1.34 12.0363101,1.04 C11.6581859,0.42 10.9734745,0 10.217226,0 L8.77626608,0 C8.03023719,0.02 7.34552574,0.42 6.96740151,1.04 C6.77322961,1.34 6.6608143,1.71 6.68125344,2.1 C6.70169259,2.4 6.60971643,2.7 6.44620325,2.98 C5.92500498,3.83 4.77019314,4.15 3.88109021,3.67 C2.85913283,3.09 1.59190568,3.44 0.999170395,4.43 L0.314458948,5.61 C-0.26805676,6.6 0.0589696023,7.87 1.07070741,8.44 C1.93937119,8.95 2.2459584,10.08 1.73497971,10.94 C1.56124696,11.21 1.37729463,11.44 1.09114656,11.58 C0.743681049,11.77 0.437093834,12.07 0.273580653,12.37 C-0.104543579,12.99 -0.0841044313,13.77 0.2940198,14.42 L0.999170395,15.62 C1.37729463,16.26 2.08244522,16.66 2.81825454,16.66 C3.16572005,16.66 3.574503,16.56 3.90152936,16.36 C4.15701871,16.19 4.46360592,16.13 4.80085186,16.13 C5.81258967,16.13 6.6608143,16.96 6.68125344,17.95 C6.68125344,19.1 7.62145424,20 8.8069248,20 L10.1967868,20 C11.3720378,20 12.3122386,19.1 12.3122386,17.95 C12.3428973,16.96 13.191122,16.13 14.2028598,16.13 C14.5298861,16.13 14.8364734,16.19 15.1021823,16.36 C15.4292086,16.56 15.827772,16.66 16.1854571,16.66 C16.9110468,16.66 17.6161974,16.26 17.9943217,15.62 L18.7096918,14.42 C19.0775965,13.75 19.1082552,12.99 18.730131,12.37" id="Path" opacity="0.400000006"></path>
                        </g>
                    </g>
                </svg>
                عملکرد
            </a>
            <a id="voorodak-tab-bale" href="#bale" role="tab" aria-controls="bale" aria-selected="false">
                <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'assets/images/bale.png'); ?>" width="22" height="22" alt="بله">
                اپ بله
            </a>

            <a id="voorodak-tab-display" href="#display" role="tab" aria-controls="display" aria-selected="false">
                <svg width="24px"  height="24px"  viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <g id="Iconly/Bulk/Image" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g id="Image" transform="translate(2.000000, 2.000000)" fill="currentColor"  fill-rule="nonzero">
                            <path d="M14.3328156,20 L5.66618229,20 C2.27689532,20 0,17.6228892 0,14.0842812 L0,5.91672095 C0,2.37811294 2.27689532,0 5.66618229,0 L14.3338177,0 C17.7231047,0 20,2.37811294 20,5.91672095 L20,14.0842812 C20,17.6228892 17.7231047,20 14.3328156,20" id="Fill-1" opacity="0.400000006"></path>
                            <path d="M13.4284,11.0896 C13.6504,10.7986 14.4744,9.8886 15.5394,10.5366 C16.2184,10.9446 16.7894,11.4966 17.4004,12.0876 C17.6334,12.3136 17.8004,12.5716 17.9104,12.8466 C18.2434,13.6786 18.0704,14.6786 17.7144,15.5026 C17.2924,16.4836 16.4844,17.2246 15.4664,17.5486 C15.0144,17.6936 14.5404,17.7556 14.0674,17.7556 L14.0674,17.7556 L5.6864,17.7556 C4.8524,17.7556 4.1144,17.5616 3.5094,17.1976 C3.1304,16.9696 3.0634,16.4446 3.3444,16.1026 C3.8144,15.5326 4.2784,14.9606 4.7464,14.3836 C5.6384,13.2796 6.2394,12.9596 6.9074,13.2406 C7.1784,13.3566 7.4504,13.5316 7.7304,13.7156 C8.4764,14.2096 9.5134,14.8876 10.8794,14.1516 C11.8194,13.6376 12.3624,12.7556 12.8364,11.9916 C13.0304,11.6806 13.2144,11.3706 13.4284,11.0896 Z M6.76,4.189 C8.13,4.189 9.245,5.305 9.245,6.675 C9.245,8.045 8.13,9.16 6.76,9.16 C5.389,9.16 4.275,8.045 4.275,6.675 C4.275,5.305 5.389,4.189 6.76,4.189 Z" id="Combined-Shape"></path>
                        </g>
                    </g>
                </svg>
                ظاهری
            </a>
            <a id="voorodak-tab-advance" href="#advance" role="tab" aria-controls="advance" aria-selected="false">
                <svg width="24px"  height="24px"  viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g transform="translate(2.000000, 3.000000)" fill="currentColor" fill-rule="nonzero">
                            <path d="M8.08328843,12.9579529 L1.5077694,12.9579529 C0.675551802,12.9579529 5.23038403e-14,13.6216572 5.23038403e-14,14.4392797 C5.23038403e-14,15.2558107 0.675551802,15.9206066 1.5077694,15.9206066 L8.08328843,15.9206066 C8.91550602,15.9206066 9.59105783,15.2558107 9.59105783,14.4392797 C9.59105783,13.6216572 8.91550602,12.9579529 8.08328843,12.9579529" id="Fill-1" opacity="0.400000006"></path>
                            <path d="M20,3.37856047 C20,2.56202954 19.3244482,1.89832525 18.4933417,1.89832525 L11.9178227,1.89832525 C11.0856051,1.89832525 10.4100533,2.56202954 10.4100533,3.37856047 C10.4100533,4.19618302 11.0856051,4.8598873 11.9178227,4.8598873 L18.4933417,4.8598873 C19.3244482,4.8598873 20,4.19618302 20,3.37856047" id="Fill-4" opacity="0.400000006"></path>
                            <path d="M6.87773957,3.37856047 C6.87773957,5.24522877 5.33885923,6.75821256 3.43886978,6.75821256 C1.53999144,6.75821256 4.39154885e-14,5.24522877 4.39154885e-14,3.37856047 C4.39154885e-14,1.51298378 1.53999144,-2.51650552e-14 3.43886978,-2.51650552e-14 C5.33885923,-2.51650552e-14 6.87773957,1.51298378 6.87773957,3.37856047" id="Fill-6"></path>
                            <path d="M20,14.3992173 C20,16.264794 18.4611197,17.7777778 16.5611302,17.7777778 C14.6622519,17.7777778 13.1222604,16.264794 13.1222604,14.3992173 C13.1222604,12.532549 14.6622519,11.0195652 16.5611302,11.0195652 C18.4611197,11.0195652 20,12.532549 20,14.3992173" id="Fill-9"></path>
                        </g>
                    </g>
                </svg>
                پیشرفته
            </a>
            <a id="voorodak-tab-security" href="#security" role="tab" aria-controls="security" aria-selected="false">

                <svg width="24px"  height="24px"  viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <title>Iconly/Bulk/Lock</title>
                    <g id="Iconly/Bulk/Lock" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g id="Lock" transform="translate(3.500000, 2.000000)" fill="currentColor" fill-rule="nonzero">
                            <path d="M12.7312014,6.7138357 C15.0886432,6.7138357 17,8.58304101 17,10.8884936 L17,10.8884936 L17,15.8253421 C17,18.1307947 15.0886432,20 12.7312014,20 L12.7312014,20 L4.26879857,20 C1.91135684,20 5.32907052e-15,18.1307947 5.32907052e-15,15.8253421 L5.32907052e-15,15.8253421 L5.32907052e-15,10.8884936 C5.32907052e-15,8.58304101 1.91135684,6.7138357 4.26879857,6.7138357 L4.26879857,6.7138357 Z M8.49491931,11.3843647 C8.00717274,11.3843647 7.61087866,11.7719192 7.61087866,12.2489094 L7.61087866,12.2489094 L7.61087866,14.454989 C7.61087866,14.9419165 8.00717274,15.329471 8.49491931,15.329471 C8.99282726,15.329471 9.38912134,14.9419165 9.38912134,14.454989 L9.38912134,14.454989 L9.38912134,12.2489094 C9.38912134,11.7719192 8.99282726,11.3843647 8.49491931,11.3843647 Z"></path>
                            <path d="M14.0228153,5.39595155 L14.0228153,6.8666713 C13.6671668,6.76729835 13.2911955,6.71761187 12.9050628,6.71761187 L12.2445726,6.71761187 L12.2445726,5.39595155 C12.2445726,3.37868053 10.5679438,1.73902674 8.50518231,1.73902674 C6.4424208,1.73902674 4.76579199,3.36874323 4.7556306,5.37607695 L4.7556306,6.71761187 L4.10530185,6.71761187 C3.70900777,6.71761187 3.33303646,6.76729835 2.97738793,6.8766086 L2.97738793,5.39595155 C2.98754931,2.41476285 5.45676629,4.4408921e-15 8.48485953,4.4408921e-15 C11.5535983,4.4408921e-15 14.0228153,2.41476285 14.0228153,5.39595155" opacity="0.400000006"></path>
                        </g>
                    </g>
                </svg>
                امنیت
            </a>
            <a id="voorodak-tab-develop" href="#develop" role="tab" aria-controls="develop" aria-selected="false">
                <svg width="24px"  height="24px"  viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <g id="Iconly/Bulk/Document" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g id="Document" transform="translate(3.000000, 2.000000)" fill="currentColor" fill-rule="nonzero">
                            <path d="M13.191,0 L4.81,0 C1.77,0 0,1.78 0,4.83 L0,15.16 C0,18.26 1.77,20 4.81,20 L13.191,20 C16.28,20 18,18.26 18,15.16 L18,4.83 C18,1.78 16.28,0 13.191,0" id="Path" opacity="0.400000006"></path>
                            <path d="M5.08,13.74 L12.92,13.74 C13.319,13.78 13.62,14.12 13.62,14.53 C13.62,14.929 13.319,15.27 12.92,15.31 L5.08,15.31 C4.78,15.35 4.49,15.2 4.33,14.95 C4.17,14.69 4.17,14.36 4.33,14.11 C4.49,13.85 4.78,13.71 5.08,13.74 Z M12.92,9.179 C13.35,9.179 13.7,9.53 13.7,9.96 C13.7,10.39 13.35,10.74 12.92,10.74 L5.08,10.74 C4.649,10.74 4.3,10.39 4.3,9.96 C4.3,9.53 4.649,9.179 5.08,9.179 L12.92,9.179 Z M8.069,4.65 C8.5,4.65 8.85,5 8.85,5.429 C8.85,5.87 8.5,6.22 8.069,6.22 L5.08,6.22 C4.649,6.22 4.3,5.87 4.3,5.44 C4.3,5.01 4.649,4.66 5.08,4.66 L5.08,4.65 L8.069,4.65 Z" id="Combined-Shape"></path>
                        </g>
                    </g>
                </svg>
                لاگ و توسعه
            </a>
        </nav>
        <div class="voorodak__body-main">
            <form method="post" action="options.php" autocomplete="off">
                <?php settings_fields('voorodak-settings'); ?>
                <?php do_settings_sections('voorodak-settings'); ?>
                <div id="gateway" class="voorodak__body-main-box" role="tabpanel" aria-labelledby="voorodak-tab-gateway" tabindex="0">
                    <table class="form-table">
                        <tr class="voorodak__gateway">
                            <th>سامانه پیامکی</th>
                            <td>
                                <select name="voorodak_options[gateway]">
                                    <?php
                                    $gateways = Voorodak_SMS::gateways();
                                    $gateway_selected = Voorodak_SMS::check_gateway($gateway);
                                    foreach ($gateways as $gateway_value => $gateway_name):
                                        $gateway_value_safe = esc_attr($gateway_value);
                                        $gateway_name_safe = esc_html($gateway_name);
                                        $is_selected = ($gateway_value === $gateway_selected);
                                        ?>
                                        <option value="<?php echo $gateway_value_safe; ?>" <?php selected($is_selected); ?>><?php echo $gateway_name_safe; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (Voorodak_SMS::gateway_uses_insecure_transport($gateway_selected)) : ?>
                                    <div class="voorodak-inline-warning">
                                        این درگاه در نسخه سازگاری فعلی از ارتباط HTTP رمزنگاری‌نشده استفاده می‌کند. در صورت امکان، از API/درگاه HTTPS جدید همان سرویس یا یک سرویس امن‌تر استفاده کنید.
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="voorodak__username">
                            <th>نام کاربری سامانه</th>
                            <td><input type="text" name="voorodak_options[gateway_username]"
                                       value="<?php echo esc_attr($gateway_username); ?>"/></td>
                        </tr>
                        <tr class="voorodak__password">
                            <th>رمز عبور سامانه</th>
                            <td><input type="password" autocomplete="new-password" name="voorodak_options[gateway_password]"
                                       value="<?php echo esc_attr($gateway_password); ?>"/></td>
                        </tr>
                        <tr class="voorodak__from">
                            <th>خط ارسال کننده</th>
                            <td><input type="text" name="voorodak_options[gateway_from]"
                                       value="<?php echo esc_attr($gateway_from); ?>"/></td>
                        </tr>
                        <tr class="voorodak__pattern">
                            <th>
                                کد الگو (پترن)
                                <span class="hint">متغیر فراخوانی کد : otp</span>
                                <span class="hint">از فیلد زیر میتوانید نام متغیر پیشفرض را تغییر دهید</span>
                            </th>
                            <td><input type="text" name="voorodak_options[gateway_pattern_otp]"
                                       value="<?php echo esc_attr($gateway_pattern_otp); ?>"/></td>
                        </tr>
                        <tr class="voorodak__message">
                            <th>متن پیامک
                            <span class="hint">متغیر کد تایید در پیام: %otp%</span>
                                <span class="hint">اگر از فیلد پایین نام متغیر پیشفرض را تغییر دادید، داخل متن پیام نیز آن را تغییر دهید</span>

                            </th>
                            <td><textarea rows="10" placeholder="کد تایید شما: %otp%" name="voorodak_options[gateway_message]"><?php echo esc_textarea($gateway_message); ?></textarea></td>
                        </tr>
                        <tr class="voorodak__variable">
                            <th>متغیر کد تایید</th>
                            <td><input type="text" name="voorodak_options[variable]"
                                       value="<?php echo esc_attr($variable); ?>"/></td>
                        </tr>
                        <tr>
                            <th><h3>تست ارسال پیامک</h3>
                            <span class="hint">ابتدا اطلاعات پیامکی را وارد نمایید و ذخیره کنید سپس تست بگیرید.</span></th>
                            <td></td>
                        </tr>
                        <tr>
                            <th>
                                <input type="text" id="test_phone_number" placeholder="شماره موبایل شما">
                            </th>
                            <td>
                                <span id="test_phone_submit" class="button">ارسال پیامک</span>
                            </td>
                        </tr>
                        <tr id="test_phone_result" style="display: none">
                            <th>پاسخ</th>
                            <td style="word-break: break-word;"></td>
                        </tr>
                    </table>
                    <div class="sms-notifications">
                        <h3>تنظیمات سایر پیامک ها
                            <svg width="20" height="20" data-slot="icon" aria-hidden="true" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="m19.5 8.25-7.5 7.5-7.5-7.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </h3>
                        <div class="sms-notifications-main" style="display: none">
                            <?php if (!(empty($gateway)) && strpos($gateway, '_pattern') !== false) : ?>
                                <table class="form-table">
                                    <tr>
                                        <th>شماره موبایل مدیران
                                            <span class="hint">جهت اطلاع رسانی ها به مدیریت</span>
                                        </th>
                                        <td>
                                            <textarea rows="5" placeholder="در هر خط میتوانید یک شماره موبایل وارد نمایید" name="voorodak_options[sms_admins]"><?php echo esc_textarea($sms_admins); ?></textarea>
                                        </td>
                                    </tr>
                                </table>
                                <h4>پیامک های ورود / ثبت نام</h4>
                                <div class="sms-notifications-notice">
                                    <div  class="sms-notifications-notice-icon">
                                        <svg width="36" height="36" data-slot="icon" aria-hidden="true" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                        راهنما
                                    </div>
                                    <div class="sms-notifications-notice-text">
                                        <ul>
                                            <li>
                                                متن نمونه الگو (به کاربر):
                                                <div class="sms-notifications-notice-text-message">
                                                    name عزیز
                                                    <br>
                                                    ثبت نام شما با موفقیت انجام شد.
                                                    <br>
                                                    به جمع کاربران ما خوش آمدید.
                                                    <br>
                                                    آدرس سایت شما
                                                </div>
                                            </li>
                                            <li>
                                                متن نمونه الگو (به مدیریت):
                                                <div class="sms-notifications-notice-text-message">
                                                    کاربر جدیدی در سایت با نام name و نام کاربری username ثبت نام کرد.
                                                    <br>
                                                    آدرس سایت شما
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <table class="form-table">
                                    <tr>
                                        <th><label>پیامک بعد از ورود کاربر (به مدیریت)</label></th>
                                        <td><input type="checkbox" class="v-toggle" name="voorodak_options[sms_login_admin]" value="1" <?php checked($sms_login_admin, '1'); ?> /></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            کد الگو (پترن)
                                        </th>
                                        <td><input type="text" name="voorodak_options[sms_login_admin_pattern]"
                                                   value="<?php echo esc_attr($sms_login_admin_pattern); ?>"/></td>
                                    </tr>

                                    <tr>
                                        <th>
                                            انتخاب متغیرها
                                        </th>
                                        <td>
                                            <?php $sms_login_admin_variables = $settings['sms_login_admin_variables'] ?? 'name-username-userid'; ?>
                                            <div class="multi-select-builder" data-name="voorodak_options[sms_login_admin_variables]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($sms_user_event_variables_options); ?>" data-default="<?php echo esc_attr($sms_login_admin_variables); ?>"></div>

                                        </td>
                                    </tr>


                                    <tr>
                                        <th><label>پیامک بعد از ورود کاربر نقش مدیر (به مدیریت)</label></th>
                                        <td><input type="checkbox" class="v-toggle" name="voorodak_options[sms_login_roleadmin_admin]" value="1" <?php checked($sms_login_roleadmin_admin, '1'); ?> /></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            کد الگو (پترن)
                                        </th>
                                        <td><input type="text" name="voorodak_options[sms_login_roleadmin_admin_pattern]"
                                                   value="<?php echo esc_attr($sms_login_roleadmin_admin_pattern); ?>"/></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            انتخاب متغیرها
                                        </th>
                                        <td>
                                            <?php $sms_login_roleadmin_admin_variables = $settings['sms_login_roleadmin_admin_variables'] ?? 'name-username-userid'; ?>
                                            <div class="multi-select-builder" data-name="voorodak_options[sms_login_roleadmin_admin_variables]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($sms_user_event_variables_options); ?>" data-default="<?php echo esc_attr($sms_login_roleadmin_admin_variables); ?>"></div>

                                        </td>
                                    </tr>

                                    <tr>
                                        <th><label>پیامک بعد از ثبت نام کاربر (به مدیریت)</label></th>
                                        <td><input type="checkbox" class="v-toggle" name="voorodak_options[sms_register_admin]" value="1" <?php checked($sms_register_admin, '1'); ?> /></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            کد الگو (پترن)
                                        </th>
                                        <td><input type="text" name="voorodak_options[sms_register_admin_pattern]"
                                                   value="<?php echo esc_attr($sms_register_admin_pattern); ?>"/></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            انتخاب متغیرها
                                        </th>
                                        <td>
                                            <?php $sms_register_admin_variables = $settings['sms_register_admin_variables'] ?? 'name-username-userid'; ?>
                                            <div class="multi-select-builder" data-name="voorodak_options[sms_register_admin_variables]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($sms_user_event_variables_options); ?>" data-default="<?php echo esc_attr($sms_register_admin_variables); ?>"></div>

                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>پیامک بعد از ورود کاربر (به کاربر)</label></th>
                                        <td><input type="checkbox" class="v-toggle" name="voorodak_options[sms_login]" value="1" <?php checked($sms_login, '1'); ?> /></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            کد الگو (پترن)
                                        </th>
                                        <td><input type="text" name="voorodak_options[sms_login_pattern]"
                                                   value="<?php echo esc_attr($sms_login_pattern); ?>"/></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            انتخاب متغیرها
                                        </th>
                                        <td>
                                            <?php $sms_login_pattern_variables = $settings['sms_login_pattern_variables'] ?? 'name-username-userid'; ?>
                                            <div class="multi-select-builder" data-name="voorodak_options[sms_login_pattern_variables]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($sms_user_event_variables_options); ?>" data-default="<?php echo esc_attr($sms_login_pattern_variables); ?>"></div>

                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>پیامک بعد از ثبت نام کاربر (به کاربر)</label></th>
                                        <td><input type="checkbox" class="v-toggle" name="voorodak_options[sms_register]" value="1" <?php checked($sms_register, '1'); ?> /></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            کد الگو (پترن)
                                        </th>
                                        <td><input type="text" name="voorodak_options[sms_register_pattern]"
                                                   value="<?php echo esc_attr($sms_register_pattern); ?>"/></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            انتخاب متغیرها
                                        </th>
                                        <td>
                                            <?php $sms_register_variables = $settings['sms_register_variables'] ?? 'name-username-userid'; ?>
                                            <div class="multi-select-builder" data-name="voorodak_options[sms_register_variables]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($sms_user_event_variables_options); ?>" data-default="<?php echo esc_attr($sms_register_variables); ?>"></div>

                                        </td>
                                    </tr>
                                </table>
                                <h4>پیامک های دیدگاه ها</h4>
                                <div class="sms-notifications-notice">
                                    <div  class="sms-notifications-notice-icon">
                                        <svg width="36" height="36" data-slot="icon" aria-hidden="true" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                        راهنما
                                    </div>
                                    <div class="sms-notifications-notice-text">
                                        <ul>
                                            <li>
                                                متن نمونه الگو:
                                                <div class="sms-notifications-notice-text-message">
                                                    سلام!
                                                    <br>
                                                    به کامنت شما در مطلب title پاسخ داده شد.
                                                    <br>
                                                    آدرس سایت شما
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <table class="form-table">
                                    <tr>
                                        <th><label>پیامک بعد از ثبت دیدگاه جدید (به مدیریت)</label></th>
                                        <td><input type="checkbox" class="v-toggle" name="voorodak_options[sms_comment_new_admin]" value="1" <?php checked($sms_comment_new_admin, '1'); ?> /></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            کد الگو (پترن)
                                        </th>
                                        <td><input type="text" name="voorodak_options[sms_comment_new_admin_pattern]"
                                                   value="<?php echo esc_attr($sms_comment_new_admin_pattern); ?>"/></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            انتخاب متغیرها
                                        </th>
                                        <td>
                                            <?php $sms_comment_new_admin_variables = $settings['sms_comment_new_admin_variables'] ?? 'title-postlink'; ?>
                                            <div class="multi-select-builder" data-name="voorodak_options[sms_comment_new_admin_variables]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($sms_comment_variables_options); ?>" data-default="<?php echo esc_attr($sms_comment_new_admin_variables); ?>"></div>

                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label>پیامک بعد از پاسخ به دیدگاه کاربر (به کاربر)</label></th>
                                        <td><input type="checkbox" class="v-toggle" name="voorodak_options[sms_comment_reply_user]" value="1" <?php checked($sms_comment_reply_user ?? '1', '1'); ?> /></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            کد الگو (پترن)
                                        </th>
                                        <td><input type="text" name="voorodak_options[sms_comment_reply_user_pattern]"
                                                   value="<?php echo esc_attr($sms_comment_reply_user_pattern); ?>"/></td>
                                    </tr>
                                    <tr>
                                        <th>
                                            انتخاب متغیرها
                                        </th>
                                        <td>
                                            <?php $sms_comment_reply_user_variables = $settings['sms_comment_reply_user_variables'] ?? 'title-postlink'; ?>
                                            <div class="multi-select-builder" data-name="voorodak_options[sms_comment_reply_user_variables]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($sms_comment_variables_options); ?>" data-default="<?php echo esc_attr($sms_comment_reply_user_variables); ?>"></div>

                                        </td>
                                    </tr>
                                </table>
                                <?php if (function_exists('is_woocommerce')): ?>
                                    <h4>پیامک های سفارشات ووکامرس</h4>
                                    <div class="sms-notifications-notice">
                                        <div  class="sms-notifications-notice-icon">
                                            <svg width="36" height="36" data-slot="icon" aria-hidden="true" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" stroke-linecap="round" stroke-linejoin="round"></path>
                                            </svg>
                                            راهنما
                                        </div>
                                        <div class="sms-notifications-notice-text">
                                            <ul>
                                                <li>
                                                    متن نمونه الگو (به کاربر):
                                                    <div class="sms-notifications-notice-text-message">
                                                        name عزیز
                                                        <br>
                                                        سفارش شما با شناسه orderid و با مبلغ total با موفقیت ثبت شد و در وضعیت تکمیل شده قرار گرفت.
                                                        <br>
                                                        آدرس سایت شما
                                                    </div>
                                                </li>

                                                <li>
                                                    متن نمونه الگو (به مدیریت):
                                                    <div class="sms-notifications-notice-text-message">
                                                        سفارش جدیدی به نام name و با شناسه orderid در سایت ثبت شد.
                                                        <br>
                                                        آدرس سایت شما
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <table class="form-table">
                                        <?php
                                        $order_statuses = wc_get_order_statuses();
                                        $option_variables_wc = [
                                            'name'          => 'نام کامل مشتری',
                                            'firstname'     => 'نام',
                                            'lastname'      => 'نام خانوادگی',
                                            'orderid'       => 'شناسه عددی سفارش',
                                            'total'         => 'مبلغ سفارش',
                                            'tracking'      => 'کد رهگیری',
                                            'count'         => 'تعداد محصولات',
                                            'shippingtotal' => 'هزینه ارسال',
                                            'discounttotal' => 'مقدار تخفیف',
                                            'note'          => 'یادداشت مشتری',
                                            'date'          => 'تاریخ سفارش (با ساعت)',
                                            'dateonly'      => 'تاریخ سفارش',
                                        ];

                                        $option_variables_wc = implode(
                                            ',',
                                            array_map(
                                                function ($key, $value) {
                                                    return "{$key}:{$value}";
                                                },
                                                array_keys($option_variables_wc),
                                                $option_variables_wc
                                            )
                                        );
                                        foreach ($order_statuses as $status_key => $status_name):
                                            if ($status_key === 'wc-checkout-draft') {
                                                continue;
                                            }
                                            $option_key_admin = "sms_order_{$status_key}_admin";
                                            $option_key_admin_pattern = "sms_order_{$status_key}_admin_pattern";
                                            $option_key_admin_variables = "sms_order_{$status_key}_admin_variables";
                                            $option_key_user = "sms_order_{$status_key}_user";
                                            $option_key_user_pattern = "sms_order_{$status_key}_user_pattern";
                                            $option_key_user_variables = "sms_order_{$status_key}_user_variables";
                                            $checked_admin = $settings[$option_key_admin] ?? '';
                                            $value_admin_pattern = $settings[$option_key_admin_pattern] ?? '';
                                            $value_admin_variables = $settings[$option_key_admin_variables] ?? 'name-orderid-total-tracking';
                                            $checked_user = $settings[$option_key_user] ?? '';
                                            $value_user_pattern = $settings[$option_key_user_pattern] ?? '';
                                            $value_user_variables = $settings[$option_key_user_variables] ?? 'name-orderid-total-tracking';

                                            ?>
                                            <tr>
                                                <th><label>پیامک وضعیت سفارش "<?php echo esc_html($status_name); ?>" (به مدیریت)</label></th>
                                                <td><input type="checkbox" class="v-toggle" name="voorodak_options[<?php echo esc_attr($option_key_admin); ?>]" value="1" <?php checked($checked_admin, '1'); ?> /></td>
                                            </tr>
                                            <tr>
                                                <th>
                                                    کد الگو (پترن)
                                                </th>
                                                <td><input type="text" name="voorodak_options[<?php echo esc_attr($option_key_admin_pattern); ?>]"
                                                           value="<?php echo esc_attr($value_admin_pattern); ?>"/></td>
                                            </tr>
                                            <tr>
                                                <th>
                                                    انتخاب متغیرها
                                                </th>
                                                <td>
                                                    <div class="multi-select-builder" data-name="voorodak_options[<?php echo esc_attr($option_key_admin_variables); ?>]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($option_variables_wc); ?>" data-default="<?php echo esc_attr($value_admin_variables); ?>"></div>

                                                </td>
                                            </tr>
                                            <tr>
                                                <th><label>پیامک وضعیت سفارش "<?php echo esc_html($status_name); ?>" (به کاربر)</label></th>
                                                <td><input type="checkbox" class="v-toggle" name="voorodak_options[<?php echo esc_attr($option_key_user); ?>]" value="1" <?php checked($checked_user, '1'); ?> /></td>
                                            </tr>
                                            <tr>
                                                <th>
                                                    کد الگو (پترن)
                                                </th>
                                                <td><input type="text" name="voorodak_options[<?php echo esc_attr($option_key_user_pattern); ?>]"
                                                           value="<?php echo esc_attr($value_user_pattern); ?>"/></td>
                                            </tr>
                                            <tr>
                                                <th>
                                                    انتخاب متغیرها
                                                </th>
                                                <td>
                                                    <div class="multi-select-builder" data-name="voorodak_options[<?php echo esc_attr($option_key_user_variables); ?>]" data-placeholder="انتخاب کنید" data-options="<?php echo esc_attr($option_variables_wc); ?>" data-default="<?php echo esc_attr($value_user_variables); ?>"></div>

                                                </td>
                                            </tr>
                                        <?php endforeach;
                                        ?>
                                    </table>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="sms-notifications-notice">
                                    برای ارسال سایر پیامک ها نیاز هست که از یک سامانه از نوع <b style="display: inline-block;margin: 0 3px;">Pattern</b> استفاده نمایید
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <div id="performance" class="voorodak__body-main-box" role="tabpanel" aria-labelledby="voorodak-tab-performance" tabindex="0" style="display: none">
                    <table class="form-table">
                        <tr>
                            <th>صفحه ورود / ثبت نام</th>
                            <td>
                                <?php
                                $pages = get_pages();
                                $exclude_ids = [];
                                if (class_exists('WooCommerce')) {
                                    $wc_pages = ['myaccount', 'cart', 'checkout'];
                                    foreach ($wc_pages as $slug) {
                                        $page_id = wc_get_page_id($slug);
                                        if ($page_id > 0) {
                                            $exclude_ids[] = $page_id;
                                        }
                                    }
                                }
                                ?>
                                <select name="voorodak_options[login_page_id]">
                                    <?php foreach ($pages as $page) : ?>
                                        <?php if (!in_array($page->ID, $exclude_ids)) : ?>
                                            <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($login_page_id, $page->ID); ?>>
                                                <?php echo esc_html($page->post_title); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </td>

                        </tr>
                        <tr class="voorodak__backurl">
                            <th>صفحه بعد از لاگین کاربر</th>
                            <td>
                                <label><input type="radio" name="voorodak_options[backurl]" value="prev" <?php checked($backurl, 'prev'); ?> />صفحه قبلی</label>
                                <label><input type="radio" name="voorodak_options[backurl]" value="home" <?php checked($backurl, 'home'); ?> />صفحه اصلی</label>
                                <label><input type="radio" name="voorodak_options[backurl]" value="custom" <?php checked($backurl, 'custom'); ?> />صفحه دلخواه</label>
                            </td>
                        </tr>
                        <tr class="voorodak__backurl-custom">
                            <th>لینک صفحه دلخواه</th>
                            <td><input placeholder="https:// لینک به صورت " type="text" name="voorodak_options[backurl_custom]"
                                       value="<?php echo esc_attr($backurl_custom); ?>"/></td>
                        </tr>
                        <tr class="voorodak__logouturl">
                            <th>لینک صفحه بعد از خروج</th>
                            <td><input placeholder="https:// لینک به صورت " type="text" name="voorodak_options[logouturl]"
                                       value="<?php echo esc_attr($logouturl); ?>"/></td>
                        </tr>
                        <tr class="voorodak__panelurl-custom">
                            <th>لینک پنل کاربری اختصاصی
                            <span class="hint">اگر پنل کاربری اختصاصی دارید، لینک آن را در این بخش وارد کنید تا صفحه ورود ثبت نام بعد از لاگین به این صفحه تغییر کند</span>
                            </th>
                            <td><input placeholder="https:// لینک به صورت " type="text" name="voorodak_options[panelurl_custom]"
                                       value="<?php echo esc_attr($panelurl_custom); ?>"/></td>
                        </tr>
                        <tr>
                            <th>فرمت ذخیره نام کاربری</th>
                            <td>
                                <select name="voorodak_options[username_format]">
                                    <option value="with-zero" <?php selected($username_format, 'with-zero'); ?>>با صفر اول (مثلا 09191234567)</option>
                                    <option value="without-zero" <?php selected($username_format, 'without-zero'); ?>>بدون صفر اول (مثلا 9191234567)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>فرمت ورود کاربران</th>
                            <td>
                                <select name="voorodak_options[login_type]">
                                    <option value="mobile" <?php selected($login_type, 'mobile'); ?>>فقط با موبایل</option>
                                    <option value="mobile-email" <?php selected($login_type, 'mobile-email'); ?>>با موبایل و ایمیل</option>
                                    <option value="mobile-email-username" <?php selected($login_type, 'mobile-email-username'); ?>>با موبایل و ایمیل و نام کاربری</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>نقش کاربر بعد از ثبت‌نام
                                <span class="hint">
                                در انتخاب این گزینه بسیار دقت کنید! به صورت پیشفرض روی مشترک و مشتری (درصورت نصب ووکامرس) انتخاب میشود
                            </span>
                            </th>
                            <td>
                                <?php
                                $roles = voorodak_get_registration_roles();
                                $saved_role = voorodak_normalize_registration_role(
                                    $settings['default_role'] ?? voorodak_get_default_registration_role()
                                );
                                ?>
                                <select name="voorodak_options[default_role]">
                                    <?php foreach ($roles as $role_key => $role_data) : ?>
                                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected($saved_role, $role_key); ?>>
                                            <?php echo esc_html(translate_user_role($role_data['name'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">نقش‌های دارای دسترسی مدیریتی برای ثبت‌نام عمومی نمایش داده نمی‌شوند.</p>
                            </td>
                        </tr>

                        <tr>
                            <th>طول کد یکبار مصرف</th>
                            <td>
                                <select name="voorodak_options[otp_length]">
                                    <option value="4" <?php selected($otp_length, '4'); ?>>4 رقم</option>
                                    <option value="5" <?php selected($otp_length, '5'); ?>>5 رقم</option>
                                    <option value="6" <?php selected($otp_length, '6'); ?>>6 رقم (پیشنهادی)</option>
                                    <option value="7" <?php selected($otp_length, '7'); ?>>7 رقم</option>
                                    <option value="8" <?php selected($otp_length, '8'); ?>>8 رقم</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>حداقل طول رمز عبور مجاز
                                <span class="hint">
                                به جهت امنیت بیشتر توصیه میکنیم زیر 8 حرف قرار ندهید
                            </span>
                            </th>
                            <td>
                                <select name="voorodak_options[password_length]">
                                    <option value="4" <?php selected($password_length, '4'); ?>>4 حرف</option>
                                    <option value="6" <?php selected($password_length, '6'); ?>>6 حرف</option>
                                    <option value="8" <?php selected($password_length, '8'); ?>>8 حرف (پیشنهادی)</option>
                                    <option value="10" <?php selected($password_length, '10'); ?>>10 حرف</option>
                                    <option value="12" <?php selected($password_length, '12'); ?>>12 حرف</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__autofill">قابلیت اتوفیل AutoFill</label></th>
                            <td><input type="checkbox" id="voorodak__autofill" name="voorodak_options[autofill]" value="1" <?php checked($autofill, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__digits">هماهنگی با کاربران قبلی</label></th>
                            <td><input type="checkbox" class="v-toggle" id="voorodak__digits" name="voorodak_options[digits]" value="1" <?php checked($digits, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th>
                                <label>کلید متا کاربران قبلی</label>
                            </th>
                            <td><input type="text" name="voorodak_options[digits_meta]" value="<?php echo esc_attr($digits_meta); ?>"/></td>
                        </tr>
                        <tr class="ignore-second">
                            <th><label for="voorodak__password-login-priority">اولویت ورود با رمز عبور</label>
                                <span class="hint">اگر فعال باشد، برای کاربران موجود ابتدا فرم رمز عبور نمایش داده می‌شود و لینک ورود با کد یکبار مصرف زیر آن قرار می‌گیرد.</span>
                            </th>
                            <td><input type="checkbox" id="voorodak__password-login-priority" name="voorodak_options[password_login_priority]" value="1" <?php checked($password_login_priority, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__remember-me">ماندگاری ورود کاربر</label>
                                <span class="hint">در حالت فعال، کوکی ورود با حالت «مرا به خاطر بسپار» وردپرس ساخته می‌شود. برای حفظ رفتار نسخه‌های قبلی به‌صورت پیش‌فرض فعال است.</span>
                            </th>
                            <td><input type="checkbox" id="voorodak__remember-me" name="voorodak_options[remember_me]" value="1" <?php checked($remember_me, '1'); ?> /></td>
                        </tr>
                        <tr class="voorodak__settings-heading-row ignore-second">
                            <td colspan="2">
                                <h3>تنظیمات ثبت نام</h3>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__register-allow">جلوگیری از ثبت نام کاربران</label></th>
                            <td><input type="checkbox" id="voorodak__register-allow" name="voorodak_options[register_allow]" value="1" <?php checked($register_allow, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__family-name">فیلد نام و نام خانوادگی در ثبت نام</label></th>
                            <td><input type="checkbox" id="voorodak__family-name" name="voorodak_options[family_name]" value="1" <?php checked($family_name, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__family-name-force">الزامی بودن نام و نام خانوادگی</label></th>
                            <td><input type="checkbox" id="voorodak__family-name-force" name="voorodak_options[family_name_force]" value="1" <?php checked($family_name_force, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__family-name-persian-only">فقط حروف فارسی برای نام و نام خانوادگی</label>
                                <span class="hint">با فعال کردن این گزینه، ورود حروف و اعداد انگلیسی در فیلدهای نام و نام خانوادگی مجاز نیست.</span>
                            </th>
                            <td><input type="checkbox" id="voorodak__family-name-persian-only" name="voorodak_options[family_name_persian_only]" value="1" <?php checked($family_name_persian_only, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__national-code-field">فیلد کد ملی در ثبت نام</label></th>
                            <td><input type="checkbox" id="voorodak__national-code-field" name="voorodak_options[national_code_field]" value="1" <?php checked($national_code_field, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__national-code-force">الزامی بودن کد ملی</label></th>
                            <td><input type="checkbox" id="voorodak__national-code-force" name="voorodak_options[national_code_force]" value="1" <?php checked($national_code_force, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__email-field">فیلد ایمیل در ثبت نام</label></th>
                            <td><input type="checkbox" id="voorodak__email-field" name="voorodak_options[email_field]" value="1" <?php checked($email_field, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__email-field-force">الزامی بودن ایمیل</label></th>
                            <td><input type="checkbox" id="voorodak__email-field-force" name="voorodak_options[email_field_force]" value="1" <?php checked($email_field_force, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__email-register">ثبت نام با ایمیل</label>
                                <span class="hint">اگر فرمت ورود شامل ایمیل باشد، کاربر جدید می‌تواند با ایمیل کد تایید دریافت کند و ثبت نام شود.</span>
                            </th>
                            <td><input type="checkbox" id="voorodak__email-register" name="voorodak_options[email_register]" value="1" <?php checked($email_register, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__disable-random-email">غیرفعال کردن ساخت ایمیل تصادفی</label>
                                <span class="hint">اگر فعال باشد، برای ثبت نام بدون ایمیل مقدار ایمیل خالی می‌ماند.</span>
                            </th>
                            <td><input type="checkbox" id="voorodak__disable-random-email" name="voorodak_options[disable_random_email]" value="1" <?php checked($disable_random_email, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__password-field">فیلد رمز عبور در ثبت نام</label><span class="hint">این فیلد در صورت فعال شدن پیشفرض الزامی خواهد بود</span></th>
                            <td><input type="checkbox" id="voorodak__password-field" name="voorodak_options[password_field]" value="1" <?php checked($password_field, '1'); ?> /></td>
                        </tr>
                        <tr class="ignore-second">
                            <td colspan="2" class="voorodak__settings-heading-row">
                                <h3>ووکامرس</h3>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__woocommerce-login">تغییر صفحه ورود/ثبت نام ووکامرس</label></th>
                            <td><input type="checkbox" id="voorodak__woocommerce-login" name="voorodak_options[woocommerce_login]" value="1" <?php checked($woocommerce_login, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__woocommerce-checkout">کاربر در صفحه پرداخت، اول لاگین کند</label></th>
                            <td><input type="checkbox" id="voorodak__woocommerce-checkout" name="voorodak_options[woocommerce_checkout]" value="1" <?php checked($woocommerce_checkout, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th>فیلد سفارشی ذخیره موبایل کاربر
                            <span class="hint">
                                مقدار پیشفرض ووکامرس: billing_phone
                            </span>
                            </th>
                            <td><input type="text" name="voorodak_options[user_field_meta]"
                                       value="<?php echo esc_attr($user_field_meta); ?>"/></td>
                        </tr>
                        <tr>
                            <th>فیلد سفارشی ذخیره موبایل کاربر 2
                                <span class="hint">
                                ذخیره در فیلد سفارشی جدا و اختصاصی دیگر
                            </span>
                            </th>
                            <td><input type="text" name="voorodak_options[user_field_meta2]"
                                       value="<?php echo esc_attr($user_field_meta2); ?>"/></td>
                        </tr>
                    </table>
                </div>
                <div id="bale" class="voorodak__body-main-box" role="tabpanel" aria-labelledby="voorodak-tab-bale" tabindex="0" style="display: none">
                    <p>در این قسمت میتوانید تنظیم کنید که کد یکبار مصرف در اپ بله برای کاربران ارسال شود یا پیام های اطلاع رسانی سایت برای مدیر فرستاده شود</p>
                    <table class="form-table">

                        <tr>
                            <th>
                                <h3>ارسال رمز یکبار مصرف</h3>
                            </th>
                        </tr>
                        <tr>
                            <th><label for="voorodak__baleotp">ارسال کد یکبار مصرف در بله</label></th>
                            <td><input type="checkbox" id="voorodak__baleotp" name="voorodak_options[baleotp]" value="1" <?php checked($baleotp, '1'); ?> /></td>
                        </tr>

                        <tr>
                            <th>Bale Access Key (الزامی)
                                <span class="hint">
                                از سایت business.bale.ai و بخش وب سرویس، میتوانید توکن را دریافت نمایید
                            </span>
                            </th>
                            <td><input type="text" name="voorodak_options[baleapi]" placeholder="مثلا: L37O5XKcKKhtR0Ya"
                                       value="<?php echo esc_attr($baleapi); ?>"/></td>
                        </tr>
                        <tr>
                            <th>Bale Bot ID (الزامی)
                                <span class="hint">
                                از سایت business.bale.ai و بخش وب سرویس، میتوانید بات آیدی را دریافت نمایید
                            </span>
                            </th>
                            <td><input type="text" name="voorodak_options[balebotid]" placeholder="مثلا: 251071113"
                                       value="<?php echo esc_attr($balebotid); ?>"/></td>
                        </tr>

                        <tr>
                            <th>
                                <h3>اطلاع رسانی ها به مدیر</h3>
                            </th>
                        </tr>
                        <tr>
                            <th>Bale Bot Token (الزامی)
                                <span class="hint">
                                از آیدی @botfather در بله یک بات بسازید و توکن آن را در این فیلد قرار دهید
                            </span>
                            </th>
                            <td><input type="text" name="voorodak_options[balebottoken]" placeholder="مثلا: 251071113:Vk73ET1uiKXGonppVpNy..."
                                       value="<?php echo esc_attr($balebottoken); ?>"/></td>
                        </tr>
                        <tr>
                            <th>شناسه گفتگو (Chat ID)
                                <span class="hint">جهت اطلاع رسانی ها به حساب مدیریت</span>

                            </th>
                            <td>
                                <textarea rows="5" placeholder="در هر خط میتوانید یک Chat ID مدیر وارد نمایید" name="voorodak_options[balechatids]"><?php echo esc_textarea($balechatids); ?></textarea>
                                <span class="hint">

                                    1- برای پیدا کردن چت آیدی یا شناسه گفتگو، وارد آدرس `web.bale.ai` شوید
                                    <br>
                                    2- چتِ شخص موردنظر را باز کنید.
                                    <br>
                                    3- به آدرس بالای مرورگر نگاه کنید؛ چیزی شبیه به `chat?uid=2005104022` می‌بینید.
                                    <br>
                                    4- عدد جلوی آن (در اینجا `2005104022`) همان `Chat ID` است.
                                    <br>
                                    نکته: اگر می‌خواهید پیام به حساب خودتان ارسال شود، وارد بخش `Saved Messages` شوید و عدد انتهای آدرس را از آنجا بردارید.
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__bale_comment_new">اطلاع رسانی مدیر بعد از ثبت کامنت جدید در بله</label></th>
                            <td><input type="checkbox" id="voorodak__bale_comment_new" name="voorodak_options[bale_comment_new]" value="1" <?php checked($bale_comment_new, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__bale_user_login">اطلاع رسانی مدیر بعد از ورود کاربران به سایت</label></th>
                            <td><input type="checkbox" id="voorodak__bale_user_login" name="voorodak_options[bale_user_login]" value="1" <?php checked($bale_user_login, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__bale_user_login_admin">اطلاع رسانی مدیر بعد از ورود مدیر به سایت</label></th>
                            <td><input type="checkbox" id="voorodak__bale_user_login_admin" name="voorodak_options[bale_user_login_admin]" value="1" <?php checked($bale_user_login_admin, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__bale_user_register">اطلاع رسانی مدیر بعد از ثبت نام کاربران در سایت</label></th>
                            <td><input type="checkbox" id="voorodak__bale_user_register" name="voorodak_options[bale_user_register]" value="1" <?php checked($bale_user_register, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__bale_order_completed">اطلاع رسانی مدیر بعد از ثبت سفارش موفق در سایت</label></th>
                            <td><input type="checkbox" id="voorodak__bale_order_completed" name="voorodak_options[bale_order_completed]" value="1" <?php checked($bale_order_completed, '1'); ?> /></td>
                        </tr>
                    </table>
                </div>

                <div id="display" class="voorodak__body-main-box" role="tabpanel" aria-labelledby="voorodak-tab-display" tabindex="0" style="display: none">
                    <table class="form-table">
                        <tr>
                            <th>قالب نمایش</th>
                            <td>
                                <select name="voorodak_options[template]">
                                    <option value="default" <?php selected($template, 'default'); ?>>پیشفرض</option>
                                    <option value="digikala" <?php selected($template, 'digikala'); ?>>دیجی کالا</option>
                                    <option value="zarinpal" <?php selected($template, 'zarinpal'); ?>>زرین پال</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>لوگو</th>
                            <td>
                                <input type="hidden" name="voorodak_options[logo]" id="voorodak__logo"
                                       value="<?php echo esc_url($logo); ?>"/>
                                <input type="button" id="voorodak__logo-upload-button" class="button" value="آپلود تصویر"/>
                                <input type="button" id="voorodak__logo-upload-remove" class="button"
                                       value="حذف تصویر" <?php echo empty($logo) ? 'style="display:none;"' : ''; ?> />
                                <div id="voorodak__logo-preview">
                                    <?php if ($logo) : ?>
                                        <img src="<?php echo esc_url($logo); ?>" style="max-width: 200px; max-height: 200px;"/>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>تصویر کنار فرم (برای قالب زرین پال)</th>
                            <td>
                                <input type="hidden" name="voorodak_options[cover]" id="voorodak__cover"
                                       value="<?php echo esc_url($cover); ?>"/>
                                <input type="button" id="voorodak__cover-upload-button" class="button" value="آپلود تصویر"/>
                                <input type="button" id="voorodak__cover-upload-remove" class="button"
                                       value="حذف تصویر" <?php echo empty($cover) ? 'style="display:none;"' : ''; ?> />
                                <div id="voorodak__cover-preview">
                                    <?php if ($cover) : ?>
                                        <img src="<?php echo esc_url($cover); ?>" style="max-width: 200px; max-height: 200px;"/>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>رنگ پس زمینه</th>
                            <td><input type="text" class="voorodak__color-picker" data-default-color="#ffffff"
                                       name="voorodak_options[bg_color]"
                                       value="<?php echo esc_attr($bg_color); ?>"/></td>
                        </tr>
                        <tr>
                            <th>رنگ دکمه</th>
                            <td><input type="text" class="voorodak__color-picker" data-default-color="#5498fa"
                                       name="voorodak_options[button_color]"
                                       value="<?php echo esc_attr($button_color); ?>"/></td>
                        </tr>
                        <tr>
                            <th>رنگ دکمه (هنگام هاور)</th>
                            <td><input type="text" class="voorodak__color-picker" data-default-color="#2c61a6"
                                       name="voorodak_options[button_color_hover]"
                                       value="<?php echo esc_attr($button_color_hover); ?>"/></td>
                        </tr>
                        <tr>
                            <th>متن اولیه فرم</th>
                            <td><input type="text" name="voorodak_options[form_name]"
                                       value="<?php echo esc_attr($form_name); ?>"/></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__otp-boxed">نمایش کد تایید به صورت خانه‌ای</label>
                                <span class="hint">با فعال کردن این گزینه، فیلد کد تایید به تعداد رقم‌های تنظیم‌شده در بخش عملکرد نمایش داده می‌شود.</span>
                            </th>
                            <td><input type="checkbox" id="voorodak__otp-boxed" name="voorodak_options[otp_boxed]" value="1" <?php checked($otp_boxed, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__btnback">دکمه بازگشت</label></th>
                            <td><input type="checkbox" id="voorodak__btnback" name="voorodak_options[btnback]" value="1" <?php checked($btnback, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th><label for="voorodak__btnback_prev">بازگشت به صفحه قبل</label>
                                <span class="hint">با فعال کردن این گزینه، کاربر با کلیک روی دکمه، به صفحه قبل بازمیگردد، اگر فعال نکنید به صفحه اصلی بازمیگردد</span>


                            </th>
                            <td><input type="checkbox" id="voorodak__btnback_prev" name="voorodak_options[btnbackprev]" value="1" <?php checked($btnbackprev, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th>
                                متن پذیرش قوانین و مقررات
                                <span class="hint">در صورت خالی بودن نمایش داده نخواهد شد</span>
                            </th>
                            <td>
                                <?php
                                wp_editor(
                                    $term_editor,
                                    'term_editor',
                                    array(
                                        'textarea_name' => 'voorodak_options[term_editor]',
                                        'media_buttons' => false,
                                        'teeny'         => true,
                                        'quicktags'     => false,
                                        'tinymce'       => array(
                                            'toolbar1' => 'bold,italic,underline,forecolor,link,unlink',
                                            'toolbar2' => '',
                                            'plugins'  => 'textcolor link'
                                        )
                                    )
                                );

                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div id="advance" class="voorodak__body-main-box" role="tabpanel" aria-labelledby="voorodak-tab-advance" tabindex="0" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th>نمایش تاریخ عضویت کاربران
                                <span class="hint">با فعال کردن این گزینه، تاریخ عضویت کاربران در صفحه لیست کاربران نمایش داده میشود.</span>
                            </th>
                            <td><input type="checkbox" name="voorodak_options[date_register]" value="1" <?php checked($date_register, '1'); ?> /></td>
                        </tr>
                        <tr>
                            <th>خروجی CSV کاربران</th>
                            <td><span id="download_list_users" class="button">دانلود لیست کاربران</span></td>
                        </tr>
                    </table>
                </div>
                <div id="security" class="voorodak__body-main-box" role="tabpanel" aria-labelledby="voorodak-tab-security" tabindex="0" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th><label for="voorodak__disable-admin-login">بستن ورود نقش های ادمین</label>
                                <span class="hint">با فعال کردن این گزینه، از ورود ادمین ها با دسترسی مدیر کل توسط فرم ورودک جلوگیری میشود و فقط از طریق صفحه ورود وردپرس امکان ورود وجود دارد، برای تدابیر امنیتی بالا پیشنهاد میشود.</span>

                            </th>
                            <td><input type="checkbox" id="voorodak__disable-admin-login" name="voorodak_options[disable_admin_login]" value="1" <?php checked($disable_admin_login, '1'); ?> /></td>
                        </tr>

                        <tr>
                            <th><label for="voorodak__captcha-enabled">کپچای فرم ورود</label>
                                <span class="hint">با فعال کردن این گزینه، قبل از ارسال کد یکبار مصرف یک سوال امنیتی ساده بررسی می‌شود.</span>
                            </th>
                            <td><input type="checkbox" id="voorodak__captcha-enabled" name="voorodak_options[captcha_enabled]" value="1" <?php checked($captcha_enabled, '1'); ?> /></td>
                        </tr>

                        <tr>
                            <th>
                                زمان Timeout درخواست ها
                            </th>
                            <td>
                                <?php
                                $timeouts = range(5, 60, 5);
                                ?>
                                <select name="voorodak_options[gateway_timeout]">
                                    <?php foreach ($timeouts as $val): ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php selected($gateway_timeout, $val); ?>>
                                            <?php echo esc_html($val); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                تعداد درخواست مجاز
                                <span class="hint">حداکثر درخواست قبل از بلاک، 10 مقدار پیشنهادی میباشد</span>
                            </th>
                            <td>
                                <select name="voorodak_options[max_requests]">
                                    <?php
                                    $max_requests = $settings['max_requests'] ?? 10;
                                    foreach ([5,10,15,20,25,30] as $val) {
                                        echo '<option value="'.$val.'" '.selected($max_requests, $val, false).'>'.$val.'</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                مدت زمان بلاک (دقیقه)
                                <span class="hint">زمان مسدودسازی، 10 مقدار پیشنهادی میباشد</span>
                            </th>
                            <td>
                                <select name="voorodak_options[block_minutes]">
                                    <?php
                                    $block_minutes = $settings['block_minutes'] ?? 10;
                                    foreach ([5,10,15,20,25,30] as $val) {
                                        echo '<option value="'.$val.'" '.selected($block_minutes, $val, false).'>'.$val.' دقیقه</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th>بلک لیست موبایل
                                <span class="hint">مسدودسازی ارسال پیامک و ورود ثبت نام (موبایل یا ایمیل یا نام کاربری را وارد نمایید)</span>
                            </th>
                            <td>
                                <textarea rows="5" placeholder="در هر خط میتوانید یک شماره موبایل یا ایمیل یا نام کاربری وارد نمایید" name="voorodak_options[block_mobile]"><?php echo esc_textarea($block_mobile); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th>بلک لیست آیپی
                                <span class="hint">مسدودسازی آیپی</span>
                            </th>
                            <td>
                                <textarea rows="5" placeholder="در هر خط میتوانید یک آیپی وارد نمایید" name="voorodak_options[block_ip]"><?php echo esc_textarea($block_ip); ?></textarea>
                            </td>
                        </tr>
                    </table>
                </div>
                <div id="develop" class="voorodak__body-main-box" role="tabpanel" aria-labelledby="voorodak-tab-develop" tabindex="0" style="display: none;">
                    <table class="form-table">
                        <tr>
                            <th>بارگذاری سراسری فایل‌های فرم
                                <span class="hint">در حالت عادی، افزونه وجود شورتکد [voorodak] را در محتوای برگه تشخیص می‌دهد. فقط اگر صفحه‌ساز شما شورتکد را در متادیتا نگه می‌دارد و فرم بدون استایل/اسکریپت نمایش داده می‌شود این گزینه را فعال کنید.</span>
                            </th>
                            <td><input type="checkbox" name="voorodak_options[use_shortcode]" value="1" <?php checked($use_shortcode, '1'); ?> /></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #eee">
                            <th>شورتکد فرم ورود/ ثبت نام</th>
                            <td style="direction: ltr"><code>[voorodak]</code></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #eee">
                            <th>شورتکد دکمه ورود/ ثبت نام</th>
                            <td style="direction: ltr">
                                [voorodak_account_btn]
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #eee">
                            <th>شورتکد دکمه ورود/ ثبت نام
                            <span class="hint">مخصوص قالب آسترا</span>
                            </th>
                            <td style="direction: ltr">
                                [voorodak_account_btn style="astra"]
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #eee">
                            <th>نمایش نام کاربر در دکمه هنگام ورود
                                <span class="hint">با گزینه showname برابر با yes میتوانید نام کاربر را هنگام ورود در سایت درون دکمه، جای متن حساب کاربری نمیاش دهید </span>
                            </th>
                            <td style="direction: ltr">
                                [voorodak_account_btn showname="yes"]
                            </td>
                        </tr>

                    </table>
                    <div class="doc-box">
                        <h3>هوک بعد از لاگین</h3>
                        <p>
                            شما می‌توانید با استفاده از اکشن زیر، عملیات دلخواهی پس از ورود موفق کاربر انجام دهید:
                        </p>
                        <div class="code-box php">
                            <code>
                                &lt;?php
                                add_action('voorodak_after_do_login', function($user_id) {
                                // مثال: ثبت لاگ یا ارسال ایمیل خوش‌آمدگویی
                                error_log("کاربر با شناسه {$user_id} وارد شد.");
                                });
                                ?&gt;
                            </code>
                        </div>
                    </div>

                    <div class="doc-box">
                        <h3>هوک بعد از ثبت نام</h3>
                        <p>
                            شما می‌توانید با استفاده از اکشن زیر، عملیات دلخواهی پس از ثبت نام موفق کاربر انجام دهید:
                        </p>
                        <div class="code-box php">
                            <code>
                                &lt;?php
                                add_action('voorodak_after_do_register', function($user_id) {
                                // مثال: ثبت لاگ یا ارسال ایمیل خوش‌آمدگویی
                                error_log("کاربر با شناسه {$user_id} ثبت نام کرد.");
                                });
                                ?&gt;
                            </code>
                        </div>
                    </div>

                    <div class="doc-box">
                        <h3>وضعیت لاگ 10 درخواست آخر وب سرویس پیامکی</h3>
                        <div class="code-box" style="text-align: right;margin-top: 20px;padding: 8px;">
                           <?php echo voorodak_log_display(); ?>
                        </div>
                    </div>

                </div>
                <?php submit_button(); ?>
            </form>
            <div class="voorodak__body-main-hints">
                <div class="melipayamak">
                    <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'assets/images/logo.svg'); ?>" alt="">
                    <div class="melipayamak__main">
                        <h3>تخفیف ویژه ملی پیامک برای افزونه ورودک</h3>
                        <p>استفاده کنندگان افزونه ورودک میتوانند با کوپن زیر تا 10 درصد تخفیف از سامانه ملی پیامک خرید نمایند</p>
                    </div>
                    <div class="melipayamak__coupon">
                        <div class="melipayamak__coupon-main">
                            <div class="melipayamak__coupon-main-inner">MPQPTDF</div>
                        </div>
                    </div>
                </div>
                <div class="voorodak__body-main-hints-list">
                    <h3>راهنمای استفاده</h3>
                    <ol>
                        <li>یک سامانه پیامکی تهیه نمایید و اطلاعات حساب خود را در تنظیمات افزونه قرار دهید</li>
                        <li>یک برگه به طور خودکار در سایت شما با نام ورود ثبت نام ایجاد شده است ، که صفحه اختصاصی ورود ثبت نام شما میباشد
                            <a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url(get_the_permalink($login_page_id)); ?>">مشاهده برگه</a> (باید در حالت غیر لاگین یعنی private یا incognito برگه را باز کنید تا ورود ثبت نام را مشاهده کنید) </li>
                        <li>لینک این صفحه را در تنظیمات سربرگ قالب خود یا المنتور در دکمه ورود ثبت نام قرار دهید تا در دسترس باشد</li>
                        <li>همچنین از تب ظاهری میتوانید برگه ورود ثبت نام را تغییر دهید و برگه دیگری را انتخاب نمایید</li>
                    </ol>
                    <div style="background: #0d9488;color:#fff;padding: 10px;text-align: center;border-radius: 10px">
                        اگر از افزونه <b>راکت</b> یا <b>لایت اسپید</b> استفاده میکنید، به بخش برگه ها بروید و حتما کش برگه ورود ثبت نام افزونه ورودک را از تنظیمات ویرایش آن برگه غیرفعال کنید تا کلید های امنیتی پلاگین کش نشوند
                    </div>
                </div>
                <div class="taktheme">
                    <a href="https://taktheme.com" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo esc_url(plugin_dir_url(__DIR__) . 'assets/images/taktheme.webp'); ?>" alt="">
                    </a>
                    <p>در صورت وجود هرگونه مشکل در افزونه یا دریافت پشتیبانی میتوانید از طریق تلگرام به پشتیبانی تک تم پیام دهید</p>
                    <div class="taktheme-actions">
                        <a href="https://t.me/taktheme_support" rel="nofollow" class="taktheme-support-telegram">پشتیبانی تلگرام</a>
                        <a href="https://ble.ir/taktheme" rel="nofollow" class="taktheme-support-bale">پشتیبانی بله</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
