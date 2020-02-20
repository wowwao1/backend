<?php
function APIsuccess($msg = 'success',$data = array(),$current_page = '',$total_page = '',$total_records = '')
{
	if($current_page != '' && $total_page != '' && $total_records != '')
	{
		echo json_encode(array("status" => true, "message" => $msg, "current_page" => $current_page, "total_page" => $total_page, "total_records" => $total_records, "data" => $data), JSON_PRETTY_PRINT);
	}
	else
	{
		echo json_encode(array("status" => true, "message" => $msg, "data" => $data), JSON_PRETTY_PRINT);	
	}
	exit;
}

function APIerror($msg = NRF)
{
	echo json_encode(array("status" => false, "message" => $msg, "data" => array()), JSON_PRETTY_PRINT);
	exit;
}

function ChkVar(&$var, $trim = true)
{
	if($trim == true)
		return (isset($var) && trim($var) != '' && $var != null && $var != 'null');
	else
		return (isset($var) && $var != '');
}

function TZ($timezone)
{
    // return in_array($timezone, timezone_identifiers_list());
    return true;
}

function DateFormat($date, $type = 'DateTime')
{
    if($type == 'DateTime')
        return (date('Y-m-d h:i a' ,strtotime($date)));
    else if($type == 'Date')
        return (date('F d, Y' ,strtotime($date)));
    else if($type == 'Time')
        return (date('h:i A' ,strtotime($date)));
    else
        return (date('Y-m-d h:i a' ,strtotime($date)));
}

function ImageCompress($source, $destination, $quality, $type = '') {

    if($type == '')
    {
        $info = getimagesize($source);
        // Get new sizes
        list($width, $height) = getimagesize($source);
        if($width > 2000 || $height > 2000)
        {
            $newwidth = $width * 0.3;
            $newheight = $height * 0.3;
            $quality = 90;
        }
        elseif($width > 1000 || $height > 1000)
        {
            $newwidth = $width * 0.6;
            $newheight = $height * 0.6;
            $quality = 90;
        }
        else
        {
            $newwidth = $width * 0.9;
            $newheight = $height * 0.9; 
        }

        // Load
        $thumb = imagecreatetruecolor($newwidth, $newheight);

        if ($info['mime'] == 'image/jpeg') 
            $image = imagecreatefromjpeg($source);

        elseif ($info['mime'] == 'image/gif') 
            $image = imagecreatefromgif($source);

        elseif ($info['mime'] == 'image/png') 
            $image = imagecreatefrompng($source);

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        imagejpeg($thumb, $destination, $quality);
    }
    else if($type == 'cover')
    {
        $info = getimagesize($source);
        
        if ($info['mime'] == 'image/jpeg') 
            $image = imagecreatefromjpeg($source);

        elseif ($info['mime'] == 'image/gif') 
            $image = imagecreatefromgif($source);

        elseif ($info['mime'] == 'image/png') 
            $image = imagecreatefrompng($source);

        
        $thumb_width = 1000;
        $thumb_height = 444;
        $width = imagesx($image);
        $height = imagesy($image);
        $original_aspect = $width / $height;
        $thumb_aspect = $thumb_width / $thumb_height;
        if ( $original_aspect >= $thumb_aspect )
        {
           // If image is wider than thumbnail (in aspect ratio sense)
           $new_height = $thumb_height;
           $new_width = $width / ($height / $thumb_height);
        }
        else
        {
           // If the thumbnail is wider than the image
           $new_width = $thumb_width;
           $new_height = $height / ($width / $thumb_width);
        }
        $thumb = imagecreatetruecolor( $thumb_width, $thumb_height );
        // Resize and crop
        imagecopyresampled($thumb,
                           $image,
                           0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
                           0 - ($new_height - $thumb_height) / 2, // Center the image vertically
                           0, 0,
                           $new_width, $new_height,
                           $width, $height);
        imagejpeg($thumb, $destination, $quality);
    }

}

function generateRandom($length = 10, $type = 'alphanum') {
	if($type == 'alphanum')
    	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    else if($type == 'num')
    	$characters = '0123456789';
    else if($type == 'alpha')
    	$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    else
    	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function delete_directory($dirname) {
         if (is_dir($dirname))
           $dir_handle = opendir($dirname);
     if (!$dir_handle)
          return false;
     while($file = readdir($dir_handle)) {
           if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file))
                     unlink($dirname."/".$file);
                else
                     delete_directory($dirname.'/'.$file);
           }
     }
     closedir($dir_handle);
     rmdir($dirname);
     return true;
}

function checkImage($imageName,$id =0,$subfolder="",$defaultImage ="no_image_thumb.png", $cords = array(), $zc=1,$ql=100) {
	global $db;

    $flag = false;
	$src = "no_image_thumb.png";
    if(empty($cords)){
        $q = $db->select("tbl_imagethumb","*",array("id"=>$id));
        if($q->affectedRows() > 0){
            $fetchRes = $q->result();
            $flag = true;
        }
    }
    else
    {
        $fetchRes = $cords;
        $flag = true;
    }


	if($flag)
	{

		$filepath = $fetchRes['folder']."/".$subfolder.$imageName;
		/*echo DIR_UPD.$filepath;die;*/
		if(is_file(DIR_UPD.$filepath))
		{
			$src = SITE_URL."image-thumb/".$fetchRes['width']."/".$fetchRes['height']."/".$zc."/".$ql."/?src=".$filepath;
		}
		else
		{
			$filepath = $fetchRes['folder']."/".$defaultImage;
			$src = SITE_URL."image-thumb/".$fetchRes['width']."/".$fetchRes['height']."/".$zc."/".$ql."/?src=".$filepath;

		}
	}

	return $src;
}