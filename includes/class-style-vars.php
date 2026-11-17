<?php
/**
 * Converts normalized slider attributes into inline CSS custom properties.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the inline style string for the slider wrapper.
 */
class Style_Vars {

	/**
	 * Convert normalized attributes into a style string (contracts.md 7.1).
	 *
	 * Only values that differ from the CSS defaults are emitted, in a fixed order.
	 *
	 * @param array $normalized Output of Attributes::normalize().
	 * @return string
	 */
	public static function from_attributes( array $normalized ) {
		$vars = array();

		if ( isset( $normalized['perView'] ) && is_array( $normalized['perView'] ) ) {
			foreach ( array( 'base', 'md', 'lg' ) as $key ) {
				if ( isset( $normalized['perView'][ $key ] ) && 1 !== $normalized['perView'][ $key ] ) {
					$vars[ '--kadenz-per-view-' . $key ] = (string) $normalized['perView'][ $key ];
				}
			}
		}

		if ( isset( $normalized['gap'] ) && '1.5rem' !== $normalized['gap'] ) {
			$vars['--kadenz-gap'] = $normalized['gap'];
		}

		if ( isset( $normalized['peek'] ) && '0px' !== $normalized['peek'] ) {
			$vars['--kadenz-peek'] = $normalized['peek'];
		}

		$height_mode = isset( $normalized['heightMode'] ) ? $normalized['heightMode'] : '';

		if ( 'aspect' === $height_mode && isset( $normalized['aspectRatio'] )
			&& '16/9' !== preg_replace( '/\s+/', '', (string) $normalized['aspectRatio'] ) ) {
			$vars['--kadenz-aspect-ratio'] = $normalized['aspectRatio'];
		}

		if ( 'min' === $height_mode && isset( $normalized['minHeight'] ) && '' !== $normalized['minHeight'] ) {
			$vars['--kadenz-min-height'] = $normalized['minHeight'];
		}

		if ( isset( $normalized['autoplay'] ) && true === $normalized['autoplay']
			&& isset( $normalized['interval'] ) && 6000 !== $normalized['interval'] ) {
			$vars['--kadenz-interval'] = $normalized['interval'] . 'ms';
		}

		$style = '';
		foreach ( $vars as $name => $value ) {
			$value = (string) $value;
			if ( 1 === preg_match( '/[;{}<>"\']|url\(/i', $value ) ) {
				continue;
			}
			$style .= $name . ':' . $value . ';';
		}

		return $style;
	}
}
