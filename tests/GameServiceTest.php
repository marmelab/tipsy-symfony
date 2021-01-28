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
        try {
            $gameService->tilt($board, Board::NORTH);
            $gameService->tilt($board, Board::WEST);
            $gameService->tilt($board, Board::NORTH);
            $gameService->tilt($board, Board::WEST);
            $gameService->tilt($board, Board::NORTH);
            $gameService->tilt($board, Board::SOUTH);
            //THEN
        } catch (Exception $e) {
            $this->fail('An error occured when succecingly moving to North West Noth West North South', $e);
        }
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
        $gameService->tilt($board, Board::NORTH);
        $gameService->tilt($board, Board::EAST);

        // THEN
        $this->assertNull($board->getCellType(6, 1));
        $this->assertEquals(2, $board->getFallenPucks(Board::BLUE));
    }

    public function test_it_should_randomly_set_current_user_when_starting_a_new_game()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();

        // THEN
        $this->assertNotNull($board->getCurrentPlayer());
        $this->assertContains($board->getCurrentPlayer(), [Board::BLUE, Board::RED]);
    }


    public function test_it_should_initialize_remaining_turns_when_starting_a_new_game()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();

        // THEN
        $this->assertNotNull($board->getRemainingTurns());
        $this->assertGreaterThan(0, $board->getRemainingTurns());
    }

    public function test_it_should_decrement_players_when_tilt_is_done()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $remainingTurns = $board->getRemainingTurns();

        // WHEN
        $gameService->tilt($board, Board::SOUTH);

        // THEN
        $this->assertEquals($remainingTurns - 1, $board->getRemainingTurns());
    }

    public function test_it_should_switch_players_when_no_remaining_turns()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $remainingTurns = $board->getRemainingTurns();
        $currentPlayer = $board->getCurrentPlayer();

        // WHEN
        for ($i = 0; $i < $remainingTurns; $i++) {
            $gameService->tilt($board, Board::SOUTH);
        }

        // THEN
        $this->assertNotEquals($currentPlayer, $board->getCurrentPlayer());
        $this->assertGreaterThan(0, $board->getRemainingTurns());
    }

    public function test_we_should_have_to_replace_pucks_when_a_puck_have_fallen_and_no_tilts_left()
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
        $this->assertTrue($board->shouldReplacePucks());
    }

    public function test_we_sould_not_have_to_replace_pucks_when_no_puck_have_fallen_and_no_tilts_left()
    {
        // GIVEN
        $gameService = new GameService();
        $board = $gameService->newGame();
        $this->assertEquals($board->getCellType(3, 3)[Board::COLOR_KEY], Board::BLACK);
        // WHEN
        $gameService->tilt($board, Board::NORTH);
        $gameService->tilt($board, Board::EAST);

        // THEN
        $this->assertFalse($board->shouldReplacePucks());
    }
}
