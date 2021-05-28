<?php
require_once("config.inc.php");
require_once("konkurs.inc.php");

$basePath = "/home/itcomp/b3d.org.ua/www/forum/conkurs/";

$Conf = new Config($basePath."config.ini");
foreach($Conf->Data["konkurs"] as $key => $value) {
	$konkurs = new Konkurs($key);
	$konkurs->check();
}
?>