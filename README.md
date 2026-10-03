# Voorodak 6.2.0 (open refactor)

A backward-compatible modernization of the Voorodak WordPress authentication and SMS plugin.

## Requirements

- WordPress 5.7+
- PHP 7.2+
- SOAP only for legacy SMS providers that require it
- WooCommerce is optional

## Core capabilities

- Mobile, email and username login
- OTP and password authentication
- Mobile/email registration
- Password recovery
- Configurable registration fields
- SMS providers and pattern messages
- Bale OTP/notifications
- WooCommerce My Account / checkout integration
- Protected pages, user banning and blacklists
- Three existing frontend templates: `default`, `digikala`, `zarinpal`

## UI / UX in 6.2.0

- Redesigned responsive admin settings surface without changing saved option keys
- Search within the active settings section
- Keyboard-accessible settings tabs and clearer unsaved-change feedback
- Modernized shared frontend design system while preserving all three existing templates
- Password visibility controls, improved Enter-key submit behavior and accessible resend controls
- Improved OTP, captcha, focus, validation, RTL/mobile and reduced-motion states

## Shortcodes

- `[voorodak]` — full authentication form
- `[voorodak_account_btn]` — account/login button
- `[voorodak_account_btn style="astra"]` — Astra-compatible button
- `[voorodak_account_btn showname="yes"]` — show the logged-in user's display name

The plugin detects `[voorodak]` in normal post/page content and loads frontend assets only where needed. For page builders that store shortcode configuration outside `post_content`, enable the global shortcode-assets compatibility option in the developer settings tab.

## Extension hooks

### Before custom login

```php
add_filter('voorodak_pre_do_login', function ($decision, $user_id) {
    return $decision;
}, 10, 2);
```

The filter receives an array containing `allow` and `message`.

### After custom login

```php
add_action('voorodak_after_do_login', function ($user_id) {
    // Custom integration.
});
```

### After registration

```php
add_action('voorodak_after_do_register', function ($user_id) {
    // Custom integration.
});
```

### Registration-role policy

```php
add_filter('voorodak_registration_role_is_safe', function ($is_safe, $role_key, $role) {
    return $is_safe;
}, 10, 3);
```

Do not return `true` for a privileged role unless public self-registration into that role is intentionally required.

### Frontend asset loading

```php
add_filter('voorodak_should_enqueue_frontend_assets', function ($should_enqueue, $page_id, $settings) {
    return $should_enqueue;
}, 10, 3);
```

Useful for page builders that store widget/shortcode data outside normal post content.

### Settings extensions

```php
add_filter('voorodak_sanitized_settings', function ($sanitized, $raw_input, $existing) {
    // Validate and append custom extension settings here.
    return $sanitized;
}, 10, 3);
```

## Security notes

- OTPs and new password-reset tokens are stored as HMAC verifiers, not reusable plaintext secrets.
- Public registration rejects privileged WordPress roles.
- Authentication failures and OTP delivery have independent rate-limit state.
- SMS logs redact configured credentials and OTP values.
- `CF-Connecting-IP` is accepted only from trusted Cloudflare source ranges by default; custom reverse proxies can extend the trusted range filter.
- Legacy provider endpoints that still use plain HTTP are shown as a warning in settings and are kept only for compatibility until their current provider API can be verified.

## Upgrade notes from 6.0.1

The plugin keeps the `voorodak_options` option and existing public meta/hook names. A one-time migration removes the obsolete commercial license key and normalizes an unsafe configured registration role. Legacy temporary state remains readable and is migrated into WordPress transients without changing active OTP/reset state.

See `REFACTOR-NOTES.md` for the detailed engineering changelog and known limitations.
