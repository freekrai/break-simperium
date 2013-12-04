break-simperium
==============

Simperium is a simple way for developers to move data as it changes, instantly and automatically. 

I want to really test Simeperium, so this app is going to be way to test that.

Running simperium_test is pretty simple, from the terminal type:

	php simperium_test.php --clients=<concurrent-clients-to-test> --token=<simperium-token> --appid=<app-id-to-test> --bucket=<bucket-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass> 	

The arguments are as follow:

- clients: The number of simulated users hitting REST. (0-n where n is an Integer)
- bucket: simperium bucket
- token: simperium token
- appid: simperium appiid
- ip: ip address to test (optional)	
- hostname: hostname in headers (optional)


This will perform a series of test posts to simperium to the bucket you've specified, the posts will occur parallel to each other, and will occur in two pieces.

First, it will perform a series of posts, then it will perform a series of queries to make sure the data exists.

You will get a report along these lines:

	Started at: 2013-12-03 03:56:06. PID: 39874
	0 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386114966-0 - post - 200 - 2.73s
	1 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386114966-1 - post - 200 - 2.73s
	0 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386114966-0 - get - 200 - 0.99s
	1 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386114966-1 - get - 200 - 0.99s
	avg rsp time: 1.86s, avg rsp/min: 64.8, responses: 4, elapsed: 3.72s
	
Displayed on the screen when it shows up.