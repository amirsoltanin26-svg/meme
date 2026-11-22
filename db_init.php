<?php
/** ساخت خودکار جدول‌ها (اگر وجود نداشته باشند). نیازی به اجرای دستی SQL نیست. */
function ensureTables(PDO $pdo): void
{
    $count = (int)$pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE()
         AND table_name IN ('users','chats','messages','telegram_links','telegram_link_codes','telegram_updates')"
    )->fetchColumn();
    if ($count >= 6) {
        return;
    }
    $opts = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_users_email (email)
    ) $opts");
    $pdo->exec("CREATE TABLE IF NOT EXISTS chats (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL DEFAULT 'New Chat',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_chats_user_updated (user_id, updated_at)
    ) $opts");
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        chat_id BIGINT UNSIGNED NOT NULL,
        role VARCHAR(20) NOT NULL,
        content MEDIUMTEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_messages_chat (chat_id, id)
    ) $opts");
    $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_links (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        telegram_user_id BIGINT NOT NULL,
        telegram_chat_id BIGINT NOT NULL,
        user_id BIGINT NOT NULL,
        active_chat_id BIGINT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_telegram_user (telegram_user_id),
        KEY idx_telegram_site_user (user_id)
    ) $opts");
    $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_link_codes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        code_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_telegram_code_hash (code_hash),
        KEY idx_telegram_code_user (user_id),
        KEY idx_telegram_code_expiry (expires_at)
    ) $opts");
    $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_updates (
        update_id BIGINT NOT NULL PRIMARY KEY,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) $opts");
}
