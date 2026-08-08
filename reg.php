<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/encrypt.php';

// Получаем параметры
$code = filter_var($_GET['code'] ?? '', FILTER_SANITIZE_STRING);
$appId = (int)($_GET['app_id'] ?? 5);
$appCode = filter_var($_GET['app_code'] ?? '725821', FILTER_SANITIZE_STRING);

// Проверяем наличие кода
if (empty($code)) {
    header("Location: register.php?error=1");
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Ищем пригласительный код
    $stmt = $conn->prepare("
        SELECT ic.*, s.name as school_name 
        FROM invitation_codes ic 
        LEFT JOIN schools s ON ic.school_id = s.id 
        WHERE ic.code = ? 
          AND ic.app_id = ? 
          AND ic.app_code = ? 
          AND ic.is_active = 1 
          AND (ic.expires_at IS NULL OR ic.expires_at > NOW()) 
          AND (ic.max_uses = 0 OR ic.used_count < ic.max_uses)
        LIMIT 1
    ");
    $stmt->execute([$code, $appId, $appCode]);
    $invitation = $stmt->fetch();

    if (!$invitation) {
        // Код не найден или недействителен
        header("Location: register.php?error=2");
        exit;
    }

    // Если форма отправлена (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Валидация
        $errors = [];

        if (empty($name) || strlen($name) < 2) {
            $errors[] = "Имя должно содержать минимум 2 символа";
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Введите корректный email";
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = "Пароль должен содержать минимум 6 символов";
        }

        if ($password !== $passwordConfirm) {
            $errors[] = "Пароли не совпадают";
        }

        // Проверяем, не занят ли email
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Пользователь с таким email уже существует";
            }
        }

        if (!empty($errors)) {
            // Показываем форму снова с ошибками
            showRegistrationForm($invitation, $code, $appId, $appCode, $errors);
            exit;
        }

        // Создаем пользователя
        $passwordHash = Encrypt::hashPassword($password);
        $userRole = $invitation['user_type'] ?? 'teacher';

        $conn->beginTransaction();

        try {
            $stmt = $conn->prepare("
                INSERT INTO users (email, password_hash, name, role, school_id, class_id, min_id) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $email,
                $passwordHash,
                $name,
                $userRole,
                $invitation['school_id'],
                $invitation['class_id']
            ]);
            $userId = $conn->lastInsertId();

            // Увеличиваем счетчик использований
            $stmt = $conn->prepare("UPDATE invitation_codes SET used_count = used_count + 1 WHERE id = ?");
            $stmt->execute([$invitation['id']]);

            // Добавляем в системные чаты
            $systemChats = [-2, -1]; // Избранное и Техподдержка
            if ($invitation['school_id']) {
                $systemChats[] = 0; // Администрация школы
            }

            foreach ($systemChats as $chatId) {
                $stmt = $conn->prepare("INSERT IGNORE INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'member')");
                $stmt->execute([$chatId, $userId]);
            }

            // Если ученик - добавляем в чат класса
            if ($userRole === 'student' && $invitation['class_id']) {
                // Находим чат класса
                $stmt = $conn->prepare("SELECT id FROM chats WHERE type = 'class' AND school_id = ? LIMIT 1");
                $stmt->execute([$invitation['school_id']]);
                $classChat = $stmt->fetch();

                if ($classChat) {
                    $stmt = $conn->prepare("INSERT IGNORE INTO chat_participants (chat_id, user_id, role) VALUES (?, ?, 'member')");
                    $stmt->execute([$classChat['id'], $userId]);
                }
            }

            // Логируем
            $stmt = $conn->prepare("INSERT INTO security_logs (user_id, event_type, ip_address, details) VALUES (?, 'login_success', ?, ?)");
            $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'], "Registration via invitation code {$code}"]);

            $conn->commit();

            // Авторизуем пользователя
            $_SESSION['user'] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => $userRole,
                'school_id' => $invitation['school_id'],
                'class_id' => $invitation['class_id'],
                'min_id' => 1,
                'avatar_type' => 'default',
                'avatar_url' => null
            ];

            // Обновляем статус
            $stmt = $conn->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?");
            $stmt->execute([$userId]);

            session_regenerate_id(true);

            // Редирект в мессенджер
            header("Location: messenger.php?welcome=1");
            exit;

        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Ошибка при создании аккаунта: " . $e->getMessage();
            showRegistrationForm($invitation, $code, $appId, $appCode, $errors);
            exit;
        }
    }

    // GET запрос - показываем форму регистрации
    showRegistrationForm($invitation, $code, $appId, $appCode);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    header("Location: register.php?error=3");
    exit;
}

