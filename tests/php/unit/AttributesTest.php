<?php
/**
 * Unit tests for Attributes.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kadenz\Slider\Attributes;
use PHPUnit\Framework\TestCase;

/**
 * Tests attribute defaults and normalization.
 */
class AttributesTest extends TestCase {

	/**
	 * Set up Brain Monkey and stub sanitize_text_field().
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ) {
				return trim( strip_tags( $value ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
			}
		);
	}

	/**
	 * Tear down Brain Monkey.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * An empty array yields exactly the defaults.
	 *
	 * @return void
	 */
	public function test_empty_input_returns_defaults() {
		$this->assertSame( Attributes::defaults(), Attributes::normalize( array() ) );
	}

	/**
	 * The defaults match contracts.md 3.2.
	 *
	 * @return void
	 */
	public function test_defaults_match_contract() {
		$this->assertSame(
			array(
				'label'            => '',
				'effect'           => 'slide',
				'perView'          => array(
					'base' => 1,
					'md'   => 1,
					'lg'   => 1,
				),
				'gap'              => '1.5rem',
				'peek'             => '0px',
				'heightMode'       => 'aspect',
				'aspectRatio'      => '16/9',
				'minHeight'        => '',
				'autoplay'         => false,
				'interval'         => 6000,
				'pauseOnHover'     => true,
				'endBehavior'      => 'stop',
				'showArrows'       => true,
				'pagination'       => 'dots',
				'controlsPosition' => 'overlay',
			),
			Attributes::defaults()
		);
	}

	/**
	 * Unknown keys are dropped.
	 *
	 * @return void
	 */
	public function test_unknown_keys_are_dropped() {
		$result = Attributes::normalize(
			array(
				'evil'   => 'x',
				'effect' => 'fade',
			)
		);
		$this->assertArrayNotHasKey( 'evil', $result );
		$this->assertSame( 'fade', $result['effect'] );
	}

