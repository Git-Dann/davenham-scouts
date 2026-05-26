( function ( $, wp ) {
	'use strict';

	if ( ! $ || ! wp || ! wp.media ) {
		return;
	}

	function openFrame( $urlTarget, $idTarget ) {
		const frame = wp.media( {
			title: 'Choose image',
			button: { text: 'Use this image' },
			multiple: false,
		} );

		frame.on( 'select', function () {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			$urlTarget.val( attachment.url ).trigger( 'change' );
			if ( $idTarget.length ) {
				$idTarget.val( attachment.id ).trigger( 'change' );
			}
		} );

		frame.open();
	}

	$( document ).on( 'click', '.db-media-open', function ( event ) {
		event.preventDefault();
		const $button = $( this );
		const $urlTarget = $( $button.data( 'target' ) );
		const $idTarget = $( $button.data( 'id-target' ) );
		openFrame( $urlTarget, $idTarget );
	} );

	$( document ).on( 'click', '.db-media-clear', function ( event ) {
		event.preventDefault();
		const $button = $( this );
		$( $button.data( 'target' ) ).val( '' ).trigger( 'change' );
		$( $button.data( 'id-target' ) ).val( '0' ).trigger( 'change' );
	} );
} )( window.jQuery, window.wp );
