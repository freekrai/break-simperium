<?php
	date_default_timezone_set('America/Los_Angeles');

	include("system/simperium.php");
	include("system/pdo.class.php");

	include("config.php");

	$poster = new Simperium_Post($appname,$apikey,$token);
	$poster->process();

	class Simperium_Post{
		private $simperium;
		private $client_id;
		private $started;
		private $how_long_to_live;
		private $silent;
		
		public function __construct($appname,$apikey,$token){
			$this->simperium = new Simperium($appname,$apikey);
			$this->simperium->set_token($token);	
			$this->started = $this->microtime();

//			$this->alert('Started at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());

			//	unique UUID to identify this poster.
			$this->client_id = $this->simperium->generate_uuid();
		}

		public function process(){
		//	grab a random number between 1 and 20:
			$len = rand(1,20);
		
		//	get random text:
			$text = $this->get_ipsum($len,'short',false);
		
		//	generate a unique UUID to use as the post id:
			$todo1_id = $this->simperium->generate_uuid();
		
		//	save the post to simperium:
			$this->update_log( "Sending Message",'sending');
		
			$this->simperium->liveblog->post( $todo1_id,array(
				'text'=>$text,
				'timeStamp' => time(),
				'done'=>'False'
			) );

			$res = json_encode($this->simperium->get_lastcode());

			$this->update_log( "Sending Message",'sent',$res);

			$this->alert("OK");
		}

		private function update_log($name,$value,$value2=''){
			$pdo = Db::singleton();
			$mt = $this->microtime() - $this->started;
			$pdo->query( "INSERT INTO log SET log_name='{$name}',log_value='{$value}',log_value2='{$value2}',log_time_elapsed='{$mt}',log_client='{$this->client_id}',log_type='p';" );
		}
	
	//	grab random text:
		private function get_ipsum($len = 10, $size = 'short', $headers = true){
			$url = 'http://loripsum.net/api/'.$len.'/'.$size.($headers ? '/headers' : null );
			$ch = curl_init();
			$timeout=5;
			$ch = curl_init($url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			$output = curl_exec($ch);
			curl_close($ch);
			return $output;
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
			echo $msg . (!empty($_SERVER['HTTP_USER_AGENT']) ? "<br />" : "\n");
			ob_flush();
			flush();
		}
		
		function __destruct() {
//			$this->alert('Finished at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());
			ob_end_clean();
		}
	}