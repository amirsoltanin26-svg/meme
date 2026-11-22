<?php

session_start();
if (empty($_SESSION["csrf"])) { $_SESSION["csrf"] = bin2hex(random_bytes(16)); }

require_once "config.php";
require_once "ai.php";

/* =========================================
   بررسی Login
========================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit;
}

$userId = (int) $_SESSION["user_id"];

$userName = $_SESSION["user_name"] ?? "User";

$userEmail = $_SESSION["user_email"] ?? "";


/* =========================================
   New Chat
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION["csrf"], (string)($_POST["csrf"] ?? ""))) {
        http_response_code(403);
        exit("درخواست نامعتبر است. صفحه را رفرش کنید.");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "new_chat") {

        $stmt = $pdo->prepare(
            "INSERT INTO chats (user_id, title)
             VALUES (?, ?)"
        );

        $stmt->execute([
            $userId,
            "New Chat"
        ]);

        $newChatId = (int) $pdo->lastInsertId();

        header("Location: chat.php?chat_id=" . $newChatId);
        exit;
    }
}


/* =========================================
   Chat فعلی
========================================= */

$chatId = isset($_GET["chat_id"])
    ? (int) $_GET["chat_id"]
    : 0;


/* =========================================
   اگر Chat انتخاب نشده
========================================= */

if ($chatId <= 0) {

    $stmt = $pdo->prepare(
        "SELECT id
         FROM chats
         WHERE user_id = ?
         ORDER BY updated_at DESC
         LIMIT 1"
    );

    $stmt->execute([$userId]);

    $lastChat = $stmt->fetch();

    if ($lastChat) {

        $chatId = (int) $lastChat["id"];

    } else {

        $stmt = $pdo->prepare(
            "INSERT INTO chats (user_id, title)
             VALUES (?, ?)"
        );

        $stmt->execute([
            $userId,
            "New Chat"
        ]);

        $chatId = (int) $pdo->lastInsertId();
    }
}


/* =========================================
   بررسی مالکیت Chat
========================================= */

$stmt = $pdo->prepare(
    "SELECT id, title
     FROM chats
     WHERE id = ?
     AND user_id = ?
     LIMIT 1"
);

$stmt->execute([
    $chatId,
    $userId
]);

$currentChat = $stmt->fetch();


if (!$currentChat) {

    header("Location: chat.php");
    exit;
}


