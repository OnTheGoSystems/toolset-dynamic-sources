<?php

namespace Toolset\DynamicSources\PostProviders;


use Toolset\DynamicSources\PostProvider;

/**
 * Post provider that returns the current post.
 *
 * Note: It needs to know the post type beforehand, sooner than the post is actually available.
 */
class IdentityPost implements PostProvider {
	const UNIQUE_SLUG = '__current_post';

	/** @var string[] */
	private $post_type_slugs;


	/**
	 * IdentityPost constructor.
	 *
	 * @param string[] $post_type_slugs
	 */
	public function __construct( $post_type_slugs ) {
		$this->post_type_slugs = $post_type_slugs;
		$this->update_post_types_from_taxonomies();
	}


	/**
	 * @return string
	 */
	public function get_unique_slug() {
		return self::UNIQUE_SLUG;
	}


	/**
	 * @return string
	 */
	public function get_label() {
		return sprintf(
			__( 'Current %s', 'wpv-views' ),
			$this->get_post_label()
		);
	}

	/**
	 * @return string
	 */
	private function get_post_label() {
		$post_type_object = get_post_type_object( reset( $this->post_type_slugs ) );

		if ( ! $post_type_object ) {
			return __( 'Post', 'wpv-views' );
		}
		return $post_type_object->labels->singular_name;
	}

	/**
	 * @inheritdoc
	 *
	 * @param int $initial_post_id ID of the initial post, which should be used to get the source post for the
	 *     dynamic content.
	 *
	 * @return int|null Post ID or null when it's not available.
	 */
	public function get_post( $initial_post_id ) {
		return (int) $initial_post_id;
	}


	/**
	 * @inheritdoc
	 *
	 * @return string[]
	 */
	public function get_post_types() {
		return $this->post_type_slugs;
	}

	/**
	 * Updates the list of post types slugs if they are categories
	 */
	private function update_post_types_from_taxonomies() {
		$taxonomies = [];
		foreach ( $this->post_type_slugs as $post_type_slug ) {
			if ( taxonomy_exists( $post_type_slug ) ) {
				$taxonomies[] = $post_type_slug;
			}
		}
		if ( ! empty( $taxonomies ) ) {
			$post_types = get_post_types();
			foreach ( $post_types as $post_type ) {
				$intersect = array_intersect( $taxonomies, get_object_taxonomies( $post_type ) );
				if ( ! empty( $intersect ) ) {
					$this->post_type_slugs[] = $post_type;
				}
			}
		}
	}
}
