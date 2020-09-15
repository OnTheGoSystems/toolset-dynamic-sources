<?php

namespace OTGS\Toolset\DynamicSources\Tests\Integrations;

use Toolset\DynamicSources\Integrations\Views as SubjectViews;

class Views extends \OTGS_TestCase {
	const CT_POST_TYPE = 'ct';
	const WPA_POST_TYPE = 'wpa';

	/**
	 * It tests that the constructor throws exceptions when not called the proper type of arguments.
	 *
	 * @test
	 */
	public function it_throws_exception_fon_non_string_ct_post_types() {
		$this->expectException( \InvalidArgumentException::class );

		new SubjectViews(
			array(),
			self::WPA_POST_TYPE,
			$this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' )
		);
	}

	/**
	 * It tests that the constructor throws exceptions when not called the proper type of arguments.
	 *
	 * @test
	 */
	public function it_throws_exception_fon_non_string_or_null_wpa_post_types() {
		$this->expectException( \InvalidArgumentException::class );

		new SubjectViews(
			self::CT_POST_TYPE,
			array(),
			$this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' )
		);
	}

	/**
	 * It test that the class is initialized properly.
	 *
	 * @test
	 */
	public function it_should_initialize() {
		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' )
		);

		// Filters.
		\WP_Mock::expectFilterAdded(
			'toolset/dynamic_sources/filters/get_dynamic_sources_data',
			array( $subject, 'integrate_views_info_for_dynamic_sources' )
		);

		\WP_Mock::expectFilterAdded(
			'toolset/dynamic_sources/filters/shortcode_post',
			array( $subject, 'maybe_get_preview_post_id_for_ct_with_post_content_source' ),
			10,
			4
		);

		\WP_Mock::expectFilterAdded(
			'toolset/dynamic_sources/filters/shortcode_post',
			array( $subject, 'maybe_get_preview_post_id_for_wpa_with_post_content_source' ),
			10,
			4
		);

		\WP_Mock::expectFilterAdded(
			'toolset/dynamic_sources/filters/post_sources',
			array( $subject, 'maybe_exclude_post_content_source_from_post_sources' )
		);

		// Actions.
		\WP_Mock::expectActionAdded(
			'rest_api_init',
			array( $subject, 'register_content_template_preview_post' )
		);

		\WP_Mock::expectActionAdded(
			'rest_api_init',
			array( $subject, 'register_set_content_template_preview_post_rest_api_routes' )
		);

		\WP_Mock::expectActionAdded(
			'toolset/dynamic_sources/filters/post_type_for_source_context',
			array( $subject, 'adjust_post_types_for_source_context_in_cts' ),
			10,
			2
		);

		\WP_Mock::expectActionAdded(
			'toolset/dynamic_sources/filters/post_type_for_source_context',
			array( $subject, 'adjust_post_types_for_source_context_in_view' ),
			10,
			2
		);

