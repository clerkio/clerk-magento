#! /usr/bin/env bash

# start services
service mysql start
service apache2 start

# set configuration variables & volumes
cd /var/www/htdocs
/root/bin/modman repair --force clerk-magento

n98-magerun --root-dir=/var/www/htdocs config:set web/unsecure/base_url $BASE_URL
n98-magerun --root-dir=/var/www/htdocs config:set web/secure/base_url $BASE_URL

chmod -R 777 /var/www/htdocs/media
chown -R www-data:www-data /var/www/htdocs/media

# Again in case root created some folder with root:root
chmod -R 777 /var/www/htdocs/media
chown -R www-data:www-data /var/www/htdocs/media

# do it after indexing so that var/log doesn't get created as root
n98-magerun --root-dir=/var/www/htdocs config:set dev/log/active 1

service apache2 stop
exec /usr/sbin/apache2ctl -D FOREGROUND
