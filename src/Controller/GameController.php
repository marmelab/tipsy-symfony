<?php

namespace App\Controller;

use App\Dto\GameDto;
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
        $withBot = array_key_exists("withBot",$request->toArray()) && $request->toArray()["withBot"];
        $game = $this->gameService->newGame($playerName);
        if ($withBot){
            $game->addPlayer("bot");
        }

        $this->entityManager->persist($game);
        $this->entityManager->flush();


        $response = $this->json(new GameDto($game));
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    /**
     * @Route("/game/pending", name="getPending", methods={"GET"})
     */
    public function getPendingGames()
    {
        $games = $this->getDoctrine()
            ->getRepository(Game::class)
            ->findAll();
        $games = array_filter($games, function($game){
            foreach ($game->players as $player){
                if (!$player["name"]){
                    return true;
                }
            }
        });
        $games = array_map(function($game){
            return new GameDto($game);
        }, $games);
        $response = $this->json(array_values($games));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * @Route("/game/{id}", name="get", methods={"GET"})
     */
    public function show(int $id, Request $request)
    {
        if (empty($id)) {
            return $this->createNotFoundException();
        }
        $game = $this->getDoctrine()
            ->getRepository(Game::class)
            ->find($id);
        if (empty($game)) {
            return $this->createNotFoundException();
        }

        $response = $this->json(new GameDto($game));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * @Route("/game/{id}/join", name="join", methods={"POST"})
     */
    public function joinGame(int $id, Request $request){
        if (empty($id)) {
            return $this->createNotFoundException();
        }
        $game = $this->getDoctrine()
            ->getRepository(Game::class)
            ->find($id);
        if (empty($game)) {
            return $this->createNotFoundException();
        }
        $playerName = $request->toArray()["playerName"];
        $game->addPlayer($playerName);
        $this->getDoctrine()->getManager()->flush();
        
        $response = $this->json(new GameDto($game));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * @Route("/game/{id}/replace", name="replace", methods={"POST"})
     */
    public function replacePuck(int $id, Request $request)
    {

        $game = $this->entityManager
            ->getRepository(Game::class)
            ->find($id);
        $this->gameService->replacePuck($game);

        $this->entityManager->flush();

        $response = $this->json(new GameDto($game));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * @Route("/game/{id}/tilt", name="tilt", methods={"POST"})
     */
    public function tilt(int $id, Request $request)
    {

        $game = $this->entityManager
            ->getRepository(Game::class)
            ->find($id);

        $playerName = $request->toArray()["playerName"];
        $direction = $request->toArray()["direction"];

        //I don't want to tilt if it's not currentPlayer turn
        if ($game->getCurrentPlayerName() != $playerName){
            $response = $this->json(new GameDto($game));
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }

        if (!in_array($direction, [Game::NORTH, Game::SOUTH, Game::EAST, Game::WEST])) {
            throw new BadRequestHttpException("Wrong action parameter");
        }

        $this->gameService->tilt($game, $direction);

        $this->entityManager->flush();

        $response = $this->json(new GameDto($game));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

}
