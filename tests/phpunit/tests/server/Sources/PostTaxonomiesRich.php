<?php

namespace OTGS\Toolset\DynamicSources\Tests\Sources;

use OTGS_TestCase;
use Toolset\DynamicSources\DynamicSources;
use Toolset\DynamicSources\Sources\PostTaxonomiesRich as Subject;
use WP_Mock;

class PostTaxonomiesRich extends OTGS_TestCase {
	private $subject;
	private $terms;

	public function setUp() {
		parent::setUp();

		$this->subject = new Subject();

		$terms = $this->getMockBuilder('WP_Term')
			->disableOriginalConstructor();

		$term1 = $terms->getMock();
		$term1->name = '1st';
		$term1->count = 1;
		$term1->description = '1st description';
		$term1->slug = 'slug1';

		$term2 = $terms->getMock();
		$term2->name = '2nd';
		$term2->count = 5;
		$term2->description = '2nd description';
		$term2->slug = 'slug2';

		$term3 = $terms->getMock();
		$term3->name = '3rd';
		$term3->count = 2;
		$term3->description = '3rd description';
		$term3->slug = 'slug3';

		$this->terms = [ $term1, $term2, $term3 ];
	}

	/**
	 * @test
	 */
	public function it_gets_the_title() {
		$this->assertEquals( 'Post Taxonomies Rich', $this->subject->get_title() );
	}

	/**
	 * @test
	 */
	public function it_gets_group() {
		$this->assertEquals( DynamicSources::POST_GROUP, $this->subject->get_group() );
	}

	/**
	 * @test
	 */
	public function it_gets_categories() {
		$this->assertSame( [ DynamicSources::TEXT_CATEGORY ], $this->subject->get_categories() );
	}

	public function content_data_provider() {
		$default_attributes = [
			'separator' => ', ',
			'format' => Subject::FORMAT_NAME,
			'show' => Subject::SHOW_NAME,
			'order' => 'asc',
		];

		return [
			[
				$default_attributes,
				'1st, 2nd, 3rd'
			],
			[
				array_merge( $default_attributes, [ 'separator' => ' | ' ] ),
				'1st | 2nd | 3rd'
			],
			[
				array_merge( $default_attributes, [ 'format' => Subject::FORMAT_LINK ] ),
				"<a href='http://test.loc/?tag=test'>1st</a>, <a href='http://test.loc/?tag=test'>2nd</a>, <a href='http://test.loc/?tag=test'>3rd</a>"
			],
			[
				array_merge( $default_attributes, [ 'format' => Subject::FORMAT_LINK, 'show' => Subject::SHOW_COUNT ] ),
				"<a href='http://test.loc/?tag=test'>1</a>, <a href='http://test.loc/?tag=test'>5</a>, <a href='http://test.loc/?tag=test'>2</a>"
			],
			[
				array_merge( $default_attributes, [ 'format' => Subject::FORMAT_LINK, 'show' => Subject::SHOW_DESCRIPTION ] ),
				"<a href='http://test.loc/?tag=test'>1st description</a>, <a href='http://test.loc/?tag=test'>2nd description</a>, <a href='http://test.loc/?tag=test'>3rd description</a>"
			],
			[
				array_merge( $default_attributes, [ 'format' => Subject::FORMAT_LINK, 'show' => Subject::SHOW_SLUG ] ),
				"<a href='http://test.loc/?tag=test'>slug1</a>, <a href='http://test.loc/?tag=test'>slug2</a>, <a href='http://test.loc/?tag=test'>slug3</a>"
			],
			[
				array_merge( $default_attributes, [ 'format' => Subject::FORMAT_URL ] ),
				"http://test.loc/?tag=test, http://test.loc/?tag=test, http://test.loc/?tag=test"
			],
			[
				array_merge( $default_attributes, [ 'format' => Subject::FORMAT_COUNT ] ),
				"1, 5, 2"
			],
			[
				array_merge( $default_attributes, [ 'format' => Subject::FORMAT_SLUG ] ),
				"slug1, slug2, slug3"
			],
			[
				array_merge( $default_attributes, [ 'format' => Subject::FORMAT_DESCRIPTION ] ),
				"1st description, 2nd description, 3rd description"
			],
			[
				array_merge( $default_attributes, [ 'order' => Subject::ORDER_DESC ] ),
				'3rd, 2nd, 1st'
			],
		];
	}

	/**
	 * @test
	 * @dataProvider content_data_provider
	 * @param array|null $attributes
	 * @param array|string $expected_content
	 */
	public function it_gets_content( $attributes, $expected_content ) {
		WP_Mock::userFunction( 'get_the_terms' )->once()->andReturn( $this->terms );
		WP_Mock::userFunction( 'get_the_ID' )->atMost( 1 )->andReturn( 0 );
		WP_Mock::userFunction( 'is_wp_error' )->once()->andReturn( false );
		WP_Mock::userFunction( 'get_tag_link' )->atMost( 1 )->andReturn( 'http://test.loc/?tag=test' );

		$this->assertEquals( $expected_content, $this->subject->get_content( null, $attributes ) );
	}

	/**
	 * @test
	 */
	public function it_returns_array_for_content_if_given_array_and_no_attributes() {
		WP_Mock::userFunction( 'get_the_terms' )->once()->andReturn( [1, 2] );
		WP_Mock::userFunction( 'get_the_ID' )->atMost( 1 )->andReturn( 0 );
		WP_Mock::userFunction( 'is_wp_error' )->once()->andReturn( false );

		$this->assertEquals( [1, 2], $this->subject->get_content( null, null ) );
	}
}
