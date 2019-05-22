<?php

namespace Toolset\DynamicSources\Rest;

/**
 * Dynamic Sources REST API handler
 */
class DynamicSources {

	public function register_available_sources_api_route() {
		$namespace = 'toolset-dynamic-sources/v1';
		$route = '/dynamic-sources';
		$args = array(
			'methods'  => \WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_dynamic_sources' ),
			'args' => array(
				'post-type' => array(
					'required' => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		);

		register_rest_route( $namespace, $route, $args );
	}

	public function get_dynamic_sources() {
		do_action( 'toolset/dynamic_sources/actions/register_sources' );

		return array(
			'postProviders' => apply_filters( 'toolset/dynamic_sources/filters/get_post_providers_for_select', array() ),
			'dynamicSources' => apply_filters( 'toolset/dynamic_sources/filters/get_grouped_sources', array() )
		);
	}
}
