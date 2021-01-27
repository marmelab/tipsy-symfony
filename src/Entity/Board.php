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

    private $fallenPucks = array();

    public function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->graph = new DirectedGraph();
    }


    public function addEdge(array $from, array $to, string $direction)
    {
        $this->graph->addVertex($this->coordinateToString($from));
        $this->graph->addVertex($this->coordinateToString($to));
        $this->graph->addEdge(
            $this->coordinateToString($from),
            $this->coordinateToString($to),
            $direction
        );
    }

    public function addExit(array $exit)
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

    public function addObstacle(array $coordinate)
    {
        if (!empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            $this->graph->removeVertex($this->coordinateToString($coordinate));
        }
    }

    public function addPuck(mixed $coordinate, string $color, bool $flipped = false)
    {
        $vertex = is_array($coordinate) ? $this->coordinateToString($coordinate) : $coordinate;
        if (!empty($this->graph->vertices[$vertex])) {
            $this->graph->vertices[$vertex]->setValue([Board::COLOR_KEY => $color, Board::FLIPPED_KEY => $flipped]);
        }
    }
    public function getHeight(): int
    {
        return $this->height;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function setCurrentPlayer(string $player)
    {
        $this->players[$player] = true;
    }
    public function getCurrentPlayer(): string
    {
        return array_search(true, $this->players);
    }

    public function setRemainingTurns(int $turns)
    {
        $this->remainingTurns = $turns;
    }

    public function getRemainingTurns(): int
    {
        return $this->remainingTurns;
    }

    public function getCellType($x, $y): mixed
    {
        $coordinate = array($x, $y);
        if (empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            return Board::OBSTACLE;
        }
        return $this->graph->vertices[$this->coordinateToString($coordinate)]->getValue();
    }

    public function getPuck($x, $y): ?array
    {
        $coordinate = array($x, $y);
        if (!empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            return $this->graph->vertices[$this->coordinateToString($coordinate)]->getValue();
        }
        return null;
    }

    public function getPucks(): array
    {
        return array_filter($this->graph->vertices, function ($vertex) {

            return $vertex->getValue() && array_key_exists(Board::COLOR_KEY, $vertex->getValue());
        });
    }
    public function getPucksIdsBy(string $color, ?bool $flipped): array
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

    private function coordinateToString(array $coordinate): string
    {
        list($x, $y) = $coordinate;
        return $x . $y;
    }

    private function getNeighbor(DirectedVertex $puck, string $direction): ?DirectedVertex
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
        return null;
    }

    private function isCellAPuck(?DirectedVertex $vertex): bool
    {
        return $vertex && $vertex->getValue() && array_key_exists(Board::COLOR_KEY, $vertex->getValue());
    }

    private function nextFreeCell(DirectedVertex $puck, string $direction): ?DirectedVertex
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

    private function removePuck(DirectedVertex $puck): array
    {
        $value = $puck->getValue();
        $puckId = array_search($puck, $this->graph->vertices);
        if (!empty($this->graph->vertices[$puckId])) {
            $this->graph->vertices[$puckId]->setValue();
        }
        return $value;
    }

    public function getFallenPucks(): array
    {
        return $this->fallenPucks;
    }
    private function getFreeCells(): array
    {
        return array_values(array_filter($this->graph->vertices, function ($vertex) {
            return empty($vertex->getValue());
        }));
    }

    private function replacePuck(array $puck)
    {
        $freeCells = $this->getFreeCells();
        $index = rand(0, count($freeCells) - 1);
        $randomFreeCell = $freeCells[$index];
        $this->addPuck(array_search($randomFreeCell, $this->graph->vertices), $puck[Board::COLOR_KEY], True);
    }

    public function movePuckTo(DirectedVertex $puck, string $direction)
    {
        $neighbor = $this->getNeighbor($puck, $direction);
        $isNeighborAPuck = $this->isCellAPuck($neighbor);
        if ($isNeighborAPuck) {
            $this->movePuckTo($neighbor, $direction);
        }
        $nextFreeCell = $this->nextFreeCell($puck, $direction);
        $puckValue = $this->removePuck($puck);

        if ($nextFreeCell->getValue() && $nextFreeCell->getValue()[Board::EXIT]) {
            array_push($this->fallenPucks, $puckValue);
        }
        $this->addPuck(array_search($nextFreeCell, $this->graph->vertices), $puckValue[Board::COLOR_KEY], $puckValue[Board::FLIPPED_KEY]);
    }
    public function setPlayers(array $players)
    {
        foreach ($players as $player) {
            $this->players[$player] = false;
        }
    }
    private function switchPlayers()
    {
        foreach (array_keys($this->players) as $player) {
            $this->players[$player] = !$this->players[$player];
        }
    }

    private function updateRemainingTurns()
    {
        $this->remainingTurns--;
        if ($this->remainingTurns == 0) {
            $this->remainingTurns = 2;
            $this->switchPlayers();
        }
    }

    public function tilt(string $direction)
    {
        $this->updateRemainingTurns();
        foreach ($this->getPucks() as $puck) {
            if (!empty($puck) && array_search($puck, $this->graph->vertices)) {
                if ($puck->getValue()) {
                    $this->movePuckTo($puck, $direction);
                }
            }
        }
    }
}
