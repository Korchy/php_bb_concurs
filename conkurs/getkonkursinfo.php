<?php
require_once("config.inc.php");
require_once("konkurs.inc.php");

$basePath = "/home/itcomp/b3d.org.ua/www/forum/conkurs/";

$Conf = new Config($basePath."config.ini");

$rez = array();
foreach($Conf->Data["konkurs"] as $key => $value) {
	if(isset($_POST["forSlider"])) {
		// Данные для слайдера
		$konkurs = new Konkurs($key);
		$forSlider = $konkurs->sliderInfo();
		$rez[] = array("http://b3d.org.ua/forum/viewtopic.php?f=".$value["forumid"]."&t=".$value["topicid"],	// link
			$value["title"],												// title
			$forSlider[0],													// img
			$forSlider[1],													// theme
			$forSlider[2]													// autor
			);
	}
	else {
		// Данные для формы загрузки изображений
		$rez[] = array("f=".$value["forumid"]."&t=".$value["topicid"], $key);
	}
}
echo json_encode($rez);
?>