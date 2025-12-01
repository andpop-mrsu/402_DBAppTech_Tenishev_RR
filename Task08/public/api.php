<?php
// public/api.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Получаем путь из PATH_INFO (работает с php -S)
$path = $_SERVER['PATH_INFO'] ?? '/';
$path = rtrim($path, '/') ?: '/';

// Подключение к SQLite
$dbPath = __DIR__ . '/../db/games.db';
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0777, true);
}
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// Создание таблицы
$db->exec("CREATE TABLE IF NOT EXISTS games (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date TEXT NOT NULL,
    player_name TEXT,
    human_symbol TEXT,
    winner TEXT,
    size INTEGER NOT NULL,
    moves TEXT NOT NULL
)");

$method = $_SERVER['REQUEST_METHOD'];

// --- GET /games ---
if ($method === 'GET' && $path === '/games') {
    $stmt = $db->query("SELECT * FROM games ORDER BY date DESC");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($games as &$game) {
        $game['moves'] = json_decode($game['moves'], true);
    }
    echo json_encode($games);
    exit;
}

// --- GET /games/123 ---
if ($method === 'GET' && preg_match('#^/games/(\d+)$#', $path, $matches)) {
    $id = (int)$matches[1];
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game) {
        http_response_code(404);
        echo json_encode(['error' => 'Game not found']);
        exit;
    }
    $game['moves'] = json_decode($game['moves'], true);
    echo json_encode($game);
    exit;
}

// --- POST /games ---
if ($method === 'POST' && $path === '/games') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $required = ['date', 'player_name', 'human_symbol', 'winner', 'size', 'moves'];
    foreach ($required as $field) {
        if (!array_key_exists($field, $input)) {
            http_response_code(400);
            echo json_encode(['error' => "Missing field: $field"]);
            exit;
        }
    }

    $movesJson = json_encode($input['moves']);
    if ($movesJson === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid moves data']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO games (date, player_name, human_symbol, winner, size, moves) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['date'],
        $input['player_name'],
        $input['human_symbol'],
        $input['winner'],
        $input['size'],
        $movesJson
    ]);

    echo json_encode(['id' => $db->lastInsertId()]);
    exit;
}

// --- POST /step/123 ---
if ($method === 'POST' && preg_match('#^/step/(\d+)$#', $path, $matches)) {
    http_response_code(400);
    echo json_encode(['error' => 'Step-by-step not implemented']);
    exit;
}

// --- 404 ---
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found: ' . $path]);