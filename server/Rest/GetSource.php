<?php

namespace Toolset\DynamicSources\Rest;

use Toolset\DynamicSources\PostProviders\IdentityPost;

/**
 * Get Source from a post REST API handler
 */
class GetSource {

	/**
	 * Post type
	 *
	 * @param string
	 */
	private $post_type;

	/**
	 * Registers the search post REST API endpoint.
	 */
	public function register_get_source_rest_api_routes() {
		$namespace = 'toolset-dynamic-sources/v1';
		$route = '/get-source';
		$args = array(
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_source' ),
			'args' => array(
				'post_type' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'permission_callback' => function( $request ) {
				$post = $request->get_param( 'post' );

				if ( ! is_numeric( $post ) ) {
					return true;
				}

				return current_user_can( 'edit_post', $post );
			},
		);

		register_rest_route( $namespace, $route, $args );
	}

	/**
	 * Forces to use only IdentityPost source
	 *
	 * @return array
	 */
	public function get_post_providers() {
		$id = 'custom_post_type|' . $this->post_type;
		return [ new IdentityPost( [ $id => $this->post_type ] ) ];
	}

	/**
	 * Callback for getting the source of a POST REST API endpoint.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function get_source( \WP_REST_Request $request ) {
		global $post;
		$post_type = $request->get_param( 'post_type' );
		$post_id = $request->get_param( 'post_id' );
		$post = get_post( $post_id );
		if ( ! $post_type ) {
			$post_type = $post->post_type;
		}
		setup_postdata( $post );
		$this->post_type = $post_type;

		add_filter( 'toolset/dynamic_sources/filters/register_post_providers', array( $this, 'get_post_providers' ), 0, 1000 );
		do_action( 'enqueue_block_editor_assets' );

		$result = apply_filters( 'toolset/dynamic_sources/filters/get_grouped_sources', array() );

		return rest_ensure_response( $result );
	}
}
