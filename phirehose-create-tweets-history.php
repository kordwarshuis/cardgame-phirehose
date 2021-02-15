<?php
/* workflow creating file with tweets history

This script creates a file with a history of tweets out of a source file with tweets that is refreshed everey ten seconds.

Source file: tweets.json
Target file: tweets-history.json

Flow of the script:
-create tweets-history.json
-for every tweet in tweets.json check if it is already in tweets-history.json
-if the tweet is already in tweets-history.json go to next tweet in tweets.json, if not: add the tweet to tweets-history.json
-if desired: do checks before adding tweet, for example: only if number of followers of poster is above 1000, etc
-the script runs every ten seconds via sleep command
-the file should not grow above a certain size (to prevent server crash or undesired hosting costs), so there should be a mechanism that removes tweets from the tail when file reaches a certain size

example code:
- https://stackoverflow.com/questions/31111963/json-manipulation-in-php
- https://stackoverflow.com/questions/34986948/how-to-remove-duplicate-data-of-json-object-using-php

json_encode — Returns the JSON representation of a value
json_decode — Decodes a JSON string, Takes a JSON encoded string and converts it into a PHP variable.

RUN FROM COMMAND LINE:
start:
$php create-tweets-history.php

stop:
Ctrl - C

 */




$fixJSON = new Func("fixJSON", function($json = null) use (&$JSON, &$Array) {
  $bulkRegex = new Func("bulkRegex", function($str = null, $callback = null) use (&$Array) {
    if (is($callback) && (isset($callback) ? _typeof($callback) : "undefined") === "function") {
      return call($callback, $str);
    } else if (is($callback) && is(call_method($Array, "isArray", $callback))) {
      for ($i = 0.0; $i < get($callback, "length"); $i++) {
        if (is(get($callback, $i)) && _typeof(get($callback, $i)) === "function") {
          $str = call_method($callback, $i, $str);
        } else {
          break;
        }

      }
      return $str;
    }

    return $str;
  });
  if (is($json) && $json !== "") {
    if ((isset($json) ? _typeof($json) : "undefined") !== "string") {
      try {
        $json = call_method($JSON, "stringify", $json);
      } catch(Exception $e_1_) {
        if ($e_1_ instanceof Ex) $e_1_ = $e_1_->value;
        return false;
      }
    }
    if ((isset($json) ? _typeof($json) : "undefined") === "string") {
      $json = call($bulkRegex, $json, false, new Arr(new Func(function($str = null) {
        return call_method($str, "replace", new RegExp("[\\n\\t]", "gm"), "");
      }), new Func(function($str = null) {
        return call_method($str, "replace", new RegExp(",\\}", "gm"), "}");
      }), new Func(function($str = null) {
        return call_method($str, "replace", new RegExp(",\\]", "gm"), "]");
      }), new Func(function($str = null) use (&$bulkRegex) {
        $str = call_method($str, "split", new RegExp("(?=[,\\}\\]])", "g"));
        $str = call_method($str, "map", new Func(function($s = null) use (&$bulkRegex) {
          if (is(call_method($s, "includes", ":")) && is($s)) {
            $strP = call_method($s, "split", new RegExp(":(.+)", ""), 2.0);
            set($strP, 0.0, call_method(get($strP, 0.0), "trim"));
            if (is(get($strP, 0.0))) {
              $firstP = call_method(get($strP, 0.0), "split", new RegExp("([,\\{\\[])", "g"));
              set($firstP, to_number(get($firstP, "length")) - 1.0, call($bulkRegex, get($firstP, to_number(get($firstP, "length")) - 1.0), false, new Func(function($p = null) {
                return call_method($p, "replace", new RegExp("[^A-Za-z0-9\\-_]", ""), "");
              })));
              set($strP, 0.0, call_method($firstP, "join", ""));
            }
            $part = call_method(get($strP, 1.0), "trim");
            if (is(call_method($part, "startsWith", "\"")) && is(call_method($part, "endsWith", "\"")) || is(call_method($part, "startsWith", "'")) && is(call_method($part, "endsWith", "'")) || is(call_method($part, "startsWith", "`")) && is(call_method($part, "endsWith", "`"))) {
              $part = call_method($part, "substr", 1.0, to_number(get($part, "length")) - 2.0);
            }
            $part = call($bulkRegex, $part, false, new Arr(new Func(function($p = null) {
              return call_method($p, "replace", new RegExp("([\"])", "gm"), "\\\$1");
            }), new Func(function($p = null) {
              return call_method($p, "replace", new RegExp("\\\\'", "gm"), "'");
            }), new Func(function($p = null) {
              return call_method($p, "replace", new RegExp("\\\\`", "gm"), "`");
            })));
            set($strP, 1.0, call_method(_concat("\"", $part, "\""), "trim"));
            $s = call_method($strP, "join", ":");
          }
          return $s;
        }));
        return call_method($str, "join", "");
      }), new Func(function($str = null) {
        return call_method($str, "replace", new RegExp("(['\"])?([a-zA-Z0-9\\-_]+)(['\"])?:", "g"), "\"\$2\":");
      }), new Func(function($str = null) {
        $str = call_method($str, "split", new RegExp("(?=[,\\}\\]])", "g"));
        $str = call_method($str, "map", new Func(function($s = null) {
          if (is(call_method($s, "includes", ":")) && is($s)) {
            $strP = call_method($s, "split", new RegExp(":(.+)", ""), 2.0);
            set($strP, 0.0, call_method(get($strP, 0.0), "trim"));
            if (is(call_method(get($strP, 1.0), "includes", "\"")) && is(call_method(get($strP, 1.0), "includes", ":"))) {
              $part = call_method(get($strP, 1.0), "trim");
              set($strP, 1.0, call_method(_concat("\"", $part, "\""), "trim"));
            }
            $s = call_method($strP, "join", ":");
          }
          return $s;
        }));
        return call_method($str, "join", "");
      })));
      try {
        $json = call_method($JSON, "parse", $json);
      } catch(Exception $e_2_) {
        if ($e_2_ instanceof Ex) $e_2_ = $e_2_->value;
        return false;
      }
    }
    return $json;
  }
  return false;
});





