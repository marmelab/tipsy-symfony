<?php

namespace App\Dto;

use App\Entity\Game;

class  PlayerDto{

    public $id;
    public $color;
    public $name;
    public $current = false;

    public function __construct($color, $name, $current,  $id) {
        $this->color = $color;
        $this->name = $name;
        $this->current = $current;
        $this->id = $id;

    }
    public static function getPlayerDtos(Game $game){
        $players = array();

        foreach ($game->players as $color => $player) {
            array_push($players, new PlayerDto($color, $player['name'], $player['current'], $player['id']));
        };
        return array_values($players);
    }
}