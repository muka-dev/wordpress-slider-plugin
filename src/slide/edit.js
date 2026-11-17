import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const TEMPLATE = [
	[
		'core/paragraph',
		{ placeholder: __( 'Slide content…', 'kadenz-slider' ) },
	],
];

/**
 * Editor component of the slide block (placeholder).
 *
 * @return {Element} Block element.
 */
export default function Edit() {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );
	return <div { ...innerBlocksProps } />;
}
