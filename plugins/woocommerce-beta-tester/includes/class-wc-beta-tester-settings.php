<?php
/**
 * Beta Tester plugin settings class
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings Class.
 */
class WC_Beta_Tester_Settings {

	/**
	 * Id for channel settings field.
	 *
	 * @var string
	 */
	public static $version_setting_id = 'wc-beta-tester-version';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_menu', array( $this, 'add_to_menus' ) );
	}

	/**
	 * Initialise settings
	 */
	public function settings_init() {
		register_setting( 'wc-beta-tester', 'wc_beta_tester_options' );

		add_settings_section(
			'wc-beta-tester-update',
			__( 'Update Settings', 'woocommerce-beta-tester' ),
			array( $this, 'update_section_html' ),
			'wc-beta-tester'
		);

		add_settings_field(
      self::$version_setting_id,
      __( 'Release Channel', 'woocommerce-beta-tester' ),
			array( $this, 'version_select_html' ),
			'wc-beta-tester',
			'wc-beta-tester-update',
			array(
				'label_for' => self::$version_setting_id,
			)
		);
	}

	/**
	 * Update section HTML output.
	 *
	 * @param array $args Arguments.
	 */
	public function update_section_html( $args ) {
	?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'The following settings allow you to choose which WooCommerce updates to receive on this site, including beta and RC versions not quite ready for production deployment.', 'woocommerce-beta-tester' ); ?></p>
	<?php
	}

	/**
	 * Version select markup output
	 *
	 * @param array $args Arguments.
	 */
	public function version_select_html( $args ) {
		$options  = get_option( 'wc_beta_tester_options' );
		$selected = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : 'stable';
		$channels = array(
			'beta' => array(
				'name'        => __( 'Beta Releases', 'woocommerce-beta-tester' ),
				'description' => __( 'Beta releases contain experimental functionality for testing purposes only. This channel will also include RC and stable releases if more current.', 'woocommerce-beta-tester' ),
			),
			'rc' => array(
				'name'        => __( 'Release Candidates', 'woocommerce-beta-tester' ),
				'description' => __( 'Release candidates are released to ensure any critical problems have not gone undetected. This channel will also include stable releases if more current.', 'woocommerce-beta-tester' ),
			),
			'stable' => array(
				'name'        => __( 'Stable Releases', 'woocommerce-beta-tester' ),
				'description' => __( 'This is the default behaviour in WordPress.', 'woocommerce-beta-tester' ),
			),
		);
		echo '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Update Channel', 'woocommerce-beta-tester' ) . '</span></legend>';
		foreach ( $channels as $channel_id => $channel ) {
			?>
			<label>
				<input type="radio" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="wc_beta_tester_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo esc_attr( $channel_id ); ?>" <?php checked( $selected, $channel_id ); ?> />
				<?php echo esc_html( $channel['name'] ); ?>
				<p class="description">
					<?php echo esc_html( $channel['description'] ); ?>
				</p>
			</label>
			<br>
			<?php
		}
		echo '</fieldset>';
	}

	/**
	 * Add options page to menu
	 */
	public function add_to_menus() {
		add_submenu_page( 'plugins.php', __( 'WooCommerce Beta Tester', 'woocommerce-beta-tester' ), __( 'WC Beta Tester', 'woocommerce-beta-tester' ), 'install_plugins', 'wc-beta-tester', array( $this, 'settings_page_html' ) );
	}

	/**
	 * Output settings HTML
	 */
	public function settings_page_html() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'wc-beta-tester-messages', 'wc-beta-tester-message', __( 'Settings Saved', 'woocommerce-beta-tester' ), 'updated' );
		}

		// show error/update messages.
		settings_errors( 'wc-beta-tester-messages' );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
		<?php

		settings_fields( 'wc-beta-tester' );
		do_settings_sections( 'wc-beta-tester' );
		submit_button();

		?>
			</form>
		</div>
		<?php
	}
}

new WC_Beta_Tester_Settings();
