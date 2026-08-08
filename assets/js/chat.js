/**
 * MTSoc Messenger - Дополнительные функции чата
 */

class ChatFunctions {
    constructor(messenger) {
        this.messenger = messenger;
    }

    // Инициализация кнопок действий в шапке чата
    initChatActions() {
        const infoBtn = document.getElementById('chat-info-btn');
        const searchBtn = document.getElementById('chat-search-btn');
        const menuBtn = document.getElementById('chat-menu-btn');

        if (infoBtn) infoBtn.addEventListener('click', () => this.showChatInfo());
        if (searchBtn) searchBtn.addEventListener('click', () => this.showSearchInChat());
        if (menuBtn) menuBtn.addEventListener('click', () => this.showChatMenu());
    }

    async showChatInfo() {
        if (this.messenger.currentChatType === 'group' || this.messenger.currentChatType === 'class') {
            this.showGroupProfile();
        } else {
            this.showUserProfile();
        }
    }

    async showUserProfile() {
        try {
            const data = await Utils.fetchAPI(`users.php?action=profile&chat_id=${this.messenger.currentChatId}`);

            if (data.success) {
                const profile = document.getElementById('chat-profile');
                profile.innerHTML = `
                    <div class="p-6 text-center">
                        <div class="w-24 h-24 rounded-full mx-auto flex items-center justify-center text-white text-3xl font-bold ${Utils.getAvatarClass('default')}">
                            ${data.user.name.charAt(0).toUpperCase()}
                        </div>
                        <h3 class="text-xl font-bold mt-4">${Utils.escapeHtml(data.user.name)}</h3>
                        <p class="text-gray-500">${Utils.escapeHtml(data.user.role)}</p>
                        ${data.user.is_online ? 
                            '<span class="inline-flex items-center gap-1 text-green-500 mt-2"><span class="online-dot"></span> В сети</span>' :
                            `<span class="text-gray-400 mt-2">Был(а) ${Utils.formatDate(data.user.last_seen)}</span>`
                        }
                    </div>
                `;
                profile.style.display = 'flex';
                document.getElementById('chat-messages').style.display = 'none';
            }
        } catch (error) {
            console.error('Error loading profile:', error);
        }
    }

    async showGroupProfile() {
        try {
            const data = await Utils.fetchAPI(`chats.php?action=info&chat_id=${this.messenger.currentChatId}`);

            if (data.success) {
                const profile = document.getElementById('group-profile');
                profile.innerHTML = `
                    <div class="p-6">
                        <h3 class="text-xl font-bold">${Utils.escapeHtml(data.chat.name)}</h3>
                        <p class="text-gray-500 mt-1">${Utils.escapeHtml(data.chat.description || '')}</p>
                        <div class="mt-4">
                            <h4 class="font-semibold mb-2">Участники (${data.participants.length})</h4>
                            <div class="space-y-2">
                                ${data.participants.map(p => `
                                    <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold ${Utils.getAvatarClass('default')}">
                                            ${p.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-medium">${Utils.escapeHtml(p.name)}</div>
                                            <div class="text-sm text-gray-500">${p.role === 'owner' ? 'Создатель' : p.role === 'admin' ? 'Администратор' : 'Участник'}</div>
                                        </div>
                                        ${p.is_online ? '<span class="online-dot"></span>' : ''}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
                profile.style.display = 'flex';
                document.getElementById('chat-messages').style.display = 'none';
            }
        } catch (error) {
            console.error('Error loading group profile:', error);
        }
    }

    showSearchInChat() {
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Поиск в чате...';
        searchInput.className = 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500';

        const messagesContainer = document.getElementById('messages-container');
        messagesContainer.insertBefore(searchInput, messagesContainer.firstChild);

        searchInput.addEventListener('input', Utils.debounce((e) => {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('.message-text').forEach(el => {
                const parent = el.closest('.message-wrapper');
                if (parent) {
                    parent.style.display = el.textContent.toLowerCase().includes(query) ? '' : 'none';
                }
            });
        }, 300));

        searchInput.focus();
    }

    showChatMenu() {
        const menu = document.createElement('div');
        menu.className = 'context-menu';
        menu.style.right = '20px';
        menu.style.top = '60px';

        menu.innerHTML = `
            <div class="context-menu-item" onclick="this.closest('.context-menu').remove(); MessengerInstance.showChatInfo()">
                <span>ℹ️</span><span>Информация</span>
            </div>
            <div class="context-menu-item">
                <span>🔇</span><span>Отключить уведомления</span>
            </div>
            <div class="context-menu-divider"></div>
            <div class="context-menu-item danger">
                <span>🚫</span><span>Покинуть чат</span>
            </div>
        `;

        document.body.appendChild(menu);
        document.addEventListener('click', () => menu.remove(), { once: true });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.MessengerInstance) {
        window.chatFunctions = new ChatFunctions(window.MessengerInstance);
        window.chatFunctions.initChatActions();
    }
});