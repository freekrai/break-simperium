#!/bin/bash
 
url="http://rser.me/simperium/index.php";
 
username=$1;
headersonly=$2;
randomwait=$3;
 
userparam="";
if [[ $username == "y" ]]; then
    userparam="?param=foobar";
fi
 
onlyheadersoption="";
if [[ $headersonly == "y" ]]; then
    onlyheadersoption="-I";
fi
 
dowait="";
if [[ $randomwait == "y" ]]; then
    dowait="y";
fi
 
while [ 0 -ne 1 ]; do
    if [[ -e /tmp/stop.txt ]]; then
        break;
    fi
    if [[ $dowait == "y" ]]; then
        sleep ${RANDOM:0:1};
    fi
    time curl ${onlyheadersoption} $url${userparam}
done
