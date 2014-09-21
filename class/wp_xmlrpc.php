<?php
class XMLRPClientWordPress
{
	var $XMLRPCURL = "";
    var $UserName  = "";
    var $PassWord = "";

    // Constructor
	public function __construct($xmlrpcurl, $username, $password) 
	{
	    $this->XMLRPCURL = $xmlrpcurl;
	    $this->UserName  = $username;
	    $this->PassWord = $password;
	}

	function send_request($requestname, $params)
	{
	    $request = xmlrpc_encode_request($requestname, $params);
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
	    curl_setopt($ch, CURLOPT_URL, $this->XMLRPCURL);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	    $results = curl_exec($ch);
	    curl_close($ch);
	    return $results;
	}

	function new_post($title, $body, $category, $keywords, $custom_fields = '', $encoding='UTF-8')
	{
	    $title = htmlentities($title,ENT_NOQUOTES,$encoding);
	 
	    $content = array(
	    	'post_status'=>'publish',
	        'post_title' => $title,
	        'post_content' => $body,
	        'post_type' => 'post',
	        'tags_input' => $keywords,
	        'post_category' => $category,
	        'custom_fields' => $custom_fields,
	        'terms_names' => array('category' =>  $category, 'post_tag' => $keywords)
	    );

	    $params = array(0,$this->UserName, $this->PassWord, $content,true);
	 	
	    return $this->send_request('wp.newPost',$params);
	}

	function create_page($title,$body,$encoding='UTF-8')
	{
	    $title = htmlentities($title,ENT_NOQUOTES,$encoding);
	 
	    $content = array(
	        'title' => $title,
	        'description' => $body
	    );
	    $params = array(0,$this->UserName,$this->PassWord,$content,true);
	 
	    return $this->send_request('wp.newPage',$params);
	}

	function display_authors()
	{
	    $params = array(0,$this->UserName,$this->PassWord);
	    return $this->send_request('wp.getAuthors',$params);
	}

	function display_posts()
	{
	    $params = array(0,$this->UserName,$this->PassWord);
	    return $this->send_request('wp.getPosts',$params);
	}
}


