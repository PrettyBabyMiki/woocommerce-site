<?php
/**
 * Transactional Emails Controller
 *
 * WooCommerce Emails Class which handles the sending on transactional emails and email templates. This class loads in available emails.
 *
 * @class 		WC_Emails
 * @version		1.7.0
 * @package		WooCommerce/Classes/Emails
 * @author 		WooThemes
 */
class WC_Emails {
	
	/**
	 * @var array Array of email notification classes.
	 * @access public
	 */
	public $emails;
	
	/**
	 * @var string Stores the emailer's address.
	 * @access private
	 */
	private $_from_address;

	/**
	 * @var string Stores the emailer's name.
	 * @access private
	 */
	private $_from_name;
	
	/**
	 * @var mixed Content type for sent emails
	 * @access private
	 */
	private $_content_type;

	/**
	 * Constructor for the email class hooks in all emails that can be sent.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		
		// Include email classes
		include_once( 'class-wc-email.php' );
		include_once( 'class-wc-email-customer-completed-order.php' );
		include_once( 'class-wc-email-customer-invoice.php' );
		include_once( 'class-wc-email-customer-new-account.php' );
		include_once( 'class-wc-email-customer-note.php' );
		include_once( 'class-wc-email-customer-reset-password.php' );
		include_once( 'class-wc-email-customer-processing-order.php' );
		include_once( 'class-wc-email-new-order.php' );
		
		$this->emails['WC_Email_New_Order'] = new WC_Email_New_Order();
		$this->emails['WC_Email_Customer_Processing_Order'] = new WC_Email_Customer_Processing_Order();
		$this->emails['WC_Email_Customer_Completed_Order'] = new WC_Email_Customer_Completed_Order();
		$this->emails['WC_Email_Customer_Invoice'] = new WC_Email_Customer_Invoice();
		$this->emails['WC_Email_Customer_Note'] = new WC_Email_Customer_Note();
		$this->emails['WC_Email_Customer_Reset_Password'] = new WC_Email_Customer_Reset_Password();
		$this->emails['WC_Email_Customer_New_Account'] = new WC_Email_Customer_New_Account();
		
		$this->emails = apply_filters( 'woocommerce_email_classes', $this->emails );

		// Email Header, Footer and content hooks
		add_action( 'woocommerce_email_header', array( &$this, 'email_header' ) );
		add_action( 'woocommerce_email_footer', array( &$this, 'email_footer' ) );
		add_action( 'woocommerce_email_order_meta', array( &$this, 'order_meta' ), 10, 3 );

		// Hooks for sending emails during store events
		add_action( 'woocommerce_low_stock_notification', array( &$this, 'low_stock' ) );
		add_action( 'woocommerce_no_stock_notification', array( &$this, 'no_stock' ) );
		add_action( 'woocommerce_product_on_backorder_notification', array( &$this, 'backorder' ));
		
		// Let 3rd parties unhook the above via this hook
		do_action( 'woocommerce_email', $this );
	}
	
	/**
	 * Return the email classes - used in admin to load settings.
	 * 
	 * @access public
	 * @return array
	 */
	function get_emails() {
		return $this->emails;
	}

	/**
	 * Get from name for email.
	 *
	 * @access public
	 * @return string
	 */
	function get_from_name() {
		if ( ! $this->_from_name )
			$this->_from_name = get_option( 'woocommerce_email_from_name' );
			
		return $this->_from_name;
	}

	/**
	 * Get from email address.
	 *
	 * @access public
	 * @return string
	 */
	function get_from_address() {
		if ( ! $this->_from_address )
			$this->_from_address = get_option( 'woocommerce_email_from_address' );
			
		return $this->_from_address;
	}

	/**
	 * Get the content type for the email.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_type() {
		return $this->_content_type;
	}

	/**
	 * Get the email header.
	 *
	 * @access public
	 * @param mixed $email_heading heading for the email
	 * @return void
	 */
	function email_header( $email_heading ) {
		woocommerce_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	}

	/**
	 * Get the email footer.
	 *
	 * @access public
	 * @return void
	 */
	function email_footer() {
		woocommerce_get_template( 'emails/email-footer.php' );
	}

	/**
	 * Wraps a message in the woocommerce mail template.
	 *
	 * @access public
	 * @param mixed $email_heading
	 * @param mixed $message
	 * @return string
	 */
	function wrap_message( $email_heading, $message, $plain_text = false ) {
		// Buffer
		ob_start();

		do_action( 'woocommerce_email_header', $email_heading );

		echo wpautop( wptexturize( $message ) );

		do_action( 'woocommerce_email_footer' );

		// Get contents
		$message = ob_get_clean();

		return $message;
	}

