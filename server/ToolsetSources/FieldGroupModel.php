<?php

namespace Toolset\DynamicSources\ToolsetSources;

/**
 * Simple model of a field group, just for the purpose of dynamic content sources from Toolset.
 */
class FieldGroupModel {


	/** @var string */
	private $slug;

	/** @var string */
	private $name;

	/** @var FieldModel[] */
	private $fields;


	/**
	 * FieldGroupModel constructor.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param FieldModel[] $fields
	 */
	public function __construct( $slug, $name, $fields ) {
		$this->slug = $slug;
		$this->name = $name;
		$this->fields = $fields;
	}


	/**
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}


	/**
	 * @return FieldModel[]
	 */
	public function get_fields() {
		return $this->fields;
	}


	/**
	 * @param string $field_slug
	 *
	 * @return FieldModel|null
	 */
	public function get_field( $field_slug ) {
		foreach( $this->fields as $field ) {
			if( $field->get_slug() === $field_slug ) {
				return $field;
			}
		}

		return null;
	}

}
