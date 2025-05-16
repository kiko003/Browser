<?php
$api_key = "sk_live_sJofMsFmPkXhKdKg8mKnyXbEBZ5gbVo8KfYwSspVh4U";
$url = "https://engine.hyperbeam.com/v0/vm";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $api_key"
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>