<?php

namespace Toolset\DynamicSources\Integrations;

class WPML {

	public function initialize() {
		add_filter( 'wpml_found_strings_in_block', array( $this, 'remove_dynamic_source_strings_from_block' ) );
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
}