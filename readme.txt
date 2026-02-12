=== Alt Text Auditor ===
Contributors: thestrangeloop
Tags: alt-text, accessibility, media, wcag, multisite
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress plugin for managing and auditing alt-text across your entire site with inline editing, powerful audit dashboard, and full multisite support.

== Description ==

Alt Text Auditor helps you identify and fix images with missing alt-text across your entire WordPress site. Improve accessibility compliance and SEO with powerful scanning, auditing, and inline editing features. Works perfectly on both single-site and WordPress Multisite (Network) installations.

**Key Features:**

* **Inline Media Library Editing** - Edit alt-text directly in the WordPress Media Library with auto-save
* **Site-Wide Audit Dashboard** - Scan all published content and media library for missing alt-text
* **Real-Time Statistics** - Track progress with visual cards showing total images, missing alt-text counts, and percentages
* **User Attribution** - See which team members have the most missing alt-text
* **Advanced Filtering** - Filter results by user, content source, post type, or search by filename
* **Quick-Edit Functionality** - Add alt-text inline from audit results
* **CSV Export** - Export filtered results for offline analysis and reporting
* **Automatic Daily Scanning** - Optional background scanning via WP Cron with configurable batch sizes
* **Full Multisite Support** - Network admin dashboard, per-site tables, and network-wide scanning
* **Performance Optimized** - Chunked scanning prevents timeouts on large sites

== Installation ==

**Single-Site Installation:**

1. Upload the `wp-alttext-auditor` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Media > Alt Text Auditor** and select the **Audit** tab to run your first scan
4. Use **Media > Library** for quick inline editing in the media library

**Multisite (Network) Installation:**

1. Upload the `wp-alttext-auditor` folder to the `/wp-content/plugins/` directory
2. **Network Activate** the plugin through the 'Network Admin > Plugins' menu
3. Database tables will be automatically created for all sites in the network
4. Network admins can view network-wide statistics at **Network Admin > Alt-Text Audit**
5. Site admins can manage their site's alt-text at **Media > Alt Text Auditor** on each site

== Frequently Asked Questions ==

= Does this plugin work with Gutenberg? =

Yes! The plugin scans both Classic Editor content and Gutenberg blocks for images.

= Will scanning timeout on large sites? =

No. The plugin processes content in batches (50 items at a time) to prevent PHP timeouts even on sites with thousands of images.

= Can I export the results? =

Yes! Click "Export to CSV" to download all filtered results. The export respects your current filters and includes formula injection protection.

= Is the plugin translation-ready? =

Yes! All text is internationalized and ready for translation. The text domain is `alt-text-auditor`.

= How do I enable debug logging? =

For development, you can hook into the `alttext_auditor_log` action. Add this to a small mu-plugin:

```
add_action('alttext_auditor_log', function ($message, $context = array()) {
    if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        $suffix = $context ? ' ' . wp_json_encode($context) : '';
        error_log('[Alt Text Auditor] ' . $message . $suffix);
    }
}, 10, 2);
```

= How do I update the plugin safely? =

If you want to keep old data, do not delete the plugin — just update it. Upload the new ZIP and let WordPress replace the existing `wp-alttext-auditor` plugin in place (Plugins > Add New > Upload Plugin). This preserves your audit data.

**Important:** Do not click **Delete** on the old plugin before updating. Deleting runs `uninstall.php` and removes the audit data table.

= What permissions are required? =

* **Media Library editing**: Requires `upload_files` capability
* **Audit Dashboard**: Requires `manage_options` capability (admin only)
* **Network Admin Dashboard** (multisite): Requires `manage_network_options` capability

= Does this work on WordPress Multisite? =

Yes! The plugin has full multisite support with network activation, per-site database tables, network admin dashboard, and network-wide automatic scanning. Network admins can view statistics across all sites, while site admins manage their own alt-text independently.

== Screenshots ==

1. Media Library with inline alt-text editing and auto-save
2. Audit Dashboard overview with statistics cards
3. Missing Alt-Text report with thumbnails and quick-edit
4. User attribution breakdown showing which users need training
5. Advanced filtering form with search functionality
6. CSV export with all filtered results

== Changelog ==

