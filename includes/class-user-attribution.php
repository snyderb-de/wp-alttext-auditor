<?php
/**
 * WP Alt Text User Attribution
 *
 * Handles user attribution and statistics for missing alt-text.
 * Tracks which users have the most images with missing alt-text.
 *
 * @package WP_AltText_Updater
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_AltText_User_Attribution {

    /**
     * Storage instance
     *
     * @var WP_AltText_Audit_Storage
     */
    private $storage;

    /**
     * Constructor
     *
     * @param WP_AltText_Audit_Storage $storage Storage instance
     */
    public function __construct($storage = null) {
        if ($storage === null) {
            require_once WP_ALTTEXT_UPDATER_PLUGIN_DIR . 'includes/class-audit-storage.php';
            $this->storage = new WP_AltText_Audit_Storage();
        } else {
            $this->storage = $storage;
        }
    }

    /**
     * Get missing alt-text counts by user
     *
     * Returns array of users with their missing alt-text counts,
     * sorted by missing count descending.
     * Results are cached for 1 hour for performance.
     *
     * @param bool $force_refresh Force fresh calculation, bypass cache
     * @return array Array of user data with counts
     */
    public function get_user_counts($force_refresh = false) {
        // Try to get cached results
        if (!$force_refresh) {
            $cached_results = get_transient('alttext_user_counts_cache');
            if ($cached_results !== false) {
                return $cached_results;
            }
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'alttext_audit_results';

        // Aggregate query to get counts per user
        $results = $wpdb->get_results("
            SELECT
                user_id,
                COUNT(*) as total_images,
                SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) as missing_alt,
                SUM(CASE WHEN has_alt = 1 THEN 1 ELSE 0 END) as has_alt,
                ROUND(SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as missing_percentage
            FROM {$table_name}
            GROUP BY user_id
            HAVING missing_alt > 0
            ORDER BY missing_alt DESC
        ");

        // Batch fetch all user data to avoid N+1 queries
        $user_ids = array_map(function($result) {
            return $result->user_id;
        }, $results);

        $users_data = $this->batch_get_users($user_ids);

        // Format user data
        $formatted_results = array();
        foreach ($results as $result) {
            $formatted_results[] = $this->format_user_data_with_cache($result->user_id, $result, $users_data);
        }

        // Cache for 1 hour
        set_transient('alttext_user_counts_cache', $formatted_results, HOUR_IN_SECONDS);

        return $formatted_results;
    }

    /**
     * Get detailed breakdown for a specific user
     *
     * @param int $user_id User ID
     * @return array Detailed user statistics
     */
    public function get_user_details($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'alttext_audit_results';
        $user_id = intval($user_id);

        // Get user's statistics
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as total_images,
                SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) as missing_alt,
                SUM(CASE WHEN has_alt = 1 THEN 1 ELSE 0 END) as has_alt,
                ROUND(SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as missing_percentage
            FROM {$table_name}
            WHERE user_id = %d
        ", $user_id));

        if (!$stats) {
            return null;
        }

        return $this->format_user_data($user_id, $stats);
    }

    /**
     * Get top offenders (users with most missing alt-text)
     *
     * @param int $limit Number of users to return
     * @return array Top users by missing alt-text count
     */
    public function get_top_offenders($limit = 10) {
        $all_users = $this->get_user_counts();

        // Already sorted by missing_alt DESC
        return array_slice($all_users, 0, $limit);
    }

    /**
     * Get user statistics with filters
     *
     * @param array $args Filter arguments (content_type, post_type, etc.)
     * @return array Filtered user statistics
     */
    public function get_filtered_user_stats($args = array()) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'alttext_audit_results';

        // Build WHERE clause from filters
        $where_clauses = array('1=1');
        $prepare_args = array();

        if (!empty($args['content_type'])) {
            $where_clauses[] = 'content_type = %s';
            $prepare_args[] = $args['content_type'];
        }

        if (!empty($args['post_type'])) {
            $where_clauses[] = 'post_type = %s';
            $prepare_args[] = $args['post_type'];
        }

        if (!empty($args['has_alt'])) {
            $where_clauses[] = 'has_alt = %d';
            $prepare_args[] = intval($args['has_alt']);
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Build query
        $query = "
            SELECT
                user_id,
                COUNT(*) as total_images,
                SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) as missing_alt,
                SUM(CASE WHEN has_alt = 1 THEN 1 ELSE 0 END) as has_alt,
                ROUND(SUM(CASE WHEN has_alt = 0 THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as missing_percentage
            FROM {$table_name}
            WHERE {$where_sql}
            GROUP BY user_id
            HAVING missing_alt > 0
            ORDER BY missing_alt DESC
        ";

        if (!empty($prepare_args)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $prepare_args));
        } else {
            $results = $wpdb->get_results($query);
        }

        // Format results
        $formatted_results = array();
        foreach ($results as $result) {
            $formatted_results[] = $this->format_user_data($result->user_id, $result);
        }

        return $formatted_results;
    }

    /**
     * Batch fetch user data to avoid N+1 queries
     *
     * @param array $user_ids Array of user IDs
     * @return array Associative array of user data keyed by user ID
     */
    private function batch_get_users($user_ids) {
        if (empty($user_ids)) {
            return array();
        }

        global $wpdb;

        // Build query to fetch all users at once
        $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, display_name, user_login, user_email
             FROM {$wpdb->users}
             WHERE ID IN ($placeholders)",
            $user_ids
        ));

        // Get user meta (roles) in batch
        $user_meta = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, meta_value
             FROM {$wpdb->usermeta}
             WHERE meta_key = 'wp_capabilities'
             AND user_id IN ($placeholders)",
            $user_ids
        ));

        // Index user meta by user_id
        $meta_by_user = array();
        foreach ($user_meta as $meta) {
            $meta_by_user[$meta->user_id] = $meta->meta_value;
        }

        // Format into associative array
        $users_data = array();
        foreach ($users as $user) {
            $capabilities = isset($meta_by_user[$user->ID]) ? maybe_unserialize($meta_by_user[$user->ID]) : array();
            $roles = is_array($capabilities) ? array_keys($capabilities) : array();

            $users_data[$user->ID] = array(
                'display_name' => $user->display_name,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'roles' => $roles
            );
        }

        return $users_data;
    }

    /**
     * Format user data with cached user information
     *
     * @param int $user_id User ID
     * @param object $counts Count data from database
     * @param array $users_data Cached user data array
     * @return array Formatted user data
     */
    private function format_user_data_with_cache($user_id, $counts, $users_data) {
        if (isset($users_data[$user_id])) {
            $user_info = $users_data[$user_id];
            $display_name = $user_info['display_name'];
            $user_login = $user_info['user_login'];
            $user_email = $user_info['user_email'];
            $roles = $user_info['roles'];
            $role = !empty($roles) ? ucfirst($roles[0]) : 'Unknown';
        } else {
            // Handle deleted users
            $display_name = sprintf(__('Deleted User (ID: %d)', 'wp-alttext-auditor'), $user_id);
            $user_login = '';
            $user_email = '';
            $role = 'Deleted';
        }

        return array(
            'user_id' => intval($user_id),
            'display_name' => $display_name,
            'user_login' => $user_login,
            'user_email' => $user_email,
            'role' => $role,
            'total_images' => intval($counts->total_images),
            'missing_alt' => intval($counts->missing_alt),
            'has_alt' => intval($counts->has_alt),
            'missing_percentage' => floatval($counts->missing_percentage)
        );
    }

    /**
     * Format user data for display
     *
     * @param int $user_id User ID
     * @param object $counts Count data from database
     * @return array Formatted user data
     */
    private function format_user_data($user_id, $counts) {
        $user = get_userdata($user_id);

        if ($user) {
            $display_name = $user->display_name;
            $user_login = $user->user_login;
            $user_email = $user->user_email;
            $roles = $user->roles;
            $role = !empty($roles) ? ucfirst($roles[0]) : 'Unknown';
        } else {
            // Handle deleted users
            $display_name = sprintf(__('Deleted User (ID: %d)', 'wp-alttext-auditor'), $user_id);
            $user_login = '';
            $user_email = '';
            $role = 'Deleted';
        }

        return array(
            'user_id' => intval($user_id),
            'display_name' => $display_name,
            'user_login' => $user_login,
            'user_email' => $user_email,
            'role' => $role,
            'total_images' => intval($counts->total_images),
            'missing_alt' => intval($counts->missing_alt),
            'has_alt' => intval($counts->has_alt),
            'missing_percentage' => floatval($counts->missing_percentage)
        );
    }

    /**
     * Get user attribution summary
     *
     * Returns overall summary of user attribution stats
     *
     * @return array Summary statistics
     */
    public function get_summary() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'alttext_audit_results';

        $summary = $wpdb->get_row("
            SELECT
                COUNT(DISTINCT user_id) as total_users,
                COUNT(DISTINCT CASE WHEN has_alt = 0 THEN user_id END) as users_with_missing
            FROM {$table_name}
        ");

        return array(
            'total_users' => intval($summary->total_users),
            'users_with_missing' => intval($summary->users_with_missing)
        );
    }
}
