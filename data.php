<?php
header("Content-Type: application/json; charset=utf-8");

// Configuración
include 'config.php';

/****  
 * GET: Obtener eventos
 * POST: Crear nuevo evento
 * PUT: Actualizar evento existente
 * DELETE: Eliminar evento
 * ********/

try {
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') { 
        // Método PUT para actualizar solo id_calendar_event
        $input = file_get_contents('php://input');
        $data = json_decode($input, true)['body'];
        # echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // Validación básica
        if (!$data || !isset($data['id_list']) || !isset($data['dia']) || !isset($data['hora']) || !isset($data['classe']) || !isset($data['id_calendar_event'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Datos no válidos (se requiere id_list, dia, hora, classe, id_calendar_event)"]);
            exit;
        }
        $id_list = filter_var($data['id_list'], FILTER_SANITIZE_STRING);
        $dia = filter_var($data['dia'], FILTER_SANITIZE_STRING);
        $classe = filter_var($data['classe'], FILTER_SANITIZE_STRING);
        $hora = filter_var($data['hora'], FILTER_SANITIZE_STRING);
        $id_calendar_event = filter_var($data['id_calendar_event'], FILTER_SANITIZE_STRING);

        if ($id_list === false || $id_calendar_event === false || $dia === false || $classe === false || $hora === false) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID o id_calendar_event no válido"]);
            exit;
        }

        $db = new PDO("sqlite:$db_file");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si el evento existe
        $checkStmt = $db->prepare("SELECT id FROM eventos WHERE dia = :dia AND hora = :hora AND classe = :classe AND id_list = :id_list");
        $checkStmt->execute([
            ':dia' => $dia,
            ':hora' => $hora,
            ':classe' => $classe,
            ':id_list' => $id_list
        ]);

        $resultado = $checkStmt->fetch();
        if ($resultado === false) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Evento no encontrado"]);
            exit;
        }

        // Actualizar solo id_calendar_event
        $stmt = $db->prepare("UPDATE eventos SET id_calendar_event = :id_calendar_event WHERE id = :id");
        $stmt->execute([
            ':id' => $resultado['id'],
            ':id_calendar_event' => $id_calendar_event
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "id_calendar_event actualizado",
            "updated_id" => $resultado['id'],
            "changes" => $stmt->rowCount()
        ]);
        exit;
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Método POST para crear nuevo evento o actualizar
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        // Validación básica
        if (!$data || !isset($data['body'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Datos no válidos"]);
            exit;
        }

        // Extraer y sanitizar datos
        $body = $data['body'];
        $nom = filter_var($body['nom'] ?? '', FILTER_SANITIZE_STRING);
        $email = filter_var($body['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $titol = filter_var($body['titol'] ?? '', FILTER_SANITIZE_STRING);
        $descripcio = filter_var($body['descripcio'] ?? '', FILTER_SANITIZE_STRING);
        $data_ini = filter_var($body['data_ini'] ?? '', FILTER_SANITIZE_STRING);
        $data_fi = filter_var($body['data_fi'] ?? '', FILTER_SANITIZE_STRING);
        $json_horas = $body['json_horas'] ?? '[]';
        $id_list = filter_var($body['id_list'] ?? '', FILTER_SANITIZE_STRING);

        // Decodificar JSON de horas
        $horas = json_decode($json_horas, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($horas)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Formato de horas no válido"]);
            exit;
        }

        // Validar datos requeridos
        if (empty($nom) || empty($email) || empty($titol) || empty($horas) || empty($id_list)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Faltan datos requeridos"]);
            exit;
        }

        // Conectar a SQLite
        $db = new PDO("sqlite:$db_file");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si existen registros con ese id_list
        $checkStmt = $db->prepare("SELECT id FROM eventos WHERE id_list = :id_list");
        $checkStmt->execute([':id_list' => $id_list]);
        $existen = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($existen) {
            // Poner status=1 a los registros existentes con ese id_list
            $updateStatus = $db->prepare("UPDATE eventos SET status = 1 WHERE id_list = :id_list");
            $updateStatus->execute([':id_list' => $id_list]);
        }

        $timestamp = time();
        $insertados = 0;
        $insertedIds = [];
        $errores = [];

        foreach ($horas as $hora) {
            $classe = filter_var($hora['classe'] ?? '', FILTER_SANITIZE_STRING);
            $dia = filter_var($hora['dia'] ?? '', FILTER_SANITIZE_STRING);
            $hora_time = filter_var($hora['hora'] ?? '', FILTER_SANITIZE_STRING);

            // Validar formato de fecha (dd/mm/yyyy)
            if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dia)) {
                $errores[] = "Formato de fecha inválido: $dia";
                continue;
            }

            try {
                $stmt = $db->prepare("
                    INSERT INTO eventos (
                        timestamp, nom, email, titol, descripcio,
                        data_ini, data_fi, classe, dia, hora, id_list, status
                    ) VALUES (
                        :timestamp, :nom, :email, :titol, :descripcio,
                        :data_ini, :data_fi, :classe, :dia, :hora, :id_list, 0
                    )
                ");
                $stmt->execute([
                    ':timestamp' => $timestamp,
                    ':nom' => $nom,
                    ':email' => $email,
                    ':titol' => $titol,
                    ':descripcio' => $descripcio,
                    ':data_ini' => $data_ini,
                    ':data_fi' => $data_fi,
                    ':classe' => $classe,
                    ':dia' => $dia,
                    ':hora' => $hora_time,
                    ':id_list' => $id_list
                ]);
                $insertados++;
                $insertedIds[] = $db->lastInsertId();
            } catch (PDOException $e) {
                $errores[] = "Error insertando hora $hora_time: " . $e->getMessage();
            }
        }

        // Construir respuesta
        $response = [
            "status" => $insertados > 0 ? "success" : "partial",
            "message" => ($existen ? "Se actualizaron status y se insertaron $insertados registros" : "Se insertaron $insertados registros"),
            "insertados" => $insertados,
            "inserted_ids" => $insertedIds,
            "total" => count($horas)
        ];

        if (!empty($errores)) {
            $response["errores"] = $errores;
        }

        if ($insertados === 0) {
            http_response_code(400);
        }

        echo json_encode($response);

    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Método GET para obtener eventos
        $db = new PDO("sqlite:$db_file");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Construir consulta con filtros opcionales
        $query = "SELECT * FROM eventos WHERE 1=1";
        $params = [];

        if (isset($_GET['date']) && !empty($_GET['date'])) {
            // Convertir fecha de yyyy-mm-dd a dd/mm/yyyy (formato almacenado)
            $filterDate = DateTime::createFromFormat('Y-m-d', $_GET['date']);
            if ($filterDate === false) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Formato de fecha inválido. Use YYYY-MM-DD"]);
                exit;
            }
            $filterDate = $filterDate->format('d/m/Y');
            $query .= " AND dia = :dia";
            $params[':dia'] = $filterDate;
        }

        $query .= " ORDER BY dia, hora";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "count" => count($resultados),
            "data" => $resultados
        ]);
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Método DELETE para eliminar un evento
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID no proporcionado"]);
            exit;
        }
        
        $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($id === false) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID no válido"]);
            exit;
        }
        
        $db = new PDO("sqlite:$db_file");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar si existe el evento
        $checkStmt = $db->prepare("SELECT id FROM eventos WHERE id = :id");
        $checkStmt->execute([':id' => $id]);
        
        if ($checkStmt->fetch() === false) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Evento no encontrado"]);
            exit;
        }
        
        // Eliminar el evento
        $stmt = $db->prepare("DELETE FROM eventos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        echo json_encode([
            "status" => "success",
            "message" => "Evento eliminado",
            "deleted_id" => $id,
            "affected_rows" => $stmt->rowCount()
        ]);
        
    } else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Método no permitido"]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error de base de datos",
        "details" => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error del servidor",
        "details" => $e->getMessage()
    ]);
}
?>