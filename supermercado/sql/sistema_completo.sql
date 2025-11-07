-- Eliminar base de datos si existe y crear nueva
DROP DATABASE IF EXISTS supermercado_db;
CREATE DATABASE supermercado_db;
USE supermercado_db;

-- Tabla usuarios con campos adicionales
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    correo VARCHAR(150),
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_sesion DATETIME,
    rol ENUM('admin', 'empleado', 'cliente') DEFAULT 'cliente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar usuarios de ejemplo (contraseña: 123456)
INSERT INTO usuarios (nombre, usuario, password, correo, rol) VALUES
-- Admins
('Administrador Principal', 'admin1', '123456', 'admin1@supermercado.com', 'admin'),
('Administrador Ventas', 'admin2', '123456', 'admin2@supermercado.com', 'admin'),
('Administrador Stock', 'admin3', '123456', 'admin3@supermercado.com', 'admin'),
-- Empleados
('Juan Pérez', 'emp1', '123456', 'emp1@supermercado.com', 'empleado'),
('María García', 'emp2', '123456', 'emp2@supermercado.com', 'empleado'),
('Carlos López', 'emp3', '123456', 'emp3@supermercado.com', 'empleado'),
-- Clientes
('Ana Martínez', 'cliente1', '123456', 'cliente1@mail.com', 'cliente'),
('Pedro Sánchez', 'cliente2', '123456', 'cliente2@mail.com', 'cliente'),
('Laura Torres', 'cliente3', '123456', 'cliente3@mail.com', 'cliente');

-- Tabla categorías
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    activa BOOLEAN DEFAULT TRUE
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
    direccion TEXT,
    contacto_nombre VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    stock_minimo INT DEFAULT 10,
    imagen VARCHAR(255),
    codigo_barras VARCHAR(50),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion DATETIME,
    id_categoria INT,
    id_proveedor INT,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar productos de ejemplo
INSERT INTO productos (nombre, descripcion, precio, stock, id_categoria, id_proveedor) VALUES
('Leche Entera 1L', 'Leche entera pasteurizada', 25.50, 100, 3, 2),
('Arroz Premium 1Kg', 'Arroz grano largo', 32.00, 200, 1, 1),
('Detergente 2L', 'Detergente líquido concentrado', 45.75, 50, 5, 3),
('Refresco Cola 2L', 'Refresco carbonatado sabor cola', 28.00, 150, 2, 1),
('Queso Fresco 500g', 'Queso fresco pasteurizado', 65.00, 30, 3, 2);

-- Tabla movimientos_inventario para registro de cambios
CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT,
    tipo_movimiento ENUM('entrada', 'salida', 'ajuste') NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT,
    stock_nuevo INT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT,
    descripcion TEXT,
    FOREIGN KEY (id_producto) REFERENCES productos(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla logs para seguimiento de actividades
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT,
    accion VARCHAR(50),
    tabla_afectada VARCHAR(50),
    id_registro INT,
    descripcion TEXT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para optimizar búsquedas
CREATE INDEX idx_productos_nombre ON productos(nombre);
CREATE INDEX idx_usuarios_usuario ON usuarios(usuario);
CREATE INDEX idx_productos_categoria ON productos(id_categoria);
CREATE INDEX idx_movimientos_fecha ON movimientos_inventario(fecha);
CREATE INDEX idx_logs_fecha ON logs(fecha);