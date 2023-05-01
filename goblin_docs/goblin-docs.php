<?php

/**
 * Plugin Name: Goblin Docs
 * Description: A simple plugin to load documentation content with AJAX and manage external links
 * Version: 1.0
 * Author: Nathan Jones
 */

function goblin_docs_enqueue_admin_scripts($hook)
{
    if ('goblin_doc_page_goblin-docs-settings' !== $hook) {
        return;
    }

    wp_enqueue_script('jquery');
    wp_enqueue_script('goblin-docs-admin', plugin_dir_url(__FILE__) . 'goblin-docs-admin.js', array('jquery'), '1.0', true);
    wp_localize_script('goblin-docs-admin', 'goblinDocsAdmin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('admin_enqueue_scripts', 'goblin_docs_enqueue_admin_scripts');

function goblin_docs_enqueue_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('goblin-docs', plugin_dir_url(__FILE__) . 'goblin-docs.js', array('jquery'), '1.0', true);
    wp_enqueue_style('goblin-docs', plugin_dir_url(__FILE__) . 'goblin-docs.css');
    wp_localize_script('goblin-docs', 'goblinDocs', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
    // Enqueue custom CSS
    $custom_css = get_option('goblin_docs_custom_css', '');
    if (!empty($custom_css)) {
        wp_add_inline_style('goblin-docs', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'goblin_docs_enqueue_scripts');

// Register Goblin Docs custom post type
function goblin_docs_register_cpt()
{
    $args = array(
        'public' => true,
        'label'  => 'Goblin Docs',
        'supports' => array('title', 'editor')
    );
    register_post_type('goblin_docs', $args);
}
add_action('init', 'goblin_docs_register_cpt');

// Register Goblin Links custom post type
function goblin_links_register_cpt()
{
    $args = array(
        'public' => true,
        'label'  => 'Goblin Links',
        'supports' => array('title')
    );
    register_post_type('goblin_links', $args);
}
add_action('init', 'goblin_links_register_cpt');

// Register Goblin Docs Categories taxonomy
function goblin_docs_register_taxonomy()
{
    $args = array(
        'public' => true,
        'label' => 'Goblin Docs Categories',
        'hierarchical' => true,
    );
    register_taxonomy('goblin_docs_categories', array('goblin_docs', 'goblin_links'), $args);
}
add_action('init', 'goblin_docs_register_taxonomy');

function goblin_docs_ajax_load()
{
    $post_id = intval($_POST['post_id']);

    if ($post_id) {
        $post = get_post($post_id);
        $content = apply_filters('the_content', $post->post_content);

        if (empty($content)) {
            error_log('Goblin Docs: No content found for post ID ' . $post_id);
        } else {
            error_log('Goblin Docs: Content found for post ID ' . $post_id);
            echo $content;
        }
    }
    wp_die();
}
add_action('wp_ajax_goblin_docs_load', 'goblin_docs_ajax_load');
add_action('wp_ajax_nopriv_goblin_docs_load', 'goblin_docs_ajax_load');

function goblin_docs_shortcode($atts)
{
    ob_start();
    $terms = get_terms(array(
        'taxonomy' => 'goblin_docs_categories',
        'orderby' => 'term_order',
        'order' => 'ASC',
        'hide_empty' => false,
    ));

?>
    <div class="goblin-docs-wrap">
        <div class="goblin-docs-menu">
            <?php foreach ($terms as $term) : ?>
                <?php
                $docs = get_posts(array(
                    'post_type' => 'goblin_docs',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'goblin_docs_categories',
                            'field' => 'term_id',
                            'terms' => $term->term_id,
                        ),
                    ),
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                ));
                $links = get_posts(array(
                    'post_type' => 'goblin_links',
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'goblin_docs_categories',
                            'field' => 'term_id',
                            'terms' => $term->term_id,
                        ),
                    ),
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                ));
                ?>

                <h3 class="goblin-docs-category-title"><?php echo $term->name; ?></h3>
                <ul>
                    <?php foreach ($docs as $doc) : ?>
                        <li>
                            <a href="#" class="goblin-docs-link" data-post-id="<?php echo $doc->ID; ?>"><?php echo $doc->post_title; ?></a>
                        </li>
                    <?php endforeach; ?>

                    <?php foreach ($links as $link) : ?>
                        <?php $link_url = get_post_meta($link->ID, '_goblin_link_url', true); ?>
                        <li>
                            <a href="<?php echo esc_url($link_url); ?>" target="_blank"><?php echo $link->post_title; ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>
        <div class="goblin-docs-container">
            <div class="goblin-docs-content"></div>
        </div>
    </div>
<?php

    return ob_get_clean();
}
add_shortcode('goblin_docs', 'goblin_docs_shortcode');

// Add custom meta box for Goblin Links custom field
function goblin_links_add_meta_box()
{
    add_meta_box('goblin_links_meta_box', 'Link URL', 'goblin_links_meta_box_callback', 'goblin_links', 'normal', 'high');
}
add_action('add_meta_boxes', 'goblin_links_add_meta_box');

