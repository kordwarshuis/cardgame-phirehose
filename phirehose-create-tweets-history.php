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

$source = 'tweets.json';
$target = 'tweets-history.json';
$maxTweets = 50000;

while (true) {
    processTweets($source, $target, $maxTweets);
    sleep(10); // in seconds
}


function processTweets($source, $target, $maxTweets = 500)
{




//     var_dump(json_decode(
//       file_get_contents($source), true
//   ));

    // JSON string from file to PHP array
    $arraySource = json_decoder($source);
    // https://stackoverflow.com/a/34987161
    $arrayTarget = json_decoder($target);

    // Replace index with tweet id
    $arrayTarget = isset($arrayTarget["statuses"]) ? $arrayTarget["statuses"] : array();
    $combinationArray = array_merge($arraySource["statuses"], $arrayTarget);
    $tweetIds = array_column($combinationArray, 'id_str');
    $modifiedArray = array_combine($tweetIds, $combinationArray);

    // https://stackoverflow.com/a/34987161
    $uniqueArray = array_unique($modifiedArray, SORT_REGULAR);

    // Remove difference from tailend of tweets, it exceeds $maxTweets
    if (count($uniqueArray) > $maxTweets) {
        $diff = count($uniqueArray) - $maxTweets;
        $uniqueArray = array_slice($uniqueArray, $diff);
    }

    // https://stackoverflow.com/a/34987161
    // Convert to source formart
    $arrayTarget["statuses"] = array_values($uniqueArray);
    $arrayTarget = json_encode($arrayTarget);

    file_put_contents('tweets-history.json', $arrayTarget);

}

function json_decoder($file) {
    $jsonString = file_get_contents($file);
    $json = json_decode($jsonString, true);

    // Validate JSON if invalid
    if (is_null($json)) {
         throw new Exception('Source file Contains Invalid JSON: '.$file);
    }

    return $json;
}