<?php
//---------------------------------
// ������
//---------------------------------
class Log {
	
	public function __construct() {
		// �����������
		
	}

	public function __destruct() {
		// ����������
		
	}

	public static function Add($logPath, $string) {
		// ���������� ������ � ���
		$logFile = fopen ($logPath."log.txt","a+");
		// ������� ����� - ������
		fputs($logFile, date("Y-m-d H:i:s").":\t".$string."\r\n");
	}
}
?>