#!/bin/sh

url=$1;
span=$2;
randomwait=$3;

function help {
    echo "Usage: ./stress_test.sh <url-to-test> <number-of-users-to-span> <random-wait>";
    echo "    url-to-test: The URL to test";
    echo "    number-of-users-to-span: The number of simulated users hitting REST. (0-n where n is an Integer)";
	echo "    random-wait: y or blank, if y, then we will wait a random number between connections";
    echo "";
}
 
if [[ $1 == "" ]]; then
    help;
    exit;
fi
 
if [[ $2 == "" ]]; then
    help;
    exit;
fi

for ((i=0; i<$span; i++)); do
	time curl -s "$url?[1-5]"  &
    if [[ $randomwait == "y" ]]; then
        sleep ${RANDOM:0:1};
    fi
done