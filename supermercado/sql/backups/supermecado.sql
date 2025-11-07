CREATE DATABASE IF NOT EXISTS supermercado_db;
USE supermercado_db;

-- =========================
-- TABLA USUARIOS
-- =========================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','empleado') DEFAULT 'empleado'
);

INSERT INTO usuarios (nombre, usuario, password, rol) VALUES
('Administrador', 'admin', 'admin123', 'admin'),
('Empleado', 'empleado', '1234', 'empleado');

-- =========================
-- TABLA CATEGORÍAS
-- =========================
CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL
);

INSERT INTO categorias (nombre) VALUES
('Abarrotes'),
('Bebidas'),
('Lácteos'),
('Carnes'),
('Limpieza'),
('Frutas y Verduras');

-- =========================
-- TABLA PROVEEDORES
-- =========================
CREATE TABLE proveedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100),
  telefono VARCHAR(20),
  correo VARCHAR(100),
  direccion VARCHAR(150)
);

INSERT INTO proveedores (nombre, telefono, correo, direccion) VALUES
('Distribuidora Central', '3214567890', 'central@proveedor.com', 'Calle 10 #15-22'),
('Lácteos Andinos', '3148529631', 'ventas@lacteos.com', 'Cra 12 #8-30'),
('Aseo Hogar S.A', '3007412589', 'contacto@aseohogar.com', 'Av. Principal 45-80');

-- =========================
-- TABLA PRODUCTOS
-- =========================
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  stock INT NOT NULL,
  id_categoria INT,
  id_proveedor INT,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL,
  FOREIGN KEY (id_proveedor) REFERENCES proveedores(id) ON DELETE SET NULL
);
