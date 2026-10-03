# Development & Modification Rules — Voorodak Plugin

## Core Principles
* **Preserve Existing Architecture**: Maintain the existing class structure, traits, file organization, option schema, hooks, and execution flow.
* **Minimal Targeted Changes**: Make only the smallest necessary code modifications. Avoid refactoring working code or altering surrounding logic.
* **Preserve Existing Functionality & Compatibility**: Ensure authentication flows, OTP logic, SMS gateway integrations, WooCommerce hooks, page locks, and security mechanisms continue to operate without regression.
* **Contract Integrity**: Do not change public API signatures, filter names, action hooks, shortcodes (`[voorodak]`, `[voorodak_account_btn]`), database option key (`voorodak_options`), transient keys, URLs, or user flows unless explicitly required.
* **WordPress Coding Standards**: Adhere strictly to WordPress coding standards, sanitization (`sanitize_text_field`, `sanitize_email`, etc.), and escaping practices (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).

## Internationalization & WPML Guidelines
* **WordPress Native i18n**: Use standard WordPress internationalization functions (`__`, `_e`, `esc_html__`, `esc_html_e`, `esc_attr__`, `esc_attr_e`, `_x`) with text domain `'voorodak'`.
* **No Custom Translation Systems**: Do not introduce custom translation frameworks or secondary key-value maps when native WordPress/WPML functions can solve the problem.
* **Isolated WPML Compatibility**: Keep WPML-specific checks and filters (`wpml_object_id`, `wpml_register_single_string`, `wpml-config.xml`) conditional and non-intrusive.
* **Translatable JavaScript**: Pass translated JS strings via `wp_localize_script` or `wp.i18n` rather than hardcoding UI strings inside JavaScript files.
* **Language-Aware References**: Ensure stored page IDs (`login_page_id`) and URLs pass through WordPress/WPML link and object translation filters when rendered.

## Modification Discipline
* **Understand Before Modifying**: Identify the precise root cause before editing code.
* **Scope Control**: Do not touch unrelated functions, assets, or administrative interfaces.
* **Regression Verification**: Review and test affected workflows after any modification.
