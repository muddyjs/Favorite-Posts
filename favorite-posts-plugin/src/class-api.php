<?php
/**
 * REST API layer.
 *
 * @package Favorite_Posts_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Favorite posts API.
 */
class Favorite_Posts_API {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'sharea/v1',
			'/favorite',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_toggle_favorite' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function( $value ) {
							return absint( $value ) > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Permissions callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_permissions( WP_REST_Request $request ) {
		$logged_in_check = Favorite_Posts_Utils::require_logged_in_user();
		if ( is_wp_error( $logged_in_check ) ) {
			return $logged_in_check;
		}

		$nonce_check = Favorite_Posts_Utils::validate_rest_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		return true;
	}

	/**
	 * Toggle favorite state for a post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_toggle_favorite( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
			return Favorite_Posts_Utils::error_response(
				'fp_invalid_post',
				__( 'Invalid post ID.', 'favorite-posts-plugin' ),
				400
			);
		}

		$favorites = Favorite_Posts_Redis::get_or_warm_user_favorites( $user_id );
		$is_saved  = in_array( $post_id, $favorites, true );

		if ( $is_saved ) {
			$removed = Favorite_Posts_DB::remove_favorite( $user_id, $post_id );
			if ( ! $removed ) {
				return Favorite_Posts_Utils::error_response(
					'fp_remove_failed',
					__( 'Failed to remove favorite.', 'favorite-posts-plugin' ),
					500
				);
			}

			$favorites = array_values( array_diff( $favorites, array( $post_id ) ) );
			Favorite_Posts_Redis::set_user_favorites( $user_id, $favorites );

			return Favorite_Posts_Utils::success_response(
				'removed',
				__( 'Bookmark removed.', 'favorite-posts-plugin' ),
				array( 'post_id' => $post_id )
			);
		}

		$added = Favorite_Posts_DB::add_favorite( $user_id, $post_id );
		if ( ! $added ) {
			return Favorite_Posts_Utils::error_response(
				'fp_add_failed',
				__( 'Failed to save favorite.', 'favorite-posts-plugin' ),
				500
			);
		}

		$favorites[] = $post_id;
		Favorite_Posts_Redis::set_user_favorites( $user_id, $favorites );

		return Favorite_Posts_Utils::success_response(
			'added',
			__( 'Bookmark successful', 'favorite-posts-plugin' ),
			array( 'post_id' => $post_id )
		);
	}
}
