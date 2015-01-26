<?php 
	$base_url = !empty($_SERVER['HTTPS']) ? "https" : "http"; 
	$base_url .= "://".$_SERVER['HTTP_HOST'];
	$pathinfo = pathinfo($_SERVER['SCRIPT_NAME']);
	$splat = basename($pathinfo['dirname']);
	$base_url .= str_replace("/".$splat, "", $pathinfo['dirname']);
	header("location:".$base_url."/error/error403");
?>