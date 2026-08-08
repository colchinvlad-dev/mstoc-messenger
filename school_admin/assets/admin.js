/**
 * Панель администратора школы - Полный код
 */
class SchoolAdmin {
    constructor(config) {
        this.schoolId = config.schoolId;
        this.isSuperAdmin = config.isSuperAdmin;
        this.currentSection = 'dashboard';
        this.apiBase = '/school_admin/api/';
        this.init();
    }

    init() {
        this.initNavigation();
        this.loadDashboard();
        
        // Делегирование событий
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-action]');
            if (!target) return;
            
            const action = target.dataset.action;
            const id = target.dataset.id;
            const role = target.dataset.role;
            
            switch (action) {
                case 'toggle-user': this.toggleUser(id); break;
                case 'delete-user': this.deleteUser(id); break;
                case 'delete-class': this.deleteClass(id); break;
                case 'edit-class': this.showClassModal(id); break;
                case 'delete-chat': this.deleteChat(id); break;
                case 'edit-chat': this.showEditChatModal(id); break;
                case 'delete-invitation': this.deleteInvitation(id); break;
                case 'copy-code': navigator.clipboard.writeText(target.dataset.code); alert('Код скопирован'); break;
                case 'show-user-modal': this.showUserModal(role); break;
                case 'show-class-modal': this.showClassModal(); break;
                case 'show-chat-modal': this.showChatModal(); break;
                case 'show-invitation-modal': this.showInvitationModal(); break;
            }
        });
    }

    initNavigation() {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.nav-item').forEach(i => {
                    i.classList.remove('active', 'text-white', 'bg-gradient-to-r', 'from-emerald-500', 'to-teal-600');
                    i.classList.add('text-gray-600');
                });
                item.classList.add('active', 'text-white', 'bg-gradient-to-r', 'from-emerald-500', 'to-teal-600');
                item.classList.remove('text-gray-600');
                this.loadSection(item.dataset.section);
            });
        });
    }

    async loadSection(section) {
        this.currentSection = section;
        switch (section) {
            case 'dashboard': await this.loadDashboard(); break;
            case 'teachers': await this.loadUsers('teacher'); break;
            case 'students': await this.loadUsers('student'); break;
            case 'classes': await this.loadClasses(); break;
            case 'chats': await this.loadChats(); break;
            case 'invitations': await this.loadInvitations(); break;
        }
    }

    async loadDashboard() {
        try {
            const stats = await this.fetchAPI('statistics.php');
            document.getElementById('stat-teachers').textContent = stats.teachers || 0;
            document.getElementById('stat-students').textContent = stats.students || 0;
            document.getElementById('stat-classes').textContent = stats.classes || 0;
            document.getElementById('stat-chats').textContent = stats.chats || 0;
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    async loadUsers(role) {
        try {
            const users = await this.fetchAPI(`users.php?action=list&role=${role}&school_id=${this.schoolId}`);
            const title = role === 'teacher' ? 'Учителя' : 'Ученики';
            
            document.getElementById('section-content').innerHTML = `
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold">${title} (${users.length})</h2>
                        <button data-action="show-user-modal" data-role="${role}" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg cursor-pointer">
                            + Добавить ${role === 'teacher' ? 'учителя' : 'ученика'}
                        </button>
                    </div>
                    <div class="p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ФИО</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    ${role === 'student' ? '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Класс</th>' : ''}
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${users.map(u => `
                                    <tr class="border-t">
                                        <td class="px-6 py-4">${u.id}</td>
                                        <td class="px-6 py-4">${this.esc(u.name)}</td>
                                        <td class="px-6 py-4">${this.esc(u.email)}</td>
                                        ${role === 'student' ? `<td class="px-6 py-4">${this.esc(u.class_name || '-')}</td>` : ''}
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${u.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                ${u.is_active ? 'Активен' : 'Заблокирован'}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 space-x-2">
                                            <button class="text-orange-600 hover:text-orange-900 cursor-pointer" data-action="toggle-user" data-id="${u.id}">
                                                ${u.is_active ? 'Забл.' : 'Разбл.'}
                                            </button>
                                            <button class="text-red-600 hover:text-red-900 cursor-pointer" data-action="delete-user" data-id="${u.id}">
                                                Удал.
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading users:', error);
        }
    }

    async loadClasses() {
        try {
            const classes = await this.fetchAPI(`classes.php?action=list&school_id=${this.schoolId}`);
            
            document.getElementById('section-content').innerHTML = `
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold">Классы (${classes.length})</h2>
                        <button data-action="show-class-modal" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg cursor-pointer">
                            + Добавить класс
                        </button>
                    </div>
                    <div class="p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Класс</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Руководитель</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Учеников</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Чат</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${classes.map(c => `
                                    <tr class="border-t">
                                        <td class="px-6 py-4">${c.id}</td>
                                        <td class="px-6 py-4 font-medium">${c.grade}${c.letter} ${this.esc(c.name || '')}</td>
                                        <td class="px-6 py-4">${this.esc(c.teacher_name || 'Не назначен')}</td>
                                        <td class="px-6 py-4">${c.students_count || 0}</td>
                                        <td class="px-6 py-4">${c.chat_id ? '✅' : '❌'}</td>
                                        <td class="px-6 py-4 space-x-2">
                                            <button class="text-blue-600 hover:text-blue-900 cursor-pointer" data-action="edit-class" data-id="${c.id}">Ред.</button>
                                            <button class="text-red-600 hover:text-red-900 cursor-pointer" data-action="delete-class" data-id="${c.id}">Удал.</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading classes:', error);
        }
    }

    async loadChats() {
        try {
            const chats = await this.fetchAPI(`chats.php?action=list&school_id=${this.schoolId}`);
            
            document.getElementById('section-content').innerHTML = `
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold">Чаты школы (${chats.length})</h2>
                        <button data-action="show-chat-modal" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg cursor-pointer">
                            + Создать чат
                        </button>
                    </div>
                    <div class="p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Участников</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${chats.map(c => `
                                    <tr class="border-t">
                                        <td class="px-6 py-4">${c.id}</td>
                                        <td class="px-6 py-4">${this.esc(c.name || 'Без названия')}</td>
                                        <td class="px-6 py-4">${this.getChatTypeLabel(c.type)}</td>
                                        <td class="px-6 py-4">${c.participants_count || 0}</td>
                                        <td class="px-6 py-4 space-x-2">
                                            <button class="text-blue-600 hover:text-blue-900 cursor-pointer" data-action="edit-chat" data-id="${c.id}">Ред.</button>
                                            <button class="text-red-600 hover:text-red-900 cursor-pointer" data-action="delete-chat" data-id="${c.id}">Удал.</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading chats:', error);
        }
    }

    async loadInvitations() {
        try {
            const invitations = await this.fetchAPI(`invitations.php?action=list&school_id=${this.schoolId}`);
            
            document.getElementById('section-content').innerHTML = `
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold">Приглашения (${invitations.length})</h2>
                        <button data-action="show-invitation-modal" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-lg cursor-pointer">
                            + Создать
                        </button>
                    </div>
                    <div class="p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Код</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Тип</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Класс</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Исп.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${invitations.map(inv => `
                                    <tr class="border-t">
                                        <td class="px-6 py-4 font-mono">${inv.code}</td>
                                        <td class="px-6 py-4">${this.getUserTypeLabel(inv.user_type)}</td>
                                        <td class="px-6 py-4">${this.esc(inv.class_name || '-')}</td>
                                        <td class="px-6 py-4">${inv.used_count}/${inv.max_uses || '∞'}</td>
                                        <td class="px-6 py-4">${inv.is_active ? '✅' : '❌'}</td>
                                        <td class="px-6 py-4 space-x-2">
                                            <button class="text-blue-600 hover:text-blue-900 cursor-pointer" data-action="copy-code" data-code="${inv.code}">Коп.</button>
                                            <button class="text-red-600 hover:text-red-900 cursor-pointer" data-action="delete-invitation" data-id="${inv.id}">Удал.</button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading invitations:', error);
        }
    }

    // ============ МОДАЛЬНЫЕ ОКНА ============
    
    showUserModal(role) {
        const title = role === 'teacher' ? 'Добавить учителя' : 'Добавить ученика';
        this.createModal(title, `
            <form id="user-form" class="space-y-4">
                <input type="text" name="name" placeholder="ФИО" required class="w-full px-3 py-2 border rounded-lg">
                <input type="email" name="email" placeholder="Email" required class="w-full px-3 py-2 border rounded-lg">
                <input type="password" name="password" placeholder="Пароль" required class="w-full px-3 py-2 border rounded-lg">
                ${role === 'student' ? '<select name="class_id" class="w-full px-3 py-2 border rounded-lg"><option value="">Без класса</option></select>' : ''}
            </form>
        `, async () => {
            const form = document.getElementById('user-form');
            const data = Object.fromEntries(new FormData(form));
            data.role = role;
            data.school_id = this.schoolId;
            await this.fetchAPI('users.php?action=create', { method: 'POST', body: JSON.stringify(data) });
            this.closeModal();
            this.loadUsers(role);
        });
        if (role === 'student') this.loadClassesForSelect();
    }

    async showClassModal(classId = null) {
        const isEdit = classId !== null;
        const title = isEdit ? 'Редактировать класс' : 'Добавить класс';
        
        let classData = null;
        if (isEdit) {
            const resp = await this.fetchAPI(`classes.php?action=get&id=${classId}`);
            if (resp.success) classData = resp;
        }
        
        this.createModal(title, `
            <form id="class-form" class="space-y-4" style="max-height:70vh;overflow-y:auto;">
                <div style="display:flex;gap:8px;">
                    <input type="number" name="grade" value="${classData?.class?.grade || ''}" placeholder="Класс (1-11)" required min="1" max="11" style="width:100px;" class="px-3 py-2 border rounded-lg">
                    <input type="text" name="letter" value="${classData?.class?.letter || ''}" placeholder="Буква" required maxlength="5" style="width:100px;" class="px-3 py-2 border rounded-lg">
                </div>
                <input type="text" name="name" value="${classData?.class?.name || ''}" placeholder="Название класса" class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="room_number" value="${classData?.class?.room_number || ''}" placeholder="Номер кабинета" class="w-full px-3 py-2 border rounded-lg">
                
                <div>
                    <label style="font-size:13px;font-weight:500;">Классный руководитель</label>
                    <select name="teacher_id" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Не назначен</option>
                    </select>
                </div>
                
                <div style="border-top:1px solid #e2e8f0;padding-top:12px;">
                    <h4 style="font-weight:600;margin-bottom:8px;">💬 Чат класса</h4>
                    <input type="text" name="chat_name" value="${classData?.class?.chat_name || `Класс ${classData?.class?.grade || ''}${classData?.class?.letter || ''}`}" placeholder="Название чата" class="w-full px-3 py-2 border rounded-lg">
                    <textarea name="chat_description" placeholder="Описание чата" class="w-full px-3 py-2 border rounded-lg mt-2" rows="2">${classData?.class?.description || ''}</textarea>
                    
                    <div class="mt-2">
                        <label style="font-size:13px;font-weight:500;">Администратор чата</label>
                        <select name="chat_admin_id" class="w-full px-3 py-2 border rounded-lg">
                            <option value="">Не назначен</option>
                        </select>
                    </div>
                </div>
            </form>
        `, async () => {
            const form = document.getElementById('class-form');
            const data = Object.fromEntries(new FormData(form));
            data.school_id = this.schoolId;
            if (isEdit) data.class_id = classId;
            
            await this.fetchAPI(`classes.php?action=${isEdit ? 'update' : 'create'}`, { method: 'POST', body: JSON.stringify(data) });
            this.closeModal();
            this.loadClasses();
        });
        
        // Загружаем учителей в select'ы
        setTimeout(async () => {
            const teachers = await this.fetchAPI(`users.php?action=list&role=teacher&school_id=${this.schoolId}`);
            document.querySelectorAll('select[name="teacher_id"], select[name="chat_admin_id"]').forEach(select => {
                select.innerHTML = '<option value="">Не назначен</option>' + 
                    teachers.map(t => `<option value="${t.id}" ${t.id == (classData?.class?.teacher_id || '') ? 'selected' : ''}>${t.name}</option>`).join('');
            });
        }, 100);
    }

    showChatModal() {
        this.createModal('Создать чат', `
            <form id="chat-form" class="space-y-4" style="max-height:70vh;overflow-y:auto;">
                <input type="text" name="name" placeholder="Название чата" required class="w-full px-3 py-2 border rounded-lg">
                <select name="type" class="w-full px-3 py-2 border rounded-lg">
                    <option value="group">Групповой чат</option>
                    <option value="subject">Чат предмета</option>
                </select>
                <textarea name="description" placeholder="Описание" class="w-full px-3 py-2 border rounded-lg" rows="2"></textarea>
                
                <div>
                    <label style="font-size:13px;font-weight:500;">Администратор чата</label>
                    <select name="admin_id" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Не назначен</option>
                    </select>
                </div>
                
                <div>
                    <label style="font-size:13px;font-weight:500;">Добавить участников</label>
                    <select name="participants" class="w-full px-3 py-2 border rounded-lg" multiple size="5">
                    </select>
                    <small style="color:#94a3b8;">Удерживайте Ctrl для выбора нескольких</small>
                </div>
            </form>
        `, async () => {
            const form = document.getElementById('chat-form');
            const data = Object.fromEntries(new FormData(form));
            data.school_id = this.schoolId;
            
            // Собираем выбранных участников
            const participantsSelect = form.querySelector('select[name="participants"]');
            const selectedParticipants = Array.from(participantsSelect.selectedOptions).map(o => o.value);
            data.participants = JSON.stringify(selectedParticipants);
            
            await this.fetchAPI('chats.php?action=create', { method: 'POST', body: JSON.stringify(data) });
            this.closeModal();
            this.loadChats();
        });
        
        // Загружаем учителей и пользователей
        setTimeout(async () => {
            const teachers = await this.fetchAPI(`users.php?action=list&role=teacher&school_id=${this.schoolId}`);
            const students = await this.fetchAPI(`users.php?action=list&role=student&school_id=${this.schoolId}`);
            const allUsers = [...teachers, ...students];
            
            const adminSelect = document.querySelector('select[name="admin_id"]');
            if (adminSelect) {
                adminSelect.innerHTML = '<option value="">Не назначен</option>' + 
                    teachers.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
            }
            
            const participantsSelect = document.querySelector('select[name="participants"]');
            if (participantsSelect) {
                participantsSelect.innerHTML = allUsers.map(u => 
                    `<option value="${u.id}">${u.name} (${u.role})</option>`
                ).join('');
            }
        }, 100);
    }

    async showEditChatModal(chatId) {
        const data = await this.fetchAPI(`chats.php?action=get&id=${chatId}`);
        if (!data || !data.success) return;
        
        this.createModal('Редактировать чат', `
            <form id="edit-chat-form" class="space-y-4" style="max-height:70vh;overflow-y:auto;">
                <input type="text" name="name" value="${this.esc(data.chat.name || '')}" placeholder="Название чата" required class="w-full px-3 py-2 border rounded-lg">
                <textarea name="description" placeholder="Описание" class="w-full px-3 py-2 border rounded-lg" rows="2">${this.esc(data.chat.description || '')}</textarea>
                
                <div>
                    <label style="font-size:13px;font-weight:500;">Администраторы (${data.participants.filter(p => p.chat_role === 'admin').length})</label>
                    <select name="add_admin" class="w-full px-3 py-2 border rounded-lg mt-1">
                        <option value="">Добавить администратора...</option>
                        ${data.available_users?.map(u => `<option value="${u.id}">${u.name}</option>`).join('') || ''}
                    </select>
                </div>
                
                <div>
                    <label style="font-size:13px;font-weight:500;">Добавить участников</label>
                    <select name="add_participants" class="w-full px-3 py-2 border rounded-lg" multiple size="5">
                        ${data.available_users?.map(u => `<option value="${u.id}">${u.name} (${u.role})</option>`).join('') || ''}
                    </select>
                </div>
                
                <div>
                    <label style="font-size:13px;font-weight:500;">Участники (${data.participants.length})</label>
                    <div style="max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:8px;">
                        ${data.participants.map(p => `
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:13px;">
                                <span>${this.esc(p.name)} ${p.chat_role === 'owner' ? '👑' : p.chat_role === 'admin' ? '⭐' : ''}</span>
                                ${p.chat_role !== 'owner' ? `<span style="color:#ef4444;cursor:pointer;" data-remove-from-chat="${p.id}">✕</span>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            </form>
        `, async () => {
            const form = document.getElementById('edit-chat-form');
            const formData = Object.fromEntries(new FormData(form));
            
            // Обновляем название и описание
            await this.fetchAPI('chat_manage.php?action=edit_chat', {
                method: 'POST',
                body: JSON.stringify({ chat_id: chatId, name: formData.name, description: formData.description })
            });
            
            // Добавляем админа
            if (formData.add_admin) {
                await this.fetchAPI('chat_manage.php?action=add_member', {
                    method: 'POST',
                    body: JSON.stringify({ chat_id: chatId, user_id: parseInt(formData.add_admin) })
                });
                await this.fetchAPI('chat_manage.php?action=change_role', {
                    method: 'POST',
                    body: JSON.stringify({ chat_id: chatId, user_id: parseInt(formData.add_admin), new_role: 'admin' })
                });
            }
            
            // Добавляем участников
            const participantsSelect = form.querySelector('select[name="add_participants"]');
            const selectedIds = Array.from(participantsSelect.selectedOptions).map(o => o.value);
            for (const uid of selectedIds) {
                await this.fetchAPI('chat_manage.php?action=add_member', {
                    method: 'POST',
                    body: JSON.stringify({ chat_id: chatId, user_id: parseInt(uid) })
                });
            }
            
            this.closeModal();
            this.loadChats();
        });
        
        // Обработчики удаления участников
        setTimeout(() => {
            document.querySelectorAll('[data-remove-from-chat]').forEach(el => {
                el.addEventListener('click', async () => {
                    const uid = el.dataset.removeFromChat;
                    await this.fetchAPI('chat_manage.php?action=remove_member', {
                        method: 'POST',
                        body: JSON.stringify({ chat_id: chatId, user_id: parseInt(uid) })
                    });
                    this.closeModal();
                    this.showEditChatModal(chatId);
                });
            });
        }, 200);
    }

    showInvitationModal() {
        this.createModal('Создать приглашение', `
            <form id="invitation-form" class="space-y-4">
                <select name="user_type" class="w-full px-3 py-2 border rounded-lg">
                    <option value="teacher">Учитель</option>
                    <option value="student">Ученик</option>
                </select>
                <select name="class_id" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Любой класс</option>
                </select>
                <input type="number" name="max_uses" value="1" min="0" placeholder="Использований (0=безлимит)" class="w-full px-3 py-2 border rounded-lg">
            </form>
        `, async () => {
            const form = document.getElementById('invitation-form');
            const data = Object.fromEntries(new FormData(form));
            data.school_id = this.schoolId;
            
            const result = await this.fetchAPI('invitations.php?action=create', { method: 'POST', body: JSON.stringify(data) });
            if (result.success) {
                this.closeModal();
                this.loadInvitations();
                alert('Код: ' + result.code);
            }
        });
        this.loadClassesForSelect();
    }

    createModal(title, formHtml, onSave) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.innerHTML = `
            <div class="modal-content p-6" style="max-width:600px;">
                <h3 class="text-lg font-bold mb-4">${title}</h3>
                ${formHtml}
                <div class="flex justify-end gap-3 mt-6">
                    <button class="cancel-btn px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg cursor-pointer">Отмена</button>
                    <button class="save-btn px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 cursor-pointer">Сохранить</button>
                </div>
            </div>
        `;
        overlay.querySelector('.cancel-btn').addEventListener('click', () => this.closeModal());
        overlay.querySelector('.save-btn').addEventListener('click', onSave);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) this.closeModal(); });
        document.body.appendChild(overlay);
    }

    closeModal() {
        const modal = document.querySelector('.modal-overlay');
        if (modal) modal.remove();
    }

    async loadClassesForSelect() {
        try {
            const classes = await this.fetchAPI(`classes.php?action=list&school_id=${this.schoolId}`);
            document.querySelectorAll('select[name="class_id"]').forEach(select => {
                select.innerHTML = '<option value="">Выберите</option>' + 
                    classes.map(c => `<option value="${c.id}">${c.grade}${c.letter} ${this.esc(c.name || '')}</option>`).join('');
            });
        } catch (error) {}
    }

    // ============ ДЕЙСТВИЯ ============
    async toggleUser(id) { await this.fetchAPI('users.php?action=toggle', { method: 'POST', body: JSON.stringify({ id }) }); this.loadSection(this.currentSection); }
    async deleteUser(id) { if (!confirm('Удалить?')) return; await this.fetchAPI('users.php?action=delete', { method: 'POST', body: JSON.stringify({ id }) }); this.loadSection(this.currentSection); }
    async deleteClass(id) { if (!confirm('Удалить класс и его чат?')) return; await this.fetchAPI('classes.php?action=delete', { method: 'POST', body: JSON.stringify({ id }) }); this.loadClasses(); }
    async deleteChat(id) { if (!confirm('Удалить чат?')) return; await this.fetchAPI('chats.php?action=delete', { method: 'POST', body: JSON.stringify({ id }) }); this.loadChats(); }
    async deleteInvitation(id) { if (!confirm('Удалить?')) return; await this.fetchAPI('invitations.php?action=delete', { method: 'POST', body: JSON.stringify({ id }) }); this.loadInvitations(); }

    getChatTypeLabel(type) {
        const labels = { 'private': 'Личный', 'group': 'Группа', 'class': 'Класс', 'subject': 'Предмет', 'admin': 'Админ.', 'support': 'Поддержка', 'favorite': 'Избранное' };
        return labels[type] || type;
    }
    getUserTypeLabel(type) {
        const labels = { 'teacher': 'Учитель', 'student': 'Ученик', 'parent': 'Родитель' };
        return labels[type] || type;
    }
    esc(text) {
        if (!text) return '';
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    async fetchAPI(url, options = {}) {
        try {
            const response = await fetch(this.apiBase + url, { headers: { 'Content-Type': 'application/json' }, ...options });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch (error) { console.error('API Error:', error); throw error; }
    }
}