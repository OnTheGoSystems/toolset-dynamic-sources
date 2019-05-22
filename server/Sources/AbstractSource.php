<?php

namespace Toolset\DynamicSources\Sources;

use Toolset\DynamicSources\PostProvider;

/**
 * Abstract parent class for the representation of a dynamic source.
 *
 * @package toolset-dynamic-sources
 */
abstract class AbstractSource implements Source {

	const NAME = '';

	const HAS_FIELDS = false;

	protected $post_provider;

	/**
	 * Gets the Source name (slug).
	 *
	 * @return string
	 */
	public function get_name() {
		return static::NAME;
	}

	public function has_fields() {
		return static::HAS_FIELDS;
	}

	/**
	 * Gets the Source fields.
	 *
	 * @return array The array of the Source's fields.
	 */
	public function get_fields() {
		return array();
	}


	/**
	 * Determine whether this source can be used with the given post provider.
	 *
	 * @param PostProvider $post_provider
	 *
	 * @return bool
	 */
	public function is_usable_with_post_provider( PostProvider $post_provider ) {
		return true;
	}

	/**
	 * Get the post provider for the source.
	 *
	 * @return mixed|void
	 */
	public function get_post_provider() {
		return $this->post_provider;
	}

	/**
	 * Set the post provider for the source.
	 * 
	 * @param $post_provider
	 *
	 * @return mixed|void
	 */
	public function set_post_provider( $post_provider ) {
		$this->post_provider = $post_provider;
	}
}
