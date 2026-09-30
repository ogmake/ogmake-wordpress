<?php
/**
 * Plugin Name:       ogmake OG Images
 * Plugin URI:        https://ogmake.com
 * Description:       Automatic og:image and twitter:image for posts, pages and custom post types, rendered by ogmake from the post title, excerpt and author.
 * Version:           0.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            ogmake
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ogmake-og-images
 *
 * @package Ogmake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OGMAKE_VERSION', '0.1.0' );
define( 'OGMAKE_DIR', plugin_dir_path( __FILE__ ) );

require_once OGMAKE_DIR . 'includes/class-ogmake-signing.php';
require_once OGMAKE_DIR . 'includes/class-ogmake-settings.php';
require_once OGMAKE_DIR . 'includes/class-ogmake-url.php';
require_once OGMAKE_DIR . 'includes/class-ogmake-frontend.php';
require_once OGMAKE_DIR . 'includes/class-ogmake-metabox.php';

/**
 * Bump the cache generation so every cached URL is regenerated.
 *
 * @return void
 */
function ogmake_bump_cache_generation() {
	update_option( 'ogmake_cache_gen', (int) get_option( 'ogmake_cache_gen', 0 ) + 1, false );
}

/**
 * Wire up hooks.
 *
 * @return void
 */
function ogmake_boot() {
	Ogmake_Settings::register_hooks();
	Ogmake_Frontend::register_hooks();
	Ogmake_Metabox::register_hooks();
}
add_action( 'plugins_loaded', 'ogmake_boot' );
