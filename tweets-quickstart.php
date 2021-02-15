<?php
    header("Access-Control-Allow-Origin: *");
    header("content-type: application/json");
    // echo file_get_contents("https://blockchainbird.com/t/twitter-phirehose/tweets-quickstart.json");
    echo file_get_contents("./tweets-quickstart.json");
?>