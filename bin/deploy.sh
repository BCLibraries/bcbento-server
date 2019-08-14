#!/usr/bin/env bash

# Branch to pull from
BRANCH=${1:-master}

# Repository to pull from
REPO=https://github.com/BCLibraries/bcbento-server

# Application directory
APP_BASE=/apps/bcbento-server

# The new release
RIGHT_NOW=`date +%Y-%m-%d-%H%M%S`
RELEASES_DIR=${APP_BASE}/releases
NEW_RELEASE=${RELEASES_DIR}/${RIGHT_NOW}-${BRANCH}

# Shared directories
SHARED_DIR=${APP_BASE}/shared
LOG_DIR=${SHARED_DIR}/log

# Pull the latest commit from master
git clone ${REPO} -b ${BRANCH} ${NEW_RELEASE}
cd ${NEW_RELEASE}
find .git -type f -exec chmod 644 {} \;

# Load the local environment variables
cp ${SHARED_DIR}/.env.local ${NEW_RELEASE}

# Install
APP_ENV=prod composer install --no-dev --optimize-autoloader

# Re-use existing log
rm -r ${NEW_RELEASE}/var/log
ln -s ${LOG_DIR} ${NEW_RELEASE}/var/log

# Replace old version with new version
unlink ${APP_BASE}/current
ln -s ${NEW_RELEASE} ${APP_BASE}/current

# Keep the last 3 releases
if [[ `ls -1 ${RELEASES_DIR} 2>/dev/null | wc -l ` -gt 3 ]];
then
	cd ${RELEASES_DIR}
	echo "Removing $(ls -t | tail -1)"
	rm -rf "$(ls -t | tail -1)"
fi