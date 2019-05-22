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


	/**
	 * FieldModel constructor.
	 *
	 * @param string $slug
	 * @param string $name
	 * @param string $type
	 */
	public function __construct( $slug, $name, $type, $categories, $options, $is_repetitive ) {
		$this->slug = $slug;
		$this->name = $name;
		$this->type = $type;
		$this->categories = $categories;
		$this->options = $options;
		$this->is_repetitive = $is_repetitive;
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
	public function get_type() {
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
	public function is_repetitive() {
		return $this->is_repetitive;
	}
}
