#!/bin/bash

yellow_echo () { echo "\033[33m\033[1m$1\033[0m"; }

# drop database
yellow_echo "[DB-Drop]: delete databases..."
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:drop --env=test --force
