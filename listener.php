<?php
/*
	php listener.php 
*/
#print_r($argv);
#die();

date_default_timezone_set('America/Los_Angeles');
include("datagarde.php");

include("simperium.php");
include("config.php");

$simperium = new Simperium($appname,$apikey);
$simperium->set_token($token);	

//	unique UUID to identify this listener.
$client_id = $simperium->generate_uuid();

//	the id where we left off last time we ran it:
$cv = '5296dad5ba5fdc4ed76072b2';
$numTodos = 0;
$a = true;
while( $a ){
	$changes = $simperium->liveblog->changes($cv,true);
	foreach($changes as $change ){
		$cv = $change->cv;
		$data = $change->d;
		echo $cv."\n-------\n";
//			echo $data->text."\n";
		if( $data ){
			$data->id = $change->cv;
			$data->client = $client_id;
			unset($data->done);
			unset($data->timeStamp);
		}
		$data = (array) $data;
#			datagarde::value( 'freekrai@me.com',"Message Recieved [".date('F j, Y')."]",json_encode($data), 'listener' );
		datagarde::value( 'freekrai@me.com',"Post Received [".date('F j, Y')."]",json_encode(array('client'=>$change->cv)), 'listener' );
	}
}