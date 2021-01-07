<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;
use App\Entity\Accueil;
use App\Entity\WarzoneTournois;

class HomeController extends AbstractController
{
    
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

  //* Display home page
    public function index(Request $request)
    {
        $repository = $this->getDoctrine()->getRepository(Accueil::class);
        $slider = $repository->findBy(['slider' => '1'], ['ordre' => 'ASC']);
        $card = $repository->findBy(['slider' => '0'], ['ordre' => 'ASC']);

        $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
        $tournois = $repository->findOneBy(['isClose' => '0']);

        return new Response($this->twig->render('home.html.twig', [
        'slider' => $slider,
        'card' => $card,
        'tournois' => $tournois,
        ]));
    }
}
