<?php
// ============================================================================
// ARCHIVO: config/config.php
// DESCRIPCIÓN: Configuración global, credenciales SQL y constantes contables
// ============================================================================

// Configuración de zona horaria (Venezuela)
date_default_timezone_set('America/Caracas');

// URL Base del Proyecto (Ajusta el nombre de la carpeta según tu servidor local)
define('BASE_URL', 'http://localhost/merkafur-erp/public/');
define('APP_NAME', 'Merkafur ERP Contable');

// Credenciales de Base de Datos MySQL / MariaDB
define('DB_HOST', 'localhost');
define('DB_NAME', 'merkafur_erp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Parámetros Fiscales por Defecto
define('IVA_GENERAL', 16.00);
define('IGTF_DIVISAS', 3.00);


// ============================================================================
// CONFIGURACIÓN DE API EXTERNA PARA TASA BCV (DolarVzla)
// ============================================================================
define('DOLARVZLA_API_URL', 'https://rates.dolarvzla.com/bcv/current.json');
define('DOLARVZLA_API_KEY', '4d1e04868f1ac59d4fb3d218b3b19571eaaecf17d0006163833adf895f21046a');
define('DOLARVZLA_TIMEOUT', 8);

// ============================================================================
// MAPA DE IDs DE CUENTAS CONTABLES FIJAS (Según 02_seed_inicial.sql)
// ============================================================================
define('CTA_CAJA_BS',             33); // 1.01.01.001 - Caja Principal Moneda Nacional (Bs)
define('CTA_CAJA_USD',            34); // 1.01.01.002 - Caja Custodia Divisas Efectivo (USD)
define('CTA_CAJA_CHICA',          35); // 1.01.01.003 - Fondo Fijo de Caja Chica
define('CTA_BANCO_BS',            36); // 1.01.02.001 - Banco Nacional - Cuenta Corriente (Bs)
define('CTA_BANCO_USD',           37); // 1.01.02.002 - Banco Custodia - Cuenta Divisas (USD)
define('CTA_POS_POR_LIQUIDAR',    38); // 1.01.03.001 - Puntos de Venta TDD/TDC y Biopago por Liquidar
define('CTA_FALTANTE_CAJA',       39); // 1.01.04.001 - Cuentas por Cobrar a Empleados (Faltantes Caja)
define('CTA_CREDITO_FISCAL_IVA',  40); // 1.01.05.001 - Crédito Fiscal IVA (Compras)
define('CTA_RET_IVA_SOPORTADA',   41); // 1.01.05.002 - Retenciones de IVA Soportadas (POS Bancos)

define('CTA_CXP_MERCANCIA',       53); // 2.01.01.001 - Cuentas por Pagar Proveedores de Mercancía
define('CTA_CXP_SERVICIOS',       54); // 2.01.01.002 - Cuentas por Pagar Proveedores de Servicios
define('CTA_DEBITO_FISCAL_IVA',   55); // 2.01.02.001 - Débito Fiscal IVA (Ventas)
define('CTA_RET_IVA_POR_ENTERAR', 56); // 2.01.02.002 - Retenciones de IVA por Enterar (75% / 100%)
define('CTA_RET_ISLR_POR_ENTERAR',57); // 2.01.02.003 - Retenciones de ISLR por Enterar
define('CTA_IGTF_POR_ENTERAR',    58); // 2.01.02.004 - IGTF Percibido en Ventas (3%) por Enterar

define('CTA_SOBRANTE_CAJA',       72); // 4.02.01.001 - Sobrantes de Caja en Arqueos POS
define('CTA_COMISION_BANCARIA',   86); // 6.02.01.001 - Comisiones Bancarias y Alquiler Puntos de Venta
define('CTA_GASTO_IGTF_BANCARIO', 87); // 6.02.01.002 - Gasto de IGTF Bancario