<?php

namespace Tenis\TicTacToe;

class CliApp
{
    private Database $db;

    public function __construct()
    {
        // Создаём папку data, если её нет
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }
        $this->db = new Database($dataDir . DIRECTORY_SEPARATOR . 'tic_tac_toe.sqlite');
    }

    public function run(array $argv): void
    {
        $options = getopt('hnlr:', ['help', 'new', 'list', 'replay:']);

        if (isset($options['h']) || isset($options['help'])) {
            $this->showHelp();
            return;
        }

        if (isset($options['l']) || isset($options['list'])) {
            $this->listGames();
            return;
        }

        if (isset($options['r']) || isset($options['replay'])) {
            $gameId = $options['r'] ?? $options['replay'];
            $this->replayGame((int)$gameId);
            return;
        }

        // Если нет аргументов — показываем меню
        $this->showMenu();
    }

    private function showHelp(): void
    {
        echo "Usage: tic-tac-toe [--new|-n] [--list|-l] [--replay ID|-r ID] [--help|-h]\n";
        echo "Board size: 3–10\n";
        echo "Moves: row col (e.g. '1 2')\n";
    }

    private function showMenu(): void
    {
        while (true) {
            echo "\n=== Tic-Tac-Toe Menu ===\n";
            echo "1. Новая игра\n";
            echo "2. Список игр\n";
            echo "3. Воспроизвести игру\n";
            echo "4. Выход\n";
            echo "Выберите действие (1-4): ";

            $choice = trim(fgets(STDIN));

            switch ($choice) {
                case '1':
                    $this->startNewGame();
                    break;
                case '2':
                    $this->listGames();
                    break;
                case '3':
                    echo "Введите ID игры для воспроизведения: ";
                    $id = (int)trim(fgets(STDIN));
                    $this->replayGame($id);
                    break;
                case '4':
                    echo "Выход...\n";
                    return;
                default:
                    echo "Неверный выбор, попробуйте снова.\n";
            }
        }
    }

    private function startNewGame(): void
    {
        $size = 0;
        while ($size < 3 || $size > 10) {
            echo "Введите размер поля (3–10): ";
            $size = (int)trim(fgets(STDIN));
        }

        echo "Введите ваше имя: ";
        $playerName = trim(fgets(STDIN));

        $humanSymbol = random_int(0, 1) ? 'X' : 'O';
        $computerSymbol = $humanSymbol === 'X' ? 'O' : 'X';

        $human = new HumanPlayer($humanSymbol);
        $computer = new ComputerPlayer($computerSymbol);

        $playerX = $humanSymbol === 'X' ? $human : $computer;
        $playerO = $humanSymbol === 'O' ? $human : $computer;

        echo "Вы играете за $humanSymbol\n";

        $game = new Game($size, $playerX, $playerO);
        $moves = $game->play(true);

        $this->db->saveGame([
            'date' => date('Y-m-d H:i:s'),
            'player_name' => $playerName,
            'human_symbol' => $humanSymbol,
            'winner' => $game->getWinner(),
            'size' => $size
        ], $moves);

        echo "Игра сохранена в базе данных.\n";
    }

    private function listGames(): void
    {
        $games = $this->db->getGames();
        if (!$games) {
            echo "Игр не найдено.\n";
            return;
        }

        echo str_pad("ID", 4) . str_pad("Date", 20) . str_pad("Player", 15)
            . str_pad("Symbol", 8) . str_pad("Winner", 8) . "Size\n";
        echo str_repeat('-', 60) . "\n";

        foreach ($games as $game) {
            echo str_pad($game['id'], 4)
                . str_pad($game['date'], 20)
                . str_pad($game['player_name'], 15)
                . str_pad($game['human_symbol'], 8)
                . str_pad($game['winner'] ?? '-', 8)
                . $game['size'] . "\n";
        }
    }

    private function replayGame(int $gameId): void
    {
        $moves = $this->db->getMoves($gameId);
        if (!$moves) {
            echo "Игра с ID $gameId не найдена или нет ходов.\n";
            return;
        }

        $rows = array_column($moves, 'row');
        $cols = array_column($moves, 'col');

        if (empty($rows) || empty($cols)) {
            echo "Нет ходов для воспроизведения игры ID $gameId.\n";
            return;
        }

        $size = max(max($rows), max($cols)) + 1;
        $board = new Board($size);

        echo "Воспроизведение игры ID $gameId:\n";

        foreach ($moves as $move) {
            $board->setCell((int)$move['row'], (int)$move['col'], $move['player']);
            $board->render();
            usleep(500_000);
        }

        echo "Воспроизведение завершено.\n";
    }
}
