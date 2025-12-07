// src/humanPlayer.js
import { Player } from './player.js';
export class HumanPlayer extends Player {
  constructor(symbol, ui) { super(symbol); this.ui = ui; }
  // human move handled by UI (app.js), so this class may be unused for direct prompting
}
