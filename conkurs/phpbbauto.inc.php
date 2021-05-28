<?php
//---------------------------------
// PhpBB автоматизация работы с форумом
//---------------------------------
define('IN_PHPBB', true);
//if(!isset($phpbb_root_path) || !$phpbb_root_path) $phpbb_root_path = "../";
if(!isset($phpbb_root_path) || !$phpbb_root_path) $phpbb_root_path = "/home/itcomp/b3d.org.ua/www/forum/";
//$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
//echo $phpbb_root_path;
$phpEx = 'php';
// Вызвать необходимые глобальные модули
require_once("/home/itcomp/b3d.org.ua/www/forum/common.php");
require_once("/home/itcomp/b3d.org.ua/www/forum/includes/message_parser.php");
//---------------------------------
class PhpbbAuto
{
	
	public $StayLogin;			// true - оставаться залогиненным после завершения действий, false - закрыть сессию
	
	public function __construct() {
		$this->StayLogin = false;
	}

	public function __destruct() {
		if($this->StayLogin==false) $this->CloseUserSession();
	}
	
	public function OpenUserSession($UserId) {
		// Открытие сессии для пользователя $UserId
		global $user;
		global $auth;
		$user->session_begin(false);
		$user->session_create($UserId, false, false, false);
		$auth->acl($user->data);
		$user->setup();
	}
	
	public function CloseUserSession() {
		// Закрытие сессии для текущего пользователя
		global $user;
		$user->session_kill();
	}
	
	public function CreatePost($ForumId, $TopicId, $Title, $Text) {
		// Создание поста на форуме $ForumId в теме $TopicId от имени текущего пользователя с текстом $Text. Передавать в кодировке Utf-8
		$poll = $uid = $bitfield = $options = ''; 
		generate_text_for_storage($Title, $uid, $bitfield, $options, false, false, false);
		generate_text_for_storage($Text, $uid, $bitfield, $options, true, true, true);
		$message_parser = new parse_message();
		$message_parser->parse_message($Text);
		$data = array( 
			'forum_id'			=> $ForumId,
			'topic_id'			=> $TopicId,
			'icon_id'			=> false,
			'enable_bbcode'		=> true,
			'enable_smilies'	=> true,
			'enable_urls'		=> true,
			'enable_sig'		=> true,
			'message'			=> $Text,
			'message_md5'		=> md5($Text),
			'bbcode_bitfield'	=> $bitfield,
			'bbcode_uid'		=> $uid,
			'post_edit_locked'	=> 0,
			'topic_title'		=> $Title,
			'notify_set'		=> false,
			'notify'			=> false,
			'post_time' 		=> 0,
			'forum_name'		=> '',
			'enable_indexing'	=> true,
		);
		if(!function_exists('submit_post')) {	// Приходится делать include здесь т.к. если делать где положено, дальше идет redeclare функций из functions_posting
			include_once("/home/itcomp/b3d.org.ua/www/forum/includes/functions_posting.php");
		}
		// Создание поста
		submit_post('reply', $Title, '', POST_NORMAL, $poll, $data);
		return "http://".$_SERVER['HTTP_HOST']."/forum/viewtopic.php?f=".$ForumId."&t=".$TopicId."&p=".$data['post_id']."#p".$data['post_id'];
	}
	
	public function EditPost($ForumId, $TopicId, $PostId, $vPostType, $PosterId, $Title, $PollTitle, $PollOptionText, $Text) {
		// Редактирование поста с голосованием
		$uid = $bitfield = $options = ''; 
		generate_text_for_storage($Title, $uid, $bitfield, $options, false, false, false);
		generate_text_for_storage($PollTitle, $uid, $bitfield, $options, false, false, false);
		// Парсить текст сообщения а голосовалки нужно вместе, иначе какие-то траблы с несоответствием uid и bitfield
		// Поэтому слияние - парсинг - разделение
		$allText = $Text."###".$PollOptionText;
		
		generate_text_for_storage($allText, $uid, $bitfield, $options, true, true, true);
		$message_parser = new parse_message();
		$message_parser->parse_message($allText);
		
		$allTextArr = explode("###", $allText);
		$Text = $allTextArr[0];
		$PollOptionText = $allTextArr[1];
		// Создание поста
		$data = array( 
			'forum_id'			=> $ForumId,
			'topic_id'			=> $TopicId,
			'post_id'			=> $PostId,
			'poster_id'			=> $PosterId,
			'icon_id'			=> false,
			'enable_bbcode'		=> true,
			'enable_smilies'	=> true,
			'enable_urls'		=> true,
			'enable_sig'		=> true,
			'message'			=> $Text,
			'message_md5'		=> md5($Text),
			'bbcode_bitfield'	=> $bitfield,
			'bbcode_uid'		=> $uid,
			'post_edit_locked'	=> 0,
			'topic_title'		=> $Title,
			'notify_set'		=> false,
			'notify'			=> false,
			'topic_priority'	=> 10,
			'post_time' 		=> 0,
			'forum_name'		=> '',
			'topic_first_post_show'	=> 1,
			'enable_indexing'	=> true
		);
		
		$poll = array(
			'poll_title'      => $PollTitle,
			'poll_length'      => 0,
			'poll_max_options'   => 1,
			'enable_bbcode'		=> true,
			'enable_smilies'	=> true,
			'enable_urls'		=> true,
			'poll_option_text'   => $PollOptionText,
			'poll_start'      => 0,
			'poll_last_vote'    => 0,
			'poll_vote_change'   => true,
			'poll_show_voters'   => true,
			'img_status'		=> 1
		);
		
		$message_parser->parse_poll($poll);
		// Отправка подготовленного поста
		if(!function_exists('submit_post')) {	// Приходится делать include здесь т.к. если делать где положено, дальше идет redeclare функций из functions_posting
//			global $phpbb_root_path;
//			include_once($phpbb_root_path."includes/functions_posting.php");
			include_once("/home/itcomp/b3d.org.ua/www/forum/includes/functions_posting.php");
		}
		$PostType = POST_NORMAL;
		if($vPostType=="POST_GLOBAL") $PostType = POST_GLOBAL;
//		submit_post('edit', $Title, '', POST_NORMAL, $poll, $data);
//		submit_post('edit', $Title, '', POST_GLOBAL, $poll, $data);
		submit_post('edit', $Title, '', $PostType, $poll, $data);
		return "http://".$_SERVER['HTTP_HOST']."/forum/viewtopic.php?f=".$ForumId."&t=".$TopicId."&p=".$data['post_id']."#p".$data['post_id'];
	}
	
	public function Logg($txt) {
		$file = fopen ("/home/itcomp/b3d.org.ua/www/forum/conkurs/ek_phpbbauto.txt","a+");
		fputs($file, "> ".$txt."\r\n");
	}
	
	public function unParse($text, $uid) {
		// Возврат распарсенного текста (для редактирования)
		$flags = '';
		$rez = generate_text_for_edit($text, $uid, $flags);
//		return htmlspecialchars_decode($rez["text"]);
		return html_entity_decode($rez["text"]);
	}
}
?>