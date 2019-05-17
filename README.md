# Backup files to Yandex-disk

How to use
-

Make /home/scripts/ directory, /var/mybackup/ directory and /var/mybackup/month/ directory

    mkdir /home/scripts
    mkdir /var/mybackup
    mkdir /var/mybackup/month

Copy backup.sh to the /home/scripts/ directory and edit it adding databases and directories to backup. You can create such many lines you need for many databases and directories.
    
    _MYSQL_USER_ - MySql user with required to backup permissions
    _MYSQL_USER_PASSWORD_ - its password
    _DATABASE_NAME_ - MySql database to backup
    _DIRECTORY_NAME_ - name of the directory to backup
    _FULL_DIRECTORY_PATH_ - path to the directory to backup

Make it executable

    chmod +x /home/scripts/backup.sh

Add the cron task to call backup.sh once a day at 00:00

    crontab -e

    0 0 * * * /home/scripts/backup.sh 2>/tmp/cron.tmp

Register and login on Yandex-disk service. Create an application.

		https://oauth.yandex.ru/client/new
	
		Name:		name your application
		Permissions:	Яндекс.Диск REST API
						Доступ к информации о Диске
						Доступ к папке приложения на Диске
						Запись в любом месте на Диске
		Callback URL:	use "подставить код для разработки"

You will get id and password. Next you need to get a token. Open in your browser the following link:
		
    https://oauth.yandex.ru/authorize?response_type=token&client_id=_ID_
			
Specify the _ ID _ with getted application id. Press "разрешить". You will get the token.

Copy the yandexbackup.php to the /home/scripts/ directory and edit it wit specifying variables:

	'_YANDEX_DISK_TOKEN_' - token
	'_YANDEX_BACKUP_DIR_' - directory on your yandex-disk for uploading your backups
	_SERVER_MAIL_ - server mail "from" which you will receive notifications
	_USER_MAIL_ - your personal mail to receive notifications
	_SERVER_IDENTIFIER_ - type something to identify your current server
	_SERVER_BACKUP_DIR_ - directory with backups (/var/mybackup/)

You can check how it works with the following command. It moves your backups from server /var/mybackup/ to your yandex-disk

    /usr/bin/php -q /home/scripts/yandexbackup.php

Add the cron task to call yandexbackup.php once a day at 02:00

	crontab -e

	0 2 * * * /usr/bin/php -q /home/scripts/yandexbackup.php 2>/tmp/cron.tmp
