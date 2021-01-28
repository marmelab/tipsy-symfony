<?php

namespace App\Entity;

use GraphDS\Graph\DirectedGraph;
use GraphDS\Vertex\DirectedVertex;
use Doctrine\ORM\Mapping as ORM;
use DOMDocument;
use GraphDS\Persistence\ExportGraph;
use SimpleXMLElement;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BoardRepository")
 */
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

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $width = 7;

    /**
     * @ORM\Column(type="integer")
     */
    private $height = 7;

    /**
     * @ORM\Column(type="string")
     */
    public $rawGraph;

    /**
     * @ORM\Column(type="json")
     */
    public $players;

    public $graph;
    /**
     * @ORM\Column(type="json")
     */
    private $fallenPucks = [Board::BLUE => 0, Board::RED => 0];

    /**
     * @ORM\Column(type="json")
     */
    private $scores = [Board::BLUE => 0, Board::RED => 0];

    /**
     * @ORM\Column(type="integer")
     */
    private $remainingTurns;

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
            && $nextFreeCell->getValue()[Board::EXIT]
            && $puckValue[Board::COLOR_KEY] != Board::BLACK
        ) {
            if (!$puckValue[Board::FLIPPED_KEY]) {
                $this->scores[$puckValue[Board::COLOR_KEY]]++;
            }
            $this->fallenPucks[$puckValue[Board::COLOR_KEY]]++;
            return;
        }

        $this->addPuck(array_search($nextFreeCell, $this->graph->vertices), $puckValue[Board::COLOR_KEY], $puckValue[Board::FLIPPED_KEY]);
    }

    public function shouldReplacePucks(): bool
    {
        return $this->getFallenPucksCount() > 0 && $this->remainingTurns == 0;
    }

    public function getFallenPucksCount(): int
    {
        $blueFallenPuck = $this->getFallenPucks(Board::BLUE);
        $redFallenPuck = $this->getFallenPucks(Board::RED);
        return $redFallenPuck + $blueFallenPuck;
    }

    public function getCurrentOpponent(): string
    {
        return array_search(false, $this->players);
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
    }

    public function serializeGraph()
    {
        $directionality = $this->graph->directed ? 'directed' : 'undirected';
        $export = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
            . '<graphml xmlns="http://graphml.graphdrawing.org/xmlns" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd">'
            . '</graphml>');

        $keyNode = $export->addChild('key');
        $keyNode->addAttribute('id', 'd0');
        $keyNode->addAttribute('for', 'node');
        $keyNode->addAttribute('attr.name', 'value');
        $keyNode->addAttribute('attr.type', 'string');
        $keyNode->addChild('default', '');

        $keyEdge = $export->addChild('key');
        $keyEdge->addAttribute('id', 'd1');
        $keyEdge->addAttribute('for', 'edge');
        $keyEdge->addAttribute('attr.name', 'weight');
        $keyEdge->addAttribute('attr.type', 'double');
        $keyEdge->addChild('default', '');

        $graphElem = $export->addChild('graph');
        $graphElem->addAttribute('id', 'G');
        $graphElem->addAttribute('edgedefault', $directionality);
        $graphElem->addAttribute('parse.nodes', $this->graph->getVertexCount());
        $graphElem->addAttribute('parse.edges', $this->graph->getEdgeCount());
        $graphElem->addAttribute('parse.nodeids', 'free');
        $graphElem->addAttribute('parse.edgeids', 'free');
        $graphElem->addAttribute('parse.order', 'nodesfirst');

        foreach ($this->graph->vertices as $vertexKey => $vertex) {
            $node = $graphElem->addChild('node');
            $node->addAttribute('id', $vertexKey);
            if (null !== ($value = $vertex->getValue())) {
                $data = $node->addChild('data', serialize($value));
                $data->addAttribute('key', 'd0');
            }
        }
        foreach ($this->graph->edges as $edgeSource) {
            foreach ($edgeSource as $edgeTarget) {
                $edge = $graphElem->addChild('edge');
                $edge->addAttribute('source', $edgeTarget->vertices['from']);
                $edge->addAttribute('target', $edgeTarget->vertices['to']);
                if (null !== ($value = $edgeTarget->getValue())) {
                    $data = $edge->addChild('data', $value);
                    $data->addAttribute('key', 'd1');
                }
            }
        }

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($export->asXML());

        $this->rawGraph = $dom->saveXML();
    }

    public function deserializeGraph()
    {

        $import = new SimpleXMLElement($this->rawGraph);
        $graph = new DirectedGraph();


        foreach ($import->graph->node as $node) {
            $vertex = (string) $node['id'];
            $value = (string) $node->data;
            if (empty($value)) {
                $default = $import->xpath('key[@for="node"]/default');
                if (!empty($default)) {
                    $value = (string) $default;
                }
            }
            $graph->addVertex($vertex);
            $graph->vertices[$vertex]->setValue(unserialize($value));
        }

        foreach ($import->graph->edge as $edge) {
            $edgeSource = (string) $edge['source'];
            $edgeTarget = (string) $edge['target'];
            $value = (string) $edge->data;
            if (empty($value)) {
                $default = $import->xpath('key[@for="edge"]/default');
                if (!empty($default)) {
                    $value = (string) $default;
                }
            }
            $graph->addEdge($edgeSource, $edgeTarget);
            $graph->edge($edgeSource, $edgeTarget)->setValue($value);
        }

        $this->graph = $graph;
    }
}
