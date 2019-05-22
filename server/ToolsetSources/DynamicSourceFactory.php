<?php

namespace Toolset\DynamicSources\ToolsetSources;


use Toolset\DynamicSources\PostProvider;
use Toolset\DynamicSources\Sources\Source;

/**
 * Dynamically generate dynamic sources for Toolset Custom Field Groups.
 */
class DynamicSourceFactory {


	/** @var CustomFieldService */
	private $custom_field_service;


	/**
	 * DynamicDynamicSourceFactory constructor.
	 *
	 * @param CustomFieldService $custom_field_service
	 */
	public function __construct( CustomFieldService $custom_field_service ) {
		$this->custom_field_service = $custom_field_service;
	}


	/**
	 * @param PostProvider[] $post_providers
	 * @return Source[]
	 */
	public function get_sources( $post_providers ) {
		$post_types = $this->aggregate_post_types( $post_providers );
		$field_group_slugs = $this->aggregate_group_slugs( $post_types );

		$sources = [];

		foreach( $field_group_slugs as $field_group_slug ) {
			$group = $this->custom_field_service->create_group_model( $field_group_slug );

			if( null === $group ) {
				// This means the group doesn't really exist or doesn't offer fields.
				continue;
			}

			$sources[] = new CustomFieldGroupSource( $group, $this->custom_field_service );
		}

		// The source may decide to disqualify itself if it doesn't have anything to offer.
		$sources = array_filter( $sources, function( Source $source ) {
			return ( ! $source instanceof PotentiallyEmptySource ) || $source->has_content();
		} );

		return $sources;
	}


	/**
	 * @param PostProvider[] $post_providers
	 *
	 * @return string[]
	 */
	private function aggregate_post_types( $post_providers ) {
		return array_unique(
			array_reduce( $post_providers, function( $carry, PostProvider $item ) {
				return array_merge( $carry, $item->get_post_types() );
			}, [] )
		);
	}


	/**
	 * @param string[] $post_types
	 * @return string[]
	 */
	private function aggregate_group_slugs( $post_types ) {
		return array_unique(
			array_reduce( $post_types, function( $carry, $item ) {
				$group_slugs = $this->custom_field_service->get_group_slugs_by_type( $item );
				return array_merge( $carry, $group_slugs );
			}, [] )
		);
	}

}
