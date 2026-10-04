<?php
/**
 * Plugin Name: Events
 * Description: Events calendar. Shortcode: [myevents].
 * Version: 1.2.0
 * Author: Events
 * Text Domain: ekdiloseis
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package Ekdiloseis
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EKDILOSEIS_VERSION', '1.2.0' );
define( 'EKDILOSEIS_FILE', __FILE__ );
define( 'EKDILOSEIS_DIR', plugin_dir_path( __FILE__ ) );
define( 'EKDILOSEIS_URL', plugin_dir_url( __FILE__ ) );

require_once EKDILOSEIS_DIR . 'includes/post-type.php';
require_once EKDILOSEIS_DIR . 'includes/categories.php';
require_once EKDILOSEIS_DIR . 'includes/meta-box.php';
require_once EKDILOSEIS_DIR . 'includes/settings.php';
require_once EKDILOSEIS_DIR . 'includes/documentation.php';
require_once EKDILOSEIS_DIR . 'includes/shortcode.php';

/**
 * Register the post type before flushing rules.
 */
function ekdiloseis_activate() {
	ekdiloseis_register_post_type();
	ekdiloseis_register_taxonomy();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ekdiloseis_activate' );

/**
 * Clean rewrite rules on deactivation.
 */
function ekdiloseis_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ekdiloseis_deactivate' );

/**
 * Check GitHub releases of digitalbtc81-oss/myevents.
 * Slug ekdiloseis matches the installed plugin folder. Release assets named
 * ekdiloseis.zip are the packages WordPress installs into that folder.
 */
require_once EKDILOSEIS_DIR . 'plugin-update-checker/plugin-update-checker.php';

$ekdiloseis_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/digitalbtc81-oss/myevents/',
	EKDILOSEIS_FILE,
	'ekdiloseis'
);
$ekdiloseis_update_checker->setBranch( 'main' );
$ekdiloseis_update_checker->getVcsApi()->enableReleaseAssets( '/^ekdiloseis\.zip$/i' );
