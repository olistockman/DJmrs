<?php

include_once ('getid3/getid3.php');
include_once(GETID3_INCLUDEPATH.'getid3.functions.php'); // Function library


$directory = "/mediaRAID/drop_mp3_here/bcs/";

$files = scandir($directory);

var_dump($files);

foreach ($files as $file) {

	if ($file == "." or $file == "..") continue;

	$filepath = $directory.$file;

	if (is_dir($filepath)) continue;

	echo $filepath . '\n' . md5_file($filepath);

	$id3 = GetAllMP3info($filepath, '');
                if (!isset($MP3fileInfo['fileformat']) || ($MP3fileInfo['fileformat'] == '')) {
                        $formatExtensions = array('mp3'=>'mp3', 'ogg'=>'ogg', 'zip'=>'zip', 'wav'=>'riff', 'avi'=>'riff', 'mid'=>'midi', 'mpg'=>'mpeg','jpg' => 'jpg');
                        if (isset($formatExtensions[fileextension($filepath)])) {
                                $id3 = GetAllMP3info($filepath, $formatExtensions[fileextension($filepath)]);
                        }
                }

	print_r($id3);

}


?>
