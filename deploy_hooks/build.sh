#!/bin/bash

cd /var/www/social-tracker/

export ST_CODE_DEPLOY_LOG_FILE="/var/www/social-tracker/var/logs/code-deploy.log"

deploy_hooks_dir="deploy_hooks"

if [[ "${ST_INSTANCE_TYPE}" = "worker" ]]
then
    . "$deploy_hooks_dir/worker-config.sh"
elif [[ "${ST_INSTANCE_TYPE}" = "web" ]]
then
    . "$deploy_hooks_dir/web-config.sh"
elif [[ "${ST_INSTANCE_TYPE}" = "scheduler" ]]
then
    . "$deploy_hooks_dir/scheduler-config.sh"
fi

. "$deploy_hooks_dir/shared-config.sh"

COMPOSER_HOME="/home/ubuntu/"
composer install -d /var/www/social-tracker

HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1)
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX var