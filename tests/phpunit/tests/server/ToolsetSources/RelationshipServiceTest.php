<?php

namespace OTGS\Toolset\DynamicSources\Tests\ToolsetSources;

use Mockery;
use OTGS_TestCase;
use Toolset\DynamicSources\ToolsetSources\PostRelationshipModel;
use Toolset\DynamicSources\ToolsetSources\RelationshipService;
use WP_Mock;

class RelationshipServiceTest extends OTGS_TestCase {
	/** @var RelationshipService $subject */
	private $subject;

	public function setUp() {
		parent::setUp();

		$this->subject = new RelationshipService();
	}

	/**
	 * This tests the default, with unnamed role label. We don't need to mock
	 * Toolset_Relationship_Definition_Repository here, because it's not called in this code path.
	 *
	 * @test
	 */
	public function it_returns_correct_role() {
		$role = $this->subject->get_role_from_name( 'child' );

		$this->assertInstanceOf( 'Toolset\DynamicSources\ToolsetSources\RelationshipRole', $role );
		$this->assertSame( 'child', $role->get_name() );
		$this->assertSame( 'Child', $role->get_label() );

		$role2 = $this->subject->get_role_from_name( 'parent' );

		$this->assertSame( 'parent', $role2->get_name() );
		$this->assertSame( 'Parent', $role2->get_label() );
	}

	/**
	 * @test
	 */
	public function it_returns_named_role() {
		$definition_stub = $this->getMockBuilder( 'Toolset_Relationship_Definition' )
			->setMethods( [ 'get_role_label_singular' ] )
			->getMock();
		$definition_stub->expects( $this->once() )
			->method( 'get_role_label_singular' )
			->willReturn( 'owner' );

		$repository_stub = Mockery::mock( 'alias:Toolset_Relationship_Definition_Repository' );
		$repository_stub->shouldReceive( 'get_definition' )
			->andReturn( $definition_stub );

		$role = $this->subject->get_role_from_name( 'parent', 'owner', $repository_stub );

		$this->assertSame( 'parent', $role->get_name() );
		$this->assertSame( 'Owner', $role->get_label() );
	}

	/**
	 * @test
	 * @dataProvider provide_relationships
	 *
	 * @param int $related_to_post
	 * @param PostRelationshipModel $relationship
	 * @param string $target_role
	 */
	public function it_returns_related_posts( $related_to_post, PostRelationshipModel $relationship, $target_role ) {
		WP_Mock::userFunction( 'toolset_get_related_post' )
			->withArgs( [ $related_to_post, $relationship->get_slug(), $target_role ] )
			->once()
			->andReturn( 2 );

		$this->assertSame(
			2,
			$this->subject->get_related_post( $related_to_post, $relationship, $target_role )
		);
	}

	/**
	 * @return array[]
	 */
	public function provide_relationships() {
		return [
			[
				1,
				new PostRelationshipModel( [
					'roles' => [
						'parent' => [ 'types' => [ 0 => 'page' ] ],
						'child' => [ 'types' => [ 0 => 'post' ] ],
					],
					'slug' => 'page-post',
				] ),
				'parent',
			],
		];
	}
}
