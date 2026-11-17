<?php
/**
 * Integration tests for the kadenz/slide render output.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider\Tests\Integration;

/**
 * Checks that core block supports are applied to the server-rendered slide.
 */
class SlideRenderTest extends \WP_UnitTestCase {

	/**
	 * Make sure the slide block type is registered before rendering.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'kadenz/slide' ) ) {
			register_block_type( KADENZ_SLIDER_DIR . 'build/slide' );
		}
	}

	/**
	 * Renders block markup and returns the HTML.
	 *
	 * @param string $markup Serialized block markup.
	 * @return string Rendered HTML.
	 */
	private function render( $markup ) {
		$blocks = parse_blocks( $markup );

		return render_block( $blocks[0] );
	}

	/**
	 * A slide with background color and flex layout renders the expected classes and inline style.
	 *
	 * @return void
	 */
	public function test_renders_class_inline_background_and_layout_class() {
		$html = $this->render(
			'<!-- wp:kadenz/slide {"style":{"color":{"background":"#ff0000"}},"layout":{"type":"flex","orientation":"vertical"}} -->'
			. '<!-- wp:paragraph --><p>Hallo</p><!-- /wp:paragraph -->'
			. '<!-- /wp:kadenz/slide -->'
		);

		$this->assertStringContainsString( 'kadenz-slide', $html );
		$this->assertStringContainsString( 'background-color:#ff0000', $html );
		$this->assertStringContainsString( 'is-layout-flex', $html );
		// Newer WordPress versions add a `wp-block-paragraph` class to the paragraph.
		$this->assertMatchesRegularExpression( '#<p( class="[^"]*")?>Hallo</p>#', $html );
	}
}
