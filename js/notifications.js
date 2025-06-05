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
                const dropdown = document.querySelector('.notifications-dropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
    }

    loadNotifications() {
        // Simulación de carga de notificaciones
        this.notifications = [
            { id: 1, title: 'Nuevo ticket creado', message: 'Ticket #12345 ha sido creado', time: '10:25', read: false },
            { id: 2, title: 'Respuesta recibida', message: 'Se ha respondido a tu ticket #12344', time: '09:45', read: true },
            { id: 3, title: 'Prioridad actualizada', message: 'Ticket #12343 ha sido marcado como urgente', time: '09:30', read: false }
        ];

        this.unreadCount = this.notifications.filter(n => !n.read).length;
        this.updateBadge();
        this.renderNotifications();
    }

    updateBadge() {
        const badge = document.getElementById('notification-badge');
        badge.textContent = this.unreadCount;
        if (this.unreadCount === 0) {
            badge.style.display = 'none';
        } else {
            badge.style.display = 'block';
        }
    }

    renderNotifications() {
        const dropdown = document.querySelector('.notifications-dropdown');
        dropdown.innerHTML = '';

        // Renderizar cada notificación
        this.notifications.forEach(notification => {
            const notificationElement = document.createElement('div');
            notificationElement.className = `notification-item ${!notification.read ? 'unread' : ''}`;
            notificationElement.innerHTML = `
                <div class="notification-content">
                    <div class="notification-text">
                        <h4>${notification.title}</h4>
                        <p>${notification.message}</p>
                    </div>
                    <div class="notification-time">${notification.time}</div>
                </div>
            `;
            
            notificationElement.addEventListener('click', () => {
                this.markAsRead(notification.id);
            });

            dropdown.appendChild(notificationElement);
        });

        // Botones de acción
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
        if (notification) {
            notification.read = true;
            this.unreadCount--;
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
        this.updateBadge();
        this.renderNotifications();
    }

    clearAll() {
        this.notifications = [];
        this.unreadCount = 0;
        this.updateBadge();
        this.renderNotifications();
    }
}

// Inicializar el sistema de notificaciones cuando la página cargue
let notificationSystem;
document.addEventListener('DOMContentLoaded', () => {
    notificationSystem = new NotificationSystem();
});
