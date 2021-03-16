# Testing instructions

## Unreleased

### Close activity panel tabs by default and track #6566

1. Open your browser console and enter `localStorage.setItem( 'debug', 'wc-admin:tracks' );`.  Make sure the "Verbose" is selected under the levels shown.
2. With the task list enabled, navigate to the homescreen.
3. Check that the `wcadmin_activity_panel_visible_panels` event is shown with `taskList: true` in its data.
4. Hide the task list.
5. Note that `wcadmin_activity_panel_visible_panels` event is shown with visible activity panels.
6. After refreshing, make sure that the "Orders" activity panel is closed by default.

### Update undefined task name properties for help panel tracks #6565

1. Enter `localStorage.setItem( 'debug', 'wc-admin:*' );` into your console. Leave your console open.
2. Navigate to the homescreen.
3. Open the "Help" tab in the top right.
4. Note the tracks information in the console includes `homescreen` for the `taskName` property.
5. Click on a help item.
6. Note `homescreen` is used for the `taskName` in the help panel click tracks event.
6. Navigate to any task in the task list.
7. Click on the "Help" tab.
8. Note the `taskName` for the event is the current task.
9. Click on a help item.
10. Note the `taskName` for the event is the current task.

### Add gross sales column to CSV export #6567

1. Navigate to Analytics -> Revenue
2. Adjust the date filter so that more than 25 rows are visible
4. Click "Download"
5. Click the download link in the email
6. See gross sales column

### Add customer name column to CSV export #6556

- Create more than 25 orders
- Go to Analytics -> Orders -> Click "Download"
- Click download link in the email
- See customer column with customer full name

### Allow the manager role to query certain options #6577

Testing `woocommerce_ces_tracks_queue`

1. Checkout this branch.
2. Open browser inspector and select the Network tab.
2. Navigate to WooCommerce -> Settings.
3. Confirm that the request to `/wp-json/wc-admin/options?options=woocommerce_ces_tracks_queue&_locale=user` returns 200 status.


Testing `woocommerce_navigation_intro_modal_dismissed`

1. Checkout this branch.
2. Navigate to WooCommerce -> Settings -> Advanced -> features (/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features) and enable Navigation
3. Open browser inspector and select the Network tab.
4. Navigate to WooCommerce -> Home
5. Confirm that the request to `/wp-json/wc-admin/options?options=woocommerce_navigation_intro_modal_dismissed&_locale=user` returns 200 status.
### Refactor profile wizard benefits step and add tests #6583

1. Deactivate Jetpack and/or WooCommerce Services.
2. Visit the profiler benefits page. `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=benefits`
3. Click "Yes please!" to continue.
4. Without connecting to Jetpack, navigate backwards using your browser's back button.
5. Make sure the page continues to display (benefits may have changed) and that action buttons are functional.
6. Make sure skipping the install works as expected.
7. Connect Jetpack.
8. Attempt to directly visit the benefits page. `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard&step=benefits`
9. Note that you are redirected to the homescreen.

### Fix hidden menu title on smaller screens #6562

1. Enable the new navigation.
2. Shorten your viewport height so that the secondary menu overlaps the main.
3. Make sure the menu title can still be seen.
### Add filter to profile wizard steps #6564

1. Add the following JS to your admin head.  You can use a plugin like "Add Admin Javascript" to do this:
```
wp.hooks.addFilter( 'woocommerce_admin_profile_wizard_steps', 'woocommerce-admin', ( steps ) => {
	return steps.filter( ( step ) => step.key !== 'product-types' );
} );
```
2. Navigate to the profile wizard. `wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard`.
3. Make sure the filtered step (product types) is not shown.

### Use wc filter to get status tabs for tools category #6525

1. Register a new tab via the filter.
```
add_filter( 'woocommerce_admin_status_tabs', function ( array $tabs ) {
	$tabs['my-tools-page'] = __( 'My Tools Page', 'your-text-domain' );
	return $tabs;
} );
```
2. Enable the new navigation.
3. Make sure the menu item for the registered tab is shown under `Tools`.

