<?php

namespace Toolset\DynamicSources\Rest;

use Toolset\DynamicSources\PostProviders\IdentityPost;

/**
 * Cache REST API handler
 */
class Cache {

	/**
	 * Post type
	 *
	 * @param string
	 */
	private $post_type;

	/**
	 * Registers the caching REST API endpoint.
	 *
	 * The caching endpoint is getting hit by the blocks to update the client side cache.
	 */
	public function register_cache_rest_api_routes() {
		$namespace = 'toolset-dynamic-sources/v1';
		$route = '/get-cache';
		$args = array(
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_cache' ),
			'args' => array(
				'post' => array(
					'required' => true,
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
					'sanitize_callback' => 'absint',
				),
			),
			'permission_callback' => function( $request ) {
				return current_user_can( 'edit_post', $request->get_param( 'post' ) );
			},
		);

		register_rest_route( $namespace, $route, $args );
	}

	/**
	 * Callback for the caching REST API endpoint.
	 *
	 * Builds the cache to be served for client-side usage.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function get_cache( \WP_REST_Request $request ) {
		$post_id = $request->get_param( 'post' );

		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		do_action( 'toolset/dynamic_sources/actions/register_sources' );

		$response = apply_filters( 'toolset/dynamic_sources/filters/cache', array(), $post_id );

		wp_reset_postdata();

		return rest_ensure_response( $response );
	}
}
