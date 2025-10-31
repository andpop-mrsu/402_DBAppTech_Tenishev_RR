<?php

namespace Tenis\TicTacToe;

class HumanPlayer extends Player
{
    public function makeMove(Board $board): array
    {
        while (true) {
            echo "Your move ({$this->symbol}), enter 'row col': ";
            $input = trim(fgets(STDIN));
            $parts = preg_split('/\s+/', $input);
            if (count($parts) < 2) {
                echo "Invalid input, use format: row col\n";
                continue;
            }
            [$row, $col] = $parts;
            $row = (int)$row - 1;
            $col = (int)$col - 1;
            if ($row >= 0 && $row < $board->getSize() && $col >= 0 && $col < $board->getSize() && $board->isCellEmpty($row, $col)) {
                return [$row, $col];
            }
            echo "Invalid move, try again.\n";
        }
    }
}
