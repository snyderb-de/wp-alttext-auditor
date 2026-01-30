<?php
/**
 * Network Settings Page (Multisite)
 *
 * Network-wide settings for the Alt-Text Auditor plugin.
 *
 * @package WP_AltText_Auditor
 * @since 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle settings save
if (isset($_POST['alttext_network_settings_nonce']) &&
    wp_verify_nonce($_POST['alttext_network_settings_nonce'], 'alttext_network_settings')) {

    if (current_user_can('manage_network_options')) {
        // Save network-wide settings
        $auto_activate = isset($_POST['auto_activate_new_sites']) ? 1 : 0;
        update_site_option('alttext_auto_activate_new_sites', $auto_activate);

        // Save cron settings
        $cron_enabled = isset($_POST['network_cron_enabled']) ? 1 : 0;
        $batch_size = isset($_POST['cron_batch_size']) ? intval($_POST['cron_batch_size']) : 10;

        // Validate batch size (must be 10, 25, 50, or 100)
        $allowed_batch_sizes = array(10, 25, 50, 100);
        if (!in_array($batch_size, $allowed_batch_sizes, true)) {
            $batch_size = 10; // Default to safest option
        }

        update_site_option('alttext_network_cron_enabled', $cron_enabled);
        update_site_option('alttext_cron_batch_size', $batch_size);

        // Schedule or unschedule network-wide cron
        if ($cron_enabled) {
            if (!wp_next_scheduled('alttext_audit_cron_scan')) {
                wp_schedule_event(time(), 'daily', 'alttext_audit_cron_scan');
            }
        } else {
            $timestamp = wp_next_scheduled('alttext_audit_cron_scan');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'alttext_audit_cron_scan');
            }
        }

        echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully.', 'wp-alttext-auditor') . '</p></div>';
    }
}

// Get current settings
$auto_activate = get_site_option('alttext_auto_activate_new_sites', 1);
$cron_enabled = get_site_option('alttext_network_cron_enabled', 0);
$batch_size = get_site_option('alttext_cron_batch_size', 10);
$next_scan = wp_next_scheduled('alttext_audit_cron_scan');

?>
<div class="wrap">
    <h1><?php echo esc_html__('Network Alt-Text Audit Settings', 'wp-alttext-auditor'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('alttext_network_settings', 'alttext_network_settings_nonce'); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="auto_activate_new_sites">
                            <?php echo esc_html__('Auto-activate for New Sites', 'wp-alttext-auditor'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input
                                type="checkbox"
                                id="auto_activate_new_sites"
                                name="auto_activate_new_sites"
                                value="1"
                                <?php checked($auto_activate, 1); ?>
                            />
                            <?php echo esc_html__('Automatically create audit tables when new sites are added to the network', 'wp-alttext-auditor'); ?>
                        </label>
                        <p class="description">
                            <?php echo esc_html__('When enabled, newly created sites will automatically have the alt-text audit database tables created.', 'wp-alttext-auditor'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Plugin Version', 'wp-alttext-auditor'); ?>
                    </th>
                    <td>
                        <strong><?php echo esc_html(WP_ALTTEXT_UPDATER_VERSION); ?></strong>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Network Sites', 'wp-alttext-auditor'); ?>
                    </th>
                    <td>
                        <?php
                        $site_count = get_blog_count();
                        printf(
                            esc_html__('%d sites in network', 'wp-alttext-auditor'),
                            $site_count
                        );
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <hr style="margin: 30px 0;" />

        <h2><?php echo esc_html__('Network-Wide Automatic Scanning', 'wp-alttext-auditor'); ?></h2>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="network_cron_enabled">
                            <?php echo esc_html__('Enable Automatic Scanning', 'wp-alttext-auditor'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input
                                type="checkbox"
                                id="network_cron_enabled"
                                name="network_cron_enabled"
                                value="1"
                                <?php checked($cron_enabled, 1); ?>
                            />
                            <?php echo esc_html__('Enable daily automatic scanning for all sites in the network', 'wp-alttext-auditor'); ?>
                        </label>
                        <p class="description">
                            <?php echo esc_html__('When enabled, the plugin will automatically scan sites in batches to prevent performance issues.', 'wp-alttext-auditor'); ?>
                        </p>
                        <?php if ($next_scan && $cron_enabled): ?>
                            <p class="description">
                                <strong><?php echo esc_html__('Next scheduled scan:', 'wp-alttext-auditor'); ?></strong>
                                <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $next_scan)); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="cron_batch_size">
                            <?php echo esc_html__('Sites Per Scan', 'wp-alttext-auditor'); ?>
                        </label>
                    </th>
                    <td>
                        <select id="cron_batch_size" name="cron_batch_size">
                            <option value="10" <?php selected($batch_size, 10); ?>>10 sites</option>
                            <option value="25" <?php selected($batch_size, 25); ?>>25 sites</option>
                            <option value="50" <?php selected($batch_size, 50); ?>>50 sites</option>
                            <option value="100" <?php selected($batch_size, 100); ?>>100 sites</option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Number of sites to scan each time the cron job runs. The cron job rotates through all sites.', 'wp-alttext-auditor'); ?>
                        </p>

                        <div class="notice notice-warning inline" style="margin: 15px 0; padding: 10px;">
                            <p><strong><?php echo esc_html__('Performance Warning:', 'wp-alttext-auditor'); ?></strong></p>
                            <ul style="margin: 10px 0 0 20px;">
                                <li><strong>10 sites (recommended):</strong> <?php echo esc_html__('Safe for most networks. Low server load.', 'wp-alttext-auditor'); ?></li>
                                <li><strong>25 sites:</strong> <?php echo esc_html__('Moderate load. Good for powerful servers.', 'wp-alttext-auditor'); ?></li>
                                <li><strong>50 sites:</strong> <?php echo esc_html__('Higher load. Ensure adequate server resources.', 'wp-alttext-auditor'); ?></li>
                                <li><strong>100 sites:</strong> <?php echo esc_html__('Very high load. Only for dedicated hosting with significant resources.', 'wp-alttext-auditor'); ?></li>
                            </ul>
                            <p style="margin-top: 10px;">
                                <?php
                                if ($site_count > 0 && $batch_size > 0) {
                                    $days_for_full_cycle = ceil($site_count / $batch_size);
                                    printf(
                                        esc_html__('With %d sites and batch size %d, it will take %d days to scan all sites once.', 'wp-alttext-auditor'),
                                        $site_count,
                                        $batch_size,
                                        $days_for_full_cycle
                                    );
                                }
                                ?>
                            </p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input
                type="submit"
                name="submit"
                id="submit"
                class="button button-primary"
                value="<?php echo esc_attr__('Save Settings', 'wp-alttext-auditor'); ?>"
            />
        </p>
    </form>

    <hr />

    <h2><?php echo esc_html__('Multisite Information', 'wp-alttext-auditor'); ?></h2>

    <div class="card">
        <h3><?php echo esc_html__('How Network-Wide Automatic Scanning Works', 'wp-alttext-auditor'); ?></h3>
        <p><?php echo esc_html__('The network-wide automatic scanning feature is designed to handle large multisite installations efficiently:', 'wp-alttext-auditor'); ?></p>
        <ul style="margin: 15px 0 0 20px;">
            <li><strong><?php echo esc_html__('Batch Processing:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Instead of scanning all sites at once, the cron job scans a configurable batch of sites per day (10/25/50/100).', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Rotation System:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('The plugin rotates through all sites in your network. Each day, it scans the next batch of sites.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Per-Site Control:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Individual site admins can still enable/disable automatic scanning for their own site. Network scans respect site-level settings.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Performance Protection:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('This prevents server overload on networks with hundreds or thousands of sites.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Full Coverage:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('All sites will be scanned eventually. The rotation ensures continuous monitoring across the entire network.', 'wp-alttext-auditor'); ?></li>
        </ul>
        <p style="margin-top: 15px;">
            <strong><?php echo esc_html__('Example:', 'wp-alttext-auditor'); ?></strong>
            <?php echo esc_html__('If you have 100 sites and set batch size to 10, the cron will scan sites 1-10 on day 1, sites 11-20 on day 2, and so on. After 10 days, it starts over at site 1.', 'wp-alttext-auditor'); ?>
        </p>
    </div>

    <div class="card">
        <h3><?php echo esc_html__('How Multisite Support Works', 'wp-alttext-auditor'); ?></h3>
        <ul>
            <li><strong><?php echo esc_html__('Per-Site Database Tables:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Each site in the network has its own audit database table.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Per-Site Configuration:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Site administrators control their own scan schedules and settings.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Network Dashboard:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Network admins can view aggregated statistics across all sites.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Network Activation:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('When network-activated, tables are created for all existing sites.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('New Sites:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Tables are automatically created when new sites are added (if auto-activate is enabled).', 'wp-alttext-auditor'); ?></li>
        </ul>
    </div>

    <div class="card">
        <h3><?php echo esc_html__('Capabilities Required', 'wp-alttext-auditor'); ?></h3>
        <ul>
            <li><strong><?php echo esc_html__('Network Admin:', 'wp-alttext-auditor'); ?></strong> <code>manage_network_options</code> <?php echo esc_html__('- Can view network dashboard and change network settings', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Site Admin:', 'wp-alttext-auditor'); ?></strong> <code>manage_options</code> <?php echo esc_html__('- Can run scans and view audit dashboard on their site', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Editor/Contributor:', 'wp-alttext-auditor'); ?></strong> <code>upload_files</code> <?php echo esc_html__('- Can edit alt-text in Media Library', 'wp-alttext-auditor'); ?></li>
        </ul>
    </div>
</div>
