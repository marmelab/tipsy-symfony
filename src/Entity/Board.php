<?php

namespace App\Entity;

class Board
{
    /** @Column(type="integer") */
    protected $id;

    /** @Column(type="integer") */
    protected $width = 7;

    /** @Column(type="integer") */
    protected $height = 7;

    // what?
    // private $graph;
    public function getHeight()
    {
        return $this->height;
    }

    public function getWidth()
    {
        return $this->width;
    }
}
