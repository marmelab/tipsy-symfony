<?php

namespace App\Entity;

class Puck {
    private $color;
    private $key;
    private $flipped;

    public function __construct(string $color, string $key, bool $flipped){
        $this->color = $color;
        $this->key = $key;
        $this->flipped = $flipped;
    }
}
