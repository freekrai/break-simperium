<?php
/*
include("datagarde.php");

You can add a stat with a count:

	datagarde::count('roger@datagarde.com', 'users signed up', 1);

Or, you can add a stat with a message:

	datagarde::value('roger@datagarde.com', 'message from user', 'message');

And you can group the stats when you call it:

	datagarde::count('roger@datagarde.com', 'users signed up', 1,'mysite.com');
*/
class datagarde{
	static function ApiPost($url,$fields,$type='get'){
		if( is_array($fields) ){
			$good = 1;
			if($good){
				if( $type == 'get' ){	//	get or post...
					$fields_string = '';
					foreach($fields as $key=>$value) { 
						$value = urlencode($value);
						$fields_string .= $key.'='.$value.'&'; 
					} 
					rtrim($fields_string,'&');
					$data = self::get_query($url."?".$fields_string);
				}else{
					$data = self::post_query($url,$fields);
				}
				return $data;
			}
		}else{
			$fields_string = $field;
			rtrim($fields_string,'&');
			$data = self::get_query($url."?".$fields_string,$debug);
			return $data;
		}
		return null;
	}
	static function get_query($url){
		$curl = curl_init($url);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		$data = curl_exec($curl);
		curl_close($curl);
		return $data;
	}
	static function post_query($url,$args){
	    $ch = curl_init();
	    $timeout=5;
	    curl_setopt($ch,CURLOPT_URL,$url);
	    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
	    curl_setopt($ch, CURLOPT_POST, true);
	    
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
	    $data = curl_exec($ch);
	    curl_close($ch);        
	    return $data;
	}
	static function count($email, $stat_name, $count,$group_name=''){
        self::ApiPost("http://datagarde.com/api", array('email' => $email, 'stat' => $stat_name, 'count' => $count, 'group' => $group_name));
	}
	static function value($email, $stat_name, $value,$group_name=''){
		self::ApiPost("http://datagarde.com/api", array('email' => $email, 'stat' => $stat_name, 'value' => $value, 'group' => $group_name),'post');
	}
	static function count_sync($email, $stat_name, $count,$group_name=''){
        return self::ApiPost("http://datagarde.com/api", array('email' => $email, 'stat' => $stat_name, 'count' => $count, 'group' => $group_name, 'sync'=>1));
    }
	static function value_sync($email, $stat_name, $value,$group_name=''){
		return self::ApiPost("http://datagarde.com/api", array('email' => $email, 'stat' => $stat_name, 'value' => $value, 'group' => $group_name, 'sync'=>1));
	}
}
?>
