<?php

namespace App\Controller;

use App\Entity\Board;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class GameController extends AbstractController
{

    private $obstacles = array(
        array(0, 3), array(1, 1), array(1, 5), array(2, 2),
        array(2, 4), array(3, 0), array(3, 6), array(4, 2),
        array(4, 4), array(5, 1), array(5, 5), array(6, 3));
    public $board;

    public function new()
    {
        $this->board = new Board(7, 7);
        $this->initEmptyBoard();
        $this->initObstacles();
        return $this->render('game/game.html.twig', [
            'board' => $this->board
        ]);
    }


    public function initEmptyBoard()
    {
        foreach (range(0, $this->board->getWidth() - 1) as $x) {
            foreach (range(0, $this->board->getHeight() - 1) as $y) {
                if ($x > 0) {
                    $this->board->addEdge(array($x - 1, $y), array($x, $y), Board::WEST);
                }
                if ($x < $this->board->getWidth() - 1) {
                    $this->board->addEdge(array($x, $y), array($x + 1, $y), Board::EAST);
                }
                if ($y > 0) {
                    $this->board->addEdge(array($x, $y), array($x, $y - 1), Board::NORTH);
                }
                if ($y < $this->board->getHeight() - 1) {
                    $this->board->addEdge(array($x, $y), array($x, $y + 1), Board::SOUTH);
                }
            }
        }
    }

    private function initObstacles()
    {
        foreach ($this->obstacles as $obstacle) {
            $this->board->addObstacle($obstacle);
        }
    }
}
