<?php

namespace App\Entity;

use GraphDS\Graph\DirectedGraph;
use Doctrine\ORM\Mapping as ORM;
use GraphDS\Vertex\DirectedVertex;

class Board
{
    const COLOR_KEY = "color";
    const FLIPPED_KEY = "flipped";
    const WEST = "west";
    const EAST = "east";
    const NORTH = "north";
    const SOUTH = "south";
    const OBSTACLE = "obstacle";
    const RED = "red";
    const BLUE = "blue";
    const BLACK = "black";
    const EXIT = "exit";

    private $id;

    private $width = 7;

    private $height = 7;

    public $graph;


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

    public function addExit($exit)
    {
        list($x, $y) = $exit;
        $this->graph->addVertex($this->coordinateToString($exit));
        $this->graph->vertices[$this->coordinateToString($exit)]->setValue([Board::EXIT => True]);

        if ($x == -1) {
            $this->addEdge(array(0, $y), $exit, Board::WEST);
        }
        if ($x == $this->width) {
            $this->addEdge(array($x - 1, $y), $exit, Board::EAST);
        }
        if ($y == $this->height) {
            $this->addEdge(array($x, $y - 1), $exit, Board::SOUTH);
        }
        if ($y == -1) {
            $this->addEdge(array($x, 0), $exit, Board::NORTH);
        }
    }

    public function addObstacle($coordinate)
    {
        if (!empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            $this->graph->removeVertex($this->coordinateToString($coordinate));
        }
    }

    public function addPuck($coordinate, $color, $flipped = false)
    {
        $vertex = is_array($coordinate) ? $this->coordinateToString($coordinate) : $coordinate;
        if (!empty($this->graph->vertices[$vertex])) {
            $this->graph->vertices[$vertex]->setValue([Board::COLOR_KEY => $color, Board::FLIPPED_KEY => $flipped]);
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

    public function getPucks()
    {
        return array_filter($this->graph->vertices, function ($vertex) {

            return $vertex->getValue() && array_key_exists(Board::COLOR_KEY, $vertex->getValue());
        });
    }
    public function getPucksIdsBy($color, $flipped = false)
    {
        $pucks = array_filter($this->graph->vertices, function ($vertex) use ($color, $flipped) {
            return $vertex->getValue()
                && array_key_exists(Board::COLOR_KEY, $vertex->getValue())
                && $vertex->getValue()[Board::COLOR_KEY] == $color
                && array_key_exists(Board::FLIPPED_KEY, $vertex->getValue())
                && $vertex->getValue()[Board::FLIPPED_KEY] == $flipped;
        });
        $graph = $this->graph;
        return array_map(function ($puck) use ($graph) {
            return array_search($puck, $graph->vertices);
        }, $pucks);
    }

    private function coordinateToString($coordinate)
    {
        list($x, $y) = $coordinate;
        return $x . $y;
    }

    private function getNeighbor($puck, $direction)
    {
        $neighbors = $puck->getOutNeighbors();
        $puckId = array_search($puck, $this->graph->vertices);
        foreach ($neighbors as $neighbor) {
            $edge = $this->graph->edge($puckId, $neighbor);
            if (
                $edge
                && $edge->getValue() == $direction
            ) {
                return $this->graph->vertices[$neighbor];
            }
        }
    }

    private function isCellAPuck($vertex)
    {
        return $vertex && $vertex->getValue() && array_key_exists(Board::COLOR_KEY, $vertex->getValue());
    }

    private function nextFreeCell($puck, $direction)
    {
        $neighbors = $puck->getOutNeighbors();
        $nextFreeCell = null;
        $puckId = array_search($puck, $this->graph->vertices);
        foreach ($neighbors as $neighbor) {
            if (
                $this->graph->edge($puckId, $neighbor)
                && $this->graph->edge($puckId, $neighbor)->getValue() == $direction
                && !$this->isCellAPuck($this->graph->vertices[$neighbor])
            ) {
                $nextFreeCell = $this->graph->vertices[$neighbor];
            }
        }
        if ($nextFreeCell) {
            return $this->nextFreeCell($nextFreeCell, $direction);
        }
        return $puck;
    }

    private function removePuck($puck)
    {
        $value = $puck->getValue();
        $puckId = array_search($puck, $this->graph->vertices);
        if (!empty($this->graph->vertices[$puckId])) {
            $this->graph->vertices[$puckId]->setValue();
        }
        return $value;
    }

    private function getFreeCells()
    {
        return array_values(array_filter($this->graph->vertices, function ($vertex) {
            return empty($vertex->getValue());
        }));
    }

    private function replacePuck($puck)
    {
        $freeCells = $this->getFreeCells();
        $index = rand(0, count($freeCells) - 1);
        $randomFreeCell = $freeCells[$index];
        $this->addPuck(array_search($randomFreeCell, $this->graph->vertices), $puck[Board::COLOR_KEY], True);
    }

    public function movePuckTo($puck, $direction)
    {
        $neighbor = $this->getNeighbor($puck, $direction);
        $isNeighborAPuck = $this->isCellAPuck($neighbor);
        if ($isNeighborAPuck) {
            $this->movePuckTo($neighbor, $direction);
        }
        $nextFreeCell = $this->nextFreeCell($puck, $direction);
        $puckValue = $this->removePuck($puck);

        if ($nextFreeCell->getValue() && $nextFreeCell->getValue()[Board::EXIT]) {
            $this->replacePuck($puckValue);
            return $puckValue;
        }
        $this->addPuck(array_search($nextFreeCell, $this->graph->vertices), $puckValue[Board::COLOR_KEY], $puckValue[Board::FLIPPED_KEY]);
        return null;
    }

    public function tilt($direction)
    {
        foreach ($this->getPucks() as $puck) {
            if (!empty($puck) && array_search($puck, $this->graph->vertices)) {
                if ($puck->getValue()) {
                    $this->movePuckTo($puck, $direction);
                }
            }
        }
        return $this;
    }
}