/**
 * Показывает форму регистрации
 */
function showRegistrationForm($invitation, $code, $appId, $appCode, $errors = []) {
    $schoolName = $invitation['school_name'] ?? 'Не указана';
    $userType = $invitation['user_type'] ?? 'teacher';
    $userTypeLabel = [
        'teacher' => 'Учитель',
        'student' => 'Ученик',
        'parent' => 'Родитель'
    ][$userType] ?? 'Пользователь';

    ?>
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
    <body class="h-full bg-slate-50 text-slate-900">

        <?php if (!empty($errors)): ?>
        <div class="fixed top-0 left-0 right-0 z-50 p-4 flex justify-center pointer-events-none">
            <div class="bg-white pl-4 pr-6 py-3 rounded-2xl shadow-lg border border-red-200 pointer-events-auto max-w-md w-full">
                <?php foreach ($errors as $error): ?>
                    <div class="flex items-center gap-2 text-red-600 text-sm py-1">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

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
                    <p class="text-slate-500 mt-2">Создание аккаунта <?php echo $userTypeLabel; ?></p>
                </div>

                <div class="bg-white/70 backdrop-blur-xl border border-slate-200/80 rounded-3xl p-10 shadow-2xl shadow-slate-200/50">
                    <!-- Информация о приглашении -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                        <div class="flex items-center gap-2 text-blue-700 text-sm font-medium mb-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Информация о приглашении
                        </div>
                        <div class="space-y-1 text-sm text-blue-600">
                            <div>🏫 Школа: <strong><?php echo htmlspecialchars($schoolName); ?></strong></div>
                            <div>👤 Тип аккаунта: <strong><?php echo $userTypeLabel; ?></strong></div>
                            <?php if ($invitation['class_id']): ?>
                            <div>📚 Класс: <strong>ID <?php echo $invitation['class_id']; ?></strong></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="code" value="<?php echo htmlspecialchars($code); ?>">
                        <input type="hidden" name="app_id" value="<?php echo $appId; ?>">
                        <input type="hidden" name="app_code" value="<?php echo htmlspecialchars($appCode); ?>">

                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">ФИО *</label>
                            <input type="text" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition placeholder-slate-400"
                                   placeholder="Иванов Иван Иванович">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition placeholder-slate-400"
                                   placeholder="your@email.com">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Пароль *</label>
                            <input type="password" id="password" name="password" required
                                   minlength="6"
                                   class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition placeholder-slate-400"
                                   placeholder="Минимум 6 символов">
                        </div>

                        <div>
                            <label for="password_confirm" class="block text-sm font-medium text-slate-700 mb-1">Подтвердите пароль *</label>
                            <input type="password" id="password_confirm" name="password_confirm" required
                                   minlength="6"
                                   class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition placeholder-slate-400"
                                   placeholder="Повторите пароль">
                        </div>

                        <button type="submit" 
                                class="w-full font-semibold text-white bg-purple-600 hover:bg-purple-700 py-3 rounded-xl shadow-lg shadow-purple-500/20 hover:shadow-purple-500/30 transition-all duration-300 transform hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                            Зарегистрироваться
                        </button>
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
    </body>
    </html>
    <?php
}