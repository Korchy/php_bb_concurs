<?php
//---------------------------------
//	Класс для работы с рангами конкурсов
//---------------------------------
require_once("/home/itcomp/b3d.org.ua/www/forum/conkurs/config.inc.php");
require_once("/home/itcomp/b3d.org.ua/www/forum/conkurs/log.inc.php");
//---------------------------------
class KonkursRank
{
	private $basePath = "/home/itcomp/b3d.org.ua/www/forum/conkurs/";
	
	private static $Connection;
	public $SqlQuery;
	public $SqlRez;
	
	private static $Conf;
	
	private static $usersMedals;	// Массив с уже сформированными строками для наград юзера usersMedals[id юзера] = строка
	
	public function __construct() {
		$this->getConfig();
		$this->connectToDb();
		if(!self::$usersMedals) self::$usersMedals = array();
	}

	public function __destruct() {
		
	}
	
	private function getConfig() {
		if(!self::$Conf) {
			self::$Conf = new Config($this->basePath."configrank.ini");
		}
	}
	
	private function connectToDb() {
		if(!self::$Connection) {
			$ConfDB = new Config($this->basePath."configdb.ini");
			self::$Connection = new mysqli($ConfDB->Data["db"]["host"], $ConfDB->Data["db"]["user"], $ConfDB->Data["db"]["password"], $ConfDB->Data["db"]["base"]);
			if (!self::$Connection->connect_errno) {
				mysqli_set_charset(self::$Connection, "utf8");
				return true;
			}
			return false;
		}
		return true;
	}
	
	public function exec() {
		if($this->SqlQuery != "") {
			$this->SqlRez = self::$Connection->query($this->SqlQuery);
			if($this->SqlRez===false) {
				$this->log("Ошибка запроса: ".$this->SqlQuery." (".self::$Connection->error.")");
				return false;
			}
  			return true;
		}
		else return false;
	}
	
	public function escape($Text) {
		if(self::$Connection) {
			$Text = self::$Connection->real_escape_string($Text);
			$Text = addcslashes($Text,'%_');
			return $Text;
		}
		else return '';
	}

	protected function log($text) {
		Log::Add($this->basePath, $text);
	}

	public function updateRank($vTable, $vUserId, $vKonkurs, $vValue) {
		// Изменение ранга пользователя $vUserId на $vValue
		// Общий ранг
		$this->SqlQuery = "update phpbb_users set user_rank=user_rank+1 where user_id='".$this->escape($vUserId)."';";
//		$this->exec();
		$this->log("Id победителя: ".$vUserId);
		// Ранг по конкурсу
		$this->SqlQuery = "select id from ".$vTable." where konkurs = '".$this->escape($vKonkurs)."' and user_id = '".$this->escape($vUserId)."';";
		$this->exec();
		if($SQLRez = $this->SqlRez) {
			if($SQLRez->num_rows == 1) {
				$this->SqlQuery = "update ".$vTable." set rank = rank + '".$this->escape($vValue)."' where konkurs = '".$this->escape($vKonkurs)."' and user_id = '".$this->escape($vUserId)."';";
				$this->exec();
			}
			else {
				$this->SqlQuery = "insert into ".$vTable." (user_id, konkurs, rank) values ('".$this->escape($vUserId)."', '".$this->escape($vKonkurs)."', '1');";
				$this->exec();
			}
		}
	}
	
	public function getRank($vUserId, $vTable = 'new_ek_rank') {
		// Получить все ранги пользователя
		if(self::$usersMedals[$vUserId]) return self::$usersMedals[$vUserId];
		// Подсчет
		$rez = "";
		$this->SqlQuery = "select konkurs, rank from ".$vTable." where user_id = '".$this->escape($vUserId)."';";
		$this->exec();
		if($SQLRez = $this->SqlRez) {
			// Для каждой возможной медали определить ее количество у игрока
			$rankArr = array('all' => array('descr' => '', 'rank' => 0, 'img' => '', 'img_rank' => 0));
			while($tmp = $SQLRez->fetch_array()) {
				if(!self::$Conf->Data[$tmp["konkurs"]]) {
					$rankArr['all']['rank'] += $tmp['rank'];
				}
				else {
					$rankArr[$tmp['konkurs']] = array('descr' => '', 'rank' => $tmp['rank'], 'img' => '', 'img_rank' => 0);
				}
			}
			// 10 первого уровня, потом 10 второго и так до максимального (10 деревянных потом 10 бронзовых и т.д. - последнего уровня сколько наберется)
			foreach($rankArr as $name => $val) {
				if($val['rank'] > 0) {
					foreach(self::$Conf->Data[$name] as $name1 => $val1) {
						if($name1 == 'descr') $rankArr[$name]['descr'] = $val1;
						else {
							// определить медаль и количество для отображения
							if((integer)substr($name1,2,6) >= $rankArr[$name]['img_rank'] && (integer)substr($name1,2,6) < $rankArr[$name]['rank']) {
								$rankArr[$name]['img_rank'] = (integer)substr($name1,2,6);
								$rankArr[$name]['img'] = $val1;
							}
							
						}
					}
					// Сформировать строчки для вывода
					$currentRez = "";
					if($rankArr[$name]['rank'] >= self::$Conf->Data[$name]['maxrank']) {
						// Достигли максимального ранга - 1 медаль
						$currentRez = "<img src=http://".$_SERVER["SERVER_NAME"]."/forum/konkurs/".$rankArr[$name]['img']." title='".$rankArr[$name]['descr'].": ".$rankArr[$name]['rank']." побед'>";
					}
					else {
						for($i = 0; $i < ($rankArr[$name]['rank'] - $rankArr[$name]['img_rank']); $i++) {
							$currentRez .= "<img src=http://".$_SERVER["SERVER_NAME"]."/forum/konkurs/".$rankArr[$name]['img']." title='".$rankArr[$name]['descr'].": ".$rankArr[$name]['rank']." побед'>";
						}
					}
					// "иные" - всегда внизу
					if($name == "all") $rez .= $currentRez."<br>";
					else $rez = $currentRez."<br>".$rez;
				}
			}
		}
		self::$usersMedals[$vUserId] = $rez;
		return $rez;
	}
}
?>