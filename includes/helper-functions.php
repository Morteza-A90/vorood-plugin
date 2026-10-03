<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;

function voorodak_csv_safe_cell($value)
{
    $value = (string) $value;

    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        return "'" . $value;
    }

    return $value;
}

add_action('wp_ajax_get_users_list_voorodak', function() {
    check_ajax_referer('voorodak_admin', 'security');
    if (!current_user_can('manage_options')) {
        wp_send_json_error('دسترسی غیرمجاز');
    }

    $handle = fopen('php://temp', 'r+');
    if (!$handle) {
        wp_send_json_error('امکان ایجاد خروجی وجود ندارد.');
    }

    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, ['نام کاربری', 'نام و نام خانوادگی', 'شماره تلفن', 'تاریخ عضویت']);

    $page = 1;
    $per_page = 500;

    do {
        $users = get_users([
            'fields' => ['ID', 'user_login', 'user_registered'],
            'number' => $per_page,
            'paged'  => $page,
        ]);

        $user_ids = array_map('absint', wp_list_pluck($users, 'ID'));
        if ($user_ids) {
            update_meta_cache('user', $user_ids);
        }

        foreach ($users as $user) {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            $billing_phone = get_user_meta($user->ID, 'billing_phone', true);
            $registered = date_i18n('Y-m-d H:i', strtotime($user->user_registered));
            $full_name = trim(($first_name ? $first_name . ' ' : '') . $last_name);

            fputcsv($handle, array_map('voorodak_csv_safe_cell', [
                $user->user_login,
                $full_name,
                $billing_phone,
                $registered,
            ]));
        }

        $page++;
    } while (count($users) === $per_page);

    rewind($handle);
    $data = stream_get_contents($handle);
    fclose($handle);

    wp_send_json_success($data);
});

/**
 * Stream the users CSV directly to the browser instead of serializing the
 * entire file inside an AJAX/JSON response. This keeps peak memory usage
 * bounded on sites with large user tables.
 */
function voorodak_export_users_csv()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('دسترسی غیرمجاز', 'voorodak'), '', ['response' => 403]);
    }

    check_admin_referer('voorodak_export_users_csv');

    if (headers_sent()) {
        wp_die(esc_html__('امکان ایجاد فایل خروجی وجود ندارد.', 'voorodak'));
    }

    nocache_headers();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="voorodak-users-' . gmdate('Y-m-d') . '.csv"');
    header('X-Content-Type-Options: nosniff');

    $handle = fopen('php://output', 'w');
    if (!$handle) {
        wp_die(esc_html__('امکان ایجاد فایل خروجی وجود ندارد.', 'voorodak'));
    }

    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, ['نام کاربری', 'نام و نام خانوادگی', 'شماره تلفن', 'تاریخ عضویت']);

    $page = 1;
    $per_page = 500;

    do {
        $users = get_users([
            'fields' => ['ID', 'user_login', 'user_registered'],
            'number' => $per_page,
            'paged'  => $page,
        ]);

        $user_ids = array_map('absint', wp_list_pluck($users, 'ID'));
        if ($user_ids) {
            update_meta_cache('user', $user_ids);
        }

        foreach ($users as $user) {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            $billing_phone = get_user_meta($user->ID, 'billing_phone', true);
            $registered = date_i18n('Y-m-d H:i', strtotime($user->user_registered));
            $full_name = trim(($first_name ? $first_name . ' ' : '') . $last_name);

            fputcsv($handle, array_map('voorodak_csv_safe_cell', [
                $user->user_login,
                $full_name,
                $billing_phone,
                $registered,
            ]));
        }

        if (function_exists('flush')) {
            flush();
        }

        $page++;
    } while (count($users) === $per_page);

    fclose($handle);
    exit;
}
add_action('admin_post_voorodak_export_users_csv', 'voorodak_export_users_csv');


