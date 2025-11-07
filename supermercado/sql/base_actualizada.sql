-- Recrear la base de datos
DROP DATABASE IF EXISTS supermercado_db;
CREATE DATABASE supermercado_db;
USE supermercado_db;

-- Tabla usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    correo VARCHAR(150),
    rol ENUM('admin', 'empleado', 'cliente') DEFAULT 'cliente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar usuarios de ejemplo (contraseña: 123456 para todos)
-- Admins
INSERT INTO usuarios (nombre, usuario, password, correo, rol) VALUES
('Admin Principal', 'admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin1@supermercado.com', 'admin'),
('Admin Ventas', 'admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin2@supermercado.com', 'admin'),
('Admin Stock', 'admin3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin3@supermercado.com', 'admin');

-- Empleados
INSERT INTO usuarios (nombre, usuario, password, correo, rol) VALUES
('Empleado Ventas', 'emp1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'emp1@supermercado.com', 'empleado'),
('Empleado Stock', 'emp2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'emp2@supermercado.com', 'empleado'),
('Empleado Caja', 'emp3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'emp3@supermercado.com', 'empleado');

-- Clientes
INSERT INTO usuarios (nombre, usuario, password, correo, rol) VALUES
('Cliente Regular', 'cliente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente1@mail.com', 'cliente'),
('Cliente VIP', 'cliente2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente2@mail.com', 'cliente'),
('Cliente Nuevo', 'cliente3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente3@mail.com', 'cliente');

-- Tabla categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion) VALUES 
('Abarrotes', 'Productos básicos y despensa'),
('Bebidas', 'Refrescos, jugos y bebidas'),
('Lácteos', 'Leche, quesos y derivados'),
('Carnes', 'Carnes frescas y procesadas'),
('Limpieza', 'Productos de limpieza del hogar'),
('Frutas y Verduras', 'Productos frescos');

-- Tabla proveedores
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(20),
    correo VARCHAR(150),
    direccion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar proveedores
INSERT INTO proveedores (nombre, telefono, correo, direccion) VALUES
('Distribuidora Central', '555-0100', 'ventas@central.com', 'Calle Principal 123'),
('Lácteos del Valle', '555-0200', 'pedidos@lacteos.com', 'Av. Industrial 456'),
('Limpieza Total', '555-0300', 'ventas@limpieza.com', 'Calle Comercio 789');

-- Tabla productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagen VARCHAR(255),
    id_categoria INT,
    id_proveedor INT,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar algunos productos de ejemplo
INSERT INTO productos (nombre, precio, stock, id_categoria, id_proveedor) VALUES
('Leche Entera 1L', 25.50, 100, 3, 2),
('Arroz Premium 1Kg', 32.00, 200, 1, 1),
('Detergente 2L', 45.75, 50, 5, 3),
('Refresco Cola 2L', 28.00, 150, 2, 1),
('Queso Fresco 500g', 65.00, 30, 3, 2);

-- Nota: La contraseña para todos los usuarios es "123456"