<?php
namespace Tenis\TicTacToe;

class CliApp {
    public function run(array $argv): void {
        $options = getopt('hnlr:', ['help','new','list','replay:']);

        if (isset($options['h']) || isset($options['help'])) {
            $this->showHelp();
            return;
        }

        if (isset($options['l']) || isset($options['list'])) {
            echo "List mode is not supported yet.\n";
            return;
        }

        if (isset($options['r']) || isset($options['replay'])) {
            echo "Replay mode is not supported yet.\n";
            return;
        }

        $this->startNewGame();
    }

    private function showHelp(): void {
        echo "Usage: tic-tac-toe [--new|-n] [--list|-l] [--replay ID|-r ID] [--help|-h]\n";
        echo "Board size: 3–10\n";
        echo "Moves: row col (e.g. '1 2')\n";
    }

    private function startNewGame(): void {
        $size = 0;
        while ($size < 3 || $size > 10) {
            echo "Enter board size (3–10): ";
            $size = (int)trim(fgets(STDIN));
        }

        $humanSymbol = random_int(0,1) ? 'X' : 'O';
        $computerSymbol = $humanSymbol === 'X' ? 'O' : 'X';

        $human = new HumanPlayer($humanSymbol);
        $computer = new ComputerPlayer($computerSymbol);

        $playerX = $humanSymbol === 'X' ? $human : $computer;
        $playerO = $humanSymbol === 'O' ? $human : $computer;

        echo "You play as $humanSymbol\n";
        $game = new Game($size, $playerX, $playerO);
        $game->play();
    }
}
