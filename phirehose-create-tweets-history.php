<?php
/* workflow creating file with tweets history

The script creates a JSON file of a history of tweets composed from a source JSON file containing tweets in Twitter format, that is refreshed every ten seconds.

The script runs on the command line every ten seconds.

Source file: tweets.json (created via a modified version of https://github.com/fennb/phirehose)
Target file: tweets-history.json

Workflow:
-create tweets-history.json file
-for every tweet in tweets.json check if it is already in tweets-history.json
-if the tweet is already in tweets-history.json go to next tweet in tweets.json, if not: add the tweet to tweets-history.json
-the script runs every ten seconds via sleep command

Attention: the file should not grow above a certain size (to prevent server crash or undesired hosting costs), so there should be a mechanism that removes tweets from the tail when file reaches a certain size or number of tweets (should be configurable)

example code:
- https://stackoverflow.com/questions/31111963/json-manipulation-in-php
- https://stackoverflow.com/questions/34986948/how-to-remove-duplicate-data-of-json-object-using-php

RUN FROM COMMAND LINE:
http://stackoverflow.com/a/23028860
start:
$php create-tweets-history.php

*/

function processTweets()
{
    $source = 'tweetsDevelop.json';
    $target = 'tweets-history.json';

//     var_dump(json_decode(
//       file_get_contents($source), true
//   ));

    // JSON string from file to PHP array
    $arraySource = json_decode(
        file_get_contents($source), true
    );

    // https://stackoverflow.com/a/34987161
    $arrayTarget = file_get_contents($target);

    // JSON string to PHP array
    $arrayTarget = json_decode($arrayTarget);
    // echo gettype($arrayTarget);

    foreach ($arraySource as $i => $i_value) {
        // merge the array item with existing array
        $arrayTarget = array_merge($arrayTarget, $i_value);
    }

    // https://stackoverflow.com/a/34987161
    $arrayTarget = array_values(array_unique($arrayTarget, SORT_REGULAR));

    // to JSON string
    $arrayTarget = json_encode($arrayTarget);

    file_put_contents('tweets-history.json', $arrayTarget);
}

// processTweets();

while (true) {
    processTweets();
    sleep(10); // in seconds
}