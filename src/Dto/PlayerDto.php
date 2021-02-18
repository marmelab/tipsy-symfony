<?php

namespace App\Dto;

use App\Entity\Game;

class  PlayerDto{

    public $id;
    public $color;
    public $name;
    public $current = false;
    public $powerUps;

    public function __construct($color, $player) {
        $this->color = $color;
        $this->name = $player["name"];
        $this->current = $player["current"];
        $this->id = $player["id"];
        $this->powerUps = $player[Game::POWERUPS_KEY];
    }
    public static function getPlayerDtos(Game $game){
        $players = array();

        foreach ($game->players as $color => $player) {
            array_push($players, new PlayerDto($color, $player));
        };
        return array_values($players);
    }
}