<?php

namespace Toolset\DynamicSources;


/**
 * Interface for an object that provides a post for the dynamic data source.
 */
interface PostProvider {

	/**
	 * Gets a slug uniquely identifying the post provider.
	 *
	 * @return string
	 */
	public function get_unique_slug();


	/**
	 * Label that an be displayed in the dropdown.
	 *
	 * @return string
	 */
	public function get_label();


	/**
	 * Post that should be used as a source. Has to be available only when dynamic content is being
	 * generated. Otherwise, do not rely on it.
	 *
	 * @param int $initial_post_id ID of the initial post, which should be used to get the source post for the
	 *     dynamic content.
	 *
	 * @return int|null Post ID or null when it's not available.
	 */
	public function get_post( $initial_post_id );


	/**
	 * Type of the post that will be provided.
	 *
	 * Note: This MUST be available even during source registration, before any specific post is available.
	 *
	 * @return string[]
	 */
	public function get_post_types();


}
