<?php

	date_default_timezone_set('America/Los_Angeles');
	include("datagarde.php");

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
		$changes = $simperium->liveblog->changes($cv,true);
		foreach($changes as $change ){
			$cv = $change->cv;
			$data = $change->d;
			echo $cv."\n-------\n";
//			echo '<pre>'.print_r($data,true).'</pre><hr />';
			if( $data ){
				$data->id = $change->cv;
				$data->client = $client_id;
				unset($data->done);
				unset($data->timeStamp);
			}
			$data = (array) $data;
			datagarde::value( 'freekrai@me.com',"Message Recieved",json_encode($data), 'listener' );
		}
	}