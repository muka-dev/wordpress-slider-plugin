import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Save component of the slide block.
 *
 * @return {Element} Inner blocks content.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
