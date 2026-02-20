<?php
/**
 * REST API tests for favorites endpoint.
 *
 * @package Favorite_Posts_Plugin
 */

/**
 * Favorite API test cases.
 */
class Favorite_Posts_REST_API_Test extends WP_UnitTestCase {

	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Test user id.
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Test post id.
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * Setup fixture.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->server  = rest_get_server();
		$this->user_id = self::factory()->user->create();
		$this->post_id = self::factory()->post->create(
			array(
				'post_status' => 'publish',
			)
		);

		wp_set_current_user( $this->user_id );
		Favorite_Posts_DB::create_table();
	}

	/**
	 * Ensure unauthenticated users are rejected.
	 *
	 * @return void
	 */
	public function test_unauthenticated_request_returns_401() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/sharea/v1/favorite' );
		$request->set_param( 'post_id', $this->post_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Ensure favorite can be added and removed.
	 *
	 * @return void
	 */
	public function test_toggle_favorite() {
		$request = new WP_REST_Request( 'POST', '/sharea/v1/favorite' );
		$request->set_param( 'post_id', $this->post_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$add_response = $this->server->dispatch( $request );
		$this->assertSame( 200, $add_response->get_status() );
		$this->assertSame( 'added', $add_response->get_data()['status'] );

		$remove_response = $this->server->dispatch( $request );
		$this->assertSame( 200, $remove_response->get_status() );
		$this->assertSame( 'removed', $remove_response->get_data()['status'] );
	}

	/**
	 * Ensure nonce is required.
	 *
	 * @return void
	 */
	public function test_invalid_nonce_returns_403() {
		$request = new WP_REST_Request( 'POST', '/sharea/v1/favorite' );
		$request->set_param( 'post_id', $this->post_id );
		$request->set_header( 'X-WP-Nonce', 'invalid' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}
}
