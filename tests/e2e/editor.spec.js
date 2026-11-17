const AxeBuilder = require( '@axe-core/playwright' ).default;
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const { insertSlider } = require( './utils' );

const CANVAS_SELECTOR = 'iframe[name="editor-canvas"]';

/**
 * Locators for the pieces of the slider UI inside the editor canvas.
 *
 * @param {Object} editor Editor fixture.
 * @return {Object} Locators.
 */
function ui( editor ) {
	const canvas = editor.canvas;
	const navigator = canvas.getByRole( 'group', {
		name: 'Slide navigation',
	} );
	return {
		canvas,
		navigator,
		status: navigator.getByRole( 'status' ),
		add: navigator.getByRole( 'button', { name: 'Add slide' } ),
		duplicate: navigator.getByRole( 'button', { name: 'Duplicate' } ),
		remove: navigator.getByRole( 'button', { name: 'Remove' } ),
		next: navigator.getByRole( 'button', { name: 'Next slide' } ),
		prev: navigator.getByRole( 'button', { name: 'Previous slide' } ),
		slides: canvas.locator( '.wp-block-kadenz-slide' ),
	};
}

/**
 * Opens the block tab of the settings sidebar.
 *
 * @param {Object} page   Page.
 * @param {Object} editor Editor fixture.
 * @return {Promise<Object>} Sidebar locator.
 */
async function openBlockSidebar( page, editor ) {
	await editor.openDocumentSettingsSidebar();
	const sidebar = page.getByRole( 'region', { name: 'Editor settings' } );
	const blockTab = sidebar.getByRole( 'tab', { name: 'Block' } );
	if ( await blockTab.count() ) {
		await blockTab.click();
	}
	return sidebar;
}

/**
 * Expands an inspector panel if it is collapsed.
 *
 * @param {Object} sidebar Sidebar locator.
 * @param {string} name    Panel title.
 */
async function openPanel( sidebar, name ) {
	const toggle = sidebar.getByRole( 'button', { name, exact: true } );
	if ( ( await toggle.getAttribute( 'aria-expanded' ) ) === 'false' ) {
		await toggle.click();
	}
}

/**
 * Reads the attributes of the first slider from the serialized content.
 *
 * @param {Object} editor Editor fixture.
 * @return {Promise<Object>} Attributes stored in the block comment.
 */
async function savedAttributes( editor ) {
	const content = await editor.getEditedPostContent();
	const match = /<!-- wp:kadenz\/slider (\{.*?\}) -->/.exec( content );
	expect( match, 'slider comment with attributes' ).not.toBeNull();
	return JSON.parse( match[ 1 ] );
}

