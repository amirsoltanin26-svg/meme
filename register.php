<?php

session_start();

require_once "config.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    // بررسی خالی نبودن اطلاعات
    if ($name === "" || $email === "" || $password === "") {

        $message = "لطفاً تمام اطلاعات را وارد کنید.";
        $messageType = "error";

    }

    // بررسی ایمیل
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "ایمیل وارد شده صحیح نیست.";
        $messageType = "error";

    }

    // بررسی رمز
    elseif (strlen($password) < 6) {

        $message = "رمز عبور باید حداقل ۶ کاراکتر باشد.";
        $messageType = "error";

    }

    // بررسی برابر بودن رمزها
    elseif ($password !== $confirmPassword) {

        $message = "رمز عبور و تکرار آن یکسان نیست.";
        $messageType = "error";

    }

    else {

        // بررسی وجود ایمیل
        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "این ایمیل قبلاً ثبت‌نام کرده است.";
            $messageType = "error";

        } else {

            // رمزنگاری پسورد
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // ثبت کاربر
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->execute([
                $name,
                $email,
                $hashedPassword
            ]);

            $message = "ثبت‌نام با موفقیت انجام شد. اکنون وارد شوید.";
            $messageType = "success";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ثبت نام | AI ChatBot</title>

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

    <link rel="stylesheet" href="stayle.css">

</head>


<body class="min-h-screen flex items-center justify-center bg-slate-950">


<div class="card w-[430px] max-w-[92%] bg-slate-900/80 backdrop-blur-xl border border-slate-700 shadow-2xl">


    <div class="card-body">


        <!-- Logo -->

        <div class="flex justify-center mb-4">

            <div class="w-20 h-20 rounded-full
                        bg-primary/10
                        border border-primary/40
                        flex items-center justify-center
                        shadow-[0_0_35px_rgba(59,130,246,0.35)]">

                <img
                    src="logo.svg"
                    alt="AI Logo"
                    class="w-14 h-14"
                >

            </div>

        </div>


        <!-- Title -->

        <h1 class="text-3xl font-bold text-center">

            ساخت حساب کاربری

        </h1>


        <p class="text-center text-slate-400 mt-2">

            به AI ChatBot خوش آمدید

        </p>


        <!-- Message -->

        <?php if ($message !== ""): ?>

            <div
                class="alert
                <?= $messageType === 'success'
                    ? 'alert-success'
                    : 'alert-error'
                ?>
                mt-5"
            >

                <span>
                    <?= htmlspecialchars($message) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- Register Form -->
         <form
            method="POST"
            action="register.php"
            class="mt-6"
        >


            <!-- Name -->

            <label class="form-control w-full">

                <div class="label">

                    <span class="label-text">
                        نام
                    </span>

                </div>

                <div class="relative">

                    <i class="fa-solid fa-user
                    absolute right-4 top-1/2
                    -translate-y-1/2
                    text-slate-400">
                    </i>

                    <input
                        type="text"
                        name="name"
                        placeholder="نام شما"
                        required
                        autocomplete="name"
                        class="input input-bordered
                        input-primary
                        w-full pr-11"
                    >

                </div>

            </label>


            <!-- Email -->

            <label class="form-control w-full mt-4">

                <div class="label">

                    <span class="label-text">
                        ایمیل
                    </span>

                </div>

                <div class="relative">

                    <i class="fa-solid fa-envelope
                    absolute right-4 top-1/2
                    -translate-y-1/2
                    text-slate-400">
                    </i>

                    <input
                        type="email"
                        name="email"
                        placeholder="example@gmail.com"
                        required
                        autocomplete="email"
                        class="input input-bordered
                        input-primary
                        w-full pr-11"
                    >

                </div>

            </label>


            <!-- Password -->

            <label class="form-control w-full mt-4">

                <div class="label">

                    <span class="label-text">
                        رمز عبور
                    </span>

                </div>

                <div class="relative">

                    <i class="fa-solid fa-lock
                    absolute right-4 top-1/2
                    -translate-y-1/2
                    text-slate-400">
                    </i>

                    <input
                        type="password"
                        name="password"
                        placeholder="حداقل ۶ کاراکتر"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="input input-bordered
                        input-primary
                        w-full pr-11"
                    >

                </div>

            </label>


            <!-- Confirm Password -->

            <label class="form-control w-full mt-4">

                <div class="label">

                    <span class="label-text">
                        تکرار رمز عبور
                    </span>

                </div>

                <div class="relative">

                    <i class="fa-solid fa-shield-halved
                    absolute right-4 top-1/2
                    -translate-y-1/2
                    text-slate-400">
                    </i>

                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="رمز را دوباره وارد کنید"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="input input-bordered
                        input-primary
                        w-full pr-11"
                    >

                </div>

            </label>


            <!-- Register -->

            <button
                type="submit"
                class="btn btn-primary w-full mt-6 text-lg"
            >
            <i class="fa-solid fa-user-plus"></i>

                ثبت نام

            </button>


        </form>


        <!-- Login -->

        <p class="text-center text-slate-400 mt-6">

            قبلاً حساب ساخته‌ای؟

            <a
                href="indext.html"
                class="text-primary font-semibold hover:underline"
            >

                وارد شوید

            </a>

        </p>


    </div>

</div>


</body>

</html>