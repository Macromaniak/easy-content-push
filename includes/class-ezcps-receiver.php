<?php
if (!defined('ABSPATH')) exit;

class EZCPS_Importer
{

    public static function register()
    {
        add_action('rest_api_init', [__CLASS__, 'ezcps_register_routes']);
    }

    public static function ezcps_register_routes()
    {
        register_rest_route('ezcps-sync/v1', '/import-post', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'ezcps_import_post_from_dev'],
            'permission_callback' => [__CLASS__, 'ezcps_check_request_origin'],
        ]);
    }

    public static function ezcps_check_request_origin($request)
    {
        $source_url = rtrim((string) EZCPS_Settings::get_option('dev_url'), '/');
        if (empty($source_url)) {
            return false;
        }

        $origin = rtrim((string) $request->get_header('X-EZCPS-Origin'), '/');
        if (empty($origin) || $origin !== $source_url) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('EZCPS: Unauthorized or missing origin header.');
            }
            return false;
        }

        return true;
    }

    public static function ezcps_import_post_from_dev($request)
    {
        $params = $request->get_json_params();
        if (empty($params['post']) || ! is_array($params['post'])) {
            return new WP_REST_Response(['error' => 'Invalid post data.'], 400);
        }

        $post_data          = self::ezcps_sanitize_post_data($params['post']);
        $acf_data           = is_array($params['acf_fields'] ?? null) ? $params['acf_fields'] : [];
        $featured_image_url = ! empty($params['post']['featured_image_url']) ? esc_url_raw($params['post']['featured_image_url']) : null;
        $page_template      = ! empty($params['post']['_wp_page_template']) ? sanitize_text_field($params['post']['_wp_page_template']) : null;
        $page_path          = ! empty($params['post']['path']) ? sanitize_text_field($params['post']['path']) : null;

        if (! empty($params['post']['parent_lookup']) && is_array($params['post']['parent_lookup'])) {
            $parent_path = sanitize_text_field($params['post']['parent_lookup']['path'] ?? '');
            $parent_type = sanitize_key($params['post']['parent_lookup']['post_type'] ?? '');

            if ($parent_path && $parent_type) {
                $parent = get_page_by_path($parent_path, OBJECT, $parent_type);
                if ($parent) {
                    $post_data['post_parent'] = $parent->ID;
                }
            }
        }

        if ($page_path && ! empty($post_data['post_type'])) {
            $existing = get_page_by_path($page_path, OBJECT, $post_data['post_type']);
            if ($existing) {
                $post_data['ID'] = $existing->ID;
            }
        }

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return new WP_REST_Response(['error' => $post_id->get_error_message()], 500);
        }

        if (! $post_id) {
            return new WP_REST_Response(['error' => 'Post creation failed.'], 500);
        }

        if ($page_template) {
            update_post_meta($post_id, '_wp_page_template', $page_template);
        }

        if ($featured_image_url) {
            $existing_id = self::ezcps_find_existing_attachment_by_url($featured_image_url);
            if (! $existing_id) {
                $existing_id = self::ezcps_sideload_file($featured_image_url, $post_id);
            }
            if ($existing_id && ! is_wp_error($existing_id)) {
                set_post_thumbnail($post_id, $existing_id);
            }
        }

        if (! empty($acf_data) && function_exists('update_field')) {
            $acf_data = self::ezcps_handle_acf_media($acf_data, $post_id);
            foreach ($acf_data as $key => $value) {
                update_field($key, $value, $post_id);
            }
        }

        $taxonomies = is_array($params['taxonomies'] ?? null) ? $params['taxonomies'] : [];
        foreach ($taxonomies as $taxonomy => $slugs) {
            $taxonomy = sanitize_key($taxonomy);
            if (! taxonomy_exists($taxonomy) || ! is_array($slugs)) {
                continue;
            }

            $term_ids = [];
            foreach ($slugs as $slug) {
                $slug = sanitize_title($slug);
                if (! $slug) {
                    continue;
                }

                $term = get_term_by('slug', $slug, $taxonomy);
                if (! $term) {
                    $term = wp_insert_term(str_replace('-', ' ', $slug), $taxonomy, ['slug' => $slug]);
                    if (is_wp_error($term)) {
                        continue;
                    }
                    $term_ids[] = $term['term_id'];
                } else {
                    $term_ids[] = $term->term_id;
                }
            }

            if (! empty($term_ids)) {
                wp_set_object_terms($post_id, $term_ids, $taxonomy);
            }
        }

        $yoast_meta = is_array($params['yoast_meta'] ?? null) ? $params['yoast_meta'] : [];
        $allowed_yoast_keys = [
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_yoast_wpseo_focuskw',
            '_yoast_wpseo_canonical',
            '_yoast_wpseo_opengraph-title',
            '_yoast_wpseo_opengraph-description',
            '_yoast_wpseo_twitter-title',
            '_yoast_wpseo_twitter-description',
        ];
        foreach ($yoast_meta as $meta_key => $meta_value) {
            if (in_array($meta_key, $allowed_yoast_keys, true)) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($meta_value));
            }
        }

        return new WP_REST_Response(['post_id' => $post_id], 200);
    }

    private static function ezcps_sanitize_post_data($post_data)
    {
        $sanitized = [
            'post_title'   => sanitize_text_field($post_data['post_title'] ?? ''),
            'post_content' => wp_kses_post($post_data['post_content'] ?? ''),
            'post_status'  => sanitize_key($post_data['post_status'] ?? 'draft'),
            'post_name'    => sanitize_title($post_data['post_name'] ?? ''),
            'post_type'    => sanitize_key($post_data['post_type'] ?? 'post'),
            'post_date'    => $post_data['post_date'] ?? current_time('mysql'),
            'post_date_gmt'=> $post_data['post_date_gmt'] ?? current_time('mysql', 1),
        ];

        if (! empty($post_data['ID'])) {
            $sanitized['ID'] = absint($post_data['ID']);
        }

        if (! empty($post_data['post_parent'])) {
            $sanitized['post_parent'] = absint($post_data['post_parent']);
        }

        return $sanitized;
    }

    public static function ezcps_handle_acf_media($fields, $post_id)
    {
        foreach ($fields as $key => &$value) {
            if (is_array($value)) {
                if (!empty($value) && isset($value[0]['post_type'], $value[0]['slug'])) {
                    $value = array_map([__CLASS__, 'ezcps_resolve_post_reference'], $value);
                } elseif (isset($value['post_type'], $value['slug'])) {
                    $resolved = self::ezcps_resolve_post_reference($value);
                    $value = $resolved ?? null;
                } else {
                    $value = self::ezcps_handle_acf_media($value, $post_id);
                }
            } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) && strpos($value, '/uploads/') !== false) {
                $existing_id = self::ezcps_find_existing_attachment_by_url($value);
                if ($existing_id) {
                    $value = $existing_id;
                } else {
                    $value = self::ezcps_sideload_file($value, $post_id);
                }
            }
        }
        return $fields;
    }

    public static function ezcps_resolve_post_reference($ref)
    {
        if (is_array($ref) && isset($ref['post_type'], $ref['slug'])) {
            $post = get_page_by_path($ref['slug'], OBJECT, $ref['post_type']);
            return $post ? $post->ID : null;
        }
        return null;
    }

    public static function ezcps_find_existing_attachment_by_url($url)
    {
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $args = [
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'meta_query'  => [
                [
                    'key'     => '_wp_attached_file',
                    'value'   => $filename,
                    'compare' => 'LIKE'
                ]
            ]
        ];
        $query = new WP_Query($args);
        foreach ($query->posts as $attachment) {
            $file = get_post_meta($attachment->ID, '_wp_attached_file', true);
            if (basename($file) === $filename) {
                return $attachment->ID;
            }
        }
        return false;
    }

    public static function ezcps_sideload_file($url, $post_id)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($url);
        if (is_wp_error($tmp)) return $tmp;

        $file_array = [
            'name'     => basename($url),
            'tmp_name' => $tmp,
        ];

        add_filter('upload_mimes', [__CLASS__, 'ezcps_allow_svg_mime']);
        $id = media_handle_sideload($file_array, $post_id);
        remove_filter('upload_mimes', [__CLASS__, 'ezcps_allow_svg_mime']);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        return $id;
    }

    public static function ezcps_allow_svg_mime($mimes)
    {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }
}

EZCPS_Importer::register();
