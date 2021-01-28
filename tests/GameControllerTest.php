<?php

namespace App\Tests;

use App\Controller\GameController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    public function test_it_should_redirect_to_game_with_set_cookie_when_requesting_index_url()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseHasCookie(GameController::COOKIE_KEY);

    }

    public function test_it_should_redirect_to_index_when_requesting_game_without_cookie()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/game/1234');

        $this->assertResponseRedirects('/');
    }
}
