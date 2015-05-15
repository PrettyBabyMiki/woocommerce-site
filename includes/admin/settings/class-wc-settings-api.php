<?php
/**
 * WooCommerce API Settings
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Settings_Rest_API' ) ) :

/**
 * WC_Settings_Rest_API
 */
class WC_Settings_Rest_API extends WC_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id    = 'api';
		$this->label = __( 'API', 'woocommerce' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_form_method_tab_' . $this->id, array( $this, 'form_method' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

		$this->notices();
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''         => __( 'Settings', 'woocommerce' ),
			'keys'     => __( 'Keys', 'woocommerce' ),
			'webhooks' => __( 'Webhooks', 'woocommerce' ),
		);

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters( 'woocommerce_settings_rest_api', array(
			array(
				'title' => __( 'General Options', 'woocommerce' ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'general_options'
			),

			array(
				'title'   => __( 'API', 'woocommerce' ),
				'desc'    => __( 'Enable the REST API', 'woocommerce' ),
				'id'      => 'woocommerce_api_enabled',
				'type'    => 'checkbox',
				'default' => 'yes',
			),

			array(
				'type' => 'sectionend',
				'id' => 'general_options'
			),
		) );

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Form method
	 *
	 * @param  string $method
	 *
	 * @return string
	 */
	public function form_method( $method ) {
		global $current_section;

		if ( 'webhooks' == $current_section ) {
			if ( isset( $_GET['edit-webhook'] ) ) {
				$webhook_id = absint( $_GET['edit-webhook'] );
				$webhook    = new WC_Webhook( $webhook_id );

				if ( 'trash' != $webhook->post_data->post_status ) {
					return 'post';
				}
			}

			return 'get';
		}

		return 'post';
	}

	/**
	 * Notices.
	 */
	private function notices() {
		if ( isset( $_GET['section'] ) && 'webhooks' == $_GET['section'] ) {
			$this->webhook_notices();
		}
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		if ( 'webhooks' == $current_section ) {
			$this->webhooks_output();
		} else {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		if ( apply_filters( 'woocommerce_rest_api_valid_to_save', ! in_array( $current_section, array( 'keys', 'webhooks' ) ) ) ) {
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );
		}
	}

	/**
	 * Webhooks output
	 */
	private function webhooks_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		if ( isset( $_GET['edit-webhook'] ) ) {
			$webhook_id = absint( $_GET['edit-webhook'] );
			$webhook    = new WC_Webhook( $webhook_id );

			if ( 'trash' != $webhook->post_data->post_status ) {
				$this->webhooks_edit_output( $webhook );
				return;
			}
		}

		$this->webhooks_table_list_output();
	}

	/**
	 * Webhooks - Notices.
	 */
	private function webhook_notices() {
		if ( isset( $_GET['trashed'] ) ) {
			$trashed = absint( $_GET['trashed'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook moved to the Trash.', '%d webhooks moved to the Trash.', $trashed, 'woocommerce' ), $trashed ) );
		}

		if ( isset( $_GET['untrashed'] ) ) {
			$untrashed = absint( $_GET['untrashed'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook restored from the Trash.', '%d webhooks restored from the Trash.', $untrashed, 'woocommerce' ), $untrashed ) );
		}

		if ( isset( $_GET['deleted'] ) ) {
			$deleted = absint( $_GET['deleted'] );

			WC_Admin_Settings::add_message( sprintf( _n( '1 webhook permanently deleted.', '%d webhooks permanently deleted.', $deleted, 'woocommerce' ), $deleted ) );
		}

		if ( isset( $_GET['updated'] ) ) {
			WC_Admin_Settings::add_message( __( 'Webhook updated successfully.', 'woocommerce' ) );
		}

		if ( isset( $_GET['created'] ) ) {
			WC_Admin_Settings::add_message( __( 'Webhook created successfully.', 'woocommerce' ) );
		}
	}

	/**
	 * Webhooks - Table list output
	 */
	private function webhooks_table_list_output() {
		echo '<h3>' . __( 'Webhooks', 'woocommerce' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&create-webhook=1' ) ) . '" class="add-new-h2">' . __( 'Add Webhook', 'woocommerce' ) . '</a></h3>';

		$webhooks_table_list = new WC_Admin_Webhooks_Table_List();
		$webhooks_table_list->prepare_items();

		echo '<input type="hidden" name="page" value="wc-settings" />';
		echo '<input type="hidden" name="tab" value="api" />';
		echo '<input type="hidden" name="section" value="webhooks" />';

		$webhooks_table_list->views();
		$webhooks_table_list->search_box( __( 'Search Webhooks', 'woocommerce' ), 'webhook' );
		$webhooks_table_list->display();
	}

	/**
	 * Webhooks - Edit webhook output
	 *
	 * @param WC_Webhook $webhook
	 */
	private function webhooks_edit_output( $webhook ) {
		include_once( 'views/html-webhooks-edit.php' );
	}

	/**
	 * Webhooks - Logs output
	 *
	 * @param  WC_Webhook $webhook
	 */
	private function webhooks_logs_output( $webhook ) {
		$current = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;
		$args    = array(
			'post_id' => $webhook->id,
			'status'  => 'approve',
			'type'    => 'webhook_delivery',
			'number'  => 10
		);

		if ( 1 < $current ) {
			$args['offset'] = ( $current - 1 ) * 10;
		}

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		$logs = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_webhook_comments' ), 10, 1 );

		if ( $logs ) {
			include_once( 'views/html-webhook-logs.php' );
		} else {
			echo '<p>' . __( 'This Webhook has no log yet.', 'woocommerce' ) . '</p>';
		}
	}

	/**
	 * Webhooks - Get the webhook topic data
	 *
	 * @return array
	 */
	private function webhooks_get_topic_data( $webhook ) {
		$topic    = $webhook->get_topic();
		$event    = '';
		$resource = '';

		if ( $topic ) {
			list( $resource, $event ) = explode( '.', $topic );

			if ( 'action' === $resource ) {
				$topic = 'action';
			} else if ( ! in_array( $resource, array( 'coupon', 'customer', 'order', 'product' ) ) ) {
				$topic = 'custom';
			}
		}

		return array(
			'topic'    => $topic,
			'event'    => $event,
			'resource' => $resource
		);
	}

	/**
	 * Webhooks - Get the logs navigation.
	 *
	 * @param  int $total
	 *
	 * @return string
	 */
	private function webhooks_get_logs_navigation( $total, $webhook ) {
		$pages   = ceil( $total / 10 );
		$current = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;

		$html = '<div class="webhook-logs-navigation">';

			$html .= '<p class="info" style="float: left;"><strong>';
			$html .= sprintf( '%s &ndash; Page %d of %d', _n( '1 item', sprintf( '%d items', $total ), $total, 'woocommerce' ), $current, $pages );
			$html .= '</strong></p>';

			if ( 1 < $pages ) {
				$html .= '<p class="tools" style="float: right;">';
					if ( 1 == $current ) {
						$html .= '<button class="button-primary" disabled="disabled">' . __( '&lsaquo; Previous', 'woocommerce' ) . '</button> ';
					} else {
						$html .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&edit-webhook=' . $webhook->id . '&log_page=' . ( $current - 1 ) ) . '#webhook-logs">' . __( '&lsaquo; Previous', 'woocommerce' ) . '</a> ';
					}

					if ( $pages == $current ) {
						$html .= '<button class="button-primary" disabled="disabled">' . __( 'Next &rsaquo;', 'woocommerce' ) . '</button>';
					} else {
						$html .= '<a class="button-primary" href="' . admin_url( 'admin.php?page=wc-settings&tab=api&section=webhooks&edit-webhook=' . $webhook->id . '&log_page=' . ( $current + 1 ) ) . '#webhook-logs">' . __( 'Next &rsaquo;', 'woocommerce' ) . '</a>';
					}
				$html .= '</p>';
			}

		$html .= '<div class="clear"></div></div>';

		return $html;
	}
}

endif;

return new WC_Settings_Rest_API();
