<?php

namespace OTGS\Toolset\DynamicSources\Tests\PostProviders;

use stdClass;
use Toolset\DynamicSources\PostProviders\IdentityPost;
use OTGS\Toolset\DynamicSources\TestCase;
use WP_Mock;

class IdentityPostTest extends TestCase {
	/**
	 * @test
	 */
	public function it_returns_label_with_context() {
		WP_Mock::userFunction( 'taxonomy_exists' )
			->once()
			->andReturn( false );

		$labels = new stdClass();
		$labels->singular_name = 'Page';
		$post_type_object = new stdClass();
		$post_type_object->labels = $labels;

		WP_Mock::userFunction( 'get_post_type_object' )
			->once()
			->andReturn( $post_type_object );

		$subject = new IdentityPost( [ 'page' ] );

		$this->assertSame( 'Current Page', $subject->get_label() );
	}
}
