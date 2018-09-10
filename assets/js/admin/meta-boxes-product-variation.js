/* global wp, woocommerce_admin_meta_boxes_variations, woocommerce_admin, accounting */
jQuery( function( $ ) {
    'use strict';

	/**
	 * Variations actions
	 */
	var wc_meta_boxes_product_variations_actions = {

		/**
		 * Initialize variations actions
		 */
		init: function() {
			$( '#variable_product_options' )
				.on( 'change', 'input.variable_is_downloadable', this.variable_is_downloadable )
				.on( 'change', 'input.variable_is_virtual', this.variable_is_virtual )
				.on( 'change', 'input.variable_manage_stock', this.variable_manage_stock )
				.on( 'click', 'button.notice-dismiss', this.notice_dismiss )
				.on( 'click', 'h3 .sort', this.set_menu_order )
				.on( 'reload', this.reload );

			$( 'input.variable_is_downloadable, input.variable_is_virtual, input.variable_manage_stock' ).change();
			$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', this.variations_loaded );
			$( document.body ).on( 'woocommerce_variations_added', this.variation_added );
		},

		/**
		 * Reload UI
		 *
		 * @param {Object} event
		 * @param {Int} qty
		 */
		reload: function() {
			wc_meta_boxes_product_variations_ajax.load_variations( 1 );
			wc_meta_boxes_product_variations_pagenav.set_paginav( 0 );
		},

		/**
		 * Check if variation is downloadable and show/hide elements
		 */
		variable_is_downloadable: function() {
			$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_downloadable' ).hide();

			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_downloadable' ).show();
			}
		},

		/**
		 * Check if variation is virtual and show/hide elements
		 */
		variable_is_virtual: function() {
			$( this ).closest( '.woocommerce_variation' ).find( '.hide_if_variation_virtual' ).show();

			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( '.woocommerce_variation' ).find( '.hide_if_variation_virtual' ).hide();
			}
		},

		/**
		 * Check if variation manage stock and show/hide elements
		 */
		variable_manage_stock: function() {
			$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_manage_stock' ).hide();
			$( this ).closest( '.woocommerce_variation' ).find( '.hide_if_variation_manage_stock' ).show();

			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_manage_stock' ).show();
				$( this ).closest( '.woocommerce_variation' ).find( '.hide_if_variation_manage_stock' ).hide();
			}
		},

		/**
		 * Notice dismiss
		 */
		notice_dismiss: function() {
			$( this ).closest( 'div.notice' ).remove();
		},

		/**
		 * Run actions when variations is loaded
		 *
		 * @param {Object} event
		 * @param {Int} needsUpdate
		 */
		variations_loaded: function( event, needsUpdate ) {
			needsUpdate = needsUpdate || false;

			var wrapper = $( '#woocommerce-product-data' );

			if ( ! needsUpdate ) {
				// Show/hide downloadable, virtual and stock fields
				$( 'input.variable_is_downloadable, input.variable_is_virtual, input.variable_manage_stock', wrapper ).change();

				// Open sale schedule fields when have some sale price date
				$( '.woocommerce_variation', wrapper ).each( function( index, el ) {
					var $el       = $( el ),
						date_from = $( '.sale_price_dates_from', $el ).val(),
						date_to   = $( '.sale_price_dates_to', $el ).val();

					if ( '' !== date_from || '' !== date_to ) {
						$( 'a.sale_schedule', $el ).click();
					}
				});

				// Remove variation-needs-update classes
				$( '.woocommerce_variations .variation-needs-update', wrapper ).removeClass( 'variation-needs-update' );

				// Disable cancel and save buttons
				$( 'button.cancel-variation-changes, button.save-variation-changes', wrapper ).attr( 'disabled', 'disabled' );
			}

			// Init TipTip
			$( '#tiptip_holder' ).removeAttr( 'style' );
			$( '#tiptip_arrow' ).removeAttr( 'style' );
			$( '.woocommerce_variations .tips, .woocommerce_variations .help_tip, .woocommerce_variations .woocommerce-help-tip', wrapper ).tipTip({
				'attribute': 'data-tip',
				'fadeIn':    50,
				'fadeOut':   50,
				'delay':     200
			});

			// Datepicker fields
			$( '.sale_price_dates_fields', wrapper ).find( 'input' ).datepicker({
				defaultDate:     '',
				dateFormat:      'yy-mm-dd',
				numberOfMonths:  1,
				showButtonPanel: true,
				onSelect:        function() {
					var option = $( this ).is( '.sale_price_dates_from' ) ? 'minDate' : 'maxDate',
						dates  = $( this ).closest( '.sale_price_dates_fields' ).find( 'input' ),
						date   = $( this ).datepicker( 'getDate' );

					dates.not( this ).datepicker( 'option', option, date );
					$( this ).change();
				}
			});

			// Allow sorting
			$( '.woocommerce_variations', wrapper ).sortable({
				items:                '.woocommerce_variation',
				cursor:               'move',
				axis:                 'y',
				handle:               '.sort',
				scrollSensitivity:    40,
				forcePlaceholderSize: true,
				helper:               'clone',
				opacity:              0.65,
				stop:                 function() {
				    wc_meta_boxes_product_variations_actions.variation_row_indexes();
				}
			});

			$( document.body ).trigger( 'wc-enhanced-select-init' );
		},

		/**
		 * Run actions when added a variation
		 *
		 * @param {Object} event
		 * @param {Int} qty
		 */
		variation_added: function( event, qty ) {
			if ( 1 === qty ) {
				wc_meta_boxes_product_variations_actions.variations_loaded( null, true );
			}
		},

		/**
		 * Lets the user manually input menu order to move items around pages
		 */
		set_menu_order: function( event ) {
			event.preventDefault();
			var $menu_order  = $( this ).closest( '.woocommerce_variation' ).find('.variation_menu_order');
			var value        = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_menu_order, $menu_order.val() );

			if ( value != null ) {
				// Set value, save changes and reload view
				$menu_order.val( parseInt( value, 10 ) ).change();
				wc_meta_boxes_product_variations_ajax.save_variations();
			}
		},

		/**
		 * Set menu order
		 */
		variation_row_indexes: function() {
			var wrapper      = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
				current_page = parseInt( wrapper.attr( 'data-page' ), 10 ),
				offset       = parseInt( ( current_page - 1 ) * woocommerce_admin_meta_boxes_variations.variations_per_page, 10 );

			$( '.woocommerce_variations .woocommerce_variation' ).each( function ( index, el ) {
				$( '.variation_menu_order', el ).val( parseInt( $( el ).index( '.woocommerce_variations .woocommerce_variation' ), 10 ) + 1 + offset ).change();
			});
		}
	};

	/**
	 * Variations media actions
	 */
	var wc_meta_boxes_product_variations_media = {

		/**
		 * wp.media frame object
		 *
		 * @type {Object}
		 */
		variable_image_frame: null,

		/**
		 * Variation image ID
		 *
		 * @type {Int}
		 */
		setting_variation_image_id: null,

		/**
		 * Variation image object
		 *
		 * @type {Object}
		 */
		setting_variation_image: null,

		/**
		 * wp.media post ID
		 *
		 * @type {Int}
		 */
		wp_media_post_id: wp.media.model.settings.post.id,

		/**
		 * Initialize media actions
		 */
		init: function() {
			$( '#variable_product_options' ).on( 'click', '.upload_image_button', this.add_image );
			$( 'a.add_media' ).on( 'click', this.restore_wp_media_post_id );
		},

		/**
		 * Added new image
		 *
		 * @param {Object} event
		 */
		add_image: function( event ) {
			var $button = $( this ),
				post_id = $button.attr( 'rel' ),
				$parent = $button.closest( '.upload_image' );

			wc_meta_boxes_product_variations_media.setting_variation_image    = $parent;
			wc_meta_boxes_product_variations_media.setting_variation_image_id = post_id;

			event.preventDefault();

			if ( $button.is( '.remove' ) ) {

				$( '.upload_image_id', wc_meta_boxes_product_variations_media.setting_variation_image ).val( '' ).change();
				wc_meta_boxes_product_variations_media.setting_variation_image.find( 'img' ).eq( 0 ).attr( 'src', woocommerce_admin_meta_boxes_variations.woocommerce_placeholder_img_src );
				wc_meta_boxes_product_variations_media.setting_variation_image.find( '.upload_image_button' ).removeClass( 'remove' );

			} else {

				// If the media frame already exists, reopen it.
				if ( wc_meta_boxes_product_variations_media.variable_image_frame ) {
					wc_meta_boxes_product_variations_media.variable_image_frame.uploader.uploader.param( 'post_id', wc_meta_boxes_product_variations_media.setting_variation_image_id );
					wc_meta_boxes_product_variations_media.variable_image_frame.open();
					return;
				} else {
					wp.media.model.settings.post.id = wc_meta_boxes_product_variations_media.setting_variation_image_id;
				}

				// Create the media frame.
				wc_meta_boxes_product_variations_media.variable_image_frame = wp.media.frames.variable_image = wp.media({
					// Set the title of the modal.
					title: woocommerce_admin_meta_boxes_variations.i18n_choose_image,
					button: {
						text: woocommerce_admin_meta_boxes_variations.i18n_set_image
					},
					states: [
						new wp.media.controller.Library({
							title: woocommerce_admin_meta_boxes_variations.i18n_choose_image,
							filterable: 'all'
						})
					]
				});

				// When an image is selected, run a callback.
				wc_meta_boxes_product_variations_media.variable_image_frame.on( 'select', function () {

					var attachment = wc_meta_boxes_product_variations_media.variable_image_frame.state().get( 'selection' ).first().toJSON(),
						url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

					$( '.upload_image_id', wc_meta_boxes_product_variations_media.setting_variation_image ).val( attachment.id ).change();
					wc_meta_boxes_product_variations_media.setting_variation_image.find( '.upload_image_button' ).addClass( 'remove' );
					wc_meta_boxes_product_variations_media.setting_variation_image.find( 'img' ).eq( 0 ).attr( 'src', url );

					wp.media.model.settings.post.id = wc_meta_boxes_product_variations_media.wp_media_post_id;
				});

				// Finally, open the modal.
				wc_meta_boxes_product_variations_media.variable_image_frame.open();
			}
		},

		/**
		 * Restore wp.media post ID.
		 */
		restore_wp_media_post_id: function() {
			wp.media.model.settings.post.id = wc_meta_boxes_product_variations_media.wp_media_post_id;
		}
	};

	/**
	 * Product variations metabox ajax methods
	 */
	var wc_meta_boxes_product_variations_ajax = {

		/**
		 * Initialize variations ajax methods
		 */
		init: function() {
			$( 'li.variations_tab a' ).on( 'click', this.initial_load );

			$( '#variable_product_options' )
				.on( 'click', 'button.save-variation-changes', this.save_variations )
				.on( 'click', 'button.cancel-variation-changes', this.cancel_variations )
				.on( 'click', '.remove_variation', this.remove_variation );

			$( document.body )
				.on( 'change', '#variable_product_options .woocommerce_variations :input', this.input_changed )
				.on( 'change', '.variations-defaults select', this.defaults_changed );

			$( 'form#post' ).on( 'submit', this.save_on_submit );

			$( '.wc-metaboxes-wrapper' ).on( 'click', 'a.do_variation_action', this.do_variation_action );
		},

		/**
		 * Check if have some changes before leave the page
		 *
		 * @return {Bool}
		 */
		check_for_changes: function() {
			var need_update = $( '#variable_product_options' ).find( '.woocommerce_variations .variation-needs-update' );

			if ( 0 < need_update.length ) {
				if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_edited_variations ) ) {
					wc_meta_boxes_product_variations_ajax.save_changes();
				} else {
					need_update.removeClass( 'variation-needs-update' );
					return false;
				}
			}

			return true;
		},

		/**
		 * Block edit screen
		 */
		block: function() {
			$( '#woocommerce-product-data' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},

		/**
		 * Unblock edit screen
		 */
		unblock: function() {
			$( '#woocommerce-product-data' ).unblock();
		},

		/**
		 * Initial load variations
		 *
		 * @return {Bool}
		 */
		initial_load: function() {
			if ( 0 === $( '#variable_product_options' ).find( '.woocommerce_variations .woocommerce_variation' ).length ) {
				wc_meta_boxes_product_variations_pagenav.go_to_page();
			}
		},

		/**
		 * Load variations via Ajax
		 *
		 * @param {Int} page (default: 1)
		 * @param {Int} per_page (default: 10)
		 */
		load_variations: function( page, per_page ) {
			page     = page || 1;
			per_page = per_page || woocommerce_admin_meta_boxes_variations.variations_per_page;

			var wrapper = $( '#variable_product_options' ).find( '.woocommerce_variations' );

			wc_meta_boxes_product_variations_ajax.block();

			$.ajax({
				url: woocommerce_admin_meta_boxes_variations.ajax_url,
				data: {
					action:     'woocommerce_load_variations',
					security:   woocommerce_admin_meta_boxes_variations.load_variations_nonce,
					product_id: woocommerce_admin_meta_boxes_variations.post_id,
					attributes: wrapper.data( 'attributes' ),
					page:       page,
					per_page:   per_page
				},
				type: 'POST',
				success: function( response ) {
					wrapper.empty().append( response ).attr( 'data-page', page );

					$( '#woocommerce-product-data' ).trigger( 'woocommerce_variations_loaded' );

					wc_meta_boxes_product_variations_ajax.unblock();
				}
			});
		},

		/**
		 * Ger variations fields and convert to object
		 *
		 * @param  {Object} fields
		 *
		 * @return {Object}
		 */
		get_variations_fields: function( fields ) {
			var data = $( ':input', fields ).serializeJSON();

			$( '.variations-defaults select' ).each( function( index, element ) {
				var select = $( element );
				data[ select.attr( 'name' ) ] = select.val();
			});

			return data;
		},

		/**
		 * Save variations changes
		 *
		 * @param {Function} callback Called once saving is complete
		 */
		save_changes: function( callback ) {
			var wrapper     = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
				need_update = $( '.variation-needs-update', wrapper ),
				data        = {};

			// Save only with products need update.
			if ( 0 < need_update.length ) {
				wc_meta_boxes_product_variations_ajax.block();

				data                 = wc_meta_boxes_product_variations_ajax.get_variations_fields( need_update );
				data.action          = 'woocommerce_save_variations';
				data.security        = woocommerce_admin_meta_boxes_variations.save_variations_nonce;
				data.product_id      = woocommerce_admin_meta_boxes_variations.post_id;
				data['product-type'] = $( '#product-type' ).val();

				$.ajax({
					url: woocommerce_admin_meta_boxes_variations.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						// Allow change page, delete and add new variations
						need_update.removeClass( 'variation-needs-update' );
						$( 'button.cancel-variation-changes, button.save-variation-changes' ).attr( 'disabled', 'disabled' );

						$( '#woocommerce-product-data' ).trigger( 'woocommerce_variations_saved' );

						if ( typeof callback === 'function' ) {
							callback( response );
						}

						wc_meta_boxes_product_variations_ajax.unblock();
					}
				});
			}
		},

		/**
		 * Save variations
		 *
		 * @return {Bool}
		 */
		save_variations: function() {
			$( '#variable_product_options' ).trigger( 'woocommerce_variations_save_variations_button' );

			wc_meta_boxes_product_variations_ajax.save_changes( function( error ) {
				var wrapper = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
					current = wrapper.attr( 'data-page' );

				$( '#variable_product_options' ).find( '#woocommerce_errors' ).remove();

				if ( error ) {
					wrapper.before( error );
				}

				$( '.variations-defaults select' ).each( function() {
					$( this ).attr( 'data-current', $( this ).val() );
				});

				wc_meta_boxes_product_variations_pagenav.go_to_page( current );
			});

			return false;
		},

		/**
		 * Save on post form submit
		 */
		save_on_submit: function( e ) {
			var need_update = $( '#variable_product_options' ).find( '.woocommerce_variations .variation-needs-update' );

			if ( 0 < need_update.length ) {
				e.preventDefault();
				$( '#variable_product_options' ).trigger( 'woocommerce_variations_save_variations_on_submit' );
				wc_meta_boxes_product_variations_ajax.save_changes( wc_meta_boxes_product_variations_ajax.save_on_submit_done );
			}
		},

		/**
		 * After saved, continue with form submission
		 */
		save_on_submit_done: function() {
			if ( $( '#hidden_post_status' ).val() !== 'publish' ) {
				$( 'form#post' ).append('<input type="hidden" name="save-post" value="1" />').submit();
			} else {
				$( 'form#post' ).append('<input type="hidden" name="publish" value="1" />').submit();
			}
		},

		/**
		 * Discart changes.
		 *
		 * @return {Bool}
		 */
		cancel_variations: function() {
			var current = parseInt( $( '#variable_product_options' ).find( '.woocommerce_variations' ).attr( 'data-page' ), 10 );

			$( '#variable_product_options' ).find( '.woocommerce_variations .variation-needs-update' ).removeClass( 'variation-needs-update' );
			$( '.variations-defaults select' ).each( function() {
				$( this ).val( $( this ).attr( 'data-current' ) );
			});

			wc_meta_boxes_product_variations_pagenav.go_to_page( current );

			return false;
		},

		/**
		 * Add variation
		 *
		 * @return {Bool}
		 */
		add_variation: function() {
			wc_meta_boxes_product_variations_ajax.block();

			var data = {
				action: 'woocommerce_add_variation',
				post_id: woocommerce_admin_meta_boxes_variations.post_id,
				loop: $( '.woocommerce_variation' ).length,
				security: woocommerce_admin_meta_boxes_variations.add_variation_nonce
			};

			$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function( response ) {
				var variation = $( response );
				variation.addClass( 'variation-needs-update' );

				$( '#variable_product_options' ).find( '.woocommerce_variations' ).prepend( variation );
				$( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );
				$( '#variable_product_options' ).trigger( 'woocommerce_variations_added', 1 );
				wc_meta_boxes_product_variations_ajax.unblock();
			});

			return false;
		},

		/**
		 * Remove variation
		 *
		 * @return {Bool}
		 */
		remove_variation: function() {
			wc_meta_boxes_product_variations_ajax.check_for_changes();

			if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_remove_variation ) ) {
				var variation     = $( this ).attr( 'rel' ),
					variation_ids = [],
					data          = {
						action: 'woocommerce_remove_variations'
					};

				wc_meta_boxes_product_variations_ajax.block();

				if ( 0 < variation ) {
					variation_ids.push( variation );

					data.variation_ids = variation_ids;
					data.security      = woocommerce_admin_meta_boxes_variations.delete_variations_nonce;

					$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function() {
						var wrapper      = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
							current_page = parseInt( wrapper.attr( 'data-page' ), 10 ),
							total_pages  = Math.ceil( ( parseInt( wrapper.attr( 'data-total' ), 10 ) - 1 ) / woocommerce_admin_meta_boxes_variations.variations_per_page ),
							page         = 1;

						$( '#woocommerce-product-data' ).trigger( 'woocommerce_variations_removed' );

						if ( current_page === total_pages || current_page <= total_pages ) {
							page = current_page;
						} else if ( current_page > total_pages && 0 !== total_pages ) {
							page = total_pages;
						}

						wc_meta_boxes_product_variations_pagenav.go_to_page( page, -1 );
					});

				} else {
					wc_meta_boxes_product_variations_ajax.unblock();
				}
			}

			return false;
		},

		/**
		 * Link all variations (or at least try :p)
		 *
		 * @return {Bool}
		 */
		link_all_variations: function() {
			wc_meta_boxes_product_variations_ajax.check_for_changes();

			if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_link_all_variations ) ) {
				wc_meta_boxes_product_variations_ajax.block();

				var data = {
					action: 'woocommerce_link_all_variations',
					post_id: woocommerce_admin_meta_boxes_variations.post_id,
					security: woocommerce_admin_meta_boxes_variations.link_variation_nonce
				};

				$.post( woocommerce_admin_meta_boxes_variations.ajax_url, data, function( response ) {
					var count = parseInt( response, 10 );

					if ( 1 === count ) {
						window.alert( count + ' ' + woocommerce_admin_meta_boxes_variations.i18n_variation_added );
					} else if ( 0 === count || count > 1 ) {
						window.alert( count + ' ' + woocommerce_admin_meta_boxes_variations.i18n_variations_added );
					} else {
						window.alert( woocommerce_admin_meta_boxes_variations.i18n_no_variations_added );
					}

					if ( count > 0 ) {
						wc_meta_boxes_product_variations_pagenav.go_to_page( 1, count );
						$( '#variable_product_options' ).trigger( 'woocommerce_variations_added', count );
					} else {
						wc_meta_boxes_product_variations_ajax.unblock();
					}
				});
			}

			return false;
		},

		/**
		 * Add new class when have changes in some input
		 */
		input_changed: function() {
			$( this )
				.closest( '.woocommerce_variation' )
				.addClass( 'variation-needs-update' );

			$( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );

			$( '#variable_product_options' ).trigger( 'woocommerce_variations_input_changed' );
		},

		/**
		 * Added new .variation-needs-update class when defaults is changed
		 */
		defaults_changed: function() {
			$( this )
				.closest( '#variable_product_options' )
				.find( '.woocommerce_variation:first' )
				.addClass( 'variation-needs-update' );

			$( 'button.cancel-variation-changes, button.save-variation-changes' ).removeAttr( 'disabled' );

			$( '#variable_product_options' ).trigger( 'woocommerce_variations_defaults_changed' );
		},

		/**
		 * Actions
		 */
		do_variation_action: function() {
			var do_variation_action = $( 'select.variation_actions' ).val(),
				data       = {},
				changes    = 0,
				value;

			switch ( do_variation_action ) {
				case 'add_variation' :
					wc_meta_boxes_product_variations_ajax.add_variation();
					return;
				case 'link_all_variations' :
					wc_meta_boxes_product_variations_ajax.link_all_variations();
					return;
				case 'delete_all' :
					if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_delete_all_variations ) ) {
						if ( window.confirm( woocommerce_admin_meta_boxes_variations.i18n_last_warning ) ) {
							data.allowed = true;
							changes      = parseInt( $( '#variable_product_options' ).find( '.woocommerce_variations' ).attr( 'data-total' ), 10 ) * -1;
						}
					}
					break;
				case 'variable_regular_price_increase' :
				case 'variable_regular_price_decrease' :
				case 'variable_sale_price_increase' :
				case 'variable_sale_price_decrease' :
					value = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value_fixed_or_percent );

					if ( value != null ) {
						if ( value.indexOf( '%' ) >= 0 ) {
							data.value = accounting.unformat( value.replace( /\%/, '' ), woocommerce_admin.mon_decimal_point ) + '%';
						} else {
							data.value = accounting.unformat( value, woocommerce_admin.mon_decimal_point );
						}
					} else {
						return;
					}
					break;
				case 'variable_regular_price' :
				case 'variable_sale_price' :
				case 'variable_stock' :
				case 'variable_weight' :
				case 'variable_length' :
				case 'variable_width' :
				case 'variable_height' :
				case 'variable_download_limit' :
				case 'variable_download_expiry' :
					value = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value );

					if ( value != null ) {
						data.value = value;
					} else {
						return;
					}
					break;
				case 'variable_sale_schedule' :
					data.date_from = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_scheduled_sale_start );
					data.date_to   = window.prompt( woocommerce_admin_meta_boxes_variations.i18n_scheduled_sale_end );

					if ( null === data.date_from ) {
						data.date_from = false;
					}

					if ( null === data.date_to ) {
						data.date_to = false;
					}

					if ( false === data.date_to && false === data.date_from ) {
						return;
					}
					break;
				default :
					$( 'select.variation_actions' ).trigger( do_variation_action );
					data = $( 'select.variation_actions' ).triggerHandler( do_variation_action + '_ajax_data', data );
					break;
			}

			if ( 'delete_all' === do_variation_action && data.allowed ) {
				$( '#variable_product_options' ).find( '.variation-needs-update' ).removeClass( 'variation-needs-update' );
			} else {
				wc_meta_boxes_product_variations_ajax.check_for_changes();
			}

			wc_meta_boxes_product_variations_ajax.block();

			$.ajax({
				url: woocommerce_admin_meta_boxes_variations.ajax_url,
				data: {
					action:       'woocommerce_bulk_edit_variations',
					security:     woocommerce_admin_meta_boxes_variations.bulk_edit_variations_nonce,
					product_id:   woocommerce_admin_meta_boxes_variations.post_id,
					product_type: $( '#product-type' ).val(),
					bulk_action:  do_variation_action,
					data:         data
				},
				type: 'POST',
				success: function() {
					wc_meta_boxes_product_variations_pagenav.go_to_page( 1, changes );
				}
			});
		}
	};

	/**
	 * Product variations pagenav
	 */
	var wc_meta_boxes_product_variations_pagenav = {

		/**
		 * Initialize products variations meta box
		 */
		init: function() {
			$( document.body )
				.on( 'woocommerce_variations_added', this.update_single_quantity )
				.on( 'change', '.variations-pagenav .page-selector', this.page_selector )
				.on( 'click', '.variations-pagenav .first-page', this.first_page )
				.on( 'click', '.variations-pagenav .prev-page', this.prev_page )
				.on( 'click', '.variations-pagenav .next-page', this.next_page )
				.on( 'click', '.variations-pagenav .last-page', this.last_page );
		},

		/**
		 * Set variations count
		 *
		 * @param {Int} qty
		 *
		 * @return {Int}
		 */
		update_variations_count: function( qty ) {
			var wrapper        = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
				total          = parseInt( wrapper.attr( 'data-total' ), 10 ) + qty,
				displaying_num = $( '.variations-pagenav .displaying-num' );

			// Set the new total of variations
			wrapper.attr( 'data-total', total );

			if ( 1 === total ) {
				displaying_num.text( woocommerce_admin_meta_boxes_variations.i18n_variation_count_single.replace( '%qty%', total ) );
			} else {
				displaying_num.text( woocommerce_admin_meta_boxes_variations.i18n_variation_count_plural.replace( '%qty%', total ) );
			}

			return total;
		},

		/**
		 * Update variations quantity when add a new variation
		 *
		 * @param {Object} event
		 * @param {Int} qty
		 */
		update_single_quantity: function( event, qty ) {
			if ( 1 === qty ) {
				var page_nav = $( '.variations-pagenav' );

				wc_meta_boxes_product_variations_pagenav.update_variations_count( qty );

				if ( page_nav.is( ':hidden' ) ) {
					$( 'option, optgroup', '.variation_actions' ).show();
					$( '.variation_actions' ).val( 'add_variation' );
					$( '#variable_product_options' ).find( '.toolbar' ).show();
					page_nav.show();
					$( '.pagination-links', page_nav ).hide();
				}
			}
		},

		/**
		 * Set the pagenav fields
		 *
		 * @param {Int} qty
		 */
		set_paginav: function( qty ) {
			var wrapper          = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
				new_qty          = wc_meta_boxes_product_variations_pagenav.update_variations_count( qty ),
				toolbar          = $( '#variable_product_options' ).find( '.toolbar' ),
				variation_action = $( '.variation_actions' ),
				page_nav         = $( '.variations-pagenav' ),
				displaying_links = $( '.pagination-links', page_nav ),
				total_pages      = Math.ceil( new_qty / woocommerce_admin_meta_boxes_variations.variations_per_page ),
				options          = '';

			// Set the new total of pages
			wrapper.attr( 'data-total_pages', total_pages );

			$( '.total-pages', page_nav ).text( total_pages );

			// Set the new pagenav options
			for ( var i = 1; i <= total_pages; i++ ) {
				options += '<option value="' + i + '">' + i + '</option>';
			}

			$( '.page-selector', page_nav ).empty().html( options );

			// Show/hide pagenav
			if ( 0 === new_qty ) {
				toolbar.not( '.toolbar-top, .toolbar-buttons' ).hide();
				page_nav.hide();
				$( 'option, optgroup', variation_action ).hide();
				$( '.variation_actions' ).val( 'add_variation' );
				$( 'option[data-global="true"]', variation_action ).show();

			} else {
				toolbar.show();
				page_nav.show();
				$( 'option, optgroup', variation_action ).show();
				$( '.variation_actions' ).val( 'add_variation' );

				// Show/hide links
				if ( 1 === total_pages ) {
					displaying_links.hide();
				} else {
					displaying_links.show();
				}
			}
		},

		/**
		 * Check button if enabled and if don't have changes
		 *
		 * @return {Bool}
		 */
		check_is_enabled: function( current ) {
			return ! $( current ).hasClass( 'disabled' );
		},

		/**
		 * Change "disabled" class on pagenav
		 */
		change_classes: function( selected, total ) {
			var first_page = $( '.variations-pagenav .first-page' ),
				prev_page  = $( '.variations-pagenav .prev-page' ),
				next_page  = $( '.variations-pagenav .next-page' ),
				last_page  = $( '.variations-pagenav .last-page' );

			if ( 1 === selected ) {
				first_page.addClass( 'disabled' );
				prev_page.addClass( 'disabled' );
			} else {
				first_page.removeClass( 'disabled' );
				prev_page.removeClass( 'disabled' );
			}

			if ( total === selected ) {
				next_page.addClass( 'disabled' );
				last_page.addClass( 'disabled' );
			} else {
				next_page.removeClass( 'disabled' );
				last_page.removeClass( 'disabled' );
			}
		},

		/**
		 * Set page
		 */
		set_page: function( page ) {
			$( '.variations-pagenav .page-selector' ).val( page ).first().change();
		},

		/**
		 * Navigate on variations pages
		 *
		 * @param {Int} page
		 * @param {Int} qty
		 */
		go_to_page: function( page, qty ) {
			page = page || 1;
			qty  = qty || 0;

			wc_meta_boxes_product_variations_pagenav.set_paginav( qty );
			wc_meta_boxes_product_variations_pagenav.set_page( page );
		},

		/**
		 * Paginav pagination selector
		 */
		page_selector: function() {
			var selected = parseInt( $( this ).val(), 10 ),
				wrapper  = $( '#variable_product_options' ).find( '.woocommerce_variations' );

			$( '.variations-pagenav .page-selector' ).val( selected );

			wc_meta_boxes_product_variations_ajax.check_for_changes();
			wc_meta_boxes_product_variations_pagenav.change_classes( selected, parseInt( wrapper.attr( 'data-total_pages' ), 10 ) );
			wc_meta_boxes_product_variations_ajax.load_variations( selected );
		},

		/**
		 * Go to first page
		 *
		 * @return {Bool}
		 */
		first_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				wc_meta_boxes_product_variations_pagenav.set_page( 1 );
			}

			return false;
		},

		/**
		 * Go to previous page
		 *
		 * @return {Bool}
		 */
		prev_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				var wrapper   = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
					prev_page = parseInt( wrapper.attr( 'data-page' ), 10 ) - 1,
					new_page  = ( 0 < prev_page ) ? prev_page : 1;

				wc_meta_boxes_product_variations_pagenav.set_page( new_page );
			}

			return false;
		},

		/**
		 * Go to next page
		 *
		 * @return {Bool}
		 */
		next_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				var wrapper     = $( '#variable_product_options' ).find( '.woocommerce_variations' ),
					total_pages = parseInt( wrapper.attr( 'data-total_pages' ), 10 ),
					next_page   = parseInt( wrapper.attr( 'data-page' ), 10 ) + 1,
					new_page    = ( total_pages >= next_page ) ? next_page : total_pages;

				wc_meta_boxes_product_variations_pagenav.set_page( new_page );
			}

			return false;
		},

		/**
		 * Go to last page
		 *
		 * @return {Bool}
		 */
		last_page: function() {
			if ( wc_meta_boxes_product_variations_pagenav.check_is_enabled( this ) ) {
				var last_page = $( '#variable_product_options' ).find( '.woocommerce_variations' ).attr( 'data-total_pages' );

				wc_meta_boxes_product_variations_pagenav.set_page( last_page );
			}

			return false;
		}
	};

	wc_meta_boxes_product_variations_actions.init();
	wc_meta_boxes_product_variations_media.init();
	wc_meta_boxes_product_variations_ajax.init();
	wc_meta_boxes_product_variations_pagenav.init();

});
