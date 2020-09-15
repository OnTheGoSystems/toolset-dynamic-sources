<?php

namespace Toolset\DynamicSources\Sources;

use Toolset\DynamicSources\DynamicSources;

/**
 * Abstract for all Author* sources, simply to DRY the common get_group().
 *
 * @package Toolset\DynamicSources\Sources
 */
abstract class AuthorSource extends AbstractSource {
	/**
	 * Gets the Source group.
	 *
	 * @return string
	 */
	public function get_group() {
		return DynamicSources::AUTHOR_GROUP;
	}
}
