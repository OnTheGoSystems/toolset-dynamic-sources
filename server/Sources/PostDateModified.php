<?php

namespace Toolset\DynamicSources\Sources;

/**
 * Source for offering the post's modified date as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class PostDateModified extends DateSource {
	const NAME = 'post-date-modified';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Modified Date', 'wpv-views' );
	}

	/**
	 * Gets the content of the Source.
	 *
	 * @param null|string $field
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 * @return string The content of the Source.
	 */
	public function get_content( $field = null, $attributes = null ) {
		global $post;

		return wp_kses_post( $this->maybe_formatted( $attributes, $post->post_modified ) );
	}
}
