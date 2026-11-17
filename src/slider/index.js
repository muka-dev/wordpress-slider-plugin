import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import './editor.scss';
import Edit from './edit';
import save from './save';
import { sliderIcon } from './icons';
import metadata from './block.json';

registerBlockType( metadata.name, {
	icon: sliderIcon,
	edit: Edit,
	save,
} );
