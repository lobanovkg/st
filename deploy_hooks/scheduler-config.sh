#!/bin/bash

# Scheduler AWS Logs
schedulerAwslogs="/var/awslogs/etc/awslogs.conf"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${schedulerAwslogs}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/scheduler/awslogs.conf ${schedulerAwslogs} &&
sudo chmod 600 ${schedulerAwslogs} &&
sudo service awslogs restart &&
echo "Finished ${ST_INSTANCE_TYPE} ${schedulerAwslogs}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1

# Scheduler Crontab
schedulerCrontab="/home/ubuntu/crontab.txt"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${schedulerCrontab}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/scheduler/crontab.txt ${schedulerCrontab} &&
sudo chown ubuntu:ubuntu ${schedulerCrontab} &&
sudo crontab -u ubuntu ${schedulerCrontab} &&
echo "Finished ${ST_INSTANCE_TYPE} ${schedulerCrontab}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1

# Scheduler Curator Config
schedulerCurator="/home/ubuntu/.curator/curator.yml"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${schedulerCurator}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/scheduler/curator/curator.yml ${schedulerCurator} &&
sudo chmod 664 ${schedulerCurator} &&
sudo chown ubuntu:ubuntu ${schedulerCurator} &&
echo "Finished ${ST_INSTANCE_TYPE} ${schedulerCurator}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1

# Scheduler Curator Delete Index Action
schedulerCuratorAction="/home/ubuntu/.curator/delete_indices_action.yml"
(
echo "-------" &&
echo "Started ${ST_INSTANCE_TYPE} ${schedulerCuratorAction}" &&
sudo aws s3 cp s3://${ST_SETTINGS_BUCKET}/${ST_SETTINGS_PROJECT}/scheduler/curator/delete_indices_action.yml ${schedulerCuratorAction} &&
sudo chmod 664 ${schedulerCuratorAction} &&
sudo chown ubuntu:ubuntu ${schedulerCuratorAction} &&
echo "Finished ${ST_INSTANCE_TYPE} ${schedulerCuratorAction}" &&
echo "-------"
) >> ${ST_CODE_DEPLOY_LOG_FILE} 2>&1
