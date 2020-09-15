<?php

namespace Toolset\DynamicSources\PostProviders;

/**
 * Class CustomPostFactory
 *
 * A factory for instantiating the CustomPost class.
 *
 * @codeCoverageIgnore
 */
class CustomPostFactory {
	/**
	 * Creates an instance of the CustomPostFactory class.
	 *
	 * @param $args
	 *
	 * @return CustomPost
	 */
	public function create_custom_post( $args = null ) {
		return new CustomPost( $args );
	}
}
