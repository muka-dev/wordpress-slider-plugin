import { BaseControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Three range controls for slides per view per container width.
 *
 * @param {Object}   props          Props.
 * @param {Object}   props.value    `{ base, md, lg }`.
 * @param {Function} props.onChange Called with the new object.
 * @param {boolean}  props.disabled Disable all controls (fade effect).
 * @return {Element} Control element.
 */
export default function PerViewControl( { value, onChange, disabled } ) {
	const steps = [
		{ key: 'base', label: __( 'Small container', 'kadenz-slider' ) },
		{ key: 'md', label: __( 'Medium container', 'kadenz-slider' ) },
		{ key: 'lg', label: __( 'Large container', 'kadenz-slider' ) },
	];

	return (
		<BaseControl
			__nextHasNoMarginBottom
			help={ __(
				'Container widths: medium starts at 600 px, large at 1000 px, measured on the slider itself. Fade always shows one slide.',
				'kadenz-slider'
			) }
		>
			{ steps.map( ( { key, label } ) => (
				<RangeControl
					key={ key }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ label }
					min={ 1 }
					max={ 6 }
					disabled={ disabled }
					value={ value[ key ] }
					onChange={ ( next ) =>
						onChange( { ...value, [ key ]: next } )
					}
				/>
			) ) }
		</BaseControl>
	);
}