= 2.1.0 =
*Release Date: February 12th, 2026*

* NEW: Unified Media menu into a single **Alt Text Auditor** page with Manager and Audit tabs
* CHANGED: Legacy Manager/Audit URLs now redirect to the unified page
* CHANGED: Audit filters and links updated to use the new unified page path
* DEV: Replaced direct error_log usage with `alttext_auditor_log()` hook for optional debug logging

= 2.0.0 =
*Release Date: January 30th, 2026*

**BREAKING CHANGE: Plugin Renamed**

* RENAMED: "WP Alt Text Auditor" → "Alt Text Auditor" (removed "WP" prefix)
* Plugin slug changed: wp-alttext-auditor → alt-text-auditor
* Text domain updated: 'wp-alttext-auditor' → 'alt-text-auditor'
* WordPress.org guideline compliance (trademarked term removal)
* IMPORTANT: Deactivate old plugin before installing v2.0.0
* Database tables/options preserved - no data migration needed
* All functionality preserved and enhanced

**WordPress.org Submission Prep**

* FIXED: Text domain consistency (294 instances corrected)
* FIXED: Removed debug error_log() calls from production code
* FIXED: Hidden .DS_Store files removed
* UPDATED: readme.txt "Tested up to: 6.9"
* UPDATED: Tags reduced to 5 (removed: images, seo)
* UPDATED: Moved security audit docs to /docs directory
* UPDATED: Constants renamed (WP_ALTTEXT_UPDATER_* → ALTTEXT_AUDITOR_*)
* READY: WordPress.org plugin directory submission

= 1.3.2 =
*Release Date: January 30th, 2026*

**Multisite Network-Wide Cron Scanning**

* NEW: Network-wide automatic scanning with configurable batch sizes (10/25/50/100 sites per day)
* NEW: Rotation system scans different sites each day to prevent server overload
* NEW: Performance warnings and cycle time calculator in network settings
* NEW: Single-site and multisite cron implementations with independent controls
* FIXED: Duplicate constant definition warning (added conditional checks)
* IMPROVED: Enhanced filename collision prevention with two-strategy matching (exact path, then filename)
* Network admins can now schedule automatic scans across large multisite networks safely

= 1.3.1 =
*Release Date: January 29th, 2026*

**Security Hardening - CRITICAL SECURITY UPDATE**

* FIXED: Unauthorized post content modification vulnerability (added edit_post capability check)
* FIXED: Stored XSS vulnerability via alt-text injection (added esc_attr() escaping)
* FIXED: Missing alt-text length validation (255 character limit enforced)
* FIXED: Multisite capability check bypass (re-verify after switch_to_blog())
* Comprehensive security audit completed for v1.3.0 multisite features
* All critical and high-priority vulnerabilities patched
* See SECURITY-AUDIT-V1.3.md for complete audit report

**IMPORTANT:** If using v1.2.0 or v1.3.0, update immediately to patch critical security vulnerabilities.

= 1.3.0 =
*Release Date: January 29th, 2026*

**Full WordPress Multisite (Network) Support**

* NEW: Complete multisite compatibility - works on both single-site and network installations
* NEW: Network activation creates audit tables for all sites automatically
* NEW: Network admin dashboard shows aggregated statistics across all sites
* NEW: Network settings page for multisite configuration
* NEW: Auto-activation option for newly created sites in network
* NEW: Per-site database tables - each site maintains its own audit data
* NEW: Per-site settings and scan schedules - site admins have full control
* NEW: Network-wide compliance overview with color-coded statistics
* NEW: Direct links from network dashboard to individual site dashboards
* Added Network: true to plugin header for WordPress.org compatibility
* Site switching implemented for cross-site operations
* Network capability checks (manage_network_options)
* Compatible with large networks (tested with 1000+ sites)

= 1.1.9 =
*Release Date: January 29th, 2026*

**Critical Bug Fix**

* FIXED: Network error when saving alt-text from audit dashboard
* Changed get_table_name() method from private to public in storage class
* Method was being called externally but had private visibility preventing saves

= 1.1.8 =
*Release Date: January 29th, 2026*

**UX Enhancements: Quick Wins**

