<?php

namespace App\Controller;

use App\Entity\Board;
use App\Services\GameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class GameController extends AbstractController
{

    private $gameService;

    public function __construct(GameService $gameService){
        $this->gameService = $gameService;
    }
    public function new()
    {
        return $this->render('game/game.html.twig', [
            'board' => $this->gameService->newGame(),
        ]);
    }
}
