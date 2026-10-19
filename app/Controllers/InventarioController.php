<?php
class InventarioController extends Controller
{
    private InventarioModel $invModel;
    private MotorContable $motor;

    public function __construct()
    {
        $this->invModel = new InventarioModel();
        $this->motor    = new MotorContable();
    }

    public function index(): void
    {
        $this->render('inventario/inventario_mermas', [
            'titulo'    => 'Control de Inventario, Kardex y Mermas',
            'productos' => $this->invModel->listarProductos()
        ]);
    }

    /**
     * Registra un Acta de Merma (vencimiento o rotura) descontando el stock
     * y generando el asiento contable al costo promedio ponderado.
     */
    public function registrarMerma(): void
    {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $idProd   = (int)($_POST['id_producto'] ?? 0);
            $cantMerma= (float)($_POST['cantidad'] ?? 0);
            $motivo   = trim($_POST['motivo'] ?? 'Merma operativa');
            $fecha    = $_POST['fecha'] ?? date('Y-m-d');

            $prod = $this->invModel->obtenerProductoPorId($idProd);
            if (!$prod) throw new Exception("Producto no encontrado.");

            if ($cantMerma <= 0 || $cantMerma > (float)$prod['stock_actual']) {
                throw new Exception("Cantidad de merma inválida o excede el stock actual disponible.");
            }

            $costoUnitario = (float)$prod['costo_promedio_bs'];
            $costoTotal    = round($cantMerma * $costoUnitario, 2);
            $stockNuevo    = round((float)$prod['stock_actual'] - $cantMerma, 3);
            $numActa       = 'MER-' . date('Ymd') . '-' . rand(100, 999);

            // Construir Asiento Contable Automático de Merma
            $lineasAsiento = [
                [
                    'id_cuenta'   => (int)$prod['id_cuenta_gasto_merma'],
                    'descripcion' => "Gasto por Merma - {$prod['nombre_producto']} ({$cantMerma} {$prod['unidad_medida']})",
                    'debe_bs'     => $costoTotal,
                    'haber_bs'    => 0.00
                ],
                [
                    'id_cuenta'   => (int)$prod['id_cuenta_inventario'],
                    'descripcion' => "Desincorporación Inventario - Ref: {$numActa}",
                    'debe_bs'     => 0.00,
                    'haber_bs'    => $costoTotal
                ]
            ];

            $idAsiento = $this->motor->registrarAsiento(
                $fecha,
                'Inventario_Merma',
                "Acta de Merma #{$numActa}: {$motivo}",
                $lineasAsiento,
                $numActa
            );

            // Actualizar stock en la tabla productos
            $stmtUpd = $db->prepare("UPDATE productos SET stock_actual = :stock WHERE id_producto = :id");
            $stmtUpd->execute([':stock' => $stockNuevo, ':id' => $idProd]);

            // Registrar movimiento en el Kardex
            $sqlKardex = "INSERT INTO kardex_inventario 
                            (id_producto, id_asiento, fecha_movimiento, tipo_movimiento, cantidad, costo_unitario_bs, costo_total_bs, stock_resultante)
                          VALUES 
                            (:prod, :asiento, :fecha, 'Merma_Rotura_Vencimiento', :cant, :costoU, :costoT, :stockR)";
            $stmtKar = $db->prepare($sqlKardex);
            $stmtKar->execute([
                ':prod'    => $idProd,
                ':asiento' => $idAsiento,
                ':fecha'   => $fecha . ' ' . date('H:i:s'),
                ':cant'    => $cantMerma,
                ':costoU'  => $costoUnitario,
                ':costoT'  => $costoTotal,
                ':stockR'  => $stockNuevo
            ]);

            $db->commit();
            $this->jsonResponse(true, "Acta de merma registrada y contabilizada correctamente.");
        } catch (Exception $e) {
            $db->rollBack();
            $this->jsonResponse(false, "Error al registrar merma: " . $e->getMessage(), [], 400);
        }
    }
}