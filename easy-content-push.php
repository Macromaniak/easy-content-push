<?php
/*
Plugin Name: Easy ContentPush
Description: Push posts with fields and media to the production site on demand.
Version: 1.4.0
Requires at least: 6.3
Requires PHP: 7.4
Author: Anandhu Nadesh
Author URI: https://profiles.wordpress.org/anandhunadesh/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Text Domain: easy-content-push
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EZCPS_VERSION', '1.4.0' );

add_action( 'plugins_loaded', 'ezcps_maybe_upgrade_settings' );
define( 'EZCPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EZCPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once EZCPS_PLUGIN_DIR . 'includes/ezcps-helpers.php';
require_once EZCPS_PLUGIN_DIR . 'includes/class-ezcps-settings.php';
require_once EZCPS_PLUGIN_DIR . 'includes/class-ezcps-push.php';
require_once EZCPS_PLUGIN_DIR . 'includes/class-ezcps-receiver.php';

// Initialize settings.
new EZCPS_Settings();
// Initialize push content (editor control).
new EZCPS_Push();
