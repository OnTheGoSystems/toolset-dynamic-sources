<?php

namespace OTGS\Toolset\DynamicSources\Tests\ToolsetSources;

use OTGS_TestCase;
use Toolset\DynamicSources\ToolsetSources\Main;
use Toolset\DynamicSources\ToolsetSources\RelationshipService;
use Toolset\DynamicSources\ToolsetSources\CustomFieldService;
use WP_Mock;

class MainTest extends OTGS_TestCase {
	/** @var Main */
	private $subject;

	public function setUp() {
		parent::setUp();

		// These two services don't have much side effects of their own, so they are relatively safe to use as they are,
		// at least for the tests here. No need for mocks.
		$this->subject = new Main( new RelationshipService(), new CustomFieldService() );
	}

	/**
	 * @test
	 */
	public function it_initializes_needed_filters() {
		WP_Mock::onFilter( 'types_is_active' )->with( false )->reply( true );

		WP_Mock::expectFilterAdded( 'toolset/dynamic_sources/filters/register_post_providers', function(){}, 10, 2 );
		WP_Mock::expectFilterAdded( 'toolset/dynamic_sources/filters/groups', function(){} );
		WP_Mock::expectFilterAdded( 'toolset/dynamic_sources/filters/register_sources', function(){}, 10, 2 );
		WP_Mock::expectFilterAdded( 'toolset/dynamic_sources/filters/cache', function(){}, 10, 2 );

		$this->subject->initialize();
	}

	/**
	 * @test
	 */
	public function it_doesnt_initialize_filters_if_types_not_active() {
		WP_Mock::onFilter( 'types_is_active' )->with( false )->reply( false );

		WP_Mock::expectFilterNotAdded( 'toolset/dynamic_sources/filters/register_post_providers', function(){} );
		WP_Mock::expectFilterNotAdded( 'toolset/dynamic_sources/filters/groups', function(){} );
		WP_Mock::expectFilterNotAdded( 'toolset/dynamic_sources/filters/register_sources', function(){} );
		WP_Mock::expectFilterNotAdded( 'toolset/dynamic_sources/filters/cache', function(){} );

		$this->subject->initialize();
	}
}
