<?php
session_start();

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'super_admin'])) {
    header('Location: ../index.php');
    exit();
}

$user = $_SESSION['user'];
$isSuperAdmin = $user['role'] === 'super_admin';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление школой - MTSoc Messenger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/messenger.css">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Сайдбар -->
        <div class="w-64 bg-white shadow-lg flex flex-col">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">Управление</h1>
                        <p class="text-sm text-gray-500">Школа №<?php echo $user['school_id'] ?? '?'; ?></p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-4 space-y-2">
                <a href="#" class="nav-item active flex items-center space-x-3 p-3 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 text-white" data-section="dashboard">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Обзор</span>
                </a>
                <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-100" data-section="teachers">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                    <span>Учителя</span>
                </a>
                <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-100" data-section="students">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                    </svg>
                    <span>Ученики</span>
                </a>
                <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-100" data-section="classes">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                    <span>Классы</span>
                </a>
                <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-100" data-section="chats">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/>
                    </svg>
                    <span>Чаты</span>
                </a>
                <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-100" data-section="invitations">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    <span>Приглашения</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        <?php echo mb_substr($user['name'], 0, 1, 'UTF-8'); ?>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-xs text-gray-500">Администратор</p>
                    </div>
                </div>
                <a href="/messenger.php" class="flex items-center space-x-2 text-sm text-gray-600 hover:bg-gray-100 p-2 rounded-lg w-full">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25"/>
                    </svg>
                    <span>Мессенджер</span>
                </a>
                <?php if ($isSuperAdmin): ?>
                <a href="/admin/index.php" class="flex items-center space-x-2 text-sm text-purple-600 hover:bg-purple-50 p-2 rounded-lg w-full mt-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37"/>
                    </svg>
                    <span>Супер-админ</span>
                </a>
                <?php endif; ?>
                <a href="/logout.php" class="flex items-center space-x-2 text-sm text-red-600 hover:bg-red-50 p-2 rounded-lg w-full mt-1">
                    <span>Выйти</span>
                </a>
            </div>
        </div>

        <!-- Контент -->
        <div class="flex-1 overflow-auto">
            <div class="p-8">
                <div class="grid grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg">
                        <p class="text-emerald-100 text-sm">Учителей</p>
                        <p class="text-3xl font-bold mt-2" id="stat-teachers">-</p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg">
                        <p class="text-blue-100 text-sm">Учеников</p>
                        <p class="text-3xl font-bold mt-2" id="stat-students">-</p>
                    </div>
                    <div class="bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl p-6 text-white shadow-lg">
                        <p class="text-orange-100 text-sm">Классов</p>
                        <p class="text-3xl font-bold mt-2" id="stat-classes">-</p>
                    </div>
                    <div class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
                        <p class="text-violet-100 text-sm">Чатов</p>
                        <p class="text-3xl font-bold mt-2" id="stat-chats">-</p>
                    </div>
                </div>
                <div id="section-content"></div>
            </div>
        </div>
    </div>

    <script src="/school_admin/assets/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.schoolAdmin = new SchoolAdmin({
                schoolId: <?php echo $user['school_id'] ?? 'null'; ?>,
                isSuperAdmin: <?php echo $isSuperAdmin ? 'true' : 'false'; ?>
            });
        });
    </script>
</body>
</html>