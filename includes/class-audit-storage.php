<?php
/**
 * WP Alt Text Audit Storage
 *
 * Handles database operations for the audit dashboard including
 * table creation, CRUD operations, and result queries.
 *
 * @package WP_AltText_Updater
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_AltText_Audit_Storage {

    /**
     * Database table name (without prefix)
     *
     * @var string
     */
    private $table_name = 'alttext_audit_results';

    /**
     * Constructor
     */
    public function __construct() {
        // Constructor reserved for future initialization
    }

    /**
     * Get the full table name with WordPress prefix
     *
     * @return string Full table name
     */
    public function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . $this->table_name;
    }

    /**
     * Create audit results table on plugin activation
     *
     * Creates the custom database table for storing audit scan results
     * with proper indexes for performance.
     *
     * @return bool True on success, false on failure
     */
    public function create_tables() {
        global $wpdb;

        $table_name = $this->get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        // Define table schema
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            content_type varchar(20) NOT NULL,
            content_id bigint(20) UNSIGNED NOT NULL,
            image_source varchar(255) DEFAULT NULL,
            attachment_id bigint(20) UNSIGNED DEFAULT NULL,
            has_alt tinyint(1) NOT NULL DEFAULT 0,
            alt_text text DEFAULT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            post_type varchar(20) DEFAULT NULL,
            scan_date datetime NOT NULL,
            last_updated datetime NOT NULL,
            PRIMARY KEY (id),
            KEY content_type_id (content_type, content_id),
            KEY user_id (user_id),
            KEY has_alt (has_alt),
            KEY attachment_id (attachment_id),
            KEY scan_date (scan_date),
            KEY has_alt_scan_date (has_alt, scan_date),
            KEY has_alt_user_id (has_alt, user_id),
            KEY content_type_has_alt (content_type, has_alt)
        ) $charset_collate;";

        // Use dbDelta for safe table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Check if table was created successfully
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
            return true;
        }

        return false;
    }

    /**
     * Drop the audit results table (used during uninstall)
     *
     * @return bool True on success, false on failure
     */
    public function drop_tables() {
        global $wpdb;

        $table_name = $this->get_table_name();

        // Validate table name format for security (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
            return false;
        }

        // Table names cannot be parameterized in MySQL, but we validate above
        // and use esc_sql for additional safety even though table name is internally controlled
        $result = $wpdb->query("DROP TABLE IF EXISTS " . esc_sql($table_name));

        return $result !== false;
    }

    /**
     * Check if the audit table exists
     *
     * @return bool True if table exists, false otherwise
     */
    public function table_exists() {
        global $wpdb;

        $table_name = $this->get_table_name();

        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    }

    /**
     * Save a single audit result to the database
     *
     * @param array $data Result data array with keys matching table columns
     * @return int|false Insert ID on success, false on failure
     */
    public function save_result($data) {
        global $wpdb;

        $table_name = $this->get_table_name();

        $result = $wpdb->insert(
            $table_name,
            $data,
            array(
                '%s', // content_type
                '%d', // content_id
                '%s', // image_source
                '%d', // attachment_id
                '%d', // has_alt
                '%s', // alt_text
                '%d', // user_id
                '%s', // post_type
                '%s', // scan_date
                '%s'  // last_updated
            )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Bulk insert multiple audit results for better performance
     *
     * @param array $results Array of result data arrays
     * @return int|false Number of rows inserted, false on failure
     */
    public function bulk_insert($results) {
        global $wpdb;

        if (empty($results)) {
            return 0;
        }

        $table_name = $this->get_table_name();
        $values = array();
        $placeholders = array();

        // Build values array and placeholders
        foreach ($results as $result) {
            $placeholders[] = "(%s, %d, %s, %d, %d, %s, %d, %s, %s, %s)";

            $values[] = $result['content_type'];
            $values[] = $result['content_id'];
            $values[] = $result['image_source'];
            $values[] = $result['attachment_id'];
            $values[] = $result['has_alt'];
            $values[] = $result['alt_text'];
            $values[] = $result['user_id'];
            $values[] = $result['post_type'];
            $values[] = $result['scan_date'];
            $values[] = $result['last_updated'];
        }

        // Build and execute the bulk insert query
        $query = "INSERT INTO $table_name
                  (content_type, content_id, image_source, attachment_id, has_alt,
                   alt_text, user_id, post_type, scan_date, last_updated)
                  VALUES " . implode(', ', $placeholders);

        $prepared_query = $wpdb->prepare($query, $values);
        $result = $wpdb->query($prepared_query);

        return $result;
    }

    /**
     * Clear all results from the audit table
     *
     * @return bool True on success, false on failure
     */
    public function clear_all_results() {
        global $wpdb;

        $table_name = $this->get_table_name();

        // Validate table name format for security (alphanumeric and underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
            return false;
        }

        // Table names cannot be parameterized in MySQL, but we validate above
        // and use esc_sql for additional safety
        $result = $wpdb->query("TRUNCATE TABLE " . esc_sql($table_name));

        // Clear cached statistics
        delete_transient('alttext_audit_stats_cache');

        return $result !== false;
    }

    /**
     * Get overall audit statistics
     *
     * Returns aggregated statistics about images and alt-text.
     * Results are cached for 24 hours.
     *
     * @param bool $force_refresh Force fresh calculation, bypass cache
     * @return array Statistics array
     */
    public function get_statistics($force_refresh = false) {
        // Try to get cached statistics
        if (!$force_refresh) {
            $cached_stats = get_transient('alttext_audit_stats_cache');
            if ($cached_stats !== false) {
                return $cached_stats;
            }
        }

        global $wpdb;
        $table_name = $this->get_table_name();

        // Calculate statistics
        $stats = $wpdb->get_row("
            SELECT
                COUNT(*) as total_images,
                SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) as missing_alt,
                SUM(CASE WHEN has_alt = 1 THEN 1 ELSE 0 END) as has_alt,
                ROUND(SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as missing_percentage,
                ROUND(SUM(CASE WHEN has_alt = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as has_percentage
            FROM {$table_name}
        ");

        // Get breakdown by content type
        $by_source = $wpdb->get_results("
            SELECT
                content_type,
                COUNT(*) as total,
                SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) as missing
            FROM {$table_name}
            GROUP BY content_type
        ");

        // Format by_source data
        $by_source_formatted = array();
        foreach ($by_source as $source) {
            $by_source_formatted[$source->content_type] = array(
                'total' => intval($source->total),
                'missing' => intval($source->missing)
            );
        }

        // Get last scan date
        $last_scan = get_option('alttext_audit_last_scan', null);

        // Build statistics array
        $statistics = array(
            'total_images' => intval($stats->total_images),
            'missing_alt' => intval($stats->missing_alt),
            'has_alt' => intval($stats->has_alt),
            'missing_percentage' => floatval($stats->missing_percentage),
            'has_percentage' => floatval($stats->has_percentage),
            'by_source' => $by_source_formatted,
            'last_scan_date' => $last_scan,
            'last_scan_human' => $last_scan ? human_time_diff(strtotime($last_scan), time()) . ' ago' : 'Never'
        );

        // Cache for 24 hours
        set_transient('alttext_audit_stats_cache', $statistics, DAY_IN_SECONDS);

        return $statistics;
    }

    /**
     * Get query results with filters and pagination
     *
     * @param array $args Query arguments (filters, pagination, sorting)
     * @return array Results with pagination info
     */
    public function get_results($args = array()) {
        global $wpdb;
        $table_name = $this->get_table_name();

        // Default arguments
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'scan_date',
            'order' => 'DESC',
            'has_alt' => null,
            'user_id' => null,
            'content_type' => null,
            'post_type' => null,
            'search' => null
        );

        $args = wp_parse_args($args, $defaults);

        // Build WHERE clause
        $where_clauses = array('1=1');
        $prepare_args = array();

        if ($args['has_alt'] !== null) {
            $where_clauses[] = 'has_alt = %d';
            $prepare_args[] = intval($args['has_alt']);
        }

        if ($args['user_id'] !== null) {
            $where_clauses[] = 'user_id = %d';
            $prepare_args[] = intval($args['user_id']);
        }

        if ($args['content_type'] !== null) {
            // Validate content_type against whitelist
            $allowed_content_types = array('post_content', 'media_library');
            if (in_array($args['content_type'], $allowed_content_types, true)) {
                $where_clauses[] = 'content_type = %s';
                $prepare_args[] = $args['content_type'];
            }
        }

        if ($args['post_type'] !== null) {
            // Validate post_type against registered post types
            $registered_post_types = get_post_types();
            if (in_array($args['post_type'], $registered_post_types, true)) {
                $where_clauses[] = 'post_type = %s';
                $prepare_args[] = $args['post_type'];
            }
        }

        if (!empty($args['search'])) {
            $where_clauses[] = '(image_source LIKE %s OR alt_text LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $prepare_args[] = $search_term;
            $prepare_args[] = $search_term;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Calculate offset
        $offset = ($args['page'] - 1) * $args['per_page'];

        // Build ORDER BY
        $allowed_orderby = array('scan_date', 'user_id', 'has_alt', 'content_type');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'scan_date';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
        if (!empty($prepare_args)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $prepare_args));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Get results
        $query = "SELECT * FROM {$table_name}
                  WHERE {$where_sql}
                  ORDER BY {$orderby} {$order}
                  LIMIT %d OFFSET %d";

        $query_args = array_merge($prepare_args, array($args['per_page'], $offset));
        $results = $wpdb->get_results($wpdb->prepare($query, $query_args));

        return array(
            'results' => $results,
            'total' => intval($total_items),
            'per_page' => intval($args['per_page']),
            'current_page' => intval($args['page']),
            'total_pages' => ceil($total_items / $args['per_page'])
        );
    }

    /**
     * Invalidate statistics cache
     *
     * Call this when data changes to force recalculation
     */
    public function invalidate_cache() {
        delete_transient('alttext_audit_stats_cache');
        delete_transient('alttext_user_counts_cache');
    }
}

