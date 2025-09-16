<?php
namespace tenishevR\TicTacToe\View;

class View {
    public static function renderStartScreen() {
        if (function_exists('\cli\line')) {
            \cli\line("=== Игра 'Крестики-нолики' ===");
            \cli\line("Меню:");
            \cli\line("1) Начать новую игру");
            \cli\line("2) Список сохранённых партий");
            \cli\line("3) Повтор партии");
            \cli\line("4) Выход");
            \cli\line("");
        } else {
            echo "=== Игра 'Крестики-нолики' ===\n";
            echo "Меню:\n";
            echo "1) Начать новую игру\n";
            echo "2) Список сохранённых партий\n";
            echo "3) Повтор партии\n";
            echo "4) Выход\n\n";
        }
    }
}