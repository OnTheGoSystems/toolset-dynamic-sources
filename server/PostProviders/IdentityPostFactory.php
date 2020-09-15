<?php

namespace Toolset\DynamicSources\PostProviders;

/**
 * Class IdentityPostFactory
 *
 * A factory for instantiating the IdentityPost class.
 *
 * @codeCoverageIgnore
 */
class IdentityPostFactory {
	/**
	 * Creates an instance of the IdentityPost class.
	 *
	 * @param $args
	 *
	 * @return IdentityPost
	 */
	public function create_identity_post( $args ) {
		return new IdentityPost( $args );
	}
}
