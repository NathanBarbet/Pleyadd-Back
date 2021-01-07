<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;
use App\Entity\Actu;
use App\Entity\User;
use App\Entity\WarzoneTournois;
use App\Entity\WarzoneSaison;
use Knp\Component\Pager\PaginatorInterface;

class PageController extends AbstractController
{
    
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

  //* Display mention legale
    public function mentionlegale(): Response
    {

        return new Response($this->twig->render('mentionlegale.html.twig'));
    }

    //* Display charte
    public function charte(): Response
    {

        return new Response($this->twig->render('charte.html.twig'));
    }

    //* Display CGU
    public function cgu(): Response
    {

        return new Response($this->twig->render('cgu.html.twig'));
    }

    //* Display FAQ
    public function faq(): Response
    {

        return new Response($this->twig->render('faq.html.twig'));
    }

    //* Display Ligue
    public function ligue(): Response
    {

        return new Response($this->twig->render('ligue.html.twig'));
    }

    //* Display Soutenir
    public function soutenir(): Response
    {

        return new Response($this->twig->render('Dons/soutenir.html.twig'));
    }

    //* Display Remerciement
    public function remerciement(): Response
    {

        return new Response($this->twig->render('Dons/remerciement.html.twig'));
    }

    //* Display debug
    public function debug(): Response
    {
        phpinfo();
    }

    //* Display Actu
    public function actu(Request $request, PaginatorInterface $paginator)
    {
        $title = 'Actualité';
        $repository = $this->getDoctrine()->getRepository(Actu::class);
        $data = $repository->findBy([], ['datePublish' => 'DESC']);

        $actus = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            6 // Nombre de résultats par page
        );

        return new Response($this->twig->render('actu.html.twig', [
        'title' => $title,
        'actus' => $actus
        ]));
    }

    public function classementWarzoneGlobal(?UserInterface $user, Request $request, PaginatorInterface $paginator)
    {
        $title = 'Warzone | Classement - Saison';
        $repository = $this->getDoctrine()->getRepository(WarzoneSaison::class);
        $dateNow = time();
        $saison = $repository->getActualSaison($dateNow);

        $dateDebut = $saison[0]->getDateDebut();
        $dateFin = $saison[0]->getDateFin();

        $repository = $this->getDoctrine()->getRepository(User::class);
        $data = $repository->getClassementWarzoneGlobal($dateDebut, $dateFin);
        if ($user) {
            $pseudo = $user->getPseudo();
            if (in_array($pseudo, array_column($data, 'pseudo'))) {
                $place = array_search($pseudo, array_column($data, 'pseudo'));
                $userPlace = $data[$place];

                $place = $place + 1;
            } else {
                $place = 0;
                $userPlace = 0;
            }
            

            $classement = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                20 // Nombre de résultats par page
            );
    
            $count = $classement->getCurrentPageNumber();
    
            return $this->render('warzoneclassement.html.twig', ['title' => $title, 'classement' => $classement, 'count' => $count, 'userPlace' => $userPlace, 'place' => $place, 'saison' => $saison]);
        } else {
            $classement = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                20 // Nombre de résultats par page
            );
    
            $count = $classement->getCurrentPageNumber();
    
            return $this->render('warzoneclassement.html.twig', ['title' => $title, 'classement' => $classement, 'count' => $count, 'saison' => $saison]);
        }
    }

    public function classementWarzoneMensuel(?UserInterface $user, Request $request, PaginatorInterface $paginator)
    {
        $title = 'Warzone | Classement - Mensuel';
        $repository = $this->getDoctrine()->getRepository(User::class);
        $date = (new \DateTime())->format('m');
        $data = $repository->getClassementWarzoneMensuel($date);

        if ($user) {
            $pseudo = $user->getPseudo();
            $place = array_search($pseudo, array_column($data, 'pseudo'));
            if (!empty($data)) {
                $userPlace = $data[$place];

                $places = $place + 1;

                $classement = $paginator->paginate(
                    $data,
                    $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                    20 // Nombre de résultats par page
                );

                if ($pseudo == $data[$place]['pseudo']) {
                    $count = $classement->getCurrentPageNumber();
        
                    return $this->render('warzoneclassement.html.twig', ['title' => $title, 'classement' => $classement, 'count' => $count, 'userPlace' => $userPlace, 'place' => $places]);
                } else {
                    $count = $classement->getCurrentPageNumber();
        
                    return $this->render('warzoneclassement.html.twig', ['title' => $title, 'classement' => $classement, 'count' => $count]);
                }
            } else {
                $classement = $paginator->paginate(
                    $data,
                    $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                    20 // Nombre de résultats par page
                );

                $count = $classement->getCurrentPageNumber();
    
                return $this->render('warzoneclassement.html.twig', ['title' => $title, 'classement' => $classement, 'count' => $count]);
            }
        } else {
            $classement = $paginator->paginate(
                $data,
                $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                20 // Nombre de résultats par page
            );
    
            $count = $classement->getCurrentPageNumber();
    
            return $this->render('warzoneclassement.html.twig', ['title' => $title, 'classement' => $classement, 'count' => $count]);
        }
    }

    //* Display Tournois
    public function tournoisWarzone(Request $request, PaginatorInterface $paginator)
    {
        $title = 'Évènements Warzone';
        $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
        $data = $repository->findBy(['isClose' => '0'], ['dateDebut' => 'ASC']);

        $tournois = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            6 // Nombre de résultats par page
        );

        return new Response($this->twig->render('warzonetournois.html.twig', [
        'title' => $title,
        'tournois' => $tournois
        ]));
    }

    //* Display Tournois Close
    public function tournoisCloseWarzone(Request $request, PaginatorInterface $paginator)
    {
        $title = 'Évènements Warzone terminés';
        $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
        $data = $repository->findBy(['isClose' => '1'], ['dateDebut' => 'DESC']);

        $tournois = $paginator->paginate(
            $data,
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            6 // Nombre de résultats par page
        );

        return new Response($this->twig->render('warzonetournoisclose.html.twig', [
        'title' => $title,
        'tournois' => $tournois
        ]));
    }
}
