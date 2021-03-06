<?php

namespace App\Tests;

use App\Controller\GameController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    public function test_it_should_redirect_to_index_when_requesting_game_without_cookie()
    {

        $client = static::createClient();
        $client->xmlHttpRequest('GET', '/game/pending');

        $this->assertResponseIsSuccessful();
    }
}
