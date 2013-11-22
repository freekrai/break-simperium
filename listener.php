<?php
	date_default_timezone_set('America/Los_Angeles');
	include("simperium.php");

	$appname = 'authorities-platforms-ed8';
	$apikey = 'ac91e724b9ec461d9ca31796c27e9fed';
	$token = '59a9eff00c80457686aece62a0047e2c';
	$simperium = new Simperium($appname,$apikey);
	$simperium->set_token($token);	

//	unique UUID to identify this listener.
	$client_id = $simperium->generate_uuid();

	$cv = '';
	$numTodos = 0;
	$a = true;
	while( $a ){
		$changes = $simperium->todo2->changes($cv,true);
		foreach($changes as $change ){
			$cv = $change->cv;
			$data = $change->d;
			echo $cv."\n-------\n";
			echo '<pre>'.print_r($data,true).'</pre><hr />';
			logToFile('./logs/'.($client_id).'.log','Message Recieved - '.$cv);
		}
	}
	
	function logToFile($filename, $msg){ 
		$fd = fopen($filename, "a");
		$str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg; 
		fwrite($fd, $str . "\n");
		fclose($fd);
	}