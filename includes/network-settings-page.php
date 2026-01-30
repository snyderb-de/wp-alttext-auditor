<?php
/**
 * Network Settings Page (Multisite)
 *
 * Network-wide settings for the Alt-Text Auditor plugin.
 *
 * @package WP_AltText_Auditor
 * @since 1.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle settings save
if (isset($_POST['alttext_network_settings_nonce']) &&
    wp_verify_nonce($_POST['alttext_network_settings_nonce'], 'alttext_network_settings')) {

    if (current_user_can('manage_network_options')) {
        // Save network-wide settings
        $auto_activate = isset($_POST['auto_activate_new_sites']) ? 1 : 0;
        update_site_option('alttext_auto_activate_new_sites', $auto_activate);

        echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully.', 'wp-alttext-auditor') . '</p></div>';
    }
}

// Get current settings
$auto_activate = get_site_option('alttext_auto_activate_new_sites', 1);

?>
<div class="wrap">
    <h1><?php echo esc_html__('Network Alt-Text Audit Settings', 'wp-alttext-auditor'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('alttext_network_settings', 'alttext_network_settings_nonce'); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="auto_activate_new_sites">
                            <?php echo esc_html__('Auto-activate for New Sites', 'wp-alttext-auditor'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input
                                type="checkbox"
                                id="auto_activate_new_sites"
                                name="auto_activate_new_sites"
                                value="1"
                                <?php checked($auto_activate, 1); ?>
                            />
                            <?php echo esc_html__('Automatically create audit tables when new sites are added to the network', 'wp-alttext-auditor'); ?>
                        </label>
                        <p class="description">
                            <?php echo esc_html__('When enabled, newly created sites will automatically have the alt-text audit database tables created.', 'wp-alttext-auditor'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Plugin Version', 'wp-alttext-auditor'); ?>
                    </th>
                    <td>
                        <strong><?php echo esc_html(WP_ALTTEXT_UPDATER_VERSION); ?></strong>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Network Sites', 'wp-alttext-auditor'); ?>
                    </th>
                    <td>
                        <?php
                        $site_count = get_blog_count();
                        printf(
                            esc_html__('%d sites in network', 'wp-alttext-auditor'),
                            $site_count
                        );
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <input
                type="submit"
                name="submit"
                id="submit"
                class="button button-primary"
                value="<?php echo esc_attr__('Save Settings', 'wp-alttext-auditor'); ?>"
            />
        </p>
    </form>

    <hr />

    <h2><?php echo esc_html__('Multisite Information', 'wp-alttext-auditor'); ?></h2>

    <div class="card">
        <h3><?php echo esc_html__('How Multisite Support Works', 'wp-alttext-auditor'); ?></h3>
        <ul>
            <li><strong><?php echo esc_html__('Per-Site Database Tables:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Each site in the network has its own audit database table.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Per-Site Configuration:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Site administrators control their own scan schedules and settings.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Network Dashboard:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Network admins can view aggregated statistics across all sites.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Network Activation:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('When network-activated, tables are created for all existing sites.', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('New Sites:', 'wp-alttext-auditor'); ?></strong> <?php echo esc_html__('Tables are automatically created when new sites are added (if auto-activate is enabled).', 'wp-alttext-auditor'); ?></li>
        </ul>
    </div>

    <div class="card">
        <h3><?php echo esc_html__('Capabilities Required', 'wp-alttext-auditor'); ?></h3>
        <ul>
            <li><strong><?php echo esc_html__('Network Admin:', 'wp-alttext-auditor'); ?></strong> <code>manage_network_options</code> <?php echo esc_html__('- Can view network dashboard and change network settings', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Site Admin:', 'wp-alttext-auditor'); ?></strong> <code>manage_options</code> <?php echo esc_html__('- Can run scans and view audit dashboard on their site', 'wp-alttext-auditor'); ?></li>
            <li><strong><?php echo esc_html__('Editor/Contributor:', 'wp-alttext-auditor'); ?></strong> <code>upload_files</code> <?php echo esc_html__('- Can edit alt-text in Media Library', 'wp-alttext-auditor'); ?></li>
        </ul>
    </div>
</div>
