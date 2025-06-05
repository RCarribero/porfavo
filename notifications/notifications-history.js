class NotificationsHistory {
    constructor() {
        this.initialize();
    }

    initialize() {
        this.createHistoryUI();
        this.setupEventListeners();
        this.loadHistory();
    }

    createHistoryUI() {
        const historyContainer = document.createElement('div');
        historyContainer.className = 'notifications-history-container';
        
        const historyButton = document.createElement('div');
        historyButton.className = 'notifications-history-button';
        historyButton.innerHTML = `<div class="icon">📋</div>`;

        const modal = document.createElement('div');
        modal.className = 'notifications-history-modal';
        modal.innerHTML = `
            <div class="notifications-history-header">
                <h2>Historial de Notificaciones</h2>
                <span class="notifications-history-close">❌</span>
            </div>
            <ul class="notifications-history-list"></ul>
        `;

        historyContainer.appendChild(historyButton);
        historyContainer.appendChild(modal);
        document.body.appendChild(historyContainer);
    }

    setupEventListeners() {
        const historyButton = document.querySelector('.notifications-history-button');
        const modal = document.querySelector('.notifications-history-modal');
        const closeButton = document.querySelector('.notifications-history-close');

        historyButton.addEventListener('click', () => {
            modal.classList.toggle('show');
        });

        closeButton.addEventListener('click', () => {
            modal.classList.remove('show');
        });

        // Cerrar al hacer clic fuera del modal
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notifications-history-container')) {
                const modal = document.querySelector('.notifications-history-modal');
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                }
            }
        });
    }

    loadHistory() {
        // Simulación de carga del historial
        const history = [
            { id: 1, title: 'Nuevo ticket creado', message: 'Ticket #12345 ha sido creado', time: '10:25', read: false },
            { id: 2, title: 'Respuesta recibida', message: 'Se ha respondido a tu ticket #12344', time: '09:45', read: true },
            { id: 3, title: 'Prioridad actualizada', message: 'Ticket #12343 ha sido marcado como urgente', time: '09:30', read: false },
            { id: 4, title: 'Ticket resuelto', message: 'Ticket #12342 ha sido marcado como resuelto', time: '09:15', read: true },
            { id: 5, title: 'Nuevo ticket creado', message: 'Ticket #12341 ha sido creado', time: '09:00', read: false }
        ];

        this.renderHistory(history);
    }

    renderHistory(history) {
        const historyList = document.querySelector('.notifications-history-list');
        historyList.innerHTML = '';

        history.forEach(item => {
            const historyItem = document.createElement('li');
            historyItem.className = `notifications-history-item ${!item.read ? 'unread' : ''}`;
            historyItem.innerHTML = `
                <div class="notification-text">
                    <h4>${item.title}</h4>
                    <p>${item.message}</p>
                </div>
                <div class="notification-time">${item.time}</div>
            `;

            historyItem.addEventListener('click', () => {
                this.markAsRead(item.id);
            });

            historyList.appendChild(historyItem);
        });
    }

    markAsRead(notificationId) {
        // Aquí iría la lógica para marcar como leído en tu backend
        console.log('Marcar como leído:', notificationId);
    }
}

// Inicializar el sistema de historial cuando la página cargue
document.addEventListener('DOMContentLoaded', () => {
    const notificationsHistory = new NotificationsHistory();
});