function jsonFixer($json){
    $patterns     = [];
    /** garbage removal */
    $patterns[0]  = "/([\s:,\{}\[\]])\s*'([^:,\{}\[\]]*)'\s*([\s:,\{}\[\]])/"; //Find any character except colons, commas, curly and square brackets surrounded or not by spaces preceded and followed by spaces, colons, commas, curly or square brackets...
    $patterns[1]  = '/([^\s:,\{}\[\]]*)\{([^\s:,\{}\[\]]*)/'; //Find any left curly brackets surrounded or not by one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[2]  =  "/([^\s:,\{}\[\]]+)}/"; //Find any right curly brackets preceded by one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[3]  = "/(}),\s*/"; //JSON.parse() doesn't allow trailing commas
    /** reformatting */
    $patterns[4]  = '/([^\s:,\{}\[\]]+\s*)*[^\s:,\{}\[\]]+/'; //Find or not one or more of any character except spaces, colons, commas, curly and square brackets followed by one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[5]  = '/["\']+([^"\':,\{}\[\]]*)["\']+/'; //Find one or more of quotation marks or/and apostrophes surrounding any character except colons, commas, curly and square brackets...
    $patterns[6]  = '/(")([^\s:,\{}\[\]]+)(")(\s+([^\s:,\{}\[\]]+))/'; //Find or not one or more of any character except spaces, colons, commas, curly and square brackets surrounded by quotation marks followed by one or more spaces and  one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[7]  = "/(')([^\s:,\{}\[\]]+)(')(\s+([^\s:,\{}\[\]]+))/"; //Find or not one or more of any character except spaces, colons, commas, curly and square brackets surrounded by apostrophes followed by one or more spaces and  one or more of any character except spaces, colons, commas, curly and square brackets...
    $patterns[8]  = '/(})(")/'; //Find any right curly brackets followed by quotation marks...
    $patterns[9]  = '/,\s+(})/'; //Find any comma followed by one or more spaces and a right curly bracket...
    $patterns[10] = '/\s+/'; //Find one or more spaces...
    $patterns[11] = '/^\s+/'; //Find one or more spaces at start of string...

    $replacements     = [];
    /** garbage removal */
    $replacements[0]  = '$1 "$2" $3'; //...and put quotation marks surrounded by spaces between them;
    $replacements[1]  = '$1 { $2'; //...and put spaces between them;
    $replacements[2]  = '$1 }'; //...and put a space between them;
    $replacements[3]  = '$1'; //...so, remove trailing commas of any right curly brackets;
    /** reformatting */
    $replacements[4]  = '"$0"'; //...and put quotation marks surrounding them;
    $replacements[5]  = '"$1"'; //...and replace by single quotation marks;
    $replacements[6]  = '\\$1$2\\$3$4'; //...and add back slashes to its quotation marks;
    $replacements[7]  = '\\$1$2\\$3$4'; //...and add back slashes to its apostrophes;
    $replacements[8]  = '$1, $2'; //...and put a comma followed by a space character between them;
    $replacements[9]  = ' $1'; //...and replace by a space followed by a right curly bracket;
    $replacements[10] = ' '; //...and replace by one space;
    $replacements[11] = ''; //...and remove it.

    $result = preg_replace($patterns, $replacements, $json);

    return $result;
  }



