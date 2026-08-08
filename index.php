<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'super_admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: messenger.php");
    }
    exit();
}

$error_message = "";
if (isset($_GET['error'])) {
    $errors = [
        1 => "Неверный логин или пароль. Пожалуйста, проверьте данные и попробуйте снова.",
        2 => "Ошибка системы безопасности. Попробуйте войти заново.",
        3 => "Ваш аккаунт заблокирован. Обратитесь к администратору."
    ];
    $error_message = $errors[$_GET['error']] ?? "Произошла ошибка. Попробуйте снова.";
}
?>
<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTSoc Messenger | Вход</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .animate-slow-spin { animation: spin 20s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        input:focus { outline: none; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-900" x-data="loginForm()">
    
    <!-- Toast уведомления -->
    <div x-show="errorMessage" 
         x-transition
         class="fixed top-0 left-0 right-0 z-50 p-4 flex justify-center pointer-events-none">
        <div class="flex items-center space-x-3 bg-white pl-4 pr-6 py-3 rounded-2xl shadow-lg border border-red-200 pointer-events-auto">
            <span class="flex p-2 rounded-full bg-red-100">
                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </span>
            <p class="text-sm font-medium text-slate-700" x-text="errorMessage"></p>
        </div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
        <!-- Декоративные элементы -->
        <div class="absolute top-0 -left-1/3 w-2/3 h-2/3 bg-purple-200 rounded-full filter blur-3xl opacity-40 animate-slow-spin"></div>
        <div class="absolute bottom-0 -right-1/4 w-2/3 h-2/3 bg-sky-200 rounded-full filter blur-3xl opacity-40 animate-slow-spin" style="animation-delay: 10s;"></div>

        <div class="w-full max-w-md z-10">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.455.09-.934.09-1.425v-2.287a6.75 6.75 0 01-2.63-4.218C2.25 7.444 6.28 3.75 12 3.75s9.75 3.694 9.75 8.25z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-slate-900">MTSoc Messenger</h1>
                <p class="text-slate-500 mt-2">Войдите в аккаунт для продолжения</p>
            </div>

            <div class="bg-white/70 backdrop-blur-xl border border-slate-200/80 rounded-3xl p-10 shadow-2xl shadow-slate-200/50">
                <form action="auth.php" method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" required 
                               x-model="email"
                               class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition placeholder-slate-400"
                               placeholder="your@email.com">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Пароль</label>
                        <input type="password" id="password" name="password" required 
                               x-model="password"
                               class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition placeholder-slate-400"
                               placeholder="Введите пароль">
                    </div>

                    <button type="submit" 
                            class="w-full font-semibold text-white bg-purple-600 hover:bg-purple-700 py-3 rounded-xl shadow-lg shadow-purple-500/20 hover:shadow-purple-500/30 transition-all duration-300 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                        Войти в аккаунт
                    </button>
                </form>

                <div class="mt-6 pt-6 border-t border-slate-200">
                    <a href="signin.php?token=demo" 
                       class="block w-full text-center font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 py-3 rounded-xl transition-all duration-300">
                        Демо-вход
                    </a>
                </div>

                <p class="mt-8 text-sm text-center text-slate-500">
                    Нет аккаунта? 
                    <a href="register.php" class="font-medium text-purple-600 hover:text-purple-700 hover:underline">
                        Зарегистрироваться
                    </a>
                </p>
            </div>
            
            <footer class="mt-8 text-center text-xs text-slate-400"> 
                © <?php echo date("Y"); ?> MTSoc Messenger. Все права защищены.
            </footer>
        </div>
    </div>
    
    <script>
        function loginForm() {
            return {
                email: '',
                password: '',
                errorMessage: <?php echo json_encode($error_message); ?>,
                init() {
                    if (this.errorMessage) {
                        setTimeout(() => { this.errorMessage = ''; }, 5000);
                    }
                }
            }
        }
    </script>
</body>
</html>