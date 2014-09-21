<?php
require('config.php');
include('progressbar.php');

/**
 * Upload Pocket import file and return all the link, title, tags, etc in array. 
 * @return Array Return the link from the Pocket import file.
 */
function uploadHTMLlinks(){
	$htmlLinks = (isset($_FILES["file"]) ? $_FILES["file"] : '' );

	if($htmlLinks){
		$allowedExts = array("html");
		$temp = explode(".", $htmlLinks['name']);	// Separate file name and it's extension
		$file_ext = strtolower(end($temp));
		$errors= array();
		$file_type= $_FILES["file"]["type"]; 
		$file_name = $_FILES["file"]["name"];
		$file_tmp =$_FILES["file"]["tmp_name"];
		$link_title = array();
		$link_url = array();
		$link_tags = array();
		$link_time_added = array();
		

		// Validate uploaded HTML file
		if($file_type !== "text/html"){
			$errors[]="file type not allowed, please choose a html file.";
		}
		if(in_array($file_ext, $allowedExts)=== false){
			$errors[]="extension not allowed, please choose a html file.";
		}
		if(empty($errors)==true){
	        move_uploaded_file($file_tmp,"uploads/".$file_name);
	   		
	        //Read uploaded file
	        $html = file_get_html("uploads/".$file_name);

	        // Add each value to it's own array
	        foreach($html->find('a') as $element){ 
	        	array_push($link_title, $element->innertext);
       		}

	        foreach($html->find('a') as $element){ 
	        	array_push($link_url, $element->href);
       		}

       		foreach($html->find('a') as $element){ 
       			array_push($link_tags, $element->tags);
       		}

       		foreach($html->find('a') as $element){ 
       			array_push($link_time_added, $element->time_added);
       		}

       		// Combine to one array ($link_article) for each individual array (title, link, url, tags)
			for($i=0; $i< count($link_url); $i++) { 
				$link_article[] = array("title" => $link_title[$i], "url" => $link_url[$i], "tags" => $link_tags[$i], "time_added" => $link_time_added[$i]);
			}

       		return $link_article;
	    }
	    else{
	        foreach ($errors as $error) {
	        	echo "$error \n";
	        }
	    }
		
	}
}

/**
 * Get URLs from Array of uploadHTMLlinks()
 * @param  array  $array from uploadHTMLlinks
 * @return array  URLs from uploadHTMLlinks()
 */
function getListURL($array = array()){
	for ($i = 0; $i <count($array); $i++) {
		$uploadedURL[] = $array[$i]['url'];
	}

	return $uploadedURL;
}

/**
 * Get Tags from Array of uploadHTMLlinks()
 * @param  array  $array from uploadHTMLlinks
 * @return array  Tags from uploadHTMLlinks()
 */
function getListTags($array = array()){
	for ($i = 0; $i <count($array); $i++) {
		$uploadedURL[] = $array[$i]['tags'];
	}

	return $uploadedURL;
}

/**
 * Get Title from Array of uploadHTMLlinks()
 * @param  array  $array from uploadHTMLlinks
 * @return array  Title from uploadHTMLlinks()
 */
function getListTitle($array = array()){
	for ($i = 0; $i <count($array); $i++) {
		$uploadedURL[] = $array[$i]['title'];
	}

	return $uploadedURL;
}

/**
 * Parse and Import all link to WordPress
 * @param  integer $parser     Content parser used, 0 for using Readability API, 1 for using Fivefilters API.
 * @param  string  $url        URL list that will be parsed and imported to WordPress
 * @param  array   $categories Category for the imported articles.
 * @param  array   $tags       Tags for the imported articles.
 * @return Void              
 */
