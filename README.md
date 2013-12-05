break-simperium
==============

Simperium is a simple way for developers to move data as it changes, instantly and automatically. 

I want to really test Simeperium, so this app is going to be way to test that.

Running simperium-test.php is pretty simple, from the terminal type:

	php simperium-test.php --clients=<concurrent-clients-to-test> --token=<simperium-token> --appid=<app-id-to-test> --bucket=<bucket-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass> --port=<port-to-connect-to> -q

Where the arguments are as follow:

- *clients*: The number of concurrent users hitting the API. (0-n where n is an Integer)
- *bucket*: simperium bucket
- *token*: simperium token
- *appid*: simperium app id
- *ip*: ip address to test (optional)	
- *hostname*: hostname in headers (optional)
- *port*: port to use (optional)

This will perform a series of test posts to simperium to the bucket you've specified, the posts will occur parallel to each other, and will occur in two pieces.

First, it will perform a series of posts, then it will perform a series of queries to make sure the data exists.

You will get a report along these lines:

	Started at: 2013-12-04 01:39:32. PID: 45713
	Sending posts to simperium
	0 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386193172-0 - post - 200 - 0.64s
	1 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386193172-1 - post - 200 - 0.64s
	------------------
	responses: 2
	status code 200: 2
	------------------
	min response time: 0.64s
	max response time: 0.64s
	median response time: 0.64s
	mean response time: 0.64s
	------------------
	
	Ok, now sending gets to simperium
	0 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386193172-0 - get - 200 - 0.8s
	1 - https://api.simperium.com/1/authorities-platforms-ed8/utest2/i/1386193172-1 - get - 200 - 0.8s
	------------------
	responses: 2
	status code 200: 2
	------------------
	min response time: 0.8s
	max response time: 0.8s
	median response time: 0.8s
	mean response time: 0.8s

Displayed on the screen after it runs.

When you run this test, you'll see each query url showing twice, that is because we do two tests: We first post the data to simperium, and then we perform a get to return that new post.

For this purpose, you'll see a key beside each url, and whether it is a post or a get, followed by the response code, and how long it took to complete.

This way, we also know that the post, and the get worked. 

If you pass -q or --q then you will only see a summary, and not the status of each query.

## Testing Users

A second test tool is simperium-users-test.php, which will test the Simperium Authentication API, this will work by following the following steps:

-	Create a user
-	Authorize the user to verify it works
-	Update the user's password
-	Delete the user

We will test this with as many users as specified by the --clients argument.

Running simperium-users-test.php is pretty simple, from the terminal type:

	php simperium-users-test.php --clients=<concurrent-clients-to-test> --appid=<app-id-to-test> --apikey=<api-key-to-test> --ip=<ip-address-to-test> --hostname=<hostname-to-pass> --port=<port-to-connect-to> -q
	
Where the arguments are as follow:

- *clients*: The number of concurrent users hitting the API. (0-n where n is an Integer)
- *apikey*: simperium api-key
- *token*: simperium token
- *appid*: simperium app id
- *hostname*: hostname in headers (optional)
- *port*: port to use (optional)

The report generated will appear similar to the simperium-test.php report.