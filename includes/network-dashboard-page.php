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

// Get sites in the network (paged for performance)
global $wpdb;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;
$offset = ($paged - 1) * $per_page;
$total_sites = get_sites(array('count' => true));
$sites = get_sites(array(
    'number' => $per_page,
    'offset' => $offset
));

require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';

$network_total_images = 0;
$network_missing_alt = 0;
$sites_data = array();

$cache_key = 'alttext_network_stats_page_' . $paged . '_' . $per_page;
$cached = get_transient($cache_key);
if (is_array($cached)) {
    $sites_data = isset($cached['sites_data']) ? $cached['sites_data'] : array();
    $network_total_images = isset($cached['network_total_images']) ? (int) $cached['network_total_images'] : 0;
    $network_missing_alt = isset($cached['network_missing_alt']) ? (int) $cached['network_missing_alt'] : 0;
} else {
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

        $site_details = get_blog_details($site->blog_id);
        $sites_data[] = array(
            'blog_id' => $site->blog_id,
            'blogname' => $site_details ? $site_details->blogname : '',
            'siteurl' => $site_details ? $site_details->siteurl : '',
            'stats' => $stats
        );

        restore_current_blog();
    }

    set_transient($cache_key, array(
        'sites_data' => $sites_data,
        'network_total_images' => $network_total_images,
        'network_missing_alt' => $network_missing_alt
    ), HOUR_IN_SECONDS);
}

$total_pages = $total_sites > 0 ? (int) ceil($total_sites / $per_page) : 1;

?>
<div class="wrap">
    <h1><?php echo esc_html__('Network-Wide Alt-Text Audit', 'alt-text-auditor'); ?></h1>

    <div class="notice notice-info">
        <p>
            <strong><?php echo esc_html__('Multisite Mode:', 'alt-text-auditor'); ?></strong>
            <?php
            /* translators: 1: number of sites shown on this page, 2: total sites in the network */
            printf(
                esc_html__('Viewing statistics for %1$d of %2$d sites in your network. Each site maintains its own audit data.', 'alt-text-auditor'),
                count($sites_data),
                (int) $total_sites
            );
            ?>
        </p>
    </div>

    <h2><?php echo esc_html__('Sites Overview', 'alt-text-auditor'); ?></h2>

    <div class="alttext-network-search" style="margin: 10px 0 15px; max-width: 520px;">
        <label for="alttext-site-search" style="display: block; font-weight: 600; margin-bottom: 6px;">
            <?php echo esc_html__('Find a site (current page)', 'alt-text-auditor'); ?>
        </label>
        <input
            type="search"
            id="alttext-site-search"
            name="alttext-site-search"
            list="alttext-site-search-list"
            class="regular-text"
            placeholder="<?php echo esc_attr__('Type a site name or URL to filter', 'alt-text-auditor'); ?>"
        />
        <datalist id="alttext-site-search-list">
            <?php foreach ($sites_data as $site_data) : ?>
                <option value="<?php echo esc_attr($site_data['blogname']); ?>"></option>
                <option value="<?php echo esc_attr($site_data['siteurl']); ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <p class="description" style="margin-top: 6px;">
            <?php echo esc_html__('Filters rows on this page only. Use pagination to view other sites.', 'alt-text-auditor'); ?>
        </p>
    </div>

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
            foreach ($sites_data as $site_data) {
                $stats = $site_data['stats'];
                $compliance_percentage = $stats['total_images'] > 0
                    ? round(($stats['has_alt'] / $stats['total_images']) * 100, 1)
                    : 0;
                ?>
                <tr class="alttext-site-row"
                    data-site-name="<?php echo esc_attr($site_data['blogname']); ?>"
                    data-site-url="<?php echo esc_attr($site_data['siteurl']); ?>">
                    <td><strong><?php echo esc_html($site_data['blogname']); ?></strong></td>
                    <td><?php echo esc_html($site_data['siteurl']); ?></td>
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
                        <a href="<?php echo esc_url(get_admin_url($site_data['blog_id'], 'upload.php?page=alt-text-auditor&view=audit')); ?>" class="button">
                            <?php echo esc_html__('View Dashboard', 'alt-text-auditor'); ?>
                        </a>
                    </td>
                </tr>
                <?php
            }

            // Network totals row
            $network_compliance = $network_total_images > 0
                ? round((($network_total_images - $network_missing_alt) / $network_total_images) * 100, 1)
                : 0;
            ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f0f0f1; font-weight: 600;">
                <td colspan="2"><strong><?php echo esc_html__('Page Totals', 'alt-text-auditor'); ?></strong></td>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var searchInput = document.getElementById('alttext-site-search');
            if (!searchInput) {
                return;
            }

            var rows = document.querySelectorAll('.alttext-site-row');
            var filterRows = function () {
                var query = searchInput.value.toLowerCase().trim();
                rows.forEach(function (row) {
                    if (!query) {
                        row.style.display = '';
                        return;
                    }
                    var name = (row.dataset.siteName || '').toLowerCase();
                    var url = (row.dataset.siteUrl || '').toLowerCase();
                    row.style.display = (name.indexOf(query) !== -1 || url.indexOf(query) !== -1) ? '' : 'none';
                });
            };

            searchInput.addEventListener('input', filterRows);
            searchInput.addEventListener('change', filterRows);
        });
    </script>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%', network_admin_url('admin.php?page=alt-text-auditor-network')),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;'),
                    'total' => $total_pages,
                    'current' => $paged,
                    'type' => 'plain'
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>

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
