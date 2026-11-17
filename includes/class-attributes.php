<?php
/**
 * Attribute defaults and normalization for the slider block.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider;

defined( 'ABSPATH' ) || exit;

/**
 * Turns arbitrary (possibly tampered) block attributes into valid values.
 */
class Attributes {

	/**
	 * Pattern for a plain CSS length.
	 */
	const LENGTH_PATTERN = '/\A(?:0|\d+(?:\.\d+)?(?:px|rem|em|%|vw|vh|svh|dvh))\z/D';

	/**
	 * Pattern for a spacing preset reference.
	 */
	const PRESET_PATTERN = '/\Avar:preset\|spacing\|([a-z0-9-]+)\z/D';

	/**
	 * Pattern for an aspect ratio.
	 */
	const RATIO_PATTERN = '/\A\d+(?:\.\d+)?\s*\/\s*\d+(?:\.\d+)?\z/D';

	/**
	 * Built-in defaults (contracts.md 3.2), before the filter runs.
	 *
	 * @return array
	 */
	private static function builtin_defaults() {
		return array(
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
		);
	}

	/**
	 * Default attributes, filtered and normalized again so a broken filter cannot do harm.
	 *
	 * @return array
	 */
	public static function defaults() {
		$builtin  = self::builtin_defaults();
		$filtered = apply_filters( 'kadenz_slider_default_attributes', $builtin );

		if ( ! is_array( $filtered ) ) {
			return $builtin;
		}

		return self::normalize_against( $filtered, $builtin );
	}

	/**
	 * Normalize raw attributes. Always returns every key with a valid value.
	 *
	 * @param array $raw Raw attributes.
	 * @return array
	 */
	public static function normalize( array $raw ) {
		return self::normalize_against( $raw, self::defaults() );
	}

