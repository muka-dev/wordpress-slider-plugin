<?php
/**
 * Slider block render callback (contracts.md 5).
 *
 * @package Kadenz\Slider
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered inner blocks. Not used, the slides are rendered here with their index.
 * @var WP_Block $block      Block instance.
 */

use Kadenz\Slider\Attributes;
use Kadenz\Slider\Plugin;
use Kadenz\Slider\Slide_Markup;
use Kadenz\Slider\Style_Vars;

defined( 'ABSPATH' ) || exit;

$kadenz_attrs = Attributes::normalize( is_array( $attributes ) ? $attributes : array() );

// Only real slides count. Foreign blocks in the markup are ignored.
$kadenz_slides = array();
foreach ( $block->inner_blocks as $kadenz_inner ) {
	if ( 'kadenz/slide' === $kadenz_inner->name ) {
		$kadenz_slides[] = $kadenz_inner;
	}
}
$kadenz_count = count( $kadenz_slides );

if ( 0 === $kadenz_count ) {
	return '';
}

Plugin::ensure_state();

$kadenz_id        = wp_unique_id( 'kadenz-slider-' );
$kadenz_track_id  = $kadenz_id . '-track';
$kadenz_is_single = 1 === $kadenz_count;
$kadenz_autoplay  = $kadenz_attrs['autoplay'] && ! $kadenz_is_single;

// Inline SVG icons, 24x24 viewBox, fill: currentColor.
$kadenz_svg_open  = '<svg class="kadenz-slider__icon%s" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true" focusable="false">';
$kadenz_svg_close = '</svg>';
$kadenz_icons     = array(
	'prev'  => sprintf( $kadenz_svg_open, ' kadenz-slider__icon--prev' ) . '<path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>' . $kadenz_svg_close,
	'next'  => sprintf( $kadenz_svg_open, ' kadenz-slider__icon--next' ) . '<path d="M8.59 16.59 10 18l6-6-6-6-1.41 1.41L13.17 12z"/>' . $kadenz_svg_close,
	'pause' => sprintf( $kadenz_svg_open, ' kadenz-slider__icon--pause' ) . '<path d="M6 5h4v14H6zm8 0h4v14h-4z"/>' . $kadenz_svg_close,
	'play'  => sprintf( $kadenz_svg_open, ' kadenz-slider__icon--play' ) . '<path d="M8 5v14l11-7z"/>' . $kadenz_svg_close,
);

// Slides: rendered here so that every slide gets its index and ARIA attributes.
$kadenz_slides_html = '';
$kadenz_slide_ctx   = array(
	'roledescription' => _x( 'slide', 'aria-roledescription', 'kadenz-slider' ),
	/* translators: 1: slide number, 2: total number of slides. */
	'label_format'    => __( '%1$d of %2$d', 'kadenz-slider' ),
);
foreach ( $kadenz_slides as $kadenz_i => $kadenz_slide ) {
	$kadenz_slide_label = isset( $kadenz_slide->attributes['label'] ) ? (string) $kadenz_slide->attributes['label'] : '';

	$kadenz_slides_html .= Slide_Markup::prepare(
		$kadenz_slide->render(),
		$kadenz_i,
		$kadenz_count,
		array_merge( $kadenz_slide_ctx, array( 'label' => $kadenz_slide_label ) )
	);
}

// Wrapper.
$kadenz_classes = array(
	'kadenz-slider',
	'is-effect-' . $kadenz_attrs['effect'],
	'is-height-' . $kadenz_attrs['heightMode'],
	'has-controls-' . $kadenz_attrs['controlsPosition'],
);
if ( $kadenz_is_single ) {
	$kadenz_classes[] = 'is-single';
}
if ( $kadenz_autoplay ) {
	$kadenz_classes[] = 'has-autoplay';
}

$kadenz_wrapper_args = array(
	'id'                   => $kadenz_id,
	'class'                => implode( ' ', $kadenz_classes ),
	'aria-roledescription' => _x( 'carousel', 'aria-roledescription', 'kadenz-slider' ),
	'aria-label'           => '' !== $kadenz_attrs['label'] ? $kadenz_attrs['label'] : __( 'Slider', 'kadenz-slider' ),
);

$kadenz_style = Style_Vars::from_attributes( $kadenz_attrs );
if ( '' !== $kadenz_style ) {
	$kadenz_wrapper_args['style'] = $kadenz_style;
}

$kadenz_directives = '';
if ( ! $kadenz_is_single ) {
	$kadenz_wrapper_args['data-wp-interactive']        = 'kadenz/slider';
	$kadenz_wrapper_args['data-wp-init']               = 'callbacks.init';
	$kadenz_wrapper_args['data-wp-class--is-ready']    = 'context.ready';
	$kadenz_wrapper_args['data-wp-class--is-playing']  = 'context.playing';

	$kadenz_context = Plugin::filter_context(
		array(
			'id'           => $kadenz_id,
			'count'        => $kadenz_count,
			'index'        => 0,
			'perView'      => 'fade' === $kadenz_attrs['effect'] ? 1 : $kadenz_attrs['perView']['base'],
			'effect'       => $kadenz_attrs['effect'],
			'endBehavior'  => $kadenz_attrs['endBehavior'],
			'autoplay'     => $kadenz_autoplay,
			'interval'     => $kadenz_attrs['interval'],
			'pauseOnHover' => $kadenz_attrs['pauseOnHover'],
			'playing'      => false,
			'userStopped'  => false,
			'ready'        => false,
			'announcement' => '',
		),
		$kadenz_attrs,
		$block
	);

	// Escaped by the Interactivity API.
	$kadenz_directives = ' ' . wp_interactivity_data_wp_context( $kadenz_context );
}

