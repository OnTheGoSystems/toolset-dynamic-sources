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

	/** @var bool */
	private $is_rfg;


	/**
	 * FieldGroupModel constructor.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param FieldModel[] $fields
	 * @param bool $is_rfg
	 */
	public function __construct( $slug, $name, $fields, $is_rfg = false ) {
		$this->slug = $slug;
		$this->name = $name;
		$this->fields = $fields;
		$this->is_rfg = $is_rfg;
	}


	/**
	 *
	 * @return string
	 */
	public function get_display_name() {
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
	public function get_field_definitions() {
		return $this->fields;
	}


	/**
	 * @param string $field_slug
	 *
	 * @return FieldModel|null
	 */
	public function get_field_definition( $field_slug ) {
		foreach( $this->fields as $field ) {
			if( $field->get_slug() === $field_slug ) {
				return $field;
			}
		}

		return null;
	}


}
