<?php
error_reporting(0);
ini_set('display_errors', 0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/x-mpegURL");

$targetUrl = "https://toffeelive.com/video/fifa-2026-6"; 
$streamBaseUrl = "https://prod-cdn01-live.toffeelive.com/live/FIFA-2026-6/0/master_2000.m3u8";

// ১. টোকেন স্ক্র্যাপ করা
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USER_AGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
curl_close($ch);

$token = "";
if (preg_match('/hdntl=([^"\'\s&>]+)/', $response, $matches)) {
    $token = $matches[0];
}

if (!empty($token)) {
    $finalUrl = (strpos($token, 'hdntl=') === false) ? $streamBaseUrl . "?hdntl=" . $token : $streamBaseUrl . "?" . $token;
} else {
    $finalUrl = $streamBaseUrl . "?hdntl=Expires=1783610625~_GO=Generated~URLPrefix=aHR0cHM6Ly9wcm9kLWNkbjAxLWxpdmUudG9mZmVlbGl2ZS5jb20~Signature=AWIR_5GbjS4OAqpULeDt-HzevuQFSs40ddnTNgR2edP-fZqWK7biyof2cmm8jnUkvASApFUpY3hZSrOKAuPqbUbgdlgO";
}

// ২. রিডাইরেক্ট না করে সার্ভার টু সার্ভার স্ট্রিম ডাটা ফেচ করা (টফি ব্লক করতে পারবে না)
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
                    "Referer: https://toffeelive.com/\r\n" .
                    "Origin: https://toffeelive.com\r\n"
    ]
];
$context = stream_context_create($opts);
$streamData = file_get_contents($finalUrl, false, $context);

// প্লেয়ারে আউটপুট পাঠানো
echo $streamData;
exit;
?>
