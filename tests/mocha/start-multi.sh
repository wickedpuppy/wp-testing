#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Define vars
HERE=$(dirname $(realpath $0))
REPO_ROOT=$(realpath $HERE/../..)

# Prepare environment
cd $REPO_ROOT
sudo WP_T_SERVER=http://wpti.dev tests/integration-environment/create.sh
cd $HERE
export PATH=$PATH:./node_modules/.bin/
export TZ="UTC"
export WP_T_MULTISITE=1

# Reset shared cookies
rm -f /tmp/cookies.*.txt

# Activate plugin and setup admin
export WP_T_SERVER=http://wpti.dev
mocha-casperjs test/0-multisite.js
# activate plugin on 2nd
# activate plugin on 1st
