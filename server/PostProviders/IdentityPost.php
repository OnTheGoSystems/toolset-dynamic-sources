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
		return __( 'Current post', 'wpv-views' );
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
	 * @return string
	 */
	public function get_post_types() {
		return $this->post_type_slugs;
	}
}
