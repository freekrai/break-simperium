<?php
/*
	Listener Reporter, gather reports based on listener and responses received.
*/
	ini_set('memory_limit', '264M');
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
			<ul class="nav nav-tabs nav-justified">
				<li><a href="reporter.php">Message Summary</a></li>
				<li><a href="reporter.php?sent=1">Message Posting Summary</a></li>
			</ul>
			<br />
<?php 	if( !isset($_GET['details']) && !isset($_GET['sent']) ){	?>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Message Summary</h3>
				</div>
				<table class="table table-striped table-bordered">
				<thead>
				<tr>
					<th>#</th>
					<th>Message CV</th>
					<th>Clients</th>
					<th>Average time to receive</th>
				</tr>
				</thead>
				<tbody>
<?php
				$i = 1;
				foreach($messages as $cv => $message ){
					$clients = count($message);
					$time = array();
					foreach( $message as $uuid => $msg ){
						$time[] = round($msg['log_time_elapsed'],2);
					}
					$avg = avrg( $time );
?>
					<tr>
						<td><?php echo $i?></td>
						<td><a href="reporter.php?details=<?php echo $cv?>"><?php echo $cv?></a></td>
						<td><?php echo $clients?></td>
						<td><?php echo round($avg,2)?></td>
					</tr>
<?php
					$i++;
				}
?>
				</tbody>
				</table>
			</div>
			<br />
<?php 	}	?>
<?php 	if( isset($_GET['details']) ){	?>
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
						<th>Date</th>
					</tr>
					</thead>
					<tbody>
<?php
					$cv = $_GET['details'];
					$message = $messages[ $cv ];
?>
					<tr>
						<td colspan=4 class="active"><?php echo $cv?></td>
					</tr>
<?php
					$i = 1;
					foreach( $message as $uuid => $msg ){
?>
						<tr>
							<td><?php echo $i?></td>
							<td><?php echo $uuid ?></td>
							<td><?php echo round($msg['log_time_elapsed'],2)?></td>
							<td><?php echo $msg['log_date']?></td>
						</tr>
<?php					
						$i++;
					}
?>
				</tbody>
				</table>
			</div>
<?php 	}	?>
		</div>
	</body>
	</html>
<?php
	function avrg( $arr ){
		$count = count( $arr );
		return (array_sum($arr) / $count);
	}
?>