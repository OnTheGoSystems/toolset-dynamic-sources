<?php

namespace Toolset\DynamicSources\Rest;

/**
 * Search Post REST API handler
 */
class SearchPost {

	/**
	 * Registers the search post REST API endpoint.
	 */
	public function register_search_post_rest_api_routes() {
		$namespace = 'toolset-dynamic-sources/v1';
		$route = '/search-post';
		$args = array(
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => array( $this, 'search_post' ),
			'args' => array(
				's' => array(
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
	 * Callback for searching post REST API endpoint.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 *
	 * @return mixed|\WP_REST_Response
	 */
	public function search_post( \WP_REST_Request $request ) {
		$search = $request->get_param( 's' );
		$posts = get_posts( array(
			's' => $search,
			'post_count' => 5,
			'post_type' => array_keys( get_post_types( array( 'public' => true ), 'names' ) ),
		) );

		$result = [];
		foreach ( $posts as $p ) {
			$result[] = [
				'id' => $p->ID,
				'title' => $p->post_title,
				'post_type' => $p->post_type,
			];
		}

		return rest_ensure_response( $result );
	}
}
