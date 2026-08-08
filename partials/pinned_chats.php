<?php
// Загружаем закрепленные чаты текущего пользователя из БД
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user']['id'] ?? 0;
$pinnedChats = [];

if ($userId) {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Ищем закрепленные чаты пользователя (is_pinned = 1)
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT message_text FROM messages WHERE id = c.last_message_id) as last_message,
               c.last_message_at as last_message_time
        FROM chats c
        JOIN chat_participants cp ON cp.chat_id = c.id AND cp.user_id = ?
        WHERE c.is_pinned = 1 AND cp.is_active = 1
        ORDER BY c.type = 'admin' DESC, c.id
    ");
    $stmt->execute([$userId]);
    $pinnedChats = $stmt->fetchAll();
}
?>

<div class="pinned-section p-4">
    <h2 class="text-sm font-semibold text-slate-500 mb-3">ЗАКРЕПЛЕННЫЕ ЧАТЫ</h2>
    <div class="space-y-2">
        <?php foreach ($pinnedChats as $chat): 
            $icon = match($chat['type']) {
                'favorite' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v12l-5-3-5 3V4z"/></svg>',
                'support' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z"/></svg>',
                'admin' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                default => mb_substr($chat['name'] ?? '?', 0, 1, 'UTF-8')
            };
            $bgColor = match($chat['type']) {
                'favorite' => 'bg-gradient-to-br from-yellow-400 to-orange-500',
                'support' => 'bg-gradient-to-br from-sky-400 to-sky-600',
                'admin' => 'bg-gradient-to-br from-violet-500 to-purple-600',
                default => 'bg-gradient-to-br from-gray-400 to-gray-600'
            };
        ?>
        <div class="chat-item p-3 rounded-lg cursor-pointer flex items-center gap-3 hover:bg-slate-100 transition-colors"
             data-chat-id="<?php echo $chat['id']; ?>"
             data-chat-type="<?php echo $chat['type']; ?>"
             data-chat-name="<?php echo htmlspecialchars($chat['name']); ?>">
            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center <?php echo $bgColor; ?> text-white">
                <?php echo $icon; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($chat['name']); ?></p>
                <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($chat['last_message'] ?? $chat['description'] ?? ''); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($pinnedChats)): ?>
            <p class="text-sm text-slate-400 text-center py-4">Нет закрепленных чатов</p>
        <?php endif; ?>
    </div>
</div>