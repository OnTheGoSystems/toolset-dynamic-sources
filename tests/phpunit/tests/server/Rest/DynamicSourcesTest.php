<?php

namespace OTGS\Toolset\DynamicSources\Tests\ToolsetSources;

use OTGS_TestCase;
use Toolset\DynamicSources\Rest\DynamicSources;
use Mockery;
use WP_Mock;

class DynamicSourcesTest extends OTGS_TestCase {
	const EXPECTED_RESULT = 'expected result';

	/** @var DynamicSources */
	private $subject;

	public function setUp() {
		parent::setUp();

		$this->subject = new DynamicSources();
	}

	/**
	 * @test
	 */
	public function it_will_return_empty_array_if_not_given_preview_post_id() {
		$wp_rest_request_stub = Mockery::mock( 'WP_REST_Request' );
		$wp_rest_request_stub->shouldReceive( 'get_param' )
			->times( 3 )
			->andReturn( null );

		WP_Mock::passthruFunction( 'absint', [ 'times' => 1 ] );

		$result = $this->subject->get_dynamic_sources( $wp_rest_request_stub );

		$this->assertEmpty( $result );
	}

	/**
	 * @test
	 */
	public function it_will_run_an_action_and_a_filter_to_provide_data_when_given_post_id() {
		$wp_rest_request_stub = Mockery::mock( 'WP_REST_Request' );
		$wp_rest_request_stub->shouldReceive( 'get_param' )
			->times( 3 )
			->andReturn( 1 );

		WP_Mock::passthruFunction( 'absint', [ 'times' => 1 ] );

		WP_Mock::expectAction( 'toolset/dynamic_sources/actions/register_sources', 1 );

		WP_Mock::onFilter( 'toolset/dynamic_sources/filters/get_dynamic_sources_data' )
			->with( [ 'previewPostId' => 1 ] )
			->reply( self::EXPECTED_RESULT );

		$result = $this->subject->get_dynamic_sources( $wp_rest_request_stub );

		$this->assertSame( self::EXPECTED_RESULT, $result );
	}
}
