<?php
/**
 * Plugin Name: Favorite Posts Performance Plugin
 * Plugin URI:  https://example.com/favorite-posts-performance-plugin
 * Description: Performance-focused post favorites plugin with REST API and caching.
 * Version:     1.0.0
 * Author:      Favorite Posts Team
 * Author URI:  https://example.com
 * Text Domain: favorite-posts-plugin
 * Domain Path: /languages
 * License:     GPL-2.0-or-later
 *
 * @package Favorite_Posts_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FP_PLUGIN_VERSION', '1.0.0' );
define( 'FP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once FP_PLUGIN_PATH . 'src/class-db.php';
require_once FP_PLUGIN_PATH . 'src/class-redis.php';
require_once FP_PLUGIN_PATH . 'src/class-utils.php';
require_once FP_PLUGIN_PATH . 'src/class-api.php';

/**
 * Run activation tasks.
 *
 * @return void
 */
function fp_plugin_activate() {
	Favorite_Posts_DB::create_table();
}

/**
 * Run deactivation tasks.
 *
 * @return void
 */
function fp_plugin_deactivate() {
	if ( function_exists( 'wp_cache_flush_group' ) ) {
		wp_cache_flush_group( 'favorite_posts' );
	}
}

register_activation_hook( __FILE__, 'fp_plugin_activate' );
register_deactivation_hook( __FILE__, 'fp_plugin_deactivate' );
register_uninstall_hook( __FILE__, 'fp_plugin_uninstall' );

/**
 * Uninstall callback wrapper.
 *
 * @return void
 */
function fp_plugin_uninstall() {
	if ( file_exists( FP_PLUGIN_PATH . 'uninstall.php' ) ) {
		require FP_PLUGIN_PATH . 'uninstall.php';
	}
}

/**
 * Load plugin translations.
 *
 * @return void
 */
function fp_load_textdomain() {
	load_plugin_textdomain( 'favorite-posts-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'fp_load_textdomain' );

/**
 * Enqueue plugin frontend assets.
 *
 * @return void
 */
function fp_enqueue_assets() {
	if ( ! is_user_logged_in() ) {
		return;
	}

	wp_enqueue_style(
		'fp-frontend-style',
		FP_PLUGIN_URL . 'assets/css/styles.css',
		array(),
		FP_PLUGIN_VERSION
	);

	wp_enqueue_script(
		'fp-frontend-script',
		FP_PLUGIN_URL . 'assets/js/frontend.js',
		array(),
		FP_PLUGIN_VERSION,
		true
	);

	wp_localize_script(
		'fp-frontend-script',
		'fpFavoriteConfig',
		array(
			'endpoint' => esc_url_raw( rest_url( 'sharea/v1/favorite' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'i18n'     => array(
				'save'      => __( 'Save', 'favorite-posts-plugin' ),
				'saved'     => __( 'Saved', 'favorite-posts-plugin' ),
				'error'     => __( 'Unable to update favorite. Please try again.', 'favorite-posts-plugin' ),
				'login'     => __( 'Please log in to save favorites.', 'favorite-posts-plugin' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'fp_enqueue_assets' );

/**
 * Register API routes.
 *
 * @return void
 */
function fp_register_services() {
	$api = new Favorite_Posts_API();
	$api->register_routes();
}
add_action( 'rest_api_init', 'fp_register_services' );

/**
 * Append favorite button to post content and excerpts.
 *
 * @param string $content Post content.
 * @return string
 */
function fp_append_favorite_button( $content ) {
	if ( ! is_user_logged_in() || ! is_singular( 'post' ) ) {
		return $content;
	}

	global $post;
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}

	return $content . Favorite_Posts_Utils::render_button( $post->ID );
}
add_filter( 'the_content', 'fp_append_favorite_button' );



/**
 * Append favorite button to excerpts in post lists.
 *
 * @param string $excerpt Post excerpt.
 * @return string
 */
function fp_append_favorite_button_to_excerpt( $excerpt ) {
	if ( ! is_user_logged_in() || is_singular( 'post' ) ) {
		return $excerpt;
	}

	global $post;
	if ( ! $post instanceof WP_Post ) {
		return $excerpt;
	}

	return $excerpt . Favorite_Posts_Utils::render_button( $post->ID );
}
add_filter( 'the_excerpt', 'fp_append_favorite_button_to_excerpt' );
