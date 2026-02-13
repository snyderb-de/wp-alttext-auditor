# Changelog

All notable changes to this project will be documented in this file.

## 2.2.0 - 2026-02-13
- Fixed scan report table dates not matching site timezone (#5).
- Fixed dashboard statistics showing incorrect "last scan" times (#4).
- Replaced all deprecated `current_time('mysql')` and `current_time('timestamp')` calls with `wp_date()` and `time()`.
- Enabled inline alt-text editing for images without attachment IDs (#1).
- Added thumbnail fallback using image src URL for non-attachment images.
- External images with no post context now show "External image - edit manually" hint.

## 2.1.7 - 2026-02-12
- Updated warning styling to default to black and turn red at thresholds.

## 2.1.6 - 2026-02-12
- Added a warning when auto-cleanup is set to "Never".
- Clarified how report retention and auto-cleanup interact.

## 2.1.5 - 2026-02-12
- Changed default auto-cleanup to keep reports and scans for 1 year.

## 2.1.4 - 2026-02-12
- Moved the Settings tab to the top-level navigation (alongside Manager and Audit).

## 2.1.3 - 2026-02-12
- Added a Settings tab with report retention controls.
- Added a debug logging toggle with log size warning and clear log button.
- Moved automatic daily scanning and data management controls into Settings.

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
