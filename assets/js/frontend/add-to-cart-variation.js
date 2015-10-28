/*global wc_add_to_cart_variation_params */
/*global wc_cart_fragments_params */
/*!
 * Variations Plugin
 */
;(function ( $, window, document, undefined ) {

	$.fn.wc_variation_form = function() {
		var $form               = this;
		var $product            = $form.closest('.product');
		var $product_id         = parseInt( $form.data( 'product_id' ), 10 );
		var $product_variations = $form.data( 'product_variations' );
		var $use_ajax           = $product_variations === false;
		var $xhr                = false;
		var $reset_variations   = $form.find( '.reset_variations' );

		// Unbind any existing events
		$form.unbind( 'check_variations update_variation_values found_variation' );
		$form.find( '.reset_variations' ).unbind( 'click' );
		$form.find( '.variations select' ).unbind( 'change focusin' );

		// Bind new events to form
		$form

		// On clicking the reset variation button
		.on( 'click', '.reset_variations', function() {
			$form.find( '.variations select' ).val( '' ).change();
			$form.trigger( 'reset_data' );
			return false;
		} )

		// When the variation is hidden
		.on( 'hide_variation', function() {
			$form.find( '.single_add_to_cart_button' ).attr( 'disabled', 'disabled' ).attr( 'title', wc_add_to_cart_variation_params.i18n_make_a_selection_text );
			return false;
		} )

		// When the variation is revealed
		.on( 'show_variation', function( event, variation, purchasable ) {
			if ( purchasable ) {
				$form.find( '.single_add_to_cart_button' ).removeAttr( 'disabled' ).removeAttr( 'title' );
			} else {
				$form.find( '.single_add_to_cart_button' ).attr( 'disabled', 'disabled' ).attr( 'title', wc_add_to_cart_variation_params.i18n_unavailable_text );
			}
			return false;
		} )

		// Reload product variations data
		.on( 'reload_product_variations', function() {
			$product_variations = $form.data( 'product_variations' );
			$use_ajax           = $product_variations === false;
		} )

		// Reset product data
		.on( 'reset_data', function() {
			$('.sku').wc_reset_content();
			$('.product_weight').wc_reset_content();
			$('.product_dimensions').wc_reset_content();
			$form.trigger( 'reset_image' );
			$form.find( '.single_variation' ).slideUp( 200 ).trigger( 'hide_variation' );
		} )

		// Reset product image
		.on( 'reset_image', function() {
			var $product_img = $product.find( 'div.images img:eq(0)' ),
				$product_link = $product.find( 'div.images a.zoom:eq(0)' ),
				o_src = $product_img.attr( 'data-o_src' ),
				o_title = $product_img.attr( 'data-o_title' ),
				o_alt = $product_img.attr( 'data-o_title' ),
				o_href = $product_link.attr( 'data-o_href' );

			if ( o_src !== undefined ) {
				$product_img.attr( 'src', o_src );
			}
			if ( o_href !== undefined ) {
				$product_link.attr( 'href', o_href );
			}
			if ( o_title !== undefined ) {
				$product_img.attr( 'title', o_title );
				$product_link.attr( 'title', o_title );
			}
			if ( o_alt !== undefined ) {
				$product_img.attr( 'alt', o_alt );
			}
		} )

		// On changing an attribute
		.on( 'change', '.variations select', function() {
			$form.find( 'input[name="variation_id"], input.variation_id' ).val( '' ).change();
			$form.find( '.wc-no-matching-variations' ).remove();

			if ( $use_ajax ) {
				if ( $xhr ) {
					$xhr.abort();
				}

				var all_attributes_chosen  = true;
				var some_attributes_chosen = false;
				var data                   = {};

				$form.find( '.variations select' ).each( function() {
					var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );

					if ( $( this ).val().length === 0 ) {
						all_attributes_chosen = false;
					} else {
						some_attributes_chosen = true;
					}

					data[ attribute_name ] = $( this ).val();
				});

				if ( all_attributes_chosen ) {
					// Get a matchihng variation via ajax
					data.product_id = $product_id;

					$xhr = $.ajax( {
						url: wc_cart_fragments_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_variation' ),
						type: 'POST',
						data: data,
						success: function( variation ) {
							if ( variation ) {
								$form.find( 'input[name="variation_id"], input.variation_id' )
									.val( variation.variation_id )
									.change();
								$form.trigger( 'found_variation', [ variation ] );
							} else {
								$form.trigger( 'reset_data' );
								$form.find( '.single_variation_wrap' ).after( '<p class="wc-no-matching-variations woocommerce-info">' + wc_add_to_cart_variation_params.i18n_no_matching_variations_text + '</p>' );
								$form.find( '.wc-no-matching-variations' ).slideDown( 200 );
							}
						}
					} );
				} else {
					$form.trigger( 'reset_data' );
				}
				if ( some_attributes_chosen ) {
					if ( $reset_variations.css( 'visibility' ) === 'hidden' ) {
						$reset_variations.css( 'visibility', 'visible' ).hide().fadeIn();
					}
				} else {
					$reset_variations.css( 'visibility', 'hidden' );
				}
			} else {
				$form.trigger( 'woocommerce_variation_select_change' );
				$form.trigger( 'check_variations', [ '', false ] );
				$( this ).blur();
			}

			// added to get around variation image flicker issue
			$( '.product.has-default-attributes > .images' ).fadeTo( 200, 1 );

			// Custom event for when variation selection has been changed
			$form.trigger( 'woocommerce_variation_has_changed' );
		} )

		// Upon gaining focus
		.on( 'focusin touchstart', '.variations select', function() {
			$( this ).find( 'option:selected' ).attr( 'selected', 'selected' );

			if ( ! $use_ajax ) {
				$form.trigger( 'woocommerce_variation_select_focusin' );
				$form.trigger( 'check_variations', [ $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' ), true ] );
			}
		} )

		// Show single variation details (price, stock, image)
		.on( 'found_variation', function( event, variation ) {
			var template               = wp.template( 'variation-template' ),
				button_template        = wp.template( 'variation-add-to-cart-template' ),
				unavailable_template   = wp.template( 'unavailable-variation-template' ),
				$single_variation_wrap = $form.find( '.single_variation_wrap' ),
				$sku                   = $product.find( '.product_meta' ).find( '.sku' ),
				$weight                = $product.find( '.product_weight' ),
				$dimensions            = $product.find( '.product_dimensions' ),
				$single_variation      = $form.find( '.single_variation' ),
				$variations_button     = $form.find( '.variations_button' ),
				purchasable            = true;

			// Display SKU
			if ( variation.sku ) {
				$sku.wc_set_content( variation.sku );
			} else {
				$sku.wc_reset_content();
			}

			// Display weight
			if ( variation.weight ) {
				$weight.wc_set_content( variation.weight );
			} else {
				$weight.wc_reset_content();
			}

			// Display dimensions
			if ( variation.dimensions ) {
				$dimensions.wc_set_content( variation.dimensions );
			} else {
				$dimensions.wc_reset_content();
			}

			// Show images
			$form.wc_variations_image_update( variation );

			// Output correct template
			if ( ! variation.variation_is_visible ) {
				$single_variation.html( unavailable_template );
				$variations_button.html( button_template( {
					variation_id: 0,
					min_qty:      '',
					max_qty:      ''
				} ) );
			} else {
				$single_variation.html( template( {
					price:        variation.price_html,
					availability: variation.availability_html,
					description:  variation.variation_description
				} ) );
				$variations_button.html( button_template( {
					variation_id: variation.variation_id,
					min_qty:      variation.min_qty,
					max_qty:      variation.max_qty
				} ) );
			}

			// Hide or show qty input
			if ( variation.is_sold_individually === 'yes' ) {
				$single_variation_wrap.find( '.quantity input.qty' ).val( '1' );
				$single_variation_wrap.find( '.quantity' ).hide();
			} else {
				$single_variation_wrap.find( '.quantity' ).show();
			}

			// Enable or disable the add to cart button
			if ( ! variation.is_purchasable || ! variation.is_in_stock || ! variation.variation_is_visible ) {
				purchasable = false;
			}

			// Refresh variation description
			//$form.wc_variations_description_update( variation.variation_description );

			// Reveal
			$single_variation.slideDown( 200 ).trigger( 'show_variation', [ variation, purchasable ] );
		})

		// Check variations
		.on( 'check_variations', function( event, exclude, focus ) {
			if ( $use_ajax ) {
				return;
			}

			var all_attributes_chosen = true,
				some_attributes_chosen = false,
				current_settings = {},
				$form = $( this ),
				$reset_variations = $form.find( '.reset_variations' );

			$form.find( '.variations select' ).each( function() {
				var attribute_name = $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );

				if ( $( this ).val().length === 0 ) {
					all_attributes_chosen = false;
				} else {
					some_attributes_chosen = true;
				}

				if ( exclude && attribute_name === exclude ) {
					all_attributes_chosen = false;
					current_settings[ attribute_name ] = '';
				} else {
					// Add to settings array
					current_settings[ attribute_name ] = $( this ).val();
				}
			});

			var matching_variations = wc_variation_form_matcher.find_matching_variations( $product_variations, current_settings );

			if ( all_attributes_chosen ) {

				var variation = matching_variations.shift();

				if ( variation ) {
					$form.find( 'input[name="variation_id"], input.variation_id' )
						.val( variation.variation_id )
						.change();
					$form.trigger( 'found_variation', [ variation ] );
				} else {
					// Nothing found - reset fields
					$form.find( '.variations select' ).val( '' );

					if ( ! focus ) {
						$form.trigger( 'reset_data' );
					}

					window.alert( wc_add_to_cart_variation_params.i18n_no_matching_variations_text );
				}

			} else {

				$form.trigger( 'update_variation_values', [ matching_variations ] );

				if ( ! focus ) {
					$form.trigger( 'reset_data' );
				}

				if ( ! exclude ) {
					$form.find( '.single_variation' ).slideUp( 200 ).trigger( 'hide_variation' );
				}
			}
			if ( some_attributes_chosen ) {
				if ( $reset_variations.css( 'visibility' ) === 'hidden' ) {
					$reset_variations.css( 'visibility', 'visible' ).hide().fadeIn();
				}
			} else {
				$reset_variations.css( 'visibility', 'hidden' );
			}
		} )

		// Disable option fields that are unavaiable for current set of attributes
		.on( 'update_variation_values', function( event, variations ) {
			if ( $use_ajax ) {
				return;
			}
			// Loop through selects and disable/enable options based on selections
			$form.find( '.variations select' ).each( function( index, el ) {

				var current_attr_name, current_attr_select = $( el );

				// Reset options
				if ( ! current_attr_select.data( 'attribute_options' ) ) {
					current_attr_select.data( 'attribute_options', current_attr_select.find( 'option:gt(0)' ).get() );
				}

				current_attr_select.find( 'option:gt(0)' ).remove();
				current_attr_select.append( current_attr_select.data( 'attribute_options' ) );
				current_attr_select.find( 'option:gt(0)' ).removeClass( 'attached' );
				current_attr_select.find( 'option:gt(0)' ).removeClass( 'enabled' );
				current_attr_select.find( 'option:gt(0)' ).removeAttr( 'disabled' );

				// Get name from data-attribute_name, or from input name if it doesn't exist
				if ( typeof( current_attr_select.data( 'attribute_name' ) ) !== 'undefined' ) {
					current_attr_name = current_attr_select.data( 'attribute_name' );
				} else {
					current_attr_name = current_attr_select.attr( 'name' );
				}

				// Loop through variations
				for ( var num in variations ) {

					if ( typeof( variations[ num ] ) !== 'undefined' ) {

						var attributes = variations[ num ].attributes;

						for ( var attr_name in attributes ) {
							if ( attributes.hasOwnProperty( attr_name ) ) {
								var attr_val = attributes[ attr_name ];

								if ( attr_name === current_attr_name ) {

									var variation_active = '';

									if ( variations[ num ].variation_is_active ) {
										variation_active = 'enabled';
									}

									if ( attr_val ) {

										// Decode entities
										attr_val = $( '<div/>' ).html( attr_val ).text();

										// Add slashes
										attr_val = attr_val.replace( /'/g, '\\\'' );
										attr_val = attr_val.replace( /"/g, '\\\"' );

										// Compare the meerkat
										current_attr_select.find( 'option[value="' + attr_val + '"]' ).addClass( 'attached ' + variation_active );

									} else {

										current_attr_select.find( 'option:gt(0)' ).addClass( 'attached ' + variation_active );

									}
								}
							}
						}
					}
				}

				// Detach unattached
				current_attr_select.find( 'option:gt(0):not(.attached)' ).remove();

				// Grey out disabled
				current_attr_select.find( 'option:gt(0):not(.enabled)' ).attr( 'disabled', 'disabled' );

			});

			// Custom event for when variations have been updated
			$form.trigger( 'woocommerce_update_variation_values' );
		});

		$form.trigger( 'wc_variation_form' );

		return $form;
	};

	/**
	 * Matches inline variation objects to chosen attributes
	 * @type {Object}
	 */
	var wc_variation_form_matcher = {
		find_matching_variations: function( product_variations, settings ) {
			var matching = [];
			for ( var i = 0; i < product_variations.length; i++ ) {
				var variation    = product_variations[i];

				if ( wc_variation_form_matcher.variations_match( variation.attributes, settings ) ) {
					matching.push( variation );
				}
			}
			return matching;
		},
		variations_match: function( attrs1, attrs2 ) {
			var match = true;
			for ( var attr_name in attrs1 ) {
				if ( attrs1.hasOwnProperty( attr_name ) ) {
					var val1 = attrs1[ attr_name ];
					var val2 = attrs2[ attr_name ];
					if ( val1 !== undefined && val2 !== undefined && val1.length !== 0 && val2.length !== 0 && val1 !== val2 ) {
						match = false;
					}
				}
			}
			return match;
		}
	};

	/**
	 * Stores the default text for an element so it can be reset later
	 */
	$.fn.wc_set_content = function( content ) {
		if ( undefined === this.attr( 'data-o_content' ) ) {
			this.attr( 'data-o_content', this.text() );
		}
		this.text( content );
	};

	/**
	 * Stores the default text for an element so it can be reset later
	 */
	$.fn.wc_reset_content = function() {
		if ( undefined !== this.attr( 'data-o_content' ) ) {
			this.text( this.attr( 'data-o_content' ) );
		}
	};

	/**
	 * Stores a default attribute for an element so it can be reset later
	 */
	$.fn.wc_set_variation_attr = function( attr, value ) {
		if ( undefined === this.attr( 'data-o_' + attr ) ) {
			this.attr( 'data-o_' + attr, ( ! this.attr( attr ) ) ? '' : this.attr( attr ) );
		}
		this.attr( attr, value );
	};

	/**
	 * Reset a default attribute for an element so it can be reset later
	 */
	$.fn.wc_reset_variation_attr = function( attr ) {
		if ( undefined !== this.attr( 'data-o_' + attr ) ) {
			this.attr( attr, this.attr( 'data-o_' + attr ) );
		}
	};

	/**
	 * Sets product images for the chosen variation
	 */
	$.fn.wc_variations_image_update = function( variation ) {
		var $form             = this,
			$product          = $form.closest('.product'),
			$product_img      = $product.find( 'div.images img:eq(0)' ),
			$product_link     = $product.find( 'div.images a.zoom:eq(0)' );

		if ( variation.image_src && variation.image_src.length > 1 ) {
			$product_img.wc_set_variation_attr( 'src', variation.image_src );
			$product_img.wc_set_variation_attr( 'title', variation.image_title );
			$product_img.wc_set_variation_attr( 'alt', variation.image_title );
			$product_link.wc_set_variation_attr( 'href', variation.image_link );
			$product_link.wc_set_variation_attr( 'title', variation.image_caption );
		} else {
			$product_img.wc_reset_variation_attr( 'src' );
			$product_img.wc_reset_variation_attr( 'title' );
			$product_img.wc_reset_variation_attr( 'alt' );
			$product_link.wc_reset_variation_attr( 'href' );
			$product_link.wc_reset_variation_attr( 'title' );
		}
	};

	/**
	 * Performs animated variation description refreshes
	 */
	$.fn.wc_variations_description_update = function( variation_description ) {
		var $form                   = this;
		var $variations_description = $form.find( '.woocommerce-variation-description' );

		if ( $variations_description.length === 0 ) {
			if ( variation_description ) {
				// add transparent border to allow correct height measurement when children have top/bottom margins
				$form.find( '.single_variation_wrap' ).prepend( $( '<div class="woocommerce-variation-description" style="border:1px solid transparent;">' + variation_description + '</div>' ).hide() );
				$form.find( '.woocommerce-variation-description' ).slideDown( 200 );
			}
		} else {
			var load_height    = $variations_description.outerHeight( true );
			var new_height     = 0;
			var animate_height = false;

			// lock height
			$variations_description.css( 'height', load_height );
			// replace html
			$variations_description.html( variation_description );
			// measure height
			$variations_description.css( 'height', 'auto' );

			new_height = $variations_description.outerHeight( true );

			if ( Math.abs( new_height - load_height ) > 1 ) {
				animate_height = true;
				// lock height
				$variations_description.css( 'height', load_height );
			}

			// animate height
			if ( animate_height ) {
				$variations_description.animate( { 'height' : new_height }, { duration: 200, queue: false, always: function() {
					$variations_description.css( { 'height' : 'auto' } );
				} } );
			}
		}
	};

	$( function() {
		if ( typeof wc_add_to_cart_variation_params !== 'undefined' ) {
			$( '.variations_form' ).each( function() {
				$( this ).wc_variation_form().find('.variations select:eq(0)').change();
			});
		}
	});

})( jQuery, window, document );
