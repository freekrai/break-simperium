<?php
/*
	Listener Reporter, gather reports based on listener and responses received.
*/

	date_default_timezone_set('America/Los_Angeles');
	include("system/pdo.class.php");
	include("config.php");
	$pdo = Db::singleton();

	$results = $pdo->query( "SELECT * from log WHERE log_type = 'l' ORDER BY log_client,log_id" );

	$listeners = array();
	$messages = array();
	
	while( $row = $results->fetch() ){
		$uuid = $row['log_client'];
		if( !empty($row['log_value2']) ){
			$mid = $row['log_value'];
			$messages[ $mid ][ $uuid ] = $row;
		}
		$listeners[ $uuid ] = $row;
	}
//	echo '<pre>'.print_r($messages,true).'</pre>';
?>
	<html>
	<head>
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.2/css/bootstrap.min.css" rel="stylesheet">
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.2/js/bootstrap.min.js"></script>
	</head>
	<body>
		<br /><br />
		<div class="container">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Messages Received</h3>
				</div>
				<table class="table table-striped table-bordered">
				<thead>
				<tr>
					<th>Message CV</th>
					<th>Client</th>
					<th>Time (In Seconds)</th>
				</tr>
				</thead>
				<tbody>
<?php
				foreach($messages as $cv => $message ){
?>
				<tr>
					<th colspan=3>
						<h4><?php echo $cv?></h4>
					</th>
				</tr>
<?php
					$i = 1;
					foreach( $message as $uuid => $msg ){
?>
					<td><?php echo $i?></td>
					<td><?php echo $uuid ?></td>
					<td><?php echo round($msg['log_value3'],2)?></td>
				</tr>
<?php					
						$i++;
					}
				}
?>
				</tbody>
				</table>
			</div>
		</div>
	</body>
	</html>