<?php

namespace Toolset\DynamicSources\Rest;

use Toolset\DynamicSources\PostProviders\CustomPost;

/**
 * Render Source REST API handler
 */
class RenderSource {

	/**
	 * WP Rest API Request
	 *
	 * @param \WP_REST_Request
	 */
	private $request;

	/**
	 * Registers the source rendering REST API endpoint.
	 */
	public function register_render_source_rest_api_routes() {
		$namespace = 'toolset-dynamic-sources/v1';
		$route = '/render-source';
		$args = array(
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => array( $this, 'render_source' ),
			'args' => array(
				'post' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'provider' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'source' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'field' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
				'view-id' => array(
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
	 * Callback for the source rendering REST API endpoint.
	 *
	 * Builds the cache to be served for client-side usage.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function render_source( \WP_REST_Request $request ) {
		$this->request = $request;
		$post_provider = $request->get_param( 'provider' );
		$post_id = $request->get_param( 'post' );
		$source = $request->get_param( 'source' );
		$field = $request->get_param( 'field' );
		$view_id = $request->get_param( 'view-id' );

		add_filter( 'toolset/dynamic_sources/filters/register_post_providers', array( $this, 'get_post_providers' ), 2000 );

		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );
		do_action( 'toolset/dynamic_sources/actions/register_sources', $view_id );

		$content = '';

		/**
		 * Filters the content of a Dynamic Source identified by $source (and $field) for the selected post defined in $post.
		 *
		 * @param string     $content The content of the dynamic source.
		 * @param string     $post_provider
		 * @param int|string $post    The selected post.
		 * @param string     $source  The selected dynamic source.
		 * @param string     $field   The field (if any) for the dynamic source.
		 */
		$content = apply_filters( 'toolset/dynamic_sources/filters/get_source_content', $content, $post_provider, $post_id, $source, $field );

		$response = array( 'sourceContent' => $content );

		wp_reset_postdata();

		return rest_ensure_response( $response );
	}

	/**
	 * Forces Custom Post provider in case
	 *
	 * @param array $providers
	 * @return array
	 */
	public function get_post_providers( $providers ) {
		$request_provider = $this->request->get_param( 'provider' );
		if ( preg_match( '/^custom_post_type\|([^\|]+)\|(\d+)$/', $request_provider, $m ) ) {
			$custom_post = new CustomPost( $m[ 1 ], $m[ 2 ] );
			return [ $custom_post->get_unique_slug() => $custom_post ];
		}
		return $providers;
	}
}
