<?php

namespace Toolset\DynamicSources\Rest;

/**
 * Handle the REST functionality of the plugin by implementing the following endpoints:
 *  - Cache building endpoint for use on the client side.
 *  - Dynamic source rendering endpoint for rendering non-cached dynamic sources.
 *
 * @package toolset-dynamic-sources
 */
class Main {
	/**
	 * Adds hooks for the REST API endpoints registration.
	 */
	public function initialize() {
		add_action( 'rest_api_init', array( new RenderSource(), 'register_render_source_rest_api_routes' ), 11 );

		add_action( 'rest_api_init', array( new Cache(), 'register_cache_rest_api_routes' ), 11 );

		add_action( 'rest_api_init', array( new SearchPost(), 'register_search_post_rest_api_routes' ), 11 );

		add_action( 'rest_api_init', array( new GetSource(), 'register_get_source_rest_api_routes' ), 11 );

		add_action( 'rest_api_init', array( new DynamicSources(), 'register_available_sources_api_route' ), 11 );
	}
}
