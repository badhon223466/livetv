<?php
/**
 * Ultimate Reverse Proxy & Stream Resolver
 * Bypass Toffee/Fifalive Referer & IP Restrictions
 * Author: Autovex Solution
 */

error_reporting(0);
ini_set('display_errors', 0);

// ১. CORS হেডার সেট করা যাতে ব্রাউজার বা প্লেয়ার কোনোভাবেই ব্লক না করে
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ২. ইনপুট ইউআরএল বা কুয়েরি চেক করা (চেইন রিকোয়েস্টের জন্য)
$tsUrl = $_GET['ts_url'];
if (!empty($tsUrl)) {
    // ভিডিওর ভেতরের ছোট ছোট পার্ট (.ts chunks) লোড করার জন্য হেডার পাঠানো
    header("Content-Type: video/MP2T");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, base64_decode($tsUrl));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USER_AGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Referer: https://toffeelive.com/",
        "Origin: https://toffeelive.com"
    ]);
    echo curl_exec($ch);
    curl_close($ch);
    exit;
}

// ৩. মেইন স্ট্রিমিং কন্টেন্ট টাইপ সেট করা
header("Content-Type: application/x-mpegURL");

// ৪. fifalive.click থেকে মেইন লাইভ সোর্স স্ক্র্যাপ করা
$targetSite = "https://fifalive.click/";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetSite);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USER_AGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36");

$htmlResponse = curl_exec($ch);
curl_close($ch);

$liveStreamUrl = "";
if (preg_match('/<source\s+src=["\'](https?:\/\/[^"\']+\.m3u8[^"\']*)["\']/', $htmlResponse, $matches)) {
    $liveStreamUrl = $matches[1];
} elseif (preg_match('/https?:\/\/[^"\'\s]+\.m3u8[^"\'\s]*/', $htmlResponse, $matches)) {
    $liveStreamUrl = $matches[0];
}

// যদি কোনো কারণে স্ক্র্যাপার লিংক না পায়, তবে ব্যাকআপ লিংক সেট হবে
if (empty($liveStreamUrl)) {
    $liveStreamUrl = "https://prod-cdn01-live.toffeelive.com/live/FIFA-2026-6/0/master_2000.m3u8?hdntl=Expires=1783610625~_GO=Generated~URLPrefix=aHR0cHM6Ly9wcm9kLWNkbjAxLWxpdmUudG9mZmVlbGl2ZS5jb20~Signature=AWIR_5GbjS4OAqpULeDt-HzevuQFSs40ddnTNgR2edP-fZqWK7biyof2cmm8jnUkvASApFUpY3hZSrOKAuPqbUbgdlgO";
}

// ৫. টফির সার্ভার থেকে মেইন .m3u8 ফাইলটি ব্যাকএন্ডে ডাউনলোড করা
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $liveStreamUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USER_AGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Referer: https://toffeelive.com/",
    "Origin: https://toffeelive.com"
]);

$m3u8Content = curl_exec($ch);
curl_close($ch);

// ৬. ম্যাজিক পার্ট: .m3u8 ফাইলের ভেতরের সব লিংকে আমাদের প্রক্সি লিংক দিয়ে রিপ্লেস করা
// এর ফলে প্লেয়ার যখন ভিডিওর পরবর্তী অংশগুলো লোড করতে যাবে, সেগুলোও টফি ব্লক করতে পারবে না
$baseUrl = substr($liveStreamUrl, 0, strrpos($liveStreamUrl, '/') + 1);
$currentProxyScript = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";

$lines = explode("\n", $m3u8Content);
foreach ($lines as &$line) {
    $line = trim($line);
    if (!empty($line) && $line[0] !== '#') {
        if (strpos($line, 'http') === false) {
            $absoluteUrl = $baseUrl . $line;
        } else {
            $absoluteUrl = $line;
        }
        // লিংকটিকে বেস৬৪ (base64) করে আমাদের নিজস্ব প্রক্সি স্ক্রিপ্টের কুয়েরি প্যারামিটার বানিয়ে দেওয়া হলো
        $line = $currentProxyScript . "?ts_url=" . base64_encode($absoluteUrl);
    }
}

// ফাইনাল আউটপুট প্লেয়ারের কাছে পাঠানো
echo implode("\n", $lines);
exit;
?>
