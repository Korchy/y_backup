<?php

class YandexBackup {

	private static $token = '_YANDEX_DISK_TOKEN_';				// Yandex-disk token
	private static $yandexBackupDir = '_YANDEX_BACKUP_DIR_';	// Yandex-disk badkup dir
	private static $mailFrom = '_SERVER_MAIL_';					// Server mail
	private static $mailTo = '_USER_MAIL_';						// User mail to receive notifications
	private static $serverId = '_SERVER_IDENTIFIER_';			// Server identification
	private static $serverBackupDir = '_SERVER_BACKUP_DIR_';	// Server backup dir
	private static $deleteAfterBackup = true;					// true - delete file after sending to Yandex-disk, false - don't delete
	
	public static function sendBackupToYandex() {
		// Move backup files to Yandex-disk
		// Dayly
		static::sendFilesFromDirToYandex(static::$serverBackupDir, static::$yandexBackupDir.static::$serverId.'/', '_'.date('d').'_');	// Current day files
		// Monthly
		if(date('d') == '01') {
			static::sendFilesFromDirToYandex(static::$serverBackupDir.'month/', static::$yandexBackupDir.static::$serverId.'/month/', '_01_'.date('m').'_');	// Current month files
		}
	}

	public static function sendFilesFromDirToYandex($sourceDir, $destDir, $template) {
		// Send files from $sourceDir to Yandex-disk $destDir. $template - mask to filter files.
		$files = scandir($sourceDir);
		foreach($files as $file) {
			if(file_exists($sourceDir.$file)) {
				if(strpos($file, $template) !== false) {
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
					curl_close($ch);

					$rezArr = json_decode($result, true);

					// Проверка на авторизацию
					if(isset($rezArr['message']) && $rezArr['message'] == 'Не авторизован.') {
						// Authorization error
						mail(static::$mailTo, static::$serverId.' - YandexBackup error', "Yandex-disk authorization error. \n Maybe service needs new token. \n"."Yandex responce: ".$result." \n File: ".$file, 'From: '.static::$serverId.' <'.static::$mailFrom.'>');
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
						curl_close($ch);

						if($result == '1') {
							// remove source file
							if(static::$deleteAfterBackup) unlink($sourceDir.$file);
						}
						else {
							// Error uploading file to yandex-disk
							mail(static::$mailTo, static::$serverId.' - YandexBackup error', "Error uploading file to Yandex-disk. \n File: ".$file."\n".$result, 'From: '.static::$serverId.' <'.static::$mailFrom.'>');
						}
					}
				}
			}
		}
	}
}

// run uploading backup to yandex-disk
YandexBackup::sendBackupToYandex();

?>
