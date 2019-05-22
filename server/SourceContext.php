<?php

namespace Toolset\DynamicSources;

/**
 * Interface for an object that provides a context in which dynamic data sources need to be
 * registered.
 */
interface SourceContext {

	/**
	 * @return string[] Array of slugs of post types which can be used as post sources.
	 */
	public function get_post_types();

}
