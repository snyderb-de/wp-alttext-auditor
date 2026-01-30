<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current page number
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

// Get filter/search/sort parameters
$alt_status = isset($_GET['alt_status']) ? sanitize_text_field($_GET['alt_status']) : 'all';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

// Create instance to access methods
$plugin_instance = new WP_AltText_Updater();
$media_query = $plugin_instance->get_media_items($current_page, $per_page, $alt_status, $search, $orderby, $order);

// Calculate pagination
$total_items = $media_query->found_posts;
$total_pages = ceil($total_items / $per_page);
?>

<div class="wrap">
    <h1><?php _e('Alt Text Manager', 'alt-text-auditor'); ?></h1>
    <p class="description" style="margin-bottom: 0;"><?php _e('Manage alt-text for your media library images. Changes are saved automatically when you modify the alt-text field.', 'alt-text-auditor'); ?></p>

    <!-- Filter and Search Form -->
    <div class="alttext-manager-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="alt-text-auditor-manager" />

            <div class="alttext-filter-row">
                <div class="alttext-filter-item">
                    <label for="alt-status-filter"><?php _e('Alt-Text Status:', 'alt-text-auditor'); ?></label>
                    <select name="alt_status" id="alt-status-filter">
                        <option value="all" <?php selected($alt_status, 'all'); ?>><?php _e('All Images', 'alt-text-auditor'); ?></option>
                        <option value="has_alt" <?php selected($alt_status, 'has_alt'); ?>><?php _e('Has Alt-Text', 'alt-text-auditor'); ?></option>
                        <option value="missing_alt" <?php selected($alt_status, 'missing_alt'); ?>><?php _e('Missing Alt-Text', 'alt-text-auditor'); ?></option>
                    </select>
                </div>

                <div class="alttext-filter-item alttext-search-item">
                    <label for="alttext-search"><?php _e('Search:', 'alt-text-auditor'); ?></label>
                    <input type="search" name="s" id="alttext-search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search filename or alt-text...', 'alt-text-auditor'); ?>" />
                </div>

                <div class="alttext-filter-item">
                    <button type="submit" class="button"><?php _e('Apply Filters', 'alt-text-auditor'); ?></button>
                    <?php if ($alt_status !== 'all' || !empty($search)) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=alt-text-auditor-manager')); ?>" class="button"><?php _e('Reset', 'alt-text-auditor'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php if ($media_query->have_posts()) : ?>
        <div class="alttext-stats">
            <p><?php printf(__('Showing %d of %d images', 'alt-text-auditor'), count($media_query->posts), $total_items); ?></p>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="column-thumbnail"><?php _e('Thumbnail', 'alt-text-auditor'); ?></th>
                    <th scope="col" class="column-filename sortable <?php echo ($orderby === 'title') ? 'sorted' : ''; ?> <?php echo ($orderby === 'title') ? strtolower($order) : 'desc'; ?>">
                        <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'title', 'order' => ($orderby === 'title' && $order === 'ASC') ? 'DESC' : 'ASC'))); ?>">
                            <span><?php _e('File Name', 'alt-text-auditor'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="column-alttext"><?php _e('Alt Text', 'alt-text-auditor'); ?></th>
                    <th scope="col" class="column-status sortable <?php echo ($orderby === 'alt_status') ? 'sorted' : ''; ?> <?php echo ($orderby === 'alt_status') ? strtolower($order) : 'desc'; ?>">
                        <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'alt_status', 'order' => ($orderby === 'alt_status' && $order === 'ASC') ? 'DESC' : 'ASC'))); ?>">
                            <span><?php _e('Status', 'alt-text-auditor'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php while ($media_query->have_posts()) : $media_query->the_post(); 
                    $attachment_id = get_the_ID();
                    $attachment_url = wp_get_attachment_url($attachment_id);
                    $thumbnail = wp_get_attachment_image($attachment_id, array(80, 80), false, array('class' => 'alttext-thumbnail'));
                    $filename = basename(get_attached_file($attachment_id));
                    $current_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                    $has_alt = !empty($current_alt);
                ?>
                <tr data-attachment-id="<?php echo esc_attr($attachment_id); ?>">
                    <td class="column-thumbnail">
                        <?php if ($thumbnail) : ?>
                            <?php echo wp_kses_post($thumbnail); ?>
                        <?php else : ?>
                            <div class="alttext-no-thumbnail">
                                <span class="dashicons dashicons-format-image"></span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="column-filename">
                        <strong><?php echo esc_html($filename); ?></strong>
                        <div class="row-actions">
                            <span class="view">
                                <a href="<?php echo esc_url($attachment_url); ?>" target="_blank"><?php _e('View', 'alt-text-auditor'); ?></a> |
                            </span>
                            <span class="edit">
                                <a href="<?php echo esc_url(get_edit_post_link($attachment_id)); ?>"><?php _e('Edit', 'alt-text-auditor'); ?></a>
                            </span>
                        </div>
                    </td>
                    <td class="column-alttext">
                        <div class="alttext-input-wrapper">
                            <input 
                                type="text" 
                                class="alttext-input" 
                                data-attachment-id="<?php echo esc_attr($attachment_id); ?>"
                                value="<?php echo esc_attr($current_alt); ?>"
                                placeholder="<?php _e('Enter alt text...', 'alt-text-auditor'); ?>"
                                maxlength="255"
                            />
                            <div class="alttext-save-indicator">
                                <span class="spinner"></span>
                                <span class="dashicons dashicons-yes-alt success-icon"></span>
                                <span class="dashicons dashicons-warning error-icon"></span>
                            </div>
                        </div>
                    </td>
                    <td class="column-status">
                        <span class="alttext-status <?php echo $has_alt ? 'has-alt' : 'no-alt'; ?>">
                            <?php if ($has_alt) : ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Has Alt Text', 'alt-text-auditor'); ?>
                            <?php else : ?>
                                <span class="dashicons dashicons-warning"></span>
                                <?php _e('Missing Alt Text', 'alt-text-auditor'); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $pagination_base_args = array(
                        'page' => 'alt-text-auditor-manager'
                    );
                    if ($alt_status !== 'all') {
                        $pagination_base_args['alt_status'] = $alt_status;
                    }
                    if (!empty($search)) {
                        $pagination_base_args['s'] = $search;
                    }
                    if ($orderby !== 'date') {
                        $pagination_base_args['orderby'] = $orderby;
                    }
                    if ($order !== 'DESC') {
                        $pagination_base_args['order'] = $order;
                    }

                    $pagination_args = array(
                        'base' => add_query_arg(array_merge($pagination_base_args, array('paged' => '%#%'))),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous'),
                        'next_text' => __('Next &raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page,
                        'type' => 'plain'
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <div class="notice notice-info">
            <p><?php _e('No images found in your media library.', 'alt-text-auditor'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php wp_reset_postdata(); ?>
</div>
