/**
 * Sistema de gestión de temas (claro/oscuro)
 * Este archivo centraliza la lógica para cambiar entre modo claro y oscuro en toda la aplicación
 */

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const body = document.body;
    const themeToggle = document.querySelector('.theme-toggle');
    const themeButton = document.getElementById('theme-button');
    
    // Función para actualizar la UI basada en el tema
    function updateThemeUI(isDarkMode) {
        // Actualizar clases del cuerpo
        if (isDarkMode) {
            body.classList.add('dark-mode');
        } else {
            body.classList.remove('dark-mode');
        }
        
        // Actualizar botón de tema si existe
        if (themeButton) {
            if (isDarkMode) {
                themeButton.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
            } else {
                themeButton.innerHTML = '<i class="fas fa-moon"></i> Modo Oscuro';
            }
        } else if (themeToggle) {
            // Si no hay botón específico pero sí contenedor
            if (isDarkMode) {
                themeToggle.innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
            } else {
                themeToggle.innerHTML = '<i class="fas fa-moon"></i> Modo Oscuro';
            }
        }
        
        // Disparar evento personalizado para que los gráficos y otros componentes respondan
        document.dispatchEvent(new CustomEvent('themeChanged', { 
            detail: { darkMode: isDarkMode }
        }));
    }
    
    // Verificar preferencia guardada al cargar
    function loadSavedTheme() {
        const savedTheme = localStorage.getItem('darkMode');
        const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Usar el tema guardado o la preferencia del sistema
        if (savedTheme === 'enabled' || (savedTheme === null && prefersDarkScheme)) {
            updateThemeUI(true);
        } else {
            updateThemeUI(false);
        }
    }
    
    // Función para alternar el tema
    function toggleTheme() {
        const isDarkModeNew = !body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDarkModeNew ? 'enabled' : 'disabled');
        updateThemeUI(isDarkModeNew);
    }
    
    // Asignar evento al botón de tema
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    if (themeButton && !themeToggle) {
        themeButton.addEventListener('click', toggleTheme);
    }
    
    // Escuchar cambios en la preferencia del sistema
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (localStorage.getItem('darkMode') === null) {
            updateThemeUI(e.matches);
        }
    });
    
    // Cargar tema al iniciar
    loadSavedTheme();
});
