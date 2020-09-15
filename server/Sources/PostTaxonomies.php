<?php

namespace Toolset\DynamicSources\Sources;

use Toolset\DynamicSources\DynamicSources;
use Toolset\DynamicSources\PostProvider;

/**
 * Source for offering the post's taxonomies as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class PostTaxonomies extends AbstractSource {
	const NAME = 'post-taxonomies';

	const HAS_FIELDS = true;

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Post Taxonomies', 'wpv-views' );
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
	 * Gets the Source fields.
	 *
	 * @return array The array of the Source's fields.
	 */
	public function get_fields() {
		$post_provider = $this->get_post_provider();

		if ( ! $post_provider ) {
			return array();
		}

		$taxonomies = array();

		foreach ( $post_provider->get_post_types() as $post_type ) {
			$post_type_taxonomies = get_object_taxonomies( $post_type, 'object' );

			if ( ! $post_type_taxonomies ) {
				continue;
			} else {
				$taxonomies = array_merge( $taxonomies, $post_type_taxonomies );
			}
		}

		if ( empty ( $taxonomies ) ) {
			return array();
		}

		$fields = [];

		foreach( $taxonomies as $taxonomy ) {
			$fields[] = [
				'label' => $taxonomy->label,
				'value' => $taxonomy->name,
				'categories' => [ 'text' ],
			];
		}
		return $fields;
	}

	/**
	 * Gets the content of the Source.
	 *
	 * @param null|string $taxonomy
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 * @return array|string The content of the Source.
	 */
	public function get_content( $taxonomy = null, $attributes = null ) {
		$result = [];

		$terms = get_the_terms( get_the_ID(), $taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$result[] = $term->name;
			}
		}

		if ( isset( $attributes[ 'separator' ] ) && is_array( $result ) ) {
			$result = implode( $attributes[ 'separator' ], $result );
		}

		return $result;
	}

	public function is_usable_with_post_provider( PostProvider $post_provider ) {
		foreach ( $post_provider->get_post_types() as $post_type ) {
			$taxonomies = get_object_taxonomies( $post_type, 'object' );
			if ( ! empty( $taxonomies ) ) {
				return true;
			}
		}

		return false;
	}
}
