break-simperium
==============

Simperium is a simple way for developers to move data as it changes, instantly and automatically. 

I want to really test Simeperium, so this app is going to be a two-sided live blog app.

This test consists of two parts:

The first, post.php, simulates users posting to a live blog, the content is random, and of varying lengths. We will be recording how long it takes to post, and any issues that come up during the posting. We will also have multiple clients posting at the same time to the same bucket.

The second part, listener.php is using the Simperium changes API endpoint to listen for changes, in this case, new posts, and then post in a file when a post is received.

We will then look at the listener log and compare records to see if there were any delays between new posts.

Finally, a second post test will be update.php, which will update posts in the live blog app with new data, so we can see how that does.

We're going to store the logs inside a mysql table, the schema for this table is inside the sql.sql file, the logs will get updated when a message begins sending, which it is actually sent, and when it was received.

Each individual instance will have a unique UUID associated with it so we can track it.

### Setup

To get started, create a file called config.php and add the following variables:

```php
<?php
	$appname = 'YOUR-APP-ID';
	$apikey = 'YOUR-API-KEY';
	$token = 'YOUR-USER_TOKEN';
	
	$dbhost = 'localhost';
	$dbname = 'YOUR-DB-NAME';
	$dbuser = 'YOUR-DB-USER';
	$dbpass = 'YOUR-DB-PASS';
	$dbsocket = false;
?>
```

The $dbsocket variable is used if testing on a local host with Mamp, as Mamp wants to connect via sockets rather than TCP. By default, this should be set to false.

**Without this file, nothing will work.**

If you are testing this locally, then you will want to download and install https://ngrok.com/, as that will let you open a tunnel to the outside due to PHP's curl client not wanting a port in the url string, such as port 8888 like Mamp uses.

ngrok also works nicely due to having it's own request inspector at http://localhost:4040 when ngrok is running.

### The actual test

There are two parts to this test, first open up terminal and load the listener:

	./load_clients.sh 100
	
This will load 100 instances of listener.php into the background. Don't worry too much about killing these, you have a few options....

First, Listener is set to die after running for 15 minutes, you can adjust this by changing the $how_long_to_live variable at the start of listener.

Second, you can also kill all instances of listener.php by calling this command:

	touch /tmp/nomorelistener
	
This will trigger listener to stop listening and kill itself.

Inside listener.php, there is also a variable called $silent, setting this to false will output content to your terminal, you don't want to have this set to false when running listener in background mode as your terminal will get messy quickly.

The idea with this, is you can run listener repeatedly and let it run in the background, gather new posts and store in the database when each has arrived.

Now, you want to open a new terminal tab and run the stress test tool...

stress_test.php is a handy tool for stress testing a system, you call it by:

	php stress_test.php url-to-test number-of-clients
	
This will then trigger a test of the url you passed, with a concurrent number of connections specified by number-of-clients

Once completed, you will get a report back for each request, and total time spent sending the request. We can then look and see where any bottle necks began occurring.

This will let you see how each post has done, and on average where any slow down may occur.

### Early tests on post.php and listener.php

I tested with up to 1,155 concurrent connections over a period of 60 seconds.

The results that were generated were a fastest time of 179 ms, a slowest time of 191 ms and an average posting time of 183 ms.

The timing on the seperate clients showed when the messages arrived to be slightly out of sync by 1 to up to 10 seconds between each client when the message arrived.
