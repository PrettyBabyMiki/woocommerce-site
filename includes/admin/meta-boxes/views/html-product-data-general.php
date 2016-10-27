<div id="general_product_data" class="panel woocommerce_options_panel">

	<div class="options_group show_if_external">
		<?php
			woocommerce_wp_text_input( array(
				'id'          => '_product_url',
				'value'       => is_callable( array( $product_object, 'get_product_url' ) ) ? $product_object->get_product_url() : '',
				'label'       => __( 'Product URL', 'woocommerce' ),
				'placeholder' => 'http://',
				'description' => __( 'Enter the external URL to the product.', 'woocommerce' ),
			) );

			woocommerce_wp_text_input( array(
				'id'          => '_button_text',
				'value'       => is_callable( array( $product_object, 'get_button_text' ) ) ? $product_object->get_button_text() : '',
				'label'       => __( 'Button text', 'woocommerce' ),
				'placeholder' => _x( 'Buy product', 'placeholder', 'woocommerce' ),
				'description' => __( 'This text will be shown on the button linking to the external product.', 'woocommerce' ),
			) );
		?>
	</div>

	<div class="options_group pricing show_if_simple show_if_external hidden">
		<?php
			woocommerce_wp_text_input( array(
				'id'        => '_regular_price',
				'value'     => $product_object->get_regular_price(),
				'label'     => __( 'Regular price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'data_type' => 'price',
			) );

			woocommerce_wp_text_input( array(
				'id'          => '_sale_price',
				'value'       => $product_object->get_sale_price(),
				'data_type'   => 'price',
				'label'       => __( 'Sale price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'description' => '<a href="#" class="sale_schedule">' . __( 'Schedule', 'woocommerce' ) . '</a>',
			) );

			$sale_price_dates_from = ( $date = $product_object->get_date_on_sale_from() ) ? date_i18n( 'Y-m-d', $date ) : '';
			$sale_price_dates_to   = ( $date = $product_object->get_date_on_sale_to() ) ? date_i18n( 'Y-m-d', $date ) : '';

			echo '<p class="form-field sale_price_dates_fields">
					<label for="_sale_price_dates_from">' . __( 'Sale price dates', 'woocommerce' ) . '</label>
					<input type="text" class="short" name="_sale_price_dates_from" id="_sale_price_dates_from" value="' . esc_attr( $sale_price_dates_from ) . '" placeholder="' . _x( 'From&hellip;', 'placeholder', 'woocommerce' ) . ' YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
					<input type="text" class="short" name="_sale_price_dates_to" id="_sale_price_dates_to" value="' . esc_attr( $sale_price_dates_to ) . '" placeholder="' . _x( 'To&hellip;', 'placeholder', 'woocommerce' ) . '  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
					<a href="#" class="cancel_sale_schedule">' . __( 'Cancel', 'woocommerce' ) . '</a>' . wc_help_tip( __( 'The sale will end at the beginning of the set date.', 'woocommerce' ) ) . '
				</p>';

			do_action( 'woocommerce_product_options_pricing' );
		?>
	</div>

	<div class="options_group show_if_downloadable hidden">
		<div class="form-field downloadable_files">
			<label><?php _e( 'Downloadable files', 'woocommerce' ); ?></label>
			<table class="widefat">
				<thead>
					<tr>
						<th class="sort">&nbsp;</th>
						<th><?php _e( 'Name', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the name of the download shown to the customer.', 'woocommerce' ) ); ?></th>
						<th colspan="2"><?php _e( 'File URL', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'This is the URL or absolute path to the file which customers will get access to. URLs entered here should already be encoded.', 'woocommerce' ) ); ?></th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( $downloadable_files = $product_object->get_downloads() ) {
						foreach ( $downloadable_files as $key => $file ) {
							include( 'html-product-download.php' );
						}
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="5">
							<a href="#" class="button insert" data-row="<?php
								$file = array(
									'file' => '',
									'name' => '',
								);
								ob_start();
								include( 'html-product-download.php' );
								echo esc_attr( ob_get_clean() );
							?>"><?php _e( 'Add File', 'woocommerce' ); ?></a>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php
			woocommerce_wp_text_input( array(
				'id'                => '_download_limit', // @todo
				'label'             => __( 'Download limit', 'woocommerce' ),
				'placeholder'       => __( 'Unlimited', 'woocommerce' ),
				'description'       => __( 'Leave blank for unlimited re-downloads.', 'woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' 	=> '1',
					'min'	=> '0',
				),
			) );

			woocommerce_wp_text_input( array(
				'id'                => '_download_expiry', // @todo
				'label'             => __( 'Download expiry', 'woocommerce' ),
				'placeholder'       => __( 'Never', 'woocommerce' ),
				'description'       => __( 'Enter the number of days before a download link expires, or leave blank.', 'woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' 	=> '1',
					'min'	=> '0',
				),
			) );

			woocommerce_wp_select( array(
				'id'          => '_download_type', // @todo
				'label'       => __( 'Download type', 'woocommerce' ),
				'description' => sprintf( __( 'Choose a download type - this controls the <a href="%s">schema</a>.', 'woocommerce' ), 'http://schema.org/' ),
				'options'     => array(
					''            => __( 'Standard Product', 'woocommerce' ),
					'application' => __( 'Application/Software', 'woocommerce' ),
					'music'       => __( 'Music', 'woocommerce' ),
				),
			) );

			do_action( 'woocommerce_product_options_downloads' );
		?>
	</div>

	<?php if ( wc_tax_enabled() ) : ?>
		<div class="options_group show_if_simple show_if_external show_if_variable">
			<?php
				woocommerce_wp_select( array(
					'id'             => '_tax_status',
					'value'          => $product_object->get_tax_status(),
					'label'          => __( 'Tax status', 'woocommerce' ),
					'options'        => array(
						'taxable' 	 => __( 'Taxable', 'woocommerce' ),
						'shipping' 	 => __( 'Shipping only', 'woocommerce' ),
						'none' 		 => _x( 'None', 'Tax status', 'woocommerce' ),
					),
					'desc_tip'       => 'true',
					'description'    => __( 'Define whether or not the entire product is taxable, or just the cost of shipping it.', 'woocommerce' ),
				) );

				$tax_classes         = WC_Tax::get_tax_classes();
				$classes_options     = array();
				$classes_options[''] = __( 'Standard', 'woocommerce' );

				if ( ! empty( $tax_classes ) ) {
					foreach ( $tax_classes as $class ) {
						$classes_options[ sanitize_title( $class ) ] = esc_html( $class );
					}
				}

				woocommerce_wp_select( array(
					'id'          => '_tax_class',
					'value'       => $product_object->get_tax_class(),
					'label'       => __( 'Tax class', 'woocommerce' ),
					'options'     => $classes_options,
					'desc_tip'    => 'true',
					'description' => __( 'Choose a tax class for this product. Tax classes are used to apply different tax rates specific to certain types of product.', 'woocommerce' ),
				) );

				do_action( 'woocommerce_product_options_tax' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_product_options_general_product_data' ); ?>
</div>
