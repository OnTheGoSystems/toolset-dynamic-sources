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
	 * For a given post type return the name of the role in relationship.
	 *
	 * @param string $post_type_slug
	 *
	 * @return string 'parent'|'child'|'intermediary'
	 */
	public function get_role_by_post_type( $post_type_slug ) {
		foreach ( $this->definition_array['roles'] as $role_name => $role ) {
			if ( is_array( $role['types'] ) ) {
				if ( $role['types'][0] === $post_type_slug ) {
					return $role_name;
				}
			} else {
				if ( $role['types'] === $post_type_slug ) {
					return $role_name;
				}
			}
		}
		return 'child';
	}


	/**
	 * @param string $role_name 'parent'|'child'|'intermediary'
	 *
	 * @return string|null
	 */
	public function get_post_type_by_role( $role_name ) {
		if ( ! array_key_exists( $role_name, $this->definition_array['roles'] ) ) {
			return null;
		}

		if ( is_array( $this->definition_array['roles'][ $role_name ]['types'] ) ) {
			return reset( $this->definition_array['roles'][ $role_name ]['types'] );
		}
		return $this->definition_array['roles'][ $role_name ]['types'];
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

	public function is_intermediary() {
		return array_key_exists( 'intermediary', $this->definition_array['roles'] );
	}

	public function is_views_filtered_o_2_m() {
		return array_key_exists( 'post_relationship_mode', $this->definition_array );
	}

	public function get_views_filtered_o_2_m_side() {
		if ( array_key_exists( 'post_type', $this->definition_array ) ) {
			return $this->get_role_by_post_type( $this->definition_array['post_type'] );
		}
		return null;
	}
}
