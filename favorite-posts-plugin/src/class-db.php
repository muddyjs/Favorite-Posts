<?php
/**
 * Database layer for favorites.
 *
 * @package Favorite_Posts_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Favorite posts DB abstraction.
 */
class Favorite_Posts_DB {

	/**
	 * Get full table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'favorites';
	}

	/**
	 * Create favorites table.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_post (user_id,post_id),
			KEY user_id (user_id),
			KEY post_id (post_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Fetch all favorited post IDs for a user.
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	public static function get_favorites( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array();
		}

		$table_name = self::table_name();

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$table_name} WHERE user_id = %d",
				$user_id
			)
		);

		return array_map( 'absint', $post_ids );
	}

	/**
	 * Add a favorite row.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function add_favorite( $user_id, $post_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		$post_id = absint( $post_id );

		if ( ! $user_id || ! $post_id ) {
			return false;
		}

		$table_name = self::table_name();

		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table_name} (user_id, post_id, created_at)
				 VALUES (%d, %d, %s)
				 ON DUPLICATE KEY UPDATE created_at = VALUES(created_at)",
				$user_id,
				$post_id,
				current_time( 'mysql', true )
			)
		);

		return false !== $result;
	}

	/**
	 * Remove a favorite row.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function remove_favorite( $user_id, $post_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		$post_id = absint( $post_id );

		if ( ! $user_id || ! $post_id ) {
			return false;
		}

		$table_name = self::table_name();

		$result = $wpdb->delete(
			$table_name,
			array(
				'user_id' => $user_id,
				'post_id' => $post_id,
			),
			array( '%d', '%d' )
		);

		return false !== $result;
	}

	/**
	 * Check if a post is favorited.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_favorited( $user_id, $post_id ) {
		global $wpdb;

		$user_id = absint( $user_id );
		$post_id = absint( $post_id );

		if ( ! $user_id || ! $post_id ) {
			return false;
		}

		$table_name = self::table_name();

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE user_id = %d AND post_id = %d LIMIT 1",
				$user_id,
				$post_id
			)
		);

		return ! empty( $exists );
	}
}
