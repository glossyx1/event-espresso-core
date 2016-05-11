/* 
 * Hook into when the EE forms are initialized, and initialize the select2 inputs
 * indicated in the localized
 */
jQuery(document).bind( 'EEFV:initialize_specific_form', function(event, passed_in_args){
	if ( typeof passed_in_args.form_data !== 'undefined'
		&& typeof passed_in_args.form_data.other_data !== 'undefined'
		&& typeof passed_in_args.form_data.other_data.select2s !== 'undefined'
	) {
		//initialize each of these select2 inputs
		jQuery.each( passed_in_args.form_data.other_data.select2s , function( select2_input_id, select2_args ) {
			if( typeof select2_args.ajax !== 'undefined' ) {
				if( typeof select2_args.ajax.data_interface !== 'undefined' ) {
					var function_name_or_declaration = select2_args.ajax.data_interface;
					var function_named = window[ function_name_or_declaration ];
					if( typeof function_named === 'function' ) {
						var obj_for_data = new function_named( select2_args.ajax.data_interface_args );
						select2_args.ajax.data = function( params) {
							return obj_for_data.prepData( params );
						};
						select2_args.ajax.beforeSend = function( xhr ) {
							return obj_for_data.beforeSend( xhr );
						};
						select2_args.ajax.processResults = function( data, params ) {
							return obj_for_data.processResults( data, params );
						};
						
					}
				}
			}
			var input = jQuery( '#' + select2_input_id ).select2( select2_args );
		});
	}
});

function EE_Select2_REST_API_Interface( data_interface_args ) {
	this.default_query_params = data_interface_args.default_query_params || {};
	this.items_per_page = this.default_query_params.limit || 10;
	this.display_field = data_interface_args.display_field;
	this.value_field = data_interface_args.value_field;
	this.nonce = data_interface_args.nonce;
	
	/**
	 * Changes the request params set by select2 and prepares them for an EE4 REST request
	 * @param object params
	 * @returns object
	 */
	this.prepData = function ( params ) {
		params.page = params.page || 1;
		var new_params =  this.default_query_params;
		new_params.limit = [
			( params.page - 1 ) * this.items_per_page,
			this.items_per_page
		];
		if( typeof new_params.where === 'undefined' ) {
			new_params.where = {};
		}
		var search_term = params.term || '';
		new_params.where[this.display_field]= [ 'like', '%' + search_term + '%' ];
		new_params.include=this.display_field;
		new_params._wpnonce = this.nonce;
		return new_params;
	};
	
	/**
	 * Sets the wp nonce header for authentication
	 * @param xhr xhr
	 * @returns void
	 */
	this.beforeSend = function( xhr ) {
		 xhr.setRequestHeader( 'X-WP-Nonce', this.nonce );
//		 if (beforeSend) {
//			return beforeSend.apply(this, arguments);
//		}
	}
	
	/**
	 * Takes incoming EE4 REST API response and turns into a data format select2 can handle
	 * @param object data
	 * @param object params
	 * @returns object
	 */
	this.processResults = function ( data, params ){
		formatted_results = [];
		for( var i=0; i<data.length; i++ ) {
			formatted_results.push(
				{
					id: data[i][this.value_field], 
					text: data[i][this.display_field]
				}
			);
		}
		params.page = params.page || 1;

		return {
		  results: formatted_results,
		  pagination: {
			more: data.length == this.items_per_page
		  }
		}
	};
}