<footer class="footer mt-auto py-4 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="footer-section">
                    <h5 class="footer-title mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Sobre el Sistema
                    </h5>
                    <p class="footer-text">Sistema de gestión de tickets para el seguimiento y control de incidencias y tareas.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="footer-section">
                    <h5 class="footer-title mb-3">
                        <i class="fas fa-link me-2"></i>
                        Enlaces Rápidos
                    </h5>
                    <ul class="footer-links list-unstyled">
                        <li>
                            <a href="<?php echo BASE_PATH; ?>index.php" class="footer-link">
                                <i class="fas fa-home me-2"></i>
                                Inicio
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_PATH; ?>index.php?controller=report&action=index" class="footer-link">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_PATH; ?>index.php?controller=report&action=custom" class="footer-link">
                                <i class="fas fa-chart-bar me-2"></i>
                                Informes
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="footer-section">
                    <h5 class="footer-title mb-3">
                        <i class="fas fa-phone me-2"></i>
                        Contacto
                    </h5>
                    <ul class="footer-contact list-unstyled">
                        <li>
                            <i class="fas fa-envelope me-2"></i>
                            contacto@gruposolutia.com
                        </li>
                        <li>
                            <i class="fas fa-phone me-2"></i>
                            +34 954 123 456
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Sevilla, España
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <hr class="mt-4 mb-4 footer-divider">
        <div class="row">
            <div class="col-12 text-center">
                <p class="footer-copyright mb-0">
                    <i class="fas fa-copyright me-2"></i>
                    <?php echo date('Y'); ?> Grupo Solutia. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer {
        background-color: var(--color-card) !important;
        color: var(--color-text);
        transition: all 0.3s ease;
        padding: 2rem 0;
    }
    
    /* Estilos para el footer en modo oscuro */
    body.dark-mode .footer .footer-title,
    body.dark-mode .footer .footer-title i,
    body.dark-mode .footer .footer-links i,
    body.dark-mode .footer .footer-contact i {
        color: #ff8c42 !important;
    }
    
    /* Asegurar que los enlaces del footer mantengan el color naranja al hacer hover en modo oscuro */
    body.dark-mode .footer .footer-link:hover,
    body.dark-mode .footer .footer-link:hover i {
        color: #ff8c42 !important;
    }

    .footer-section {
        padding: 1rem;
    }

    .footer-title {
        color: var(--color-primary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .footer-text {
        color: var(--color-text);
        opacity: 0.8;
        line-height: 1.6;
    }

    .footer-link {
        color: var(--color-text);
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0;
        border-radius: 4px;
    }

    .footer-link:hover {
        color: var(--color-primary);
        background-color: rgba(52, 152, 219, 0.1);
        transform: translateX(5px);
    }

    .footer-contact {
        color: var(--color-text);
        opacity: 0.8;
    }

    .footer-contact li {
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .footer-divider {
        border-color: var(--color-border);
        opacity: 0.5;
    }

    .footer-copyright {
        color: var(--color-text);
        opacity: 0.8;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    body.dark-mode .footer {
        background-color: var(--color-card) !important;
    }

    body.dark-mode .footer-text,
    body.dark-mode .footer-contact {
        opacity: 0.8;
    }

    body.dark-mode .footer-divider {
        border-color: var(--color-border);
        opacity: 0.5;
    }

    body.dark-mode .footer-link:hover {
        background-color: rgba(255,140,66,0.1);
    }
</style>

<!-- Scripts de Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Tema oscuro/claro
    const themeToggle = document.querySelector('.theme-toggle');
    const body = document.body;

    // Verificar preferencia guardada
    if (localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark-mode');
        themeToggle.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        const isDarkMode = body.classList.contains('dark-mode');
        
        if (isDarkMode) {
            themeToggle.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
            localStorage.setItem('darkMode', 'enabled');
        } else {
            themeToggle.innerHTML = '<i class="fas fa-moon"></i> Modo Oscuro';
            localStorage.setItem('darkMode', 'disabled');
        }
    });
</script>
</body>
</html>
