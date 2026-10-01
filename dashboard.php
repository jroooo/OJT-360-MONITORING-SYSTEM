
<?php
session_start();

// Temporary test dashboard
$user = $_SESSION['username'] ?? 'Test User';
$role = $_SESSION['role'] ?? 'Student';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>OJT360 - Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen">

    <!-- NAVBAR -->
    <nav class="bg-slate-900 text-white px-6 py-4 flex items-center justify-between">

        <div class="text-2xl font-extrabold">
            OJT<span class="text-cyan-400">360</span>
        </div>

        <a
            href="login.php"
            class="bg-cyan-500 hover:bg-cyan-600 px-4 py-2 rounded-lg font-semibold"
        >
            Logout
        </a>

    </nav>


    <!-- DASHBOARD -->
    <main class="max-w-7xl mx-auto px-6 py-10">

        <div class="mb-8">

            <p class="text-cyan-500 font-bold uppercase text-sm tracking-wider">
                Dashboard
            </p>

            <h1 class="text-3xl font-extrabold text-slate-800 mt-1">
                Welcome, <?= htmlspecialchars($user) ?>! 👋
            </h1>

            <p class="text-slate-500 mt-2">
                You have successfully logged in to OJT360.
            </p>

        </div>


        <!-- TEST SUCCESS CARD -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">

            <div class="flex items-center gap-4">

                <div class="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-2xl">
                    ✓
                </div>

                <div>
                    <h2 class="text-xl font-bold text-slate-800">
                        Login Successful
                    </h2>

                    <p class="text-slate-500">
                        Your login is working and redirected to the dashboard.
                    </p>
                </div>

            </div>

        </div>


        <!-- INFORMATION CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">

                <p class="text-sm text-slate-500">
                    Account Role
                </p>

                <h3 class="text-2xl font-extrabold text-slate-800 mt-2">
                    <?= htmlspecialchars($role) ?>
                </h3>

            </div>


            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">

                <p class="text-sm text-slate-500">
                    OJT Status
                </p>

                <h3 class="text-2xl font-extrabold text-cyan-500 mt-2">
                    Active
                </h3>

            </div>


            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">

                <p class="text-sm text-slate-500">
                    Rendered Hours
                </p>

                <h3 class="text-2xl font-extrabold text-slate-800 mt-2">
                    0 Hours
                </h3>

            </div>

        </div>


        <!-- TEMPORARY MESSAGE -->
        <div class="mt-8 bg-cyan-50 border border-cyan-200 rounded-2xl p-6">

            <h2 class="font-bold text-cyan-800">
                Temporary Dashboard
            </h2>

            <p class="text-cyan-700 text-sm mt-1">
                This page is only for testing the Login → Dashboard connection.
                You can replace this design later with the actual OJT360 dashboard.
            </p>

        </div>

    </main>

</body>
</html>
```
