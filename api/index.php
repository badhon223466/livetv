<?php
/**
 * Advanced Live TV Stream Scraper & Proxy
 * Target: fifalive.click (DOM Source Parser)
 * Author: Autovex Solution
 */

error_reporting(0);
ini_set('display_errors', 0);

// CORS এবং কনটেন্ট টাইপ হেডার সেট করা
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/x-mpegURL");

$targetSite = "https://fifalive.click/";

// ১. cURL দিয়ে সাইটের রিয়েল-টাইম সোর্স কোড নিয়ে আসা
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetSite);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USER_AGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Referer: https://fifalive.click/",
    "Origin: https://fifalive.click"
]);

$htmlResponse = curl_exec($ch);
curl_close($ch);

$liveStreamUrl = "";

// ২. আপনার দেওয়া নির্দিষ্ট HTML Tag প্যাটার্ন অনুযায়ী স্ক্র্যাপ করা (<source src="...")
if (preg_match('/<source\s+src=["\'](https?:\/\/[^"\']+\.m3u8[^"\']*)["\']/', $htmlResponse, $matches)) {
    $liveStreamUrl = $matches[1];
}
// ব্যাকআপ প্যাটার্ন: যদি ট্যাগের বাইরে সাধারণ টেক্সট হিসেবে থাকে
elseif (preg_match('/https?:\/\/[^"\']+\.m3u8[^"\']*/', $htmlResponse, $matches)) {
    $liveStreamUrl = $matches[0];
}

// ৩. লাইভ লিংক পাওয়া গেলে প্রক্সি করা, না পাওয়া গেলে ব্যাকআপে চালানো
if (!empty($liveStreamUrl)) {
    
    // টফির ফেসবুক রিডাইরেক্ট সিকিউরিটি ভাঙার জন্য সার্ভার-টু-সার্ভার রিকোয়েস্ট হেডার
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
                        "Referer: https://toffeelive.com/\r\n" .
                        "Origin: https://toffeelive.com\r\n"
    ]
    ];
    
    $context = stream_context_create($opts);
    $streamData = file_get_contents($liveStreamUrl, false, $context);

    if ($streamData !== false) {
        echo $streamData;
    } else {
        // যদি ডাটা রিড করতে সমস্যা হয় তবে সরাসরি রিডাইরেক্ট
        header("Location: " . $liveStreamUrl);
    }
    exit;

} else {
    // চূড়ান্ত ব্যাকআপ (যদি কোনো কারণে সাইট ডাউন থাকে বা লিংক না পায়)
    $backupUrl = "https://prod-cdn01-live.toffeelive.com/live/FIFA-2026-6/0/master_2000.m3u8?hdntl=Expires=1783610625~_GO=Generated~URLPrefix=aHR0cHM6Ly9wcm9kLWNkbjAxLWxpdmUudG9mZmVlbGl2ZS5jb20~Signature=AWIR_5GbjS4OAqpULeDt-HzevuQFSs40ddnTNgR2edP-fZqWK7biyof2cmm8jnUkvASApFUpY3hZSrOKAuPqbUbgdlgO";
    header("Location: " . $backupUrl);
    exit;
}
?>