function processTweets()
{
    $source = 'tweets.json';
    $target = 'tweets-history.json';

    // https://www.php.net/manual/en/function.cli-set-process-title.php
    // $title = "My Amazing PHP Script";
    // $pid = getmypid(); // you can use this to see your process title in ps
    
    // if (!cli_set_process_title($title)) {
    //     echo "Unable to set process title for PID $pid...\n";
    //     exit(1);
    // } else {
    //     echo "The process title '$title' for PID $pid has been set for your process!\n";
    //     sleep(5);
    // }


    




    // read content from file ito JSON string (serialized):
    $arraySource = file_get_contents($source);
    // echo $arraySource;
    

    echo "\n\n\n";

    // $arraySource = jsonFixer($arraySource);
    $arraySource = jsonFixer($arraySource);



    
    // file_put_contents('tweets-fixed.json', $arraySource);
    

    // JSON string to PHP array
    $arraySource = json_decode($arraySource, true);

    // https://stackoverflow.com/a/34987161
    // will remove outer object and only take array
    // array_unique makes no sense here, TODO: test if this can be removed
    // $arraySource = array_values(array_unique($arraySource, SORT_REGULAR));
    
    // $arraySource = array_values($arraySource, SORT_REGULAR);

    

    //take the only key of the array with only one key (which is an array):
    // $arraySource = $arraySource[0];
    echo $arraySource;
    //we now have an array with objects


    echo "gettype arraySource \n";
    echo gettype($arraySource);
    


    // read content from file:
    // $arrayTweetsHistorySerialized = file_get_contents($target);
    $arrayTarget = file_get_contents($target);
    echo $arrayTarget;


    // JSON string to PHP array
    $arrayTarget = json_decode($arrayTarget);
    echo gettype($arrayTarget);




    foreach ($arraySource as $i => $i_value) {
        if ((int) $i_value['user']['followers_count'] >= 750) {
            echo $i_value['user']['followers_count'];
            echo '\n';

            $qualityTweet[] = $i_value;

            // merge the array item with existing array
            $arrayTarget = array_merge($arrayTarget, $qualityTweet);
        }
    }

    // to JSON string
    // $arraySourceSerialized = json_encode($arraySource);

    // https://stackoverflow.com/a/34987161
    $arrayTarget = array_values(array_unique($arrayTarget, SORT_REGULAR));

    // to JSON string
    $arrayTarget = json_encode($arrayTarget);

    file_put_contents('tweets-history.json', $arrayTarget);
}

// http://stackoverflow.com/a/23028860
// endless loop, runs every ten seconds

processTweets();
// while (true) {
//     processTweets();
//     sleep(10); // in seconds
// }