/* =========================================
   ارسال پیام
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "send_message") {

    $message = trim($_POST["message"] ?? "");


    // پیام خالی ذخیره نشود
    if ($message === "") {
        header("Location: chat.php?chat_id=" . $chatId);
        exit;
    }

        /*
        |--------------------------------------------------------------------------
        | ذخیره پیام کاربر
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            "INSERT INTO messages
            (chat_id, role, content)
            VALUES (?, ?, ?)"
        );

        $stmt->execute([
            $chatId,
            "user",
            $message
        ]);


        /*
        |--------------------------------------------------------------------------
        | تغییر عنوان Chat
        |--------------------------------------------------------------------------
        */

        if ($currentChat["title"] === "New Chat") {

            $title = function_exists("mb_substr")
                ? mb_substr($message, 0, 40)
                : substr($message, 0, 40);


            $stmt = $pdo->prepare(
                "UPDATE chats
                 SET title = ?
                 WHERE id = ?
                 AND user_id = ?"
            );


            $stmt->execute([
                $title,
                $chatId,
                $userId
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | گرفتن پاسخ AI
        |--------------------------------------------------------------------------
        */

        try {

            $aiAnswer = askAI(
                $pdo,
                $chatId,
                $message
            );


            /*
            |--------------------------------------------------------------------------
            | ذخیره پاسخ AI
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                "INSERT INTO messages
                (chat_id, role, content)
                VALUES (?, ?, ?)"
            );


            $stmt->execute([
                $chatId,
                "assistant",
                $aiAnswer
            ]);


        } catch (Exception $e) {


            /*
            |--------------------------------------------------------------------------
            | نمایش خطای قابل فهم داخل Chat
            |--------------------------------------------------------------------------
            */

            $errorMessage =
                "متأسفانه در ارتباط با هوش مصنوعی مشکلی پیش آمد. لطفاً دوباره تلاش کنید.";
            error_log("AI error: " . $e->getMessage());


            $stmt = $pdo->prepare(
                "INSERT INTO messages
                (chat_id, role, content)
                VALUES (?, ?, ?)"
            );


            $stmt->execute([
                $chatId,
                "assistant",
                $errorMessage
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Update زمان Chat
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare(
            "UPDATE chats
             SET updated_at = CURRENT_TIMESTAMP
             WHERE id = ?
             AND user_id = ?"
        );


        $stmt->execute([
            $chatId,
            $userId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | برگشت به Chat
    |--------------------------------------------------------------------------
    */

    header(
        "Location: chat.php?chat_id=" . $chatId
    );

    exit;
}
           



/* =========================================
   پیام‌ها
========================================= */

$stmt = $pdo->prepare(
    "SELECT id, role, content, created_at
     FROM messages
     WHERE chat_id = ?
     ORDER BY created_at ASC, id ASC"
);

$stmt->execute([$chatId]);

$messages = $stmt->fetchAll();
/* =========================================
   Chat History
========================================= */

$stmt = $pdo->prepare(
    "SELECT id, title, updated_at
     FROM chats
     WHERE user_id = ?
     ORDER BY updated_at DESC"
);

$stmt->execute([$userId]);

$chats = $stmt->fetchAll();

?>

<!DOCTYPE html>

<html lang="fa" dir="rtl" data-theme="dark">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>AI ChatBot</title>


    <!-- Tailwind -->

    <script src="https://cdn.tailwindcss.com"></script>


    <!-- DaisyUI -->

    <link
        href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="stayle.css"
    >

</head>


<body class="bg-slate-950 text-white">


<div class="h-screen flex overflow-hidden">


    <!-- SIDEBAR -->

    <aside
        class="
            w-80
            bg-slate-900
            border-l
            border-slate-800
            flex
            flex-col
        "
    >

        <!-- Logo -->

        <div
            class="
                p-5
                border-b
                border-slate-800
                flex
                items-center
                gap-3
            "
        >

            <div
                class="
                    w-12
                    h-12
                    rounded-full
                    bg-blue-500/10
                    border
                    border-blue-500/30
                    flex
                    items-center
                    justify-center
                "
            >

                <img
                    src="logo.svg"
                    class="w-8 h-8"
                    alt="AI"
                >

            </div>


            <div>

                <h1 class="font-bold text-lg">
                    AI ChatBot
                </h1>

                <p class="text-xs text-slate-500">
                    Smart Assistant
                </p>

            </div>

        </div>


        <!-- Telegram Link -->
        <div class="px-4 pb-2">
            <a href="telegram_link.php" class="btn btn-outline btn-info w-full gap-2">
                <i class="fa-brands fa-telegram"></i>
                اتصال تلگرام
            </a>
        </div>

        <!-- New Chat -->

        <div class="p-4">

            <form method="POST" action="chat.php">

                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION["csrf"]) ?>">
<input
                    type="hidden"
                    name="action"
                    value="new_chat"
                >

                <button
                    type="submit"
                    class="btn btn-primary w-full gap-2"
                >

                    <i class="fa-solid fa-plus"></i>

                    New Chat

                </button>

            </form>

        </div>


        <!-- History -->

        <div class="px-4">

            <p
                class="
                    text-xs
                    text-slate-500
                    font-bold
                    mb-3
                "
            >
                CHAT HISTORY
            </p>

        </div>


        <div class="flex-1 overflow-y-auto px-3">

            <?php foreach ($chats as $chat): ?>

                <a
                    href="chat.php?chat_id=<?= (int)$chat["id"] ?>"
                    class="
                        block
                        rounded-xl
                        px-4
                        py-3
                        mb-2
                        transition
                        <?= ((int)$chat["id"] === $chatId)
                            ? "bg-blue-600/20 border border-blue-500/30"
                            : "hover:bg-slate-800"
                        ?>
                    "
                >

                    <div class="flex items-center gap-3">

                        <i class="fa-regular fa-message text-slate-500"></i>

                        <span class="truncate text-sm">

                            <?= htmlspecialchars($chat["title"]) ?>

                        </span>
                        </div>

                </a>

            <?php endforeach; ?>

        </div>


        <!-- Account -->

        <div
            class="
                p-4
                border-t
                border-slate-800
            "
        >

            <div class="flex items-center gap-3 mb-3">

                <div
                    class="
                        w-11
                        h-11
                        rounded-full
                        bg-blue-600
                        flex
                        items-center
                        justify-center
                        font-bold
                    "
                >

                    <?php

                    $firstLetter = function_exists("mb_substr")
                        ? mb_substr($userName, 0, 1)
                        : substr($userName, 0, 1);

                    echo htmlspecialchars(
                        strtoupper($firstLetter)
                    );

                    ?>

                </div>


                <div class="min-w-0">

                    <p class="font-semibold truncate">

                        <?= htmlspecialchars($userName) ?>

                    </p>

                    <p
                        class="
                            text-xs
                            text-slate-500
                            truncate
                        "
                    >

                        <?= htmlspecialchars($userEmail) ?>

                    </p>

                </div>

            </div>


            <a
                href="logout.php"
                class="
                    btn
                    btn-ghost
                    btn-sm
                    w-full
                    text-red-400
                "
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                خروج از حساب

            </a>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="flex-1 flex flex-col min-w-0">


        <!-- HEADER -->

        <header
            class="
                h-20
                border-b
                border-slate-800
                bg-slate-900/70
                backdrop-blur-xl
                flex
                items-center
                justify-between
                px-6
            "
        >

            <div>

                <h2 class="font-bold text-lg">

                    <?= htmlspecialchars($currentChat["title"]) ?>

                </h2>

                <p class="text-xs text-slate-500">

                    AI Assistant

                </p>

            </div>


            <!-- Account -->

            <div class="dropdown dropdown-end">

                <div
                    tabindex="0"
                    role="button"
                    class="btn btn-ghost btn-circle"
                >

                    <div
                        class="
                            w-10
                            h-10
                            rounded-full
                            bg-blue-600
                            flex
                            items-center
                            justify-center
                            font-bold
                        "
                    >

                        <?php

                        echo htmlspecialchars(
                            strtoupper($firstLetter)
                        );

                        ?>

                    </div>

                </div>


                <ul
                    tabindex="0"
                    class="
                        dropdown-content
                        menu
                        bg-slate-900
                        rounded-box
                        z-[1]
                        w-64
                        p-2
                        shadow-2xl
                        border
                        border-slate-800
                    "
                >

                    <li class="menu-title">
                        <?= htmlspecialchars($userName) ?>

                    </li>

                    <li>

                        <a>

                            <?= htmlspecialchars($userEmail) ?>

                        </a>

                    </li>

                    <li>

                        <a href="logout.php">

                            <i class="fa-solid fa-right-from-bracket"></i>

                            خروج

                        </a>

                    </li>

                </ul>

            </div>

        </header>


        <!-- MESSAGES -->

        <section
            id="messages"
            class="
                flex-1
                overflow-y-auto
                px-5
                py-8
            "
        >

            <div
                class="
                    max-w-4xl
                    mx-auto
                    space-y-6
                "
            >

                <?php if (empty($messages)): ?>

                    <div
                        class="
                            min-h-[400px]
                            flex
                            flex-col
                            items-center
                            justify-center
                            text-center
                        "
                    >

                        <div
                            class="
                                w-24
                                h-24
                                rounded-full
                                bg-blue-500/10
                                border
                                border-blue-500/20
                                flex
                                items-center
                                justify-center
                                mb-6
                            "
                        >

                            <img
                                src="logo.svg"
                                class="w-14 h-14"
                                alt="AI"
                            >

                        </div>


                        <h2 class="text-3xl font-bold mb-3">

                            آماده‌ام کمک کنم

                        </h2>


                        <p class="text-slate-500 max-w-md">

                            سوالت را بنویس و با دستیار هوشمند
                            شروع به گفتگو کن.

                        </p>

                    </div>


                <?php else: ?>


                    <?php foreach ($messages as $message): ?>


                        <?php if ($message["role"] === "user"): ?>

                            <div class="flex justify-start">

                                <div
                                    class="
                                        max-w-[75%]
                                        bg-blue-600
                                        rounded-2xl
                                        rounded-br-md
                                        px-5
                                        py-3
                                        shadow-lg
                                    "
                                >

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $message["content"]
                                        )
                                    ) ?>

                                </div>

                            </div>


                        <?php else: ?>

                            <div class="flex justify-end">

                                <div
                                    class="
                                        max-w-[75%]
                                        bg-slate-800
                                        border
                                        border-slate-700
                                        rounded-2xl
                                        rounded-bl-md
                                        px-5
                                        py-4
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            items-center
                                            gap-2
                                            mb-2
                                            text-blue-400
                                            text-sm
                                            font-bold
                                        "
                                    >

                                        <i class="fa-solid fa-robot"></i>

                                        AI

                                    </div>


                                    <?= nl2br(
                                        htmlspecialchars(
                                            $message["content"]
                                        )
                                    ) ?>

                                </div>

                            </div>

                        <?php endif; ?>


                    <?php endforeach; ?>


                <?php endif; ?>

            </div>

        </section>


        <!-- INPUT -->

        <footer
            class="
                border-t
                border-slate-800
                bg-slate-900/70
                backdrop-blur-xl
                p-5
            "
        >

            <form
                method="POST"
                action="chat.php?chat_id=<?= $chatId ?>"
                class="max-w-4xl mx-auto"
            >

                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION["csrf"]) ?>">
<input
                    type="hidden"
                    name="action"
                    value="send_message"
                >


                <div
                    class="
                        flex
                        items-end
                        gap-3
                        bg-slate-950
                        border
                        border-slate-700
                        rounded-2xl
                        p-2
                        focus-within:border-blue-500
                    "
                >

                    <textarea
                        name="message"
                        rows="1"
                        required
                        placeholder="پیامت را بنویس..."
                        class="
                            textarea
                            textarea-ghost
                            resize-none
                            flex-1
                            text-white
                            focus:outline-none
                            min-h-[48px]
                        "
                    ></textarea>


                    <button
                        type="submit"
                        class="
                            btn
                            btn-primary
                            btn-circle
                            flex-shrink-0
                        "
                    >

                        <i class="fa-solid fa-paper-plane"></i>

                    </button>

                </div>


                <p
                    class="
                        text-center
                        text-xs
                        text-slate-600
                        mt-3
                    "
                >

                    AI ممکن است گاهی اشتباه کند.

                </p>

            </form>

        </footer>

    </main>

</div>




<script>
    const messagesBox = document.getElementById("messages");

    if (messagesBox) {
        messagesBox.scrollTop = messagesBox.scrollHeight;
    }
</script>

