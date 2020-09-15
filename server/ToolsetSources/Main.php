<?php

namespace Toolset\DynamicSources\ToolsetSources;

use Toolset\DynamicSources\PostProvider;
use Toolset\DynamicSources\SourceContext\SourceContext;

/**
 * Main controller to initialize dynamic content sources from Toolset.
 */
class Main {

	/** @var string Group of the sources coming from Toolset. */
	const SOURCE_GROUP_KEY = 'toolset';


	/** @var RelationshipService */
	private $relationship_service;


	/** @var CustomFieldService */
	private $custom_field_service;

	/** @var DynamicSourceFactory */
	private $source_factory;


	/**
	 * Main constructor.
	 *
	 * @param RelationshipService $relationship_service
	 * @param CustomFieldService $custom_field_service
	 */
	public function __construct( RelationshipService $relationship_service, CustomFieldService $custom_field_service ) {
		$this->relationship_service = $relationship_service;
		$this->custom_field_service = $custom_field_service;
	}


	/**
	 * Hook into appropriate filters to register our sources.
	 */
	public function initialize() {

		// We won't do anything if we don't have Types.
		if ( ! apply_filters( 'types_is_active', false ) ) {
			return;
		}

		add_filter(
			'toolset/dynamic_sources/filters/register_post_providers',
			function( $post_providers, SourceContext $source_context ) {
				return array_merge( $post_providers, $this->get_toolset_post_providers( $source_context ) );
			},
			10, 2
		);

		// Add the Toolset source group.
		add_filter( 'toolset/dynamic_sources/filters/groups', function( $groups ) {
			return array_merge( [ self::SOURCE_GROUP_KEY => __( 'Custom fields', 'example' ) ], $groups );
		} );

		// Register dynamic sources according to the current post type.
		add_filter(
			'toolset/dynamic_sources/filters/register_sources',
			function( $dynamic_sources, $post_providers ) {
				$this->source_factory = new DynamicSourceFactory( $this->custom_field_service );
				$sources_to_add = $this->source_factory->get_sources( $post_providers );
				$dynamic_sources = array_merge( $sources_to_add, $dynamic_sources);

				return $dynamic_sources;
			},
			10, 2
		);

		// Register dynamic sources caching
		add_filter(
			'toolset/dynamic_sources/filters/cache',
			function( $cache, $post_id ) {
				global $post;

				if ( ! $post ) {
					return $cache;
				}

				$post_id = $post_id ? $post_id : $post->ID;

				if ( ! $post_id ) {
					return $cache;
				}

				do_action( 'toolset/dynamic_sources/actions/register_sources' );

				/** @var PostProvider[] $post_providers */
				$post_providers = apply_filters( 'toolset/dynamic_sources/filters/get_post_providers', array() );
				foreach ( $post_providers as $post_provider ) {
					global $post;

					// Save the global post.
					$global_post = $post;

					// Fetch the post that should be used as a source.
					$post = get_post( $post_provider->get_post( $post_id ) );

					$post_provider_sources_to_cache = $this->source_factory->get_sources( array( $post_provider ) );

					$cache_for_provider = array();
					foreach ( $post_provider_sources_to_cache as $source ) {
						$cache_for_source = array_reduce(
							$source->get_fields(),
							function ( $result, $field ) use ( $source ) {
								$result[ $field['value'] ] = $source->get_content( $field['value'] );
								return $result;
							},
							[]
						);
						$cache_for_provider[ $source->get_name() ] = $cache_for_source;
					}

					if ( ! isset( $cache[ $post_provider->get_unique_slug() ] ) ) {
						$cache[ $post_provider->get_unique_slug() ] = array();
					}

					$cache[ $post_provider->get_unique_slug() ] = array_merge( $cache[ $post_provider->get_unique_slug() ], $cache_for_provider );

					// Restore the previously set global post.
					$post = $global_post;
				}

				return $cache;
			},
			10,
			2
		);
	}

	/**
	 * @param SourceContext $source_context
	 *
	 * @return array
	 */
	private function get_toolset_post_providers( SourceContext $source_context ) {
		$post_providers = [];

		foreach ( $source_context->get_post_types() as $current_post_type_slug ) {
			$relationships = $this
				->relationship_service
				->get_relationships_acceptable_for_sources( $current_post_type_slug, $source_context );

			foreach ( $relationships as $post_relationship ) {
				// A relationship can be M2M, but turned into O2M by a Views filter, so we can offer intermediary.
				if ( $post_relationship->is_views_filtered_o_2_m() ) {
					$intermediary = new RelatedPostProvider(
						$post_relationship,
						$this->relationship_service->get_role_from_name(
							'intermediary',
							$post_relationship->get_slug()
						),
						$this->relationship_service
					);
					$post_providers[ $intermediary->get_unique_slug() ] = $intermediary;
				} elseif ( $post_relationship->is_intermediary() ) {
					// Here we need to account for the fact that an intermediary post has 2 related providers to offer.
					$parent_provider = new RelatedPostProvider(
						$post_relationship,
						$this->relationship_service->get_role_from_name(
							$post_relationship->get_role_by_post_type(
								$post_relationship->get_post_type_by_role( 'parent' )
							)
						),
						$this->relationship_service
					);
					$post_providers[ $parent_provider->get_unique_slug() ] = $parent_provider;

					$child_provider = new RelatedPostProvider(
						$post_relationship,
						$this->relationship_service->get_role_from_name(
							$post_relationship->get_role_by_post_type(
								$post_relationship->get_post_type_by_role( 'child' )
							)
						),
						$this->relationship_service
					);
					$post_providers[ $child_provider->get_unique_slug() ] = $child_provider;
				} else {
					$post_provider = new RelatedPostProvider(
						$post_relationship,
						$this->relationship_service->get_role_from_name(
							$post_relationship->get_role_by_post_type(
								$post_relationship->get_other_post_type( $current_post_type_slug )
							),
							$post_relationship->get_slug()
						),
						$this->relationship_service
					);

					$post_providers[ $post_provider->get_unique_slug() ] = $post_provider;
				}
			}
		}
		return $post_providers;
	}
}