		$subject->initialize();
	}

	/**
	 * Data provider for "it_adjusts_post_types_for_source_context_in_cts".
	 *
	 * @return array
	 */
	public function source_context_in_cts_provider() {
		return array(
			'set1' => array(
				'post_type' => 'lorem',
				'post_id' => 0,
				'assigned_post_types' => null,
				'expected_post_type' => 'lorem',
			),
			'set2' => array(
				'post_type' => self::CT_POST_TYPE,
				'post_id' => 1,
				'assigned_post_types' => array( 'lorem', 'ipsum' ),
				'expected_post_type' => array( 'lorem', 'ipsum' ),
			),
			'set3' => array(
				'post_type' => self::CT_POST_TYPE,
				'post_id' => 1,
				'assigned_post_types' => array(),
				'expected_post_type' => array( 'post', 'movie', 'car' ),
				'single_assigned_posts' => array(
					'2' => 'post',
					'3' => 'movie',
					'4' => 'post',
					'5' => 'car',
				),
			),
		);
	}

	/**
	 * It tests that the tested method adjust the post types for source context in Content Templates.
	 *
	 * @param string       $post_type
	 * @param int          $post_id
	 * @param null|array   $assigned_post_types
	 * @param string|array $expected_post_type
	 * @param null|array   $single_assigned_posts
	 *
	 * @test
	 *
	 * @dataProvider source_context_in_cts_provider
	 */
	public function it_adjusts_post_types_for_source_context_in_cts( $post_type, $post_id, $assigned_post_types, $expected_post_type, $single_assigned_posts = null ) {
		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );
		if ( self::CT_POST_TYPE === $post_type ) {
			if ( ! empty( $assigned_post_types ) ) {
				$views_integration_internals_mock
					->expects( $this->once() )
					->method( 'get_assigned_post_types' )
					->with( $post_id )
					->willReturn( $assigned_post_types );
			}

			if (
				empty( $assigned_post_types ) &&
				! empty( $single_assigned_posts )
			) {
				$views_integration_internals_mock->expects( $this->once() )->method( 'maybe_get_single_assigned_posts_for_ct' )->willReturn( array_keys( $single_assigned_posts ) );
				foreach( $single_assigned_posts as $single_assigned_post_id => $single_assigned_post_type ) {
					\WP_Mock::userFunction( 'get_post_type' )->once()->with( $single_assigned_post_id )->andReturn( $single_assigned_post_type );
				}
			}
		}

		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		$this->assertEquals(
			$expected_post_type,
			$subject->adjust_post_types_for_source_context_in_cts( $post_type, $post_id )
		);
	}

	/**
	 * It tests that the tested method registers content template preview post meta.
	 *
	 * @test
	 */
	public function it_registers_content_template_preview_post() {
		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );
		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		\WP_Mock::userFunction( 'register_meta' )
				->with(
					'post',
					'tb_preview_post',
					array(
						'object_subtype' => self::CT_POST_TYPE,
						'show_in_rest' => true,
						'single' => true,
						'type' => 'number',
					)
				)->once();

		$subject->register_content_template_preview_post();
	}

	/**
	 * Data provider for "it_adjusts_post_types_for_source_context_in_views".
	 *
	 * @return array
	 */
	public function source_context_in_views_provider() {
		return array(
			'set1' => array(
				'post_type' => 'lorem',
				'post_id' => 0,
				'is_admin' => true,
				'post_content' => '',
				'has_blocks' => false,
				'blocks' => array(),
				'view_post_types' => array(),
				'expected_post_type' => 'lorem',
			),
			'set2' => array(
				'post_type' => 'lorem',
				'post_id' => 0,
				'is_admin' => false,
				'post_content' => '',
				'has_blocks' => false,
				'blocks' => array(),
				'view_post_types' => array(),
				'expected_post_type' => 'lorem',
			),
			'set3' => array(
				'post_type' => 'lorem',
				'post_id' => 1,
				'is_admin' => false,
				'post_content' => '',
				'has_blocks' => false,
				'blocks' => array(),
				'view_post_types' => array(),
				'expected_post_type' => 'lorem',
			),
			'set4' => array(
				'post_type' => 'lorem',
				'post_id' => 1,
				'is_admin' => false,
				'post_content' => '',
				'has_blocks' => true,
				'blocks' => array(),
				'view_post_types' => array(),
				'expected_post_type' => 'lorem',
			),
			'set5' => array(
				'post_type' => 'lorem',
				'post_id' => 1,
				'is_admin' => false,
				'post_content' => 'toolset-views/view-editor',
				'has_blocks' => true,
				'blocks' => array(),
				'view_post_types' => array(),
				'expected_post_type' => 'lorem',
			),
			'set6' => array(
				'post_type' => 'lorem',
				'post_id' => 1,
				'is_admin' => false,
				'post_content' => 'toolset-views/view-editor',
				'has_blocks' => true,
				'blocks' => array(
					array(
						'blockName' => 'ipsum',
					),
				),
				'view_post_types' => array(),
				'expected_post_type' => 'lorem',
			),
			'set7' => array(
				'post_type' => 'lorem',
				'post_id' => 1,
				'is_admin' => false,
				'post_content' => 'toolset-views/view-editor',
				'has_blocks' => true,
				'blocks' => array(
					array(
						'blockName' => 'toolset-views/view-editor',
					),
				),
				'view_post_types' => array(),
				'expected_post_type' => 'lorem',
			),
			'set8' => array(
				'post_type' => 'lorem',
				'post_id' => 1,
				'is_admin' => false,
				'post_content' => 'toolset-views/view-editor',
				'has_blocks' => true,
				'blocks' => array(
					array(
						'blockName' => 'toolset-views/view-editor',
					),
				),
				'view_post_types' => array( 'ipsum' ),
				'expected_post_type' => array( 'lorem', 'ipsum' ),
			),
		);
	}

	/**
	 * It tests that the tested method adjusts the post types for source context in Views.
	 *
	 * @param string       $post_type
	 * @param int          $post_id
	 * @param bool         $is_admin
	 * @param string       $post_content
	 * @param bool         $has_blocks
	 * @param array        $blocks
	 * @param array        $view_post_types
	 * @param string|array $expected_post_type
	 *
	 * @test
	 *
	 * @dataProvider source_context_in_views_provider
	 */
	public function it_adjusts_post_types_for_source_context_in_views(
		$post_type,
		$post_id,
		$is_admin,
		$post_content,
		$has_blocks,
		$blocks,
		$view_post_types,
		$expected_post_type
	) {
		// Mocks.
		$post_mock = $this->getMockBuilder( 'WP_Post_Mock' )->setMockClassName( 'WP_Post' )->getMock();
		$post_mock->post_content = $post_content;
		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );

		// User functions.
		\WP_Mock::userFunction( 'is_admin' )->once()->andReturn( $is_admin );
		if ( ! $is_admin && $post_id ) {
			\WP_Mock::userFunction( 'get_post' )->once()->with( $post_id )->andReturn( $post_mock );
			\WP_Mock::userFunction( 'has_blocks' )->once()->with( $post_content )->andReturn( $has_blocks );

			if (
				$has_blocks &&
				false !== strpos( $post_content, 'toolset-views/view-editor' ) &&
				strpos( $post_content, 'toolset-views/view-editor' ) >= 0
			) {
				\WP_Mock::userFunction( 'parse_blocks' )->once()->with( $post_content )->andReturn( $blocks );
				foreach ( $blocks as $block ) {
					if (
						isset( $block['blockName'] ) &&
						'toolset-views/view-editor' === $block['blockName']
					) {
						$views_integration_internals_mock
							->expects( $this->once() )
							->method( 'maybe_get_view_block_post_types' )
							->with( $block )
							->willReturn( $view_post_types );
					}
				}
			}
		}

		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		$this->assertEquals( $expected_post_type, $subject->adjust_post_types_for_source_context_in_view( $post_type, $post_id ) );
	}

	/**
	 * Data provider for "maybe_it_gets_preview_post_id_for_ct_with_post_content_source".
	 *
	 * @return array
	 */
	public function preview_post_id_for_ct() {
		return array(
			'set1' => array(
				'post' => '123',
				'post_provider' => '',
				'source' => '',
				'field' => '',
				'is_ct' => false,
				'preview_post' => 0,
			),
			'set2' => array(
				'post' => '123',
				'post_provider' => '',
				'source' => 'post-content',
				'field' => '',
				'is_ct' => false,
				'preview_post' => 0,
			),
			'set3' => array(
				'post' => '123',
				'post_provider' => '',
				'source' => 'post-content',
				'field' => '',
				'is_ct' => true,
				'preview_post' => 0,
			),
			'set4' => array(
				'post' => '123',
				'post_provider' => '',
				'source' => 'post-content',
				'field' => '',
				'is_ct' => true,
				'preview_post' => 1,
			),
		);
	}

	/**
	 * It tests that the tested method get the preview post id for Content Templates that include a block with the post content source.
	 *
	 * @param string $post
	 * @param string $post_provider
	 * @param string $source
	 * @param string $field
	 * @param bool   $is_ct
	 * @param int    $preview_post
	 *
	 * @test
	 *
	 * @dataProvider preview_post_id_for_ct
	 */
	public function maybe_it_gets_preview_post_id_for_ct_with_post_content_source(
		$post,
		$post_provider,
		$source,
		$field,
		$is_ct,
		$preview_post
	) {
		$abs_int_preview_post = (int) $preview_post;
		$expected_post = $post;

		// Mocks.
		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );

		// User functions.
		if ( 'post-content' === $source ) {
			\WP_Mock::userFunction( 'get_post_type' )->once()->with( $post )->andReturn( $is_ct ? self::CT_POST_TYPE : '' );
			if ( $is_ct ) {
				\WP_Mock::userFunction( 'get_post_meta' )->once()->with( $post, 'tb_preview_post', true )->andReturn( $preview_post );
				\WP_Mock::userFunction( 'absint' )->once()->with( $preview_post )->andReturn( $abs_int_preview_post );

				if ( $abs_int_preview_post <= 0 ) {
					$expected_post = null;
				} else {
					$expected_post = $abs_int_preview_post;
				}
			}
		}

		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		$this->assertEquals(
			$expected_post,
			$subject->maybe_get_preview_post_id_for_ct_with_post_content_source( $post, $post_provider, $source, $field )
		);
	}

	/**
	 * Data provider for "maybe_it_gets_preview_post_id_for_wpa_with_post_content_source".
	 *
	 * @return array
	 */
	public function preview_post_id_for_wpa() {
		return array(
			'set1' => array(
				'post_provider' => '',
				'source' => '',
				'field' => '',
				'is_wpa' => false,
			),
			'set2' => array(
				'post_provider' => '',
				'source' => 'post-content',
				'field' => '',
				'is_wpa' => false,
			),
			'set3' => array(
				'post_provider' => '',
				'source' => 'post-content',
				'field' => '',
				'is_wpa' => true,
			),
		);
	}

	/**
	 * It tests that the tested method get the preview post id for WordPress Archives that include a block with the post content source.
	 *
	 * @param string $post_provider
	 * @param string $source
	 * @param string $field
	 * @param bool   $is_wpa
	 *
	 * @test
	 *
	 * @dataProvider preview_post_id_for_wpa
	 */
	public function maybe_it_gets_preview_post_id_for_wpa_with_post_content_source( $post_provider, $source, $field, $is_wpa ) {
		$post = 1;

		// Mocks.
		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );

		// User functions.
		if ( 'post-content' === $source ) {
			\WP_Mock::userFunction( 'get_post_type' )->once()->with( $post )->andReturn( $is_wpa ? self::WPA_POST_TYPE : '' );
		}

		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		$this->assertEquals(
			! $is_wpa ? $post : null,
			$subject->maybe_get_preview_post_id_for_wpa_with_post_content_source( $post, $post_provider, $source, $field )
		);
	}

	/**
	 * Data provider for "maybe_excludes_post_content_source_from_post_sources".
	 *
	 * @return array
	 */
	public function exclude_post_content_source_provider() {
		return array(
			'set1' => array(
				'post_sources' => array(),
				'get_post' => null,
				'page_now' => '',
				'is_ct_or_wpa' => true,
				'expected_sources' => array(),
			),
			'set2' => array(
				'post_sources' => array( 'Lorem' ),
				'get_post' => 2,
				'page_now' => '',
				'is_ct_or_wpa' => true,
				'expected_sources' => array( 'Lorem' ),
			),
			'set3' => array(
				'post_sources' => array( 'Lorem' ),
				'get_post' => 3,
				'page_now' => 'lorem',
				'is_ct_or_wpa' => true,
				'expected_sources' => array( 'Lorem' ),
			),
			'set4' => array(
				'post_sources' => array( 'Lorem' ),
				'get_post' => 4,
				'page_now' => 'post.php',
				'is_ct_or_wpa' => true,
				'expected_sources' => array( 'Lorem' ),
			),
			'set5' => array(
				'post_sources' => array( 'Lorem' ),
				'get_post' => 5,
				'page_now' => 'post.php',
				'is_ct_or_wpa' => true,
				'expected_sources' => array( 'Lorem' ),
			),
			'set6' => array(
				'post_sources' => array( 'Lorem', 'PostContent' ),
				'get_post' => 6,
				'page_now' => 'post.php',
				'is_ct_or_wpa' => false,
				'expected_sources' => array( 'Lorem' ),
			),
			'set7' => array(
				'post_sources' => array( 'Lorem', 'PostContent' ),
				'get_post' => 7,
				'page_now' => 'post-new.php',
				'is_ct_or_wpa' => false,
				'expected_sources' => array( 'Lorem' ),
			),
		);
	}

	/**
	 * It tests that the post content source is removed from the set of sources when not needed.
	 *
	 * @param array  $post_sources
	 * @param int    $get_post
	 * @param string $page_now
	 * @param bool   $is_ct_or_wpa
	 * @param array  $expected_sources
	 *
	 * @test
	 *
	 * @dataProvider exclude_post_content_source_provider
	 */
	public function maybe_excludes_post_content_source_from_post_sources( $post_sources, $get_post, $page_now, $is_ct_or_wpa, $expected_sources ) {
		global $pagenow;
		$pagenow = $page_now;

		$_GET['post'] = $get_post;

		// Mocks.
		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );

		// User functions.
		$post = $get_post ? $get_post : 0;
		\WP_Mock::userFunction( 'sanitize_text_field' )->once()->with( $post )->andReturn( (int) $post );
		if ( 'post.php' === $page_now ) {
			\WP_Mock::userFunction( 'get_post_type' )->once()->with( $post )->andReturn( $is_ct_or_wpa ? self::CT_POST_TYPE : '' );
		}

		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		$this->assertEquals(
			$expected_sources,
			$subject->maybe_exclude_post_content_source_from_post_sources( $post_sources )
		);

		$pagenow = null;
		$_GET['post'] = null;
	}

	/**
	 * It tests that the routes for saving the Content Template preview post ID are registered properly
	 *
	 * @test
	 */
	public function it_registers_set_content_template_preview_post_rest_api_routes() {
		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );
		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		$args = array(
			'methods' => \WP_REST_Server::CREATABLE,
			'callback' => array( $subject, 'set_preview_post' ),
			'args' => array(
				'ctId' => array(
					'required' => true,
					'validate_callback' => array( $subject, 'set_preview_post_argument_validation' ),
					'sanitize_callback' => 'absint',
				),
				'previewPostId' => array(
					'required' => true,
					'validate_callback' => array( $subject, 'set_preview_post_argument_validation' ),
					'sanitize_callback' => 'absint',
				),
			),
			'permission_callback' => array( $subject, 'set_preview_post_permission_callback' ),
		);

		\WP_Mock::userFunction( 'register_rest_route' )->with(
			'toolset-dynamic-sources/v1',
			'/preview-post',
			$args
		)->once();

		$subject->register_set_content_template_preview_post_rest_api_routes();
	}

	/**
	 * It tests that the parameters for the set preview post ID REST API endpoint are valid.
	 *
	 * @test
	 */
	public function it_validates_the_set_preview_post_argument() {
		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );
		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		$this->assertFalse( $subject->set_preview_post_argument_validation( 'lorem' ) );

		$this->assertTrue( $subject->set_preview_post_argument_validation( '123' ) );

		$this->assertTrue( $subject->set_preview_post_argument_validation( 123 ) );
	}

	/**
	 * It tests that it is validated that the current user has the right permissions to access the set preview post ID REST API endpoint.
	 *
	 * @test
	 */
	public function it_validates_the_permission_for_accessing_the_set_preview_post_endpoint() {
		$wp_rest_request_mock = $this
			->getMockBuilder( 'WP_REST_Request_mock' )
			->setMockClassName( 'WP_REST_Request' )
			->setMethods( array( 'get_param' ) )
			->getMock();

		$ct_id = 1;
		$wp_rest_request_mock->expects( $this->once() )->method( 'get_param' )->with( 'ctId' )->willReturn( $ct_id );

		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );
		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		\WP_Mock::userFunction( 'current_user_can' )->with( 'edit_post', $ct_id );

		$subject->set_preview_post_permission_callback( $wp_rest_request_mock );
	}

	/**
	 * It tests that the the preview post ID is properly set.
	 *
	 * @test
	 */
	public function it_sets_the_preview_post() {
		$wp_rest_request_mock = $this
			->getMockBuilder( 'WP_REST_Request_mock' )
			->setMockClassName( 'WP_REST_Request' )
			->setMethods( array( 'get_param' ) )
			->getMock();

		$ct_id = 1;
		$preview_post_id = 2;
		$wp_rest_request_mock
			->method( 'get_param' )
			->withConsecutive(
				array( 'ctId' ),
				array( 'previewPostId' )
			)
			->willReturnOnConsecutiveCalls(
				$ct_id,
				$preview_post_id
			);

		$views_integration_internals_mock = $this->createMock( '\Toolset\DynamicSources\Integrations\Views\Internals' );
		$subject = new SubjectViews(
			self::CT_POST_TYPE,
			self::WPA_POST_TYPE,
			$views_integration_internals_mock
		);

		\WP_Mock::userFunction( 'update_post_meta' )->with( $ct_id, 'tb_preview_post', $preview_post_id )->andReturn( true );

		\WP_Mock::userFunction( 'rest_ensure_response' )->with( true )->andReturn( true );

		$this->assertTrue( $subject->set_preview_post( $wp_rest_request_mock ) );
	}
}
