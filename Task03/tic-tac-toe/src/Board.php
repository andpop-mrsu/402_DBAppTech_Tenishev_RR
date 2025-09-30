<?php
namespace Tenis\TicTacToe;

class Board {
    private int $size;
    private array $cells;

    public function __construct(int $size) {
        $this->size = $size;
        $this->cells = array_fill(0, $size, array_fill(0, $size, '.'));
    }

    public function getSize(): int {
        return $this->size;
    }

    public function getCells(): array {
        return $this->cells;
    }

    public function isCellEmpty(int $row, int $col): bool {
        return $this->cells[$row][$col] === '.';
    }

    public function setCell(int $row, int $col, string $symbol): void {
        $this->cells[$row][$col] = $symbol;
    }

    public function isFull(): bool {
        foreach ($this->cells as $row) {
            if (in_array('.', $row, true)) return false;
        }
        return true;
    }

    public function render(): void {
        echo "   ";
        for ($c = 1; $c <= $this->size; $c++) echo $c . ' ';
        echo PHP_EOL;
        foreach ($this->cells as $i => $row) {
            echo str_pad((string)($i + 1), 2, ' ', STR_PAD_LEFT) . ' ';
            echo implode(' ', $row) . PHP_EOL;
        }
    }
}
