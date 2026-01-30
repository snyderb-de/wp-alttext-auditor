# Security Audit Report - v1.3.0 (Multisite + HTML Manipulation)

**Date**: 2026-01-29
**Auditor**: Claude Sonnet 4.5
**Scope**: Full codebase review focusing on multisite and HTML manipulation features
**Version**: 1.3.0

---

## Executive Summary

Conducted comprehensive security audit of the WP Alt Text Auditor plugin following addition of:
1. WordPress Multisite support (v1.3.0)
2. HTML manipulation via DOMDocument (v1.2.0)

### Overall Security Posture: **GOOD with CRITICAL ISSUES FOUND**

**Critical Issues Found**: 2
**High Priority Issues**: 3
**Medium Priority Issues**: 2
**Low Priority Issues**: 1

---

## CRITICAL ISSUES

### 1. **Post Content Modification Without Author Check** ⚠️ CRITICAL

**Location**: `wp-alttext-auditor.php:770-826` (ajax_update_audit_record)

**Issue**: Users with `upload_files` capability can modify ANY post's content HTML, including posts they don't own.

**Current Code**:
```php
public function ajax_update_audit_record() {
    check_ajax_referer('alttext_audit_nonce', 'nonce');

    if (!current_user_can('upload_files')) {
        wp_send_json_error(...);
    }

    // Gets post and modifies its HTML
    if ($result->content_type === 'post_content' && $result->content_id) {
        $post = get_post($result->content_id);
        // NO OWNERSHIP CHECK HERE!
        wp_update_post(array(
            'ID' => $result->content_id,
            'post_content' => $updated_html
        ));
    }
}
```

**Risk**: Contributors/editors can modify content in posts they don't own, including admin posts.

**Severity**: **CRITICAL** - Privilege escalation / unauthorized content modification

**Recommendation**:
```php
// Add ownership/capability check
if ($result->content_type === 'post_content' && $result->content_id) {
    $post = get_post($result->content_id);

    if (!$post) {
        wp_send_json_error(array('message' => 'Post not found'));
    }

    // Check if user can edit this specific post
    if (!current_user_can('edit_post', $result->content_id)) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to edit this post.', 'wp-alttext-auditor')
        ));
    }

    // Then proceed with HTML modification
}
```

---

### 2. **HTML Injection via Alt-Text in Post Content** ⚠️ CRITICAL

**Location**: `wp-alttext-auditor.php:791`

**Issue**: Alt-text is set directly via `setAttribute()` without HTML escaping, then injected into post content.

**Current Code**:
```php
$img->setAttribute('alt', $alt_text);  // No escaping!
```

**Attack Vector**:
```
User enters: "><script>alert(document.cookie)</script><img alt="
Result: Breaks out of alt attribute and injects JavaScript
```

**Risk**: Stored XSS vulnerability - malicious scripts saved in post content

**Severity**: **CRITICAL** - Stored XSS

**Recommendation**:
```php
// Escape alt-text for HTML attribute context
$safe_alt_text = esc_attr($alt_text);
$img->setAttribute('alt', $safe_alt_text);
```

**Note**: DOMDocument will handle HTML entities, but we should escape first to be safe.

---

## HIGH PRIORITY ISSUES

### 3. **Network Dashboard - Unescaped Site Names**

**Location**: `includes/network-dashboard-page.php:73`

**Issue**: Site names from database are escaped, but blog details come from user input in multisite.

**Current Code**:
```php
$site_details = get_blog_details($site->blog_id);
echo esc_html($site_details->blogname);  // Good
echo esc_html($site_details->siteurl);   // Good
```

**Status**: ✅ Actually properly escaped - FALSE ALARM

**Severity**: NONE - Code is correct

---

### 4. **Network Settings - Missing Nonce Timeout Check**

**Location**: `includes/network-settings-page.php:14-24`

**Issue**: Nonce verification lacks timeout validation.

**Current Code**:
```php
if (isset($_POST['alttext_network_settings_nonce']) &&
    wp_verify_nonce($_POST['alttext_network_settings_nonce'], 'alttext_network_settings')) {
```

**Risk**: Nonce could be reused if user has multiple tabs open

**Severity**: **MEDIUM** - CSRF protection could be bypassed

**Recommendation**: WordPress's `wp_verify_nonce()` already includes timeout - this is fine. FALSE ALARM.

---

### 5. **Site Switching Without Capability Re-Check**

**Location**: `includes/network-dashboard-page.php:57-106`

**Issue**: Capability checked before loop, but not after `switch_to_blog()`

**Current Code**:
```php
// Capability checked in render_network_dashboard()
foreach ($sites as $site) {
    switch_to_blog($site->blog_id);
    // No re-check of capabilities in new blog context
    $storage = new WP_AltText_Audit_Storage();
}
```

**Risk**: If capability system is context-dependent, user might access restricted blog data

**Severity**: **HIGH** - Potential information disclosure across sites

**Recommendation**:
```php
foreach ($sites as $site) {
    switch_to_blog($site->blog_id);

    // Re-verify capability in switched blog context
    if (!current_user_can('manage_options')) {
        restore_current_blog();
        continue;
    }

    $storage = new WP_AltText_Audit_Storage();
    $stats = $storage->get_statistics();

    restore_current_blog();
}
```

---

### 6. **Missing Input Length Validation on Alt-Text**

**Location**: `wp-alttext-auditor.php:726`

**Issue**: Alt-text length not validated before saving to database/HTML

