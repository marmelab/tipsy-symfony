<?php

namespace App\Entity;

use GraphDS\Graph\DirectedGraph;
use GraphDS\Vertex\DirectedVertex;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BoardRepository")
 */
class Game
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

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $width = 7;

    /**
     * @ORM\Column(type="integer")
     */
    private $height = 7;

    /**
     * @ORM\Column(type="array")
     */
    public $players;

    /**
     * @ORM\Column(type="object")
     */
    public $graph;

    /**
     * @ORM\Column(type="array")
     */
    public $pucks = [];

    /**
     * @ORM\Column(type="json")
     */
    public $fallenPucks = [Game::BLUE => 0, Game::RED => 0];

    /**
     * @ORM\Column(type="json")
     */
    public $scores = [Game::BLUE => 0, Game::RED => 0];

    /**
     * @ORM\Column(type="integer")
     */
    public $remainingTurns;

    public function __construct(?int $width, ?int $height)
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
        $this->graph->vertices[$this->coordinateToString($exit)]->setValue([Game::EXIT => True]);

        if ($x == -1) {
            $this->addEdge(array(0, $y), $exit, Game::WEST);
        }
        if ($x == $this->width) {
            $this->addEdge(array($x - 1, $y), $exit, Game::EAST);
        }
        if ($y == $this->height) {
            $this->addEdge(array($x, $y - 1), $exit, Game::SOUTH);
        }
        if ($y == -1) {
            $this->addEdge(array($x, 0), $exit, Game::NORTH);
        }
    }

    public function addObstacle(array $coordinate)
    {
        if (!empty($this->graph->vertices[$this->coordinateToString($coordinate)])) {
            $this->graph->removeVertex($this->coordinateToString($coordinate));
        }
    }

    public function setPucks(array $pucks)
    {
        $this->pucks = $pucks;
    }

    public function addPuck(mixed $coordinate, string $color, bool $flipped = false)
    {
        $key = is_array($coordinate) ? $this->coordinateToString($coordinate) : $coordinate;
        if (!empty($this->graph->vertices[$key])) {
            $this->graph->vertices[$key]->setValue([Game::COLOR_KEY => $color, Game::FLIPPED_KEY => $flipped]);
        }
        $this->pucks += [$key =>  new Puck($color, $key, $flipped)];
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
    public function setCurrentPlayer(string $color, string $name)
    {
        $this->players[$color] = [ "current" => true, "name" => $name];
    }
    public function getCurrentPlayer(): string
    {
        foreach (array_keys($this->players) as $color) {
            if ($this->players[$color]['current']) {
                return $color;
            }
        }
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
            return Game::OBSTACLE;
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

            return $vertex->getValue() && array_key_exists(Game::COLOR_KEY, $vertex->getValue());
        });
    }
    public function getPucksIdsBy(string $color, ?bool $flipped): array
    {
        $pucks = array_filter($this->graph->vertices, function ($vertex) use ($color, $flipped) {
            return $vertex->getValue()
                && array_key_exists(Game::COLOR_KEY, $vertex->getValue())
                && $vertex->getValue()[Game::COLOR_KEY] == $color
                && array_key_exists(Game::FLIPPED_KEY, $vertex->getValue())
                && $vertex->getValue()[Game::FLIPPED_KEY] == $flipped;
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
        return $vertex && $vertex->getValue() && array_key_exists(Game::COLOR_KEY, $vertex->getValue());
    }

    private function nextFreeCell(DirectedVertex $puck, string $direction): ?DirectedVertex
    {
        $neighbors = $puck->getOutNeighbors();
        $puckId = array_search($puck, $this->graph->vertices);
        $nextFreeCell = null;
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
        unset($this->pucks[$puckId]);
        return $value;
    }

    public function getFallenPucks(string $color): int
    {
        return $this->fallenPucks[$color];
    }

    public function decrementFallenPucks(string $color)
    {
        $this->fallenPucks[$color]--;
    }

    private function getFreeCells(): array
    {
        return array_values(array_filter($this->graph->vertices, function ($vertex) {
            return empty($vertex->getValue());
        }));
    }

    public function replacePuck(string $color)
    {
        $freeCells = $this->getFreeCells();
        $index = rand(0, count($freeCells) - 1);
        $randomFreeCell = $freeCells[$index];
        $this->addPuck(array_search($randomFreeCell, $this->graph->vertices), $color, True);
        if ($this->getFallenPucksCount() == 0) {
            $this->remainingTurns = 2;
            $this->switchPlayers();
        }

        $this->graph = clone $this->graph;
    }

    public function getScore(string $player): int
    {
        return $this->scores[$player];
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

        if (
            $nextFreeCell->getValue()
            && $nextFreeCell->getValue()[Game::EXIT]
            && $puckValue[Game::COLOR_KEY] != Game::BLACK
        ) {
            if (!$puckValue[Game::FLIPPED_KEY]) {
                $this->scores[$puckValue[Game::COLOR_KEY]]++;
            }
            $this->fallenPucks[$puckValue[Game::COLOR_KEY]]++;
            return;
        }

        $this->addPuck(array_search($nextFreeCell, $this->graph->vertices), $puckValue[Game::COLOR_KEY], $puckValue[Game::FLIPPED_KEY]);
    }

    public function shouldReplacePucks(): bool
    {
        return $this->getFallenPucksCount() > 0 && $this->remainingTurns == 0;
    }

    public function getFallenPucksCount(): int
    {
        $blueFallenPuck = $this->getFallenPucks(Game::BLUE);
        $redFallenPuck = $this->getFallenPucks(Game::RED);
        return $redFallenPuck + $blueFallenPuck;
    }

    public function getCurrentOpponent(): string
    {
        foreach (array_keys($this->players) as $color) {
            if (!$this->players[$color]['current']) {
                return $color;
            }
        }
    }
    public function setPlayers(array $players){
        $this->players = $players;
    }
    public function setEmptyPlayers(array $players)
    {
        foreach ($players as $player) {
            $this->players[$player] = ["name" => "", "current" => false];;
        }
    }
    private function switchPlayers()
    {
        foreach (array_keys($this->players) as $color) {
            $this->players[$color]['current'] = !$this->players[$color]['current'];
        }
    }


    private function updateRemainingTurns()
    {
        $this->remainingTurns--;
    }

    public function tilt(string $direction)
    {
        foreach ($this->getPucks() as $puck) {
            if (!empty($puck) && array_search($puck, $this->graph->vertices)) {
                if ($puck->getValue()) {
                    $this->movePuckTo($puck, $direction);
                }
            }
        }
        $this->updateRemainingTurns();

        if ($this->remainingTurns == 0 && $this->getFallenPucksCount() == 0) {
            $this->remainingTurns = 2;
            $this->switchPlayers();
        }

        $this->graph = clone $this->graph;
    }

    public function isFull(): bool
    {
        foreach (array_keys($this->players) as $color) {
            if (!$this->players[$color]['name']) {
                return false;
            }
        }
        return true;
    }

    public function addPlayer(string $playerName)
    {
        foreach (array_keys($this->players) as $color) {
            if (!$this->players[$color]['name']) {
                $this->players[$color]['name'] = $playerName;
            }
        }
    }

    public function hasPlayer(string $playerName): bool
    {
        return !empty(array_filter($this->players, function ($player) use ($playerName) {
            return $player['name'] == $playerName;
        }));
    }

    public function itsMyTurn($playerName)
    {
        return ($this->players[$this->getCurrentPlayer()]['name'] == $playerName);
    }
}
