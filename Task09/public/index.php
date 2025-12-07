<?php

use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
use Slim\Psr7\Request;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Подключение к SQLite
$dbPath = __DIR__ . '/../db/games.db';
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0777, true);
}
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Создание таблицы (если её нет)
$db->exec("CREATE TABLE IF NOT EXISTS games (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date TEXT NOT NULL,
    player_name TEXT,
    human_symbol TEXT,
    winner TEXT,
    size INTEGER NOT NULL,
    moves TEXT NOT NULL
)");

// CORS заголовки
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

// Обработка OPTIONS запросов (для CORS)
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// GET / — редирект на index.html
$app->get('/', function ($request, $response) {
    return $response
        ->withHeader('Location', '/index.html')
        ->withStatus(302);
});

//  GET /games — список всех игр
$app->get('/games', function ($request, $response) use ($db) {
    $stmt = $db->query("SELECT * FROM games ORDER BY date DESC");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($games as &$game) {
        $game['moves'] = json_decode($game['moves'], true);
    }
    $response->getBody()->write(json_encode($games));
    return $response->withHeader('Content-Type', 'application/json');
});

// GET /games/{id} — одна игра по ID
$app->get('/games/{id}', function ($request, $response, $args) use ($db) {
    $id = (int) $args['id'];
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) {
        $response->getBody()->write(json_encode(['error' => 'Game not found']));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
    
    $game['moves'] = json_decode($game['moves'], true);
    $response->getBody()->write(json_encode($game));
    return $response->withHeader('Content-Type', 'application/json');
});

// POST /games — создание новой игры
$app->post('/games', function ($request, $response) use ($db) {
    $input = json_decode($request->getBody()->getContents(), true);
    
    if (!$input) {
        $response->getBody()->write(json_encode(['error' => 'Invalid JSON']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    $required = ['date', 'player_name', 'human_symbol', 'winner', 'size', 'moves'];
    foreach ($required as $field) {
        if (!array_key_exists($field, $input)) {
            $response->getBody()->write(json_encode(['error' => "Missing field: $field"]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    $movesJson = json_encode($input['moves']);
    if ($movesJson === false) {
        $response->getBody()->write(json_encode(['error' => 'Invalid moves data']));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
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

    $response->getBody()->write(json_encode(['id' => $db->lastInsertId()]));
    return $response->withHeader('Content-Type', 'application/json');
});

// POST /step/{id} — не реализовано
$app->post('/step/{id}', function ($request, $response, $args) {
    $response->getBody()->write(json_encode(['error' => 'Step-by-step not implemented']));
    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
});

$app->run();