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
		checkboxHack.bindUpdateAriaExpandedOnInput( checkbox );
		checkboxHack.updateAriaExpanded( checkbox );
		checkboxHack.bindToggleOnEnter( checkbox );

		if ( mw.config.get( 'wgUserName' ) ) {
			checkbox.addEventListener( 'input', saveSidebarState( checkbox ) );
		}
	}
}

function addTocToggle() {
	var $tocModule = $( '#p-mirage-toc' ),
		$button,
		$tocContent;

	if ( !$tocModule.length ) {
		return;
	}

	$tocContent = $tocModule.find( '.skin-mirage-module-body' );

	$button = $( '<span>' )
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
		.on( 'click', function () {
			$tocContent.toggle();
			$button.toggleClass( 'skin-mirage-toc-toggle' );
		} );

	$tocModule.prepend( $button );
}

module.exports = {
	initialize: initialize,
	addTocToggle: addTocToggle
};
