<?php

namespace App\Entity;

use GraphDS\Graph\DirectedGraph;

class Board
{
    const WEST = "west";
    const EAST = "east";
    const NORTH = "north";
    const SOUTH = "south";

    /** @Column(type="integer") */
    private $id;

    /** @Column(type="integer") */
    private $width = 7;

    /** @Column(type="integer") */
    private $height = 7;


    private $graph;


    public function __construct(Int $width, Int $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->graph = new DirectedGraph();
    }


    public function addEdge($from, $to, $direction)
    {
        $this->graph->addVertex($this->coordinateToString($from));
        $this->graph->addVertex($this->coordinateToString($to));
        $this->graph->addEdge(
            $this->coordinateToString($from),
            $this->coordinateToString($to),
            $direction
        );
    }

    public function addObstacle($coordinate)
    {
        if (in_array($coordinate, $this->graph->vertices)) {
            $this->graph->removeVertex($this->coordinateToString($coordinate));
        }
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getWidth()
    {
        return $this->width;
    }

    private function coordinateToString($coordinate)
    {
        list($x, $y) = $coordinate;
        return $x . ' ' . $y;
    }

    private function stringToCoordinate($coordinate)
    {
        list($x, $y) = explode(' ', $coordinate);
        return array($x, $y);
    }
}
