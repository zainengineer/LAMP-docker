#!/usr/bin/env bash

script_path=$( cd "$(dirname "${BASH_SOURCE}")" ; pwd -P )
root_path=$(dirname "$script_path");

bash "$root_path/LAMP-docker/builder/docker_run.sh" -o 1 -c 1
