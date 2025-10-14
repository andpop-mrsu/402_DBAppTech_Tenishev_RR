<?php

namespace Tenis\TicTacToe;

class ComputerPlayer extends Player
{
    public function makeMove(Board $board): array
    {
        $empty = [];
        foreach ($board->getCells() as $r => $row) {
            foreach ($row as $c => $cell) {
                if ($cell === '.') {
                    $empty[] = [$r, $c];
                }
            }
        }
        $choice = $empty[array_rand($empty)];
        echo "Computer ({$this->symbol}) moves: " . ($choice[0] + 1) . " " . ($choice[1] + 1) . PHP_EOL;
        return $choice;
    }
}
