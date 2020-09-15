<?php

namespace OTGS\Toolset\DynamicSources\Tests;

use Toolset\DynamicSources\Registration;

class RegistrationTest extends \OTGS\Toolset\DynamicSources\TestCase {
	/**
	 * Data provider for the "it_excludes_featured_image_source_from_registration" method.
	 *
	 * @return array
	 */
	public function it_excludes_featured_image_source_from_registration_data_provider() {
		return array(
			'set1' => array(
				'post_types' => 'lorem',
				'post_types_support_thumbnail' => array(
					'lorem' => false,
				),
			),
			'set2' => array(
				'post_types' => 'lorem',
				'post_types_support_thumbnail' => array(
					'lorem' => true,
				),
			),
			'set3' => array(
				'post_types' => array( 'lorem' ),
				'post_types_support_thumbnail' => array(
					'lorem' => false,
				),
			),
			'set4' => array(
				'post_types' => array( 'lorem' ),
				'post_types_support_thumbnail' => array(
					'lorem' => true,
				),
			),
			'set5' => array(
				'post_types' => array( 'lorem', 'ipsum' ),
				'post_types_support_thumbnail' => array(
					'lorem' => true,
					'ipsum' => false,
				),
			),
		);
	}

	/**
	 * It tests that the featured image source is excluded when it's not relevant.
	 *
	 * @param string|array $post_types
	 * @param array        $post_types_support_thumbnail
	 *
	 * @test
	 *
	 * @dataProvider it_excludes_featured_image_source_from_registration_data_provider
	 */
	public function it_excludes_featured_image_source_from_registration( $post_types, $post_types_support_thumbnail ) {
		$featured_image_source_mock = $this->createMock( 'Toolset\DynamicSources\Sources\MediaFeaturedImageData' );
		$sources_for_registration = array(
			'lorem',
			'ipsum',
			'dolor',
			$featured_image_source_mock,
		);

		$post_types_for_user_functions = array_merge( array(), is_array( $post_types ) ? $post_types : array( $post_types ) );
		$should_exclude_featured_image = true;
		foreach( $post_types_for_user_functions as $post_type ) {
			\WP_Mock::userFunction( 'post_type_supports' )->with( $post_type, 'thumbnail' )->andReturn( $post_types_support_thumbnail[ $post_type ] );
			if ( $post_types_support_thumbnail[ $post_type ] ) {
				$should_exclude_featured_image = false;
				break;
			}
		}

		$expected_sources_for_registration = array_merge( $sources_for_registration, array() );
		if ( $should_exclude_featured_image ) {
			array_pop( $expected_sources_for_registration );
		}

		$subject = new Registration(
			$this->createMock( 'Toolset\DynamicSources\PostProviders\IdentityPostFactory' ),
			$this->createMock( 'Toolset\DynamicSources\SourceContext\PostTypeSourceContextFactory' )
		);

		$this->assertEquals( $expected_sources_for_registration, $subject->maybe_exclude_featured_image_source_from_registration( $post_types, $sources_for_registration ) );
	}

	/**
	 * Data provider for the "it_builds_source_context" method.
	 *
	 * @return array
	 */
	public function it_builds_source_context_data_provider() {
		return array(
			'set1' => array(
				'source_context_is_instance_of_source_context' => true,
			),
			'set2' => array(
				'source_context_is_instance_of_source_context' => false,
			)
		);
	}

	/**
	 * It tests that the source context is build properly.
	 *
	 * @param bool $source_context_is_instance_of_source_context
	 *
	 * @test
	 *
	 * @dataProvider it_builds_source_context_data_provider
	 */
	public function it_builds_source_context( $source_context_is_instance_of_source_context ) {
		$post_type = 'lorem';

		$source_context_mock = $source_context_is_instance_of_source_context ?
			$this->createMock( 'Toolset\DynamicSources\SourceContext\PostTypeSourceContext' ) :
			null;

		$post_type_source_context_factory_mock = $this->createMock( 'Toolset\DynamicSources\SourceContext\PostTypeSourceContextFactory' );
		$post_type_source_context_factory_mock
			->expects( $this->once() )
			->method( 'create_post_type_source_context' )
			->with( $post_type )
			->willReturn( $source_context_mock );

		// todo: Here we need to test that the 'toolset/dynamic_sources/filters/source_context' filter is applied.

		if ( ! $source_context_is_instance_of_source_context ) {
			$this->expectException( \InvalidArgumentException::class );
		}

		$subject = new Registration(
			$this->createMock( 'Toolset\DynamicSources\PostProviders\IdentityPostFactory' ),
			$post_type_source_context_factory_mock
		);
		$subject->build_source_context( $post_type );
	}

	public function lorem() {
		return array(
			'set1' => array(
				'post_type' => array( 'lorem', 'ipsum' ),
				'identity_post_slug' => 'lorem',
			),
		);
	}

	/**
	 * It tests that the post providers are registered properly.
	 *
	 * @test
	 */
	public function it_registers_post_providers() {
		$post_types = array( 'lorem', 'ipsum' );
		$identity_post_unique_slug = 'dolor';

		$source_context_mock = $this->createMock( 'Toolset\DynamicSources\SourceContext\PostTypeSourceContext' );
		$source_context_mock->expects( $this->once() )->method( 'get_post_types' )->willReturn( $post_types );

		$identity_post_mock = $this->createMock( 'Toolset\DynamicSources\PostProviders\IdentityPost' );
		$identity_post_mock->expects( $this->once() )->method( 'get_unique_slug' )->willReturn( $identity_post_unique_slug );

		$post_type_source_context_factory_mock = $this->createMock( 'Toolset\DynamicSources\PostProviders\IdentityPostFactory' );
		$post_type_source_context_factory_mock
			->expects( $this->once() )
			->method( 'create_identity_post' )
			->with( $post_types )
			->willReturn( $identity_post_mock );

		$expected_output = array( $identity_post_unique_slug => $identity_post_mock );

		\WP_Mock::onFilter( 'toolset/dynamic_sources/filters/register_post_providers' )
		        ->with( array( $identity_post_unique_slug => $identity_post_mock ), $source_context_mock )
		        ->reply( array_merge( $expected_output, array( 'sit' => 'amet' ) ) );

		$subject = new Registration(
			$post_type_source_context_factory_mock,
			$this->createMock( 'Toolset\DynamicSources\SourceContext\PostTypeSourceContextFactory' )
		);

		$this->assertEquals(
			$expected_output,
			$subject->register_post_providers( $source_context_mock )
		);
	}
}
