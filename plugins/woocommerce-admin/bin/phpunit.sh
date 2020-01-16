#!/usr/bin/env bash
WORKING_DIR="$PWD"
cd "$WP_CORE_DIR/wp-content/plugins/woocommerce-admin/"
phpunit --version
if [[ {$RUN_RANDOM} == 1 ]]; then
	./vendor/bin/phpunit -c phpunit.xml.dist --order-by=random
else
	phpunit -c phpunit.xml.dist
fi
TEST_RESULT=$?
cd "$WORKING_DIR"
exit $TEST_RESULT
