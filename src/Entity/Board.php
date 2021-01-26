<?php

namespace App\Entity;

use GraphDS\Graph\DirectedGraph;
use Doctrine\ORM\Mapping as ORM;
use GraphDS\Vertex\DirectedVertex;

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

    public function addObstacle($coordinate)
    {
        if (!empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            $this->graph->removeVertex($this->coordinateToString($coordinate));
        }
    }

    public function addPuck($coordinate, $color)
    {
        $vertex = is_array($coordinate) ? $this->coordinateToString($coordinate) : $coordinate;
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

    public function getPucks()
    {
        return array_filter($this->graph->vertices, function ($vertex) {
            return $vertex->getValue();
        });
    }

    private function coordinateToString($coordinate)
    {
        list($x, $y) = $coordinate;
        return $x . ' ' . $y;
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
        return $vertex && $vertex->getValue();
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
                && !$this->graph->vertices[$neighbor]->getValue()
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
        $color = $puck->getValue();
        $puckId = array_search($puck, $this->graph->vertices);
        if (!empty($this->graph->vertices[$puckId])) {
            $this->graph->vertices[$puckId]->setValue();
        }
        return [$color];
    }

    public function movePuckTo($puck, $direction)
    {
        // neighbor = self.__get_node_by_direction(node, direction)
        $neighbor = $this->getNeighbor($puck, $direction);
        // is_neighbor_a_puck = neighbor and self.graph.has_node(neighbor) \
        //     and self.graph.nodes[neighbor].get(Board.PUCK_KEY)
        $isNeighborAPuck = $this->isCellAPuck($neighbor);
        if ($isNeighborAPuck) {
            $this->movePuckTo($neighbor, $direction);
        }
        $nextFreeCell = $this->nextFreeCell($puck, $direction);
        list($color) = $this->removePuck($puck);
        $this->addPuck(array_search($nextFreeCell, $this->graph->vertices), $color);
    }

    public function tilt($direction)
    {
        $pucks = $this->getPucks();
        foreach ($this->getPucks() as $puck) {
            if (!empty($puck) && array_search($puck, $this->graph->vertices)) {
                $this->movePuckTo($puck, $direction);
            }
        }
    }
}
