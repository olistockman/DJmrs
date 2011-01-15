<?php

include_once ('includes/getid3/getid3.php');
include_once(GETID3_INCLUDEPATH.'getid3.functions.php'); // Function library
include_once ('includes/db_conf.php');

$directory = "/mediaRAID/drop_mp3_here/bcs/";

$files = scandir($directory);

var_dump($files);

/*$temp_fileout = "query_file.txt";
$fw = fopen($temp_fileout,'w');*/

foreach ($files as $file) {

	if ($file == "." or $file == "..") continue;

	$filename = $directory.$file;

	if (is_dir($filename)) continue;

	$filemd5 = md5_file($filename);

	echo $filename."\n".$filemd5."\n";

	$fileinfo = GetAllMP3info($filename, '');
	if (!isset($MP3fileInfo['fileformat']) || ($MP3fileInfo['fileformat'] == '')) {
                $formatExtensions = array('mp3'=>'mp3', 'ogg'=>'ogg', 'zip'=>'zip', 'wav'=>'riff', 'avi'=>'riff', 'mid'=>'midi', 'mpg'=>'mpeg','jpg' => 'jpg');
                if (isset($formatExtensions[fileextension($filename)])) {
                        $fileinfo = GetAllMP3info($filename, $formatExtensions[fileextension($filename)]);
                }
        }

	foreach ($fileinfo as $key => $value) {
		if (is_array($value)) continue;
		$fileinfo[$key] = mysql_real_escape_string($value);
	}

	if (array_key_exists('id3v2',$fileinfo['id3'])){
		$id3 = $fileinfo['id3']['id3v2'];
	} else {
		$id3 = $fileinfo['id3']['id3v1'];
	}

	foreach ($id3 as $key => $value) {
		if (is_array($value)) continue;
		$id3[$key] = mysql_real_escape_string($value);
	}

	mysql_real_escape_string($filemd5);
	mysql_real_escape_string($file);
	mysql_real_escape_string($directory);

	$dbq = "INSERT INTO `dm_music`(
	`file_md5`,
	`file_name`,
	`file_path`,
	`file_type`,
	`file_size`,
	`id3_title`,
	`id3_artist`,
	`id3_album`,
	`id3_year`,
	`id3_comment`,
	`id3_track`,
	`id3_genre`,
	`file_bitrate`,
	`file_playtime_seconds`,
	`file_playtime_string`)
	VALUES (
	'$filemd5',
	'$file',
	'$directory',
	'".$fileinfo['fileformat']."',
	'".$fileinfo['filesize']."',
	'".$id3['title']."',
	'".$id3['artist']."',
	'".$id3['album']."',
	'".$id3['year']."',
	'".$id3['comment']."',
	'".$id3['track']."',
	'".$id3['genre']."',
	'".$fileinfo['bitrate']."',
	'".$fileinfo['playtime_seconds']."',
	'".$fileinfo['playtime_string']."');\n";

	/*fwrite($fw,$dbq);*/

	mysql_query($dbq) or die (mysql_error());

}
?>
