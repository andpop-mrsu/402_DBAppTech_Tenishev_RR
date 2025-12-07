export class Board {
  constructor(size) {
    this.size = size;
    this.cells = Array.from({ length: size }, () => Array(size).fill('.'));
  }

  getSize() {
    return this.size;
  }

  getCells() {
    return this.cells;
  }

  isCellEmpty(row, col) {
    return this.cells[row][col] === '.';
  }

  setCell(row, col, symbol) {
    this.cells[row][col] = symbol;
  }

  isFull() {
    return this.cells.every(row => row.every(cell => cell !== '.'));
  }
}
