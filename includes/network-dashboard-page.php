<?php
/**
 * Network-Wide Alt-Text Audit Dashboard (Multisite)
 *
 * Provides network admins with a consolidated view of alt-text compliance
 * across all sites in the network.
 *
 * @package WP_AltText_Auditor
 * @since 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get all sites in the network
global $wpdb;
$sites = get_sites(array('number' => 1000)); // Get up to 1000 sites

?>
<div class="wrap">
    <h1><?php echo esc_html__('Network-Wide Alt-Text Audit', 'alt-text-auditor'); ?></h1>

    <div class="notice notice-info">
        <p>
            <strong><?php echo esc_html__('Multisite Mode:', 'alt-text-auditor'); ?></strong>
            <?php
            printf(
                esc_html__('Viewing statistics across %d sites in your network. Each site maintains its own audit data.', 'alt-text-auditor'),
                count($sites)
            );
            ?>
        </p>
    </div>

    <h2><?php echo esc_html__('Sites Overview', 'alt-text-auditor'); ?></h2>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Site', 'alt-text-auditor'); ?></th>
                <th><?php echo esc_html__('URL', 'alt-text-auditor'); ?></th>
                <th><?php echo esc_html__('Total Images', 'alt-text-auditor'); ?></th>
                <th><?php echo esc_html__('Missing Alt-Text', 'alt-text-auditor'); ?></th>
                <th><?php echo esc_html__('Compliance', 'alt-text-auditor'); ?></th>
                <th><?php echo esc_html__('Actions', 'alt-text-auditor'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';

            $network_total_images = 0;
            $network_missing_alt = 0;

            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);

                // SECURITY: Re-verify capability in switched blog context
                if (!current_user_can('manage_options')) {
                    restore_current_blog();
                    continue;
                }

                $storage = new WP_AltText_Audit_Storage();
                $stats = $storage->get_statistics();

                $network_total_images += $stats['total_images'];
                $network_missing_alt += $stats['missing_alt'];

                $compliance_percentage = $stats['total_images'] > 0
                    ? round(($stats['has_alt'] / $stats['total_images']) * 100, 1)
                    : 0;

                $site_details = get_blog_details($site->blog_id);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($site_details->blogname); ?></strong></td>
                    <td><?php echo esc_html($site_details->siteurl); ?></td>
                    <td><?php echo esc_html($stats['total_images']); ?></td>
                    <td>
                        <?php if ($stats['missing_alt'] > 0) : ?>
                            <span style="color: #d63638; font-weight: 600;">
                                <?php echo esc_html($stats['missing_alt']); ?>
                            </span>
                        <?php else : ?>
                            <span style="color: #00a32a;">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($compliance_percentage >= 90) : ?>
                            <span style="color: #00a32a; font-weight: 600;">
                                <?php echo esc_html($compliance_percentage); ?>%
                            </span>
                        <?php elseif ($compliance_percentage >= 70) : ?>
                            <span style="color: #dba617; font-weight: 600;">
                                <?php echo esc_html($compliance_percentage); ?>%
                            </span>
                        <?php else : ?>
                            <span style="color: #d63638; font-weight: 600;">
                                <?php echo esc_html($compliance_percentage); ?>%
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(get_admin_url($site->blog_id, 'upload.php?page=alt-text-auditor-audit')); ?>" class="button">
                            <?php echo esc_html__('View Dashboard', 'alt-text-auditor'); ?>
                        </a>
                    </td>
                </tr>
                <?php
                restore_current_blog();
            }

            // Network totals row
            $network_compliance = $network_total_images > 0
                ? round((($network_total_images - $network_missing_alt) / $network_total_images) * 100, 1)
                : 0;
            ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f0f0f1; font-weight: 600;">
                <td colspan="2"><strong><?php echo esc_html__('Network Totals', 'alt-text-auditor'); ?></strong></td>
                <td><strong><?php echo esc_html($network_total_images); ?></strong></td>
                <td>
                    <strong style="color: <?php echo esc_attr($network_missing_alt > 0 ? '#d63638' : '#00a32a'); ?>;">
                        <?php echo esc_html($network_missing_alt); ?>
                    </strong>
                </td>
                <td>
                    <strong style="color: <?php echo esc_attr($network_compliance >= 90 ? '#00a32a' : ($network_compliance >= 70 ? '#dba617' : '#d63638')); ?>;">
                        <?php echo esc_html($network_compliance); ?>%
                    </strong>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="card" style="max-width: 800px; margin-top: 20px;">
        <h2><?php echo esc_html__('About Network-Wide Auditing', 'alt-text-auditor'); ?></h2>
        <p>
            <?php echo esc_html__('Each site in your network maintains its own audit database and settings. Network admins can view statistics across all sites from this dashboard.', 'alt-text-auditor'); ?>
        </p>
        <ul>
            <li><?php echo esc_html__('Site administrators can run scans and manage alt-text on their individual sites', 'alt-text-auditor'); ?></li>
            <li><?php echo esc_html__('Network statistics are calculated in real-time from each site\'s audit database', 'alt-text-auditor'); ?></li>
            <li><?php echo esc_html__('Visit individual site dashboards to perform scans and fix missing alt-text', 'alt-text-auditor'); ?></li>
        </ul>
    </div>
</div>
