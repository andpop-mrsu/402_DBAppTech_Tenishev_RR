import { DB } from './db.js';
import { Game } from './game.js';
import { Board } from './board.js';

let curGame = null;
let movesLog = [];
let humanSymbolGlobal = null;

const playerInput = document.getElementById('playerName');
const statusElem = document.getElementById('status');
const gamesTable = document.querySelector('#gamesTable tbody');
const startBtn = document.getElementById('newGameBtn');
const listBtn = document.getElementById('listBtn');
const boardContainer = document.getElementById('boardContainer');
const replayBtn = document.getElementById('replayBtn');
const replayIdInput = document.getElementById('replayId');
const gamesSection = document.getElementById('gamesList');

function setStatus(text) {
  statusElem.textContent = text;
}

function renderBoard(size) {
  boardContainer.innerHTML = '';
  boardContainer.style.display = 'grid';
  boardContainer.style.gridTemplateColumns = `repeat(${size}, 50px)`;
  for (let r = 0; r < size; r++) {
    for (let c = 0; c < size; c++) {
      const cell = document.createElement('div');
      cell.className = 'cell';
      cell.dataset.row = r;
      cell.dataset.col = c;
      cell.addEventListener('click', () => handleMove(r, c));
      boardContainer.appendChild(cell);
    }
  }
}

function updateBoard(board) {
  const cells = document.querySelectorAll('.cell');
  const data = board.getCells();
  cells.forEach(cell => {
    const r = +cell.dataset.row;
    const c = +cell.dataset.col;
    cell.textContent = data[r][c] === '.' ? '' : data[r][c];
  });
}

function handleMove(r, c) {
  if (!curGame) return;
  const board = curGame.getBoard();
  if (!board.isCellEmpty(r, c)) return;

  const current = curGame.getCurrentSymbol();
  board.setCell(r, c, current);
  movesLog.push({ move_number: movesLog.length + 1, player: current, row: r, col: c });

  updateBoard(board);

  if (curGame.checkWin(r, c, current)) {
    curGame.setWinner(current);
    finishGame();
    return;
  }

  if (board.isFull()) {
    finishGame();
    return;
  }

  curGame.switchTurn();

  if (curGame.getCurrentSymbol() !== humanSymbolGlobal) {
    setTimeout(() => computerMove(), 300);
  }
}

function computerMove() {
  const board = curGame.getBoard();
  const empty = [];
  for (let r = 0; r < board.getSize(); r++) {
    for (let c = 0; c < board.getSize(); c++) {
      if (board.isCellEmpty(r, c)) empty.push([r, c]);
    }
  }
  const [r, c] = empty[Math.floor(Math.random() * empty.length)];
  const symbol = curGame.getCurrentSymbol();
  board.setCell(r, c, symbol);
  movesLog.push({ move_number: movesLog.length + 1, player: symbol, row: r, col: c });
  updateBoard(board);

  if (curGame.checkWin(r, c, symbol)) {
    curGame.setWinner(symbol);
    finishGame();
    return;
  }

  if (board.isFull()) {
    finishGame();
    return;
  }

  curGame.switchTurn();
}

async function finishGame() {
  const winner = curGame.getWinner();
  setStatus(winner ? `Winner: ${winner}` : "It's a draw!");

  const gameObj = {
    date: new Date().toISOString(),
    player_name: playerInput.value || 'Anonymous',
    human_symbol: humanSymbolGlobal,
    winner: winner,
    size: curGame.getBoard().getSize(),
    moves: movesLog
  };

  await DB.addGame(gameObj);
  setStatus(`${winner ? 'Game saved. Winner: ' + winner : 'Game saved. Draw.'}`);
}

function startGame() {
  const size = parseInt(document.getElementById('size').value, 10);
  if (isNaN(size) || size < 3 || size > 10) {
    alert('Введите размер поля от 3 до 10');
    return;
  }

  // Скрыть список игр при новой игре
  gamesSection.classList.add('hidden');

  movesLog = [];
  curGame = new Game(size);

  humanSymbolGlobal = Math.random() < 0.5 ? 'X' : 'O';
  curGame.setCurrentSymbol('X');
  curGame.setHumanSymbol(humanSymbolGlobal);

  renderBoard(size);
  setStatus(`Вы играете за ${humanSymbolGlobal}`);

  if (curGame.getCurrentSymbol() !== humanSymbolGlobal) {
    setTimeout(() => computerMove(), 500);
  }
}

async function renderGamesList() {
  const games = await DB.getGames();
  const tbody = gamesTable;
  tbody.innerHTML = '';

  if (!games.length) {
    tbody.innerHTML = '<tr><td colspan="6">Сохранённых игр нет.</td></tr>';
  } else {
    for (const g of games) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${g.id}</td>
        <td>${new Date(g.date).toLocaleString()}</td>
        <td>${g.player_name || '-'}</td>
        <td>${g.human_symbol || '-'}</td>
        <td>${g.winner || '-'}</td>
        <td>${g.size}</td>
      `;
      tbody.appendChild(tr);
    }
  }

  // Сделать секцию видимой
  gamesSection.classList.remove('hidden');
}

async function replayGameById() {
  const gameId = parseInt(replayIdInput.value);
  if (isNaN(gameId)) {
    alert('Введите ID игры для воспроизведения');
    return;
  }

  const game = await DB.getGameById(gameId);
  if (!game || !game.moves) {
    alert(`Игра с ID ${gameId} не найдена.`);
    return;
  }

  const { size, moves, winner } = game;
  curGame = new Game(size);
  renderBoard(size);
  setStatus(`Воспроизведение игры #${gameId}...`);

  for (let i = 0; i < moves.length; i++) {
    const move = moves[i];
    await new Promise(res => setTimeout(res, 500));
    curGame.getBoard().setCell(move.row, move.col, move.player);
    updateBoard(curGame.getBoard());
  }

  setStatus(`Воспроизведение завершено. ${winner ? 'Победитель: ' + winner : 'Ничья.'}`);
}

// события
startBtn.addEventListener('click', startGame);
listBtn.addEventListener('click', renderGamesList);
replayBtn.addEventListener('click', replayGameById);
