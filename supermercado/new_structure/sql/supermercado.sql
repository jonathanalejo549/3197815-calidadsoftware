-- Drop database if it exists and create a new one
DROP DATABASE IF EXISTS supermercado;
CREATE DATABASE supermercado DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE supermercado;

-- =========================
-- TABLA USUARIOS
-- =========================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado') DEFAULT 'empleado',
    correo VARCHAR(100) UNIQUE,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_sesion DATETIME,
    activo BOOLEAN DEFAULT TRUE,
    intentos_fallidos INT DEFAULT 0
) ENGINE=InnoDB;

-- =========================
-- TABLA LOGS
-- =========================
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    accion VARCHAR(50) NOT NULL,
    descripcion TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- TABLA CATEGORÍAS
-- =========================
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================
-- TABLA PROVEEDORES
-- =========================
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    correo VARCHAR(100),
    direccion TEXT,
    contacto_nombre VARCHAR(100),
    contacto_telefono VARCHAR(20),
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================
-- TABLA PRODUCTOS
-- =========================
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    precio_compra DECIMAL(10,2) NOT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    stock_minimo INT DEFAULT 10,
    id_categoria INT,
    id_proveedor INT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- TABLA MOVIMIENTOS
-- =========================
CREATE TABLE movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT,
    id_usuario INT,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    motivo VARCHAR(100),
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================
-- DATOS DE PRUEBA
-- =========================

-- Insertar usuarios
INSERT INTO usuarios (nombre, usuario, password, rol, correo) VALUES
('Administrador', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin@supermercado.com'),
('Empleado', 'empleado', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'empleado', 'empleado@supermercado.com');

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion) VALUES
('Abarrotes', 'Productos alimenticios no perecederos'),
('Bebidas', 'Refrescos, jugos y bebidas alcohólicas'),
('Lácteos', 'Leche, quesos y derivados'),
('Carnes', 'Carnes rojas, blancas y embutidos'),
('Limpieza', 'Productos de aseo y limpieza'),
('Frutas y Verduras', 'Productos frescos del campo');

-- Insertar proveedores
INSERT INTO proveedores (nombre, telefono, correo, direccion, contacto_nombre) VALUES
('Distribuidora Central', '3214567890', 'central@proveedor.com', 'Calle 10 #15-22', 'Juan Pérez'),
('Lácteos Andinos', '3148529631', 'ventas@lacteos.com', 'Cra 12 #8-30', 'María López'),
('Aseo Hogar S.A', '3007412589', 'contacto@aseohogar.com', 'Av. Principal 45-80', 'Carlos Ruiz');

-- Crear trigger para actualizar stock
DELIMITER //
CREATE TRIGGER after_movimiento_insert
AFTER INSERT ON movimientos
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'entrada' THEN
        UPDATE productos SET stock = stock + NEW.cantidad
        WHERE id = NEW.id_producto;
    ELSE
        UPDATE productos SET stock = stock - NEW.cantidad
        WHERE id = NEW.id_producto;
    END IF;
END //
DELIMITER ;

-- Crear vista para productos con stock bajo
CREATE VIEW productos_stock_bajo AS
SELECT 
    p.id,
    p.codigo,
    p.nombre,
    p.stock,
    p.stock_minimo,
    c.nombre as categoria,
    pr.nombre as proveedor
FROM productos p
LEFT JOIN categorias c ON p.id_categoria = c.id
LEFT JOIN proveedores pr ON p.id_proveedor = pr.id
WHERE p.stock <= p.stock_minimo AND p.activo = TRUE;