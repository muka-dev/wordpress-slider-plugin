/**
 * Shared helpers for Kadenz Slider E2E tests.
 */
const AxeBuilder = require( '@axe-core/playwright' ).default;
const { expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Inserts a `kadenz/slider` block with `slides` child slides, each containing
 * a paragraph "Slide n".
 *
 * @param {Object} editor                  Editor fixture from `@wordpress/e2e-test-utils-playwright`.
 * @param {Object} [options]               Options.
 * @param {number} [options.slides=2]      Number of slides.
 * @param {Object} [options.attributes={}] Extra attributes for the slider block.
 * @return {Promise<void>} Resolves once the block is inserted.
 */
async function insertSlider( editor, { slides = 2, attributes = {} } = {} ) {
	const innerBlocks = [];
	for ( let n = 1; n <= slides; n++ ) {
		innerBlocks.push( {
			name: 'kadenz/slide',
			innerBlocks: [
				{
					name: 'core/paragraph',
					attributes: { content: `Slide ${ n }` },
				},
			],
		} );
	}
	await editor.insertBlock( {
		name: 'kadenz/slider',
		attributes,
		innerBlocks,
	} );
}

/**
 * Publishes the current post and opens its frontend view in the given page.
 *
 * @param {Object} editor Editor fixture from `@wordpress/e2e-test-utils-playwright`.
 * @param {Object} page   Playwright `Page` used for navigation.
 * @return {Promise<void>} Resolves once the frontend page has loaded.
 */
async function publishAndVisit( editor, page ) {
	const postId = await editor.publishPost();
	await page.goto( `/?p=${ postId }` );
}

/**
 * Asserts there are no axe violations (WCAG 2.0/2.1/2.2 A and AA) within `selector`.
 *
 * @param {Object} page                        Playwright `Page` to analyse.
 * @param {string} [selector='.kadenz-slider'] CSS selector to restrict the scan to.
 * @return {Promise<void>} Resolves when the page has no violations.
 */
async function expectNoAxeViolations( page, selector = '.kadenz-slider' ) {
	const results = await new AxeBuilder( { page } )
		.withTags( [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ] )
		.include( selector )
		.analyze();
	expect( results.violations ).toEqual( [] );
}

module.exports = { insertSlider, publishAndVisit, expectNoAxeViolations };
