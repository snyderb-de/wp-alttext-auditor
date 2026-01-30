# What's Next - WordPress.org Submission

## ✅ COMPLETED (Just Committed)

All critical and high-priority Plugin Check issues have been resolved:
- Text domain consistency fixed
- Hidden files removed
- Readme.txt updated (Tested up to: 6.9, tags reduced)
- Debug code removed
- Security audit files organized

**Commit**: `aa63c25` - "WordPress.org submission prep: Fix critical Plugin Check issues"

---

## 🔄 RECOMMENDED NEXT STEPS

### 1. **Test the Plugin** (30 min)
- Install on a fresh WordPress 6.9 test site
- Activate and verify no PHP warnings/errors
- Run a manual scan
- Test inline editing
- Test CSV export
- Verify multisite functionality (if you use multisite)

### 2. **Add Translator Comments** (1-2 hours)
**Priority**: HIGH (for international accessibility)

Add context for translators on the most important strings:

```php
// Before
__('Missing Alt-Text', 'wp-alttext-auditor');

// After
/* translators: Tab label showing images without alt text */
__('Missing Alt-Text', 'wp-alttext-auditor');
```

**Where to focus**:
- Dashboard tab labels
- Button text
- Statistics card titles
- Error messages
- User-facing notifications

**Benefit**: Makes plugin easier to translate, improves WordPress.org rating

### 3. **Fix Unordered Placeholders** (30 min)
**Priority**: MEDIUM (best practice, not blocking)

8 instances need ordered placeholders:

```php
// Before
sprintf(__('Showing %d to %d of %d images', 'domain'), $start, $end, $total);

// After
sprintf(__('Showing %1$d to %2$d of %3$d images', 'domain'), $start, $end, $total);
```

**Files to check**:
- `includes/class-audit-dashboard.php:1115` (and similar lines)

### 4. **Security Review** (30 min)
**Priority**: MEDIUM

Review the 12 instances flagged for missing escaping:
- Most are likely already safe or false positives
- Add `esc_html()`, `esc_attr()`, `esc_url()` where genuinely needed
- Document why some outputs don't need escaping (if in safe contexts)

### 5. **Optional: Replace parse_url()** (15 min)
**Priority**: LOW (WordPress wrapper is more secure)

One instance in `includes/class-audit-scanner.php:498`:

```php
// Before
$filename = basename(parse_url($image_src, PHP_URL_PATH));

// After
$parsed = wp_parse_url($image_src);
$filename = isset($parsed['path']) ? basename($parsed['path']) : '';
```

### 6. **Run Plugin Check Again** (5 min)
After completing fixes, run WordPress Plugin Check tool again to verify:
- No critical errors remain
- Warning count reduced
- Ready for submission

### 7. **Create Screenshots** (1 hour)
**Priority**: HIGH (required for WordPress.org)

Plugin Check expects screenshots matching readme.txt:
1. Media Library with inline alt-text editing
2. Audit Dashboard overview with statistics
3. Missing Alt-Text report with thumbnails
4. User attribution breakdown
5. Advanced filtering form
6. CSV export

**Format**: PNG or JPG, 1200px wide recommended
**Naming**: `screenshot-1.png`, `screenshot-2.png`, etc.

### 8. **Final Pre-Submission Checklist**
- [ ] Test on WordPress 6.9
- [ ] Test on PHP 7.4 and 8.2
- [ ] No PHP errors/warnings in debug mode
- [ ] Screenshots created and added
- [ ] README.txt validated at https://wordpress.org/plugins/developers/readme-validator/
- [ ] Plugin ZIP created (exclude: .git, .github, .claude, node_modules, docs/)
- [ ] Run Plugin Check one final time

---

## ⚠️ DECISION NEEDED: Plugin Name

**Issue**: Plugin name contains "WP" which WordPress.org discourages

**Options**:
1. **Keep name** and request exception (many plugins do this successfully)
2. **Rename to** "Alt Text Auditor" (remove WP prefix)
   - Requires slug change to `alt-text-auditor`
   - More work but follows guidelines strictly

**Recommendation**: Try submission as-is first. WordPress.org reviewers often allow "WP" for established plugins. Only rename if rejected.

---

## 📋 MEDIUM-PRIORITY IMPROVEMENTS (Post-Launch)

These can be done after initial WordPress.org approval:

1. **Remove deprecated load_plugin_textdomain()**
   - WordPress auto-loads since 4.6+
   - Already in "Requires at least: 5.0"

2. **Add object caching to direct database queries**
   - 40+ queries flagged
   - Most are already cached via transients
   - Consider additional caching for frequently-run queries

3. **Code organization**
   - Move some admin page code to separate files
   - Not blocking, just nice-to-have

---

## 🎯 IMMEDIATE ACTION

**Recommended order**:
1. Test plugin on WordPress 6.9 ✅ **(DO THIS FIRST)**
2. Create screenshots 📸
3. Add translator comments to top 20 strings 🌍
4. Fix unordered placeholders (8 instances) 🔢
5. Run Plugin Check again ✔️
6. Submit to WordPress.org 🚀

**Time estimate**: 3-4 hours total

---

## 📚 Resources

- WordPress Plugin Handbook: https://developer.wordpress.org/plugins/
- Plugin Check Tool: https://wordpress.org/plugins/plugin-check/
- README.txt Validator: https://wordpress.org/plugins/developers/readme-validator/
- Screenshot Guidelines: https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/

---

**Questions?** Review `PLUGIN-CHECK-TODO.md` for detailed analysis of all issues.
