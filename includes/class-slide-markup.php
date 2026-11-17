<?php
/**
 * Adds ARIA and index attributes to the rendered markup of a single slide.
 *
 * @package Kadenz\Slider
 */

namespace Kadenz\Slider;

defined( 'ABSPATH' ) || exit;

/**
 * Prepares slide HTML for the slider (contracts.md 5.2).
 *
 * This class calls no WordPress functions besides WP_HTML_Tag_Processor.
 * Translated strings are passed in through the context array.
 */
class Slide_Markup {

	/**
	 * Set role, ARIA attributes and the zero-based index on the first tag of a slide.
	 *
	 * Context keys:
	 * - label:           Optional custom slide label (string).
	 * - roledescription: Translated "slide" (string).
	 * - label_format:    Translated fallback label format with %1$d and %2$d (string).
	 *
	 * @param string $html  Rendered slide HTML.
	 * @param int    $index Zero-based slide index.
	 * @param int    $count Total number of slides.
	 * @param array  $ctx   Context, see above.
	 * @return string The HTML with the attributes applied. Unchanged if it contains no tag.
	 */
	public static function prepare( $html, $index, $count, array $ctx = array() ) {
		$html = (string) $html;
		$tags = new \WP_HTML_Tag_Processor( $html );

		if ( ! $tags->next_tag() ) {
			return $html;
		}

		$label = isset( $ctx['label'] ) ? trim( (string) $ctx['label'] ) : '';
		if ( '' === $label ) {
			$format = isset( $ctx['label_format'] ) && '' !== $ctx['label_format'] ? (string) $ctx['label_format'] : '%1$d of %2$d';
			$label  = sprintf( $format, $index + 1, $count );
		}

		$role_description = isset( $ctx['roledescription'] ) && '' !== $ctx['roledescription'] ? (string) $ctx['roledescription'] : 'slide';

		$tags->set_attribute( 'role', 'group' );
		$tags->set_attribute( 'aria-roledescription', $role_description );
		$tags->set_attribute( 'aria-label', $label );
		$tags->set_attribute( 'data-kadenz-index', (string) (int) $index );

		return $tags->get_updated_html();
	}
}
