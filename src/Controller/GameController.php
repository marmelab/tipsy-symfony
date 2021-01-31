<?php

namespace App\Controller;

use App\Entity\Game;
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

    public function new()
    {

        $playerHash = $this->generatePlayerHash();
        $game = $this->gameService->newGame($playerHash);
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($game);
        $entityManager->flush();

        $response = $this->redirectToRoute('game', ['id' => $game->getId()]);
        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));

        return $response;
    }

    public function show(int $id, Request $request)
    {
        if (empty($id)) {
            return $this->redirectToRoute('index');
        }
        $game = $this->getDoctrine()
            ->getRepository(Game::class)
            ->find($id);
        if (empty($game)) {
            return $this->redirectToRoute('index');
        }
        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        if (empty($playerHash) || !$game->hasPlayer($playerHash)) {
            if ($game->isFull()) {
                return $this->redirectToRoute('index');
            }
            $playerHash = $this->generatePlayerHash();
            $game->addPlayer($playerHash);
            $this->getDoctrine()->getManager()->flush();
        }
        $response = $this->render('game/game.html.twig', [
            'game' => $game
        ]);
        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));
        return $response;
    }

    public function replacePuck(int $id, Request $request)
    {
        // $playerHash = $request->cookies->get($this::COOKIE_KEY);
        // if (!$playerHash) {
        //     return $this->redirectToRoute('index');
        // }

        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager
            ->getRepository(Game::class)
            ->find($id);
        $this->gameService->replacePuck($game);

        $entityManager->flush();

        return $this->redirectToRoute('game', ['id' => $game->getId()]);
    }

    public function tilt(int $id, Request $request)
    {

        $entityManager = $this->getDoctrine()->getManager();
        $game = $entityManager
            ->getRepository(Game::class)
            ->find($id);

        $action = $request->get('action');
        if (!in_array($action, [Game::NORTH, Game::SOUTH, Game::EAST, Game::WEST])) {
            throw new BadRequestHttpException("Wrong action parameter");
        }
        $this->gameService->tilt($game, $action);

        $entityManager->flush();

        return $this->redirectToRoute('game', ['id' => $game->getId()]);
    }

    protected function generatePlayerHash()
    {
        return hash('sha256', uniqid(), false);
    }
}
