<?php

namespace Toolset\DynamicSources\Sources;

use Toolset\DynamicSources\DynamicSources;

/**
 * Source for offering the author's ID as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class AuthorID extends AuthorSource {
	const NAME = 'author-id';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Author ID', 'wpv-views' );
	}

	/**
	 * Gets the Source categories, i.e. the type of content this Source can offer.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( DynamicSources::TEXT_CATEGORY, DynamicSources::NUMBER_CATEGORY );
	}

	/**
	 * Gets the content of the Source.
	 *
	 * @param null|string $field
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 *
	 * @return int Author ID
	 */
	public function get_content( $field = null, $attributes = null ) {
		return wp_kses_post( get_the_author_meta( 'ID' ) );
	}
}
