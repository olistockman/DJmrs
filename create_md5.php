<?php
include_once ('includes/getid3/getid3.php');
include_once (GETID3_INCLUDEPATH.'getid3.functions.php'); // Function library
include_once ('includes/db_conf.php');

mysql_connect($db_host,$db_user,$db_pass) or die(mysql_error());
mysql_select_db($db_database) or die(mysql_error());

$start_directory = "/mediaRAID/drop_mp3_here/music";

/*$files = scandir($directory);*/

/*var_dump($files);*/

DEFINE ("FILEOUT", "query_file.txt");

subdir_scan($start_directory);

function subdir_scan($directory){
	$files = scandir($directory);

	foreach ($files as $file) {

		if ($file == "." or $file == "..") continue;

		if (is_dir($directory."/".$file)){
			subdir_scan($directory."/".$file);
		} else {
			file_to_db($directory."/".$file);
		}
	}
}

function file_to_db($filename){

	$directory = dirname($filename);

	$filemd5 = md5_file($filename);

	$fileinfo = GetAllMP3info($filename, '');
	if (!isset($MP3fileInfo['fileformat']) || ($MP3fileInfo['fileformat'] == '')) {
                $formatExtensions = array('mp3'=>'mp3', 'ogg'=>'ogg', 'zip'=>'zip', 'wav'=>'riff', 'avi'=>'riff', 'mid'=>'midi', 'mpg'=>'mpeg','jpg' => 'jpg');
                if (isset($formatExtensions[fileextension($filename)])) {
                        $fileinfo = GetAllMP3info($filename, $formatExtensions[fileextension($filename)]);
                }
        }

	if ($fileinfo['fileformat'] == "mp3") {

	if (array_key_exists('id3v2',$fileinfo['id3'])){
		$id3 = $fileinfo['id3']['id3v2'];
	} else {
		$id3 = $fileinfo['id3']['id3v1'];
	}

	clean_array($fileinfo);
	clean_array($id3);

	/* MUST ADD FULL ESCAPING */
	mysql_real_escape_string($filemd5);
	mysql_real_escape_string($filename);
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
	'$filename',
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

	$fw = fopen(FILEOUT,'a+');
	fwrite($fw,$dbq);

	echo $filename." - ".$filemd5."\n";

	/*mysql_query($dbq) or die (mysql_error());*/

	}

}

function clean_array($array){
	foreach ($array as $key => $value){
		if (is_array($value)) continue;
		$array[$key] = mysql_real_escape_string($value);
	}
	return $array;
}

?>