	/**
	 * Validate a CSS length (contracts.md 3.3).
	 *
	 * @param string $value    Raw value.
	 * @param bool   $allow_vh Whether vh, svh and dvh units are allowed.
	 * @return string|null Normalized CSS value or null if invalid.
	 */
	public static function css_length( $value, $allow_vh = true ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		if ( 1 === preg_match( self::PRESET_PATTERN, $value, $matches ) ) {
			return 'var(--wp--preset--spacing--' . $matches[1] . ')';
		}

		if ( 1 !== preg_match( self::LENGTH_PATTERN, $value ) ) {
			return null;
		}

		if ( ! $allow_vh && 1 === preg_match( '/(?:vh|svh|dvh)\z/', $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Normalize raw attributes using the given (already valid) fallback values.
	 *
	 * @param array $raw      Raw attributes.
	 * @param array $fallback Valid fallback values, all keys present.
	 * @return array
	 */
	private static function normalize_against( array $raw, array $fallback ) {
		$out = array();

		$out['label'] = isset( $raw['label'] ) && is_string( $raw['label'] )
			? sanitize_text_field( $raw['label'] )
			: $fallback['label'];

		$out['effect'] = self::enum( $raw, 'effect', array( 'slide', 'fade' ), $fallback['effect'] );

		$out['perView'] = self::per_view( $raw, $fallback['perView'], 'fade' === $out['effect'] );

		$out['gap']  = self::length_value( $raw, 'gap', true, $fallback['gap'] );
		$out['peek'] = self::length_value( $raw, 'peek', false, $fallback['peek'] );

		$out['heightMode'] = self::enum( $raw, 'heightMode', array( 'aspect', 'min', 'auto' ), $fallback['heightMode'] );

		$out['aspectRatio'] = isset( $raw['aspectRatio'] ) && is_string( $raw['aspectRatio'] )
			&& 1 === preg_match( self::RATIO_PATTERN, $raw['aspectRatio'] )
			? $raw['aspectRatio']
			: $fallback['aspectRatio'];

		if ( isset( $raw['minHeight'] ) && '' === $raw['minHeight'] ) {
			$out['minHeight'] = '';
		} else {
			$out['minHeight'] = self::length_value( $raw, 'minHeight', true, $fallback['minHeight'] );
		}

		$out['autoplay'] = self::boolean( $raw, 'autoplay', $fallback['autoplay'] );

		$out['interval'] = isset( $raw['interval'] ) && is_numeric( $raw['interval'] )
			? max( 2000, min( 30000, (int) $raw['interval'] ) )
			: $fallback['interval'];

		$out['pauseOnHover'] = self::boolean( $raw, 'pauseOnHover', $fallback['pauseOnHover'] );
		$out['endBehavior']  = self::enum( $raw, 'endBehavior', array( 'stop', 'rewind' ), $fallback['endBehavior'] );
		$out['showArrows']   = self::boolean( $raw, 'showArrows', $fallback['showArrows'] );
		$out['pagination']   = self::enum( $raw, 'pagination', array( 'none', 'dots', 'numbers', 'progress' ), $fallback['pagination'] );

		$out['controlsPosition'] = self::enum( $raw, 'controlsPosition', array( 'overlay', 'below' ), $fallback['controlsPosition'] );

		return $out;
	}

	/**
	 * Read an enum value.
	 *
	 * @param array  $raw      Raw attributes.
	 * @param string $key      Attribute key.
	 * @param array  $allowed  Allowed values.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private static function enum( array $raw, $key, array $allowed, $fallback ) {
		if ( isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) && in_array( $raw[ $key ], $allowed, true ) ) {
			return $raw[ $key ];
		}
		return $fallback;
	}

	/**
	 * Read a boolean value; only real booleans are accepted.
	 *
	 * @param array  $raw      Raw attributes.
	 * @param string $key      Attribute key.
	 * @param bool   $fallback Fallback value.
	 * @return bool
	 */
	private static function boolean( array $raw, $key, $fallback ) {
		if ( isset( $raw[ $key ] ) && is_bool( $raw[ $key ] ) ) {
			return $raw[ $key ];
		}
		return $fallback;
	}

	/**
	 * Read a CSS length value.
	 *
	 * @param array  $raw      Raw attributes.
	 * @param string $key      Attribute key.
	 * @param bool   $allow_vh Whether viewport-height units are allowed.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private static function length_value( array $raw, $key, $allow_vh, $fallback ) {
		if ( isset( $raw[ $key ] ) ) {
			$length = self::css_length( $raw[ $key ], $allow_vh );
			if ( null !== $length ) {
				return $length;
			}
		}
		return $fallback;
	}

	/**
	 * Normalize the perView object including inheritance and the fade rule.
	 *
	 * @param array $raw      Raw attributes.
	 * @param array $fallback Fallback perView.
	 * @param bool  $is_fade  Whether the effect is fade.
	 * @return array
	 */
	private static function per_view( array $raw, array $fallback, $is_fade ) {
		if ( $is_fade ) {
			return array(
				'base' => 1,
				'md'   => 1,
				'lg'   => 1,
			);
		}

		if ( ! isset( $raw['perView'] ) || ! is_array( $raw['perView'] ) ) {
			return $fallback;
		}

		$input = $raw['perView'];
		$base  = self::view_count( $input, 'base', $fallback['base'] );
		$md    = self::view_count( $input, 'md', $base );
		$lg    = self::view_count( $input, 'lg', $md );

		return array(
			'base' => $base,
			'md'   => $md,
			'lg'   => $lg,
		);
	}

	/**
	 * Read one perView entry, clamped to 1-6.
	 *
	 * @param array  $input    Raw perView array.
	 * @param string $key      Breakpoint key.
	 * @param int    $fallback Value used when the entry is missing or not numeric.
	 * @return int
	 */
	private static function view_count( array $input, $key, $fallback ) {
		if ( isset( $input[ $key ] ) && is_numeric( $input[ $key ] ) ) {
			return max( 1, min( 6, (int) $input[ $key ] ) );
		}
		return $fallback;
	}
}
