<?php
/**
 * Uninstall handler for Favorite Posts Performance Plugin.
 *
 * @package Favorite_Posts_Plugin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'favorites';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

$user_ids = get_users(
	array(
		'fields' => 'ID',
	)
);

foreach ( $user_ids as $user_id ) {
	wp_cache_delete( 'fp_favorites_user_' . absint( $user_id ), 'favorite_posts' );
}
