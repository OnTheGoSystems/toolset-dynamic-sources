<?php

namespace Toolset\DynamicSources\ToolsetSources;

use Toolset\DynamicSources\PostProvider;

/**
 * Post provider that offers a post which is related to the initial post
 * via a specific relationship in a specific role.
 */
class RelatedPostProvider implements PostProvider {


	const SLUG_PREFIX = 'toolset_relationship';

	const SLUG_SEPARATOR = '|';


	/** @var PostRelationshipModel */
	private $relationship;


	/** @var RelationshipRole */
	private $role;


	/** @var RelationshipService */
	private $relationship_service;


	/**
	 * RelatedPostProvider constructor.
	 *
	 * @param PostRelationshipModel $relationship
	 * @param RelationshipRole $role
	 * @param RelationshipService $relationship_service
	 */
	public function __construct(
		PostRelationshipModel $relationship,
		RelationshipRole $role,
		RelationshipService $relationship_service
	) {
		$this->relationship = $relationship;
		$this->role = $role;
		$this->relationship_service = $relationship_service;
	}


	/**
	 * Gets a slug uniquely identifying the post provider.
	 *
	 * @return string
	 */
	public function get_unique_slug() {
		return implode(
			self::SLUG_SEPARATOR,
			[ self::SLUG_PREFIX, $this->relationship->get_slug(), $this->role->get_name() ]
		);
	}


	/**
	 * Label that an be displayed in the dropdown.
	 *
	 * @return string
	 */
	public function get_label() {
		return sprintf(
			__( '%s in the %s relationship', 'toolset-dynamic-sources' ),
			$this->role->get_label(),
			$this->relationship->get_display_name()
		);
	}


	/**
	 * Post that should be used as a source. Has to be available only when dynamic content is being
	 * generated. Otherwise, do not rely on it.
	 *
	 * @param int $initial_post_id ID of the initial post, which should be used to get the source post for the
	 *     dynamic content.
	 *
	 * @return int|null Post ID or null when it's not available.
	 */
	public function get_post( $initial_post_id ) {
		$initial_post = get_post( $initial_post_id );
		if( null === $initial_post ) {
			return null;
		}

		$post_id = $this->relationship_service->get_related_post(
			$initial_post,
			$this->relationship->get_slug(),
			$this->relationship_service->get_other_role_name(
				$this->relationship->get_role_by_post_type( $initial_post->post_type )
			)
		);

		return $post_id;
	}


	/**
	 * Type of the post that will be provided.
	 *
	 * Note: This MUST be available even during source registration, before any specific post is available.
	 *
	 * @return string[]
	 */
	public function get_post_types() {
		return [ $this->relationship->get_post_type_by_role( $this->role->get_name() ) ];
	}

}