function add_lock_voorodak_meta_box() {
    global $post;
    if (empty($post) || !isset($post->ID)) {
        return;
    }
    $post_id = $post->ID;
    $voorodak_options = get_option(VOORODAK_OPTION);
    $illegals = [];
    if (is_array($voorodak_options) && !empty($voorodak_options['login_page_id'])) {
        $illegals[] = $voorodak_options['login_page_id'];
    }
    if (function_exists('is_woocommerce')){
        $illegals[] = wc_get_page_id('myaccount');
        $illegals[] = wc_get_page_id('checkout');
    }
    if (in_array($post_id, $illegals)) {
        return;
    }
    add_meta_box(
        'lock_voorodak_meta_box',
        'ورودک',
        'render_lock_voorodak_meta_box',
        ['post', 'page', 'product'],
        'side',
        'default'
    );
}

function render_lock_voorodak_meta_box($post) {
    $value = get_post_meta($post->ID, '_lock_voorodak', true);
    wp_nonce_field('save_lock_voorodak_meta_box', 'lock_voorodak_nonce');
    ?>
    <label for="lock_voorodak" style="margin-top: 10px;display: inline-block;">
        <input type="checkbox" name="lock_voorodak" id="lock_voorodak" value="1" <?php checked($value, '1'); ?> />
        <b>قفل کردن صفحه</b>
    </label>
    <div style="font-size: 12px;margin-top: 5px">با فعالسازی این گزینه، کاربران برای مشاهده این صفحه ابتدا باید در سایت ورود کنند، سپس به این صفحه بازمیگردن</div>
    <?php
}

function save_lock_voorodak_meta_box($post_id) {
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id)) {
        return;
    }

    $nonce = isset($_POST['lock_voorodak_nonce']) ? sanitize_text_field(wp_unslash($_POST['lock_voorodak_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'save_lock_voorodak_meta_box')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['lock_voorodak'])) {
        update_post_meta($post_id, '_lock_voorodak', '1');
    } else {
        delete_post_meta($post_id, '_lock_voorodak');
    }
}

add_action('add_meta_boxes', 'add_lock_voorodak_meta_box');
add_action('save_post', 'save_lock_voorodak_meta_box');

function voorodak_banned_field($user) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $banned = get_user_meta($user->ID, 'voorodak_banned', true);
    ?>
    <h2>تنظیمات ورودک</h2>
    <?php wp_nonce_field('voorodak_save_banned_field', 'voorodak_banned_nonce'); ?>
    <table class="form-table">
        <tr>
            <th><label for="voorodak_banned">مسدود کردن کاربر</label></th>
            <td>
                <input type="checkbox" name="voorodak_banned" id="voorodak_banned" value="1" <?php checked($banned, 1); ?>>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'voorodak_banned_field');
add_action('edit_user_profile', 'voorodak_banned_field');

