<?php

	include("simperium.php");

	$appname = 'authorities-platforms-ed8';
	$apikey = 'ac91e724b9ec461d9ca31796c27e9fed';
	$token = '59a9eff00c80457686aece62a0047e2c';
	$simperium = new Simperium($appname,$apikey);

	$simperium->set_token($token);	

	$cv = '';
	$numTodos = 0;
	$a = true;
	while( $a ){
		$changes = $simperium->todo2->changes($cv,true);
		foreach($changes as $change ){
			echo '<pre>'.print_r($change,true).'</pre><hr />';
			$cv = $change->cv;
		}
	}