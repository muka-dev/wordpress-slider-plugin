import {
	BlockControls,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Button,
	Notice,
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';

import metadata from './block.json';
import { sliderIcon } from './icons';
import PerViewControl from './components/PerViewControl';
import SlideNavigator from './components/SlideNavigator';
import {
	ToggleGroupControl,
	ToggleGroupControlOption,
	UnitControl,
} from './components/compat';

const TEMPLATE = [ [ 'kadenz/slide' ], [ 'kadenz/slide' ] ];
const ASPECT_PRESETS = [ '16/9', '4/3', '3/2', '1/1', '21/9', '9/16' ];
const CUSTOM_RATIO = 'custom';

const GAP_UNITS = [
	{ value: 'px', label: 'px', default: 0 },
	{ value: 'rem', label: 'rem', default: 1.5 },
	{ value: 'em', label: 'em', default: 1.5 },
	{ value: '%', label: '%', default: 2 },
	{ value: 'vw', label: 'vw', default: 2 },
];
const HEIGHT_UNITS = [
	{ value: 'px', label: 'px', default: 400 },
	{ value: 'rem', label: 'rem', default: 25 },
	{ value: 'em', label: 'em', default: 25 },
	{ value: 'vh', label: 'vh', default: 50 },
	{ value: 'svh', label: 'svh', default: 50 },
	{ value: 'dvh', label: 'dvh', default: 50 },
	{ value: 'vw', label: 'vw', default: 50 },
	{ value: '%', label: '%', default: 50 },
];

/**
 * Converts `var:preset|spacing|slug` values to a CSS custom property.
 *
 * @param {string} value Attribute value.
 * @return {string} CSS value usable in the editor.
 */
function toCssLength( value ) {
	const match = /^var:preset\|spacing\|([a-z0-9-]+)$/.exec( value || '' );
	return match ? `var(--wp--preset--spacing--${ match[ 1 ] })` : value;
}

/**
 * Editor component of the slider block.
 *
 * Shows one slide at a time. The active index is local state and is never
 * saved.
 *
 * @param {Object}   props               Block props.
 * @param {string}   props.clientId      Block client ID.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Block element.
 */
export default function Edit( { clientId, attributes, setAttributes } ) {
	const {
		label,
		effect,
		perView,
		gap,
		peek,
		heightMode,
		aspectRatio,
		minHeight,
		autoplay,
		interval,
		pauseOnHover,
		endBehavior,
		showArrows,
		pagination,
		controlsPosition,
	} = attributes;

	const [ activeIndex, setActiveIndex ] = useState( 0 );
	const [ customRatio, setCustomRatio ] = useState(
		! ASPECT_PRESETS.includes( aspectRatio )
	);

	const { slideIds, selectedSlideIndex, isActive } = useSelect(
		( select ) => {
			const store = select( 'core/block-editor' );
			const ids = store.getBlockOrder( clientId );
			const selectedId = store.getSelectedBlockClientId();
			let selectedIndex = -1;
			if ( selectedId ) {
				const chain = [
					...store.getBlockParents( selectedId ),
					selectedId,
				];
				selectedIndex = ids.findIndex( ( id ) => chain.includes( id ) );
			}
			return {
				slideIds: ids,
				selectedSlideIndex: selectedIndex,
				isActive:
					selectedId === clientId ||
					store.hasSelectedInnerBlock( clientId, true ),
			};
		},
		[ clientId ]
	);
	const { insertBlock, removeBlock, duplicateBlocks, selectBlock } =
		useDispatch( 'core/block-editor' );

	const count = slideIds.length;
	const safeIndex = Math.min( activeIndex, Math.max( 0, count - 1 ) );

	// Follow the selection into the matching slide.
	useEffect( () => {
		if ( selectedSlideIndex >= 0 ) {
			setActiveIndex( selectedSlideIndex );
		}
	}, [ selectedSlideIndex ] );

	// Keep the index in range after slides were removed.
	useEffect( () => {
		if ( activeIndex !== safeIndex ) {
			setActiveIndex( safeIndex );
		}
	}, [ activeIndex, safeIndex ] );

	const addSlide = () => {
		const at = count === 0 ? 0 : safeIndex + 1;
		insertBlock( createBlock( 'kadenz/slide' ), at, clientId );
		setActiveIndex( at );
	};
	const duplicateSlide = () => {
		if ( ! slideIds[ safeIndex ] ) {
			return;
		}
		duplicateBlocks( [ slideIds[ safeIndex ] ] );
		setActiveIndex( safeIndex + 1 );
	};
	const removeSlide = () => {
		if ( ! slideIds[ safeIndex ] ) {
			return;
		}
		removeBlock( slideIds[ safeIndex ], false );
		setActiveIndex( Math.max( 0, safeIndex - 1 ) );
		selectBlock( clientId );
	};
	const goTo = ( index ) => {
		setActiveIndex( index );
		if ( slideIds[ index ] ) {
			selectBlock( slideIds[ index ] );
		}
	};

	const slidesStyle = {};
	if ( heightMode === 'aspect' && aspectRatio ) {
		slidesStyle.aspectRatio = aspectRatio;
	}
	if ( heightMode === 'min' && minHeight ) {
		slidesStyle.minHeight = toCssLength( minHeight );
	}
	const blockProps = useBlockProps( {
		className: `kadenz-slider-editor is-height-${ heightMode } is-effect-${ effect }`,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'kadenz-slider-editor__slides has-active-slide',
			style: slidesStyle,
		},
		{
			allowedBlocks: metadata.allowedBlocks,
			template: TEMPLATE,
			renderAppender: false,
		}
	);

	// Only the active slide is visible; `clientId` is a UUID, so safe in CSS.
	const visibilityCss =
		count > 0
			? `#block-${ clientId } > .kadenz-slider-editor__slides > :not(:nth-child(${
					safeIndex + 1
			  })) { display: none; }`
			: '';

	const isFade = effect === 'fade';
	const ratioSelectValue =
		customRatio || ! ASPECT_PRESETS.includes( aspectRatio )
			? CUSTOM_RATIO
			: aspectRatio;

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton onClick={ addSlide }>
						{ _x( 'Add slide', 'button', 'kadenz-slider' ) }
					</ToolbarButton>
				</ToolbarGroup>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'kadenz-slider' ) }>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						isBlock
						label={ __( 'Effect', 'kadenz-slider' ) }
						value={ effect }
						onChange={ ( value ) =>
							setAttributes( { effect: value } )
						}
					>
						<ToggleGroupControlOption
							value="slide"
							label={ __( 'Slide', 'kadenz-slider' ) }
						/>
						<ToggleGroupControlOption
							value="fade"
							label={ __( 'Fade', 'kadenz-slider' ) }
						/>
					</ToggleGroupControl>
					<PerViewControl
						value={ perView }
						disabled={ isFade }
						onChange={ ( value ) =>
							setAttributes( { perView: value } )
						}
					/>
					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Gap', 'kadenz-slider' ) }
						units={ GAP_UNITS }
						value={ toCssLength( gap ) }
						onChange={ ( value ) =>
							setAttributes( { gap: value || '1.5rem' } )
						}
					/>
					<UnitControl
						__next40pxDefaultSize
						label={ __( 'Peek', 'kadenz-slider' ) }
						help={ __(
							'How much of the neighbouring slide shows.',
							'kadenz-slider'
						) }
						units={ GAP_UNITS }
						value={ toCssLength( peek ) }
						onChange={ ( value ) =>
							setAttributes( { peek: value || '0px' } )
						}
					/>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						isBlock
						label={ __( 'Height', 'kadenz-slider' ) }
						value={ heightMode }
						onChange={ ( value ) =>
							setAttributes( { heightMode: value } )
						}
					>
						<ToggleGroupControlOption
							value="aspect"
							label={ __( 'Aspect ratio', 'kadenz-slider' ) }
						/>
						<ToggleGroupControlOption
							value="min"
							label={ __( 'Min height', 'kadenz-slider' ) }
						/>
						<ToggleGroupControlOption
							value="auto"
							label={ __( 'Auto', 'kadenz-slider' ) }
						/>
					</ToggleGroupControl>
					{ heightMode === 'aspect' && (
						<>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Aspect ratio', 'kadenz-slider' ) }
								value={ ratioSelectValue }
								options={ [
									...ASPECT_PRESETS.map( ( ratio ) => ( {
										value: ratio,
										label: ratio.replace( '/', ':' ),
									} ) ),
									{
										value: CUSTOM_RATIO,
										label: __( 'Custom', 'kadenz-slider' ),
									},
								] }
								onChange={ ( value ) => {
									if ( value === CUSTOM_RATIO ) {
										setCustomRatio( true );
									} else {
										setCustomRatio( false );
										setAttributes( { aspectRatio: value } );
									}
								} }
							/>
							{ ratioSelectValue === CUSTOM_RATIO && (
								<TextControl
									__nextHasNoMarginBottom
									__next40pxDefaultSize
									label={ __(
										'Custom aspect ratio',
										'kadenz-slider'
									) }
									help={ __(
										'Width / height, for example 5/2.',
										'kadenz-slider'
									) }
									value={ aspectRatio }
									onChange={ ( value ) =>
										setAttributes( { aspectRatio: value } )
									}
								/>
							) }
						</>
					) }
					{ heightMode === 'min' && (
						<UnitControl
							__next40pxDefaultSize
							label={ __( 'Minimum height', 'kadenz-slider' ) }
							units={ HEIGHT_UNITS }
							value={ toCssLength( minHeight ) }
							onChange={ ( value ) =>
								setAttributes( { minHeight: value || '' } )
							}
						/>
					) }
				</PanelBody>
				<PanelBody
					title={ __( 'Behavior', 'kadenz-slider' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Autoplay', 'kadenz-slider' ) }
						help={
							autoplay
								? __(
										'A pause button is always shown for accessibility.',
										'kadenz-slider'
								  )
								: undefined
						}
						checked={ autoplay }
						onChange={ ( value ) =>
							setAttributes( { autoplay: value } )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Interval (seconds)', 'kadenz-slider' ) }
						min={ 2 }
						max={ 30 }
						disabled={ ! autoplay }
						value={ Math.round( interval / 1000 ) }
						onChange={ ( value ) =>
							setAttributes( {
								interval: ( value ?? 6 ) * 1000,
							} )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Pause on hover', 'kadenz-slider' ) }
						checked={ pauseOnHover }
						onChange={ ( value ) =>
							setAttributes( { pauseOnHover: value } )
						}
					/>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						isBlock
						label={ __( 'At the last slide', 'kadenz-slider' ) }
						value={ endBehavior }
						onChange={ ( value ) =>
							setAttributes( { endBehavior: value } )
						}
					>
						<ToggleGroupControlOption
							value="stop"
							label={ __( 'Stop', 'kadenz-slider' ) }
						/>
						<ToggleGroupControlOption
							value="rewind"
							label={ __( 'Rewind', 'kadenz-slider' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
				<PanelBody
					title={ __( 'Navigation', 'kadenz-slider' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show arrows', 'kadenz-slider' ) }
						checked={ showArrows }
						onChange={ ( value ) =>
							setAttributes( { showArrows: value } )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Pagination', 'kadenz-slider' ) }
						value={ pagination }
						options={ [
							{
								value: 'none',
								label: __( 'None', 'kadenz-slider' ),
							},
							{
								value: 'dots',
								label: __( 'Dots', 'kadenz-slider' ),
							},
							{
								value: 'numbers',
								label: __( 'Numbers', 'kadenz-slider' ),
							},
							{
								value: 'progress',
								label: __( 'Progress bar', 'kadenz-slider' ),
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { pagination: value } )
						}
					/>
					<ToggleGroupControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						isBlock
						label={ __( 'Controls position', 'kadenz-slider' ) }
						value={ controlsPosition }
						onChange={ ( value ) =>
							setAttributes( { controlsPosition: value } )
						}
					>
						<ToggleGroupControlOption
							value="overlay"
							label={ __( 'Overlay', 'kadenz-slider' ) }
						/>
						<ToggleGroupControlOption
							value="below"
							label={ __( 'Below', 'kadenz-slider' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
				<PanelBody
					title={ __( 'Accessibility', 'kadenz-slider' ) }
					initialOpen={ false }
				>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Slider name', 'kadenz-slider' ) }
						help={ __(
							'Announced by screen readers, for example “Customer stories”.',
							'kadenz-slider'
						) }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
					{ ! label && (
						<Notice
							status="warning"
							isDismissible={ false }
							className="kadenz-slider-editor__notice"
						>
							{ __(
								'Give the slider a name so screen reader users know what it contains.',
								'kadenz-slider'
							) }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				{ visibilityCss && <style>{ visibilityCss }</style> }
				{ count === 0 && (
					<Placeholder
						icon={ sliderIcon }
						label={ __( 'Slider', 'kadenz-slider' ) }
						instructions={ __(
							'This slider has no slides yet.',
							'kadenz-slider'
						) }
					>
						<Button variant="primary" onClick={ addSlide }>
							{ __( 'Add first slide', 'kadenz-slider' ) }
						</Button>
					</Placeholder>
				) }
				<div { ...innerBlocksProps } />
				{ isActive && count > 0 && (
					<SlideNavigator
						index={ safeIndex }
						count={ count }
						onSelect={ goTo }
						onAdd={ addSlide }
						onDuplicate={ duplicateSlide }
						onRemove={ removeSlide }
					/>
				) }
			</div>
		</>
	);
}
