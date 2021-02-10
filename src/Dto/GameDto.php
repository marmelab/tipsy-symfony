<?php

namespace App\Dto;

use App\Entity\Game;

class  GameDto{

    public $id;
    public $players;

    public $pucks = [];

    public $fallenPucks = [Game::BLUE => 0, Game::RED => 0];

    public function __construct(Game $game){
        $this->id = $game->id;
        $this->players = PlayerDto::getPlayerDtos($game);
        $this->fallenPucks = array_values($game->fallenPucks);
        $this->pucks = PuckDto::getPucksDto($game);
    }
}