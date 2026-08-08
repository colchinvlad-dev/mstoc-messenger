/**
 * MTSoc Messenger - Главный файл приложения
 */

// Утилиты
const Utils = {
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    formatTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    },

    formatMessageTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        const now = new Date();
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        }
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Вчера ' + date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
        }
        return date.toLocaleDateString('ru-RU') + ' ' + date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
    },

    async fetchAPI(url, options = {}) {
        try {
            const headers = {};
            if (!(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
            }
            
            const response = await fetch(window.MTCONFIG.apiBaseUrl + url, {
                headers: headers,
                credentials: 'same-origin',
                ...options
            });
            
            if (response.status === 401) {
                window.location.href = '/index.php';
                return null;
            }
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: error.message };
        }
    },

    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
    }
};

console.log('App.js loaded, MTCONFIG:', window.MTCONFIG);