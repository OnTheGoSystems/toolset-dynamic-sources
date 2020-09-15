<?php

namespace OTGS\Toolset\DynamicSources\Tests\Integrations\Views;

use Toolset\DynamicSources\Integrations\Views\Internals;

class InternalsTest extends \OTGS_TestCase {
	/**
	 * Data provider for "it_gets_preview_posts".
	 *
	 * @return array
	 */
	public function get_preview_posts_provider() {
		return array(
			'set1' => array(
				'post_types' => array(),
				'post_types_for_posts' => array(),
				'public_post_types' => array(),
			),
			'set2' => array(
				'post_types' => array( 'attachment' ),
				'post_types_for_posts' => array(),
				'public_post_types' => array(),
			),
			'set3' => array(
				'post_types' => array( 'lorem', 'attachment' ),
				'post_types_for_posts' => array(),
				'public_post_types' => array(),
			),
			'set4' => array(
				'post_types' => array( 'lorem', 'attachment' ),
				'post_types_for_posts' => array( 'lorem' ),
				'public_post_types' => array( 'lorem' ),
			),
		);
	}

	/**
	 * It tests that the tested method gets the preview posts.
	 *
	 * @param array $post_types
	 * @param array $post_types_for_posts
	 * @param array $public_post_types
	 *
	 * @test
	 *
	 * @dataProvider get_preview_posts_provider
	 */
	public function it_gets_preview_posts( $post_types, $post_types_for_posts, $public_post_types ) {
		$include = '';
		$ct_mock = $this->getMockBuilder( 'WP_Post_Mock' )
						->setMockClassName( 'WPV_Content_Template_Embedded' )
						->setMethods( array( 'get_instance', 'get_posts_using_this' ) )
						->getMock();

		// User Functions.
		if ( ! $post_types ) {
			\WP_Mock::userFunction( 'get_the_ID' )->andReturn( '1' );
			$single_assigned_posts_for_ct = array( '2', '3' );
			$include = implode( ',', $single_assigned_posts_for_ct );

			$ct_mock->method( 'get_instance' )->willReturnSelf();
			$ct_mock->method( 'get_posts_using_this' )->with( '*', 'flat_array', 5 )->willReturn( $single_assigned_posts_for_ct );

			\WP_Mock::userFunction( 'get_post_types' )->once()->with( array( 'public' => true ) )->andReturn( array() );
		}

		\WP_Mock::userFunction( 'get_posts' )
				->once()
				->with(
					array(
						'post_type' => $post_types_for_posts,
						'posts_per_page' => 5,
						'suppress_filters' => false,
						'post_status' => 'any',
						'include' => $include,
					)
				);
		foreach ( $post_types as $post_type ) {
			if ( 'attachment' === $post_type ) {
				continue;
			}
			$maybe_get_post_type_object_return = in_array( $post_type, $public_post_types ) ? (object) array( 'public' => true ) : (object) array( 'public' => false );
			\WP_Mock::userFunction( 'get_post_type_object' )->once()->with( $post_type )->andReturn( $maybe_get_post_type_object_return );
		}

		$subject = new Internals( array(), array( $ct_mock, 'get_instance' ) );


		$subject->get_preview_posts( $post_types );
	}
}