function voorodak_save_banned_field($user_id) {
    if (!current_user_can('manage_options') || !current_user_can('edit_user', $user_id)) {
        return;
    }
    $nonce = isset($_POST['voorodak_banned_nonce']) ? sanitize_text_field(wp_unslash($_POST['voorodak_banned_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'voorodak_save_banned_field')) {
        return;
    }
    $banned = isset($_POST['voorodak_banned']) ? 1 : 0;
    update_user_meta($user_id, 'voorodak_banned', $banned);
}
add_action('personal_options_update', 'voorodak_save_banned_field');
add_action('edit_user_profile_update', 'voorodak_save_banned_field');

function voorodak_is_woocommerce_active()
{
    return class_exists('WooCommerce') || function_exists('WC') || function_exists('is_woocommerce');
}

function voorodak_set_default_woocommerce_options()
{
    if (!voorodak_is_woocommerce_active()) {
        return;
    }

    if (get_option('voorodak_woocommerce_defaults_initialized') === '1') {
        return;
    }

    $settings = get_option(VOORODAK_OPTION);

    if (!is_array($settings)) {
        $settings = [];
    }

    if (!array_key_exists('woocommerce_login', $settings)) {
        $settings['woocommerce_login'] = '1';
    }

    if (!array_key_exists('woocommerce_checkout', $settings)) {
        $settings['woocommerce_checkout'] = '1';
    }

    update_option(VOORODAK_OPTION, $settings);
    update_option('voorodak_woocommerce_defaults_initialized', '1', false);
}

add_action('admin_init', 'voorodak_set_default_woocommerce_options', 5);

function voorodak_get_login_page_id()
{
    $settings = get_option(VOORODAK_OPTION);

    if (!is_array($settings) || empty($settings['login_page_id'])) {
        return 0;
    }

    $login_page_id = absint($settings['login_page_id']);

    return (int) apply_filters('wpml_object_id', $login_page_id, 'page', true);
}

function voorodak_is_login_register_page()
{
    $login_page_id = voorodak_get_login_page_id();

    return $login_page_id && is_page($login_page_id);
}

function voorodak_prevent_login_page_cache()
{
    if (!voorodak_is_login_register_page()) {
        return;
    }

    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }

    if (!defined('DONOTCACHEOBJECT')) {
        define('DONOTCACHEOBJECT', true);
    }

    if (!defined('DONOTMINIFY')) {
        define('DONOTMINIFY', true);
    }

    if (has_action('litespeed_control_set_nocache')) {
        do_action('litespeed_control_set_nocache', 'voorodak_login_register_page');
    }

    nocache_headers();
}

function voorodak_add_login_page_to_rocket_reject_uri($urls)
{
    $login_page_id = voorodak_get_login_page_id();

    if (!$login_page_id) {
        return $urls;
    }

    $path = wp_parse_url(get_permalink($login_page_id), PHP_URL_PATH);

    if (empty($path)) {
        return $urls;
    }

    $urls[] = trailingslashit($path);
    $urls[] = untrailingslashit($path) . '/?$';

    return array_values(array_unique(array_filter($urls)));
}

add_action('template_redirect', 'voorodak_prevent_login_page_cache', 0);
add_filter('rocket_cache_reject_uri', 'voorodak_add_login_page_to_rocket_reject_uri');


add_filter('voorodak_pre_do_login', function($data, $user_id) {
    $banned = get_user_meta($user_id, 'voorodak_banned', true);
    if ($banned){
        return [
            'allow' => false,
            'message' => 'این حساب کاربری توسط مدیریت مسدود شده و امکان ورود ندارد.'
        ];
    }
    return $data;
}, 10, 2);

add_filter('voorodak_pre_do_login', function ($data, $user_id) {
    $settings = get_option(VOORODAK_OPTION, []);
    if (empty($settings['disable_admin_login'])) {
        return $data;
    }

    $user = get_userdata($user_id);
    if ($user instanceof WP_User && in_array('administrator', (array) $user->roles, true)) {
        return [
            'allow' => false,
            'message' => 'ورود مدیران از فرم ورودک غیرفعال است. لطفاً از صفحه ورود وردپرس استفاده کنید.',
        ];
    }

    return $data;
}, 15, 2);

// Keep the ban meaningful outside Voorodak's custom AJAX flow as well. This
// covers wp-login.php, WooCommerce's native password login and other code paths
// that use WordPress' standard authentication pipeline.
add_filter('wp_authenticate_user', function ($user, $password) {
    if (!($user instanceof WP_User)) {
        return $user;
    }

    if (get_user_meta($user->ID, 'voorodak_banned', true)) {
        return new WP_Error(
            'voorodak_user_banned',
            __('این حساب کاربری توسط مدیریت مسدود شده و امکان ورود ندارد.', 'voorodak')
        );
    }

    return $user;
}, 20, 2);


function voorodak_log_display() {
    $logs = get_option('voorodak_log', []);

    if (empty($logs)) {
        echo '<p>هیچ لاگی ثبت نشده است.</p>';
        return;
    }

    echo '<ul style="list-style:none; padding:0;margin: 0; font-family: monospace;">';
    foreach ($logs as $log) {
        $line = str_replace(["\r", "\n"], ' ', $log);
        echo '<li style="border-bottom:1px solid #ccc; padding:6px 0;color: #fff;">' . esc_html(wp_strip_all_tags($line)) . '</li>';
    }
    echo '</ul>';
}


function voorodak_get_variable($settings) {
    return $settings['variable'] ?? 'otp';
}

function voorodak_get_roles() {
    global $wp_roles;
    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }

    return $wp_roles->roles;
}

