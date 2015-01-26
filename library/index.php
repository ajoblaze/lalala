<?php 
	$base_url = !empty($_SERVER['HTTPS']) ? "https" : "http"; 
	$base_url .= "://".$_SERVER['HTTP_HOST'];
	$pathinfo = pathinfo($_SERVER['SCRIPT_NAME']);
	$explode = explode("/",$pathinfo['dirname']);
	foreach ($explode as $exp) : 
		if ($exp != "") {
			$base_url .= "/".$exp;
			if ($exp == "public") {
				break;
			}
		}
	endforeach;
	header("location:".$base_url."/error/error403");
?>