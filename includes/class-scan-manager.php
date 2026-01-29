<?php
/**
 * WP Alt Text Scan Manager
 *
 * Manages scan records including metadata, reports, and history.
 *
 * @package WP_AltText_Updater
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_AltText_Scan_Manager {

    /**
     * Option name for storing scan records
     */
    const SCANS_OPTION = 'alttext_audit_scans';

    /**
     * Create a new scan record
     *
     * @param array $args {
     *     Scan parameters
     *
     *     @type string $scan_type Type of scan (content, media, drafts, full)
     *     @type string $trigger How scan was initiated (manual, cron)
     *     @type int $user_id User who initiated the scan
     *     @type array $stats Statistics (total, missing, has_alt)
     *     @type string $report_filename Associated HTML report filename
     * }
     * @return string Scan ID
     */
    public function create_scan($args) {
        $defaults = array(
            'scan_type' => 'full',
            'trigger' => 'manual',
            'user_id' => get_current_user_id(),
            'stats' => array(
                'total' => 0,
                'missing' => 0,
                'has_alt' => 0,
                'content' => 0,
                'media' => 0,
                'drafts' => 0
            ),
            'report_filename' => ''
        );

        $scan = wp_parse_args($args, $defaults);

        // If stats were passed from get_statistics(), map the keys
        if (isset($args['stats']['total_images'])) {
            $scan['stats']['total'] = $args['stats']['total_images'];
            $scan['stats']['missing'] = $args['stats']['missing_alt'];
            $scan['stats']['has_alt'] = $args['stats']['has_alt'];
        }

        // Generate unique scan ID
        $scan_id = 'scan_' . current_time('timestamp') . '_' . wp_rand(1000, 9999);

        $scan['id'] = $scan_id;
        $scan['date'] = current_time('mysql');
        $scan['timestamp'] = current_time('timestamp');
        $scan['status'] = 'completed';

        // Get existing scans
        $scans = $this->get_scans();

        // Add new scan to beginning
        array_unshift($scans, $scan);

        // Keep last 50 scans
        $scans = array_slice($scans, 0, 50);

        // Save to options
        update_option(self::SCANS_OPTION, $scans);

        return $scan_id;
    }

    /**
     * Get all scan records
     *
     * @param array $args Optional filter arguments
     * @return array Array of scan records
     */
    public function get_scans($args = array()) {
        $scans = get_option(self::SCANS_OPTION, array());

        // Apply filters if provided
        if (!empty($args['scan_type'])) {
            $scans = array_filter($scans, function($scan) use ($args) {
                return $scan['scan_type'] === $args['scan_type'];
            });
        }

        if (!empty($args['trigger'])) {
            $scans = array_filter($scans, function($scan) use ($args) {
                return $scan['trigger'] === $args['trigger'];
            });
        }

        if (!empty($args['user_id'])) {
            $scans = array_filter($scans, function($scan) use ($args) {
                return $scan['user_id'] == $args['user_id'];
            });
        }

        return array_values($scans);
    }

    /**
     * Get a single scan by ID
     *
     * @param string $scan_id Scan ID
     * @return array|null Scan record or null if not found
     */
    public function get_scan($scan_id) {
        $scans = $this->get_scans();

        foreach ($scans as $scan) {
            if ($scan['id'] === $scan_id) {
                return $scan;
            }
        }

        return null;
    }

    /**
     * Delete scan records by IDs
     *
     * @param array $scan_ids Array of scan IDs to delete
     * @return int Number of scans deleted
     */
    public function delete_scans($scan_ids) {
        $scans = $this->get_scans();
        $deleted = 0;

        // Filter out scans to delete and delete associated reports
        $scans = array_filter($scans, function($scan) use ($scan_ids, &$deleted) {
            if (in_array($scan['id'], $scan_ids)) {
                // Delete associated report file
                if (!empty($scan['report_filename'])) {
                    $upload_dir = wp_upload_dir();
                    $report_file = trailingslashit($upload_dir['basedir']) . 'alttext-reports/' . $scan['report_filename'];
                    if (file_exists($report_file)) {
                        @unlink($report_file);
                    }
                }
                $deleted++;
                return false;
            }
            return true;
        });

        update_option(self::SCANS_OPTION, array_values($scans));

        return $deleted;
    }

    /**
     * Update scan record
     *
     * @param string $scan_id Scan ID
     * @param array $data Data to update
     * @return bool Success
     */
    public function update_scan($scan_id, $data) {
        $scans = $this->get_scans();

        foreach ($scans as $key => $scan) {
            if ($scan['id'] === $scan_id) {
                $scans[$key] = array_merge($scan, $data);
                update_option(self::SCANS_OPTION, $scans);
                return true;
            }
        }

        return false;
    }

    /**
     * Get report URL for a scan
     *
     * @param string $report_filename Report filename
     * @return string Report URL
     */
    public function get_report_url($report_filename) {
        if (empty($report_filename)) {
            return '';
        }

        $upload_dir = wp_upload_dir();
        return trailingslashit($upload_dir['baseurl']) . 'alttext-reports/' . $report_filename;
    }

    /**
     * Clean up old scans (keep last 50)
     */
    public function cleanup_old_scans() {
        $scans = $this->get_scans();

        if (count($scans) > 50) {
            // Delete reports for scans being removed
            $scans_to_remove = array_slice($scans, 50);
            foreach ($scans_to_remove as $scan) {
                if (!empty($scan['report_filename'])) {
                    $upload_dir = wp_upload_dir();
                    $report_file = trailingslashit($upload_dir['basedir']) . 'alttext-reports/' . $scan['report_filename'];
                    if (file_exists($report_file)) {
                        @unlink($report_file);
                    }
                }
            }

            // Keep only last 50
            $scans = array_slice($scans, 0, 50);
            update_option(self::SCANS_OPTION, $scans);
        }
    }

    /**
     * Auto-cleanup old scans based on settings
     *
     * Deletes scans older than the configured number of days
     */
    public function auto_cleanup_by_age() {
        $cleanup_days = get_option('alttext_auto_cleanup_days', 'never');

        if ($cleanup_days === 'never') {
            return;
        }

        $days = intval($cleanup_days);
        if ($days <= 0) {
            return;
        }

        $cutoff_timestamp = current_time('timestamp') - ($days * DAY_IN_SECONDS);
        $scans = $this->get_scans();
        $scan_ids_to_delete = array();

        // Find scans older than cutoff
        foreach ($scans as $scan) {
            if ($scan['timestamp'] < $cutoff_timestamp) {
                $scan_ids_to_delete[] = $scan['id'];
            }
        }

        // Delete old scans
        if (!empty($scan_ids_to_delete)) {
            $deleted = $this->delete_scans($scan_ids_to_delete);
            if ($deleted > 0) {
                error_log("WP Alt Text Updater: Auto-cleanup deleted {$deleted} scans older than {$days} days");
            }
        }
    }
}
