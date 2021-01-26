<?php

namespace App\Entity;

use GraphDS\Graph\DirectedGraph;
use Doctrine\ORM\Mapping as ORM;


class Board
{
    const WEST = "west";
    const EAST = "east";
    const NORTH = "north";
    const SOUTH = "south";
    const OBSTACLE = "obstacle";
    const RED = "red";
    const BLUE = "blue";
    const BLACK = "black";

    private $id;

    private $width = 7;

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
        if (!empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            $this->graph->removeVertex($this->coordinateToString($coordinate));
        }
    }

    public function addPuck($coordinate, $color)
    {
        $vertex = $this->coordinateToString($coordinate);
        if (!empty($this->graph->vertices[$vertex])) {
            $this->graph->vertices[$vertex]->setValue($color);
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

    public function getId()
    {
        return $this->id;
    }
    public function getCellType($x, $y)
    {
        $coordinate = array($x, $y);
        if (empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            return Board::OBSTACLE;
        }
        return $this->graph->vertices[$this->coordinateToString($coordinate)]->getValue();
    }

    public function getPuck($x, $y)
    {
        $coordinate = array($x, $y);
        if (!empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            return $this->graph->vertices[$this->coordinateToString($coordinate)]->getValue();
        }
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
