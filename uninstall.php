<?php
/**
 * Plugin Uninstall Handler
 *
 * Fired when the plugin is uninstalled.
 * Removes all plugin data from the database.
 *
 * @package WP_AltText_Updater
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete plugin options
delete_option('alttext_audit_last_scan');
delete_option('alttext_audit_cron_enabled');

// Delete transients
delete_transient('alttext_audit_stats_cache');
delete_transient('alttext_user_counts_cache');

// Delete all rate limiting transients (per-user export rate limits)
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_alttext_export_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_alttext_export_%'");

// Drop custom database table
$table_name = $wpdb->prefix . 'alttext_audit_results';

// Validate table name format for security (alphanumeric and underscore only)
if (preg_match('/^[a-zA-Z0-9_]+$/', $table_name)) {
    $wpdb->query("DROP TABLE IF EXISTS " . esc_sql($table_name));
}

// Clear scheduled cron events
$timestamp = wp_next_scheduled('alttext_audit_cron_scan');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'alttext_audit_cron_scan');
}

// Note: We don't delete attachment meta (_wp_attachment_image_alt) as that's
// WordPress core data and should persist even if our plugin is removed.
