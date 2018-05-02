#!/bin/bash

# Web AWS Logs
webAwslogs="/var/awslogs/etc/awslogs.conf"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${webAwslogs}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/web/awslogs.conf ${webAwslogs} &&
sudo chmod 600 ${webAwslogs} &&
sudo service awslogs restart &&
echo "Finished ${ST_INSTANCE_TYPE} ${webAwslogs}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1

# Web Nginx Site Available Config
webNginxSiteConfig="/etc/nginx/sites-available/default"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${webNginxSiteConfig}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/web/nginx/default ${webNginxSiteConfig} &&
sudo chmod 644 ${webNginxSiteConfig} &&
sudo service nginx restart &&
echo "Finished ${ST_INSTANCE_TYPE} ${webNginxSiteConfig}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1

# Web Nginx Config
webNginxConfig="/etc/nginx/nginx.conf"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${webNginxConfig}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/web/nginx/nginx.conf ${webNginxConfig} &&
sudo chmod 644 ${webNginxConfig} &&
sudo service nginx restart &&
echo "Finished ${ST_INSTANCE_TYPE} ${webNginxConfig}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1
