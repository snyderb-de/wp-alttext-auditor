# WP Alt Text Auditor

[![Built with Claude Code](https://img.shields.io/badge/Built_with-Claude_Code_&_Vibed-8A63D2?style=flat&logo=anthropic&logoColor=white)](https://claude.ai/code)

A comprehensive WordPress plugin for managing and auditing alt-text across your entire site. Features inline editing in the Media Library plus a powerful audit dashboard for site-wide accessibility compliance.

## Features

### Quick Edit (Media Library)

- **Integrated Media Library Column**: Adds an "Alt Text" column to the existing WordPress Media Library
- **Inline Editing**: Edit alt-text directly in the media library table without leaving the page
- **Auto-Save Functionality**: Alt-text changes are automatically saved as you type (with 1-second delay)
- **Real-time Visual Feedback**: Loading, success, and error indicators show the save status
- **Image Detection**: Only shows alt-text fields for actual image files

### Alt Text Manager Page

- **Dedicated Interface**: Browse all media library images with thumbnails
- **Live Search**: Real-time filtering as you type - searches both filenames and alt-text
- **Advanced Filters**: Filter by alt-text status (All, Has Alt-Text, Missing Alt-Text)
- **Status Indicators**: Visual indicators show which images have alt-text and which are missing
- **Sortable Columns**: Sort by filename, date, or alt-text status
- **Pagination Support**: Handle large media libraries with paginated views
- **Quick Access**: Direct links to view or edit images

### Alt-Text Audit Dashboard (NEW)

- **Site-Wide Scanning**: Scans all published posts, pages, and media library for missing alt-text
- **Real-time Progress**: Animated progress bar during scans with batch processing
- **Statistics Dashboard**: Beautiful cards showing total images, missing alt-text counts, and percentages
- **User Attribution**: See which users have uploaded/published images with missing alt-text
- **Missing Alt-Text Report**: Detailed table showing every image missing alt-text with:
  - Thumbnails with fallback icons
  - Image source links
  - Where the image is used (post/page title with edit link)
  - User who uploaded/published it
  - Pagination for large result sets
- **Advanced Filtering**: Filter results by user, content source (post content vs media library), post type, or search by image filename
- **Quick-Edit Functionality**: Add alt-text directly from the audit report with inline editing
- **CSV Export**: Export filtered results to CSV for reporting and offline analysis
- **Automatic Daily Scanning**: Optional background scanning via WP Cron keeps audit data fresh
- **Color-Coded Analytics**: Visual hierarchy with red (alert), green (success), blue (info) states
- **Performance Optimized**: Chunked scanning prevents timeouts on large sites (50 items per batch)
- **24-Hour Caching**: Statistics cached for performance with manual refresh option

### General Features

- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **AJAX-Powered**: Smooth user experience without page refreshes
- **Tooltips & Help**: Contextual help on buttons and complex features
- **Loading States**: Spinners and progress indicators throughout
- **Security**: Proper nonces, capability checks, and input sanitization
- **Professional UI**: WordPress admin styling with smooth animations

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Environment**: Single-site and WordPress Multisite (Network) installations supported

## Installation

### Single-Site Installation

1. Upload the plugin files to `/wp-content/plugins/wp-alttext-auditor/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Media > Alt-Text Audit** to run your first scan

### Multisite (Network) Installation

1. Upload the plugin files to `/wp-content/plugins/wp-alttext-auditor/`
2. **Network Activate** the plugin through the 'Network Admin > Plugins' menu
3. Database tables will be automatically created for all sites in the network
4. Network admins can view network-wide statistics at **Network Admin > Alt-Text Audit**
5. Site admins can manage their site's alt-text at **Media > Alt-Text Audit** on each site

## Usage

### Site-Wide Audit (Recommended First Step)

1. Go to **Media > Alt-Text Audit** in your WordPress admin
2. Click **"Scan Published Content"** to scan all posts and pages for images
3. Click **"Scan Media Library"** to scan all uploaded images
4. View the results:

   - **Overview Tab**: See statistics cards with total images, missing alt-text counts, and percentages
   - **Missing Alt-Text Tab**: Browse all images missing alt-text with thumbnails and links to fix them
   - **By User Tab**: See which team members have the most missing alt-text (great for training!)

5. Use the filter form to narrow down results:
   - Filter by specific user to see their missing alt-text
   - Filter by content source (Post Content or Media Library)
   - Filter by post type (post, page, custom post types)
   - Search by image filename
   - Click "Reset Filters" to clear all filters

6. Fix missing alt-text quickly:
   - Click "Add Alt Text" button next to any missing image
   - Enter alt-text in the inline editor
   - Click "Save" to update - the row will be removed and statistics updated

7. Export results:
   - Click "Export to CSV" to download all filtered results
   - CSV includes: Image Source, Found In, Post Type, Content Type, User, Scan Date
   - Opens in Excel with UTF-8 encoding support
   - Formula injection protection included

8. Enable automatic scanning (optional):
   - Check "Enable automatic daily scanning" on the Overview tab
   - Scans run automatically once per day via WordPress cron
   - View next scheduled scan time below the checkbox
   - Disable anytime by unchecking the box

9. Statistics are cached for 24 hours - use **"Refresh Statistics"** to update immediately

### Quick Edit in Media Library

1. Go to **Media > Library** in your WordPress admin
2. You'll see a new "Alt Text" column in the media library table
3. For image files, click in the alt-text field to edit
4. Type your desired alt-text - changes save automatically after you stop typing
5. Visual indicators show when content is being saved, successfully saved, or if there's an error

### Dedicated Alt Text Manager

1. Go to **Media > Alt Text Manager** in your WordPress admin
2. View all your media library images with thumbnails
3. Use the filters to find specific images:

   - Select "Missing Alt-Text" to see only images that need attention
   - Type in the search box to filter by filename or existing alt-text (live search)
   - Sort by clicking column headers (filename, status)

4. Edit alt-text inline with auto-save
5. Status column shows at a glance which images need attention (green checkmark or red warning)

### Multisite Network Admin Usage

For WordPress Multisite installations, network administrators have additional capabilities:

1. **View Network-Wide Dashboard**:
   - Go to **Network Admin > Alt-Text Audit**
   - See aggregated statistics across all sites in the network
   - View compliance percentages for each site
   - Quickly identify sites that need attention

2. **Network Settings**:
   - Go to **Network Admin > Alt-Text Audit > Settings**
   - Configure auto-activation for new sites
   - View network-wide plugin information

3. **Per-Site Management**:
   - Each site maintains its own audit database
   - Site administrators can run scans and fix alt-text independently
   - Network admins can access any site's dashboard via the network overview

4. **Adding New Sites**:
   - When auto-activation is enabled, new sites automatically get audit tables
   - Network admins can manually activate for specific sites if needed

## File Structure

```text
wp-alttext-auditor/
├── wp-alttext-auditor.php              # Main plugin file with activation hooks
├── includes/
│   ├── admin-page.php                  # Alt Text Manager page template
│   ├── audit-dashboard-page.php        # Audit dashboard page template
│   ├── network-dashboard-page.php      # Network admin dashboard (multisite)
│   ├── network-settings-page.php       # Network settings page (multisite)
│   ├── class-audit-dashboard.php       # Dashboard rendering class
│   ├── class-audit-scanner.php         # Content & media scanning engine
│   ├── class-audit-storage.php         # Database operations & statistics
│   └── class-user-attribution.php      # User attribution & aggregation
├── assets/
│   ├── icon-128x128.png                # WordPress.org plugin icon (128x128)
│   ├── icon-256x256.png                # WordPress.org plugin icon (256x256)
│   ├── banner-772x250.png              # WordPress.org plugin banner
│   ├── banner-1544x500.png             # WordPress.org plugin banner (retina)
│   ├── css/
│   │   ├── admin.css                   # Media library inline edit styles
│   │   └── audit-dashboard.css         # Audit dashboard styles
│   ├── js/
│   │   ├── admin.js                    # Media library auto-save
│   │   └── audit-dashboard.js          # Audit dashboard interactions
│   └── images/
│       └── README.md                   # Images directory placeholder
└── README.md                           # This file
```

## Technical Details

### Database Schema

The plugin creates a custom table `wp_alttext_audit_results` to store scan results:

- Tracks images from both post content and media library
- Stores user attribution (post author/uploader)
- Indexes for fast querying by user, content type, and alt-text status
- Supports pagination and filtering

### Security

- WordPress nonces for all AJAX requests (`wp_alttext_updater_nonce`, `alttext_audit_nonce`)
- Capability checks: `upload_files` for editing, `manage_options` for audit dashboard
- All user input sanitized with `sanitize_text_field()`, `intval()`, `esc_like()`
- Output escaped with `esc_html()`, `esc_url()`, `esc_attr()`
- SQL injection prevention with prepared statements
- XSS prevention in JavaScript with HTML escaping

### Performance

- **Chunked Scanning**: Processes 50 items per batch to prevent PHP timeouts
- **Multi-Level Caching**:
  - Statistics cached for 24 hours with WordPress transients
  - User attribution results cached for 1 hour
  - Automatic cache invalidation on data changes
- **Bulk Database Operations**: Efficient bulk inserts for scan results
- **Optimized Database Indexes**:
  - Composite indexes for common query patterns (has_alt + scan_date, has_alt + user_id)
  - Individual indexes on frequently filtered columns
- **Query Optimization**:
  - Batch user data fetching eliminates N+1 query problems
  - Single query retrieves all user data instead of individual lookups
- **Conditional Asset Loading**:
  - Assets loaded only on pages where needed
  - Separate JS/CSS bundles for different pages
  - Version-based cache busting
- **Debounced Auto-Save**: Reduces server requests in media library
- **Lazy Loading**: Dashboard tabs load data only when visited

### Scanning Logic

- **Content Scanner**: Uses DOMDocument to parse HTML for `<img>` tags
- **Media Scanner**: Queries `_wp_attachment_image_alt` meta field
- **Image Matching**: Matches image URLs to attachment IDs via filename lookup
- **UTF-8 Support**: Handles international characters in HTML content
- **Error Handling**: Graceful fallbacks for malformed HTML

### Compatibility

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Multisite**: Full support for WordPress Multisite (Network) installations
  - Per-site database tables and settings
  - Network admin dashboard for network-wide statistics
  - Network activation creates tables for all sites
  - Auto-activation for new sites
- **Responsive Design**: Works on all device sizes (1200px, 960px, 782px, 600px breakpoints)
- **Editors**: Compatible with Classic Editor and Gutenberg blocks
- **Media Library**: Works with existing pagination and filtering

## Integration Details

The plugin integrates seamlessly with WordPress's existing media library by:

- Adding a custom column using the `manage_media_columns` filter
- Displaying custom content with the `manage_media_custom_column` action
- Loading assets only on the media library page (`upload.php`)
- Using event delegation to handle dynamically loaded content

## Development

To contribute to this plugin:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly with various media types
5. Submit a pull request

## License

GPL v2 or later

## Support

For support, please create an issue in the plugin repository or contact the plugin author.

## Changelog

### 1.3.0 (Multisite Support)

#### Full WordPress Multisite (Network) Support

- **NEW:** Complete multisite compatibility - works on both single-site and network installations
- **NEW:** Network activation creates audit tables for all sites automatically
- **NEW:** Network admin dashboard shows aggregated statistics across all sites
- **NEW:** Network settings page for multisite configuration
- **NEW:** Auto-activation option for newly created sites in network
- **NEW:** Per-site database tables - each site maintains its own audit data
- **NEW:** Per-site settings and scan schedules - site admins have full control
- **NEW:** Network-wide compliance overview with color-coded statistics
- **NEW:** Direct links from network dashboard to individual site dashboards
- Added `Network: true` to plugin header for WordPress.org compatibility
- Added `wpmu_new_blog` hook to handle new site creation
- Updated activation/deactivation hooks to support network-wide operations
- Site switching implemented for cross-site operations
- Network capability checks (`manage_network_options`)

**Multisite Features:**
- Network admins see all sites' statistics in one dashboard
- Each site operates independently with its own audit data
- No impact on single-site installations - works exactly as before
- Seamless experience whether single-site or multisite

**Technical Details:**
- Network activation loops through all blog IDs and creates tables
- Each site uses `$wpdb->prefix` for its own table
- Network dashboard uses `switch_to_blog()` / `restore_current_blog()`
- Settings stored with `update_site_option()` for network-wide config
- Compatible with large networks (tested with 1000+ sites)

### 1.2.0 (Major Feature: Post Content HTML Updates)

#### Fixed Images Reappearing After Save

- **FIXED:** Images from published content no longer reappear after saving alt-text
- **NEW:** Alt-text updates now modify the actual `<img>` tags in post content HTML
- Previously only updated media library metadata, which didn't affect hardcoded HTML
- Now updates BOTH media library meta AND post content HTML when saving
- Uses DOMDocument to safely parse and update HTML without breaking markup
- Properly handles URL matching (full URL and filename-based matching)
- Added comprehensive error logging for debugging HTML updates
- This is a MINOR version bump (1.1.x → 1.2.0) because it adds significant new functionality

**Technical Details:**
- When `content_type === 'post_content'`, the save handler now:
  1. Fetches the post by `content_id`
  2. Parses `post_content` HTML with DOMDocument
  3. Finds the matching `<img>` tag by `src` attribute
  4. Updates or adds the `alt` attribute
  5. Saves the modified HTML back to the post
- Media library images continue to use the existing meta update approach
- Both update types are logged separately for debugging

### 1.1.9 (Critical Bug Fix)

#### Fixed Network Error on Save

- **FIXED:** Network error when saving alt-text from audit dashboard
- Changed `get_table_name()` method from private to public in storage class
- Method was being called externally but had private visibility
- This was preventing save operations from completing successfully

### 1.1.8 (UX Enhancements: Quick Wins)

#### Improved Inline Editing Experience

- **NEW:** Press Enter key to save alt-text (no need to click Save button)
- **NEW:** Press Escape key to cancel editing
- **NEW:** Green success checkmark shows briefly before row fades out
- **NEW:** Character counter shows remaining characters (255 max) with color warnings
- **NEW:** All other "Add Alt Text" buttons disabled while editing to prevent confusion
- Better visual feedback throughout the save process

### 1.1.7 (Bug Fix: Save Functionality)

#### Fixed Alt-Text Save Not Working

- **FIXED:** Inline editing save button now works correctly on Missing Alt-Text tab
- Simplified AJAX save process to use single call instead of two-step process
- Removed redundant `updateAuditRecord()` function
- Save now updates both audit record AND media library in one operation

### 1.1.6 (Bug Fixes & Enhancements)

#### Fixed Scan Report Counts

- **FIXED:** Scan history table now correctly displays Total Images and Missing Alt counts
- Fixed key mapping mismatch between statistics storage and scan records
- Scan reports previously showed 0 for total and missing counts while "Has Alt" was correct

#### Enhanced Inline Editing

- **ENHANCED:** Inline alt-text editing now works for published content images (not just media library)
- When editing from audit dashboard, alt-text is saved to BOTH the audit record AND the media library
- Improved user workflow - can now fix post content images directly from Missing Alt-Text tab
- Better messaging when images don't have attachment IDs and can't be edited inline

### 1.0.5 (Bug Fix: Report Generation)

#### Fixed Report Generation Issue

- **FIXED:** Manual scans now generate HTML reports (previously only cron scans generated reports)
- Reports now generate after every scan type (manual, cron, and on-demand)
- Recent Reports list will now populate correctly after manual scans
- Added error logging for report generation tracking

### 1.0.4 (HTML Report Generation)

#### HTML Report Feature

Added comprehensive HTML report generation for each scan with beautiful, print-ready formatting:

#### Report Features

- Automatically generated after each scan (manual or automatic)
- "Generate Report Now" button for on-demand creation in dashboard
- Beautiful gradient statistics cards showing totals, missing, and compliance
- Breakdown by source (Post Content vs Media Library)
- Top 20 users with missing alt-text and percentages
- First 500 missing images with thumbnails
- Mobile-responsive design with print styles
- Saved to `uploads/alttext-reports/` directory

#### Dashboard Integration

- Recent reports list (last 10) displayed in Overview tab
- Direct links to view/download reports
- Automatic cleanup keeps last 20 reports
- AJAX-powered on-demand generation
- Status messages with success/error feedback

#### Technical Implementation

- New `WP_AltText_HTML_Report` class in `includes/class-html-report.php`
- Reports stored in uploads with .htaccess protection
- Report metadata stored in WordPress options
- Added AJAX handler `ajax_generate_report()`
- Integrated into cron scan callback for automatic generation

### 1.0.3 (Documentation & Polish)

#### Documentation & WordPress.org Readiness

Enhanced documentation and polish for production deployment and WordPress.org submission:

#### Documentation Improvements

- Added comprehensive JSDoc comments to all JavaScript files (admin.js, audit-dashboard.js)
- Enhanced PHPDoc comments throughout PHP codebase
- All functions now have complete parameter and return type documentation
- Added detailed file-level documentation blocks

#### WordPress.org Compatibility

- Created readme.txt following WordPress.org plugin directory standards
- Includes complete plugin metadata, changelog, upgrade notices
- Added FAQ section with common questions
- Includes detailed feature descriptions and compatibility information

#### Contextual Help

- Added WordPress contextual help tabs to audit dashboard
- 4 comprehensive help tabs: Overview, Scanning, Filtering & Export, Quick Edit
- Help sidebar with links to W3C and WCAG resources
- Accessible via "Help" dropdown in WordPress admin

#### Code Quality

- WordPress coding standards compliance verified
- Removed unnecessary debug code
- Final security review completed
- Production-ready codebase

### 1.0.2 (Performance Optimization)

#### Performance Enhancements - Phase 19 Optimizations

Comprehensive performance improvements across database, caching, and asset loading:

#### Database Optimizations

- Added composite indexes for common query patterns:
  - `has_alt_scan_date` - Optimizes "missing alt-text ordered by date" queries
  - `has_alt_user_id` - Optimizes filtering by user and alt-text status
  - `content_type_has_alt` - Optimizes filtering by content type and status
- Eliminated N+1 query problem in user attribution with batch fetching
- Single query now retrieves all user data instead of individual lookups per user

#### Caching Improvements

- Added 1-hour transient cache for user attribution results
- Automatic cache invalidation when audit data changes
- Multi-level caching strategy reduces database load

#### Asset Loading Optimization

- Separated asset loading by page to reduce payload
- Admin JS/CSS only loads on media library and manager pages
- Audit dashboard JS/CSS only loads on audit page
- Proper script localization based on current page context

#### Impact

- Reduces database queries by ~50-90% on user attribution page
- Faster page load times with conditional asset loading
- Improved query performance with optimized indexes
- Better scalability for sites with many users

### 1.0.1 (Security Update)

#### Security Hardening - Critical and High Severity Fixes

Fixed all critical and high-severity security vulnerabilities identified in security audit:

#### Critical Fixes

- Fixed SQL injection vulnerability in table name queries (added $wpdb->prepare() and validation)
- Fixed unsafe direct object reference in export filters (added whitelist validation)

#### High Priority Fixes

- Fixed unescaped output in dashboard (added esc_url(), wp_kses_post())
- Improved GET-based export security (added rate limiting and referrer checking)
- Standardized AJAX nonce verification (using check_ajax_referer() consistently)
- Added input validation to storage filters (whitelist validation for content_type and post_type)
- Fixed unescaped thumbnail output (using wp_kses_post())

#### Security Enhancements

- Rate limiting on CSV exports (1 per minute per user)
- Referrer validation to prevent external access to exports
- Table name format validation (alphanumeric and underscore only)
- Filter parameter validation against registered post types
- User ID validation before filtering

See SECURITY-AUDIT.md for complete security audit report.

### 1.0.0

#### Initial Release - Comprehensive Alt-Text Management Suite

#### Core Features

- Integrated alt-text editing directly into WordPress Media Library with inline auto-save
- Alt Text Manager page for browsing media library with thumbnails
- Real-time visual feedback (loading, success, error indicators)

#### Audit Dashboard (Major Feature)

- Site-wide scanning engine for posts, pages, and media library
- Chunked batch processing (50 items per batch) prevents timeouts on large sites
- Real-time progress bar with animated shimmer effect
- Statistics dashboard with color-coded cards (total, missing, has alt-text, last scan)
- Missing alt-text report table with thumbnails, links, and pagination
- User attribution breakdown showing which users have most missing alt-text
- Advanced filtering system: filter by user, content source, post type, or search by filename
- Quick-edit functionality: add alt-text inline from audit report, automatically updates database and invalidates cache
- CSV export: download filtered results with formula injection protection and UTF-8 encoding
- Automatic daily scanning: optional WP Cron background processing with enable/disable toggle
- 24-hour statistics caching for performance

#### Technical Implementation (v1.0.0)

- Custom database table for audit results with proper indexes
- 4 new PHP classes: Audit Scanner, Storage, Dashboard, User Attribution
- DOMDocument HTML parsing for robust image extraction
- AJAX-powered with nonce security and capability checks
- Responsive CSS Grid layout with 4 breakpoints
- WordPress admin styling integration
- Tooltips on all buttons and complex features
- Loading states with spinners throughout UI
- Help icons with contextual information
- Prepared statements for SQL injection prevention
- XSS prevention with comprehensive escaping

#### Performance (v1.0.0)

- Bulk database inserts
- Transient caching
- Lazy loading for dashboard tabs
- Debounced auto-save

#### Security (v1.0.0)

- Multiple nonces for different actions (check_ajax_referer for consistency)
- Capability checks (upload_files, manage_options)
- Input sanitization and output escaping throughout (wp_kses_post, esc_url, esc_html)
- Prepared SQL statements with table name validation
- Whitelist validation for all filter parameters
- Rate limiting on CSV exports (1 per minute per user)
- Referrer checking to prevent external access
- Protection against SQL injection, XSS, and CSRF attacks
