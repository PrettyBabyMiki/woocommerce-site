<?php
/**
 * Sorting
 */
?>
<form class="woocommerce_ordering" method="<?php if (get_option( 'permalink_structure' )=="") echo 'POST'; else echo 'GET'; ?>">
	<select name="orderby" class="orderby">
		<?php
			$catalog_orderby = apply_filters('woocommerce_catalog_orderby', array(
				'title' 	=> __('Alphabetically', 'woocommerce'),
				'date' 		=> __('Most Recent', 'woocommerce'),
				'price' 	=> __('Price', 'woocommerce')
			));

			foreach ($catalog_orderby as $id => $name) echo '<option value="'.$id.'" '.selected( $_SESSION['orderby'], $id, false ).'>'.$name.'</option>';
		?>
	</select>
</form>