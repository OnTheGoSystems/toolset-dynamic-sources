<?php

namespace Toolset\DynamicSources\Sources;

use Toolset\DynamicSources\DynamicSources;

/**
 * Source for offering the post's content as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class PostContent extends AbstractSource {
	const NAME = 'post-content';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Content (Body)', 'wpv-views' );
	}

	/**
	 * Gets the Source group.
	 *
	 * @return string
	 */
	public function get_group() {
		return DynamicSources::POST_GROUP;
	}

	/**
	 * Gets the Source categories, i.e. the type of content this Source can offer.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( DynamicSources::TEXT_CATEGORY );
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

		$content = null;

		if ( ! $post->dynamic_sources_content_processed ) {
			// In order to avoid infinite loops, a property for the post object is set, stating that this post has been
			// processed during the "Post Content" source rendering.
			// Before applying the "the_content" filter this property is checked and if the post has been already processed,
			// the source returns null to prevent an infinite loop.
			$post->dynamic_sources_content_processed = true;
			$content = apply_filters( 'the_content', $post->post_content );

			// After the source has returned its content, the property for the post object is set back to false to allow
			// rendering of the same source by another block.
			$post->dynamic_sources_content_processed = false;
		}

		return $content;
	}
}
