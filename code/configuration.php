<?php

$hoster = $_SERVER["HTTP_HOST"];
$hoster_array = explode(":",$hoster);
$hostingserverfile = $hoster_array[0];
$hostingserverfile .= '.php';
$hostingserverpath = 'configs/' . $hostingserverfile;
include ($hostingserverpath);


?>