function importToWordPress($parser = 0, $url, $categories = array(), $tags = array()){
	$objXMLRPClientWordPress = new XMLRPClientWordPress(WP_URL , WP_USERNAME , WP_PASSWORD);

	if(!is_null($url) || !empty($url)){
		for ($i = 0; $i <count($url); $i++) {
			$startItem =  $i+1;
			$totalItem = count($url);
			$progressPecentage = ($startItem*100)/$totalItem;

			try{
				if($parser == 0){	// USE Readability as Parser
					$content = file_get_contents(READABILITY_URL.'?url='.$url[$i].'&token='.READABILITY_TOKEN);
				}
				else{	// USE Fivefilters as Parser
					$content = file_get_contents(FIVEFILTERS_URL.'?url='.$url[$i].'&links=preserve&format=json');
				}

				// Check for any error on HTTP request
				if($content === false){
					$allowedExts = array("jpg", "jpeg", "png", "gif");
					$temp = explode(".", $url[$i]);						// Separate file name and it's extension
					$file_ext = strtolower(end($temp));

					//If the error url is image, add new post as images (For Readability API).
					if(in_array($file_ext, $allowedExts) === true){
						echo "Image found, added as image to WordPress";

						$objXMLRPClientWordPress->new_post('Image','<figure><img src="'.$url[$i].'" alt="image" /></figure>', array('images'), $tags[$i], array(array( "key" => "source_url", "value" => $url[$i] )));
					}
				}

				$json = json_decode($content, true);

				if($parser == 0){
					$article_title = $json['title'];
					$article_content = $json['content'];
				}
				else{
					$article_title = $json['rss']['channel']['item']['title'];
					$article_content = $json['rss']['channel']['item']['description'];
				}
				
				// Print progress of succesfully title posted to WP
				echo htmlallentities($article_title)." &raquo; Added<br>";

				// Post to WP
				$objXMLRPClientWordPress->new_post($article_title, $article_content, $categories, $tags[$i], array(array( "key" => "source_url", "value" => $url[$i])) );

				showProgressbar($progressPecentage);

			}
			catch(Exception $ex){
				 echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
			
		}
		
		deleteAllFiles('uploads');	//Delete all Pocket HTML file insied uploads
		echo "<br/>Import Complete<br/>";
	}	
}

/**
 * Show Progressbar for importing Article to WordPress
 * @param  integer $percentage Percentage of progressbar
 * @return void           
 */
function showProgressbar($percentage = 0){
//Progressbar 
	echo '<script>
		document.getElementById("bar").value="'.$percentage.'";
		document.getElementById("progress").style="width:'.$percentage.'%";
		document.getElementById("progress").innerHTML="'.$percentage.'%";
		document.getElementById("percentage").innerHTML="'.$percentage.'%";
	 </script>';

	// This is for the buffer achieve the minimum size in order to flush data
	echo str_repeat(' ',1024*64);

	// Send output to browser immediately
	ob_flush(); 
	flush(); 

	// Sleep one second so we can see the delay
	// sleep(1);
}

/**
 * Convert all weird character of UTF 8
 * @param  String $str String with weird character.
 * @return String    Clean string from weird character.
 */
function htmlallentities($str){
  $res = '';
  $strlen = strlen($str);

  for($i=0; $i<$strlen; $i++){
    $byte = ord($str[$i]);
    if($byte < 128) // 1-byte char
      $res .= $str[$i];
    elseif($byte < 192); // invalid utf8
    elseif($byte < 224) // 2-byte char
      $res .= '&#'.((63&$byte)*64 + (63&ord($str[++$i]))).';';
    elseif($byte < 240) // 3-byte char
      $res .= '&#'.((15&$byte)*4096 + (63&ord($str[++$i]))*64 + (63&ord($str[++$i]))).';';
    elseif($byte < 248) // 4-byte char
      $res .= '&#'.((15&$byte)*262144 + (63&ord($str[++$i]))*4096 + (63&ord($str[++$i]))*64 + (63&ord($str[++$i]))).';';
  }
  return $res;
}

function deleteAllFiles($directory){
	$files = glob($directory.'/*'); // get all file names
	foreach($files as $file){ // iterate files
	  if(is_file($file)){
	  	unlink($file); // delete file
	  }  
	}
}
?>