# Tech Service  
## Guía para ejecutar el proyecto con XAMPP

Este documento describe paso a paso cómo instalar y ejecutar el proyecto **Tech Service** en un entorno local utilizando **XAMPP** y **Visual Studio Code**.

---

## Requisitos previos

- **XAMPP**
- **Visual Studio Code (VS Code)**
- **Navegador web** (Google Chrome, Firefox, Edge)

---

## Instalación de XAMPP

1. Descargar XAMPP desde el sitio oficial:  
   https://www.apachefriends.org

2. Ejecutar el instalador de XAMPP.

3. Durante la instalación, seleccionar los siguientes componentes:
   - Apache
   - MySQL

4. Finalizar la instalación y abrir el **Panel de Control de XAMPP**.

---

## Inicio de servicios

1. Abrir el **Panel de Control de XAMPP**.
2. Iniciar los servicios:
   - Apache
   - MySQL
3. Verificar que ambos servicios estén en estado **Running**.

---

## Configuración del proyecto PHP

1. Ubicar la carpeta del proyecto **Tech Service**.
2. Copiar la carpeta del proyecto.
3. Pegar la carpeta dentro del directorio de XAMPP:

C:\xampp\htdocs\

4. El nombre de la carpeta será utilizado como la URL del proyecto.

---

## Configuración de la base de datos

1. Abrir el navegador web.
2. Acceder a **phpMyAdmin** desde:

http://localhost/phpmyadmin

3. Crear una nueva base de datos llamada:

tech_service

4. Importar el archivo `.sql` del proyecto si está disponible.
5. Configurar los datos de conexión en el archivo PHP correspondiente:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "tech_service";
```
Abrir el proyecto en Visual Studio Code

Abrir Visual Studio Code.

Seleccionar Archivo > Abrir carpeta.

Navegar hasta la ruta:

C:\xampp\htdocs\tech-service


Abrir la carpeta del proyecto.

Ejecución del proyecto

Abrir el navegador web.

Ingresar la siguiente URL:

http://localhost/tech-service


Si existe un archivo index.php, el sistema se cargará automáticamente.

Verificación del funcionamiento

La página debe cargarse correctamente.

En caso de errores, verificar:

Que Apache y MySQL estén activos.

Que la carpeta esté correctamente ubicada en htdocs.

Que la conexión a la base de datos sea correcta.

Notas finales

El proyecto se ejecuta en un entorno local.

Para su publicación en internet, se requiere un servicio de hosting compatible con PHP y MySQL.

Proyecto desarrollado con PHP, MySQL y XAMPP.

