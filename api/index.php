<?php
/**
 * Automated Toffee Live Stream Proxy Server
 * Running on Vercel Serverless
 */

error_reporting(0);
ini_set('display_errors', 0);

// CORS Header যাতে ব্লগারে প্লেয়ারটি ব্লক না খায়
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/x-mpegURL");

$targetUrl = "https://toffeelive.com/video/fifa-2026-6"; 
$streamBaseUrl = "https://prod-cdn01-live.toffeelive.com/live/FIFA-2026-6/0/master_2000.m3u8";

// cURL দিয়ে টোকেন স্ক্র্যাপ করা
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USER_AGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
curl_close($ch);

$token = "";
if (preg_match('/hdntl=([^"\'\s&>]+)/', $response, $matches)) {
    $token = $matches[0];
}

if (empty($token) && preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $cookieMatches)) {
    foreach ($cookieMatches[1] as $cookie) {
        if (strpos($cookie, 'hdntl=') !== false) {
            $token = $cookie;
            break;
        }
    }
}

if (!empty($token)) {
    if (strpos($token, 'hdntl=') === false) {
        $finalUrl = $streamBaseUrl . "?hdntl=" . $token;
    } else {
        $finalUrl = $streamBaseUrl . "?" . $token;
    }
} else {
    // ব্যাকআপ টোকেন
    $backupToken = "hdntl=Expires=1783610625~_GO=Generated~URLPrefix=aHR0cHM6Ly9wcm9kLWNkbjAxLWxpdmUudG9mZmVlbGl2ZS5jb20~Signature=AWIR_5GbjS4OAqpULeDt-HzevuQFSs40ddnTNgR2edP-fZqWK7biyof2cmm8jnUkvASApFUpY3hZSrOKAuPqbUbgdlgO";
    $finalUrl = $streamBaseUrl . "?" . $backupToken;
}

// প্লেয়ারকে ফাইনাল লিংকে রিডাইরেক্ট করা
header("Location: " . $finalUrl);
exit;
?>