<?php

namespace Toolset\DynamicSources\Rest;

use WP_REST_Request;

/**
 * Dynamic Sources REST API handler
 */
class DynamicSources {
	/**
	 * REST route registration.
	 */
	public function register_available_sources_api_route() {
		$namespace = 'toolset-dynamic-sources/v1';
		$route = '/dynamic-sources';
		$args = array(
			'methods' => \WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_dynamic_sources' ),
			'args' => array(
				'post-type' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'preview-post-id' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'view-id' => array(
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			'permission_callback' => '__return_true',
		);

		register_rest_route( $namespace, $route, $args );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array|mixed|void
	 */
	public function get_dynamic_sources( WP_REST_Request $request ) {
		$post_types = $request->get_param( 'post-type' );
		$preview_post_id = absint( $request->get_param( 'preview-post-id' ) );
		$view_id = $request->get_param( 'view-id' );

		if ( 0 === $preview_post_id || ! $post_types ) {
			return array();
		}

		do_action( 'toolset/dynamic_sources/actions/register_sources', $view_id );

		return apply_filters(
			'toolset/dynamic_sources/filters/get_dynamic_sources_data',
			[ 'previewPostId' => $preview_post_id ]
		);
	}
}
