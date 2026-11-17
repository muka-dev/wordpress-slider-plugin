import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const TEMPLATE = [
	[
		'core/heading',
		{ level: 2, placeholder: __( 'Slide title', 'kadenz-slider' ) },
	],
	[ 'core/paragraph', { placeholder: __( 'Slide text…', 'kadenz-slider' ) } ],
];

/**
 * Editor component of the slide block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Block element.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'kadenz-slide' } );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		templateLock: false,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Slide', 'kadenz-slider' ) }>
					<TextControl
						label={ __( 'Label', 'kadenz-slider' ) }
						help={ __(
							'Optional name for screen readers. If empty, ‘3 of 7’ is announced.',
							'kadenz-slider'
						) }
						value={ attributes.label }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}
