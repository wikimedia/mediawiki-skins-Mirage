var checkboxHack = require( 'mediawiki.page.ready' ).checkboxHack,
	debounce = require( 'mediawiki.util' ).debounce,
	api;

function saveSidebarState( checkbox ) {
	return debounce( 1000, function () {
		api = api || new mw.Api();
		api.saveOption( 'mirage-show-right-rail', checkbox.checked ? 1 : 0 );
	} );
}

function initialize() {
	var checkbox = window.document.getElementById( 'mirage-right-rail-checkbox' ),
		button = window.document.getElementById( 'mirage-right-rail-button' );

	if ( checkbox instanceof HTMLInputElement && button ) {
		checkboxHack.bindToggleOnClick( checkbox, button );
		checkboxHack.bindUpdateAriaExpandedOnInput( checkbox, button );
		checkboxHack.updateAriaExpanded( checkbox, button );
		checkboxHack.bindToggleOnSpaceEnter( checkbox, button );

		if ( mw.config.get( 'wgUserName' ) ) {
			checkbox.addEventListener( 'input', saveSidebarState( checkbox ) );
		}
	}
}

module.exports = {
	initialize: initialize
};
