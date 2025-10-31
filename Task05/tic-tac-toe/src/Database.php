<?php

namespace Tenis\TicTacToe;

use RedBeanPHP\R as R;

class Database
{
    public function __construct(string $dbFile)
    {
        R::setup("sqlite:$dbFile");
        // Автоматически создаёт таблицы и поля при первом запуске
        R::freeze(false);
    }

    /**
     * Сохранение игры и её ходов
     */
    public function saveGame(array $gameData, array $moves): int
    {
        // Создаём объект игры
        $game = R::dispense('games');
        $game->date         = $gameData['date'];
        $game->player_name  = $gameData['player_name'];
        $game->human_symbol = $gameData['human_symbol'];
        $game->winner       = $gameData['winner'];
        $game->size         = $gameData['size'];

        // Добавляем ходы через связь ownMoves
        foreach ($moves as $m) {
            $move = R::dispense('moves');
            $move->move_number = $m['move_number'];
            $move->player      = $m['player'];
            $move->row         = $m['row'];
            $move->col         = $m['col'];

            $game->ownMoves[] = $move;
        }

        return R::store($game);
    }

    /**
     * Получение списка игр
     */
    public function getGames(): array
    {
        $games = R::findAll('games', 'ORDER BY date DESC');
        $result = [];

        foreach ($games as $g) {
            $result[] = [
                'id'           => $g->id,
                'date'         => $g->date,
                'player_name'  => $g->player_name,
                'human_symbol' => $g->human_symbol,
                'winner'       => $g->winner,
                'size'         => $g->size
            ];
        }

        return $result;
    }

    /**
     * Получение ходов конкретной игры
     */
    public function getMoves(int $gameId): array
    {
        $game = R::load('games', $gameId);
        if (!$game->id) {
            return [];
        }

        $result = [];
        foreach ($game->ownMoves as $m) {
            $result[] = [
                'move_number' => $m->move_number,
                'player'      => $m->player,
                'row'         => $m->row,
                'col'         => $m->col
            ];
        }

        // Сортируем по move_number на всякий случай
        usort($result, fn($a, $b) => $a['move_number'] <=> $b['move_number']);

        return $result;
    }
}
