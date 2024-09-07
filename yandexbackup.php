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
	private static $log_path = '/var/log/yandex_backup.log';		// path to log file
	
	public static function sendBackupToYandex() {
		// Move backup files to Yandex-disk
		// Dayly
		static::log('=== DAYLY BACKUP ===');
		static::sendFilesFromDirToYandex(static::$src_dir, static::$dest_dir, '_'.date('d').'_');	// current day
		// Monthly
		if(date('d') == '01') {
			static::log('=== MONTHLY BACKUP ===');
			static::sendFilesFromDirToYandex(static::$src_month_dir, static::$dest_month_dir, '_01_'.date('m').'_');	// 01 day of a month
		}
	}

	private static function sendFilesFromDirToYandex($source_dir, $destDir, $template) {
		// Send files from $source_dir to Yandex-disk $destDir. $template - mask to filter files.
		// scan $source_dir for files (except directories and . and ..)
		$files = array_filter(
			scandir($source_dir),
			function($item) use ($source_dir) {
				return !is_dir($source_dir . $item) && !in_array($item, ['.', '..']);
			}
		);
		static::log('Found: ' . count($files) . ' flies');
		foreach($files as $file) {
			if(file_exists($source_dir.$file)) {
				if(strpos($file, $template) !== false) {
					static::log('Sending: ' . $file);
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
						$curl_err .= 'CURL ERR:' . PHP_EOL . var_export($last_err, true);
					}

					curl_close($ch);

					$rezArr = json_decode($result, true);

					// Проверка на авторизацию
					// if(!result || isset($rezArr['message']) && $rezArr['message'] == 'Не авторизован.') {
					if(!$result || isset($rezArr['message'])) {
						// Ошибка авторизации
						static::log('CURL ERR: ' . $curl_err);
						static::log('REZULT: ' . var_export($result, true));
						mail(static::$mailTo, static::$serverId.' - YandexBackup error', 'Ошибка авторизации при сохранении бекапа на Яндекс-диск. \n Возможно нужен новый токен. \n' . 'Ответ сервера: ' . var_export($result, true) . ' \n Файл: '.$file . '\n' . $curl_err, 'From: ' . static::$serverId . ' <'.static::$mailFrom.'>');
					}
					else {
						// Upload $file to yandex-disk
						$uploadRef = $rezArr['href'];	// url for uploading

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $uploadRef);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
						curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
						curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($source_dir . $file));
						$result = curl_exec($ch);
						
						// check errors
						$last_err = curl_error($ch);
						if($last_err) {
							$curl_err .= 'CURL ERR:' . chr(13) . var_export($last_err, true);
						}

						curl_close($ch);

						if($result == '1') {
							// remove source file
							if(static::$deleteAfterBackup) unlink($source_dir . $file);
							static::log('OK');
							}
						else {
							// Error uploading file to yandex-disk
							static::log('CURL ERR: ' . $curl_err);
							static::log('REZULT: ' . var_export($result, true));
							mail(static::$mailTo, static::$serverId . ' - YandexBackup error', 'Ошибка загрузки файла при сохранении бекапа на Яндекс-диск. \n Файл: ' . $file . '\n' . var_export($result, true) . '\n' . $curl_err, 'From: ' . static::$serverId . ' <'.static::$mailFrom.'>');
						}
					}
				}
			}
		}
	}

	private static function log($info) {
		// Add info to the log-file
		$log_file = fopen(static::$log_path, 'ab');
		$info = (is_array($info) ? var_export($info, true) : $info);
		fputs($log_file, date('Y-m-d H:i:s') . ':    ' . $info . PHP_EOL);
		fclose($log_file);
	}
}

// run backup to yandex
echo "Backup to Yandex-disk";
YandexBackup::sendBackupToYandex();

?>
