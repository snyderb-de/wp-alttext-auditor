# Project Plan - Alt Text Auditor

## Scope
Consolidated TODOs and action items found across repo documentation and checklists. No inline TODO/FIXME markers were found in code comments, so this plan is sourced from the existing docs and checklists and cross-checked where possible.

## Sources Reviewed
- PLUGIN-CHECK-TODO.md
- WHATS-NEXT.md
- docs/SECURITY-AUDIT-V1.3.md
- docs/SECURITY-AUDIT.md
- readme.txt
- alt-text-auditor.php
- includes/

## Status Legend
- Open: Not yet completed or still required
- Verify: Marked as completed in docs or partially verified, but needs confirmation in current code/package
- Optional: Non-blocking improvements or future enhancements

## A. Critical Security Fixes (v1.3 audit)
1. Completed - Add per-post capability check before HTML modification in `ajax_update_audit_record` to prevent unauthorized post edits. Source: docs/SECURITY-AUDIT-V1.3.md
2. Completed - Escape alt-text before `setAttribute()` when updating post content to prevent stored XSS. Source: docs/SECURITY-AUDIT-V1.3.md

## B. High Priority Security & Data Integrity (v1.3 audit)
1. Completed - Enforce alt-text max length of 255 characters before saving; return an error if exceeded. Source: docs/SECURITY-AUDIT-V1.3.md
2. Completed - Re-check capabilities after `switch_to_blog()` in network dashboard loop. Source: docs/SECURITY-AUDIT-V1.3.md

## C. Medium/Low Priority Security & Stability (v1.3 audit)
1. Open - Add optimistic locking or conflict warning for concurrent post-content updates. Source: docs/SECURITY-AUDIT-V1.3.md
2. Open - Add pagination and caching for network dashboard stats to avoid timeouts on large networks. Source: docs/SECURITY-AUDIT-V1.3.md
3. Optional - Sanitize network settings checkbox with `absint()` for clarity. Source: docs/SECURITY-AUDIT-V1.3.md

## D. Plugin Check / WordPress.org Submission Fixes
1. Verify - Text domain consistency. Docs mention mixed domains, but current code uses `alt-text-auditor`. Re-run Plugin Check to confirm. Source: PLUGIN-CHECK-TODO.md, alt-text-auditor.php
2. Completed - Remove `.DS_Store` files from repo/package (removed from working tree; `.git` artifacts remain but are not distributed). Source: PLUGIN-CHECK-TODO.md
3. Completed - Ensure `.claude/` is excluded from distribution package (excluded via `.distignore`). Source: PLUGIN-CHECK-TODO.md
4. Verify - Readme "Tested up to" value updated to 6.9. Current readme.txt shows 6.9, but must be validated by actual testing. Source: PLUGIN-CHECK-TODO.md, readme.txt
5. Completed - Readme tags reduced to 5. Current readme.txt shows 5 tags. Source: PLUGIN-CHECK-TODO.md, readme.txt
6. Completed - Remove or guard remaining `error_log()` calls in production code (replaced with `alttext_auditor_log()` hook). Source: PLUGIN-CHECK-TODO.md, code scan
7. Open - Add translator comments for key i18n strings (50+ instances referenced). Source: PLUGIN-CHECK-TODO.md, WHATS-NEXT.md
8. Open - Fix unordered translation placeholders (8 instances). Source: PLUGIN-CHECK-TODO.md, WHATS-NEXT.md
9. Open - Audit and add missing escaping (`esc_html`, `esc_attr`, `esc_url`) in flagged outputs. Source: PLUGIN-CHECK-TODO.md, WHATS-NEXT.md
10. Open - Replace `parse_url()` with `wp_parse_url()` where flagged in includes/class-audit-scanner.php:498. Source: PLUGIN-CHECK-TODO.md, WHATS-NEXT.md
11. Optional - Remove deprecated `load_plugin_textdomain()` if targeting WP 5.0+. Source: PLUGIN-CHECK-TODO.md, WHATS-NEXT.md
12. Optional - Add object caching for frequently-run direct `$wpdb` queries beyond existing transients. Source: PLUGIN-CHECK-TODO.md, WHATS-NEXT.md
13. Optional - Use specific superglobals for nonces (e.g., `$_POST` for AJAX). Source: PLUGIN-CHECK-TODO.md
14. Optional - Prefix custom global variable names with plugin prefix. Source: PLUGIN-CHECK-TODO.md
15. Optional - Move admin page code out of main plugin file into separate files. Source: PLUGIN-CHECK-TODO.md

## E. Pre-Submission Testing & Validation
1. Open - Test plugin on WordPress 6.9 (fresh site) and verify no PHP warnings/errors. Source: WHATS-NEXT.md, PLUGIN-CHECK-TODO.md
2. Open - Test on PHP 7.4 and 8.2. Source: WHATS-NEXT.md, PLUGIN-CHECK-TODO.md
3. Open - Run Plugin Check again after fixes. Source: WHATS-NEXT.md, PLUGIN-CHECK-TODO.md
4. Open - Validate readme.txt with the WordPress.org readme validator. Source: WHATS-NEXT.md
5. Open - Create required screenshots for WordPress.org (6 screenshots listed in WHATS-NEXT.md). Source: WHATS-NEXT.md
6. Open - Build submission ZIP excluding `.git`, `.github`, `.claude`, `node_modules`, `docs/`. Source: WHATS-NEXT.md

## F. Security Testing Checklist (v1.3 audit)
1. Open - Test HTML injection with malicious alt-text input.
2. Open - Test contributor attempting to edit admin’s post.
3. Open - Test network dashboard with 100+ sites.
4. Open - Test concurrent post editing.
5. Open - Test alt-text values longer than 255 characters.
6. Open - Test multisite capability checks after `switch_to_blog()`.
7. Open - Test all nonces still work after changes.
Source: docs/SECURITY-AUDIT-V1.3.md

## G. Legacy Audit Optional Improvements (v1.0.x)
1. Optional - Prefix CSS classes with plugin identifier to reduce theme conflicts. Source: docs/SECURITY-AUDIT.md
2. Optional - Improve JavaScript module isolation (currently IIFE). Source: docs/SECURITY-AUDIT.md
3. Optional - Standardize AJAX error handling with `wp_send_json_error()`. Source: docs/SECURITY-AUDIT.md
4. Optional - Add transient existence checks for graceful fallbacks. Source: docs/SECURITY-AUDIT.md
5. Optional - Replace `error_log()` usage with WordPress hooks (informational). Source: docs/SECURITY-AUDIT.md
6. Optional - Consider WP-CLI support for database operations. Source: docs/SECURITY-AUDIT.md

## H. Legacy Audit Testing Recommendations (v1.0.x)
1. Optional - Test all AJAX handlers with invalid nonces.
2. Optional - Test filter parameters with malicious input.
3. Optional - Test export functionality across user roles.
4. Optional - Test CSV export with formula-injection payloads.
5. Optional - Test database queries with unusual table prefixes.
6. Optional - Test JavaScript with XSS payloads.
7. Optional - Test CSS class conflicts with popular themes.
8. Optional - Test JS conflicts with popular plugins (Yoast, WooCommerce).
Source: docs/SECURITY-AUDIT.md

## I. Decision Points
1. Open - Decide whether to keep “WP” in the plugin name or rename to “Alt Text Auditor”. Source: PLUGIN-CHECK-TODO.md, WHATS-NEXT.md

## J. Maintenance
1. Optional - Schedule annual security review or before major version updates. Source: docs/SECURITY-AUDIT.md
