# Project Documentation — Voorodak Plugin

## Project Overview
* **Plugin Name**: افزونه ورود ثبت نام پیامکی ورودک (Voorodak SMS Auth)
* **Current Version**: 6.2.0
* **Text Domain**: voorodak
* **Purpose**: Provides SMS-based and OTP authentication, passwordless login, user registration, password recovery, WooCommerce login integration, and event-driven SMS/Bale messenger notifications.

## Current Architecture
The plugin follows an object-oriented class structure using traits and specialized classes:
* `voorodak.php`: Main entry point, constant definitions (`VOORODAK_VERSION`, `VOORODAK_OPTION`), plugin activation hook (`voorodak_set_default_login_page_id`), PHP compatibility check, and core file inclusions.
* `trait Voorodak_Options`: Centralized helper trait in `includes/class-voorodak.php` providing settings retrieval (`get_settings`), notice HTML rendering (`add_message`), IP rate limiting, rate-limit transient management, captcha generation, and input validation helpers.
* `class Voorodak_Base`: Registers admin menu (`voorodak-settings`), registers and sanitizes settings (`validate_settings`), enqueues frontend and admin assets (`enqueue_assets`, `enqueue_admin_assets`), appends custom columns to user table, and handles admin test SMS AJAX (`submit_test_phone`).
* `class Voorodak_Auth`: Handles public AJAX authentication endpoints (`wp_ajax_nopriv_voorodak__submit-username`, `submit_otp`, `submit_password`, `submit_forget`, `submit_otp_reset`, `submit_reset`), user lookup by mobile/email/username, OTP generation and HMAC validation, password management, login (`do_login`), and registration (`do_register`).
* `class Voorodak_Templates`: Intercepts template loading (`template_include` for `html-template.php`), processes shortcodes (`[voorodak]`, `[voorodak_account_btn]`), and manages page redirect rules for locked pages and WooCommerce.
* `class Voorodak_SMS`: Base class handling gateway integration for 25+ Iranian SMS providers and Bale Messenger API (`bale_send`), payload encoding, logging, and error handling.
* `class Voorodak_SMS_Notifications`: Extends `Voorodak_SMS` in `includes/class-voorodak-sms-notifications.php`. Registers hooks on login (`voorodak_after_do_login`, `wp_login`), registration (`voorodak_after_do_register`), comments (`comment_post`), and WooCommerce order status changes.
* `includes/helper-functions.php`: Handles CSV user export (`voorodak_export_users_csv`), page locking meta box (`_lock_voorodak`), cache rejection rules, user ban authentication filter (`wp_authenticate_user`), log formatting, and registration role safety checks.

## Main Views & Assets
* `view/html-shortcode.php`: Main frontend auth form template (rendered by `[voorodak]` shortcode). Contains multi-step forms for username entry, OTP verification, password entry, forgot password, OTP reset, and password reset.
* `view/html-settings.php`: Tabbed admin settings page.
* `view/html-template.php`: Custom standalone page template loaded on the configured login page.
* `view/html-notice-error.php` / `view/html-notice-success.php`: Notice markup templates used by `add_message()`.
* `assets/js/script.js`: Frontend JavaScript controller. Handles AJAX requests, form step transitions, client-side input validation, password visibility toggle, countdown timers, and OTP box autofill/navigation.
* `assets/js/script-admin.js`: Admin JS controller for tab navigation, settings search, color picker, test SMS, and CSV export.

## Authentication Flow
1. **Username Submission**: Client submits identity via AJAX to `voorodak__submit-username`. System validates rate limits and captcha, looks up user ID, and sends OTP SMS/email or prompts for password based on plugin settings.
2. **OTP / Registration**: Client submits 6-digit OTP to `voorodak__submit-otp`. System verifies HMAC transient. If user exists, triggers `do_login()`. If user is new and registration is permitted, collects required fields (first name, last name, national code, email, password), invokes `do_register()`, and logs in.
3. **Password / Recovery**: Users can switch to password auth (`voorodak__submit-password`) or request password reset via email link / OTP (`voorodak__submit-forget`, `voorodak__submit-otp-reset`, `voorodak__submit-reset`).

## Frontend Rendering
* UI rendered via shortcode `[voorodak]` or custom template `html-template.php`.
* Forms toggle visibility dynamically via JS without full page reloads.
* Server responses return JSON payloads containing message HTML formatted by `add_message()` using notice templates.

## Translation System (Current State)
* **Header Declaration**: `voorodak.php` specifies `Text Domain: voorodak` and `Domain Path: /languages`.
* **Missing Text Domain Loader**: `load_plugin_textdomain()` is **not** called anywhere in the codebase.
* **Missing Language Files**: No `/languages` directory exists in the plugin repository, and no `.pot`, `.po`, or `.mo` files are present.
* **Hardcoded Strings**: 99%+ of frontend template labels, button texts, server AJAX responses, notice messages, email subjects/bodies, and JavaScript client-side validation errors are hardcoded directly in Persian string literals.
* **Minimal i18n Functions**: Only 10 lines in the entire codebase currently use WordPress translation functions (`__`, `_e`, `esc_html__`, `esc_attr_e`).

## WPML Compatibility (Current State)
* **No WPML Integration**: The plugin currently contains no explicit WPML hooks, filters, API calls (`wpml_object_id`, `wpml_register_single_string`), or configuration (`wpml-config.xml`).
* **Language-Dependent Page IDs**: Option `login_page_id` stores a single static page ID without translating it to the active WPML language ID during runtime page lookups or link generation.
* **Dynamic Options**: Configurable option strings (such as `form_name` and `term_editor`) are rendered directly without WPML string translation filters.

## Known Multilingual Issue
When WPML switches the website language (e.g. to English or Arabic):
* Frontend form titles, placeholder texts, labels, buttons, and helper texts remain in Persian.
* Server-side AJAX error and success notices are returned in Persian.
* Client-side JavaScript validation messages appear in Persian.
* Navigation links and redirects point to the default language page ID instead of the current language page variant.

## Important Constraints
* Must preserve `voorodak_options` option structure and key names.
* Must preserve action hooks (`voorodak_pre_do_login`, `voorodak_after_do_login`, `voorodak_after_do_register`).
* Must preserve shortcodes `[voorodak]` and `[voorodak_account_btn]`.
* Must preserve security, rate-limiting, and OTP HMAC verification logic.
* Must retain existing class architecture and theme template options (`default`, `digikala`, `zarinpal`).

## Planned Scope
Future work will focus strictly on improving multilingual and WPML compatibility through minimal, targeted changes:
1. Registering text domain via `load_plugin_textdomain` and creating a standard `.pot` template.
2. Wrapping hardcoded PHP user-facing strings in WordPress i18n functions (`__`, `esc_html__`, `esc_attr__`).
3. Localizing JavaScript validation and interface strings via `wp_localize_script`.
4. Adding WPML language page resolution (`wpml_object_id`) for `login_page_id`.
5. Registering dynamic options (`form_name`, `term_editor`) for WPML translation via `wpml-config.xml` or filter hooks.
