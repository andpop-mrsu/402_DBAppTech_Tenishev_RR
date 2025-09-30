<?php
namespace Tenis\TicTacToe;

class Game {
    private Board $board;
    private Player $playerX;
    private Player $playerO;
    private string $current = 'X';
    private ?string $winner = null;

    public function __construct(int $size, Player $playerX, Player $playerO) {
        $this->board = new Board($size);
        $this->playerX = $playerX;
        $this->playerO = $playerO;
    }

    public function play(): void {
        while (!$this->isOver()) {
            $this->board->render();
            $player = $this->current === 'X' ? $this->playerX : $this->playerO;
            [$row, $col] = $player->makeMove($this->board);
            $this->board->setCell($row, $col, $player->getSymbol());

            if ($this->checkWin($row, $col, $player->getSymbol())) {
                $this->winner = $player->getSymbol();
                break;
            }
            if ($this->board->isFull()) break;

            $this->current = $this->current === 'X' ? 'O' : 'X';
        }

        $this->board->render();
        if ($this->winner) {
            echo "Winner: {$this->winner}\n";
        } else {
            echo "It's a draw!\n";
        }
    }

    private function isOver(): bool {
        return $this->winner !== null || $this->board->isFull();
    }

    private function checkWin(int $row, int $col, string $symbol): bool {
        $size = $this->board->getSize();
        $cells = $this->board->getCells();

        // row
        if (count(array_unique($cells[$row])) === 1) return true;

        // col
        $colVals = array_column($cells, $col);
        if (count(array_unique($colVals)) === 1) return true;

        // diag main
        if ($row === $col) {
            $diag = [];
            for ($i = 0; $i < $size; $i++) $diag[] = $cells[$i][$i];
            if (count(array_unique($diag)) === 1) return true;
        }

        // diag anti
        if ($row + $col === $size - 1) {
            $diag = [];
            for ($i = 0; $i < $size; $i++) $diag[] = $cells[$i][$size - 1 - $i];
            if (count(array_unique($diag)) === 1) return true;
        }

        return false;
    }
}
