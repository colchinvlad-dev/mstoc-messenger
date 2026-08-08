<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'super_admin') {
    header('Location: ../index.php');
    exit();
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель управления - MTSoc Messenger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/messenger.css">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <!-- Сайдбар -->
        <div class="w-64 bg-white shadow-lg flex flex-col">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">Управление</h1>
                        <p class="text-sm text-gray-500">MTSoc Messenger</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-4 space-y-2">
                <a href="#" class="nav-item active flex items-center space-x-3 p-3 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 text-white" data-section="dashboard">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Обзор</span>
                </a>
                <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-100" data-section="schools">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Школы</span>
                </a>
                <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-100" data-section="admins">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    <span>Администраторы</span>
                </a>
                <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg text-gray-600 hover:bg-gray-100" data-section="statistics">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Статистика</span>
                </a>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        <?php echo mb_substr($user['name'], 0, 1, 'UTF-8'); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-xs text-gray-500">Супер-администратор</p>
                    </div>
                </div>
                <a href="/logout.php" class="flex items-center space-x-2 text-sm text-red-600 hover:bg-red-50 p-2 rounded-lg mt-2 w-full">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Выйти</span>
                </a>
            </div>
        </div>

        <!-- Контент -->
        <div class="flex-1 overflow-auto">
            <div class="p-8">
                <div class="grid grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-purple-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg">
                        <p class="text-blue-100 text-sm">Всего школ</p>
                        <p class="text-3xl font-bold mt-2" id="stat-schools">-</p>
                    </div>
                    <div class="bg-gradient-to-br from-pink-500 to-rose-600 rounded-2xl p-6 text-white shadow-lg">
                        <p class="text-pink-100 text-sm">Администраторов</p>
                        <p class="text-3xl font-bold mt-2" id="stat-admins">-</p>
                    </div>
                    <div class="bg-gradient-to-br from-sky-500 to-cyan-600 rounded-2xl p-6 text-white shadow-lg">
                        <p class="text-sky-100 text-sm">Пользователей</p>
                        <p class="text-3xl font-bold mt-2" id="stat-users">-</p>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg">
                        <p class="text-emerald-100 text-sm">Сообщений сегодня</p>
                        <p class="text-3xl font-bold mt-2" id="stat-messages">-</p>
                    </div>
                </div>
                <div id="section-content"></div>
            </div>
        </div>
    </div>

    <script src="/admin/assets/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.adminPanel = new AdminPanel();
        });
    </script>
</body>
</html>