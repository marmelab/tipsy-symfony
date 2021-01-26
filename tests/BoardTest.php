<?php

use App\Entity\Board;
use App\Services\GameService;
use PHPUnit\Framework\TestCase;

class BoardTest extends TestCase
{
    public function testTiltToRightShouldMovePuckToRightBorder(){
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $this->assertEquals($board->getCellType(3,3),Board::BLACK);
        // WHEN
        $board = $gameService->tilt($board,Board::EAST);

        // THEN
        $this->assertEquals($board->getCellType(3,3),Board::RED);
        $this->assertEquals($board->getCellType(4,3),Board::BLACK);
        $this->assertEquals($board->getCellType(5,3),Board::RED);

    }
}
