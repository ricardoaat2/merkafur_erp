-- ============================================================================
-- SISTEMA ERP CONTABLE: MERKAFUR SUPERMERCADOS (VERSIÓN COMPACTA - 13 TABLAS)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS merkafur_erp
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE merkafur_erp;

-- 1. ROLES (Opción B: Solo Administrador y Contador)
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 2. USUARIOS
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 3. PERIODOS CONTABLES Y TASA VIGENTE
CREATE TABLE periodos_contables (
    id_periodo INT AUTO_INCREMENT PRIMARY KEY,
    anio_fiscal SMALLINT NOT NULL,
    mes_fiscal TINYINT NOT NULL,
    tasa_bcv_vigente DECIMAL(18,4) NOT NULL DEFAULT 50.0000,
    ultima_sincronizacion_tasa DATETIME NULL,
    estado ENUM('Abierto', 'Cerrado') NOT NULL DEFAULT 'Abierto',
    UNIQUE KEY uk_periodo (anio_fiscal, mes_fiscal)
) ENGINE=InnoDB;

-- 4. PLAN DE CUENTAS CONTABLE
CREATE TABLE plan_cuentas (
    id_cuenta INT AUTO_INCREMENT PRIMARY KEY,
    id_cuenta_padre INT NULL,
    codigo_cuenta VARCHAR(30) NOT NULL UNIQUE,
    nombre_cuenta VARCHAR(150) NOT NULL,
    tipo_cuenta ENUM('Activo', 'Pasivo', 'Patrimonio', 'Ingreso', 'Costo', 'Gasto') NOT NULL,
    naturaleza ENUM('Deudora', 'Acreedora') NOT NULL,
    nivel TINYINT NOT NULL,
    acepta_movimientos TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_cuenta_padre FOREIGN KEY (id_cuenta_padre) REFERENCES plan_cuentas(id_cuenta) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. ASIENTOS CONTABLES (Cabecera del Libro Diario)
CREATE TABLE asientos_contables (
    id_asiento BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_periodo INT NOT NULL,
    id_usuario INT NOT NULL,
    numero_asiento VARCHAR(25) NOT NULL UNIQUE,
    fecha_asiento DATE NOT NULL,
    origen_modulo ENUM('Manual', 'Compras', 'POS_Ventas', 'Inventario_Merma', 'Tesoreria') NOT NULL,
    documento_referencia VARCHAR(50) NULL,
    glosa_concepto VARCHAR(255) NOT NULL,
    tasa_cambio DECIMAL(18,4) NOT NULL,
    total_debe_bs DECIMAL(18,2) NOT NULL,
    total_haber_bs DECIMAL(18,2) NOT NULL,
    estado ENUM('Mayorizado', 'Anulado') NOT NULL DEFAULT 'Mayorizado',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_partida_doble CHECK (total_debe_bs = total_haber_bs AND total_debe_bs > 0),
    CONSTRAINT fk_asiento_periodo FOREIGN KEY (id_periodo) REFERENCES periodos_contables(id_periodo) ON DELETE RESTRICT,
    CONSTRAINT fk_asiento_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 6. DETALLES DE ASIENTO (Renglones del Libro Diario y Fuente del Libro Mayor)
CREATE TABLE detalles_asiento (
    id_detalle BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_asiento BIGINT NOT NULL,
    id_cuenta INT NOT NULL,
    descripcion_linea VARCHAR(200) NOT NULL,
    debe_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    haber_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT chk_debe_o_haber CHECK ((debe_bs > 0 AND haber_bs = 0) OR (haber_bs > 0 AND debe_bs = 0)),
    CONSTRAINT fk_detalle_asiento FOREIGN KEY (id_asiento) REFERENCES asientos_contables(id_asiento) ON DELETE RESTRICT,
    CONSTRAINT fk_detalle_cuenta FOREIGN KEY (id_cuenta) REFERENCES plan_cuentas(id_cuenta) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 7. CATEGORÍAS / DEPARTAMENTOS DEL SUPERMERCADO (Puente Contable Automático)
CREATE TABLE categorias_departamento (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_departamento VARCHAR(100) NOT NULL UNIQUE,
    id_cuenta_inventario INT NOT NULL,
    id_cuenta_costo_venta INT NOT NULL,
    id_cuenta_ingreso_venta INT NOT NULL,
    id_cuenta_gasto_merma INT NOT NULL,
    CONSTRAINT fk_dep_inv FOREIGN KEY (id_cuenta_inventario) REFERENCES plan_cuentas(id_cuenta) ON DELETE RESTRICT,
    CONSTRAINT fk_dep_costo FOREIGN KEY (id_cuenta_costo_venta) REFERENCES plan_cuentas(id_cuenta) ON DELETE RESTRICT,
    CONSTRAINT fk_dep_ingreso FOREIGN KEY (id_cuenta_ingreso_venta) REFERENCES plan_cuentas(id_cuenta) ON DELETE RESTRICT,
    CONSTRAINT fk_dep_merma FOREIGN KEY (id_cuenta_gasto_merma) REFERENCES plan_cuentas(id_cuenta) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 8. PRODUCTOS
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    id_categoria INT NOT NULL,
    codigo_barras VARCHAR(50) NOT NULL UNIQUE,
    nombre_producto VARCHAR(150) NOT NULL,
    unidad_medida ENUM('UND', 'KG', 'LT') NOT NULL DEFAULT 'UND',
    alicuota_iva DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    costo_promedio_bs DECIMAL(18,4) NOT NULL DEFAULT 0.0000,
    precio_venta_usd DECIMAL(18,4) NOT NULL,
    stock_actual DECIMAL(18,3) NOT NULL DEFAULT 0.000,
    CONSTRAINT fk_prod_categoria FOREIGN KEY (id_categoria) REFERENCES categorias_departamento(id_categoria) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 9. KARDEX DE INVENTARIO Y MERMAS
CREATE TABLE kardex_inventario (
    id_kardex BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_asiento BIGINT NOT NULL,
    fecha_movimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo_movimiento ENUM('Entrada_Compra', 'Salida_Venta_POS', 'Merma_Perecederos', 'Merma_Rotura_Vencimiento') NOT NULL,
    cantidad DECIMAL(18,3) NOT NULL,
    costo_unitario_bs DECIMAL(18,4) NOT NULL,
    costo_total_bs DECIMAL(18,2) NOT NULL,
    stock_resultante DECIMAL(18,3) NOT NULL,
    CONSTRAINT fk_kardex_prod FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT,
    CONSTRAINT fk_kardex_asiento FOREIGN KEY (id_asiento) REFERENCES asientos_contables(id_asiento) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 10. PROVEEDORES
CREATE TABLE proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    rif VARCHAR(20) NOT NULL UNIQUE,
    razon_social VARCHAR(150) NOT NULL,
    porcentaje_retencion_iva DECIMAL(5,2) NOT NULL DEFAULT 75.00,
    id_cuenta_cxp INT NOT NULL,
    CONSTRAINT fk_prov_cxp FOREIGN KEY (id_cuenta_cxp) REFERENCES plan_cuentas(id_cuenta) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 11. FACTURAS DE COMPRA Y RETENCIONES (Todo integrado para Libro de Compras y CxP)
CREATE TABLE facturas_compra (
    id_factura_compra BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_asiento BIGINT NOT NULL,
    numero_factura VARCHAR(40) NOT NULL,
    numero_control VARCHAR(40) NOT NULL,
    fecha_recepcion DATE NOT NULL,
    monto_exento_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    base_imponible_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    monto_iva_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_factura_bs DECIMAL(18,2) NOT NULL,
    numero_comprobante_ret VARCHAR(30) NULL UNIQUE,
    iva_retenido_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    islr_retenido_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    saldo_pendiente_bs DECIMAL(18,2) NOT NULL,
    estado_pago ENUM('Pendiente', 'Pagada', 'Anulada') NOT NULL DEFAULT 'Pendiente',
    UNIQUE KEY uk_prov_fac (id_proveedor, numero_factura),
    CONSTRAINT fk_compra_prov FOREIGN KEY (id_proveedor) REFERENCES proveedores(id_proveedor) ON DELETE RESTRICT,
    CONSTRAINT fk_compra_asiento FOREIGN KEY (id_asiento) REFERENCES asientos_contables(id_asiento) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 12. CIERRES DE CAJA Z Y ARQUEO INTEGRADO (Alimenta Libro de Ventas y Asiento POS)
CREATE TABLE cierres_caja_z (
    id_cierre_z BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_asiento BIGINT NOT NULL,
    numero_caja VARCHAR(20) NOT NULL,
    serial_maquina_fiscal VARCHAR(50) NOT NULL,
    numero_reporte_z VARCHAR(20) NOT NULL,
    fecha_operacion DATE NOT NULL,
    ventas_exentas_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    base_imponible_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    iva_cobrado_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    igtf_cobrado_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    total_ventas_sistema_bs DECIMAL(18,2) NOT NULL,
    cobrado_efectivo_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    cobrado_divisas_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    cobrado_punto_venta_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    cobrado_pago_movil_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    diferencia_caja_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    costo_venta_total_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    UNIQUE KEY uk_caja_z (numero_caja, numero_reporte_z),
    CONSTRAINT fk_cierre_asiento FOREIGN KEY (id_asiento) REFERENCES asientos_contables(id_asiento) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 13. MOVIMIENTOS BANCARIOS Y TESORERÍA
CREATE TABLE movimientos_bancarios (
    id_movimiento_banco BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_cuenta_banco INT NOT NULL,
    id_asiento BIGINT NOT NULL,
    id_factura_compra BIGINT NULL,
    fecha_movimiento DATE NOT NULL,
    tipo_operacion ENUM('Pago_Proveedor', 'Deposito_Caja', 'Liquidacion_POS') NOT NULL,
    numero_referencia VARCHAR(50) NOT NULL,
    monto_bs DECIMAL(18,2) NOT NULL,
    comision_bancaria_bs DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    conciliado TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_banco_cta FOREIGN KEY (id_cuenta_banco) REFERENCES plan_cuentas(id_cuenta) ON DELETE RESTRICT,
    CONSTRAINT fk_banco_asiento FOREIGN KEY (id_asiento) REFERENCES asientos_contables(id_asiento) ON DELETE RESTRICT,
    CONSTRAINT fk_banco_factura FOREIGN KEY (id_factura_compra) REFERENCES facturas_compra(id_factura_compra) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================================
-- VISTA SQL PARA EL LIBRO MAYOR (Calcula movimientos y efecto en saldo)
-- ============================================================================
CREATE OR REPLACE VIEW vw_libro_mayor AS
SELECT 
    a.id_periodo,
    c.id_cuenta,
    c.codigo_cuenta,
    c.nombre_cuenta,
    c.tipo_cuenta,
    c.naturaleza,
    a.id_asiento,
    a.numero_asiento,
    a.fecha_asiento,
    a.origen_modulo,
    a.documento_referencia,
    d.descripcion_linea,
    d.debe_bs,
    d.haber_bs,
    ROUND(d.debe_bs / a.tasa_cambio, 2) AS debe_usd,
    ROUND(d.haber_bs / a.tasa_cambio, 2) AS haber_usd,
    CASE 
        WHEN c.naturaleza = 'Deudora' THEN (d.debe_bs - d.haber_bs)
        ELSE (d.haber_bs - d.debe_bs)
    END AS efecto_saldo_bs
FROM detalles_asiento d
INNER JOIN asientos_contables a ON d.id_asiento = a.id_asiento
INNER JOIN plan_cuentas c ON d.id_cuenta = c.id_cuenta
WHERE a.estado = 'Mayorizado';