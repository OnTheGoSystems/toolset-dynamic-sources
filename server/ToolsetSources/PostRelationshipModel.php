<?php

namespace Toolset\DynamicSources\ToolsetSources;

/**
 * Simple model representing a post relationship.
 *
 * Note: Works only for standard post relationships (no self-joins or polymorphic rels - if those become supported
 * in the future, the code needs to be adjusted).
 */
class PostRelationshipModel {

	/** @var array Relationship definition array as returned by the toolset_get_relationships() function. */
	private $definition_array;


	/**
	 * PostRelationshipModel constructor.
	 *
	 * @param array $definition_array Relationship definition array as returned by the toolset_get_relationships() function.
	 */
	public function __construct( $definition_array ) {
		$this->definition_array = $definition_array;
	}


	/**
	 * For a given post type that is either a parent or a child in this relationship,
	 * return the post type in the opposite role.
	 *
	 * @param string $current_post_type
	 * @return string
	 */
	public function get_other_post_type( $current_post_type ) {
		$parent_post_type = $this->definition_array['roles']['parent']['types'][0];
		$child_post_type = $this->definition_array['roles']['child']['types'][0];

		return ( $current_post_type === $parent_post_type ? $child_post_type : $parent_post_type );
	}


	/**
	 * For a given post type that is either a parent or a child in this relationship,
	 * return the name of the role.
	 *
	 * @param string $post_type_slug
	 *
	 * @return string 'parent'|'child'
	 */
	public function get_role_by_post_type( $post_type_slug ) {
		$parent_post_type = $this->definition_array['roles']['parent']['types'][0];

		return ( $post_type_slug === $parent_post_type ? 'parent' : 'child' );
	}


	/**
	 * @param string $role_name 'parent'|'child'
	 *
	 * @return string
	 */
	public function get_post_type_by_role( $role_name ) {
		return $this->definition_array['roles'][ $role_name ]['types'][0];
	}


	/**
	 * @return string Relationship slug.
	 */
	public function get_slug() {
		return $this->definition_array['slug'];
	}


	public function get_display_name() {
		return $this->definition_array['labels']['plural'];
	}
}
