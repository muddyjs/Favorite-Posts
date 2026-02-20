<?php
/**
 * Cache layer for favorites.
 *
 * @package Favorite_Posts_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Favorite post cache helper.
 */
class Favorite_Posts_Redis {

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'favorite_posts';

	/**
	 * Cache TTL in seconds.
	 *
	 * @var int
	 */
	const CACHE_TTL = 600;

	/**
	 * Get cache key for user favorites.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function get_cache_key( $user_id ) {
		return 'fp_favorites_user_' . absint( $user_id );
	}

	/**
	 * Get favorites from cache.
	 *
	 * @param int $user_id User ID.
	 * @return array|null
	 */
	public static function get_user_favorites( $user_id ) {
		$cache_key = self::get_cache_key( $user_id );
		$data      = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $data ) {
			return null;
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		return array_map( 'absint', $data );
	}

	/**
	 * Prime cache for user favorites.
	 *
	 * @param int   $user_id   User ID.
	 * @param int[] $favorites Post IDs.
	 * @return void
	 */
	public static function set_user_favorites( $user_id, $favorites ) {
		$cache_key = self::get_cache_key( $user_id );
		$values    = array_values( array_unique( array_map( 'absint', (array) $favorites ) ) );

		wp_cache_set( $cache_key, $values, self::CACHE_GROUP, self::CACHE_TTL );
	}

	/**
	 * Delete user favorites cache.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function flush_user_favorites( $user_id ) {
		$cache_key = self::get_cache_key( $user_id );
		wp_cache_delete( $cache_key, self::CACHE_GROUP );
	}

	/**
	 * Get favorites from cache or database.
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	public static function get_or_warm_user_favorites( $user_id ) {
		$favorites = self::get_user_favorites( $user_id );

		if ( null !== $favorites ) {
			return $favorites;
		}

		$favorites = Favorite_Posts_DB::get_favorites( $user_id );
		self::set_user_favorites( $user_id, $favorites );

		return $favorites;
	}
}
