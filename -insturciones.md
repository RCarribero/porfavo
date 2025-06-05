# Practicas-Victoria-FP
# Documentación Sistema de Tickets de Soporte

## 1. Introducción

### 1.1 Propósito del Sistema
El Sistema de Tickets de Soporte es una aplicación web desarrollada con PHP y MySQL que permite gestionar incidencias técnicas de forma eficiente y organizada. La plataforma facilita la comunicación entre usuarios y el equipo técnico, mejorando los tiempos de respuesta y la satisfacción del cliente.

### 1.2 Alcance
Esta aplicación está diseñada para entornos empresariales o educativos donde exista la necesidad de gestionar solicitudes de soporte técnico. El sistema se desarrollará inicialmente para servidor local con la posibilidad de migración a la nube.

### 1.3 Público Objetivo
- Administradores del sistema
- Personal técnico de soporte
- Usuarios finales que reportan incidencias

## 2. Requisitos del Sistema

### 2.1 Requisitos Funcionales

#### Gestión de Usuarios
- RF-01: El sistema debe permitir el registro de nuevos usuarios.
- RF-02: El sistema debe implementar un mecanismo de autenticación.
- RF-03: El sistema debe soportar tres roles: administrador, técnico y cliente.
- RF-04: Los usuarios deben poder recuperar sus contraseñas.
- RF-05: Los usuarios deben poder modificar su información personal.

#### Gestión de Tickets
- RF-06: Los clientes deben poder crear nuevos tickets de soporte.
- RF-07: Los tickets deben incluir campos para título, descripción, categoría y prioridad.
- RF-08: Los administradores deben poder asignar tickets a técnicos.
- RF-09: El sistema debe permitir adjuntar archivos a los tickets.
- RF-10: Los técnicos deben poder cambiar el estado de los tickets (abierto, en progreso, resuelto, cerrado).
- RF-11: Los usuarios deben poder añadir comentarios a los tickets existentes.
- RF-12: El sistema debe generar un identificador único para cada ticket.

#### Notificaciones
- RF-13: El sistema debe enviar notificaciones por correo electrónico cuando se cree un ticket.
- RF-14: El sistema debe enviar notificaciones cuando cambie el estado de un ticket.
- RF-15: El sistema debe enviar notificaciones cuando se añada un comentario a un ticket.

#### Administración
- RF-16: Los administradores deben poder gestionar usuarios (crear, modificar, desactivar).
- RF-17: Los administradores deben poder gestionar categorías de tickets.
- RF-18: El sistema debe proporcionar estadísticas básicas sobre tickets (número de tickets abiertos, resueltos, etc.).
- RF-19: Los administradores deben poder exportar informes en formato CSV o PDF.

### 2.2 Requisitos No Funcionales

#### Seguridad
- RNF-01: Las contraseñas deben almacenarse cifradas en la base de datos.
- RNF-02: El sistema debe implementar protección contra inyección SQL.
- RNF-03: El sistema debe implementar protección contra ataques XSS.
- RNF-04: Las sesiones deben caducar después de 30 minutos de inactividad.

#### Rendimiento
- RNF-05: El sistema debe soportar al menos 50 usuarios concurrentes.
- RNF-06: El tiempo de respuesta para operaciones básicas no debe superar los 3 segundos.

#### Usabilidad
- RNF-07: La interfaz debe ser responsive y compatible con los principales navegadores.
- RNF-08: El sistema debe ofrecer mensajes de error claros y específicos.

#### Mantenibilidad
- RNF-09: El código debe estar documentado siguiendo un estándar coherente.
- RNF-10: El sistema debe seguir un patrón de diseño MVC (Modelo-Vista-Controlador).

## 3. Arquitectura del Sistema

### 3.1 Arquitectura General
El sistema seguirá una arquitectura Cliente-Servidor utilizando el patrón MVC:
- **Cliente**: Navegador web del usuario
- **Servidor**: Aplicación PHP
- **Base de Datos**: MySQL

### 3.2 Diagrama de Componentes

