#!/usr/bin/php
<?php
/*
	Parallel load tester to test Simperium responses and requests
	
	Call by:
	
	php simperium_test.php --clients=<concurrent-clients-to-test> --token=<simperium-token> --appid=<app-id-to-test> --bucket=<bucket-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass> 	
*/
	date_default_timezone_set('America/Los_Angeles');
	$sites = array();
	$concurrent = 2;   // Any number.
	
	$arguments = arguments( $argv );
	
	if( !count($arguments) ){
	    echo "Usage: php simperium_test.php --clients=<concurrent-clients-to-test> --bucket=<simperium-bucket> --token=<simperium-token> --appid=<app-id-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass>\n";
	    echo "    clients: The number of simulated users hitting REST. (0-n where n is an Integer)\n";
		echo "    bucket: simperium bucket\n";
		echo "    token: simperium token\n";
		echo "    appid: simperium appi-d\n";
		echo "    ip: ip address to test (optional)\n";		
		echo "    hostname: hostname in headers (optional)\n";
	    echo "\n";
		exit;		
	}
	
	$url = 'https://api.simperium.com';
	$hostname = '';
	$token = '';
	$appid = '';
	$bucket = '';
	if( isset($arguments['ip']) ) $url = $arguments['ip'];
	if( isset($arguments['hostname']) ) $hostname = $arguments['hostname'];
	if( isset($arguments['token']) ) $token = $arguments['token'];
	if( isset($arguments['appid']) ) $appid = $arguments['appid'];
	if( isset($arguments['bucket']) ) $bucket = $arguments['bucket'];
	$concurrent = $arguments['clients'];
	
	if ( empty($concurrent) || !is_numeric($concurrent) ) {
		die('Please specify a valid max_clients value!');
	}
	if ( empty($appid) ) {
		die('Please specify a valid app id!');
	}
	if ( empty($token) ) {
		die('Please specify a valid token!');
	}
	if ( empty($bucket) ) {
		die('Please specify a valid bucket!');
	}
	
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
	/*
		returns randomly generated text.
	*/
	function get_random_text(){
		$size = rand(1,20);
		$data = '';
		for( $i = 0;$i <= $size;$i++){
			$data .= md5( time() );
		}
		return $data;
	}

	$mc = new Simperium_Test($concurrent,$url,$appid,$bucket,$token,$hostname);
	$mc->process();

	class Simperium_Test{
		private $slug;
		private $maxConcurrent = 2;
		private $currentIndex  = 0;
		private $responded_clients = 0;
		private $responded_clients_time = 0;
		private $time = 0;
		private $hostname = '';
		private $token;
		private $bucket;
		private $clients = array();
		private $info  = array();
		private $mh;
		private $urls = array();
		private $posts = array();
		private $gets = array();
		
		public function __construct($concurrent,$url,$appid,$bucket,$token,$hostname=''){
			$this->slug = time();
			$todo = array();
			for ($i = 0; $i < $concurrent; $i++) {
				$this->posts[] = array(
					'url'=> $url.'/1/'.$appid.'/'.$bucket.'/i/'.$this->slug.'-'.$i,
					'post'=> array(
						'text'=>get_random_text()
					)
				);
				$this->gets[] = array(
					'url'=> $url.'/1/'.$appid.'/'.$bucket.'/i/'.$this->slug.'-'.$i
				);
			}
			$this->token = $token;
			$this->hostname = $hostname;
			$this->allToDo = $todo;
			$this->maxConcurrent = $concurrent;
			$this->time = $this->microtime();
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

		public function process(){

			$this->time = $this->microtime();
			$this->alert('Started at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());

			$posts = $this->multiRequest( $this->posts );
			$gets = $this->multiRequest( $this->gets );

			$msg = '';
			$msg .= "avg rsp time: " . round($this->responded_clients_time / $this->responded_clients, 2) . "s, ";
			$msg .= "avg rsp/min: " . round($this->responded_clients / $this->elapsed(), 2) * 60 . ", ";
			$msg .= "responses: " . $this->responded_clients . ", ";
			$msg .= "elapsed: " . $this->elapsed() . "s";
			$this->alert($msg);

		}

		private function multiRequest($data, $options = array()) {
			$curly = array();
			$result = array();
			$mh = curl_multi_init();
			if( function_exists('curl_multi_setopt') ){
				curl_multi_setopt( $mh, CURLMOPT_PIPELINING, 0);
				curl_multi_setopt( $mh, CURLMOPT_MAXCONNECTS, 20);
			}
			$i = 0;
			foreach ($data as $id => $d) {
				$curly[$id] = curl_init();
				$headers = array();
				$headers[] = 'X-Simperium-Token: '.$this->token;
				if( $this->hostname != '' ){
					$headers[] = 'Host: '.$this->hostname;
				}
				curl_setopt($curly[$id], CURLOPT_HTTPHEADER, $headers);
				$url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
				curl_setopt($curly[$id], CURLOPT_URL,            $url);
				curl_setopt($curly[$id], CURLOPT_HEADER,         0);
				curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
				$method = 'get';
				if (is_array($d)) {
					if (!empty($d['post'])) {
						$method = 'post';
						curl_setopt($curly[$id], CURLOPT_POST,       1);
						curl_setopt($curly[$id], CURLOPT_POSTFIELDS, json_encode($d['post']) );
					}
				}
				if (!empty($options)) {
					curl_setopt_array($curly[$id], $options);
				}
				curl_multi_add_handle($mh, $curly[$id]);
				$this->info[$id]['url'] = $d['url'];
				$this->info[$id]['key'] = $i;
				$this->info[$id]['time'] = $this->microtime();
				$this->info[$id]['method'] = $method;
				$i++;
			}
			$running = null;
			do {
				curl_multi_exec($mh, $running);
			} while($running > 0);
			foreach($curly as $id => $c) {
				$this->responded_clients_time += $this->elapsed( $this->info[$id]['time'] );
				$this->responded_clients++;

				$this->info[$id]['headers'] = curl_getinfo($c);
				$this->info[$id]['content'] = curl_multi_getcontent($c);
				curl_multi_remove_handle($mh, $c);

				$this->alert( 
					$this->info[$id]['key'] . ' - ' . 
					$this->info[$id]['url'] . ' - ' . 
					$this->info[$id]['method'] . ' - ' . 
					$this->info[$id]['headers']['http_code'] .' - '. 
					$this->elapsed( $this->info[$id]['time'] ).'s' 
				);
			}
			curl_multi_close($mh);
			return $this->info;
		}
		function __destruct() {
			ob_end_clean();
		}
	}