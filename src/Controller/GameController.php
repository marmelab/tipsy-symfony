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

    public function new(EntityManagerInterface $manager)
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
        $game = $this->getDoctrine()
            ->getRepository(Game::class)
            ->find($id);

        $playerHash = $request->cookies->get($this::COOKIE_KEY);
        $response = $this->render('game/game.html.twig', [
            'game' => $game
        ]);
        if (!$playerHash || empty($id)) {
            if ($game->isFull()){
                return $this->redirectToRoute('index');
            }
            $playerHash = $this->generatePlayerHash();
            $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));
        }

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
