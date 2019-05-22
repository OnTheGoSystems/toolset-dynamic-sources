<?php

namespace Toolset\DynamicSources\Sources;


use Toolset\DynamicSources\PostProvider;


/**
 * Interface for a single dynamic source.
 *
 * Each source may or may not offer multiple fields which further specify the data it should produce.
 */
interface Source {

	/**
	 * Gets the Source name (slug).
	 *
	 * @return string
	 */
	public function get_name();


	/**
	 * Gets the Source title.
	 *
	 * @return string
	 */
	public function get_title();


	/**
	 * Gets the Source group.
	 *
	 * @return string
	 */
	public function get_group();


	/**
	 * Gets the Source categories, i.e. the type of content this Source can offer.
	 *
	 * @return array
	 */
	public function get_categories();


	/**
	 * Check whether the source offers different fields.
	 *
	 * @return bool
	 */
	public function has_fields();


	/**
	 * Gets the Source fields.
	 *
	 * @return array The array of the Source's fields.
	 */
	public function get_fields();


	/**
	 * Retrieve the dynamic content.
	 *
	 * When this is called, the global $post variable is set to the post that should be use
	 * as the source of the content.
	 *
	 * @param string|null $field Name of the field if it's used, null otherwise
	 * @param array|null  $attributes Extra attributes coming from shortcode
	 *
	 * @return string
	 */
	public function get_content( $field = null, $attributes = null );


	/**
	 * Determine whether this source can be used with the given post provider.
	 *
	 * @param PostProvider $post_provider
	 *
	 * @return bool
	 */
	public function is_usable_with_post_provider( PostProvider $post_provider );

	/**
	 * Get the post provider for the source.
	 *
	 * @return mixed
	 */
	public function get_post_provider();

	/**
	 * Set the post provider for the source.
	 *
	 * @param $post_provider
	 *
	 * @return mixed
	 */
	public function set_post_provider( $post_provider );

}
