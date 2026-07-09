<?php
/**
 * Automated Live TV Stream Proxy Server (Scraping from fifalive.click)
 * Author: Autovex Solution
 */

error_reporting(0);
ini_set('display_errors', 0);

// CORS এবং কনটেন্ট টাইপ হেডার যাতে যেকোনো প্লেয়ারে চলে
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/x-mpegURL");

// ১. টার্গেট সাইটের URL (যেখান থেকে লিংক স্ক্র্যাপ হবে)
$targetSite = "https://fifalive.click/";

// ২. cURL এর মাধ্যমে সাইটের সোর্স কোড নিয়ে আসা
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetSite);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USER_AGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Referer: https://fifalive.click/",
    "Origin: https://fifalive.click"
]);

$htmlResponse = curl_exec($ch);
curl_close($ch);

// ৩. রেগুলার এক্সপ্রেশন (Regex) দিয়ে সোর্স কোড থেকে .m3u8 লিংকটি খুঁজে বের করা
$liveStreamUrl = "";

// প্যাটার্ন ১: যদি কোডে সরাসরি কোনো m3u8 লিংক থাকে (টোকেনসহ)
if (preg_match('/https?:\/\/[^"\']+\.m3u8[^"\']*/', $htmlResponse, $matches)) {
    $liveStreamUrl = $matches[0];
} 
// প্যাটার্ন ২: যদি আইফ্রেম (iframe) বা অন্য কোনো প্লেয়ার সোর্সের ভেতর থাকে
else if (preg_match('/src=["\'](https?:\/\/.*?\/live\/.*?)["\']/', $htmlResponse, $matches)) {
    $liveStreamUrl = $matches[1];
}

// ৪. যদি স্ক্র্যাপার সফলভাবে লিংক খুঁজে পায়, তবে রিডাইরেক্ট বা প্রক্সি করবে
if (!empty($liveStreamUrl)) {
    
    // রিডাইরেক্ট করার আগে যদি কোনো নির্দিষ্ট হেডার পাস করতে হয় (যেমন টফির ক্ষেত্রে লেগেছিল)
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
                        "Referer: https://fifalive.click/\r\n" .
                        "Origin: https://fifalive.click\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $streamData = file_get_contents($liveStreamUrl, false, $context);

    // যদি সরাসরি ফাইল কনটেন্ট পাওয়া যায় তবে তা প্রিন্ট করবে, নাহলে ডাইরেক্ট রিডাইরেক্ট করবে
    if ($streamData) {
        echo $streamData;
    } else {
        header("Location: " . $liveStreamUrl);
    }
    exit;

} else {
    // ব্যাকআপ সোর্স (যদি সাইট থেকে স্ক্র্যাপ করতে না পারে, তবে আগের দেওয়া টফি লিংকটি ব্যাকআপ হিসেবে চলবে)
    $backupUrl = "https://prod-cdn01-live.toffeelive.com/live/FIFA-2026-6/0/master_2000.m3u8?hdntl=Expires=1783610625~_GO=Generated~URLPrefix=aHR0cHM6Ly9wcm9kLWNkbjAxLWxpdmUudG9mZmVlbGl2ZS5jb20~Signature=AWIR_5GbjS4OAqpULeDt-HzevuQFSs40ddnTNgR2edP-fZqWK7biyof2cmm8jnUkvASApFUpY3hZSrOKAuPqbUbgdlgO";
    header("Location: " . $backupUrl);
    exit;
}
?>
