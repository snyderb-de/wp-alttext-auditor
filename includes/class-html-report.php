<?php
/**
 * WP Alt Text HTML Report Generator
 *
 * Generates HTML reports for each scan with statistics,
 * missing images list, and user attribution.
 *
 * @package WP_AltText_Updater
 * @since 1.0.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_AltText_HTML_Report {

    /**
     * Storage instance
     *
     * @var WP_AltText_Audit_Storage
     */
    private $storage;

    /**
     * User attribution instance
     *
     * @var WP_AltText_User_Attribution
     */
    private $user_attribution;

    /**
     * Constructor
     *
     * @param WP_AltText_Audit_Storage $storage Storage instance
     * @param WP_AltText_User_Attribution $user_attribution User attribution instance
     */
    public function __construct($storage = null, $user_attribution = null) {
        if ($storage === null) {
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
            $this->storage = new WP_AltText_Audit_Storage();
        } else {
            $this->storage = $storage;
        }

        if ($user_attribution === null) {
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-user-attribution.php';
            $this->user_attribution = new WP_AltText_User_Attribution($this->storage);
        } else {
            $this->user_attribution = $user_attribution;
        }
    }

    /**
     * Generate and save HTML report
     *
     * @return string|false Report file path on success, false on failure
     */
    public function generate_report() {
        // Get statistics
        $stats = $this->storage->get_statistics(true);

        // Get user attribution
        $users = $this->user_attribution->get_user_counts(true);

        // Get missing alt-text items (limit to 500 for performance)
        $missing_items = $this->storage->get_results(array(
            'has_alt' => 0,
            'per_page' => 500,
            'page' => 1
        ));

        // Generate HTML
        $html = $this->generate_html($stats, $users, $missing_items);

        // Save to uploads directory
        $result = $this->save_report($html);

        // Log only if failed
        if (!$result) {
            alttext_auditor_log('Failed to generate/save HTML report.');
        }

        return $result;
    }

    /**
     * Generate HTML content
     *
     * @param array $stats Statistics data
     * @param array $users User attribution data
     * @param array $missing_items Missing alt-text items
     * @return string HTML content
     */
    private function generate_html($stats, $users, $missing_items) {
        $site_name = get_bloginfo('name');
        $scan_date = current_time('F j, Y g:i a');

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alt-Text Audit Report - <?php echo esc_html($site_name); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2271b1; margin-bottom: 10px; font-size: 32px; }
        .meta { color: #666; margin-bottom: 30px; font-size: 14px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; }
        .stat-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.danger { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .stat-card.info { background: linear-gradient(135deg, #2271b1 0%, #4f9fd9 100%); }
        .stat-value { font-size: 36px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 14px; opacity: 0.9; }
        h2 { color: #2271b1; margin: 40px 0 20px; font-size: 24px; border-bottom: 2px solid #2271b1; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f0f0f1; padding: 12px; text-align: left; font-weight: 600; color: #1d2327; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #f0f0f1; }
        tr:hover { background: #f9f9f9; }
        .user-name { font-weight: 600; }
        .percentage { color: #d63638; font-weight: 600; }
        .image-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .no-thumb { width: 60px; height: 60px; background: #f0f0f1; display: flex; align-items: center; justify-content: center; border-radius: 4px; color: #999; }
        .image-source { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: #666; }
        .found-in { font-size: 13px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 600; }
        .badge.post { background: #f0f6fc; color: #0969da; }
        .badge.media { background: #fff8e1; color: #f57c00; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #f0f0f1; text-align: center; color: #666; font-size: 13px; }
        @media print { body { background: white; padding: 0; } .container { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>Alt-Text Audit Report</h1>
        <div class="meta">
            <strong><?php echo esc_html($site_name); ?></strong> |
            Generated: <?php echo esc_html($scan_date); ?>
        </div>

        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-value"><?php echo esc_html(number_format($stats['total_images'])); ?></div>
                <div class="stat-label">Total Images Scanned</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-value"><?php echo esc_html(number_format($stats['missing_alt'])); ?></div>
                <div class="stat-label">Missing Alt-Text (<?php echo esc_html($stats['missing_percentage']); ?>%)</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo esc_html(number_format($stats['has_alt'])); ?></div>
                <div class="stat-label">Has Alt-Text (<?php echo esc_html($stats['has_percentage']); ?>%)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($users); ?></div>
                <div class="stat-label">Users with Missing Alt-Text</div>
            </div>
        </div>

        <?php if (!empty($stats['by_source'])): ?>
        <h2>Breakdown by Source</h2>
        <table>
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Total Images</th>
                    <th>Missing Alt-Text</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['by_source'] as $source => $data): ?>
                <tr>
                    <td><?php echo $source === 'post_content' ? 'Post Content' : 'Media Library'; ?></td>
                    <td><?php echo esc_html(number_format($data['total'])); ?></td>
                    <td><?php echo esc_html(number_format($data['missing'])); ?></td>
                    <td class="percentage"><?php echo esc_html($data['total'] > 0 ? round(($data['missing'] / $data['total']) * 100, 1) : 0); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($users)): ?>
        <h2>Missing Alt-Text by User</h2>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Total Images</th>
                    <th>Missing Alt-Text</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($users, 0, 20) as $user): ?>
                <tr>
                    <td class="user-name"><?php echo esc_html($user['display_name']); ?></td>
                    <td><?php echo esc_html($user['role']); ?></td>
                    <td><?php echo esc_html(number_format($user['total_images'])); ?></td>
                    <td><?php echo esc_html(number_format($user['missing_alt'])); ?></td>
                    <td class="percentage"><?php echo esc_html($user['missing_percentage']); ?>%</td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($users) > 20): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #666; font-style: italic;">
                        ... and <?php echo count($users) - 20; ?> more users
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if (!empty($missing_items['results'])): ?>
        <h2>Images Missing Alt-Text (First <?php echo count($missing_items['results']); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Image</th>
                    <th>Source</th>
                    <th>Found In</th>
                    <th>Type</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($missing_items['results'] as $item): ?>
                <?php
                $thumbnail = '';
                if ($item->attachment_id) {
                    $thumbnail = wp_get_attachment_image_url($item->attachment_id, 'thumbnail');
                }

                $found_in = '';
                if ($item->content_type === 'post_content' && $item->content_id) {
                    $post = get_post($item->content_id);
                    if ($post) {
                        $found_in = esc_html($post->post_title);
                    }
                } else {
                    $found_in = 'Media Library';
                }

                $user = get_userdata($item->user_id);
                $user_name = $user ? $user->display_name : 'Unknown';
                ?>
                <tr>
                    <td>
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="" class="image-thumb">
                        <?php else: ?>
                            <div class="no-thumb">📷</div>
                        <?php endif; ?>
                    </td>
                    <td class="image-source"><?php echo esc_html(basename($item->image_source)); ?></td>
                    <td class="found-in"><?php echo esc_html($found_in); ?></td>
                    <td>
                        <span class="badge <?php echo $item->content_type === 'post_content' ? 'post' : 'media'; ?>">
                            <?php echo $item->content_type === 'post_content' ? 'Post' : 'Media'; ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($user_name); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if ($missing_items['total'] > 500): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #666; font-style: italic;">
                        ... and <?php echo esc_html(number_format($missing_items['total'] - 500)); ?> more images.
                        View full list in WordPress admin dashboard.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="footer">
            <p>Generated by WP Alt-Text Updater v<?php echo ALTTEXT_AUDITOR_VERSION; ?></p>
            <p>For accessibility compliance and SEO optimization</p>
        </div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Save report to uploads directory
     *
     * @param string $html HTML content
     * @return string|false File path on success, false on failure
     */
    private function save_report($html) {
        // Get uploads directory
        $upload_dir = wp_upload_dir();
        $reports_dir = trailingslashit($upload_dir['basedir']) . 'alttext-reports';

        // Create directory if it doesn't exist
        if (!file_exists($reports_dir)) {
            wp_mkdir_p($reports_dir);

            // Add .htaccess to prevent direct listing
            $htaccess = $reports_dir . '/.htaccess';
            file_put_contents($htaccess, "Options -Indexes\n");
        }

        // Generate filename with timestamp
        $filename = 'alttext-report-' . current_time('Y-m-d-His') . '.html';
        $file_path = trailingslashit($reports_dir) . $filename;

        // Save file
        $result = file_put_contents($file_path, $html);

        if ($result === false) {
            return false;
        }

        // Store report info in options for dashboard access
        $this->store_report_info($filename);

        return $file_path;
    }

    /**
     * Store report information for dashboard access
     *
     * @param string $filename Report filename
     */
    private function store_report_info($filename) {
        $reports = get_option('alttext_audit_reports', array());

        // Add new report to beginning of array
        array_unshift($reports, array(
            'filename' => $filename,
            'date' => current_time('mysql'),
            'timestamp' => current_time('timestamp')
        ));

        // Keep only last 20 reports
        $reports = array_slice($reports, 0, 20);

        update_option('alttext_audit_reports', $reports);
    }

    /**
     * Get list of available reports
     *
     * @return array Array of report information
     */
    public static function get_available_reports() {
        return get_option('alttext_audit_reports', array());
    }

    /**
     * Get report download URL
     *
     * @param string $filename Report filename
     * @return string Download URL
     */
    public static function get_report_url($filename) {
        $upload_dir = wp_upload_dir();
        $reports_url = trailingslashit($upload_dir['baseurl']) . 'alttext-reports';

        return trailingslashit($reports_url) . $filename;
    }

    /**
     * Delete old reports (keep last 20)
     */
    public static function cleanup_old_reports() {
        $upload_dir = wp_upload_dir();
        $reports_dir = trailingslashit($upload_dir['basedir']) . 'alttext-reports';

        if (!file_exists($reports_dir)) {
            return;
        }

        $files = glob($reports_dir . '/alttext-report-*.html');

        // Sort by modification time, newest first
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Delete all except the 20 newest
        $files_to_delete = array_slice($files, 20);
        foreach ($files_to_delete as $file) {
            @unlink($file);
        }
    }
}
