<?php

class Voorodak_SMS_Notifications extends Voorodak_SMS
{

    public function __construct()
    {

        parent::__construct();

        if (!(empty($this->method)) && strpos($this->method, '_pattern') !== false) {
            if (($this->settings['sms_login_admin'] ?? '') == '1'){
                add_action('voorodak_after_do_login', [$this, 'sms_login_admin']);
            }

            if (($this->settings['sms_login_roleadmin_admin'] ?? '') == '1') {
                // Listen to the standard WordPress login lifecycle so both Voorodak
                // and non-Voorodak administrator logins are covered exactly once.
                add_action('wp_login', [$this, 'handle_wp_login'], 10, 2);
            }

            if (($this->settings['sms_register_admin'] ?? '') == '1') {
                add_action('voorodak_after_do_register', [$this, 'sms_register_admin']);
            }

            if (($this->settings['sms_login'] ?? '') == '1') {
                add_action('voorodak_after_do_login', [$this, 'sms_login']);
            }

            if (($this->settings['sms_register'] ?? '') == '1') {
                add_action('voorodak_after_do_register', [$this, 'sms_register']);
            }

            if (($this->settings['sms_comment_new_admin'] ?? '') == '1') {
                add_action('comment_post', [$this, 'sms_comment_new_admin'], 10, 3);
            }

            if (($this->settings['sms_comment_reply_user'] ?? '') == '1') {
                add_action('wp_set_comment_status', [$this, 'sms_comment_reply_user'], 10, 2);
                add_action('comment_post', [$this, 'sms_comment_reply_user_admin'], 10, 2);
            }

            add_action('init', [$this, 'woocommerce_orders_sms']);

        }

        // Bale notifications are independent from the selected SMS gateway.
        // Previously they were registered only when a pattern-based SMS
        // provider was active, which silently disabled Bale for other setups.
        if (($this->settings['bale_comment_new'] ?? '') === '1') {
            add_action('comment_post', [$this, 'messenger_comment_new_admin'], 10, 3);
        }

        if (
            ($this->settings['bale_user_login'] ?? '') === '1'
            || ($this->settings['bale_user_login_admin'] ?? '') === '1'
        ) {
            add_action('wp_login', [$this, 'messenger_handle_wp_login'], 10, 2);
        }

        if (($this->settings['bale_user_register'] ?? '') === '1') {
            add_action('voorodak_after_do_register', [$this, 'messenger_user_registration']);
        }

        if (($this->settings['bale_order_completed'] ?? '') === '1') {
            add_action('woocommerce_order_status_completed', [$this, 'messenger_send_completed_order'], 10, 1);
            add_action('woocommerce_order_status_processing', [$this, 'messenger_send_completed_order'], 10, 1);
        }

    }

    public function messenger_send_completed_order($order_id) {

        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) return;

        $items_list = "";
        foreach ( $order->get_items() as $item ) {
            $items_list .= "🔸 " . $item->get_name() . " ( " . $item->get_quantity() . " عدد )\n";
        }

        $message_template = "✅ سفارش جدید ثبت شد!\n🆔 شماره سفارش: {order_id}\n👤 مشتری: {customer_name}\n📞 شماره تماس: {phone}\n🛍️ محصولات:\n{items}\n💰 مبلغ کل: {total} {currency}\n💳 روش پرداخت: {payment_method}\n📅 زمان: {date}\n🔗 مدیریت سفارش: {admin_url}";

        $final_message = str_replace(
            ['{order_id}', '{customer_name}', '{phone}', '{items}', '{total}', '{currency}', '{payment_method}', '{date}', '{admin_url}'],
            [
                $order_id,
                trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                $order->get_billing_phone(),
                trim($items_list),
                number_format((float) $order->get_total()),
                html_entity_decode(get_woocommerce_currency_symbol($order->get_currency())),
                html_entity_decode($order->get_payment_method_title()),
                wp_date('j F Y H:i'),
                $order->get_edit_order_url()
            ],
            $message_template
        );



