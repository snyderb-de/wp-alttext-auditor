=== WP Alt Text Auditor ===
Contributors: thestrangeloop
Author Name: Bryan Snyder (snyderb.de@gmail.com)
Tags: alt-text, accessibility, media, images, wcag, seo
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Comprehensive WordPress plugin for managing and auditing alt-text across your entire site with inline editing and powerful audit dashboard.

== Description ==

WP Alt Text Updater helps you identify and fix images with missing alt-text across your entire WordPress site. Improve accessibility compliance and SEO with powerful scanning, auditing, and inline editing features.

**Key Features:**

* **Inline Media Library Editing** - Edit alt-text directly in the WordPress Media Library with auto-save
* **Site-Wide Audit Dashboard** - Scan all published content and media library for missing alt-text
* **Real-Time Statistics** - Track progress with visual cards showing total images, missing alt-text counts, and percentages
* **User Attribution** - See which team members have the most missing alt-text
* **Advanced Filtering** - Filter results by user, content source, post type, or search by filename
* **Quick-Edit Functionality** - Add alt-text inline from audit results
* **CSV Export** - Export filtered results for offline analysis and reporting
* **Automatic Daily Scanning** - Optional background scanning via WP Cron
* **Performance Optimized** - Chunked scanning prevents timeouts on large sites

== Installation ==

1. Upload the `wp-alttext-auditor` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Media > Alt-Text Audit** to run your first scan
4. Use **Media > Library** for quick inline editing in the media library

== Frequently Asked Questions ==

= Does this plugin work with Gutenberg? =

Yes! The plugin scans both Classic Editor content and Gutenberg blocks for images.

= Will scanning timeout on large sites? =

No. The plugin processes content in batches (50 items at a time) to prevent PHP timeouts even on sites with thousands of images.

= Can I export the results? =

Yes! Click "Export to CSV" to download all filtered results. The export respects your current filters and includes formula injection protection.

= Is the plugin translation-ready? =

Yes! All text is internationalized and ready for translation. The text domain is `wp-alttext-auditor`.

= What permissions are required? =

* **Media Library editing**: Requires `upload_files` capability
* **Audit Dashboard**: Requires `manage_options` capability (admin only)

== Screenshots ==

1. Media Library with inline alt-text editing and auto-save
2. Audit Dashboard overview with statistics cards
3. Missing Alt-Text report with thumbnails and quick-edit
4. User attribution breakdown showing which users need training
5. Advanced filtering form with search functionality
6. CSV export with all filtered results

== Changelog ==

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
* Compatible with multisite installations

**Support:**

For support, feature requests, or bug reports, please visit the plugin repository.

**Credits:**

Developed with accessibility and performance in mind to help site owners achieve WCAG compliance.
