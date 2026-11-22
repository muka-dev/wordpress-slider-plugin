import { Button } from '@wordpress/components';
import { __, _x, isRTL, sprintf } from '@wordpress/i18n';

/**
 * Navigator below the slider: previous/next, position and slide management.
 *
 * @param {Object}   props             Props.
 * @param {number}   props.index       Zero-based active slide index.
 * @param {number}   props.count       Number of slides.
 * @param {Function} props.onSelect    Called with a new zero-based index.
 * @param {Function} props.onAdd       Adds a slide.
 * @param {Function} props.onDuplicate Duplicates the active slide.
 * @param {Function} props.onRemove    Removes the active slide.
 * @return {Element} Navigator element.
 */
export default function SlideNavigator( {
	index,
	count,
	onSelect,
	onAdd,
	onDuplicate,
	onRemove,
} ) {
	const rtl = isRTL();
	const prevGlyph = rtl ? '›' : '‹';
	const nextGlyph = rtl ? '‹' : '›';

	return (
		<div
			className="kadenz-slider-navigator"
			role="group"
			aria-label={ __( 'Slide navigation', 'kadenz-slider' ) }
		>
			<div className="kadenz-slider-navigator__position">
				<Button
					variant="tertiary"
					size="compact"
					label={ __( 'Previous slide', 'kadenz-slider' ) }
					disabled={ index <= 0 }
					onClick={ () => onSelect( index - 1 ) }
				>
					<span aria-hidden="true">{ prevGlyph }</span>
				</Button>
				<span
					className="kadenz-slider-navigator__status"
					role="status"
					aria-live="polite"
				>
					{ sprintf(
						/* translators: 1: current slide number, 2: total number of slides. */
						__( 'Slide %1$d of %2$d', 'kadenz-slider' ),
						index + 1,
						count
					) }
				</span>
				<Button
					variant="tertiary"
					size="compact"
					label={ __( 'Next slide', 'kadenz-slider' ) }
					disabled={ index >= count - 1 }
					onClick={ () => onSelect( index + 1 ) }
				>
					<span aria-hidden="true">{ nextGlyph }</span>
				</Button>
			</div>
			<div className="kadenz-slider-navigator__actions">
				<Button variant="secondary" size="compact" onClick={ onAdd }>
					{ _x( 'Add slide', 'button', 'kadenz-slider' ) }
				</Button>
				<Button
					variant="secondary"
					size="compact"
					onClick={ onDuplicate }
				>
					{ __( 'Duplicate', 'kadenz-slider' ) }
				</Button>
				<Button
					variant="secondary"
					size="compact"
					isDestructive
					onClick={ onRemove }
				>
					{ __( 'Remove', 'kadenz-slider' ) }
				</Button>
			</div>
		</div>
	);
}
