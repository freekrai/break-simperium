<?php
	//	Simperium PHP library
	include("simperium.php");

	$appname = 'authorities-platforms-ed8';
	$apikey = 'ac91e724b9ec461d9ca31796c27e9fed';

	$simperium = new Simperium($appname,$apikey);
	$token = $simperium->authorize('freekrai@me.com','banshee');

echo $token. ' ---- '.$simperium->get_token().'<br />';

//	echo '<hr />';
	$todo1_id = $simperium->generate_uuid();
	$simperium->todo2->post( $todo1_id,array('text'=>'Ok.. how about this one?<br /><img src="http://media.giphy.com/media/Dp7POJcVxRDUc/giphy.gif" />', 'done'=>'False') );

//	$simperium->todo2->post( '98de2d65-6541-408c-ab4d-a800b0e9ca82', array('done'=>'True') );
/*
echo '<hr />';
$ret = $simperium->todo2->index();
$simperium->_debug($ret);
*/
/*
echo '<hr />';
$ret = $simperium->todo2->index(true);
$simperium->_debug($ret);
*/
/*
echo '<hr />';
foreach( $simperium->todo2->index()->index as $v ){
	echo $v->id.'<br />';
	$ret = $simperium->get( $v->id );
	$simperium->_debug($ret);
}
*/
/*
echo '<hr />';
$ret = $simperium->todo2->get('61a27242-e268-4951-89b8-1d42b379d353');

$simperium->_debug($ret);

echo '<hr />';
$buckets = $simperium->buckets();
$simperium->_debug( $buckets );
*/