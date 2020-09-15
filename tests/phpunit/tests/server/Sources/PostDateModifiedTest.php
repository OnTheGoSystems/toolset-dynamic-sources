<?php

namespace OTGS\Toolset\DynamicSources\Tests\Sources;

use OTGS_TestCase;
use stdClass;
use Toolset\DynamicSources\Sources\PostDateModified;
use WP_Mock;

class PostDateModifiedTest extends OTGS_TestCase {
	const SAMPLE_DATE = '2020-02-10 09:41:14';

	private $subject;

	public function setUp() {
		parent::setUp();

		$this->subject = new PostDateModified();

		global $post;
		$post = new stdClass();
		$post->post_modified = self::SAMPLE_DATE;
	}

	public function tearDown() {
		parent::tearDown();
		unset( $post );
	}

	/**
	 * @test
	 */
	public function it_gets_the_title() {
		$this->assertEquals( 'Post Modified Date', $this->subject->get_title() );
	}

	/**
	 * @test
	 */
	public function it_returns_date_unformatted_if_format_not_given() {
		WP_Mock::passthruFunction( 'wp_kses_post' )->once();

		$this->assertEquals( self::SAMPLE_DATE, $this->subject->get_content() );
	}

	public function content_data_provider() {
		return [
			[ self::SAMPLE_DATE, 'Y-m-d H:i:s' ],
			[ 'February 10, 2020', 'F j, Y' ],
			[ 'February 10, 2020 9:41 am', 'F j, Y g:i a' ],
			[ '10/02/20', 'd/m/y' ],
			[ 'Monday, 2020-02-10 09:41:14', 'l, Y-m-d H:i:s' ],
		];
	}

	/**
	 * @test
	 * @dataProvider content_data_provider
	 * @param string $expected
	 * @param string $format
	 */
	public function it_applies_given_formatting_to_date( $expected, $format ) {
		WP_Mock::passthruFunction( 'wp_kses_post' )->once();

		$this->assertEquals( $expected,	$this->subject->get_content( null, [ 'format' => $format ] ) );
	}
}
