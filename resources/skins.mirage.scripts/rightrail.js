const checkboxHack = require( 'mediawiki.page.ready' ).checkboxHack;
const debounce = require( 'mediawiki.util' ).debounce;
let api;

function saveSidebarState( checkbox ) {
	return debounce( 1000, () => {
		api = api || new mw.Api();
		api.saveOption( 'mirage-show-right-rail', checkbox.checked ? 1 : 0 );
	} );
}

function initialize() {
	const checkbox = window.document.getElementById( 'mirage-right-rail-checkbox' );
	const button = window.document.getElementById( 'mirage-right-rail-button' );

	if ( checkbox instanceof HTMLInputElement && button ) {
		checkboxHack.bindToggleOnClick( checkbox, button );
		checkboxHack.bindUpdateAriaExpandedOnInput( checkbox );
		checkboxHack.updateAriaExpanded( checkbox );
		checkboxHack.bindToggleOnEnter( checkbox );

		if ( mw.user.isNamed() ) {
			checkbox.addEventListener( 'input', saveSidebarState( checkbox ) );
		}
	}
}

function addTocToggle() {
	const $tocModule = $( '#p-mirage-toc' );

	if ( !$tocModule.length ) {
		return;
	}

	const $tocContent = $tocModule.find( '.skin-mirage-module-body' );

	const $button = $( '<span>' )
		.text( mw.msg( 'mirage-toggle-toc' ) )
		.attr( 'role', 'button' )
		.attr( 'id', 'mirage-toc-toggle' )
		.addClass( [
			'skin-mirage-ooui-indicator',
			'skin-mirage-ooui-icon',
			'skin-mirage-ooui-icon-indicator-up',
			'skin-mirage-ooui-icon-small',
			'skin-mirage-ooui-icon-no-label'
		] )
		.on( 'click', () => {
			$tocContent.toggle();
			$button.toggleClass( 'skin-mirage-toc-toggle' );
		} );

	$tocModule.prepend( $button );
}

module.exports = {
	initialize,
	addTocToggle
};
