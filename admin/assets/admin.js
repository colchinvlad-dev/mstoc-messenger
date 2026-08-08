/**
 * Панель супер-администратора
 */
class AdminPanel {
    constructor() {
        this.currentSection = 'dashboard';
        this.apiBase = '/admin/api/';
        this.init();
    }

    init() {
        this.initNavigation();
        this.loadDashboard();
        
        // Делегирование событий для таблиц
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-action]');
            if (!target) return;
            
            const action = target.dataset.action;
            const id = target.dataset.id;
            
            switch (action) {
                case 'edit-school':
                    this.showEditSchoolModal(
                        target.dataset.id,
                        target.dataset.name,
                        target.dataset.number,
                        target.dataset.address || '',
                        target.dataset.email || '',
                        target.dataset.phone || ''
                    );
                    break;
                case 'toggle-school':
                    this.toggleSchool(target.dataset.id);
                    break;
                case 'delete-school':
                    this.deleteSchool(target.dataset.id);
                    break;
                case 'delete-admin':
                    this.deleteAdmin(target.dataset.id);
                    break;
            }
        });
    }

    initNavigation() {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.nav-item').forEach(i => {
                    i.classList.remove('active', 'text-white', 'bg-gradient-to-r', 'from-blue-500', 'to-blue-600');
                    i.classList.add('text-gray-600');
                });
                item.classList.add('active', 'text-white', 'bg-gradient-to-r', 'from-blue-500', 'to-blue-600');
                item.classList.remove('text-gray-600');
                this.loadSection(item.dataset.section);
            });
        });
    }

    async loadSection(section) {
        this.currentSection = section;
        switch (section) {
            case 'dashboard': await this.loadDashboard(); break;
            case 'schools': await this.loadSchools(); break;
            case 'admins': await this.loadAdmins(); break;
            case 'statistics': await this.loadStatistics(); break;
        }
    }

    async loadDashboard() {
        try {
            const stats = await this.fetchAPI('statistics.php');
            document.getElementById('stat-schools').textContent = stats.total_schools || 0;
            document.getElementById('stat-admins').textContent = stats.total_admins || 0;
            document.getElementById('stat-users').textContent = stats.total_users || 0;
            document.getElementById('stat-messages').textContent = stats.messages_today || 0;
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    async loadSchools() {
        try {
            const schools = await this.fetchAPI('schools.php?action=list');
            const container = document.getElementById('section-content');
            container.innerHTML = `
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold">Управление школами</h2>
                        <button onclick="adminPanel.showSchoolModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">+ Добавить школу</button>
                    </div>
                    <div class="p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Номер</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Адрес</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${schools.map(s => `
                                    <tr class="border-t">
                                        <td class="px-6 py-4">${s.id}</td>
                                        <td class="px-6 py-4">${s.number}</td>
                                        <td class="px-6 py-4">${this.escapeHtml(s.name)}</td>
                                        <td class="px-6 py-4 text-gray-500">${this.escapeHtml(s.address || '-')}</td>
                                        <td class="px-6 py-4 text-gray-500">${this.escapeHtml(s.email || '-')}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${s.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                ${s.is_active ? 'Активна' : 'Неактивна'}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 space-x-2">
                                            <button class="text-blue-600 hover:text-blue-900" 
                                                data-action="edit-school"
                                                data-id="${s.id}"
                                                data-name="${this.escapeHtml(s.name)}"
                                                data-number="${s.number}"
                                                data-address="${this.escapeAttr(s.address || '')}"
                                                data-email="${this.escapeAttr(s.email || '')}"
                                                data-phone="${this.escapeAttr(s.phone || '')}">
                                                Ред.
                                            </button>
                                            <button class="text-orange-600 hover:text-orange-900" 
                                                data-action="toggle-school"
                                                data-id="${s.id}">
                                                ${s.is_active ? 'Откл.' : 'Вкл.'}
                                            </button>
                                            <button class="text-red-600 hover:text-red-900" 
                                                data-action="delete-school"
                                                data-id="${s.id}">
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
            console.error('Error loading schools:', error);
            document.getElementById('section-content').innerHTML = `<div class="bg-red-50 p-4 rounded-lg text-red-600">Ошибка загрузки: ${error.message}</div>`;
        }
    }

    async loadAdmins() {
        try {
            const admins = await this.fetchAPI('schools.php?action=admins');
            const container = document.getElementById('section-content');
            container.innerHTML = `
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold">Управление администраторами</h2>
                        <button onclick="adminPanel.showAdminModal()" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg">+ Добавить администратора</button>
                    </div>
                    <div class="p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ФИО</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Школа</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Статус</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${admins.map(a => `
                                    <tr class="border-t">
                                        <td class="px-6 py-4">${a.id}</td>
                                        <td class="px-6 py-4">${this.escapeHtml(a.name)}</td>
                                        <td class="px-6 py-4">${this.escapeHtml(a.email)}</td>
                                        <td class="px-6 py-4">${this.escapeHtml(a.school_name || 'Все школы')}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${a.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                ${a.is_active ? 'Активен' : 'Неактивен'}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 space-x-2">
                                            <button class="text-red-600 hover:text-red-900" 
                                                data-action="delete-admin"
                                                data-id="${a.id}">
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
            console.error('Error loading admins:', error);
        }
    }

    async loadStatistics() {
        try {
            const stats = await this.fetchAPI('statistics.php?action=detailed');
            const container = document.getElementById('section-content');
            container.innerHTML = `
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Детальная статистика</h2>
                    <div class="bg-gray-50 p-4 rounded-lg overflow-auto">
                        <pre class="text-sm">${JSON.stringify(stats, null, 2)}</pre>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    showSchoolModal() {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'school-modal';
        overlay.innerHTML = `
            <div class="modal-content p-6">
                <h3 class="text-lg font-bold mb-4">Добавить школу</h3>
                <form id="school-form" class="space-y-4">
                    <input type="text" name="name" placeholder="Название школы" required class="w-full px-3 py-2 border rounded-lg">
                    <input type="number" name="number" placeholder="Номер школы" required class="w-full px-3 py-2 border rounded-lg">
                    <textarea name="address" placeholder="Адрес" class="w-full px-3 py-2 border rounded-lg"></textarea>
                    <input type="email" name="email" placeholder="Email школы" class="w-full px-3 py-2 border rounded-lg">
                    <input type="tel" name="phone" placeholder="Телефон" class="w-full px-3 py-2 border rounded-lg">
                </form>
                <div class="flex justify-end gap-3 mt-4">
                    <button onclick="adminPanel.closeModal('school-modal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Отмена</button>
                    <button onclick="adminPanel.saveSchool()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Сохранить</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) this.closeModal('school-modal');
        });
    }

    showEditSchoolModal(id, name, number, address, email, phone) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'edit-school-modal';
        overlay.innerHTML = `
            <div class="modal-content p-6">
                <h3 class="text-lg font-bold mb-4">Редактировать школу #${id}</h3>
                <form id="edit-school-form" class="space-y-4">
                    <input type="hidden" name="id" value="${id}">
                    <input type="text" name="name" value="${this.escapeHtml(name)}" placeholder="Название школы" required class="w-full px-3 py-2 border rounded-lg">
                    <input type="number" name="number" value="${number}" placeholder="Номер школы" required class="w-full px-3 py-2 border rounded-lg">
                    <textarea name="address" placeholder="Адрес" class="w-full px-3 py-2 border rounded-lg">${this.escapeHtml(address)}</textarea>
                    <input type="email" name="email" value="${this.escapeHtml(email)}" placeholder="Email школы" class="w-full px-3 py-2 border rounded-lg">
                    <input type="tel" name="phone" value="${this.escapeHtml(phone)}" placeholder="Телефон" class="w-full px-3 py-2 border rounded-lg">
                </form>
                <div class="flex justify-end gap-3 mt-4">
                    <button onclick="adminPanel.closeModal('edit-school-modal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Отмена</button>
                    <button onclick="adminPanel.updateSchool()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Обновить</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) this.closeModal('edit-school-modal');
        });
    }

    showAdminModal() {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'admin-modal';
        overlay.innerHTML = `
            <div class="modal-content p-6">
                <h3 class="text-lg font-bold mb-4">Добавить администратора</h3>
                <form id="admin-form" class="space-y-4">
                    <input type="text" name="name" placeholder="ФИО" required class="w-full px-3 py-2 border rounded-lg">
                    <input type="email" name="email" placeholder="Email" required class="w-full px-3 py-2 border rounded-lg">
                    <input type="password" name="password" placeholder="Пароль" required class="w-full px-3 py-2 border rounded-lg">
                    <select name="school_id" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Все школы (супер-админ)</option>
                    </select>
                </form>
                <div class="flex justify-end gap-3 mt-4">
                    <button onclick="adminPanel.closeModal('admin-modal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Отмена</button>
                    <button onclick="adminPanel.saveAdmin()" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">Сохранить</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) this.closeModal('admin-modal');
        });
        this.loadSchoolsForSelect();
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.remove();
    }

    async loadSchoolsForSelect() {
        try {
            const schools = await this.fetchAPI('schools.php?action=list');
            const select = document.querySelector('#admin-form select[name="school_id"]');
            if (select) {
                select.innerHTML = '<option value="">Все школы (супер-админ)</option>' + 
                    schools.map(s => `<option value="${s.id}">${this.escapeHtml(s.name)} (№${s.number})</option>`).join('');
            }
        } catch (error) {
            console.error('Error loading schools:', error);
        }
    }

    async saveSchool() {
        const form = document.getElementById('school-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        try {
            await this.fetchAPI('schools.php?action=create', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            this.closeModal('school-modal');
            this.loadSchools();
        } catch (error) {
            console.error('Error saving school:', error);
            alert('Ошибка при сохранении');
        }
    }

    async updateSchool() {
        const form = document.getElementById('edit-school-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        try {
            await this.fetchAPI('schools.php?action=update', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            this.closeModal('edit-school-modal');
            this.loadSchools();
        } catch (error) {
            console.error('Error updating school:', error);
            alert('Ошибка при обновлении');
        }
    }

    async saveAdmin() {
        const form = document.getElementById('admin-form');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        try {
            await this.fetchAPI('schools.php?action=create_admin', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            this.closeModal('admin-modal');
            this.loadAdmins();
        } catch (error) {
            console.error('Error saving admin:', error);
            alert('Ошибка при сохранении');
        }
    }

    async toggleSchool(id) {
        try {
            await this.fetchAPI('schools.php?action=toggle', {
                method: 'POST',
                body: JSON.stringify({ id })
            });
            this.loadSchools();
        } catch (error) {
            console.error('Error toggling school:', error);
        }
    }

    async deleteSchool(id) {
        if (!confirm('Удалить школу? Это действие необратимо.')) return;
        try {
            await this.fetchAPI('schools.php?action=delete', {
                method: 'POST',
                body: JSON.stringify({ id })
            });
            this.loadSchools();
        } catch (error) {
            console.error('Error deleting school:', error);
        }
    }

    async deleteAdmin(id) {
        if (!confirm('Удалить администратора?')) return;
        try {
            await this.fetchAPI('schools.php?action=delete_admin', {
                method: 'POST',
                body: JSON.stringify({ id })
            });
            this.loadAdmins();
        } catch (error) {
            console.error('Error deleting admin:', error);
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    escapeAttr(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    async fetchAPI(url, options = {}) {
        try {
            const response = await fetch(this.apiBase + url, {
                headers: { 'Content-Type': 'application/json' },
                ...options
            });
            
            if (!response.ok) {
                const text = await response.text();
                throw new Error(`HTTP ${response.status}: ${text}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
}