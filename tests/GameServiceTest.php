<?php

use App\Entity\Game;
use App\Services\GameService;
use PHPUnit\Framework\TestCase;

class GameServiceTest extends TestCase
{
    public function test_tilt_to_right_should_move_puck_to_right_border()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::BLACK);
        // WHEN
        $gameService->tilt($game, Game::EAST);

        // THEN
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::RED);
        $this->assertEquals($game->getCellType(4, 3)[Game::COLOR_KEY], Game::BLACK);
        $this->assertEquals($game->getCellType(5, 3)[Game::COLOR_KEY], Game::RED);
    }

    public function test_tilt_to_left_should_move_puck_to_left_border()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::BLACK);
        // WHEN
        $gameService->tilt($game, Game::WEST);

        // THEN
        $this->assertEquals($game->getCellType(1, 3)[Game::COLOR_KEY], Game::RED);
        $this->assertEquals($game->getCellType(2, 3)[Game::COLOR_KEY], Game::BLACK);
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::RED);
    }

    public function test_tilt_north_west_north_west_north_south_should_not_end_with_error()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::BLACK);
        // WHEN
        try {
            $gameService->tilt($game, Game::NORTH);
            $gameService->tilt($game, Game::WEST);
            $gameService->tilt($game, Game::NORTH);
            $gameService->tilt($game, Game::WEST);
            $gameService->tilt($game, Game::NORTH);
            $gameService->tilt($game, Game::SOUTH);
            //THEN
        } catch (Exception $e) {
            $this->fail('An error occured when succecingly moving to North West Noth West North South', $e);
        }
    }

    public function test_tilt_north_east_north_east_north_east_should_move_a_puck_out()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::BLACK);
        // WHEN
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);

        // THEN
        $this->assertNull($game->getCellType(6, 1));
        $this->assertEquals(2, $game->getFallenPucks(Game::BLUE));
    }

    public function test_it_should_set_current_user_when_starting_a_new_game()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));

        // THEN
        $this->assertNotNull($game->getCurrentPlayer());
        $this->assertContains($game->getCurrentPlayer(), [Game::BLUE, Game::RED]);
    }


    public function test_it_should_initialize_remaining_turns_when_starting_a_new_game()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));

        // THEN
        $this->assertNotNull($game->getRemainingTurns());
        $this->assertGreaterThan(0, $game->getRemainingTurns());
    }

    public function test_it_should_decrement_players_when_tilt_is_done()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $remainingTurns = $game->getRemainingTurns();

        // WHEN
        $gameService->tilt($game, Game::SOUTH);

        // THEN
        $this->assertEquals($remainingTurns - 1, $game->getRemainingTurns());
    }

    public function test_it_should_switch_players_when_no_remaining_turns()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $remainingTurns = $game->getRemainingTurns();
        $currentPlayer = $game->getCurrentPlayer();

        // WHEN
        for ($i = 0; $i < $remainingTurns; $i++) {
            $gameService->tilt($game, Game::SOUTH);
        }

        // THEN
        $this->assertNotEquals($currentPlayer, $game->getCurrentPlayer());
        $this->assertGreaterThan(0, $game->getRemainingTurns());
    }

    public function test_we_should_have_to_replace_pucks_when_a_puck_have_fallen_and_no_tilts_left()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::BLACK);
        // WHEN
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);

        // THEN
        $this->assertTrue($game->shouldReplacePucks());
    }

    public function test_we_sould_not_have_to_replace_pucks_when_no_puck_have_fallen_and_no_tilts_left()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::BLACK);
        // WHEN
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);

        // THEN
        $this->assertFalse($game->shouldReplacePucks());
    }

    public function test_we_should_switch_player_and_reset_remaining_turns_after_replacing_all_fallen_pucks()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::BLACK);
        $currentPlayer = $game->getCurrentPlayer();
        // WHEN
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);
        // player switch
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);
        $gameService->replacePuck($game);
        // player reswitch

        // THEN
        $this->assertGreaterThan(0, $game->getRemainingTurns());
        $this->assertEquals($currentPlayer, $game->getCurrentPlayer());
    }

    public function test_we_should_increase_score_of_the_blue_player_when_a_blue_puck_have_fallen()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals($game->getCellType(3, 3)[Game::COLOR_KEY], Game::BLACK);
        // WHEN
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);
        $gameService->tilt($game, Game::NORTH);
        $gameService->tilt($game, Game::EAST);

        // THEN
        $this->assertEquals(1, $game->getScore(Game::BLUE));
        $this->assertEquals(0, $game->getScore(Game::RED));
    } 
    
    public function test_we_should_increment_remaining_turns_when_using_beer_power_up()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals(2, $game->remainingTurns);

        // WHEN
        $gameService->usePowerUp($game, Game::BEER);

        // THEN
        $this->assertEquals(3, $game->remainingTurns);
    }    
    
    public function test_we_should_do_nothing_when_using_beer_power_up_and_no_beers_remain()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $game->players[Game::RED]["powerUps"][Game::BEER] = 0;

        // WHEN
        $gameService->usePowerUp($game, Game::BEER);

        // THEN
        $this->assertEquals(2, $game->remainingTurns);
    } 

    public function test_we_should_decrement_beers_when_using_beer_power_up()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $this->assertEquals(2, $game->remainingTurns);
        $this->assertEquals($game->players[Game::RED]["powerUps"][Game::BEER],1);

        // WHEN
        $gameService->usePowerUp($game, Game::BEER);

        // THEN
        $this->assertEquals($game->players[Game::RED]["powerUps"][Game::BEER],0);
    }    
    
    public function test_we_should_switch_player_color_when_using_whisky()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $game->addPlayer("joe", hash('sha256', uniqid(), false));
        $this->assertEquals($game->players[Game::RED]["name"], "bobby");
        $this->assertEquals($game->players[Game::BLUE]["name"], "joe");

        // WHEN
        $gameService->usePowerUp($game, Game::WHISKY);
        
        // THEN
        $this->assertEquals($game->players[Game::BLUE]["name"], "bobby");
        $this->assertEquals($game->players[Game::RED]["name"], "joe");
        $this->assertEquals($game->players[Game::BLUE]["powerUps"][Game::WHISKY],0);
        $this->assertEquals($game->players[Game::RED]["powerUps"][Game::WHISKY],1);
    }   

    public function test_we_should_do_nothing_when_using_whisky_power_up_and_no_whisky_remain()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $game->players[Game::RED]["powerUps"][Game::WHISKY] = 0;

        // WHEN
        $gameService->usePowerUp($game, Game::WHISKY);

        // THEN
        $this->assertEquals($game->players[Game::RED]["name"], "bobby");
    } 


    public function test_it_should_create_empty_game_with_the_right_playerName()
    {
        // GIVEN
        $gameService = new GameService();
        $game = $gameService->newGame("bobby",hash('sha256', uniqid(), false));
        $gameService->tilt($game, Game::EAST);

        // THEN
        $this->assertEquals($game->players[Game::RED]["name"], "bobby");
    }
}
