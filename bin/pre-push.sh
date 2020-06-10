#!/bin/bash

PROTECTED_BRANCH="master"
REMOTE_REF=$(echo $HUSKY_GIT_STDIN | cut -d " " -f 3)

if [[ "$REMOTE_REF" == *"$PROTECTED_BRANCH"* ]]; then
	printf "$(tput setaf 3)You're about to push to master, is that what you intended? [y/N]: $(tput sgr0)"
	read -r PROCEED < /dev/tty
	echo

	if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" = "y" ]; then
		echo "$(tput setaf 2)Brace yourself! Pushing to the master branch...$(tput sgr0)"
		echo
		exit 0
	fi

	echo "$(tput setaf 2)Push to master cancelled!$(tput sgr0)"
	echo
	exit 1
fi
