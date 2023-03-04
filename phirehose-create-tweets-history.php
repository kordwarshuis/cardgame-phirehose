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
$previousTweetsCount = 0;

while (true) {
    processTweets($source, $target, $maxTweets, $maxHashTags, $forbiddenWords, $requiredWords, $minFollowers, $previousTweetsCount);
    sleep(10); // in seconds
}


function processTweets($source, $target, $maxTweets = 500, $maxHashTags, $forbiddenWords, $requiredWords, $minFollowers, &$previousTweetsCount)
{
    // JSON string from file to PHP array
    $arraySource = json_decoder($source);
    $arrayTarget = json_decoder($target);
    
    $combinationArray = array_merge($arraySource["statuses"], $arrayTarget["statuses"]);

    // creates new array from 'id_str'
    $tweetIds = array_column($combinationArray, 'id_str');

    // array_combine ( array $keys , array $values ) : array
    // Creates an array by using the values from the keys array as keys and the values from the values array as the corresponding values.
    $modifiedArray = array_combine($tweetIds, $combinationArray);

    // https://stackoverflow.com/a/34987161
    $uniqueArray = array_unique($modifiedArray, SORT_REGULAR);

    /*
    {
    "1384059264382033921": {
    "created_at": "Mon Apr 19 08:20:17 +0000 2021",
    "id": 1384059264382033921,
    "id_str": "1384059264382033921", … ETC

    becomes

    [{
    "created_at": "Mon Apr 19 08:20:17 +0000 2021",
    "id": 1384059264382033921,
    "id_str": "1384059264382033921",…
     */
    $arrayTarget = array_values($uniqueArray);

    $arrayTargetStripped = [];

    // reduce the array by copying
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

    $arrayTargetFinal['statuses'] = $arrayTargetStripped;

    $previousTweetsCount = count($arrayTargetFinal['statuses']);

    $toFile = $previousTweetsCount . ", ";

    file_put_contents('phirehose-log.txt', $toFile, FILE_APPEND);

    $arrayTargetFinalString = json_encode($arrayTargetFinal);

    // sometimes the file is suddenly empty for unknow reasons. This should make this not happen
    if ($arrayTargetFinal['statuses'] >= $previousTweetsCount) {
        copy('tweets-history.json', 'tweets-history-backup.json');
        file_put_contents('tweets-history.json', $arrayTargetFinalString);
        $filesize = filesize("tweets-history.json") . "\n";
        file_put_contents('phirehose-log.txt', $filesize, FILE_APPEND);
    }
}

function json_decoder($file)
{
    $jsonString = file_get_contents($file);
    $json = json_decode($jsonString, true);

    // Validate JSON if invalid
    if (is_null($json)) {
        //  throw new Exception('Source file Contains Invalid JSON: '.$file);
        echo "Problem with JSON";
        // mail('kor@dwarshuis.com', 'Problem with JSON', 'Problem with JSON');
        return json_decode('{"statuses":[{"created_at":"Mon Apr 12 18:07:38 +0000 2021","id":1,"id_str":"1","text":"-","source":"-","truncated":false,"in_reply_to_status_id":null,"in_reply_to_status_id_str":null,"in_reply_to_user_id":null,"in_reply_to_user_id_str":null,"in_reply_to_screen_name":null,"user":{"id":1,"id_str":"1","name":"-","screen_name":"-","location":"-","url":"-","description":"-","translator_type":"1","protected":false,"verified":false,"followers_count":1,"friends_count":2,"listed_count":1,"favourites_count":1,"statuses_count":1,"created_at":"","utc_offset":null,"time_zone":null,"geo_enabled":false,"lang":null,"contributors_enabled":false,"is_translator":false,"profile_background_color":"-","profile_background_image_url":"","profile_background_image_url_https":"","profile_background_tile":false,"profile_link_color":"-","profile_sidebar_border_color":"-","profile_sidebar_fill_color":"-","profile_text_color":"-","profile_use_background_image":true,"profile_image_url":"-","profile_image_url_https":"-","profile_banner_url":"-","default_profile":true,"default_profile_image":false,"following":null,"follow_request_sent":null,"notifications":null},"geo":null,"coordinates":null,"place":null,"contributors":null,"retweeted_status":{"created_at":"","id":1,"id_str":"1","text":"-","source":"-","truncated":false,"in_reply_to_status_id":null,"in_reply_to_status_id_str":null,"in_reply_to_user_id":null,"in_reply_to_user_id_str":null,"in_reply_to_screen_name":null,"user":{"id":1,"id_str":"1","name":"-","screen_name":"-","location":"-","url":null,"description":"-","translator_type":"none","protected":false,"verified":false,"followers_count":1,"friends_count":1,"listed_count":1,"favourites_count":1,"statuses_count":1,"created_at":"","utc_offset":null,"time_zone":null,"geo_enabled":false,"lang":null,"contributors_enabled":false,"is_translator":false,"profile_background_color":"","profile_background_image_url":"","profile_background_image_url_https":"","profile_background_tile":false,"profile_link_color":"","profile_sidebar_border_color":"","profile_sidebar_fill_color":"","profile_text_color":"","profile_use_background_image":true,"profile_image_url":"","profile_image_url_https":"","profile_banner_url":"","default_profile":true,"default_profile_image":false,"following":null,"follow_request_sent":null,"notifications":null},"geo":null,"coordinates":null,"place":null,"contributors":null,"is_quote_status":false,"quote_count":1,"reply_count":1,"retweet_count":1,"favorite_count":1,"entities":{"hashtags":[{"text":"","indices":[1,1]}],"urls":[],"user_mentions":[],"symbols":[]},"favorited":false,"retweeted":false,"filter_level":"low","lang":"en"},"is_quote_status":false,"quote_count":0,"reply_count":0,"retweet_count":0,"favorite_count":0,"entities":{"hashtags":[{"text":"","indices":[1,1]}],"urls":[],"user_mentions":[{"screen_name":"","name":"","id":1,"id_str":"1","indices":[1,1]}],"symbols":[]},"favorited":false,"retweeted":false,"filter_level":"low","lang":"en","timestamp_ms":"1"}]}', true);
    }

    return $json;
}
