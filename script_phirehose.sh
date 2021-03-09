#!/bin/bash
#Cronjob to stop and restart Phirehose twitter feeds
# hvancann@bcws.io
cd ~/domains/blockchainbird.org/public_html/t/twitter-phirehose/
#Be very aware that these variables are crucial for the survival of your running system!!
NAMESTR="phirehose"
UIDSTR="blockcb"
MIN_LEN1=8
MIN_LEN2=5
KILL_GO=0
errcode=0
# <- Be very sure to use deterministic string to select from the list of PIDs

#Check the length of the input strings. If they are long enough the kill comaand will proceed!
#Again: this is a loose canon.
if [[ ${#NAMESTR} > ${MIN_LEN1} ]] && [[ ${#UIDSTR} > ${MIN_LEN2} ]]; 
then KILL_GO=1
fi

#Find the processes, select only those that have both NAMESTR and UIDSTR 
#and isolate the list of PIDs
if [[ ${KILL_GO} ]];
then {
  echo "Every process that contains '${NAMESTR}' in name and '${UIDSTR}' in UID will be killed.";
  errcode=$(ps -ef | grep $NAMESTR | grep $UIDSTR | grep -v $$ | tr -s ' ' | cut -d ' ' -f2 | xargs kill -s 9 2> /dev/null ); 
  # sttderr -> null: we don't want to know that self-kill did not succeed.
  # grep -v $$ -> exclude the PID of this bash script
  echo "Error code of kill pipe: " $errcode >&2
  #Example   ps -ef | grep phirehose | grep blockcb | tr -s ' ' | cut -d ' ' -f2 ...
  # creates tweets history https://github.com/kordwarshuis/cardgame/issues/124 
  # ./phirehose-create-tweets-history.php

  # start the service again:
  ./start_phirehose.sh; 
  exit 0 #the script succeeded
}
else {
  echo "No kill op processes by $$"
  echo "Length of argument '$NAMESTR' is ${#NAMESTR}, minimum is $MIN_LEN1.";
  echo "Length of argument '$UIDSTR' is ${#UIDSTR}, minimum is $MIN_LEN2.";
  exit 1
}
fi
