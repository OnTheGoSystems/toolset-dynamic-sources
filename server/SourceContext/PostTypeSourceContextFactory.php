<?php

namespace Toolset\DynamicSources\SourceContext;

/**
 * Class PostTypeSourceContextFactory
 *
 * A factory for creating the PostTypeSourceContextFactory class.
 *
 * @package Toolset\DynamicSources\SourceContext
 */
class PostTypeSourceContextFactory {
	/**
	 * Creates an instance of the PostTypeSourceContext class.
	 *
	 * @param $args
	 *
	 * @return PostTypeSourceContext
	 */
	public function create_post_type_source_context( $args ) {
		return new PostTypeSourceContext( $args );
	}

	/**
	 * @param string[]|string $post_types
	 * @param int $view_id
	 *
	 * @return ViewSourceContext
	 */
	public function create_view_source_context( $post_types, $view_id ) {
		return new ViewSourceContext( $post_types, $view_id );
	}
}
