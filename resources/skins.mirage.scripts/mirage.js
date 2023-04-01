function closeAllDropdowns( $dropdowns ) {
	$dropdowns
		.find( '.skin-mirage-dropdown-list, .skin-mirage-dropdown-sub-list' )
		.addClass( 'skin-mirage-dropdown-hide' );
	$dropdowns
		.find( '.skin-mirage-dropdown-indicator' )
		.removeClass( 'skin-mirage-rotate' );
}

function attachDropdownEvents() {
	const $dropdowns = $( '.skin-mirage-dropdown-container' );

	$dropdowns.on( 'click', function ( event ) {
		// Don't close the list when it is clicked.
		if ( $( event.target ).closest( '.skin-mirage-dropdown-list' ).length > 0 ) {
			return;
		}

		const $element = $( this );
		const $dropdown = $element.find( '.skin-mirage-dropdown-list' )
			.not( '.skin-mirage-dropdown-sub-list' );

		if ( $dropdown.hasClass( 'skin-mirage-dropdown-hide' ) ) {
			closeAllDropdowns( $dropdowns );

			$dropdown.removeClass( 'skin-mirage-dropdown-hide' );
			$element.find( '.skin-mirage-dropdown-indicator' ).addClass( 'skin-mirage-rotate' );
		} else {
			closeAllDropdowns( $dropdowns );
		}
	} );

	$dropdowns.find( '.skin-mirage-sub-list-icon' ).on( 'click', function () {
		closeAllDropdowns( $dropdowns );

		const $element = $( this );

		$element
			.parentsUntil( '.skin-mirage-dropdown-list' )
			.removeClass( 'skin-mirage-dropdown-hide' );
		$element
			.siblings( '.skin-mirage-dropdown-sub-list' )
			.removeClass( 'skin-mirage-dropdown-hide' );
	} );

	$( document ).on( 'click', function ( event ) {
		if ( $( event.target ).closest( $dropdowns ).length === 0 ) {
			closeAllDropdowns( $dropdowns );
		}
	} );
}

function main() {
	const ulsModuleStatus = mw.loader.getState( 'ext.uls.interface' );
	const rightRail = require( './rightrail.js' );

	attachDropdownEvents();

	rightRail.initialize();
	rightRail.addTocToggle();

	// No such thing as $wgResourceLoaderSkinScripts :(
	if ( ulsModuleStatus && ulsModuleStatus !== 'registered' ) {
		mw.loader.using( 'ext.uls.interface' ).then( () => $( '#skin-mirage-language-button' )
			.addClass( 'p-lang--uls-ready' )
			.find( '.skin-mirage-dropdown-list' )
			.remove()
		);
	}
}

main();
