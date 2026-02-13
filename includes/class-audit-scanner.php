<?php
/**
 * WP Alt Text Audit Scanner
 *
 * Handles scanning of published content and media library for images
 * with missing alt-text attributes.
 *
 * @package WP_AltText_Updater
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_AltText_Audit_Scanner {

    /**
     * Storage instance for saving results
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
            require_once ALTTEXT_AUDITOR_PLUGIN_DIR . 'includes/class-audit-storage.php';
            $this->storage = new WP_AltText_Audit_Storage();
        } else {
            $this->storage = $storage;
        }
    }

    /**
     * Scan published posts and pages for images with missing alt-text
     *
     * @param int $batch_size Number of posts to process per batch
     * @param int $offset Starting offset for pagination
     * @return array Progress data with processed count, total, and percentage
     */
    public function scan_content($batch_size = 50, $offset = 0) {
        // Query published posts and pages
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'modified',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);
        $results = array();

        // Parse each post's content for images
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post();

                // Extract images from post content
                $images = $this->parse_html_images($post->post_content);

                // Process each image found
                foreach ($images as $img_data) {
                    $attachment_id = $this->match_image_to_attachment($img_data['src']);

                    $results[] = array(
                        'content_type' => 'post_content',
                        'content_id' => $post->ID,
                        'image_source' => $img_data['src'],
                        'attachment_id' => $attachment_id,
                        'has_alt' => !empty($img_data['alt']) ? 1 : 0,
                        'alt_text' => $img_data['alt'],
                        'user_id' => $post->post_author,
                        'post_type' => $post->post_type,
                        'scan_date' => wp_date('Y-m-d H:i:s'),
                        'last_updated' => wp_date('Y-m-d H:i:s')
                    );
                }
            }
        }

        wp_reset_postdata();

        // Save results if any were found
        if (!empty($results)) {
            $this->storage->bulk_insert($results);
        }

        // Calculate progress
        $total = $query->found_posts;
        $processed = min($offset + $batch_size, $total);
        $percentage = $total > 0 ? round(($processed / $total) * 100) : 100;
        $continue = $processed < $total;

        return array(
            'processed' => $processed,
            'total' => $total,
            'percentage' => $percentage,
            'continue' => $continue,
            'results_count' => count($results)
        );
    }

    /**
     * Scan draft posts and pages for images with missing alt-text
     *
     * @param int $batch_size Number of posts to process per batch
     * @param int $offset Starting offset for pagination
     * @return array Progress data with processed count, total, and percentage
     */
    public function scan_drafts($batch_size = 50, $offset = 0) {
        // Query draft posts and pages
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'draft',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'modified',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);
        $results = array();

        // Parse each post's content for images
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post();

                // Extract images from post content
                $images = $this->parse_html_images($post->post_content);

                // Process each image found
                foreach ($images as $img_data) {
                    $attachment_id = $this->match_image_to_attachment($img_data['src']);

                    $results[] = array(
                        'content_type' => 'draft_content',
                        'content_id' => $post->ID,
                        'image_source' => $img_data['src'],
                        'attachment_id' => $attachment_id,
                        'has_alt' => !empty($img_data['alt']) ? 1 : 0,
                        'alt_text' => $img_data['alt'],
                        'user_id' => $post->post_author,
                        'post_type' => $post->post_type,
                        'scan_date' => wp_date('Y-m-d H:i:s'),
                        'last_updated' => wp_date('Y-m-d H:i:s')
                    );
                }
            }
        }

        wp_reset_postdata();

        // Save results if any were found
        if (!empty($results)) {
            $this->storage->bulk_insert($results);
        }

        // Calculate progress
        $total = $query->found_posts;
        $processed = min($offset + $batch_size, $total);
        $percentage = $total > 0 ? round(($processed / $total) * 100) : 100;
        $continue = $processed < $total;

        return array(
            'processed' => $processed,
            'total' => $total,
            'percentage' => $percentage,
            'continue' => $continue,
            'results_count' => count($results)
        );
    }

    /**
     * Scan media library for attachments with missing alt-text
     *
     * @param int $batch_size Number of attachments to process per batch
     * @param int $offset Starting offset for pagination
     * @return array Progress data with processed count, total, and percentage
     */
    public function scan_media_library($batch_size = 50, $offset = 0) {
        // Query image attachments
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $query = new WP_Query($args);
        $results = array();

        // Process each attachment
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attachment = get_post();

                // Get alt-text from post meta
                $alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
                $has_alt = !empty($alt);

                // Get uploader (post author)
                $uploader_id = $attachment->post_author;

                // Get attachment URL
                $image_url = wp_get_attachment_url($attachment->ID);

                $results[] = array(
                    'content_type' => 'media_library',
                    'content_id' => $attachment->ID,
                    'image_source' => $image_url,
                    'attachment_id' => $attachment->ID,
                    'has_alt' => $has_alt ? 1 : 0,
                    'alt_text' => $alt,
                    'user_id' => $uploader_id,
                    'post_type' => null,
                    'scan_date' => wp_date('Y-m-d H:i:s'),
                    'last_updated' => wp_date('Y-m-d H:i:s')
                );
            }
        }

        wp_reset_postdata();

        // Save results if any were found
        if (!empty($results)) {
            $this->storage->bulk_insert($results);
        }

        // Calculate progress
        $total = $query->found_posts;
        $processed = min($offset + $batch_size, $total);
        $percentage = $total > 0 ? round(($processed / $total) * 100) : 100;
        $continue = $processed < $total;

        return array(
            'processed' => $processed,
            'total' => $total,
            'percentage' => $percentage,
            'continue' => $continue,
            'results_count' => count($results)
        );
    }

    /**
     * Parse HTML content for img tags and extract src and alt attributes
     *
     * Uses DOMDocument for robust HTML parsing.
     *
     * @param string $html_content HTML content to parse
     * @return array Array of images with 'src' and 'alt' keys
     */
    private function parse_html_images($html_content) {
        $images = array();

        // Return early if content is empty
        if (empty($html_content)) {
            return $images;
        }

        // Use DOMDocument for robust HTML parsing
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();

        // Convert encoding to handle UTF-8 properly
        $html_content = mb_convert_encoding($html_content, 'HTML-ENTITIES', 'UTF-8');

        // Load HTML
        @$dom->loadHTML($html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Extract all img tags
        $img_tags = $dom->getElementsByTagName('img');

        foreach ($img_tags as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');

            // Only include images with a src attribute
            if (!empty($src)) {
                $images[] = array(
                    'src' => $src,
                    'alt' => $alt
                );
            }
        }

        // Clear libxml errors
        libxml_clear_errors();

        return $images;
    }

    /**
     * Match image URL to attachment ID in media library
     *
     * Extracts filename from URL and queries the wp_postmeta table
     * for matching _wp_attached_file entries.
     *
     * @param string $image_src Image URL or src attribute
     * @return int|null Attachment ID if found, null otherwise
     */
    private function match_image_to_attachment($image_src) {
        global $wpdb;

        // Return null for empty src
        if (empty($image_src)) {
            return null;
        }

        // Extract path from URL
        $parsed_url = wp_parse_url($image_src);
        if (!$parsed_url || !isset($parsed_url['path'])) {
            return null;
        }

        $full_path = $parsed_url['path'];

        // Extract the relative path from uploads directory
        // Look for "uploads/" in the path and get everything after it
        if (strpos($full_path, '/uploads/') !== false) {
            $relative_path = substr($full_path, strpos($full_path, '/uploads/') + 9);
        } else {
            // Fallback: use the full path basename
            $relative_path = basename($full_path);
        }

        // STRATEGY 1: Try exact match on relative path first (most accurate)
        // This prevents filename collisions (e.g., 2024/01/hero.jpg vs 2025/03/hero.jpg)
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_wp_attached_file'
             AND meta_value = %s
             LIMIT 1",
            $relative_path
        ));

        if ($attachment_id) {
            return intval($attachment_id);
        }

        // STRATEGY 2: Fallback to filename-based LIKE match (less accurate but broader)
        // Only use if exact match fails (e.g., for resized images or URL variations)
        $filename = basename($full_path);
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_wp_attached_file'
             AND meta_value LIKE %s
             LIMIT 1",
            '%' . $wpdb->esc_like($filename)
        ));

        return $attachment_id ? intval($attachment_id) : null;
    }

    /**
     * Get the user ID for a given post (for attribution)
     *
     * @param int $post_id Post ID
     * @return int User ID (post author)
     */
    private function get_content_user_id($post_id) {
        $post = get_post($post_id);
        return $post ? intval($post->post_author) : 0;
    }

    /**
     * Get the user ID for a given attachment (for attribution)
     *
     * @param int $attachment_id Attachment ID
     * @return int User ID (uploader)
     */
    private function get_attachment_user_id($attachment_id) {
        $attachment = get_post($attachment_id);
        return $attachment ? intval($attachment->post_author) : 0;
    }

    /**
     * Clear all scan results from database
     *
     * Useful for re-scanning from scratch
     *
     * @return bool True on success, false on failure
     */
    public function clear_results() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alttext_audit_results';

        $result = $wpdb->query("TRUNCATE TABLE $table_name");

        // Clear cached statistics
        delete_transient('alttext_audit_stats_cache');

        return $result !== false;
    }
}
