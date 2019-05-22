<?php

namespace Toolset\DynamicSources\OtherFieldsSources;

/**
 * Source for offering the post's fields as dynamic content, in a different group
 *
 * @package toolset-dynamic-sources
 */
class Main {
	const GROUP_KEY = 'metabox';

	/**
	 * Hook into appropriate filters to register our sources.
	 */
	public function initialize() {
		// Add the Toolset source group.
		add_filter( 'toolset/dynamic_sources/filters/groups', function( $groups ) {
			return array_merge( $groups, $this->get_group() );
		} );

		// Register dynamic sources according to the current post type.
		add_filter(
			'toolset/dynamic_sources/filters/register_sources',
			function( $dynamic_sources, $post_providers ) {
				$this->source_factory = new DynamicSourceFactory();
				$sources_to_add = $this->source_factory->get_sources( $post_providers );
				$dynamic_sources = array_merge( $dynamic_sources, $sources_to_add );

				return $dynamic_sources;
			},
			10, 2
		);

		// Register dynamic sources caching
		add_filter(
			'toolset/dynamic_sources/filters/cache',
			function( $cache, $post_id ) {
				if ( ! $post_id ) {
					return $cache;
				}

				$post_providers = apply_filters( 'toolset/dynamic_sources/filters/get_post_providers', array() );
				foreach ( $post_providers as $post_provider ) {

					// Fetch the post that should be used as a source.
					$post_provided = get_post( $post_provider->get_post( $post_id ) );
					$post_provider_sources_to_cache = $this->source_factory->get_sources( array( $post_provider ) );

					$cache_for_provider = array();
					foreach ( $post_provider_sources_to_cache as $source ) {
						$cache_for_provider[ $source->get_name() ] = $source->get_content( $post_provided->ID );
					}

					if ( ! isset( $cache[ $post_provider->get_unique_slug() ] ) ) {
						$cache[ $post_provider->get_unique_slug() ] = array();
					}

					$cache[ $post_provider->get_unique_slug() ] = array_merge( $cache[ $post_provider->get_unique_slug() ], $cache_for_provider );
				}

				return $cache;
			},
			10,
			2
		);
	}

	/**
	 * Gets the Source group.
	 *
	 * @return string
	 */
	public function get_group() {
		return [ self::GROUP_KEY => __( 'Other custom fields', 'toolset-dynamic-sources' ) ];
	}
}
