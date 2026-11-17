import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Save component of the slider block.
 *
 * @return {Element} Inner blocks content.
 */
export default function save() {
	return <InnerBlocks.Content />;
}