### Remove mobile activity panel toggle #6539

1. Narrow your viewport to < 782px.
2. Navigate to various WooCommerce pages.
3. Make sure the various tabs can be seen and function as expected.
4. Navigate to a WooCommerce Admin page that is not the homepage.
5. Open the "Inbox" panel.
6. Click on the "Inbox" panel button again.
7. Make sure the panel closes as expected and does not reopen immediately.

### Add legacy report items to new navigation #6507

1. Enable the new navigation experience.
2. Navigate to Analytics->Reports.
3. Note that all the reports exist and navigating to those reports works as expected.
4. Check that report menu items are marked active when navigating to that page.

### Add navigation container tests #6464

1. On a new site, finish the store setup wizard, but don't hide the task list.
2. Navigate to a WooCommerce Admin Analytics page.
3. Note the menu is under the "Analytics" level.
4. Click the "Store Setup" link in the top right hand corner.
5. Note that the navigation level automatically is updated to the root level where the "Home" item is marked active.

### Add preview site button on the appearance task #6457

1. Navigate to Home and click "Personalzie your store" task.
2. Click on the "Preview Site" button on the header.
3. A new tab should open and the URL should be the site URL.
4. Navigate to other tasks such as "Store Details" or "Add products" .
5. The "Preview Site" should not be shown on the other tasks.

### Store profiler - Added MailPoet to new Business Details step  #6515

- Create a brand new site and go to the OBW.
- In the first OBW step (`Store Details`) set `US` in the `Country / Region` selector.
- Continue with the profiler.
- In the 4th step (`Business Details`) choose any of the options in both selectors.
- Under `Free features` tab, verify that the displayed extensions are:
```
Mailpoet
Facebook
Google Ads
Mailchimp
Creative Mail
```
(In that order)
- Verify that the Creative Mail option copy is `Emails made easy with Creative Mail`.

### Store profiler - Added MailPoet to Business Details step  #6503

- Create a brand new site and go to the OBW.
- In the first OBW step (`Store Details`) set a Country / Region other than `US | BR | FR | ID | GB | DE | VN | CA | PL | MY | AU | NG | GR | BE | PT | DK | SE | JP` (e.g.: Uruguay).
- Continue with the profiler.
- In the 4th step (`Business Details`) choose any of the options in both selectors.
- Verify that the displayed extensions are:
```
Mailpoet
Facebook
Google Ads
Mailchimp
Creative Mail
```
(In that order)
- Verify that the Creative Mail option is toggled off by default.

### Fix double prefixing of navigation URLs #6460

1. Register a navigation menu item with a full URL or admin link.
```
	\Automattic\WooCommerce\Admin\Features\Navigation\Menu::add_plugin_item(
		array(
			'id'         => 'my-page,
			'title'      => 'My Page,
			'capability' => 'manage_woocommerce',
			'url'        => admin_url( 'my-page '),
		)
	);
```
2. Enable the navigation.
3. Check that the menu item is marked active when visiting that page.
4. Make sure old menu items are still correctly marked active.

### Fix summary number style regression on analytics reports #5913

- Go to Analytics
- See that the active (selected) tab is white, with a highlight above the tab.
- See that inactive tabs are a lighter shade of grey.

### Update payment card style on mobile #6413

- Using a small size screen, go to your WooCommerce -> Home -> Choose payment methods.
- See that the text descriptions for payment methods have a margin between them and the edge of the screen.

### Navigation: Correct error thrown when enabling #6462

1. Create a fresh store
2. Navigate to WooCommerce -> Settings -> Advanced Tab -> Features
3. Check the box to add the new navigation feature, and hit save
4. Ensure that the new navigation appears on the left as expected

### Remove Mollie promo note on install #6510

