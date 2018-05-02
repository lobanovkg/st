#!/bin/bash

# Symfony Parameters
sharedParameters="/var/www/social-tracker/app/config/parameters.yml"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${sharedParameters}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/shared/parameters.yml ${sharedParameters} &&
sudo chmod 644 ${sharedParameters} &&
sudo chown ubuntu:ubuntu ${sharedParameters} &&
echo "Finished ${ST_INSTANCE_TYPE} ${sharedParameters}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1

# PHP Cli Config
sharedPhpCliConfig="/etc/php/7.2/cli/php.ini"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${sharedPhpCliConfig}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/shared/php/cli.ini ${sharedPhpCliConfig} &&
sudo chmod 644 ${sharedPhpCliConfig} &&
echo "Finished ${ST_INSTANCE_TYPE} ${sharedPhpCliConfig}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1

# PHP Fpm Config
sharedPhpFpmConfig="/etc/php/7.2/fpm/php.ini"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${sharedPhpFpmConfig}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/shared/php/fpm.ini ${sharedPhpFpmConfig} &&
sudo chmod 644 ${sharedPhpFpmConfig} &&
echo "Finished ${ST_INSTANCE_TYPE} ${sharedPhpFpmConfig}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1
