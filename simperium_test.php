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
		echo "    appid: simperium app-id\n";
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
		private $results = array();
		
		public function __construct($concurrent,$url,$appid,$bucket,$token,$hostname=''){
			$this->slug = time();

			$url = parse_url( $url );
			$url = $url['scheme'].'://'.$url['host'];
			$port = $url['port'];
/*
			We want to set up our post and get queries.
			
			First, we loop through the number of concurrent connections we are setting up, and add  a url that is the same for both
			post and get. 
			
			In the post, we also create a unique post variable containing random text.
			
			We then add the same url to the gets function so we can make sure the new post we added to our bucket exists.
*/

			for ($i = 0; $i < $concurrent; $i++) {
				$this->posts[] = array(
					'url'=> $url.'/1/'.$appid.'/'.$bucket.'/i/'.$this->slug.'-'.$i,
					'port' => $port,
					'post'=> array(
						'text'=>get_random_text()
					)
				);
				$this->gets[] = array(
					'url'=> $url.'/1/'.$appid.'/'.$bucket.'/i/'.$this->slug.'-'.$i,
					'port' => $port,					
				);
			}
			$this->token = $token;
			$this->hostname = $hostname;
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
		/*
			process
			
			-	The brains of the class, process takes the posts and gets variables and performs the curl requests on them, it then
				builds a report showing stats based on the returned results.
		
		*/
		public function process(){
			$this->time = $this->microtime();
			$this->alert('Started at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());

			$posts = $this->multiRequest( $this->posts );
			$gets = $this->multiRequest( $this->gets );
			$result = array_merge((array)$posts, (array)$gets);

			$msg = '';
			$line = array();
			foreach( $this->results['status'] as $code=>$cnt ){
				$line[] = $code." = ".$cnt;
			}
			$msg .= 'status codes returned: '.implode(",",$line)."\n";

			$times = $this->results['times'];

			$msg .= "min response time: " . min($times) . "s, ";
			$msg .= "max response time: " . max($times) . "s, ";
			$msg .= "median response time: " . $this->mean_median_range($times, 'median') . "s, ";
			$msg .= "mean response time: " . $this->mean_median_range($times,'mean') . "s\n";
						
			$msg .= "average response time: " . round($this->responded_clients_time / $this->responded_clients, 2) . "s, ";
			$msg .= "average response / min: " . round($this->responded_clients / $this->elapsed(), 2) * 60 . ", ";
			$msg .= "responses: " . $this->responded_clients . ", ";
			$msg .= "elapsed: " . $this->elapsed() . "s";
			$this->alert($msg);
			$this->alert('Finished at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());
			exit;
		}

		private function multiRequest($data, $options = array()) {
			$curly = array();
			$result = array();
			$mh = curl_multi_init();
			if( function_exists('curl_multi_setopt') ){
				curl_multi_setopt( $mh, CURLMOPT_PIPELINING, 0);
				curl_multi_setopt( $mh, CURLMOPT_MAXCONNECTS, 100);
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
					if (!empty($d['port']) ){
						curl_setopt($curly[$id],CURLOPT_PORT, $d['port']);
					}
					if (!empty($d['post']) ) {
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
				$this->info[$id]['status'] = $this->info[$id]['headers']['http_code'];
				$this->info[$id]['content'] = curl_multi_getcontent($c);
				curl_multi_remove_handle($mh, $c);
				$end = $this->elapsed( $this->info[$id]['time'] );
				$this->alert( 
					$this->info[$id]['key'] . ' - ' . 
					$this->info[$id]['url'] . ' - ' . 
					$this->info[$id]['method'] . ' - ' . 
					$this->info[$id]['status'] .' - '. 
					$end.'s' 
				);
				$this->results['times'][] = $end;
				$this->results['methods'][ $this->info[$id]['method'] ][ $this->info[$id]['status'] ]++;
				$this->results['status'][ $this->info[$id]['status'] ]++;
			}
			curl_multi_close($mh);
			return $this->info;
		}

		private function mean_median_range($array, $output = 'mean'){
			if(!is_array($array)){ 
				return FALSE; 
			}else{ 
				switch($output){ 
					case 'mean': 
						$count = count($array); 
						$sum = array_sum($array); 
						$total = $sum / $count; 
						break; 
					case 'median': 
						rsort($array); 
						$middle = round(count($array) / 2); 
						$total = $array[$middle-1]; 
						break; 
					case 'mode': 
						$v = array_count_values($array); 
						arsort($v); 
						foreach($v as $k => $v){$total = $k; break;} 
						break; 
					case 'range': 
						sort($array); 
						$sml = $array[0]; 
						rsort($array); 
						$lrg = $array[0]; 
						$total = $lrg - $sml; 
						break; 
				} 
				return $total; 
			} 
		} 

		private function average( $arr ){
			$count = count( $arr );
			return (array_sum($arr) / $count);
		}

		public function __destruct() {
			ob_end_clean();
		}
	}