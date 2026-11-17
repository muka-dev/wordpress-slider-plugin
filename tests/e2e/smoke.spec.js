const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );
const { insertSlider, publishAndVisit } = require( './utils' );

test.describe( 'Kadenz Slider smoke test', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'renders slides on the frontend', async ( { editor, page } ) => {
		await insertSlider( editor, { slides: 2 } );
		await publishAndVisit( editor, page );
		const slides = page.locator( '.wp-block-kadenz-slide' );
		await expect( slides ).toHaveCount( 2 );
		await expect( slides.first() ).toBeVisible();
	} );
} );
