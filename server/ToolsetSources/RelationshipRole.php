<?php

namespace Toolset\DynamicSources\ToolsetSources;

/**
 * Represents a role in a relationship.
 */
class RelationshipRole {


	/** @var string */
	private $role_name;

	/** @var string */
	private $role_label;


	/**
	 * RelationshipRole constructor.
	 *
	 * @param string $name
	 * @param string $label
	 */
	public function __construct( $name, $label ) {
		$this->role_name = $name;
		$this->role_label = $label;
	}


	/**
	 * @return string
	 */
	public function get_name() {
		return $this->role_name;
	}


	/**
	 * @return string
	 */
	public function get_label() {
		return $this->role_label;
	}

}
