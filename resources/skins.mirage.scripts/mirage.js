$( function () {
	var $dropdowns = $( '.skin-mirage-dropdown-container' );

	function closeAll() {
		$dropdowns
			.find( '.skin-mirage-dropdown-list, .skin-mirage-dropdown-sub-list' )
			.addClass( 'skin-mirage-dropdown-hide' );
		$dropdowns
			.find( '.skin-mirage-dropdown-indicator' )
			.removeClass( 'skin-mirage-rotate' );
	}

	$dropdowns.on( 'click', function ( event ) {
		var $element = $( this ),
			$dropdown = $element.find( '.skin-mirage-dropdown-list' )
				.not( '.skin-mirage-dropdown-sub-list' );

		// Don't close the list when it is clicked.
		if ( $( event.target ).closest( '.skin-mirage-dropdown-list' ).length > 0 ) {
			return;
		}

		if ( $dropdown.hasClass( 'skin-mirage-dropdown-hide' ) ) {
			closeAll();

			$dropdown.removeClass( 'skin-mirage-dropdown-hide' );
			$element.find( '.skin-mirage-dropdown-indicator' ).addClass( 'skin-mirage-rotate' );
		} else {
			closeAll();
		}
	} );

	$dropdowns.find( '.skin-mirage-sub-list-icon' ).on( 'click', function () {
		var $element = $( this );

		closeAll();

		$element
			.parentsUntil( '.skin-mirage-dropdown-list' )
			.removeClass( 'skin-mirage-dropdown-hide' );
		$element
			.siblings( '.skin-mirage-dropdown-sub-list' )
			.removeClass( 'skin-mirage-dropdown-hide' );
	} );

	$( document ).on( 'click', function ( event ) {
		if ( $( event.target ).closest( $dropdowns ).length === 0 ) {
			closeAll();
		}
	} );
} );
