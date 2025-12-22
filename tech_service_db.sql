-- BASE DE DATOS PARA SERVICIO TÉCNICO DE COMPUTADORAS
-- (Anteriormente Bike Store)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. Crear Base de Datos
DROP DATABASE IF EXISTS `tech_service_db`;
CREATE DATABASE IF NOT EXISTS `tech_service_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tech_service_db`;

-- 2. Tabla de Usuarios (Personal, Técnicos, Admin)
-- Renombrado de 'usuarios'
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'technician', -- roles: admin, technician, reception
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tabla de Clientes
-- Base 'customers' mantenida y mejorada
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL, -- Para acceso web cliente
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Categorías (Hardware y Servicios)
-- Renombrado de 'categorias'
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL, -- Ej: Laptops, Impresoras, Componentes, Servicios
  `description` text,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Productos (Repuestos y Hardware)
-- Renombrado de 'productos'
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `description` text,
  `model` varchar(100),
  `price` decimal(10,2) NOT NULL, -- Precio de venta
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) NOT NULL,
  `image_url` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_cat_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Servicios (Catálogo de Mano de Obra)
-- NUEVA TABLA
CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL, -- Ej: Formateo, Limpieza, Cambio de Pantalla
  `description` text,
  `base_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Dispositivos del Cliente (Equipos a reparar)
-- NUEVA TABLA
CREATE TABLE `devices` (
  `device_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `device_type` varchar(50) NOT NULL, -- Laptop, PC, Printer
  `brand` varchar(50),
  `model` varchar(100),
  `serial_number` varchar(100),
  `password` varchar(100), -- Clave del dispositivo si es necesaria
  `notes` text, -- Estado físico, rayones, etc.
  PRIMARY KEY (`device_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `devices_cust_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. Tickets de Servicio (Órdenes de Reparación)
-- Reemplaza logicamente a 'orders' para el flujo de reparación
CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `technician_id` int(11) DEFAULT NULL, -- Quién lo está reparando
  `status` varchar(20) NOT NULL DEFAULT 'Pendiente', -- Pendiente, Diagnostico, En Proceso, Terminado, Entregado
  `priority` varchar(20) DEFAULT 'Normal',
  `problem_description` text NOT NULL, -- Lo que dice el cliente
  `diagnosis` text, -- Lo que dice el técnico
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`ticket_id`),
  KEY `customer_id` (`customer_id`),
  KEY `device_id` (`device_id`),
  KEY `technician_id` (`technician_id`),
  CONSTRAINT `tickets_cust_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  CONSTRAINT `tickets_dev_fk` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`),
  CONSTRAINT `tickets_tech_fk` FOREIGN KEY (`technician_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. Detalles del Ticket (Repuestos usados y Servicios realizados)
-- Reemplaza 'order_items'
CREATE TABLE `ticket_details` (
  `detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `item_type` varchar(20) NOT NULL, -- 'Product' (Repuesto) o 'Service' (Mano de obra)
  `product_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL, -- Precio al momento de agregar
  `subtotal` decimal(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  PRIMARY KEY (`detail_id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `details_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE,
  CONSTRAINT `details_prod_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `details_serv_fk` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 10. Pagos (Facturación)
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL, -- Efectivo, Tarjeta, Yape
  `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `payments_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- DATOS DE EJEMPLO (SEEDER)

-- Categorías
INSERT INTO `categories` (`name`, `description`) VALUES
('Servicio Técnico', 'Mano de obra y reparaciones'),
('Laptops', 'Equipos portátiles y repuestos'),
('Componentes PC', 'Discos, RAM, Placas, etc.'),
('Periféricos', 'Teclados, Mouses, Monitores');

-- Servicios Standard
INSERT INTO `services` (`name`, `description`, `base_price`) VALUES
('Formateo e Instalación SO', 'Instalación de Windows/Linux + Drivers básicos', 50.00),
('Limpieza Preventiva', 'Limpieza interna de polvo + Cambio pasta térmica', 40.00),
('Diagnóstico', 'Revisión general para detectar fallas', 30.00),
('Cambio de Pantalla Laptop', 'Mano de obra por cambio de panel', 60.00);

-- Usuarios
INSERT INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin', 'admin123', 'admin@techservice.com', 'admin'),
('tecnico1', 'tech123', 'juan@techservice.com', 'technician');

-- Clientes
INSERT INTO `customers` (`first_name`, `last_name`, `email`, `phone`) VALUES
('Maria', 'Gonzales', 'maria@gmail.com', '987654321'),
('Carlos', 'Perez', 'carlos@hotmail.com', '999888777');

-- Productos
INSERT INTO `products` (`name`, `model`, `price`, `stock_quantity`, `category_id`) VALUES
('SSD Kingston 480GB', 'A400', 120.00, 10, 3),
('Memoria RAM 8GB DDR4', 'HyperX', 150.00, 15, 3),
('Teclado Mecánico', 'Redragon', 180.00, 5, 4);

-- Ejemplo de Flujo:
-- 1. Cliente Maria trae su Laptop HP
INSERT INTO `devices` (`customer_id`, `device_type`, `brand`, `model`, `serial_number`, `notes`) 
VALUES (1, 'Laptop', 'HP', 'Pavilion 15', '5CD12345', 'No enciende, pantalla azul');

-- 2. Se crea un Ticket de recepción (Pendiente)
INSERT INTO `tickets` (`customer_id`, `device_id`, `technician_id`, `status`, `problem_description`)
VALUES (1, 1, 2, 'Diagnostico', 'Cliente reporta que no inicia Windows');

-- 3. Técnico revisa y agrega servicios/repuestos al ticket
-- Agrega servicio de Formateo
INSERT INTO `ticket_details` (`ticket_id`, `item_type`, `service_id`, `quantity`, `unit_price`)
VALUES (1, 'Service', 1, 1, 50.00);
-- Agrega un SSD nuevo
INSERT INTO `ticket_details` (`ticket_id`, `item_type`, `product_id`, `quantity`, `unit_price`)
VALUES (1, 'Product', 1, 1, 120.00);

-- Actualizar Total del Ticket
UPDATE `tickets` SET `total_cost` = (SELECT SUM(subtotal) FROM `ticket_details` WHERE `ticket_id` = 1) WHERE `ticket_id` = 1;

COMMIT;