	/**
	 * Data provider: single attribute input and expected normalized value.
	 *
	 * @return array
	 */
	public function provide_cases() {
		$default_per_view = array(
			'base' => 1,
			'md'   => 1,
			'lg'   => 1,
		);

		return array(
			'perView clamps and inherits lg'     => array(
				array(
					'base' => 0,
					'md'   => 99,
				),
				'perView',
				array(
					'base' => 1,
					'md'   => 6,
					'lg'   => 6,
				),
			),
			'perView base only inherits'         => array(
				array( 'base' => 2 ),
				'perView',
				array(
					'base' => 2,
					'md'   => 2,
					'lg'   => 2,
				),
			),
			'perView md only inherits to lg'     => array(
				array( 'md' => 3 ),
				'perView',
				array(
					'base' => 1,
					'md'   => 3,
					'lg'   => 3,
				),
			),
			'perView numeric strings'            => array(
				array(
					'base' => '2',
					'md'   => '3',
					'lg'   => '4',
				),
				'perView',
				array(
					'base' => 2,
					'md'   => 3,
					'lg'   => 4,
				),
			),
			'perView non numeric entry inherits' => array(
				array(
					'base' => 2,
					'md'   => 'x',
					'lg'   => true,
				),
				'perView',
				array(
					'base' => 2,
					'md'   => 2,
					'lg'   => 2,
				),
			),
			'perView string'                     => array( 'abc', 'perView', $default_per_view ),
			'perView null'                       => array( null, 'perView', $default_per_view ),
			'interval too low'                   => array( 100, 'interval', 2000 ),
			'interval not numeric'               => array( 'abc', 'interval', 6000 ),
			'interval too high'                  => array( 99999, 'interval', 30000 ),
			'interval numeric string'            => array( '3000', 'interval', 3000 ),
			'interval negative'                  => array( -5, 'interval', 2000 ),
			'gap ok'                             => array( '2rem', 'gap', '2rem' ),
			'gap zero'                           => array( '0', 'gap', '0' ),
			'gap decimal'                        => array( '1.25em', 'gap', '1.25em' ),
			'gap injection'                      => array( '2rem;color:red', 'gap', '1.5rem' ),
			'gap trailing newline'               => array( "2rem\n", 'gap', '1.5rem' ),
			'gap negative'                       => array( '-2rem', 'gap', '1.5rem' ),
			'gap not a string'                   => array( 5, 'gap', '1.5rem' ),
			'gap preset'                         => array( 'var:preset|spacing|40', 'gap', 'var(--wp--preset--spacing--40)' ),
			'gap preset with space'              => array( 'var:preset|spacing|4 0', 'gap', '1.5rem' ),
			'gap preset uppercase'               => array( 'var:preset|spacing|AB', 'gap', '1.5rem' ),
			'gap vh allowed'                     => array( '5vh', 'gap', '5vh' ),
			'peek ok'                            => array( '10px', 'peek', '10px' ),
			'peek vh rejected'                   => array( '10vh', 'peek', '0px' ),
			'peek svh rejected'                  => array( '10svh', 'peek', '0px' ),
			'peek dvh rejected'                  => array( '10dvh', 'peek', '0px' ),
			'peek vw allowed'                    => array( '5vw', 'peek', '5vw' ),
			'peek preset'                        => array( 'var:preset|spacing|large', 'peek', 'var(--wp--preset--spacing--large)' ),
			'aspect ok'                          => array( '4/3', 'aspectRatio', '4/3' ),
			'aspect with spaces'                 => array( '4 / 3', 'aspectRatio', '4 / 3' ),
			'aspect decimal'                     => array( '1.5/1', 'aspectRatio', '1.5/1' ),
			'aspect colon'                       => array( '4:3', 'aspectRatio', '16/9' ),
			'aspect expression'                  => array( 'expression(1)', 'aspectRatio', '16/9' ),
			'aspect not a string'                => array( 4, 'aspectRatio', '16/9' ),
			'minHeight ok'                       => array( '300px', 'minHeight', '300px' ),
			'minHeight vh ok'                    => array( '50vh', 'minHeight', '50vh' ),
			'minHeight empty'                    => array( '', 'minHeight', '' ),
			'minHeight invalid'                  => array( 'tall', 'minHeight', '' ),
			'pagination invalid'                 => array( 'foo', 'pagination', 'dots' ),
			'pagination progress'                => array( 'progress', 'pagination', 'progress' ),
			'pagination not a string'            => array( array( 'dots' ), 'pagination', 'dots' ),
			'label stripped'                     => array( '<b>Hi</b>', 'label', 'Hi' ),
			'label not a string'                 => array( 5, 'label', '' ),
			'effect invalid'                     => array( 'zoom', 'effect', 'slide' ),
			'effect fade'                        => array( 'fade', 'effect', 'fade' ),
			'heightMode min'                     => array( 'min', 'heightMode', 'min' ),
			'heightMode invalid'                 => array( 'huge', 'heightMode', 'aspect' ),
			'endBehavior rewind'                 => array( 'rewind', 'endBehavior', 'rewind' ),
			'endBehavior invalid'                => array( 'loop', 'endBehavior', 'stop' ),
			'controlsPosition below'             => array( 'below', 'controlsPosition', 'below' ),
			'controlsPosition invalid'           => array( 'above', 'controlsPosition', 'overlay' ),
			'autoplay true'                      => array( true, 'autoplay', true ),
			'autoplay string is not bool'        => array( 'true', 'autoplay', false ),
			'autoplay int is not bool'           => array( 1, 'autoplay', false ),
			'pauseOnHover false'                 => array( false, 'pauseOnHover', false ),
			'pauseOnHover string is not bool'    => array( 'false', 'pauseOnHover', true ),
			'showArrows false'                   => array( false, 'showArrows', false ),
			'showArrows null'                    => array( null, 'showArrows', true ),
		);
	}

	/**
	 * Each attribute is normalized as expected.
	 *
	 * @dataProvider provide_cases
	 *
	 * @param mixed  $input    Raw value.
	 * @param string $key      Attribute key.
	 * @param mixed  $expected Expected normalized value.
	 * @return void
	 */
	public function test_normalize_single_attribute( $input, $key, $expected ) {
		$result = Attributes::normalize( array( $key => $input ) );
		$this->assertSame( $expected, $result[ $key ] );
	}

