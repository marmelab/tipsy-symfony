<?php

namespace App\Services;

use App\Entity\Board;
use App\Repository\BoardRepository;
use Doctrine\ORM\EntityManagerInterface;

class GameService
{
    private $obstacles = array(
        array(0, 3), array(1, 1), array(1, 5), array(2, 2),
        array(2, 4), array(3, 0), array(3, 6), array(4, 2),
        array(4, 4), array(5, 1), array(5, 5), array(6, 3)
    );
    private $blue_pucks = array(
        array(1, 2), array(3, 2), array(5, 2),
        array(1, 4), array(3, 4), array(5, 4)
    );
    private $red_pucks = array(
        array(2, 1), array(2, 3), array(2, 5),
        array(4, 1), array(4, 3), array(4, 5)
    );
    private $black_puck = array(3, 3);

    public function newGame()
    {
        $board = new Board(7, 7);
        $this->initEmptyBoard($board);
        $this->initObstacles($board);
        $this->initPucks($board);
        return $board;
    }

    private function initPucks($board)
    {
        foreach ($this->red_pucks as $red_puck) {
            $board->addPuck($red_puck, Board::RED);
        }
        foreach ($this->blue_pucks as $blue_puck) {
            $board->addPuck($blue_puck, Board::BLUE);
        }
        $board->addPuck($this->black_puck, Board::BLACK);
    }

    private function initEmptyBoard($board)
    {
        foreach (range(0, $board->getWidth() - 1) as $x) {
            foreach (range(0, $board->getHeight() - 1) as $y) {
                if ($x > 0) {
                    $board->addEdge(array($x - 1, $y), array($x, $y), Board::WEST);
                }
                if ($x < $board->getWidth() - 1) {
                    $board->addEdge(array($x, $y), array($x + 1, $y), Board::EAST);
                }
                if ($y > 0) {
                    $board->addEdge(array($x, $y), array($x, $y - 1), Board::NORTH);
                }
                if ($y < $board->getHeight() - 1) {
                    $board->addEdge(array($x, $y), array($x, $y + 1), Board::SOUTH);
                }
            }
        }
    }

    private function initObstacles($board)
    {
        foreach ($this->obstacles as $obstacle) {
            $board->addObstacle($obstacle);
        }
    }
}