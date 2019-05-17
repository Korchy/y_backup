#!/bin/bash
IF=$(date +%d)

# backup files to /var/mybackup/ directory

# DAYLY
# mysql database sample
mysqldump -u _MYSQL_USER_ --password=_MYSQL_USER_PASSWORD_ -f _DATABASE_NAME_ > /var/mybackup/_DATABASE_NAME__"$IF"_mysql.sql
# directory sample
tar -czf /var/mybackup/_DIRECTORY_NAME__"$IF"_html.tar.gz /_FULL_DIRECTORY_PATH_

# MONTHLY
if [ $IF -eq 1 ]; then
        # mysql database sample
        cp /var/mybackup/_DATABASE_NAME__"$IF"_mysql.sql /var/mybackup/month/_DATABASE_NAME__01_$(date +%m)_mysql.sql
        # directory sample
        cp /var/mybackup/_DIRECTORY_NAME__"$IF"_html.tar.gz /var/mybackup/month/_DIRECTORY_NAME__01_$(date +%m)_html.tar.gz
fi
