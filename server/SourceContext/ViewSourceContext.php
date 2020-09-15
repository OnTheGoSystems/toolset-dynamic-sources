<?php

namespace Toolset\DynamicSources\SourceContext;

/**
 * Holds information about the context in which the dynamic sources are being used.
 *
 * This implementation is concerned with post types and View id.
 */
class ViewSourceContext extends PostTypeSourceContext implements SourceContext {
	/** @var int */
	private $view_id;

	/**
	 * @param string[]|string $post_types
	 * @param int $view_id
	 */
	public function __construct( $post_types, $view_id ) {
		parent::__construct( $post_types );

		$this->view_id = $view_id;
	}

	/**
	 * @return int
	 */
	public function get_view_id() {
		return $this->view_id;
	}
}
