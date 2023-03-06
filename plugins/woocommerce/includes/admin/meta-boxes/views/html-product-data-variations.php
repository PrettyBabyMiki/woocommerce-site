<?php
/**
 * Product data variations
 *
 * @package WooCommerce\Admin\Metaboxes\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="variable_product_options" class="panel wc-metaboxes-wrapper hidden">
	<div id="variable_product_options_inner">

		<?php if ( ! count( $variation_attributes ) ) : ?>

		<div id="message" class="inline notice woocommerce-message">
			<p>
				<?php echo esc_html_e( 'Offer customers multiple product options, like size and color. Start by creating a new custom attribute and enter available values (they’ll be shown as selectable product options).', 'woocommerce' ); ?> <a target="_blank" href="https://woocommerce.com/document/variable-product/#add-variations"><?php esc_html_e( 'Learn more about creating variations', 'woocommerce' ); ?></a>
			</p>
		</div>
		<div class="wc-metabox">
			<div class="woocommerce_variation_new_attribute_data wc-metabox-content">
			<?php
				$i                    = 0;
				$is_variations_screen = true;
				$attribute            = new WC_Product_Attribute();
				$attribute->set_variation( true );
				require __DIR__ . '/html-product-attribute-inner.php';
			?>
				<div class="toolbar">
					<button type="button" class="button button-primary save-variations"><?php esc_html_e( 'Save variations', 'woocommerce' ); ?></button>
				</div>
			</div>
		</div>
		<?php else : ?>

			<div class="toolbar toolbar-variations-defaults">
				<div class="variations-defaults">
					<strong><?php esc_html_e( 'Default Form Values', 'woocommerce' ); ?>: <?php echo wc_help_tip( __( 'Choose a default form value if you want a certain variation already selected when a user visits the product page.', 'woocommerce' ) ); ?></strong>
					<?php
					foreach ( $variation_attributes as $attribute ) {
						$selected_value = isset( $default_attributes[ sanitize_title( $attribute->get_name() ) ] ) ? $default_attributes[ sanitize_title( $attribute->get_name() ) ] : '';
						?>
						<select name="default_attribute_<?php echo esc_attr( sanitize_title( $attribute->get_name() ) ); ?>" data-current="<?php echo esc_attr( $selected_value ); ?>">
							<?php /* translators: WooCommerce attribute label */ ?>
							<option value=""><?php echo esc_html( sprintf( __( 'No default %s&hellip;', 'woocommerce' ), wc_attribute_label( $attribute->get_name() ) ) ); ?></option>
							<?php if ( $attribute->is_taxonomy() ) : ?>
								<?php foreach ( $attribute->get_terms() as $option ) : ?>
									<?php /* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */ ?>
									<option <?php selected( $selected_value, $option->slug ); ?> value="<?php echo esc_attr( $option->slug ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $option->name, $option, $attribute->get_name(), $product_object ) ); ?></option>
									<?php /* phpcs:enable */ ?>
								<?php endforeach; ?>
							<?php else : ?>
								<?php foreach ( $attribute->get_options() as $option ) : ?>
									<?php /* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */ ?>
									<option <?php selected( $selected_value, $option ); ?> value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute->get_name(), $product_object ) ); ?></option>
									<?php /* phpcs:enable */ ?>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<?php
					}
					?>
				</div>
				<div class="clear"></div>
			</div>

			<?php /* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */ ?>
			<?php do_action( 'woocommerce_variable_product_before_variations' ); ?>
			<?php /* phpcs:enable */ ?>

			<div class="toolbar toolbar-top">
				<select id="field_to_edit" class="variation_actions">
					<option data-global="true" value="add_variation"><?php esc_html_e( 'Add variation', 'woocommerce' ); ?></option>
					<option data-global="true" value="link_all_variations"><?php esc_html_e( 'Create variations from all attributes', 'woocommerce' ); ?></option>
					<option value="delete_all"><?php esc_html_e( 'Delete all variations', 'woocommerce' ); ?></option>
					<optgroup label="<?php esc_attr_e( 'Status', 'woocommerce' ); ?>">
						<option value="toggle_enabled"><?php esc_html_e( 'Toggle &quot;Enabled&quot;', 'woocommerce' ); ?></option>
						<option value="toggle_downloadable"><?php esc_html_e( 'Toggle &quot;Downloadable&quot;', 'woocommerce' ); ?></option>
						<option value="toggle_virtual"><?php esc_html_e( 'Toggle &quot;Virtual&quot;', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Pricing', 'woocommerce' ); ?>">
						<option value="variable_regular_price"><?php esc_html_e( 'Set regular prices', 'woocommerce' ); ?></option>
						<option value="variable_regular_price_increase"><?php esc_html_e( 'Increase regular prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
						<option value="variable_regular_price_decrease"><?php esc_html_e( 'Decrease regular prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
						<option value="variable_sale_price"><?php esc_html_e( 'Set sale prices', 'woocommerce' ); ?></option>
						<option value="variable_sale_price_increase"><?php esc_html_e( 'Increase sale prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
						<option value="variable_sale_price_decrease"><?php esc_html_e( 'Decrease sale prices (fixed amount or percentage)', 'woocommerce' ); ?></option>
						<option value="variable_sale_schedule"><?php esc_html_e( 'Set scheduled sale dates', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Inventory', 'woocommerce' ); ?>">
						<option value="toggle_manage_stock"><?php esc_html_e( 'Toggle &quot;Manage stock&quot;', 'woocommerce' ); ?></option>
						<option value="variable_stock"><?php esc_html_e( 'Stock', 'woocommerce' ); ?></option>
						<option value="variable_stock_status_instock"><?php esc_html_e( 'Set Status - In stock', 'woocommerce' ); ?></option>
						<option value="variable_stock_status_outofstock"><?php esc_html_e( 'Set Status - Out of stock', 'woocommerce' ); ?></option>
						<option value="variable_stock_status_onbackorder"><?php esc_html_e( 'Set Status - On backorder', 'woocommerce' ); ?></option>
						<option value="variable_low_stock_amount"><?php esc_html_e( 'Low stock threshold', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Shipping', 'woocommerce' ); ?>">
						<option value="variable_length"><?php esc_html_e( 'Length', 'woocommerce' ); ?></option>
						<option value="variable_width"><?php esc_html_e( 'Width', 'woocommerce' ); ?></option>
						<option value="variable_height"><?php esc_html_e( 'Height', 'woocommerce' ); ?></option>
						<option value="variable_weight"><?php esc_html_e( 'Weight', 'woocommerce' ); ?></option>
					</optgroup>
					<optgroup label="<?php esc_attr_e( 'Downloadable products', 'woocommerce' ); ?>">
						<option value="variable_download_limit"><?php esc_html_e( 'Download limit', 'woocommerce' ); ?></option>
						<option value="variable_download_expiry"><?php esc_html_e( 'Download expiry', 'woocommerce' ); ?></option>
					</optgroup>
					<?php /* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */ ?>
					<?php do_action( 'woocommerce_variable_product_bulk_edit_actions' ); ?>
					<?php /* phpcs:enable */ ?>
				</select>
				<a class="button bulk_edit do_variation_action"><?php esc_html_e( 'Go', 'woocommerce' ); ?></a>

				<div class="variations-pagenav">
					<?php /* translators: variations count */ ?>
					<span class="displaying-num"><?php echo esc_html( sprintf( _n( '%s item', '%s items', $variations_count, 'woocommerce' ), $variations_count ) ); ?></span>
					<span class="expand-close">
						(<a href="#" class="expand_all"><?php esc_html_e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php esc_html_e( 'Close', 'woocommerce' ); ?></a>)
					</span>
					<span class="pagination-links">
						<a class="first-page disabled" title="<?php esc_attr_e( 'Go to the first page', 'woocommerce' ); ?>" href="#">&laquo;</a>
						<a class="prev-page disabled" title="<?php esc_attr_e( 'Go to the previous page', 'woocommerce' ); ?>" href="#">&lsaquo;</a>
						<span class="paging-select">
							<label for="current-page-selector-1" class="screen-reader-text"><?php esc_html_e( 'Select Page', 'woocommerce' ); ?></label>
							<select class="page-selector" id="current-page-selector-1" title="<?php esc_attr_e( 'Current page', 'woocommerce' ); ?>">
								<?php for ( $i = 1; $i <= $variations_total_pages; $i++ ) : ?>
									<?php /* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */ ?>
									<option value="<?php echo $i; // WPCS: XSS ok. ?>"><?php echo $i; // WPCS: XSS ok. ?></option>
									<?php /* phpcs:enable */ ?>
								<?php endfor; ?>
							</select>
							<?php echo esc_html_x( 'of', 'number of pages', 'woocommerce' ); ?> <span class="total-pages"><?php echo esc_html( $variations_total_pages ); ?></span>
						</span>
						<a class="next-page" title="<?php esc_attr_e( 'Go to the next page', 'woocommerce' ); ?>" href="#">&rsaquo;</a>
						<a class="last-page" title="<?php esc_attr_e( 'Go to the last page', 'woocommerce' ); ?>" href="#">&raquo;</a>
					</span>
				</div>
				<div class="clear"></div>
			</div>

			<?php /* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */ ?>
			<div class="woocommerce_variations wc-metaboxes" data-attributes="<?php echo wc_esc_json( wp_json_encode( wc_list_pluck( $variation_attributes, 'get_data' ) ) ); // WPCS: XSS ok. ?>" data-total="<?php echo esc_attr( $variations_count ); ?>" data-total_pages="<?php echo esc_attr( $variations_total_pages ); ?>" data-page="1" data-edited="false"></div>
			<?php /* phpcs:enable */ ?>

			<div class="toolbar">
				<button type="button" class="button-primary save-variation-changes" disabled="disabled"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
				<button type="button" class="button cancel-variation-changes" disabled="disabled"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></button>

				<div class="variations-pagenav">
					<?php /* translators: variations count*/ ?>
					<span class="displaying-num"><?php echo esc_html( sprintf( _n( '%s item', '%s items', $variations_count, 'woocommerce' ), $variations_count ) ); ?></span>
					<span class="expand-close">
						(<a href="#" class="expand_all"><?php esc_html_e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php esc_html_e( 'Close', 'woocommerce' ); ?></a>)
					</span>
					<span class="pagination-links">
						<a class="first-page disabled" title="<?php esc_attr_e( 'Go to the first page', 'woocommerce' ); ?>" href="#">&laquo;</a>
						<a class="prev-page disabled" title="<?php esc_attr_e( 'Go to the previous page', 'woocommerce' ); ?>" href="#">&lsaquo;</a>
						<span class="paging-select">
							<label for="current-page-selector-1" class="screen-reader-text"><?php esc_html_e( 'Select Page', 'woocommerce' ); ?></label>
							<select class="page-selector" id="current-page-selector-1" title="<?php esc_attr_e( 'Current page', 'woocommerce' ); ?>">
								<?php for ( $i = 1; $i <= $variations_total_pages; $i++ ) : ?>
									<?php /* phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment */ ?>
									<option value="<?php echo $i; // WPCS: XSS ok. ?>"><?php echo $i; // WPCS: XSS ok. ?></option>
									<?php /* phpcs:enable */ ?>
								<?php endfor; ?>
							</select>
							<?php echo esc_html_x( 'of', 'number of pages', 'woocommerce' ); ?> <span class="total-pages"><?php echo esc_html( $variations_total_pages ); ?></span>
						</span>
						<a class="next-page" title="<?php esc_attr_e( 'Go to the next page', 'woocommerce' ); ?>" href="#">&rsaquo;</a>
						<a class="last-page" title="<?php esc_attr_e( 'Go to the last page', 'woocommerce' ); ?>" href="#">&raquo;</a>
					</span>
				</div>
				<div class="clear"></div>
			</div>

		<?php endif; ?>
	</div>
</div>
<script type="text/template" id="tmpl-wc-modal-set-price-variations">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<div class="components-modal__content woocommerce-set-price-variations" role="document">
				<div class="components-modal__header">
					<h2><?php echo esc_attr( $modal_title ); ?></h2>
				</div>
				<div class="woocommerce-usage-modal__wrapper">
					<div class="woocommerce-usage-modal__message">
						<span><?php esc_html_e( 'Add price to all variations that don\'t have a price', 'woocommerce' ); ?> (<?php echo esc_attr( get_woocommerce_currency_symbol() ); ?> <?php echo esc_textarea( get_woocommerce_currency() ); ?>)</span>
						<input type="text" class="components-text-control__input wc_input_variations_price"/>
					</div>
					<div class="woocommerce-usage-modal__actions">
						<button class="modal-close components-button is-secondary"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></button>
						<button class="modal-close button components-button add_variations_price_button button-primary" disabled><?php esc_html_e( 'Add prices', 'woocommerce' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
