# Voorodak 6.2.0 - Refactor Notes

This fork is a careful modernization of the existing Voorodak 6.0.1 codebase. It is intentionally not a ground-up rewrite.

## Open-source bootstrap

- Removed commercial license/domain verification and the proprietary update-server coupling.
- Removed the source SHA-256 anti-tamper gate so legitimate source changes no longer disable the plugin.
- Removed the global ionCube requirement.
- SOAP is now checked only when a provider that requires `SoapClient` is used.
- A one-time migration removes the obsolete `license_key` setting while preserving supported existing options.
- Existing GPL and original author attribution remain intact.

## Authentication and security

- OTP values are stored as HMAC verifiers rather than plaintext.
- OTP resend cooldown, validation, consumption, and delivery quota are explicit and independently tracked.
- Authentication-failure rate limiting is separated from OTP delivery limiting and from general SMS notifications.
- PHP session based limiting was removed in favor of WordPress transients/object cache.
- Password input is no longer mutated by generic text sanitizers.
- Reset tokens are stored as HMAC verifiers with direct lookup and legacy-token compatibility.
- Invalid reset-token shapes are rejected before the legacy database compatibility scan.
- Reset links are generated from the configured WordPress login page instead of a client supplied URL.
- Public registration cannot assign privileged WordPress roles. Existing unsafe role settings are normalized during migration and again at runtime.
- Public registration also rejects content-authoring and WooCommerce-management roles (for example Author, Editor and Shop Manager) unless an integrator explicitly opts in through the role-safety filter.
- Cloudflare client-IP forwarding is accepted only when the direct peer belongs to a trusted Cloudflare range; arbitrary clients can no longer spoof `CF-Connecting-IP` to rotate limiter keys.
- User bans can only be managed by administrators and now apply to WordPress' standard authentication pipeline as well as Voorodak login.
- The "disable administrator login" setting now covers every Voorodak login method, including OTP.
- Successful Voorodak logins fire the standard `wp_login` lifecycle event where appropriate.
- Remember-me behavior is configurable while preserving the legacy always-on default.
- Captcha/honeypot and existing blacklist features remain available.

## Data and settings

- Settings saving now uses a schema-like allowlist for booleans, enums, numbers, URLs, colors, text and allowed HTML.
- Provider credentials are preserved byte-for-byte and escaped only on output.
- Dynamic WooCommerce SMS status settings are preserved even when a status/plugin is temporarily unavailable.
- `voorodak_sanitized_settings` is available for extensions that add their own settings.
- Legacy temporary option payloads are proactively migrated once to native WordPress transients, with lazy migration retained as a compatibility fallback.
- Expired legacy reset tokens are rejected during compatibility lookup rather than being temporarily revived.
- Legacy temporary option SQL prefixes are escaped as literal prefixes before cleanup/migration queries.

## Integrations and notifications

- SMS sending no longer changes authentication rate-limit state.
- SOAP WSDL cache behavior is scoped to individual clients instead of changing the PHP process globally.
- Bale notification hooks are independent from the selected SMS gateway type.
- WooCommerce/Bale order notification placeholder alignment was corrected.
- WooCommerce order notes now distinguish successful and failed SMS attempts.
- Bulk order status changes verify edit capability for each order.
- Legacy HTTP SMS transports are clearly flagged in the admin UI instead of being silently rewritten to unverified endpoints.

## Performance

- The authentication controller lazily creates the SMS service only when SMS is actually needed.
- Frontend assets load on the configured login page or when `[voorodak]` is detected in page content.
- A compatibility switch is available for builders that store shortcodes outside `post_content`.
- Admin assets remain scoped to Voorodak screens.
- CSV user export streams directly to the browser, pages through users, preloads user meta in batches, and protects spreadsheet cells from formula injection.
- SMS logs are capped, non-autoloaded, and sensitive values are redacted.

## UI / accessibility

- Existing `default`, `digikala`, and `zarinpal` templates remain supported.
- Frontend styling was modernized progressively rather than replacing the templates.
- Admin settings now include a product header, active-section search, keyboard-accessible tabs and unsaved-change feedback without changing saved option keys.
- Admin controls, cards, switches, responsive tables and sticky save actions were redesigned with scoped styles.
- Password fields include accessible visibility controls and authentication forms handle Enter-key submit without duplicate native/AJAX requests.
- OTP resend controls are keyboard accessible and OTP digit fields expose indexed accessible labels.
- Form focus states, validation states, OTP fields, autocomplete hints, mobile behavior and reduced-motion behavior were improved.
- Dynamic validation text is inserted as text rather than unsafe HTML.
- Login-password validation no longer applies the stricter new-password policy to existing accounts; registration/reset password UX mirrors the server policy instead.
- Login and password-recovery forms use independent captcha challenges.
- Authentication status messages use an ARIA live region.
- The bundled font file was removed; the UI uses a system font stack.
- `wp_body_open()` is present in the custom login page template.

## Compatibility intentionally preserved

- `voorodak_options` option name.
- Existing user meta and WooCommerce meta keys.
- `[voorodak]` and `[voorodak_account_btn]` shortcodes.
- `voorodak_pre_do_login`, `voorodak_after_do_login`, and `voorodak_after_do_register` hooks.
- Current SMS provider implementations unless specifically hardened without changing their API contract.
- Existing frontend template choices.

## Remaining high-impact decisions / limitations

- Several legacy SMS providers still use HTTP endpoints in the compatibility implementation. They should be migrated only after each provider's current API contract is verified.
- Non-Cloudflare reverse proxies should extend `voorodak_trusted_proxy_ip_ranges` / `voorodak_client_ip` if they need forwarded client-IP support.
- Administrator-configured external redirect URLs are intentionally still supported.
- Original Voorodak/TakTheme branding is retained until an explicit rebranding decision is made.
- Full visual browser regression testing still requires running the plugin inside a real WordPress/WooCommerce test site.
