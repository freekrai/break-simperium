#!/usr/bin/php
<?php
/*
	Parallel load tester.
	
	Call by:
	
	php stress_test.php --url=<url-to-test> --clients=<number-of-clients>	
*/
	date_default_timezone_set('America/Los_Angeles');
	$sites = array();
	$concurrent = 2;   // Any number.
	
	$arguments = arguments( $argv );
	
	$url = $arguments['url'];
	$concurrent = $arguments['clients'];
	
	if ( empty($concurrent) || !is_numeric($concurrent) ) {
		die('Please specify a valid max_clients value!');
	}
	
	if ( empty($url) ) {
		die('Please specify a valid url to test!');
	}
	
	for ($i = 0; $i < $concurrent; $i++) {
		$sites[] = $url;
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
	
	
	$mc = new Stress_Test($sites, $concurrent);
	$mc->process();
	
	class Stress_Test{
		private $allToDo;
		private $multiHandle;
		private $maxConcurrent = 2;
		private $currentIndex  = 0;
		private $responded_clients = 0;
		private $responded_clients_time = 0;
		private $time = 0;
		private $clients = array();
		private $info  = array();
		private $options = array(CURLOPT_RETURNTRANSFER => true,CURLOPT_FOLLOWLOCATION => true,CURLOPT_MAXREDIRS => 3,CURLOPT_TIMEOUT => 3);
		
		public function __construct($todo, $concurrent){
			$this->allToDo = $todo;
			$this->maxConcurrent = $concurrent;
			$this->time = $this->microtime();
			$this->multiHandle = curl_multi_init();
			$this->alert('Started at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());
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
			$running = 0;
			if( function_exists('curl_multi_setopt') ){
				curl_multi_setopt( $this->multiHandle, CURLMOPT_PIPELINING, 0);
				curl_multi_setopt( $this->multiHandle, CURLMOPT_MAXCONNECTS, 20);
			}
			do {
				$this->_addHandles( min(array($this->maxConcurrent - $running, $this->_moreToDo())) );
				while ($exec = curl_multi_exec($this->multiHandle, $running) === -1) {
				}
				curl_multi_select($this->multiHandle);
				while ($multiInfo = curl_multi_info_read($this->multiHandle, $msgs)) {				
					$this->_showData($multiInfo);
					curl_multi_remove_handle($this->multiHandle, $multiInfo['handle']);
					curl_close($multiInfo['handle']);
				}
			} while ($running || $this->_moreTodo());
	
			$msg = '';
			$msg .= "avg rsp time: " . round($this->responded_clients_time / $this->responded_clients, 2) . "s, ";
			$msg .= "avg rsp/min: " . round($this->responded_clients / $this->elapsed(), 2) * 60 . ", ";
			$msg .= "responses: " . $this->responded_clients . ", ";
			$msg .= "elapsed: " . $this->elapsed() . "s";
			$this->alert($msg);
		}    
		
		private function _addHandles($num){
			while ($num-- > 0) {
				$handle = curl_init($this->allToDo[$this->currentIndex]);
				curl_setopt_array($handle, $this->options);
				curl_multi_add_handle($this->multiHandle, $handle);
				$this->info[$handle]['url'] = $this->allToDo[$this->currentIndex];
				$this->info[$handle]['key'] = $this->currentIndex;
				$this->info[$handle]['time'] = $this->microtime();
				$this->currentIndex++;
			}
		}
		
		private function _moreToDo(){
			return count($this->allToDo) - $this->currentIndex;
		}
		
		private function _showData($multiInfo){
			$this->responded_clients_time += $this->elapsed( $this->info[$multiInfo['handle']]['time'] );
			$this->responded_clients++;
	
			$this->info[$multiInfo['handle']]['multi'] = $multiInfo;
			$this->info[$multiInfo['handle']]['curl']  = curl_getinfo($multiInfo['handle']);
	#		$this->alert( print_r($this->info[$multiInfo['handle']],true) );
			$content = curl_multi_getcontent($multiInfo['handle']);
	
			$this->alert( 
				$this->info[$multiInfo['handle']]['key'] . ' - ' . 
				$this->info[$multiInfo['handle']]['url'] . ' - ' . 
				$content.' - '.
				$this->elapsed( $this->info[$multiInfo['handle']]['time'] ).'s' 
			);
		}
		function __destruct() {
			curl_multi_close($this->multiHandle);
			ob_end_clean();
		}
	}