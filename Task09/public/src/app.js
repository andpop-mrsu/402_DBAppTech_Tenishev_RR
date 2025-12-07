// public/src/app.js

import { Game } from './game.js';
import { Board } from './board.js';

let curGame = null;
let movesLog = [];
let humanSymbolGlobal = null;
let gameFinished = false;

const API_BASE = '';

const playerInput = document.getElementById('playerName');
const statusElem = document.getElementById('status');
const gamesTable = document.querySelector('#gamesTable tbody');
const startBtn = document.getElementById('newGameBtn');
const listBtn = document.getElementById('listBtn');
const boardContainer = document.getElementById('boardContainer');
const gamesSection = document.getElementById('gamesList');

// Утилита для запросов
async function apiRequest(url, options = {}) {
  const response = await fetch(API_BASE + url, {
    headers: {
      'Content-Type': 'application/json',
    },
    ...options,
  });
  if (!response.ok) {
    const errorText = await response.text().catch(() => 'Unknown error');
    throw new Error(`HTTP ${response.status}: ${errorText}`);
  }
  return response.json();
}

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
  gameFinished = false;
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
  if (!curGame || gameFinished) return;
  
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
  if (gameFinished) return;
  
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

function disableBoard() {
  const cells = document.querySelectorAll('.cell');
  cells.forEach(cell => {
    cell.style.pointerEvents = 'none';
    cell.style.opacity = '0.7';
  });
}

function enableBoard() {
  const cells = document.querySelectorAll('.cell');
  cells.forEach(cell => {
    cell.style.pointerEvents = 'auto';
    cell.style.opacity = '1';
  });
}

// 🚀 Основное изменение: сохранение через API
async function finishGame() {
  gameFinished = true;
  disableBoard();
  
  const winner = curGame.getWinner();
  setStatus(winner ? `🎉 Победитель: ${winner}` : "🤝 Ничья!");

  const gameObj = {
    date: new Date().toISOString(),
    player_name: playerInput.value || 'Anonymous',
    human_symbol: humanSymbolGlobal,
    winner: winner,
    size: curGame.getBoard().getSize(),
    moves: movesLog
  };

  try {
    await apiRequest('/games', {
      method: 'POST',
      body: JSON.stringify(gameObj),
    });
    setStatus(`${winner ? '🎉 Игра сохранена. Победитель: ' + winner : '🤝 Игра сохранена. Ничья.'} Нажмите "Новая игра" для продолжения.`);
  } catch (err) {
    console.error(err);
    setStatus('❌ Ошибка сохранения игры: ' + err.message);
  }
}

function startGame() {
  const size = parseInt(document.getElementById('size').value, 10);
  if (isNaN(size) || size < 3 || size > 10) {
    alert('Введите размер поля от 3 до 10');
    return;
  }

  gamesSection.classList.add('hidden');
  boardContainer.classList.remove('hidden');
  statusElem.classList.remove('hidden');
  
  boardContainer.classList.add('fade-in');
  statusElem.classList.add('slide-down');

  movesLog = [];
  curGame = new Game(size);

  humanSymbolGlobal = Math.random() < 0.5 ? 'X' : 'O';
  curGame.setCurrentSymbol('X');
  curGame.setHumanSymbol(humanSymbolGlobal);

  renderBoard(size);
  setStatus(`Вы играете за ${humanSymbolGlobal}`);

  enableBoard();

  if (curGame.getCurrentSymbol() !== humanSymbolGlobal) {
    setTimeout(() => computerMove(), 500);
  }
}

// 📥 Загрузка списка игр
async function renderGamesList() {
  try {
    const games = await apiRequest('/games');
    const tbody = gamesTable;
    tbody.innerHTML = '';

    if (!games.length) {
      tbody.innerHTML = '<tr><td colspan="7">Сохранённых игр нет.</td></tr>';
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
          <td>
            <button class="replay-btn" data-id="${g.id}">
              ▶️ Воспроизвести
            </button>
          </td>
        `;
        tbody.appendChild(tr);
      }

      const replayButtons = document.querySelectorAll('.replay-btn');
      replayButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
          const gameId = parseInt(e.target.closest('.replay-btn').dataset.id);
          replayGameById(gameId);
        });
      });
    }

    gamesSection.classList.remove('hidden');
    gamesSection.classList.add('fade-in');
    boardContainer.classList.add('hidden');
    statusElem.classList.add('hidden');
  } catch (err) {
    console.error(err);
    alert('❌ Ошибка загрузки списка игр: ' + err.message);
  }
}

// 🔄 Воспроизведение
async function replayGameById(gameId) {
  try {
    const game = await apiRequest(`/games/${gameId}`);
    if (!game || !game.moves) {
      alert(`Игра с ID ${gameId} не найдена.`);
      return;
    }

    const { size, moves, winner, player_name, human_symbol } = game;
    curGame = new Game(size);
    
    boardContainer.classList.remove('hidden');
    statusElem.classList.remove('hidden');
    gamesSection.classList.add('hidden');
    
    boardContainer.classList.add('fade-in');
    statusElem.classList.add('slide-down');
    
    renderBoard(size);
    setStatus(`Воспроизведение игры #${gameId} (${player_name}, ${human_symbol})...`);

    disableBoard();
    gameFinished = true;

    for (let i = 0; i < moves.length; i++) {
      const move = moves[i];
      await new Promise(res => setTimeout(res, 600));
      curGame.getBoard().setCell(move.row, move.col, move.player);
      updateBoard(curGame.getBoard());
      setStatus(`Воспроизведение игры #${gameId}... Ход ${i + 1}/${moves.length}`);
    }

    setStatus(`Воспроизведение завершено. ${winner ? '🏆 Победитель: ' + winner : '🤝 Ничья.'} Игрок: ${player_name} (${human_symbol})`);
  } catch (err) {
    console.error(err);
    alert('❌ Ошибка воспроизведения: ' + err.message);
  }
}

// события
startBtn.addEventListener('click', startGame);
listBtn.addEventListener('click', renderGamesList);

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
  boardContainer.classList.add('hidden');
  statusElem.classList.add('hidden');
  gamesSection.classList.add('hidden');
});