* NEW: Press Enter key to save alt-text (no need to click Save button)
* NEW: Press Escape key to cancel editing
* NEW: Green success checkmark shows briefly before row fades out
* NEW: Character counter shows remaining characters (255 max) with color warnings
* NEW: All other "Add Alt Text" buttons disabled while editing to prevent confusion
* Better visual feedback throughout the save process

= 1.1.7 =
*Release Date: January 29th, 2026*

**Bug Fix: Save Functionality**

* FIXED: Inline editing save button now works correctly on Missing Alt-Text tab
* Simplified AJAX save process to use single call instead of two-step process
* Save now updates both audit record AND media library in one operation

= 1.1.6 =
*Release Date: January 29th, 2026*

**Bug Fixes & Enhancements**

* FIXED: Scan history table now correctly displays Total Images and Missing Alt counts (previously showed 0)
* FIXED: Key mapping mismatch between statistics storage and scan records
* ENHANCED: Inline alt-text editing now works for published content images, not just media library
* ENHANCED: Editing from audit dashboard saves to BOTH audit record AND media library
* Improved user workflow for fixing post content images directly from Missing Alt-Text tab

= 1.1.2 =
*Release Date: January 21st, 2026*

**Bug Fix: Scan Completion Notification**

* Added clickable link to Scans tab after scan completes
* Users now get clear notification: "View report in Scans tab →"
* Fixed disabled button state to include all three scan buttons
* Improved user experience - now obvious where to find generated reports

= 1.1.1 =
*Release Date: January 21st, 2026*

**UI Improvements & Live Search**

* Added live search to Alt Text Manager - filters table in real-time as you type
* Improved visual design with consistent card-style panels across both admin pages
* Better spacing and visual hierarchy throughout the interface
* Enhanced filter forms with unified white background and subtle shadows
* Search now filters both filenames and alt-text content
* "No results" message displays when search yields no matches
* Improved responsive design for mobile and tablet devices

= 1.1.0 =
*Release Date: January 20th, 2026*

**Major Update: Scans Tab & Draft Scanning**

* New "Scans" tab showing complete scan history with sortable columns
* Draft content scanning - scan unpublished posts/pages for missing alt-text
* HTML report modal viewer - view reports directly in the dashboard
* Download and print scan reports with formatted HTML output
* Bulk delete scans with checkbox selection
* Scan trigger badges (Manual vs Automatic)
* Auto-cleanup settings - automatically delete old scans after X days
* Clear all data button with confirmation for complete data reset
* Improved scan management and data lifecycle controls

= 1.0.5 =
*Release Date: January 20th, 2026*

**Bug Fix: Report Generation**

* Fixed: Manual scans now generate HTML reports (previously only cron scans generated reports)
* Reports now generate after every scan type (manual, cron, and on-demand)
* Recent Reports list will now populate correctly after manual scans

= 1.0.4 =
*Release Date: January 17th, 2026*

**HTML Report Generation**

* Added comprehensive HTML report generator for each scan
* Beautiful, print-ready reports with statistics, missing images, and user attribution
* Reports automatically generated after each scan (manual or automatic)
* "Generate Report Now" button for on-demand report creation
* Recent reports list in dashboard (last 10 shown)
* Reports saved to uploads/alttext-reports/ directory
* Automatic cleanup keeps last 20 reports
* Mobile-responsive HTML reports with gradient cards and data tables
* Includes up to 500 missing images with thumbnails
* Top 20 users with missing alt-text breakdown

= 1.0.3 =
*Release Date: January 17th, 2026*

**Documentation & Polish**

* Added comprehensive JSDoc to all JavaScript files
* Added WordPress contextual help tabs to audit dashboard (4 help tabs + sidebar)
* Created readme.txt for WordPress.org plugin directory submission
* Enhanced PHPDoc comments throughout codebase
* Added help documentation accessible via "Help" tab in dashboard
* Improved code comments and inline documentation
* WordPress coding standards compliance verified
* Ready for WordPress.org plugin directory submission

= 1.0.2 =
*Release Date: January 17th, 2026*

**Performance Enhancements**

