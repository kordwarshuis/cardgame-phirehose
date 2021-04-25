<?php
ini_set('memory_limit', '1024M'); // or you could use 1G

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

include_once 'phirehose-create-tweets-history-config.php';

while (true) {
    processTweets($source, $target, $maxTweets, $maxHashTags, $forbiddenWords, $requiredWords, $minFollowers);
    sleep(10); // in seconds
}

function processTweets($source, $target, $maxTweets = 500, $maxHashTags, $forbiddenWords, $requiredWords, $minFollowers)
{

    // echo "running\n";

    // JSON string from file to PHP array
    $arraySource = json_decoder($source);
    $arrayTarget = json_decoder($target);

    $combinationArray = array_merge($arraySource["statuses"], $arrayTarget["statuses"]);
    // echo "count combination array: ";
    // echo count($combinationArray);
    // echo "\n";

    // $arrayTargetOld = $arrayTarget;

    // creates new array from 'id_str'
    $tweetIds = array_column($combinationArray, 'id_str');

    // print_r($combinationArray);
    // print_r($tweetIds);

    // array_combine ( array $keys , array $values ) : array
    // Creates an array by using the values from the keys array as keys and the values from the values array as the corresponding values.
    $modifiedArray = array_combine($tweetIds, $combinationArray);

    // https://stackoverflow.com/a/34987161
    // ontdubbel
    // unique array is een object met een key die de id_str is en die heeft als value het hele object (waar de id_str ook weer in staat)
    $uniqueArray = array_unique($modifiedArray, SORT_REGULAR);

    // Remove difference from tailend of tweets, it exceeds $maxTweets
    // if (count($uniqueArray) > $maxTweets) {
    //     $diff = count($uniqueArray) - $maxTweets;
    //     $uniqueArray = array_slice($uniqueArray, $diff);
    // }

    // https://stackoverflow.com/a/34987161
    // array_values ( array $array ) : array
    // array_values() returns all the values from the array and indexes the array numerically.

    // file_put_contents('tweets-TEST1.json', json_encode($uniqueArray));

    /* de value van de key wordt genomen en de key verwijderd, dus:

    {
    "1384059264382033921": {
    "created_at": "Mon Apr 19 08:20:17 +0000 2021",
    "id": 1384059264382033921,
    "id_str": "1384059264382033921", … ETC

    wordt

    [{
    "created_at": "Mon Apr 19 08:20:17 +0000 2021",
    "id": 1384059264382033921,
    "id_str": "1384059264382033921",…

     */
    $arrayTarget = array_values($uniqueArray);
    // file_put_contents('tweets-TEST2.json', json_encode($arrayTarget));

    $arrayTargetStripped = [];

    // array uitdunnen
    for ($i = 0; $i < count($arrayTarget); $i++) {
        $tmp["created_at"] = $arrayTarget[$i]["created_at"];
        $tmp["id_str"] = $arrayTarget[$i]["id_str"];
        $tmp["text"] = $arrayTarget[$i]["text"];
        $tmp["lang"] = $arrayTarget[$i]["lang"];
        $tmp["timestamp_ms"] = $arrayTarget[$i]["timestamp_ms"];
        $tmp["user"]["name"] = $arrayTarget[$i]["user"]["name"];
        $tmp["user"]["screen_name"] = $arrayTarget[$i]["user"]["screen_name"];
        $tmp["user"]["followers_count"] = $arrayTarget[$i]["user"]["followers_count"];
        $tmp["user"]["profile_image_url_https"] = $arrayTarget[$i]["user"]["profile_image_url_https"];
        $tmp["user"]["verified"] = $arrayTarget[$i]["user"]["verified"];

        array_push($arrayTargetStripped, $tmp);

        // remove tweets with not enough followers
        if ($arrayTargetStripped[$i]["user"]["followers_count"] != null) {
            if ($arrayTargetStripped[$i]["user"]["followers_count"] < $minFollowers) {
                unset($arrayTargetStripped[$i]);
            }
        }

        // remove tweets with too many hashtags
        preg_match_all("/(#\w+)/", $arrayTargetStripped[$i]["text"], $matches);
        if ($arrayTargetStripped[$i]["text"] != null) {
            if (count($matches[0]) > $maxHashTags) {
                unset($arrayTargetStripped[$i]);
            }
        }

        // remove tweets with forbidden words or sentences
        if ($arrayTargetStripped[$i]["text"] != null) {
            foreach ($forbiddenWords as $value) {
                if (stripos($arrayTargetStripped[$i]["text"], $value) !== false) {
                    unset($arrayTargetStripped[$i]);
                }
            }
        }

        // only keep tweets with required words or sentences
        if ($arrayTargetStripped[$i]["text"] != null) {
            $found = false;
            // we have to through all $value to see if there is a hit
            foreach ($requiredWords as $value) {
                if (stripos($arrayTargetStripped[$i]["text"], $value) !== false) {
                    $found = true;
                }
            }
            // if no hit, then remove
            if ($found == false) {
                unset($arrayTargetStripped[$i]);
            }
        }
    }

    // remove tail if longer than allowed number of tweets
    if (count($arrayTargetStripped) > $maxTweets) {
        $diff = count($arrayTargetStripped) - $maxTweets;
        $arrayTargetStripped = array_slice($arrayTargetStripped, 0, (count($arrayTargetStripped) - $diff));
    }

    $arrayTargetStripped = array_values($arrayTargetStripped);

    // print_r($arrayTargetStripped);
    // var_dump($arrayTargetStripped);

    $arrayTargetFinal['statuses'] = $arrayTargetStripped;

    $arrayTargetFinal = json_encode($arrayTargetFinal);

    file_put_contents('tweets-history.json', $arrayTargetFinal);
}

function json_decoder($file)
{
    $jsonString = file_get_contents($file);
    $json = json_decode($jsonString, true);

    // Validate JSON if invalid
    if (is_null($json)) {
        //  throw new Exception('Source file Contains Invalid JSON: '.$file);
        echo "Problem with JSON";
        return json_decode('{"statuses": [{}]}', true);
    }

    return $json;
}
