<?php
/**
 * Tests for Slide_Markup::prepare().
 *
 * Lives in the integration suite because WP_HTML_Tag_Processor is part of WordPress core and is not
 * available in the WordPress-free unit suite (neither wp-phpunit nor any other dev dependency ships it).
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider\Tests\Integration;

use Kadenz\Slider\Slide_Markup;

/**
 * Checks ARIA and index attributes on the first tag of slide HTML.
 */
class SlideMarkupTest extends \WP_UnitTestCase {

	/**
	 * Default context used by the tests.
	 *
	 * @param string $label Optional slide label.
	 * @return array
	 */
	private function ctx( $label = '' ) {
		return array(
			'label'           => $label,
			'roledescription' => 'slide',
			'label_format'    => '%1$d of %2$d',
		);
	}

	/**
	 * Sets role, roledescription, label and index on a plain div.
	 *
	 * @return void
	 */
	public function test_sets_attributes_on_first_tag() {
		$html = Slide_Markup::prepare( '<div class="x"><p>Hi</p></div>', 1, 3, $this->ctx() );

		$this->assertStringContainsString( 'role="group"', $html );
		$this->assertStringContainsString( 'aria-roledescription="slide"', $html );
		$this->assertStringContainsString( 'aria-label="2 of 3"', $html );
		$this->assertStringContainsString( 'data-kadenz-index="1"', $html );
		$this->assertStringContainsString( 'class="x"', $html );
		$this->assertStringContainsString( '<p>Hi</p>', $html );
		$this->assertSame( 1, substr_count( $html, 'role=' ), 'Only the first tag is touched.' );
	}

	/**
	 * Leading whitespace is preserved and the first tag is still found.
	 *
	 * @return void
	 */
	public function test_handles_leading_whitespace() {
		$html = Slide_Markup::prepare( "\n\t <div class=\"x\">A</div>", 0, 2, $this->ctx() );

		$this->assertStringStartsWith( "\n\t <div", $html );
		$this->assertStringContainsString( 'data-kadenz-index="0"', $html );
		$this->assertStringContainsString( 'aria-label="1 of 2"', $html );
	}

	/**
	 * A comment before the first tag is skipped, the attributes go on the first real tag.
	 *
	 * @return void
	 */
	public function test_skips_leading_comment() {
		$html = Slide_Markup::prepare( '<!-- wp:kadenz/slide --><div class="x">A</div>', 2, 3, $this->ctx() );

		$this->assertStringStartsWith( '<!-- wp:kadenz/slide -->', $html );
		$this->assertMatchesRegularExpression( '/<div [^>]*data-kadenz-index="2"/', $html );
		$this->assertStringContainsString( 'aria-label="3 of 3"', $html );
	}

	/**
	 * A custom label replaces the numeric fallback and is escaped.
	 *
	 * @return void
	 */
	public function test_uses_custom_label_and_escapes_it() {
		$html = Slide_Markup::prepare( '<div>A</div>', 0, 2, $this->ctx( 'Intro "one" <b>' ) );

		$this->assertStringContainsString( 'aria-label="Intro &quot;one&quot; &lt;b&gt;"', $html );
		$this->assertStringNotContainsString( '1 of 2', $html );
	}

	/**
	 * Existing attributes with the same name are overwritten, not duplicated.
	 *
	 * @return void
	 */
	public function test_overwrites_existing_attributes() {
		$html = Slide_Markup::prepare( '<div role="region" aria-label="old">A</div>', 0, 1, $this->ctx() );

		$this->assertSame( 1, substr_count( $html, 'role=' ) );
		$this->assertStringContainsString( 'role="group"', $html );
		$this->assertStringNotContainsString( 'old', $html );
	}

	/**
	 * Input without any tag is returned unchanged.
	 *
	 * @return void
	 */
	public function test_returns_input_without_tag_unchanged() {
		$this->assertSame( 'just text', Slide_Markup::prepare( 'just text', 0, 1, $this->ctx() ) );
		$this->assertSame( '', Slide_Markup::prepare( '', 0, 1, $this->ctx() ) );
	}
}
