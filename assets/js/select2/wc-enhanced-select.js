jQuery( function( $ ) {

	// Chosen selects
	jQuery(".wc-enhanced-select, select.chosen_select").select2({
		minimumResultsForSearch: jQuery( this ).data( 'minimum_results_for_search' ) ? jQuery( this ).data( 'minimum_results_for_search' ) : '5',
		allowClear:  jQuery( this ).data( 'allow_clear' ) ? true : false,
		placeholder: jQuery( this ).data( 'placeholder' )
	});

});