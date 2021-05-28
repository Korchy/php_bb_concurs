<?php
//---------------------------------
//	Класс для работы с конкурсами
//	Для ЕК:
//		[EK=B51elpK.jpg]Новая тема[/EK]
//---------------------------------
require_once("phpbbauto.inc.php");
require_once("config.inc.php");
require_once("log.inc.php");
require_once("konkursrank.inc.php");
//---------------------------------
class Konkurs
{
	protected $basePath = "/home/itcomp/b3d.org.ua/www/forum/conkurs/";
	
	protected $Conf;
	
	private $Connection;
	public $SqlQuery;
	public $SqlRez;
	
	protected $konkursId;	// Идентификатор вида конкурса
	protected $ForumId;		// Id форума
	protected $TopicId;		// Id темы
	protected $PostId;		// Id первого поста
	protected $PostType;	// Тип первого поста (обычный / глобальное объявление)
	protected $UserId;		// Id пользователя форума от лица которого оформляется ежедневка (Бот конкурса) (527 - Korchy)
	protected $GalleryForumId;	// Id форума для галереи
	protected $GalleryTopicId;	// Id темы для галереи
	protected $Table;			// Таблица для хранения данных конкурса
	protected $TableRank;		// Таблица для хранения наград конкурса
	protected $Rules;			// Правила конкурса
	protected $Title;			// Заголовок темы конкурса
	protected $Period;			// Период отработки конкурса (в днях)
	
	public function __construct($vKonkursId) {
		$this->konkursId = $vKonkursId;
		$this->Conf = new Config($this->basePath."config.ini");
		$this->ForumId = $this->Conf->Data["konkurs"][$vKonkursId]["forumid"];
		$this->TopicId = $this->Conf->Data["konkurs"][$vKonkursId]["topicid"];
		$this->PostId = $this->Conf->Data["konkurs"][$vKonkursId]["postid"];
		$this->PostType = $this->Conf->Data["konkurs"][$vKonkursId]["posttype"];
		$this->UserId = $this->Conf->Data["konkurs"][$vKonkursId]["userid"];
		$this->GalleryForumId = $this->Conf->Data["konkurs"][$vKonkursId]["galleryforumid"];
		$this->GalleryTopicId = $this->Conf->Data["konkurs"][$vKonkursId]["gallerytopicid"];
		$this->Table = $this->Conf->Data["konkurs"][$vKonkursId]["table"];
		$this->TableRank = $this->Conf->Data["konkurs"][$vKonkursId]["tablerank"];
		$this->Rules = $this->Conf->Data["konkurs"][$vKonkursId]["rules"];
		$this->Title = $this->Conf->Data["konkurs"][$vKonkursId]["title"];
		$this->Period = $this->Conf->Data["konkurs"][$vKonkursId]["period"];
	}

	public function __destruct() {
		unset($this->Conf);
	}

	public function connectToDb() {
		if(!$this->Connection) {
			$ConfDB = new Config($this->basePath."configdb.ini");
			$this->Connection = new mysqli($ConfDB->Data["db"]["host"], $ConfDB->Data["db"]["user"], $ConfDB->Data["db"]["password"], $ConfDB->Data["db"]["base"]);
			if (!$this->Connection->connect_errno) {
				mysqli_set_charset($this->Connection, "utf8");
				return true;
			}
			return false;
		}
		return true;
	}
	
	public function exec() {
		if($this->SqlQuery != "") {
			$this->SqlRez = $this->Connection->query($this->SqlQuery);
			if($this->SqlRez===false) {
				$this->log("Ошибка запроса: ".$this->SqlQuery." (".$this->Connection->error.")");
				return false;
			}
  			return true;
		}
		else return false;
	}
	
	public function escape($Text) {
		if($this->Connection!=NULL) {
			$Text = $this->Connection->real_escape_string($Text);
			$Text = addcslashes($Text,'%_');
			return $Text;
		}
		else return '';
	}

	protected function log($text) {
		Log::Add($this->basePath, $text);
	}
	
	public function addNew($params_array) {
		// Обработка сообщения и добавление новой работы в конкурсную таблицу
/*
		foreach($params_array as $key => $val) {
			$this->log($key." - ".$val);
		}
*/
		// Проверка на PREVIEW (т.к. при предпросмотре тоже отрабатываются bbcode)
		if(isset($_POST["preview"])) return;
		// Добавление работы на конкурс
		$konkursType = mb_strtolower($params_array[1]);	// ek
		$bigImg = $params_array[2];						// http://i.imgur.com/B51elpK.jpg
		$smallImg = $params_array[3];					// http://i.imgur.com/B51elpKm.jpg
		$newTheme = $params_array[4];					// НОВАЯ ТЕМА
		global $user;
		$phpbbUserId = $user->data['user_id'];
		$phpbbUserName = $user->data['username'];
		$this->connectToDb();
		$this->SqlQuery = "insert into ".$this->Table." (user_id, user_name, konkurs, big_img, small_img, theme, is_new) values ('".$this->escape($phpbbUserId)."', '".$this->escape($phpbbUserName)."', '".$this->escape($konkursType)."', '".$this->escape($bigImg)."','".$this->escape($smallImg)."','".$this->escape($newTheme)."',0);";
		$this->exec();
	}
	
