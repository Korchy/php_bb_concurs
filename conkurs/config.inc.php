<?php
//---------------------------------
// Конфиг
//---------------------------------
require_once("log.inc.php");
//---------------------------------
class Config
{
	public $Data;	// Массив с настройками

	public function __construct($File) {
		// Конструктор
		$this->Data = array();
		if(file_exists($File)==true) $this->LoadConfig($File);
	}

	public function __destruct() {
		// Деструктор
		unset($this->Data);
	}
	
	private function LoadConfig($File) {
		// Загрузка данных из файла в массив
		$XML = simplexml_load_file($File);
		if(!$XML) Log::Add("/home/itcomp/b3d.org.ua/www/forum/conkurs/", "Ошибка открытия файла config.ini");
		$this->Data = json_decode(json_encode((array)$XML), 1);
	}
}
?>