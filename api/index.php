<?php
/**
 * Ultimate Live TV Stream Scraper & Proxy Engine
 * Anti-Block & Direct Stream Resolver
 */

error_reporting(0);
ini_set('display_errors', 0);

// CORS এবং কন্টেন্ট কনফিগারেশন হেডার
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Content-Type: application/x-mpegURL");

$targetSite = "https://fifalive.click/";

// ১. সাইটের লাইভ সোর্স কোড রিয়েল-টাইম নিয়ে আসা
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetSite);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USER_AGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Referer: https://fifalive.click/",
    "Origin: https://fifalive.click"
]);

$htmlResponse = curl_exec($ch);
curl_close($ch);

$liveStreamUrl = "";

// ২. সোর্স কোডের ভেতরের m3u8 লিংকটি পুঙ্খানুপুঙ্খভাবে খুঁজে বের করা
if (preg_match('/<source\s+src=["\'](https?:\/\/[^"\']+\.m3u8[^"\']*)["\']/', $htmlResponse, $matches)) {
    $liveStreamUrl = $matches[1];
} elseif (preg_match('/https?:\/\/[^"\'\s]+\.m3u8[^"\'\s]*/', $htmlResponse, $matches)) {
    $liveStreamUrl = $matches[0];
}

// ৩. এরর ফিক্সিং মেকানিজম (Manifest Load Error দূর করার জন্য)
if (!empty($liveStreamUrl)) {
    // সরাসরি ফাইলটি ব্যাকএন্ডে ডাউনলোড না করে, প্লেয়ারকে মূল লিংকে রিডাইরেক্ট করে পাঠানো
    // এতে করে Vercel-এর আইপি ব্লকিং বা নেটওয়ার্ক এররের সমস্যাটি আর থাকবে না
    header("Location: " . $liveStreamUrl);
    exit;
} else {
    // ব্যাকআপ সোর্স (যদি মেইন সাইট থেকে কোনো লিংকই না পাওয়া যায়)
    $backupUrl = "https://prod-cdn01-live.toffeelive.com/live/FIFA-2026-6/0/master_2000.m3u8?hdntl=Expires=1783610625~_GO=Generated~URLPrefix=aHR0cHM6Ly9wcm9kLWNkbjAxLWxpdmUudG9mZmVlbGl2ZS5jb20~Signature=AWIR_5GbjS4OAqpULeDt-HzevuQFSs40ddnTNgR2edP-fZqWK7biyof2cmm8jnUkvASApFUpY3hZSrOKAuPqbUbgdlgO";
    header("Location: " . $backupUrl);
    exit;
}
?>