/**
 * Returns whether a role is safe to assign from a public self-registration flow.
 *
 * The capability list is intentionally conservative. Sites can further restrict
 * allowed roles via the `voorodak_registration_role_is_safe` filter.
 *
 * @param string $role_key WordPress role slug.
 * @return bool
 */
function voorodak_is_safe_registration_role($role_key) {
    $role_key = sanitize_key((string) $role_key);
    $roles = voorodak_get_roles();

    if ($role_key === '' || empty($roles[$role_key]['capabilities']) || !is_array($roles[$role_key]['capabilities'])) {
        return false;
    }

    $privileged_capabilities = [
        'manage_options',
        'edit_users',
        'create_users',
        'promote_users',
        'delete_users',
        'remove_users',
        'activate_plugins',
        'install_plugins',
        'update_plugins',
        'delete_plugins',
        'edit_plugins',
        'switch_themes',
        'install_themes',
        'update_themes',
        'delete_themes',
        'edit_themes',
        'update_core',
        'edit_files',
        'unfiltered_html',
        // Content-management roles such as Author/Editor and WooCommerce
        // Shop Manager are also inappropriate defaults for public signup.
        'edit_posts',
        'publish_posts',
        'edit_others_posts',
        'delete_posts',
        'delete_others_posts',
        'edit_pages',
        'publish_pages',
        'upload_files',
        'moderate_comments',
        'manage_woocommerce',
        'edit_products',
        'publish_products',
        'edit_shop_orders',
        'edit_others_shop_orders',
    ];

    $capabilities = $roles[$role_key]['capabilities'];
    $is_safe = true;

    foreach ($privileged_capabilities as $capability) {
        if (!empty($capabilities[$capability])) {
            $is_safe = false;
            break;
        }
    }

    /**
     * Filter whether a role may be assigned by Voorodak public registration.
     *
     * Returning true should only be done when the role is intentionally safe
     * for unauthenticated self-registration.
     *
     * @param bool   $is_safe  Calculated safety result.
     * @param string $role_key Role slug.
     * @param array  $role     Raw role definition.
     */
    return (bool) apply_filters(
        'voorodak_registration_role_is_safe',
        $is_safe,
        $role_key,
        $roles[$role_key]
    );
}

/**
 * Get roles that are safe for public self-registration.
 *
 * @return array
 */
function voorodak_get_registration_roles() {
    return array_filter(
        voorodak_get_roles(),
        static function ($role_data, $role_key) {
            return voorodak_is_safe_registration_role($role_key);
        },
        ARRAY_FILTER_USE_BOTH
    );
}

/**
 * Get the safest sensible default registration role for this installation.
 *
 * @return string
 */
function voorodak_get_default_registration_role() {
    if (class_exists('WooCommerce') && get_role('customer') && voorodak_is_safe_registration_role('customer')) {
        return 'customer';
    }

    if (get_role('subscriber') && voorodak_is_safe_registration_role('subscriber')) {
        return 'subscriber';
    }

    $roles = voorodak_get_registration_roles();
    $role_keys = array_keys($roles);

    return $role_keys[0] ?? '';
}

/**
 * Normalize a configured registration role to a role that is safe for
 * unauthenticated self-registration.
 *
 * @param string $role_key Configured role slug.
 * @return string
 */
function voorodak_normalize_registration_role($role_key) {
    $role_key = sanitize_key((string) $role_key);

    if ($role_key !== '' && voorodak_is_safe_registration_role($role_key)) {
        return $role_key;
    }

    return voorodak_get_default_registration_role();
}

/**
 * One-time migrations for the open-source refactor.
 *
 * This intentionally keeps the existing option name and all supported keys so
 * current installations can upgrade in place. Only obsolete commercial state
 * and an unsafe registration role are normalized.
 */
