<?php

namespace App\Services;

use App\Entity\Game;
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
    private $exits = array(array(1, -1), array(7, 1), array(-1, 5), array(5, 7));
    private $players = array(Game::RED, Game::BLUE);

    public function newGame( string $playerName, string $id): Game
    {
        $game = new Game(7, 7);
        $this->initEmptyBoard($game);
        $this->initObstacles($game);
        $this->initPucks($game);
        $this->initExits($game);
        $this->initPlayers($game, $playerName, $id);
        return $game;
    }

    private function initPlayers(Game $game, string $name, string $id)
    {
        $firstPlayer = $this->players[rand(0, 1)];
        $game->setEmptyPlayers($this->players);
        $game->setCurrentPlayer($firstPlayer, $name, $id);
        $game->setRemainingTurns(2);
    }
    private function initExits(Game $game)
    {
        foreach ($this->exits as $exit) {
            $game->addExit($exit);
        }
    }

    private function initPucks(Game $game)
    {
        foreach ($this->red_pucks as $red_puck) {
            $game->addPuck($red_puck, Game::RED);
        }
        foreach ($this->blue_pucks as $blue_puck) {
            $game->addPuck($blue_puck, Game::BLUE);
        }
        $game->addPuck($this->black_puck, Game::BLACK);
    }

    public function replacePuck(Game $game)
    {
        $opponent = $game->getCurrentOpponent();
        $opponentPucks = $game->getFallenPucks($opponent);

        $player = $game->getCurrentPlayer();
        $currentPlayerPucks = $game->getFallenPucks($player);
        if($currentPlayerPucks==0 && $opponentPucks==0){
            return;
        }
        if ($opponentPucks > 0) {
            $player = $opponent;
        }
        $game->decrementFallenPucks($player);
        $game->replacePuck($player);
    }

    private function initEmptyBoard(Game $game)
    {
        foreach (range(0, $game->getWidth() - 1) as $x) {
            foreach (range(0, $game->getHeight() - 1) as $y) {
                if ($x > 0) {
                    $game->addEdge(array($x, $y), array($x - 1, $y), Game::WEST);
                }
                if ($x < $game->getWidth() - 1) {
                    $game->addEdge(array($x, $y), array($x + 1, $y), Game::EAST);
                }
                if ($y > 0) {
                    $game->addEdge(array($x, $y), array($x, $y - 1), Game::NORTH);
                }
                if ($y < $game->getHeight() - 1) {
                    $game->addEdge(array($x, $y), array($x, $y + 1), Game::SOUTH);
                }
            }
        }
    }

    private function initObstacles(Game $game)
    {
        foreach ($this->obstacles as $obstacle) {
            $game->addObstacle($obstacle);
        }
    }

    public function tilt(Game $game, String $direction)
    {
        $game->tilt($direction);
    }
}