$kadenz_wrapper = get_block_wrapper_attributes( $kadenz_wrapper_args ) . $kadenz_directives;

// Autoplay toggle (behaviour follows in T12).
$kadenz_toggle = '';
if ( $kadenz_autoplay ) {
	$kadenz_toggle = '<button type="button" class="kadenz-slider__toggle" aria-controls="' . esc_attr( $kadenz_track_id ) . '"'
		. ' data-wp-on--click="actions.toggleAutoplay" data-wp-bind--aria-label="state.toggleLabel">'
		. $kadenz_icons['pause'] . $kadenz_icons['play']
		. '</button>';
}

// Arrows.
$kadenz_arrows = '';
if ( ! $kadenz_is_single && $kadenz_attrs['showArrows'] ) {
	$kadenz_arrows = '<button type="button" class="kadenz-slider__arrow kadenz-slider__arrow--prev"'
		. ' aria-controls="' . esc_attr( $kadenz_track_id ) . '"'
		. ' aria-label="' . esc_attr__( 'Previous slide', 'kadenz-slider' ) . '"'
		. ' data-wp-on--click="actions.prev" data-wp-bind--aria-disabled="state.isAtStart">'
		. $kadenz_icons['prev']
		. '</button>'
		. '<button type="button" class="kadenz-slider__arrow kadenz-slider__arrow--next"'
		. ' aria-controls="' . esc_attr( $kadenz_track_id ) . '"'
		. ' aria-label="' . esc_attr__( 'Next slide', 'kadenz-slider' ) . '"'
		. ' data-wp-on--click="actions.next" data-wp-bind--aria-disabled="state.isAtEnd">'
		. $kadenz_icons['next']
		. '</button>';
}

// Track.
$kadenz_track_attrs = ' id="' . esc_attr( $kadenz_track_id ) . '"';
if ( ! $kadenz_is_single ) {
	$kadenz_track_attrs .= ' tabindex="0" data-wp-on--keydown="actions.onKeydown"';
}

// Pagination.
$kadenz_pagination = '';
if ( ! $kadenz_is_single ) {
	$kadenz_mode = $kadenz_attrs['pagination'];

	if ( 'dots' === $kadenz_mode || 'numbers' === $kadenz_mode ) {
		$kadenz_pagination = '<div class="kadenz-slider__pagination kadenz-slider__pagination--' . esc_attr( $kadenz_mode ) . '">';
		for ( $kadenz_p = 0; $kadenz_p < $kadenz_count; $kadenz_p++ ) {
			$kadenz_pagination .= '<button type="button" class="kadenz-slider__page"'
				. ' aria-controls="' . esc_attr( $kadenz_track_id ) . '"'
				/* translators: %d: slide number. */
				. ' aria-label="' . esc_attr( sprintf( __( 'Go to slide %d', 'kadenz-slider' ), $kadenz_p + 1 ) ) . '" '
				. wp_interactivity_data_wp_context( array( 'page' => $kadenz_p ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the Interactivity API.
				. ' data-wp-on--click="actions.goToPage"'
				. ' data-wp-bind--aria-current="state.isCurrentPage"'
				. ' data-wp-bind--hidden="state.isPageHidden">';
			if ( 'numbers' === $kadenz_mode ) {
				$kadenz_pagination .= '<span class="kadenz-slider__page-label">' . (int) ( $kadenz_p + 1 ) . '</span>';
			}
			$kadenz_pagination .= '</button>';
		}
		$kadenz_pagination .= '</div>';
	} elseif ( 'progress' === $kadenz_mode ) {
		$kadenz_pagination = '<div class="kadenz-slider__progress" aria-hidden="true">'
			. '<span class="kadenz-slider__progress-bar" data-wp-style--inline-size="state.progressSize"></span>'
			. '</div>';
	}
}

// Live region.
$kadenz_status = '';
if ( ! $kadenz_is_single ) {
	$kadenz_status = '<div class="kadenz-slider__status screen-reader-text" aria-live="polite" aria-atomic="true"'
		. ' data-wp-text="context.announcement"></div>';
}

// All dynamic values above are escaped; the SVGs are constants; the slides are rendered block markup.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo '<section ' . $kadenz_wrapper . '>'
	. $kadenz_toggle
	. '<div class="kadenz-slider__viewport">'
	. $kadenz_arrows
	. '<div class="kadenz-slider__track"' . $kadenz_track_attrs . '>'
	. $kadenz_slides_html
	. '</div>'
	. '</div>'
	. $kadenz_pagination
	. $kadenz_status
	. '</section>';
