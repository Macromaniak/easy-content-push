<?php
/**
 * Easy ContentPush Sender - Helper functions
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

/**
 * Normalize a site URL for comparison (scheme, host, path; no trailing slash).
 *
 * @param string $url Raw URL.
 * @return string Normalized URL or empty string if invalid.
 */
function ezcps_normalize_site_url($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    $url = esc_url_raw($url);
    if ($url === '') {
        return '';
    }

    $parts = wp_parse_url($url);
    if (empty($parts['host'])) {
        return untrailingslashit($url);
    }

    $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : 'https';
    $host   = strtolower($parts['host']);
    $port   = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    $path   = isset($parts['path']) ? untrailingslashit($parts['path']) : '';

    return $scheme . '://' . $host . $port . $path;
}

/**
 * JSON body used for connection test (ping) requests.
 *
 * @return string
 */
function ezcps_get_ping_request_body()
{
    return wp_json_encode(['ezcps_action' => 'ping']);
}

/**
 * Build request headers for a signed sync request.
 *
 * @param string $body    Raw JSON body.
 * @param string $secret  Shared sync key.
 * @return array<string, string>
 */
function ezcps_build_sync_request_headers($body, $secret)
{
    $origin = ezcps_normalize_site_url(get_site_url());

    return [
        'Content-Type'     => 'application/json',
        'X-EZCPS-Origin'   => $origin,
        'X-EZCPS-Signature' => ezcps_compute_sync_signature($body, $secret),
    ];
}

/**
 * Compute HMAC-SHA256 signature for a sync request body.
 *
 * @param string $body   Raw request body.
 * @param string $secret Shared sync key.
 * @return string Header value (sha256=...).
 */
function ezcps_compute_sync_signature($body, $secret)
{
    return 'sha256=' . hash_hmac('sha256', $body, $secret);
}

/**
 * Verify sync request signature header against body and secret.
 *
 * @param string $body              Raw request body.
 * @param string $signature_header  X-EZCPS-Signature header value.
 * @param string $secret            Shared sync key.
 * @return bool
 */
function ezcps_verify_sync_signature($body, $signature_header, $secret)
{
    if ($secret === '' || $signature_header === '') {
        return false;
    }

    $expected = ezcps_compute_sync_signature($body, $secret);
    $provided = trim((string) $signature_header);

    if (strpos($provided, 'sha256=') !== 0) {
        return false;
    }

    return hash_equals($expected, $provided);
}

/**
 * Generate a cryptographically secure sync key.
 *
 * @return string
 */
function ezcps_generate_sync_key()
{
    if (function_exists('wp_generate_password')) {
        return wp_generate_password(64, true, true);
    }

    return bin2hex(random_bytes(32));
}

/**
 * Upgrade stored settings when the plugin is updated.
 */
function ezcps_maybe_upgrade_settings()
{
    $options = get_option('ezcps_settings', []);
    if (! is_array($options)) {
        $options = [];
    }

    $changed = false;

    if (empty($options['allowed_origins']) && ! empty($options['dev_url'])) {
        $legacy = ezcps_normalize_site_url($options['dev_url']);
        if ($legacy !== '') {
            $options['allowed_origins'] = [$legacy];
            $changed                    = true;
        }
    }

    if (! isset($options['allowed_origins']) || ! is_array($options['allowed_origins'])) {
        $options['allowed_origins'] = [];
        $changed                    = true;
    }

    if ($changed) {
        update_option('ezcps_settings', $options, false);
    }
}