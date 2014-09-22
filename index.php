<?php require("class/simple_html_dom.php"); ?>
<?php require("class/wp_xmlrpc.php"); ?>
<?php require("inc/pocket_wp_importer.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<title>Pocket 2 WordPress Importer</title>
</head>
<body>
	<div class="import">
		<h1>Pocket 2 WordPress Importer</h1>
		<p>Import all your Pocket articles to your WordPress website. Edit <code>config.php</code> and fill your data then, upload your Pocket exported file here.</p>
	
		<form action="" method="POST" enctype="multipart/form-data">
		    <input type="file" name="file" />
		    <input type="submit"/>
		</form>
	</div>
</body>
</html>

<?php

	$inputURL = uploadHTMLlinks();
	
	if(!is_null($inputURL)){
		$uploadedURL = getListURL($inputURL);
		$uploadedTags = getListTags($inputURL);
		
		importToWordPress(0, $uploadedURL, array('Pocket'), $uploadedTags);
	}
	
	// importToWordPress($bulkURL);		
?>