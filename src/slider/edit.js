import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import metadata from './block.json';

const TEMPLATE = [ [ 'kadenz/slide' ], [ 'kadenz/slide' ] ];

/**
 * Editor component of the slider block (placeholder).
 *
 * @return {Element} Block element.
 */
export default function Edit() {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: metadata.allowedBlocks,
		template: TEMPLATE,
	} );
	return <div { ...innerBlocksProps } />;
}
