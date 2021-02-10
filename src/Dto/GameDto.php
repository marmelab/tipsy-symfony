<?php

namespace App\Dto;

use App\Entity\Game;

class  GameDto{

    public $players;

    public $pucks = [];

    public $fallenPucks = [Game::BLUE => 0, Game::RED => 0];

    public function __construct(Game $game){
        $this->players = array_values($game->players);
        $this->fallenPucks = array_values($game->fallenPucks);
        $this->pucks = array_values($game->getPucks());
    }
}