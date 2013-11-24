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

//	the id where we left off last time we ran it:
	$cv = '52917ce2ba5fdc4ed738e596';
	$numTodos = 0;
	$a = true;
	while( $a ){
		$changes = $simperium->liveblog->changes($cv,true);
		foreach($changes as $change ){
			$cv = $change->cv;
			$data = $change->d;
			echo $cv."\n-------\n";
			if( $data ){
				$data->id = $change->cv;
				$data->client = $client_id;
				unset($data->done);
				unset($data->timeStamp);
			}
			$data = (array) $data;
#			datagarde::value( 'freekrai@me.com',"Message Recieved",json_encode($data), 'listener' );
			datagarde::value( 'freekrai@me.com',"Post Received",json_encode(array('client'=>$change->cv)), 'listener' );
		}
	}