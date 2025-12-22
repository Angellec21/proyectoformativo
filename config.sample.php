<?php
// Archivo de ejemplo. Copia a `config.php` y rellena con tus valores locales.

// URL base (para generar enlaces/QR en facturas). En local usa http://localhost/bike_store
$BASE_URL = 'http://localhost/bike_store';

// Tienda por defecto para decrementar stock durante checkout
$DEFAULT_STORE_ID = 1;

// Configuración de correo (usa smtp4dev/Papercut en desarrollo o credenciales reales para SMTP)
$MAIL_HOST = '';
$MAIL_USER = '';
$MAIL_PASS = '';
$MAIL_PORT = 587;
$MAIL_FROM = 'noreply@local.bike';

// Retornamos BASE_URL para compatibilidad con includes previos
return isset($BASE_URL) ? $BASE_URL : null;

?>
