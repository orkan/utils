window.demo = window.demo || {};

(function ($) {

	$.extend( demo, {
		/*
		 * -------------------------------------------------------------------------------------------------------------
		 * Selector
		 */
		selectAll: function() {
			$( '#selector :checkbox' ).prop( 'checked', true );
		},
		selectNone: function() {
			$( '#selector :checkbox' ).prop( 'checked', false );
		},
		selectInvert: function() {
			$( '#selector :checkbox' ).each( function() {
				$( this ).prop( 'checked', !$( this ).is( ':checked' ) );
			});
		},
		selectCbox: function() {
			this.selectNone();
			$( '#selector :checkbox[name*=checkbox]' )
				.add( '#selector :checkbox[name*=Cbox]' )
				.each( function() {
					$( this ).prop( 'checked', true );
			});
		},
		selectRadio: function() {
			this.selectNone();
			$( '#selector :checkbox[name*=radio]' ).each( function() {
					$( this ).prop( 'checked', true );
			});
		},
		selectName: function( text ) {
			this.selectNone();
			$( '#selector :checkbox' )
				.filter( function() {
					return $( this ).attr( 'name' ).toLowerCase().indexOf( text.toLowerCase() ) > -1;
				})
				.each( function() {
					$( this ).prop( 'checked', true );
			});
		},
	});

	// onLoad
	$(function() {
	});

})( jQuery );