function voorodak_run_open_source_migrations() {
    $migration_version = 2;
    $installed_version = absint(get_option('voorodak_open_refactor_schema', 0));

    if ($installed_version >= $migration_version) {
        return;
    }

    $settings = get_option(VOORODAK_OPTION, []);
    if (is_array($settings)) {
        $changed = false;

        if (array_key_exists('license_key', $settings)) {
            unset($settings['license_key']);
            $changed = true;
        }

        if (!empty($settings['default_role'])) {
            $safe_role = voorodak_normalize_registration_role($settings['default_role']);
            if ($safe_role !== $settings['default_role']) {
                $settings['default_role'] = $safe_role;
                $changed = true;
            }
        }

        if ($changed) {
            update_option(VOORODAK_OPTION, $settings);
        }
    }

    update_option('voorodak_open_refactor_schema', $migration_version, false);
}
add_action('admin_init', 'voorodak_run_open_source_migrations', 5);

add_action('add_meta_boxes', function () {

    $screens = ['shop_order'];

    if (function_exists('wc_get_page_screen_id')) {
        $screens[] = wc_get_page_screen_id('shop-order');
    }

    foreach ($screens as $screen) {
        add_meta_box(
            'sms_order_settings',
            'تنظیمات ورودک',
            'voorodak_render_sms_order_settings_box',
            $screen,
            'side',
            'default'
        );
    }

});

function voorodak_render_sms_order_settings_box($object) {

    if (!class_exists('WooCommerce')) return;

    $order = ($object instanceof WC_Order)
        ? $object
        : wc_get_order($object->ID ?? 0);

    if (!$order) return;

    wp_nonce_field('voorodak_save_order_sms_settings', 'voorodak_order_sms_nonce');

    woocommerce_wp_checkbox([
        'id' => '_disable_sms',
        'label' => 'عدم ارسال پیامک برای این سفارش',
        'value' => $order->get_meta('_disable_sms'),
    ]);

    woocommerce_wp_text_input([
        'id' => '_tracking_code',
        'label' => 'کد رهگیری',
        'description' => 'کد رهگیری سفارش را وارد کنید.',
        'value' => $order->get_meta('_tracking_code'),
    ]);
}

add_action('woocommerce_process_shop_order_meta', function($order_id){
    if (!class_exists('WooCommerce')) return;

    $nonce = isset($_POST['voorodak_order_sms_nonce']) ? sanitize_text_field(wp_unslash($_POST['voorodak_order_sms_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'voorodak_save_order_sms_settings')) {
        return;
    }

    if (!current_user_can('edit_shop_order', $order_id) && !current_user_can('edit_post', $order_id)) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) return;

    $disable_sms = !empty($_POST['_disable_sms']) ? 'yes' : 'no';
    $order->update_meta_data('_disable_sms', $disable_sms);

    $tracking_code = isset($_POST['_tracking_code']) ? sanitize_text_field(wp_unslash($_POST['_tracking_code'])) : '';
    $order->update_meta_data('_tracking_code', $tracking_code);

    $order->save();
});



add_action('init', function () {
    register_post_status('wc-shipping', [
        'label' => 'در حال ارسال',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('در حال ارسال <span class="count">(%s)</span>', 'در حال ارسال <span class="count">(%s)</span>')
    ]);
});

add_filter('wc_order_statuses', function ($statuses) {
    $new = [];
    foreach ($statuses as $key => $label) {
        $new[$key] = $label;
        if ($key === 'wc-processing') {
            $new['wc-shipping'] = 'در حال ارسال';
        }
    }
    return $new;
});

function voorodak_add_shipping_bulk_action($bulk_actions)
{
    $bulk_actions['mark_shipping'] = 'تغییر وضعیت به در حال ارسال';

    return $bulk_actions;
}

function voorodak_handle_shipping_bulk_action($redirect_to, $action, $order_ids)
{
    if ($action !== 'mark_shipping') {
        return $redirect_to;
    }

    if (!class_exists('WooCommerce')) {
        return $redirect_to;
    }

    $changed = 0;

    foreach ((array) $order_ids as $order_id) {
        $order_id = absint($order_id);
        if (!$order_id) {
            continue;
        }

        if (!current_user_can('edit_shop_order', $order_id) && !current_user_can('edit_post', $order_id)) {
            continue;
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            continue;
        }

        $order->update_status('shipping', 'وضعیت سفارش از کارهای دسته جمعی به در حال ارسال تغییر کرد.', true);
        $changed++;
    }

    return add_query_arg('voorodak_bulk_shipping_changed', $changed, $redirect_to);
}

function voorodak_shipping_bulk_action_notice()
{
    if (empty($_GET['voorodak_bulk_shipping_changed'])) {
        return;
    }

    $changed = absint(wp_unslash($_GET['voorodak_bulk_shipping_changed']));

    if (!$changed) {
        return;
    }

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf('%d سفارش به وضعیت در حال ارسال تغییر کرد.', $changed)) . '</p></div>';
}