* Added composite database indexes for faster queries on large datasets
* Eliminated N+1 query problem with batch user data fetching (50-90% fewer queries)
* Added 1-hour transient cache for user attribution results
* Optimized asset loading - JS/CSS only loads on pages where needed
* Improved query performance for sites with 10,000+ images

= 1.0.1 =
*Release Date: January 17th, 2026*

**Security Hardening - Critical and High Severity Fixes**

* Fixed SQL injection vulnerability in table name queries
* Fixed unsafe direct object reference in export filters
* Fixed unescaped output in dashboard (added esc_url(), wp_kses_post())
* Improved GET-based export security (added rate limiting and referrer checking)
* Standardized AJAX nonce verification (using check_ajax_referer() consistently)
* Added input validation to storage filters (whitelist validation)
* Fixed unescaped thumbnail output

**Security Enhancements:**
* Rate limiting on CSV exports (1 per minute per user)
* Referrer validation to prevent external access
* Table name format validation (alphanumeric and underscore only)
* Filter parameter validation against registered post types
* User ID validation before filtering

= 1.0.0 =
*Release Date: January 15th, 2026*

**Initial Release**

* Integrated alt-text editing directly into WordPress Media Library
* Alt Text Manager page for browsing media library with thumbnails
* Site-wide scanning engine for posts, pages, and media library
* Chunked batch processing (50 items per batch) prevents timeouts
* Real-time progress bar with animated effects
* Statistics dashboard with color-coded cards
* Missing alt-text report table with thumbnails and pagination
* User attribution breakdown
* Advanced filtering system (by user, content source, post type, filename search)
* Quick-edit functionality for inline alt-text updates
* CSV export with formula injection protection and UTF-8 encoding
* Automatic daily scanning via WP Cron (optional)
* 24-hour statistics caching for performance
* Responsive design with 4 breakpoints
* WordPress admin styling integration
* Contextual help tabs with detailed documentation

== Upgrade Notice ==

= 1.3.2 =
Adds network-wide automatic scanning for multisite with configurable batch sizes. Fixes duplicate constant warning. Improved filename collision prevention.

= 1.3.1 =
CRITICAL SECURITY UPDATE: Patches unauthorized post modification and XSS vulnerabilities. All users should upgrade immediately.

= 1.3.0 =
Major update: Full WordPress Multisite support with network admin dashboard, per-site tables, and network-wide scanning capabilities.

= 1.0.4 =
New feature: HTML reports automatically generated for each scan with beautiful statistics, images, and user attribution.

= 1.0.3 =
Documentation update with WordPress.org compatibility, contextual help tabs, and comprehensive code documentation.

= 1.0.2 =
Performance update with database optimizations and faster query performance. Recommended for sites with large media libraries.

= 1.0.1 =
Critical security update. All users should upgrade immediately. Fixes SQL injection and XSS vulnerabilities.

= 1.0.0 =
Initial release of WP Alt Text Updater with comprehensive alt-text management and audit features.

== Additional Information ==

**Performance:**

* Chunked scanning processes 50 items per batch to prevent timeouts
* Multi-level caching (statistics: 24 hours, user attribution: 1 hour)
* Optimized database indexes for fast queries
* Conditional asset loading reduces page weight
* Batch user data fetching eliminates N+1 queries

**Security:**

* WordPress nonces for all AJAX requests
* Capability checks (`upload_files` for editing, `manage_options` for audit)
* All user input sanitized (`sanitize_text_field()`, `intval()`, `esc_like()`)
* Output escaped (`esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`)
* SQL injection prevention with prepared statements
* XSS prevention throughout
* Rate limiting on exports (1 per minute per user)
* Referrer validation on sensitive operations

**Compatibility:**

* WordPress 5.0+
* PHP 7.4+
* Works with Classic Editor and Gutenberg blocks
* Mobile responsive (1200px, 960px, 782px, 600px breakpoints)
* Full WordPress Multisite (Network) support
  - Per-site database tables and settings
  - Network admin dashboard for network-wide statistics
  - Network activation creates tables for all sites
  - Auto-activation for new sites
  - Network-wide automatic scanning with configurable batch sizes

**Support:**

For support, feature requests, or bug reports, please visit the plugin repository.

**Credits:**

Developed with accessibility and performance in mind to help site owners achieve WCAG compliance.
