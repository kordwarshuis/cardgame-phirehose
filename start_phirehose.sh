#!/bin/bash
#(Re)start Phirehose twitter feeds
#Called by script_phirehose.sh
# hvancann@bcws.io

  #start up phirehose-collect.php again
  screen -dmS phirehosecollect php phirehose-collect.php;
  sleep 0.5;
  #start up phirehose-consume.php again
  screen -dmS phirehoseconsume php phirehose-consume.php;
  sleep 0.5;
  #start up phirehose-create-tweets-history.php again
  screen -dmS phirehosehistory php phirehose-create-tweets-history.php;
  sleep 0.5;