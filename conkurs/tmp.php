<?php
require_once("konkursrank.inc.php");

$kr = new KonkursRank();

echo $kr->getRank(530);
echo "<br>";
echo $kr->getRank(527);
echo "<br>";
echo $kr->getRank(554);
echo "<br>";
echo $kr->getRank(547);
echo "<br>";
echo $kr->getRank(2);

?>