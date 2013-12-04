<?php
	date_default_timezone_set('America/Los_Angeles');

	include("system/simperium.php");
	include("system/pdo.class.php");

	include("config.php");

	$poster = new Simperium_Test($appname,$apikey,$token);
	$poster->process();

	class Simperium_Test{
		private $simperium;
		private $client_id;
		private $started;
		private $how_long_to_live;
		private $silent;
		
		public function __construct($appname,$apikey,$token){
			$this->simperium = new Simperium($appname,$apikey);
			$this->simperium->set_token($token);	
			$this->started = $this->microtime();

			$this->alert('Started at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());

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
		
		
			$resu = $this->simperium->utest->post( $todo1_id,array(
				'text'=>$text,
				'timeStamp' => time(),
				'done'=>'False'
			) );

			$res = $this->simperium->get_lastcode();

			//	now let's make sure it actually exists
			if( $res['http_code'] == 200 ){
				//	do a get based on the post id..
				$data = $this->simperium->utest->get( $todo1_id );
				if( isset($data->text) ){
					echo $todo1_id." - OK<br />";
				}
			}
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
#			ob_flush();
#			flush();
		}
		
		function __destruct() {
			$this->alert('Finished at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());
#			ob_end_clean();
		}
	}