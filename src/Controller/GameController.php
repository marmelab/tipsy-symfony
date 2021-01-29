<?php

namespace App\Controller;

use App\Entity\Board;
use App\Services\GameService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'tipsy-game';
    private $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    public function new(EntityManagerInterface $manager)
    {
        $game = $this->gameService->newGame();
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

        return $this->render('game/game.html.twig', [
            'board' => $board
        ]);
    }

    public function replacePuck(int $id, Request $request)
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (!$playerHash) {
            return $this->redirectToRoute('index');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $board = $entityManager
            ->getRepository(Board::class)
            ->find($id);
        $this->gameService->replacePuck($board);
        $entityManager->persist($board);
        $entityManager->flush();

        return $this->redirectToRoute('game', ['id' => $board->getId()]);
    }

    public function tilt(int $id, Request $request)
    {
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (!$playerHash) {
            return $this->redirectToRoute('index');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $board = $entityManager
            ->getRepository(Board::class)
            ->find($id);

        $action = $request->get('action');
        if (!in_array($action, [Board::NORTH, Board::SOUTH, Board::EAST, Board::WEST])) {
            throw new BadRequestHttpException("Wrong action parameter");
        }
        $this->gameService->tilt($board, $action);
        $entityManager->persist($board);
        $entityManager->flush();

        return $this->redirectToRoute('game', ['id' => $board->getId()]);
    }

    protected function generatePlayerHash()
    {
        return hash('sha256', uniqid(), false);
    }
}
