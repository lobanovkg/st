#!/bin/bash

# Worker AWS Logs
workerAwslogs="/var/awslogs/etc/awslogs.conf"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${workerAwslogs}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/worker/awslogs.conf ${workerAwslogs} &&
sudo chmod 600 ${workerAwslogs} &&
sudo service awslogs restart &&
echo "Finished ${ST_INSTANCE_TYPE} ${workerAwslogs}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1

# Worker Crontab
workerCrontab="/home/ubuntu/crontab.txt"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${workerCrontab}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/worker/crontab.txt ${workerCrontab} &&
sudo chown ubuntu:ubuntu ${workerCrontab} &&
sudo crontab -u ubuntu ${workerCrontab} &&
echo "Finished ${ST_INSTANCE_TYPE} ${workerCrontab}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1
