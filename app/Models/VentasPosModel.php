<?php
class VentasPosModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarCierresZ(): array
    {
        $sql = "SELECT z.*, a.numero_asiento 
                FROM cierres_caja_z z
                INNER JOIN asientos_contables a ON z.id_asiento = a.id_asiento
                ORDER BY z.fecha_operacion DESC, z.id_cierre_z DESC";
        return $this->db->query($sql)->fetchAll();
    }
}