<?php
session_start();
require_once __DIR__ . "/config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: indext.html");
    exit;
}
$userId = (int)$_SESSION["user_id"];
$code = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = bin2hex(random_bytes(5)); // 10-character one-time code
    $hash = hash('sha256', $code);
    $pdo->prepare("DELETE FROM telegram_link_codes WHERE user_id = ?")->execute([$userId]);
    $stmt = $pdo->prepare(
        "INSERT INTO telegram_link_codes (user_id, code_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))"
    );
    $stmt->execute([$userId, $hash]);
}
$stmt = $pdo->prepare("SELECT telegram_user_id FROM telegram_links WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$linked = $stmt->fetch();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>اتصال تلگرام</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet">
<link rel="stylesheet" href="stayle.css">
</head>
<body class="min-h-screen flex items-center justify-center p-5 text-white">
<main class="w-full max-w-xl rounded-2xl border border-slate-700 bg-slate-900 p-7 shadow-2xl">
<h1 class="text-2xl font-bold mb-3">اتصال حساب تلگرام</h1>
<p class="text-slate-300 mb-5">با اتصال تلگرام، پیام‌های ربات و سایت در حساب کاربری و دیتابیس مشترک ذخیره می‌شوند.</p>
<?php if ($linked): ?>
<div class="alert alert-success mb-4">یک حساب تلگرام به حساب سایت متصل است. ساخت کد جدید اتصال قبلی این حساب را جایگزین می‌کند.</div>
<?php endif; ?>
<?php if ($code): ?>
<div class="rounded-xl bg-slate-950 border border-blue-500 p-4 mb-4">
<p class="text-sm text-slate-400 mb-2">این کد فقط ۱۰ دقیقه اعتبار دارد:</p>
<div class="text-3xl font-bold tracking-widest text-center" dir="ltr"><?= htmlspecialchars($code) ?></div>
<p class="text-sm text-slate-400 mt-3">در ربات تلگرام ارسال کن:</p>
<code dir="ltr" class="block text-center text-blue-300 mt-1">/start <?= htmlspecialchars($code) ?></code>
</div>
<?php endif; ?>
<form method="post">
<button class="btn btn-primary w-full" type="submit">ساخت کد اتصال جدید</button>
</form>
<a class="btn btn-ghost w-full mt-3" href="chat.php">بازگشت به چت</a>
</main>
</body>
</html>
