<?php
/*
	php listener.php 
*/

date_default_timezone_set('America/Los_Angeles');
include("system/simperium.php");
include("system/pdo.class.php");

include("config.php");

$simperium = new Simperium($appname,$apikey);
$simperium->set_token($token);	

//	unique UUID to identify this listener.
$client_id = $simperium->generate_uuid();

//	the id where we left off last time we ran it:
$cv = get_last_cv();

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
		update_log("Post Received [".date('F j, Y')."]",$change->cv);
	}
}
//	store the message into the log table
function update_log($name,$value){
	global $client_id;
	$pdo = Db::singleton();
	$pdo->query( "INSERT INTO log SET log_name='{$name}',log_value='{$value}',log_client='{$client_id}',log_type='l';" );
}

function get_last_cv(){
	$pdo = Db::singleton();
	$row = $pdo->query( "SELECT log_value AS cv from log WHERE log_type = 'l' ORDER BY log_id DESC LIMIT 1;" )->fetch();
	return $row['cv'];
}