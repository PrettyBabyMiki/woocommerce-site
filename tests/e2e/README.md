# WooCommerce End to End Tests

Automated end-to-end tests for WooCommerce.

## Table of contents

- [Pre-requisites](#pre-requisites)
  - [Install Node.js](#install-node.js)
  - [Install NVM](#install-nvm)
  - [Install Docker](#install-docker)
- [Configuration](#configuration)
  - [Test Environment](#test-environment)
  - [Test Variables](#test-variables)
  - [Jest test sequencer](#jest-test-sequencer)
  - [Chromium Download](#chromium-download)
- [Running tests](#running-tests)
  - [Prep work for running tests](#prep-work-for-running-tests)
  - [How to run tests in headless mode](#how-to-run-tests-in-headless-mode) 
  - [How to run tests in non-headless mode](#how-to-run-tests-in-non-headless-mode)
  - [How to run tests in debug mode](#how-to-run-tests-in-debug-mode)
  - [How to run an individual test](#how-to-run-an-individual-test)
  - [How to skip tests](#how-to-skip-tests)
  - [How to run tests using custom WordPress, PHP and MariaDB versions](#how-to-run-tests-using-custom-wordpress,-php-and-mariadb-versions)
- [Guide for writing e2e tests](#guide-for-writing-e2e-tests) 
  - [Tools for writing tests](#tools-for-writing-tests)
  - [Creating test structure](#creating-test-structure)
  - [Writing the test](#writing-the-test)
  - [Best practices](#best-practices)
- [Debugging tests](#debugging-tests)

## Pre-requisites

### Install Node.js

Follow [instructions on the node.js site](https://nodejs.org/en/download/) to install Node.js. 

### Install NVM

Follow instructions in the [NVM repository](https://github.com/nvm-sh/nvm) to install NVM. 

### Install Docker

Install Docker Desktop if you don't have it installed:

- [Docker Desktop for Mac](https://docs.docker.com/docker-for-mac/install/)
- [Docker Desktop for Windows](https://docs.docker.com/docker-for-windows/install/)

Once installed, you should see `Docker Desktop is running` message with the green light next to it indicating that everything is working as expected.

Note, that if you install docker through other methods such as homebrew, for example, your steps to set it up will be different. The commands listed in steps below may also vary.

## Configuration

This section explains how e2e tests are working behind the scenes. These are not instructions on how to build environment for running e2e tests and run them. If you are looking for instructions on how to run e2e tests, jump to [Running tests](#running-tests). 

### Test Environment

We recommend using Docker for running tests locally in order for the test environment to match the setup on Travis CI (where Docker is also used for running tests). [An official WordPress Docker image](https://github.com/docker-library/docs/blob/master/wordpress/README.md) is used to build the site. Once the site using the WP Docker image is built, the current WooCommerce dev branch is being copied to the `plugins` folder of that newly built test site. No WooCommerce Docker image is being built or needed.

### Test Variables

The jest test sequencer uses the following test variables:

```
{
  "url": "http://localhost:8084/",
  "users": {
    "admin": {
      "username": "admin",
      "password": "password"
    },
    "customer": {
      "username": "customer",
      "password": "password"
    }
  }
}
```

If you need to modify the port for your local test environment (eg. port is already in use), copy `tests/e2e/env/config/default.json` to `tests/e2e/config/default.json` and edit that copy. Only edit this file while your test container is `down`.

### Jest test sequencer

[Jest](https://jestjs.io/) is being used to run e2e tests. By default, jest runs tests ordered by the time it takes to run the test (the test that takes longer to run will be run first, the test that takes less time to run will run last). Jest sequencer introduces tools that can be used to specify the order in which the tests are being run. In our case, they are being run in alphabetical order of the directories where tests are located. This way, tests in the new directory `activate-and-setup` will run first. 

Setup Wizard e2e test (located in `activate-and-setup` directory) will run before all other tests. This will allow making sure that WooCommerce is activated on the site and for the setup wizard to be completed on a brand new install of WooCommerce. 

### Chromium Download

By default, `Puppeteer` downloads the `Chromium` package every time you run `npm install` or `npm update`. To disable that download add the following to your `.bash_profile` or `.zshrc` (whichever you use):

```shell script
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
```

Puppeteer will still automatically download Chromium when needed.

## Running tests

### Prep work for running tests

- `cd` to the WooCommerce plugin folder

- `git checkout master` or checkout the branch where you need to run tests 

- Run `nvm use`

- Run `npm install`

- Run `composer install --no-dev`

- Run `npm run build:assets`

- Run `npm install jest --global`

- Run `npm run docker:up` - it will build the test site using Docker.  

- Run `docker ps` - to confirm that the Docker containers were built and running. You should see the log that looks similar to below indicating that everything had been built as expected:

```
CONTAINER ID        IMAGE               COMMAND                  CREATED             STATUS              PORTS                  NAMES
c380e1964506        env_wordpress-cli   "entrypoint.sh"          7 seconds ago       Up 5 seconds                               woocommerce_wordpress-cli
2ab8e8439e9f        wordpress:5.5.1     "docker-entrypoint.s…"   8 seconds ago       Up 7 seconds        0.0.0.0:8084->80/tcp   woocommerce_wordpress-www
4c1e3f2a49db        mariadb:10.5.5      "docker-entrypoint.s…"   10 seconds ago      Up 8 seconds        3306/tcp               woocommerce_db
```

Note that by default, Docker will download the latest images available for WordPress, PHP and MariaDB. In the example above, you can see that WordPress 5.5.1 and MariaDB 10.5.5 were used. 

See [How to run tests using custom WordPress, PHP and MariaDB versions](#how-to-run-tests-using-custom-wordpress,-php-and-mariadb-versions) if you'd like to use different versions.  

- Navigate to `http://localhost:8084/`

If everything went well, you should be able to access the site. If you changed the port to something other than `8084` as per [Test Variables](#test-variables) section, use the appropriate port to access your site. 

As noted in [Test Variables](#test-variables) section, use the following Admin user details to login:

```
Username: admin
PW: password
```

- Run `npm run docker:down` when you are done with running e2e tests or when making any changes to test suite.

Note that running `npm run docker:down` and then `npm run docker:up` re-initializes the test container.

### How to run tests in headless mode

To run e2e tests in headless mode use the following command:

```bash
npm run test:e2e
```

### How to run tests in non-headless mode

Tests are run headless by default. However, sometimes it's useful to observe the browser while running tests. To do so, you can run tests in a non-headless (dev) mode:

```bash
npm run test:e2e-dev
```

The dev mode also enables SlowMo mode. SlowMo slows down Puppeteer’s operations so we can better see what is happening in the browser. 

By default, SlowMo mode is set to slow down running of tests by 50 milliseconds. If you'd like to override it and have the tests run faster or slower in the `-dev` mode, pass `PUPPETEER_SLOWMO` variable when running tests as shown below: 

```
PUPPETEER_SLOWMO=10 npm run test:e2e-dev
```

The faster you want the tests to run, the lower the value should be of `PUPPETEER_SLOWMO` should be. 

For example:

- `PUPPETEER_SLOWMO=10` - will run tests faster
- `PUPPETEER_SLOWMO=70` - will run tests slower

### How to run tests in debug mode

Tests are run headless by default. While writing tests it may be useful to have the debugger loaded while running a test in non-headless mode. To run tests in debug mode:
            
```bash
npm run test:e2e-debug
```

When all tests have been completed the debugger is left active. Control doesn't return to the command line until the debugger is closed. Otherwise, debug mode functions the same as non-headless mode.

### How to run an individual test

To run an individual test, use the direct path to the spec. For example:

```bash
npm run test:e2e ./tests/e2e/specs/wp-admin/test-create-order.js
``` 

### How to skip tests

To skip the tests, use `.only` in the relevant test entry to specify the tests that you do want to run. 

For example, in order to skip Setup Wizard tests, add `.only` to the login and activation tests as follows in the `setup-wizard.test.js`:

```
it.only( 'Can login', async () => {}
```

```
it.only( 'Can make sure WooCommerce is activated. If not, activate it', async () => {}
```

As a result, when you run `setup-wizard.test.js`, only the login and activate tests will run. The rest will be skipped. You should see the following in the terminal:

```
 PASS  tests/e2e/specs/activate-and-setup/setup-wizard.test.js (11.927s)
  Store owner can login and make sure WooCommerce is activated
    ✓ Can login (7189ms)
    ✓ Can make sure WooCommerce is activated. If not, activate it (1187ms)
  Store owner can go through store Setup Wizard
    ○ skipped Can start Setup Wizard
    ○ skipped Can fill out Store setup details
    ○ skipped Can fill out Payment details
    ○ skipped Can fill out Shipping details
    ○ skipped Can fill out Recommended details
    ○ skipped Can skip Activate Jetpack section
    ○ skipped Can finish Setup Wizard - Ready! section
  Store owner can finish initial store setup
    ○ skipped Can enable tax rates and calculations
    ○ skipped Can configure permalink settings
```

You can also use `.skip` in the same fashion. For example:

```
it.skip( 'Can start Setup Wizard', async () => {}
```

Finally, you can apply both `.only` and `.skip` to `describe` part of the test:

```
describe.skip( 'Store owner can go through store Setup Wizard', () => {}
```

### How to run tests using custom WordPress, PHP and MariaDB versions

The following variables can be used to specify the versions of WordPress, PHP and MariaDB that you'd like to use to built your test site with Docker:

- `WP_VERSION`
- `TRAVIS_PHP_VERSION`
- `TRAVIS_MARIADB_VERSION`  

The full command to build the site will look as follows:

```
TRAVIS_MARIADB_VERSION=10.5.3 TRAVIS_PHP_VERSION=7.4.5 WP_VERSION=5.4.1 npm run docker:up
```

## Guide for writing e2e tests

### Tools for writing tests

We use the following tools to write e2e tests:

- [Puppeteer](https://github.com/GoogleChrome/puppeteer) – a Node library which provides a high-level API to control Chrome or Chromium over the DevTools Protocol
- [jest-puppeteer](https://github.com/smooth-code/jest-puppeteer) – provides all required configuration to run tests using Puppeteer
- [expect-puppeteer](https://github.com/smooth-code/jest-puppeteer/tree/master/packages/expect-puppeteer) – assertion library for Puppeteer

In the WooCommerce Core repository the tests are kept in `tests/e2e/core-tests/specs/` folder. However, if you are writing tests in your own project using WooCommerce Core e2e packages, the tests should be located in `tests/e2e/specs/` folder.

The following packages are used to write tests:

- `@automattic/puppeteer-utils` - utilities and configuration for running puppeteer against WordPress. See details in the [package's repository](https://github.com/Automattic/puppeteer-utils).
- `@woocommerce/e2e-utils` - this package contains utilities to simplify writing e2e tests specific to WooCommmerce. See details in the [package's repository](https://github.com/woocommerce/woocommerce/tree/master/tests/e2e/utils).

### Creating test structure

It is a good practice to start working on the test by identifying what needs to be tested on the higher and lower levels. For example, if you are writing a test to verify that merchant can create virtual product, the overview of the test will be as follows:

- Merchant can create virtual product
  - Merchant can log in
  - Merchant can create virtual product
  - Merchant can verify that virtual product was created   
  
Once you identify the structure of the test, you can move on to writing it. 

### Writing the test

The structure of the test serves as a skeleton for the test itself. You can turn it into a test by using `describe()` and `it()` methods of Jest:

- [`describe()`](https://jestjs.io/docs/en/api#describename-fn) - creates a block that groups together several related tests;
- [`it()`](https://jestjs.io/docs/en/api#testname-fn-timeout) - actual method that runs the test. 

Based on our example, the test skeleton would look as follows:

```
describe( 'Merchant can create virtual product', () => {
	it( 'merchant can log in', async () => {

	} );

	it( 'merchant can create virtual product', async () => {

	} );

	it( 'merchant can verify that virtual product was created', async () => {

	} );
} );
```

Next, you can start filling up each section with relevant functions (test building blocks). Note, that we have the `@woocommerce/e2e-utils` package where many reusable helper functions can be found for writing tests. For example, `flows.js` of `@woocommerce/e2e-utils` package contains `StoreOwnerFlow` object that has `login` method. As a result, in the test it can be used as `await StoreOwnerFlow.login();` so the first `it()` section of the test will become:

```
it( 'merchant can log in', async () => {
      await StoreOwnerFlow.login();
	} );
```

Moving to the next section where we need to actually create a product. You will find that we have a reusable function such as `createSimpleProduct()` in the `components.js` of `@woocommerce/e2e-utils` package. However, note that this function should not be used for this test because the way simple product is being created in this function is by using WooCommerce REST API. Because this is not how the merchant would typically create a virtual product, we would need to test it by writing actual steps for creating a product in the test. 

`createSimpleProduct()` should be used in tests where you need to test something else than creating a simple product. In other words, this function exists in order to quickly fill the site with test data required for running tests. For example, if you want to write a test that will verify that shopper can place a product to the cart on the site, you can use `createSimpleProduct()` to create a product to test the cart. 

Because `createSimpleProduct()` can't be used in the case of our example test, we'd need to navigate to the page where the user would usually create a product. To do that, there is `openNewProduct()` function of the `StoreOwnerFlow` object that we already used above. As a result, that part of the test will look as follows:

```
it( 'merchant can create virtual product', async () => {
      await StoreOwnerFlow.openNewProduct();
	} );
```

You would then continue writing the test using utilities where possible. 

Make sure to utilize the functions of the `@automattic/puppeteer-utils` package where possible. For example, if you need to wait for certain element to be ready to be clicked on and then click on it, you can use `waitAndClick()` function:

```
await waitAndClick( page, '#selector' ); 
```

### Best practices

- It is best to keep the tests inside `describe()` block granular as it helps to debug the test if it fails. When the test is done running, you will see the result along with the breakdown of how each of the test sections performed. If one of the tests within `describe()` block fails, it will be shown as follows:

```
FAIL ../specs/front-end/front-end-my-account.test.js (9.219s)
  My account page
    ✓ allows customer to login (2924ms)
    ✓ allows customer to see orders (1083ms)
    x allows customer to see downloads (887ms)
    ✓ allows customer to see addresses (1161ms)
    ✓ allows customer to see account details (1066ms)
```

In the example above, you can see that `allows customer to see downloads` part of the test failed and can start looking at it right away. Without steps the test goes through being detailed, it is more difficult to debug it. 

## Debugging tests 

For Puppeteer debugging, follow [Google's documentation](https://developers.google.com/web/tools/puppeteer/debugging).   
