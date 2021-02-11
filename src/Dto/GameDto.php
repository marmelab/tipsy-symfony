<?php

namespace App\Dto;

use App\Entity\Game;

class  GameDto{

    public $id;
    public $players;
    public $scores;
    public $currentPlayer;
    public $remainingTurns;

    public $pucks = [];

    public $fallenPucks = [Game::BLUE => 0, Game::RED => 0];

    public function __construct(Game $game){
        $this->id = $game->id;
        $this->players = PlayerDto::getPlayerDtos($game);
        $this->scores = $game->scores;
        $this->fallenPucks = array_values($game->fallenPucks);
        $this->pucks = PuckDto::getPucksDto($game);
        $this->currentPlayer = $game->getCurrentPlayerName();
        $this->remainingTurns = $game->getRemainingTurns();
    }
}