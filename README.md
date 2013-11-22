break-simperium
==============

Simperium is a simple way for developers to move data as it changes, instantly and automatically. 

I want to really test Simeperium, so this app is going to be a two-sided live blog app.

This test consists of two parts:

The first, post.php, simulates users posting to a live blog, the content is random, and of varying lengths. We will be recording how long it takes to post, and any issues that come up during the posting. We will also have multiple clients posting at the same time to the same bucket.

The second part, listener.php is using the Simperium changes API endpoint to listen for changes, in this case, new posts, and then post in a file when a post is received.

We will then look at the listener log and compare records to see if there were any delays between new posts.

