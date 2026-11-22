<?php
require_once __DIR__ . "/ai_config.php";

function askAI(PDO $pdo, int $chatId, string $userMessage): string
{
    global $OPENAI_API_KEY, $OPENAI_MODEL, $OPENAI_BASE_URL;

    if (!$OPENAI_API_KEY) {
        throw new RuntimeException("کلید AvalAI روی سرور تنظیم نشده است.");
    }

    $stmt = $pdo->prepare(
        "SELECT role, content FROM messages WHERE chat_id = ? ORDER BY id DESC LIMIT 30"
    );
    $stmt->execute([$chatId]);
    // فقط ۳۰ پیام آخر به AI فرستاده می‌شود
    $history = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

    $messages = [[
        "role" => "system",
        "content" => "You are a helpful and accurate AI assistant. Answer in the same language as the user. If the user speaks Persian, answer in Persian."
    ]];

    foreach ($history as $item) {
        if (in_array($item["role"], ["user", "assistant"], true)) {
            $messages[] = ["role" => $item["role"], "content" => $item["content"]];
        }
    }

    $payload = json_encode([
        "model" => $OPENAI_MODEL,
        "messages" => $messages
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    $ch = curl_init($OPENAI_BASE_URL . "/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . trim($OPENAI_API_KEY)
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 20
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException("خطا در اتصال به سرویس هوش مصنوعی: " . $error);
    }

    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        $message = is_array($result)
            ? ($result["error"]["message"] ?? $result["message"] ?? $result["detail"] ?? "")
            : "";
        throw new RuntimeException("AvalAI HTTP " . $httpCode . ": " . ($message ?: "پاسخ نامعتبر"));
    }

    $answer = $result["choices"][0]["message"]["content"] ?? "";
    if (is_array($answer)) {
        $answer = json_encode($answer, JSON_UNESCAPED_UNICODE);
    }
    if (!is_string($answer) || trim($answer) === "") {
        throw new RuntimeException("پاسخ خالی از AvalAI دریافت شد.");
    }
    return trim($answer);
}