	public function check() {
		// Вызов идет через cron раз в сутки
		$this->connectToDb();
		// Переделана проверка - если старт этапа плюс кол-во дней в периоде > сегодня ---> не отрабатывать (ждать конца этапа)
		$this->SqlQuery = "select win_theme_start from ".$this->Table." where konkurs='".$this->escape($this->konkursId)."' and win_theme_start is not null order by win_theme_start desc limit 1;";
		$this->exec();
		if($SQLRez = $this->SqlRez) {
			while($tmp = $SQLRez->fetch_array()) {
				if((strtotime($tmp["win_theme_start"]) + $this->Period*86400) > time()) return;
			}
		}
		// Отработать
		$F = new PhpbbAuto();
		$F->OpenUserSession($this->UserId);
//		$this->connectToDb();
		// Для текущего конкурса
		$this->SqlQuery = "select id from ".$this->Table." where konkurs='".$this->escape($this->konkursId)."' and is_new='0' group by user_id;";
		$this->exec();
		if($SQLRez = $this->SqlRez) {
			if($SQLRez->num_rows > 1) {
				// Все участники
				$members = array();
				$this->SqlQuery = "select po.poll_option_id, ek.id, ek.user_id, ek.user_name, po.poll_option_text, po.poll_option_total, ek.theme, substring(po.poll_option_text, locate('hidden', po.poll_option_text)+7,8) as uid from phpbb_poll_options po inner join new_ek ek on substring(po.poll_option_text, locate('hidden', po.poll_option_text)+16, locate('[/hidden', po.poll_option_text)-locate('hidden', po.poll_option_text)-16) = ek.id where ek.konkurs = '".$this->escape($this->konkursId)."';";
				$this->exec();
				if($SQLRez1 = $this->SqlRez) {
					while($tmp1 = $SQLRez1->fetch_array()) {
						$members[$tmp1["poll_option_id"]] = array($tmp1["id"], $tmp1["user_id"], $F->unParse($tmp1["poll_option_text"], $tmp1["uid"]), $tmp1["poll_option_total"], $tmp1["theme"], $tmp1["user_name"]);
					}
				}
				// Проверка на голосование за себя
				$this->SqlQuery = "select pv.poll_option_id, pv.vote_user_id from phpbb_poll_votes pv where pv.topic_id='".$this->escape($this->TopicId)."';";
				$this->exec();
				if($SQLRez1 = $this->SqlRez) {
					while($tmp1 = $SQLRez1->fetch_array()) {
						if($tmp1["vote_user_id"] == $members[$tmp1["poll_option_id"]][1]) $members[$tmp1["poll_option_id"]][3]--;	// Если это голос за себя - снять 1 голос с работы
					}
				}
				// Определение победителей
				$winRate = 0;
				$votesTotal = 0;
				foreach($members as $id => $work) {
					if($work[3] > $winRate) $winRate = $work[3];
					$votesTotal += $work[3];
				}
				$winners = array();
				$loosers = array();
				foreach($members as $id => $work) {
					if($work[3] >= $winRate) $winners[] = $work;
					else $loosers[] = $work;
				}
				// Тема на следующий этап - рендом из победителей
				$winTheme = array_rand($winners);
				// Сохранить тему для будущего голосования и дату с которой начался очередной этап конкурса
				$this->SqlQuery = "update ".$this->Table." set win_theme=1, win_theme_start='".date('Y-m-d')."' where id='".$this->escape($winners[$winTheme][0])."';";
				$this->exec();
				// Текст для поста с победителями
				$lastTheme = "";
				$this->SqlQuery = "select theme from ".$this->Table." where win_theme='1' and konkurs='".$this->escape($this->konkursId)."' order by inpdate desc limit 2,1;";
				$this->exec();
				if($SQLRez1 = $this->SqlRez) {
					while($tmp1 = $SQLRez1->fetch_array()) {
						$lastTheme = $tmp1["theme"];
					}
				}
				$galleryTxtP1 = "[b]".date("d.m.Y")." «".$lastTheme."»[/b]\r\n";
				if(count($winners)>1) $galleryTxtP1 = $galleryTxtP1."[b][color=#FF0000]Победители:[/color][/b]\r\n";
				else $galleryTxtP1 = $galleryTxtP1."[b][color=#FF0000]Победитель:[/color][/b]\r\n";
				foreach($winners as $id => $winner) {
					$galleryTxtP1 = $galleryTxtP1."[b]".$winner[5]."[/b]\r\n".$winner[2]." (".$winner[3]." из ".$votesTotal.")\r\n";
				}
				if(count($loosers)>0) {
					$galleryTxtP1 = $galleryTxtP1."[b][color=#0000FF]Работы участников:[/color][/b]\r\n";
					$galleryTxtP1 = $galleryTxtP1."[spoiler]\r\n";
					foreach($loosers as $id => $looser) {
						$galleryTxtP1 = $galleryTxtP1."[b]".$looser[5]."[/b]\r\n".$looser[2]." (".$looser[3]." из ".$votesTotal.")\r\n";
					}
					$galleryTxtP1 = $galleryTxtP1."[/spoiler]\r\n";
				}
				$link = $F->CreatePost($this->GalleryForumId, $this->GalleryTopicId, "", $galleryTxtP1);
				// Обновить звание победителя
				$rank = new KonkursRank();
				foreach($winners as $id => $winner) {
					$rank->updateRank($this->TableRank, $winner[1], $this->konkursId, 1);
				}
				// Переоформить голосовалку
				// Удалять старое вручную через таблицы т.к. идет правка поста (хотя не понятно, почему не удаляется автоматом)
				$PollItems = "";
				$this->SqlQuery = "delete from phpbb_poll_votes where topic_id='".$this->escape($this->TopicId)."';";
				$this->exec();
				$this->SqlQuery = "delete from phpbb_poll_options where topic_id='".$this->escape($this->TopicId)."';";
				$this->exec();
				$this->SqlQuery = "select id, big_img, small_img, user_name from ".$this->Table." where is_new='0' and konkurs='".$this->escape($this->konkursId)."';";
				$this->exec();
				if($SQLRez1 = $this->SqlRez) {
					while($tmp1 = $SQLRez1->fetch_array()) {
						$newItem = "[url=".$tmp1["big_img"]."][img]".$tmp1["small_img"]."[/img][/url]"."[HIDDEN]".$tmp1["id"]."[/HIDDEN]";
						$PollItems = $PollItems."\r\n".$newItem;
					}
				}
				// Закрыть добавленные
				$this->SqlQuery = "update ".$this->Table." set is_new='1' where is_new='0' and konkurs='".$this->escape($this->konkursId)."';";
				$this->exec();
				$newTheme = "[size=200]ТЕКУЩАЯ ТЕМА: [b][color=#FF8000]«".$winners[$winTheme][4]."»[/color][/b][/size]\r\n(допускается отклонение от темы)";
				$newFirstPost = $this->Rules."\r\n".$newTheme;
//				$contDouwn = "[b]До окончания приема работ осталось: [/b]"."[COUNTDOWN PERIOD=".($this->Period * 86400000)."]".date("Y-m-d H.i", strtotime(date("Y-m-d")." 22:00:00"))."[/COUNTDOWN]";
				$contDouwn = "[b]До окончания приема работ осталось: [/b]"."[COUNTDOWN PERIOD=86400000]".date("Y-m-d H.i", strtotime(date("Y-m-d")." 22:00:00")+($this->Period * 86400))."[/COUNTDOWN]";
				$newFirstPost = $newFirstPost."\r\n".$contDouwn;
				// Тема голосования
				$pollTheme = "";
				$this->SqlQuery = "select theme from ".$this->Table." where win_theme='1' and konkurs='".$this->escape($this->konkursId)."' order by inpdate desc limit 1,1;";
				$this->exec();
				$newTheme = "";
				if($SQLRez1 = $this->SqlRez) {
					while($tmp1 = $SQLRez1->fetch_array()) {
						$pollTheme = $tmp1["theme"];
					}
				}
				$link = $F->EditPost($this->ForumId, $this->TopicId, $this->PostId, $this->PostType, $this->UserId, $this->Title, "Голосование на тему «".$pollTheme."»", $PollItems, $newFirstPost);
			}
			else {
				// Обновить перенесение
				$link = $F->CreatePost($this->ForumId, $this->TopicId, $this->Title, "Конкурс продлен");
			}
		}
	}
	
	public function sliderInfo() {
		// Возврат ссылки на выигравшее изображение с последнего этапа и текущую тему
		$this->connectToDb();
		$rez = array();
		// Переделана проверка - если старт этапа плюс кол-во дней в периоде > сегодня ---> не отрабатывать (ждать конца этапа)
		$this->SqlQuery = "select small_img, theme, user_name from ".$this->Table." where win_theme='1' and konkurs='".$this->escape($this->konkursId)."' order by inpdate desc limit 0,1;";
		$this->exec();
		if($SQLRez = $this->SqlRez) {
			while($tmp = $SQLRez->fetch_array()) {
				$rez[0] = $tmp["small_img"];
				$rez[1] = $tmp["theme"];
				$rez[2] = $tmp["user_name"];
			}
		}
		return $rez;
	}
}
?>