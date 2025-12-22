<?php
// Configuración básica del proyecto
// Ajusta `BASE_URL` a la URL con la que quieres que se generen los enlaces (QR, PDFs, etc.).
// Para uso local en la misma LAN, pon la IP de tu PC, por ejemplo:
// $BASE_URL = 'http://192.168.1.100/bike_store';
// Si no se define, el código usará `$_SERVER['HTTP_HOST']` como antes.

// Ejemplo por defecto (edítalo):
// Base URL por defecto (puedes sobrescribir con variable de entorno `BASE_URL`)
$BASE_URL = getenv('BASE_URL') !== false ? getenv('BASE_URL') : 'http://localhost/bike_store';

// DEFAULT STORE (usado para decrementar stock durante checkout si no se selecciona tienda)
$DEFAULT_STORE_ID = 1;

// Mail placeholders (ajusta si deseas enviar facturas por correo)
$MAIL_HOST = getenv('MAIL_HOST') !== false ? getenv('MAIL_HOST') : '';
$MAIL_USER = getenv('MAIL_USER') !== false ? getenv('MAIL_USER') : '';
$MAIL_PASS = getenv('MAIL_PASS') !== false ? getenv('MAIL_PASS') : '';
$MAIL_PORT = getenv('MAIL_PORT') !== false ? (int)getenv('MAIL_PORT') : 587;
$MAIL_SECURE = getenv('MAIL_SECURE') !== false ? getenv('MAIL_SECURE') : 'tls';
$MAIL_FROM = getenv('MAIL_FROM') !== false ? getenv('MAIL_FROM') : $MAIL_USER;
$MAIL_FROM_NAME = getenv('MAIL_FROM_NAME') !== false ? getenv('MAIL_FROM_NAME') : 'Bike Store';

// Si prefieres usar ngrok o una URL pública, reemplaza la línea anterior por la URL pública.

// Retornamos la BASE_URL para compatibilidad con includes previos
return isset($BASE_URL) ? $BASE_URL : null;