test.describe( 'Kadenz Slider editor UI', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'inserts two slides and adds a third one', async ( { editor } ) => {
		await insertSlider( editor, { slides: 2 } );
		const { status, add, slides } = ui( editor );

		await expect( slides ).toHaveCount( 2 );
		await expect( status ).toHaveText( 'Slide 1 of 2' );

		await add.click();
		await expect( slides ).toHaveCount( 3 );
		await expect( status ).toContainText( '3 of 3' );
	} );

	test( 'shows only the active slide', async ( { editor } ) => {
		await insertSlider( editor, { slides: 3 } );
		const { slides, next, status } = ui( editor );

		await expect( slides.nth( 0 ) ).toBeVisible();
		await expect( slides.nth( 1 ) ).toBeHidden();
		await next.click();
		await expect( status ).toHaveText( 'Slide 2 of 3' );
		await expect( slides.nth( 0 ) ).toBeHidden();
		await expect( slides.nth( 1 ) ).toBeVisible();
	} );

	test( 'follows a selection made in the List View', async ( {
		editor,
		page,
	} ) => {
		await insertSlider( editor, { slides: 2 } );
		const { add, status, slides } = ui( editor );
		await add.click();
		await expect( status ).toContainText( '3 of 3' );

		await page.getByRole( 'button', { name: 'Document Overview' } ).click();
		const listView = page.getByRole( 'treegrid', {
			name: 'Block navigation structure',
		} );
		const firstSlideRow = listView
			.getByRole( 'row', { name: /^Slide/ } )
			.first();
		await firstSlideRow.getByRole( 'link' ).focus();
		await page.keyboard.press( 'ArrowRight' );
		await listView
			.getByRole( 'link', { name: /^Paragraph/ } )
			.first()
			.click();

		await expect( status ).toContainText( '1 of 3' );
		await expect( slides.nth( 0 ) ).toBeVisible();
		await expect( slides.nth( 2 ) ).toBeHidden();
	} );

	test( 'duplicates and removes slides, ending in a placeholder', async ( {
		editor,
	} ) => {
		await insertSlider( editor, { slides: 2 } );
		const { duplicate, remove, status, slides, canvas } = ui( editor );
		const addFirst = canvas.getByRole( 'button', {
			name: 'Add first slide',
		} );

		await duplicate.click();
		await expect( slides ).toHaveCount( 3 );
		await expect( status ).toContainText( '2 of 3' );

		await remove.click();
		await remove.click();
		await expect( slides ).toHaveCount( 1 );
		await remove.click();
		await expect( slides ).toHaveCount( 0 );
		await expect( addFirst ).toBeVisible();

		await addFirst.click();
		await expect( slides ).toHaveCount( 1 );
	} );

	test( 'stores all attributes in the block comment', async ( {
		editor,
		page,
	} ) => {
		await insertSlider( editor, { slides: 2 } );
		const sidebar = await openBlockSidebar( page, editor );
		const spin = ( name ) => sidebar.getByRole( 'spinbutton', { name } );
		const radio = ( name ) =>
			sidebar.getByRole( 'radio', { name, exact: true } );
		const check = ( name ) => sidebar.getByRole( 'checkbox', { name } );

		// Layout.
		await openPanel( sidebar, 'Layout' );
		await radio( 'Fade' ).click();
		await radio( 'Slide' ).click();
		await spin( 'Small container' ).fill( '2' );
		await spin( 'Medium container' ).fill( '3' );
		await spin( 'Large container' ).fill( '4' );
		await spin( 'Gap' ).fill( '2' );
		await spin( 'Peek' ).fill( '40' );
		await sidebar
			.getByLabel( 'Aspect ratio', { exact: true } )
			.selectOption( '4/3' );
		await radio( 'Min height' ).click();
		await spin( 'Minimum height' ).fill( '320' );

		// Behavior.
		await openPanel( sidebar, 'Behavior' );
		await check( 'Autoplay' ).check();
		await expect(
			sidebar.getByText(
				'A pause button is always shown for accessibility.'
			)
		).toBeVisible();
		await spin( 'Interval (seconds)' ).fill( '10' );
		await check( 'Pause on hover' ).uncheck();
		await radio( 'Rewind' ).click();

		// Navigation.
		await openPanel( sidebar, 'Navigation' );
		await check( 'Show arrows' ).uncheck();
		await sidebar.getByLabel( 'Pagination' ).selectOption( 'numbers' );
		await radio( 'Below' ).click();

		// Accessibility.
		await openPanel( sidebar, 'Accessibility' );
		const notice =
			'Give the slider a name so screen reader users know what it contains.';
		await expect( sidebar.getByText( notice ) ).toBeVisible();
		await sidebar
			.getByRole( 'textbox', { name: 'Slider name' } )
			.fill( 'Customer stories' );
		await expect( sidebar.getByText( notice ) ).toBeHidden();

		const attributes = await savedAttributes( editor );
		expect( attributes ).toMatchObject( {
			label: 'Customer stories',
			perView: { base: 2, md: 3, lg: 4 },
			gap: '2rem',
			peek: '40px',
			heightMode: 'min',
			aspectRatio: '4/3',
			minHeight: '320px',
			autoplay: true,
			interval: 10000,
			pauseOnHover: false,
			endBehavior: 'rewind',
			showArrows: false,
			pagination: 'numbers',
			controlsPosition: 'below',
		} );
	} );

	test( 'supports a custom aspect ratio and the fade effect', async ( {
		editor,
		page,
	} ) => {
		await insertSlider( editor, { slides: 2 } );
		const sidebar = await openBlockSidebar( page, editor );
		await openPanel( sidebar, 'Layout' );

		await sidebar
			.getByLabel( 'Aspect ratio', { exact: true } )
			.selectOption( 'custom' );
		await sidebar
			.getByRole( 'textbox', { name: 'Custom aspect ratio' } )
			.fill( '5/2' );
		await sidebar.getByRole( 'radio', { name: 'Fade' } ).click();

		await expect(
			sidebar.getByRole( 'spinbutton', { name: 'Medium container' } )
		).toBeDisabled();

		const attributes = await savedAttributes( editor );
		expect( attributes ).toMatchObject( {
			aspectRatio: '5/2',
			effect: 'fade',
		} );
	} );

	test( 'saves and reloads without validation errors', async ( {
		editor,
		page,
	} ) => {
		await insertSlider( editor, { slides: 2 } );
		await editor.publishPost();
		await page.reload();
		await expect( ui( editor ).slides ).toHaveCount( 2 );
		await expect(
			page.getByText(
				'This block contains unexpected or invalid content'
			)
		).toHaveCount( 0 );
		const blocks = await editor.getBlocks();
		expect( blocks[ 0 ].isValid ).toBe( true );
		expect( blocks[ 0 ].innerBlocks.every( ( b ) => b.isValid ) ).toBe(
			true
		);
	} );

	test( 'is operable with the keyboard only', async ( { editor, page } ) => {
		await insertSlider( editor, { slides: 3 } );
		const { next, prev, status } = ui( editor );

		await next.focus();
		await page.keyboard.press( 'Enter' );
		await expect( status ).toHaveText( 'Slide 2 of 3' );
		await prev.focus();
		await page.keyboard.press( 'Space' );
		await expect( status ).toHaveText( 'Slide 1 of 3' );

		const sidebar = await openBlockSidebar( page, editor );
		const behavior = sidebar.getByRole( 'button', {
			name: 'Behavior',
			exact: true,
		} );
		await behavior.focus();
		await page.keyboard.press( 'Enter' );
		await expect( behavior ).toHaveAttribute( 'aria-expanded', 'true' );
		const autoplay = sidebar.getByRole( 'checkbox', { name: 'Autoplay' } );
		await autoplay.focus();
		await page.keyboard.press( 'Space' );
		await expect( autoplay ).toBeChecked();
	} );

	test( 'has no axe violations in the editor canvas', async ( {
		editor,
		page,
	} ) => {
		await insertSlider( editor, { slides: 2 } );
		await expect( ui( editor ).navigator ).toBeVisible();

		const results = await new AxeBuilder( { page } )
			.withTags( [
				'wcag2a',
				'wcag2aa',
				'wcag21a',
				'wcag21aa',
				'wcag22aa',
			] )
			.include( [ [ CANVAS_SELECTOR, '.kadenz-slider-editor' ] ] )
			.analyze();
		expect( results.violations ).toEqual( [] );
	} );
} );
