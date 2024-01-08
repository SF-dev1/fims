<?php
//DEFINE FOLDERS
$folders = array(
	array(
		'folder_path' => ROOT_PATH.'/labels',
		'delete_after' => 7
	),
	array(
		'folder_path' => ROOT_PATH.'/labels/single',
		'delete_after' => 7
	),
	array(
		'folder_path' => ROOT_PATH.'/labels/final',
		'delete_after' => 7
	),
	array(
		'folder_path' => ROOT_PATH.'/labels/manifest',
		'delete_after' => 7
	),
	array(
		'folder_path' => ROOT_PATH.'/labels/POD',
		'delete_after' => 7
	),
	array(
		'folder_path' => UPLOAD_PATH.'/amazon_pickup',
		'delete_after' => 7
	),
	array(
		'folder_path' => UPLOAD_PATH.'/amazon_returns',
		'delete_after' => 7
	),
	array(
		'folder_path' => UPLOAD_PATH.'/bulk_alias',
		'delete_after' => 7
	),
	array(
		'folder_path' => UPLOAD_PATH.'/elegible-listings',
		'delete_after' => 7
	),
	array(
		'folder_path' => UPLOAD_PATH.'/order_csv',
		'delete_after' => 7
	),
	array(
		'folder_path' => UPLOAD_PATH.'/promotion_offer',
		'delete_after' => 180
	),
	array(
		'folder_path' => UPLOAD_PATH.'/sellers_docs',
		'delete_after' => 0
	),
	array(
		'folder_path' => UPLOAD_PATH.'/invoices',
		'delete_after' => 7
	),
	array(
		'folder_path' => UPLOAD_PATH.'/cod_pincodes',
		'delete_after' => 7
	),
	array(
		'folder_path' => UPLOAD_PATH.'/crest',
		'delete_after' => 7
	),
	array(
		'folder_path' => TODAYS_LOG_PATH,
		'delete_after' => 7
	),
	array(
		'folder_path' => TODAYS_LOG_PATH.'/min-price',
		'delete_after' => 7
	),
	array(
		'folder_path' => TODAYS_PICKLIST_PATH,
		'delete_after' => 7
	),
	array(
		'folder_path' => TODAYS_STOCKIST_PATH,
		'delete_after' => 7
	)
);

foreach ($folders as $folder) {
	$path = $folder['folder_path'];
	if (!is_dir($path))
		mkdir($path, 0775, true);

	$timeAfter = 60 * 60 * 24 * $folder['delete_after'];
	if ($path == TODAYS_LOG_PATH){
		$path = TODAYS_LOG_PATH.'/'.date("Y", (time() - $timeAfter));
	}
	if (date('H', time()) == '03' && $timeAfter !== 0)
		delete_unwanted_files($path, $timeAfter);
}

function delete_unwanted_files($path, $timeAfter){
	if (is_dir($path)){
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) { 
				if(preg_match("/^.*\.(pdf|log|csv|xlsx)$/i", $file)){
					$fpath = $path.'/'.$file;
					if (file_exists($fpath))
						$filelastmodified = filemtime($fpath);

					if ( (time() - $filelastmodified ) > $timeAfter)
						unlink($fpath);
				}
			}
			closedir($handle);
		}
		delete_empty_folder($path);
	}
}

function delete_empty_folder($path){
	$empty = true;
	foreach (glob($path.DIRECTORY_SEPARATOR."*") as $file){
		$empty &= is_dir($file) && delete_empty_folder($file);
	}
	return $empty && rmdir($path);
}
?>