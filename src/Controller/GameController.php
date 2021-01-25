<?php

namespace App\Controller;

use App\Entity\Board;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class GameController extends AbstractController
{
    public function new()
    {
        $board = new Board();

        return $this->render('game/game.html.twig', [
            'board' => $board
        ]);
    }
}
