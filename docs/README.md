# 📚 TaskFlow - Sistema de Gestión de Tareas Académicas

TaskFlow es una plataforma web desarrollada en **Laravel** diseñada para ayudar a los estudiantes a gestionar, categorizar y organizar sus entregas, exámenes y proyectos escolares de manera eficiente.

## 🛠️ Requisitos del Sistema (Entorno de Operación)
Para desplegar este proyecto en un entorno local o servidor, asegúrate de contar con:
* PHP >= 8.1
* Composer
* MySQL o MariaDB
* Node.js y NPM

## ⚙️ Instrucciones de Instalación (Manual de Operación)
Sigue estos pasos para levantar el entorno de desarrollo:

1. **Clonar el repositorio:**
   `git clone https://github.com/cC00k13/TaskFlow_App.git`
2. **Instalar dependencias de PHP y Node:**
   `composer install`
   `npm install && npm run build`
3. **Configurar el entorno:**
   Copia el archivo de ejemplo y genera tu clave de aplicación:
   `cp .env.example .env`
   `php artisan key:generate`
4. **Base de Datos:**
   Configura tus credenciales en el archivo `.env` y ejecuta las migraciones:
   `php artisan migrate`
5. **Levantar el servidor local:**
   `php artisan serve`

## 📖 Manual de Usuario y Documentación Técnica
Acorde a nuestra metodología ágil, la documentación detallada del sistema, diagramas de arquitectura y guías de uso paso a paso se encuentran centralizadas en nuestro directorio interno de documentación. 

## 👥 Equipo de Desarrollo (Scrum Team)
* César Alonso (Backend)
* Emmanuel Contreras (DBA)
* Hannya Contreras (Product Owner / QA)
* Humberto Jireh (Gestión)
* Natalya Ramírez (Frontend)
