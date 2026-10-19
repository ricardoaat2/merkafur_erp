<?php
class ComprasModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarProveedores(): array
    {
        return $this->db->query("SELECT * FROM proveedores ORDER BY razon_social ASC")->fetchAll();
    }

    public function guardarProveedor(array $d): bool
    {
        $sql = "INSERT INTO proveedores (rif, razon_social, porcentaje_retencion_iva, id_cuenta_cxp)
                VALUES (:rif, :razon, :ret, :cxp)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':rif'   => trim($d['rif']),
            ':razon' => trim($d['razon_social']),
            ':ret'   => (float)$d['porcentaje_retencion_iva'],
            ':cxp'   => CTA_CXP_MERCANCIA
        ]);
    }

    public function listarFacturas(?string $estado = null): array
    {
        $where = $estado ? "WHERE f.estado_pago = :est" : "";
        $sql = "SELECT f.*, p.rif, p.razon_social, a.numero_asiento
                FROM facturas_compra f
                INNER JOIN proveedores p ON f.id_proveedor = p.id_proveedor
                INNER JOIN asientos_contables a ON f.id_asiento = a.id_asiento
                {$where}
                ORDER BY f.fecha_recepcion DESC";
        $stmt = $this->db->prepare($sql);
        if ($estado) $stmt->execute([':est' => $estado]);
        else $stmt->execute();
        return $stmt->fetchAll();
    }
}