<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Importer' ) ) {
	return;
}

/**
 * Product importer - import products into WooCommerce.
 *
 * @author      Automattic
 * @category    Admin
 * @package     WooCommerce/Admin/Importers
 * @version     3.1.0
 */
class WC_Product_Importer extends WP_Importer {

	/**
	 * The current file id.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The current file url.
	 *
	 * @var string
	 */
	public $file_url;

	/**
	 * The current import page.
	 *
	 * @var string
	 */
	public $import_page;

	/**
	 * The current delimiter.
	 *
	 * @var string
	 */
	public $delimiter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->import_page = 'woocommerce_product_csv';
		$this->delimiter   = empty( $_POST['delimiter'] ) ? ',' : (string) wc_clean( $_POST['delimiter'] );
	}

	/**
	 * Registered callback function for the WordPress Importer.
	 *
	 * Manages the three separate stages of the CSV import process.
	 */
	public function dispatch() {

		$this->header();

		$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];

		switch ( $step ) {

			case 0:
				$this->greet();
				break;

			case 1:
				check_admin_referer( 'import-upload' );

				if ( $this->handle_upload() ) {

					if ( $this->id ) {
						$file = get_attached_file( $this->id );
					} else {
						$file = ABSPATH . $this->file_url;
					}

					$this->import( $file );
				}
				break;
		}

		$this->footer();
	}

	/**
	 * Import is starting.
	 */
	private function import_start() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable();
		}
		wc_set_time_limit( 0 );
		@ob_flush();
		@flush();
		@ini_set( 'auto_detect_line_endings', '1' );
	}

	/**
	 * Import the file if it exists and is valid.
	 *
	 * @param mixed $file
	 */
	public function import( $file ) {
		if ( ! is_file( $file ) ) {
			$this->import_error( __( 'The file does not exist, please try again.', 'woocommerce' ) );
		}

		$this->import_start();

		$loop = 0;

		if ( ( $handle = fopen( $file, "r" ) ) !== false ) {

			$header = fgetcsv( $handle, 0, $this->delimiter );

			while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== false ) {
				// TODO. Parse and process input.
			}

			fclose( $handle );
		}

		// Show Result
		echo '<div class="updated settings-error"><p>';
		/* translators: %s: products count */
		printf(
			__( 'Import complete - imported %s products.', 'woocommerce' ),
			'<strong>' . $loop . '</strong>'
		);
		echo '</p></div>';

		$this->import_end();
	}

	/**
	 * Performs post-import cleanup of files and the cache.
	 */
	public function import_end() {
		echo '<p>' . __( 'All done!', 'woocommerce' ) . ' <a href="' . admin_url( 'edit.php?post_type=product' ) . '">' . __( 'View products', 'woocommerce' ) . '</a>' . '</p>';

		do_action( 'import_end' );
	}

	/**
	 * Handles the CSV upload and initial parsing of the file to prepare for.
	 * displaying author import options.
	 *
	 * @return bool False if error uploading or invalid file, true otherwise
	 */
	public function handle_upload() {
		if ( empty( $_POST['file_url'] ) ) {

			$file = wp_import_handle_upload();

			if ( isset( $file['error'] ) ) {
				$this->import_error( $file['error'] );
			}

			$this->id = absint( $file['id'] );

		} elseif ( file_exists( ABSPATH . $_POST['file_url'] ) ) {
			$this->file_url = esc_attr( $_POST['file_url'] );
		} else {
			$this->import_error();
		}

		return true;
	}

	/**
	 * Output header html.
	 */
	public function header() {
		echo '<div class="wrap">';
		echo '<h1>' . __( 'Import products', 'woocommerce' ) . '</h1>';
	}

	/**
	 * Output footer html.
	 */
	public function footer() {
		echo '</div>';
	}

	/**
	 * Output information about the uploading process.
	 */
	public function greet() {

		echo '<div class="narrow">';
		echo '<p>' . __( 'Hi there! Upload a CSV file containing products to import them into your shop. Choose a .csv file to upload, then click "Upload file and import".', 'woocommerce' ) . '</p>';

		$action = 'admin.php?import=woocommerce_product_csv&step=1';

		$bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
		$size = size_format( $bytes );
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) :
			?><div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'woocommerce' ); ?></p>
			<p><strong><?php echo $upload_dir['error']; ?></strong></p></div><?php
		else :
			?>
			<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr( wp_nonce_url( $action, 'import-upload' ) ); ?>">
				<table class="form-table">
					<tbody>
						<tr>
							<th>
								<label for="upload"><?php _e( 'Choose a file from your computer:', 'woocommerce' ); ?></label>
							</th>
							<td>
								<input type="file" id="upload" name="import" size="25" />
								<input type="hidden" name="action" value="save" />
								<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
								<small><?php
									/* translators: %s: maximum upload size */
									printf(
										__( 'Maximum size: %s', 'woocommerce' ),
										$size
									);
								?></small>
							</td>
						</tr>
						<tr>
							<th>
								<label for="file_url"><?php _e( 'OR enter path to file:', 'woocommerce' ); ?></label>
							</th>
							<td>
								<?php echo ' ' . ABSPATH . ' '; ?><input type="text" id="file_url" name="file_url" size="25" />
							</td>
						</tr>
						<tr>
							<th><label><?php _e( 'Delimiter', 'woocommerce' ); ?></label><br/></th>
							<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import', 'woocommerce' ); ?>" />
				</p>
			</form>
			<?php
		endif;

		echo '</div>';
	}

	/**
	 * Show import error and quit.
	 * @param  string $message
	 */
	private function import_error( $message = '' ) {
		echo '<p><strong>' . __( 'Sorry, there has been an error.', 'woocommerce' ) . '</strong><br />';
		if ( $message ) {
			echo esc_html( $message );
		}
		echo '</p>';
		$this->footer();
		die();
	}

}
