<?php
/**
 * Admin View: Product Export
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'wc-product-export' );
?>
<div class="wrap woocommerce">
	<h1><?php esc_html_e( 'Import / Export Data', 'woocommerce' ); ?></h1>

	<form class="woocommerce-exporter">
		<span class="spinner is-active"></span>
		<h2><?php esc_html_e( 'Export products', 'woocommerce' ); ?></h2>
		<p><?php esc_html_e( 'Generate and download a CSV file containing a list of all products.', 'woocommerce' ); ?></p>

		<table class="form-table woocommerce-exporter-options">
			<tbody>
				<tr>
					<th scope="row">
						<label for="woocommerce-exporter-types"><?php esc_html_e( 'Which product types should be exported?', 'woocommerce' ); ?></label>
					</th>
					<td>
						<select id="woocommerce-exporter-types" class="woocommerce-exporter-types wc-enhanced-select" style="width:100%;" multiple data-placeholder="<?php esc_attr_e( 'Export all', 'woocommerce' ); ?>">
							<?php
								foreach ( wc_get_product_types() as $value => $label ) {
									echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
								}
							?>
							<option value="variation"><?php esc_html_e( 'Product variations', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="woocommerce-exporter-columns"><?php esc_html_e( 'What product data should be exported?', 'woocommerce' ); ?></label>
					</th>
					<td>
						<select id="woocommerce-exporter-columns" class="woocommerce-exporter-columns wc-enhanced-select" style="width:100%;" multiple data-placeholder="<?php esc_attr_e( 'Export all data', 'woocommerce' ); ?>">
							<?php
								foreach ( $exporter->get_default_column_names() as $column_id => $column_name ) {
									echo '<option value="' . esc_attr( $column_id ) . '">' . esc_html( $column_name ) . '</option>';
								}
							?>
							<option value="downloads"><?php esc_html_e( 'Downloads', 'woocommerce' ); ?></option>
							<option value="attributes"><?php esc_html_e( 'Attributes', 'woocommerce' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="woocommerce-exporter-meta"><?php esc_html_e( 'Export custom meta data?', 'woocommerce' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="woocommerce-exporter-meta" value="1" />
						<label for="woocommerce-exporter-meta"><?php esc_html_e( 'Yes, export all meta data', 'woocommerce' ); ?></label>
					</td>
				</tr>
			</tbody>
		</table>
		<div class="form-row form-row-submit">
			<input type="submit" class="woocommerce-exporter-button button button-primary" value="<?php esc_attr_e( 'Generate CSV', 'woocommerce' ); ?>" />
		</div>
		<div>
			<progress class="woocommerce-exporter-progress" max="100" value="0"></progress>
		</div>
	</form>
</div>
