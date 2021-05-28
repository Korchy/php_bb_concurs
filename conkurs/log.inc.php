<?php
//---------------------------------
// Логгер
//---------------------------------
class Log {
	
	public function __construct() {
		// Конструктор
		
	}

	public function __destruct() {
		// Деструктор
		
	}

	public static function Add($logPath, $string) {
		// Добавление данных в лог
		$logFile = fopen ($logPath."log.txt","a+");
		// текущее время - данные
		fputs($logFile, date("Y-m-d H:i:s").":\t".$string."\r\n");
	}
}
?>