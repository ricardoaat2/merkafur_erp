-- ============================================================================
-- SISTEMA ERP CONTABLE: MERKAFUR SUPERMERCADOS (VERSIÓN COMPACTA)
-- ARCHIVO: 02_seed_inicial.sql
-- DESCRIPCIÓN: Datos iniciales obligatorios para arrancar el ERP
-- ============================================================================

USE merkafur_erp;

-- 1. ROLES DEL SISTEMA (Opción B: Administrador y Contador)
INSERT INTO roles (id_rol, nombre_rol) VALUES
(1, 'Administrador'),
(2, 'Contador');

-- 2. USUARIOS INICIALES
-- Nota: El hash corresponde a la clave de prueba: 123456 (generado con password_hash BCRYPT)
INSERT INTO usuarios (id_usuario, id_rol, nombre_completo, username, password_hash, activo) VALUES
(1, 1, 'Administrador General Merkafur', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
(2, 2, 'Contador Principal Merkafur', 'contador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- 3. PERIODO CONTABLE INICIAL ABIERTO (Octubre 2026)
INSERT INTO periodos_contables (id_periodo, anio_fiscal, mes_fiscal, tasa_bcv_vigente, estado) VALUES
(1, 2026, 10, 50.0000, 'Abierto');

-- ============================================================================
-- 4. PLAN DE CUENTAS CONTABLE DE 4 NIVELES (88 CUENTAS)
-- ============================================================================

-- NIVEL 1: Grupos Principales (acepta_movimientos = 0)
INSERT INTO plan_cuentas (id_cuenta, id_cuenta_padre, codigo_cuenta, nombre_cuenta, tipo_cuenta, naturaleza, nivel, acepta_movimientos) VALUES
(1, NULL, '1', 'ACTIVOS', 'Activo', 'Deudora', 1, 0),
(2, NULL, '2', 'PASIVOS', 'Pasivo', 'Acreedora', 1, 0),
(3, NULL, '3', 'PATRIMONIO', 'Patrimonio', 'Acreedora', 1, 0),
(4, NULL, '4', 'INGRESOS', 'Ingreso', 'Acreedora', 1, 0),
(5, NULL, '5', 'COSTOS DE VENTA', 'Costo', 'Deudora', 1, 0),
(6, NULL, '6', 'GASTOS OPERATIVOS Y FINANCIEROS', 'Gasto', 'Deudora', 1, 0);

-- NIVEL 2: Subgrupos Financieros (acepta_movimientos = 0)
INSERT INTO plan_cuentas (id_cuenta, id_cuenta_padre, codigo_cuenta, nombre_cuenta, tipo_cuenta, naturaleza, nivel, acepta_movimientos) VALUES
(7,  1, '1.01', 'ACTIVO CORRIENTE', 'Activo', 'Deudora', 2, 0),
(8,  1, '1.02', 'ACTIVO NO CORRIENTE', 'Activo', 'Deudora', 2, 0),
(9,  2, '2.01', 'PASIVO CORRIENTE', 'Pasivo', 'Acreedora', 2, 0),
(10, 3, '3.01', 'CAPITAL Y RESULTADOS', 'Patrimonio', 'Acreedora', 2, 0),
(11, 4, '4.01', 'INGRESOS OPERATIVOS DE SUPERMERCADO', 'Ingreso', 'Acreedora', 2, 0),
(12, 4, '4.02', 'OTROS INGRESOS NO OPERATIVOS', 'Ingreso', 'Acreedora', 2, 0),
(13, 5, '5.01', 'COSTO DE MERCANCÍA VENDIDA', 'Costo', 'Deudora', 2, 0),
(14, 6, '6.01', 'GASTOS DE OPERACIÓN DE SUPERMERCADO', 'Gasto', 'Deudora', 2, 0),
(15, 6, '6.02', 'GASTOS FINANCIEROS Y BANCARIOS', 'Gasto', 'Deudora', 2, 0);

-- NIVEL 3: Cuentas Mayor / Rubros (acepta_movimientos = 0)
INSERT INTO plan_cuentas (id_cuenta, id_cuenta_padre, codigo_cuenta, nombre_cuenta, tipo_cuenta, naturaleza, nivel, acepta_movimientos) VALUES
(16, 7,  '1.01.01', 'Efectivo en Cajas y Bóveda', 'Activo', 'Deudora', 3, 0),
(17, 7,  '1.01.02', 'Bancos Disponibles', 'Activo', 'Deudora', 3, 0),
(18, 7,  '1.01.03', 'Medios Electrónicos POS por Liquidar', 'Activo', 'Deudora', 3, 0),
(19, 7,  '1.01.04', 'Cuentas por Cobrar', 'Activo', 'Deudora', 3, 0),
(20, 7,  '1.01.05', 'Créditos y Anticipos Fiscales', 'Activo', 'Deudora', 3, 0),
(21, 7,  '1.01.06', 'Inventarios de Mercancía (Kardex)', 'Activo', 'Deudora', 3, 0),
(22, 8,  '1.02.01', 'Propiedad, Planta y Equipos de Supermercado', 'Activo', 'Deudora', 3, 0),
(23, 9,  '2.01.01', 'Cuentas por Pagar Comerciales', 'Pasivo', 'Acreedora', 3, 0),
(24, 9,  '2.01.02', 'Obligaciones Tributarias por Enterar', 'Pasivo', 'Acreedora', 3, 0),
(25, 9,  '2.01.03', 'Obligaciones Laborales por Pagar', 'Pasivo', 'Acreedora', 3, 0),
(26, 10, '3.01.01', 'Capital y Reservas', 'Patrimonio', 'Acreedora', 3, 0),
(27, 11, '4.01.01', 'Ventas de Mercancía por Departamento', 'Ingreso', 'Acreedora', 3, 0),
(28, 12, '4.02.01', 'Ingresos Varios y Cambiarios', 'Ingreso', 'Acreedora', 3, 0),
(29, 13, '5.01.01', 'Costo de Venta por Departamento', 'Costo', 'Deudora', 3, 0),
(30, 14, '6.01.01', 'Gastos de Personal y Servicios de Tienda', 'Gasto', 'Deudora', 3, 0),
(31, 14, '6.01.02', 'Pérdidas por Mermas y Desincorporaciones', 'Gasto', 'Deudora', 3, 0),
(32, 15, '6.02.01', 'Comisiones y Diferencial Cambiario', 'Gasto', 'Deudora', 3, 0);

-- NIVEL 4: Subcuentas Auxiliares de Detalle (acepta_movimientos = 1)
INSERT INTO plan_cuentas (id_cuenta, id_cuenta_padre, codigo_cuenta, nombre_cuenta, tipo_cuenta, naturaleza, nivel, acepta_movimientos) VALUES
-- Disponibilidades (Cajas, Bancos y POS)
(33, 16, '1.01.01.001', 'Caja Principal Moneda Nacional (Bs)', 'Activo', 'Deudora', 4, 1),
(34, 16, '1.01.01.002', 'Caja Custodia Divisas Efectivo (USD)', 'Activo', 'Deudora', 4, 1),
(35, 16, '1.01.01.003', 'Fondo Fijo de Caja Chica', 'Activo', 'Deudora', 4, 1),
(36, 17, '1.01.02.001', 'Banco Nacional - Cuenta Corriente (Bs)', 'Activo', 'Deudora', 4, 1),
(37, 17, '1.01.02.002', 'Banco Custodia - Cuenta Divisas (USD)', 'Activo', 'Deudora', 4, 1),
(38, 18, '1.01.03.001', 'Puntos de Venta TDD/TDC y Biopago por Liquidar', 'Activo', 'Deudora', 4, 1),
(39, 19, '1.01.04.001', 'Cuentas por Cobrar a Empleados (Faltantes Caja)', 'Activo', 'Deudora', 4, 1),
-- Créditos Fiscales
(40, 20, '1.01.05.001', 'Crédito Fiscal IVA (Compras)', 'Activo', 'Deudora', 4, 1),
(41, 20, '1.01.05.002', 'Retenciones de IVA Soportadas (POS Bancos)', 'Activo', 'Deudora', 4, 1),
-- Inventarios por Departamento (IDs 42 al 48)
(42, 21, '1.01.06.001', 'Inventario de Víveres Exentos', 'Activo', 'Deudora', 4, 1),
(43, 21, '1.01.06.002', 'Inventario de Víveres Gravados y Limpieza', 'Activo', 'Deudora', 4, 1),
(44, 21, '1.01.06.003', 'Inventario de Carnicería, Aves y Pescadería', 'Activo', 'Deudora', 4, 1),
(45, 21, '1.01.06.004', 'Inventario de Charcutería y Lácteos', 'Activo', 'Deudora', 4, 1),
(46, 21, '1.01.06.005', 'Inventario de Fruver (Frutas y Verduras)', 'Activo', 'Deudora', 4, 1),
(47, 21, '1.01.06.006', 'Inventario de Panadería y Pastelería', 'Activo', 'Deudora', 4, 1),
(48, 21, '1.01.06.007', 'Inventario de Licores y Bebidas', 'Activo', 'Deudora', 4, 1),
-- Activos Fijos
(49, 22, '1.02.01.001', 'Cavas Cuarto, Neveras y Equipos de Frío', 'Activo', 'Deudora', 4, 1),
(50, 22, '1.02.01.002', 'Góndolas, Estanterías y Mobiliario de Tienda', 'Activo', 'Deudora', 4, 1),
(51, 22, '1.02.01.003', 'Equipos de Computación, Balanzas y Cajas Fiscales', 'Activo', 'Deudora', 4, 1),
(52, 22, '1.02.01.099', 'Depreciación Acumulada Propiedad y Equipos', 'Activo', 'Acreedora', 4, 1),
-- Pasivos (Cuentas por Pagar e Impuestos por Enterar)
(53, 23, '2.01.01.001', 'Cuentas por Pagar Proveedores de Mercancía', 'Pasivo', 'Acreedora', 4, 1),
(54, 23, '2.01.01.002', 'Cuentas por Pagar Proveedores de Servicios', 'Pasivo', 'Acreedora', 4, 1),
(55, 24, '2.01.02.001', 'Débito Fiscal IVA (Ventas)', 'Pasivo', 'Acreedora', 4, 1),
(56, 24, '2.01.02.002', 'Retenciones de IVA por Enterar (75% / 100%)', 'Pasivo', 'Acreedora', 4, 1),
(57, 24, '2.01.02.003', 'Retenciones de ISLR por Enterar', 'Pasivo', 'Acreedora', 4, 1),
(58, 24, '2.01.02.004', 'IGTF Percibido en Ventas (3%) por Enterar', 'Pasivo', 'Acreedora', 4, 1),
(59, 25, '2.01.03.001', 'Nómina y Beneficios Laborales por Pagar', 'Pasivo', 'Acreedora', 4, 1),
-- Patrimonio
(60, 26, '3.01.01.001', 'Capital Social Suscrito y Pagado', 'Patrimonio', 'Acreedora', 4, 1),
(61, 26, '3.01.01.002', 'Reserva Legal', 'Patrimonio', 'Acreedora', 4, 1),
(62, 26, '3.01.01.003', 'Resultados Acumulados de Ejercicios Anteriores', 'Patrimonio', 'Acreedora', 4, 1),
(63, 26, '3.01.01.004', 'Resultado del Ejercicio Actual', 'Patrimonio', 'Acreedora', 4, 1),
-- Ingresos por Ventas de Departamento (IDs 64 al 71)
(64, 27, '4.01.01.001', 'Ventas de Víveres Exentos', 'Ingreso', 'Acreedora', 4, 1),
(65, 27, '4.01.01.002', 'Ventas de Víveres Gravados y Limpieza', 'Ingreso', 'Acreedora', 4, 1),
(66, 27, '4.01.01.003', 'Ventas de Carnicería, Aves y Pescadería', 'Ingreso', 'Acreedora', 4, 1),
(67, 27, '4.01.01.004', 'Ventas de Charcutería y Lácteos', 'Ingreso', 'Acreedora', 4, 1),
(68, 27, '4.01.01.005', 'Ventas de Fruver (Frutas y Verduras)', 'Ingreso', 'Acreedora', 4, 1),
(69, 27, '4.01.01.006', 'Ventas de Panadería y Pastelería', 'Ingreso', 'Acreedora', 4, 1),
(70, 27, '4.01.01.007', 'Ventas de Licores y Bebidas', 'Ingreso', 'Acreedora', 4, 1),
(71, 27, '4.01.01.099', 'Devoluciones y Rebajas en Ventas POS', 'Ingreso', 'Deudora', 4, 1),
-- Otros Ingresos
(72, 28, '4.02.01.001', 'Sobrantes de Caja en Arqueos POS', 'Ingreso', 'Acreedora', 4, 1),
(73, 28, '4.02.01.002', 'Ganancia por Diferencial Cambiario', 'Ingreso', 'Acreedora', 4, 1),
-- Costos de Venta por Departamento (IDs 74 al 80)
(74, 29, '5.01.01.001', 'Costo de Venta - Víveres Exentos', 'Costo', 'Deudora', 4, 1),
(75, 29, '5.01.01.002', 'Costo de Venta - Víveres Gravados y Limpieza', 'Costo', 'Deudora', 4, 1),
(76, 29, '5.01.01.003', 'Costo de Venta - Carnicería, Aves y Pescadería', 'Costo', 'Deudora', 4, 1),
(77, 29, '5.01.01.004', 'Costo de Venta - Charcutería y Lácteos', 'Costo', 'Deudora', 4, 1),
(78, 29, '5.01.01.005', 'Costo de Venta - Fruver', 'Costo', 'Deudora', 4, 1),
(79, 29, '5.01.01.006', 'Costo de Venta - Panadería y Pastelería', 'Costo', 'Deudora', 4, 1),
(80, 29, '5.01.01.007', 'Costo de Venta - Licores y Bebidas', 'Costo', 'Deudora', 4, 1),
-- Gastos Operativos, Mermas y Financieros (IDs 81 al 88)
(81, 30, '6.01.01.001', 'Sueldos, Salarios y Beneficios de Personal', 'Gasto', 'Deudora', 4, 1),
(82, 30, '6.01.01.002', 'Electricidad, Agua, Internet y Mantenimiento Frío', 'Gasto', 'Deudora', 4, 1),
(83, 30, '6.01.01.003', 'Material de Empaque, Bolsas y Etiquetas de Peso', 'Gasto', 'Deudora', 4, 1),
(84, 31, '6.01.02.001', 'Gasto por Merma de Perecederos (Carnes, Charcutería, Fruver)', 'Gasto', 'Deudora', 4, 1),
(85, 31, '6.01.02.002', 'Gasto por Merma de Víveres (Vencimiento y Roturas)', 'Gasto', 'Deudora', 4, 1),
(86, 32, '6.02.01.001', 'Comisiones Bancarias y Alquiler Puntos de Venta', 'Gasto', 'Deudora', 4, 1),
(87, 32, '6.02.01.002', 'Gasto de IGTF Bancario (Pagos y Transferencias)', 'Gasto', 'Deudora', 4, 1),
(88, 32, '6.02.01.003', 'Pérdida por Diferencial Cambiario', 'Gasto', 'Deudora', 4, 1);

-- ============================================================================
-- 5. CATEGORÍAS / DEPARTAMENTOS DEL SUPERMERCADO (PUENTE CONTABLE AUTOMÁTICO)
-- ============================================================================
INSERT INTO categorias_departamento (id_categoria, nombre_departamento, id_cuenta_inventario, id_cuenta_costo_venta, id_cuenta_ingreso_venta, id_cuenta_gasto_merma) VALUES
(1, 'Víveres Exentos',               42, 74, 64, 85),
(2, 'Víveres Gravados y Limpieza',   43, 75, 65, 85),
(3, 'Carnicería, Aves y Pescadería', 44, 76, 66, 84),
(4, 'Charcutería y Lácteos',         45, 77, 67, 84),
(5, 'Fruver (Frutas y Verduras)',    46, 78, 68, 84),
(6, 'Panadería y Pastelería',        47, 79, 69, 84),
(7, 'Licores y Bebidas',             48, 80, 70, 85);

-- ============================================================================
-- 6. PROVEEDORES DE PRUEBA (VINCULADOS A CUENTA POR PAGAR ID 53)
-- ============================================================================
INSERT INTO proveedores (id_proveedor, rif, razon_social, porcentaje_retencion_iva, id_cuenta_cxp) VALUES
(1, 'J-00006372-9', 'Alimentos Polar Comercial C.A.', 75.00, 53),
(2, 'J-30124589-1', 'Distribuidora Frigorífica Los Llanos C.A.', 75.00, 53),
(3, 'J-40589123-0', 'Suministros y Limpieza Industrial Barquisimeto C.A.', 100.00, 53);

-- ============================================================================
-- 7. PRODUCTOS INICIALES DEL SUPERMERCADO
-- ============================================================================
INSERT INTO productos (id_producto, id_categoria, codigo_barras, nombre_producto, unidad_medida, alicuota_iva, costo_promedio_bs, precio_venta_usd, stock_actual) VALUES
(1, 1, '759100000001', 'Harina de Maíz Precocida 1Kg', 'UND', 0.00,  42.0000, 1.1000, 250.000),
(2, 1, '759100000002', 'Aceite Vegetal Comestible 1Lt', 'LT', 0.00,  95.0000, 2.4000, 120.000),
(3, 2, '759100000003', 'Detergente en Polvo Multiuso 1Kg', 'UND', 16.00, 80.0000, 2.2000, 85.000),
(4, 3, '200000000001', 'Carne de Res de Primera (Solomo)', 'KG', 0.00, 310.0000, 7.8000, 65.500),
(5, 5, '200000000002', 'Tomate Perita Seleccionado', 'KG', 0.00,  45.0000, 1.3000, 90.000);