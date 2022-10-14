<?php
/**
 * @author lazyvlad
 * @since 10/15/2022 (1.0.0)
 * @version 1.0.0
 * @note THIS IS NOT A PRODUCTION SCRIPT, it's for showcase purposes!!!
 * FOR PRODUCTION always sanitize the input from the GET parameters
 */


	$server_name = $_SERVER['SERVER_NAME'];

	//this is usually empty as you host your app probably in the root dir of your host. However if your access it on a subfolder set this value
	$sub_folder = 'images';

	//show the file if it's an image  instead of returning a json object . not sure why we would want this, but here it is
	$show_file_if_last = false;

	//open_basedir violations don't throw exceptions but warnings and that's why we turn all errors into exceptions
	set_error_handler(function($errno, $errstr, $errfile, $errline) {
		// error was suppressed with the @-operator
		if (0 === error_reporting()) {
			return false;
		}
		
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	});

	//set open_basedir ini config. This denies access to the parent folder so if they send us a path like ./../../ , hich will results into going two folders up and showing the content of home folder, this directive will stop that
	ini_set('open_basedir',dirname(__FILE__));

	//we build URLs based on entry later on
	$entry_point = "https://${server_name}/${sub_folder}";
	

	//show data as json
	$as_api = true;

	//starting date
	$cut_date = (isset($_GET['cut_date'])) ? $_GET['cut_date'] : false;
	//ending date
	$cut_date_end = (isset($_GET['cut_date_end'])) ? $_GET['cut_date_end'] : false;


	//which directory to scan (relative folder)
	$directory_to_scan = (isset($_GET['folder'])) ? $_GET['folder'] : 'data';

	//should we use recursion to show the result or just get the data in the current directory and don't care about subfolder traversal
	$depth_search = (isset($_GET['depth_search'])) ? $_GET['depth_search'] : true;

	//how do we return the results
	$return_direction = (isset($_GET['return_direction'])) ? $_GET['return_direction'] : 'older';
	
	// STYLING (light or dark)
	$color	= "light";
	
	// ADD SPECIFIC FILES YOU WANT TO IGNORE HERE
	$ignore_file_list = array( ".htaccess", "Thumbs.db", ".DS_Store", "index.php", "node_modules" );
	
	// ADD SPECIFIC FILE EXTENSIONS YOU WANT TO IGNORE HERE, EXAMPLE: array('psd','jpg','jpeg')
	$ignore_ext_list = array( );
	
	// SORT BY
	$sort_by = "date_desc"; // options: name_asc, name_desc, date_asc, date_desc //this is a bit tricky atm doesn't really work properly
	
	
	// TOGGLE SUB FOLDERS, SET TO false IF YOU WANT OFF TODO remove this
	$toggle_sub_folders = true;
	
	// IGNORE EMPTY FOLDERS
	$ignore_empty_folders = true;


/**
 * $filename
 * get the extention
 */
function ext($filename) 
{
	return substr( strrchr( $filename,'.' ),1 );
}
/**
 * get the dir name
 */
function dir_name($dir_name){
	return substr( strrchr( $dir_name,'/' ),1 );
}

/**
 * is the file image?
 */
function is_image($filename){

	if(@is_array(getimagesize($filename))){
		$image = true;
	} else {
		$image = false;
	}	

	return $image;
}
/**
 * calculate some size
 */
function display_size($bytes, $precision = 2) 
{
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= (1 << (10 * $pow)); 
	return round($bytes, $precision) . $units[$pow];
}
/**
 * count the number of files in a directory
 */
function count_dir_files( $dir)
{
	$fi = new FilesystemIterator(__DIR__ . "/" . $dir, FilesystemIterator::SKIP_DOTS);
	return iterator_count($fi);
}
/**
 * get the directory size
 */
function get_directory_size($path)
{
    $bytestotal = 0;
    $path = realpath($path);
    if($path!==false && $path!='' && file_exists($path))
    {
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object)
        {
            $bytestotal += $object->getSize();
        }
    }
    
    return display_size($bytestotal);
}
/**
 * $file
 * $cutd int - cut date based on return direction will return older or younger than this date
 * $return_direction - default value is older
 */
function include_in_response($file){


	global $cut_date,$cut_date_end;

	$modified_at = filemtime($file);

	if($cut_date){
		if($cut_date_end){
			return ($modified_at > $cut_date && $modified_at < $cut_date_end);				
		}
		else{
			return ($modified_at > $cut_date);
		}
	}
	else{
		if($cut_date_end){
			return ($modified_at<$cut_date_end);
		}
		else{
			return true;
		}
	}



	
	
}

/**
 * return an object of file properties 
 */ 
