# Social Tracker Project
Micro service to track new social posts on twitter and instagram by accounts.
Primary used for Live Events section on uproxx.com

# INSTALATION #
1) Clone git repository
2) Go to the project dir
3) Run command:
$ vagrant up --debug &> var/logs/vagrant.log

# USAGE #
Via browser http://10.20.30.60
Via command line:
1) Go to project dir
2) $ vagrant ssh
3) cd /var/www

# SCHEDULER CRONTAB #
SYMFONY_ENV=prod
* * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:social_account_to_sqs_command --enable-debug --log-name=add_account_to_sqs_log_path --queue-url=account_grabber_queue_url
*/5 * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:social_post_to_sqs_command --enable-debug --from=0 --to=60 --log-name=add_post_to_sqs_log_path --queue-url=validate_post_url_queue_url
0 * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:social_post_to_sqs_command --enable-debug --from=61 --to=720 --log-name=add_post_to_sqs_log_path --queue-url=validate_post_url_queue_url
* */12 * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:social_post_to_sqs_command --enable-debug --from=721 --to=1440 --log-name=add_post_to_sqs_log_path --queue-url=validate_post_url_queue_url
0 * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:track_json_structure_command --enable-debug --account-name=uproxx --post-url='https://www.instagram.com/p/BeYVKNBnJKi/' --log-name=truck_json_structure_instagram_api

# WORKER CRONTAB #
SYMFONY_ENV=prod
* * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:validate_social_account_command --enable-debug
* * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:social_account_grabber_command --enable-debug
* * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:social_post_grabber_command --enable-debug --queue-url=post_comment_grabber
* * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:validate_social_post_command --enable-debug
* * * * * /usr/bin/php /var/www/social-tracker/bin/console social_tracker:social_post_grabber_command --enable-debug --queue-url=rewrite_post_queue_url