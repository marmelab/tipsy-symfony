<?php

use App\Entity\Board;
use App\Services\GameService;
use PHPUnit\Framework\TestCase;

class GameServiceTest extends TestCase
{
    public function test_tilt_to_right_should_move_puck_to_right_border()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $this->assertEquals($board->getCellType(3, 3)[Board::COLOR_KEY], Board::BLACK);
        // WHEN
        $gameService->tilt($board, Board::EAST);

        // THEN
        $this->assertEquals($board->getCellType(3, 3)[Board::COLOR_KEY], Board::RED);
        $this->assertEquals($board->getCellType(4, 3)[Board::COLOR_KEY], Board::BLACK);
        $this->assertEquals($board->getCellType(5, 3)[Board::COLOR_KEY], Board::RED);
    }

    public function test_tilt_to_left_should_move_puck_to_left_border()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $this->assertEquals($board->getCellType(3, 3)[Board::COLOR_KEY], Board::BLACK);
        // WHEN
        $gameService->tilt($board, Board::WEST);

        // THEN
        $this->assertEquals($board->getCellType(1, 3)[Board::COLOR_KEY], Board::RED);
        $this->assertEquals($board->getCellType(2, 3)[Board::COLOR_KEY], Board::BLACK);
        $this->assertEquals($board->getCellType(3, 3)[Board::COLOR_KEY], Board::RED);
    }

    public function test_tilt_north_west_north_west_north_south_should_not_end_with_error()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $this->assertEquals($board->getCellType(3, 3)[Board::COLOR_KEY], Board::BLACK);
        // WHEN
        $gameService->tilt($board, Board::NORTH);
        $gameService->tilt($board, Board::WEST);
        $gameService->tilt($board, Board::NORTH);
        $gameService->tilt($board, Board::WEST);
        $gameService->tilt($board, Board::NORTH);
        $gameService->tilt($board, Board::SOUTH);
    }

    public function test_tilt_north_east_north_east_north_east_should_move_a_puck_out()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $this->assertEquals($board->getCellType(3, 3)[Board::COLOR_KEY], Board::BLACK);
        // WHEN
        $gameService->tilt($board, Board::NORTH);
        $gameService->tilt($board, Board::EAST);
        $gameService->tilt($board, Board::NORTH);
        $gameService->tilt($board, Board::EAST);

        // THEN
        $this->assertNull($board->getCellType(6, 1));
        $flippedBluePucks = $board->getPucksIdsBy(Board::BLUE,true);
        $unflippedBluePucks = $board->getPucksIdsBy(Board::BLUE,false);
        $this->assertEquals(1, count($flippedBluePucks));
        $this->assertEquals(5, count($unflippedBluePucks));
    }

    public function test_it_should_randomly_set_current_user_when_starting_a_new_game()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();

        // THEN
        $this->assertNotNull($board->getCurrentPlayer());
        $this->assertContains($board->getCurrentPlayer(),['Blue','Red']);
    }
}
