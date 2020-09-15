<?php

namespace OTGS\Toolset\DynamicSources\Tests\ToolsetSources;

use OTGS_TestCase;
use stdClass;
use Toolset\DynamicSources\ToolsetSources\RelatedPostProvider;
use Toolset\DynamicSources\ToolsetSources\RelationshipService;
use Toolset\DynamicSources\ToolsetSources\PostRelationshipModel;
use Toolset\DynamicSources\ToolsetSources\RelationshipRole;
use WP_Mock;

class RelatedPostProviderTest extends OTGS_TestCase {
	/**
	 * Note that we are using real model classes here, and not mocks. For the purpose of current tests, these classes
	 * just contain the data given to them in constructors, without side effects, so no need to mock them. But thread
	 * carefully if adding more tests - this may not be the case in all circumstances.
	 *
	 * @return array[]
	 */
	public function provide_relationships() {
		$labels = new stdClass();
		$labels->singular_name = 'Page';
		$post_type_object = new stdClass();
		$post_type_object->labels = $labels;

		$labels_2 = new stdClass();
		$labels_2->singular_name = 'Post';
		$post_type_object_2 = new stdClass();
		$post_type_object_2->labels = $labels_2;

		return [
			[
				new PostRelationshipModel( [
					'roles' => [ 'intermediary' => true, 'parent' => [ 'types' => [ 0 => 'page' ] ] ]
				] ),
				new RelationshipRole( 'parent', 'Parent' ),
				'Related: Page',
				false,
				$post_type_object
			],
			[
				new PostRelationshipModel( [
					'roles' => [ 'parent' => [ 'types' => [ 0 => 'page' ] ], 'child' => [ 'types' => [ 0 => 'post' ] ] ]
				] ),
				new RelationshipRole( 'parent', 'Parent' ),
				'Parent: Page',
				false,
				$post_type_object
			],
			[
				new PostRelationshipModel( [
					'roles' => [ 'parent' => [ 'types' => [ 0 => 'page' ] ], 'child' => [ 'types' => [ 0 => 'post' ] ] ]
				] ),
				new RelationshipRole( 'child', 'Child' ),
				'Child: Post',
				false,
				$post_type_object_2
			],
			[
				new PostRelationshipModel( [
					'roles' => [
						'parent' => [ 'types' => [ 0 => 'page' ] ],
						'child' => [ 'types' => [ 0 => 'post' ] ]
					],
					'labels' => [ 'plural' => 'Relationship' ],
				] ),
				new RelationshipRole( 'child', 'Child' ),
				'Child: Post (Relationship)',
				true,
				$post_type_object_2
			],
		];
	}

	/**
	 * @test
	 * @dataProvider provide_relationships
	 *
	 * @param PostRelationshipModel $relationship_model
	 * @param RelationshipRole $relationship_role
	 * @param string $expected_label
	 * @param boolean $duplicate
	 * @param stdClass $post_type_object
	 */
	public function it_returns_correct_label_depending_on_relationship(
		PostRelationshipModel $relationship_model,
		RelationshipRole $relationship_role,
		$expected_label,
		$duplicate,
		$post_type_object
	) {
		WP_Mock::userFunction( 'get_post_type_object' )
			->once()
			->andReturn( $post_type_object );

		$subject = new RelatedPostProvider( $relationship_model, $relationship_role, new RelationshipService() );

		$this->assertSame( $expected_label, $subject->get_label( $duplicate ) );
	}
}
