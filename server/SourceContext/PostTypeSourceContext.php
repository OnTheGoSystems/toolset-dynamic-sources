<?php

namespace Toolset\DynamicSources\SourceContext;


/**
 * Holds information about the context in which the dynamic sources are being used.
 *
 * For now, this implementatin is concerned only with post types.
 */
class PostTypeSourceContext implements SourceContext {


	/**
	 * @var string[]
	 */
	private $post_types;


	/**
	 * PostTypeSourceContext constructor.
	 *
	 * @param string[]|string $post_types
	 */
	public function __construct( $post_types ) {
		if( is_string( $post_types ) ) {
			$post_types = [ $post_types ];
		}

		if( ! is_array( $post_types ) ) {
			throw new \InvalidArgumentException();
		}

		$this->post_types = $post_types;
	}


	/**
	 * @inheritdoc
	 *
	 * @return string[]
	 */
	public function get_post_types() {
		return $this->post_types;
	}

}
