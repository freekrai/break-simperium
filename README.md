break-simperium
==============

Simperium is a simple way for developers to move data as it changes, instantly and automatically. 

I want to really test Simeperium, so this app is going to be way to test that.

Running simperium_test.php is pretty simple, from the terminal type:

	php simperium_test.php --clients=<concurrent-clients-to-test> --token=<simperium-token> --appid=<app-id-to-test> --bucket=<bucket-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass> 	

Where the arguments are as follow:

- *clients*: The number of concurrent users hitting the API. (0-n where n is an Integer)
- *bucket*: simperium bucket
- *token*: simperium token
- *appid*: simperium appiid
- *ip*: ip address to test (optional)	
- *hostname*: hostname in headers (optional)

This will perform a series of test posts to simperium to the bucket you've specified, the posts will occur parallel to each other, and will occur in two pieces.

First, it will perform a series of posts, then it will perform a series of queries to make sure the data exists.

You will get a report along these lines:

	Started at: 2013-12-03 09:03:34. PID: 40342
	0 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386133414-0 - post - 200 - 1.17s
	1 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386133414-1 - post - 200 - 1.17s
	0 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386133414-0 - get - 200 - 0.58s
	1 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386133414-1 - get - 200 - 0.58s
	status codes returned: 200 = 4
	min response time: 0.58s, max response time: 1.17s, median response time: 1.17s, mean response time: 0.875s
	average response time: 0.88s, average response / min: 137.4, responses: 4, elapsed: 1.75s
	Finished at: 2013-12-03 09:03:36. PID: 40342
	
Displayed on the screen after it runs.

When you run this test, you'll see each query url showing twice, that is because we do two tests: We first post the data to simperium, and then we perform a get to return that new post.

For this purpose, you'll see a key beside each url, and whether it is a post or a get, followed by the response code, and how long it took to complete.

This way, we also know that the post, and the get worked. 