```
+-----------------+        +------------------+        +----------------+
|                 |        |                  |        |                |
|  Capa de        |        |  Capa de         |        |  Capa de       |
|  Presentación   | <----> |  Negocio         | <----> |  Datos         |
|  (Vistas)       |        |  (Controladores) |        |  (Modelos)     |
|                 |        |                  |        |                |
+-----------------+        +------------------+        +----------------+
                                                             ^
                                                             |
                                                             v
                                                       +------------+
                                                       |            |
                                                       |  MySQL     |
                                                       |  Database  |
                                                       |            |
                                                       +------------+
```

### 3.3 Estructura de Directorios

```
ticket_system/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── config/
│   ├── database.php
│   └── config.php
├── controllers/
│   ├── TicketController.php
│   ├── UserController.php
│   └── ...
├── models/
│   ├── Ticket.php
│   ├── User.php
│   └── ...
├── views/
│   ├── tickets/
│   ├── users/
│   └── ...
├── lib/
│   ├── phpmailer/
│   └── ...
├── uploads/
├── index.php
└── README.md
```

## 4. Modelo de Datos

### 4.1 Diagrama Entidad-Relación

```
+---------------+       +----------------+       +---------------+
|     USERS     |       |     TICKETS    |       |   CATEGORIES  |
+---------------+       +----------------+       +---------------+
| PK id         |       | PK id          |       | PK id         |
| username      |<----->| FK user_id     |       | name          |
| password      |       | FK category_id |<----->| description   |
| email         |       | title          |       +---------------+
| role          |       | description    |
| created_at    |       | priority       |       +---------------+
+---------------+       | status         |       |   COMMENTS    |
                        | created_at     |       +---------------+
                        | updated_at     |       | PK id         |
                        +----------------+       | FK ticket_id  |
                                |                | FK user_id    |
                                |                | comment       |
                                |                | created_at    |
                                v                +---------------+
                        +----------------+
                        |   ATTACHMENTS  |
                        +----------------+
                        | PK id          |
                        | FK ticket_id   |
                        | filename       |
                        | filepath       |
                        | filesize       |
                        | created_at     |
                        +----------------+
```

### 4.2 Descripción de Tablas

#### Tabla users
| Campo      | Tipo        | Descripción                           |
|------------|-------------|---------------------------------------|
| id         | INT         | Identificador único (clave primaria)  |
| username   | VARCHAR(50) | Nombre de usuario                     |
| password   | VARCHAR(255)| Contraseña cifrada                    |
| email      | VARCHAR(100)| Correo electrónico                    |
| role       | ENUM        | Rol: admin, tech, client              |
| created_at | TIMESTAMP   | Fecha de creación                     |

#### Tabla tickets
| Campo       | Tipo        | Descripción                          |
|-------------|-------------|--------------------------------------|
| id          | INT         | Identificador único (clave primaria) |
| user_id     | INT         | Usuario que creó el ticket (FK)      |
| category_id | INT         | Categoría del ticket (FK)            |
| title       | VARCHAR(100)| Título del ticket                    |
| description | TEXT        | Descripción detallada                |
| priority    | ENUM        | Prioridad: low, medium, high, urgent |
| status      | ENUM        | Estado: open, in_progress, resolved, closed |
| created_at  | TIMESTAMP   | Fecha de creación                    |
| updated_at  | TIMESTAMP   | Fecha de última actualización        |

#### Tabla categories
| Campo       | Tipo        | Descripción                          |
|-------------|-------------|--------------------------------------|
| id          | INT         | Identificador único (clave primaria) |
| name        | VARCHAR(50) | Nombre de la categoría               |
| description | TEXT        | Descripción de la categoría          |

#### Tabla comments
| Campo       | Tipo        | Descripción                          |
|-------------|-------------|--------------------------------------|
| id          | INT         | Identificador único (clave primaria) |
| ticket_id   | INT         | Ticket al que pertenece (FK)         |
| user_id     | INT         | Usuario que hizo el comentario (FK)  |
| comment     | TEXT        | Contenido del comentario             |
| created_at  | TIMESTAMP   | Fecha de creación                    |

#### Tabla attachments
| Campo       | Tipo        | Descripción                          |
|-------------|-------------|--------------------------------------|
| id          | INT         | Identificador único (clave primaria) |
| ticket_id   | INT         | Ticket al que pertenece (FK)         |
| filename    | VARCHAR(255)| Nombre original del archivo          |
| filepath    | VARCHAR(255)| Ruta al archivo                      |
| filesize    | INT         | Tamaño del archivo en bytes          |
| created_at  | TIMESTAMP   | Fecha de creación                    |

