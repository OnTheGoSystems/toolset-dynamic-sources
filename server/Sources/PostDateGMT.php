<?php

namespace Toolset\DynamicSources\Sources;

/**
 * Source for offering the post's creation date in GMT as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class PostDateGMT extends DateSource {
	const NAME = 'post-date-gmt';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Date GMT', 'wpv-views' );
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

		return wp_kses_post( $this->maybe_formatted( $attributes, $post->post_date_gmt ) );
	}
}
