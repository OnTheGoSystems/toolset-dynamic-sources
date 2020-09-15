<?php

namespace Toolset\DynamicSources\ToolsetSources;

/**
 * Simple model representing a custom field definition.
 */
class FieldModel {

	/** @var string */
	private $slug;

	/** @var string */
	private $name;

	/** @var string */
	private $type;

	/** @var array */
	private $categories;

	/** @var array */
	private $options;

	private $is_repeatable;


	/**
	 * FieldModel constructor.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param string $type
	 * @param $categories
	 * @param $options
	 * @param $is_repeatable
	 */
	public function __construct( $slug, $name, $type, $categories, $options, $is_repeatable ) {
		$this->slug = $slug;
		$this->name = $name;
		$this->type = $type;
		$this->categories = $categories;
		$this->options = $options;
		$this->is_repeatable = $is_repeatable;
	}


	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}


	/**
	 * @return string
	 */
	public function get_type_slug() {
		return $this->type;
	}

	public function get_categories() {
		return $this->categories;
	}

	/**
	 * @return array
	 */
	public function get_options() {
		return $this->options;
	}

	/**
	 * @return array
	 */
	public function is_repeatable() {
		return $this->is_repeatable;
	}
}
