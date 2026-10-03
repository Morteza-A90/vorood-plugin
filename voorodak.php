<?php
/**
 *
 * Plugin Name: افزونه ورود ثبت نام پیامکی ورودک
 * Plugin URI:  https://taktheme.com/product/voorodak/
 * Description: به کمک افزونه ورود ثبت نام پیامکی ورودک میتوانید فرایند ورود و ثبت نام کاربران خود را بسیار ساده کنید تا تنها با شماره موبایل و کد تایید در سایت وارد یا عضو شوند.
 * Version:     6.2.0
 * Author:      Mehdi Amrollahi
 * Author URI:  https://taktheme.com/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: voorodak
 * Domain Path: /languages
 * Requires at least: 5.7
 * Requires PHP: 7.2
 *
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

define('VOORODAK_VERSION', '6.2.0');
define('VOORODAK_OPTION', 'voorodak_options');
define('VOORODAK_RESET_TOEKN', 'voorodak_reset_token_');
define('VOORODAK_RESET_LOOKUP', 'voorodak_reset_lookup_');
define('VOORODAK_OTP', 'voorodak_otp_');
define('VOORODAK_SENT_EMAIL', 'voorodak_sent_email_');
define('VOORODAK_PLUGIN_FILE', __FILE__);
define('VOORODAK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VOORODAK_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Create the default authentication page on first activation.
 *
 * @return false|int|WP_Error
 */
function voorodak_set_default_login_page_id()
{
    if (!get_option(VOORODAK_OPTION)) {
        $login_page = array(
            'post_title' => __('ورود / ثبت نام', 'voorodak'),
            'post_name' => 'auth',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'page',
            'ping_status' => 'closed',
            'comment_status' => 'closed',
        );

        $login_page_id = wp_insert_post($login_page);
        if (!is_wp_error($login_page_id)) {
            $array_setting = array('login_page_id' => $login_page_id);
            if (update_option(VOORODAK_OPTION, $array_setting)) {
                return $login_page_id;
            }
        }
    }

    return false;
}
register_activation_hook(__FILE__, 'voorodak_set_default_login_page_id');

/**
 * Keep unsupported PHP versions from loading the plugin. Runtime extensions such
 * as SOAP are checked only by the provider that actually needs them.
 */
function voorodak_has_supported_php()
{
    return version_compare(PHP_VERSION, '7.2', '>=');
}

if (!voorodak_has_supported_php()) {
    add_action('admin_notices', function () {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('افزونه ورودک به PHP 7.2 یا بالاتر نیاز دارد.', 'voorodak')
        );
    });
    return;
}

require_once VOORODAK_PLUGIN_PATH . 'includes/class-voorodak.php';
require_once VOORODAK_PLUGIN_PATH . 'includes/helper-functions.php';
require_once VOORODAK_PLUGIN_PATH . 'includes/class-voorodak-sms-notifications.php';
