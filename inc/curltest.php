<?php 

function _isCurl(){
    return function_exists('curl_version');
}

if (!_iscurl()) {
	echo "Curl disabled"; 
} else {


	echo "Curl enabled<br/>"; 
    echo "pre_init<br/>";
	
	$url = "https://api-sandbox.justgiving.com/docs";
    $ch = curl_init($url);
  
	echo "post_init<br/>";
	
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	echo "post_setopt<br/>";
	echo "pre_exec<br/>";

	$result = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);

	echo "post_close<br/>";

	echo "result: <br/>";

	echo $result;
	
}