function goblin_links_meta_box_callback($post)
{
    $value = get_post_meta($post->ID, '_goblin_link_url', true);
?>
    <label for="goblin_link_url">URL:</label>
    <input type="url" id="goblin_link_url" name="goblin_link_url" value="<?php echo esc_attr($value); ?>" style="width: 100%;">
<?php
}

function goblin_links_save_meta_box_data($post_id)
{
    if (!isset($_POST['goblin_link_url'])) {
        return;
    }
    $url = sanitize_text_field($_POST['goblin_link_url']);
    update_post_meta($post_id, '_goblin_link_url', $url);
}
add_action('save_post', 'goblin_links_save_meta_box_data');

function goblin_docs_add_menu()
{
    add_submenu_page(
        'edit.php?post_type=goblin_doc',
        'Goblin Docs Settings',
        'Settings',
        'manage_options',
        'goblin-docs-settings',
        'goblin_docs_render_settings_page'
    );
}
add_action('admin_menu', 'goblin_docs_add_menu');

function goblin_docs_render_settings_page()
{
?>
    <div class="wrap">
        <h2>Goblin Docs Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('goblin_docs_settings_group');
            do_settings_sections('goblin_docs_settings');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

function goblin_docs_register_settings()
{
    register_setting('goblin_docs_settings_group', 'goblin_docs_custom_css');

    add_settings_section(
        'goblin_docs_general_settings',
        'Docs Custom CSS',
        null,
        'goblin_docs_settings'
    );

    add_settings_field(
        'goblin_docs_custom_css',
        'Custom CSS',
        'goblin_docs_custom_css_callback',
        'goblin_docs_settings',
        'goblin_docs_general_settings'
    );
}
add_action('admin_init', 'goblin_docs_register_settings');

function goblin_docs_custom_css_callback()
{
    $custom_css = get_option('goblin_docs_custom_css', '');
    echo '<textarea id="goblin_docs_custom_css" name="goblin_docs_custom_css" rows="10" cols="50">' . esc_textarea($custom_css) . '</textarea>';
}

// Add link AJAX action
function goblin_docs_add_link()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';

    if ($section_id && $title && $url) {
        $link_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => 'goblin_links',
            'post_status' => 'publish',
        ));

        if ($link_id) {
            update_post_meta($link_id, '_goblin_link_url', $url);
            wp_set_object_terms($link_id, $section_id, 'goblin_docs_categories');
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to create link');
        }
    } else {
        wp_send_json_error('Missing required data');
    }
}
add_action('wp_ajax_goblin_docs_add_link', 'goblin_docs_add_link');

// Delete link AJAX action
function goblin_docs_delete_link()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    $link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;

    if ($link_id) {
        if (wp_delete_post($link_id, true)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete link');
        }
    } else {
        wp_send_json_error('Missing link ID');
    }
}
add_action('wp_ajax_goblin_docs_delete_link', 'goblin_docs_delete_link');

// Load links AJAX action
function goblin_docs_load_links()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized user');
    }

    $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;

    if ($section_id) {
        $links = get_posts(array(
            'post_type' => 'goblin_links',
            'tax_query' => array(
                array(
                    'taxonomy' => 'goblin_docs_categories',
                    'field' => 'term_id',
                    'terms' => $section_id,
                ),
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ));

        $link_data = array();

        foreach ($links as $link) {
            $link_data[] = array(
                'id' => $link->ID,
                'title' => $link->post_title,
                'url' => get_post_meta($link->ID, '_goblin_link_url', true),
            );
        }

        wp_send_json_success($link_data);
    } else {
        wp_send_json_error('Missing section ID');
    }
}
add_action('wp_ajax_goblin_docs_load_links', 'goblin_docs_load_links');

function goblin_docs_add_settings_page()
{
    add_options_page(
        'Goblin Docs Custom CSS',
        'Goblin Docs CSS',
        'manage_options',
        'goblin-docs-custom-css',
        'goblin_docs_settings_page_html'
    );
}
add_action('admin_menu', 'goblin_docs_add_settings_page');

function goblin_docs_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('goblin_docs_css_options');
            do_settings_sections('goblin_docs_css');
            submit_button('Save Changes');
            ?>
        </form>
    </div>
<?php
}


function goblin_docs_settings_init()
{
    register_setting('goblin_docs_css_options', 'goblin_docs_custom_css');

    add_settings_section(
        'goblin_docs_css_section',
        'Custom CSS for Goblin Docs',
        'goblin_docs_css_section_callback',
        'goblin_docs_css'
    );

    add_settings_field(
        'goblin_docs_css_field',
        'CSS Code',
        'goblin_docs_css_field_callback',
        'goblin_docs_css',
        'goblin_docs_css_section'
    );
}
add_action('admin_init', 'goblin_docs_settings_init');

function goblin_docs_css_section_callback()
{
    echo 'Edit the custom CSS for Goblin Docs here:';
}

function goblin_docs_css_field_callback()
{
    $css = get_option('goblin_docs_custom_css');
    echo '<textarea name="goblin_docs_custom_css" rows="10" cols="50">' . esc_textarea($css) . '</textarea>';
}


