<?php
// Exit if accessed directly.
defined('ABSPATH') || exit;
$template = $settings['template'] ?? 'default';
$login_page_id = voorodak_get_login_page_id();
$bg_color = $settings['bg_color'] ?? '#ffffff';
$button_color = $settings['button_color'] ?? '#5498fa';
$button_color_hover = $settings['button_color_hover'] ?? '#2c61a6';
$logo = $settings['logo'] ?? '';
$cover = $settings['cover'] ?? '';
$otp_length = $settings['otp_length'] ?? '6';
$otp_boxed = $settings['otp_boxed'] ?? '';
$family_name = $settings['family_name'] ?? '';
$national_code_field = $settings['national_code_field'] ?? '';
$email_field = $settings['email_field'] ?? '';
$password_field = $settings['password_field'] ?? '';
$password_login_priority = $settings['password_login_priority'] ?? '';
$login_type = $settings['login_type'] ?? 'mobile-email';
$show_password_login_forms = $login_type != 'mobile' || !empty($password_login_priority);
$baleotp = $settings['baleotp'] ?? '';
$btnback = $settings['btnback'] ?? '';
if ($login_type == 'mobile'){
    $username_placeholder = __('شماره موبایل', 'voorodak');
}elseif ($login_type == 'mobile-email'){
    $username_placeholder = __('شماره موبایل یا ایمیل', 'voorodak');
}else{
    $username_placeholder = __('شماره موبایل یا ایمیل یا نام کاربری', 'voorodak');
}
$reset_token = isset($_GET['reset_token']) ? sanitize_text_field(wp_unslash($_GET['reset_token'])) : null;
$form_name = !empty($settings['form_name']) ? $settings['form_name'] : __('ورود / ثبت نام', 'voorodak');
$term_editor = $settings['term_editor'] ?? '';
$button_style = '--voorodak-button-color: ' . $button_color . '; --voorodak-button-color-hover: ' . $button_color_hover . ';';
$captcha_enabled = !empty($settings['captcha_enabled']);
$captcha_challenge_main = $captcha_enabled ? $this->get_captcha_challenge() : false;
$captcha_challenge_forget = $captcha_enabled ? $this->get_captcha_challenge() : false;
?>
<div class="voorodak voorodak-<?php echo esc_attr($template); ?>" style="background: <?php echo esc_attr($bg_color); ?>">
    <?php if ($btnback):
        $btnbackprev = $settings['btnbackprev'] ?? '';
        if ($btnbackprev){
            $btnback_url = wp_get_referer();
            $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
            $login_page_url = $login_page_id ? get_permalink($login_page_id) : '';
            $referer_path = wp_parse_url($btnback_url, PHP_URL_PATH);
            $current_path = wp_parse_url($request_uri, PHP_URL_PATH);
            $login_page_path = wp_parse_url($login_page_url, PHP_URL_PATH);

            if (
                empty($btnback_url) ||
                ($referer_path && $current_path && untrailingslashit($referer_path) === untrailingslashit($current_path)) ||
                ($referer_path && $login_page_path && untrailingslashit($referer_path) === untrailingslashit($login_page_path))
            ) {
                $btnback_url = home_url();
            }
        }else{
            $btnback_url = home_url();
        }
        ?>
        <a href="<?php echo esc_url($btnback_url); ?>" class="voorodak-back">
            <svg class="16" height="16" data-slot="icon" aria-hidden="true" fill="none" stroke-width="2.2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <?php esc_html_e('بازگشت', 'voorodak'); ?>
        </a>
    <?php endif; ?>
    <div class="voorodak__wrapper" style="<?php echo esc_attr($button_style); ?>">
        <?php if ($template == 'zarinpal'): ?>
        <div class='voorodak__wrapper-main-right'>
        <?php endif; ?>
        <div class="voorodak__wrapper-main">
            <div class="voorodak__wrapper-main-head">
                <svg style="display: none" class="20" height="20" data-slot="icon" aria-hidden="true" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <?php if ($logo): ?>
                    <a href="<?php echo esc_url(home_url()); ?>">
                        <img src="<?php echo esc_url($logo); ?>" width="150" height="65" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!$reset_token): ?>
                <form method="post" action="" class="voorodak__wrapper-main-box" id="voorodak__wrapper-main-username">
                    <div class="voorodak__wrapper-main-box-title" role="heading" aria-level="2"><?php echo esc_html($form_name); ?></div>
                    <div class="voorodak__wrapper-main-box-description">
                        <p><?php esc_html_e('سلام!', 'voorodak'); ?></p>
                        <?php if ($login_type == 'mobile'){
                            $placeholder_inter = __('شماره موبایل', 'voorodak');
                        }elseif ($login_type == 'mobile-email'){
                            $placeholder_inter = __('شماره موبایل یا ایمیل', 'voorodak');
                        }else{
                            $placeholder_inter = __('اطلاعات کاربری', 'voorodak');
                        } ?>
                        <p><?php echo esc_html(sprintf(/* translators: %s: Placeholder type */ __('لطفا %s خود را وارد کنید', 'voorodak'), $placeholder_inter)); ?></p>
                    </div>
                    <div class="voorodak__wrapper-main-box-field">
                        <input type="text" name="voorodak__username" placeholder="<?php echo esc_attr($username_placeholder); ?>" aria-label="<?php echo esc_attr($username_placeholder); ?>" autocomplete="username"<?php if ($login_type == 'mobile') echo ' inputmode="numeric"'; ?>>
                    </div>
                    <?php if ($captcha_challenge_main): ?>
                    <div class="voorodak__wrapper-main-box-field voorodak__captcha-field">
                        <label for="voorodak-captcha-main"><?php echo esc_html($captcha_challenge_main['question']); ?></label>
                        <input id="voorodak-captcha-main" type="text" name="voorodak__captcha" placeholder="<?php esc_attr_e('کد امنیتی', 'voorodak'); ?>" aria-label="<?php esc_attr_e('کد امنیتی', 'voorodak'); ?>" inputmode="numeric" autocomplete="off">
                        <input type="hidden" name="voorodak__captcha-token" value="<?php echo esc_attr($captcha_challenge_main['token']); ?>">
                    </div>
                    <?php endif; ?>
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="voorodak__website-field">
                    <button type="submit" id="voorodak__submit-username"><?php esc_html_e('ورود', 'voorodak'); ?></button>
                    <?php if($term_editor): ?>
                    <div class="voorodak__terms">
                        <?php echo wp_kses_post($term_editor); ?>
                    </div>
                    <?php endif; ?>
                </form>
                <form method="post" action="" class="voorodak__wrapper-main-box" id="voorodak__wrapper-main-otp" style="display: none">
                    <div class="voorodak__wrapper-main-box-title" role="heading" aria-level="2"><?php esc_html_e('کد تایید', 'voorodak'); ?></div>
                    <div class="voorodak__wrapper-main-box-description"></div>
                    <div class="voorodak__wrapper-main-box-field">
                        <?php if($family_name): ?>
                        <input type="text" name="voorodak__first_name" placeholder="<?php esc_attr_e('نام', 'voorodak'); ?>" aria-label="<?php esc_attr_e('نام', 'voorodak'); ?>" autocomplete="given-name">
                        <input type="text" name="voorodak__last_name" placeholder="<?php esc_attr_e('نام خانوادگی', 'voorodak'); ?>" aria-label="<?php esc_attr_e('نام خانوادگی', 'voorodak'); ?>" autocomplete="family-name">
                        <div class="clear"></div>
                        <?php endif; ?>
                        <?php if ($national_code_field): ?>
                        <input type="text" name="voorodak__national_code" placeholder="<?php esc_attr_e('کد ملی', 'voorodak'); ?>" aria-label="<?php esc_attr_e('کد ملی', 'voorodak'); ?>" inputmode="numeric" maxlength="10" autocomplete="off">
                        <?php endif; ?>
                        <?php if ($email_field): ?>
                        <input type="email" name="voorodak__email" placeholder="<?php esc_attr_e('ایمیل', 'voorodak'); ?>" aria-label="<?php esc_attr_e('ایمیل', 'voorodak'); ?>" autocomplete="email">
                        <?php endif; ?>
                        <?php if ($password_field): ?>
                        <div class="voorodak-password-control">
                            <input type="password" name="voorodak__password_register" placeholder="<?php esc_attr_e('رمز عبور', 'voorodak'); ?>" aria-label="<?php esc_attr_e('رمز عبور', 'voorodak'); ?>" autocomplete="new-password">
                            <button type="button" class="voorodak-password-toggle" aria-label="<?php esc_attr_e('نمایش رمز عبور', 'voorodak'); ?>" aria-pressed="false">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            </button>
                        </div>
                        <?php endif; ?>
                        <?php if ($otp_boxed): ?>
                            <label class="voorodak__otp-label"><?php esc_html_e('کد تایید', 'voorodak'); ?></label>
                            <div class="voorodak__otp-boxes" data-target="voorodak__otp">
                                <?php for ($i = 0; $i < absint($otp_length); $i++): ?>
                                    <input type="text" class="voorodak__otp-box" aria-label="<?php echo esc_attr(sprintf(/* translators: 1: Digit index, 2: Total length */ __('رقم %1$d از %2$d کد تایید', 'voorodak'), $i + 1, absint($otp_length))); ?>" inputmode="numeric" maxlength="<?php echo $i === 0 ? esc_attr($otp_length) : '1'; ?>" autocomplete="<?php echo $i === 0 ? 'one-time-code' : 'off'; ?>">
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" class="otp-field1" name="voorodak__otp" value="">
                        <?php else: ?>
                            <input type="text" class="otp-field1" name="voorodak__otp" placeholder="<?php esc_attr_e('کد تایید', 'voorodak'); ?>" aria-label="<?php esc_attr_e('کد تایید', 'voorodak'); ?>" inputmode="numeric" maxlength="<?php echo esc_attr($otp_length) ?>" autocomplete="one-time-code">
                        <?php endif; ?>
                    </div>
                    <?php if ($show_password_login_forms): ?>
                    <div class="voorodak__wrapper-main-box-action">
                        <a href="#voorodak__wrapper-main-password"><?php esc_html_e('ورود با رمز عبور', 'voorodak'); ?>
                            <svg width="12" height="12" data-slot="icon" aria-hidden="true" fill="none" stroke-width="3" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.75 19.5 8.25 12l7.5-7.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="voorodak__wrapper-main-box-timer">
                        <div class="voorodak__wrapper-main-box-timer-countdown">
                            <span>02:00</span>
                            <?php esc_html_e('تا دریافت مجدد کد', 'voorodak'); ?>
                        </div>
                        <div class="voorodak__wrapper-main-box-timer-resend" style="display: none;" role="button" tabindex="0" aria-label="<?php esc_attr_e('دریافت مجدد کد تایید', 'voorodak'); ?>">
                            <?php esc_html_e('دریافت کد', 'voorodak'); ?>
                            <svg width="12" height="12" data-slot="icon" aria-hidden="true" fill="none" stroke-width="3" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.75 19.5 8.25 12l7.5-7.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                    </div>
                    <button type="submit" id="voorodak__submit-otp"><?php esc_html_e('تایید', 'voorodak'); ?></button>
                </form>
                <?php if ($show_password_login_forms): ?>
                <form method="post" action="" class="voorodak__wrapper-main-box" id="voorodak__wrapper-main-password" style="display: none">
                    <div class="voorodak__wrapper-main-box-title" role="heading" aria-level="2"><?php esc_html_e('رمز عبور را وارد کنید', 'voorodak'); ?></div>
                    <div class="voorodak__wrapper-main-box-field">
                        <div class="voorodak-password-control">
                            <input type="password" name="voorodak__password" placeholder="<?php esc_attr_e('رمز عبور', 'voorodak'); ?>" aria-label="<?php esc_attr_e('رمز عبور', 'voorodak'); ?>" autocomplete="current-password">
                            <button type="button" class="voorodak-password-toggle" aria-label="<?php esc_attr_e('نمایش رمز عبور', 'voorodak'); ?>" aria-pressed="false">
                                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="voorodak__wrapper-main-box-action">
                        <a href="#voorodak__wrapper-main-otp"><?php esc_html_e('ورود با رمز یکبار مصرف', 'voorodak'); ?>
                            <svg width="12" height="12" data-slot="icon" aria-hidden="true" fill="none" stroke-width="3" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.75 19.5 8.25 12l7.5-7.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </a>
                        <a href="#voorodak__wrapper-main-forget"><?php esc_html_e('فراموشی رمز عبور', 'voorodak'); ?>
                            <svg width="12" height="12" data-slot="icon" aria-hidden="true" fill="none" stroke-width="3" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.75 19.5 8.25 12l7.5-7.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </a>
                    </div>
                    <button type="submit" id="voorodak__submit-password"><?php esc_html_e('تایید', 'voorodak'); ?></button>
                </form>
                <form method="post" action="" class="voorodak__wrapper-main-box" id="voorodak__wrapper-main-forget" style="display: none">
                    <div class="voorodak__wrapper-main-box-title" role="heading" aria-level="2"><?php esc_html_e('فراموشی رمز عبور', 'voorodak'); ?></div>
                    <div class="voorodak__wrapper-main-box-field">
                        <input type="text" name="voorodak__username-forget" placeholder="<?php esc_attr_e('شماره موبایل یا ایمیل', 'voorodak'); ?>" aria-label="<?php esc_attr_e('شماره موبایل یا ایمیل', 'voorodak'); ?>" autocomplete="username">
                    </div>
                    <?php if ($captcha_challenge_forget): ?>
                    <div class="voorodak__wrapper-main-box-field voorodak__captcha-field">
                        <label for="voorodak-captcha-forget"><?php echo esc_html($captcha_challenge_forget['question']); ?></label>
                        <input id="voorodak-captcha-forget" type="text" name="voorodak__captcha" placeholder="<?php esc_attr_e('کد امنیتی', 'voorodak'); ?>" aria-label="<?php esc_attr_e('کد امنیتی', 'voorodak'); ?>" inputmode="numeric" autocomplete="off">
                        <input type="hidden" name="voorodak__captcha-token" value="<?php echo esc_attr($captcha_challenge_forget['token']); ?>">
                    </div>
                    <?php endif; ?>
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="voorodak__website-field">
                    <button type="submit" id="voorodak__submit-forget"><?php esc_html_e('تایید', 'voorodak'); ?></button>
                </form>
                <form method="post" action="" class="voorodak__wrapper-main-box" id="voorodak__wrapper-main-otp-reset" style="display: none">
                    <div class="voorodak__wrapper-main-box-title" role="heading" aria-level="2"><?php esc_html_e('کد تایید', 'voorodak'); ?></div>
                    <div class="voorodak__wrapper-main-box-field">
                        <?php if ($otp_boxed): ?>
                            <label class="voorodak__otp-label"><?php esc_html_e('کد تایید', 'voorodak'); ?></label>
                            <div class="voorodak__otp-boxes" data-target="voorodak__otp-reset">
                                <?php for ($i = 0; $i < absint($otp_length); $i++): ?>
                                    <input type="text" class="voorodak__otp-box" aria-label="<?php echo esc_attr(sprintf(/* translators: 1: Digit index, 2: Total length */ __('رقم %1$d از %2$d کد تایید', 'voorodak'), $i + 1, absint($otp_length))); ?>" inputmode="numeric" maxlength="<?php echo $i === 0 ? esc_attr($otp_length) : '1'; ?>" autocomplete="<?php echo $i === 0 ? 'one-time-code' : 'off'; ?>">
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" class="otp-field2" name="voorodak__otp-reset" value="">
                        <?php else: ?>
                            <input type="text" class="otp-field2" name="voorodak__otp-reset" placeholder="<?php esc_attr_e('کد تایید', 'voorodak'); ?>" aria-label="<?php esc_attr_e('کد تایید', 'voorodak'); ?>" inputmode="numeric" maxlength="<?php echo esc_attr($otp_length) ?>" autocomplete="one-time-code">
                        <?php endif; ?>
                    </div>
                    <div class="voorodak__wrapper-main-box-timer">
                        <div class="voorodak__wrapper-main-box-timer-countdown">
                            <span>02:00</span>
                            <?php esc_html_e('تا دریافت مجدد کد', 'voorodak'); ?>
                        </div>
                        <div class="voorodak__wrapper-main-box-timer-resend" style="display: none;" role="button" tabindex="0" aria-label="<?php esc_attr_e('دریافت مجدد کد تایید', 'voorodak'); ?>">
                            <?php esc_html_e('دریافت کد', 'voorodak'); ?>
                            <svg width="12" height="12" data-slot="icon" aria-hidden="true" fill="none" stroke-width="3" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15.75 19.5 8.25 12l7.5-7.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                    </div>
                    <button type="submit" id="voorodak__submit-otp-reset"><?php esc_html_e('تایید', 'voorodak'); ?></button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($show_password_login_forms): ?>
            <form method="post" action="" class="voorodak__wrapper-main-box" id="voorodak__wrapper-main-reset"<?php echo !$reset_token ? ' style="display: none"' : ''; ?>>
                <div class="voorodak__wrapper-main-box-title" role="heading" aria-level="2"><?php esc_html_e('تغییر رمز عبور', 'voorodak'); ?></div>
                <div class="voorodak__wrapper-main-box-field">
                    <div class="voorodak-password-control">
                    <input type="password" name="voorodak__new-password" placeholder="<?php esc_attr_e('رمز عبور جدید', 'voorodak'); ?>" aria-label="<?php esc_attr_e('رمز عبور جدید', 'voorodak'); ?>" autocomplete="new-password">
                    <button type="button" class="voorodak-password-toggle" aria-label="<?php esc_attr_e('نمایش رمز عبور', 'voorodak'); ?>" aria-pressed="false">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
                </div>
                <div class="voorodak__wrapper-main-box-field">
                    <div class="voorodak-password-control">
                    <input type="password" name="voorodak__new-password2" placeholder="<?php esc_attr_e('تکرار رمز عبور جدید', 'voorodak'); ?>" aria-label="<?php esc_attr_e('تکرار رمز عبور جدید', 'voorodak'); ?>" autocomplete="new-password">
                    <button type="button" class="voorodak-password-toggle" aria-label="<?php esc_attr_e('نمایش رمز عبور', 'voorodak'); ?>" aria-pressed="false">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                    </button>
                </div>
                </div>
                <input type="hidden" name="voorodak__reset-token" value="<?php echo esc_attr($reset_token); ?>">
                <button type="submit" id="voorodak__submit-reset"><?php esc_html_e('تایید', 'voorodak'); ?></button>
            </form>
            <?php endif; ?>
        </div>
        <div class="voorodak__wrapper-messages" role="status" aria-live="polite" aria-atomic="true"></div>
        <?php if ($template == 'zarinpal'): ?>
        </div><div class='voorodak__wrapper-main-left' style="background-color: <?php echo ($cover) ? 'transparent' : esc_attr($button_color) ; ?>">
            <?php if ($cover && !wp_is_mobile()): ?>
                <img src="<?php echo esc_url($cover); ?>" width="600" height="500" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
            <?php endif; ?>
        </div></div>
        <?php endif; ?>
    </div>
</div>
