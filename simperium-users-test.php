#!/usr/bin/php
<?php
/**
 * simperium-users-test.php
 *
 * Parallel load tester to test the Simperium Authentication API.
 *
 * Call by:
 * 	
 * php simperium-users-test.php --clients=<concurrent-clients-to-test> --appid=<app-id-to-test> --apikey=<api-key-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass> -q -nodelete
*/
date_default_timezone_set('America/Los_Angeles');

/**
* Simperium_User_Test.
*
* Simperium_User_Test performs a set number of tests against the Simperium Authentication API, based on the value of $concurrent.
*
* It will first add a set of users to simperium, then it will perform a series of tests to simperium based on the 
* users it added.
*
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
	private $appid;
	private $apikey;
	private $silent;
	private $clients = array();
	private $info  = array();
	private $mh;
	private $users = array();
	private $results = array();
	private $tests = array();	

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
		    echo "Usage: php simperium-users-test.php --clients=<concurrent-clients-to-test> --appid=<app-id-to-test> --apikey=<api-key-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass> -q -nodelete\n";
		    echo "    clients: The number of concurrent users hitting REST. (0-n where n is an Integer)\n";
			echo "    appid: simperium app-id\n";
			echo "    apikey: simperium api-key\n";
			echo "    ip: ip address to test (optional)\n";		
			echo "    hostname: hostname in headers (optional)\n";
			echo "    port: port to connect to (optional)\n";
			echo "    q: only display summary\n";
			echo "    nodelete: don't delete the test users\n";
		    echo "\n";
			exit;		
		}

		//	set default values...
		$url = 'https://auth.simperium.com';
		$hostname = '';
		$appid = '';
		$apikey = '';
		$port = '';
		$silent = false;
		$delete = true;
		
		if( isset($arguments['ip']) ) $url = $arguments['ip'];
		if( isset($arguments['hostname']) ) $hostname = $arguments['hostname'];
		if( isset($arguments['appid']) ) $appid = $arguments['appid'];
		if( isset($arguments['apikey']) ) $apikey = $arguments['apikey'];
		if( isset($arguments['port']) ) $port = $arguments['port'];
		if( isset($arguments['q']) ) $silent = true;
		if( isset($arguments['nodelete']) ) $delete = false;
		
		$concurrent = $arguments['clients'];
		
		if ( empty($concurrent) || !is_numeric($concurrent) ) {
			die('Please specify a valid clients value!');
		}
		if ( empty($appid) ) {
			die('Please specify a valid app id!');
		}

		//	parse the $url variable to see if there was a port in the url string (ie, http://127.0.0.1:8080)
		$url = parse_url( $url );
		$url = $url['scheme'].'://'.$url['host'];			
		if( isset($url['port']) )	$port = $url['port'];

		$this->tests['create'] = array();
		$this->tests['authorize'] = array();
		$this->tests['update'] = array();
		$this->tests['delete'] = array();
/*
		First, we'll set up a set of users to create,
		
		Then we'll set up the $tests variable with our tests:
		
		-	create a user
		-	authorize said user to make sure he actually got created
		-	update the user to change his password
		-	delete said user to clean up after ourselves (unless -nodelete was passed in the command-line)
*/
		for ($i = 0; $i < $concurrent; $i++) {
			$username = $this->generate_email();
			$password = $this->generate_password();
			$new_password = $this->generate_password();
						
			$this->tests['create'][] = array(
				'url'=> $url.'/1/'.$appid.'/create/',
				'port' => $port,
				'post'=> array(
					'username'=>$username,
					'password'=>$password
				)
			);
			$this->tests['authorize'][] = array(
				'url'=> $url.'/1/'.$appid.'/authorize/',
				'port' => $port,
				'post'=> array(
					'username'=>$username,
					'password'=>$new_password
				)
			);
			$this->tests['update'][] = array(
				'url'=> $url.'/1/'.$appid.'/update/',
				'port' => $port,
				'post'=> array(
					'username'=>$username,
					'password'=>$password,
					'new_password'=>$new_password
				)
			);
			if( $delete ){
				$this->tests['delete'][] = array(
					'url'=> $url.'/1/'.$appid.'/delete/',
					'port' => $port,
					'post'=> array(
						'username'=>$username
					)
				);
			}
		}

		//	populate the values we set from the command-line:
		$this->silent = $silent;
		$this->apikey = $apikey;
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
		foreach($this->tests as $type => $posts ){
			if( !count($posts) )	continue;
			$this->alert("Sending {$type} to simperium");
			$this->multi_request( $posts, 0, $type );
			$times = $this->results[$type]['times'];
			$msg = '';
			$msg .= "------------------\n";
			$msg .= "responses: " . count($times) . "\n";
	
			$line = array();
			foreach( $this->results[$type]['status'] as $code=>$cnt ){
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
		}
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
	* @param    array	$urls{
	*           An array of urls to connect to
	*           @type	int		$id		
	*           @type	array	$data{
	*               @type	string	$url	URL to connect to
	*               @type	int		$port	Port to connect to if a port was passed to use
    *               @type	string	$post	post, this is a json string we send to Simperium
	*			}
	* )
	* @param    int    $pipeline    Either 0 or 1, used to set the CURLMOPT_PIPELINING setting in curl.
	* @param    string    $method    The test being conducted
	*
	*
	*/
	private function multi_request($urls, $pipeline = 0, $method = 'get') {
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
			$headers[] = 'X-Simperium-API-Key: '.$this->apikey;
			if( $this->hostname != '' ){
				$headers[] = 'Host: '.$this->hostname;
			}
			curl_setopt($curly[$id], CURLOPT_HTTPHEADER, $headers);
			$url = (is_array($data) && !empty($data['url'])) ? $data['url'] : $data;
			curl_setopt($curly[$id], CURLOPT_URL,            $url);
			curl_setopt($curly[$id], CURLOPT_HEADER,         0);
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
			if (is_array($data)) {
				//	set the port if a port was passed
				if (!empty($data['port']) ){
					curl_setopt($curly[$id],CURLOPT_PORT, $data['port']);
				}
				//	if post was passed, then 
				if (!empty($data['post']) ) {
					curl_setopt($curly[$id], CURLOPT_POST,       1);
					curl_setopt($curly[$id], CURLOPT_POSTFIELDS, json_encode($data['post']) );
				}
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
	* generate_username.
	*
	* For our testing purposes, we're going to create some random users to use, this function lets us create a random username.
	*
	* @param  	int  	$min 				The minimum string length of the username
	* @param  	int  	$max 				The maximum string length of the username
	* @param  	bool  	$case_sensitive 	Whether the username is case sensitive or not
	*
	* @return	string	$username			The generated username
	*/
	private function generate_username( $min = 5, $max = 15, $case_sensitive = false ){
		// Set length
		$length = rand($min, $max);
		
		// Set allowed chars (And whether they should use case)
		if ( $case_sensitive ){
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		}else{
			$chars = "abcdefghijklmnopqrstuvwxyz";
		}
			
		// Get string length
		$chars_length = strlen($chars);
		
		// Create username char for char
		$username = "";
		
		for ( $i = 0; $i < $length; $i++ ){
			$username .= $chars[mt_rand(0, $chars_length)];
		}
		
		return $username;
	}

	/**
	* generate_email.
	*
	* Generates a random email address.
	*
	* First it grabs a random username from the generate_username, then it picks a random email domain from the $email_domains array.
	* We then put these together into an email address.
	*
	* @param  	int  	$min 				The minimum string length of the username
	* @param  	int  	$max 				The maximum string length of the username
	* @param  	bool  	$case_sensitive 	Whether the username is case sensitive or not
	*
	* @return	float	$diff	Difference in seconds between $time and current microtime
	*/
	private function generate_email( $min = 5, $max = 15, $case_sensitive = false ){
		$email_domains = array('gmail.com', 'yahoo.com', 'hotmail.com','automattic.com','google.com','live.com');

		//	create a randomly generated username....
		$username = $this->generate_username($min,$max,$case_sensitive);
		
		//	grab a random email domain..
		$tld = array_rand($email_domains,2);

		$email = $username.'@'.$email_domains[ $tld[0] ];
		
		//	return the randomly generated email address
		return $email;
	}	

	/**
	* generate_password.
	*
	* Returns randomly generated password.
	*
	* @param  	int  	$min 				The minimum string length of the password
	* @param  	int  	$max 				The maximum string length of the password
	*
	* @return	string	$password			The randomly generated password
	*/
	private function generate_password( $min = 8, $max = 15){
		// Set length
		$length = rand($min, $max);
	
		// Set characters to use
		$lower = 'abcdefghijklmnopqrstuvwxyz';
		$upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$chars = '123456789@#$%&';
		
		// Calculate string length
		$lower_length = strlen($lower);
		$upper_length = strlen($upper);
		$chars_length = strlen($chars);
	
		// Generate password char for char
		$password = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++){
			if ($alt == 0){
				$password .= $lower[mt_rand(0, $lower_length)]; $alt = 1;
			}
			if ($alt == 1){
				$password .= $upper[mt_rand(0, $upper_length)]; $alt = 2;
			}else{
				$password .= $chars[mt_rand(0, $chars_length)]; $alt = 0;
			}
		}
		return $password;
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
			} elseif( preg_match('/-(.*)/',$arg,$reg) ) {
				$_ARG[$reg[1]] = 'true';
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