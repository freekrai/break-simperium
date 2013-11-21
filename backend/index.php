<?php
	//	Simperium PHP library
	include("simperium.php");

	$appname = 'authorities-platforms-ed8';
	$apikey = 'ac91e724b9ec461d9ca31796c27e9fed';
	$token = '59a9eff00c80457686aece62a0047e2c';
	$simperium = new Simperium($appname,$apikey);
	$simperium->set_token($token);

//	grab a random number between 1 and 20:
	$len = rand(1,20);

//	get random text:
	$text = get_ipsum($len,'short',false);

//	generate a unique UUID to use as the post id:
	$todo1_id = $simperium->generate_uuid();

//	save the post to simperium:
	$simperium->todo2->post( $todo1_id,array(
		'text'=>$text,
		'timeStamp' => time(),
		'done'=>'False'
	) );

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