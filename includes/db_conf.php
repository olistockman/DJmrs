<?php

$db_host = "localhost";
$db_user = "djmrs";
$db_pass = "djmrs";
$db_database = "DJmrs";

mysql_connect($db_host,$db_user,$db_pass) or die(mysql_error());
@mysql_select_db($db_database) or die(mysql_error());

?>
