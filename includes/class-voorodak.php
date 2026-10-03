<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

trait Voorodak_Options
{
    /**
     * @return false|mixed|void
     */
    public function get_settings()
    {
        if ($settings = get_option(VOORODAK_OPTION)) {
            return $settings;
        } else {
            return false;
        }
    }

    public function add_message($message, $type = 'error')
    {
        ob_start();
        include plugin_dir_path(__DIR__) . 'view/html-notice-' . $type . '.php';
        return ob_get_clean();
    }

    public function check_admin()
    {
        if (is_admin() && is_user_logged_in() && current_user_can('manage_options')) {
            return true;
        } else {
            return false;
        }
    }

    private function get_secure_auth_key()
    {
        return wp_salt('auth');
    }

    /**
     * @return mixed|string
     */
    private function get_user_ip()
    {
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)
            ? (string) $_SERVER['REMOTE_ADDR']
            : '';

        $forwarded_ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)
            ? (string) $_SERVER['HTTP_CF_CONNECTING_IP']
            : '';

        // CF-Connecting-IP is client-controlled when an origin can be reached
        // directly. Trust it only when the immediate peer is a known Cloudflare
        // proxy. Integrators can extend/replace these ranges via the filter.
        if ($remote_ip && $forwarded_ip && $this->is_trusted_proxy_ip($remote_ip)) {
            $remote_ip = $forwarded_ip;
        }

        $remote_ip = (string) apply_filters('voorodak_client_ip', $remote_ip, $_SERVER);

        return filter_var($remote_ip, FILTER_VALIDATE_IP) ? $remote_ip : 'UNKNOWN';
    }

    private function is_trusted_proxy_ip($ip)
    {
        $cloudflare_ranges = [
            '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '104.16.0.0/13', '104.24.0.0/14', '108.162.192.0/18',
            '131.0.72.0/22', '141.101.64.0/18', '162.158.0.0/15',
            '172.64.0.0/13', '173.245.48.0/20', '188.114.96.0/20',
            '190.93.240.0/20', '197.234.240.0/22', '198.41.128.0/17',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32',
            '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29',
            '2c0f:f248::/32',
        ];

        $ranges = apply_filters('voorodak_trusted_proxy_ip_ranges', $cloudflare_ranges);
        if (!is_array($ranges)) {
            return false;
        }

        foreach ($ranges as $cidr) {
            if ($this->ip_is_in_cidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ip_is_in_cidr($ip, $cidr)
    {
        if (!is_string($cidr) || strpos($cidr, '/') === false) {
            return false;
        }

        [$network, $prefix_length] = explode('/', $cidr, 2);
        $ip_binary = @inet_pton($ip);
        $network_binary = @inet_pton($network);
        $prefix_length = (int) $prefix_length;

        if ($ip_binary === false || $network_binary === false || strlen($ip_binary) !== strlen($network_binary)) {
            return false;
        }

        $max_bits = strlen($ip_binary) * 8;
        if ($prefix_length < 0 || $prefix_length > $max_bits) {
            return false;
        }

        $full_bytes = intdiv($prefix_length, 8);
        $remaining_bits = $prefix_length % 8;

        if ($full_bytes && substr($ip_binary, 0, $full_bytes) !== substr($network_binary, 0, $full_bytes)) {
            return false;
        }

        if ($remaining_bits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remaining_bits)) & 0xFF;

        return (ord($ip_binary[$full_bytes]) & $mask) === (ord($network_binary[$full_bytes]) & $mask);
    }

    private function get_limiter_ip_key()
    {
        return 'voorodak_ip_limiter_' . $this->get_user_ip();
    }

    private function get_limiter_identity_key($identity)
    {
        $identity = strtolower(trim((string) $identity));

        if ($identity === '') {
            return '';
        }

        return 'voorodak_identity_limiter_' . hash('sha256', $identity);
    }

    private function get_otp_send_ip_key()
    {
        return 'voorodak_otp_send_ip_' . hash('sha256', $this->get_user_ip());
    }

    private function get_otp_send_identity_key($identity)
    {
        $identity = strtolower(trim((string) $identity));

        if ($identity === '') {
            return '';
        }

        return 'voorodak_otp_send_identity_' . hash('sha256', $identity);
    }

    /**
     * OTP delivery has its own limiter. It must not share state with failed
     * password/OTP verification attempts or with arbitrary SMS notifications.
     */
    private function check_otp_send_rate_limit($identity)
    {
        $settings = $this->get_settings();
        $max_requests = max(1, absint($settings['max_requests'] ?? 10));

        $ip_count = (int) (voorodak_get_option_transient($this->get_otp_send_ip_key()) ?: 0);
        if ($ip_count >= $max_requests) {
            wp_send_json_error([
                'message' => $this->add_message(__('تعداد درخواست‌های ارسال کد بیش از حد مجاز است، چند دقیقه صبر کنید.', 'voorodak'))
            ]);
        }

        $identity_key = $this->get_otp_send_identity_key($identity);
        if ($identity_key) {
            $identity_count = (int) (voorodak_get_option_transient($identity_key) ?: 0);
            if ($identity_count >= $max_requests) {
                wp_send_json_error([
                    'message' => $this->add_message(__('تعداد درخواست‌های ارسال کد برای این حساب بیش از حد مجاز است، چند دقیقه صبر کنید.', 'voorodak'))
                ]);
            }
        }
    }

    private function record_otp_send_attempt($identity)
    {
        $settings = $this->get_settings();
        $ttl = max(1, absint($settings['block_minutes'] ?? 10)) * MINUTE_IN_SECONDS;

        $ip_key = $this->get_otp_send_ip_key();
        $ip_count = (int) (voorodak_get_option_transient($ip_key) ?: 0);
        voorodak_set_option_transient($ip_key, $ip_count + 1, $ttl);

        $identity_key = $this->get_otp_send_identity_key($identity);
        if ($identity_key) {
            $identity_count = (int) (voorodak_get_option_transient($identity_key) ?: 0);
            voorodak_set_option_transient($identity_key, $identity_count + 1, $ttl);
        }
    }

    private function get_rate_limit($username = null)
    {
        $settings = $this->get_settings();

        $block_ip = $settings['block_ip'] ?? '';
        $block_ips = array_filter(
            array_map('trim', explode("\n", $block_ip))
        );
        if (in_array($this->get_user_ip(), $block_ips)) {
            wp_send_json_error([
                'message' => $this->add_message(
                    __('درخواست شما قابل پردازش نیست، چند دقیقه صبر کنید.', 'voorodak')
                )
            ]);
        }

        if (!empty($username)) {
            $block_mobile = $settings['block_mobile'] ?? '';
            $block_mobiles = array_filter(
                array_map('trim', explode("\n", $block_mobile))
            );
            if (in_array($username, $block_mobiles)) {
                wp_send_json_error([
                    'message' => $this->add_message(
                        __('درخواست شما قابل پردازش نیست، چند دقیقه صبر کنید.', 'voorodak')
                    )
                ]);
            }
        }



        $max_requests = $settings['max_requests'] ?? 10;
        $ip_count = voorodak_get_option_transient($this->get_limiter_ip_key()) ?? 0;
        if ($ip_count >= $max_requests) {
            wp_send_json_error([
                'message' => $this->add_message(
                    __('درخواست‌ها بیش از حد مجاز است، چند دقیقه صبر کنید.', 'voorodak')
                )
            ]);
        }
        if (!empty($username)) {
            $identity_key = $this->get_limiter_identity_key($username);
            $identity_count = $identity_key ? (voorodak_get_option_transient($identity_key) ?: 0) : 0;
            if ($identity_count >= $max_requests) {
                wp_send_json_error([
                    'message' => $this->add_message(
                        __('درخواست‌های مشکوک شناسایی شد، چند دقیقه صبر کنید.', 'voorodak')
                    )
                ]);
            }
        }
    }

    private function set_rate_limit($identity = null)
    {
        $settings = $this->get_settings();
        $block_minutes = $settings['block_minutes'] ?? 10;
        $ttl = max(1, absint($block_minutes)) * MINUTE_IN_SECONDS;

        $ip_key = $this->get_limiter_ip_key();
        $ip_count = voorodak_get_option_transient($ip_key) ?: 0;
        voorodak_set_option_transient($ip_key, $ip_count + 1, $ttl);

        if (!empty($identity)) {
            $identity_key = $this->get_limiter_identity_key($identity);
            if ($identity_key) {
                $identity_count = voorodak_get_option_transient($identity_key) ?: 0;
                voorodak_set_option_transient($identity_key, $identity_count + 1, $ttl);
            }
        }
    }

    private function clean_rate_limit($identity = null)
    {
        voorodak_delete_option_transient($this->get_limiter_ip_key());

        if (!empty($identity)) {
            $identity_key = $this->get_limiter_identity_key($identity);
            if ($identity_key) {
                voorodak_delete_option_transient($identity_key);
            }
        }
    }

    private function is_captcha_enabled()
    {
        $settings = $this->get_settings();
        return !empty($settings['captcha_enabled']);
    }

    public function get_captcha_challenge()
    {
        if (!$this->is_captcha_enabled()) {
            return false;
        }

        $left = random_int(1, 9);
        $right = random_int(1, 9);
        $token = wp_generate_password(24, false, false);
        $answer = (string) ($left + $right);

        voorodak_set_option_transient(
            'voorodak_captcha_' . $token,
            wp_hash($answer),
            10 * MINUTE_IN_SECONDS
        );

        return [
            'token' => $token,
            'question' => sprintf('%d + %d = ?', $left, $right),
        ];
    }

    private function validate_captcha_request($check_challenge = true)
    {
        $honeypot = isset($_POST['website']) ? sanitize_text_field(wp_unslash($_POST['website'])) : '';

        if (!empty($honeypot)) {
            $this->set_rate_limit();
            $message = __('درخواست نامعتبر می‌باشد.', 'voorodak');
            wp_send_json_error(['message' => $this->add_message($message)]);
        }

        if (!$check_challenge) {
            return true;
        }

        if (!$this->is_captcha_enabled()) {
            return true;
        }

        $token = isset($_POST['captcha_token']) ? preg_replace('/[^A-Za-z0-9]/', '', sanitize_text_field(wp_unslash($_POST['captcha_token']))) : '';
        $answer = isset($_POST['captcha']) ? sanitize_text_field(wp_unslash($_POST['captcha'])) : '';
        $answer = preg_replace('/\D+/', '', $answer);

        $captcha_key = $token ? 'voorodak_captcha_' . $token : '';
        $stored = $captcha_key ? voorodak_get_option_transient($captcha_key) : false;

        if (empty($stored) || !hash_equals((string) $stored, (string) wp_hash($answer))) {
            $this->set_rate_limit();
            $message = __('کد امنیتی صحیح نیست.', 'voorodak');
            wp_send_json_error(['message' => $this->add_message($message)]);
        }

        voorodak_delete_option_transient($captcha_key);

        return true;
    }

    private function is_persian_name_value($value)
    {
        $value = trim((string) $value);

        if ($value === '') {
            return true;
        }

        if (preg_match('/[A-Za-z0-9]/u', $value)) {
            return false;
        }

        return (bool) preg_match('/^[\x{0600}-\x{06FF}\s\x{200C}]+$/u', $value);
    }

    private function normalize_national_code($value)
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    private function is_valid_national_code($value)
    {
        $value = $this->normalize_national_code($value);

        if (!preg_match('/^\d{10}$/', $value) || preg_match('/^(\d)\1{9}$/', $value)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $value[$i] * (10 - $i);
        }

        $remainder = $sum % 11;
        $check_digit = (int) $value[9];

        return $remainder < 2 ? $check_digit === $remainder : $check_digit === 11 - $remainder;
    }



}

class Voorodak_Base
{
    use Voorodak_Options;


    public function __construct()
    {
        // These hooks are safe to register unconditionally. The previous
        // realpath/WP_PLUGIN_DIR guard could fail on symlinked or non-standard
        // plugin directories and leave the settings page unavailable.
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_admin_settings'));
        add_action('admin_notices', [$this, 'admin_notices']);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_submit_test_phone', array($this, 'submit_test_phone'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter( 'manage_users_columns', array($this, 'user_table_th') );
        add_filter( 'manage_users_custom_column', array($this, 'user_table_td'), 10, 3 );
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('voorodak', false, dirname(plugin_basename(VOORODAK_PLUGIN_FILE)) . '/languages');
    }

    /**
     * @return void
     */
    public function register_admin_menu()
    {
        add_menu_page('ورودک', 'ورودک', 'manage_options', 'voorodak-settings', array($this, 'render_settings_page'));
    }

    /**
     * @return void
     */
    public function register_admin_settings()
    {
        register_setting('voorodak-settings', 'voorodak_options', [$this, 'validate_settings']);
    }

    /**
     * @return void
     */
    public function admin_notices()
    {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== 'voorodak-settings') {
            return;
        }

        settings_errors('voorodak_messages');
    }

    /**
     * @return void
     */
    public function submit_test_phone()
    {
        check_ajax_referer('voorodak_admin', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
        }
        $settings = $this->get_settings();
        $variable = voorodak_get_variable($settings);
        $otp = '1234';
        $phone = isset($_POST['phone']) ? preg_replace('/\D+/', '', sanitize_text_field(wp_unslash($_POST['phone']))) : '';
        if (!preg_match('/^09\d{9}$/', $phone)) {
            wp_send_json_error('شماره موبایل صحیح نیست.');
        }
        $sms = new Voorodak_SMS();
        $sms->otp = array($variable => $otp);
        $sms->to = $phone;
        $response = $sms->send(false,$otp,$phone);
        $result = $response;
        if (empty($result)){
            $result = 'خطایی یافت نشد، لاگ وب سرویس را از تب لاگ و توسعه بررسی نمایید';
        }
        wp_send_json_success($result);
    }

    /**
     * @param $input
     * @return mixed
     */
    public function validate_settings($input)
    {
        if (!is_array($input)) {
            return [];
        }

        $existing_settings = get_option(VOORODAK_OPTION, []);
        if (!is_array($existing_settings)) {
            $existing_settings = [];
        }

        $sanitized = [];

        // Checkbox values must be persisted explicitly so an unchecked option does not
        // fall back to a feature default on the next request.
        $checkbox_keys = [
            'autofill', 'bale_comment_new', 'bale_order_completed', 'bale_user_login',
            'bale_user_login_admin', 'bale_user_register', 'baleotp', 'btnback', 'btnbackprev',
            'captcha_enabled', 'date_register', 'digits', 'disable_admin_login',
            'disable_random_email', 'email_field', 'email_field_force', 'email_register',
            'family_name', 'family_name_force', 'family_name_persian_only', 'national_code_field',
            'national_code_force', 'otp_boxed', 'password_field', 'password_login_priority',
            'register_allow', 'remember_me', 'sms_comment_new_admin', 'sms_comment_reply_user', 'sms_login',
            'sms_login_admin', 'sms_login_roleadmin_admin', 'sms_register', 'sms_register_admin',
            'use_shortcode', 'woocommerce_checkout', 'woocommerce_login',
        ];

        foreach ($checkbox_keys as $key) {
            $sanitized[$key] = !empty($input[$key]) ? '1' : '';
        }

        // Preserve WooCommerce order notification settings dynamically because order
        // statuses can be extended by third-party plugins. The full `wc-*` key is part
        // of the plugin's existing option contract and must remain unchanged.
        if (function_exists('wc_get_order_statuses')) {
            foreach (array_keys(wc_get_order_statuses()) as $status) {
                $status_key = sanitize_key((string) $status);
                foreach (['admin', 'user'] as $recipient) {
                    $base_key = "sms_order_{$status_key}_{$recipient}";
                    $sanitized[$base_key] = !empty($input[$base_key]) ? '1' : '';

                    foreach (['pattern', 'variables'] as $suffix) {
                        $key = "{$base_key}_{$suffix}";
                        if (isset($input[$key]) && is_scalar($input[$key])) {
                            $sanitized[$key] = sanitize_text_field((string) $input[$key]);
                        }
                    }
                }
            }
        }

        $text_keys = [
            'gateway_username', 'gateway_from', 'gateway_pattern_otp', 'variable',
            'digits_meta', 'user_field_meta', 'user_field_meta2', 'balebotid', 'form_name',
            'sms_login_admin_pattern', 'sms_login_roleadmin_admin_pattern',
            'sms_register_admin_pattern', 'sms_login_pattern', 'sms_register_pattern',
            'sms_comment_new_admin_pattern', 'sms_comment_reply_user_pattern',
            'sms_login_admin_variables', 'sms_login_roleadmin_admin_variables',
            'sms_register_admin_variables', 'sms_login_pattern_variables', 'sms_register_variables',
            'sms_comment_new_admin_variables', 'sms_comment_reply_user_variables',
        ];

        foreach ($text_keys as $key) {
            if (isset($input[$key]) && is_scalar($input[$key])) {
                $sanitized[$key] = sanitize_text_field((string) $input[$key]);
            }
        }

        $textarea_keys = ['gateway_message', 'sms_admins', 'balechatids', 'block_ip', 'block_mobile'];
        foreach ($textarea_keys as $key) {
            if (isset($input[$key]) && is_scalar($input[$key])) {
                $sanitized[$key] = sanitize_textarea_field((string) $input[$key]);
            }
        }

        // Credentials are intentionally not normalized or trimmed. Some providers issue
        // tokens whose exact byte representation is significant. Output escaping is done
        // at render time.
        foreach (['gateway_password', 'baleapi', 'balebottoken'] as $secret_key) {
            if (isset($input[$secret_key]) && is_scalar($input[$secret_key])) {
                $sanitized[$secret_key] = (string) $input[$secret_key];
            }
        }

        if (isset($input['gateway']) && is_scalar($input['gateway'])) {
            $gateway = Voorodak_SMS::check_gateway(sanitize_key((string) $input['gateway']));
            $gateways = Voorodak_SMS::gateways();
            $sanitized['gateway'] = array_key_exists($gateway, $gateways) ? $gateway : 'melipayamakrest_pattern';
        }

        $enum_settings = [
            'template' => ['default', 'digikala', 'zarinpal'],
            'login_type' => ['mobile', 'mobile-email', 'mobile-email-username'],
            'username_format' => ['with-zero', 'without-zero'],
            'backurl' => ['prev', 'home', 'custom'],
        ];
        foreach ($enum_settings as $key => $allowed) {
            if (isset($input[$key]) && is_scalar($input[$key])) {
                $value = sanitize_key((string) $input[$key]);
                if (in_array($value, $allowed, true)) {
                    $sanitized[$key] = $value;
                }
            }
        }

        if (isset($input['default_role']) && is_scalar($input['default_role'])) {
            $requested_role = sanitize_key((string) $input['default_role']);
            $safe_role = voorodak_normalize_registration_role($requested_role);
            $sanitized['default_role'] = $safe_role;

            if ($requested_role !== '' && $requested_role !== $safe_role) {
                add_settings_error(
                    'voorodak_messages',
                    'voorodak_unsafe_registration_role',
                    'نقش انتخاب‌شده برای ثبت‌نام عمومی دارای دسترسی مدیریتی بود و به یک نقش امن تغییر کرد.',
                    'warning'
                );
            }
        }

        if (isset($input['login_page_id'])) {
            $sanitized['login_page_id'] = absint($input['login_page_id']);
        }

        $number_rules = [
            'gateway_timeout' => [5, 60, 20],
            'max_requests' => [5, 30, 10],
            'block_minutes' => [5, 30, 10],
            'otp_length' => [4, 8, 6],
            'password_length' => [4, 128, 8],
        ];
        foreach ($number_rules as $key => [$min, $max, $default]) {
            $value = isset($input[$key]) ? absint($input[$key]) : $default;
            $sanitized[$key] = min($max, max($min, $value));
        }

        foreach (['backurl_custom', 'logouturl', 'panelurl_custom', 'logo', 'cover'] as $url_key) {
            if (isset($input[$url_key]) && is_scalar($input[$url_key])) {
                $sanitized[$url_key] = esc_url_raw((string) $input[$url_key]);
            }
        }

        foreach (['bg_color', 'button_color', 'button_color_hover'] as $color_key) {
            if (isset($input[$color_key]) && is_scalar($input[$color_key])) {
                $color = sanitize_hex_color((string) $input[$color_key]);
                if ($color !== null && $color !== '') {
                    $sanitized[$color_key] = $color;
                }
            }
        }

        if (isset($input['term_editor']) && is_scalar($input['term_editor'])) {
            $sanitized['term_editor'] = wp_kses_post((string) $input['term_editor']);
        }

        // Whitelist notification pattern/variable fields created for WooCommerce statuses.
        foreach ($input as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            if (preg_match('/^sms_order_[a-z0-9_-]+_(admin|user)_(pattern|variables)$/', $key)) {
                $sanitized[$key] = sanitize_text_field((string) $value);
            }
        }

        // Preserve settings for WooCommerce/third-party order statuses that are
        // temporarily unavailable while this settings screen is saved. Known
        // statuses are already normalized above, so only currently-unrendered
        // dynamic keys reach this compatibility branch.
        foreach ($existing_settings as $key => $value) {
            if (!is_string($key) || array_key_exists($key, $sanitized)) {
                continue;
            }

            if (preg_match('/^sms_order_[a-z0-9_-]+_(admin|user)$/', $key)) {
                $sanitized[$key] = !empty($value) ? '1' : '';
                continue;
            }

            if (preg_match('/^sms_order_[a-z0-9_-]+_(admin|user)_(pattern|variables)$/', $key)) {
                $sanitized[$key] = is_scalar($value) ? sanitize_text_field((string) $value) : '';
            }
        }

        add_settings_error(
            'voorodak_messages',
            'voorodak_message',
            __('تنظیمات با موفقیت ذخیره شد.', 'voorodak'),
            'updated'
        );

        return (array) apply_filters(
            'voorodak_sanitized_settings',
            $sanitized,
            $input,
            $existing_settings
        );
    }

    public function render_settings_page()
    {
        require_once plugin_dir_path(__DIR__) . 'view/html-settings.php';
    }


    /**
     * @return void
     */
    public function enqueue_assets()
    {
        $settings = $this->get_settings();
        $autofill = $settings['autofill'] ?? '';
        $otp_length = $settings['otp_length'] ?? '6';
        $password_length = $settings['password_length'] ?? '8';
        $login_type = $settings['login_type'] ?? 'mobile-email';
        $backurl_default = home_url();
        if (function_exists('is_woocommerce')) {
            $backurl_default = get_permalink(wc_get_page_id('myaccount'));
        }
        $backurl = $settings['backurl'] ?? 'prev';
        $backurl_custom = $settings['backurl_custom'] ?? '';
        $backurl_query = isset($_GET['backurl']) ? sanitize_key(wp_unslash($_GET['backurl'])) : '';
        if (function_exists('is_woocommerce') && $backurl_query === 'checkout') {
            $backurl = wc_get_checkout_url();
        } elseif ($backurl == 'home') {
            $backurl = home_url();
        } elseif ($backurl == 'custom' && !empty($backurl_custom)) {
            $backurl = esc_url_raw($backurl_custom);
        }elseif (isset($_GET['backUrl'])){
            $backurl = wp_validate_redirect(esc_url_raw(wp_unslash($_GET['backUrl'])), $backurl_default);
        } else {
            $backurl = wp_get_referer();
            if (empty($backurl)) {
                $backurl = $backurl_default;
            }
        }
        $login_page_id = voorodak_get_login_page_id();
        $current_page_id = get_queried_object_id();
        $post_content = $current_page_id ? (string) get_post_field('post_content', $current_page_id) : '';
        $contains_shortcode = $post_content !== '' && has_shortcode($post_content, 'voorodak');
        $force_shortcode_assets = !empty($settings['use_shortcode']);
        $should_enqueue = (
            (absint($login_page_id) > 0 && absint($login_page_id) === absint($current_page_id))
            || $contains_shortcode
            || $force_shortcode_assets
        );

        /**
         * Allow builders/integrations that store shortcode configuration outside
         * post_content (for example in custom metadata) to opt in without forcing
         * assets onto every page.
         */
        $should_enqueue = (bool) apply_filters(
            'voorodak_should_enqueue_frontend_assets',
            $should_enqueue,
            $current_page_id,
            $settings
        );

        if ($should_enqueue && !is_user_logged_in()) {
            $script_path = VOORODAK_PLUGIN_PATH . 'assets/js/script.js';
            $script_version = VOORODAK_VERSION;
            if (is_readable($script_path)) {
                $script_version .= '.' . filemtime($script_path);
            }

            wp_enqueue_script('voorodak-script', VOORODAK_PLUGIN_URL . 'assets/js/script.js', [], $script_version, true);
            wp_enqueue_style('voorodak-style', VOORODAK_PLUGIN_URL . 'assets/css/style.css', [], VOORODAK_VERSION);
            $data = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce("voorodak_security"),
                'backurl' => $backurl,
                'otp_length' => $otp_length,
                'login_type' => $login_type,
                'password_length' => $password_length,
                'autofill' => $autofill,
                'captcha_enabled' => !empty($settings['captcha_enabled']) ? '1' : '',
                'family_name_persian_only' => !empty($settings['family_name_persian_only']) ? '1' : '',
                'national_code_field' => !empty($settings['national_code_field']) ? '1' : '',
                'national_code_force' => !empty($settings['national_code_force']) ? '1' : '',
                'password_login_priority' => !empty($settings['password_login_priority']) ? '1' : '',
                'i18n' => array(
                    'show_password' => __('نمایش رمز عبور', 'voorodak'),
                    'hide_password' => __('پنهان کردن رمز عبور', 'voorodak'),
                    'field_required' => __('لطفا این قسمت را خالی نگذارید', 'voorodak'),
                    'invalid_mobile' => __('شماره موبایل صحیح نمی‌باشد.', 'voorodak'),
                    'invalid_mobile_email' => __('شماره موبایل یا ایمیل صحیح نمی‌باشد.', 'voorodak'),
                    'enter_captcha' => __('لطفا کد امنیتی را وارد کنید', 'voorodak'),
                    'persian_name_only' => __('نام و نام خانوادگی باید فقط با حروف فارسی وارد شود', 'voorodak'),
                    'national_code_required' => __('کد ملی الزامی میباشد', 'voorodak'),
                    'invalid_national_code' => __('کد ملی معتبر نمیباشد', 'voorodak'),
                    /* translators: %s: OTP length */
                    'otp_length_error' => __('کد تایید باید %s رقم باشد', 'voorodak'),
                    'otp_numeric' => __('کد تایید باید عددی باشد', 'voorodak'),
                    'enter_password' => __('لطفا رمز عبور را وارد کنید', 'voorodak'),
                    /* translators: %s: Password minimum length */
                    'password_min_length' => __('رمز عبور باید حداقل %s کاراکتر باشد', 'voorodak'),
                    'password_strength' => __('رمز عبور باید شامل حداقل یک حرف انگلیسی و یک عدد باشد', 'voorodak'),
                    'password_mismatch' => __('تکرار رمز عبور با رمز جدید مطابقت ندارد', 'voorodak'),
                    'sending' => __('در حال ارسال ...', 'voorodak'),
                ),
            );
            if (isset($_GET['backUrl'])){
                $data['backUrl'] = wp_validate_redirect(esc_url_raw(wp_unslash($_GET['backUrl'])), $backurl_default);
            }
            if ($login_page_id) {
                $data['login_url'] = get_the_permalink($login_page_id);
            }
            wp_localize_script('voorodak-script', 'voorodak_data', $data);
        }
    }

    /**
     * @param $hook
     * @return void
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'voorodak') === false) return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();
        wp_enqueue_script('voorodak-script-admin', VOORODAK_PLUGIN_URL . 'assets/js/script-admin.js', ['wp-color-picker'], VOORODAK_VERSION, true);
        wp_enqueue_style('voorodak-style-admin', VOORODAK_PLUGIN_URL . 'assets/css/style-admin.css', [], VOORODAK_VERSION);
        wp_localize_script('voorodak-script-admin', 'voorodak_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('voorodak_admin'),
            'users_export_url' => wp_nonce_url(
                admin_url('admin-post.php?action=voorodak_export_users_csv'),
                'voorodak_export_users_csv'
            ),
        ));
    }


    public function user_table_th( $column ) {
        $settings = $this->get_settings();
        $date_register = $settings['date_register'] ?? '';
        if ($date_register) {
            $column['signup_date'] = 'تاریخ ثبت نام';
        }
        return $column;
    }

    public function user_table_td( $val, $column_name, $user_id ) {
        $settings = $this->get_settings();
        $date_register = $settings['date_register'] ?? '';
        if ($date_register) {
            if ( $column_name === 'signup_date' ) {
                $user = get_user_by( 'id', $user_id );
                $date_formatted = new DateTime( $user->user_registered );
                return wp_date( 'j F Y', strtotime( $date_formatted->format( 'Y-m-d' ) ) );
            }
        }
        return $val;
    }


}

class Voorodak_Auth
{
    use Voorodak_Options;

    private $voorodak_sms = null;

    public function __construct()
    {
        add_action('wp_ajax_nopriv_voorodak__submit-username', array($this, 'submit_username'));
        add_action('wp_ajax_nopriv_voorodak__submit-otp', array($this, 'submit_otp'));
        add_action('wp_ajax_nopriv_voorodak__submit-otp-reset', array($this, 'submit_otp_reset'));
        add_action('wp_ajax_nopriv_voorodak__submit-password', array($this, 'submit_password'));
        add_action('wp_ajax_nopriv_voorodak__submit-forget', array($this, 'submit_forget'));
        add_action('wp_ajax_nopriv_voorodak__submit-reset', array($this, 'submit_reset'));
    }

    private function get_sms_service()
    {
        if (!$this->voorodak_sms instanceof Voorodak_SMS) {
            $this->voorodak_sms = new Voorodak_SMS();
        }

        return $this->voorodak_sms;
    }


    /**
     * @return void
     */
    private function invalid_request()
    {
        $message = __('درخواست نامعتبر می‌باشد.', 'voorodak');
        wp_send_json_error(array('message' => $this->add_message($message)));
    }


    /**
     * @return void
     */
    private function validate_ajax_request($username = null)
    {
        check_ajax_referer('voorodak_security', 'security');
        $this->get_rate_limit($username);
    }

    /**
     * @param $username
     * @return string|void
     */
    private function validate_username($username)
    {
        $settings = $this->get_settings();
        $login_type = $settings['login_type'] ?? 'mobile-email';
        $validate_mobile = preg_match("/^09[0-9]{9}$/", $username);
        $validate_email = filter_var($username, FILTER_VALIDATE_EMAIL);
        if ($login_type == 'mobile-email-username') {
            if ($validate_mobile) {
                return 'mobile';
            } elseif ($validate_email) {
                return 'email';
            } else {
                return 'username';
            }
        } elseif ($login_type == 'mobile-email') {
            if ($validate_mobile) {
                return 'mobile';
            } elseif ($validate_email) {
                return 'email';
            } else {
                $message = __('شماره موبایل یا ایمیل صحیح نمی‌باشد.', 'voorodak');
                wp_send_json_error(array('message' => $this->add_message($message)));
            }
        } else {
            if ($validate_mobile) {
                return 'mobile';
            } else {
                $message = __('شماره موبایل صحیح نمی‌باشد.', 'voorodak');
                wp_send_json_error(array('message' => $this->add_message($message)));
            }
        }
    }

    private function get_username_format_save($username){
        $settings =  $this->get_settings();
        $username_format = $settings['username_format'] ?? 'with-zero';
        $username_save = $username;
        if ($username_format == 'without-zero' && $username[0] == "0") {
            $username_save = substr($username, 1);
        }
        return $username_save;
    }


    public function get_user_id_by_digits_field($mobile) {
        $settings =  $this->get_settings();
        $digits_meta = $settings['digits_meta'] ?? 'digits_phone';
        $mobile = sanitize_text_field($mobile);
        if (empty($mobile)) {
            return false;
        }
        if ($digits_meta == 'digits_phone'){
            if (substr($mobile, 0, 1) == '0') {
                $mobile = '+98' . substr($mobile, 1);
            }
        }

        global $wpdb;
        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                $digits_meta,
                $mobile
            )
        );
        return $user_id ? $user_id : false;
    }

    private function get_user_id_by_mobile_meta($mobile)
    {
        $settings = $this->get_settings();
        $meta_keys = array_filter(array_unique([
            'billing_phone',
            $settings['user_field_meta'] ?? 'billing_phone',
            $settings['user_field_meta2'] ?? '',
            $settings['digits_meta'] ?? 'digits_phone',
            'digits_phone',
        ]));

        $mobile = preg_replace('/\D+/', '', sanitize_text_field($mobile));
        if (empty($mobile)) {
            return false;
        }

        $mobile_without_zero = ltrim($mobile, '0');
        $variants = array_filter(array_unique([
            $mobile,
            '0' . $mobile_without_zero,
            $mobile_without_zero,
            '98' . $mobile_without_zero,
            '+98' . $mobile_without_zero,
        ]));

        global $wpdb;
        $meta_placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
        $value_placeholders = implode(',', array_fill(0, count($variants), '%s'));
        $query = $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key IN ($meta_placeholders) AND meta_value IN ($value_placeholders) LIMIT 1",
            array_merge($meta_keys, $variants)
        );
        $user_id = $wpdb->get_var($query);

        return $user_id ? absint($user_id) : false;
    }



    /**
     * @param $mobile
     * @param $exit
     * @return false|int|void
     */
    public function get_user_id_by_mobile($mobile, $exit = true)
    {
        $settings = $this->get_settings();
        $username_save = $this->get_username_format_save($mobile);
        $user_id = username_exists(sanitize_user($username_save));
        if ($user_id) {
            return $user_id;
        }
        $digits = $settings['digits'] ?? '';
        if ($digits){
            $user_id = $this->get_user_id_by_digits_field($mobile);
            if ($user_id) {
                return $user_id;
            }
        }
        $user_id = $this->get_user_id_by_mobile_meta($mobile);
        if ($user_id) {
            return $user_id;
        }
        if ($exit) {
            $message = __('کاربری با چنین شماره موبایلی در سایت وجود ندارد.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        } else {
            return false;
        }

    }

    /**
     * @param $email
     * @param $exit
     * @return false|int|void
     */
    private function get_user_id_by_email($email, $exit = true)
    {
        $user_id = email_exists(sanitize_email($email));
        if ($user_id) {
            return $user_id;
        }
        if ($exit) {
            $message = __('کاربری با چنین مشخصات در سایت وجود ندارد، لطفا با شماره موبایل وارد شوید.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        } else {
            return false;
        }
    }

    /**
     * @param $username
     * @param $exit
     * @return false|int|void
     */
    private function get_user_id_by_username($username, $exit = true)
    {
        $user_id = username_exists(sanitize_user($username));
        if ($user_id) {
            return $user_id;
        }
        if ($exit) {
            $message = __('کاربری با چنین مشخصات در سایت وجود ندارد، لطفا با شماره موبایل وارد شوید.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        } else {
            return false;
        }
    }

    /**
     * @param $user_id
     * @param $password
     * @return bool
     */
    private function check_user_password($user_id, $password, $identity = null)
    {
        $user = get_user_by('id', $user_id);
        if (!wp_check_password($password, $user->data->user_pass, $user_id)) {
            $this->set_rate_limit($identity);
            $message = __('رمز عبور صحیح نمی‌باشد.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        }
        $this->clean_rate_limit($identity);
        return true;
    }

    /**
     * @param $mobile
     * @return int|mixed
     * @throws Exception
     */
    private function generate_otp($mobile, $force_generate = false)
    {
        $settings = $this->get_settings();
        $otp_length = min(8, max(4, absint($settings['otp_length'] ?? 6)));
        $otp_transient_key = VOORODAK_OTP . $mobile;

        if (!$force_generate && voorodak_get_option_transient($otp_transient_key)) {
            return false;
        }

        $min = 10 ** ($otp_length - 1);
        $max = (10 ** $otp_length) - 1;
        $otp = (string) random_int($min, $max);
        $otp_verifier = hash_hmac('sha256', $otp, $this->get_secure_auth_key());

        voorodak_set_option_transient($otp_transient_key, $otp_verifier, 2 * MINUTE_IN_SECONDS);

        return $otp;
    }

    /**
     * @param $mobile
     * @param $otp
     * @return bool|void
     */
    public function check_otp($mobile, $otp)
    {
        $otp_transient_key = VOORODAK_OTP . $mobile;
        $stored_verifier = voorodak_get_option_transient($otp_transient_key);

        if (!$stored_verifier) {
            $this->set_rate_limit($mobile);
            $message = __('رمز یکبار مصرف ایجاد نشده یا منقضی شده است.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        }

        $otp = (string) $otp;
        $settings = $this->get_settings();
        $otp_length = min(8, max(4, absint($settings['otp_length'] ?? 6)));
        if (!preg_match('/^\d{' . $otp_length . '}$/', $otp)) {
            $this->set_rate_limit($mobile);
            $message = __('کد تایید صحیح نمی‌باشد.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        }

        $candidate = hash_hmac('sha256', $otp, $this->get_secure_auth_key());
        if (!hash_equals((string) $stored_verifier, $candidate)) {
            $this->set_rate_limit($mobile);
            $message = __('کد تایید صحیح نمی‌باشد.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        }

        $this->clean_rate_limit($mobile);
        voorodak_delete_option_transient($otp_transient_key);

        return true;
    }

    /**
     * @param $user_id
     * @return mixed|void
     */
    private function get_reset_token_verifier($reset_token)
    {
        return hash_hmac('sha256', (string) $reset_token, $this->get_secure_auth_key());
    }

    private function get_reset_lookup_key($reset_token)
    {
        return VOORODAK_RESET_LOOKUP . $this->get_reset_token_verifier($reset_token);
    }

    private function generate_reset_token_password($user_id)
    {
        $user_id = absint($user_id);
        $reset_token_transient_key = VOORODAK_RESET_TOEKN . $user_id;
        $existing_state = voorodak_get_option_transient($reset_token_transient_key);

        if (is_array($existing_state) && !empty($existing_state['lookup_key'])) {
            voorodak_delete_option_transient($existing_state['lookup_key']);
        }

        $reset_token = wp_generate_password(64, false, false);
        $verifier = $this->get_reset_token_verifier($reset_token);
        $lookup_key = VOORODAK_RESET_LOOKUP . $verifier;

        voorodak_set_option_transient(
            $reset_token_transient_key,
            [
                'verifier' => $verifier,
                'lookup_key' => $lookup_key,
            ],
            2 * HOUR_IN_SECONDS
        );
        voorodak_set_option_transient($lookup_key, $user_id, 2 * HOUR_IN_SECONDS);

        return $reset_token;
    }

    /**
     * @param $reset_token
     * @return array|string|string[]|void
     */
    private function get_user_id_by_reset_token($reset_token = null)
    {
        $reset_token = is_string($reset_token) ? trim($reset_token) : '';
        // Both the current and legacy implementations generate long
        // alphanumeric tokens. Reject malformed input before touching the
        // compatibility database scan to keep invalid requests cheap.
        if ($reset_token === '' || !preg_match('/^[A-Za-z0-9]{32,128}$/', $reset_token)) {
            $this->set_rate_limit();
            $message = __('درخواست بازیابی نامعتبر می‌باشد.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        }

        $lookup_key = $this->get_reset_lookup_key($reset_token);
        $user_id = absint(voorodak_get_option_transient($lookup_key));
        if ($user_id) {
            $stored_state = voorodak_get_option_transient(VOORODAK_RESET_TOEKN . $user_id);
            $verifier = $this->get_reset_token_verifier($reset_token);

            if (
                is_array($stored_state)
                && isset($stored_state['verifier'])
                && is_string($stored_state['verifier'])
                && hash_equals($stored_state['verifier'], $verifier)
            ) {
                return $user_id;
            }

            // Compatibility with reset tokens created by the previous plugin version.
            if (is_string($stored_state) && hash_equals($stored_state, $reset_token)) {
                return $user_id;
            }

            voorodak_delete_option_transient($lookup_key);
        }

        // Compatibility path for reset tokens generated by older plugin versions.
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like(VOORODAK_RESET_TOEKN) . '%'
            )
        );

        foreach ((array) $results as $row) {
            if (empty($row->option_name) || empty($row->option_value)) {
                continue;
            }

            $data = maybe_unserialize($row->option_value);
            if (!is_array($data) || !isset($data['value']) || !is_string($data['value'])) {
                continue;
            }

            // Legacy temporary options carried their own expiration timestamp.
            // Never revive a token that has already expired merely because the
            // daily legacy cleanup has not run yet.
            $expires_at = isset($data['expire']) ? absint($data['expire']) : 0;
            if ($expires_at && time() >= $expires_at) {
                delete_option($row->option_name);
                continue;
            }

            if (hash_equals($data['value'], $reset_token)) {
                $user_id = absint(str_replace(VOORODAK_RESET_TOEKN, '', $row->option_name));
                if ($user_id) {
                    $remaining_ttl = $expires_at ? max(1, $expires_at - time()) : 2 * HOUR_IN_SECONDS;
                    voorodak_set_option_transient($lookup_key, $user_id, $remaining_ttl);
                    return $user_id;
                }
            }
        }

        $this->set_rate_limit();
        $message = __('توکن بازیابی رمز عبور صحیح نمی‌باشد.', 'voorodak');
        wp_send_json_error(array('message' => $this->add_message($message)));
    }

    /**
     * @param $to
     * @param $user_id
     * @return void
     */
    private function send_email_reset_token($user_id, $to, $login_url)
    {
        $sent_email_transient_key = VOORODAK_SENT_EMAIL . $user_id;
        if (voorodak_get_option_transient($sent_email_transient_key)) {
            $message = __('ایمیل بازیابی رمز عبور قبلا برای شما ارسال شده است.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        }

        $reset_token = $this->generate_reset_token_password($user_id);
        $url_reset_pass = add_query_arg('reset_token', rawurlencode($reset_token), $login_url);
        $site_name = get_bloginfo('name');
        /* translators: %s: Site name */
        $subject = sprintf(__('درخواست تغییر رمز برای %s', 'voorodak'), $site_name);
        /* translators: %s: Site name */
        $body = sprintf(__('جهت تغییر رمز عبور حساب خود در سایت %s کافیست روی لینک زیر کلیک نمایید:', 'voorodak'), $site_name);
        $body .= '<br>';
        $body .= __('این لینک فقط 2 ساعت اعتبار دارد.', 'voorodak');
        $body .= '<br>';
        $body .= "<a target='_blank' rel='noopener noreferrer' href='" . esc_url($url_reset_pass) . "'>" . esc_html($url_reset_pass) . "</a>";
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if (wp_mail($to, $subject, $body, $headers)) {
            voorodak_set_option_transient($sent_email_transient_key, 1, HOUR_IN_SECONDS);
            $message = __('ایمیل بازیابی رمز عبور برای شما ارسال شد، بخش inbox و spam ایمیل خود را چک کنید', 'voorodak');
            wp_send_json_success(array('message' => $this->add_message($message, 'success')));
        }

        // Do not leave a usable reset token behind if the email was not delivered.
        voorodak_delete_option_transient($this->get_reset_lookup_key($reset_token));
        voorodak_delete_option_transient(VOORODAK_RESET_TOEKN . absint($user_id));
        $message = __('مشکلی در ارسال ایمیل بازیابی پیش آمده است، مجدد تلاش کنید', 'voorodak');
        wp_send_json_error(array('message' => $this->add_message($message)));
    }

    private function send_email_register_otp($to, $otp)
    {
        $site_name = get_bloginfo('name');
        /* translators: %s: Site name */
        $subject = sprintf(__('کد تایید ثبت نام در %s', 'voorodak'), $site_name);
        /* translators: %s: Site name */
        $body = sprintf(__('کد تایید ثبت نام شما در سایت %s:', 'voorodak'), $site_name);
        $body .= '<br>';
        $body .= '<strong style="font-size: 22px; direction: ltr; display: inline-block;">' . esc_html($otp) . '</strong>';
        $body .= '<br>';
        $body .= __('این کد فقط 2 دقیقه اعتبار دارد.', 'voorodak');
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if (wp_mail($to, $subject, $body, $headers)) {
            return true;
        }

        // The OTP was generated before attempting delivery. If mail delivery
        // fails, remove the verifier/cooldown so the user can retry immediately
        // instead of being locked out for the full OTP lifetime.
        voorodak_delete_option_transient(VOORODAK_OTP . $to);
        $message = __('مشکلی در ارسال ایمیل کد تایید پیش آمده است، مجدد تلاش کنید', 'voorodak');
        wp_send_json_error(array('message' => $this->add_message($message)));
    }

    /**
     * @param $user_id
     * @param $new_password
     * @param $new_password2
     * @return bool|void
     */
    private function update_user_password($user_id, $new_password, $new_password2)
    {
        $settings = $this->get_settings();
        $password_length = $settings['password_length'] ?? '8';
        if ($new_password !== $new_password2) {
            $message = __('رمزهای عبور مطابقت ندارند.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        } elseif (strlen($new_password) < $password_length || !preg_match('/[a-zA-Z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            /* translators: %s: Minimum password length */
            $message = sprintf(__('رمز عبور باید حداقل %s کاراکتر و شامل حروف انگلیسی و عدد باشد.', 'voorodak'), $password_length);
            wp_send_json_error(array('message' => $this->add_message($message)));
        } else {
            wp_set_password($new_password, $user_id);
            return true;
        }
    }

    /**
     * @param $user_id
     * @param $message
     * @return void
     */
    private function do_login($user_id, $message = null, $fire_login_hook = true)
    {
        if (null === $message) {
            $message = __('با موفقیت وارد شدید، لطفا صبر کنید ...', 'voorodak');
        }
        $pre = apply_filters('voorodak_pre_do_login', ['allow' => true, 'message' => $message], $user_id);
        if (empty($pre['allow']) || $pre['allow'] === false) {
            wp_send_json_error(['message' => $this->add_message($pre['message'])]);
        }
        wp_clear_auth_cookie();
        wp_set_current_user($user_id);
        $settings = $this->get_settings();
        $remember_me = !is_array($settings)
            || !array_key_exists('remember_me', $settings)
            || !empty($settings['remember_me']);
        wp_set_auth_cookie($user_id, $remember_me);
        $this->clean_rate_limit();
        if ($fire_login_hook) {
            $user = get_userdata($user_id);
            if ($user instanceof WP_User) {
                // Mirror the core wp_signon() lifecycle so security, audit and CRM
                // integrations that listen to wp_login can observe Voorodak logins.
                do_action('wp_login', $user->user_login, $user);
            }
            do_action('voorodak_after_do_login', $user_id);
        }
        wp_send_json_success(['message' => $this->add_message($message, 'success')]);
    }


    /**
     * @param $username
     * @return int|void|WP_Error
     */
    private function do_register($username, $first_name = null, $last_name = null, $email = null, $password_user = null, $national_code = null)
    {
        $settings = $this->get_settings();
        $user_field_meta  = $settings['user_field_meta'] ?? 'billing_phone';
        $user_field_meta2 = $settings['user_field_meta2'] ?? '';
        $family_name      = $settings['family_name'] ?? '';
        $email_field      = $settings['email_field'] ?? '';
        $password_field   = $settings['password_field'] ?? '';
        $disable_random_email = !empty($settings['disable_random_email']);

        if (strlen($username) < 5) {
            $message = __('نام کاربری معتبر نمی‌باشد.', 'voorodak');
            wp_send_json_error(['message' => $this->add_message($message)]);
        }

        $password       = wp_generate_password(32);
        $username_save  = $this->get_username_format_save($username);
        $length         = ($username_save[0] == '0') ? 7 : 6;
        /* translators: %s: Censored username string */
        $display_name   = sprintf(__('کاربر(%s)', 'voorodak'), str_repeat('*', 4) . substr($username_save, 0, $length));
        $domain         = parse_url(home_url(), PHP_URL_HOST);
        $domain         = $domain ? preg_replace('/^www\./', '', $domain) : 'example.com';
        $fake_email     = $disable_random_email ? '' : 'user-' . wp_generate_password(12, false, false) . '@' . $domain;
        $saved_role = voorodak_normalize_registration_role(
            $settings['default_role'] ?? voorodak_get_default_registration_role()
        );
        $userdata = [
            'user_login'   => $username_save,
            'user_pass'    => $password,
            'user_email'   => $fake_email,
            'display_name' => $display_name,
            'role'         => $saved_role,
        ];

        if ($family_name && !empty($first_name) && !empty($last_name)) {
            $userdata['display_name'] = $first_name . ' ' . $last_name;
            $userdata['first_name']   = $first_name;
            $userdata['last_name']    = $last_name;
        }

        if (!empty($email) && is_email($email)) {
            $userdata['user_email'] = sanitize_email($email);
        }

        if ($password_field && !empty($password_user)) {
            $userdata['user_pass'] = $password_user;
        }

        $user_id = wp_insert_user($userdata);

        if (is_wp_error($user_id)) {
            $message = __('خطایی در ثبت نام پیش آمده است، مجدد تلاش کنید', 'voorodak');
            wp_send_json_error(['message' => $this->add_message($message)]);
        } else {
            // Email-based registration must not populate phone metadata with an
            // email address. This also prevents WooCommerce billing_phone from
            // being polluted when email registration is enabled.
            if (preg_match('/^09\d{9}$/', (string) $username)) {
                update_user_meta($user_id, $user_field_meta, $username);

                if (!empty($user_field_meta2)) {
                    update_user_meta($user_id, $user_field_meta2, $username);
                }
            }

            if (!empty($national_code)) {
                update_user_meta($user_id, 'national_code', $national_code);
            }

            if ((function_exists('is_woocommerce'))) {
                if (!empty($first_name)) {
                    update_user_meta($user_id, 'billing_first_name', $first_name);
                }
                if (!empty($last_name)) {
                    update_user_meta($user_id, 'billing_last_name', $last_name);
                }
            }

            do_action('voorodak_after_do_register', $user_id);
            return $user_id;
        }
    }

    public function submit_username()
    {
        if (!isset($_POST['username'])) $this->invalid_request();
        $username = sanitize_text_field(wp_unslash($_POST['username']));
        $this->validate_ajax_request($username);
        $settings = $this->get_settings();
        $variable = voorodak_get_variable($settings);
        $register_allow = $settings['register_allow'] ?? '';
        $email_register = !empty($settings['email_register']);
        $username_type = $this->validate_username($username);
        if ($username_type == 'mobile') {
            $sent = false;
            $user_id = $this->get_user_id_by_mobile($username, false);
            $password_login_priority = !empty($settings['password_login_priority']);
            $otp_request = !empty($_POST['otp_request']);
            $this->validate_captcha_request(!($password_login_priority && $user_id && !$otp_request));
            if ($register_allow && !$user_id) {
                $message = __('کاربری با چنین مشخصات در سایت وجود ندارد.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }
            if ($password_login_priority && $user_id && !$otp_request) {
                wp_send_json_success(array('message' => '', 'description' => '', 'sent' => false, 'register' => false, 'password_priority' => true));
            }
            $this->check_otp_send_rate_limit($username);
            $otp = $this->generate_otp($username);
            if ($otp !== false) {
                $this->record_otp_send_attempt($username);
                $sms = $this->get_sms_service();
                $sms->to = $username;
                $sms->otp = array($variable => $otp);
                $sent = $sms->send(false,$otp,$username);
            }
            if ($user_id){
                /* translators: %s: Mobile phone number */
                $description = sprintf(__('کد تایید برای شماره %s پیامک شد', 'voorodak'), $username);
                $register_status = false;
            }else{
                /* translators: %s: Mobile phone number */
                $description = sprintf(__('حساب کاربری با شماره موبایل %s وجود ندارد. برای ساخت حساب جدید، کد تایید برای این شماره ارسال گردید.', 'voorodak'), $username);
                $register_status = true;
            }
            wp_send_json_success(array('message' => '', 'description' => $description, 'sent' => $sent, 'register' => $register_status));
        }elseif ($username_type == 'email'){
            $this->validate_captcha_request();
            $user_id = $this->get_user_id_by_email($username, false);
            if ($user_id) {
                wp_send_json_success(array('message' => ''));
            }
            if ($register_allow) {
                $message = __('کاربری با چنین مشخصات در سایت وجود ندارد و ثبت نام کاربران غیرفعال است.', 'voorodak');
                wp_send_json_error(array('message' => $this->add_message($message)));
            }
            if (!$email_register) {
                $message = __('کاربری با چنین مشخصات در سایت وجود ندارد، لطفا با شماره موبایل وارد شوید.', 'voorodak');
                wp_send_json_error(array('message' => $this->add_message($message)));
            }
            $this->check_otp_send_rate_limit($username);
            $otp = $this->generate_otp($username);
            $sent = false;
            if ($otp !== false) {
                $this->record_otp_send_attempt($username);
                $sent = $this->send_email_register_otp($username, $otp);
            }
            /* translators: %s: Email address */
            $description = sprintf(__('حساب کاربری با ایمیل %s وجود ندارد. برای ساخت حساب جدید، کد تایید برای این ایمیل ارسال گردید.', 'voorodak'), $username);
            wp_send_json_success(array('message' => '', 'description' => $description, 'sent' => $sent, 'register' => true, 'email_register' => true));
        } else {
            $this->validate_captcha_request();
            $user_id = $this->get_user_id_by_username($username);
            wp_send_json_success(array('message' => ''));
        }
    }


    public function submit_otp()
    {

        if (empty($_POST['username']) || empty($_POST['otp'])) {
            $this->invalid_request();
        }

        $username   = isset($_POST['username'])   ? sanitize_text_field(wp_unslash($_POST['username']))   : '';
        $otp        = isset($_POST['otp'])        ? sanitize_text_field(wp_unslash($_POST['otp']))        : '';
        $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
        $last_name  = isset($_POST['last_name'])  ? sanitize_text_field(wp_unslash($_POST['last_name']))  : '';
        $email      = isset($_POST['email'])      ? sanitize_text_field(wp_unslash($_POST['email']))      : '';
        $password   = isset($_POST['password']) && is_string($_POST['password']) ? wp_unslash($_POST['password']) : '';
        $national_code = isset($_POST['national_code']) ? $this->normalize_national_code(sanitize_text_field(wp_unslash($_POST['national_code']))) : '';

        $this->validate_ajax_request($username);


        $username_type = $this->validate_username($username);
        $settings = $this->get_settings();
        $email_register = !empty($settings['email_register']);
        $register_allow = !empty($settings['register_allow']);
        if ($username_type !== 'mobile' && !($username_type === 'email' && $email_register)) {
            $message = __('کد تایید برای این روش ورود معتبر نمی‌باشد.', 'voorodak');
            wp_send_json_error(['message' => $this->add_message($message)]);
        }

        $user_id = ($username_type === 'email') ? $this->get_user_id_by_email($username, false) : $this->get_user_id_by_mobile($username, false);

        if ($user_id) {
            if ($this->check_otp($username, $otp)) {
                $this->do_login($user_id);
            }
        } else {
            if ($register_allow) {
                $message = __('ثبت نام کاربران غیرفعال است.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($username_type === 'email') {
                $email = $username;
            }

            $family_name         = $settings['family_name']         ?? '';
            $family_name_force   = $settings['family_name_force']   ?? '';
            $family_name_persian_only = $settings['family_name_persian_only'] ?? '';
            $email_field         = $settings['email_field']         ?? '';
            $email_field_force   = $settings['email_field_force']   ?? '';
            $password_field      = $settings['password_field']      ?? '';
            $password_length     = $settings['password_length']     ?? '8';
            $national_code_field = $settings['national_code_field'] ?? '';
            $national_code_force = $settings['national_code_force'] ?? '';

            if ($family_name && $family_name_force && (empty($first_name) || empty($last_name))) {
                $message = __('نام و نام خانوادگی الزامی می‌باشد.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($family_name && $family_name_persian_only && (!$this->is_persian_name_value($first_name) || !$this->is_persian_name_value($last_name))) {
                $message = __('نام و نام خانوادگی باید فقط با حروف فارسی وارد شود.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($national_code_field && $national_code_force && empty($national_code)) {
                $message = __('کد ملی الزامی می‌باشد.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($national_code_field && !empty($national_code) && !$this->is_valid_national_code($national_code)) {
                $message = __('کد ملی معتبر نمی‌باشد.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($email_field && $email_field_force && empty($email)) {
                $message = __('ایمیل الزامی می‌باشد.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($email_field && !empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = __('ایمیل معتبر نمی‌باشد.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($email_field && !empty($email) && email_exists($email)) {
                $message = __('ایمیل تکراری می‌باشد.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($password_field && empty(trim($password))) {
                $message = __('رمز عبور الزامی می‌باشد.', 'voorodak');
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($password_field && !empty($password) &&
                (strlen($password) < $password_length ||
                    !preg_match('/[a-zA-Z]/', $password) ||
                    !preg_match('/[0-9]/', $password))) {
                /* translators: %s: Minimum password length */
                $message = sprintf(__('رمز عبور باید حداقل %s کاراکتر و شامل حروف و عدد باشد.', 'voorodak'), $password_length);
                wp_send_json_error(['message' => $this->add_message($message)]);
            }

            if ($this->check_otp($username, $otp)) {
                $user_id = $this->do_register($username, $first_name, $last_name, $email, $password, $national_code);
                $this->do_login($user_id, __('با موفقیت ثبت نام شدید، لطفا صبر کنید ...', 'voorodak'), false);
            }
        }
    }

    public function submit_password()
    {
        if (!isset($_POST['username']) || !isset($_POST['password'])) $this->invalid_request();
        $username = sanitize_text_field(wp_unslash($_POST['username']));
        $password = is_string($_POST['password']) ? wp_unslash($_POST['password']) : '';
        $this->validate_ajax_request($username);
        $username_type = $this->validate_username($username);
        $user_id = false;
        if ($username_type == 'email') {
            $user_id = $this->get_user_id_by_email($username);
        } elseif ($username_type == 'username') {
            $user_id = $this->get_user_id_by_username($username);
        } else {
            $user_id = $this->get_user_id_by_mobile($username);
        }
        if ($user_id && $this->check_user_password($user_id, $password, $username)) {
            $this->do_login($user_id);
        }
    }

    public function submit_forget()
    {
        if (!isset($_POST['username'])) $this->invalid_request();
        $username = sanitize_text_field(wp_unslash($_POST['username']));
        $this->validate_ajax_request($username);
        $this->validate_captcha_request();
        $settings = $this->get_settings();
        $variable = voorodak_get_variable($settings);
        $login_page_id = voorodak_get_login_page_id();
        $login_url = $login_page_id ? get_permalink($login_page_id) : home_url('/');
        $username_type = $this->validate_username($username);
        $sent = false;
        if ($username_type == 'email') {
            $user_id = $this->get_user_id_by_email($username);
            $this->send_email_reset_token($user_id, $username, $login_url);
        } elseif ($username_type == 'mobile') {
            $user_id = $this->get_user_id_by_mobile($username);
            $this->check_otp_send_rate_limit($username);
            $otp = $this->generate_otp($username);
            if ($otp !== false) {
                $this->record_otp_send_attempt($username);
                $sms = $this->get_sms_service();
                $sms->to = $username;
                $sms->otp = array($variable => $otp);
                $sent = $sms->send(false,$otp,$username);
            }
            wp_send_json_success(array('message' => '', 'sent' => $sent));
        }else{
            $message = __('لطفا شماره موبایل یا ایمیل را صحیح وارد نمایید.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        }
    }

    public function submit_otp_reset()
    {
        if (!isset($_POST['username']) || !isset($_POST['otp'])) $this->invalid_request();
        $username = sanitize_text_field(wp_unslash($_POST['username']));
        $this->validate_ajax_request($username);
        $otp = sanitize_text_field(wp_unslash($_POST['otp']));
        $username_type = $this->validate_username($username);
        if ($username_type != 'mobile') {
            $message = __('شماره موبایل صحیح نمی‌باشد.', 'voorodak');
            wp_send_json_error(array('message' => $this->add_message($message)));
        }
        $user_id = $this->get_user_id_by_mobile($username);
        $this->check_otp($username, $otp);
        $reset_token = $this->generate_reset_token_password($user_id);
        wp_send_json_success(array('message' => '', 'reset_token' => $reset_token));
    }

    public function submit_reset()
    {
        $this->validate_ajax_request();
        if (!isset($_POST['new_password']) || !isset($_POST['new_password2']) || !isset($_POST['reset_token'])) $this->invalid_request();
        $new_password = is_string($_POST['new_password']) ? wp_unslash($_POST['new_password']) : '';
        $new_password2 = is_string($_POST['new_password2']) ? wp_unslash($_POST['new_password2']) : '';
        $reset_token = sanitize_text_field(trim(wp_unslash($_POST['reset_token'])));
        $user_id = $this->get_user_id_by_reset_token($reset_token);
        if ($this->update_user_password($user_id, $new_password, $new_password2)) {
            voorodak_delete_option_transient($this->get_reset_lookup_key($reset_token));
            voorodak_delete_option_transient(VOORODAK_RESET_TOEKN . $user_id);
            voorodak_delete_option_transient(VOORODAK_SENT_EMAIL . $user_id);
            $this->do_login($user_id, __('رمز عبور تغییر کرد، در حال ورود ...', 'voorodak'));
        }
    }
}

class Voorodak_Templates
{

    use Voorodak_Options;

    public function __construct()
    {
        add_filter('template_include', array($this, 'template'));
        add_shortcode('voorodak', array($this, 'shortcode'));
        add_shortcode('voorodak_account_btn', array($this, 'shortcode_btn'));
        add_action('template_redirect', array($this, 'redirect'));
        if (function_exists('is_woocommerce')) {
            add_filter('woocommerce_logout_default_redirect_url', array($this, 'logout_wc'));
        }
        add_action('wp_logout', array($this, 'logout'));
    }

    public function logout()
    {
        $settings = $this->get_settings();
        $my_logout_url = $settings['logouturl'] ?? '';
        if (!empty($my_logout_url)) {
            wp_redirect($my_logout_url);
            exit();
        }
    }

    public function logout_wc($logout_url )
    {
        $settings = $this->get_settings();
        $my_logout_url = $settings['logouturl'] ?? '';
        if (!empty($my_logout_url)) {
            return $my_logout_url;
        }
        return $logout_url;
    }

    public function template($template)
    {
        global $wp_query;
        $login_page_id = voorodak_get_login_page_id();
        if (!empty($wp_query->queried_object_id) && absint($login_page_id) > 0 && absint($wp_query->queried_object_id) === absint($login_page_id)) {
            $new_template = plugin_dir_path(__DIR__) . 'view/html-template.php';
            if (file_exists($new_template)) {
                return $new_template;
            }
        }
        return $template;
    }

    public function shortcode()
    {
        ob_start();
        $settings = $this->get_settings();
        require plugin_dir_path(__DIR__) . 'view/html-shortcode.php';
        return ob_get_clean();
    }

    public function shortcode_btn($atts)
    {
        $settings = $this->get_settings();
        $login_page_id = voorodak_get_login_page_id();
        $panelurl_custom = $settings['panelurl_custom'] ?? '';
        $atts = shortcode_atts([
            'style' => '',
            'showname' => '',
        ], $atts);

        if (is_user_logged_in()) {
            if (strtolower($atts['showname']) === 'yes') {
                $user = wp_get_current_user();
                $text = $user->display_name;
            } else {
                $text = __('حساب کاربری', 'voorodak');
            }
        } else {
            $text = __('ورود/عضویت', 'voorodak');
        }

        if (function_exists('wc_get_page_permalink')) {
            $url = wc_get_page_permalink('myaccount');
        } else {
            $url = get_the_permalink($login_page_id);
        }

        if (is_user_logged_in() && !empty($panelurl_custom)){
            $url = $panelurl_custom;
        }

        if (strtolower($atts['style']) === 'astra') {
            return '
        <a class="ast-custom-button-link" href="' . esc_url($url) . '" target="_self">
            <div class="ast-custom-button">' . esc_html($text) . '</div>
        </a>';
        }
        return '<a class="voorodak-button button" href="' . esc_url($url) . '">' . esc_html($text) . '</a>';
    }

    public function redirect()
    {
        $settings = $this->get_settings();
        $login_page_id = voorodak_get_login_page_id();
        $panelurl_custom = $settings['panelurl_custom'] ?? '';
        $woocommerce_login = $settings['woocommerce_login'] ?? '';
        $woocommerce_checkout = $settings['woocommerce_checkout'] ?? '';
        if ($login_page_id && is_page($login_page_id) && is_user_logged_in()) {
            if (function_exists('is_woocommerce')){
                $logged_url = get_permalink(get_option('woocommerce_myaccount_page_id'));
            }else{
                $logged_url = home_url();
            }
            if (!empty($panelurl_custom)){
                $logged_url = $panelurl_custom;
            }
            wp_redirect($logged_url);
            die;
        }
        if (function_exists('is_woocommerce')) {
            if ($woocommerce_login && is_account_page() && !is_user_logged_in()) {
                wp_safe_redirect(get_the_permalink($login_page_id));
                exit;
            }
            if ($woocommerce_checkout && is_checkout() && !is_user_logged_in()) {
                wp_safe_redirect(add_query_arg('backurl', 'checkout', get_the_permalink($login_page_id)));
                exit;
            }
        }
        if ( (is_single() || is_page()) && !is_user_logged_in() ){
            global $post;
            $_lock_voorodak = get_post_meta($post->ID, '_lock_voorodak', true);
            if ($_lock_voorodak){
                wp_safe_redirect(add_query_arg('backUrl', get_the_permalink($post->ID), get_the_permalink($login_page_id)));
                exit;
            }
        }
    }

}


class Voorodak_SMS
{
    use Voorodak_Options;

    public $to;
    public $pattern_otp;
    public $otp;
    private $username;
    private $password;
    private $from;
    private $message;
    protected $method;
    protected $settings;
    protected $timeout = 20;

    public function __construct()
    {
        $this->settings = $this->get_settings();
        if ($this->settings) {
            $this->method = $this->check_gateway($this->settings['gateway'] ?? null) ?? '';
            $this->username = isset($this->settings['gateway_username']) ? (string) $this->settings['gateway_username'] : '';
            $this->password = isset($this->settings['gateway_password']) ? (string) $this->settings['gateway_password'] : '';
            $this->from = !empty($this->settings['gateway_from']) ? trim($this->settings['gateway_from']) : '';
            $this->pattern_otp = !empty($this->settings['gateway_pattern_otp']) ? trim($this->settings['gateway_pattern_otp']) : '';
            $this->message = $this->settings['gateway_message'] ?? '';
            $this->timeout = (int) ($this->settings['gateway_timeout'] ?? 20);
        }
    }

    public static function check_gateway($method_name)
    {
        $replace = [
            'melipayamak_pattern' => 'melipayamakrest_pattern',
            'farazsms_pattern' => 'farazsmsnew_pattern',
            'farazsms' => 'farazsmsnew_pattern',
        ];

        return $replace[$method_name] ?? $method_name;
    }

    /**
     * @return string[]
     */
    public static function gateways()
    {
        return [
            //'melipayamak_pattern' => 'Melipayamak.com Soap (Pattern)',
            'melipayamakrest_pattern' => 'Melipayamak.com Rest (Pattern)',
            'melipayamak' => 'Melipayamak.com',
            'farazsmsnew_pattern' => 'Farazsms.com/iranpayamak (Pattern)',
            'farapayamak_pattern' => 'Farapayamak.ir (Pattern)',
            'farapayamak' => 'Farapayamak.ir',
            'ippanelnew_pattern' => 'Ippanel.co (New Pattern)',
            'ippanel_pattern' => 'Ippanel.co (Pattern)',
            'ippanel' => 'Ippanel.co',
            //'farazsms_pattern' => 'Farazsms.com (Pattern)',
            //'farazsms' => 'Farazsms.com',
            'modirpayamak_pattern' => 'Modirpayamak.com (Pattern)',
            'modirpayamak' => 'Modirpayamak.com',
            'rangine_pattern' => 'Rangine.ir (Pattern)',
            'rangine' => 'Rangine.ir',
            'maxsms_pattern' => 'Maxsms.co (Pattern)',
            'maxsms' => 'Maxsms.co',
            'kavenegar_pattern' => 'Kavenegar.com (Pattern)',
            'smsir' => 'Sms.ir',
            'smsir_pattern' => 'Sms.ir (Pattern)',
            'payamito' => 'Payamito.com',
            'payamito_pattern' => 'Payamito.com (Pattern)',
            'panelchi_pattern' => 'Panelchi.com (Pattern)',
            'niazpardaz' => 'Niazpardaz-sms.com',
            'ghasedak' => 'Ghasedak.me',
            'ghasedak_pattern' => 'Ghasedak.me (Pattern)',
            'payamresan_pattern' => 'Payam-resan.com (Pattern)',
            'sabanovin' => 'Sabanovin.com',
            'raygansms' => 'Raygansms.com',
            'raygansms_pattern' => 'Raygansms.com (Pattern)',
            'rahpayam_pattern' => 'MSGway.com',
        ];
    }

    /**
     * Legacy providers that still use an unencrypted HTTP transport in this
     * compatibility implementation. They remain available for backward
     * compatibility, but administrators should prefer a modern HTTPS provider.
     *
     * @return string[]
     */
    public static function insecure_transport_gateways()
    {
        return [
            'ippanel_pattern',
            'ippanel',
            'rangine_pattern',
            'rangine',
            'maxsms_pattern',
            'maxsms',
            'farapayamak_pattern',
            'farapayamak',
            'kavenegar_pattern',
            'payamito',
            'payamito_pattern',
            'niazpardaz',
            'payamresan_pattern',
            'raygansms',
        ];
    }

    public static function gateway_uses_insecure_transport($gateway)
    {
        $gateway = self::check_gateway(sanitize_key((string) $gateway));

        return in_array($gateway, self::insecure_transport_gateways(), true);
    }

    public function send($skip_error = false, $otp = false, $phone = false)
    {
        $method = $this->method;
        $username = $this->username;
        if (!empty($phone) && empty($this->to)) {
            $this->to = $phone;
        }

        if (empty($method) || empty($username)){
            return $this->send_error('سامانه پیامکی یا اطلاعات اتصال آن تنظیم نشده است.', $skip_error, $phone);
        }

        if (!$this->is_valid_gateway_method($method) || !method_exists($this, $method) || !is_callable([$this, $method])) {
            return $this->send_error('درگاه پیامکی انتخاب‌شده معتبر نیست.', $skip_error, $phone);
        }

        if (!is_array($this->otp)) {
            $this->otp = [];
        }

        if (!empty($this->message)) {
            foreach ($this->otp as $key => $value) {
                $this->message = str_replace('%' . $key . '%', $value, $this->message);
            }
        }

        if ($this->check_admin()){
            return $this->call_gateway($method);
        }else{
            $response = $this->call_gateway($method);
            $baleotp = $this->settings['baleotp'] ?? '';
            $send_bale = false;
            if ($baleotp && $otp){
                $send_bale = $this->bale_send('otp',$phone,$otp);
            }
            if ($response || $send_bale) {
                return true;
            }else{
                if (!$skip_error) {
                    return $this->send_error('مشکلی در ارسال پیامک از طرف سامانه وجود دارد.', $skip_error, $phone, false);
                }
            }
        }
        return false;
    }

    private function call_gateway($method)
    {
        try {
            return $this->$method();
        } catch (Throwable $e) {
            return $this->gateway_exception_response($e);
        }
    }

    private function is_valid_gateway_method($method)
    {
        $gateway_methods = array_map([self::class, 'check_gateway'], array_keys(self::gateways()));

        return in_array($method, array_unique($gateway_methods), true);
    }

    private function send_error($message, $skip_error = false, $phone = false, $save_log = true)
    {
        if ($save_log) {
            $this->log_save($message);
        }

        if (!empty($phone)) {
            voorodak_delete_option_transient(VOORODAK_OTP . $phone);
        }

        if ($skip_error) {
            return false;
        }

        if ($this->check_admin()) {
            return $message;
        }

        wp_send_json_error(array('message' => $this->add_message($message)));
    }

    public function bale_send($type, $to, $message)
    {
        try {
            if ($type === 'text' || $type === 'otp') {

                $to = preg_replace('/\D+/', '', $to);
                if (strpos($to, '98') !== 0) {
                    $to = '98' . ltrim($to, '0');
                }

                $body = [
                    'bot_id'       => intval($this->settings['balebotid'] ?? ''),
                    'phone_number' => $to,
                    'message_data' => []
                ];

                if ($type === 'text') {
                    $body['message_data']['message'] = [
                        'text' => $message
                    ];
                }

                if ($type === 'otp') {
                    $body['message_data']['otp_message'] = [
                        'otp' => $message
                    ];
                }

                $response = $this->remote_request('https://safir.bale.ai/api/v3/send_message', [
                    'headers' => [
                        'api-access-key' => $this->settings['baleapi'] ?? '',
                        'Content-Type'   => 'application/json'
                    ],
                    'body'    => $this->encode_gateway_body($body),
                    'timeout' => 20
                ]);
            }

            if ($type === 'bot') {

                $body = [
                    'chat_id' => $to,
                    'text'    => $message
                ];
                $bot_token = (string) ($this->settings['balebottoken'] ?? '');
                $response = $this->remote_request('https://tapi.bale.ai/bot' . $bot_token . '/sendMessage', [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'body'        => $this->encode_gateway_body($body),
                    'timeout'     => 20,
                    'data_format' => 'body'
                ]);
            }
        } catch (Exception $e) {
            return false;
        }

        if (empty($response) || is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_response_code($response) == 200;
    }

    public function log_save($log_entry)
    {
        $option_name = 'voorodak_log';
        $logs = get_option($option_name, []);

        if (!is_array($logs)) {
            $logs = [];
        }
        unset($logs['_last_fingerprint'], $logs['_last_fingerprint_time']);

        if (is_array($log_entry) || is_object($log_entry)) {
            $log_entry = wp_json_encode($log_entry, JSON_UNESCAPED_UNICODE);
        }

        $log_entry = $this->redact_sensitive_log((string) $log_entry);
        $log_entry = trim(wp_strip_all_tags($log_entry));

        $log_entry .= ' | ' . wp_date('j F Y H:i:s');

        array_unshift($logs, $log_entry);
        $logs = array_slice($logs, 0, 10);

        update_option($option_name, $logs, false);
    }

    private function redact_sensitive_log($log_entry)
    {
        $log_entry = (string) $log_entry;

        foreach ([$this->password, $this->username] as $secret) {
            $secret = (string) $secret;
            if ($secret !== '' && strlen($secret) >= 4) {
                $log_entry = str_replace($secret, '[REDACTED]', $log_entry);
                $log_entry = str_replace(rawurlencode($secret), '[REDACTED]', $log_entry);
                $log_entry = str_replace(urlencode($secret), '[REDACTED]', $log_entry);
            }
        }

        foreach ((array) $this->otp as $otp_value) {
            $otp_value = (string) $otp_value;
            if ($otp_value !== '') {
                $log_entry = str_replace($otp_value, '[OTP]', $log_entry);
            }
        }

        $patterns = [
            '/([?&](?:api[_-]?key|token|password|pass|secret|authorization)=)[^&\s]+/i',
            '/("(?:api[_-]?key|token|password|pass|secret|authorization)"\s*:\s*")[^"]+(")/i',
        ];

        $log_entry = preg_replace($patterns[0], '$1[REDACTED]', $log_entry);
        $log_entry = preg_replace($patterns[1], '$1[REDACTED]$2', $log_entry);

        return is_string($log_entry) ? $log_entry : '';
    }

    private function encode_gateway_body($body)
    {
        return wp_json_encode($body, JSON_UNESCAPED_UNICODE);
    }

    private function remote_request($url, $args = [], $method = 'POST')
    {
        $args = array_merge([
            'method'  => $method,
            'timeout' => $this->timeout,
        ], $args);

        $remote = wp_remote_request($url, $args);

        if (is_wp_error($remote)) {
            throw new Exception($remote->get_error_message());
        }

        return $remote;
    }

    private function remote_body($url, $args = [], $method = 'POST')
    {
        return wp_remote_retrieve_body($this->remote_request($url, $args, $method));
    }

    private function decode_gateway_json($body)
    {
        $data = json_decode($body, true);

        return is_array($data) ? $data : [];
    }

    private function soap_client($wsdl)
    {
        if (!class_exists('SoapClient')) {
            throw new RuntimeException('افزونه SOAP در PHP فعال نیست. برای استفاده از این درگاه پیامکی، SOAP را روی هاست فعال کنید.');
        }

        return new SoapClient($wsdl, [
            'encoding'           => 'UTF-8',
            'cache_wsdl'         => defined('WSDL_CACHE_NONE') ? WSDL_CACHE_NONE : 0,
            'connection_timeout' => $this->timeout,
        ]);
    }

    private function gateway_response($result, $final_response, $log_entry = null)
    {
        $this->log_save($log_entry ?? $result);

        return $this->check_admin() ? $result : $final_response;
    }

    private function gateway_exception_response($e)
    {
        $result = $e->getMessage();
        $this->log_save($result);

        return $this->check_admin() ? $result : false;
    }

    /**
     * @return bool|void
     */
    public function ippanelnew_pattern()
    {

        try {
            $body = [
                'sending_type' => 'pattern',
                'code' => $this->pattern_otp,
                'from_number' => $this->from,
                'recipients' => array($this->to),
                'params' => $this->otp
            ];

            $response = $this->remote_body('https://edge.ippanel.com/v1/api/send', [
                'body'    => $this->encode_gateway_body($body),
                'headers' => [
                    'accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'Authorization' => $this->username
                ],
            ]);

            $responseData = $this->decode_gateway_json($response);
            $finalResponse = (!empty($responseData['meta']['status']) && $responseData['meta']['status'] === true);

        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }

        return $this->gateway_response($response, $finalResponse, $response);
    }


    /**
     * @return bool|void
     */
    public function ippanel_pattern()
    {
        try {
            $client = $this->soap_client("http://ippanel.com/class/sms/wsdlservice/server.php?wsdl");
            $user = $this->username;
            $pass = $this->password;
            $fromNum = $this->from;
            $toNum = array($this->to);
            $pattern_otp = $this->pattern_otp;
            $input_data = $this->otp;
            $response = $client->sendPatternSms($fromNum, $toNum, $user, $pass, $pattern_otp, $input_data);
            $finalResponse = (strlen($response) > 7);
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($response, $finalResponse, $response);
    }

    /**
     * @return bool|string
     */
    public function ippanel()
    {
        try {
            $client = $this->soap_client("http://ippanel.com/class/sms/wsdlservice/server.php?wsdl");
            $user = $this->username;
            $pass = $this->password;
            $fromNum = $this->from;
            $toNum = [$this->to];
            $messageContent = $this->message;
            $op = "send";
            $time = '';
            $response = $client->SendSMS($fromNum, $toNum, $messageContent, $user, $pass, $time, $op);
            $finalResponse = (strlen($response) > 7);
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($response, $finalResponse, $response);
    }

    /**
     * @return bool|string
     */
    public function farazsmsnew_pattern()
    {
        try {
            $body = [
                'code' => $this->pattern_otp,
                'attributes' => $this->otp,
                //'attributes' => array_change_key_case((array) $this->otp, CASE_UPPER),
                'recipient' => $this->to,
                'line_number' => $this->from,
                'number_format' => 'english'
            ];

            $response = $this->remote_body('https://api.iranpayamak.com/ws/v1/sms/pattern', [
                'body' => $this->encode_gateway_body($body),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Api-Key' => $this->username
                ],
            ]);

            $data = $this->decode_gateway_json($response);
            $finalResponse = (($data['status'] ?? '') === 'success');
            $result = '';
            if (!empty($data['message'])) {
                if (is_array($data['message'])) {
                    $messages = [];
                    foreach ($data['message'] as $field => $msgs) {
                        foreach ((array) $msgs as $msg) {
                            $messages[] = $msg;
                        }
                    }
                    $result = trim(implode(', ', $messages));
                } else {
                    $result = $data['message'];
                }
            }
            if ($finalResponse) {
                $result = 'پیام با موفقیت ارسال شد';
            }
        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }

        return $this->gateway_response($result, $finalResponse);
    }

    /**
     * @return bool|void
     */

    public function farapayamakrest_pattern()
    {
        try {


            $response = $this->remote_body('https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber', [
                'body' => $this->encode_gateway_body([
                    "username" => $this->username,
                    "password" => $this->password,
                    "to" => $this->to,
                    "text" => implode(';', array_values($this->otp)),
                    "bodyId" => $this->pattern_otp,
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
            $messages = voorodak_farapyamak_messages();
            $data = $this->decode_gateway_json($response);
            $finalResponse = isset($data['Value']) && (($data['Value'] > 15 || $data['Value'] == 7));
            if($finalResponse){
                $result = $messages[1];
            }else{
                $result = $messages[$data['Value']] ?? $response;
            }
        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse);
    }

    /**
     * @return bool|string
     */
    public function farapayamak_pattern()
    {
        try {
            $sms = $this->soap_client("http://api.payamak-panel.com/post/Send.asmx?wsdl");
            $data = [
                "username" => $this->username,
                "password" => $this->password,
                "to"       => $this->to,
                "text"     => array_values($this->otp),
                "bodyId"   => $this->pattern_otp,
                "isflash"  => false
            ];
            $response = $sms->SendByBaseNumber($data)->SendByBaseNumberResult;
            $messages = voorodak_farapyamak_messages();
            $finalResponse = ($response > 20 || $response == 7);
            $result = $finalResponse ? $messages[1] : ($messages[$response] ?? $response ?? 'خطای ناشناخته، بخش لاگ را بررسی نمایید');
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse);
    }

    /**
     * @return bool|string
     */
    public function farapayamak()
    {
        try {
            $sms = $this->soap_client("http://api.payamak-panel.com/post/Send.asmx?wsdl");
            $data = [
                "username" => $this->username,
                "password" => $this->password,
                "to"       => $this->to,
                "from"     => $this->from,
                "text"     => $this->message,
                "isflash"  => false
            ];
            $response = $sms->SendSimpleSMS2($data)->SendSimpleSMS2Result;
            $messages = voorodak_farapyamak_messages();
            $finalResponse = ($response > 20 || $response == 7);
            $result = $finalResponse ? $messages[1] : ($messages[$response] ?? $response ?? 'خطای ناشناخته، بخش لاگ را بررسی نمایید');
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse);
    }


    /**
     * @return bool|void
     */
    public function melipayamakrest_pattern()
    {
        return $this->farapayamakrest_pattern();
    }

    /**
     * @return bool|string
     */
    public function melipayamak()
    {
        return $this->farapayamak();
    }


    /**
     * @return bool|void
     */
    public function modirpayamak_pattern()
    {
        return $this->ippanel_pattern();
    }

    /**
     * @return bool|void
     */
    public function modirpayamak()
    {
        return $this->ippanel();
    }

    /**
     * @return bool|void
     */
    public function rangine_pattern()
    {
        return $this->ippanel_pattern();
    }

    /**
     * @return bool|void
     */
    public function rangine()
    {
        return $this->ippanel();
    }

    /**
     * @return bool|void
     */
    public function maxsms_pattern()
    {
        return $this->ippanel_pattern();
    }

    /**
     * @return bool|void
     */
    public function maxsms()
    {
        return $this->ippanel();
    }

    /**
     * @return bool|string
     */
    public function kavenegar_pattern()
    {
        try {
            $url  = "http://api.kavenegar.com/v1/{$this->username}/verify/lookup.json";
            $url .= "?receptor={$this->to}&template={$this->pattern_otp}";
            $i = 1;
            foreach ($this->otp as $val) {
                if ($i > 20) break;
                $tokenKey = ($i === 1) ? "token" : "token{$i}";
                $url .= "&{$tokenKey}=" . urlencode($val);
                $i++;
            }
            $response = $this->remote_body($url, [], 'GET');
            $data = $this->decode_gateway_json($response);
            $finalResponse = (!empty($data['return']['status']) && $data['return']['status'] == 200);
            $result = $data['return']['message'] ?? 'خطای ناشناخته، بخش لاگ را بررسی نمایید';
        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse);
    }


    /**
     * @return bool|string
     */
    public function smsir()
    {
        try {
            $url = "https://api.sms.ir/v1/send?username={$this->username}"
                . "&password={$this->password}"
                . "&line={$this->from}"
                . "&mobile={$this->to}"
                . "&text=" . urlencode($this->message);
            $response = $this->remote_body($url, [], 'GET');
            $data = $this->decode_gateway_json($response);
            $finalResponse = (($data['status'] ?? null) == 1);
            $result = $data['message'] ?? 'خطای ناشناخته، بخش لاگ را بررسی نمایید';
        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse);
    }


    /**
     * @return bool|string
     */
    public function smsir_pattern()
    {
        try {
            $parameters = [];
            foreach ($this->otp as $key => $val) {
                $parameters[] = [
                    'name' => $key,
                    'value' => $val
                ];
            }
            $response = $this->remote_body('https://api.sms.ir/v1/send/verify', [
                'body' => $this->encode_gateway_body([
                    'mobile' => $this->to,
                    'templateId' => $this->pattern_otp,
                    'parameters' => $parameters
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'text/plain',
                    'x-api-key' => $this->username
                ],
            ]);
            $data = $this->decode_gateway_json($response);
            $finalResponse = (($data['status'] ?? null) == 1);
            $result = $data['message'] ?? 'خطای ناشناخته، بخش لاگ را بررسی نمایید';
        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse);
    }


    /**
     * @return bool|string
     */
    public function payamito()
    {
        try {
            $client = $this->soap_client("http://api.payamak-panel.com/post/Send.asmx?wsdl");
            $args = [
                "username" => $this->username,
                "password" => $this->password,
                "from" => $this->from,
                "to" => $this->to,
                "text" => $this->message,
                "isflash" => false,
            ];
            $response = $client->SendSimpleSMS2($args)->SendSimpleSMS2Result;
            $finalResponse = (strlen($response) > 7);
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }

        return $this->gateway_response($response, $finalResponse, $response);
    }


    /**
     * @return bool|string
     */
    public function payamito_pattern()
    {
        try {
            $client = $this->soap_client("http://api.payamak-panel.com/post/Send.asmx?wsdl");
            $args = [
                "username" => $this->username,
                "password" => $this->password,
                "to" => $this->to,
                "text" => array_values($this->otp),
                "bodyId" => $this->pattern_otp,
            ];
            $response = $client->SendByBaseNumber($args)->SendByBaseNumberResult;
            $finalResponse = (strlen($response) > 7);
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($response, $finalResponse, $response);
    }


    /**
     * @return bool|string
     */
    public function panelchi_pattern()
    {
        try {
            $url = 'https://api.panelchi.com/sms/pattern';

            $body = [
                'pattern' => $this->pattern_otp,
                'recipient' => $this->to,
                'variables' => $this->otp,
                'sourceNumber' => $this->from,
            ];

            $response_body = $this->remote_body($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization'       => 'Bearer ' . $this->username,
                ],
                'body' => $this->encode_gateway_body($body),
            ]);
            $data = $this->decode_gateway_json($response_body);
            $finalResponse = ($data['data']['status'] ?? null) === 'CREATED';
            $result = $finalResponse ? 'با موفقیت ارسال شد.' : ($data['detail'] ?? 'خطای ناشناخته');
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse);
    }

    /**
     * @return bool|mixed|string
     */

    public function niazpardaz()
    {
        try {
            $sms = $this->soap_client("http://payamak-service.ir/SendService.svc?wsdl");
            $data = [
                "userName" => $this->username,
                "password" => $this->password,
                "toNumbers" => $this->to,
                "fromNumber" => $this->from,
                "messageContent" => $this->message,
                "isflash" => false
            ];
            $response = $sms->SendSMS($data)->SendSMSResult;
            $finalResponse = ($response == '0');
            $result = $finalResponse ? 'با موفقیت ارسال شد.' : ($response ?? 'خطای ناشناخته');
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse);
    }

    /**
     * @return bool|mixed|string
     */

    public function ghasedak()
    {
        try {
            $url = 'https://gateway.ghasedak.me/rest/api/v1/webservice/sendsinglesms';

            $body = [
                'message' => $this->message,
                'receptor' => $this->to,
            ];

            $response_body = $this->remote_body($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'ApiKey'       => $this->username,
                ],
                'body' => $this->encode_gateway_body($body),
            ]);
            $messages = voorodak_ghasedak_messages();
            $data = $this->decode_gateway_json($response_body);
            $statusCode = $data['statusCode'] ?? 500;
            $finalResponse = !empty($data['isSuccess']) && $data['isSuccess'] === true;
            $result = $finalResponse ? $messages[200] : ($messages[$statusCode] ?? $data['message'] ?? 'خطای ناشناخته، بخش لاگ را بررسی نمایید');
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse, $response_body ?? $result);
    }


    /**
     * @return bool|string
     */
    public function ghasedak_pattern()
    {
        try {
            $url = 'https://gateway.ghasedak.me/rest/api/v1/WebService/SendOtpSMS';


            $inputs = [];
            foreach ($this->otp as $key => $value) {
                $inputs[] = [
                    'param' => $key,
                    'value' => $value
                ];
            }

            $body = [
                'templateName' => $this->pattern_otp,
                'receptors' => [
                    [
                        'mobile' => $this->to,
                        'clientReferenceId' => '1'
                    ]
                ],
                'inputs' => $inputs,
                'udh' => true
            ];

            $response_body = $this->remote_body($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'ApiKey'       => $this->username,
                ],
                'body' => $this->encode_gateway_body($body),
            ]);
            $messages = voorodak_ghasedak_messages();
            $data = $this->decode_gateway_json($response_body);
            $statusCode = $data['statusCode'] ?? 500;
            $finalResponse = !empty($data['isSuccess']) && $data['isSuccess'] === true;
            $result = $finalResponse ? $messages[200] : ($messages[$statusCode] ?? $data['message'] ?? 'خطای ناشناخته، بخش لاگ را بررسی نمایید');
        } catch (SoapFault|Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse, $response_body ?? $result);
    }


    /**
     * @return bool|string
     */
    public function payamresan_pattern()
    {
        try {
            $url = 'http://api.sms-webservice.com/api/V3/SendTokenSingle';
            $body = [
                'ApiKey'      => $this->username,
                'TemplateKey' => $this->pattern_otp,
                'Destination' => $this->to,
            ];
            foreach (array_values($this->otp) as $index => $value) {
                $body['P' . ($index + 1)] = $value;
            }
            $body = $this->remote_body($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $this->encode_gateway_body($body),
            ]);
            $data = $this->decode_gateway_json($body);
            if (!empty($data['Success']) && $data['Success'] === true) {
                $result = 'پیامک با موفقیت ارسال شد';
                $finalResponse = true;
            } else {
                $result = $data['Error'] ?? 'خطای ناشناخته';
                $finalResponse = false;
            }
        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }
        return $this->gateway_response($result, $finalResponse, $body ?? $result);
    }

    /**
     * @return bool|string
     */
    public function rahpayam_pattern()
    {
        try {

            $url = add_query_arg([
                'apiKey'     => $this->username,
                'mobile'     => $this->to,
                'method'     => 'sms',
                'templateID' => $this->pattern_otp,
            ], 'https://api.msgway.com/send');

            foreach (array_values($this->otp) as $param) {
                $url .= '&params[]=' . rawurlencode($param);
            }

            $response = $this->remote_request($url, [], 'GET');
            $status_code = wp_remote_retrieve_response_code($response);
            $body        = wp_remote_retrieve_body($response);

            $data = $this->decode_gateway_json($body);

            if ($status_code === 200 && !empty($data['status']) && $data['status'] === 'success') {
                $result = $data['referenceID'] ?? '';
                $finalResponse = true;
            } else {
                $result = $data['error']['message'] ?? 'خطای ناشناخته';
                $finalResponse = false;
            }

        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }

        return $this->gateway_response($result, $finalResponse, $body ?? $result);
    }
    /**
     * @return bool|string
     */
    public function sabanovin()
    {
        try {
            $params = [
                'gateway' => $this->from,
                'to' => $this->to,
                'text' => $this->message,
            ];
            $url = 'https://api.sabanovin.com/v1/' . $this->username . '/sms/send.json?' . http_build_query($params);

            $response = $this->remote_body($url, [], 'GET');
            $responseData = $this->decode_gateway_json($response);
            $finalResponse = (!empty($responseData['status']['code']) && $responseData['status']['code'] == 200);

        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }

        return $this->gateway_response($response, $finalResponse, $response);
    }

    /**
     * @return bool|string
     */
    public function raygansms()
    {
        try {
            if (empty($this->username) || empty($this->password)) {
                return false;
            }
            $data = [
                'Smsclass' => 1,
                'Username' => $this->username,
                'Password' => $this->password,
                'RecNumber' => is_array($this->to) ? implode('-', $this->to) : $this->to,
                'PhoneNumber' => $this->from,
                'MessageBody' => $this->message,
            ];

            $url = 'http://smspanel.trez.ir/SendGroupMessageWithUrl.ashx?' . http_build_query($data);
            $response = $this->remote_body($url, [], 'GET');
            $finalResponse = (!empty($response) && $response >= 2000);

        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }

        return $this->gateway_response($response, $finalResponse, $response);
    }

    /**
     * @return bool|string
     */
    public function raygansms_pattern()
    {
        try {
            $tokens = [];
            $i = 1;
            foreach ($this->otp as $val) {
                if ($i > 9) break;
                $tokens["token{$i}"] = $val;
                $i++;
            }

            $data = array_merge([
                'accessHash' => $this->username,
                'PhoneNumber' => $this->from,
                'PatternId' => $this->pattern_otp,
                'RecNumber' => $this->to,
                'Smsclass' => 1
            ], $tokens);

            $url = 'https://smspanel.trez.ir/SendPatternWithUrl.ashx?' . http_build_query($data);
            $response = $this->remote_body($url, [], 'GET');
            $finalResponse = (!empty($response) && $response >= 2000);

        } catch (Exception $e) {
            return $this->gateway_exception_response($e);
        }

        return $this->gateway_response($response, $finalResponse, $response);
    }


}

$voorodak_base = new Voorodak_Base();
$voorodak_templates = new Voorodak_Templates();
$voorodak_auth = new Voorodak_Auth();

