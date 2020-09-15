<?php

namespace Toolset\DynamicSources\Integrations;

use Toolset\DynamicSources\DynamicSources;
use WP_Block_Parser_Block;

class WPML {

	public function initialize() {
		add_filter( 'wpml_found_strings_in_block', array( $this, 'remove_dynamic_source_strings_from_block' ), 10, 2 );
		add_filter(
			'toolset/dynamic_sources/filters/shortcode_post_provider',
			array( $this, 'convert_post_provider' )
		);
	}

	/**
	 * @param array $strings
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	public function remove_dynamic_source_strings_from_block( array $strings, WP_Block_Parser_Block $block ) {
		$dynamicAttributeStrings = $this->getDynamicAttributeStrings( $block );

		foreach ( $strings as $key => $string ) {
			if (
				$this->isOnlyDynamicShortcode( $string->value )
				|| in_array( $string->value, $dynamicAttributeStrings, true )
			) {
				unset( $strings[ $key ] );
			}
		}

		return array_values( $strings );
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	private function isOnlyDynamicShortcode( $value ) {
		return (bool) preg_match( '/^\[' . \Toolset\DynamicSources\DynamicSources::SHORTCODE . '[^\]]*]$/', $value );
	}

	/**
	 * @param WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	private function getDynamicAttributeStrings( WP_Block_Parser_Block $block ) {
		$strings = [];

		if ( isset( $block->attrs['dynamic'] ) ) {
			foreach ( $block->attrs['dynamic'] as $attributeName => $config ) {
				if (
					isset( $config['isActive'], $block->attrs[ $attributeName ] )
					&& $config['isActive']
					&& is_string( $block->attrs[ $attributeName ] )
				) {
					$strings[] = $block->attrs[ $attributeName ];
				}
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
