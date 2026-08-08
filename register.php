<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MTSoc Messenger | Регистрация</title>
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
<body class="h-full bg-slate-50 text-slate-900" x-data="registerForm()">

    <div x-show="errorMessage" 
         x-transition
         class="fixed top-0 left-0 right-0 z-50 p-4 flex justify-center pointer-events-none">
        <div class="flex items-center space-x-3 bg-white pl-4 pr-6 py-3 rounded-2xl shadow-lg border border-red-200 pointer-events-auto">
            <span class="flex p-2 rounded-full bg-red-100">
                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </span>
            <p class="text-sm font-medium text-slate-700" x-text="errorMessage"></p>
        </div>
    </div>

    <div class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
        <div class="absolute top-0 -left-1/3 w-2/3 h-2/3 bg-purple-200 rounded-full filter blur-3xl opacity-40 animate-slow-spin"></div>
        <div class="absolute bottom-0 -right-1/4 w-2/3 h-2/3 bg-sky-200 rounded-full filter blur-3xl opacity-40 animate-slow-spin" style="animation-delay: 10s;"></div>

        <div class="w-full max-w-md z-10">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-slate-900">Регистрация</h1>
                <p class="text-slate-500 mt-2">Введите пригласительный код для создания аккаунта</p>
            </div>

            <div class="bg-white/70 backdrop-blur-xl border border-slate-200/80 rounded-3xl p-10 shadow-2xl shadow-slate-200/50">
                <form action="reg.php" method="GET" class="space-y-6">
                    <div>
                        <label for="code" class="block text-sm font-medium text-slate-700">Пригласительный код</label>
                        <div class="mt-1">
                            <input type="text" name="code" id="code" 
                                   x-model="code"
                                   placeholder="Введите ваш код" required 
                                   class="w-full px-4 py-3 bg-white/50 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors duration-200">
                        </div>
                    </div>
                    
                    <input type="hidden" name="app_id" value="5">
                    <input type="hidden" name="app_code" value="725821">
                    
                    <div>
                        <button type="submit" 
                                :disabled="!code"
                                class="w-full font-semibold text-white bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed py-3 rounded-xl shadow-lg shadow-purple-500/20 hover:shadow-purple-500/30 transition-all duration-300 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            Продолжить
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-sm text-center text-slate-500">
                    Уже есть аккаунт? 
                    <a href="index.php" class="font-medium text-purple-600 hover:text-purple-700 hover:underline">
                        Войти
                    </a>
                </p>
            </div>
            
            <footer class="mt-8 text-center text-xs text-slate-400"> 
                © <?php echo date("Y"); ?> MTSoc Messenger
            </footer>
        </div>
    </div>
    
    <script>
        function registerForm() {
            return {
                code: '',
                errorMessage: ''
            }
        }
    </script>
</body>
</html>