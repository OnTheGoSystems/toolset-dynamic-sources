<?php

namespace Toolset\DynamicSources;

use Toolset\DynamicSources\Sources\Source;

/**
 * Stores dynamic sources and provides an easy access to them.
 */
class SourceStorage {


	/** @var PostProvider[] */
	private $post_providers;


	/** @var Source[] Indexed by source name. */
	private $sources = array();


	/**
	 * SourceStorage constructor.
	 *
	 * @param PostProvider[] $post_providers
	 */
	public function __construct( $post_providers ) {
		$this->post_providers = $post_providers;
	}


	/**
	 * @param Source $source
	 */
	public function add_source( Source $source ) {
		if( array_key_exists( $source->get_name(), $this->sources ) ) {
			return;
		}
		$this->sources[ $source->get_name() ] = $source;
	}


	/**
	 * @param string $source_name
	 *
	 * @return Source|null
	 */
	public function get_source( $source_name ) {
		if ( ! array_key_exists( $source_name, $this->sources ) ) {
			return null;
		}

		return $this->sources[ $source_name ];
	}


	/**
	 * Provide only sources that can be used with the given post provider.
	 *
	 * @param PostProvider $for_post_provider
	 *
	 * @return Source[]
	 */
	public function get_sources_for_post_provider( PostProvider $for_post_provider ) {
		return array_filter( $this->sources, function( Source $source ) use( $for_post_provider ) {
			return $source->is_usable_with_post_provider( $for_post_provider );
		} );
	}
}
