<?php

namespace Toolset\DynamicSources\Integrations;

use Toolset\DynamicSources\DynamicSources;

class WPML {

	public function initialize() {
		add_filter( 'wpml_found_strings_in_block', array( $this, 'remove_dynamic_source_strings_from_block' ) );
		add_filter(
			'toolset/dynamic_sources/filters/shortcode_post_provider',
			array( $this, 'convert_post_provider' )
		);
	}

	/**
	 * @param array $strings
	 *
	 * @return array
	 */
	public function remove_dynamic_source_strings_from_block( array $strings ) {
		foreach ( $strings as $key => $string ) {
			if ( 0 === strpos( $string->value, '[' . \Toolset\DynamicSources\DynamicSources::SHORTCODE ) ) {
				unset( $strings[ $key ] );
			}
		}

		return $strings;
	}

	/**
	 * @param string $post_provider
	 *
	 * @return string
	 */
	public function convert_post_provider( $post_provider ) {
		if ( $this->is_other_post_provider( $post_provider ) ) {
			list( $slug, $post_type, $post_id ) = explode( '|', $post_provider );
			$post_id                            = apply_filters( 'wpml_object_id', $post_id, $post_type );
			$post_provider                      = implode( '|', array( $slug, $post_type, $post_id ) );
		}

		return $post_provider;
	}

	/**
	 * @param string $post_provider
	 *
	 * @return bool
	 */
	private function is_other_post_provider( $post_provider ) {
		return (bool) preg_match( DynamicSources::CUSTOM_POST_TYPE_REGEXP, '"' . $post_provider . '"' );
	}
}
