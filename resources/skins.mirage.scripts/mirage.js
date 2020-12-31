$( function () {
	var $dropdowns = $( '.mirage-dropdown-container' );

	function closeAll() {
		$dropdowns
			.find( '.dropdown-list, .dropdown-sub-list' )
			.addClass( 'dropdown-hide' );
		$dropdowns
			.find( '.dropdown-indicator' )
			.removeAttr( 'style' );
	}

	$dropdowns.on( 'click', function ( event ) {
		var $element = $( this ),
			$dropdown = $element.find( '.dropdown-list' ).not( '.dropdown-sub-list' );

		// Don't close the list when it is clicked.
		if ( $( event.target ).closest( '.dropdown-list' ).length > 0 ) {
			return;
		}

		if ( $dropdown.hasClass( 'dropdown-hide' ) ) {
			closeAll();

			$dropdown.removeClass( 'dropdown-hide' );
			$element.find( '.dropdown-indicator' ).css( {
				'-webkit-transform': 'rotate( 180deg )',
				'-moz-transform': 'rotate( 180deg )',
				transform: 'rotate( 180deg )'
			} );
		} else {
			closeAll();
		}
	} );

	$dropdowns.find( '.mirage-sub-list-icon' ).on( 'click', function () {
		var $element = $( this );

		closeAll();

		$element
			.parentsUntil( '.dropdown-list' )
			.removeClass( 'dropdown-hide' );
		$element
			.siblings( '.dropdown-sub-list' )
			.removeClass( 'dropdown-hide' );
	} );

	$( document ).on( 'click', function ( event ) {
		if ( $( event.target ).closest( $dropdowns ).length === 0 ) {
			closeAll();
		}
	} );
} );
