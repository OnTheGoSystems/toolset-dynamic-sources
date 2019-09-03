<?php

namespace OTGS\Toolset\DynamicSources\Tests\Integrations;

class DynamicSourcesTest extends \OTGS\Toolset\DynamicSources\TestCase {
	public function provide_boolean() {
		return [
			[ true ],
			[ false ],
		];
	}

	/**
	 * @param bool $initialized
	 *
	 * @test
	 * @dataProvider provide_boolean
	 */
	public function maybe_initialize( $initialized = false ) {
		\WP_Mock::userFunction( 'did_action', array(
			'args' => 'toolset/dynamic_sources/actions/toolset_dynamic_sources_initialize',
			'return' => $initialized ? 2 : 1,
		) );

		$subject = new \Toolset\DynamicSources\DynamicSources();

		$this->initialize_sequence( $subject, $initialized );

		$subject->initialize();
	}

	private function initialize_sequence( $subject, $initialized = false ) {
		$this->initialize_actions( $subject, $initialized );
		$this->initialize_filters( $subject, $initialized );
		$this->initialize_shortcodes( $subject, $initialized );
	}

	private function initialize_shortcodes( $subject, $initialized ) {
		$times = $initialized ? 0 : 1;

		\WP_Mock::wpFunction(
			'add_shortcode',
			array(
				'times' => $times,
				'args' => array(
					'tb-dynamic-container',
					array( $subject, 'dynamic_container_shortcode_render' )
				),
			)
		);

		\WP_Mock::wpFunction(
			'add_shortcode',
			array(
				'times' => $times,
				'args' => array(
					$subject::SHORTCODE,
					array( $subject, 'dynamic_shortcode_render' )
				),
			)
		);
	}

	private function initialize_filters( $subject, $initialized ) {
		$method = $initialized ? '\WP_Mock::expectFilterNotAdded' : '\WP_Mock::expectFilterAdded';

		call_user_func_array(
			$method,
			array(
				'toolset/dynamic_sources/filters/get_post_providers',
				array( $subject, 'get_post_providers' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'toolset/dynamic_sources/filters/get_post_providers_for_select',
				array( $subject, 'get_post_providers_for_select' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'toolset/dynamic_sources/filters/get_grouped_sources',
				array( $subject, 'get_grouped_sources' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'toolset/dynamic_sources/filters/get_source_fields',
				array( $subject, 'get_source_fields' ),
				10,
				4,
			)
		);

		call_user_func_array(
			$method,
			array(
				'toolset/dynamic_sources/filters/get_source_content',
				array( $subject, 'get_source_content' ),
				10,
				5,
			)
		);

		call_user_func_array(
			$method,
			array(
				'toolset/dynamic_sources/filters/get_dynamic_sources_data',
				array( $subject, 'get_dynamic_sources_data' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'the_content',
				array( $subject, 'shortcode_render' ),
				-1
			)
		);

		call_user_func_array(
			$method,
			array(
				'wpv-pre-do-shortcode',
				array( $subject, 'shortcode_render' ),
				-1
			)
		);

		call_user_func_array(
			$method,
			array(
				'wpv_filter_content_template_output',
				array( $subject, 'shortcode_render' ),
				-1
			)
		);

		call_user_func_array(
			$method,
			array(
				'toolset/dynamic_sources/filters/register_post_providers',
				array( $subject, 'set_custom_post_provider' ),
				10000
			)
		);
	}

	private function initialize_actions( $subject, $initialized ) {
		$method = $initialized ? '\WP_Mock::expectActionNotAdded' : '\WP_Mock::expectActionAdded';

		call_user_func_array(
			$method,
			array(
				'init',
				array( $subject, 'initialize_toolset_fields_sources' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'init',
				array( $subject, 'initialize_sources' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'init',
				array( $subject, 'initialize_other_fields_sources' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'init',
				array( $subject, 'initialize_views_integration' ),
				9
			)
		);

		call_user_func_array(
			$method,
			array(
				'rest_api_init',
				array( $subject, 'initialize_rest' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'enqueue_block_editor_assets',
				array( $subject, 'register_sources' ),
				1,
			)
		);

		call_user_func_array(
			$method,
			array(
				'toolset/dynamic_sources/actions/register_sources',
				array( $subject, 'register_sources' ),
			)
		);

		call_user_func_array(
			$method,
			array(
				'enqueue_block_editor_assets',
				array( $subject, 'register_localization_data' ),
			)
		);
	}

	/**
	 * @test
	 */
	public function it_registers_dynamic_sources_for_shortcode_render() {
		$subject = new \Toolset\DynamicSources\DynamicSources();
		$post = 1;
		$post_type = 'post';

		\WP_Mock::passthruFunction( 'sanitize_text_field' );
		\WP_Mock::userFunction( 'get_the_id' )->andReturn( $post );
		\WP_Mock::userFunction( 'get_post_type' )->andReturn( $post_type );
		\WP_Mock::userFunction( 'shortcode_atts' )->andReturnUsing(
			function( $defaults, $atts ) {
				return array_merge( $defaults, $atts );
			}
		);

		\WP_Mock::expectAction( 'toolset/dynamic_sources/actions/register_sources' );

		$subject->dynamic_shortcode_render( array() );
	}
}
