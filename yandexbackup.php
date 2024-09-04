<?php

class YandexBackup {

	private static $token = '_YANDEX_DISK_TOKEN_';					// Yandex-disk token
	private static $mailFrom = '_SERVER_MAIL_';						// Server mail from which notifications sent
	private static $mailTo = '_USER_MAIL_';							// User mail to receive notifications
	private static $serverId = '_SERVER_IDENTIFIER_';				// Server identification (use your own text)
	private static $deleteAfterBackup = true;						// true - delete files on server after sending to Yandex-disk, false - not
	private static $src_dir = '_SERVER_BACKUP_DIR_';				// path to backuping files on local computer (ex: /var/mybackups/)
	private static $dest_dir = '_YANDEX_BACKUP_DIR_';				// path to destination folder on Yandex-disk to place backuping files. (ex: backup/myserver_backup/)
	private static $src_month_dir = '_SERVER_BACKUP_MONTH_DIR_';	// path to backuping files on local computer (once a month copy) (ex: /var/mybackups/monthly/)
	private static $dest_month_dir = '_YANDEX_BACKUP_MONTH_DIR_';	// path to destination folder on Yandex-disk to place backuping files (once month copy) (ex: backup/myserver_backup_monthly/)
	
	public static function sendBackupToYandex() {
		// Move backup files to Yandex-disk
		// Dayly
		static::sendFilesFromDirToYandex(static::$src_dir, static::$dest_dir, '_'.date('d').'_');	// current day
		// Monthly
		if(date('d') == '01') {
			static::sendFilesFromDirToYandex(static::$src_month_dir, static::$dest_month_dir, '_01_'.date('m').'_');	// 01 day of a month
		}
	}

	public static function sendFilesFromDirToYandex($sourceDir, $destDir, $template) {
		// Send files from $sourceDir to Yandex-disk $destDir. $template - mask to filter files.
		$files = scandir($sourceDir);
		foreach($files as $file) {
			if(file_exists($sourceDir.$file)) {
				if(strpos($file, $template) !== false) {
					$curl_err = '';
					$header = array(
						'Accept: application/json',
						'Authorization: OAuth '.static::$token,
					);
					// Get yandex-disk url for uploading
					$url = 'https://cloud-api.yandex.net/v1/disk/resources/upload?path='.$destDir.$file.'&overwrite=true';

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url); 
					curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					$result = curl_exec($ch);
					// check errors
					$last_err = curl_error($ch);
					if($last_err) {
						// error_log('CURL ERR: ' . $last_err);
						// echo 'CURL ERR: ' . var_export($last_err, true);
						$curl_err .= 'CURL ERR:' . chr(13) . var_export($last_err, true);
					}
					curl_close($ch);

					$rezArr = json_decode($result, true);

					// Connecting check
					if(!$result) {
						// Maybe authorization error
						mail(static::$mailTo, static::$serverId.' - YandexBackup error', 'Yandex-disk authorization error. \n Maybe service needs new token. \n' . 'Yandex responce: ' . $result . ' \n Файл: ' . $file . '\n' . $curl_err, 'From: ' . static::$serverId . ' <'.static::$mailFrom.'>');
					}
					else {
						// Upload $file to yandex-disk
						$uploadRef = $rezArr['href'];	// url for uploading

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $uploadRef);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
						curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($sourceDir.$file));
						$result = curl_exec($ch);
						
						// check errors
						$last_err = curl_error($ch);
						if($last_err) {
							// error_log('CURL ERR: ' . $last_err);
							// echo 'CURL ERR: ' . var_export($last_err, true);
							$curl_err .= 'CURL ERR:' . chr(13) . var_export($last_err, true);
						}

						curl_close($ch);

						if($result == '1') {
							// remove source file
							if(static::$deleteAfterBackup) unlink($sourceDir.$file);
						}
						else {
							// Error uploading file to yandex-disk
							mail(static::$mailTo, static::$serverId.' - YandexBackup error', 'Error uploading file to Yandex-disk. \n Файл: ' . $file . '\n' . var_export($result, true) . '\n' . $curl_err, 'From: ' . static::$serverId . ' <'.static::$mailFrom.'>');
						}
					}
				}
			}
		}
	}
}

// run backup to yandex
YandexBackup::sendBackupToYandex();

?>
