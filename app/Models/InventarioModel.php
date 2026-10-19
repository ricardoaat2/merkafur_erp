<?php
class InventarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listarProductos(): array
    {
        $sql = "SELECT p.*, d.nombre_departamento 
                FROM productos p
                INNER JOIN categorias_departamento d ON p.id_categoria = d.id_categoria
                ORDER BY p.nombre_producto ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerProductoPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, d.id_cuenta_inventario, d.id_cuenta_gasto_merma 
             FROM productos p
             INNER JOIN categorias_departamento d ON p.id_categoria = d.id_categoria
             WHERE p.id_producto = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}