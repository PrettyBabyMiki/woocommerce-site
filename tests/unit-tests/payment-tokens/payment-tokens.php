<?php
namespace WooCommerce\Tests\Payment_Tokens;

/**
 * Class Payment_Tokens
 * @package WooCommerce\Tests\Payment_Tokens
 */
class Payment_Tokens extends \WC_Unit_Test_Case {

	/**
	 * Test getting tokens associated with an order.
	 * @since 2.6.0
	 */
	function test_wc_payment_tokens_get_order_tokens() {
		$order = \WC_Helper_Order::create_order();
		$this->assertEmpty( \WC_Payment_Tokens::get_order_tokens( $order->id ) );

		$token = \WC_Helper_Payment_Token::create_cc_token();
		update_post_meta( $order->id, '_payment_tokens', array( $token->get_id() ) );

		$this->assertCount( 1, \WC_Payment_Tokens::get_order_tokens( $order->id ) );

	}

	/**
	 * Test getting tokens associated with a user and no gateway ID.
	 * @since 2.6.0
	 */
	function test_wc_payment_tokens_get_customer_tokens_no_gateway() {
		$this->assertEmpty( \WC_Payment_Tokens::get_customer_tokens( 1 ) );

		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( 1 );
		$token->save();

		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( 1 );
		$token->save();

		$this->assertCount( 2, \WC_Payment_Tokens::get_customer_tokens( 1 ) );
	}

	/**
	 * Test getting tokens associated with a user and for a specific gateway.
	 * @since 2.6.0
	 */
	function test_wc_payment_tokens_get_customer_tokens_with_gateway() {
		$this->assertEmpty( \WC_Payment_Tokens::get_customer_tokens( 1 ) );

		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( 1 );
		$token->set_gateway_id( 'simplify_commerce' );
		$token->save();

		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token->set_user_id( 1 );
		$token->set_gateway_id( 'paypal' );
		$token->save();

		$this->assertCount( 2, \WC_Payment_Tokens::get_customer_tokens( 1 ) );
		$this->assertCount( 1, \WC_Payment_Tokens::get_customer_tokens( 1, 'simplify_commerce' ) );

		foreach ( \WC_Payment_Tokens::get_customer_tokens( 1, 'simplify_commerce' ) as $simplify_token ) {
			$this->assertEquals( 'simplify_commerce', $simplify_token->get_gateway_id() );
		}
	}

	/**
	 * Test getting a token by ID.
	 * @since 2.6.0
	 */
	function test_wc_payment_tokens_get() {
		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();
		$get_token = \WC_Payment_Tokens::get( $token_id );
		$this->assertEquals( $token->get_token(), $get_token->get_token() );
	}

	/**
	 * Test deleting a token by ID.
	 * @since 2.6.0
	 */
	function test_wc_payment_tokens_delete() {
		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();

		\WC_Payment_Tokens::delete( $token_id );

		$get_token = \WC_Payment_Tokens::get( $token_id );
		$this->assertNull( $get_token );
	}

	/**
	 * Test getting a token's type by ID.
	 * @since 2.6.0
	 */
	function test_wc_payment_tokens_get_type_by_id() {
		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();
		$this->assertEquals( 'CC', \WC_Payment_Tokens::get_token_type_by_id( $token_id ) );
	}

	/**
	 * Test setting a users default token.
	 * @since 2.6.0
	 */
	function test_wc_payment_tokens_set_users_default() {
		$token = \WC_Helper_Payment_Token::create_cc_token();
		$token_id = $token->get_id();
		$token->set_user_id( 1 );
		$token->save();

		$token2 = \WC_Helper_Payment_Token::create_cc_token();
		$token_id_2 = $token2->get_id();
		$token2->set_user_id( 1 );
		$token2->save();

		$this->assertFalse( $token->is_default() );
		$this->assertFalse( $token2->is_default() );

		\WC_Payment_Tokens::set_users_default( 1, $token_id_2 );
		$token->read( $token_id );
		$token2->read( $token_id_2 );
		$this->assertFalse( $token->is_default() );
		$this->assertTrue( $token2->is_default() );

		\WC_Payment_Tokens::set_users_default( 1, $token_id );
		$token->read( $token_id );
		$token2->read( $token_id_2 );
		$this->assertTrue( $token->is_default() );
		$this->assertFalse( $token2->is_default() );
	}

}
