<?php
/**
 * WooCommerce Marketing.
 * NOTE: DO NOT edit this file in WooCommerce core, this is generated from woocommerce-admin.
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\Marketing\InstalledExtensions;
use Automattic\WooCommerce\Admin\Loader;

/**
 * Contains backend logic for the Marketing feature.
 */
class Marketing {
	/**
	 * Name of recommended plugins transient.
	 *
	 * @var string
	 */
	const RECOMMENDED_PLUGINS_TRANSIENT = 'wc_marketing_recommended_plugins';

	/**
	 * Name of knowledge base post transient.
	 *
	 * @var string
	 */
	const KNOWLEDGE_BASE_TRANSIENT = 'wc_marketing_knowledge_base';

	/**
	 * Class instance.
	 *
	 * @var Marketing instance
	 */
	protected static $instance = null;

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_pages' ) );

		if ( ! is_admin() ) {
			return;
		}

		add_filter( 'woocommerce_admin_preload_options', array( $this, 'preload_options' ) );
		add_filter( 'woocommerce_shared_settings', array( $this, 'component_settings' ), 30 );
	}

	/**
	 * Registers report pages.
	 */
	public function register_pages() {
		$marketing_pages = array(
			array(
				'id'       => 'woocommerce-marketing',
				'title'    => __( 'Marketing', 'woocommerce-admin' ),
				'path'     => '/marketing',
				'icon'     => 'dashicons-megaphone',
				'position' => 58, // After WooCommerce & Product menu items.
			),
		);

		$marketing_pages = apply_filters( 'woocommerce_marketing_menu_items', $marketing_pages );

		foreach ( $marketing_pages as $marketing_page ) {
			if ( ! is_null( $marketing_page ) ) {
				wc_admin_register_page( $marketing_page );
			}
		}
	}

	/**
	 * Preload options to prime state of the application.
	 *
	 * @param array $options Array of options to preload.
	 * @return array
	 */
	public function preload_options( $options ) {
		$options[] = 'woocommerce_marketing_overview_welcome_hidden';

		return $options;
	}

	/**
	 * Add settings for marketing feature.
	 *
	 * @param array $settings Component settings.
	 * @return array
	 */
	public function component_settings( $settings ) {
		// Bail early if not on a wc-admin powered page.
		if ( ! Loader::is_admin_page() ) {
			return $settings;
		}

		$settings['marketing']['installedExtensions'] = InstalledExtensions::get_data();
		$settings['marketing']['connectNonce']        = wp_create_nonce( 'connect' );

		return $settings;
	}

	/**
	 * Load recommended plugins from WooCommerce.com
	 *
	 * @return array
	 */
	public function get_recommended_plugins() {
		$plugins = get_transient( self::RECOMMENDED_PLUGINS_TRANSIENT );

		if ( false === $plugins ) {
			// TODO update placeholder URL.
			$request = wp_remote_get( 'https://ephemeral-findingsimple-20200320.atomicsites.blog/wp-json/wccom/marketing-tab/1.0/recommendations.json' );
			$plugins = [];

			if ( ! is_wp_error( $request ) && 200 === $request['response']['code'] ) {
				$plugins = json_decode( $request['body'], true );
			}

			// Cache an empty result to avoid repeated failed requests.
			set_transient( self::RECOMMENDED_PLUGINS_TRANSIENT, $plugins, 3 * DAY_IN_SECONDS );
		}

		return array_values( $plugins );
	}

	/**
	 * Load knowledge base posts from WooCommerce.com
	 *
	 * @return array
	 */
	public function get_knowledge_base_posts() {
		$posts = get_transient( self::KNOWLEDGE_BASE_TRANSIENT );

		if ( false === $posts ) {
			$request_url = add_query_arg(
				array(
					'categories' => 1744, // Marketing.
					'page'       => 1,
					'per_page'   => 8,
					'_embed'     => 1,
				),
				'https://woocommerce.com/wp-json/wp/v2/posts'
			);
			$request = wp_remote_get( $request_url );
			$posts   = [];

			if ( ! is_wp_error( $request ) && 200 === $request['response']['code'] ) {
				$raw_posts = json_decode( $request['body'], true );

				foreach ( $raw_posts as $raw_post ) {
					$post = [
						'title'         => html_entity_decode( $raw_post['title']['rendered'] ),
						'date'          => $raw_post['date_gmt'],
						'link'          => $raw_post['link'],
						'author_name'   => isset( $raw_post['author_name'] ) ? html_entity_decode( $raw_post['author_name'] ) : '',
						'author_avatar' => isset( $raw_post['author_avatar_url'] ) ? $raw_post['author_avatar_url'] : '',
					];

					$featured_media = $raw_post['_embedded']['wp:featuredmedia'];

					if ( count( $featured_media ) > 0 ) {
						$image         = current( $featured_media );
						$post['image'] = add_query_arg(
							array(
								'resize' => '650,340',
								'crop'   => 1,
							),
							$image['source_url']
						);
					}

					$posts[] = $post;
				}
			}

			set_transient( self::KNOWLEDGE_BASE_TRANSIENT, $posts, DAY_IN_SECONDS );
		}

		return $posts;
	}

}