add_filter('bulk_actions-edit-shop_order', 'voorodak_add_shipping_bulk_action');
add_filter('handle_bulk_actions-edit-shop_order', 'voorodak_handle_shipping_bulk_action', 10, 3);
add_filter('bulk_actions-woocommerce_page_wc-orders', 'voorodak_add_shipping_bulk_action');
add_filter('handle_bulk_actions-woocommerce_page_wc-orders', 'voorodak_handle_shipping_bulk_action', 10, 3);
add_action('admin_notices', 'voorodak_shipping_bulk_action_notice');


function voorodak_farapyamak_messages() {
    return [
        '0'  => 'پنل اس ام اس امکان اتصال به وب سرویس را ندارد / نام کاربری یا رمز عبور وارد شده صحیح نیست.',
        '1'  => 'ارسال پیامک با موفقیت انجام شد.',
        '2'  => 'موجودی و اعتبار پنل اس ام اس کافی نیست.',
        '3'  => 'محدودیت در ارسال روزانه',
        '4'  => 'محدودیت در حجم و تعداد ارسال پیامک',
        '5'  => 'شماره فرستنده یا سرشماره پیامکی معتبر نمی‌باشد.',
        '6'  => 'سامانه در حال بروزرسانی است.',
        '7'  => 'متن پیامک حاوی کلمه یا کلمات فیلتر شده است.',
        '8'  => 'عدم رسیدن به حداقل تعداد ارسال پیامک',
        '9'  => 'ارسال از خطوط عمومی از طریق وب سرویس امکان‌پذیر نمی‌باشد.',
        '10' => 'پنل اس ام اس کاربر فعال نمی‌باشد و یا پنل پیامک کاربر مسدود شده است.',
        '11' => 'ارسال نشده / شماره موبایل گیرنده در لیست سیاه مخابرات قرار دارد.',
        '12' => 'مدارک پنل اس ام اس کاربر کامل نمی‌باشد.',
        '14' => 'سرشماره فرستنده پیامک، امکان ارسال لینک را ندارد.',
        '15' => 'در پیام‌های چندگیرنده، عبارت «لغو11» در انتهای متن نوشته نشده است.',
        '16' => 'شماره موبایل گیرنده یافت نشد؛ پارامتر to را بررسی کنید.',
        '17' => 'متن پیامک خالی است یا متغیر text مقدار ندارد.',
        '18' => 'شماره موبایل گیرنده نامعتبر است.',
        '35' => 'در متد REST، شماره گیرنده در لیست سیاه مخابرات قرار دارد.',
        '-1' => 'دسترسی به وب‌سرویس پترن غیرفعال است؛ با پشتیبانی تماس بگیرید.',
        '-2' => 'در هر بار ارسال، فقط یک شماره موبایل مجاز به استفاده است.',
        '-3' => 'سرشماره پیامک در سیستم تعریف نشده یا تعداد شماره گیرنده نامعتبر است.',
        '-4' => 'کد پترن (کد متن) اشتباه است یا هنوز توسط مدیر سامانه تأیید نشده است.',
        '-5' => 'تعداد اندیس‌های پارامتر text با تعداد متغیرهای پترن مطابقت ندارد.',
        '-6' => 'خطای داخلی؛ ممکن است ساختار پترن یا کاراکترهای درون {} اشتباه نوشته شده باشند.',
        '-7' => 'خطا در شماره فرستنده؛ برای بررسی با پشتیبانی تماس بگیرید.',
        '-10' => 'ارسال لینک، آی‌پی یا ایمیل به‌جای متغیر مجاز نیست؛ آن‌ها را از متن حذف کنید.',
    ];
}

