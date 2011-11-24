<?php

function resolve_url($url) {
	
	$curl = curl_init();
	curl_setopt($curl,CURLOPT_URL,$url);
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);
	
	// So we can see the headers and see where the t.co shortened URL is going...
	curl_setopt($curl, CURLOPT_HEADER, true);
	
	$response = curl_exec($curl);
	curl_close($curl);
	
	// Get header array and  return the 301 location header...
	$headers = array();
	$response_array = explode("\r\n\r\n", $response);
	
	foreach ($response_array as $each) {
		if (substr($each, 0, 4) == 'HTTP') {
			$headers[] = $each;
		}
	}
	
	foreach ($headers as $header) {
		
		$str = substr(trim(strstr($header, ' ')), 0, 3);
		
		$lines = explode("\n", $header);
		$header_array['status_line'] = trim(array_shift($lines));
		
		foreach ($lines as $line) {
			list($key, $val) = explode(':', $line, 2);
			$header_array[trim($key)] = trim($val);
		}
		
		if (isset($header_array['Location'])) {
			return $header_array['Location'];
		} else {
			return FALSE;
		}
	}
}

function download_url($url, $write=FALSE, $write_name='', $path='/Users/andy/scripts/paste_dump/output/'){
	
	if (function_exists('curl_init')) {
		$curl = curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,10);
		
		// Do we want to write this file?
		if($write && $write_name != ''){
			
			$fh = fopen($path.$write_name, "w");
			curl_setopt($curl, CURLOPT_FILE, $fh);
			curl_exec($curl);
			curl_close($curl);
		} else {
			$response = curl_exec($curl);
			curl_close($curl);
		}
	} else {
		$response = file_get_contents($url);
	}
	
	// Do we want to write this file?
	if(!$write){
		return $response;
	}
}

$user = 'PastebinLeaks';
$limit = 10;
$url = 'http://api.twitter.com/1/statuses/user_timeline.json?screen_name='.$user.'&count='.$limit;

$response = download_url($url);
$twitter = json_decode($response, true);
$results = count($twitter);

for ($i=0; $i<$results; $i++) {
	
	// Get some tweet info
	//$tweet_id = $twitter[$i]['id_str'];
	//$user = $twitter[$i]['user']['screen_name'];
	//$date = date('Y-m-d H:i:s', strtotime($twitter[$i]['created_at']));
	$tweet = $twitter[$i]['text'];
	
	// Pull out link(s) from tweet
	preg_match_all('/[a-z]+:\/\/\S+/', $tweet, $matches);
	
	if(!empty($matches[0])){
		
		foreach($matches[0] as $key=>$val){
			
			// Get the 301 location from the headers
			$new_url = resolve_url($val);
			
			// This should be a pastebin URL, so check it is!
			if(substr($new_url, 7, 8) == 'pastebin'){
				// Now, restructure URL to download the file...
				// http://pastebin.com/wVnhWDNc to....
				// http://pastebin.com/download.php?i=wVnhWDNc
				$pastebin_id = substr($new_url, 20);
				download_url("http://pastebin.com/download.php?i=".$pastebin_id, TRUE, $pastebin_id.'.txt');
			}
		}
	}
}
?>