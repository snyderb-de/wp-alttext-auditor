<?php
/**
 * Alt-Text Audit Dashboard Template
 *
 * Main dashboard page template for the audit functionality.
 *
 * @package WP_AltText_Updater
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';

// Get tabs from dashboard instance
$tabs = $this->get_tabs($current_tab);
?>

<div class="wrap audit-dashboard-wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper wp-clearfix audit-dashboard-tabs">
        <?php foreach ($tabs as $tab_key => $tab_data) : ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, admin_url('admin.php?page=wp-alttext-auditor-audit'))); ?>"
               class="nav-tab <?php echo $tab_data['active'] ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_data['title']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Tab Content -->
    <div class="audit-tab-content">
        <?php
        switch ($current_tab) {
            case 'overview':
                $this->render_overview_tab();
                break;

            case 'scans':
                $this->render_scans_tab();
                break;

            case 'missing':
                $this->render_missing_alt_tab();
                break;

            case 'users':
                $this->render_users_tab();
                break;

            case 'content':
                $this->render_content_tab();
                break;

            case 'media':
                $this->render_media_tab();
                break;

            default:
                $this->render_overview_tab();
                break;
        }
        ?>
    </div>
</div>
