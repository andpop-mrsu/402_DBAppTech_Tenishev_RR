import { Board } from './board.js';

export class Game {
  constructor(size) {
    this.board = new Board(size);
    this.currentSymbol = 'X';
    this.humanSymbol = null;
    this.winner = null;
  }

  getBoard() {
    return this.board;
  }

  getCurrentSymbol() {
    return this.currentSymbol;
  }

  setCurrentSymbol(symbol) {
    this.currentSymbol = symbol;
  }

  switchTurn() {
    this.currentSymbol = this.currentSymbol === 'X' ? 'O' : 'X';
  }

  setHumanSymbol(symbol) {
    this.humanSymbol = symbol;
  }

  getHumanSymbol() {
    return this.humanSymbol;
  }

  setWinner(symbol) {
    this.winner = symbol;
  }

  getWinner() {
    return this.winner;
  }

  checkWin(row, col, symbol) {
    const size = this.board.getSize();
    const cells = this.board.getCells();

    // Проверка строки
    if (cells[row].every(cell => cell === symbol)) return true;

    // Проверка столбца
    if (cells.every(r => r[col] === symbol)) return true;

    // Проверка диагонали
    if (row === col && cells.every((r, i) => r[i] === symbol)) return true;

    // Проверка обратной диагонали
    if (row + col === size - 1 && cells.every((r, i) => r[size - 1 - i] === symbol)) return true;

    return false;
  }
}
