# Plugin Check TODO - WordPress.org Submission

**Plugin**: WP Alt Text Auditor v1.3.2
**Analysis Date**: January 30, 2026
**Status**: Pre-submission review

---

## CRITICAL (Must Fix Before Submission)

### 1. Text Domain Inconsistency ⚠️ HIGH PRIORITY
**Issue**: Plugin uses mixed text domains (`wp-alttext-updater` vs `wp-alttext-auditor`)
**Impact**: Blocks proper internationalization, confuses translators
**Files Affected**:
- `wp-alttext-auditor.php` (main file)
- `includes/class-audit-dashboard.php`
- `includes/admin-page.php`
- `includes/class-user-attribution.php`

**Action**: Find/replace all instances of `'wp-alttext-updater'` with `'wp-alttext-auditor'`

### 2. Hidden Files in Package
**Issue**: `.DS_Store` files detected (macOS system files)
**Impact**: Unprofessional, adds unnecessary bloat
**Action**: Remove all `.DS_Store` files and add to `.gitignore`

```bash
find . -name ".DS_Store" -delete
echo ".DS_Store" >> .gitignore
```

### 3. Readme.txt - "Tested up to" Outdated
**Issue**: Shows 6.7, current WordPress is 6.9
**Impact**: Signals plugin may not be compatible with latest WordPress
**Action**: Update to `Tested up to: 6.9` after testing

### 4. AI Instruction Directory Detected
**Issue**: `.claude/` directory present in plugin package
**Impact**: Exposes development artifacts, bloats package
**Action**: Remove from distribution package, add to `.gitignore`

### 5. Unexpected Markdown Files
**Files**: `SECURITY-AUDIT.md`, `SECURITY-AUDIT-V1.3.md`
**Impact**: Non-standard files increase package size
**Action**: Move to `/docs` directory or remove from distribution

---

## HIGH PRIORITY (Should Fix)

### 6. Plugin Name Contains "WP"
**Issue**: Plugin slug/name uses trademarked term "wp"
**Impact**: May be rejected by WordPress.org reviewers
**Note**: Many plugins use "WP" prefix, but guidelines discourage it
**Action**: Consider renaming to "Alt Text Auditor" (remove WP prefix) OR request exception

### 7. Readme.txt Tag Limit Exceeded
**Issue**: 7 tags used, limit is 5
**Current**: `alt-text, accessibility, media, images, wcag, seo, multisite`
**Action**: Reduce to 5 most important tags:
```
Tags: alt-text, accessibility, media, wcag, multisite
```

### 8. Debug Code in Production
**Issue**: Multiple `error_log()` calls in `wp-alttext-auditor.php`
**Lines**: 2182, 2206, 2230, 2234
**Action**: Remove or wrap in `WP_DEBUG` conditional

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Debug message');
}
```

### 9. Missing Translator Comments
**Issue**: 50+ instances of missing translator comments for i18n strings
**Impact**: Translators won't have context
**Example Fix**:
```php
// Before
__('Missing Alt-Text', 'wp-alttext-auditor');

// After
/* translators: Tab label for images without alt text */
__('Missing Alt-Text', 'wp-alttext-auditor');
```

### 10. Unordered Placeholders in Translations
**Issue**: 8 instances with mixed `%s` and `%d` without ordering
**Example**: `includes/class-audit-dashboard.php:1115`
**Fix**: Use ordered placeholders:
```php
// Before
sprintf(__('Showing %d to %d of %d images', 'domain'), $start, $end, $total);

// After
sprintf(__('Showing %1$d to %2$d of %3$d images', 'domain'), $start, $end, $total);
```

---

## MEDIUM PRIORITY (Best Practices)

### 11. Deprecated Function Usage
**Issue**: `load_plugin_textdomain()` discouraged since WP 4.6+
**File**: `wp-alttext-auditor.php:88`
**Note**: Still works, but WordPress auto-loads text domains now
**Action**: Remove if targeting WP 5.0+ (already in requirements)

### 12. Direct Database Queries Without Caching
**Issue**: 40+ direct `$wpdb` queries flagged
**Note**: Some are legitimate (custom tables), but consider object caching
**Action**: Add transient caching for frequently-run queries (already done for stats)

### 13. Use `wp_parse_url()` Instead of `parse_url()`
**File**: `includes/class-audit-scanner.php:498`
**Reason**: WordPress wrapper is more secure
**Fix**:
```php
// Before
$filename = basename(parse_url($image_src, PHP_URL_PATH));

// After
$parsed = wp_parse_url($image_src);
$filename = isset($parsed['path']) ? basename($parsed['path']) : '';
```

### 14. Nonce Variable Name Convention
**Issue**: Uses `$_REQUEST['nonce']` instead of `$_POST['nonce']`
**Files**: Multiple AJAX handlers
**Action**: Use specific superglobal (`$_POST` for AJAX)

### 15. Missing Escaping in Some Outputs
**Issue**: 12 instances flagged for missing `esc_html()`, `esc_attr()`, etc.
**Files**: Various template files
**Action**: Audit and add appropriate escaping

---

## LOW PRIORITY / OPTIONAL (Common Warnings)

### 16. Global Variable Naming
**Issue**: Custom globals don't use plugin prefix
**Note**: Many plugins have this, not blocking
**Action**: Defer to future refactor

### 17. File Organization
**Issue**: Some admin page code in main plugin file
**Note**: Common pattern, not a blocker
**Action**: Consider moving to separate files in future version

### 18. Duplicate Constant Checks
**Issue**: Constants defined without checking existence (FIXED in latest)
**Status**: ✅ Already fixed with conditional checks

---

## IGNORE (False Positives / Acceptable)

### 19. "Disallow Direct File Access" Warnings
**Note**: Plugin uses alternative security methods (capability checks)
**Action**: None required

### 20. Some Escaping Warnings on Admin Pages
**Note**: Many are already escaped or safe contexts
**Action**: Audit individually, but not all need fixes

---

## Recommended Fix Order

1. **Text domain consistency** (critical for i18n)
2. **Remove hidden/dev files** (.DS_Store, .claude/, security audits)
3. **Update readme.txt** (tested up to, tags)
4. **Remove debug code** (error_log calls)
5. **Add translator comments** (top 20 most important strings)
6. **Fix unordered placeholders** (8 instances)
7. **Consider plugin name change** (if "WP" is rejected)
8. **Security review** (missing escaping, parse_url)

---

## Quick Wins (Fix in <30 min)

- Remove `.DS_Store` files
- Remove `.claude/` directory from package
- Move/remove security audit markdown files
- Update readme.txt tested version
- Remove `error_log()` calls
- Reduce tags to 5

---

## Notes

- **"Some plugins have these too"**: You're right that many successful plugins have similar warnings. WordPress.org reviewers focus on security/functionality over perfect code style.
- **Must fix**: Text domain, hidden files, debug code
- **Should fix**: Translator comments, readme issues
- **Optional**: Coding style preferences

---

## Submission Checklist

- [ ] All text domains use `wp-alttext-auditor`
- [ ] No hidden files (.DS_Store)
- [ ] No dev artifacts (.claude/)
- [ ] Readme.txt tested up to 6.9
- [ ] Readme.txt tags reduced to 5
- [ ] No debug code (error_log)
- [ ] Security audit markdown files removed/moved
- [ ] Test plugin on WordPress 6.9
- [ ] Test plugin with PHP 7.4 and 8.2
- [ ] Run Plugin Check again after fixes
