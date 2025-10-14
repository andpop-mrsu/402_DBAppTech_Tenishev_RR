<?php

namespace Tenis\TicTacToe;

use PDO;

class Database
{
    private PDO $pdo;

    public function __construct(string $dbFile)
    {
        $this->pdo = new PDO("sqlite:$dbFile");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initSchema();
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date TEXT,
                player_name TEXT,
                human_symbol TEXT,
                winner TEXT,
                size INTEGER
            );
        ");
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS moves (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER,
                move_number INTEGER,
                player TEXT,
                row INTEGER,
                col INTEGER,
                FOREIGN KEY(game_id) REFERENCES games(id)
            );
        ");
    }

    public function saveGame(array $gameData, array $moves): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO games (date, player_name, human_symbol, winner, size)
            VALUES (:date, :player_name, :human_symbol, :winner, :size)
        ");
        $stmt->execute($gameData);
        $gameId = (int)$this->pdo->lastInsertId();

        $stmtMove = $this->pdo->prepare("
            INSERT INTO moves (game_id, move_number, player, row, col)
            VALUES (:game_id, :move_number, :player, :row, :col)
        ");
        foreach ($moves as $move) {
            $stmtMove->execute(array_merge(['game_id' => $gameId], $move));
        }

        return $gameId;
    }

    public function getGames(): array
    {
        return $this->pdo->query("SELECT * FROM games ORDER BY date DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMoves(int $gameId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM moves WHERE game_id = :game_id ORDER BY move_number ASC");
        $stmt->execute(['game_id' => $gameId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
