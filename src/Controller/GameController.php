<?php

namespace App\Controller;

use App\Entity\Game;
use App\Services\GameService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GameController extends AbstractController
{
    const COOKIE_KEY = 'tipsy-game';
    private $gameService;

    public function __construct(GameService $gameService, EntityManagerInterface $entityManager)
    {
        $this->gameService = $gameService;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/game", name="new", methods={"POST"})
     */
    public function new(Request $request)
    {
        $playerName = $request->toArray()["playerName"];
        $playerHash = $this->generatePlayerHash();
        $game = $this->gameService->newGame($playerHash, $playerName);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $response = $this->json($game);
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

        $response = $this->json($game);
        $response->headers->setCookie(new Cookie($this::COOKIE_KEY, $playerHash));
        return $response;
    }

    public function replacePuck(int $id, Request $request)
    {

        $game = $this->entityManager
            ->getRepository(Game::class)
            ->find($id);
        $this->gameService->replacePuck($game);

        $this->entityManager->flush();

        return $this->redirectToRoute('game', ['id' => $game->getId()]);
    }

    public function tilt(int $id, Request $request)
    {

        $game = $this->entityManager
            ->getRepository(Game::class)
            ->find($id);

        $action = $request->get('action');
        if (!in_array($action, [Game::NORTH, Game::SOUTH, Game::EAST, Game::WEST])) {
            throw new BadRequestHttpException("Wrong action parameter");
        }
        $this->gameService->tilt($game, $action);

        $this->entityManager->flush();

        return $this->redirectToRoute('game', ['id' => $game->getId()]);
    }

    protected function generatePlayerHash()
    {
        return hash('sha256', uniqid(), false);
    }
}
