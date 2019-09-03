<?php

namespace Toolset\DynamicSources\ToolsetSources;

/**
 * Layer for communicating with Toolset Common regarding relationships.
 */
class RelationshipService {

	/**
	 * @var RelationshipRole
	 */
	private $roles;


	/**
	 * RelationshipService constructor.
	 */
	public function __construct() {
		$this->roles = [
			'parent' => new RelationshipRole( 'parent', __( 'Parent', 'wpv-views' ) ),
			'child' => new RelationshipRole( 'child', __( 'Child', 'wpv-views' ) ),
		];
	}

	/**
	 * For a given post type, return relationships that can be used for dynamic content sources.
	 * That means relationships, where we can have only a single post on the other side.
	 *
	 * @param string $post_type_slug
	 *
	 * @return PostRelationshipModel[]
	 */
	public function get_relationships_acceptable_for_sources( $post_type_slug ) {

		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			return [];
		}

		$base_args = [
			'type_constraints' => [
				'parent' => [
					'domain' => 'posts',
				],
				'child' => [
					'domain' => 'posts',
				],
			],
			'origin' => 'any',
		];

		$one_to_many_args = $base_args;
		$one_to_many_args['cardinality'] = 'one-to-many';
		$one_to_many_args['type_constraints']['child']['type'] = $post_type_slug;

		$one_to_many_relationships = toolset_get_relationships( $one_to_many_args );

		$one_to_one_args = $base_args;
		$one_to_one_args['cardinality'] = 'one-to-one';
		$one_to_one_args['type_constraints']['any']['type'] = $post_type_slug;

		$one_to_one_relationships = toolset_get_relationships( $one_to_one_args );

		$relationship_models = array_map( function( $definition_array ) {
			return new PostRelationshipModel( $definition_array );
		}, array_merge( $one_to_one_relationships, $one_to_many_relationships ) );

		return $relationship_models;
	}


	/**
	 * Obtain a related post.
	 *
	 * @param int|\WP_Post $related_to_post Post to which the result needs to be related, or its ID.
	 * @param string $relationship_slug Slug of the relationship.
	 * @param string $target_role Role of the returned post.
	 *
	 * @return int|null
	 */
	public function get_related_post( $related_to_post, $relationship_slug, $target_role ) {
		$post_id = toolset_get_related_post( $related_to_post, $relationship_slug, $target_role );

		return $post_id;
	}


	/**
	 * @param string $role_name 'parent'|'child'
	 *
	 * @return string 'child'|'parent'
	 */
	public function get_other_role_name( $role_name ) {
		return ( 'parent' === $role_name ? 'child' : 'parent' );
	}


	/**
	 * @param string $role_name 'parent'|'child'
	 *
	 * @return RelationshipRole
	 */
	public function get_role_from_name( $role_name ) {
		return $this->roles[ $role_name ];
	}
}
