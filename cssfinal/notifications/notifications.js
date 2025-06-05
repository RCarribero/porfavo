class NotificationSystem {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.initialize();
    }

    initialize() {
        this.createNotificationUI();
        this.setupEventListeners();
        this.loadNotifications();
    }

    createNotificationUI() {
        const notificationsContainer = document.createElement('div');
        notificationsContainer.className = 'notifications-container';
        
        const bellButton = document.createElement('div');
        bellButton.className = 'notification-bell';
        bellButton.innerHTML = `
            <div class="icon">🔔</div>
            <div class="notification-badge" id="notification-badge">${this.unreadCount}</div>
        `;

        const dropdown = document.createElement('div');
        dropdown.className = 'notifications-dropdown';
        
        notificationsContainer.appendChild(bellButton);
        notificationsContainer.appendChild(dropdown);
        document.body.appendChild(notificationsContainer);
    }

    setupEventListeners() {
        const bellButton = document.querySelector('.notification-bell');
        const dropdown = document.querySelector('.notifications-dropdown');

        bellButton.addEventListener('click', () => {
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notifications-container')) {
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
    }

    loadNotifications() {
        const saved = localStorage.getItem('notifications');
        this.notifications = saved ? JSON.parse(saved) : [];

        this.unreadCount = this.notifications.filter(n => !n.read).length;
        this.updateBadge();
        this.renderNotifications();
    }

    saveNotifications() {
        localStorage.setItem('notifications', JSON.stringify(this.notifications));
    }

    updateBadge() {
        const badge = document.getElementById('notification-badge');
        badge.textContent = this.unreadCount;
        badge.style.display = this.unreadCount === 0 ? 'none' : 'block';
    }

    renderNotifications() {
        const dropdown = document.querySelector('.notifications-dropdown');
        dropdown.innerHTML = '';

        this.notifications.forEach(notification => {
            const notificationElement = document.createElement('div');
            notificationElement.className = `notification-item ${!notification.read ? 'unread' : ''}`;
            // Convertir la fecha del servidor a la hora local del usuario
            const serverDate = new Date(notification.time);
            const localTime = serverDate.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            notificationElement.innerHTML = `
                <div class="notification-content">
                    <div class="notification-text">
                        <h4>${notification.title}</h4>
                        <p>${notification.message}</p>
                    </div>
                    <div class="notification-time">${localTime}</div>
                </div>
            `;
            
            notificationElement.addEventListener('click', () => {
                this.markAsRead(notification.id);
            });

            dropdown.appendChild(notificationElement);
        });

        const actions = document.createElement('div');
        actions.className = 'notification-actions';
        actions.innerHTML = `
            <button class="primary" onclick="notificationSystem.markAllAsRead()">Marcar todas como leídas</button>
            <button onclick="notificationSystem.clearAll()">Limpiar todas</button>
        `;
        dropdown.appendChild(actions);
    }

    markAsRead(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification && !notification.read) {
            notification.read = true;
            this.unreadCount--;
            this.saveNotifications();
            this.updateBadge();
            this.renderNotifications();
        }
    }

    markAllAsRead() {
        this.notifications.forEach(notification => {
            if (!notification.read) {
                notification.read = true;
                this.unreadCount--;
            }
        });
        this.saveNotifications();
        this.updateBadge();
        this.renderNotifications();
    }

    clearAll() {
        this.notifications = [];
        this.unreadCount = 0;
        localStorage.removeItem('notifications');
        this.updateBadge();
        this.renderNotifications();
    }

    addNotification(notification) {
        this.notifications.unshift(notification);
        this.unreadCount++;
        this.saveNotifications();
        this.updateBadge();
        this.renderNotifications();
    }
}

// Inicializar el sistema de notificaciones cuando la página cargue
let notificationSystem;
document.addEventListener('DOMContentLoaded', () => {
    notificationSystem = new NotificationSystem();
});
