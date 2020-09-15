<?php

namespace OTGS\Toolset\DynamicSources\Tests\PostProviders;

use OTGS\Toolset\DynamicSources\TestCase;
use Toolset\DynamicSources\PostProviders\PostProviders;

class PostProvidersTest extends TestCase {
	/**
	 * It tests that the class is initialized properly.
	 *
	 * @test
	 */
	public function it_initializes() {
		$custom_post_factory_mock = $this->createMock( 'Toolset\DynamicSources\PostProviders\CustomPostFactory' );
		$subject = new PostProviders( $custom_post_factory_mock );
		$this->expectFilterAdded('toolset/dynamic_sources/filters/register_post_providers', array( $subject, 'set_custom_post_provider' ), 10000 );
		$subject->initialize();
	}

	/**
	 * Data provider for the method "it_sets_custom_post_provider_data_provider".
	 *
	 * @return array
	 */
	public function it_sets_custom_post_provider_data_provider() {
		return array(
			'set1' => array(
				'content' => null,
				'post_content' => null,
				'blocks' => null,
			),
			'set2' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(),
			),
			'set3' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
					)
				),
			),
			'set4' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array(),
					)
				),
			),
			'set5' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array( 'ref' => 2 ),
						'post_with_ref_id_exists' => false,
					)
				),
			),
			'set6' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array( 'ref' => 2 ),
						'post_with_ref_id_exists' => true,
						'is_reusable_block' => false,
					)
				),
			),
			'set7' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array( 'ref' => 2 ),
						'post_with_ref_id_exists' => true,
						'is_reusable_block' => true,
						'reusable_block_published' => false,
					)
				),
			),
			'set8' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array( 'ref' => 2 ),
						'post_with_ref_id_exists' => true,
						'is_reusable_block' => true,
						'reusable_block_published' => true,
						'reusable_block_password' => 'asd'
					)
				),
			),
			'set9' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array( 'ref' => 2 ),
						'post_with_ref_id_exists' => true,
						'is_reusable_block' => true,
						'reusable_block_published' => true,
						'reusable_block_password' => ''
					)
				),
			),
			'set10' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array( 'ref' => 2 ),
						'post_with_ref_id_exists' => true,
						'is_reusable_block' => true,
						'reusable_block_published' => true,
						'reusable_block_password' => '',
						'reusable_block_content' => 'lorem',
					)
				),
			),
			'set11' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array( 'ref' => 2 ),
						'post_with_ref_id_exists' => true,
						'is_reusable_block' => true,
						'reusable_block_published' => true,
						'reusable_block_password' => '',
						'reusable_block_content' => '[tb-dynamic provider="custom_post_type|posta|60" post="current" source="toolset_custom_field|post-a-cf" field="a-url" force-string="first" ]',
					)
				),
			),
			'set11' => array(
				'content' => 'lorem',
				'post_content' => null,
				'blocks' => array(
					'block1' => array(
						'lorem' => array(),
						'attrs' => array( 'ref' => 2 ),
						'post_with_ref_id_exists' => true,
						'is_reusable_block' => true,
						'reusable_block_published' => true,
						'reusable_block_password' => '',
						'reusable_block_content' => '[tb-dynamic provider="custom_post_type|posta|60" post="current" source="toolset_custom_field|post-a-cf" field="a-url" force-string="first" ]',
					)
				),
			),
			'set12' => array(
				'content' => null,
				'post_content' => '[tb-dynamic provider="custom_post_type|posta|60" post="current" source="toolset_custom_field|post-a-cf" field="a-url" force-string="first" ]',
				'blocks' => array(),
			),
		);
	}

	/**
	 * It tests that the custom post provider is set whenever it is relevant.
	 *
	 * @test
	 *
	 * @dataProvider it_sets_custom_post_provider_data_provider
	 */
	public function it_sets_custom_post_provider( $content, $post_content, $blocks ) {
		global $post;

		$custom_post_unique_slug = 'custom_post';
		$custom_post_mock = $this->createMock( 'Toolset\DynamicSources\PostProviders\CustomPost' );
		$custom_post_mock->expects( $this->at( 0 ) )->method( 'get_unique_slug' )->willReturn( $custom_post_unique_slug );

		$custom_post_factory_mock = $this->createMock( 'Toolset\DynamicSources\PostProviders\CustomPostFactory' );
		$custom_post_factory_mock->expects( $this->at( 0 ) )->method( 'create_custom_post' )->willReturn( $custom_post_mock );

		$providers = array( 'lorem' );
		$expected_providers =
			array_merge(
				$providers,
				array(
					$custom_post_unique_slug => $custom_post_mock,
				)
			);

		$content_for_block_parsing = $content;

		if ( null !== $post_content) {
			$global_post_mock = $this->getMockBuilder( 'WP_Post_Mock' )->setMockClassName( 'WP_Post' )->getMock();
			$global_post_mock->post_content = $post_content;
			$post = $global_post_mock;
			$content_for_block_parsing = $post_content;
		}

		\WP_Mock::userFunction( 'parse_blocks' )->with( $content_for_block_parsing )->andReturn( $blocks );

		if ( $blocks ) {
			foreach ( $blocks as $block ) {
				if ( isset( $block['attrs'] ) && ! empty( $block['attrs']['ref'] ) ) {
					$reusable_block_mock = $this->getMockBuilder( 'WP_Post_Mock' )->setMockClassName( 'WP_Post' )->getMock();
					$reusable_block_mock->post_type =
						isset( $block[ 'is_reusable_block' ] ) && $block[ 'is_reusable_block' ] ?
						'wp_block' :
						'dummy_post_type';
					$reusable_block_mock->post_status =
						isset( $block[ 'reusable_block_published' ] ) && $block[ 'reusable_block_published' ] ?
						'publish' :
						'draft';
					$reusable_block_mock->post_password =
						isset( $block[ 'reusable_block_password' ] ) ?
							$block[ 'reusable_block_password' ] :
							'';
					$reusable_block_mock->post_content =
						isset( $block[ 'reusable_block_content' ] ) ?
							$block[ 'reusable_block_content' ] :
							'';
					\WP_Mock::userFunction( 'get_post' )->with( $block['attrs']['ref'] )->andReturn( $block[ 'post_with_ref_id_exists' ] ? $reusable_block_mock : null );
					if ( preg_match( \Toolset\DynamicSources\DynamicSources::CUSTOM_POST_TYPE_REGEXP, $reusable_block_mock->post_content ) ) {
						$another_custom_post_unique_slug = 'another_custom_post';
						$another_custom_post_mock = $this->createMock( 'Toolset\DynamicSources\PostProviders\CustomPost' );
						$another_custom_post_mock->expects( $this->once() )->method( 'get_unique_slug' )->willReturn( $another_custom_post_unique_slug );
						$custom_post_factory_mock
							->expects( $this->at( 1 ) )
							->method( 'create_custom_post' )
							->with( 'posta', '60' )
							->willReturn( $another_custom_post_mock );
						$expected_providers[ $another_custom_post_unique_slug ] = $another_custom_post_mock;
					}
				}
			}
		}

		if (
			null !== $post_content &&
			preg_match( \Toolset\DynamicSources\DynamicSources::CUSTOM_POST_TYPE_REGEXP, $post_content )
		) {
			$yet_another_custom_post_unique_slug = 'yet_another_custom_post';
			$yet_another_custom_post_mock = $this->createMock( 'Toolset\DynamicSources\PostProviders\CustomPost' );
			$yet_another_custom_post_mock->expects( $this->once() )->method( 'get_unique_slug' )->willReturn( $yet_another_custom_post_unique_slug );
			$custom_post_factory_mock
				->expects( $this->at( 1 ) )
				->method( 'create_custom_post' )
				->with( 'posta', '60' )
				->willReturn( $yet_another_custom_post_mock );
			$expected_providers[ $yet_another_custom_post_unique_slug ] = $yet_another_custom_post_mock;
		}

		$subject = new PostProviders( $custom_post_factory_mock );
		$this->assertEquals( $expected_providers, $subject->set_custom_post_provider( $providers, $content ) );

		$post = null;
	}
}
