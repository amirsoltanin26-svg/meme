<?php
/**
 * تنظیمات امن اتصال به AvalAI
 * مقادیر را در Environment Variables هاست تعریف کنید.
 */
$OPENAI_API_KEY = getenv('AVALAI_API_KEY') ?: '';
$OPENAI_MODEL = getenv('AVALAI_MODEL') ?: 'claude-opus-5';
$OPENAI_BASE_URL = rtrim(getenv('AVALAI_BASE_URL') ?: 'https://api.avalai.ir/v1', '/');

if ($OPENAI_API_KEY === '') {
    // کلید را داخل فایل عمومی قرار ندهید.
}
