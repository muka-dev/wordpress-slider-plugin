import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import './editor.scss';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

const icon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		aria-hidden="true"
		focusable="false"
	>
		<path
			fill="currentColor"
			d="M5 6h14a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Zm1.5 1.5v9h11v-9h-11Z"
		/>
		<circle cx="18.5" cy="5.5" r="1.5" fill="currentColor" />
	</svg>
);

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save,
	/**
	 * Shows the optional slide label in the List View.
	 *
	 * @param {Object} attributes Block attributes.
	 * @return {string|undefined} Label text, if set.
	 */
	__experimentalLabel: ( attributes ) => attributes.label || undefined,
} );
