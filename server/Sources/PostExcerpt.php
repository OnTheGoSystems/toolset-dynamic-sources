<?php

namespace Toolset\DynamicSources\Sources;

use Toolset\DynamicSources\DynamicSources;

/**
 * Source for offering the post's excerpt as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class PostExcerpt extends AbstractSource {
	const NAME = 'post-excerpt';

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Excerpt', 'wpv-views' );
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
	 * Gets the excerpt field, or post content as fallback.
	 *
	 * @param null|string $field
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 * @return string The content of the Source.
	 */
	public function get_content( $field = null, $attributes = null ) {
		$post = get_post();

		if ( ! $post ) {
			return '';
		}

		if ( $post->post_excerpt ) {
			$content = $post->post_excerpt;
		} else {
			$content = $post->post_content;
		}

		$processed_content = wp_strip_all_tags( $content );

		if ( is_array( $attributes ) ) {
			$excerpt_more = ! empty( $attributes['renderellipsis'] ) ? $attributes['ellipsistext'] : '';

			if (
				array_key_exists( 'countby', $attributes ) &&
				array_key_exists( 'length', $attributes )
			) {
				if ( $attributes['countby'] === 'word' ) {
					$processed_content = wp_trim_words( $processed_content, $attributes['length'], $excerpt_more );
				} elseif ( $attributes['countby'] === 'char' ) {
					$processed_content = wp_html_excerpt( $processed_content, $attributes['length'], $excerpt_more );
				}
			}
		}

		return $processed_content;
	}
}
