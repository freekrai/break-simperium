<?php
	date_default_timezone_set('America/Los_Angeles');

	//	Simperium PHP library
	include("simperium.php");

	$appname = 'authorities-platforms-ed8';
	$apikey = 'ac91e724b9ec461d9ca31796c27e9fed';
	$token = '59a9eff00c80457686aece62a0047e2c';
	$simperium = new Simperium($appname,$apikey);
	$simperium->set_token($token);


//	unique UUID to identify this client.
	$client_id = $simperium->generate_uuid();

//	grab a random number between 1 and 20:
	$len = rand(1,20);

//	get random text:
	$text = get_ipsum($len,'short',false);

//	generate a unique UUID to use as the post id:
	$todo1_id = $simperium->generate_uuid();

//	save the post to simperium:
	$data = array(
		'client' => $client_id,
		'status' => 'sending'
	);
	datagarde::value( 'freekrai@me.com',"Sending Message ",json_encode($data), 'listener' );

	$simperium->liveblog->post( $todo1_id,array(
		'text'=>$text,
		'timeStamp' => time(),
		'done'=>'False'
	) );
	$data = array(
		'client' => $client_id,
		'status' => 'sent'
	);
	datagarde::value( 'freekrai@me.com',"Sending Message ",json_encode($data), 'listener' );

//	grab random text:
	function get_ipsum($len = 10, $size = 'short', $headers = true){
		$url = 'http://loripsum.net/api/'.$len.'/'.$size.($headers ? '/headers' : null );
		$ch = curl_init();
		$timeout=5;
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}