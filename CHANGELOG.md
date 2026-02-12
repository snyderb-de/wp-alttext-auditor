# Changelog

All notable changes to this project will be documented in this file.

## 2.1.2 - 2026-02-12
- Added a site search filter to the multisite network dashboard (filters current page).

## 2.1.1 - 2026-02-12
- Added a conflict warning for concurrent post-content updates.
- Added pagination and caching to the multisite network dashboard.
- Fixed ordered translation placeholders and added translator comments for key strings.
- Added missing output escaping in the HTML report and admin screens.
- Replaced remaining `parse_url()` usage with `wp_parse_url()`.

## 2.1.0 - 2026-02-12
- Unified Media menu into a single **Alt Text Auditor** page with Manager and Audit tabs.
- Redirected legacy Manager/Audit URLs to the unified page.
- Updated audit filters and links to use the unified page path.
- Replaced direct `error_log()` usage with `alttext_auditor_log()` hook.

## 2.0.0 - 2026-01-30
- Renamed plugin from "WP Alt Text Auditor" to "Alt Text Auditor" (removed "WP" prefix).
- Changed plugin slug: `wp-alttext-auditor` to `alt-text-auditor`.
- Updated text domain: `wp-alttext-auditor` to `alt-text-auditor`.
- Updated readme tags and tested-up-to value for WordPress.org submission.
