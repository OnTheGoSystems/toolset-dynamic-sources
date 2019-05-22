<?php

namespace Toolset\DynamicSources;

class Cache {
	private $sources;

	public function __construct( $sources ) {
		$this->sources = $sources;
	}

	public function initialize() {
		add_filter( 'toolset/dynamic_sources/filters/cache', array( $this, 'add_cached_data' ), 1, 2 );
	}

	public function add_cached_data( $cache, $post_id ) {
		global $post;

		$post_id = $post_id ?: $post->ID;

		if ( ! $post_id ) {
			return $cache;
		}

		$post_providers = apply_filters( 'toolset/dynamic_sources/filters/get_post_providers', array() );
		foreach ( $post_providers as $key => $post_provider ) {
			// For the case of Templates, each Template will have a post meta with the ID of the post to be used for preview.
			$tb_preview_post = get_post_meta( $post_id, 'tb_preview_post', true );
			$post_id = '' !== $tb_preview_post ? $tb_preview_post : $post_id;

			foreach ( $this->sources as $source_type ) {
				$args = array(
					$source_type['sources'],
				);

				if ( $source_type['post_relevant'] ) {
					$args[] = $post_provider->get_unique_slug();
					$args[] = $post_id;
				}

				$cache = array_merge( $cache, call_user_func_array( array( $this, 'get_data_for_sources' ), $args ) );
			}
		}

		return $cache;
	}

	public function get_data_for_sources( $sources, $post_provider = null, $post_id = null ) {
		$cache_for_sources = array();
		foreach ( $sources as $source ) {
			$sourceClass = '\Toolset\DynamicSources\Sources\\' . $source;
			$source_name = constant ( $sourceClass . '::NAME' );
			$source_has_fields = constant ( $sourceClass . '::HAS_FIELDS' );

			$source_fields = array();
			if ( $source_has_fields ) {
				$source_fields = apply_filters(
					'toolset/dynamic_sources/filters/get_source_fields',
					array(),
					$post_id,
					$source_name,
					$post_provider
				);
			}

			$source_content = array();
			if ( $source_has_fields && ! empty( $source_fields ) ) {
				foreach ( $source_fields as $field ) {
					$key = $field['value'];
					if ( is_array( $key ) ) {
						$key = implode( '|', $key );
					}
					$source_content[ $key ] = apply_filters(
						'toolset/dynamic_sources/filters/get_source_content',
						'',
						$post_provider,
						$post_id,
						$source_name,
						$field['value']
					);
				}
			} else {
				$source_content = apply_filters(
					'toolset/dynamic_sources/filters/get_source_content',
					'',
					$post_provider,
					$post_id,
					$source_name
				);
			}

			if ( ! $source_content ) {
				continue;
			}

			if ( $post_provider ) {
				$cache_for_sources[ $post_provider ][ $source_name ] = $source_content;
			} else {
				$cache_for_sources[ $source_name ] = $source_content;
			}
		}

		return $cache_for_sources;
	}
}