	/**
	 * Every key is always present with the right type.
	 *
	 * @return void
	 */
	public function test_normalize_always_returns_all_keys() {
		$result = Attributes::normalize( array( 'perView' => 'x' ) );
		$this->assertSame( array_keys( Attributes::defaults() ), array_keys( $result ) );
	}

	/**
	 * Fade forces every perView entry to 1.
	 *
	 * @return void
	 */
	public function test_fade_forces_per_view_to_one() {
		$result = Attributes::normalize(
			array(
				'effect'  => 'fade',
				'perView' => array(
					'base' => 2,
					'md'   => 2,
					'lg'   => 3,
				),
			)
		);
		$this->assertSame(
			array(
				'base' => 1,
				'md'   => 1,
				'lg'   => 1,
			),
			$result['perView']
		);
	}

	/**
	 * A broken defaults filter is normalized again.
	 *
	 * @return void
	 */
	public function test_defaults_filter_result_is_normalized() {
		Monkey\Filters\expectApplied( 'kadenz_slider_default_attributes' )
			->andReturn(
				array(
					'interval' => -5,
					'gap'      => 'url(x)',
					'effect'   => 'nope',
					'perView'  => array( 'base' => 3 ),
				)
			);

		$defaults = Attributes::defaults();
		$this->assertSame( 2000, $defaults['interval'] );
		$this->assertSame( '1.5rem', $defaults['gap'] );
		$this->assertSame( 'slide', $defaults['effect'] );
		$this->assertSame(
			array(
				'base' => 3,
				'md'   => 3,
				'lg'   => 3,
			),
			$defaults['perView']
		);
	}

	/**
	 * A filter returning a non-array falls back to the built-in defaults.
	 *
	 * @return void
	 */
	public function test_defaults_filter_returning_non_array_falls_back() {
		Monkey\Filters\expectApplied( 'kadenz_slider_default_attributes' )->andReturn( 'junk' );

		$defaults = Attributes::defaults();
		$this->assertSame( 6000, $defaults['interval'] );
		$this->assertSame( 'slide', $defaults['effect'] );
	}

	/**
	 * Filtered defaults are used as fallback in normalize().
	 *
	 * @return void
	 */
	public function test_filtered_defaults_are_used_as_fallback() {
		Monkey\Filters\expectApplied( 'kadenz_slider_default_attributes' )
			->andReturn( array( 'interval' => 4000 ) );

		$result = Attributes::normalize( array( 'interval' => 'abc' ) );
		$this->assertSame( 4000, $result['interval'] );
	}

	/**
	 * Data provider for css_length().
	 *
	 * @return array
	 */
	public function provide_lengths() {
		return array(
			'zero'              => array( '0', true, '0' ),
			'px'                => array( '12px', true, '12px' ),
			'percent'           => array( '50%', true, '50%' ),
			'dvh allowed'       => array( '10dvh', true, '10dvh' ),
			'dvh disallowed'    => array( '10dvh', false, null ),
			'vh disallowed'     => array( '10vh', false, null ),
			'vw disallowed ok'  => array( '10vw', false, '10vw' ),
			'no unit'           => array( '12', true, null ),
			'empty'             => array( '', true, null ),
			'preset'            => array( 'var:preset|spacing|50', true, 'var(--wp--preset--spacing--50)' ),
			'preset disallowed' => array( 'var:preset|color|50', true, null ),
			'css var'           => array( 'var(--x)', true, null ),
			'calc'              => array( 'calc(1px)', true, null ),
		);
	}

	/**
	 * Tests css_length().
	 *
	 * @dataProvider provide_lengths
	 *
	 * @param string      $value    Input.
	 * @param bool        $allow_vh Whether vh units are allowed.
	 * @param string|null $expected Expected result.
	 * @return void
	 */
	public function test_css_length( $value, $allow_vh, $expected ) {
		$this->assertSame( $expected, Attributes::css_length( $value, $allow_vh ) );
	}

	/**
	 * Non-string input to css_length() is rejected.
	 *
	 * @return void
	 */
	public function test_css_length_rejects_non_strings() {
		$this->assertNull( Attributes::css_length( 5 ) ); // phpcs:ignore
	}
}
