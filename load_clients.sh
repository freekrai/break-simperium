#!/bin/sh

span=$1;
randomwait=$2;

function help {
    echo "Usage: ./stress_test.sh <number-of-users-to-span> <random-wait>";
    echo "    number-of-users-to-span: The number of simulated users hitting REST. (0-n where n is an Integer)";
	echo "    random-wait: y or blank, if y, then we will wait a random number between connections";
    echo "";
}
 
if [[ $1 == "" ]]; then
    help;
    exit;
fi

for ((i=0; i<$span; i++)); do
	php listener.php &
    if [[ $randomwait == "y" ]]; then
        sleep ${RANDOM:0:1};
    fi
done