<?php

namespace Toolset\DynamicSources\Sources;

use Toolset\DynamicSources\DynamicSources;

/**
 * Source for offering the post's featured image data as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class MediaFeaturedImageData extends AbstractSource {
	const NAME = 'media-featured-image-data';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Featured Image Data', 'toolset-dynamic-sources' );
	}

	/**
	 * Gets the Source group.
	 *
	 * @return string
	 */
	public function get_group() {
		return DynamicSources::MEDIA_GROUP;
	}

	/**
	 * Gets the Source categories, i.e. the type of content this Source can offer.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( DynamicSources::TEXT_CATEGORY, DynamicSources::URL_CATEGORY, DynamicSources::IMAGE_CATEGORY );
	}

	private function get_attachment() {
		$id = get_post_thumbnail_id();

		if ( ! $id ) {
			return false;
		}

		return get_post( $id );
	}

	/**
	 * Gets the content of the Source.
	 *
	 * @param null|string $field
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 *
	 * @return string The content of the Source.
	 */
	public function get_content( $field = null, $attributes = null ) {
		$attachment = $this->get_attachment();

		if ( ! $attachment ) {
			return '';
		}

		$size = isset( $attributes[ 'size' ] ) ? $attributes[ 'size' ] : 'full';

		$value = wp_get_attachment_image_src( $attachment->ID, $size );

		return wp_kses_post( $value );
	}
}