### 4.3 Script SQL de Creación de Base de Datos

```sql
-- Crear base de datos
CREATE DATABASE ticket_system;
USE ticket_system;

-- Tabla de usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'tech', 'client') NOT NULL DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de categorías
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Tabla de tickets
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Tabla de comentarios
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabla de archivos adjuntos
CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    filesize INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);

-- Insertar datos de prueba
INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'admin@example.com', 'admin'),
('tech1', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'tech1@example.com', 'tech'),
('client1', '$2y$10$HfzIhGCCaxqyaIdGgjARSuOKAcm1Uy82YfLuNaajn6JrjLWy9Sj/W', 'client1@example.com', 'client');

INSERT INTO categories (name, description) VALUES
('Hardware', 'Problemas con equipos físicos'),
('Software', 'Problemas con aplicaciones y programas'),
('Red', 'Problemas de conectividad y red'),
('Otros', 'Otros tipos de incidencias');
```

## 5. Funcionalidades Detalladas

### 5.1 Autenticación y Registro

#### 5.1.1 Registro de Usuarios
- Formulario con campos: nombre de usuario, correo electrónico, contraseña y confirmación.
- Validación en cliente y servidor.
- Verificación de correo electrónico mediante enlace de activación.
- Los nuevos usuarios se registran por defecto con rol "cliente".

#### 5.1.2 Inicio de Sesión
- Formulario con campos: nombre de usuario/correo y contraseña.
- Opción "Recordarme" con cookies seguras.
- Bloqueo temporal de cuenta tras múltiples intentos fallidos.
- Redirección a página según rol de usuario.

#### 5.1.3 Recuperación de Contraseña
- Solicitud mediante correo electrónico.
- Generación de token único con caducidad.
- Formulario para establecer nueva contraseña.

### 5.2 Panel de Usuario Cliente

#### 5.2.1 Creación de Tickets
- Formulario con campos: título, descripción, categoría, prioridad.
- Opción para adjuntar archivos.
- Validación de campos y tamaño máximo de archivos.
- Confirmación de creación exitosa.

#### 5.2.2 Visualización de Tickets Propios
- Lista de tickets con filtros por estado, fecha y categoría.
- Resumen con estado actual, técnico asignado y última actualización.
- Capacidad de ordenar por diferentes campos.

#### 5.2.3 Detalle de Ticket
- Visualización completa de información del ticket.
- Historial de cambios de estado.
- Lista de comentarios ordenados cronológicamente.
- Formulario para añadir nuevos comentarios.
- Visualización y descarga de archivos adjuntos.

### 5.3 Panel de Técnico

#### 5.3.1 Lista de Tickets Asignados
- Vista de tickets asignados con filtros avanzados.
- Indicadores visuales de prioridad y tiempo de espera.
- Opción de búsqueda por ID, título o contenido.

#### 5.3.2 Gestión de Tickets
- Cambio de estado: abierto, en progreso, resuelto, cerrado.
- Posibilidad de añadir comentarios internos y públicos.
- Reasignación a otros técnicos (con aprobación).
- Historial de acciones realizadas.

### 5.4 Panel de Administración

#### 5.4.1 Gestión de Usuarios
- Creación, edición y desactivación de usuarios.
- Asignación y modificación de roles.
- Reinicio de contraseñas.
- Visualización de actividad de usuarios.

#### 5.4.2 Gestión de Categorías
- Creación, edición y eliminación de categorías.
- Asignación de técnicos por defecto a categorías.
- Estadísticas por categoría.

#### 5.4.3 Informes y Estadísticas
- Dashboard con KPIs principales.
- Informes personalizables por fecha, técnico, categoría.
- Exportación en formatos CSV, PDF.
- Gráficos de rendimiento y tendencias.

### 5.5 Sistema de Notificaciones

#### 5.5.1 Notificaciones por Correo
- Plantillas para diferentes eventos:
  - Creación de nuevo ticket
  - Asignación de ticket a técnico
  - Cambio de estado
  - Nuevo comentario
  - Resolución de ticket
