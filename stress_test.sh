#!/bin/sh
curl -s "http://rser.me/symperium/post.php?[1-5]" &
pidlist="$pidlist $!" 
curl -s "http://rser.me/symperium/post.php?[1-5]" &
pidlist="$pidlist $!" 
curl -s "http://rser.me/symperium/post.php?[1-5]" &
pidlist="$pidlist $!" 
curl -s "http://rser.me/symperium/post.php?[1-5]" &
pidlist="$pidlist $!" 
curl -s "http://rser.me/symperium/post.php?[1-5]" &
pidlist="$pidlist $!" 
curl -s "http://rser.me/symperium/post.php?[1-5]" &
pidlist="$pidlist $!" 
curl -s "http://rser.me/symperium/post.php?[1-5]" &
pidlist="$pidlist $!"  

for job in $pidlist do 
  echo $job     
  wait $job || let "FAIL+=1" 
done  

if [ "$FAIL" == "0" ]; then 
  echo "YAY!" 
else 
  echo "FAIL! ($FAIL)" 
fi