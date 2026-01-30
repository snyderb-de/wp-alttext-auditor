<?php
/**
 * WP Alt Text Audit Dashboard
 *
 * Handles rendering of the audit dashboard UI including tabs,
 * statistics, and data tables.
 *
 * @package WP_AltText_Updater
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_AltText_Audit_Dashboard {

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
     */
    public function __construct() {
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-user-attribution.php';

        $this->storage = new WP_AltText_Audit_Storage();
        $this->user_attribution = new WP_AltText_User_Attribution($this->storage);
    }

    /**
     * Render the main dashboard page
     */
    public function render_dashboard() {
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

        // Load the dashboard template
        include ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/audit-dashboard-page.php';
    }

    /**
     * Render the overview tab
     *
     * Displays statistics cards and scan controls
     */
    public function render_overview_tab() {
        ?>
        <div class="audit-overview-tab">
            <h2><?php _e('Alt-Text Audit Overview', 'alt-text-auditor'); ?></h2>

            <p><?php _e('This dashboard provides a comprehensive view of all images across your site and their alt-text status.', 'alt-text-auditor'); ?></p>

            <!-- Statistics Cards (populated via AJAX) -->
            <div class="audit-stats-cards" id="audit-stats-cards">
                <div class="audit-loading">
                    <span class="spinner is-active"></span>
                    <p><?php _e('Loading statistics...', 'alt-text-auditor'); ?></p>
                </div>
            </div>

            <!-- Scan Controls -->
            <div class="audit-scan-controls">
                <h3>
                    <?php _e('Scan Controls', 'alt-text-auditor'); ?>
                    <span class="dashicons dashicons-info audit-help-icon" title="<?php esc_attr_e('Scans analyze your entire site for images. Content scan checks posts/pages HTML. Media scan checks attachments metadata.', 'alt-text-auditor'); ?>"></span>
                </h3>
                <p><?php _e('Run a fresh scan to analyze your content and media library for missing alt-text.', 'alt-text-auditor'); ?></p>

                <button id="scan-content-btn" class="button button-primary" title="<?php esc_attr_e('Scans all published posts and pages for images with missing alt-text', 'alt-text-auditor'); ?>">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Scan Published Content', 'alt-text-auditor'); ?>
                </button>

                <button id="scan-drafts-btn" class="button button-primary" title="<?php esc_attr_e('Scans all draft posts and pages for images with missing alt-text', 'alt-text-auditor'); ?>">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Scan Draft Content', 'alt-text-auditor'); ?>
                </button>

                <button id="scan-media-btn" class="button button-primary" title="<?php esc_attr_e('Scans all media library attachments for missing alt-text metadata', 'alt-text-auditor'); ?>">
                    <span class="dashicons dashicons-format-image"></span>
                    <?php _e('Scan Media Library', 'alt-text-auditor'); ?>
                </button>

                <button id="clear-cache-btn" class="button" title="<?php esc_attr_e('Clears the 24-hour statistics cache and recalculates immediately', 'alt-text-auditor'); ?>">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh Statistics', 'alt-text-auditor'); ?>
                </button>

                <p class="description">
                    <?php _e('Scans process in batches to prevent timeouts. Large sites may take several minutes. View all scans and reports in the "Scans" tab.', 'alt-text-auditor'); ?>
                </p>
            </div>

            <!-- Progress Bar (shown during scan) -->
            <div id="scan-progress" class="audit-scan-progress" style="display:none;">
                <div class="audit-progress-bar">
                    <div class="audit-progress-fill" style="width: 0%"></div>
                </div>
                <p class="audit-progress-text">
                    <?php _e('Scanning...', 'alt-text-auditor'); ?>
                    <span class="percentage">0%</span>
                    <span class="scan-items"></span>
                </p>
                <p class="audit-progress-eta" style="font-size: 12px; color: #666; margin-top: 5px;">
                    <span class="eta-text"></span>
                </p>
                <button type="button" id="cancel-scan-btn" class="button" style="margin-top: 10px;">
                    <?php _e('Cancel Scan', 'alt-text-auditor'); ?>
                </button>
                <details class="audit-scan-details" style="margin-top: 10px; font-size: 12px;">
                    <summary style="cursor: pointer; color: #2271b1; user-select: none;">
                        <?php _e('Show scan details', 'alt-text-auditor'); ?>
                    </summary>
                    <div class="scan-details-content" style="margin-top: 10px; padding: 10px; background: #f6f7f7; border-radius: 3px; max-height: 200px; overflow-y: auto;">
                        <p style="margin: 0; font-family: monospace; font-size: 11px; color: #555;">
                            <span class="current-scan-item"><?php _e('Initializing scan...', 'alt-text-auditor'); ?></span>
                        </p>
                    </div>
                </details>
            </div>

            <!-- Quick Help -->
            <div class="audit-notice" style="margin-top: 20px;">
                <p>
                    <strong><?php _e('How it works:', 'alt-text-auditor'); ?></strong><br>
                    <?php _e('1. Click a scan button above to analyze your content or media library', 'alt-text-auditor'); ?><br>
                    <?php _e('2. Wait for the scan to complete (progress will be shown)', 'alt-text-auditor'); ?><br>
                    <?php _e('3. View results in the "Missing Alt-Text" and "By User" tabs', 'alt-text-auditor'); ?><br>
                    <?php _e('4. Statistics are cached for 24 hours - use "Refresh Statistics" to update immediately', 'alt-text-auditor'); ?>
                </p>
            </div>

            <!-- Automatic Scanning -->
            <div class="audit-scan-controls" style="margin-top: 20px;">
                <h3><?php _e('Automatic Daily Scanning', 'alt-text-auditor'); ?></h3>
                <p class="description">
                    <?php _e('Enable automatic daily scans to keep your audit results up-to-date without manual intervention.', 'alt-text-auditor'); ?>
                </p>

                <?php
                $cron_enabled = get_option('alttext_audit_cron_enabled', 0);
                $next_scan = wp_next_scheduled('alttext_audit_cron_scan');
                ?>

                <label class="audit-cron-toggle" title="<?php esc_attr_e('Automatically run a full scan once per day using WordPress cron system', 'alt-text-auditor'); ?>">
                    <input type="checkbox" id="cron-enabled-checkbox" <?php checked($cron_enabled, 1); ?>>
                    <span><?php _e('Enable automatic daily scanning', 'alt-text-auditor'); ?></span>
                </label>

                <?php if ($cron_enabled && $next_scan) : ?>
                    <p class="description" id="next-scan-info">
                        <?php printf(
                            __('Next scan scheduled for: %s', 'alt-text-auditor'),
                            '<strong>' . wp_date(get_option('date_format') . ' ' . get_option('time_format'), $next_scan) . '</strong>'
                        ); ?>
                    </p>
                <?php endif; ?>

                <p class="audit-cron-status" id="cron-status-message" style="display:none;"></p>
            </div>

            <!-- Data Management -->
            <div class="audit-scan-controls" style="margin-top: 20px;">
                <h3><?php _e('Data Management', 'alt-text-auditor'); ?></h3>
                <p class="description">
                    <?php _e('Manage scan history, reports, and automatic cleanup settings.', 'alt-text-auditor'); ?>
                </p>

                <!-- Auto-cleanup Setting -->
                <div style="margin-bottom: 15px;">
                    <label for="auto-cleanup-days">
                        <strong><?php _e('Automatically delete old scans and reports after:', 'alt-text-auditor'); ?></strong>
                    </label>
                    <select id="auto-cleanup-days" name="auto_cleanup_days" style="margin-left: 10px;">
                        <?php
                        $cleanup_days = get_option('alttext_auto_cleanup_days', 'never');
                        $options = array(
                            'never' => __('Never (keep all)', 'alt-text-auditor'),
                            '30' => __('30 days', 'alt-text-auditor'),
                            '60' => __('60 days', 'alt-text-auditor'),
                            '90' => __('90 days', 'alt-text-auditor'),
                            '120' => __('120 days', 'alt-text-auditor'),
                            '365' => __('365 days (1 year)', 'alt-text-auditor')
                        );
                        foreach ($options as $value => $label) :
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($cleanup_days, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="save-cleanup-setting-btn" class="button" style="margin-left: 10px;">
                        <?php _e('Save Setting', 'alt-text-auditor'); ?>
                    </button>
                    <p class="description" style="margin-top: 5px;">
                        <?php _e('Old scans and their HTML reports will be automatically deleted based on this setting.', 'alt-text-auditor'); ?>
                    </p>
                </div>

                <!-- Clear All Data Button -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <button type="button" id="clear-all-data-btn" class="button button-secondary" style="color: #d63638;">
                        <span class="dashicons dashicons-trash"></span>
                        <?php _e('Clear All Scan Data', 'alt-text-auditor'); ?>
                    </button>
                    <p class="description" style="margin-top: 5px; color: #d63638;">
                        <?php _e('Warning: This will permanently delete ALL scan records and HTML reports. This action cannot be undone!', 'alt-text-auditor'); ?>
                    </p>
                    <p class="audit-clear-status" id="clear-data-status-message" style="display:none; margin-top: 10px;"></p>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render the scans tab
     *
     * Displays table of all scan history with reports
     */
    public function render_scans_tab() {
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-scan-manager.php';
        $scan_manager = new WP_AltText_Scan_Manager();

        // Get all scans
        $scans = $scan_manager->get_scans();

        // Get sorting parameters
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

        // Sort scans
        usort($scans, function($a, $b) use ($orderby, $order) {
            $result = 0;

            switch ($orderby) {
                case 'date':
                    $result = $a['timestamp'] - $b['timestamp'];
                    break;
                case 'type':
                    $result = strcmp($a['scan_type'], $b['scan_type']);
                    break;
                case 'user':
                    $user_a = get_userdata($a['user_id']);
                    $user_b = get_userdata($b['user_id']);
                    $name_a = $user_a ? $user_a->display_name : '';
                    $name_b = $user_b ? $user_b->display_name : '';
                    $result = strcmp($name_a, $name_b);
                    break;
                case 'total':
                    $result = $a['stats']['total'] - $b['stats']['total'];
                    break;
                case 'missing':
                    $result = $a['stats']['missing'] - $b['stats']['missing'];
                    break;
                case 'has_alt':
                    $result = $a['stats']['has_alt'] - $b['stats']['has_alt'];
                    break;
            }

            return $order === 'desc' ? -$result : $result;
        });
        ?>

        <div class="audit-scans-wrapper">
            <h2><?php _e('Scan History & Reports', 'alt-text-auditor'); ?></h2>
            <p class="description">
                <?php _e('View all scan history with downloadable HTML reports. Scans are generated from manual scans, automatic daily scans, or on-demand report generation.', 'alt-text-auditor'); ?>
            </p>

            <?php if (empty($scans)) : ?>
                <div class="notice notice-info inline">
                    <p><?php _e('No scans found. Run your first scan to see results here.', 'alt-text-auditor'); ?></p>
                </div>
            <?php else : ?>
                <form method="post" action="" id="scans-form">
                    <?php wp_nonce_field('alttext_bulk_scans', 'alttext_scans_nonce'); ?>

                    <div class="tablenav top">
                        <div class="alignleft actions bulkactions">
                            <select name="bulk_action" id="bulk-action-selector-top">
                                <option value="-1"><?php _e('Bulk Actions', 'alt-text-auditor'); ?></option>
                                <option value="delete"><?php _e('Delete', 'alt-text-auditor'); ?></option>
                            </select>
                            <button type="button" id="apply-bulk-action" class="button action"><?php _e('Apply', 'alt-text-auditor'); ?></button>
                        </div>
                    </div>

                    <table class="wp-list-table widefat fixed striped scans-table">
                        <thead>
                            <tr>
                                <td class="manage-column column-cb check-column">
                                    <input type="checkbox" id="select-all-scans" />
                                </td>
                                <th class="manage-column column-date sortable <?php echo $orderby === 'date' ? ($order === 'desc' ? 'desc' : 'asc') : 'desc'; ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'date', 'order' => ($orderby === 'date' && $order === 'desc' ? 'asc' : 'desc')))); ?>">
                                        <span><?php _e('Date', 'alt-text-auditor'); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="manage-column column-type sortable <?php echo $orderby === 'type' ? ($order === 'desc' ? 'desc' : 'asc') : 'desc'; ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'type', 'order' => ($orderby === 'type' && $order === 'desc' ? 'asc' : 'desc')))); ?>">
                                        <span><?php _e('Type', 'alt-text-auditor'); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="manage-column column-trigger">
                                    <?php _e('Trigger', 'alt-text-auditor'); ?>
                                </th>
                                <th class="manage-column column-user sortable <?php echo $orderby === 'user' ? ($order === 'desc' ? 'desc' : 'asc') : 'desc'; ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'user', 'order' => ($orderby === 'user' && $order === 'desc' ? 'asc' : 'desc')))); ?>">
                                        <span><?php _e('User', 'alt-text-auditor'); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="manage-column column-total sortable <?php echo $orderby === 'total' ? ($order === 'desc' ? 'desc' : 'asc') : 'desc'; ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'total', 'order' => ($orderby === 'total' && $order === 'desc' ? 'asc' : 'desc')))); ?>">
                                        <span><?php _e('Total Images', 'alt-text-auditor'); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="manage-column column-missing sortable <?php echo $orderby === 'missing' ? ($order === 'desc' ? 'desc' : 'asc') : 'desc'; ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'missing', 'order' => ($orderby === 'missing' && $order === 'desc' ? 'asc' : 'desc')))); ?>">
                                        <span><?php _e('Missing Alt', 'alt-text-auditor'); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="manage-column column-has-alt sortable <?php echo $orderby === 'has_alt' ? ($order === 'desc' ? 'desc' : 'asc') : 'desc'; ?>">
                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'has_alt', 'order' => ($orderby === 'has_alt' && $order === 'desc' ? 'asc' : 'desc')))); ?>">
                                        <span><?php _e('Has Alt', 'alt-text-auditor'); ?></span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th class="manage-column column-report">
                                    <?php _e('Report', 'alt-text-auditor'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scans as $scan) :
                                $user = get_userdata($scan['user_id']);
                                $user_name = $user ? $user->display_name : __('Unknown', 'alt-text-auditor');

                                // Format scan type
                                $type_labels = array(
                                    'content' => __('Published Content', 'alt-text-auditor'),
                                    'media' => __('Media Library', 'alt-text-auditor'),
                                    'drafts' => __('Draft Content', 'alt-text-auditor'),
                                    'full' => __('Full Scan', 'alt-text-auditor')
                                );
                                $type_label = isset($type_labels[$scan['scan_type']]) ? $type_labels[$scan['scan_type']] : ucfirst($scan['scan_type']);

                                // Format trigger
                                $trigger_labels = array(
                                    'manual' => __('Manual', 'alt-text-auditor'),
                                    'cron' => __('Automatic', 'alt-text-auditor')
                                );
                                $trigger_label = isset($trigger_labels[$scan['trigger']]) ? $trigger_labels[$scan['trigger']] : ucfirst($scan['trigger']);
                            ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="scan_ids[]" value="<?php echo esc_attr($scan['id']); ?>" class="scan-checkbox" />
                                    </th>
                                    <td class="column-date">
                                        <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $scan['timestamp'])); ?>
                                    </td>
                                    <td class="column-type">
                                        <span class="badge scan-type-<?php echo esc_attr($scan['scan_type']); ?>">
                                            <?php echo esc_html($type_label); ?>
                                        </span>
                                    </td>
                                    <td class="column-trigger">
                                        <span class="badge scan-trigger-<?php echo esc_attr($scan['trigger']); ?>">
                                            <?php echo esc_html($trigger_label); ?>
                                        </span>
                                    </td>
                                    <td class="column-user">
                                        <?php echo esc_html($user_name); ?>
                                    </td>
                                    <td class="column-total">
                                        <?php echo number_format($scan['stats']['total']); ?>
                                    </td>
                                    <td class="column-missing">
                                        <strong style="color: #d63638;"><?php echo number_format($scan['stats']['missing']); ?></strong>
                                    </td>
                                    <td class="column-has-alt">
                                        <strong style="color: #00a32a;"><?php echo number_format($scan['stats']['has_alt']); ?></strong>
                                    </td>
                                    <td class="column-report">
                                        <?php if (!empty($scan['report_filename'])) :
                                            $report_url = $scan_manager->get_report_url($scan['report_filename']);
                                        ?>
                                            <div class="report-actions">
                                                <button type="button" class="button button-small view-report-btn" data-scan-id="<?php echo esc_attr($scan['id']); ?>" data-report-url="<?php echo esc_url($report_url); ?>" title="<?php esc_attr_e('View report in modal', 'alt-text-auditor'); ?>">
                                                    <?php _e('View', 'alt-text-auditor'); ?>
                                                </button>
                                                <a href="<?php echo esc_url($report_url); ?>" download class="button button-small" title="<?php esc_attr_e('Download report', 'alt-text-auditor'); ?>">
                                                    <?php _e('Download', 'alt-text-auditor'); ?>
                                                </a>
                                                <a href="<?php echo esc_url($report_url); ?>" target="_blank" class="button button-small" title="<?php esc_attr_e('Open report in new tab for printing', 'alt-text-auditor'); ?>">
                                                    <?php _e('Print', 'alt-text-auditor'); ?>
                                                </a>
                                            </div>
                                        <?php else : ?>
                                            <span style="color: #666;"><?php _e('N/A', 'alt-text-auditor'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php endif; ?>
        </div>

        <!-- Modal for viewing reports -->
        <div id="report-modal" class="report-modal" style="display:none;">
            <div class="report-modal-overlay"></div>
            <div class="report-modal-content">
                <div class="report-modal-header">
                    <h2><?php _e('Scan Report', 'alt-text-auditor'); ?></h2>
                    <button type="button" class="report-modal-close">&times;</button>
                </div>
                <div class="report-modal-body">
                    <iframe id="report-iframe" src="" frameborder="0"></iframe>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the missing alt-text tab
     *
     * Displays table of images with missing alt-text
     */
    public function render_missing_alt_tab() {
        // Get pagination parameters
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;

        // Get filter parameters
        $filter_user = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : null;
        $filter_content_type = isset($_GET['filter_content_type']) ? sanitize_text_field($_GET['filter_content_type']) : null;
        $filter_post_type = isset($_GET['filter_post_type']) ? sanitize_text_field($_GET['filter_post_type']) : null;
        $filter_search = isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : null;

        // Build query args
        $query_args = array(
            'has_alt' => 0,  // Only missing alt-text
            'per_page' => $per_page,
            'page' => $current_page,
            'orderby' => 'scan_date',
            'order' => 'DESC'
        );

        // Apply filters
        if ($filter_user) {
            $query_args['user_id'] = $filter_user;
        }
        if ($filter_content_type) {
            $query_args['content_type'] = $filter_content_type;
        }
        if ($filter_post_type) {
            $query_args['post_type'] = $filter_post_type;
        }
        if ($filter_search) {
            $query_args['search'] = $filter_search;
        }

        // Get results from storage
        $results = $this->storage->get_results($query_args);

        // Get users for filter dropdown
        $users = $this->get_users_with_missing_alt();

        // Get post types for filter dropdown
        $post_types = $this->get_post_types_with_missing_alt();

        ?>
        <div class="audit-missing-alt-tab">
            <h2><?php _e('Images Missing Alt-Text', 'alt-text-auditor'); ?></h2>

            <p><?php _e('This table shows all images found across your site that are missing alt-text attributes. Use the filters below to narrow down results.', 'alt-text-auditor'); ?></p>

            <!-- Filter Form -->
            <div class="audit-filters">
                <form method="get" class="audit-filter-form" id="audit-filter-form">
                    <input type="hidden" name="page" value="alt-text-auditor-audit">
                    <input type="hidden" name="tab" value="missing">

                    <select name="filter_user" id="filter-user">
                        <option value=""><?php _e('All Users', 'alt-text-auditor'); ?></option>
                        <?php foreach ($users as $user) : ?>
                            <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($filter_user, $user->ID); ?>>
                                <?php echo esc_html($user->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="filter_content_type" id="filter-content-type">
                        <option value=""><?php _e('All Sources', 'alt-text-auditor'); ?></option>
                        <option value="post_content" <?php selected($filter_content_type, 'post_content'); ?>>
                            <?php _e('Post Content', 'alt-text-auditor'); ?>
                        </option>
                        <option value="media_library" <?php selected($filter_content_type, 'media_library'); ?>>
                            <?php _e('Media Library', 'alt-text-auditor'); ?>
                        </option>
                    </select>

                    <select name="filter_post_type" id="filter-post-type">
                        <option value=""><?php _e('All Post Types', 'alt-text-auditor'); ?></option>
                        <?php foreach ($post_types as $post_type) : ?>
                            <?php if ($post_type) : ?>
                                <option value="<?php echo esc_attr($post_type); ?>" <?php selected($filter_post_type, $post_type); ?>>
                                    <?php echo esc_html(ucfirst($post_type)); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>

                    <input type="search" name="filter_search" id="filter-search"
                           placeholder="<?php esc_attr_e('Search image source...', 'alt-text-auditor'); ?>"
                           value="<?php echo esc_attr($filter_search); ?>">

                    <button type="submit" class="button" title="<?php esc_attr_e('Apply the selected filters to narrow down results', 'alt-text-auditor'); ?>"><?php _e('Apply Filters', 'alt-text-auditor'); ?></button>

                    <?php if ($filter_user || $filter_content_type || $filter_post_type || $filter_search) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=alt-text-auditor-audit&tab=missing')); ?>"
                           class="button" id="reset-filters" title="<?php esc_attr_e('Clear all filters and show all results', 'alt-text-auditor'); ?>">
                            <?php _e('Reset Filters', 'alt-text-auditor'); ?>
                        </a>
                    <?php endif; ?>

                    <a href="#" class="button audit-export-csv" id="export-csv-btn" title="<?php esc_attr_e('Download filtered results as CSV file with formula injection protection', 'alt-text-auditor'); ?>">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export to CSV', 'alt-text-auditor'); ?>
                    </a>
                </form>
            </div>

            <?php if ($results['total'] > 0) : ?>

                <!-- Results count -->
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <span class="displaying-num">
                            <?php printf(
                                _n('%s item', '%s items', $results['total'], 'alt-text-auditor'),
                                number_format_i18n($results['total'])
                            ); ?>
                        </span>
                    </div>
                    <?php if ($results['total_pages'] > 1) : ?>
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo; Previous'),
                                'next_text' => __('Next &raquo;'),
                                'total' => $results['total_pages'],
                                'current' => $current_page,
                                'type' => 'plain'
                            ));
                            ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Results table -->
                <table class="wp-list-table widefat fixed striped audit-results-table">
                    <thead>
                        <tr>
                            <th class="column-thumbnail"><?php _e('Image', 'alt-text-auditor'); ?></th>
                            <th class="column-image-source"><?php _e('Source', 'alt-text-auditor'); ?></th>
                            <th class="column-found-in"><?php _e('Found In', 'alt-text-auditor'); ?></th>
                            <th class="column-user"><?php _e('User', 'alt-text-auditor'); ?></th>
                            <th class="column-alt-text"><?php _e('Alt Text', 'alt-text-auditor'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['results'] as $result) : ?>
                            <?php
                            $user = get_userdata($result->user_id);
                            $user_name = $user ? $user->display_name : __('Unknown User', 'alt-text-auditor');

                            $found_in = '';
                            if ($result->content_type === 'post_content' && $result->content_id) {
                                $post = get_post($result->content_id);
                                if ($post) {
                                    $found_in = '<a href="' . esc_url(get_edit_post_link($result->content_id)) . '">' .
                                                esc_html($post->post_title) . '</a> (' . esc_html($result->post_type) . ')';
                                }
                            } elseif ($result->content_type === 'media_library') {
                                $found_in = __('Media Library', 'alt-text-auditor');
                            }

                            $thumbnail_url = '';
                            if ($result->attachment_id) {
                                $thumbnail_url = wp_get_attachment_image_url($result->attachment_id, 'thumbnail');
                            }
                            ?>
                            <tr>
                                <td class="column-thumbnail">
                                    <?php if ($thumbnail_url) : ?>
                                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="" class="audit-thumbnail">
                                    <?php else : ?>
                                        <div class="audit-no-thumbnail">
                                            <span class="dashicons dashicons-format-image"></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="column-image-source">
                                    <?php if ($result->image_source) : ?>
                                        <a href="<?php echo esc_url($result->image_source); ?>" target="_blank">
                                            <?php echo esc_html(basename($result->image_source)); ?>
                                        </a>
                                    <?php else : ?>
                                        <em><?php _e('No source', 'alt-text-auditor'); ?></em>
                                    <?php endif; ?>
                                </td>
                                <td class="column-found-in">
                                    <?php echo wp_kses_post($found_in); ?>
                                </td>
                                <td class="column-user">
                                    <?php echo esc_html($user_name); ?>
                                </td>
                                <td class="column-alt-text">
                                    <?php if ($result->attachment_id) : ?>
                                        <div class="audit-alt-text-display" data-attachment-id="<?php echo esc_attr($result->attachment_id); ?>" data-result-id="<?php echo esc_attr($result->id); ?>">
                                            <span class="audit-status no-alt">
                                                <span class="dashicons dashicons-warning"></span>
                                                <?php _e('Missing', 'alt-text-auditor'); ?>
                                            </span>
                                            <button type="button" class="button button-small audit-quick-edit-trigger">
                                                <?php _e('Add Alt Text', 'alt-text-auditor'); ?>
                                            </button>
                                        </div>
                                        <div class="audit-alt-text-edit" style="display: none;">
                                            <input type="text" class="audit-quick-edit-input" placeholder="<?php esc_attr_e('Enter alt text...', 'alt-text-auditor'); ?>" maxlength="255">
                                            <button type="button" class="button button-primary audit-save-quick-edit">
                                                <?php _e('Save', 'alt-text-auditor'); ?>
                                            </button>
                                            <button type="button" class="button audit-cancel-quick-edit">
                                                <?php _e('Cancel', 'alt-text-auditor'); ?>
                                            </button>
                                            <span class="spinner"></span>
                                        </div>
                                    <?php else : ?>
                                        <span class="audit-status no-alt">
                                            <span class="dashicons dashicons-warning"></span>
                                            <?php _e('Missing', 'alt-text-auditor'); ?>
                                        </span>
                                        <br>
                                        <small><em><?php _e('No attachment ID - cannot edit from here', 'alt-text-auditor'); ?></em></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Bottom pagination -->
                <?php if ($results['total_pages'] > 1) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo; Previous'),
                                'next_text' => __('Next &raquo;'),
                                'total' => $results['total_pages'],
                                'current' => $current_page,
                                'type' => 'plain'
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else : ?>

                <!-- Empty state -->
                <div class="audit-empty-state">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <h3><?php _e('No Missing Alt-Text Found', 'alt-text-auditor'); ?></h3>
                    <p><?php _e('Great job! All scanned images have alt-text, or no scan has been run yet.', 'alt-text-auditor'); ?></p>
                    <p><a href="<?php echo esc_url(add_query_arg('tab', 'overview')); ?>" class="button button-primary">
                        <?php _e('Run a Scan', 'alt-text-auditor'); ?>
                    </a></p>
                </div>

            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render the user attribution tab
     *
     * Displays breakdown of missing alt-text by user
     */
    public function render_users_tab() {
        ?>
        <div class="audit-users-tab">
            <h2><?php _e('Missing Alt-Text by User', 'alt-text-auditor'); ?></h2>

            <p><?php _e('This table shows which users have uploaded or published images with missing alt-text. Use this to identify team members who may need training on accessibility best practices.', 'alt-text-auditor'); ?></p>

            <div id="audit-user-results">
                <div class="audit-loading">
                    <span class="spinner is-active"></span>
                    <p><?php _e('Loading user attribution data...', 'alt-text-auditor'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the content breakdown tab
     *
     * Displays breakdown by posts/pages
     */
    public function render_content_tab() {
        ?>
        <div class="audit-content-tab">
            <h2><?php _e('Content Breakdown', 'alt-text-auditor'); ?></h2>

            <div id="audit-content-results">
                <p><?php _e('Content breakdown will be available in future phases', 'alt-text-auditor'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the media library tab
     *
     * Displays unused media without alt-text
     */
    public function render_media_tab() {
        ?>
        <div class="audit-media-tab">
            <h2><?php _e('Media Library', 'alt-text-auditor'); ?></h2>

            <div id="audit-media-results">
                <p><?php _e('Media library breakdown will be available in future phases', 'alt-text-auditor'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Get navigation tabs
     *
     * @param string $current_tab Current active tab
     * @return array Array of tab data
     */
    public function get_tabs($current_tab = 'overview') {
        return array(
            'overview' => array(
                'title' => __('Overview', 'alt-text-auditor'),
                'active' => $current_tab === 'overview'
            ),
            'scans' => array(
                'title' => __('Scans', 'alt-text-auditor'),
                'active' => $current_tab === 'scans'
            ),
            'missing' => array(
                'title' => __('Missing Alt-Text', 'alt-text-auditor'),
                'active' => $current_tab === 'missing'
            ),
            'users' => array(
                'title' => __('By User', 'alt-text-auditor'),
                'active' => $current_tab === 'users'
            ),
            'content' => array(
                'title' => __('By Content', 'alt-text-auditor'),
                'active' => $current_tab === 'content'
            ),
            'media' => array(
                'title' => __('Media Library', 'alt-text-auditor'),
                'active' => $current_tab === 'media'
            )
        );
    }

    /**
     * Get list of users who have images with missing alt-text
     *
     * @return array Array of WP_User objects
     */
    private function get_users_with_missing_alt() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alttext_audit_results';

        $user_ids = $wpdb->get_col("
            SELECT DISTINCT user_id
            FROM {$table_name}
            WHERE has_alt = 0
            ORDER BY user_id ASC
        ");

        $users = array();
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Get list of post types that have images with missing alt-text
     *
     * @return array Array of post type names
     */
    private function get_post_types_with_missing_alt() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alttext_audit_results';

        $post_types = $wpdb->get_col("
            SELECT DISTINCT post_type
            FROM {$table_name}
            WHERE has_alt = 0 AND post_type IS NOT NULL
            ORDER BY post_type ASC
        ");

        return $post_types;
    }
}
