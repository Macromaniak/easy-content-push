<?php

/**
 * Easy ContentPush Sender - Push to Live Logic
 * Handles the Push to Live button and AJAX
 */

if (! defined('ABSPATH')) exit;

class EZCPS_Push
{

    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'ezcps_enqueue_block_editor_assets']);
        add_action('admin_enqueue_scripts', [$this, 'ezcps_enqueue_classic_admin_assets']);
        add_action('add_meta_boxes', array($this, 'ezcps_register_meta_box'));
        add_action('wp_ajax_ezcps_push_to_live', [$this, 'ezcps_ajax_push_to_live']);
    }

    /**
     * Enqueue block editor sidebar assets (loads inside the editor iframe on WP 7.0+).
     */
    public function ezcps_enqueue_block_editor_assets()
    {
        $post_types = EZCPS_Settings::get_option('post_types', []);
        if (empty($post_types)) {
            return;
        }

        wp_enqueue_script(
            'ezcps-block-editor',
            EZCPS_PLUGIN_URL . 'assets/js/ezcps-block-editor.js',
            ['wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n'],
            EZCPS_VERSION,
            true
        );

        wp_localize_script(
            'ezcps-block-editor',
            'ezcpsBlockEditor',
            [
                'ajaxUrl'    => admin_url('admin-ajax.php'),
                'nonce'      => wp_create_nonce('ezcps_push_to_live'),
                'postTypes'  => array_values($post_types),
            ]
        );
    }

    /**
     * Enqueue classic editor assets (non-block editor screens only).
     */
    public function ezcps_enqueue_classic_admin_assets($hook)
    {
        global $post;
        if (! in_array($hook, ['post.php', 'post-new.php'], true) || ! isset($post->post_type)) {
            return;
        }

        $post_types = EZCPS_Settings::get_option('post_types', []);
        if (! in_array($post->post_type, $post_types, true)) {
            return;
        }

        if (use_block_editor_for_post_type($post->post_type)) {
            return;
        }

        wp_enqueue_script(
            'ezcps-admin-js',
            EZCPS_PLUGIN_URL . 'assets/js/ezcps-admin.js',
            ['jquery'],
            EZCPS_VERSION,
            true
        );
        wp_localize_script(
            'ezcps-admin-js',
            'ezcps_ajax_object',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('ezcps_push_to_live'),
                'post_id'  => $post->ID,
            ]
        );
    }

    public function ezcps_register_meta_box()
    {
        $post_types = EZCPS_Settings::get_option('post_types', []);
        foreach ($post_types as $post_type) {
            add_meta_box(
                'ezcps-push-to-live',
                __('Easy ContentPush', 'easy-content-push'),
                array($this, 'ezcps_add_push_button'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function ezcps_add_push_button()
    {
        global $post;
        if (! $post) {
            return;
        }

        $post_types = EZCPS_Settings::get_option('post_types', []);
        if (! in_array($post->post_type, $post_types, true)) {
            return;
        }

        $nonce    = wp_create_nonce('ezcps_push_to_live');
        $ajax_url = admin_url('admin-ajax.php');
        $post_id  = $post->ID;

        echo '<div id="ezcps-push-to-live-container" style="margin-top:15px;">';
        echo '<button type="button" class="button button-primary" id="ezcps-push-to-live-btn">' . esc_html__('Push to Live', 'easy-content-push') . '</button>';
        echo '<p id="ezcps-push-to-live-msg"></p>';
        echo '</div>';

        // Inline script runs in the meta box DOM context (works inside the editor iframe).
        ?>
        <script>
        (function () {
            var btn = document.getElementById('ezcps-push-to-live-btn');
            var msg = document.getElementById('ezcps-push-to-live-msg');
            if (!btn || btn.dataset.ezcpsBound) {
                return;
            }
            btn.dataset.ezcpsBound = '1';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                btn.disabled = true;
                msg.textContent = '<?php echo esc_js(__('Pushing...', 'easy-content-push')); ?>';
                msg.style.color = '';
                var formData = new FormData();
                formData.append('action', 'ezcps_push_to_live');
                formData.append('security', '<?php echo esc_js($nonce); ?>');
                formData.append('post_id', '<?php echo esc_js((string) $post_id); ?>');
                fetch('<?php echo esc_url($ajax_url); ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                    .then(function (response) { return response.json(); })
                    .then(function (response) {
                        btn.disabled = false;
                        if (response.success) {
                            msg.style.color = 'green';
                            msg.textContent = response.data.message;
                        } else {
                            msg.style.color = 'red';
                            msg.textContent = response.data && response.data.message
                                ? response.data.message
                                : '<?php echo esc_js(__('Something went wrong.', 'easy-content-push')); ?>';
                        }
                    })
                    .catch(function (error) {
                        btn.disabled = false;
                        msg.style.color = 'red';
                        msg.textContent = '<?php echo esc_js(__('Error:', 'easy-content-push')); ?> ' + error.message;
                    });
            });
        })();
        </script>
        <?php
    }

    public function ezcps_ajax_push_to_live()
    {
        check_ajax_referer('ezcps_push_to_live', 'security');
        if (! current_user_can('edit_post', intval($_POST['post_id']))) {
            wp_send_json_error(['message' => __('You do not have permission.', 'easy-content-push')]);
        }
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        if (! $post) {
            wp_send_json_error(['message' => __('Post not found.', 'easy-content-push')]);
        }

        $dev_url  = EZCPS_Settings::get_option('dev_url');
        if (empty($dev_url)) {
            $dev_url = get_site_url();
        }
        $prod_url = EZCPS_Settings::get_option('prod_url');

        if (empty($prod_url)) {
            wp_send_json_error(['message' => __('Production URL is not set.', 'easy-content-push')]);
        }

        $acf_fields = function_exists('get_fields') ? ezcps_prepare_acf_fields_for_transfer(get_fields($post_id)) : [];
        $acf_fields = ezcps_replace_dev_urls($acf_fields, $dev_url, $prod_url);

        $thumbnail_id = get_post_thumbnail_id($post_id);
        $thumbnail_url = $thumbnail_id ? ezcps_get_original_image_url($thumbnail_id) : null;
        $template = get_post_meta($post_id, '_wp_page_template', true);

        $parent_id = $post->post_parent;
        $parent = $parent_id ? get_post($parent_id) : null;
        $parent_data = $parent ? ['path' => get_page_uri($parent), 'post_type' => $parent->post_type] : [];

        $post_data = [
            'post_title'        => $post->post_title,
            'post_content'      => $post->post_content,
            'post_status'       => $post->post_status,
            'post_name'         => $post->post_name,
            'path'              => get_page_uri($post_id),
            'post_type'         => $post->post_type,
            '_wp_page_template' => $template,
            'parent_lookup'     => $parent_data,
            'featured_image_url' => $thumbnail_url,
            'post_date'         => $post->post_date,
            'post_date_gmt'     => $post->post_date_gmt,
        ];

        $payload = [
            'post'       => $post_data,
            'acf_fields' => $acf_fields,
            'taxonomies' => ezcps_get_post_taxonomies($post_id, $post->post_type),
            'yoast_meta' => ezcps_get_yoast_meta($post_id),
        ];

        $response = wp_remote_post(
            trailingslashit($prod_url) . 'wp-json/ezcps-sync/v1/import-post',
            [
                'headers' => ['Content-Type' => 'application/json', 'X-EZCPS-Origin' => get_site_url()],
                'body'    => wp_json_encode($payload),
                'timeout' => 20,
            ]
        );

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EZCPS push failed: ' . $response->get_error_message());
            }
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (! isset($data['post_id'])) {
            if (isset($data['message'])) {
                wp_send_json_error(['message' => $data['message']]);
            }
            wp_send_json_error(['message' => __('Something went wrong!', 'easy-content-push')]);
        }

        wp_send_json_success(['message' => __('Successfully pushed to live.', 'easy-content-push')]);
    }
}
