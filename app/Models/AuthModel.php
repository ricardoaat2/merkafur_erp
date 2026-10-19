<?php
// ============================================================================
// ARCHIVO: app/Models/AuthModel.php
// DESCRIPCIÓN: Consultas SQL de Usuarios, Roles y Periodos Contables
// ============================================================================

class AuthModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Busca un usuario activo por su username junto con el nombre de su rol
     */
    public function obtenerUsuarioPorUsername(string $username): ?array
    {
        $sql = "SELECT u.id_usuario, u.id_rol, u.nombre_completo, u.username, 
                       u.password_hash, u.activo, r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.username = :username AND u.activo = 1
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lista todos los usuarios registrados en el ERP
     */
    public function listarUsuarios(): array
    {
        $sql = "SELECT u.id_usuario, u.nombre_completo, u.username, u.activo, r.id_rol, r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                ORDER BY u.id_usuario ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Lista los roles disponibles (Administrador y Contador)
     */
    public function listarRoles(): array
    {
        return $this->db->query("SELECT id_rol, nombre_rol FROM roles ORDER BY id_rol ASC")->fetchAll();
    }

    /**
     * Crea un nuevo usuario con contraseña encriptada BCRYPT
     */
    public function crearUsuario(int $idRol, string $nombreCompleto, string $username, string $passwordPlano): bool
    {
        $hash = password_hash($passwordPlano, PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuarios (id_rol, nombre_completo, username, password_hash, activo)
                VALUES (:id_rol, :nombre, :username, :hash, 1)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_rol'   => $idRol,
            ':nombre'   => $nombreCompleto,
            ':username' => $username,
            ':hash'     => $hash
        ]);
    }

    /**
     * Activa o desactiva un usuario
     */
    public function cambiarEstadoUsuario(int $idUsuario, int $nuevoEstado): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET activo = :estado WHERE id_usuario = :id");
        return $stmt->execute([':estado' => $nuevoEstado, ':id' => $idUsuario]);
    }

    /**
     * Lista todos los periodos contables fiscales
     */
    public function listarPeriodos(): array
    {
        $sql = "SELECT id_periodo, anio_fiscal, mes_fiscal, tasa_bcv_vigente, estado
                FROM periodos_contables
                ORDER BY anio_fiscal DESC, mes_fiscal DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Actualiza la tasa de cambio BCV del periodo actualmente Abierto
     * y registra la fecha/hora exacta de sincronización
     */
    public function actualizarTasaPeriodoAbierto(float $nuevaTasa): bool
    {
        if ($nuevaTasa <= 0) {
            throw new Exception("La tasa de cambio debe ser mayor a cero.");
        }
        $stmt = $this->db->prepare(
            "UPDATE periodos_contables 
             SET tasa_bcv_vigente = :tasa, 
                 ultima_sincronizacion_tasa = NOW() 
             WHERE estado = 'Abierto'"
        );
        return $stmt->execute([':tasa' => round($nuevaTasa, 4)]);
    }

    /**
     * Se conecta mediante cURL a la API de DolarVzla usando la API Key,
     * extrae la tasa oficial BCV y actualiza automáticamente la base de datos.
     */
   public function sincronizarTasaDesdeApi(): array
    {
        // Clave directa para descartar problemas de carga de constantes
        $apiKey = '4d1e04868f1ac59d4fb3d218b3b19571eaaecf17d0006163833adf895f21046a';
        $apiUrl = 'https://rates.dolarvzla.com/bcv/current.json';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'x-dolarvzla-key: ' . $apiKey
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            throw new Exception("Error de conexión cURL: " . $curlError);
        }

        $data = json_decode($response, true);
        $tasaExtraida = $this->extraerValorBcvDeJson($data ?? []);

        if ($tasaExtraida <= 0) {
            throw new Exception("No se pudo extraer la tasa del JSON devuelto.");
        }

        $this->actualizarTasaPeriodoAbierto($tasaExtraida);

        return [
            'tasa_bcv' => round($tasaExtraida, 4),
            'fecha'    => date('d/m/Y h:i A')
        ];
    }

    /**
     * Busca el precio del BCV en diferentes estructuras posibles del JSON de la API
     */
    private function extraerValorBcvDeJson(array $data): float
{
    // Lee directamente current.usd según la respuesta de la API
    if (isset($data['current']['usd']) && is_numeric($data['current']['usd'])) {
        return (float) $data['current']['usd'];
    }
    return 0.0;
}

    /**
     * Verifica si la tasa BCV aún no se ha sincronizado hoy; de ser así, intenta actualizarla sola
     */
    public function autoSincronizarTasaDiaria(): void
    {
        try {
            $stmt = $this->db->query(
                "SELECT ultima_sincronizacion_tasa FROM periodos_contables WHERE estado = 'Abierto' LIMIT 1"
            );
            $ultimaSync = $stmt->fetchColumn();

            // Si nunca se ha sincronizado o la última sincronización no fue hoy, consultar API
            if (empty($ultimaSync) || substr($ultimaSync, 0, 10) !== date('Y-m-d')) {
                $this->sincronizarTasaDesdeApi();
            }
        } catch (Exception $e) {
            // Si no hay internet al iniciar sesión, falla en silencio y mantiene la última tasa guardada en BD
        }
    }

    /**
     * Cierra el periodo actual y abre un nuevo mes contable (Exclusivo Contador)
     */
    public function abrirNuevoPeriodo(int $anio, int $mes, float $tasaInicial): bool
    {
        $this->db->beginTransaction();
        try {
            // 1. Cerrar cualquier periodo que esté abierto actualmente
            $this->db->exec("UPDATE periodos_contables SET estado = 'Cerrado' WHERE estado = 'Abierto'");

            // 2. Insertar o reabrir el nuevo periodo solicitado
            $sql = "INSERT INTO periodos_contables (anio_fiscal, mes_fiscal, tasa_bcv_vigente, estado)
                    VALUES (:anio, :mes, :tasa, 'Abierto')
                    ON DUPLICATE KEY UPDATE tasa_bcv_vigente = :tasa_upd, estado = 'Abierto'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':anio'     => $anio,
                ':mes'      => $mes,
                ':tasa'     => $tasaInicial,
                ':tasa_upd' => $tasaInicial
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}