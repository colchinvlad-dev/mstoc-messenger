<?php
session_start();

if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTSoc Messenger - <?php echo htmlspecialchars($user['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/messenger.css">
</head>
<body class="bg-slate-100">
    <div class="flex h-screen text-slate-800">
        <!-- Сайдбар -->
        <aside class="w-96 flex flex-col bg-white border-r border-slate-200 relative flex-shrink-0">
            <!-- Заголовок -->
            <header class="p-4 border-b border-slate-200 flex-shrink-0">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.455.09-.934.09-1.425v-2.287a6.75 6.75 0 01-2.63-4.218C2.25 7.444 6.28 3.75 12 3.75s9.75 3.694 9.75 8.25z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-800">MTSoc Messenger</h1>
                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($user['name']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Профиль -->
                    <div class="relative">
                        <button id="profile-toggle-button" class="p-2 rounded-full hover:bg-slate-100 transition-colors">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                <?php echo mb_substr($user['name'], 0, 1, 'UTF-8'); ?>
                            </div>
                        </button>
                        <div id="profile-dropdown-menu" class="dropdown-menu w-72 bg-white rounded-xl shadow-xl border border-slate-200 right-0 top-full mt-2">
                            <div class="p-4 flex items-center gap-4 border-b border-slate-200">
                                <div class="w-16 h-16 rounded-full flex-shrink-0 flex items-center justify-center text-white text-2xl font-bold bg-gradient-to-br from-blue-500 to-purple-600">
                                    <?php echo mb_substr($user['name'], 0, 2, 'UTF-8'); ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-base truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="online-dot"></div>
                                        <p class="text-sm text-slate-500">Online</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 text-sm space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Роль:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($user['role']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Школа:</span>
                                    <span class="font-medium">№<?php echo htmlspecialchars($user['school_id'] ?? '-'); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-600">Почта:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                            <div class="p-2 border-t border-slate-200">
                                <?php if ($user['role'] === 'super_admin'): ?>
                                <a href="/admin/index.php" class="flex items-center gap-2 w-full text-left px-3 py-2 rounded-lg text-purple-600 hover:bg-purple-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Супер-админ панель
                                </a>
                                <?php endif; ?>

                                <?php if (in_array($user['role'], ['admin', 'super_admin'])): ?>
                                <a href="/school_admin/index.php" class="flex items-center gap-2 w-full text-left px-3 py-2 rounded-lg text-emerald-600 hover:bg-emerald-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    Управление школой
                                </a>
                                <?php endif; ?>
                                <a href="logout.php" class="flex items-center gap-2 w-full text-left px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                    </svg>
                                    Выйти из аккаунта
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Поиск -->
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" placeholder="Поиск чатов и сообщений..." 
                           class="w-full py-2.5 pl-10 pr-4 bg-slate-100 rounded-xl border-transparent focus:ring-2 focus:ring-purple-500 transition placeholder-slate-500">
                </div>
            </header>
            
            <!-- Табы -->
            <nav class="flex-shrink-0 flex items-center justify-around p-2 border-b border-slate-200 bg-slate-50/50">
                <button class="sidebar-tab py-2 px-4 text-sm rounded-lg active flex items-center gap-2" data-tab="staff">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                    Персонал
                </button>
                <button class="sidebar-tab py-2 px-4 text-sm rounded-lg text-slate-600 hover:bg-slate-100 flex items-center gap-2" data-tab="students">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                    </svg>
                    Учащиеся
                </button>
                <button class="sidebar-tab py-2 px-4 text-sm rounded-lg text-slate-600 hover:bg-slate-100 flex items-center gap-2" data-tab="chats">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/>
                    </svg>
                    Чаты
                </button>
            </nav>

            <!-- Список чатов -->
            <div class="flex-1 overflow-y-auto">
                <div id="panel-staff" class="sidebar-panel">
                    <?php include 'partials/pinned_chats.php'; ?>
                </div>
                <div id="panel-students" class="sidebar-panel" style="display: none;">
                    <?php include 'partials/pinned_chats.php'; ?>
                </div>
                <div id="panel-chats" class="sidebar-panel" style="display: none;">
                    <?php include 'partials/pinned_chats.php'; ?>
                </div>
            </div>
        </aside>

        <!-- Основная область -->
        <main class="flex-1 flex flex-col bg-slate-50">
            <!-- Шапка чата -->
            <header id="chat-header" class="flex items-center justify-between p-4 bg-white border-b border-slate-200 chat-header-active" style="display: none;">
                <div class="flex items-center gap-4 min-w-0 flex-1">
                    <div id="chat-header-avatar-container" class="relative"></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3">
                            <h2 id="chat-header-name" class="font-bold text-lg text-slate-800 truncate"></h2>
                            <div id="chat-status" class="flex items-center gap-1.5">
                                <div class="online-dot"></div>
                                <span class="text-xs text-green-600 font-medium">online</span>
                            </div>
                        </div>
                        <p id="chat-header-role" class="text-sm text-slate-600 mt-1 truncate"></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <div id="typing-indicator" class="typing-indicator items-center gap-2 text-sm text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                        <div class="flex gap-1">
                            <div class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce"></div>
                            <div class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                            <div class="w-1.5 h-1.5 bg-slate-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                        </div>
                        <span class="text-xs font-medium">печатает...</span>
                    </div>

                    <div class="flex items-center gap-1">
                        <button id="chat-search-btn" class="p-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-600" title="Поиск в чате">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                        <button id="chat-info-btn" class="p-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-600" title="Информация о чате">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                            </svg>
                        </button>
                        <button id="chat-menu-btn" class="p-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-600" title="Меню">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Контент -->
            <div class="flex-1 relative">
                <!-- Плейсхолдер -->
                <div id="chat-placeholder" class="absolute inset-0 p-6 flex items-center justify-center">
                    <div class="text-center max-w-md">
                        <div class="w-24 h-24 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.455.09-.934.09-1.425v-2.287a6.75 6.75 0 01-2.63-4.218C2.25 7.444 6.28 3.75 12 3.75s9.75 3.694 9.75 8.25z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-800 mb-3">Добро пожаловать в MTSoc Messenger!</h3>
                        <p class="text-slate-600 mb-6">Выберите чат из списка слева, чтобы начать общение.</p>
                    </div>
                </div>
                
                <!-- Сообщения -->
                <div id="chat-messages" class="absolute inset-0 flex flex-col" style="display: none;">
                    <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messages-container"></div>
                </div>

                <!-- Профиль пользователя -->
                <div id="chat-profile" class="absolute inset-0 flex flex-col bg-white" style="display: none;"></div>

                <!-- Профиль группы -->
                <div id="group-profile" class="absolute inset-0 flex flex-col bg-white" style="display: none;"></div>
            </div>
            
            <!-- Футер -->
            <footer id="chat-footer" class="p-4 bg-white border-t border-slate-200" style="display: none;">
                <div id="reply-preview-container" class="mb-2" style="display: none;"></div>
                <div id="file-preview" class="mb-2" style="display: none;"></div>
                <div class="flex items-end gap-3">
                    <button id="attach-button" class="p-3 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                        </svg>
                    </button>
                    <input type="file" id="file-input" class="hidden" accept="image/*,.pdf,.doc,.docx,.txt">
                    <div class="flex-1 bg-white rounded-2xl border border-slate-200 p-1.5 shadow-sm">
                        <textarea id="message-input" placeholder="Напишите сообщение..." rows="1"
                                  class="message-input w-full resize-none border-0 focus:ring-0 focus:outline-none px-3 py-2 text-slate-700 placeholder-slate-400 rounded-xl"></textarea>
                    </div>
                    <button id="send-button" class="p-3 bg-purple-500 text-white rounded-xl hover:bg-purple-600 transition-colors shadow-lg shadow-purple-500/25 flex items-center justify-center">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                        </svg>
                    </button>
                </div>
            </footer>
        </main>
    </div>
    
    <script>
        // Глобальный конфиг - ДО загрузки всех скриптов
        window.MTCONFIG = {
            userId: <?php echo (int)$user['id']; ?>,
            userName: "<?php echo addslashes($user['name']); ?>",
            userRole: "<?php echo $user['role']; ?>",
            userEmail: "<?php echo $user['email']; ?>",
            schoolId: <?php echo $user['school_id'] ?? 'null'; ?>,
            apiBaseUrl: '/api/'
        };
        console.log('Config loaded:', window.MTCONFIG);
    </script>
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/messenger.js"></script>
</body>
</html>