<?php

use App\Entity\Board;
use App\Services\GameService;
use PHPUnit\Framework\TestCase;

class GameServiceTest extends TestCase
{
    public function testTiltToRightShouldMovePuckToRightBorder(){
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $this->assertEquals($board->getCellType(3,3)[Board::COLOR_KEY],Board::BLACK);
        // WHEN
        $board = $gameService->tilt($board,Board::EAST);

        // THEN
        $this->assertEquals($board->getCellType(3,3)[Board::COLOR_KEY],Board::RED);
        $this->assertEquals($board->getCellType(4,3)[Board::COLOR_KEY],Board::BLACK);
        $this->assertEquals($board->getCellType(5,3)[Board::COLOR_KEY],Board::RED);

    }

    public function testTiltToRightShouldMovePuckToLeftBorder(){
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $this->assertEquals($board->getCellType(3,3)[Board::COLOR_KEY],Board::BLACK);
        // WHEN
        $board = $gameService->tilt($board,Board::WEST);

        // THEN
        $this->assertEquals($board->getCellType(1,3)[Board::COLOR_KEY],Board::RED);
        $this->assertEquals($board->getCellType(2,3)[Board::COLOR_KEY],Board::BLACK);
        $this->assertEquals($board->getCellType(3,3)[Board::COLOR_KEY],Board::RED);

    }
}