- Personalización de contenido según destinatario.

#### 5.5.2 Notificaciones en Plataforma
- Centro de notificaciones para cada usuario.
- Marcado de notificaciones como leídas.
- Historial de notificaciones.

## 6. Interfaz de Usuario

### 6.1 Wireframes Principales

#### 6.1.1 Página de Inicio y Login
```
+--------------------------------------+
|  LOGO                   INICIAR      |
|                         SESIÓN       |
+--------------------------------------+
|                                      |
|  +-------------------------------+   |
|  |  Sistema de Tickets de Soporte|   |
|  |                               |   |
|  |  [Formulario de login]        |   |
|  |  Usuario:                     |   |
|  |  [                    ]       |   |
|  |  Contraseña:                  |   |
|  |  [                    ]       |   |
|  |                               |   |
|  |  [Iniciar sesión]             |   |
|  |                               |   |
|  |  ¿Olvidó su contraseña?       |   |
|  |  Registrarse                  |   |
|  +-------------------------------+   |
|                                      |
+--------------------------------------+
```

#### 6.1.2 Dashboard Cliente
```
+--------------------------------------+
|  LOGO           USUARIO ▼            |
+--------------------------------------+
|                                      |
| [Panel]   [Mis Tickets]  [Perfil]    |
|                                      |
+--------------------------------------+
|                                      |
|  Resumen                             |
|                                      |
|  Tickets abiertos: XX                |
|  Tickets resueltos: XX               |
|  Total tickets: XX                   |
|                                      |
|  [+ Nuevo Ticket]                    |
|                                      |
|  Tickets recientes                   |
|  +-------------------------------+   |
|  | ID | Título | Estado | Fecha  |   |
|  |----+--------+--------+--------|   |
|  | 1  | ...    | ...    | ...    |   |
|  | 2  | ...    | ...    | ...    |   |
|  +-------------------------------+   |
|                                      |
+--------------------------------------+
```

#### 6.1.3 Formulario de Nuevo Ticket
```
+--------------------------------------+
|  LOGO           USUARIO ▼            |
+--------------------------------------+
|                                      |
| [Panel]   [Mis Tickets]  [Perfil]    |
|                                      |
+--------------------------------------+
|                                      |
|  Nuevo Ticket                        |
|                                      |
|  Título:                             |
|  [                            ]      |
|                                      |
|  Categoría:                          |
|  [Desplegable               ▼]       |
|                                      |
|  Prioridad:                          |
|  [Desplegable               ▼]       |
|                                      |
|  Descripción:                        |
|  +----------------------------+      |
|  |                            |      |
|  |                            |      |
|  +----------------------------+      |
|                                      |
|  Adjuntar archivo:                   |
|  [Seleccionar archivo...]            |
|                                      |
|  [Cancelar]     [Crear Ticket]       |
|                                      |
+--------------------------------------+
```

### 6.2 Diseño Responsivo

La interfaz debe adaptarse a diferentes dispositivos:
- Ordenadores de escritorio (>1200px)
- Tabletas (768px - 1199px)
- Dispositivos móviles (<767px)

Se utilizará Bootstrap 5 para garantizar la compatibilidad y coherencia visual en todos los dispositivos.

### 6.3 Paleta de Colores

- **Principal**: #3498db (Azul)
- **Secundario**: #2ecc71 (Verde)
- **Acento**: #e74c3c (Rojo)
- **Fondo**: #f8f9fa (Gris claro)
- **Texto**: #343a40 (Negro suave)

Estados de tickets representados por colores:
- Abierto: #e74c3c (Rojo)
- En progreso: #f39c12 (Naranja)
- Resuelto: #2ecc71 (Verde)
- Cerrado: #7f8c8d (Gris)

## 7. Implementación y Desarrollo

### 7.1 Configuración del Entorno

#### 7.1.1 Requisitos Previos
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Composer para gestión de dependencias
- Git para control de versiones

#### 7.1.2 Dependencias
- Bootstrap 5 (Frontend)
- FontAwesome (Iconos)
- PHPMailer (Envío de correos)
- DataTables (Tablas dinámicas)
- Chart.js (Gráficos)

