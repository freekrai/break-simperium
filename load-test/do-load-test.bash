#!/bin/bash
 
span=$1;
username=$2;
headersonly=$3;
randomwait=$4;
 
function help {
    echo "Usage: ./load-tester.bash <number-of-users-to-span> <boolean-use-service> <boolean-headers-only> <boolean-random-wait>";
    echo "    number-of-users-to-span: The number of simulated users hitting REST. (0-n where n is an Integer)";
    echo "    boolean-use-service: Call the service by adding a param=foobar parameter. (y or n)";
    echo "    boolean-headers-only: Only cURL the headers. (y or n)";
    echo "    boolean-random-wait: Wait a random amount of time from 1-9 seconds before hitting server. (y or n)";
    echo "";
    echo "If you create /tmp/stop.txt, the load tester will exit.";
}
 
if [[ $1 == "" ]]; then
    help;
    exit;
fi
 
if [[ $2 == "" ]]; then
    help;
    exit;
fi
 
if [[ $3 == "" ]]; then
    help;
    exit;
fi
 
if [[ $5 == "" ]]; then
    help;
    exit;
fi
 
if [[ -e /tmp/stop.txt ]]; then
    echo "Cannot start tests. Remove /tmp/stop.txt first.";
    exit;
fi
 
for ((i=0; i<$span; i++)); do
    ./load-tester.bash $username $headersonly $randomwait &
done
