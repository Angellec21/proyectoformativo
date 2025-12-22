# Tech Service (adaptación desde Bike Store)

Proyecto PHP adaptado para gestionar un taller de servicio técnico de computadoras: órdenes de servicio, clientes, equipos, catálogo de servicios, pagos e invoices PDF.

## Contenido principal (nuevo)
- `index.php` — Panel con métricas de órdenes, servicios y clientes.
- `orders.php` — Crear y listar órdenes de servicio (cliente/equipo/tecnico/sucursal/prioridad).
- `customers.php` — Listado de clientes y equipos asociados.
- `catalog.php` — Catálogo de servicios y precios base.
- `assets/styles.css` — Tema UI orientado a soporte técnico (dark/neón).
- `bd.php` — Conexión PDO a `tech_service`.

## Requisitos
- PHP 7.4+ (o 8.x) con extensiones básicas (PDO, mbstring, etc.)
- MySQL o MariaDB para la base de datos
- Composer (recomendado) para dependencias
- Servidor web local (XAMPP, WAMP, etc.) para servir la app

## Instalación y configuración local

### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/Proyecto_Tech_Service.github.io.git
cd Proyecto_Tech_Service.github.io
```

### 2. Configurar la base de datos
1. Importa `tech_service_db.sql` en MySQL/MariaDB
2. Copia `bd.sample.php` como `bd.php` y ajusta las credenciales:
   ```bash
   cp bd.sample.php bd.php
   ```
3. Edita `bd.php` con tus credenciales de base de datos

### 3. Configurar variables de entorno
1. Copia `config.sample.php` como `config.php`:
   ```bash
   cp config.sample.php config.php
   ```
2. Ajusta la `BASE_URL` según tu entorno:
   - Local: `http://localhost/Proyecto_Tech_Service.github.io`
   - Producción: Tu dominio completo

### 4. Instalar dependencias
```bash
composer install
```

### 5. Configuración de correo (opcional)
Si deseas enviar facturas por correo, configura las variables MAIL_* en `config.php` o mediante variables de entorno.

## Correo / PDFs
- dompdf y mail helper siguen disponibles si decides generar factura PDF y enviar correo; adapta `libs/` según el nuevo flujo.

## Pruebas de envío de correo
Se incluye un endpoint de prueba para enviar una factura de ejemplo: `secciones/Pedidos/test_email.php?to=tu@correo`.
También hay un script CLI para enviar una prueba desde la línea de comandos (útil si no tienes servidor web):

```powershell
php scripts/send_test_email_cli.php lecaroquispe@gmail.com
```

El script genera un PDF mínimo y usa `libs/mail_sender.php` para intentar enviar el correo. Si no configuras SMTP, el helper intentará `mail()` como fallback (en Windows/XAMPP `mail()` suele no estar configurado, por lo que recomiendo smtp4dev).

## Generación de facturas
Reutiliza `secciones/Pedidos/` si quieres PDF; ajusta las consultas a `service_orders` y `payments`.

## Notas sobre GitHub
- `config.php` y `bd.php` están ignorados por seguridad. Usa los archivos `.sample.php` como plantillas.
- No commitees credenciales reales en el código
- Los archivos temporales (`temp_*.php`, `test_*.php`) son solo para desarrollo local

## Deployment a producción

### ⚠️ IMPORTANTE: Este proyecto NO funciona en GitHub Pages
GitHub Pages solo sirve sitios estáticos (HTML/CSS/JS). Este proyecto requiere:
- PHP 7.4+ con PDO
- MySQL/MariaDB
- Servidor web (Apache/Nginx)

### Opciones de hosting recomendadas para PHP:
1. **Hosting compartido**: Hostinger, InfinityFree, 000webhost
2. **VPS**: DigitalOcean, Linode, Vultr
3. **Cloud**: AWS EC2, Google Cloud, Azure
4. **Local/LAN**: XAMPP, WAMP, LAMP

### Consideraciones de seguridad
- ✅ Cambia las credenciales de base de datos
- ✅ Configura un usuario MySQL específico (no uses `root`)
- ✅ Actualiza `BASE_URL` en `config.php`
- ✅ Configura SMTP real si usarás correo electrónico
- ✅ Asegura que los archivos `.php` no sean accesibles sin autenticación
- ✅ Habilita HTTPS en producción

### Variables de entorno recomendadas
Para mayor seguridad, usa variables de entorno en lugar de archivos de configuración:
- `BASE_URL`: URL completa de tu aplicación
- `MAIL_HOST`, `MAIL_USER`, `MAIL_PASS`: Configuración SMTP
- Credenciales de BD a través de configuración del servidor

## Contribuir
- Abre issues para bugs o features.
- Para colaborar: fork -> branch -> PR.
