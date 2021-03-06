#!/usr/bin/env bash

source /etc/profile.d/phpenv.sh

ln --symbolic composer.lock.dist composer.lock

if [[ "$TRAVIS_PHP_VERSION" == "5.2" ]];
then
    phpenv shell 5.3
    composer self-update
    composer install --no-ansi --no-interaction --no-progress --optimize-autoloader --prefer-dist
    phpenv shell --unset
else
    composer self-update
    composer install --no-ansi --no-interaction --no-progress --optimize-autoloader --prefer-dist
fi
