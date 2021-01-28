<?php

namespace App\Controller;

use App\Entity\Board;
use App\Repository\BoardRepository;
use App\Services\GameService;
use Doctrine\ORM\EntityManagerInterface;
use GraphDS\Persistence\ExportGraph;
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

    public function __construct(GameService $gameService, SessionInterface $session, BoardRepository $boardRepo)
    {
        $this->gameService = $gameService;
        $this->session = $session;
    }

    public function new()
    {
        $game = $this->gameService->newGame();
        $game->serializeGraph();
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($game);
        $entityManager->flush();

        $playerHash = $this->generatePlayerHash();
        $response = $this->redirectToRoute('game', ['id' => $game->getId()]);
        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));


        return $response;
    }

    public function show(int $id, Request $request)
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (!$playerHash) {
            return $this->redirectToRoute('index');
        }
        $board = $this->getDoctrine()
            ->getRepository(Board::class)
            ->find($id);
        $board->deserializeGraph();

        return $this->render('game/game.html.twig', [
            'board' => $board
        ]);
    }

    public function replacePuck(Request $request)
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (!$playerHash || !$this->session->get($playerHash)) {
            return $this->redirectToRoute('index');
        }
        $board = $this->session->get($playerHash);
        $this->gameService->replacePuck($board);

        return $this->redirectToRoute('game');
    }

    public function tilt(Request $request)
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (!$playerHash || !$this->session->get($playerHash)) {
            return $this->redirectToRoute('index');
        }

        $board = $this->session->get($playerHash);

        $action = $request->get('action');
        if (!in_array($action, [Board::NORTH, Board::SOUTH, Board::EAST, Board::WEST])) {
            throw new BadRequestHttpException("Wrong action parameter");
        }
        $this->gameService->tilt($board, $action);
        $this->session->set($playerHash, $board);

        return $this->redirectToRoute('game');
    }

    protected function generatePlayerHash()
    {
        return hash('sha256', uniqid(), false);
    }
}
