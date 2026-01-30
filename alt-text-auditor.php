<?php
/**
 * Plugin Name: Alt Text Auditor
 * Plugin URI: https://github.com/snyderb-de/alt-text-auditor
 * Description: A comprehensive WordPress plugin for managing and auditing alt-text across your entire site with inline editing and powerful audit dashboard. Supports both single-site and multisite installations.
 * Version: 2.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Bryan Snyder (snyderb-de@gmail.com)
 * License: GPL v2 or later
 * Text Domain: alt-text-auditor
 * Network: true
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('ALTTEXT_AUDITOR_VERSION')) {
    define('ALTTEXT_AUDITOR_VERSION', '2.0.0');
}
if (!defined('ALTTEXT_AUDITOR_PLUGIN_DIR')) {
    define('ALTTEXT_AUDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('ALTTEXT_AUDITOR_PLUGIN_URL')) {
    define('ALTTEXT_AUDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Main plugin class
class WP_AltText_Updater {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Load plugin text domain
        load_plugin_textdomain('alt-text-auditor', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Initialize admin functionality
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));

            // Add network admin menu if multisite
            if (is_multisite()) {
                add_action('network_admin_menu', array($this, 'add_network_admin_menu'));
            }

            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
            add_action('wp_ajax_update_alt_text', array($this, 'ajax_update_alt_text'));
            add_action('wp_ajax_alttext_audit_scan', array($this, 'ajax_audit_scan'));
            add_action('wp_ajax_alttext_audit_stats', array($this, 'ajax_audit_stats'));
            add_action('wp_ajax_alttext_audit_users', array($this, 'ajax_audit_users'));
            add_action('wp_ajax_alttext_update_audit_record', array($this, 'ajax_update_audit_record'));
            add_action('wp_ajax_alttext_audit_export', array($this, 'ajax_audit_export'));
            add_action('wp_ajax_alttext_toggle_cron', array($this, 'ajax_toggle_cron'));
            add_action('wp_ajax_alttext_generate_report', array($this, 'ajax_generate_report'));
            add_action('wp_ajax_alttext_delete_scans', array($this, 'ajax_delete_scans'));
            add_action('wp_ajax_alttext_clear_all_data', array($this, 'ajax_clear_all_data'));
            add_action('wp_ajax_alttext_save_cleanup_setting', array($this, 'ajax_save_cleanup_setting'));
            add_action('wp_ajax_alttext_cancel_scan', array($this, 'ajax_cancel_scan'));
            add_filter('manage_media_columns', array($this, 'add_alt_text_column'));
            add_action('manage_media_custom_column', array($this, 'display_alt_text_column'), 10, 2);
        }

        // Register cron hook
        add_action('alttext_audit_cron_scan', array($this, 'cron_scan_callback'));
    }
    
    /**
     * Add Alt Text column to media library
     */
    public function add_alt_text_column($columns) {
        // Insert the alt text column after the title column
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['alt_text'] = __('Alt Text', 'alt-text-auditor');
            }
        }
        return $new_columns;
    }
    
    /**
     * Display Alt Text column content
     */
    public function display_alt_text_column($column_name, $attachment_id) {
        if ($column_name === 'alt_text') {
            $current_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            $attachment = get_post($attachment_id);
            
            // Only show for images
            if (wp_attachment_is_image($attachment_id)) {
                echo '<div class="alttext-input-wrapper">';
                echo '<input type="text" class="alttext-input" data-attachment-id="' . esc_attr($attachment_id) . '" value="' . esc_attr($current_alt) . '" placeholder="' . esc_attr__('Enter alt text...', 'alt-text-auditor') . '" maxlength="255" />';
                echo '<div class="alttext-save-indicator">';
                echo '<span class="spinner"></span>';
                echo '<span class="dashicons dashicons-yes-alt success-icon"></span>';
                echo '<span class="dashicons dashicons-warning error-icon"></span>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<span class="alttext-not-image">' . __('Not an image', 'alt-text-auditor') . '</span>';
            }
        }
    }

    /**
     * Add admin menu for Alt Text Manager
     */
    public function add_admin_menu() {
        // Alt Text Manager page
        add_media_page(
            __('Alt Text Manager', 'alt-text-auditor'),
            __('Alt Text Manager', 'alt-text-auditor'),
            'upload_files',
            'alt-text-auditor-manager',
            array($this, 'render_admin_page')
        );

        // Alt-Text Audit Dashboard page
        add_media_page(
            __('Alt-Text Audit', 'alt-text-auditor'),
            __('Alt-Text Audit', 'alt-text-auditor'),
            'manage_options',
            'alt-text-auditor-audit',
            array($this, 'render_audit_dashboard')
        );
    }

    /**
     * Add network admin menu for multisite
     */
    public function add_network_admin_menu() {
        add_menu_page(
            __('Network Alt-Text Audit', 'alt-text-auditor'),
            __('Alt-Text Audit', 'alt-text-auditor'),
            'manage_network_options',
            'alt-text-auditor-network',
            array($this, 'render_network_dashboard'),
            'dashicons-images-alt2',
            30
        );

        add_submenu_page(
            'alt-text-auditor-network',
            __('Network Settings', 'alt-text-auditor'),
            __('Settings', 'alt-text-auditor'),
            'manage_network_options',
            'alt-text-auditor-network-settings',
            array($this, 'render_network_settings')
        );
    }

    /**
     * Render the admin page
     */
    public function render_admin_page() {
        if (!current_user_can('upload_files')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        include ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/admin-page.php';
    }

    /**
     * Render the audit dashboard page
     */
    public function render_audit_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Add contextual help tabs
        $this->add_audit_dashboard_help();

        // Load dashboard class
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-dashboard.php';

        $dashboard = new WP_AltText_Audit_Dashboard();
        $dashboard->render_dashboard();
    }

    /**
     * Render the network-wide audit dashboard for multisite
     */
    public function render_network_dashboard() {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Load network dashboard template
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/network-dashboard-page.php';
    }

    /**
     * Render the network settings page for multisite
     */
    public function render_network_settings() {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Load network settings template
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/network-settings-page.php';
    }

    /**
     * Add contextual help tabs to audit dashboard
     */
    private function add_audit_dashboard_help() {
        $screen = get_current_screen();

        // Overview tab help
        $screen->add_help_tab(array(
            'id' => 'audit-overview',
            'title' => __('Overview', 'alt-text-auditor'),
            'content' => '<p>' . __('<strong>Alt-Text Audit Dashboard</strong>', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('The audit dashboard helps you identify and fix images with missing alt-text across your entire WordPress site.', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('<strong>Key Features:</strong>', 'alt-text-auditor') . '</p>' .
                         '<ul>' .
                         '<li>' . __('Scan published posts and pages for images', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('Scan media library for missing alt-text', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('View statistics and track progress', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('See which users have the most missing alt-text', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('Quick-edit alt-text inline', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('Export results to CSV', 'alt-text-auditor') . '</li>' .
                         '</ul>'
        ));

        // Scanning help
        $screen->add_help_tab(array(
            'id' => 'audit-scanning',
            'title' => __('Scanning', 'alt-text-auditor'),
            'content' => '<p>' . __('<strong>How Scanning Works</strong>', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('The scanner processes your content in batches (50 items at a time) to prevent timeouts on large sites.', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('<strong>Scan Published Content:</strong> Scans all published posts and pages for embedded images and checks their alt-text attributes.', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('<strong>Scan Media Library:</strong> Checks all image attachments in your media library for alt-text meta data.', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('<strong>Automatic Scanning:</strong> Enable daily automatic scanning to keep your audit data fresh without manual intervention.', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('Results are cached for 24 hours for performance. Use the "Refresh Statistics" button to update immediately.', 'alt-text-auditor') . '</p>'
        ));

        // Filtering help
        $screen->add_help_tab(array(
            'id' => 'audit-filtering',
            'title' => __('Filtering & Export', 'alt-text-auditor'),
            'content' => '<p>' . __('<strong>Filter Results</strong>', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('Use the filter form to narrow down results:', 'alt-text-auditor') . '</p>' .
                         '<ul>' .
                         '<li>' . __('<strong>User:</strong> Filter by author/uploader', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('<strong>Content Source:</strong> Post content vs media library', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('<strong>Post Type:</strong> Posts, pages, or custom post types', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('<strong>Search:</strong> Search by image filename or existing alt-text', 'alt-text-auditor') . '</li>' .
                         '</ul>' .
                         '<p>' . __('<strong>CSV Export:</strong> Click "Export to CSV" to download all filtered results for offline analysis or reporting. The export respects your current filters.', 'alt-text-auditor') . '</p>'
        ));

        // Quick-edit help
        $screen->add_help_tab(array(
            'id' => 'audit-quick-edit',
            'title' => __('Quick Edit', 'alt-text-auditor'),
            'content' => '<p>' . __('<strong>Inline Alt-Text Editing</strong>', 'alt-text-auditor') . '</p>' .
                         '<p>' . __('Fix missing alt-text directly from the audit results:', 'alt-text-auditor') . '</p>' .
                         '<ol>' .
                         '<li>' . __('Click the "Add Alt Text" button next to any missing image', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('Enter descriptive alt-text in the input field', 'alt-text-auditor') . '</li>' .
                         '<li>' . __('Click "Save" to update both WordPress and the audit database', 'alt-text-auditor') . '</li>' .
                         '</ol>' .
                         '<p>' . __('The row will be automatically removed from the table and statistics will update after saving.', 'alt-text-auditor') . '</p>'
        ));

        // Set help sidebar
        $screen->set_help_sidebar(
            '<p><strong>' . __('For more information:', 'alt-text-auditor') . '</strong></p>' .
            '<p><a href="https://www.w3.org/WAI/tutorials/images/" target="_blank">' . __('W3C Alt Text Guide', 'alt-text-auditor') . '</a></p>' .
            '<p><a href="https://www.w3.org/TR/WCAG21/" target="_blank">' . __('WCAG 2.1 Guidelines', 'alt-text-auditor') . '</a></p>'
        );
    }

    /**
     * Get media items (images only) for admin page
     *
     * @param int $page Current page number
     * @param int $per_page Items per page
     * @param string $alt_status Filter by alt-text status: 'all', 'has_alt', 'missing_alt'
     * @param string $search Search term for filename or alt-text
     * @param string $orderby Order by field: 'date', 'title', 'alt_status'
     * @param string $order Sort order: 'ASC' or 'DESC'
     */
    public function get_media_items($page = 1, $per_page = 20, $alt_status = 'all', $search = '', $orderby = 'date', $order = 'DESC') {
        global $wpdb;

        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'order' => in_array(strtoupper($order), array('ASC', 'DESC')) ? strtoupper($order) : 'DESC'
        );

        // Handle orderby
        switch ($orderby) {
            case 'title':
                $args['orderby'] = 'title';
                break;
            case 'alt_status':
                // For sorting by alt-text status, we need a meta query
                $args['orderby'] = 'meta_value';
                $args['meta_key'] = '_wp_attachment_image_alt';
                $args['order'] = ($order === 'DESC') ? 'ASC' : 'DESC'; // Reverse because empty values sort first
                break;
            default:
                $args['orderby'] = 'date';
        }

        // Handle alt-text status filter
        if ($alt_status === 'has_alt') {
            $args['meta_query'] = array(
                array(
                    'key' => '_wp_attachment_image_alt',
                    'compare' => '!=',
                    'value' => ''
                )
            );
        } elseif ($alt_status === 'missing_alt') {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_wp_attachment_image_alt',
                    'compare' => '=',
                    'value' => ''
                )
            );
        }

        // Handle search
        if (!empty($search)) {
            // WordPress search handles post_title by default
            $args['s'] = $search;

            // Also search in alt-text meta
            add_filter('posts_where', array($this, 'alttext_search_where'), 10, 2);
            add_filter('posts_join', array($this, 'alttext_search_join'), 10, 2);
            add_filter('posts_groupby', array($this, 'alttext_search_groupby'), 10, 2);
        }

        $query = new WP_Query($args);

        // Remove search filters
        if (!empty($search)) {
            remove_filter('posts_where', array($this, 'alttext_search_where'));
            remove_filter('posts_join', array($this, 'alttext_search_join'));
            remove_filter('posts_groupby', array($this, 'alttext_search_groupby'));
        }

        return $query;
    }

    /**
     * Modify WHERE clause to search in alt-text meta
     */
    public function alttext_search_where($where, $query) {
        global $wpdb;
        $search_term = $query->get('s');
        if (!empty($search_term)) {
            $where .= $wpdb->prepare(" OR alttext_meta.meta_value LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
        }
        return $where;
    }

    /**
     * Join postmeta table for alt-text search
     */
    public function alttext_search_join($join, $query) {
        global $wpdb;
        $search_term = $query->get('s');
        if (!empty($search_term)) {
            $join .= " LEFT JOIN {$wpdb->postmeta} AS alttext_meta ON ({$wpdb->posts}.ID = alttext_meta.post_id AND alttext_meta.meta_key = '_wp_attachment_image_alt')";
        }
        return $join;
    }

    /**
     * Group by post ID to avoid duplicate results from meta join
     */
    public function alttext_search_groupby($groupby, $query) {
        global $wpdb;
        $search_term = $query->get('s');
        if (!empty($search_term)) {
            $groupby = "{$wpdb->posts}.ID";
        }
        return $groupby;
    }

    /**
     * Enqueue admin scripts and styles
     *
     * Optimized asset loading:
     * - Conditional loading based on page hook
     * - Version-based cache busting
     * - Separate assets for different pages to reduce payload
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on media library page, alt text manager page, and audit dashboard
        if ($hook !== 'upload.php' && $hook !== 'media_page_alt-text-auditor-manager' && $hook !== 'media_page_alt-text-auditor-audit') {
            return;
        }

        // Load admin JS only on media library and alt text manager pages
        if ($hook === 'upload.php' || $hook === 'media_page_alt-text-auditor-manager') {
            wp_enqueue_script(
                'wp-alttext-updater-admin',
                ALTTEXT_AUDITOR_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                ALTTEXT_AUDITOR_VERSION,
                true
            );

            wp_enqueue_style(
                'wp-alttext-updater-admin',
                ALTTEXT_AUDITOR_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                ALTTEXT_AUDITOR_VERSION
            );
        }

        // Enqueue audit dashboard assets only on audit page
        if ($hook === 'media_page_alt-text-auditor-audit') {
            wp_enqueue_script(
                'wp-alttext-updater-audit-dashboard',
                ALTTEXT_AUDITOR_PLUGIN_URL . 'assets/js/audit-dashboard.js',
                array('jquery'),
                ALTTEXT_AUDITOR_VERSION,
                true
            );

            wp_enqueue_style(
                'wp-alttext-updater-audit-dashboard',
                ALTTEXT_AUDITOR_PLUGIN_URL . 'assets/css/audit-dashboard.css',
                array(),
                ALTTEXT_AUDITOR_VERSION
            );
        }

        // Localize script for AJAX - use appropriate script handle based on page
        $script_handle = ($hook === 'media_page_alt-text-auditor-audit') ? 'wp-alttext-updater-audit-dashboard' : 'wp-alttext-updater-admin';

        wp_localize_script($script_handle, 'altTextAuditor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alttext_auditor_nonce'),
            'audit_nonce' => wp_create_nonce('alttext_audit_nonce'),
            'updating_text' => __('Updating...', 'alt-text-auditor'),
            'updated_text' => __('Updated!', 'alt-text-auditor'),
            'error_text' => __('Error updating alt text', 'alt-text-auditor'),
            'has_alt_text' => __('Has Alt Text', 'alt-text-auditor'),
            'missing_alt_text' => __('Missing Alt Text', 'alt-text-auditor'),
            'scanning_text' => __('Scanning...', 'alt-text-auditor'),
            'scan_complete_text' => __('Scan complete!', 'alt-text-auditor'),
            'scan_error_text' => __('Error during scan', 'alt-text-auditor')
        ));
    }
    
    
    /**
     * AJAX handler for updating alt text
     */
    public function ajax_update_alt_text() {
        // Check nonce for security
        check_ajax_referer('alttext_auditor_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'alt-text-auditor')), 403);
        }
        
        $attachment_id = intval($_POST['attachment_id']);
        $alt_text = sanitize_text_field($_POST['alt_text']);
        
        // Verify the attachment exists and user can edit it
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            wp_send_json_error(array(
                'message' => __('Invalid attachment', 'alt-text-auditor')
            ));
        }
        
        // Update the alt text
        $result = update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Alt text updated successfully', 'alt-text-auditor'),
                'alt_text' => $alt_text
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update alt text', 'alt-text-auditor')
            ));
        }
    }

    /**
     * AJAX handler for audit scanning
     *
     * Handles chunked scanning of content and media library.
     * Processes batches of 50 items per request to prevent timeouts.
     */
    public function ajax_audit_scan() {
        // Verify nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'alt-text-auditor')
            ));
        }

        // Check if scan has been cancelled
        if (get_transient('alttext_audit_scan_cancelled')) {
            delete_transient('alttext_audit_scan_cancelled');
            delete_transient('alttext_audit_scan_progress');
            delete_transient('alttext_audit_scan_start_time');
            wp_send_json_error(array(
                'message' => __('Scan cancelled by user.', 'alt-text-auditor'),
                'cancelled' => true
            ));
        }

        // Set execution time limit to prevent timeouts (5 minutes per batch)
        @set_time_limit(300);

        // Get parameters
        $scan_type = sanitize_text_field($_POST['scan_type']);
        $batch = isset($_POST['batch']) ? intval($_POST['batch']) : 0;
        $batch_size = 50;
        $offset = $batch * $batch_size;

        // Validate scan type
        if (!in_array($scan_type, array('content', 'media', 'drafts'))) {
            wp_send_json_error(array(
                'message' => __('Invalid scan type.', 'alt-text-auditor')
            ));
        }

        // Load scanner class
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-scanner.php';

        $storage = new WP_AltText_Audit_Storage();
        $scanner = new WP_AltText_Audit_Scanner($storage);

        // Clear results on first batch
        if ($batch === 0) {
            $scanner->clear_results();
            set_transient('alttext_audit_scan_start_time', current_time('mysql'), HOUR_IN_SECONDS);
        }

        // Perform scan based on type
        if ($scan_type === 'content') {
            $result = $scanner->scan_content($batch_size, $offset);
        } elseif ($scan_type === 'drafts') {
            $result = $scanner->scan_drafts($batch_size, $offset);
        } else {
            $result = $scanner->scan_media_library($batch_size, $offset);
        }

        // Store progress in transient
        $progress_data = array(
            'scan_type' => $scan_type,
            'batch' => $batch,
            'processed' => $result['processed'],
            'total' => $result['total'],
            'percentage' => $result['percentage'],
            'last_update' => current_time('mysql')
        );
        set_transient('alttext_audit_scan_progress', $progress_data, HOUR_IN_SECONDS);

        // If scan is complete, clear cache and update last scan time
        if (!$result['continue']) {
            delete_transient('alttext_audit_stats_cache');
            update_option('alttext_audit_last_scan', current_time('mysql'));
            delete_transient('alttext_audit_scan_start_time');

            // Generate HTML report after scan completes
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-html-report.php';
            $report = new WP_AltText_HTML_Report($storage);
            $report_file = $report->generate_report();

            // Cleanup old reports (keep last 20)
            WP_AltText_HTML_Report::cleanup_old_reports();

            // Create scan record
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-scan-manager.php';
            $scan_manager = new WP_AltText_Scan_Manager();

            $stats = $storage->get_statistics(true);
            $scan_manager->create_scan(array(
                'scan_type' => $scan_type,
                'trigger' => 'manual',
                'user_id' => get_current_user_id(),
                'stats' => $stats,
                'report_filename' => $report_file ? basename($report_file) : ''
            ));

            // Report generated successfully
            if ($report_file) {
                // Report file: $report_file
            }
        }

        // Return progress data
        wp_send_json_success(array(
            'processed' => $result['processed'],
            'total' => $result['total'],
            'percentage' => $result['percentage'],
            'continue' => $result['continue'],
            'current_batch' => $batch,
            'results_count' => $result['results_count'],
            'scan_type' => $scan_type,
            'current_item' => !empty($result['last_item']) ? sprintf(__('Scanning: %s', 'alt-text-auditor'), $result['last_item']) : '',
            'message' => sprintf(
                __('Processed %d of %d items (%d%%)', 'alt-text-auditor'),
                $result['processed'],
                $result['total'],
                $result['percentage']
            )
        ));
    }

    /**
     * AJAX handler for getting audit statistics
     *
     * Returns overall statistics about images and alt-text.
     */
    public function ajax_audit_stats() {
        // Verify nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'alt-text-auditor')
            ));
        }

        // Load storage class
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';

        $storage = new WP_AltText_Audit_Storage();

        // Get force_refresh parameter
        $force_refresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] === 'true';

        // Get statistics
        $statistics = $storage->get_statistics($force_refresh);

        // Return statistics
        wp_send_json_success($statistics);
    }

    /**
     * AJAX handler for getting user attribution data
     *
     * Returns counts of missing alt-text by user.
     */
    public function ajax_audit_users() {
        // Verify nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'alt-text-auditor')
            ));
        }

        // Load classes
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-user-attribution.php';

        $storage = new WP_AltText_Audit_Storage();
        $user_attribution = new WP_AltText_User_Attribution($storage);

        // Get parameters
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 0;

        // Get user counts
        if ($limit > 0) {
            $user_counts = $user_attribution->get_top_offenders($limit);
        } else {
            $user_counts = $user_attribution->get_user_counts();
        }

        // Get summary
        $summary = $user_attribution->get_summary();

        // Return data
        wp_send_json_success(array(
            'users' => $user_counts,
            'summary' => $summary
        ));
    }

    /**
     * AJAX handler for updating audit record after alt-text is saved
     *
     * Updates the audit database to mark an image as having alt-text
     * and clears the statistics cache.
     */
    public function ajax_update_audit_record() {
        // Verify nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'alt-text-auditor')
            ));
        }

        // Get parameters
        $result_id = isset($_POST['result_id']) ? intval($_POST['result_id']) : 0;
        $alt_text = isset($_POST['alt_text']) ? sanitize_text_field($_POST['alt_text']) : '';

        if (!$result_id) {
            wp_send_json_error(array(
                'message' => __('Invalid result ID.', 'alt-text-auditor')
            ));
        }

        // Validate alt-text length (WordPress standard is 255 characters)
        if (strlen($alt_text) > 255) {
            wp_send_json_error(array(
                'message' => __('Alt-text must be 255 characters or less.', 'alt-text-auditor')
            ));
        }

        // Load storage class
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
        $storage = new WP_AltText_Audit_Storage();

        // Get the audit record to check for attachment_id
        global $wpdb;
        $table_name = $storage->get_table_name();

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $result_id
        ));

        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Result not found.', 'alt-text-auditor')
            ));
        }

        // If this result has an attachment_id, update the media library too
        $saved_to_media = false;
        if ($result->attachment_id) {
            $attachment = get_post($result->attachment_id);
            if ($attachment && $attachment->post_type === 'attachment') {
                update_post_meta($result->attachment_id, '_wp_attachment_image_alt', $alt_text);
                $saved_to_media = true;
            }
        }

        // If this is a post_content type, update the HTML in the post content
        $saved_to_post_content = false;
        if ($result->content_type === 'post_content' && $result->content_id) {
            $post = get_post($result->content_id);

            if (!$post) {
                wp_send_json_error(array(
                    'message' => __('Post not found.', 'alt-text-auditor')
                ));
            }

            // SECURITY: Check if user has permission to edit this specific post
            if (!current_user_can('edit_post', $result->content_id)) {
                wp_send_json_error(array(
                    'message' => __('You do not have permission to edit this post.', 'alt-text-auditor')
                ));
            }

            if ($post && !empty($post->post_content)) {
                // Parse the post content HTML
                libxml_use_internal_errors(true);
                $dom = new DOMDocument();
                $dom->loadHTML(mb_convert_encoding($post->post_content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                $img_tags = $dom->getElementsByTagName('img');
                $found_and_updated = false;

                // Find the matching img tag by src
                foreach ($img_tags as $img) {
                    $src = $img->getAttribute('src');

                    // Match by full URL or by filename
                    if ($src === $result->image_source ||
                        basename(parse_url($src, PHP_URL_PATH)) === basename(parse_url($result->image_source, PHP_URL_PATH))) {

                        // SECURITY: Escape alt-text to prevent HTML injection
                        // DOMDocument will handle the HTML entities properly
                        $safe_alt_text = esc_attr($alt_text);

                        // Update the alt attribute
                        $img->setAttribute('alt', $safe_alt_text);
                        $found_and_updated = true;
                        break;
                    }
                }

                if ($found_and_updated) {
                    // Save the updated HTML back to the post
                    $updated_html = $dom->saveHTML();

                    // Remove the HTML wrapper tags that DOMDocument adds
                    $updated_html = preg_replace('/^<!DOCTYPE.+?>/', '', $updated_html);
                    $updated_html = str_replace(['<html>', '</html>', '<body>', '</body>'], '', $updated_html);
                    $updated_html = trim($updated_html);

                    $post_update_result = wp_update_post(array(
                        'ID' => $result->content_id,
                        'post_content' => $updated_html
                    ), true);

                    if (!is_wp_error($post_update_result)) {
                        $saved_to_post_content = true;
                    }
                }

                libxml_clear_errors();
            }
        }

        // Update the audit record
        $updated = $wpdb->update(
            $table_name,
            array(
                'has_alt' => 1,
                'alt_text' => $alt_text,
                'last_updated' => current_time('mysql')
            ),
            array('id' => $result_id),
            array('%d', '%s', '%s'),
            array('%d')
        );

        if ($updated !== false) {
            // Clear statistics cache
            delete_transient('alttext_audit_stats_cache');

            wp_send_json_success(array(
                'message' => __('Alt-text saved successfully.', 'alt-text-auditor'),
                'updated' => $updated,
                'saved_to_media' => $saved_to_media,
                'saved_to_post_content' => $saved_to_post_content,
                'attachment_id' => $result->attachment_id,
                'content_type' => $result->content_type
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update audit record.', 'alt-text-auditor')
            ));
        }
    }

    /**
     * AJAX handler for exporting audit results to CSV
     *
     * Generates a CSV file with all missing alt-text results,
     * respecting current filter parameters.
     */
    public function ajax_audit_export() {
        // Verify nonce - use GET parameter since this is a direct download link
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'alttext_audit_nonce')) {
            wp_die(__('Security check failed', 'alt-text-auditor'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'alt-text-auditor'));
        }

        // Check referrer to prevent direct URL access from external sites
        $referer = wp_get_referer();
        if (!$referer || strpos($referer, admin_url()) !== 0) {
            wp_die(__('Invalid request. Please export from the audit dashboard.', 'alt-text-auditor'));
        }

        // Rate limiting: prevent abuse by limiting exports to once per minute per user
        $rate_limit_key = 'alttext_export_' . get_current_user_id();
        if (get_transient($rate_limit_key)) {
            wp_die(__('Please wait before exporting again. Exports are limited to once per minute.', 'alt-text-auditor'));
        }
        set_transient($rate_limit_key, 1, 60); // 60 second cooldown

        // Load storage class
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
        $storage = new WP_AltText_Audit_Storage();

        // Get and validate filter parameters from GET
        $filter_user = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : null;
        $filter_content_type = isset($_GET['filter_content_type']) ? sanitize_text_field($_GET['filter_content_type']) : null;
        $filter_post_type = isset($_GET['filter_post_type']) ? sanitize_text_field($_GET['filter_post_type']) : null;
        $filter_search = isset($_GET['filter_search']) ? sanitize_text_field($_GET['filter_search']) : null;

        // Validate content_type against whitelist
        $allowed_content_types = array('post_content', 'media_library');
        if ($filter_content_type && !in_array($filter_content_type, $allowed_content_types, true)) {
            $filter_content_type = null;
        }

        // Validate post_type against registered post types
        if ($filter_post_type) {
            $registered_post_types = get_post_types();
            if (!in_array($filter_post_type, $registered_post_types, true)) {
                $filter_post_type = null;
            }
        }

        // Validate user_id exists if specified
        if ($filter_user) {
            $user = get_userdata($filter_user);
            if (!$user) {
                $filter_user = null;
            }
        }

        // Build query args
        $query_args = array(
            'has_alt' => 0,
            'per_page' => -1, // Get all results
            'page' => 1
        );

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

        // Get results
        $results = $storage->get_results($query_args);

        // Set headers for CSV download
        $filename = 'missing-alt-text-' . date('Y-m-d-H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write CSV header
        fputcsv($output, array(
            'Image Source',
            'Found In',
            'Post Type',
            'Content Type',
            'User',
            'Scan Date'
        ));

        // Write data rows
        foreach ($results['results'] as $result) {
            // Get user info
            $user = get_userdata($result->user_id);
            $user_name = $user ? $user->display_name : 'Unknown User';

            // Get "Found In" info
            $found_in = '';
            if ($result->content_type === 'post_content' && $result->content_id) {
                $post = get_post($result->content_id);
                if ($post) {
                    $found_in = $post->post_title;
                }
            } elseif ($result->content_type === 'media_library') {
                $found_in = 'Media Library';
            }

            // Escape values to prevent CSV formula injection
            $image_source = $this->escape_csv_value($result->image_source);
            $found_in = $this->escape_csv_value($found_in);
            $post_type = $this->escape_csv_value($result->post_type);
            $content_type = $this->escape_csv_value($result->content_type);
            $user_name = $this->escape_csv_value($user_name);

            // Write row
            fputcsv($output, array(
                $image_source,
                $found_in,
                $post_type ? ucfirst($post_type) : '',
                $content_type === 'post_content' ? 'Post Content' : 'Media Library',
                $user_name,
                $result->scan_date
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Escape CSV value to prevent formula injection
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    private function escape_csv_value($value) {
        if (empty($value)) {
            return $value;
        }

        // Check if value starts with potentially dangerous characters
        $dangerous_chars = array('=', '+', '-', '@', "\t", "\r");
        $first_char = substr($value, 0, 1);

        if (in_array($first_char, $dangerous_chars)) {
            // Prepend single quote to prevent Excel from interpreting as formula
            $value = "'" . $value;
        }

        return $value;
    }

    /**
     * AJAX handler for toggling cron scanning on/off
     */
    public function ajax_toggle_cron() {
        // Verify nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'alt-text-auditor')
            ));
        }

        // Get enabled parameter
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === 'true';

        // Update option
        update_option('alttext_audit_cron_enabled', $enabled ? 1 : 0);

        if ($enabled) {
            // Schedule cron event if not already scheduled
            if (!wp_next_scheduled('alttext_audit_cron_scan')) {
                wp_schedule_event(time(), 'daily', 'alttext_audit_cron_scan');
            }

            wp_send_json_success(array(
                'message' => __('Automatic daily scanning enabled.', 'alt-text-auditor'),
                'enabled' => true
            ));
        } else {
            // Unschedule cron event
            $timestamp = wp_next_scheduled('alttext_audit_cron_scan');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'alttext_audit_cron_scan');
            }

            wp_send_json_success(array(
                'message' => __('Automatic daily scanning disabled.', 'alt-text-auditor'),
                'enabled' => false
            ));
        }
    }

    /**
     * AJAX handler for generating HTML report on demand
     */
    public function ajax_generate_report() {
        // Check nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'alt-text-auditor')), 403);
        }

        // Load required classes
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-html-report.php';

        $storage = new WP_AltText_Audit_Storage();
        $report = new WP_AltText_HTML_Report($storage);

        // Generate report
        $report_file = $report->generate_report();

        if ($report_file) {
            $filename = basename($report_file);
            $report_url = WP_AltText_HTML_Report::get_report_url($filename);

            // Create scan record for on-demand report
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-scan-manager.php';
            $scan_manager = new WP_AltText_Scan_Manager();

            $stats = $storage->get_statistics(true);
            $scan_manager->create_scan(array(
                'scan_type' => 'full',
                'trigger' => 'manual',
                'user_id' => get_current_user_id(),
                'stats' => $stats,
                'report_filename' => $filename
            ));

            wp_send_json_success(array(
                'message' => __('Report generated successfully!', 'alt-text-auditor'),
                'report_url' => $report_url,
                'filename' => $filename
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to generate report.', 'alt-text-auditor')
            ));
        }
    }

    /**
     * AJAX handler for deleting scans
     */
    public function ajax_delete_scans() {
        // Check nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'alt-text-auditor')), 403);
        }

        // Get scan IDs
        $scan_ids = isset($_POST['scan_ids']) ? array_map('sanitize_text_field', $_POST['scan_ids']) : array();

        if (empty($scan_ids)) {
            wp_send_json_error(array('message' => __('No scans selected.', 'alt-text-auditor')));
        }

        // Load scan manager
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-scan-manager.php';
        $scan_manager = new WP_AltText_Scan_Manager();

        // Delete scans
        $deleted = $scan_manager->delete_scans($scan_ids);

        if ($deleted > 0) {
            wp_send_json_success(array(
                'message' => sprintf(__('Deleted %d scan(s) successfully.', 'alt-text-auditor'), $deleted),
                'deleted' => $deleted
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete scans.', 'alt-text-auditor')));
        }
    }

    /**
     * AJAX handler for clearing all scan data
     */
    public function ajax_clear_all_data() {
        // Check nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'alt-text-auditor')), 403);
        }

        // Load scan manager
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-scan-manager.php';
        $scan_manager = new WP_AltText_Scan_Manager();

        // Get all scans
        $scans = $scan_manager->get_scans();
        $scan_ids = array_column($scans, 'id');

        // Delete all scans and reports
        $deleted = 0;
        if (!empty($scan_ids)) {
            $deleted = $scan_manager->delete_scans($scan_ids);
        }

        // Also delete any orphaned report files
        $upload_dir = wp_upload_dir();
        $reports_dir = trailingslashit($upload_dir['basedir']) . 'alttext-reports';
        if (file_exists($reports_dir)) {
            $files = glob($reports_dir . '/alttext-report-*.html');
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Successfully cleared all scan data (%d scans deleted).', 'alt-text-auditor'), $deleted)
        ));
    }

    /**
     * AJAX handler for saving auto-cleanup setting
     */
    public function ajax_save_cleanup_setting() {
        // Check nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'alt-text-auditor')), 403);
        }

        // Get and validate days setting
        $days = isset($_POST['days']) ? sanitize_text_field($_POST['days']) : 'never';
        $valid_options = array('never', '30', '60', '90', '120', '365');

        if (!in_array($days, $valid_options)) {
            wp_send_json_error(array('message' => __('Invalid cleanup setting.', 'alt-text-auditor')));
        }

        // Save setting
        update_option('alttext_auto_cleanup_days', $days);

        $message = $days === 'never'
            ? __('Auto-cleanup disabled. Scans will be kept indefinitely.', 'alt-text-auditor')
            : sprintf(__('Auto-cleanup enabled. Scans older than %s days will be automatically deleted.', 'alt-text-auditor'), $days);

        wp_send_json_success(array('message' => $message));
    }

    /**
     * AJAX handler for cancelling an in-progress scan
     */
    public function ajax_cancel_scan() {
        // Check nonce
        check_ajax_referer('alttext_audit_nonce', 'nonce');

        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'alt-text-auditor')), 403);
        }

        // Set cancellation flag
        set_transient('alttext_audit_scan_cancelled', true, MINUTE_IN_SECONDS);

        wp_send_json_success(array(
            'message' => __('Scan cancellation requested. The scan will stop after the current batch completes.', 'alt-text-auditor')
        ));
    }

    /**
     * Cron callback for automatic scanning
     *
     * Runs a full scan of both content and media library
     */
    public function cron_scan_callback() {
        // Check if multisite and if network-wide scanning is enabled
        if (is_multisite()) {
            $this->cron_scan_multisite();
        } else {
            $this->cron_scan_single_site();
        }
    }

    /**
     * Cron callback for single-site scanning
     */
    private function cron_scan_single_site() {
        // Load required classes
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-scanner.php';

        $storage = new WP_AltText_Audit_Storage();
        $scanner = new WP_AltText_Audit_Scanner($storage);

        // Clear old results
        $scanner->clear_results();

        // Set scan start time
        set_transient('alttext_audit_scan_start_time', current_time('mysql'), HOUR_IN_SECONDS);

        // Scan all content (posts and pages)
        $content_offset = 0;
        $content_batch_size = 50;
        do {
            $content_result = $scanner->scan_content($content_batch_size, $content_offset);
            $content_offset += $content_batch_size;
        } while ($content_result['continue']);

        // Scan all media library
        $media_offset = 0;
        $media_batch_size = 50;
        do {
            $media_result = $scanner->scan_media_library($media_batch_size, $media_offset);
            $media_offset += $media_batch_size;
        } while ($media_result['continue']);

        // Clear cache and update last scan time
        delete_transient('alttext_audit_stats_cache');
        update_option('alttext_audit_last_scan', current_time('mysql'));
        delete_transient('alttext_audit_scan_start_time');

        // Generate HTML report
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-html-report.php';
        $report = new WP_AltText_HTML_Report($storage);
        $report_file = $report->generate_report();

        // Cleanup old reports (keep last 20)
        WP_AltText_HTML_Report::cleanup_old_reports();

        // Create scan record
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-scan-manager.php';
        $scan_manager = new WP_AltText_Scan_Manager();

        $stats = $storage->get_statistics(true);
        $scan_manager->create_scan(array(
            'scan_type' => 'full',
            'trigger' => 'cron',
            'user_id' => 0, // System user for cron
            'stats' => $stats,
            'report_filename' => $report_file ? basename($report_file) : ''
        ));

        // Cleanup old scans (hard limit of 50)
        $scan_manager->cleanup_old_scans();

        // Auto-cleanup based on age setting
        $scan_manager->auto_cleanup_by_age();

        // Scan completed
    }

    /**
     * Cron callback for multisite network-wide scanning
     *
     * Scans multiple sites in batches to prevent performance issues on large networks.
     */
    private function cron_scan_multisite() {
        // Get batch size setting (default to 10 sites per cron run)
        $batch_size = get_site_option('alttext_cron_batch_size', 10);

        // Load required classes
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-scanner.php';

        // Get all sites (limit to 10000 for safety)
        $sites = get_sites(array('number' => 10000));

        // Get last processed site index (for rotating through sites)
        $last_index = get_site_option('alttext_cron_last_site_index', 0);
        $sites_to_scan = array();

        // Select next batch of sites to scan
        for ($i = 0; $i < $batch_size && $i < count($sites); $i++) {
            $site_index = ($last_index + $i) % count($sites);
            $sites_to_scan[] = $sites[$site_index];
        }

        $scanned_count = 0;
        $total_sites = count($sites_to_scan);

        foreach ($sites_to_scan as $site) {
            switch_to_blog($site->blog_id);

            // Verify capability in switched blog context
            // Since this is a cron job, we run with system privileges
            // but we still check if the site has the plugin active
            if (!get_option('alttext_audit_cron_enabled')) {
                restore_current_blog();
                continue;
            }

            $storage = new WP_AltText_Audit_Storage();
            $scanner = new WP_AltText_Audit_Scanner($storage);

            // Clear old results for this site
            $scanner->clear_results();

            // Scan content (posts and pages) for this site
            $content_offset = 0;
            $content_batch_size = 50;
            do {
                $content_result = $scanner->scan_content($content_batch_size, $content_offset);
                $content_offset += $content_batch_size;
            } while ($content_result['continue']);

            // Scan media library for this site
            $media_offset = 0;
            $media_batch_size = 50;
            do {
                $media_result = $scanner->scan_media_library($media_batch_size, $media_offset);
                $media_offset += $media_batch_size;
            } while ($media_result['continue']);

            // Clear cache and update last scan time
            delete_transient('alttext_audit_stats_cache');
            update_option('alttext_audit_last_scan', current_time('mysql'));

            // Generate HTML report for this site
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-html-report.php';
            $report = new WP_AltText_HTML_Report($storage);
            $report_file = $report->generate_report();

            // Cleanup old reports (keep last 20)
            WP_AltText_HTML_Report::cleanup_old_reports();

            // Create scan record
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-scan-manager.php';
            $scan_manager = new WP_AltText_Scan_Manager();

            $stats = $storage->get_statistics(true);
            $scan_manager->create_scan(array(
                'scan_type' => 'full',
                'trigger' => 'cron',
                'user_id' => 0, // System user for cron
                'stats' => $stats,
                'report_filename' => $report_file ? basename($report_file) : ''
            ));

            // Cleanup old scans
            $scan_manager->cleanup_old_scans();
            $scan_manager->auto_cleanup_by_age();

            $scanned_count++;

            // Site scan completed

            restore_current_blog();
        }

        // Update the last processed site index
        $new_index = ($last_index + $batch_size) % count($sites);
        update_site_option('alttext_cron_last_site_index', $new_index);

        // Batch scan completed
    }

    /**
     * Plugin activation hook
     *
     * @param bool $network_wide Whether the plugin is being network-activated
     */
    public static function activate($network_wide = false) {
        // Load storage class if needed for table creation
        require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';

        if (is_multisite() && $network_wide) {
            // Network activation - create tables for all sites
            global $wpdb;

            // Get all blog IDs
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::activate_single_site();
                restore_current_blog();
            }
        } else {
            // Single site activation
            self::activate_single_site();
        }
    }

    /**
     * Activate plugin for a single site
     */
    private static function activate_single_site() {
        if (class_exists('WP_AltText_Audit_Storage')) {
            $storage = new WP_AltText_Audit_Storage();
            $storage->create_tables();
        }

        // Set default options
        add_option('alttext_audit_cron_enabled', 0);
        add_option('alttext_audit_version', ALTTEXT_AUDITOR_VERSION);
    }

    /**
     * Plugin deactivation hook
     *
     * @param bool $network_wide Whether the plugin is being network-deactivated
     */
    public static function deactivate($network_wide = false) {
        if (is_multisite() && $network_wide) {
            // Network deactivation - clear for all sites
            global $wpdb;

            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                self::deactivate_single_site();
                restore_current_blog();
            }
        } else {
            // Single site deactivation
            self::deactivate_single_site();
        }
    }

    /**
     * Deactivate plugin for a single site
     */
    private static function deactivate_single_site() {
        // Clear scheduled cron events
        wp_clear_scheduled_hook('alttext_audit_cron_scan');

        // Clear transients
        delete_transient('alttext_audit_stats_cache');
        delete_transient('alttext_audit_scan_progress');
    }

    /**
     * Handle new site creation in multisite
     *
     * @param int $blog_id Blog ID of the new site
     */
    public static function on_new_blog($blog_id) {
        if (is_plugin_active_for_network(plugin_basename(__FILE__))) {
            switch_to_blog($blog_id);
            self::activate_single_site();
            restore_current_blog();
        }
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('WP_AltText_Updater', 'activate'));
register_deactivation_hook(__FILE__, array('WP_AltText_Updater', 'deactivate'));

// Handle new sites in multisite
add_action('wpmu_new_blog', array('WP_AltText_Updater', 'on_new_blog'));

// Initialize the plugin
new WP_AltText_Updater();
