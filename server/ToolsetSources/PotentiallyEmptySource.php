<?php

namespace Toolset\DynamicSources\ToolsetSources;

/**
 * Represents a dynamic content source that may or may not actually offer some content, in case it cannot be
 * determined before even instantiating it.
 */
interface PotentiallyEmptySource {

	/**
	 * @return bool
	 */
	public function has_content();

}
