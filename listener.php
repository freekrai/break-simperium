#!/usr/bin/php
<?php
/*
	php listener.php --silent=false 
	or
	php listener.php --silent
	
	passing an argument with no value automatically sets the value of that argument to true.
*/

	define("IS_CLI_CALL",( strcmp(php_sapi_name(),'cli') == 0 ));
	if( !IS_CLI_CALL ){
		echo 'You do not want to call this script via a browser';
		exit;
	}
	if( file_exists('/tmp/nomorelistener') ){
		echo "No More Listener (/tmp/nomorelistener) file found in /tmp. Can't start up";
		exit;
	}

	$arguments = arguments( $argv );

	$how_long_to_live = 15 * 60;	//	15 minutes
	$silent = true;
	
	if( isset($arguments['silent']) )	$silent = $arguments['silent'];

	set_time_limit( $how_long_to_live );
	ini_set('max_execution_time', $how_long_to_live);

	date_default_timezone_set('America/Los_Angeles');
	include("system/simperium.php");
	include("system/pdo.class.php");
	
	include("config.php");
		
	$client = new Simperium_Listener($appname,$apikey,$token,$how_long_to_live,$silent);
	$client->listen();


/*
	Handy function for CLI-based apps that parse all arguments that start with -- or - and return an array with the key as that argument and the variable
*/
	function arguments($argv) {
		$_ARG = array();
		foreach ($argv as $arg) {
			if (ereg('--([^=]+)=(.*)',$arg,$reg)) {
				$_ARG[$reg[1]] = $reg[2];
			} elseif(ereg('-([a-zA-Z0-9])',$arg,$reg)) {
				$_ARG[$reg[1]] = 'true';
			}
		}
		return $_ARG;
	}
		
	class Simperium_Listener{
		private $simperium;
		private $client_id;
		private $started;
		private $how_long_to_live;
		private $silent;
		
		public function __construct($appname,$apikey,$token,$how_long_to_live,$silent = false){
			$this->simperium = new Simperium($appname,$apikey);
			$this->simperium->set_token($token);	
			$this->started = $this->microtime();
			$this->how_long_to_live = $how_long_to_live;
			$this->silent = $this->toBool( $silent );

			$this->alert('Started at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());
			//	unique UUID to identify this listener.
			$this->client_id = $this->simperium->generate_uuid();
			$this->update_log( "Listener Started",date('Y-m-d h:i:s') . '. PID: ' . getmypid() );
		}

		/*
			toBool
			-	takes a variable and returns either true or false depending on what it has been set to.
		*/
		function toBool($var) {
			if (!is_string($var)) return (bool) $var;
			switch (strtolower($var)) {
				case '1':
				case 'true':
				case 'on':
				case 'yes':
				case 'y':
					return true;
				default:
					return false;
			}
		}
		
		public function listen(){
			//	the id where we left off last time we ran it:
			$cv = $this->get_last_cv();

			if( !$cv )	$cv = '529ccbe6ba5fdc4ed75dae0a';
			
			$numTodos = 0;
			$a = true;
			while( $a ){
				//	if /tmp/nomorelistener exists, then we stop what we are doing...
				if( file_exists('/tmp/nomorelistener') ){
					$this->alert( "No More Listener file found in /tmp. Shutting down" );
					exit;
				}
				$changes = $this->simperium->liveblog->changes($cv,true);
				foreach($changes as $change ){
					$cv = $change->cv;
					$data = $change->d;
					$this->alert( "Message Received: ".$cv );
					$this->update_log("Post Received",$change->cv,$data->text);
				}

				// if the time in minutes has passed, then end this instance
				$elapsed = round( $this->microtime() - $this->started,2);
				if( $elapsed > $this->how_long_to_live ){
					$this->alert( ($this->how_long_to_live / 60)." minutes have elapsed. Shutting down" );
					exit;
				}
			}
		}
		
//		store the message into the log table
		private function update_log($name,$value,$text=''){
			$pdo = Db::singleton();
			$mt = $this->microtime() - $this->started;
			$pdo->query( "INSERT INTO log SET log_name='{$name}',log_value='{$value}',log_value2='{$text}',log_time_elapsed='{$mt}',log_client='{$this->client_id}',log_type='l';" );
		}
		
		private function get_last_cv(){
			$pdo = Db::singleton();
			$row = $pdo->query( "SELECT log_value AS cv from log WHERE log_type = 'l' AND log_name='Post Received' ORDER BY log_id DESC LIMIT 1;" )->fetch();
			return $row['cv'];
		}
		
		private function elapsed($time = 0) {
			if (empty($time)) $time = $this->time;
			$diff = $this->microtime() - $time;
			$diff = round($diff,2);
			return $diff;
		}
		
		private function microtime() {
			return microtime(true);
		}
		
		private function alert($msg) {
			if( !$this->silent ){
				echo $msg . (!empty($_SERVER['HTTP_USER_AGENT']) ? "<br />" : "\n");
			}
			ob_flush();
			flush();
		}
		
		function __destruct() {
			ob_end_clean();
		}
	}