- If you do not currently have the Mollie note on your WooCommerce Admin home screen, you can add a test note with the correct name as follows:
    1. install the WooCommerce Admin Test Helper plugin [here](https://github.com/woocommerce/woocommerce-admin-test-helper)
    2. Go to the Admin notes tab
    3. Add an admin note with the name `wc-admin-effortless-payments-by-mollie`
    4. Go to the WCA home screen and verify that your test note is present
- The note is removed on a new version install, so either install an old version of WCA and upgrade to the current one, or trigger the install process manually:
    1. install the WCA test helper
    2. go to the Tools tab
    3. click the `Trigger WCA install` button

### Deprecate Onboarding::has_woocommerce_support #6401

-   Clear existing site transients. For example, by using the [Transients Manager](https://wordpress.org/plugins/transients-manager/) plugin, and pressing the "Delete all transients" button it provides.
-   Add any new theme to WordPress but **DO NOT** activate it.
-   Initialize the Onboarding Wizard.
-   See that the Themes step loads fast 😎
-   See that the new theme is listed in the Themes step.

### Set up tasks can now navigate back to the home screen #6397

1. With a fresh install of wc-admin and woocommerce, go to the home screen
2. Going to the homescreen redirects to the profile setup wizard, click "Skip setup store details" to return to the home screen
3. On the home screen you will see the setup task list. It has the heading "Get ready to start selling"

For each task in that list apart from "Store details":

1. Click the item
2. You should land on the setup task page
3. A title in the top left should reflect the original task name from the task list. e.g. "Add tax rates"
4. Clicking the chevron to the left of the title should take you back to the home screen

### Add Ireland to Square payment method #6559

1. Go to the store setup wizard `/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard`
1. Set up your store with Ireland as its country, and proceed until the `Business Details` step
1. In "Currently selling anywhere?" dropdown, select either:
    - Yes, in person at physical stores and/or events
    - Yes, on another platform and in person at physical stores and/or events
1. Finish the setup wizard, and go to payments task `/wp-admin/admin.php?page=wc-admin&task=payments`
1. Observe Square as a payment method option

### Add CES survey for search product, order, customer #6420
- Make sure tracking is enabled in settings.
- Delete the option `woocommerce_ces_shown_for_actions` to make sure CES prompt triggers when updating settings.
- Enable the logging of Tracks events to your browser dev console `localStorage.setItem( 'debug', 'wc-admin:tracks' );`

**Testing search on products:**
- Go to Products > All Products.
- Type in anything in search bar, click on "Search products".
- Observe CES prompt "How easy was it to use search?" is displayed.

**Testing search on orders:**
- Go to Orders > Orders.
- Type in anything in search bar, click on "Search orders".
- Observe CES prompt "How easy was it to use search?" is displayed.

**Testing search on customers:**
- Go to Customers.
- Type in anything in search bar, and press enter.
- Observe CES prompt "How easy was it to use search?" is displayed

### Add CES survey for importing products #6419
- Make sure tracking is enabled in settings.
- Delete the option `woocommerce_ces_shown_for_actions` to make sure CES prompt triggers when updating settings.
- Enable the logging of Tracks events to your browser dev console `localStorage.setItem( 'debug', 'wc-admin:tracks' );`
- If you don't have a product CSV export, you can obtain a sample CSV [here](https://gist.githubusercontent.com/ilyasfoo/507f9579531cf4bf50fe4c0e9c48a23d/raw/05e47e6731471464c757e893c3f2d8a9b89453c0/product-export.csv).
- Go to Products > All Products.
- Click on "Import".
- Upload CSV file and finish the import process.
- Observe CES prompt "How easy was it to import products?" is displayed.

### Add CES survey for adding product categories and tags #6418
- Make sure tracking is enabled in settings.
- Delete the option `woocommerce_ces_shown_for_actions` to make sure CES prompt triggers when updating settings.
- Enable the logging of Tracks events to your browser dev console `localStorage.setItem( 'debug', 'wc-admin:tracks' );`

**Testing product categories:**
- Go to Products > Categories.
- Add a new category.
- Observe CES prompt "How easy was it to add a product category?" is displayed.

**Testing product tags:**
- Go to Products > Tags.
- Add a new tag.
- Observe CES prompt "How easy was it to add a product tag?" is displayed.

**Testing product attributes:**
- Go to Products > Attributes.
- Add a new attribute.
- Observe CES prompt "How easy was it to add a product attribute?" is displayed.

# 2.1.3
### Fix a bug where the JetPack connection flow would not activate #6521

1. With a fresh install of wc-admin and woocommerce, go to the home screen
2. Going to the homescreen redirects to the profile setup wizard
3. The first step is "Store details" choose United States (any state) for country and fill in the other details with test data.
4. Click "continue", you should be taken to the "Industry" step.
5. In the "Industry" step check the "Food and Drink" option only. Click "continue"
6. In the "Product Type" step choose any value and click "continue"
7. You should arrive at the "Business details" step which provides 2 tabs: "Business details" and "Free features". In the "Business Details" tab fill out the dropdowns with any values. Click "continue".
8. In the "Free features" step expand the list of extensions to install by clicking the arrow to the right of "Add recommended business features to my site".
9. Uncheck all the extensions except for "Enhance speed and security with Jetpack"
10. Click "continue", the plugin will be installed and you should arrive at the theme step.
11. Click "Continue with my active theme"
12. After finishing the wizard, this should redirect you to the "Jetpack" setup connection flow. (You should not be redirected straight to the homescreen).

### Update target audience of business feature step #6508

Scenario #1

1. With a fresh install of wc-admin and woocommerce, go to the home screen, which starts the onboarding wizard
2. Fill out the store details with a canadian address (addr: 4428 Blanshard, country/region: Canada -- British Columbia, city: Victoria, postcode: V8W 2H9)
3. Click continue and select **Fashion, apparel, and accessories**, continue, and select **Physical products**, and continue.
4. The business details tab should show a **Business details** tab, and a **Free features** tab (disabled at first)
     - There should only be dropdowns visible on the **Business details** step (no checkboxes)
5. Select **1-10** for the first dropdown, and **No** for the second, and click Continue.
6. Click on the expansion icon for the **Add recommended business features to my site**
7. It should list 7 features, including **WooCommerce Payments** (top one)
     - Note down the selected features, for step 10
8. Click continue, and select your theme, after it should redirect to the home screen (showing the welcome modal, you can step through this).
9. The home screen task list should include a **Set up WooCommerce Payments** task, and there should also be a **Set up additional payment providers** inbox card displayed (below the task list).
10. Go to **Plugins > installed Plugins**, check if the selected plugin features selected in step 7 are installed and activated.

Scenario #2

1. With a fresh install of wc-admin and woocommerce, go to the home screen, which starts the onboarding wizard
2. Fill out the store details with a spanish address (addr: C/ Benito Guinea 52, country/region: Spain -- Barcelona, city: Canet de Mar, postcode: 08360)
3. Click continue and select **Fashion, apparel, and accessories**, continue, and select **Physical products**, and continue.
4. On the business details tab select **1-10** for the first dropdown, and **No** for the second.
     - After filling the dropdowns it should show several checkboxes with plugins (Facebook, mailchimp, creative mail, google ads)
     - Note which ones you kept selected (you can unselect one or two)
5. Click continue, and select your theme, it should show the **WooCommerce Shipping & Tax** step after, you can click **No thanks**.
6. You will be redirected to the home screen, showing the welcome modal, you can step through this.
7. The task list should show the **Choose payment methods** task, and the **Set up additional payment providers** inbox card should not be present.
8. Click on the **Choose payment methods** task, it should not be displaying the **Woocommerce Payments** option.
9. Go to **Plugins > installed Plugins**, check if the selected plugin features selected in step 4 are installed and activated.

## 2.1.2

### Add Guards to "Deactivate Plugin" Note Handlers #6532

#### Test incompatible WooCommerce version

-   Install and activate Woocommerce 4.7
-   See that the Woocommerce Admin plugin is deactivated.
-   Add the Deactivate Plugin note via SQL.

```
INSERT INTO `wp_wc_admin_notes` (`name`, `type`, `locale`, `title`, `content`, `content_data`, `status`, `source`, `date_created`, `date_reminder`, `is_snoozable`, `layout`, `image`, `is_deleted`, `icon`) VALUES ( 'wc-admin-deactivate-plugin', 'info', 'en_US', 'Deactivate old WooCommerce Admin version', 'Your current version of WooCommerce Admin is outdated and a newer version is included with WooCommerce.  We recommend deactivating the plugin and using the stable version included with WooCommerce.', '{}', 'unactioned', 'woocommerce-admin', '2021-03-08 01:26:44', NULL, 0, 'plain', '', 0, 'info');
```

-   See that the note is in the inbox
-   Activate the Woocommerce Admin plugin.
-   See that Woocommerce Admin immediately de-activates without a fatal error.
-   See that the note remains in inbox

#### Test compatible WooCommerce version

-   Deactivate the Woocommerce Admin plugin.
-   Install and activate the latest Woocommerce version.
-   Add the Deactivate Plugin note via SQL.

```
INSERT INTO `wp_wc_admin_notes` (`name`, `type`, `locale`, `title`, `content`, `content_data`, `status`, `source`, `date_created`, `date_reminder`, `is_snoozable`, `layout`, `image`, `is_deleted`, `icon`) VALUES ( 'wc-admin-deactivate-plugin', 'info', 'en_US', 'Deactivate old WooCommerce Admin version', 'Your current version of WooCommerce Admin is outdated and a newer version is included with WooCommerce.  We recommend deactivating the plugin and using the stable version included with WooCommerce.', '{}', 'unactioned', 'woocommerce-admin', '2021-03-08 01:26:44', NULL, 0, 'plain', '', 0, 'info');
```

-   Activate the Woocommerce Admin plugin.
-   See that note is **not** in the inbox
-   Add the Deactivate Plugin note via SQL.

```
INSERT INTO `wp_wc_admin_notes` (`name`, `type`, `locale`, `title`, `content`, `content_data`, `status`, `source`, `date_created`, `date_reminder`, `is_snoozable`, `layout`, `image`, `is_deleted`, `icon`) VALUES ( 'wc-admin-deactivate-plugin', 'info', 'en_US', 'Deactivate old WooCommerce Admin version', 'Your current version of WooCommerce Admin is outdated and a newer version is included with WooCommerce.  We recommend deactivating the plugin and using the stable version included with WooCommerce.', '{}', 'unactioned', 'woocommerce-admin', '2021-03-08 01:26:44', NULL, 0, 'plain', '', 0, 'info');
```

-   De-activate the Woocommerce Admin plugin.
-   See that note is **not** in the inbox

## 2.1.0

### Correct the Klarna slug #6440

1. Set up a new store with a UK address so that Klarna available as a payment processor
2. Go to the "Choose payment methods" task item
3. Set up Klarna. The plugin will install.
4. Click Continue. It should take you back to the payment methods page - previously it wasn't doing anything but a console error was displayed.

### Navigation: Reset submenu before making Flyout #6396

-   Download and activate the MailChimp plugin.
-   Turn on Navigation at Settings > Advanced > Features
-   Return to the WP dashboard
-   Hover over WooCommerce and see the flyout menu appear
-   MailChimp should not be included.

### Email notes now are turned off by default #6324

-   Create a zip for testing with `npm run zip:test`.
-   Create a `jurassic.ninja` instance.
-   Upload the plugin and activate it.
-   Update the installation date (we need a 10-day old store). You can do it with an SQL statement like this (using the WP phpMyAdmin plugin):

```
UPDATE `wp_options` SET `option_value`=UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 10 day)) WHERE `option_name` = 'woocommerce_admin_install_timestamp';
```

-   Confirm that `woocommerce_merchant_email_notifications` was not set before by `core` with a SQL statement like:

```
DELETE FROM `wp_options` WHERE `wp_options`.`option_name` = 'woocommerce_merchant_email_notifications';
```

or with wp-cli:

```
wp option delete 'woocommerce_merchant_email_notifications';
```

-   Run the cron job `wc_admin_daily` (this tool can help [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/)).
    -   Go to **Tools > Cron Events** and scroll down to the `wc_admin_daily`.
    -   Hover over the item and click `Edit` change the **Next Run** to `Now` and click `Update Event`.
    -   It will redirect you to the cron event list, and `wc_admin_daily` should be near the top, if you wait 10 seconds and refresh the page the `wc_admin_daily` should be near the bottom again, this means it has been run, and scheduled again to run tomorrow.
-   You should have not received an email note.
-   Verify the note `wc-admin-add-first-product-note` was added in the DB and its `status` is `unactioned`. You can use a statement like this:

```
SELECT `status` FROM `wp_wc_admin_notes` WHERE `name` = 'wc-admin-add-first-product-note';
```

or with wp-cli:

```
wp db query 'SELECT status FROM wp_wc_admin_notes WHERE name = "wc-admin-add-first-product-note"' --skip-column-names
```

-   Run the cron again.
-   The note's status should continue being `unactioned`.

### Refactor menu item mapping and sorting #6382

1. Enable the new navigation under WooCommerce -> Settings -> Advanced -> Features.
2. Navigate to a WooCommerce page.
3. Make sure all items and categories continue to work as expected.
4. Activate multiple extensions that register WooCommerce extension categories. (e.g., WooCommerce Bookings and WooCommerce Payments).
5. Favorite and unfavorite menu items.
6. Make sure the menu item order is correct after unfavoriting.
7. Create a user with permissions to see some but not all registered WooCommerce pages.
8. Check that a user without permission to access a menu item cannot see said menu item.

### Fixed associated Order Number for refunds #6428

1. In a store with refunded orders.
2. Go to `Analytics` > `Orders`
3. Set the `Date Range` filter in order to cover the refunded order date.
4. Verify that now the associated order number and the related products are visible.

### Remove CES actions for adding and editing a product and editing an order #6355

1. Add a product. The customer effort score survey should not appear.
2. Edit a product. The customer effort score survey should not appear.
3. Edit an order. The customer effort score survey should not appear.

### Center the activity panel #6289

1. Narrow your screen to <782px
2. Go to WooCommerce home and orders page
3. Click on 'w' button, see that the activity panel renders as expected.

### Make sure that industry is defined in payment methods #6281

-   Start a new store, and skip the initial onboarding flow, there is a button `Skip store details` at the bottom
-   Load the `Set up payments` task, the payment options should load correctly.

### Add a new note with a link to the downloadable product doc #6277

1. Make sure your store does not have any download products.
2. Install WP Crontrol plugin.
3. Add a new download product.
4. Navigate to Tools -> Cron Events and run `wc_admin_daily`
5. Navigate to WooCommerce -> Home and confirm that the note has been added.

### Onboarding - Fixed "Business Details" error #6271

-   Check out this branch.
-   Go to the "Industry" step in the OBW and select `Food and drink`.
-   Go to the "Business Details" step and press `Free features`.
-   Press `Continue`.
-   It should work.
-   Try also selecting and unselecting some checkboxes before pressing `Continue`.

### Change `siteUrl` to `homeUrl` on navigation site title #6240

-   Go to WP settings and set the home page to My account
-   Go to WC settings and use the new navigation feature
-   Click on the header site title My Site and see that the page direct to My account

### Refactor panel with withFocusOutside #6233

-   Go to WooCommerce home page
-   Click on Display and Help button back and forth, check that the popover and the panel close as expected.
-   Check that the setup store tab continues to work.

### Move capability checks to client #6365

1. Create various non-admin users with custom capabilities. Make sure to not include the `view_woocommerce_reports` for at least one role. https://wordpress.org/plugins/leira-roles/
2. Log in as the non-admin users.
3. Check that the correct menu items are shown.
4. Check that there aren't items shown to the user they should not be able to use or interact with.
5. Enable the new navigation under WooCommerce -> Settings -> Advanced -> Features.
6. Check that the users are able to see the new navigation menu.
7. Click on various tabs in the activity panel.
8. Make sure the tabs work as expected.
9. Make sure that users without the `manage_woocommerce` permission are not able to see the "Store Setup" tab.
10. With a user that can `manage_woocommerce`, navigate to the homepage via URL and make sure the homescreen is shown. `/wp-admin/admin.php?page=wc-admin`
11. With a user that cannot `view_woocommerce_reports` make sure navigating to an analytics report does not work. `/wp-admin/admin.php?page=wc-admin&path=/analytics/overview`

### Add CES track settings tab on updating settings #6368

-   Make sure tracking is enabled in settings:

```
/wp-admin/admin.php?page=wc-settings&tab=advanced&section=woocommerce_com
```

-   Delete the option `woocommerce_ces_shown_for_actions` to make sure CES prompt triggers when updating settings.
-   Enable the logging of Tracks events to your browser dev console:

```
localStorage.setItem( 'debug', 'wc-admin:tracks' );
```

-   Go to WooCommerce > Settings, and select a top-level tab such as Products, Shipping, etc.
-   Click on `Save changes`.
-   Observe in developer console, `wcadmin_ces_snackbar_view` is logged when CES prompt is displayed.
-   In the event props, it should have a new `settings_area` key followed by the value of the settings tab you have selected.

### Add navigation intro modal #6367

1. Visit the homescreen and dismiss the original welcome modal if you haven't already.
2. Enable the new navigation under WooCommerce -> Settings -> Advanced -> Features. (This will also require opting into tracking).
3. Visit the WooCommerce Admin homescreen.
4. Note the new modal.
5. Check that pagination works as expected and modal styling is as expected.
6. Dismiss the modal.
7. Refresh the page to verify the modal does not reappear.
8. On a new site, enable the navigation before visiting the homescreen.
9. Navigate to the homescreen.
10. Note the welcome modal is shown and the navigation intro modal is not shown.
11. Refresh the page and note the nav intro modal was dismissed and never shown.

## 2.0.0

### Add the Mollie payment provider setup task #6257

-   You'll need a site that has the setup task list visible. Complete the OBW and make sure you're in a Mollie supported country (Such as United Kingdom).
-   Go to the setup payments task
-   Mollie should be listed as an option
-   Click "Set up" button on the Mollie task
-   It should install and activate the mollie payments plugin
-   The connect step should provide links to create an account or go straight to Mollie settings. (test both links work)
-   Click "continue"
-   You should arrive back at the payment provider list

### Fix: allow for more terms to be shown for product attributes in the Analytics orders report. #5868

1. Create a product attribute
2. Give the attribute more than 10 terms
3. Go to Analytics -> Orders
4. Add an attribute filter to the list, choose your attribute.
5. Go to the second input field and click on it, a result set of all your terms should appear

### Add: new inbox message - Getting started in Ecommerce - watch this webinar. #6086

-   First you'll need to make sure you meet the criteria for the note:
    1. obw is done
    2. revenue is between 0-2500
    3. do not check "setting up for client" in obw
    4. store should have no products
-   Next you need to install WP Crontrol, go to its list of cron events and click "run now" on "wc_admin_daily"
-   Confirm the new note is displayed and that the content matches that specified below:
    -   Title: Getting Started in eCommerce - webinar
    -   Copy: We want to make eCommerce and this process of getting started as easy as possible for you. Watch this webinar to get tips on how to have our store up and running in a breeze.
    -   CTA leads to: https://youtu.be/V_2XtCOyZ7o
    -   CTA label: Watch the webinar

### Update: store deprecation welcome modal support doc link #6094

-   Starting with a fresh store (or by deleting the woocommerce_task_list_welcome_modal_dismissed option), visit /wp-admin/admin.php?page=wc-admin. You should see the standard welcome modal.
-   Add &from-calypso to the URL. You should see the Calypso welcome modal.
-   Notice "Learn more" links to https://wordpress.com/support/new-woocommerce-experience-on-wordpress-dot-com/

### Enhancement: Allowing users to create products by selecting a template. #5892

-   Load new store and finish the Wizard
-   Go to the `Add my products` task
-   Click the `Start with a template` option, and select either a physical, digital, variable product
-   Once you click `Go`, it should redirect you to an edit page of the new post, with the data from the sample-data.csv (mentioned in the original ticket). Only the title is missing, as it is saved as auto-draft.
-   You should be able to save the new product as draft or publish it.
-   You should be able to exit without making any changes, and not having the product show up as draft in your product list.
    -   Create new product from template
    -   Wait until redirected
    -   Without saving go to the **Products > all products** page, the new product should not be displayed.

### Update: Homescreen layout, moving Inbox panel for better interaction. #6122

-   Create a new woo store, and finish the onboarding wizard.
-   Go to the home screen, and make sure the panels follow this order:
-   Two column:
    -   Left column: store setup and/or management tasks + inbox panel
    -   Right column: stats overview + store management shortcuts (only shows when setup tasks is hidden)
-   Single column:
    -   store setup tasks, inbox panel, stats overview, store management links (only visible when setup tasks is hidden).
-   Hide the setup tasks list, and see if the store management links show up in the right place.

### Enhancement: Use the new Paypal payments plugin for onboarding. #6261

-   Create new woo store, and finish the onboarding wizard
-   Go to the home screen, and click the **Set up payments** task. **Paypal Payments** option should be listed as an option, with a **Set up** button.
-   Click **Set up** on the Paypal plugin.
-   It should automatically start the **Install** step, and install and enable the [Paypal Payments](https://woocommerce.com/products/woocommerce-paypal-payments/) plugin.
-   For Paypal Payments version greater then `1.1.0`.
    -   For the second step it should show a `Connect` button
    -   Click on **Connect** and a window should popup for Paypal, follow this until finished. The last button is - Go back to Woocommerce Developers
    -   Once done, the page should reload, and briefly show the setup screen again, it should then finish and go back to the payment list.
    -   Once on the payment list, the `Set up` button should be gone, and instead show a toggle, that is set to enabled.
        -   The enable/disable button should be correctly reflected in the Woocommerce payment settings screen as well.
-   For Paypal Payments version `1.1.0` and below
    -   For the second step it will show the manual fields (merchant email, merchant id, client id, client secret).
    -   Check if the help links work below, they should help with finding the above credentials.
        -   If you have a business account set up, you can find the credentials in these two places
        -   [Get live app credentials](https://developer.paypal.com/developer/applications/)
        -   [Merchant id](https://www.paypal.com/businessmanage/account/aboutBusiness)
    -   Fill in the credentials and click **Proceed**, this should succeed and redirect you to the Payment options list
    -   The **Set up** button should be replaced by a toggle, that is set to enabled.
        -   The enable/disable button should be correctly reflected in the Woocommerce payment settings screen as well.

Once everything is configured and enabled do a client test

-   Make sure you have added a product and store homescreen (Finish the **Personalize my store** task)
-   Navigate to one of the products and add it to the cart
-   Click **go to checkout**
-   Paypal should be one of the options to pay
-   Filling in your billing/shipping info then click pay with **Paypal**
-   The paypal pay window should pop up correctly without errors.
