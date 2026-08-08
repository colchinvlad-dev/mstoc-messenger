/**
 * MTSoc Messenger - Полный код
 */
class MessengerApp {
    constructor() {
        this.currentChatId = null;
        this.currentChatType = null;
        this.replyToMessageId = null;
        this.pendingFile = null;
        this.activeTab = 'staff';
        this.searchTimeout = null;
        this.init();
    }

    init() {
        console.log('Messenger initializing...');
        this.initSidebar();
        this.initProfileDropdown();
        this.initMessageInput();
        this.initFileUpload();
        this.initSearch();
        this.bindPinnedChats();
        this.bindChatHeaderButtons();
        
        this.loadChatList('staff');
        this.loadUsers('teacher');
    }

    // ============ САЙДБАР ============
    initSidebar() {
        document.querySelectorAll('.sidebar-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                this.activeTab = tab.dataset.tab;
                
                document.querySelectorAll('.sidebar-panel').forEach(p => p.style.display = 'none');
                const panel = document.getElementById('panel-' + this.activeTab);
                if (panel) {
                    panel.style.display = 'block';
                    this.loadChatList(this.activeTab);
                    if (this.activeTab === 'staff') {
                        this.loadUsers('teacher');
                    } else if (this.activeTab === 'students') {
                        this.loadUsers('student');
                    }
                }
            });
        });
    }

    initProfileDropdown() {
        const toggleBtn = document.getElementById('profile-toggle-button');
        const dropdown = document.getElementById('profile-dropdown-menu');
        if (!toggleBtn || !dropdown) return;
        
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', (e) => {
            if (!dropdown.contains(e.target) && !toggleBtn.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }

    initMessageInput() {
        const input = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-button');
        if (!input || !sendBtn) return;

        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 150) + 'px';
        });

        sendBtn.addEventListener('click', () => this.sendMessage());

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
    }

    initFileUpload() {
        const fileInput = document.getElementById('file-input');
        const attachBtn = document.getElementById('attach-button');
        if (!fileInput || !attachBtn) return;

        attachBtn.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) {
                alert('Файл слишком большой (макс. 10MB)');
                fileInput.value = '';
                return;
            }
            this.pendingFile = file;
            this.showFilePreview(file);
        });
    }

    initSearch() {
        const searchInput = document.querySelector('header input[type="text"]');
        if (!searchInput) return;
        
        searchInput.addEventListener('input', () => {
            clearTimeout(this.searchTimeout);
            const query = searchInput.value.trim();
            
            if (query.length < 2) {
                if (this.currentChatId) {
                    this.loadMessages(this.currentChatId);
                }
                return;
            }
            
            this.searchTimeout = setTimeout(async () => {
                await this.searchMessages(query);
            }, 500);
        });
    }

    async searchMessages(query) {
        try {
            const chatId = this.currentChatId || 0;
            const data = await Utils.fetchAPI(`messages.php?action=search&q=${encodeURIComponent(query)}&chat_id=${chatId}`);
            
            if (data && data.success && data.messages) {
                const container = document.getElementById('messages-container');
                
                if (data.messages.length === 0) {
                    container.innerHTML = '<div style="text-align:center;color:#94a3b8;margin-top:40px;">Ничего не найдено</div>';
                    return;
                }
                
                container.innerHTML = '';
                
                const header = document.createElement('div');
                header.style.cssText = 'text-align:center;padding:8px;background:#fef3c7;border-radius:8px;margin-bottom:12px;font-size:13px;color:#92400e;';
                header.textContent = `🔍 Найдено ${data.messages.length} сообщений`;
                container.appendChild(header);
                
                data.messages.forEach(msg => {
                    if (msg.chat_name) {
                        const chatLabel = document.createElement('div');
                        chatLabel.style.cssText = 'font-size:11px;color:#8b5cf6;margin-bottom:4px;font-weight:600;cursor:pointer;';
                        chatLabel.textContent = `📌 ${msg.chat_name}`;
                        chatLabel.addEventListener('click', () => {
                            this.openChat(msg.chat_id, msg.chat_name, msg.chat_type);
                        });
                        container.appendChild(chatLabel);
                    }
                    this.displayMessage(msg);
                });
                
                container.scrollTop = 0;
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    showFilePreview(file) {
        const preview = document.getElementById('file-preview');
        if (!preview) return;
        preview.innerHTML = `
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;">
                <span>📎 ${file.name}</span>
                <button onclick="document.getElementById('file-preview').style.display='none';window._messenger.pendingFile=null;" style="color:red;cursor:pointer;margin-left:auto;">✕</button>
            </div>
        `;
        preview.style.display = 'block';
    }

    bindChatHeaderButtons() {
        const infoBtn = document.getElementById('chat-info-btn');
        const searchBtn = document.getElementById('chat-search-btn');
        const menuBtn = document.getElementById('chat-menu-btn');
        
        if (infoBtn) {
            infoBtn.addEventListener('click', () => {
                if (this.currentChatId) this.showChatInfo();
            });
        }
        
        if (searchBtn) {
            searchBtn.addEventListener('click', () => {
                const query = prompt('Поиск в чате:');
                if (query && query.length >= 2) {
                    this.searchMessages(query);
                }
            });
        }
        
        if (menuBtn) {
            menuBtn.addEventListener('click', () => {
                if (this.currentChatId && ['group', 'class', 'subject'].includes(this.currentChatType)) {
                    this.showChatManagement(this.currentChatId);
                }
            });
        }
    }

    // ============ ЗАГРУЗКА ДАННЫХ ============
    bindPinnedChats() {
        document.querySelectorAll('.pinned-section .chat-item').forEach(item => {
            item.addEventListener('click', () => {
                this.openChat(
                    parseInt(item.dataset.chatId),
                    item.dataset.chatName,
                    item.dataset.chatType
                );
            });
        });
    }

    async loadChatList(tab) {
        try {
            const data = await Utils.fetchAPI(`chats.php?action=list&tab=${tab}`);
            if (!data || !data.success) return;
            
            const panel = document.getElementById('panel-' + tab);
            if (!panel) return;
            
            const oldList = panel.querySelector('.chat-list-dynamic');
            if (oldList) oldList.remove();
            
            if (!data.chats || data.chats.length === 0) return;
            
            const listEl = document.createElement('div');
            listEl.className = 'chat-list-dynamic px-4 pb-2';
            listEl.innerHTML = `
                <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;margin:8px 0;padding:0 4px;">Чаты</div>
                ${data.chats.map(c => {
                    const name = c.participant_name || c.name || 'Чат';
                    const lastMsg = c.last_message || '';
                    return `
                    <div class="dynamic-chat-item" 
                         data-chat-id="${c.id}" 
                         data-chat-type="${c.type}" 
                         data-chat-name="${Utils.escapeHtml(name)}"
                         style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;cursor:pointer;margin-bottom:2px;"
                         onmouseover="this.style.background='#f1f5f9'" 
                         onmouseout="this.style.background='transparent'">
                        <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#8b5cf6,#6366f1);display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:14px;flex-shrink:0;">
                            ${name.charAt(0).toUpperCase()}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:500;">${Utils.escapeHtml(name)}</div>
                            <div style="font-size:11px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${lastMsg}</div>
                        </div>
                    </div>
                `}).join('')}
            `;
            
            const usersList = panel.querySelector('.users-list');
            if (usersList) {
                panel.insertBefore(listEl, usersList);
            } else {
                panel.appendChild(listEl);
            }
            
            listEl.querySelectorAll('.dynamic-chat-item').forEach(item => {
                item.addEventListener('click', () => {
                    this.openChat(
                        parseInt(item.dataset.chatId),
                        item.dataset.chatName,
                        item.dataset.chatType
                    );
                });
            });
            
        } catch (error) {
            console.error('Error loading chat list:', error);
        }
    }

    async loadUsers(role) {
        try {
            const data = await Utils.fetchAPI(`chats.php?action=users&role=${role}&school_id=${window.MTCONFIG.schoolId || ''}`);
            if (!data || !data.success) return;
            
            const containerId = role === 'teacher' ? 'panel-staff' : 'panel-students';
            const container = document.getElementById(containerId);
            if (!container) return;

            const oldList = container.querySelector('.users-list');
            if (oldList) oldList.remove();

            const listEl = document.createElement('div');
            listEl.className = 'users-list px-4 pb-4';
            
            const title = role === 'teacher' ? 'Сотрудники' : 'Учащиеся';
            
            listEl.innerHTML = `
                <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;margin:8px 0;padding:0 4px;">${title}</div>
                ${data.users && data.users.length > 0 ? data.users.map(u => `
                    <div class="user-item" 
                         data-user-id="${u.id}"
                         data-user-name="${Utils.escapeHtml(u.name)}"
                         data-chat-id="${u.existing_chat_id || ''}"
                         style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:10px;cursor:pointer;margin-bottom:2px;"
                         onmouseover="this.style.background='#f1f5f9'" 
                         onmouseout="this.style.background='transparent'">
                        <div style="position:relative;flex-shrink:0;">
                            <div style="width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:14px;background:${(u.role === 'teacher' || u.role === 'admin') ? 'linear-gradient(135deg, #3b82f6, #1d4ed8)' : 'linear-gradient(135deg, #10b981, #059669)'};">
                                ${u.name.charAt(0).toUpperCase()}
                            </div>
                            ${u.is_online ? '<div style="position:absolute;bottom:0;right:0;width:9px;height:9px;background:#10b981;border:2px solid white;border-radius:50%;"></div>' : ''}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:500;">${Utils.escapeHtml(u.name)}</div>
                            <div style="font-size:11px;color:#94a3b8;">${u.class_name || (u.role === 'admin' ? 'Администратор' : u.role === 'teacher' ? 'Учитель' : 'Ученик')} ${u.existing_chat_id ? '💬' : ''}</div>
                        </div>
                    </div>
                `).join('') : `<div style="color:#94a3b8;font-size:13px;padding:10px;text-align:center;">Нет пользователей</div>`}
            `;

            container.appendChild(listEl);

            listEl.querySelectorAll('.user-item').forEach(item => {
                item.addEventListener('click', () => {
                    const userId = parseInt(item.dataset.userId);
                    const userName = item.dataset.userName;
                    const existingChatId = item.dataset.chatId;
                    
                    if (existingChatId) {
                        this.openChat(parseInt(existingChatId), userName, 'private');
                    } else {
                        this.openPrivateChat(userId, userName);
                    }
                });
            });
            
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    // ============ ОТКРЫТИЕ ЧАТА ============
    async openPrivateChat(targetId, userName) {
        try {
            const data = await Utils.fetchAPI('chats.php?action=get_or_create_private', {
                method: 'POST',
                body: JSON.stringify({ user_id: targetId })
            });
            
            if (data && data.success) {
                this.openChat(data.chat_id, userName, 'private');
            }
        } catch (error) {
            console.error('Error opening private chat:', error);
        }
    }

    openChat(chatId, chatName, chatType) {
        this.currentChatId = chatId;
        this.currentChatType = chatType;
        this.replyToMessageId = null;

        document.getElementById('chat-header-name').textContent = chatName;
        document.getElementById('chat-header-role').textContent = this.getChatTypeLabel(chatType);

        const avatarContainer = document.getElementById('chat-header-avatar-container');
        if (avatarContainer) {
            avatarContainer.innerHTML = `
                <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:white;font-size:18px;font-weight:700;background:linear-gradient(135deg, #8b5cf6, #3b82f6);">
                    ${chatName.charAt(0).toUpperCase()}
                </div>
            `;
        }

        document.getElementById('chat-placeholder').style.display = 'none';
        document.getElementById('chat-messages').style.display = 'flex';
        document.getElementById('chat-header').style.display = 'flex';
        document.getElementById('chat-footer').style.display = 'block';
        document.getElementById('group-profile').style.display = 'none';
        document.getElementById('chat-profile').style.display = 'none';
        
        document.getElementById('file-preview').style.display = 'none';
        document.getElementById('reply-preview-container').style.display = 'none';

        const menuBtn = document.getElementById('chat-menu-btn');
        if (menuBtn) {
            menuBtn.style.display = ['group', 'class', 'subject'].includes(chatType) ? 'block' : 'none';
        }

        this.loadMessages(chatId);

        document.querySelectorAll('.chat-item, .dynamic-chat-item, .user-item').forEach(item => {
            item.style.background = 'transparent';
        });
        const activeItem = document.querySelector(`[data-chat-id="${chatId}"]`);
        if (activeItem) activeItem.style.background = '#eff6ff';

        document.getElementById('message-input').focus();
    }

    getChatTypeLabel(type) {
        const labels = {
            'private': 'Личный чат',
            'group': 'Группа',
            'class': 'Класс',
            'admin': 'Администрация',
            'support': 'Техподдержка',
            'favorite': 'Избранное',
            'subject': 'Предмет'
        };
        return labels[type] || 'Чат';
    }

    // ============ УПРАВЛЕНИЕ ЧАТОМ ============
    showChatInfo() {
        if (['group', 'class', 'subject'].includes(this.currentChatType)) {
            this.showChatManagement(this.currentChatId);
        } else {
            this.showUserProfile();
        }
    }

    async showUserProfile() {
        try {
            const data = await Utils.fetchAPI(`chats.php?action=info&chat_id=${this.currentChatId}`);
            if (!data || !data.success) return;
            
            const otherUser = data.participants.find(p => p.id != window.MTCONFIG.userId);
            if (!otherUser) return;
            
            document.getElementById('chat-profile').innerHTML = `
                <div style="text-align:center;padding:24px;">
                    <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#8b5cf6,#3b82f6);display:flex;align-items:center;justify-content:center;color:white;font-size:32px;font-weight:700;margin:0 auto;">
                        ${otherUser.name.charAt(0).toUpperCase()}
                    </div>
                    <h3 style="font-size:18px;font-weight:600;margin-top:12px;">${Utils.escapeHtml(otherUser.name)}</h3>
                    <p style="color:#64748b;">${otherUser.role}</p>
                    <p style="color:#10b981;margin-top:8px;">${otherUser.is_online ? '🟢 В сети' : '⚫ Не в сети'}</p>
                </div>
            `;
            document.getElementById('chat-profile').style.display = 'flex';
            document.getElementById('chat-messages').style.display = 'none';
        } catch (error) {
            console.error('Error loading profile:', error);
        }
    }

    async showChatManagement(chatId) {
        try {
            const data = await Utils.fetchAPI(`chat_manage.php?action=info&chat_id=${chatId}`);
            if (!data || !data.success) {
                alert('Не удалось загрузить информацию о чате');
                return;
            }
            
            const isOwner = data.my_role === 'owner';
            const isAdmin = data.my_role === 'admin' || isOwner;
            
            let html = `
                <div style="padding:20px;overflow-y:auto;height:100%;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h3 style="font-weight:700;font-size:18px;">${Utils.escapeHtml(data.chat.name)}</h3>
                        <button onclick="document.getElementById('group-profile').style.display='none';document.getElementById('chat-messages').style.display='flex';" style="font-size:24px;cursor:pointer;background:none;border:none;">✕</button>
                    </div>
                    
                    ${isAdmin ? `
                    <div style="background:#f8fafc;border-radius:12px;padding:12px;margin-bottom:16px;">
                        <h4 style="font-weight:600;margin-bottom:8px;">✏️ Редактировать чат</h4>
                        <input type="text" id="edit-chat-name" value="${Utils.escapeHtml(data.chat.name)}" placeholder="Название чата" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;">
                        <textarea id="edit-chat-desc" placeholder="Описание" style="width:100%;padding:8px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;resize:vertical;" rows="2">${Utils.escapeHtml(data.chat.description || '')}</textarea>
                        <button onclick="window._messenger.editChat(${chatId})" style="padding:8px 16px;background:#3b82f6;color:white;border:none;border-radius:8px;cursor:pointer;">Сохранить</button>
                    </div>
                    ` : ''}
                    
                    <div style="background:#f8fafc;border-radius:12px;padding:12px;margin-bottom:16px;">
                        <p style="font-size:13px;color:#64748b;">Тип: <strong>${data.chat.type}</strong></p>
                        <p style="font-size:13px;color:#64748b;">Ваша роль: <strong>${isOwner ? '👑 Создатель' : isAdmin ? '⭐ Администратор' : '👤 Участник'}</strong></p>
                        <p style="font-size:13px;color:#64748b;">Участников: <strong>${data.participants.length}</strong></p>
                    </div>
                    
                    ${isAdmin && data.available_users && data.available_users.length > 0 ? `
                    <div style="margin-bottom:16px;">
                        <h4 style="font-weight:600;margin-bottom:8px;">➕ Добавить участника</h4>
                        <div style="display:flex;gap:8px;">
                            <select id="add-member-select" style="flex:1;padding:8px;border:1px solid #e2e8f0;border-radius:8px;">
                                <option value="">Выберите пользователя...</option>
                                ${data.available_users.map(u => `<option value="${u.id}">${Utils.escapeHtml(u.name)} (${u.user_role})</option>`).join('')}
                            </select>
                            <button onclick="window._messenger.addMember(${chatId})" style="padding:8px 16px;background:#10b981;color:white;border:none;border-radius:8px;cursor:pointer;">Добавить</button>
                        </div>
                    </div>
                    ` : ''}
                    
                    <h4 style="font-weight:600;margin-bottom:8px;">👥 Участники</h4>
            `;
            
            data.participants.forEach(p => {
                const canRemove = isAdmin && p.chat_role !== 'owner' && p.id != window.MTCONFIG.userId;
                const canPromote = isOwner && p.chat_role === 'member' && p.id != window.MTCONFIG.userId;
                const canDemote = isOwner && p.chat_role === 'admin' && p.id != window.MTCONFIG.userId;
                
                html += `
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px;border-bottom:1px solid #f1f5f9;">
                        <div>
                            <span style="font-weight:500;">${Utils.escapeHtml(p.name)}</span>
                            <span style="font-size:12px;margin-left:4px;">${p.chat_role === 'owner' ? '👑' : p.chat_role === 'admin' ? '⭐' : ''}</span>
                            ${p.is_online ? '<span style="font-size:11px;color:#10b981;margin-left:4px;">● онлайн</span>' : ''}
                        </div>
                        <div style="display:flex;gap:4px;">
                            ${canPromote ? `<button onclick="window._messenger.changeRole(${chatId}, ${p.id}, 'admin')" style="color:#f59e0b;font-size:11px;background:none;border:none;cursor:pointer;" title="Назначить админом">⭐</button>` : ''}
                            ${canDemote ? `<button onclick="window._messenger.changeRole(${chatId}, ${p.id}, 'member')" style="color:#94a3b8;font-size:11px;background:none;border:none;cursor:pointer;" title="Понизить до участника">👤</button>` : ''}
                            ${canRemove ? `<button onclick="window._messenger.removeMember(${chatId}, ${p.id})" style="color:#ef4444;font-size:11px;background:none;border:none;cursor:pointer;" title="Удалить">✕</button>` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '<div style="margin-top:20px;display:flex;flex-direction:column;gap:8px;">';
            
            if (isOwner) {
                html += `<button onclick="window._messenger.deleteChat(${chatId})" style="padding:10px;background:#ef4444;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;">🗑️ Удалить чат</button>`;
            } else {
                html += `<button onclick="window._messenger.leaveChat(${chatId})" style="padding:10px;background:#f59e0b;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;">🚪 Покинуть чат</button>`;
            }
            
            html += '</div></div>';
            
            document.getElementById('group-profile').innerHTML = html;
            document.getElementById('group-profile').style.display = 'flex';
            document.getElementById('chat-messages').style.display = 'none';
            
        } catch (error) {
            console.error('Error loading chat info:', error);
            alert('Ошибка загрузки информации о чате');
        }
    }

    async editChat(chatId) {
        const name = document.getElementById('edit-chat-name')?.value?.trim();
        const description = document.getElementById('edit-chat-desc')?.value?.trim();
        
        if (!name) { alert('Введите название'); return; }
        
        await Utils.fetchAPI('chat_manage.php?action=edit_chat', {
            method: 'POST',
            body: JSON.stringify({ chat_id: chatId, name, description })
        });
        
        this.showChatManagement(chatId);
        this.loadChatList(this.activeTab);
    }

    async addMember(chatId) {
        const select = document.getElementById('add-member-select');
        const userId = select?.value;
        if (!userId) { alert('Выберите пользователя'); return; }
        
        await Utils.fetchAPI('chat_manage.php?action=add_member', {
            method: 'POST',
            body: JSON.stringify({ chat_id: chatId, user_id: parseInt(userId) })
        });
        
        this.showChatManagement(chatId);
    }

    async changeRole(chatId, userId, newRole) {
        const label = newRole === 'admin' ? 'Назначить администратором?' : 'Понизить до участника?';
        if (!confirm(label)) return;
        
        await Utils.fetchAPI('chat_manage.php?action=change_role', {
            method: 'POST',
            body: JSON.stringify({ chat_id: chatId, user_id: userId, new_role: newRole })
        });
        
        this.showChatManagement(chatId);
    }

    async removeMember(chatId, userId) {
        if (!confirm('Удалить участника из чата?')) return;
        try {
            await Utils.fetchAPI('chat_manage.php?action=remove_member', {
                method: 'POST',
                body: JSON.stringify({ chat_id: chatId, user_id: userId })
            });
            this.showChatManagement(chatId);
        } catch (error) {
            console.error('Error removing member:', error);
        }
    }

    async deleteChat(chatId) {
        if (!confirm('Удалить чат навсегда? Это действие нельзя отменить!')) return;
        try {
            await Utils.fetchAPI('chat_manage.php?action=delete_chat', {
                method: 'POST',
                body: JSON.stringify({ chat_id: chatId })
            });
            this.hideChat();
            this.loadChatList(this.activeTab);
        } catch (error) {
            console.error('Error deleting chat:', error);
        }
    }

    async leaveChat(chatId) {
        if (!confirm('Покинуть чат?')) return;
        try {
            await Utils.fetchAPI('chat_manage.php?action=leave_chat', {
                method: 'POST',
                body: JSON.stringify({ chat_id: chatId })
            });
            this.hideChat();
            this.loadChatList(this.activeTab);
        } catch (error) {
            console.error('Error leaving chat:', error);
        }
    }

    hideChat() {
        document.getElementById('group-profile').style.display = 'none';
        document.getElementById('chat-messages').style.display = 'none';
        document.getElementById('chat-header').style.display = 'none';
        document.getElementById('chat-footer').style.display = 'none';
        document.getElementById('chat-placeholder').style.display = 'flex';
        this.currentChatId = null;
    }

    // ============ СООБЩЕНИЯ ============
    async loadMessages(chatId) {
        try {
            const data = await Utils.fetchAPI(`messages.php?action=list&chat_id=${chatId}`);
            
            const container = document.getElementById('messages-container');
            container.innerHTML = '';

            if (!data || !data.success || !data.messages || data.messages.length === 0) {
                container.innerHTML = '<div style="text-align:center;color:#94a3b8;margin-top:40px;">Нет сообщений</div>';
                return;
            }

            data.messages.forEach(msg => this.displayMessage(msg));

            setTimeout(() => container.scrollTop = container.scrollHeight, 100);

            const msgIds = data.messages.map(m => m.id);
            this.markAsRead(chatId, msgIds);
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    displayMessage(message) {
        const container = document.getElementById('messages-container');
        const wrapper = document.createElement('div');
        
        const isSent = message.sender_id == window.MTCONFIG.userId;
        const isSystem = message.is_system == 1;
        
        if (isSystem) {
            wrapper.style.cssText = 'text-align:center;margin-bottom:8px;';
            wrapper.innerHTML = `<span style="font-size:11px;color:#94a3b8;background:#f1f5f9;padding:4px 12px;border-radius:12px;">${Utils.escapeHtml(message.message_text || '')}</span>`;
            container.appendChild(wrapper);
            return;
        }
        
        wrapper.style.cssText = `display:flex;${isSent ? 'justify-content:flex-end;' : 'justify-content:flex-start;'}margin-bottom:12px;`;
        wrapper.dataset.messageId = message.id;

        const time = Utils.formatMessageTime(message.created_at);
        const readIcon = isSent ? (message.read_by && message.read_by.length > 0 ? '✓✓' : '✓') : '';

        const bubbleStyle = isSent 
            ? 'background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;border-bottom-right-radius:4px;'
            : 'background:#fff;border:1px solid #e2e8f0;border-bottom-left-radius:4px;';

        const timeColor = isSent ? 'color:#bfdbfe;' : 'color:#94a3b8;';

        let fileHtml = '';
        if (message.file_path) {
            if (message.file_mime_type && message.file_mime_type.startsWith('image/')) {
                fileHtml = `<img src="${message.file_path}" style="max-width:200px;border-radius:8px;margin-top:6px;cursor:pointer;" onclick="window.open('${message.file_path}')" loading="lazy">`;
            } else {
                fileHtml = `
                    <div style="margin-top:6px;background:rgba(255,255,255,0.9);border-radius:8px;padding:6px 10px;display:flex;align-items:center;gap:8px;cursor:pointer;" onclick="window.open('${message.file_path}')">
                        <span>📎</span>
                        <span style="font-size:12px;color:#1e293b;">${Utils.escapeHtml(message.file_name || 'Файл')}</span>
                    </div>`;
            }
        }

        wrapper.innerHTML = `
            <div style="max-width:70%;">
                ${!isSent && message.sender_name ? `<div style="font-size:11px;color:#64748b;margin-bottom:3px;">${Utils.escapeHtml(message.sender_name)}</div>` : ''}
                <div style="padding:8px 14px;border-radius:16px;${bubbleStyle}">
                    <div style="font-size:14px;line-height:1.4;word-wrap:break-word;">${Utils.escapeHtml(message.message_text || '')}</div>
                    ${fileHtml}
                    <div style="display:flex;align-items:center;justify-content:flex-end;margin-top:4px;gap:4px;">
                        <span style="font-size:10px;${timeColor}">${time}</span>
                        ${readIcon ? `<span style="font-size:11px;${isSent && message.read_by && message.read_by.length > 0 ? 'color:#60a5fa;' : 'color:#94a3b8;'}">${readIcon}</span>` : ''}
                    </div>
                </div>
            </div>
        `;

        wrapper.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.showContextMenu(e, message);
        });

        container.appendChild(wrapper);
    }

    showContextMenu(e, message) {
        const existing = document.querySelector('.context-menu');
        if (existing) existing.remove();

        const menu = document.createElement('div');
        menu.style.cssText = `position:fixed;z-index:1000;background:#fff;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.15);padding:4px;min-width:160px;left:${e.clientX}px;top:${e.clientY}px;`;
        
        const items = [
            { label: '📋 Копировать', action: () => {
                navigator.clipboard.writeText(message.message_text || '');
            }}
        ];
        
        if (message.sender_id == window.MTCONFIG.userId) {
            items.push({ 
                label: '🗑️ Удалить', 
                action: () => this.deleteMessage(message), 
                danger: true 
            });
        }

        items.forEach(item => {
            const div = document.createElement('div');
            div.style.cssText = `padding:8px 12px;border-radius:6px;cursor:pointer;font-size:13px;color:${item.danger ? '#ef4444' : '#374151'};`;
            div.textContent = item.label;
            
            div.addEventListener('mouseover', () => {
                div.style.background = item.danger ? '#fef2f2' : '#f3f4f6';
            });
            div.addEventListener('mouseout', () => {
                div.style.background = 'transparent';
            });
            div.addEventListener('click', () => {
                item.action();
                menu.remove();
            });
            menu.appendChild(div);
        });

        document.body.appendChild(menu);
        
        const closeMenu = () => {
            menu.remove();
            document.removeEventListener('click', closeMenu);
        };
        setTimeout(() => document.addEventListener('click', closeMenu), 0);
    }

    async sendMessage() {
        const input = document.getElementById('message-input');
        const text = input.value.trim();
        
        if (!text && !this.pendingFile) return;
        if (!this.currentChatId) return;

        const formData = new FormData();
        formData.append('chat_id', this.currentChatId);
        formData.append('text', text);
        if (this.pendingFile) formData.append('file', this.pendingFile);

        const sendBtn = document.getElementById('send-button');
        sendBtn.disabled = true;

        try {
            const data = await Utils.fetchAPI('messages.php?action=send', { method: 'POST', body: formData });
            
            if (data && data.success) {
                input.value = '';
                input.style.height = 'auto';
                this.pendingFile = null;
                document.getElementById('file-preview').style.display = 'none';
                
                this.displayMessage(data.message);
                
                const container = document.getElementById('messages-container');
                setTimeout(() => container.scrollTop = container.scrollHeight, 100);
            }
        } catch (error) {
            console.error('Send error:', error);
        } finally {
            sendBtn.disabled = false;
            input.focus();
        }
    }

    async deleteMessage(message) {
        if (!confirm('Удалить сообщение?')) return;
        await Utils.fetchAPI('messages.php?action=delete', { 
            method: 'POST', 
            body: JSON.stringify({ message_id: message.id }) 
        });
        this.loadMessages(this.currentChatId);
    }

    async markAsRead(chatId, messageIds) {
        try {
            await Utils.fetchAPI('messages.php?action=read', {
                method: 'POST',
                body: JSON.stringify({ chat_id: chatId, message_ids: messageIds })
            });
        } catch (error) {}
    }
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    window._messenger = new MessengerApp();
});