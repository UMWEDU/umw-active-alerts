jQuery( function( $ ) {
	$( '#advisory_expires_meta_box .datetimepicker, .inline-edit-group .datetimepicker' ).datetimepicker( { 'dateFormat':'yy-mm-dd', 'stepMinute':15, 'maxDate':'+3d', 'minDate':'-0d' } );
} );