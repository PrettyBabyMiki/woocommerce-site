/* eslint-disable jest/expect-expect */
/*
 * Internal dependencies
 */

// Setup and onboarding tests
const runActivationTest = require( './activate-and-setup/activate.test' );
const { runOnboardingFlowTest, runTaskListTest } = require( './activate-and-setup/onboarding-tasklist.test' );
const runInitialStoreSettingsTest = require( './activate-and-setup/setup.test' );

// Shopper tests
const runCartApplyCouponsTest = require( './shopper/front-end-cart-coupons.test');
const runCartPageTest = require( './shopper/front-end-cart.test' );
const runCheckoutApplyCouponsTest = require( './shopper/front-end-checkout-coupons.test');
const runCheckoutPageTest = require( './shopper/front-end-checkout.test' );
const runMyAccountPageTest = require( './shopper/front-end-my-account.test' );
const runSingleProductPageTest = require( './shopper/front-end-single-product.test' );
const runVariableProductUpdateTest = require( './shopper/front-end-variable-product-updates.test' );

// Merchant tests
const runCreateCouponTest = require( './merchant/wp-admin-coupon-new.test' );
const runCreateOrderTest = require( './merchant/wp-admin-order-new.test' );
const runEditOrderTest = require( './merchant/wp-admin-order-edit.test' );
const { runAddSimpleProductTest, runAddVariableProductTest } = require( './merchant/wp-admin-product-new.test' );
const runUpdateGeneralSettingsTest = require( './merchant/wp-admin-settings-general.test' );
const runProductSettingsTest = require( './merchant/wp-admin-settings-product.test' );
const runTaxSettingsTest = require( './merchant/wp-admin-settings-tax.test' );
const runOrderStatusFiltersTest = require( './merchant/wp-admin-order-status-filters.test' );
const runOrderRefundTest = require( './merchant/wp-admin-order-refund.test' );
const runOrderApplyCouponTest = require( './merchant/wp-admin-order-apply-coupon.test' );
const runProductEditDetailsTest = require( './merchant/wp-admin-product-edit-details.test' );
const runProductSearchTest = require( './merchant/wp-admin-product-search.test' );
const runMerchantOrdersCustomerPaymentPage = require( './merchant/wp-admin-order-customer-payment-page.test' );

// REST API tests
const runExternalProductAPITest = require( './api/external-product.test' );
const runCouponApiTest = require( './api/coupon.test' );
const runGroupedProductAPITest = require( './api/grouped-product.test' );
const runVariableProductAPITest = require( './api/variable-product.test' );

const runSetupOnboardingTests = () => {
	runActivationTest();
	runOnboardingFlowTest();
	runTaskListTest();
	runInitialStoreSettingsTest();
};

const runShopperTests = () => {
	runCartApplyCouponsTest();
	runCartPageTest();
	runCheckoutApplyCouponsTest();
	runCheckoutPageTest();
	runMyAccountPageTest();
	runSingleProductPageTest();
	runVariableProductUpdateTest();
};

const runMerchantTests = () => {
	runCreateCouponTest();
	runCreateOrderTest();
	runEditOrderTest();
	runAddSimpleProductTest();
	runAddVariableProductTest();
	runUpdateGeneralSettingsTest();
	runProductSettingsTest();
	runTaxSettingsTest();
	runOrderStatusFiltersTest();
	runOrderRefundTest();
	runOrderApplyCouponTest();
	runProductEditDetailsTest();
	runProductSearchTest();
	runMerchantOrdersCustomerPaymentPage();
}

const runApiTests = () => {
	runExternalProductAPITest();
	runGroupedProductAPITest();
	runVariableProductAPITest();
	runCouponApiTest();
}

module.exports = {
	runActivationTest,
	runOnboardingFlowTest,
	runTaskListTest,
	runInitialStoreSettingsTest,
	runSetupOnboardingTests,
	runExternalProductAPITest,
	runGroupedProductAPITest,
	runVariableProductAPITest,
	runCouponApiTest,
	runCartApplyCouponsTest,
	runCartPageTest,
	runCheckoutApplyCouponsTest,
	runCheckoutPageTest,
	runMyAccountPageTest,
	runSingleProductPageTest,
	runVariableProductUpdateTest,
	runShopperTests,
	runCreateCouponTest,
	runCreateOrderTest,
	runEditOrderTest,
	runAddSimpleProductTest,
	runAddVariableProductTest,
	runUpdateGeneralSettingsTest,
	runProductSettingsTest,
	runTaxSettingsTest,
	runOrderStatusFiltersTest,
	runOrderRefundTest,
	runOrderApplyCouponTest,
	runProductEditDetailsTest,
	runProductSearchTest,
	runMerchantOrdersCustomerPaymentPage,
	runMerchantTests,
	runApiTests,
};
