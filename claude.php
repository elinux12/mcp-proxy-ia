<?php
// claude.php – Backend Proxy Estabilizado para MCP

// ----------------------------------------------
// --- Encabezados CORS y Configuración ---
// ----------------------------------------------
// Estos encabezados permiten la conexión desde el frontend (plugin)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
// Incluye todos los encabezados que el frontend envía, incluyendo 'api_key'
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, api_key");
header("Content-Type: application/json; charset=UTF-8");

// Maneja la petición OPTIONS (pre-vuelo de CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'response' => '⟦MCP⟧ → Método no permitido',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// ----------------------------------------------
// --- Recibir Petición del Frontend ---
// ----------------------------------------------
$input = file_get_contents('php://input');

// 1. Validar Cuerpo de Petición
if (empty($input)) {
    http_response_code(400); 
    echo json_encode([
        'success' => false,
        'response' => '⚠️ El cuerpo de la petición está vacío',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

$data = json_decode($input, true);

// 2. Validar JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); 
    echo json_encode([
        'success' => false,
        'response' => '⚠️ JSON malformado o inválido',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// 3. Obtener Datos del Frontend (Usando 'api_key' en minúsculas)
$prompt = $data['prompt'] ?? null;
$apiKey = $data['api_key'] ?? null; 
$provider = strtolower($data['provider'] ?? '');

// 4. Validar Clave API
if (!$apiKey || strlen($apiKey) < 10) {
    http_response_code(401); // No autorizado
    echo json_encode([
        'success' => false,
        'response' => '⚠️ Clave API faltante o inválida',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// 5. Validar Prompt y Proveedor
if (!$prompt || !$provider) {
    http_response_code(400); 
    echo json_encode([
        'success' => false,
        'response' => '⚠️ Prompt o proveedor faltante',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}


// ----------------------------------------------
// --- Lógica de Invocación (cURL Setup) ---
// ----------------------------------------------
$url = '';
$payload = '';
$headers = ["Content-Type: application/json"];
$output = '';

if ($provider === 'claude') {
    $url = 'https://api.anthropic.com/v1/messages';
    $payload = json_encode([
        'model' => 'claude-3-haiku', 
        'max_tokens' => 1000,
        'temperature' => 0.7,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ]);
    $headers[] = "x-api-key: $apiKey";
    $headers[] = "anthropic-version: 2023-06-01";
} elseif ($provider === 'gemini') {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=$apiKey";
    $payload = json_encode([
        'contents' => [
            ['parts' => [['text' => $prompt]]]
        ]
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'response' => '⚠️ Proveedor no reconocido',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// 🧠 Invocación vía cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$result = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'response' => '⚠️ Error al invocar el modelo: ' . $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// ----------------------------------------------
// --- Procesar Respuesta y Devolver al Frontend ---
// ----------------------------------------------
$parsed = json_decode($result, true);

if ($provider === 'claude') {
    // Manejo de error de API
    if(isset($parsed['error'])) {
         http_response_code(400);
         echo json_encode(['success' => false, 'response' => $parsed['error']['message'] ?? 'Error desconocido de la API Claude']);
         exit();
    }
    $output = $parsed['content'][0]['text'] ?? '⚠️ Sin respuesta de Claude';
} elseif ($provider === 'gemini') {
    // Manejo de error de API
    if(isset($parsed['error'])) {
         http_response_code(400);
         echo json_encode(['success' => false, 'response' => $parsed['error']['message'] ?? 'Error desconocido de la API Gemini']);
         exit();
    }
    $output = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? '⚠️ Sin respuesta de Gemini';
}

// 7. Devolver Éxito
http_response_code(200);
echo json_encode([
    'success' => true,
    'response' => $output,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>