<?php

namespace Toolset\DynamicSources\PostProviders;


use Toolset\DynamicSources\PostProvider;

/**
 * Post provider for a custom post.
 *
 * Note: It needs to know the post type beforehand, sooner than the post is actually available.
 */
class CustomPost implements PostProvider {
	const UNIQUE_SLUG = '__custom_post';

	/**
	 * Specific provider slug
	 *
	 * @param string
	 */
	private $provider_slug;

	/**
	 * Specific provider post type
	 *
	 * @param string
	 */
	private $post_type;

	/**
	 * Specific provider post id
	 *
	 * @param string
	 */
	private $post_id;



	/**
	 * Constructor.
	 *
	 * @param string $provider_slug
	 */
	public function __construct( $post_type = null, $post_id = null ) {
		$this->post_type = $post_type;
		$this->post_id = $post_id;
		$this->provider_slug = $post_type && $post_id ? 'custom_post_type|' . $post_type . '|' . $post_id : null;
	}

	/**
	 * @return string
	 */
	public function get_unique_slug() {
		return $this->provider_slug ? $this->provider_slug : self::UNIQUE_SLUG;
	}

	/**
	 * @return string
	 */
	public function get_label() {
		if ( $this->post_id ) {
			return get_the_title( $this->post_id );
		}
		return __( 'Other post', 'wpv-views' );
	}

	/**
	 * It returns 0 because it is not attached to any post, in the control it will
	 *
	 * @param int $initial_post_id ID of the initial post, which should be used to get the source post for the
	 *     dynamic content.
	 *
	 * @return 0 Post ID or null when it's not available.
	 */
	public function get_post( $initial_post_id ) {
		return $this->post_id ? $this->post_id : $initial_post_id;
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_post_types() {
		return $this->post_type ? [ $this->post_type ] : [];
	}
}