        $bale_order_completed = $this->settings['bale_order_completed'] ?? '';
        if ($bale_order_completed){
            $this->process_bot_event($this->get_admin_phones('balechatids'),$final_message);
        }

    }

    public function messenger_comment_new_admin($comment_ID, $comment_approved, $commentdata)
    {
        $comment = get_comment($comment_ID);
        if (!$comment) {
            return;
        }
        $post = get_post($comment->comment_post_ID);
        if (!$post) {
            return;
        }
        if ($comment->user_id) {
            $user = get_userdata($comment->user_id);
            if ($user && in_array('administrator', (array)$user->roles)) {
                return;
            }
        }

        $message_template = "🔔 یک دیدگاه جدید ثبت شد\n💬 متن: {comment_text}\n👤 نویسنده: {author}\n📧 ایمیل: {email}\n🔗 لینک: {link}\n📅 تاریخ: {date}\n";
        $comment_text = $commentdata['comment_content'];
        $author = $commentdata['comment_author'];
        $email = $commentdata['comment_author_email'];
        $link = get_comment_link($comment_ID);
        $date = wp_date('j F Y H:i');
        $final_message = str_replace(
            ['{comment_text}', '{author}', '{email}', '{link}', '{date}'],
            [$comment_text, $author, $email, $link, $date],
            $message_template
        );

        $bale_comment_new = $this->settings['bale_comment_new'] ?? '';
        if ($bale_comment_new){
            $this->process_bot_event($this->get_admin_phones('balechatids'),$final_message);
        }

    }

    public function messenger_user_login($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        $bale_status_send = false;

        if (in_array('administrator', $user->roles)){
            $message_template = "⚠️ هشدار: ورود ادمین\n👤 ادمین: {username}\n📅 تاریخ: {date}\n⏰ ساعت: {time}\n📍 آی‌پی: {ip}\n🔐 سطح دسترسی: مدیر کل";
            $bale_status_send = $this->settings['bale_user_login_admin'] ?? '';
        }else{
            $message_template = "🔑 ورود کاربر به سایت\n👤 نام کاربری: {username}\n📧 ایمیل: {email}\n⏰ زمان: {date} {time}\n🌐 آی‌پی: {ip}";
            $bale_status_send = $this->settings['bale_user_login'] ?? '';


        }

        $final_message = str_replace(
            ['{username}', '{email}', '{date}', '{time}', '{ip}'],
            [
                $user->user_login,
                $user->user_email,
                wp_date('j F Y'),
                wp_date('H:i'),
                filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ? $_SERVER['REMOTE_ADDR'] : ''
            ],
            $message_template
        );

        if ($bale_status_send){
            $this->process_bot_event($this->get_admin_phones('balechatids'),$final_message);
        }

    }

    public function messenger_user_registration($user_id) {
        $user = get_userdata($user_id);
        $message_template = "🎉 ثبت‌نام کاربر جدید\n👤 نام کاربر: {username}\n📧 ایمیل: {email}\n📅 تاریخ عضویت: {date}\n🆔 شناسه کاربر: {user_id}";

        $final_message = str_replace(
            ['{username}', '{email}', '{date}', '{user_id}'],
            [$user->user_login, $user->user_email, wp_date('j F Y'), $user_id],
            $message_template
        );

        $bale_user_register = $this->settings['bale_user_register'] ?? '';
        if ($bale_user_register){
            $this->process_bot_event($this->get_admin_phones('balechatids'),$final_message);
        }
    }

    public function messenger_handle_wp_login($user_login, $user)
    {
        $this->messenger_user_login($user->ID);
    }

    public function woocommerce_orders_sms()
    {
        if (!function_exists('wc_get_order_statuses') || !function_exists('wc_get_order')) {
            return;
        }

        foreach (wc_get_order_statuses() as $status_key => $status_name) {

            if ($status_key === 'wc-checkout-draft') {
                continue;
            }

            $status = str_replace('wc-', '', $status_key);

            $types = [
                'admin' => [
                    'option_key' => "sms_order_{$status_key}_admin",
                    'receiver'   => function ($order) {
                        return $this->get_admin_phones();
                    },
                    'note'       => 'پیامک به مدیر ارسال شد',
                ],
                'user' => [
                    'option_key' => "sms_order_{$status_key}_user",
                    'receiver'   => function ($order) {
                        return $this->get_user_phone(
                            $order->get_user_id(),
                            $order->get_billing_phone()
                        );
                    },
                    'note'       => 'پیامک به مشتری ارسال شد',
                ],
            ];

            foreach ($types as $type) {

                $option_key = $type['option_key'];

                if ((int)(($this->settings[$option_key]) ?? 0) !== 1) {
                    continue;
                }

                add_action(
                    "woocommerce_order_status_{$status}",
                    function ($order_id) use ($status_key, $option_key, $type) {

                        $order = wc_get_order($order_id);

                        if (!$order || $order->get_meta('_disable_sms') === 'yes') {
                            return;
                        }

                        $userdata = $this->get_user_display_name($order->get_user_id(), $order);
                        $date = $order->get_date_created();

                        $params = [
                            'orderid' => $order_id,
                            'total'   => number_format($order->get_total()),
                            'name'    => $userdata[0] ?? '',
                            'firstname' => $order->get_billing_first_name(),
                            'lastname'  => $order->get_billing_last_name(),
                            'count' => $order->get_item_count(),
                            'shippingtotal' => number_format($order->get_shipping_total()),
                            'discounttotal' => number_format($order->get_discount_total()),
                            'date'     => $date ? wp_date('j F Y H:i', $date->getTimestamp()) : null,
                            'dateonly' => $date ? wp_date('j F Y', $date->getTimestamp()) : null,
                            'note'    => $order->get_customer_note(),

                        ];

                        $tracking_code = $order->get_meta('_tracking_code');

                        if (!empty($tracking_code)) {
                            $params['tracking'] = $tracking_code;
                        }

                        $fields = $this->settings[$option_key . '_variables'] ?? 'name-orderid-total-tracking';
                        $result = $this->filter_user_data($fields, $params);


                        $sent = $this->process_sms_event(
                            $type['receiver']($order),
                            $this->settings[$option_key . '_pattern'] ?? '',
                            $result
                        );

                        $order->add_order_note(
                            sprintf(
                                '%s (وضعیت: %s)',
                                $sent ? $type['note'] : 'ارسال پیامک ناموفق بود',
                                $status_key
                            ),
                            false
                        );
                    }
                );
            }
        }
    }

    public function sms_login_admin($user_id)
    {
        $data = $this->get_user_event_data($user_id);
        $fields = $this->settings['sms_login_admin_variables'] ?? 'name-username-userid';
        $result = $this->filter_user_data($fields, $data);
        $this->process_sms_event(
            $this->get_admin_phones(),
            $this->settings['sms_login_admin_pattern'],
            $result
        );
    }

    public function sms_login_roleadmin_admin($user_id)
    {
        $user = get_userdata($user_id);
        if (!$user || !in_array('administrator', (array)$user->roles)) {
            return;
        }
        $data = $this->get_user_event_data($user_id);
        $fields = $this->settings['sms_login_roleadmin_admin_variables'] ?? 'name-username-userid';
        $result = $this->filter_user_data($fields, $data);
        $this->process_sms_event(
            $this->get_admin_phones(),
            $this->settings['sms_login_roleadmin_admin_pattern'],
            $result
        );
    }

    public function handle_wp_login($user_login, $user)
    {
        $this->sms_login_roleadmin_admin($user->ID);
    }

    public function sms_register_admin($user_id)
    {
        $data = $this->get_user_event_data($user_id);
        $fields = $this->settings['sms_register_admin_variables'] ?? 'name-username-userid';
        $result = $this->filter_user_data($fields, $data);
        $this->process_sms_event(
            $this->get_admin_phones(),
            $this->settings['sms_register_admin_pattern'],
            $result
        );
    }

    public function sms_login($user_id)
    {
        $data = $this->get_user_event_data($user_id);
        $fields = $this->settings['sms_login_pattern_variables'] ?? ($this->settings['sms_login_variables'] ?? 'name-username-userid');
        $result = $this->filter_user_data($fields, $data);
        $this->process_sms_event(
            $this->get_user_phone($user_id),
            $this->settings['sms_login_pattern'],
            $result
        );
    }

    public function sms_register($user_id)
    {
        $data = $this->get_user_event_data($user_id);
        $fields = $this->settings['sms_register_variables'] ?? 'name-username-userid';
        $result = $this->filter_user_data($fields, $data);
        $this->process_sms_event(
            $this->get_user_phone($user_id),
            $this->settings['sms_register_pattern'],
            $result
        );
    }

    public function sms_comment_new_admin($comment_ID, $comment_approved, $commentdata)
    {
        $comment = get_comment($comment_ID);
        if (!$comment) {
            return;
        }
        $post = get_post($comment->comment_post_ID);
        if (!$post) {
            return;
        }
        if ($comment->user_id) {
            $user = get_userdata($comment->user_id);
            if ($user && in_array('administrator', (array)$user->roles)) {
                return;
            }
        }

        $data = $this->get_comment_event_data($post);
        $fields = $this->settings['sms_comment_new_admin_variables'] ?? 'title-postlink';
        $result = $this->filter_user_data($fields, $data);

        $this->process_sms_event(
            $this->get_admin_phones(),
            $this->settings['sms_comment_new_admin_pattern'],
            $result
        );

    }

    public function sms_comment_reply_user($comment_ID, $status)
    {
        if ($status !== 'approve' && (int)$status !== 1) {
            return;
        }

        $comment = get_comment($comment_ID);
        if (!$comment || !$comment->comment_parent) {
            return;
        }

        $parent_comment = get_comment($comment->comment_parent);
        if (!$parent_comment) {
            return;
        }

        $post = get_post($comment->comment_post_ID);
        if (!$post) {
            return;
        }

        $user_id = $parent_comment->user_id;
        if ($user_id) {

            $data = $this->get_comment_event_data($post);
            $fields = $this->settings['sms_comment_reply_user_variables'] ?? 'title-postlink';
            $result = $this->filter_user_data($fields, $data);

            $this->process_sms_event(
                $this->get_user_phone($user_id),
                $this->settings['sms_comment_reply_user_pattern'],
                $result
            );
        }
    }

    public function sms_comment_reply_user_admin($comment_ID, $status)
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $this->sms_comment_reply_user($comment_ID, $status);
    }

    private function get_user_phone($user_id, $order_billing_phone = null)
    {
        if (!empty($order_billing_phone)) {
            return [$order_billing_phone];
        }

        if (!$user_id) {
            return false;
        }

        $user = get_userdata($user_id);
        if ($user) {
            $username = $user->user_login;
            if (preg_match('/^09\d{9}$/', $username)) {
                return [$username];
            }

            $billing_phone = get_user_meta($user_id, 'billing_phone', true);
            if (!empty($billing_phone)) {
                return [$billing_phone];
            }
        }

        return false;
    }

    private function get_admin_phones($type = 'sms_admins')
    {
        $phones = $this->settings[$type] ?? '';

        if (empty($phones)) {
            return false;
        }

        return array_filter(
            array_map('trim', explode("\n", $phones)),
            function ($phone) use ($type) {
                if ($type === 'sms_admins') {
                    return preg_match('/^09\d{9}$/', $phone);
                }

                return is_numeric($phone);
            }
        ) ?: false;
    }

    private function process_sms_event($receivers, $pattern, $otp_data)
    {
        if (!$receivers || empty($pattern) || empty($otp_data)) {
            return false;
        }

        $all_sent = true;
        $attempted = false;

        foreach ((array)$receivers as $to) {
            if (empty($to)) {
                $all_sent = false;
                continue;
            }

            $attempted = true;
            $this->to = $to;
            $this->pattern_otp = $pattern;
            $this->otp = $otp_data;
            if (!$this->send(true)) {
                $all_sent = false;
            }
        }

        return $attempted && $all_sent;
    }

    private function process_bot_event($receivers, $data)
    {
        if (!$receivers || empty($data)) {
            return false;
        }

        foreach ((array)$receivers as $to) {
            $this->bale_send('bot', $to, $data);
        }

        return true;
    }

    private function get_user_display_name($user_id, $order = null)
    {
        if (empty($user_id) || !$user_id) {
            if (!empty($order) && is_object($order)) {
                $order_first = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() : '';
                $order_last  = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name() : '';
                $display_name = trim($order_first . ' ' . $order_last);
                if (!empty($display_name)) return [$display_name, 'ناشناس'];
            }
            return ['کاربر', 'ناشناس'];
        }

        $user = get_userdata($user_id);
        if (!$user) {
            if (!empty($order) && is_object($order)) {
                $order_first = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() : '';
                $order_last  = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name() : '';
                $display_name = trim($order_first . ' ' . $order_last);
                if (!empty($display_name)) return [$display_name, 'ناشناس'];
            }
            return ['کاربر', 'ناشناس'];
        }

        $first_name = $user->first_name ?? '';
        $last_name  = $user->last_name ?? '';
        $display_name = '';

        if (!empty($first_name) || !empty($last_name)) {
            $display_name = trim($first_name . ' ' . $last_name);
        } elseif (function_exists('wc_get_order')) {
            $meta = get_user_meta($user_id);
            $billing_first = $meta['billing_first_name'][0] ?? '';
            $billing_last  = $meta['billing_last_name'][0] ?? '';
            if (!empty($billing_first) || !empty($billing_last)) {
                $display_name = trim($billing_first . ' ' . $billing_last);
            } elseif (!empty($order) && is_object($order)) {
                $order_billing_first = method_exists($order, 'get_billing_first_name') ? $order->get_billing_first_name() : '';
                $order_billing_last  = method_exists($order, 'get_billing_last_name') ? $order->get_billing_last_name() : '';
                if (!empty($order_billing_first) || !empty($order_billing_last)) {
                    $display_name = trim($order_billing_first . ' ' . $order_billing_last);
                }
            }
        }

        if (empty($display_name)) {
            $display_name = 'کاربر';
        }

        return [$display_name, $user->user_login];
    }

    private function get_user_event_data($user_id)
    {
        $userdata = $this->get_user_display_name($user_id);

        return [
            'name' => $userdata[0],
            'username' => $userdata[1],
            'userid' => $user_id,
            'date' => wp_date('j F Y'),
            'time' => wp_date('H:i'),
        ];
    }

    private function get_comment_event_data($post)
    {
        return [
            'title' => $post->post_title,
            'postlink' => urlencode(get_the_permalink($post->ID)),
            'date' => wp_date('j F Y'),
            'time' => wp_date('H:i'),
        ];
    }


    private function filter_user_data($fields, $data)
    {
        $result = [];

        foreach (explode('-', $fields) as $field) {
            if (isset($data[$field])) {
                $result[$field] = $data[$field];
            }
        }

        return $result;
    }

}

$sms_notifications = new Voorodak_SMS_Notifications();