	/**
	 * Send the email.
	 *
	 * @access public
	 * @param mixed $to
	 * @param mixed $subject
	 * @param mixed $message
	 * @param string $headers (default: "Content-Type: text/html\r\n")
	 * @param string $attachments (default: "")
	 * @param string $content_type (default: "text/html")
	 * @return void
	 */
	function send( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "", $content_type = 'text/html' ) {
		
		// Set content type
		$this->_content_type = $content_type;
		
		// Filters for the email
		add_filter( 'wp_mail_from', array( &$this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( &$this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( &$this, 'get_content_type' ) );
		
		// Send
		wp_mail( $to, $subject, $message, $headers, $attachments );

		// Unhook filters
		remove_filter( 'wp_mail_from', array( &$this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( &$this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( &$this, 'get_content_type' ) );
	}
	
	/**
	 * Prepare and send the customer invoice email on demand.
	 *
	 * @access public
	 * @param mixed $pay_for_order
	 * @return void
	 */
	function customer_invoice( $order ) {
		$email = $this->emails['WC_Email_Customer_Invoice'];
		$email->trigger( $order );
	}
	
	/**
	 * Customer new account welcome email.
	 *
	 * @access public
	 * @param mixed $user_id
	 * @param mixed $plaintext_pass
	 * @return void
	 */
	function customer_new_account( $user_id, $plaintext_pass ) {
		if ( ! $user_id || ! $plaintext_pass) 
			return;
		
		$email = $this->emails['WC_Email_Customer_New_Account'];
		$email->trigger( $user_id, $plaintext_pass );
	}	
	 
	/**
	 * Add order meta to email templates.
	 * 
	 * @access public
	 * @param mixed $order
	 * @param bool $sent_to_admin (default: false)
	 * @param bool $plain_text (default: false)
	 * @return void
	 */
	function order_meta( $order, $sent_to_admin = false, $plain_text = false ) {

		$meta = array();
		$show_fields = apply_filters( 'woocommerce_email_order_meta_keys', array( 'coupons' ), $sent_to_admin );

		if ( $order->customer_note )
			$meta[ __( 'Note', 'woocommerce' ) ] = wptexturize( $order->customer_note );

		if ( $show_fields ) 
			foreach ( $show_fields as $field ) {
				$value = get_post_meta( $order->id, $field, true );
				if ( $value ) 
					$meta[ ucwords( esc_attr( $field ) ) ] = wptexturize( $value );
			}

		if ( sizeof( $meta ) > 0 ) {
		
			if ( $plain_text ) {
				
				foreach ( $meta as $key => $value )
					echo $key . ': ' . $value . "\n";
				
			} else {

				foreach ( $meta as $key => $value )
					echo '<p><strong>' . $key . ':</strong> ' . $value . '</p>';
			}
		}
	}

	/**
	 * Low stock notification email.
	 *
	 * @access public
	 * @param mixed $product
	 * @return void
	 */
	function low_stock( $product ) {

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_low_stock', sprintf( '[%s] %s', $blogname, __( 'Product low in stock', 'woocommerce' ) ), $product );

		$sku = ($product->sku) ? '(' . $product->sku . ') ' : '';

		if ( ! empty( $product->variation_id ) )
			$title = sprintf(__('Variation #%s of %s', 'woocommerce'), $product->variation_id, get_the_title($product->id)) . ' ' . $sku;
		else
			$title = sprintf(__('Product #%s - %s', 'woocommerce'), $product->id, get_the_title($product->id)) . ' ' . $sku;

		$message = $title . __('is low in stock.', 'woocommerce');

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'low_stock', $product);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'low_stock', $product);

		// Send the mail
		wp_mail( get_option('woocommerce_stock_email_recipient'), $subject, $message, $headers, $attachments );
	}

	/**
	 * No stock notification email.
	 *
	 * @access public
	 * @param mixed $product
	 * @return void
	 */
	function no_stock( $product ) {

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_no_stock', sprintf( '[%s] %s', $blogname, __( 'Product out of stock', 'woocommerce' ) ), $product );

		$sku = ($product->sku) ? '(' . $product->sku . ') ' : '';

		if ( ! empty( $product->variation_id ) )
			$title = sprintf(__('Variation #%s of %s', 'woocommerce'), $product->variation_id, get_the_title($product->id)) . ' ' . $sku;
		else
			$title = sprintf(__('Product #%s - %s', 'woocommerce'), $product->id, get_the_title($product->id)) . ' ' . $sku;

		$message = $title . __('is out of stock.', 'woocommerce');

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'no_stock', $product);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'no_stock', $product);

		// Send the mail
		wp_mail( get_option('woocommerce_stock_email_recipient'), $subject, $message, $headers, $attachments );
	}

	/**
	 * Backorder notification email.
	 *
	 * @access public
	 * @param mixed $args
	 * @return void
	 */
	function backorder( $args ) {

		$defaults = array(
			'product' => '',
			'quantity' => '',
			'order_id' => ''
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		if (!$product || !$quantity) return;

		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$subject = apply_filters( 'woocommerce_email_subject_backorder', sprintf( '[%s] %s', $blogname, __( 'Product Backorder', 'woocommerce' ) ), $product );

		$sku = ($product->sku) ? ' (' . $product->sku . ')' : '';

		if ( ! empty( $product->variation_id ) )
			$title = sprintf(__('Variation #%s of %s', 'woocommerce'), $product->variation_id, get_the_title($product->id)) . $sku;
		else
			$title = sprintf(__('Product #%s - %s', 'woocommerce'), $product->id, get_the_title($product->id)) . $sku;

		$message = sprintf(__('%s units of %s have been backordered in order #%s.', 'woocommerce'), $quantity, $title, $order_id );

		//	CC, BCC, additional headers
		$headers = apply_filters('woocommerce_email_headers', '', 'backorder', $args);

		// Attachments
		$attachments = apply_filters('woocommerce_email_attachments', '', 'backorder', $args);

		// Send the mail
		wp_mail( get_option('woocommerce_stock_email_recipient'), $subject, $message, $headers, $attachments );
	}

}