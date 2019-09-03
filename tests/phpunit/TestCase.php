<?php

namespace OTGS\Toolset\DynamicSources;

/**
 * Base test case for Toolset Dynamic Sources unit tests.
 */
abstract class TestCase extends \OTGS_TestCase {
	/**
	 * @param $filter
	 * @param $args
	 * @param $reply
	 *
	 * @return mixed|string
	 */
	protected function expectFilter( $filter, $args, $reply ) {
		$filter_responder = call_user_func_array( array( \WP_Mock::onFilter( $filter ), 'with' ), $args );
		if ( ! $filter_responder instanceof \WP_Mock\Filter_Responder ) {
			return '';
		}
		$filter_responder->reply( $reply );
		return $filter_responder->send();
	}
}
