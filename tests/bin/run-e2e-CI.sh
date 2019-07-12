#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then

	# Lets build using grunt
	grunt e2e-build

	WP_SITE_URL="http://localhost:8080"

	# Start xvfb to run the tests
	export BASE_URL="$WP_SITE_URL"
	export DISPLAY=:99.0
	sh -e /etc/init.d/xvfb start
	sleep 3

	# Run the tests
	npm test
fi
