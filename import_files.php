<?php
/* DJmrs - Dj Media Resource Server

    Copyright (C) 2011  Oliver Stockman

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

    To contact the author, please visit http://www.vfdevelopers.co.uk

*/

include_once ('includes/getid3/getid3.php');
include_once (GETID3_INCLUDEPATH.'getid3.functions.php'); // Function library
include_once ('includes/db_conf.php');

DEFINE ("FILEOUT", "tmp/query_file.txt"); //used for debugging
$fw = fopen(FILEOUT,'w+'); //used for debugging

function get_options() {
	global $fw;
	$options = array();
	$dbq = "SELECT c.key, c.value FROM dm_config AS c";
	$result = mysql_query($dbq) or die (mysql_error());

	while ($row = mysql_fetch_assoc($result)) {
		$options[$row['key']] = $row['value'];
	}

	return $options;

	foreach ($options as $key => $value) {
		fwrite($fw,$key." - ".$value."\n");
	}

}

$options = get_options();

foreach ($options AS $key => $value) {
	if (preg_match('#^scan_directory#',$key)) {
		subdir_scan($value);
	}
}

function subdir_scan($directory){
	global $options;
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

function move_file_into_library($filename,$file_path,$to_path,$db_id) {

	global $options;
	global $fw;
	//fwrite($fw,$filename."--".$file_path."--".$to_path."--".$db_id."\n");

	if (!empty($filename) AND !empty($file_path) AND !empty($to_path)) {

		$to_path = preg_replace('#\s#','_',$to_path);
		$to_path = preg_replace('#\W#','',$to_path);

		$to_path = $options['library_directory']."/".$to_path;

		//fwrite($fw,$filename."--".$file_path."--".$to_path."--".$db_id."\n");}
		if (!file_exists($to_path))
			mkdir($to_path);

		if (copy($file_path."/".$filename,$to_path."/".$filename)) {
			//unlink($file_path."/".$filename);
		  	if (isset($db_id) AND !empty($db_id)) {
				$to_path = mysql_real_escape_string($to_path);
				$dbq = "UPDATE dm_music SET file_path = '$to_path' WHERE id = $db_id;";

				mysql_query($dbq) or die (mysql_error());
			}
		}
	}
}


function file_to_db($filename){

	global $fw;
	global $options;

	$directory = dirname($filename);
	$file_short_name = basename($filename);

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

		$fileinfo = clean_array($fileinfo);
		$id3 = clean_array($id3);

		$filemd5 = mysql_real_escape_string($filemd5);
		$file_short_name = mysql_real_escape_string($file_short_name);
		$directory = mysql_real_escape_string($directory);

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
		'$file_short_name',
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

		//fwrite($fw,$dbq);

		echo $filename." - ".$filemd5."\n";

		mysql_query($dbq) or die (mysql_error());

		$insert_id = mysql_insert_id();

		if (isset($id3['album']) AND !empty($id3['album'])) {
			move_file_into_library($file_short_name,$directory,$id3['album'],$insert_id);
		}
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
