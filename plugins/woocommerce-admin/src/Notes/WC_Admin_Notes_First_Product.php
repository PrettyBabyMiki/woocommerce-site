<?php
/**
 * WooCommerce Admin: Do you need help with adding your first product?
 *
 * Adds a note to ask the client if they need help adding their first product.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_First_Product.
 */
class WC_Admin_Notes_First_Product {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-first-product';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		// We want to show the note after seven days.
		if ( ! self::wc_admin_active_for( 7 * DAY_IN_SECONDS ) ) {
			return;
		}

		$onboarding_profile = get_option( 'woocommerce_onboarding_profile', array() );

		// Confirm that $onboarding_profile is set.
		if ( empty( $onboarding_profile ) ) {
			return;
		}

		// Make sure that the person who filled out the OBW was not setting up
		// the store for their customer/client.
		if (
			! isset( $onboarding_profile['setup_client'] ) ||
			$onboarding_profile['setup_client']
		) {
			return;
		}

		// Don't show if there are products.
		$query    = new \WC_Product_Query(
			array(
				'limit'    => 1,
				'paginate' => true,
				'return'   => 'ids',
				'status'   => array( 'publish' ),
			)
		);
		$products = $query->get_products();
		$count    = $products->total;
		if ( 0 !== $count ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Do you need help with adding your first product?', 'woocommerce-admin' ) );
		$note->set_content( __( 'This video tutorial will help you go through the process of adding your first product in WooCommerce.', 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'first-product-watch-tutorial',
			__( 'Watch tutorial', 'woocommerce-admin' ),
			'https://www.youtube.com/watch?v=sFtXa00Jf_o&list=PLHdG8zvZd0E575Ia8Mu3w1h750YLXNfsC&index=24'
		);

		return $note;
	}
}