function voorodak_ghasedak_messages() {
    return [
        '200' => 'با موفقیت انجام شد',
        '400' => 'پارامترهای ورودی صحیح نمی باشد یا مشکل دارد',
        '401' => 'ApiKey معتبر نمی باشد یا مشکل احراز هویت',
        '402' => 'در حال حاضر سرور قادر به پاسخگویی نیست',
        '406' => 'اطلاعات مالکیت خط تائید نشده است',
        '412' => 'مشکل دسترسی به خط یا سرویس وجود دارد',
        '413' => 'مشکل در طول پیام یا تعداد گیرنده‌ها',
        '418' => 'اعتبار کافی نمی‌باشد',
        '419' => 'تعرفه ارسال معتبر نمی باشد',
        '420' => 'استفاده از لینک غیر مجاز در متن پیامک',
        '422' => 'پیام به دلیل وجود کاراکتر نامناسب قابل ارسال نیست',
        '426' => 'ارتقاء پلن مورد نیاز است',
        '428' => 'قالب پیام نامعتبر میباشد',
        '451' => 'درخواست تکراری می باشد',
    ];
}




function voorodak_set_option_transient($key, $value, $seconds = 3600)
{
    $seconds = max(1, absint($seconds));

    // Native transients automatically benefit from a persistent object cache when one
    // is available and avoid creating autoloaded application options.
    set_transient($key, $value, $seconds);

    // Remove a legacy payload with the same logical key after a successful write.
    delete_option($key);
}

function voorodak_get_option_transient($key)
{
    $value = get_transient($key);
    if ($value !== false) {
        return $value;
    }

    // Backward compatibility for temporary values created before native transients
    // were adopted. Migrate a still-valid payload lazily when it is first read.
    $data = maybe_unserialize(get_option($key));

    if (!is_array($data) || !array_key_exists('value', $data)) {
        return false;
    }

    $expire = isset($data['expire']) ? absint($data['expire']) : 0;
    if ($expire && time() >= $expire) {
        delete_option($key);
        return false;
    }

    $ttl = $expire ? max(1, $expire - time()) : HOUR_IN_SECONDS;
    set_transient($key, $data['value'], $ttl);
    delete_option($key);

    return $data['value'];
}

function voorodak_delete_option_transient($key)
{
    delete_transient($key);
    delete_option($key);
}

/**
 * Proactively migrate temporary values created by pre-refactor versions.
 * Lazy migration in voorodak_get_option_transient() remains as a fallback,
 * but doing this once removes recurring wp_options scans from normal traffic.
 */
