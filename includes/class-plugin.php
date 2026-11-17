<?php
/**
 * Main plugin class.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the blocks.
 */
class Plugin {

	/**
	 * Whether the global interactivity state was already registered in this request.
	 *
	 * @var bool
	 */
	private static $state_registered = false;

	/**
	 * Hook the plugin into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register the slider and slide blocks from the build directory.
	 *
	 * @return void
	 */
	public static function register_blocks() {
		foreach ( array( 'slider', 'slide' ) as $block ) {
			$dir = KADENZ_SLIDER_DIR . 'build/' . $block;
			if ( ! file_exists( $dir . '/block.json' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					_doing_it_wrong(
						__METHOD__,
						sprintf(
							/* translators: %s: block build directory. */
							esc_html__( 'Kadenz Slider: the build directory "%s" is missing. Run "npm run build".', 'kadenz-slider' ),
							esc_html( 'build/' . $block )
						),
						esc_html( KADENZ_SLIDER_VERSION )
					);
				}
				continue;
			}
			register_block_type( $dir );
		}
	}

	/**
	 * Register the global interactivity state once per request (contracts.md 6.2).
	 *
	 * Called by the slider render callback, so pages without a slider pay nothing.
	 *
	 * @return void
	 */
	public static function ensure_state() {
		if ( self::$state_registered ) {
			return;
		}
		self::$state_registered = true;

		wp_interactivity_state(
			'kadenz/slider',
			array(
				'i18n' => array(
					/* translators: 1: current slide number, 2: total number of slides. Announced to screen readers. */
					'slideOf' => __( 'Slide %1$d of %2$d', 'kadenz-slider' ),
					'play'    => __( 'Start automatic slide show', 'kadenz-slider' ),
					'pause'   => __( 'Stop automatic slide show', 'kadenz-slider' ),
				),
			)
		);
	}

	/**
	 * Run the per-instance interactivity context through the public filter.
	 *
	 * Filter `kadenz_slider_render_context` (contracts.md 8): receives the context array, the normalized
	 * attributes and the WP_Block. Extra keys are allowed, but the keys from contracts.md 6.1 must be kept.
	 *
	 * @param array     $context    Context with the keys from contracts.md 6.1.
	 * @param array     $attributes Normalized slider attributes.
	 * @param \WP_Block $block      The slider block instance.
	 * @return array
	 */
	public static function filter_context( array $context, array $attributes, $block ) {
		$filtered = apply_filters( 'kadenz_slider_render_context', $context, $attributes, $block );

		return is_array( $filtered ) ? $filtered : $context;
	}
}
