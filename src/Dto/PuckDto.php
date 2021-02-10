<?php

namespace App\Dto;

use App\Entity\Game;

class  PuckDto{

    public $color;
    public $position;
    public $flipped = false;

    public function __construct($color, $position, $flipped) {
        $this->color = $color;
        $this->position = $position;
        $this->flipped = $flipped;

    }
    public static function getPucksDto(Game $game){
        $pucks = array();
        foreach ($game->graph->vertices as $position => $vertex) {
            if ($vertex->getValue() && array_key_exists(Game::COLOR_KEY, $vertex->getValue())){
                array_push($pucks, 
                new PuckDto(
                    $vertex->getValue()[Game::COLOR_KEY], 
                    ["x"=>intval(strval($position)[0]),"y"=>intval(strval($position)[1])],
                    $vertex->getValue()[Game::FLIPPED_KEY]));
            }
        };
        return array_values($pucks);
    }
}