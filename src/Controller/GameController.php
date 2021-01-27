<?php

namespace App\Controller;

use App\Entity\Board;
use App\Services\GameService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'tipsy-game';
    private $gameService;
    private $session;

    public function __construct(GameService $gameService, SessionInterface $session, LoggerInterface $logger)
    {
        $this->gameService = $gameService;
        $this->session = $session;
    }

    public function new()
    {
        $game = $this->gameService->newGame();
        $playerHash = $this->generatePlayerHash();
        $response = $this->redirectToRoute('game');
        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));
        $this->session->set($playerHash, $game);

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

    public function tilt(Request $request)
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        $board = $this->session->get($playerHash);

        $action = $request->get('action');
        if (!in_array($action, [Board::NORTH, Board::SOUTH, Board::EAST, Board::WEST])) {
            throw new BadRequestHttpException("Wrong action parameter");
        }
        $board = $this->gameService->tilt($board, $action);
        $this->session->set($playerHash, $board);


        return $this->redirectToRoute('game');
    }

    protected function generatePlayerHash()
    {
        return hash('sha256', uniqid(), false);
    }
}