function voorodak_migrate_legacy_option_transients()
{
    if (get_option('voorodak_legacy_transients_migrated') === '1') {
        return;
    }

    global $wpdb;

    $prefixes = [
        'voorodak_otp_',
        'voorodak_ip_limiter_',
        'voorodak_session_limiter_',
        'voorodak_identity_limiter_',
        'voorodak_otp_send_ip_',
        'voorodak_otp_send_identity_',
        'voorodak_reset_token_',
        'voorodak_reset_lookup_',
        'voorodak_sent_email_',
    ];

    $like_parts = [];
    foreach ($prefixes as $prefix) {
        $like_parts[] = $wpdb->prepare('option_name LIKE %s', $wpdb->esc_like($prefix) . '%');
    }

    if (!$like_parts) {
        update_option('voorodak_legacy_transients_migrated', '1', false);
        return;
    }

    $rows = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options} WHERE " . implode(' OR ', $like_parts)
    );

    $now = time();
    foreach ((array) $rows as $row) {
        if (empty($row->option_name)) {
            continue;
        }

        $data = maybe_unserialize($row->option_value);
        if (!is_array($data) || !array_key_exists('value', $data)) {
            continue;
        }

        $expires_at = isset($data['expire']) ? absint($data['expire']) : 0;
        if ($expires_at && $expires_at <= $now) {
            delete_option($row->option_name);
            continue;
        }

        $ttl = $expires_at ? max(1, $expires_at - $now) : HOUR_IN_SECONDS;
        set_transient($row->option_name, $data['value'], $ttl);
        delete_option($row->option_name);
    }

    update_option('voorodak_legacy_transients_migrated', '1', false);
}
add_action('wp_loaded', 'voorodak_migrate_legacy_option_transients', 1);

function voorodak_clean_expired_options()
{
    global $wpdb;

    $prefixes = [
        'voorodak_otp_',
        'voorodak_ip_limiter_',
        'voorodak_session_limiter_',
        'voorodak_identity_limiter_',
        'voorodak_otp_send_ip_',
        'voorodak_otp_send_identity_',
        'voorodak_reset_token_',
        'voorodak_reset_lookup_',
        'voorodak_sent_email_',
        'voorodak_captcha_'
    ];

    if (empty($prefixes)) return;

    $like_parts = [];

    foreach ($prefixes as $p) {
        $like_parts[] = $wpdb->prepare("option_name LIKE %s", $wpdb->esc_like($p) . '%');
    }

    $like_sql = implode(' OR ', $like_parts);

    $sql = "SELECT option_name, option_value FROM {$wpdb->options} WHERE $like_sql";

    $options = $wpdb->get_results($sql);

    if (empty($options)) return;

    foreach ($options as $opt) {

        $data = maybe_unserialize($opt->option_value);

        if (is_array($data) && isset($data['expire']) && time() > $data['expire']) {
            delete_option($opt->option_name);
        }
    }
}

function voorodak_cleanup_captcha_options()
{
    global $wpdb;

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like('voorodak_captcha_') . '%'
        )
    );
}

function voorodak_fix_legacy_option_transients_autoload()
{
    global $wpdb;

    $prefixes = [
        'voorodak_otp_',
        'voorodak_ip_limiter_',
        'voorodak_session_limiter_',
        'voorodak_identity_limiter_',
        'voorodak_otp_send_ip_',
        'voorodak_otp_send_identity_',
        'voorodak_reset_token_',
        'voorodak_reset_lookup_',
        'voorodak_sent_email_',
    ];

    $like_parts = [];

    foreach ($prefixes as $prefix) {
        $like_parts[] = $wpdb->prepare('option_name LIKE %s', $wpdb->esc_like($prefix) . '%');
    }

    if (empty($like_parts)) {
        return;
    }

    $wpdb->query(
        "UPDATE {$wpdb->options} SET autoload = 'no' WHERE " . implode(' OR ', $like_parts)
    );
}

function voorodak_clean_expired_options_auto()
{
    if (get_option('voorodak_legacy_transients_migrated') === '1') {
        return;
    }

    $last_run = get_option('voorodak_last_cleanup');

    $today = date('Y-m-d');

    if ($last_run === $today) {
        return;
    }

    update_option('voorodak_last_cleanup', $today, false);

    voorodak_clean_expired_options();
}

add_action('wp_loaded', 'voorodak_clean_expired_options_auto');

function voorodak_cleanup_legacy_captcha_options_auto()
{
    if (get_option('voorodak_captcha_autoload_cleanup_done') === '1') {
        return;
    }

    voorodak_cleanup_captcha_options();
    voorodak_fix_legacy_option_transients_autoload();
    update_option('voorodak_captcha_autoload_cleanup_done', '1', false);
}

add_action('wp_loaded', 'voorodak_cleanup_legacy_captcha_options_auto', 1);
