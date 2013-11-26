break-simperium
==============

Simperium is a simple way for developers to move data as it changes, instantly and automatically. 

I want to really test Simeperium, so this app is going to be a two-sided live blog app.

This test consists of two parts:

The first, post.php, simulates users posting to a live blog, the content is random, and of varying lengths. We will be recording how long it takes to post, and any issues that come up during the posting. We will also have multiple clients posting at the same time to the same bucket.

The second part, listener.php is using the Simperium changes API endpoint to listen for changes, in this case, new posts, and then post in a file when a post is received.

We will then look at the listener log and compare records to see if there were any delays between new posts.

Finally, a second post test will be update.php, which will update posts in the live blog app with new data, so we can see how that does.

Logs will be stored inside datagarde.com, a stats collection website that will just store when a message begins sending, when it is actually sent and then when it was received.

Each individual instance will have a unique UUID associated with it so we can track it.

## Setup

To get started, create a file called config.php and add the following variables:

```php
<?php
	$appname = 'YOUR-APP-ID';
	$apikey = 'YOUR-API-KEY';
	$token = 'YOUR-USER_TOKEN';
?>

Without this file, nothing will work.

## Early tests on post.php and listener.php

I tested with up to 1,155 concurrent connections over a period of 60 seconds.

The results that were generated were a fastest time of 179 ms, a slowest time of 191 ms and an average posting time of 183 ms.

The timing on the seperate clients showed when the messages arrived to be slightly out of sync by 1 to up to 10 seconds between each client when the message arrived.

## Notes

One thing worth noting, according to https://simperium.com/docs/reference/http/#bucketall, when you pass cv as a variable then it is supposed to resume from that record, but I've found it starts from the beginning.