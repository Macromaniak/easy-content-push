<?php
/**
 * Easy StagePush Sender - Helper functions
 * Handles the Utility functions
 */

if (! defined('ABSPATH')) exit;

    function ezcps_prepare_acf_fields_for_transfer($fields)
    {
        foreach ($fields as $key => &$value) {
            if (is_array($value) && isset($value['ID']) && get_post_mime_type($value['ID'])) {
                $file_path = get_post_meta($value['ID'], '_wp_attached_file', true);
                $uploads = wp_upload_dir();
                $value = $file_path ? trailingslashit($uploads['baseurl']) . $file_path : null;
            } elseif (is_array($value) && ! empty($value) && is_numeric(reset($value))) {
                $value = array_map(function ($id) {
                    $post = get_post($id);
                    return $post ? ['post_type' => $post->post_type, 'slug' => $post->post_name] : null;
                }, $value);
            } elseif (is_int($value)) {
                $mime = get_post_mime_type($value);
                if ($mime) {
                    $file_path = get_post_meta($value, '_wp_attached_file', true);
                    $uploads = wp_upload_dir();
                    $value = $file_path ? trailingslashit($uploads['baseurl']) . $file_path : null;
                } else {
                    $post = get_post($value);
                    $value = $post ? ['post_type' => $post->post_type, 'slug' => $post->post_name] : null;
                }
            } elseif (is_array($value)) {
                $value = ezcps_prepare_acf_fields_for_transfer($value);
            }
        }
        return $fields;
    }

    function ezcps_get_original_image_url($image_id)
    {
        $meta = wp_get_attachment_metadata($image_id);
        $uploads = wp_upload_dir();
        return $meta && isset($meta['file']) ? trailingslashit($uploads['baseurl']) . $meta['file'] : null;
    }

    function ezcps_get_post_taxonomies($post_id, $post_type)
    {
        $taxonomies = get_object_taxonomies($post_type);
        $term_data = [];
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy);
            if (! is_wp_error($terms) && ! empty($terms)) {
                $term_data[$taxonomy] = wp_list_pluck($terms, 'slug');
            }
        }
        return $term_data;
    }

    function ezcps_get_yoast_meta($post_id)
    {
        $yoast_keys = [
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_yoast_wpseo_focuskw',
            '_yoast_wpseo_canonical',
            '_yoast_wpseo_opengraph-title',
            '_yoast_wpseo_opengraph-description',
            '_yoast_wpseo_twitter-title',
            '_yoast_wpseo_twitter-description',
        ];
        $meta = [];
        foreach ($yoast_keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if (! empty($value)) {
                $meta[$key] = $value;
            }
        }
        return $meta;
    }

    function ezcps_replace_dev_urls($data, $dev_url, $prod_url)
    {
        foreach ($data as $key => &$value) {
            if (
                is_string($value) &&
                strpos($value, $dev_url) !== false &&
                strpos($value, '/wp-content/uploads/') === false // <-- SKIP uploads/media
            ) {
                $value = str_replace($dev_url, $prod_url, $value);
            } elseif (is_array($value)) {
                $value = ezcps_replace_dev_urls($value, $dev_url, $prod_url);
            }
        }
        return $data;
    }
?>