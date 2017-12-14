#!/usr/bin/env bash
export PHP_IDE_CONFIG="serverName=php_docker_example.test"
export XDEBUG_CONFIG="idekey=PHPSTORM"
php -dxdebug.remote_enable=1 -dxdebug.remote_autostart=1 $1