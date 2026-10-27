<?php
/**
 * Offline security regression tests through the real plugin entry points.
 * WordPress services are test doubles; this is not a full WordPress install.
 * Usage: php tests/security-regression.php <source-root> <disposable-fixture-dir>
 */
$source_root = $argv[1] ?? dirname(__DIR__);
$fixture_root = $argv[2] ?? '';
if (!$fixture_root || !is_dir($fixture_root)) {
    fwrite(STDERR, "Provide an existing disposable fixture directory.\n");
    exit(2);
}
define('ABSPATH', $fixture_root . '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);

class TestResponse extends Exception {
    public $success;
    public $data;
    public $status;
    public function __construct($success, $data, $status = 200) {
        parent::__construct('WordPress response');
        $this->success = $success;
        $this->data = $data;
        $this->status = $status;
    }
}
function __( $s, $domain = null ) { return $s; }
function _e( $s, $domain = null ) { echo $s; }
function esc_attr($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function esc_html($s) { return esc_attr($s); }
function esc_attr__($s, $domain = null) { return esc_attr($s); }
function esc_html__($s, $domain = null) { return esc_html($s); }
function esc_url($s) { return esc_attr($s); }
function wp_unslash($s) { return stripslashes($s); }
function sanitize_text_field($s) { return is_scalar($s) ? trim(strip_tags((string) $s)) : ''; }
function trailingslashit($s) { return rtrim($s, '/') . '/'; }
function absint($s) { return abs((int) $s); }
function plugin_dir_path($s) { return dirname($s) . '/'; }
function plugin_dir_url($s) { return 'https://example.test/plugin/'; }
function plugin_basename($s) { return basename($s); }
function load_plugin_textdomain(...$args) {}
function register_activation_hook(...$args) {}
function register_deactivation_hook(...$args) {}
function add_action($name, $callback, ...$args) { $GLOBALS['hooks'][$name][] = $callback; }
function add_filter(...$args) {}
function do_action(...$args) {}
function is_admin() { return true; }
function is_multisite() { return $GLOBALS['multisite']; }
function get_sites($args) { return [1, 2]; }
function switch_to_blog($id) { $GLOBALS['site_stack'][] = $GLOBALS['site']; $GLOBALS['site'] = $id; }
function restore_current_blog() { $GLOBALS['site'] = array_pop($GLOBALS['site_stack']); }
function wp_next_scheduled(...$args) { return false; }
function wp_unschedule_event(...$args) {}
function esc_sql($s) { return $s; }
function get_current_blog_id() { return $GLOBALS['site']; }
function get_current_user_id() { return 1; }
function current_user_can($capability, $id = null) {
    return $capability === 'edit_post' ? in_array((int) $id, $GLOBALS['editable'], true) : !empty($GLOBALS['caps'][$capability]);
}
function check_ajax_referer(...$args) {
    if (!$GLOBALS['nonce_valid']) { throw new TestResponse(false, [], 403); }
}
function check_admin_referer($action) {
    if (($_GET['_wpnonce'] ?? '') !== hash('sha256', $action)) { throw new TestResponse(false, [], 403); }
}
function wp_create_nonce($action) { return hash('sha256', $action); }
function wp_nonce_url($url, $action) { return esc_html(add_query_arg(['_wpnonce' => wp_create_nonce($action)], $url)); }
function wp_send_json_error($data = [], $status = 200) { throw new TestResponse(false, $data, $status); }
function wp_send_json_success($data = [], $status = 200) { throw new TestResponse(true, $data, $status); }
function wp_die($message, $title = '', $args = []) { throw new TestResponse(false, ['message' => $message], $args['response'] ?? 500); }
function nocache_headers() { $GLOBALS['nocache'] = true; header('Expires: Wed, 11 Jan 1984 05:00:00 GMT'); }
function get_post($id) { return $GLOBALS['posts'][(int) $id] ?? null; }
function get_post_meta($id, ...$args) { return $GLOBALS['meta'][$id] ?? ''; }
function update_post_meta($id, $key, $value) { $GLOBALS['writes'][] = ['meta', $id, $value]; $GLOBALS['meta'][$id] = $value; return true; }
function wp_update_post($data, ...$args) { $GLOBALS['writes'][] = ['post', $data['ID'], $data['post_content']]; return $data['ID']; }
function is_wp_error($value) { return false; }
function wp_parse_url($url) { return parse_url($url); }
function wp_date($format, $timestamp = null) { return date($format, $timestamp ?? time()); }
function wp_generate_uuid4() { return sprintf('00000000-0000-4000-8000-%012d', ++$GLOBALS['uuid']); }
function wp_rand($min, $max) { return random_int($min, $max); }
function get_option($name, $default = false) { return $GLOBALS['options'][$GLOBALS['site']][$name] ?? $default; }
function add_option($name, $value, $deprecated = '', $autoload = true) {
    if ($GLOBALS['fail_options'] || array_key_exists($name, $GLOBALS['options'][$GLOBALS['site']] ?? [])) { return false; }
    $GLOBALS['options'][$GLOBALS['site']][$name] = $value;
    $GLOBALS['autoload'][$name] = $autoload;
    return true;
}
function update_option($name, $value) {
    if ($GLOBALS['fail_options'] || $GLOBALS['fail_updates']) { return false; }
    $GLOBALS['options'][$GLOBALS['site']][$name] = $value;
    return true;
}
function delete_option($name) { if ($GLOBALS['fail_deletes']) { return false; } unset($GLOBALS['options'][$GLOBALS['site']][$name]); return true; }
function set_transient($name, $value, $expiry) { $GLOBALS['transients'][$name] = $value; }
function delete_transient($name) { unset($GLOBALS['transients'][$name]); }
function wp_upload_dir($time = null, $create_dir = true) { return ['basedir' => $GLOBALS['uploads'] . '/site-' . $GLOBALS['site'], 'baseurl' => 'https://example.test/uploads/site-' . $GLOBALS['site'], 'error' => $create_dir && $GLOBALS['upload_creation_error']]; }
function wp_mkdir_p($path) { return is_dir($path) || mkdir($path, 0700, true); }
function admin_url($path) { return 'https://example.test/site-' . $GLOBALS['site'] . '/wp-admin/' . $path; }
function add_query_arg($args, $url = 'https://example.test/wp-admin/admin.php') {
    $parts = parse_url($url);
    parse_str($parts['query'] ?? '', $query);
    return strtok($url, '?') . '?' . http_build_query(array_merge($query, $args));
}
function wp_parse_args($args, $defaults) { return array_merge($defaults, $args); }
function selected($a, $b) { if ($a === $b) { echo 'selected'; } }
function get_the_ID() { return 11; }
function wp_get_attachment_url($id) { return 'https://example.test/image.jpg'; }
function wp_get_attachment_image(...$args) { return ''; }
function get_attached_file($id) { return '/uploads/image.jpg'; }
function get_edit_post_link($id) { return 'https://example.test/edit'; }
function wp_reset_postdata() {}
class WP_Query {
    public $found_posts = 1;
    public $posts = [11];
    private $read = false;
    public function __construct($args) { $GLOBALS['query_args'] = $args; }
    public function have_posts() { return !$this->read; }
    public function the_post() { $this->read = true; }
}
class TestDatabase {
    public $prefix = 'wp_';
    public $options = 'wp_options';
    public function prepare($query, ...$args) { return $query; }
    public function get_row($query) { return $GLOBALS['record']; }
    public function update(...$args) { $GLOBALS['writes'][] = ['audit']; return 1; }
    public function query($query) { return true; }
}
function reset_state() {
    static $case = 0;
    $GLOBALS['site'] = 1;
    $GLOBALS['multisite'] = false;
    $GLOBALS['site_stack'] = [];
    $GLOBALS['caps'] = ['upload_files' => true];
    $GLOBALS['editable'] = [11, 21];
    $GLOBALS['nonce_valid'] = true;
    $GLOBALS['posts'] = [11 => (object) ['ID' => 11, 'post_type' => 'attachment'], 12 => (object) ['ID' => 12, 'post_type' => 'attachment'], 21 => (object) ['ID' => 21, 'post_type' => 'post', 'post_content' => '<img src="https://example.test/image.jpg" alt="">', 'post_modified' => '2026-01-01']];
    $GLOBALS['record'] = (object) ['id' => 1, 'content_type' => 'media_library', 'content_id' => 12, 'attachment_id' => 12, 'image_source' => 'https://example.test/image.jpg'];
    $GLOBALS['writes'] = $GLOBALS['meta'] = $GLOBALS['options'] = $GLOBALS['autoload'] = $GLOBALS['transients'] = $GLOBALS['hooks'] = [];
    $GLOBALS['fail_options'] = $GLOBALS['fail_deletes'] = $GLOBALS['fail_updates'] = $GLOBALS['upload_creation_error'] = $GLOBALS['nocache'] = false;
    $GLOBALS['uuid'] = 0;
    $GLOBALS['uploads'] = $GLOBALS['fixture_root'] . '/case-' . ++$case;
    $_GET = $_POST = [];
    $GLOBALS['wpdb'] = new TestDatabase();
}
$GLOBALS['fixture_root'] = $fixture_root;
reset_state();
require $source_root . '/alt-text-auditor.php';
require_once $source_root . '/includes/class-html-report.php';
require_once $source_root . '/includes/class-scan-manager.php';
$plugin = new WP_AltText_Updater();
if (($argv[3] ?? '') === '--bootstrap') { return; }

// The report endpoint exits on success, so exercise it in an isolated subprocess.
if (($argv[3] ?? '') === '--serve') {
    $fixture = json_decode(stream_get_contents(STDIN), true);
    foreach ($fixture as $name => $value) { $GLOBALS[$name] = $value; }
    $_GET = $fixture['get'];
    register_shutdown_function(function() { fwrite(STDERR, $GLOBALS['nocache'] ? 'NO_CACHE' : 'CACHEABLE'); });
    try { WP_AltText_HTML_Report::serve_report(); }
    catch (TestResponse $response) { echo 'DENIED:' . $response->status; exit(0); }
    exit(1);
}
function expect($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
function response($method) {
    try { $GLOBALS['plugin']->$method(); }
    catch (TestResponse $response) { return $response; }
    throw new RuntimeException('Handler did not terminate with a response');
}
function save_report($html = '<!DOCTYPE html><html><body>Private audit</body></html>') {
    $report = new WP_AltText_HTML_Report(new stdClass(), new stdClass());
    $save = new ReflectionMethod($report, 'save_report');
    $save->setAccessible(true);
    return $save->invoke($report, $html);
}
function legacy_report($filename = 'alttext-report-2026-01-01-000000.html') {
    $path = wp_upload_dir()['basedir'] . '/alttext-reports/' . $filename;
    wp_mkdir_p(dirname($path));
    file_put_contents($path, '<html>Legacy private audit</html>');
    return $path;
}
function serve($filename, $changes = []) {
    $url = WP_AltText_HTML_Report::get_report_url($filename);
    parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $get);
    $fixture = array_merge(['get' => $get, 'site' => $GLOBALS['site'], 'options' => $GLOBALS['options'], 'caps' => ['manage_options' => true]], $changes);
    $process = proc_open([PHP_BINARY, __FILE__, $GLOBALS['source_root'], $GLOBALS['fixture_root'], '--serve'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fwrite($pipes[0], json_encode($fixture)); fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    expect(proc_close($process) === 0, 'Report subprocess failed: ' . $err);
    expect($err === 'NO_CACHE', 'Private report response did not disable caching');
    return $out;
}
function render_manager($orderby, $order) {
    $_GET = ['orderby' => $orderby, 'order' => $order];
    ob_start();
    try { include $GLOBALS['source_root'] . '/includes/admin-page.php'; return ob_get_clean(); }
    catch (Throwable $error) { ob_end_clean(); throw $error; }
}
$tests = [];
foreach (['title', 'alt_status'] as $sort) {
    $tests['sorting rejects injected attributes: ' . $sort] = function() use ($sort) {
        foreach (['DESC" onmouseover="alert(1)', "DESC' autofocus onfocus='alert(1)", 'DESC&#34; onmouseover=alert(1)', ['DESC']] as $payload) {
            $dom = new DOMDocument();
            @$dom->loadHTML(render_manager($sort, $payload));
            foreach ($dom->getElementsByTagName('th') as $header) {
                foreach ($header->attributes as $attribute) { expect(stripos($attribute->name, 'on') !== 0 && $attribute->name !== 'autofocus', 'Injected event attribute reached a sorting header'); }
            }
            expect(strpos(render_manager($sort, $payload), 'alert(1)') === false, 'Untrusted sort value remains in rendered page');
        }
    };
}
$tests['sorting preserves ascending, descending, and defaults'] = function() {
    expect(strpos(render_manager('title', 'asc'), 'sorted asc') !== false, 'Ascending indicator lost');
    expect($GLOBALS['query_args']['order'] === 'ASC', 'Ascending query changed');
    expect(strpos(render_manager('alt_status', 'DESC'), 'sorted desc') !== false, 'Descending indicator lost');
    expect($GLOBALS['query_args']['order'] === 'ASC', 'Alt-status reverse ordering changed');
    expect(strpos(render_manager('title', 'invalid'), 'sorted desc') !== false, 'Invalid order did not default safely');
};
foreach (['ajax_update_alt_text', 'ajax_update_audit_record'] as $method) {
    $tests[$method . ' denies foreign attachments without writes'] = function() use ($method) {
        $_POST = ['attachment_id' => 12, 'result_id' => 1, 'alt_text' => 'Forged'];
        $r = response($method);
        expect(!$r->success && $r->status === 403 && !$GLOBALS['writes'], 'Foreign attachment update was not denied before writes');
    };
    $tests[$method . ' allows authorized attachment edits'] = function() use ($method) {
        $GLOBALS['editable'][] = 12;
        $_POST = ['attachment_id' => 12, 'result_id' => 1, 'alt_text' => 'Allowed'];
        expect(response($method)->success && $GLOBALS['meta'][12] === 'Allowed', 'Authorized attachment update failed');
    };
    $tests[$method . ' rejects invalid nonce'] = function() use ($method) {
        $GLOBALS['nonce_valid'] = false;
        $_POST = ['attachment_id' => 11, 'result_id' => 1, 'alt_text' => 'Forged'];
        expect(response($method)->status === 403 && !$GLOBALS['writes'], 'Invalid nonce allowed writes');
    };
}
$tests['audit authorizes attachment before any post mutation'] = function() {
    $GLOBALS['record']->content_type = 'post_content'; $GLOBALS['record']->content_id = 21;
    $_POST = ['result_id' => 1, 'alt_text' => 'Forged'];
    expect(response('ajax_update_audit_record')->status === 403 && !$GLOBALS['writes'], 'Denied attachment caused a partial post write');
};
$tests['audit denies unauthorized draft record even with own attachment'] = function() {
    $GLOBALS['record']->content_type = 'draft_content'; $GLOBALS['record']->content_id = 22; $GLOBALS['record']->attachment_id = 11;
    $_POST = ['result_id' => 1, 'alt_text' => 'Forged'];
    expect(response('ajax_update_audit_record')->status === 403 && !$GLOBALS['writes'], 'Foreign draft audit record was changed');
};
$tests['audit preserves post-only and stale-attachment editing'] = function() {
    foreach ([0, 99] as $attachment_id) {
        $GLOBALS['record']->content_type = 'post_content'; $GLOBALS['record']->content_id = 21; $GLOBALS['record']->attachment_id = $attachment_id;
        $_POST = ['result_id' => 1, 'alt_text' => 'Description'];
        expect(response('ajax_update_audit_record')->success, 'Authorized post-only update failed');
    }
    expect($GLOBALS['writes'][0][0] === 'post', 'Post content was not saved');
};
$tests['audit updates both authorized objects'] = function() {
    $GLOBALS['editable'][] = 12;
    $GLOBALS['record']->content_type = 'post_content'; $GLOBALS['record']->content_id = 21;
    $_POST = ['result_id' => 1, 'alt_text' => 'Description'];
    expect(response('ajax_update_audit_record')->success, 'Combined update failed');
    expect(array_column($GLOBALS['writes'], 0) === ['post', 'meta', 'audit'], 'Combined update lost a write');
};
$tests['authors cannot set shared cancellation flag'] = function() {
    expect(response('ajax_cancel_scan')->status === 403 && !$GLOBALS['transients'], 'Author could cancel administrator scan');
};
$tests['administrators can cancel scans'] = function() {
    $GLOBALS['caps']['manage_options'] = true;
    expect(response('ajax_cancel_scan')->success && !empty($GLOBALS['transients']['alttext_audit_scan_cancelled']), 'Administrator cancellation failed');
};
$tests['reports have no public file and require administrator permission'] = function() {
    $filename = basename(save_report());
    expect(!file_exists(wp_upload_dir()['basedir'] . '/alttext-reports/' . $filename), 'Private report HTML was written to public uploads');
    expect(strpos(WP_AltText_HTML_Report::get_report_url($filename), 'admin-post.php') !== false, 'Report URL bypasses authenticated handler');
    expect(serve($filename, ['caps' => []]) === 'DENIED:403', 'Anonymous caller could read report');
    expect(serve($filename, ['caps' => ['upload_files' => true]]) === 'DENIED:403', 'Author could read report');
    expect(serve($filename) === '<!DOCTYPE html><html><body>Private audit</body></html>', 'Administrator report content changed');
    foreach ($GLOBALS['autoload'] as $option => $autoload) { if (strpos($option, 'alttext_audit_report_') === 0) { expect($autoload === false, 'Report content autoloads on every request'); } }
};
$tests['reports reject wrong nonce, traversal, arrays, and missing IDs'] = function() {
    $filename = basename(save_report());
    expect(serve($filename, ['get' => ['report' => $filename, '_wpnonce' => 'bad']]) === 'DENIED:403', 'Invalid report nonce accepted');
    foreach (['../' . $filename, '%2e%2e/' . $filename, [$filename], 'alttext-report-missing.html'] as $invalid) {
        expect(serve($filename, ['get' => ['report' => $invalid, '_wpnonce' => 'bad']]) !== '<!DOCTYPE html><html><body>Private audit</body></html>', 'Malformed report reference disclosed content');
    }
};
$tests['legacy public reports are preserved privately and removed'] = function() {
    $path = legacy_report(); $filename = basename($path);
    expect(WP_AltText_HTML_Report::migrate_legacy_reports(), 'Legacy migration failed');
    expect(!file_exists($path), 'Legacy report remained public');
    expect(serve($filename) === '<html>Legacy private audit</html>', 'Legacy report content or ID changed');
    expect(WP_AltText_HTML_Report::migrate_legacy_reports(), 'Migration is not idempotent');
};
$tests['migration failure preserves original and remains visibly retryable'] = function() {
    $path = legacy_report(); $GLOBALS['fail_options'] = true;
    expect(!WP_AltText_HTML_Report::migrate_legacy_reports() && file_exists($path), 'Failed migration lost original report');
    $GLOBALS['plugin']->init();
    expect(!empty($GLOBALS['hooks']['admin_notices']), 'Failed migration has no administrator warning');
    $GLOBALS['fail_options'] = false;
    expect(WP_AltText_HTML_Report::migrate_legacy_reports() && !file_exists($path), 'Migration did not retry successfully');
};
$tests['reports remain scoped to their own site'] = function() {
    $filename = basename(save_report()); $GLOBALS['site'] = 2;
    expect(serve($filename) === 'DENIED:404', 'Another site could read private report');
    $GLOBALS['site'] = 1; expect(strpos(serve($filename), 'Private audit') !== false, 'Original site lost report');
};
$tests['retention, selected deletion, and clear-all remove private content'] = function() {
    update_option('alttext_report_retention_count', 1);
    $first = basename(save_report()); $second = basename(save_report());
    expect(serve($first) === 'DENIED:404' && strpos(serve($second), 'Private audit') !== false, 'Report retention failed');
    $manager = new WP_AltText_Scan_Manager();
    $id = $manager->create_scan(['report_filename' => $second]);
    expect($manager->get_report_url($second) === WP_AltText_HTML_Report::get_report_url($second), 'Scan history still uses public URL');
    expect($manager->delete_scans([$id]) === 1 && serve($second) === 'DENIED:404', 'Scan deletion left report content');
    $third = basename(save_report()); WP_AltText_HTML_Report::delete_all_reports();
    expect(serve($third) === 'DENIED:404' && !WP_AltText_HTML_Report::get_available_reports(), 'Clear-all left report content');
};
$tests['JSON report links survive JavaScript HTML escaping'] = function() {
    $filename = basename(save_report());
    $url = WP_AltText_HTML_Report::get_report_url($filename);
    expect(strpos($url, '&amp;') === false && strpos($url, '&#') === false, 'Report URL is already HTML-escaped');
    $dom = new DOMDocument(); @$dom->loadHTML('<a href="' . esc_attr($url) . '">View Report</a>');
    parse_str(parse_url($dom->getElementsByTagName('a')->item(0)->getAttribute('href'), PHP_URL_QUERY), $query);
    expect($query['report'] === $filename && isset($query['_wpnonce']), 'Report link query was double-escaped');
};
$tests['failed private-content deletion preserves scan and report references'] = function() {
    $filename = basename(save_report());
    $manager = new WP_AltText_Scan_Manager(); $id = $manager->create_scan(['report_filename' => $filename]);
    $GLOBALS['fail_deletes'] = true;
    expect($manager->delete_scans([$id]) === 0 && $manager->get_scan($id), 'Failed deletion dropped scan reference');
    expect(count(WP_AltText_HTML_Report::get_available_reports()) === 1 && strpos(serve($filename), 'Private audit') !== false, 'Failed deletion dropped report reference');
    expect(!WP_AltText_HTML_Report::delete_all_reports(), 'Failed clear-all reported success');
};
$tests['failed report-index update is reported and retryable'] = function() {
    $filename = basename(save_report()); $GLOBALS['fail_updates'] = true;
    expect(!WP_AltText_HTML_Report::delete_report($filename), 'Failed index update reported success');
    expect(count(WP_AltText_HTML_Report::get_available_reports()) === 1, 'Failed update lost retry reference');
    $GLOBALS['fail_updates'] = false;
    expect(WP_AltText_HTML_Report::delete_report($filename) && !WP_AltText_HTML_Report::get_available_reports(), 'Index update could not be retried');
};
$tests['private report deletion does not create uploads directories'] = function() {
    $filename = basename(save_report()); $GLOBALS['upload_creation_error'] = true;
    expect(WP_AltText_HTML_Report::delete_report($filename), 'Unrelated uploads creation error blocked private deletion');
    expect(serve($filename) === 'DENIED:404', 'Private content remained after deletion');
};
$tests['failed removal of legacy public file keeps backup and scan'] = function() {
    $filename = basename(save_report()); $file = legacy_report($filename);
    $manager = new WP_AltText_Scan_Manager(); $id = $manager->create_scan(['report_filename' => $filename]);
    chmod(dirname($file), 0500);
    try {
        expect($manager->delete_scans([$id]) === 0 && $manager->get_scan($id), 'Failed file deletion dropped scan');
        expect(file_exists($file) && strpos(serve($filename), 'Private audit') !== false, 'Failed file deletion dropped backup');
    } finally { chmod(dirname($file), 0700); }
    expect($manager->delete_scans([$id]) === 1 && !file_exists($file), 'Legacy file deletion did not retry');
};
$tests['old-scan cleanup keeps references when private deletion fails'] = function() {
    $filename = basename(save_report());
    $scans = [];
    for ($i = 0; $i < 51; $i++) { $scans[] = ['id' => 'scan-' . $i, 'report_filename' => $filename]; }
    update_option(WP_AltText_Scan_Manager::SCANS_OPTION, $scans);
    $GLOBALS['fail_deletes'] = true;
    $manager = new WP_AltText_Scan_Manager(); $manager->cleanup_old_scans();
    expect(count($manager->get_scans()) === 51, 'Old-scan cleanup dropped failed report reference');
};
$tests['clear-all refuses to erase scan history when migration fails'] = function() {
    legacy_report();
    $GLOBALS['caps']['manage_options'] = true; $GLOBALS['fail_options'] = true;
    expect(response('ajax_clear_all_data')->status === 500, 'Clear-all claimed success after migration failure');
};
$tests['uninstall removes private reports from every network site'] = function() {
    $first = basename(save_report()); $GLOBALS['site'] = 2; $second = basename(save_report()); $GLOBALS['site'] = 1;
    $GLOBALS['multisite'] = true;
    if (!defined('WP_UNINSTALL_PLUGIN')) { define('WP_UNINSTALL_PLUGIN', true); }
    include $GLOBALS['source_root'] . '/uninstall.php';
    expect($GLOBALS['site'] === 1, 'Uninstall did not restore site context');
    expect(serve($first) === 'DENIED:404', 'First network site retained report');
    $GLOBALS['site'] = 2; expect(serve($second) === 'DENIED:404', 'Second network site retained report');
};
$failures = 0;
foreach ($tests as $name => $test) {
    reset_state();
    try { $test(); echo "PASS: $name\n"; }
    catch (Throwable $error) { $failures++; echo "FAIL: $name: " . $error->getMessage() . "\n"; }
}
echo count($tests) . ' tests; ' . $failures . " failures\n";
exit($failures ? 1 : 0);
