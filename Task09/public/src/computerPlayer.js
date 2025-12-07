// src/computerPlayer.js
import { Player } from './player.js';
export class ComputerPlayer extends Player {
  makeMove(board){
    const empty=[];
    board.getCells().forEach((row,r)=>{
      row.forEach((cell,c)=>{ if(cell==='.'){ empty.push([r,c]); } });
    });
    const choice = empty[Math.floor(Math.random()*empty.length)];
    return choice;
  }
}
