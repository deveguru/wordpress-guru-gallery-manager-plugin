<?php
/**
 * Plugin Name:       Guru Gallery (Professional Edition)
 * Plugin URI:        https://github.com/deveguru
 * Description:       A professional, stable, and Elementor-compatible gallery plugin with advanced customization, hover effects, and live search.
 * Version:           4.0.0
 * Author:            Alireza Fatemi
 * Author URI:        https://alirezafatemi.ir
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       guru-gallery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'Elementor\Widget_Base' ) ) {
    class Guru_Gallery_Elementor_Widget extends \Elementor\Widget_Base {
        public function get_name() { return 'guru-gallery'; }
        public function get_title() { return __( 'Guru Gallery', 'guru-gallery' ); }
        public function get_icon() { return 'eicon-gallery-grid'; }
        public function get_categories() { return [ 'general' ]; }
        protected function register_controls() {
            $this->start_controls_section( 'content_section', [ 'label' => __( 'Select Gallery', 'guru-gallery' ) ] );
            $galleries = get_posts(['post_type' => 'gg_gallery', 'numberposts' => -1, 'post_status' => 'publish']);
            $options = ['0' => __( '— Select a gallery —', 'guru-gallery' )];
            if ($galleries) { foreach ($galleries as $gallery) { $options[$gallery->ID] = $gallery->post_title; } }
            $this->add_control('gallery_id', ['label' => __( 'Choose Gallery', 'guru-gallery' ), 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $options, 'default' => '0']);
            $this->end_controls_section();
        }
        protected function render() {
            $settings = $this->get_settings_for_display();
            $gallery_id = $settings['gallery_id'];
            if (!empty($gallery_id) && '0' !== $gallery_id) {
                echo do_shortcode('[guru_gallery id="' . esc_attr($gallery_id) . '"]');
            } elseif (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="elementor-alert elementor-alert-warning">' . __( 'Please select a gallery from the widget settings.', 'guru-gallery' ) . '</div>';
            }
        }
    }
}

final class Guru_Gallery_Plugin {

    const VERSION = '4.0.0';
    private $post_type = 'gg_gallery';
    private $meta_key = '_gg_gallery_data';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . $this->post_type, [$this, 'save_gallery_meta']);
        add_shortcode('guru_gallery', [$this, 'render_shortcode']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter("manage_{$this->post_type}_posts_columns", [$this, 'add_shortcode_column']);
        add_action("manage_{$this->post_type}_posts_custom_column", [$this, 'render_shortcode_column'], 10, 2);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_ajax_gg_search_images', [$this, 'ajax_search_images']);
        add_action('wp_ajax_nopriv_gg_search_images', [$this, 'ajax_search_images']);
        add_action('elementor/widgets/register', [$this, 'register_elementor_widget']);
    }

    public function register_post_type() {
        register_post_type($this->post_type, [
            'labels' => ['name' => __('Guru Galleries', 'guru-gallery'), 'singular_name' => __('Gallery', 'guru-gallery'), 'menu_name' => __('Guru Gallery', 'guru-gallery'), 'add_new_item' => __('Add New Gallery', 'guru-gallery'), 'edit_item' => __('Edit Gallery', 'guru-gallery'), 'all_items' => __('All Galleries', 'guru-gallery')],
            'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_position' => 20, 'menu_icon' => 'dashicons-format-gallery', 'supports' => ['title'], 'capability_type' => 'post'
        ]);
    }

    public function add_meta_boxes() {
        add_meta_box('gg_gallery_manager', __('Gallery Management & Settings', 'guru-gallery'), [$this, 'render_meta_box'], $this->post_type, 'normal', 'high');
    }

    public function enqueue_admin_assets($hook) {
        global $post;
        if (('post.php' === $hook || 'post-new.php' === $hook) && isset($post) && $post->post_type === $this->post_type) {
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('jquery-ui-sortable');
            wp_register_style('gg-admin-style', false);
            wp_enqueue_style('gg-admin-style');
            wp_add_inline_style('gg-admin-style', $this->get_admin_css());
            wp_register_script('gg-admin-script', ['jquery', 'wp-color-picker', 'jquery-ui-sortable'], self::VERSION, true);
            wp_enqueue_script('gg-admin-script');
            wp_add_inline_script('gg-admin-script', $this->get_admin_js());
        }
    }

    public function render_meta_box($post) {
        wp_nonce_field('gg_save_gallery_meta', 'gg_meta_nonce');
        $data = get_post_meta($post->ID, $this->meta_key, true);
        $defaults = [ 'images' => [], 'settings' => [ 'columns_desktop' => 4, 'columns_tablet' => 3, 'columns_mobile' => 1, 'gap' => 15, 'aspect_ratio' => 'original', 'border_radius' => 4, 'border_width' => 0, 'border_style' => 'solid', 'border_color' => '#333333', 'hover_effect' => 'zoom', 'overlay_style' => 'gradient', 'textColor' => '#FFFFFF', 'titleColor' => '#111111', 'bgColor' => '#FFFFFF', 'headerColor' => '#F1F1F1', 'shadowColor' => 'rgba(0,0,0,0.1)' ]];
        $data = wp_parse_args($data, $defaults);
        $settings = $data['settings'];
        ?>
        <div id="gg-gallery-app">
            <div class="gg-tabs">
                <a href="#gg-tab-images" class="gg-tab active"><?php _e('Image Management', 'guru-gallery'); ?></a>
                <a href="#gg-tab-settings" class="gg-tab"><?php _e('Layout & Styling', 'guru-gallery'); ?></a>
            </div>
            <div id="gg-tab-images" class="gg-tab-content active">
                <div id="gg-image-list"></div>
                <p class="gg-no-images"><?php _e('No images in this gallery yet. Click to add!', 'guru-gallery'); ?></p>
                <button type="button" class="button button-primary button-large" id="gg-add-images"><?php _e('Add / Edit Images', 'guru-gallery'); ?></button>
            </div>
            <div id="gg-tab-settings" class="gg-tab-content">
                <h3 class="gg-section-title"><?php _e('Responsive Layout', 'guru-gallery'); ?></h3>
                <table class="form-table">
                    <tr><th><label for="gg_cols_desktop"><?php _e('Columns (Desktop)', 'guru-gallery'); ?></label></th><td><input type="number" id="gg_cols_desktop" name="gg_settings[columns_desktop]" value="<?php echo esc_attr($settings['columns_desktop']); ?>" min="1" max="12" /></td></tr>
                    <tr><th><label for="gg_cols_tablet"><?php _e('Columns (Tablet)', 'guru-gallery'); ?></label></th><td><input type="number" id="gg_cols_tablet" name="gg_settings[columns_tablet]" value="<?php echo esc_attr($settings['columns_tablet']); ?>" min="1" max="6" /></td></tr>
                    <tr><th><label for="gg_cols_mobile"><?php _e('Columns (Mobile)', 'guru-gallery'); ?></label></th><td><input type="number" id="gg_cols_mobile" name="gg_settings[columns_mobile]" value="<?php echo esc_attr($settings['columns_mobile']); ?>" min="1" max="4" /></td></tr>
                    <tr><th><label for="gg_gap"><?php _e('Image Spacing (px)', 'guru-gallery'); ?></label></th><td><input type="number" id="gg_gap" name="gg_settings[gap]" value="<?php echo esc_attr($settings['gap']); ?>" min="0" max="100" /></td></tr>
                </table>
                <h3 class="gg-section-title"><?php _e('Image Styling', 'guru-gallery'); ?></h3>
                <table class="form-table">
                    <tr><th><label for="gg_aspect_ratio"><?php _e('Image Aspect Ratio', 'guru-gallery'); ?></label></th><td><select id="gg_aspect_ratio" name="gg_settings[aspect_ratio]">
                        <option value="original" <?php selected($settings['aspect_ratio'], 'original'); ?>><?php _e('Original', 'guru-gallery'); ?></option>
                        <option value="1-1" <?php selected($settings['aspect_ratio'], '1-1'); ?>><?php _e('Square (1:1)', 'guru-gallery'); ?></option>
                        <option value="16-9" <?php selected($settings['aspect_ratio'], '16-9'); ?>><?php _e('Landscape (16:9)', 'guru-gallery'); ?></option>
                        <option value="4-3" <?php selected($settings['aspect_ratio'], '4-3'); ?>><?php _e('Landscape (4:3)', 'guru-gallery'); ?></option>
                    </select></td></tr>
                    <tr><th><label for="gg_border_radius"><?php _e('Border Radius (px)', 'guru-gallery'); ?></label></th><td><input type="number" id="gg_border_radius" name="gg_settings[border_radius]" value="<?php echo esc_attr($settings['border_radius']); ?>" min="0" max="100" /></td></tr>
                     <tr><th><label><?php _e('Image Border', 'guru-gallery'); ?></label></th><td class="gg-multi-input">
                        <input type="number" name="gg_settings[border_width]" value="<?php echo esc_attr($settings['border_width']); ?>" min="0" max="20" title="<?php _e('Border Width (px)', 'guru-gallery'); ?>" />
                        <select name="gg_settings[border_style]">
                            <option value="solid" <?php selected($settings['border_style'], 'solid'); ?>><?php _e('Solid', 'guru-gallery'); ?></option>
                            <option value="dashed" <?php selected($settings['border_style'], 'dashed'); ?>><?php _e('Dashed', 'guru-gallery'); ?></option>
                            <option value="dotted" <?php selected($settings['border_style'], 'dotted'); ?>><?php _e('Dotted', 'guru-gallery'); ?></option>
                        </select>
                        <input type="text" name="gg_settings[border_color]" value="<?php echo esc_attr($settings['border_color']); ?>" class="gg-color-picker">
                    </td></tr>
                </table>
                <h3 class="gg-section-title"><?php _e('Effects & Colors', 'guru-gallery'); ?></h3>
                <table class="form-table">
                    <tr><th><label for="gg_hover_effect"><?php _e('Hover Effect', 'guru-gallery'); ?></label></th><td><select id="gg_hover_effect" name="gg_settings[hover_effect]">
                        <option value="zoom" <?php selected($settings['hover_effect'], 'zoom'); ?>><?php _e('Zoom', 'guru-gallery'); ?></option>
                        <option value="slide" <?php selected($settings['hover_effect'], 'slide'); ?>><?php _e('Caption Slide Up', 'guru-gallery'); ?></option>
                        <option value="fade" <?php selected($settings['hover_effect'], 'fade'); ?>><?php _e('Overlay Fade', 'guru-gallery'); ?></option>
                    </select></td></tr>
                    <tr><th><label for="gg_overlay_style"><?php _e('Caption Overlay Style', 'guru-gallery'); ?></label></th><td><select id="gg_overlay_style" name="gg_settings[overlay_style]">
                        <option value="gradient" <?php selected($settings['overlay_style'], 'gradient'); ?>><?php _e('Bottom Gradient', 'guru-gallery'); ?></option>
                        <option value="full" <?php selected($settings['overlay_style'], 'full'); ?>><?php _e('Full Overlay', 'guru-gallery'); ?></option>
                    </select></td></tr>
                    <tr><th><label><?php _e('Gallery Title Color', 'guru-gallery'); ?></label></th><td><input type="text" name="gg_settings[titleColor]" value="<?php echo esc_attr($settings['titleColor']); ?>" class="gg-color-picker"></td></tr>
                    <tr><th><label><?php _e('Caption & Text Color', 'guru-gallery'); ?></label></th><td><input type="text" name="gg_settings[textColor]" value="<?php echo esc_attr($settings['textColor']); ?>" class="gg-color-picker"></td></tr>
                    <tr><th><label><?php _e('Header Background Color', 'guru-gallery'); ?></label></th><td><input type="text" name="gg_settings[headerColor]" value="<?php echo esc_attr($settings['headerColor']); ?>" class="gg-color-picker"></td></tr>
                    <tr><th><label><?php _e('Gallery Background Color', 'guru-gallery'); ?></label></th><td><input type="text" name="gg_settings[bgColor]" value="<?php echo esc_attr($settings['bgColor']); ?>" class="gg-color-picker"></td></tr>
                    <tr><th><label><?php _e('Image Shadow Color', 'guru-gallery'); ?></label></th><td><input type="text" name="gg_settings[shadowColor]" value="<?php echo esc_attr($settings['shadowColor']); ?>" class="gg-color-picker" data-alpha-enabled="true"></td></tr>
                </table>
            </div>
            <textarea id="gg_gallery_data_input" name="gg_gallery_data" style="display:none;"><?php echo esc_textarea(json_encode($data['images'])); ?></textarea>
        </div>
        <?php
    }

    public function save_gallery_meta($post_id) {
        if (!isset($_POST['gg_meta_nonce']) || !wp_verify_nonce($_POST['gg_meta_nonce'], 'gg_save_gallery_meta') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) return;
        $sanitized_data = [];
        if (isset($_POST['gg_gallery_data'])) {
            $images = json_decode(stripslashes($_POST['gg_gallery_data']), true);
            $sanitized_images = [];
            if (is_array($images)) { foreach ($images as $img) { $sanitized_images[] = ['id' => intval($img['id']), 'url' => esc_url_raw($img['url']), 'thumbnail_url' => esc_url_raw($img['thumbnail_url']), 'caption' => sanitize_text_field($img['caption']), 'description' => sanitize_textarea_field($img['description'])]; } }
            $sanitized_data['images'] = $sanitized_images;
        }
        if (isset($_POST['gg_settings']) && is_array($_POST['gg_settings'])) {
            $s = $_POST['gg_settings'];
            $sanitized_data['settings'] = ['columns_desktop' => intval($s['columns_desktop']), 'columns_tablet' => intval($s['columns_tablet']), 'columns_mobile' => intval($s['columns_mobile']), 'gap' => intval($s['gap']), 'aspect_ratio' => sanitize_key($s['aspect_ratio']), 'border_radius' => intval($s['border_radius']), 'border_width' => intval($s['border_width']), 'border_style' => sanitize_key($s['border_style']), 'border_color' => sanitize_hex_color($s['border_color']), 'hover_effect' => sanitize_key($s['hover_effect']), 'overlay_style' => sanitize_key($s['overlay_style']), 'textColor' => sanitize_hex_color($s['textColor']), 'titleColor' => sanitize_hex_color($s['titleColor']), 'bgColor' => sanitize_hex_color($s['bgColor']), 'headerColor' => sanitize_hex_color($s['headerColor']), 'shadowColor' => $this->sanitize_rgba_color($s['shadowColor'])];
        }
        update_post_meta($post_id, $this->meta_key, $sanitized_data);
    }

    public function render_shortcode($atts) {
        $id = intval($atts['id'] ?? 0);
        if ($id <= 0 || get_post_type($id) !== $this->post_type) return '';
        $title = get_the_title($id);
        $data = get_post_meta($id, $this->meta_key, true);
        $s = $data['settings'] ?? [];
        $images = $data['images'] ?? [];
        $aspect_ratio_css = 'original' !== ($s['aspect_ratio'] ?? 'original') ? 'aspect-ratio:' . str_replace('-', '/', $s['aspect_ratio']) . ';' : '';
        ob_start();
        echo '<div class="gg-gallery-wrapper" id="gg-gallery-'.$id.'" data-gallery-id="'.$id.'" data-hover-effect="'.esc_attr($s['hover_effect']??'zoom').'" data-overlay-style="'.esc_attr($s['overlay_style']??'gradient').'" style="--gg-bg-color:'.esc_attr($s['bgColor']??'#fff').';--gg-gap:'.intval($s['gap']??15).'px;--gg-border-radius:'.intval($s['border_radius']??4).'px;--gg-border:'.intval($s['border_width']??0).'px '.esc_attr($s['border_style']??'solid').' '.esc_attr($s['border_color']??'#333').';--gg-shadow-color:'.esc_attr($s['shadowColor']??'rgba(0,0,0,0.1)').';--gg-cols-desktop:'.intval($s['columns_desktop']??4).';--gg-cols-tablet:'.intval($s['columns_tablet']??3).';--gg-cols-mobile:'.intval($s['columns_mobile']??1).';"><div class="gg-gallery-header" style="background:'.esc_attr($s['headerColor']??'#f1f1f1').';"><h2 class="gg-gallery-title" style="color:'.esc_attr($s['titleColor']??'#111').';">'.esc_html($title).'</h2><div class="gg-search-wrapper"><input type="search" class="gg-gallery-search" placeholder="'.__('Search image captions...', 'guru-gallery').'"/></div></div><div class="gg-gallery-items-container" style="'.$aspect_ratio_css.'">';
        if (!empty($images)) { foreach ($images as $img) echo $this->get_frontend_image_html($img, $s); } else { echo '<p class="gg-no-images-frontend">'.__('No images in this gallery.', 'guru-gallery').'</p>'; }
        echo '</div><div class="gg-loading-overlay"><span>'.__('Searching...', 'guru-gallery').'</span></div></div>';
        return ob_get_clean();
    }

    public function ajax_search_images() {
        check_ajax_referer('gg_frontend_nonce', 'nonce');
        $id = intval($_POST['gallery_id'] ?? 0);
        $term = sanitize_text_field($_POST['search_term'] ?? '');
        if ($id <= 0) wp_send_json_error();
        $data = get_post_meta($id, $this->meta_key, true);
        $s = $data['settings'] ?? [];
        $images = $data['images'] ?? [];
        $filtered = empty($term) ? $images : array_filter($images, function($img) use ($term) { return stripos($img['caption'], $term) !== false; });
        ob_start();
        if (!empty($filtered)) { foreach ($filtered as $img) echo $this->get_frontend_image_html($img, $s); } else { echo '<p class="gg-no-images-frontend">' . __('No images found matching your search.', 'guru-gallery') . '</p>'; }
        wp_send_json_success(['html' => ob_get_clean()]);
    }
    
    public function enqueue_frontend_assets() {
        wp_register_style('gg-frontend-styles', false);
        wp_enqueue_style('gg-frontend-styles');
        wp_add_inline_style('gg-frontend-styles', $this->get_frontend_css());
        wp_register_script('gg-frontend-scripts', ['jquery'], self::VERSION, true);
        wp_enqueue_script('gg-frontend-scripts');
        wp_localize_script('gg-frontend-scripts', 'gg_frontend_ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('gg_frontend_nonce')]);
        wp_add_inline_script('gg-frontend-scripts', $this->get_frontend_js());
    }

    public function add_shortcode_column($c) { $c['shortcode'] = __('Shortcode', 'guru-gallery'); return $c; }
    public function render_shortcode_column($col, $id) { if ($col === 'shortcode') echo '<input type="text" value="[guru_gallery id=\''.$id.'\']" readonly onfocus="this.select();" style="width:100%;text-align:left;direction:ltr;">'; }
    public function register_elementor_widget($widgets_manager) { if (class_exists('Guru_Gallery_Elementor_Widget')) $widgets_manager->register(new Guru_Gallery_Elementor_Widget()); }

    private function get_frontend_image_html($img, $s) {
        ob_start();
        echo '<div class="gg-gallery-item"><img src="'.esc_url($img['url']).'" alt="'.esc_attr($img['caption']).'" loading="lazy"/><div class="gg-item-overlay" style="color:'.esc_attr($s['textColor']??'#fff').';"><div class="gg-item-caption">'.esc_html($img['caption']).'</div>';
        if (!empty($img['description'])) echo '<div class="gg-item-description">'.wp_kses_post($img['description']).'</div>';
        echo '</div></div>';
        return ob_get_clean();
    }
    
    private function sanitize_rgba_color($c) { if (empty($c)) return ''; if (strpos($c, 'rgba') === false) return sanitize_hex_color($c); sscanf($c, 'rgba(%d,%d,%d,%f)', $r, $g, $b, $a); return sprintf('rgba(%d,%d,%d,%s)', (int)$r, (int)$g, (int)$b, (float)$a); }
    private function get_admin_css(){return"#gg-gallery-app{background:#fff;border:1px solid #ccd0d4;padding:1rem;margin-top:1rem}#gg-gallery-app .gg-tabs{border-bottom:1px solid #ccd0d4;margin-bottom:1rem}#gg-gallery-app .gg-tab{display:inline-block;padding:8px 16px;text-decoration:none;color:#555;border:1px solid transparent;margin-bottom:-1px;font-weight:600}#gg-gallery-app .gg-tab.active{background:#f0f0f1;border-color:#ccd0d4 #ccd0d4 #f0f0f1}#gg-gallery-app .gg-tab-content{display:none;padding-top:1rem}#gg-gallery-app .gg-tab-content.active{display:block}#gg-gallery-app #gg-image-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem}#gg-gallery-app .gg-image-item{position:relative;padding:8px;border:1px solid #ddd;background:#f9f9f9;cursor:move;border-radius:4px}#gg-gallery-app .gg-image-item .gg-thumbnail{width:100%;height:auto;display:block;margin-bottom:8px;border-radius:2px}#gg-gallery-app .gg-image-item .gg-remove-image{position:absolute;top:2px;right:2px;width:24px;height:24px;padding:0;line-height:22px;border-radius:50%;background:#d63638;color:#fff;border:none;cursor:pointer;font-weight:700;font-size:18px}#gg-gallery-app .gg-image-item input,#gg-gallery-app .gg-image-item textarea{margin-top:5px;width:100%}#gg-gallery-app .gg-no-images{color:#777;font-style:italic;padding:2rem;border:2px dashed #ddd;text-align:center;margin:1rem 0}#gg-gallery-app .gg-section-title{border-bottom:1px solid #eee;padding-bottom:10px;margin:20px 0 10px;font-size:1.2em}#gg-gallery-app .gg-multi-input{display:flex;gap:10px;align-items:center}#gg-gallery-app .gg-multi-input input[type=number]{width:70px}";}
    private function get_admin_js(){return"jQuery(function(d){let a,b=[];function f(){d('#gg-image-list').empty();b.forEach(function(e){const t=`<div class=\"gg-image-item\" data-id=\"\${e.id}\" data-url=\"\${e.url}\"><img src=\"\${e.thumbnail_url}\" class=\"gg-thumbnail\"/><div class=\"gg-image-details\"><input type=\"text\" class=\"gg-caption widefat\" placeholder=\"Caption\" value=\"\${e.caption}\"/><textarea class=\"gg-description widefat\" placeholder=\"Description\">\${e.description}</textarea></div><button type=\"button\" class=\"gg-remove-image\">&times;</button></div>`;d('#gg-image-list').append(t)});b.length>0?d('.gg-no-images').hide():d('.gg-no-images').show()}function g(){d('#gg_gallery_data_input').val(JSON.stringify(b))}d(function(){b=JSON.parse(d('#gg_gallery_data_input').val()||'[]');f();d('#gg-add-images').on('click',function(e){e.preventDefault();a&&a.open()||(a=wp.media({title:'Select or Upload Images',button:{text:'Use these images'},multiple:'add'}),a.on('select',function(){let e=a.state().get('selection').toJSON();e.forEach(function(e){b.some(t=>t.id==e.id)||b.push({id:e.id,url:e.sizes.large?e.sizes.large.url:e.url,thumbnail_url:e.sizes.thumbnail?e.sizes.thumbnail.url:e.url,caption:e.caption||e.title,description:e.description||''})});f();g()}),a.open())});d('#gg-gallery-app .gg-tab').on('click',function(e){e.preventDefault();const t=d(this).attr('href');d('#gg-gallery-app .gg-tab, #gg-gallery-app .gg-tab-content').removeClass('active');d(this).addClass('active');d(t).addClass('active')});d('#gg-image-list').sortable({update:function(){b=[];d('#gg-image-list .gg-image-item').each(function(){const e=d(this);b.push({id:e.data('id'),url:e.data('url'),thumbnail_url:e.find('img').attr('src'),caption:e.find('.gg-caption').val(),description:e.find('.gg-description').val()})});g()}}).on('click','.gg-remove-image',function(){const t=d(this).closest('.gg-image-item').data('id');b=b.filter(e=>e.id!==t);f();g()}).on('input change','.gg-caption, .gg-description',function(){const t=d(this).closest('.gg-image-item').data('id'),c=d(this).is('.gg-caption')?'caption':'description',n=d(this).val();b=b.map(e=>e.id===t?{...e,[c]:n}:e);g()});d('.gg-color-picker').wpColorPicker()})});";}
    private function get_frontend_css(){return".gg-gallery-wrapper{position:relative;padding:20px;background:var(--gg-bg-color);direction:rtl}.gg-gallery-header{display:flex;justify-content:space-between;align-items:center;padding:15px;margin-bottom:var(--gg-gap);border-radius:var(--gg-border-radius)}.gg-gallery-title{margin:0;font-size:1.5em}.gg-gallery-search{padding:8px 12px;border:1px solid #ccc;border-radius:var(--gg-border-radius);min-width:250px}.gg-gallery-items-container{display:grid;gap:var(--gg-gap);grid-template-columns:repeat(var(--gg-cols-desktop),1fr)}.gg-gallery-items-container[style*='aspect-ratio'] .gg-gallery-item img{width:100%;height:100%;object-fit:cover}@media(max-width:1024px){.gg-gallery-items-container{grid-template-columns:repeat(var(--gg-cols-tablet),1fr)}}@media(max-width:767px){.gg-gallery-header{flex-direction:column;gap:10px}.gg-gallery-items-container{grid-template-columns:repeat(var(--gg-cols-mobile),1fr)}}.gg-gallery-item{position:relative;overflow:hidden;box-shadow:0 2px 8px var(--gg-shadow-color);border-radius:var(--gg-border-radius);border:var(--gg-border)}.gg-gallery-item img{width:100%;height:auto;display:block;transition:transform .4s ease, filter .4s ease}.gg-item-overlay{position:absolute;bottom:0;left:0;right:0;padding:20px 15px;transition:opacity .4s ease, transform .4s ease}.gg-item-caption{font-weight:700;font-size:1.1em}.gg-item-description{font-size:.9em;margin-top:5px;opacity:.8}.gg-gallery-wrapper[data-overlay-style=gradient] .gg-item-overlay{background:linear-gradient(0deg,rgba(0,0,0,.7) 0,transparent 100%)}.gg-gallery-wrapper[data-overlay-style=full] .gg-item-overlay{top:0;background:rgba(0,0,0,0.5)}.gg-gallery-wrapper[data-hover-effect=zoom] .gg-gallery-item:hover img{transform:scale(1.1)}.gg-gallery-wrapper[data-hover-effect=slide] .gg-item-overlay{transform:translateY(100%)}.gg-gallery-wrapper[data-hover-effect=slide] .gg-gallery-item:hover .gg-item-overlay{transform:translateY(0)}.gg-gallery-wrapper[data-hover-effect=fade] .gg-item-overlay{opacity:0}.gg-gallery-wrapper[data-hover-effect=fade] .gg-gallery-item:hover .gg-item-overlay{opacity:1}.gg-gallery-wrapper[data-hover-effect=fade] .gg-gallery-item:hover img{filter:brightness(.8)}.gg-loading-overlay{position:absolute;inset:0;background:rgba(255,255,255,.8);display:none;align-items:center;justify-content:center;z-index:10;font-size:1.2em;font-weight:700}.gg-no-images-frontend{grid-column:1/-1;text-align:center;padding:2rem}";}
    private function get_frontend_js(){return"jQuery(document).ready(function(t){let e;t('.gg-gallery-search').on('keyup',function(){clearTimeout(e);const o=t(this),a=o.closest('.gg-gallery-wrapper'),n=a.find('.gg-gallery-items-container'),i=a.find('.gg-loading-overlay');e=setTimeout(function(){i.css('display','flex'),t.ajax({url:gg_frontend_ajax.ajax_url,type:'POST',data:{action:'gg_search_images',nonce:gg_frontend_ajax.nonce,gallery_id:a.data('gallery-id'),search_term:o.val()},success:function(t){t.success&&n.html(t.data.html)},complete:function(){i.hide()}})},300)})});";}
}

new Guru_Gallery_Plugin();
