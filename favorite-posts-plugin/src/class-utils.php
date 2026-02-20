<?php
/**
 * Utility helpers.
 *
 * @package Favorite_Posts_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Favorite plugin utilities.
 */
class Favorite_Posts_Utils {

	/**
	 * Ensure current user is authenticated.
	 *
	 * @return true|WP_Error
	 */
	public static function require_logged_in_user() {
		if ( is_user_logged_in() ) {
			return true;
		}

		return new WP_Error(
			'fp_auth_required',
			__( 'You must be logged in to manage favorites.', 'favorite-posts-plugin' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Validate REST nonce from request.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return true|WP_Error
	 */
	public static function validate_rest_nonce( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'x_wp_nonce' );

		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'wp_rest' ) ) {
			return new WP_Error(
				'fp_invalid_nonce',
				__( 'Invalid request token.', 'favorite-posts-plugin' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Check if a post is favorited by current user.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_favorited_for_current_user( $post_id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$user_id   = get_current_user_id();
		$favorites = Favorite_Posts_Redis::get_or_warm_user_favorites( $user_id );

		return in_array( absint( $post_id ), $favorites, true );
	}

	/**
	 * Render favorite button HTML.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function render_button( $post_id ) {
		$post_id    = absint( $post_id );
		$is_saved   = self::is_favorited_for_current_user( $post_id );
		$state      = $is_saved ? ' is-favorited' : '';
		$aria_state = $is_saved ? 'true' : 'false';
		$label      = $is_saved ? __( 'Saved', 'favorite-posts-plugin' ) : __( 'Save', 'favorite-posts-plugin' );

		return sprintf(
			'<button class="fp-favorite-btn%1$s" data-post-id="%2$d" aria-pressed="%3$s"><span class="fp-label">%4$s</span></button>',
			esc_attr( $state ),
			absint( $post_id ),
			esc_attr( $aria_state ),
			esc_html( $label )
		);
	}

	/**
	 * Standardized API success response.
	 *
	 * @param string $status  Action status.
	 * @param string $message Display message.
	 * @param array  $data    Optional payload.
	 * @return WP_REST_Response
	 */
	public static function success_response( $status, $message, $data = array() ) {
		return new WP_REST_Response(
			array(
				'success' => true,
				'status'  => sanitize_key( $status ),
				'message' => sanitize_text_field( $message ),
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Standardized API error response.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status.
	 * @return WP_Error
	 */
	public static function error_response( $code, $message, $status = 400 ) {
		return new WP_Error(
			sanitize_key( $code ),
			sanitize_text_field( $message ),
			array( 'status' => absint( $status ) )
		);
	}
}