function display_block( $file )
{
	global $ignore_file_list, $ignore_ext_list, $entry_point,$cut_date,$return_direction,$cut_date_end;
	
	$file_ext = ext($file);
	if( !$file_ext AND is_dir($file)) $file_ext = "dir";
	if(in_array($file, $ignore_file_list)) return;
	if(in_array($file_ext, $ignore_ext_list)) return;
	
	
    $return = array();
	if ($file_ext === "dir") 
	{

        $return = array(
            'type' => 'dir',
            'file' => $file,
            'file_ext' => 'dir',
            'basename_file' => basename($file),
            'count_dir_files' => count_dir_files($file),
            'get_directory_size' => get_directory_size($file),
			'last_modified_t' => filemtime($file),
            'last_modified' => date("D. F jS, Y - h:ia", filemtime($file)),
			'cut_date' => date("D. F jS, Y - h:ia", $cut_date),
			'cut_date_end' => date("D. F jS, Y - h:ia", $cut_date_end),
			'return_direction' => $return_direction
        );
		
	}
	else
	{
        $return = array(

            'type' =>'file',
            'file' => $file,
            'file_ext' => $file_ext,
			'full_url' => $entry_point . '/' . $file,
			'is_image' => is_image($file),
            'basename_file' => basename($file),
            'file_size'     => display_size(filesize($file)),
			'last_modified_t' => filemtime($file),
            'last_modified' => date("D. F jS, Y - h:ia", filemtime($file)),
			'cut_date' => date("D. F jS, Y - h:ia", $cut_date),
			'cut_date_end' => date("D. F jS, Y - h:ia", $cut_date_end),			
			'return_direction' => $return_direction
        );
	}

    return $return;


}

$final_listing = array();
// RECURSIVE FUNCTION TO BUILD THE BLOCKS
function build_blocks( $items, $folder, $step=0, $listing =array())
{
	global $ignore_file_list, $ignore_ext_list, $sort_by, $toggle_sub_folders, $ignore_empty_folders,$cut_date,$return_direction,$depth_search;


	
	$objects = array();
	$objects['directories'] = array();
	$objects['files'] = array();
	
	foreach($items as $c => $item)
	{
		if( $item == ".." OR $item == ".") continue;
	
		// IGNORE FILE
		if(in_array($item, $ignore_file_list)) { continue; }
	
		if( $folder && $item )
		{
			$item = "$folder/$item";
		}

		$file_ext = ext($item);
		
		// IGNORE EXT
		if(in_array($file_ext, $ignore_ext_list)) { continue; }
		
		// DIRECTORIES
		if( is_dir($item) ) 
		{
			$objects['directories'][] = $item; 
			continue;
		}
		
		// FILE DATE
		$file_time = date("U", filemtime($item));
		
		// FILES
		if( $item )
		{
			$objects['files'][] = $item;

		}
	}
	
	foreach($objects['directories'] as $c => $file)
	{
		$sub_items = (array) scandir( $file );
		
		if( $toggle_sub_folders )
		{
			if( $sub_items )
			{

				if(include_in_response($file,$cut_date,$return_direction)){
					$listing[] = array(
						'name' 	=> 	dir_name($file),
						 'type' => 	'dir',
						 'data' => 	display_block($file),
						 'subs' => 	($depth_search) ? build_blocks($sub_items,$file,$step++) : null //if it has subfolders then we do build_blocks again, to infinity
					);
					
				}

			}
		}
	}
	
	// SORT BEFORE LOOP
	if( $sort_by == "date_asc" ) { ksort($objects['files']); }
	elseif( $sort_by == "date_desc" ) { krsort($objects['files']); }
	elseif( $sort_by == "name_asc" ) { natsort($objects['files']); }
	elseif( $sort_by == "name_desc" ) { arsort($objects['files']); }
	
	foreach($objects['files'] as $t => $file)
	{
		$fileExt = ext($file);
		if(in_array($file, $ignore_file_list)) { continue; }
		if(in_array($fileExt, $ignore_ext_list)) { continue; }
		if(!include_in_response($file,$cut_date,$return_direction)){continue; }
		$listing[] = array('name' => dir_name($file), 'type'=>'file', 'data' => display_block($file));
	}

    return $listing;
}
$items = array();

/**
 *
 * send the file to the browser if it's an image
 */

 if($show_file_if_last){
	 
	 if(is_file(dirname(__FILE__) . '/'. $directory_to_scan)){
		if(is_image($file)){

			$file = dirname(__FILE__) . '/'. $directory_to_scan;
			$fp = fopen($file, 'rb');
		
			header("Content-Type: image/".ext($file));
			header("Content-Length: " . filesize($file));
		
			fpassthru($fp);
			exit;

		} else {
			//handle code if not image, CBA to do it now
		}

	 }
 }


try{
	if(is_file(dirname(__FILE__) . '/'. $directory_to_scan)){

		$file = dirname(__FILE__) . '/'. $directory_to_scan;
		$file_ext = ext($file);

		$return = array(

			'type' =>'file',
			'file' => $file,
			'file_ext' => $file_ext,
			'full_url' => $entry_point . '/' . $file,
			'is_image' => is_image($file),
			'basename_file' => basename($file),
			'file_size'     => display_size(filesize($file)),
			'last_modified_t' => filemtime($file),
			'last_modified' => date("D. F jS, Y - h:ia", filemtime($file)),
			'cut_date' => date("D. F jS, Y - h:ia", $cut_date),
			'cut_date_end' => date("D. F jS, Y - h:ia", $cut_date_end),			
			'return_direction' => $return_direction
		);	

		echo json_encode($return);
		exit;

	}

	if(is_dir(dirname(__FILE__) . '/'. $directory_to_scan))
		$items = scandir( dirname(__FILE__) . '/'. $directory_to_scan );

}
catch(Exception $err){
	echo json_encode(array());
	exit;
}



// GET THE BLOCKS STARTED, FALSE TO INDICATE MAIN FOLDER

$final_listing = build_blocks( $items, $directory_to_scan,0);



// echo json_encode($cut_date);
echo json_encode($final_listing);
?>


