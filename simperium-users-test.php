#!/usr/bin/php
<?php
/**
 * simperium-users-test.php
 *
 * Parallel load tester to test Simperium users.
 *
 * Call by:
 * 	
 * php simperium-users-test.php --clients=<concurrent-clients-to-test> --token=<simperium-token> --appid=<app-id-to-test> --bucket=<bucket-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass> 	
*/
date_default_timezone_set('America/Los_Angeles');

/**
* Simperium_User_Test.
*
* Simperium_User_Test performs a set number of tests against the Simperium authorization API, based on the value of $concurrent.
* It will first add a set of users to simperium, then it will perform a series of tests to simperium based on the 
* users it added.
* 
* We will then return a report describing the results of the tests, and if any failures occurred.
*
*/
class Simperium_User_Test{
	private $slug;
	private $responded_clients = 0;
	private $responded_clients_time = 0;
	private $time = 0;
	private $hostname = '';
	private $token;
	private $bucket;
	private $silent;
	private $clients = array();
	private $info  = array();
	private $mh;
	private $posts = array();
	private $gets = array();
	private $results = array();

	/**
	* Constructor.
	*
	* The constructor function, handles setting up our inititial queries.
	* It takes the arguments passed from the command-line and from there sets up our variables on how the test will behave.
	*
	* @global	array	$argv		Global array set inside php's core from CLI apps
	*/		
	public function __construct(){
		global $argv;
		$this->slug = time();

		//	get arguments from command line and parse them into a usable array
		$arguments = $this->get_arguments( $argv );
		
		//	no arguments were passed.. we don't know what to do.. display help information instead..
		if( !count($arguments) ){
		    echo "Usage: php simperium_test.php --clients=<concurrent-clients-to-test> --bucket=<simperium-bucket> --token=<simperium-token> --appid=<app-id-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass>\n";
		    echo "    clients: The number of concurrent users hitting REST. (0-n where n is an Integer)\n";
			echo "    bucket: simperium bucket\n";
			echo "    token: simperium token\n";
			echo "    appid: simperium app-id\n";
			echo "    ip: ip address to test (optional)\n";		
			echo "    hostname: hostname in headers (optional)\n";
			echo "    port: port to connect to (optional)\n";
			echo "    q: only display summary\n";
		    echo "\n";
			exit;		
		}
		
		//	set default values...
		$url = 'https://auth.simperium.com';
		$hostname = '';
		$token = '';
		$appid = '';
		$bucket = '';
		$port = '';
		$silent = false;
		
		if( isset($arguments['ip']) ) $url = $arguments['ip'];
		if( isset($arguments['hostname']) ) $hostname = $arguments['hostname'];
		if( isset($arguments['token']) ) $token = $arguments['token'];
		if( isset($arguments['appid']) ) $appid = $arguments['appid'];
		if( isset($arguments['bucket']) ) $bucket = $arguments['bucket'];
		if( isset($arguments['port']) ) $port = $arguments['port'];
		if( isset($arguments['q']) ) $silent = true;
		
		$concurrent = $arguments['clients'];
		
		if ( empty($concurrent) || !is_numeric($concurrent) ) {
			die('Please specify a valid clients value!');
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

		//	parse the $url variable to see if there was a port in the url string (ie, http://127.0.0.1:8080)
		$url = parse_url( $url );
		$url = $url['scheme'].'://'.$url['host'];			
		if( isset($url['port']) )	$port = $url['port'];
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
					'text'=>$this->get_random_text()
				)
			);
			$this->gets[] = array(
				'url'=> $url.'/1/'.$appid.'/'.$bucket.'/i/'.$this->slug.'-'.$i,
				'port' => $port,					
			);
		}

		//	populate the values we set from the command-line:
		$this->silent = $silent;
		$this->token = $token;
		$this->hostname = $hostname;
		$this->time = $this->microtime();

		//	start the testing...
		$this->process();
	}

	/**
	* process.
	*
	* The brains of the class, process takes the posts and gets variables and performs the curl requests on them, it then
	* builds a report showing stats based on the returned results.
	*
	*/
	private function process(){
		$this->time = $this->microtime();
		$this->alert('Started at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());

		$this->alert("Sending posts to simperium");
		$this->multi_request( $this->posts, 0 );

		$times = $this->results['post']['times'];
		$msg = '';
		$msg .= "------------------\n";
		$msg .= "responses: " . count($times) . "\n";

		$line = array();
		foreach( $this->results['post']['status'] as $code=>$cnt ){
			$line[] = "status code ".$code.": ".$cnt;
		}
		$msg .= implode("\n",$line)."\n";
		$msg .= "------------------\n";

		$msg .= "min response time: " . min($times) . "s\n";
		$msg .= "max response time: " . max($times) . "s\n";
		$msg .= "median response time: " . $this->get_median($times) . "s\n";
		$msg .= "mean response time: " . $this->get_mean($times) . "s\n";
		$msg .= "------------------\n";
		$this->alert($msg);

		$this->alert("Ok, now sending gets to simperium");
		$this->multi_request( $this->gets, 1 );

		$times = $this->results['get']['times'];
		$msg = '';
		$msg .= "------------------\n";
		$msg .= "responses: " . count($times) . "\n";

		$line = array();
		foreach( $this->results['get']['status'] as $code=>$cnt ){
			$line[] = "status code ".$code.": ".$cnt;
		}
		$msg .= implode("\n",$line)."\n";
		$msg .= "------------------\n";

		$msg .= "min response time: " . min($times) . "s\n";
		$msg .= "max response time: " . max($times) . "s\n";
		$msg .= "median response time: " . $this->get_median($times) . "s\n";
		$msg .= "mean response time: " . $this->get_mean($times) . "s\n";
		$this->alert($msg);
		$this->alert('Finished at: ' . date('Y-m-d h:i:s') . '. PID: ' . getmypid());
		exit;
	}

	/**
	* multi_request.
	*
	* Handles our curl requests, and returns  results of each query we made to simperium.
	* If the $silent variable is not set to true (meaning we didn't pass -q in the command line, then we will see a brief summary of each 
	* query we performed.
	* 
	* @see		process
	*
	* @param 	array	$urls{
	*			An array of urls to connect to
	*			@type	int		$id		
	*			@type	array	$data{
	*				@type	string	$url	URL to connect to
	*				@type	int		$port	Port to connect to if a port was passed to use
					@type	string	$post	post, this is a json string we send to Simperium
	*			}
	* )
	*
	*/
	private function multi_request($urls, $pipeline = 0, $options = array()) {
		$curly = array();
		$result = array();
		$mh = curl_multi_init();
		
		if( function_exists('curl_multi_setopt') ){
			curl_multi_setopt( $mh, CURLMOPT_PIPELINING, $pipeline);
			curl_multi_setopt( $mh, CURLMOPT_MAXCONNECTS, 100);
		}
		$i = 0;

		foreach ($urls as $id => $data ) {
			$curly[$id] = curl_init();
			$headers = array();
			$headers[] = 'X-Simperium-Token: '.$this->token;
			if( $this->hostname != '' ){
				$headers[] = 'Host: '.$this->hostname;
			}
			curl_setopt($curly[$id], CURLOPT_HTTPHEADER, $headers);
			$url = (is_array($data) && !empty($data['url'])) ? $data['url'] : $data;
			curl_setopt($curly[$id], CURLOPT_URL,            $url);
			curl_setopt($curly[$id], CURLOPT_HEADER,         0);
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
			$method = 'get';
			if (is_array($data)) {
				//	set the port if a port was passed
				if (!empty($data['port']) ){
					curl_setopt($curly[$id],CURLOPT_PORT, $data['port']);
				}
				//	if post was passed, then 
				if (!empty($data['post']) ) {
					$method = 'post';
					curl_setopt($curly[$id], CURLOPT_POST,       1);
					curl_setopt($curly[$id], CURLOPT_POSTFIELDS, json_encode($data['post']) );
				}
			}
			
			//	if any options were passed for this connection
			if (!empty($options)) {
				curl_setopt_array($curly[$id], $options);
			}
			//	add this curl connection to our multi so it will get run at once.
			curl_multi_add_handle($mh, $curly[$id]);

			//	Let's set the info for this query, which is the url, key, current microtime, and the method used (post or get)
			$this->info[$id]['url'] = $data['url'];
			$this->info[$id]['key'] = $i;
			$this->info[$id]['time'] = $this->microtime();
			$this->info[$id]['method'] = $method;
			$i++;
		}
		
		//	enter a while loop and remain in the loop until we finish all our connections...
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while($running > 0);
		
		//	We've finished running, now let's get some info together.
		foreach($curly as $id => $c) {

			//	Let's set some more info to the $info variable, we will set the headers, status code, and content, as well as the end time.
			$this->info[$id]['headers'] = curl_getinfo($c);
			$this->info[$id]['status'] = $this->info[$id]['headers']['http_code'];
			$this->info[$id]['content'] = curl_multi_getcontent($c);
			//	get the end time, which is the total time curl has returned that the query took..
			$this->info[$id]['endtime'] = round($this->info[$id]['headers']['total_time'],2);

			$this->responded_clients_time += $this->info[$id]['endtime'];
			$this->responded_clients++;

			//	remove this query from the curl multi handle.
			curl_multi_remove_handle($mh, $c);

			//	if $silent is not true, then echo a brief summary of the query.
			if( !$this->silent ) {
				$this->alert( 
					$this->info[$id]['key'] . ' - ' . 
					$this->info[$id]['url'] . ' - ' . 
					$this->info[$id]['method'] . ' - ' . 
					$this->info[$id]['status'] .' - '. 
					$this->info[$id]['endtime'].'s' 
				);
			}

			//	add the results to our $results variable containing the end time, and the status
			if( !isset($this->results[ $this->info[$id]['method'] ]['times']) ){
				$this->results[ $this->info[$id]['method'] ]['times'] = array();
				$this->results[ $this->info[$id]['method'] ]['methods'] = array();
				$this->results[ $this->info[$id]['method'] ]['status'] = array();
				$this->results[ $this->info[$id]['method'] ]['methods'][ $this->info[$id]['method'] ][ $this->info[$id]['status'] ] = 0;
				$this->results[ $this->info[$id]['method'] ]['status'][ $this->info[$id]['status'] ] = 0;
			}
			$this->results[ $this->info[$id]['method'] ]['times'][] = $this->info[$id]['endtime'];
			$this->results[ $this->info[$id]['method'] ]['methods'][ $this->info[$id]['method'] ][ $this->info[$id]['status'] ]++;
			$this->results[ $this->info[$id]['method'] ]['status'][ $this->info[$id]['status'] ]++;
		}
		curl_multi_close($mh);
	}


	/**
	* elapsed.
	*
	* Returns the time elapsed in seconds between two microtimes.
	*
	* @param  	float  	$time 	The time to compare it to, if it is 0, then we use the $time variable we initialized in the constructor.
	*
	* @return	float	$diff	Difference in seconds between $time and current microtime
	*/
	private function elapsed($time = 0) {
		if (empty($time)) $time = $this->time;
		$diff = $this->microtime() - $time;
		$diff = round($diff,2);
		return $diff;
	}

	/**
	* microtime.
	*
	* Returns the current microtime.
	*
	* @return	float		$microtime	The current microtime.
	*/
	private function microtime() {
		return microtime(true);
	}

	/**
	* alert.
	*
	* Outputs the given message to the screen.
	*
	* @param	string		$msg		The message to output to the screen.
	*/
	private function alert($msg) {
		echo $msg . (!empty($_SERVER['HTTP_USER_AGENT']) ? "<br />" : "\n");
	}

	/**
	* get_mode.
	*
	* The mode is defined as the element that appears most frequently in a given set of elements. Using the definition of frequency given 
	* above, mode can also be defined as the element with the largest frequency in a given data set.
	* 
	* @see		process
	*
	* @param 	array	$array{
	*			An array of numbers to find the mode of
	* )
	*
	*/
	private function get_mode( $array ){
		$v = array_count_values($array); 
		arsort($v); 
		foreach($v as $k => $v){$total = $k; break;} 
		return $total;
	}

	/**
	* get_range.
	*
	* The range is defined as the difference between the highest and lowest number in a given data set.
	* 
	* @see		process
	*
	* @param 	array	$array{
	*			An array of numbers to find the range of
	* )
	*
	*/
	private function get_range( $array ){
		sort($array); 
		$sml = $array[0]; 
		rsort($array); 
		$lrg = $array[0]; 
		return $lrg - $sml; 
	}

	/**
	* get_median.
	*
	* The median is defined as the number in the middle of a given set of numbers arranged in order of increasing magnitude. 
	* 
	* @see		process
	*
	* @param 	array	$array{
	*			An array of numbers to find the median of
	* )
	*
	*/
	private function get_median( $array ){
		rsort($array); 
		$middle = round(count($array) / 2); 
		return $array[$middle-1]; 
	}

	/**
	* get_mean.
	*
	* Return the average of the numbers passed to it.
	* 
	* @see		process
	*
	* @param 	array	$array{
	*			An array of numbers to find the mean of
	* )
	*
	*/
	private function get_mean( $array ){
		$count = count( $array );
		return (array_sum($array) / $count);
	}

	/**
	* get_arguments.
	*
	* Handy function for CLI-based apps that parse all arguments that start with -- or - and return an array with the key 
	* as that argument and the variable
	*
	* @see		Constructor
	*
	* @param 	array	$argv	arguments passed from command line.
	*
	* @return	array	$_ARG	array of arguments where the key is the argument we passed, and the value is the value of said argument.
	*							If not value was set (for example -q), then set set the value of the key to true.
	* 
	*/
	private function get_arguments($argv) {
		$_ARG = array();
		foreach ($argv as $arg) {
			if ( preg_match('/--(.*)=(.*)/',$arg,$reg) ) {
				$_ARG[$reg[1]] = $reg[2];
			} elseif( preg_match('/-([a-zA-Z0-9])/',$arg,$reg) ) {
				$_ARG[$reg[1]] = 'true';
			}
		}
		return $_ARG;
	}
	
	/**
	* get_random_text.
	*
	* Generates a blob of random text that we can use for simulating our posts to Simperium.
	*
	* @return	string	$data	Randomly generated text.
	* 
	*/
	private function get_random_text(){
		$size = rand(1,20);
		$data = '';
		for( $i = 0;$i <= $size;$i++){
			$data .= substr(md5(rand(0, 1000000)), 0, 35);
		}
		return $data;
	}
}

//	Load our Simperium_User_Test class and call process to begin the testing...
new Simperium_User_Test();