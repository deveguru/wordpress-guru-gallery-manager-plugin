<?php
/**
 * Plugin Name: Guru Tabbed Gallery (Professional Edition)
 * Plugin URI: https://github.com/deveguru
 * Description: A professional tabbed gallery plugin with categories as tabs, lightbox functionality and advanced customization features.
 * Version: 4.2.0
 * Author: Alireza Fatemi
 * Author URI: https://alirezafatemi.ir
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: guru-gallery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Guru_Tabbed_Gallery_Plugin {

 const VERSION = '4.2.0'; // Version updated
 private $post_type = 'gg_gallery';
 private $meta_key = '_gg_gallery_data';

 public function __construct() {
    // Core actions
    add_action('init', [$this, 'register_post_type']);
    add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
    add_action('save_post_' . $this->post_type, [$this, 'save_gallery_meta']);
    add_shortcode('guru_tabbed_gallery', [$this, 'render_shortcode']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    add_action('admin_menu', [$this, 'add_settings_page']);

    // AJAX handlers
    add_action('wp_ajax_gg_search_images', [$this, 'ajax_search_images']);
    add_action('wp_ajax_nopriv_gg_search_images', [$this, 'ajax_search_images']);
    
    // --- NEW: Actions for gallery ordering ---
    add_action('wp_ajax_gg_update_gallery_order', [$this, 'ajax_update_gallery_order']);
    add_action('admin_footer-edit.php', [$this, 'add_reorder_script']);
    
    // Admin list table columns
    add_filter("manage_{$this->post_type}_posts_columns", [$this, 'add_custom_columns']);
    add_action("manage_{$this->post_type}_posts_custom_column", [$this, 'render_custom_columns'], 10, 2);
    add_filter("manage_edit-{$this->post_type}_sortable_columns", [$this, 'make_order_column_sortable']);
 }

 public function register_post_type() {
    register_post_type($this->post_type, [
        'labels' => [
            'name' => __('گالری‌های گورو', 'guru-gallery'),
            'singular_name' => __('گالری', 'guru-gallery'),
            'menu_name' => __('گالری گورو', 'guru-gallery'),
            'add_new_item' => __('افزودن گالری جدید', 'guru-gallery'),
            'edit_item' => __('ویرایش گالری', 'guru-gallery'),
            'all_items' => __('همه گالری‌ها', 'guru-gallery')
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-format-gallery',
        // --- MODIFIED: Added 'page-attributes' to enable ordering ---
        'supports' => ['title', 'page-attributes'], 
        'capability_type' => 'post'
    ]);
 }

 public function add_meta_boxes() {
    add_meta_box('gg_gallery_manager', __('مدیریت گالری و تنظیمات', 'guru-gallery'), [$this, 'render_meta_box'], $this->post_type, 'normal', 'high');
 }

 public function add_settings_page() {
    add_submenu_page(
        'edit.php?post_type=' . $this->post_type,
        __('راهنمای شورت‌کد', 'guru-gallery'),
        __('راهنمای شورت‌کد', 'guru-gallery'),
        'manage_options',
        'gg-shortcode-guide',
        [$this, 'render_settings_page']
    );
 }

 public function render_settings_page() {
    // This function remains unchanged.
    ?>
    <div class="wrap">
        <h1><?php _e('راهنمای شورت‌کد گالری تب‌دار', 'guru-gallery'); ?></h1>
        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-top: 20px;">
            <h2><?php _e('نحوه استفاده', 'guru-gallery'); ?></h2>
            <p><?php _e('برای نمایش تمام گالری‌ها به صورت تب‌دار از شورت‌کد زیر استفاده کنید. ترتیب نمایش گالری‌ها را می‌توانید با کشیدن و رها کردن در صفحه "همه گالری‌ها" مدیریت کنید.', 'guru-gallery'); ?></p>
            <code style="background: #f1f1f1; padding: 10px; display: block; font-size: 14px; margin: 10px 0;">
            [guru_tabbed_gallery]
            </code>
            <h3><?php _e('پارامترهای اختیاری:', 'guru-gallery'); ?></h3>
            <ul>
                <li><strong>show_search:</strong> <?php _e('نمایش جعبه جستجو (true/false)', 'guru-gallery'); ?></li>
                <li><strong>columns_desktop:</strong> <?php _e('تعداد ستون در دسکتاپ (پیش‌فرض: 4)', 'guru-gallery'); ?></li>
                <li><strong>columns_tablet:</strong> <?php _e('تعداد ستون در تبلت (پیش‌فرض: 3)', 'guru-gallery'); ?></li>
                <li><strong>columns_mobile:</strong> <?php _e('تعداد ستون در موبایل (پیش‌فرض: 1)', 'guru-gallery'); ?></li>
            </ul>
            <h3><?php _e('مثال با پارامترها:', 'guru-gallery'); ?></h3>
            <code style="background: #f1f1f1; padding: 10px; display: block; font-size: 14px; margin: 10px 0;">
            [guru_tabbed_gallery show_search="true" columns_desktop="3" columns_tablet="2" columns_mobile="1"]
            </code>
            <div style="background: #e7f3ff; padding: 15px; border-radius: 4px; margin-top: 20px;">
            <h4><?php _e('ویژگی جدید: لایت‌باکس', 'guru-gallery'); ?></h4>
            <p><?php _e('حالا می‌توانید روی هر تصویر کلیک کنید تا در حالت بزرگنمایی مشاهده شود. همچنین امکان مرور تصاویر با کلیدهای جهت‌دار و بستن با کلید Escape وجود دارد.', 'guru-gallery'); ?></p>
            </div>
        </div>
    </div>
    <?php
 }

 public function enqueue_admin_assets($hook) {
    global $post;
    
    // Enqueue scripts for the gallery edit page
    if (('post.php' === $hook || 'post-new.php' === $hook) && isset($post) && $post->post_type === $this->post_type) {
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_register_style('gg-admin-style', false);
        wp_enqueue_style('gg-admin-style');
        wp_add_inline_style('gg-admin-style', $this->get_admin_css());
        wp_register_script('gg-admin-script', false, ['jquery', 'wp-color-picker', 'jquery-ui-sortable'], self::VERSION, true);
        wp_enqueue_script('gg-admin-script');
        wp_add_inline_script('gg-admin-script', $this->get_admin_js());
    }
    
    // --- NEW: Enqueue sortable script for the gallery list page ---
    if ('edit.php' === $hook && isset($_GET['post_type']) && $_GET['post_type'] === $this->post_type) {
        wp_enqueue_script('jquery-ui-sortable');
    }
 }

 // This function remains unchanged.
 public function render_meta_box($post) {
    wp_nonce_field('gg_save_gallery_meta', 'gg_meta_nonce');
    $data = get_post_meta($post->ID, $this->meta_key, true);
    $defaults = [
        'images' => [],
        'settings' => [
            'aspect_ratio' => 'original', 'border_radius' => 4, 'border_width' => 0,
            'border_style' => 'solid', 'border_color' => '#333333',
            'hover_effect' => 'zoom', 'overlay_style' => 'gradient'
        ]
    ];
    $data = wp_parse_args($data, $defaults);
    $settings = $data['settings'];
    ?>
    <div id="gg-gallery-app">
        <div class="gg-tabs">
            <a href="#gg-tab-images" class="gg-tab active"><?php _e('مدیریت تصاویر', 'guru-gallery'); ?></a>
            <a href="#gg-tab-settings" class="gg-tab"><?php _e('تنظیمات نمایش', 'guru-gallery'); ?></a>
        </div>
        <div id="gg-tab-images" class="gg-tab-content active">
            <div id="gg-image-list"></div>
            <p class="gg-no-images"><?php _e('هنوز تصویری در این گالری وجود ندارد. برای افزودن کلیک کنید!', 'guru-gallery'); ?></p>
            <button type="button" class="button button-primary button-large" id="gg-add-images"><?php _e('افزودن / ویرایش تصاویر', 'guru-gallery'); ?></button>
        </div>
        <div id="gg-tab-settings" class="gg-tab-content">
            <h3 class="gg-section-title"><?php _e('استایل و ظاهر تصاویر', 'guru-gallery'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="gg_aspect_ratio"><?php _e('نسبت ابعاد تصویر', 'guru-gallery'); ?></label></th>
                    <td>
                        <select id="gg_aspect_ratio" name="gg_settings[aspect_ratio]">
                            <option value="original" <?php selected($settings['aspect_ratio'], 'original'); ?>><?php _e('اصلی (بدون تغییر)', 'guru-gallery'); ?></option>
                            <option value="1-1" <?php selected($settings['aspect_ratio'], '1-1'); ?>><?php _e('مربع (1:1)', 'guru-gallery'); ?></option>
                            <option value="16-9" <?php selected($settings['aspect_ratio'], '16-9'); ?>><?php _e('منظره (16:9)', 'guru-gallery'); ?></option>
                            <option value="4-3" <?php selected($settings['aspect_ratio'], '4-3'); ?>><?php _e('منظره (4:3)', 'guru-gallery'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="gg_border_radius"><?php _e('گردی گوشه‌ها (px)', 'guru-gallery'); ?></label></th>
                    <td><input type="number" id="gg_border_radius" name="gg_settings[border_radius]" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="100" /></td>
                </tr>
                <tr>
                    <th><label><?php _e('کادر دور تصویر', 'guru-gallery'); ?></label></th>
                    <td class="gg-multi-input">
                        <input type="number" name="gg_settings[border_width]" value="<?php echo esc_attr($settings['border_width']); ?>" min="0" max="20" title="<?php _e('ضخامت کادر (px)', 'guru-gallery'); ?>" />
                        <select name="gg_settings[border_style]">
                            <option value="solid" <?php selected($settings['border_style'], 'solid'); ?>><?php _e('خط صاف', 'guru-gallery'); ?></option>
                            <option value="dashed" <?php selected($settings['border_style'], 'dashed'); ?>><?php _e('خط‌چین', 'guru-gallery'); ?></option>
                            <option value="dotted" <?php selected($settings['border_style'], 'dotted'); ?>><?php _e('نقطه‌چین', 'guru-gallery'); ?></option>
                        </select>
                        <input type="text" name="gg_settings[border_color]" value="<?php echo esc_attr($settings['border_color']); ?>" class="gg-color-picker">
                    </td>
                </tr>
                <tr>
                    <th><label for="gg_hover_effect"><?php _e('افکت هاور (Hover)', 'guru-gallery'); ?></label></th>
                    <td>
                        <select id="gg_hover_effect" name="gg_settings[hover_effect]">
                            <option value="zoom" <?php selected($settings['hover_effect'], 'zoom'); ?>><?php _e('زوم شدن تصویر', 'guru-gallery'); ?></option>
                            <option value="slide" <?php selected($settings['hover_effect'], 'slide'); ?>><?php _e('نمایش کپشن از پایین', 'guru-gallery'); ?></option>
                            <option value="fade" <?php selected($settings['hover_effect'], 'fade'); ?>><?php _e('نمایش روکش تیره', 'guru-gallery'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="gg_overlay_style"><?php _e('استایل روکش اطلاعات', 'guru-gallery'); ?></label></th>
                    <td>
                        <select id="gg_overlay_style" name="gg_settings[overlay_style]">
                            <option value="gradient" <?php selected($settings['overlay_style'], 'gradient'); ?>><?php _e('گرادیان از پایین', 'guru-gallery'); ?></option>
                            <option value="full" <?php selected($settings['overlay_style'], 'full'); ?>><?php _e('پوشش کامل', 'guru-gallery'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <textarea id="gg_gallery_data_input" name="gg_gallery_data" style="display:none;"><?php echo esc_textarea(json_encode($data['images'])); ?></textarea>
    </div>
    <?php
 }

 // This function remains unchanged.
 public function save_gallery_meta($post_id) {
    if (!isset($_POST['gg_meta_nonce']) || !wp_verify_nonce($_POST['gg_meta_nonce'], 'gg_save_gallery_meta') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    $sanitized_data = [];
    if (isset($_POST['gg_gallery_data'])) {
        $images = json_decode(stripslashes($_POST['gg_gallery_data']), true);
        $sanitized_images = [];
        if (is_array($images)) {
            foreach ($images as $img) {
                $sanitized_images[] = [
                    'id' => intval($img['id']), 'url' => esc_url_raw($img['url']),
                    'thumbnail_url' => esc_url_raw($img['thumbnail_url']),
                    'caption' => sanitize_text_field($img['caption']),
                    'description' => sanitize_textarea_field($img['description'])
                ];
            }
        }
        $sanitized_data['images'] = $sanitized_images;
    }

    if (isset($_POST['gg_settings']) && is_array($_POST['gg_settings'])) {
        $s = $_POST['gg_settings'];
        $sanitized_data['settings'] = [
            'aspect_ratio' => sanitize_key($s['aspect_ratio']), 'border_radius' => intval($s['border_radius']),
            'border_width' => intval($s['border_width']), 'border_style' => sanitize_key($s['border_style']),
            'border_color' => sanitize_hex_color($s['border_color']),
            'hover_effect' => sanitize_key($s['hover_effect']), 'overlay_style' => sanitize_key($s['overlay_style'])
        ];
    }

    update_post_meta($post_id, $this->meta_key, $sanitized_data);
 }

 public function render_shortcode($atts) {
    $atts = shortcode_atts([
        'show_search' => 'true', 'columns_desktop' => '4',
        'columns_tablet' => '3', 'columns_mobile' => '1'
    ], $atts, 'guru_tabbed_gallery');
    
    // --- MODIFIED: Changed orderby to 'menu_order' to respect manual sorting ---
    $galleries = get_posts([
        'post_type' => $this->post_type,
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order', 
        'order' => 'ASC'
    ]);

    if (empty($galleries)) {
        return '<div class="gg-no-galleries">' . __('هیچ گالری‌ای هنوز ایجاد نشده است.', 'guru-gallery') . '</div>';
    }

    ob_start();
    // The rest of this function remains unchanged
    ?>
    <div class="gg-tabbed-gallery-wrapper" 
        data-cols-desktop="<?php echo esc_attr($atts['columns_desktop']); ?>"
        data-cols-tablet="<?php echo esc_attr($atts['columns_tablet']); ?>"
        data-cols-mobile="<?php echo esc_attr($atts['columns_mobile']); ?>">
        
        <?php if ($atts['show_search'] === 'true'): ?>
        <div class="gg-search-wrapper">
            <input type="search" class="gg-gallery-search" placeholder="<?php _e('جستجو در تصاویر...', 'guru-gallery'); ?>"/>
        </div>
        <?php endif; ?>

        <div class="gg-tabs-nav">
            <?php foreach ($galleries as $index => $gallery): ?>
            <button class="gg-tab-button <?php echo $index === 0 ? 'active' : ''; ?>" 
                    data-tab="tab-<?php echo $gallery->ID; ?>">
                <?php echo esc_html($gallery->post_title); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <div class="gg-tabs-content">
            <?php foreach ($galleries as $index => $gallery): ?>
            <?php
            $data = get_post_meta($gallery->ID, $this->meta_key, true);
            $images = $data['images'] ?? [];
            $settings = $data['settings'] ?? [];
            $aspect_ratio_css = 'original' !== ($settings['aspect_ratio'] ?? 'original') 
                ? 'aspect-ratio:' . str_replace('-', '/', $settings['aspect_ratio']) . ';' 
                : '';
            ?>
            <div class="gg-tab-panel <?php echo $index === 0 ? 'active' : ''; ?>" 
                id="tab-<?php echo $gallery->ID; ?>"
                data-gallery-id="<?php echo $gallery->ID; ?>"
                data-hover-effect="<?php echo esc_attr($settings['hover_effect'] ?? 'zoom'); ?>"
                data-overlay-style="<?php echo esc_attr($settings['overlay_style'] ?? 'gradient'); ?>"
                style="--gg-border-radius:<?php echo intval($settings['border_radius'] ?? 4); ?>px;--gg-border:<?php echo intval($settings['border_width'] ?? 0); ?>px <?php echo esc_attr($settings['border_style'] ?? 'solid'); ?> <?php echo esc_attr($settings['border_color'] ?? '#333'); ?>;">
                
                <div class="gg-gallery-items-container" style="<?php echo $aspect_ratio_css; ?>">
                    <?php if (!empty($images)): ?>
                        <?php foreach ($images as $img_index => $img): ?>
                            <?php echo $this->get_frontend_image_html($img, $settings, $gallery->ID, $img_index); ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="gg-no-images-frontend"><?php _e('تصویری در این گالری وجود ندارد.', 'guru-gallery'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="gg-loading-overlay">
            <span><?php _e('در حال جستجو...', 'guru-gallery'); ?></span>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div id="gg-lightbox" class="gg-lightbox">
        <div class="gg-lightbox-overlay"></div>
        <div class="gg-lightbox-content">
            <button class="gg-lightbox-close">&times;</button>
            <button class="gg-lightbox-prev">&#8249;</button>
            <button class="gg-lightbox-next">&#8250;</button>
            <div class="gg-lightbox-image-container">
                <img class="gg-lightbox-image" src="" alt="">
                <div class="gg-lightbox-caption">
                    <h3 class="gg-lightbox-title"></h3>
                    <p class="gg-lightbox-description"></p>
                </div>
            </div>
            <div class="gg-lightbox-counter">
                <span class="gg-current-image">1</span> / <span class="gg-total-images">1</span>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
 }

 // This function remains unchanged.
 public function ajax_search_images() {
    check_ajax_referer('gg_frontend_nonce', 'nonce');
    
    $gallery_id = intval($_POST['gallery_id'] ?? 0);
    $term = sanitize_text_field($_POST['search_term'] ?? '');

    if ($gallery_id <= 0) wp_send_json_error();

    $data = get_post_meta($gallery_id, $this->meta_key, true);
    $settings = $data['settings'] ?? [];
    $images = $data['images'] ?? [];

    $filtered = empty($term) ? $images : array_filter($images, function($img) use ($term) {
        return stripos($img['caption'], $term) !== false || stripos($img['description'], $term) !== false;
    });

    ob_start();
    if (!empty($filtered)) {
        $img_index = 0;
        foreach ($filtered as $img) {
            echo $this->get_frontend_image_html($img, $settings, $gallery_id, $img_index);
            $img_index++;
        }
    } else {
        echo '<p class="gg-no-images-frontend">' . __('هیچ تصویری منطبق با جستجوی شما یافت نشد.', 'guru-gallery') . '</p>';
    }

    wp_send_json_success(['html' => ob_get_clean()]);
 }

 // This function remains unchanged.
 public function enqueue_frontend_assets() {
    wp_register_style('gg-frontend-styles', false);
    wp_enqueue_style('gg-frontend-styles');
    wp_add_inline_style('gg-frontend-styles', $this->get_frontend_css());

    wp_register_script('gg-frontend-scripts', ['jquery'], self::VERSION, true);
    wp_enqueue_script('gg-frontend-scripts');
    wp_localize_script('gg-frontend-scripts', 'gg_frontend_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gg_frontend_nonce')
    ]);
    wp_add_inline_script('gg-frontend-scripts', $this->get_frontend_js());
 }

 // --- NEW: Handles admin list table columns ---
 public function add_custom_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        if ($key === 'date') {
            // Add our custom columns before the date column
            $new_columns['menu_order'] = __('ترتیب', 'guru-gallery');
            $new_columns['shortcode'] = __('شورت‌کد', 'guru-gallery');
        }
        $new_columns[$key] = $value;
    }
    return $new_columns;
 }

 // --- MODIFIED: Renders content for all custom columns ---
 public function render_custom_columns($column, $post_id) {
    switch ($column) {
        case 'menu_order':
            $post = get_post($post_id);
            echo $post->menu_order;
            break;
        case 'shortcode':
            echo '<div style="margin-bottom: 5px;"><strong>' . __('این گالری:', 'guru-gallery') . '</strong></div>';
            echo '<input type="text" value="[guru_gallery id=\'' . $post_id . '\']" readonly onfocus="this.select();" style="width:100%;text-align:left;direction:ltr;font-size:11px;">';
            echo '<div style="margin-top: 10px;"><strong>' . __('تمام گالری‌ها (تب‌دار):', 'guru-gallery') . '</strong></div>';
            echo '<input type="text" value="[guru_tabbed_gallery]" readonly onfocus="this.select();" style="width:100%;text-align:left;direction:ltr;font-size:11px;background:#e7f3ff;">';
            break;
    }
 }

 // --- NEW: Makes the 'Order' column sortable ---
 public function make_order_column_sortable($columns) {
    $columns['menu_order'] = 'menu_order';
    return $columns;
 }

 // --- NEW: AJAX handler to update gallery order ---
 public function ajax_update_gallery_order() {
    check_ajax_referer('gg_reorder_nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied.');
    }

    $order = $_POST['order'] ?? [];
    if (is_array($order)) {
        foreach ($order as $index => $post_id) {
            wp_update_post([
                'ID' => intval($post_id),
                'menu_order' => $index,
            ]);
        }
        wp_send_json_success('Order updated.');
    } else {
        wp_send_json_error('Invalid order data.');
    }
 }

 // --- NEW: Adds JavaScript for drag-and-drop reordering ---
 public function add_reorder_script() {
    global $current_screen;
    if ($current_screen->id !== 'edit-' . $this->post_type) {
        return;
    }
    ?>
    <style>
        #the-list tr { cursor: move; }
        .ui-sortable-helper {
            display: table;
            background-color: #f9f9f9;
            border: 1px dashed #bbb;
        }
    </style>
    <script type="text/javascript">
        jQuery(function($) {
            $('#the-list').sortable({
                items: 'tr',
                axis: 'y',
                helper: 'clone',
                update: function() {
                    let order = $(this).sortable('serialize');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'gg_update_gallery_order',
                            nonce: '<?php echo wp_create_nonce('gg_reorder_nonce'); ?>',
                            order: $(this).sortable('toArray', { attribute: 'id' }).map(id => id.replace('post-', ''))
                        },
                        success: function(response) {
                           // Optional: show a success message
                        }
                    });
                }
            });
        });
    </script>
    <?php
 }

 // --- All private 'get' functions below remain unchanged ---

 private function get_frontend_image_html($img, $settings, $gallery_id, $img_index) {
    ob_start();
    ?>
    <div class="gg-gallery-item" 
        data-gallery-id="<?php echo esc_attr($gallery_id); ?>"
        data-image-index="<?php echo esc_attr($img_index); ?>"
        data-image-url="<?php echo esc_url($img['url']); ?>"
        data-image-caption="<?php echo esc_attr($img['caption']); ?>"
        data-image-description="<?php echo esc_attr($img['description']); ?>">
        <img src="<?php echo esc_url($img['url']); ?>" alt="<?php echo esc_attr($img['caption']); ?>" loading="lazy"/>
        <div class="gg-item-overlay">
            <?php if (!empty($img['caption'])): ?>
                <div class="gg-item-caption"><?php echo esc_html($img['caption']); ?></div>
            <?php endif; ?>
            <?php if (!empty($img['description'])): ?>
                <div class="gg-item-description"><?php echo wp_kses_post($img['description']); ?></div>
            <?php endif; ?>
        </div>
        <div class="gg-lightbox-trigger">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M21 21L16.514 16.506M19 10.5C19 15.194 15.194 19 10.5 19S2 15.194 2 10.5 5.806 2 10.5 2 19 5.806 19 10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    </div>
    <?php
    return ob_get_clean();
 }

 private function get_admin_css() {
    return "
    #gg-gallery-app{background:#fff;border:1px solid #ccd0d4;padding:1rem;margin-top:1rem}
    #gg-gallery-app .gg-tabs{border-bottom:1px solid #ccd0d4;margin-bottom:1rem}
    #gg-gallery-app .gg-tab{display:inline-block;padding:8px 16px;text-decoration:none;color:#555;border:1px solid transparent;margin-bottom:-1px;font-weight:600}
    #gg-gallery-app .gg-tab.active{background:#f0f0f1;border-color:#ccd0d4 #ccd0d4 #f0f0f1}
    #gg-gallery-app .gg-tab-content{display:none;padding-top:1rem}
    #gg-gallery-app .gg-tab-content.active{display:block}
    #gg-gallery-app #gg-image-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem}
    #gg-gallery-app .gg-image-item{position:relative;padding:8px;border:1px solid #ddd;background:#f9f9f9;cursor:move;border-radius:4px}
    #gg-gallery-app .gg-image-item .gg-thumbnail{width:100%;height:auto;display:block;margin-bottom:8px;border-radius:2px}
    #gg-gallery-app .gg-image-item .gg-remove-image{position:absolute;top:2px;right:2px;width:24px;height:24px;padding:0;line-height:22px;border-radius:50%;background:#d63638;color:#fff;border:none;cursor:pointer;font-weight:700;font-size:18px}
    #gg-gallery-app .gg-image-item input,#gg-gallery-app .gg-image-item textarea{margin-top:5px;width:100%}
    #gg-gallery-app .gg-no-images{color:#777;font-style:italic;padding:2rem;border:2px dashed #ddd;text-align:center;margin:1rem 0}
    #gg-gallery-app .gg-section-title{border-bottom:1px solid #eee;padding-bottom:10px;margin:20px 0 10px;font-size:1.2em}
    #gg-gallery-app .gg-multi-input{display:flex;gap:10px;align-items:center}
    #gg-gallery-app .gg-multi-input input[type=number]{width:70px}
    ";
 }

 private function get_admin_js() {
    return "
    jQuery(function($) {
        let mediaUploader, galleryImages = [];

        function renderImageList() {
            $('#gg-image-list').empty();
            galleryImages.forEach(function(image) {
                const imageHtml = `
                <div class=\"gg-image-item\" data-id=\"\${image.id}\" data-url=\"\${image.url}\">
                    <img src=\"\${image.thumbnail_url}\" class=\"gg-thumbnail\"/>
                    <div class=\"gg-image-details\">
                        <input type=\"text\" class=\"gg-caption widefat\" placeholder=\"کپشن\" value=\"\${image.caption}\"/>
                        <textarea class=\"gg-description widefat\" placeholder=\"توضیحات\">\${image.description}</textarea>
                    </div>
                    <button type=\"button\" class=\"gg-remove-image\">&times;</button>
                </div>
                `;
                $('#gg-image-list').append(imageHtml);
            });

            if (galleryImages.length > 0) {
                $('.gg-no-images').hide();
            } else {
                $('.gg-no-images').show();
            }
        }

        function updateGalleryData() {
            $('#gg_gallery_data_input').val(JSON.stringify(galleryImages));
        }

        $(document).ready(function() {
            galleryImages = JSON.parse($('#gg_gallery_data_input').val() || '[]');
            renderImageList();

            $('#gg-add-images').on('click', function(e) {
                e.preventDefault();
                if (mediaUploader && mediaUploader.open) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: 'انتخاب یا آپلود تصاویر',
                    button: { text: 'استفاده از این تصاویر' },
                    multiple: 'add'
                });

                mediaUploader.on('select', function() {
                    let attachments = mediaUploader.state().get('selection').toJSON();
                    attachments.forEach(function(attachment) {
                        if (!galleryImages.some(img => img.id == attachment.id)) {
                            galleryImages.push({
                                id: attachment.id,
                                url: attachment.sizes.large ? attachment.sizes.large.url : attachment.url,
                                thumbnail_url: attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url,
                                caption: attachment.caption || attachment.title,
                                description: attachment.description || ''
                            });
                        }
                    });
                    renderImageList();
                    updateGalleryData();
                });

                mediaUploader.open();
            });

            $('#gg-gallery-app .gg-tab').on('click', function(e) {
                e.preventDefault();
                const target = $(this).attr('href');
                $('#gg-gallery-app .gg-tab, #gg-gallery-app .gg-tab-content').removeClass('active');
                $(this).addClass('active');
                $(target).addClass('active');
            });

            $('#gg-image-list').sortable({
                update: function() {
                    galleryImages = [];
                    $('#gg-image-list .gg-image-item').each(function() {
                        const \$item = $(this);
                        galleryImages.push({
                            id: \$item.data('id'),
                            url: \$item.data('url'),
                            thumbnail_url: \$item.find('img').attr('src'),
                            caption: \$item.find('.gg-caption').val(),
                            description: \$item.find('.gg-description').val()
                        });
                    });
                    updateGalleryData();
                }
            }).on('click', '.gg-remove-image', function() {
                const itemId = $(this).closest('.gg-image-item').data('id');
                galleryImages = galleryImages.filter(img => img.id !== itemId);
                renderImageList();
                updateGalleryData();
            }).on('input change', '.gg-caption, .gg-description', function() {
                const itemId = $(this).closest('.gg-image-item').data('id');
                const field = $(this).is('.gg-caption') ? 'caption' : 'description';
                const value = $(this).val();
                galleryImages = galleryImages.map(img => img.id === itemId ? {...img, [field]: value} : img);
                updateGalleryData();
            });

            $('.gg-color-picker').wpColorPicker();
        });
    });
    ";
 }

 private function get_frontend_css() {
    return "
    .gg-tabbed-gallery-wrapper{position:relative;padding:20px;background:#fff;direction:rtl;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
    .gg-search-wrapper{margin-bottom:20px;text-align:center}
    .gg-gallery-search{padding:12px 16px;border:2px solid #ddd;border-radius:25px;width:300px;max-width:100%;font-size:14px;outline:none;transition:border-color 0.3s ease}
    .gg-gallery-search:focus{border-color:#0073aa}
    .gg-tabs-nav{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:20px;border-bottom:2px solid #eee;padding-bottom:10px}
    .gg-tab-button{background:none;border:none;padding:12px 20px;cursor:pointer;font-size:14px;font-weight:600;color:#666;border-radius:6px 6px 0 0;transition:all 0.3s ease;position:relative}
    .gg-tab-button:hover{background:#f0f0f0;color:#333}
    .gg-tab-button.active{background:#0073aa;color:#fff;box-shadow:0 2px 5px rgba(0,115,170,0.3)}
    .gg-tabs-content{position:relative}
    .gg-tab-panel{display:none}
    .gg-tab-panel.active{display:block}
    .gg-gallery-items-container{display:grid;gap:20px;grid-template-columns:repeat(var(--gg-cols-desktop,4),1fr)}
    .gg-gallery-items-container[style*='aspect-ratio'] .gg-gallery-item img{width:100%;height:100%;object-fit:cover}
    @media(max-width:1024px){.gg-gallery-items-container{grid-template-columns:repeat(var(--gg-cols-tablet,3),1fr)}}
    @media(max-width:767px){.gg-tabs-nav{justify-content:center}.gg-tab-button{font-size:12px;padding:8px 12px}.gg-gallery-items-container{grid-template-columns:repeat(var(--gg-cols-mobile,1),1fr)}}
    .gg-gallery-item{position:relative;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.1);border-radius:var(--gg-border-radius);border:var(--gg-border);transition:transform 0.3s ease, box-shadow 0.3s ease;cursor:pointer}
    .gg-gallery-item:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,0.15)}
    .gg-gallery-item img{width:100%;height:auto;display:block;transition:transform 0.4s ease, filter 0.4s ease}
    .gg-item-overlay{position:absolute;bottom:0;left:0;right:0;padding:20px 15px;transition:opacity 0.4s ease, transform 0.4s ease;color:#fff}
    .gg-item-caption{font-weight:700;font-size:1.1em;margin-bottom:5px}
    .gg-item-description{font-size:0.9em;opacity:0.9;line-height:1.4}
    .gg-lightbox-trigger{position:absolute;top:10px;right:10px;width:40px;height:40px;background:rgba(0,0,0,0.7);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;opacity:0;transition:opacity 0.3s ease;z-index:2}
    .gg-gallery-item:hover .gg-lightbox-trigger{opacity:1}
    .gg-tab-panel[data-overlay-style=gradient] .gg-item-overlay{background:linear-gradient(0deg,rgba(0,0,0,0.8) 0,transparent 100%)}
    .gg-tab-panel[data-overlay-style=full] .gg-item-overlay{top:0;background:rgba(0,0,0,0.6);display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center}
    .gg-tab-panel[data-hover-effect=zoom] .gg-gallery-item:hover img{transform:scale(1.1)}
    .gg-tab-panel[data-hover-effect=slide] .gg-item-overlay{transform:translateY(100%)}
    .gg-tab-panel[data-hover-effect=slide] .gg-gallery-item:hover .gg-item-overlay{transform:translateY(0)}
    .gg-tab-panel[data-hover-effect=fade] .gg-item-overlay{opacity:0}
    .gg-tab-panel[data-hover-effect=fade] .gg-gallery-item:hover .gg-item-overlay{opacity:1}
    .gg-tab-panel[data-hover-effect=fade] .gg-gallery-item:hover img{filter:brightness(0.7)}
    .gg-loading-overlay{position:absolute;inset:0;background:rgba(255,255,255,0.9);display:none;align-items:center;justify-content:center;z-index:10;font-size:1.2em;font-weight:700;border-radius:8px}
    .gg-no-images-frontend{grid-column:1/-1;text-align:center;padding:3rem;color:#666;font-style:italic;font-size:1.1em}
    .gg-no-galleries{text-align:center;padding:2rem;background:#f9f9f9;border:2px dashed #ddd;border-radius:8px;color:#666;font-size:1.1em}
    .gg-lightbox{position:fixed;top:0;left:0;width:100%;height:100%;z-index:9999;display:none;direction:ltr}
    .gg-lightbox-overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);cursor:pointer}
    .gg-lightbox-content{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center}
    .gg-lightbox-image-container{position:relative;max-width:90%;max-height:90%;display:flex;flex-direction:column;align-items:center}
    .gg-lightbox-image{max-width:100%;max-height:80vh;object-fit:contain;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.5)}
    .gg-lightbox-caption{background:rgba(0,0,0,0.8);color:#fff;padding:20px;border-radius:0 0 8px 8px;text-align:center;max-width:100%}
    .gg-lightbox-title{margin:0 0 10px 0;font-size:1.3em;font-weight:700}
    .gg-lightbox-description{margin:0;font-size:1em;opacity:0.9;line-height:1.5}
    .gg-lightbox-close{position:absolute;top:20px;right:20px;width:50px;height:50px;background:rgba(0,0,0,0.7);color:#fff;border:none;border-radius:50%;font-size:24px;cursor:pointer;z-index:10001;transition:background 0.3s ease}
    .gg-lightbox-close:hover{background:rgba(0,0,0,0.9)}
    .gg-lightbox-prev,.gg-lightbox-next{position:absolute;top:50%;transform:translateY(-50%);width:60px;height:60px;background:rgba(0,0,0,0.7);color:#fff;border:none;border-radius:50%;font-size:30px;cursor:pointer;z-index:10001;transition:background 0.3s ease}
    .gg-lightbox-prev:hover,.gg-lightbox-next:hover{background:rgba(0,0,0,0.9)}
    .gg-lightbox-prev{left:20px}
    .gg-lightbox-next{right:20px}
    .gg-lightbox-counter{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.7);color:#fff;padding:8px 16px;border-radius:20px;font-size:14px;z-index:10001}
    @media(max-width:768px){.gg-lightbox-image-container{max-width:95%;max-height:95%}.gg-lightbox-image{max-height:70vh}.gg-lightbox-close{top:10px;right:10px;width:40px;height:40px;font-size:20px}.gg-lightbox-prev,.gg-lightbox-next{width:50px;height:50px;font-size:24px}.gg-lightbox-prev{left:10px}.gg-lightbox-next{right:10px}.gg-lightbox-caption{padding:15px}.gg-lightbox-title{font-size:1.1em}.gg-lightbox-description{font-size:0.9em}}
    ";
 }
 
 private function get_frontend_js() {
    return "
    jQuery(document).ready(function($) {
        let searchTimeout;
        let currentGalleryImages = [];
        let currentImageIndex = 0;

        // Tab switching functionality
        $('.gg-tab-button').on('click', function() {
            const tabId = $(this).data('tab');
            $('.gg-tab-button, .gg-tab-panel').removeClass('active');
            $(this).addClass('active');
            $('#' + tabId).addClass('active');
            updateCurrentGalleryImages();
        });

        // Search functionality
        $('.gg-gallery-search').on('keyup', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            const wrapper = $(this).closest('.gg-tabbed-gallery-wrapper');
            const activeTab = wrapper.find('.gg-tab-panel.active');
            const galleryId = activeTab.data('gallery-id');
            const container = activeTab.find('.gg-gallery-items-container');
            const loadingOverlay = wrapper.find('.gg-loading-overlay');

            searchTimeout = setTimeout(function() {
                if (!galleryId) return;
                loadingOverlay.css('display', 'flex');
                $.ajax({
                    url: gg_frontend_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'gg_search_images',
                        nonce: gg_frontend_ajax.nonce,
                        gallery_id: galleryId,
                        search_term: searchTerm
                    },
                    success: function(response) {
                        if (response.success) {
                            container.html(response.data.html);
                            updateCurrentGalleryImages();
                        }
                    },
                    complete: function() {
                        loadingOverlay.hide();
                    }
                });
            }, 300);
        });

        // Update grid columns based on data attributes
        $('.gg-tabbed-gallery-wrapper').each(function() {
            const wrapper = $(this);
            const desktopCols = wrapper.data('cols-desktop') || 4;
            const tabletCols = wrapper.data('cols-tablet') || 3;
            const mobileCols = wrapper.data('cols-mobile') || 1;
            wrapper.css({
                '--gg-cols-desktop': desktopCols,
                '--gg-cols-tablet': tabletCols,
                '--gg-cols-mobile': mobileCols
            });
        });

        // Lightbox functionality
        function updateCurrentGalleryImages() {
            const activeTab = $('.gg-tab-panel.active');
            currentGalleryImages = [];
            activeTab.find('.gg-gallery-item').each(function(index) {
                const item = $(this);
                currentGalleryImages.push({
                    url: item.data('image-url'),
                    caption: item.data('image-caption'),
                    description: item.data('image-description'),
                    index: index
                });
            });
        }

        function openLightbox(index) {
            if (currentGalleryImages.length === 0) return;
            currentImageIndex = index;
            const image = currentGalleryImages[currentImageIndex];
            $('#gg-lightbox .gg-lightbox-image').attr('src', image.url).attr('alt', image.caption);
            $('#gg-lightbox .gg-lightbox-title').text(image.caption);
            $('#gg-lightbox .gg-lightbox-description').text(image.description);
            $('#gg-lightbox .gg-current-image').text(currentImageIndex + 1);
            $('#gg-lightbox .gg-total-images').text(currentGalleryImages.length);
            if (currentGalleryImages.length <= 1) {
                $('.gg-lightbox-prev, .gg-lightbox-next').hide();
            } else {
                $('.gg-lightbox-prev, .gg-lightbox-next').show();
            }
            $('#gg-lightbox').fadeIn(300);
            $('body').addClass('gg-lightbox-open').css('overflow', 'hidden');
        }

        function closeLightbox() {
            $('#gg-lightbox').fadeOut(300);
            $('body').removeClass('gg-lightbox-open').css('overflow', '');
        }

        function showPrevImage() {
            openLightbox((currentImageIndex > 0) ? currentImageIndex - 1 : currentGalleryImages.length - 1);
        }

        function showNextImage() {
            openLightbox((currentImageIndex < currentGalleryImages.length - 1) ? currentImageIndex + 1 : 0);
        }

        updateCurrentGalleryImages();
        $(document).on('click', '.gg-gallery-item', function(e) {
            e.preventDefault();
            updateCurrentGalleryImages();
            openLightbox($(this).data('image-index'));
        });
        $('.gg-lightbox-close, .gg-lightbox-overlay').on('click', closeLightbox);
        $('.gg-lightbox-prev').on('click', function(e) { e.stopPropagation(); showPrevImage(); });
        $('.gg-lightbox-next').on('click', function(e) { e.stopPropagation(); showNextImage(); });
        $(document).on('keydown', function(e) {
            if ($('#gg-lightbox').is(':visible')) {
                if (e.keyCode === 27) closeLightbox();
                if (e.keyCode === 37) showPrevImage();
                if (e.keyCode === 39) showNextImage();
            }
        });
        $('.gg-lightbox-content').on('click', function(e) { if (e.target === this) closeLightbox(); });
        $('.gg-lightbox-image-container').on('click', e => e.stopPropagation());
    });
    ";
 }
}

new Guru_Tabbed_Gallery_Plugin();
