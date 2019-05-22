<?php

namespace Toolset\DynamicSources\OtherFieldsSources;

use Toolset\DynamicSources\DynamicSources;
use Toolset\DynamicSources\Sources\AbstractSource;

/**
 * Source for offering a post's field as dynamic content.
 *
 * @package toolset-dynamic-sources
 */
class PostField extends AbstractSource {
	const HAS_FIELDS = false;

	/** @var object */
	private $meta;

	public function __construct( $meta ) {
		$this->meta = $meta;
	}

	/**
	 * Gets the Source name (slug).
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->meta->meta_key;
	}

	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->meta->meta_key;
	}

	/**
	 * Gets the Source group.
	 *
	 * @return string
	 */
	public function get_group() {
		return Main::GROUP_KEY;
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
	 * @param int $post_id Post ID
	 * @return string The content of the Source.
	 */
	public function get_content( $post_id = null, $attributes = null ) {
		global $post;
		if ( ! $post_id && $post ) {
			$post_id = $post->ID;
		}
		return get_post_meta( $post_id, $this->meta->meta_key, true );
	}
}
