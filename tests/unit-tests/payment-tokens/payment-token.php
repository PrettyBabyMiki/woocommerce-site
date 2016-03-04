<?php
namespace WooCommerce\Tests\Payment_Tokens;

/**
 * Class Payment_Token
 * @package WooCommerce\Tests\Payment_Tokens
 */
class Payment_Token extends \WC_Unit_Test_Case {

	/**
	 * Test get_id to make sure it returns the ID passed into the class.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_get_id() {
		$token = new \WC_Payment_Token_Stub( 1 );
		$this->assertEquals( 1, $token->get_id() );
	}

	/**
	 * Test get type returns the class name/type.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_get_type() {
		$token = new \WC_Payment_Token_Stub( 1 );
		$this->assertEquals( 'stub', $token->get_type() );
	}

	/**
	 * Test get token to make sure it returns the passed token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_get_token() {
		$raw_token = time() . ' ' . __FUNCTION__;
		$token = new \WC_Payment_Token_Stub( 1, array( 'token' => $raw_token ) );
		$this->assertEquals( $raw_token, $token->get_token() );
	}

	/**
	 * Test set token to make sure it sets the pased token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_set_token() {
		$raw_token = time() . ' ' . __FUNCTION__;
		$token = new \WC_Payment_Token_Stub( 1 );
		$token->set_token( $raw_token );
		$this->assertEquals( $raw_token, $token->get_token() );
	}

	/**
	 * Test get user ID to make sure it passes the correct ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_get_user_id() {
		$token = new \WC_Payment_Token_Stub( 1, array( 'user_id' => 1 ) );
		$this->assertEquals( 1, $token->get_user_id() );
	}

	/**
	 * Test get user ID to make sure it returns 0 if there is no user ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_get_user_id_defaults_to_0() {
		$token = new \WC_Payment_Token_Stub( 1 );
		$this->assertEquals( 0, $token->get_user_id() );
	}

	/**
	 * Test set user ID to make sure it passes the correct ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_set_user_id() {
		$token = new \WC_Payment_Token_Stub( 1 );
		$token->set_user_id( 5 );
		$this->assertEquals( 5, $token->get_user_id() );
	}

	/**
	 * Test getting the gateway ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_get_gateway_id() {
		$token = new \WC_Payment_Token_Stub( 1, array( 'gateway_id' => 'paypal' ) );
		$this->assertEquals( 'paypal', $token->get_gateway_id() );
	}

	/**
	 * Test set the gateway ID.
	 * @since 2.6.0
	 */
	public function test_wc_payment_set_gateway_id() {
		$token = new \WC_Payment_Token_Stub( 1 );
		$token->set_gateway_id( 'paypal' );
		$this->assertEquals( 'paypal', $token->get_gateway_id() );
	}

	/**
	 * Test setting a token as default.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_set_default() {
		$token = new \WC_Payment_Token_Stub( 1 );
		$token->set_default( true );
		$this->assertTrue( $token->is_default() );
		$token->set_default( false );
		$this->assertFalse( $token->is_default() );
	}

	/**
	 * Test is_default.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_is_default_returns_correct_state() {
		$token = new \WC_Payment_Token_Stub( 1, array( 'is_default' => true ) );
		$this->assertTrue( $token->is_default() );
		$token = new \WC_Payment_Token_Stub( 1 );
		$this->assertFalse( $token->is_default() );
		$token = new \WC_Payment_Token_Stub( 1, array( 'is_default' => false ) );
		$this->assertFalse( $token->is_default() );
	}

	/**
	 * Test that get_data returns the correct internal representation for a token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_get_data() {
		$raw_token = time() . ' ' . __FUNCTION__;
		$token = new \WC_Payment_Token_Stub( 1, array(
			'token'      => $raw_token,
			'gateway_id' => 'paypal'
		) );
		$token->set_extra( 'woocommerce' );

		$data = $token->get_data();

		$this->assertEquals( $raw_token, $data['token'] );
		$this->assertEquals( 'paypal', $data['gateway_id'] );
		$this->assertEquals( 'stub', $data['type'] );
		$this->assertEquals( 'woocommerce', $data['meta']['extra'] );
	}

	/**
	 * Test token validation.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_validation() {
		$token = new \WC_Payment_Token_Stub( 1 );
		$token->set_token( time() . ' ' . __FUNCTION__ );
		$this->assertTrue( $token->validate() );

		$token = new \WC_Payment_Token_Stub( 1 );
		$this->assertFalse( $token->validate() );
	}

	/**
	 * Test reading a token from the database.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_read() {
		$token = \WC_Helper_Payment_Token::create_stub_token( __FUNCTION__ );
		$token_id = $token->get_id();

		$token_read = new \WC_Payment_Token_Stub();
		$token_read->read( $token_id );

		$this->assertEquals( $token->get_token(), $token_read->get_token() );
		$this->assertEquals( $token->get_extra(), $token_read->get_extra() );
	}

	/**
	 * Test updating a token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_update() {
		$token = \WC_Helper_Payment_Token::create_stub_token( __FUNCTION__ );
		$this->assertEquals( __FUNCTION__, $token->get_extra() );
		$token->set_extra( ':)' );
		$token->update();
		$this->assertEquals( ':)', $token->get_extra() );
	}

	/**
	 * Test creating a new token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_create() {
		$token = new \WC_Payment_Token_Stub();
		$token->set_extra( __FUNCTION__ );
		$token->set_token( time() );
		$token->create();

		$this->assertNotEmpty( $token->get_id() );
		$this->assertEquals( __FUNCTION__, $token->get_extra() );
	}

	/**
	 * Test deleting a token.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_delete() {
		$token = \WC_Helper_Payment_Token::create_stub_token( __FUNCTION__ );
		$token_id = $token->get_id();
		$token->delete();
		$get_token = \WC_Payment_Tokens::get( $token_id );
		$this->assertNull( $get_token );
	}

	/**
	 * Test a meta function (like CC's last4) doesn't work on the core abstract class.
	 * @since 2.6.0
	 */
	public function test_wc_payment_token_last4_doesnt_work() {
		$token = new \WC_Payment_Token_Stub();
		$this->assertFalse( is_callable( $token, 'get_last4' ) );
	}

}
