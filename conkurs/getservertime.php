<?php
list($usec, $sec) = explode(" ", microtime());
$currentTime = round(((float)$usec + (float)$sec)*1000);
$now = new DateTime('NOW');
$currentTime += ($now->getOffset())*1000;
echo $currentTime;
?>