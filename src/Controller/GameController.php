<?php

namespace App\Controller;

use App\Entity\Board;
use App\Services\GameService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'tipsy-game';
    private $gameService;
    private $session;

    public function __construct(GameService $gameService, SessionInterface $session)
    {
        $this->gameService = $gameService;
        $this->session = $session;
    }

    public function new()
    {
        $game = $this->gameService->newGame();
        $player = $this->generatePlayerHash();
        $response = $this->redirectToRoute('game');
        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $player));
        $this->session->set($player, $game);

        return $response;
    }

    public function show(Request $request)
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        $board = $this->session->get($playerHash);
        return $this->render('game/game.html.twig', [
            'board' => $board
        ]);
    }

    public function action(Request $request)
    {
        $action = $request->query->get('action');
        $board = $this->getSessionBoard($request);
        $this->gameService->tilt($board, $action);
        return $this->redirectToRoute('game');

    }

    private function getSessionBoard(Request $request) {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        return $this->session->get($playerHash);
    }

    protected function generatePlayerHash()
    {
        return hash('sha256', uniqid(), false);
    }
}
