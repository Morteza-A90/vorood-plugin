# Changelog

## 6.2.0

### UI / UX
- بازطراحی progressive پنل تنظیمات با هدر محصول، نوار جستجو، ناوبری tab قابل استفاده با کیبورد و وضعیت تغییرات ذخیره‌نشده.
- طراحی مجدد responsive کنترل‌های تنظیمات، checkboxها، فرم‌ها، کارت‌ها و ناحیه راهنما بدون تغییر کلیدهای تنظیمات موجود.
- اضافه شدن جستجوی داخل هر بخش تنظیمات بدون دست‌کاری داده‌های ذخیره‌شده.
- بازطراحی frontend مشترک برای قالب‌های `default`، `digikala` و `zarinpal` با حفظ انتخاب قالب و رنگ‌های سفارشی.
- اضافه شدن نمایش/پنهان‌سازی رمز عبور با کنترل accessible.
- بهبود OTP، captcha، پیام‌های خطا/موفقیت، focus state، mobile RTL و reduced-motion.

### Fixes
- جلوگیری از submit طبیعی فرم‌های AJAX و درخواست/reload تکراری هنگام کلیک یا فشردن Enter.
- پشتیبانی صحیح از submit فرم‌های ورود/ثبت‌نام با کلید Enter در flow مبتنی بر AJAX.


## 6.1.1

### Production readiness
- Fixed email registration incorrectly writing the email address into mobile/billing phone metadata.
- Tightened public-registration role safety to reject content-management and WooCommerce management roles such as Author, Editor and Shop Manager by default.
- Hardened Cloudflare client-IP handling so `CF-Connecting-IP` is trusted only when the immediate peer is within Cloudflare's published proxy ranges; added filters for custom proxy setups.
- Fixed legacy password-reset compatibility so expired old tokens cannot be revived during fallback lookup.
- Removed fragile `realpath(WP_PLUGIN_DIR)` runtime guards that could break settings/assets on symlinked or non-standard WordPress installations.
- Switched WooCommerce logout redirect integration to the correct filter registration.
- Migrates legacy temporary option payloads proactively once, avoiding recurring compatibility scans on normal requests.
- Escapes legacy option-prefix SQL patterns correctly to avoid wildcard overmatching.

### Performance / UX
- User CSV export now streams directly through an authenticated `admin-post` endpoint and preloads user meta in chunks instead of buffering the whole CSV inside JSON.
- Separated login-password validation from new-password policy so legacy users with valid older passwords are not blocked by client-side registration rules.
- Added matching client-side password-policy feedback for registration/reset flows while keeping server validation authoritative.
- Login and forgotten-password forms now receive independent captcha challenges.
- Dynamic OTP descriptions are inserted as text rather than HTML.
- SMS logs are rendered/stored as plain text with a timestamp separator.

## 6.1.0

### Security
- Removed commercial licensing, proprietary updater and anti-tamper runtime gates.
- Reworked OTP and reset-token storage around HMAC verifiers.
- Separated OTP delivery quota from authentication failure limiting.
- Removed PHP session based rate limiting.
- Prevented public assignment of privileged registration roles.
- Prevented users from managing their own ban state; bans now affect standard WordPress authentication too.
- Applied the administrator-login restriction consistently to OTP and password flows.
- Hardened settings validation, redirects, CSV output and sensitive logging.

### Compatibility / integrations
- Preserved existing templates, options, meta keys, shortcodes and public hooks.
- Corrected WordPress `wp_login` lifecycle compatibility for custom logins.
- Fixed Bale hooks being incorrectly dependent on a pattern SMS gateway.
- Fixed WooCommerce order/Bale placeholder mapping and dynamic status settings preservation.
- Added per-order capability checks to the shipping bulk action.
- Scoped SOAP requirements to providers that need them.

### Performance
- Moved temporary state to WordPress transients/object cache with lazy legacy migration.
- Lazily initializes the SMS service for authentication requests.
- Loads frontend assets only on the login page/shortcode pages unless compatibility mode is enabled.
- Paged and hardened CSV export.

### UI / UX
- Modernized the existing frontend and admin UI progressively.
- Improved RTL/mobile form behavior, focus states, validation and accessibility metadata.
- Preserved all three existing frontend templates.
