#!/usr/bin/env bash

pushd .

CURRENT_FOLDER=$(dirname $(readlink -f "$0"))
#PARENT_FOLDER=$(dirname $(dirname $(readlink -f "$0")))

#cd "$PARENT_FOLDER"
#cd "$PARENT_FOLDER"

#DOCKER_FULL_COMMAND="docker-compose -f $PARENT_FOLDER/docker-compose.yml -f $CURRENT_FOLDER/docker-compose-override.yml up"

#php "$CURRENT_FOLDER/docker_run.php" "$@"
#php -dxdebug.remote_enable=1 -dxdebug.remote_autostart=1 "$CURRENT_FOLDER/docker_run.php" "$@"
echo
php "$CURRENT_FOLDER/docker_run.php" "$@" | sh
popd