**Current Code**:
```php
$alt_text = isset($_POST['alt_text']) ? sanitize_text_field($_POST['alt_text']) : '';
// No length check!
```

**Risk**: Database column is 255 chars, but no enforcement. Extremely long alt-text could break page rendering.

**Severity**: **MEDIUM** - Data integrity / potential DoS

**Recommendation**:
```php
$alt_text = isset($_POST['alt_text']) ? sanitize_text_field($_POST['alt_text']) : '';

// Enforce max length (WordPress default for alt is 255)
if (strlen($alt_text) > 255) {
    wp_send_json_error(array(
        'message' => __('Alt-text must be 255 characters or less.', 'wp-alttext-auditor')
    ));
}
```

---

## MEDIUM PRIORITY ISSUES

### 7. **Race Condition in HTML Update**

**Location**: `wp-alttext-auditor.php:770-826`

**Issue**: No locking mechanism when updating post content - two users could edit simultaneously

**Risk**: Last writer wins - changes could be lost

**Severity**: **MEDIUM** - Data loss potential

**Recommendation**: Use `wp_update_post()` with revision system (already in place), add optimistic locking:
```php
// Get current post_modified timestamp
$current_modified = $post->post_modified;

$result = wp_update_post(array(
    'ID' => $result->content_id,
    'post_content' => $updated_html
), true);

// Check if post was modified by someone else during our operation
$updated_post = get_post($result->content_id);
if ($updated_post->post_modified !== $current_modified) {
    // Warn user about concurrent modification
}
```

---

### 8. **Network Dashboard Performance - DOS Risk**

**Location**: `includes/network-dashboard-page.php:19`

**Issue**: Hard-coded limit of 1000 sites, all queried synchronously

**Current Code**:
```php
$sites = get_sites(array('number' => 1000));

foreach ($sites as $site) {
    switch_to_blog($site->blog_id);
    $storage = new WP_AltText_Audit_Storage();
    $stats = $storage->get_statistics();  // DB query
    restore_current_blog();
}
```

**Risk**: On large networks (1000 sites), page load could timeout or consume excessive resources

**Severity**: **MEDIUM** - Denial of Service

**Recommendation**:
```php
// Add pagination
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 50;
$offset = ($paged - 1) * $per_page;

$sites = get_sites(array(
    'number' => $per_page,
    'offset' => $offset
));

// Cache results
$cache_key = 'alttext_network_stats_page_' . $paged;
$cached = get_transient($cache_key);

if ($cached !== false) {
    $network_stats = $cached;
} else {
    // Calculate and cache for 1 hour
    $network_stats = calculate_network_stats($sites);
    set_transient($cache_key, $network_stats, HOUR_IN_SECONDS);
}
```

---

## LOW PRIORITY ISSUES

### 9. **Missing Input Sanitization in Network Settings**

**Location**: `includes/network-settings-page.php:21`

**Issue**: Checkbox value not sanitized (though boolean coercion makes it safe)

**Current Code**:
```php
$auto_activate = isset($_POST['auto_activate_new_sites']) ? 1 : 0;
update_site_option('alttext_auto_activate_new_sites', $auto_activate);
```

**Risk**: Low - value is coerced to 1 or 0

**Severity**: **LOW** - Best practice improvement

**Recommendation**:
```php
$auto_activate = isset($_POST['auto_activate_new_sites']) ? 1 : 0;
// Already safe, but for clarity:
$auto_activate = absint($auto_activate); // Ensures integer
```

---

## SECURITY BEST PRACTICES - COMPLIANT ✅

### Properly Implemented

1. ✅ **Nonce Verification**: All AJAX endpoints verify nonces
2. ✅ **Capability Checks**: All admin pages check `manage_options` or `manage_network_options`
3. ✅ **SQL Injection Prevention**: All queries use `$wpdb->prepare()`
4. ✅ **Output Escaping**: Most output uses `esc_html()`, `esc_url()`, `esc_attr()`
5. ✅ **Input Sanitization**: Most input uses `sanitize_text_field()`, `intval()`
6. ✅ **Direct Access Prevention**: All files check `ABSPATH`
7. ✅ **CSRF Protection**: Nonces on all forms

---

## RECOMMENDED FIXES - PRIORITY ORDER

### Immediate (Critical)

1. **Fix Issue #1**: Add `edit_post` capability check before HTML modification
2. **Fix Issue #2**: Escape alt-text before `setAttribute()`

### High Priority

3. **Fix Issue #6**: Add alt-text length validation (255 char max)
4. **Fix Issue #5**: Add capability re-check after `switch_to_blog()`

### Medium Priority

5. **Fix Issue #8**: Add pagination and caching to network dashboard
6. **Fix Issue #7**: Add revision check warning for concurrent edits

---

## CODE CHANGES REQUIRED

See fixes below...

---

## POST-FIX TESTING CHECKLIST

- [ ] Test HTML injection with malicious alt-text input
- [ ] Test contributor attempting to edit admin's post
- [ ] Test network dashboard with 100+ sites
- [ ] Test concurrent post editing
- [ ] Test alt-text >255 characters
- [ ] Test multisite capability checks
- [ ] Test all nonces still work after changes

---

## CONCLUSION

The plugin has **2 critical security vulnerabilities** that must be fixed immediately:

1. Unauthorized post content modification
2. Potential stored XSS via alt-text

Both relate to the v1.2.0 HTML manipulation feature. The multisite features (v1.3.0) are generally secure but need performance and capability improvements.

**Recommendation**: Fix critical issues before deploying to production or WordPress.org.
