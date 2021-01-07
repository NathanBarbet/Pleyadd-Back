<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;
use App\Entity\User;
use App\Entity\WarzoneUserPoint;
use App\Entity\WarzoneUserPalmares;
use App\Entity\Perm;
use App\Entity\Actu;
use App\Entity\Accueil;
use App\Entity\WarzoneTournois;
use App\Entity\WarzoneSaison;
use App\Entity\WarzoneTournoisEquipe;
use App\Entity\WarzoneTournoisEquipeList;
use App\Entity\WarzoneTournoisEquipeResultats;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use App\Form\WarzonePointsType;
use App\Form\ActuType;
use App\Form\AccueilType;
use App\Form\AccueilType2;
use App\Form\WarzoneTournoisType;
use App\Form\WarzoneTournoisAdminMkSoloType;
use App\Form\WarzoneTournoisAdminMkDuoType;
use App\Form\WarzoneTournoisAdminMkTrioType;
use App\Form\WarzoneTournoisAdminMkQuadType;
use App\Form\WarzoneTournoisResultatsSoloTypePart1;
use App\Form\WarzoneTournoisResultatsDuoTypePart1;
use App\Form\WarzoneTournoisResultatsTrioTypePart1;
use App\Form\WarzoneTournoisResultatsQuadTypePart1;
use App\Form\WarzoneTournoisTopKillerType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdminController extends AbstractController
{
    private $twig;
    private $encoder;
    private $em;
    private $ipService;
    private $sendEmail;

    public function __construct(Environment $twig, EntityManagerInterface $em)
    {
        $this->twig = $twig;
        $this->em = $em;
    }

    //* Admin Home
    public function adminHome(UserInterface $user): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();

            if ($rank->getId() == '3' or $rank->getId() == '2') {
                #date
                $jour = (new \DateTime())->format('z');
                $semaine = (new \DateTime())->format('W');
                $mois = (new \DateTime())->format('m');
                $an = (new \DateTime())->format('Y');

                $jour = $jour + 1;
                #requetes
                $repository = $this->getDoctrine()->getRepository(User::class);
                $countJour = $repository->getInscritJour($jour);
                $countSemaine = $repository->getInscritSemaine($semaine);
                $countMois = $repository->getInscritMois($mois);
                $countAn = $repository->getInscritAn($an);
                $countTotal = $repository->getInscritTotal();

                $jour30 = $jour - 30;
                $inscrits30Jours = $repository->getInscritParJours($jour, $jour30);

                $inscritsForWeek = $repository->getInscritParSemaine();
                
                $inscritsForMonth = $repository->getInscritParMois();

                $inscritsWithTrn = $repository->getInscritWithTrn();
                $inscritsWithoutTrn = $repository->getInscritWithoutTrn();

                $repository = $this->getDoctrine()->getRepository(WarzoneSaison::class);
                $playersAllSaisons = $repository->findAllSaisons();
                $pointsAllSaisons = $repository->findAllSaisons();

                for ($i = 0; $i < count($pointsAllSaisons); ++$i) {
                    $dateDebut = $pointsAllSaisons[$i]['dateDebut'];
                    $dateFin = $pointsAllSaisons[$i]['dateFin'];

                    $repository = $this->getDoctrine()->getRepository(WarzoneUserPoint::class);
                    $countPoints = $repository->getPointsGiveTotalSaisons($dateDebut, $dateFin);
                    
                    if ($countPoints['count'] != null) {
                        array_push($pointsAllSaisons[$i], $countPoints);
                    } else {
                        $countPoints['count'] = 0;
                        array_push($pointsAllSaisons[$i], $countPoints);
                    }
                }

                for ($i = 0; $i < count($playersAllSaisons); ++$i) {
                    $dateDebut = $playersAllSaisons[$i]['dateDebut'];
                    $dateFin = $playersAllSaisons[$i]['dateFin'];

                    $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);
                    $countPlayers = $repository->playersOnSaisons($dateDebut, $dateFin);
                    $count = $countPlayers[0]['count'];
                    if ($count != null) {
                        array_push($playersAllSaisons[$i], $count);
                    } else {
                        $count = 0;
                        array_push($playersAllSaisons[$i], $count);
                    }
                }

                $playersAllSaisons[0][0] = $playersAllSaisons[0][0] + 563;

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);
                $playersOnTournois = $repository->playersOnTournois();
                $playersOnMonth = $repository->playersOnMonth();
                $playersOnMonth[0][1] = $playersOnMonth[0][1] + 332;
                $array = array (
                    "mois" => "October",
                    1 => 231,
                    "moisnbr" => "10",
                );
                array_unshift($playersOnMonth, $array);

                $repository = $this->getDoctrine()->getRepository(User::class);
                $kdRatioAll = $repository->kdRatioMoy();
                $kdRatioMoy = $kdRatioAll[0][1] / $kdRatioAll[0]['count'];

                return new Response($this->twig->render('Admin/admin.html.twig', [
                    'countJour' => $countJour,
                    'countSemaine' => $countSemaine,
                    'countMois' => $countMois,
                    'countAn' => $countAn,
                    'countTotal' => $countTotal,
                    'playersOnTournois' => $playersOnTournois,
                    'playersOnMonth' => $playersOnMonth,
                    'inscrits30Jours' => $inscrits30Jours,
                    'inscritsForMonth' => $inscritsForMonth,
                    'inscritsForWeek' => $inscritsForWeek,
                    'inscritsWithTrn' => $inscritsWithTrn,
                    'inscritsWithoutTrn' => $inscritsWithoutTrn,
                    'pointsAllSaisons' => $pointsAllSaisons,
                    'playersAllSaisons' => $playersAllSaisons,
                    'kdRatioMoy' => $kdRatioMoy,
                    ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    public function adminWarzonePoints(UserInterface $user, Request $request, PaginatorInterface $paginator): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Warzone Points';

                $repository = $this->getDoctrine()->getRepository(WarzoneUserPoint::class);
                $data = $repository->findBy([], ['date_give' => 'DESC']);

                $pointsGive = $paginator->paginate(
                    $data,
                    $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                    50 // Nombre de résultats par page
                );
                $count = $pointsGive->getCurrentPageNumber();

                return new Response($this->twig->render('Admin/adminWarzonePoints.html.twig', [
                'titre' => $titre,
                'pointsGive' => $pointsGive,
                'count' => $count,
                ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Points give
    public function adminWarzonePointsGive(UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Warzone Give Points';

                $form = $this->createForm(WarzonePointsType::class);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $userPoint = new WarzoneUserPoint();

                    $joueur = $form->get('pseudo')->getData();
                    $pseudo = $joueur->getPseudo();
                    $repository = $this->getDoctrine()->getRepository(User::class);
                    $userObject = $repository->findOneBy(['pseudo' => $pseudo]);
                    if (!empty($userObject)) {
                        $userPoint->setUserId($userObject);

                        $userPoint->setUserGive($user);

                        $points = $form->get('points')->getData();
                        $userPoint->setPoint($points);

                        $userPoint->setDateGive(new \DateTime('now'));

                        $this->addFlash('success', "Points give");

                        $this->em->persist($userPoint);
                        $this->em->flush();

                        return $this->redirectToRoute('adminWarzonePoints');
                    } else {
                        $this->addFlash('error', "Pseudo inconnu");
                        return $this->redirectToRoute('adminWarzonePointsGive');
                    }
                } else {
                    return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView(),
                    ]));
                }
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Points del
    public function adminWarzonePointsDel($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                    $repository = $this->getDoctrine()->getRepository(WarzoneUserPoint::class);
                    $pointsObject = $repository->findOneBy(['id' => $id]);

                    $this->em->remove($pointsObject);
                    $this->em->flush();

                    return $this->redirectToRoute('adminWarzonePoints');
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Actu
    public function adminActu(UserInterface $user, Request $request, PaginatorInterface $paginator): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Actu';

                $repository = $this->getDoctrine()->getRepository(Actu::class);
                $data = $repository->findBy([], ['datePublish' => 'DESC']);

                $actus = $paginator->paginate(
                    $data,
                    $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                    50 // Nombre de résultats par page
                );
                $count = $actus->getCurrentPageNumber();

                return new Response($this->twig->render('Admin/adminActu.html.twig', [
                'titre' => $titre,
                'actus' => $actus,
                'count' => $count,
                ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Actu add
    public function adminActuAdd(UserInterface $user, Request $request, SluggerInterface $slugger): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Add Actu';

                $form = $this->createForm(ActuType::class);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $actu = new Actu();

                    $titre = $form->get('titre')->getData();
                    $actu->setTitre($titre);

                    $texte = $form->get('texte')->getData();
                    $actu->setTexte($texte);

                    $actu->setUser($user);

                    $actu->setDatePublish(new \DateTime('now'));

                    $image = $form->get('image')->getData();

                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                    try {
                        $image->move(
                            $this->getParameter('Actu'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                    }

                    $actu->setLien($newFilename);

                    $this->addFlash('success', "Actu ajouter");

                    $this->em->persist($actu);
                    $this->em->flush();

                    return $this->redirectToRoute('adminActu');
                } else {
                    return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView(),
                    ]));
                }
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Actu del
    public function adminActuDel($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                    $repository = $this->getDoctrine()->getRepository(Actu::class);
                    $actuObject = $repository->findOneBy(['id' => $id]);

                    $this->em->remove($actuObject);
                    $this->em->flush();

                    return $this->redirectToRoute('adminActu');
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Actu edit
    public function adminActuEdit($id, UserInterface $user, Request $request, SluggerInterface $slugger): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Edit Actu';
                $repository = $this->getDoctrine()->getRepository(Actu::class);
                $actuObject = $repository->findOneBy(['id' => $id]);

                $form = $this->createForm(ActuType::class, $actuObject);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $repository = $this->getDoctrine()->getRepository(Actu::class);
                    $actu = $repository->findOneBy(['id' => $id]);

                    $titre = $form->get('titre')->getData();
                    $actu->setTitre($titre);

                    $texte = $form->get('texte')->getData();
                    $actu->setTexte($texte);

                    $image = $form->get('image')->getData();
                    if (!empty($image)) {
                        $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                        try {
                            $image->move(
                                $this->getParameter('Actu'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                        }

                        $actu->setLien($newFilename);
                    }

                    $this->addFlash('success', "Actu éditer");

                    $this->em->flush();

                    return $this->redirectToRoute('adminActu');
                } else {
                    return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView(),
                    'actuObject' => $actuObject,
                    ]));
                }
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Accueil
    public function adminAccueil(UserInterface $user, Request $request, PaginatorInterface $paginator): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Accueil';

                $repository = $this->getDoctrine()->getRepository(Accueil::class);
                $data = $repository->findBy(['slider' => '1'], ['ordre' => 'ASC']);
                $card = $repository->findBy(['slider' => '0'], ['ordre' => 'ASC']);

                $slider = $paginator->paginate(
                    $data,
                    $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                    50 // Nombre de résultats par page
                );
                $count = $slider->getCurrentPageNumber();

                return new Response($this->twig->render('Admin/adminAccueil.html.twig', [
                'titre' => $titre,
                'slider' => $slider,
                'count' => $count,
                'card' => $card,
                ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Slider add
    public function adminAccueilSliderAdd(UserInterface $user, Request $request, SluggerInterface $slugger): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Add Slider';

                $form = $this->createForm(AccueilType::class);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $slider = new Accueil();

                    $slider->setUser($user);

                    $slider->setSlider('1');

                    $ordre = $form->get('ordre')->getData();
                    $slider->setOrdre($ordre);

                    $href = $form->get('href')->getData();
                    $slider->setHref($href);

                    $image = $form->get('image')->getData();

                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                    try {
                        $image->move(
                            $this->getParameter('Accueil'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                    }

                    $slider->setLien($newFilename);

                    $this->addFlash('success', "Slider ajouter");

                    $this->em->persist($slider);
                    $this->em->flush();

                    return $this->redirectToRoute('adminAccueil');
                } else {
                    return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView(),
                    ]));
                }
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Actu del
    public function adminAccueilSliderDel($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                    $repository = $this->getDoctrine()->getRepository(Accueil::class);
                    $accueilSlider = $repository->findOneBy(['id' => $id]);

                    $this->em->remove($accueilSlider);
                    $this->em->flush();

                    return $this->redirectToRoute('adminAccueil');
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Slider edit
    public function adminAccueilSliderEdit($id, UserInterface $user, Request $request, SluggerInterface $slugger): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Edit Slider';
                $repository = $this->getDoctrine()->getRepository(Accueil::class);
                $sliderObject = $repository->findOneBy(['id' => $id]);

                $form = $this->createForm(AccueilType::class, $sliderObject);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $repository = $this->getDoctrine()->getRepository(Accueil::class);
                    $slider = $repository->findOneBy(['id' => $id]);

                    $slider->setSlider('1');

                    $href = $form->get('href')->getData();
                    $slider->setHref($href);

                    $ordre = $form->get('ordre')->getData();
                    $slider->setOrdre($ordre);

                    $image = $form->get('image')->getData();
                    if (!empty($image)) {
                        $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                        try {
                            $image->move(
                                $this->getParameter('Accueil'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                        }

                        $slider->setLien($newFilename);
                    }

                    $this->addFlash('success', "Slider modifier");

                    $this->em->flush();

                    return $this->redirectToRoute('adminAccueil');
                } else {
                    return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView(),
                    'sliderObject' => $sliderObject,
                    ]));
                }
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }


    //* Admin Card edit
    public function adminAccueilCardEdit($id, UserInterface $user, Request $request, SluggerInterface $slugger): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Edit Accueil Card';
                $repository = $this->getDoctrine()->getRepository(Accueil::class);
                $cardObject = $repository->findOneBy(['id' => $id]);

                $form = $this->createForm(AccueilType2::class, $cardObject);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $repository = $this->getDoctrine()->getRepository(Accueil::class);
                    $card = $repository->findOneBy(['id' => $id]);

                    $card->setSlider('0');

                    $ordre = $form->get('ordre')->getData();
                    $card->setOrdre($ordre);

                    $href = $form->get('href')->getData();
                    $card->setHref($href);

                    $image = $form->get('image')->getData();
                    if (!empty($image)) {
                        $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                        try {
                            $image->move(
                                $this->getParameter('Accueil'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                        }

                        $card->setLien($newFilename);
                    }

                    $this->addFlash('success', "Card editer");

                    $this->em->flush();

                    return $this->redirectToRoute('adminAccueil');
                } else {
                    return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView(),
                    'cardObject' => $cardObject,
                    ]));
                }
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois
    public function adminWarzoneTournois(UserInterface $user, Request $request, PaginatorInterface $paginator): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Warzone Tournois';

                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $data = $repository->findBy([], ['dateDebut' => 'DESC']);

                $tournois = $paginator->paginate(
                    $data,
                    $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
                    50 // Nombre de résultats par page
                );
                $count = $tournois->getCurrentPageNumber();

                return new Response($this->twig->render('Admin/adminWarzoneTournois.html.twig', [
                'titre' => $titre,
                'tournois' => $tournois,
                'count' => $count,
                ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois add
    public function adminWarzoneTournoisAdd(UserInterface $user, Request $request, SluggerInterface $slugger): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Add Warzone Tournois';

                $form = $this->createForm(WarzoneTournoisType::class);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $tournois = new WarzoneTournois();

                    $nom = $form->get('nom')->getData();
                    $tournois->setNom($nom);

                    $description = $form->get('description')->getData();
                    $tournois->setDescription($description);

                    $type = $form->get('type')->getData();
                    $tournois->setType($type);

                    $nombre = $form->get('nombre')->getData();
                    $tournois->setNombre($nombre);

                    $dateDebut = $form->get('dateDebut')->getData();
                    $tournois->setDateDebut($dateDebut);

                    $dateFin = $form->get('dateFin')->getData();
                    $tournois->setDateFin($dateFin);

                    $dateFinInscription = $form->get('dateFinInscription')->getData();
                    $tournois->setDateFinInscription($dateFinInscription);

                    $plateforme = $form->get('plateforme')->getData();
                    $tournois->setPlateforme($plateforme);

                    $recompenses = $form->get('recompenses')->getData();
                    $tournois->setRecompenses($recompenses);

                    $kdcap = $form->get('kdcap')->getData();
                    $tournois->setKdcap($kdcap);

                    $reglements = $form->get('reglements')->getData();
                    $tournois->setReglements($reglements);

                    $image = $form->get('image')->getData();

                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                    try {
                        $image->move(
                            $this->getParameter('Tournois'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                    }

                    $tournois->setLien($newFilename);

                    $imageMobile = $form->get('imageMobile')->getData();

                    $originalFilename2 = pathinfo($imageMobile->getClientOriginalName(), PATHINFO_FILENAME);

                    $safeFilename2 = $slugger->slug($originalFilename2);
                    $newFilename2 = $safeFilename2 . '-' . uniqid() . '.' . $imageMobile->guessExtension();

                    try {
                        $imageMobile->move(
                            $this->getParameter('Tournois'),
                            $newFilename2
                        );
                    } catch (FileException $e) {
                    }

                    $tournois->setLienMobile($newFilename2);

                    $this->addFlash('success', "Tournoi ajouter");

                    $this->em->persist($tournois);
                    $this->em->flush();

                    return $this->redirectToRoute('adminWarzoneTournois');
                } else {
                    return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView(),
                    ]));
                }
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois del
    public function adminWarzoneTournoisDel($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                    $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                    $tournois = $repository->findOneBy(['id' => $id]);

                    $this->em->remove($tournois);
                    $this->em->flush();

                    return $this->redirectToRoute('adminWarzoneTournois');
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois edit
    public function adminWarzoneTournoisEdit($id, UserInterface $user, Request $request, SluggerInterface $slugger): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3') {
                $titre = 'Admin - Edit Warzone Tournois';
                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournois = $repository->findOneBy(['id' => $id]);

                $form = $this->createForm(WarzoneTournoisType::class, $tournois);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                    $tournois = $repository->findOneBy(['id' => $id]);

                    $nom = $form->get('nom')->getData();
                    $tournois->setNom($nom);

                    $description = $form->get('description')->getData();
                    $tournois->setDescription($description);

                    $type = $form->get('type')->getData();
                    $tournois->setType($type);

                    $nombre = $form->get('nombre')->getData();
                    $tournois->setNombre($nombre);

                    $dateDebut = $form->get('dateDebut')->getData();
                    $tournois->setDateDebut($dateDebut);

                    $dateFin = $form->get('dateFin')->getData();
                    $tournois->setDateFin($dateFin);

                    $dateFinInscription = $form->get('dateFinInscription')->getData();
                    $tournois->setDateFinInscription($dateFinInscription);

                    $plateforme = $form->get('plateforme')->getData();
                    $tournois->setPlateforme($plateforme);

                    $recompenses = $form->get('recompenses')->getData();
                    $tournois->setRecompenses($recompenses);

                    $kdcap = $form->get('kdcap')->getData();
                    $tournois->setKdcap($kdcap);

                    $reglements = $form->get('reglements')->getData();
                    $tournois->setReglements($reglements);

                    $image = $form->get('image')->getData();
                    if (!empty($image)) {
                        $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);

                        $safeFilename = $slugger->slug($originalFilename);
                        $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();

                        try {
                            $image->move(
                                $this->getParameter('Tournois'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                        }

                        $tournois->setLien($newFilename);
                    }

                    $imageMobile = $form->get('imageMobile')->getData();
                    if (!empty($imageMobile)) {
                        $originalFilename2 = pathinfo($imageMobile->getClientOriginalName(), PATHINFO_FILENAME);

                        $safeFilename2 = $slugger->slug($originalFilename2);
                        $newFilename2 = $safeFilename2 . '-' . uniqid() . '.' . $imageMobile->guessExtension();

                        try {
                            $imageMobile->move(
                                $this->getParameter('Tournois'),
                                $newFilename2
                            );
                        } catch (FileException $e) {
                        }

                        $tournois->setLienMobile($newFilename2);
                    }

                    $this->addFlash('success', "Tournoi edit");

                    $this->em->persist($tournois);
                    $this->em->flush();

                    return $this->redirectToRoute('adminWarzoneTournois');
                } else {
                    return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView(),
                    'tournois' => $tournois,
                    ]));
                }
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Gestion Warzone Tournois
    public function adminGestionWarzoneTournois(UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $titre = 'Admin - Gestion Warzone Tournois';

                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournois = $repository->findAll();

                return new Response($this->twig->render('Admin/adminGestionWarzoneTournois.html.twig', [
                'titre' => $titre,
                'tournois' => $tournois,
                ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Gestion Warzone Tournois Manage
    public function adminGestionWarzoneTournoisManage($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $titre = 'Admin - Gestion Warzone Tournois Manage';

                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournoi = $repository->findOneBy(['id' => $id]);

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeResultats::class);
                $partie = $repository->getAllResults($id);

                $form = $this->createForm(WarzoneTournoisTopKillerType::class, $tournoi);
                $form->handleRequest($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $topKiller = $form->get('topKillerUser')->getData();
                    $killsTopKiller = $form->get('topKillerKills')->getData();

                    $tournoi->setTopKillerUser($topKiller);
                    $tournoi->setTopKillerKills($killsTopKiller);
                    $this->em->flush();
                }

                return new Response($this->twig->render('Admin/adminGestionWarzoneTournoisManage.html.twig', [
                'titre' => $titre,
                'tournoi' => $tournoi,
                'partie' => $partie,
                'form' => $form->createView()
                ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Gestion Warzone Tournois Manage Valide
    public function adminGestionWarzoneTournoisManageValide($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeResultats::class);
                $partie = $repository->findOneBy(['id' => $id]);
                $tournoi = $partie->getTournois();
                $tournoiID = $tournoi->getId();

                if ($partie->getIsValide() == '0') {
                    $partie->setIsValide('1');
                } elseif ($partie->getIsValide() == '1') {
                    $partie->setIsValide('0');
                }

                $this->em->flush();

                return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $tournoiID));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Gestion Warzone Tournois Manage Valide
    public function adminGestionWarzoneTournoisManageEdit($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $titre = 'Admin - Gestion Warzone Tournois Manage Edit';
                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeResultats::class);
                $partie = $repository->findOneBy(['id' => $id]);
                $tournoi = $partie->getTournois();
                $tournoiID = $tournoi->getId();

                if ($tournoi->getNombre() == '1') {
                    $form = $this->createForm(WarzoneTournoisResultatsSoloTypePart1::class, $partie);
                    $form->handleRequest($request);
                } elseif ($tournoi->getNombre() == '2') {
                    $form = $this->createForm(WarzoneTournoisResultatsDuoTypePart1::class, $partie);
                    $form->handleRequest($request);
                } elseif ($tournoi->getNombre() == '3') {
                    $form = $this->createForm(WarzoneTournoisResultatsTrioTypePart1::class, $partie);
                    $form->handleRequest($request);
                } elseif ($tournoi->getNombre() == '4') {
                    $form = $this->createForm(WarzoneTournoisResultatsQuadTypePart1::class, $partie);
                    $form->handleRequest($request);
                }


                if ($tournoi->getNombre() == '1') {
                    if ($form->isSubmitted() && $form->isValid()) {
                        $userKills1 = $form->get('userKills1')->getData();
                        $partie->setUserKills1($userKills1);
                        $position = $form->get('position')->getData();
                        $partie->setPosition($position);

                        if ($position == '1') {
                            $score = $userKills1 + 15;
                        } elseif ($position == '2') {
                            $score = $userKills1 + 10;
                        } elseif ($position == '3') {
                            $score = $userKills1 + 7;
                        } elseif ($position >= '4' & $position <= '7') {
                            $score = $userKills1 + 4;
                        } elseif ($position >= '8' & $position <= '10') {
                            $score = $userKills1 + 2;
                        } else {
                            $score = $userKills1;
                        }
    
                        $partie->setScore($score);
                        $this->em->flush();

                        $this->addFlash('success', 'Partie modifier');
            
                        return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $tournoiID));
                    }
                } elseif ($tournoi->getNombre() == '2') {
                    if ($form->isSubmitted() && $form->isValid()) {
                        $userKills1 = $form->get('userKills1')->getData();
                        $partie->setUserKills1($userKills1);
                        $userKills2 = $form->get('userKills2')->getData();
                        $partie->setUserKills2($userKills2);
                        $position = $form->get('position')->getData();
                        $partie->setPosition($position);

                        if ($position == '1') {
                            $score = $userKills1 + $userKills2 + 15;
                        } elseif ($position == '2') {
                            $score = $userKills1 + $userKills2 + 10;
                        } elseif ($position == '3') {
                            $score = $userKills1 + $userKills2 + 7;
                        } elseif ($position >= '4' & $position <= '7') {
                            $score = $userKills1 + $userKills2 + 4;
                        } elseif ($position >= '8' & $position <= '10') {
                            $score = $userKills1 + $userKills2 + 2;
                        } else {
                            $score = $userKills1 + $userKills2;
                        }
    
                        $partie->setScore($score);
                        $this->em->flush();

                        $this->addFlash('success', 'Partie modifier');
            
                        return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $tournoiID));
                    }
                } elseif ($tournoi->getNombre() == '3') {
                    if ($form->isSubmitted() && $form->isValid()) {
                        $userKills1 = $form->get('userKills1')->getData();
                        $partie->setUserKills1($userKills1);
                        $userKills2 = $form->get('userKills2')->getData();
                        $partie->setUserKills2($userKills2);
                        $userKills3 = $form->get('userKills3')->getData();
                        $partie->setUserKills3($userKills3);
                        $position = $form->get('position')->getData();
                        $partie->setPosition($position);

                        if ($position == '1') {
                            $score = $userKills1 + $userKills2 + $userKills3 + 15;
                        } elseif ($position == '2') {
                            $score = $userKills1 + $userKills2 + $userKills3 + 10;
                        } elseif ($position == '3') {
                            $score = $userKills1 + $userKills2 + $userKills3 + 7;
                        } elseif ($position >= '4' & $position <= '7') {
                            $score = $userKills1 + $userKills2 + $userKills3 + 4;
                        } elseif ($position >= '8' & $position <= '10') {
                            $score = $userKills1 + $userKills2 + $userKills3 + 2;
                        } else {
                            $score = $userKills1 + $userKills2 + $userKills3;
                        }
    
                        $partie->setScore($score);
                        $this->em->flush();

                        $this->addFlash('success', 'Partie modifier');
            
                        return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $tournoiID));
                    }
                } elseif ($tournoi->getNombre() == '4') {
                    if ($form->isSubmitted() && $form->isValid()) {
                        $userKills1 = $form->get('userKills1')->getData();
                        $partie->setUserKills1($userKills1);
                        $userKills2 = $form->get('userKills2')->getData();
                        $partie->setUserKills2($userKills2);
                        $userKills3 = $form->get('userKills3')->getData();
                        $partie->setUserKills3($userKills3);
                        $userKills4 = $form->get('userKills4')->getData();
                        $partie->setUserKills4($userKills4);
                        $position = $form->get('position')->getData();
                        $partie->setPosition($position);

                        if ($position == '1') {
                            $score = $userKills1 + $userKills2 + $userKills3 + $userKills4 + 15;
                        } elseif ($position == '2') {
                            $score = $userKills1 + $userKills2 + $userKills3 + $userKills4 + 10;
                        } elseif ($position == '3') {
                            $score = $userKills1 + $userKills2 + $userKills3 + $userKills4 + 7;
                        } elseif ($position >= '4' & $position <= '7') {
                            $score = $userKills1 + $userKills2 + $userKills3 + $userKills4 + 4;
                        } elseif ($position >= '8' & $position <= '10') {
                            $score = $userKills1 + $userKills2 + $userKills3 + $userKills4 + 2;
                        } else {
                            $score = $userKills1 + $userKills2 + $userKills3 + $userKills4;
                        }
    
                        $partie->setScore($score);
                        $this->em->flush();

                        $this->addFlash('success', 'Partie modifier');
            
                        return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $tournoiID));
                    }
                }

                return new Response($this->twig->render('Admin/adminGestionWarzoneTournoisManageEdit.html.twig', [
                'partie' => $partie,
                'tournois' => $tournoi,
                'titre' => $titre,
                'form' => $form->createView()
                ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois Equipe disqual
    public function adminGestionWarzoneTournoisManageDel($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeResultats::class);
                $partie = $repository->findOneBy(['id' => $id]);
                $equipe = $partie->getEquipe();
                $tournoi = $partie->getTournois();
                $tournoiID = $tournoi->getId();

                $this->em->remove($equipe);
                $this->em->flush();

                return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $tournoiID));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois Open
    public function adminGestionWarzoneTournoisManageOpen($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournoi = $repository->findOneBy(['id' => $id]);

                if ($tournoi->getIsBegin() == '0') {
                    $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);
                    $teamUnvalide = $repository->getAllUnvalide($id);

                    for ($i = 0; $i < count($teamUnvalide); ++$i) {
                        $equipeUnvalideID = $teamUnvalide[$i]['id'];

                        $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipe::class);
                        $equipeUnvalide = $repository->findOneBy(['id' => $equipeUnvalideID]);

                        $this->em->remove($equipeUnvalide);
                        $this->em->flush();
                    }

                    $tournoi->setIsBegin('1');
                } elseif ($tournoi->getIsBegin() == '1') {
                    $tournoi->setIsBegin('0');
                }

                $this->em->flush();

                return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $id));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois Close
    public function adminGestionWarzoneTournoisManageClose($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournoi = $repository->findOneBy(['id' => $id]);

                if ($tournoi->getIsClose() == '0') {
                    $tournoi->setIsClose('1');
                } elseif ($tournoi->getIsClose() == '1') {
                    $tournoi->setIsClose('0');
                }

                $this->em->flush();

                return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $id));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois Give points ALL
    public function adminGestionWarzoneTournoisManageGivePoints($id, UserInterface $user, Request $request): Response
    {

        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournoi = $repository->findOneBy(['id' => $id]);

                if ($tournoi->getIsClose() == '0') {
                    $this->addFlash('error', 'Tournoi non terminé');
                    return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $id));
                }

                if ($tournoi->getPointsIsGive() == '1') {
                    $this->addFlash('error', 'Points déjà donner');
                    return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $id));
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeResultats::class);
                $allTeamsResults = $repository->getAllTeamsResults($id);

                $allTeamsResultsTotal = $repository->getAllTeamsResultsTotal($id);

                if ($allTeamsResults != [] and $allTeamsResultsTotal != []) {
                    foreach ($allTeamsResults as $element) {
                        $result[$element['team']][] = $element;
                    }
            
                    foreach ($allTeamsResultsTotal as $element) {
                        $result2[$element['team']]['scoreGlobal'] = $element;
                    }
            
                    $result3 = array_merge_recursive($result, $result2);
            
                    usort($result3, function ($a, $b) {
                        return $b['scoreGlobal']['scoreGlobal'] <=> $a['scoreGlobal']['scoreGlobal'];
                    });
                } else {
                    $result3 = '0';
                }

                /**TEAM 1ERE POSITION */
                $teamUser1 = $result3[0][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '150';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[0][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[0][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '150';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[0][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[0][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '150';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[0][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[0][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '150';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 2EME POSITION */
                $teamUser1 = $result3[1][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '100';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[1][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[1][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '100';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[1][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[1][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '100';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[1][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[1][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '100';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 3EME POSITION */
                $teamUser1 = $result3[2][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '75';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[2][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[2][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '75';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[2][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[2][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '75';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[2][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[2][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '75';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 4EME POSITION */
                $teamUser1 = $result3[3][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '50';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[3][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[3][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[3][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[3][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[3][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[3][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 5EME POSITION */
                $teamUser1 = $result3[4][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '50';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[4][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[4][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[4][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[4][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[4][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[4][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 6EME POSITION */
                $teamUser1 = $result3[5][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '50';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[5][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[5][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[5][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[5][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[5][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[5][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 7EME POSITION */
                $teamUser1 = $result3[6][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '50';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[6][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[6][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[6][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[6][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[6][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[6][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '50';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 8EME POSITION */
                $teamUser1 = $result3[7][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '25';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[7][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[7][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[7][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[7][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[7][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[7][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 9EME POSITION */
                $teamUser1 = $result3[8][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '25';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[8][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[8][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[8][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[8][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[8][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[8][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                /**TEAM 10EME POSITION */
                $teamUser1 = $result3[9][0][0]->getUser1('user1');

                $userPoint = new WarzoneUserPoint();
                $userPoint->setUserId($teamUser1);
                $userPoint->setUserGive($user);

                $points = '25';
                $userPoint->setPoint($points);

                $userPoint->setDateGive(new \DateTime('now'));

                $this->em->persist($userPoint);
                $this->em->flush();

                if ($result3[9][0][0]->getUser2('user2') != null) {
                    $teamUser2 = $result3[9][0][0]->getUser2('user2');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser2);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[9][0][0]->getUser3('user3') != null) {
                    $teamUser3 = $result3[9][0][0]->getUser3('user3');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser3);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                if ($result3[9][0][0]->getUser4('user4') != null) {
                    $teamUser4 = $result3[9][0][0]->getUser4('user4');
                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser4);
                    $userPoint->setUserGive($user);

                    $points = '25';
                    $userPoint->setPoint($points);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();
                }
                /** FIN TEAM */

                for ($i = 0; $i < count($result3); ++$i) {
                    $scorePart1 = $result3[$i][0][0]->getUserKills1('userKills1');
                    $scorePart2 = $result3[$i][1][0]->getUserKills1('userKills1');
                    $scorePart3 = $result3[$i][2][0]->getUserKills1('userKills1');
                    $scoreTotal = $scorePart1 + $scorePart2 + $scorePart3;
                    $teamUser1 = $result3[$i][0][0]->getUser1('user1');

                    $userPoint = new WarzoneUserPoint();
                    $userPoint->setUserId($teamUser1);
                    $userPoint->setUserGive($user);

                    $userPoint->setPoint($scoreTotal);

                    $userPoint->setDateGive(new \DateTime('now'));

                    $this->em->persist($userPoint);
                    $this->em->flush();

                    if ($result3[$i][0][0]->getUser2('user2') != null) {
                        $scorePart1 = $result3[$i][0][0]->getUserKills2('userKills2');
                        $scorePart2 = $result3[$i][1][0]->getUserKills2('userKills2');
                        $scorePart3 = $result3[$i][2][0]->getUserKills2('userKills2');
                        $scoreTotal = $scorePart1 + $scorePart2 + $scorePart3;
                        $teamUser2 = $result3[$i][0][0]->getUser2('user2');

                        $userPoint = new WarzoneUserPoint();
                        $userPoint->setUserId($teamUser2);
                        $userPoint->setUserGive($user);
    
                        $userPoint->setPoint($scoreTotal);
    
                        $userPoint->setDateGive(new \DateTime('now'));
    
                        $this->em->persist($userPoint);
                        $this->em->flush();
                    }
                    if ($result3[$i][0][0]->getUser3('user3') != null) {
                        $scorePart1 = $result3[$i][0][0]->getUserKills3('userKills3');
                        $scorePart2 = $result3[$i][1][0]->getUserKills3('userKills3');
                        $scorePart3 = $result3[$i][2][0]->getUserKills3('userKills3');
                        $scoreTotal = $scorePart1 + $scorePart2 + $scorePart3;
                        $teamUser3 = $result3[$i][0][0]->getUser3('user3');

                        $userPoint = new WarzoneUserPoint();
                        $userPoint->setUserId($teamUser3);
                        $userPoint->setUserGive($user);
    
                        $userPoint->setPoint($scoreTotal);
    
                        $userPoint->setDateGive(new \DateTime('now'));
    
                        $this->em->persist($userPoint);
                        $this->em->flush();
                    }
                    if ($result3[$i][0][0]->getUser4('user4') != null) {
                        $scorePart1 = $result3[$i][0][0]->getUserKills4('userKills4');
                        $scorePart2 = $result3[$i][1][0]->getUserKills4('userKills4');
                        $scorePart3 = $result3[$i][2][0]->getUserKills4('userKills4');
                        $scoreTotal = $scorePart1 + $scorePart2 + $scorePart3;
                        $teamUser4 = $result3[$i][0][0]->getUser4('user4');

                        $userPoint = new WarzoneUserPoint();
                        $userPoint->setUserId($teamUser4);
                        $userPoint->setUserGive($user);
    
                        $userPoint->setPoint($scoreTotal);
    
                        $userPoint->setDateGive(new \DateTime('now'));
    
                        $this->em->persist($userPoint);
                        $this->em->flush();
                    }
                }

                $tournoi->setPointsIsGive('1');
                $this->em->persist($tournoi);
                $this->em->flush();
                
                return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $id));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Tournois Give palmares ALL
    public function adminGestionWarzoneTournoisManageGivePalmares($id, UserInterface $user, Request $request): Response
    {

        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournoi = $repository->findOneBy(['id' => $id]);

                if ($tournoi->getIsClose() == '0') {
                    $this->addFlash('error', 'Tournoi non terminé');
                    return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $id));
                }

                if ($tournoi->getPalmaresIsGive() == '1') {
                    $this->addFlash('error', 'Palmares déjà donner');
                    return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $id));
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeResultats::class);
                $allTeamsResults = $repository->getAllTeamsResults($id);

                $allTeamsResultsTotal = $repository->getAllTeamsResultsTotal($id);

                if ($allTeamsResults != [] and $allTeamsResultsTotal != []) {
                    foreach ($allTeamsResults as $element) {
                        $result[$element['team']][] = $element;
                    }
            
                    foreach ($allTeamsResultsTotal as $element) {
                        $result2[$element['team']]['scoreGlobal'] = $element;
                    }
            
                    $result3 = array_merge_recursive($result, $result2);
            
                    usort($result3, function ($a, $b) {
                        return $b['scoreGlobal']['scoreGlobal'] <=> $a['scoreGlobal']['scoreGlobal'];
                    });
                } else {
                    $result3 = '0';
                }
                
                for ($i = 0; $i < count($result3); ++$i) {
                    $teamUser1 = $result3[$i][0][0]->getUser1('user1');
                    $nomTeam = $result3[$i][0][0]->getEquipe('equipe');
                    $position = $i + 1;
                    $killPart1 = $result3[$i][0][0]->getUserKills1('userKills1');
                    $killPart2 = $result3[$i][1][0]->getUserKills1('userKills1');
                    $killPart3 = $result3[$i][2][0]->getUserKills1('userKills1');
                    $killsTotal = $killPart1 + $killPart2 + $killPart3;

                    $partTop1 = '0';
                    $partTop3 = '0';
                    $partTop10 = '0';
                    $partTop15 = '0';
                    $partTop20 = '0';
                    $posPart1 = $result3[$i][0][0]->getPosition('position');
                    $posPart2 = $result3[$i][1][0]->getPosition('position');
                    $posPart3 = $result3[$i][2][0]->getPosition('position');
                    if ($posPart1 == '1') {
                        ++$partTop1;
                    }
                    if ($posPart2 == '1') {
                        ++$partTop1;
                    }
                    if ($posPart3 == '1') {
                        ++$partTop1;
                    }

                    if ($posPart1 <= '3' & $posPart1 >= '2') {
                        ++$partTop3;
                    }
                    if ($posPart2 <= '3' & $posPart2 >= '2') {
                        ++$partTop3;
                    }
                    if ($posPart3 <= '3' & $posPart3 >= '2') {
                        ++$partTop3;
                    }

                    if ($posPart1 <= '10' & $posPart1 >= '4') {
                        ++$partTop10;
                    }
                    if ($posPart2 <= '10' & $posPart2 >= '4') {
                        ++$partTop10;
                    }
                    if ($posPart3 <= '10' & $posPart3 >= '4') {
                        ++$partTop10;
                    }

                    if ($posPart1 <= '15' & $posPart1 >= '11') {
                        ++$partTop15;
                    }
                    if ($posPart2 <= '15' & $posPart2 >= '11') {
                        ++$partTop15;
                    }
                    if ($posPart3 <= '15' & $posPart3 >= '11') {
                        ++$partTop15;
                    }

                    if ($posPart1 <= '20' & $posPart1 >= '16') {
                        ++$partTop20;
                    }
                    if ($posPart2 <= '20' & $posPart2 >= '16') {
                        ++$partTop20;
                    }
                    if ($posPart3 <= '20' & $posPart3 >= '16') {
                        ++$partTop20;
                    }


                    $userPalmares = new WarzoneUserPalmares();
                    $userPalmares->setUserid($teamUser1);
                    $userPalmares->setTournoisid($tournoi);
                    $userPalmares->setEquipeid($nomTeam);
                    $userPalmares->setPosition($position);
                    $userPalmares->setNombreEquipe(count($result3));
                    $userPalmares->setNombreKills($killsTotal);
                    $userPalmares->setPartTop1($partTop1);
                    $userPalmares->setPartTop3($partTop3);
                    $userPalmares->setPartTop10($partTop10);
                    $userPalmares->setPartTop15($partTop15);
                    $userPalmares->setPartTop20($partTop20);

                    $this->em->persist($userPalmares);
                    $this->em->flush();

                    if ($result3[$i][0][0]->getUser2('user2') != null) {
                        $teamUser2 = $result3[$i][0][0]->getUser2('user2');
                        $nomTeam = $result3[$i][0][0]->getEquipe('equipe');
                        $position = $i + 1;
                        $killPart1 = $result3[$i][0][0]->getUserKills2('userKills2');
                        $killPart2 = $result3[$i][1][0]->getUserKills2('userKills2');
                        $killPart3 = $result3[$i][2][0]->getUserKills2('userKills2');
                        $killsTotal = $killPart1 + $killPart2 + $killPart3;

                        $userPalmares = new WarzoneUserPalmares();
                        $userPalmares->setUserid($teamUser2);
                        $userPalmares->setTournoisid($tournoi);
                        $userPalmares->setEquipeid($nomTeam);
                        $userPalmares->setPosition($position);
                        $userPalmares->setNombreEquipe(count($result3));
                        $userPalmares->setNombreKills($killsTotal);
                        $userPalmares->setPartTop1($partTop1);
                        $userPalmares->setPartTop3($partTop3);
                        $userPalmares->setPartTop10($partTop10);
                        $userPalmares->setPartTop15($partTop15);
                        $userPalmares->setPartTop20($partTop20);

                        $this->em->persist($userPalmares);
                        $this->em->flush();
                    }
                    if ($result3[$i][0][0]->getUser3('user3') != null) {
                        $teamUser3 = $result3[$i][0][0]->getUser3('user3');
                        $nomTeam = $result3[$i][0][0]->getEquipe('equipe');
                        $position = $i + 1;
                        $killPart1 = $result3[$i][0][0]->getUserKills3('userKills3');
                        $killPart2 = $result3[$i][1][0]->getUserKills3('userKills3');
                        $killPart3 = $result3[$i][2][0]->getUserKills3('userKills3');
                        $killsTotal = $killPart1 + $killPart2 + $killPart3;

                        $userPalmares = new WarzoneUserPalmares();
                        $userPalmares->setUserid($teamUser3);
                        $userPalmares->setTournoisid($tournoi);
                        $userPalmares->setEquipeid($nomTeam);
                        $userPalmares->setPosition($position);
                        $userPalmares->setNombreEquipe(count($result3));
                        $userPalmares->setNombreKills($killsTotal);
                        $userPalmares->setPartTop1($partTop1);
                        $userPalmares->setPartTop3($partTop3);
                        $userPalmares->setPartTop10($partTop10);
                        $userPalmares->setPartTop15($partTop15);
                        $userPalmares->setPartTop20($partTop20);

                        $this->em->persist($userPalmares);
                        $this->em->flush();
                    }
                    if ($result3[$i][0][0]->getUser4('user4') != null) {
                        $teamUser4 = $result3[$i][0][0]->getUser4('user4');
                        $nomTeam = $result3[$i][0][0]->getEquipe('equipe');
                        $position = $i + 1;
                        $killPart1 = $result3[$i][0][0]->getUserKills4('userKills4');
                        $killPart2 = $result3[$i][1][0]->getUserKills4('userKills4');
                        $killPart3 = $result3[$i][2][0]->getUserKills4('userKills4');
                        $killsTotal = $killPart1 + $killPart2 + $killPart3;

                        $userPalmares = new WarzoneUserPalmares();
                        $userPalmares->setUserid($teamUser4);
                        $userPalmares->setTournoisid($tournoi);
                        $userPalmares->setEquipeid($nomTeam);
                        $userPalmares->setPosition($position);
                        $userPalmares->setNombreEquipe(count($result3));
                        $userPalmares->setNombreKills($killsTotal);
                        $userPalmares->setPartTop1($partTop1);
                        $userPalmares->setPartTop3($partTop3);
                        $userPalmares->setPartTop10($partTop10);
                        $userPalmares->setPartTop15($partTop15);
                        $userPalmares->setPartTop20($partTop20);

                        $this->em->persist($userPalmares);
                        $this->em->flush();
                    }
                }
                $tournoi->setPalmaresIsGive('1');
                $this->em->persist($tournoi);
                $this->em->flush();
                
                return $this->redirectToRoute('adminGestionWarzoneTournoisManage', array('id' => $id));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Gestion Warzone Teams Manage
    public function adminGestionWarzoneTeamsList($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $titre = 'Admin - Gestion Warzone Teams Lists';

                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournoi = $repository->findOneBy(['id' => $id]);

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);
                $teams = $repository->getWarzoneTournoisEquipeAdmin($id);
                
                foreach ($teams as $val) {
                    if (array_key_exists('nom', $val)) {
                        $result[$val['nom']][] = $val;
                    } else {
                        $result[""][] = $val;
                    }
                }
        
                if ($teams == []) {
                    $result = "0";
                }

                return new Response($this->twig->render('Admin/adminGestionWarzoneTeams.html.twig', [
                'titre' => $titre,
                'tournoi' => $tournoi,
                'teams' => $result,
                ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Teams disqual
    public function adminGestionWarzoneTeamsDel($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipe::class);
                $team = $repository->findOneBy(['id' => $id]);
                $tournoi = $team->getTournois();
                $tournoiID = $tournoi->getId();

                $this->em->remove($team);
                $this->em->flush();

                return $this->redirectToRoute('adminGestionWarzoneTeamsList', array('id' => $tournoiID));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Admin Teams Add
    public function adminGestionWarzoneTeamsAdd($id, UserInterface $user, Request $request): Response
    {
        if (isset($user)) {
            $rank = $user->getPerm();
        
            if ($rank->getId() == '3' or $rank->getId() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
                $tournois = $repository->findOneBy(['id' => $id]);
                
                if ($tournois->getNombre() == '1') {
                    $form = $this->createForm(WarzoneTournoisAdminMkSoloType::class, $user);
                    $form->handleRequest($request);
                } elseif ($tournois->getNombre() == '2') {
                    $form = $this->createForm(WarzoneTournoisAdminMkDuoType::class, $user);
                    $form->handleRequest($request);
                } elseif ($tournois->getNombre() == '3') {
                    $form = $this->createForm(WarzoneTournoisAdminMkTrioType::class, $user);
                    $form->handleRequest($request);
                } elseif ($tournois->getNombre() == '4') {
                    $form = $this->createForm(WarzoneTournoisAdminMkQuadType::class, $user);
                    $form->handleRequest($request);
                }

                if ($form->isSubmitted() && $form->isValid()) {
                    $team = $form->get('team')->getData();
                    if ($tournois->getNombre() == '1') {
                        $memberTeam1 = $form->get('pseudo1')->getData();

                        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
                        $palmaresKillsUser1 = $repository->findBy(['user_id' => $memberTeam1], ['tournois_id' => 'DESC']);

                        $killsTournoisUser1 = 0;
                        if (isset($palmaresKillsUser1[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser1); ++$i) {
                                    $killsTournoisUser1 = $killsTournoisUser1 + $palmaresKillsUser1[$i]->getNombreKills();
                            }
                            $moyKillsUser1 = $killsTournoisUser1 / count($palmaresKillsUser1);
                        } else {
                            $moyKillsUser1 = 0;
                        }

                        $elo = (1000 * $user->getWarzoneKdratio()) + (0.5 * $user->getWarzoneGamesPlayed()) + (50 * $moyKillsUser1);
                    } elseif ($tournois->getNombre() == '2') {
                        $memberTeam1 = $form->get('pseudo1')->getData();
                        $memberTeam2 = $form->get('pseudo2')->getData();

                        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
                        $palmaresKillsUser1 = $repository->findBy(['user_id' => $memberTeam1], ['tournois_id' => 'DESC']);
                        $palmaresKillsUser2 = $repository->findBy(['user_id' => $memberTeam2], ['tournois_id' => 'DESC']);

                        $killsTournoisUser1 = 0;
                        if (isset($palmaresKillsUser1[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser1); ++$i) {
                                    $killsTournoisUser1 = $killsTournoisUser1 + $palmaresKillsUser1[$i]->getNombreKills();
                            }
                            $moyKillsUser1 = $killsTournoisUser1 / count($palmaresKillsUser1);
                        } else {
                            $moyKillsUser1 = 0;
                        }

                        $killsTournoisUser2 = 0;
                        if (isset($palmaresKillsUser2[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser2); ++$i) {
                                    $killsTournoisUser2 = $killsTournoisUser2 + $palmaresKillsUser2[$i]->getNombreKills();
                            }
                            $moyKillsUser2 = $killsTournoisUser2 / count($palmaresKillsUser2);
                        } else {
                            $moyKillsUser2 = 0;
                        }

                        $elo = (1000 * ($user->getWarzoneKdratio() + $memberTeam2->getWarzoneKdratio())) + (0.5 * ($user->getWarzoneGamesPlayed() + $memberTeam2->getWarzoneGamesPlayed())) + (50 * ($moyKillsUser1 + $moyKillsUser2));
                    } elseif ($tournois->getNombre() == '3') {
                        $memberTeam1 = $form->get('pseudo1')->getData();
                        $memberTeam2 = $form->get('pseudo2')->getData();
                        $memberTeam3 = $form->get('pseudo3')->getData();

                        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
                        $palmaresKillsUser1 = $repository->findBy(['user_id' => $memberTeam1], ['tournois_id' => 'DESC']);
                        $palmaresKillsUser2 = $repository->findBy(['user_id' => $memberTeam2], ['tournois_id' => 'DESC']);
                        $palmaresKillsUser3 = $repository->findBy(['user_id' => $memberTeam3], ['tournois_id' => 'DESC']);

                        $killsTournoisUser1 = 0;
                        if (isset($palmaresKillsUser1[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser1); ++$i) {
                                    $killsTournoisUser1 = $killsTournoisUser1 + $palmaresKillsUser1[$i]->getNombreKills();
                            }
                            $moyKillsUser1 = $killsTournoisUser1 / count($palmaresKillsUser1);
                        } else {
                            $moyKillsUser1 = 0;
                        }

                        $killsTournoisUser2 = 0;
                        if (isset($palmaresKillsUser2[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser2); ++$i) {
                                    $killsTournoisUser2 = $killsTournoisUser2 + $palmaresKillsUser2[$i]->getNombreKills();
                            }
                            $moyKillsUser2 = $killsTournoisUser2 / count($palmaresKillsUser2);
                        } else {
                            $moyKillsUser2 = 0;
                        }

                        $killsTournoisUser3 = 0;
                        if (isset($palmaresKillsUser3[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser3); ++$i) {
                                    $killsTournoisUser3 = $killsTournoisUser3 + $palmaresKillsUser3[$i]->getNombreKills();
                            }
                            $moyKillsUser3 = $killsTournoisUser3 / count($palmaresKillsUser3);
                        } else {
                            $moyKillsUser3 = 0;
                        }

                        $elo = (1000 * ($user->getWarzoneKdratio() + $memberTeam2->getWarzoneKdratio() + $memberTeam3->getWarzoneKdratio())) + (0.5 * ($user->getWarzoneGamesPlayed() + $memberTeam2->getWarzoneGamesPlayed() + $memberTeam3->getWarzoneGamesPlayed())) + (50 * ($moyKillsUser1 + $moyKillsUser2 + $moyKillsUser3));
                    } elseif ($tournois->getNombre() == '4') {
                        $memberTeam1 = $form->get('pseudo1')->getData();
                        $memberTeam2 = $form->get('pseudo2')->getData();
                        $memberTeam3 = $form->get('pseudo3')->getData();
                        $memberTeam4 = $form->get('pseudo4')->getData();

                        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
                        $palmaresKillsUser1 = $repository->findBy(['user_id' => $memberTeam1], ['tournois_id' => 'DESC']);
                        $palmaresKillsUser2 = $repository->findBy(['user_id' => $memberTeam2], ['tournois_id' => 'DESC']);
                        $palmaresKillsUser3 = $repository->findBy(['user_id' => $memberTeam3], ['tournois_id' => 'DESC']);
                        $palmaresKillsUser4 = $repository->findBy(['user_id' => $memberTeam4], ['tournois_id' => 'DESC']);

                        $killsTournoisUser1 = 0;
                        if (isset($palmaresKillsUser1[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser1); ++$i) {
                                    $killsTournoisUser1 = $killsTournoisUser1 + $palmaresKillsUser1[$i]->getNombreKills();
                            }
                            $moyKillsUser1 = $killsTournoisUser1 / count($palmaresKillsUser1);
                        } else {
                            $moyKillsUser1 = 0;
                        }

                        $killsTournoisUser2 = 0;
                        if (isset($palmaresKillsUser2[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser2); ++$i) {
                                    $killsTournoisUser2 = $killsTournoisUser2 + $palmaresKillsUser2[$i]->getNombreKills();
                            }
                            $moyKillsUser2 = $killsTournoisUser2 / count($palmaresKillsUser2);
                        } else {
                            $moyKillsUser2 = 0;
                        }

                        $killsTournoisUser3 = 0;
                        if (isset($palmaresKillsUser3[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser3); ++$i) {
                                    $killsTournoisUser3 = $killsTournoisUser3 + $palmaresKillsUser3[$i]->getNombreKills();
                            }
                            $moyKillsUser3 = $killsTournoisUser3 / count($palmaresKillsUser3);
                        } else {
                            $moyKillsUser3 = 0;
                        }

                        $killsTournoisUser4 = 0;
                        if (isset($palmaresKillsUser4[0])) {
                            for ($i = 0; $i < count($palmaresKillsUser4); ++$i) {
                                    $killsTournoisUser4 = $killsTournoisUser4 + $palmaresKillsUser4[$i]->getNombreKills();
                            }
                            $moyKillsUser4 = $killsTournoisUser4 / count($palmaresKillsUser4);
                        } else {
                            $moyKillsUser4 = 0;
                        }

                        $elo = (1000 * ($user->getWarzoneKdratio() + $memberTeam2->getWarzoneKdratio() + $memberTeam3->getWarzoneKdratio() + $memberTeam4->getWarzoneKdratio())) + (0.5 * ($user->getWarzoneGamesPlayed() + $memberTeam2->getWarzoneGamesPlayed() + $memberTeam3->getWarzoneGamesPlayed() + $memberTeam4->getWarzoneGamesPlayed())) + (50 * ($moyKillsUser1 + $moyKillsUser2 + $moyKillsUser3 + $moyKillsUser4));
                    }

                    $addTeam = new WarzoneTournoisEquipe();
        
                    $addTeam->setNom($team);
                    $addTeam->setTournois($tournois);
                    $addTeam->setElo($elo);
        
                    $this->em->persist($addTeam);
                    $this->em->flush();
                    
                    if ($tournois->getNombre() == '1') {
                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam1);
                        $addTeamMember->setLead('1');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();
                    } elseif ($tournois->getNombre() == '2') {
                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam1);
                        $addTeamMember->setLead('1');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();

                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam2);
                        $addTeamMember->setLead('0');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();
                    } elseif ($tournois->getNombre() == '3') {
                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam1);
                        $addTeamMember->setLead('1');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();

                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam2);
                        $addTeamMember->setLead('0');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();

                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam3);
                        $addTeamMember->setLead('0');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();
                    } elseif ($tournois->getNombre() == '4') {
                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam1);
                        $addTeamMember->setLead('1');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();

                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam2);
                        $addTeamMember->setLead('0');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();

                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam3);
                        $addTeamMember->setLead('0');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();

                        $addTeamMember = new WarzoneTournoisEquipeList();
                        $addTeamMember->setEquipe($addTeam);
                        $addTeamMember->setUser($memberTeam4);
                        $addTeamMember->setLead('0');
                        $addTeamMember->setIsValide('1');
        
                        $this->em->persist($addTeamMember);
                        $this->em->flush();
                    }

                    if ($tournois->getNombre() == '1') {
                        $addResultPart1 = new WarzoneTournoisEquipeResultats();
                        $addResultPart1->setEquipe($addTeam);
                        $addResultPart1->setTournois($tournois);
                        $addResultPart1->setPartie('1');
                        $addResultPart1->setUser1($memberTeam1);
                        $addResultPart1->setUserKills1('0');
                        $addResultPart1->setPosition('0');
                        $addResultPart1->setScore('0');
                        $this->em->persist($addResultPart1);
                        $this->em->flush();
        
                        $addResultPart2 = new WarzoneTournoisEquipeResultats();
                        $addResultPart2->setEquipe($addTeam);
                        $addResultPart2->setTournois($tournois);
                        $addResultPart2->setPartie('2');
                        $addResultPart2->setUser1($memberTeam1);
                        $addResultPart2->setUserKills1('0');
                        $addResultPart2->setPosition('0');
                        $addResultPart2->setScore('0');
                        $this->em->persist($addResultPart2);
                        $this->em->flush();
        
                        $addResultPart3 = new WarzoneTournoisEquipeResultats();
                        $addResultPart3->setEquipe($addTeam);
                        $addResultPart3->setTournois($tournois);
                        $addResultPart3->setPartie('3');
                        $addResultPart3->setUser1($memberTeam1);
                        $addResultPart3->setUserKills1('0');
                        $addResultPart3->setPosition('0');
                        $addResultPart3->setScore('0');
                        $this->em->persist($addResultPart3);
                        $this->em->flush();
                    } elseif ($tournois->getNombre() == '2') {
                        $addResultPart1 = new WarzoneTournoisEquipeResultats();
                        $addResultPart1->setEquipe($addTeam);
                        $addResultPart1->setTournois($tournois);
                        $addResultPart1->setPartie('1');
                        $addResultPart1->setUser1($memberTeam1);
                        $addResultPart1->setUserKills1('0');
                        $addResultPart1->setUser2($memberTeam2);
                        $addResultPart1->setUserKills2('0');
                        $addResultPart1->setPosition('0');
                        $addResultPart1->setScore('0');
                        $this->em->persist($addResultPart1);
                        $this->em->flush();
        
                        $addResultPart2 = new WarzoneTournoisEquipeResultats();
                        $addResultPart2->setEquipe($addTeam);
                        $addResultPart2->setTournois($tournois);
                        $addResultPart2->setPartie('2');
                        $addResultPart2->setUser1($memberTeam1);
                        $addResultPart2->setUserKills1('0');
                        $addResultPart2->setUser2($memberTeam2);
                        $addResultPart2->setUserKills2('0');
                        $addResultPart2->setPosition('0');
                        $addResultPart2->setScore('0');
                        $this->em->persist($addResultPart2);
                        $this->em->flush();
        
                        $addResultPart3 = new WarzoneTournoisEquipeResultats();
                        $addResultPart3->setEquipe($addTeam);
                        $addResultPart3->setTournois($tournois);
                        $addResultPart3->setPartie('3');
                        $addResultPart3->setUser1($memberTeam1);
                        $addResultPart3->setUserKills1('0');
                        $addResultPart3->setUser2($memberTeam2);
                        $addResultPart3->setUserKills2('0');
                        $addResultPart3->setPosition('0');
                        $addResultPart3->setScore('0');
                        $this->em->persist($addResultPart3);
                        $this->em->flush();
                    } elseif ($tournois->getNombre() == '3') {
                        $addResultPart1 = new WarzoneTournoisEquipeResultats();
                        $addResultPart1->setEquipe($addTeam);
                        $addResultPart1->setTournois($tournois);
                        $addResultPart1->setPartie('1');
                        $addResultPart1->setUser1($memberTeam1);
                        $addResultPart1->setUserKills1('0');
                        $addResultPart1->setUser2($memberTeam2);
                        $addResultPart1->setUserKills2('0');
                        $addResultPart1->setUser3($memberTeam3);
                        $addResultPart1->setUserKills3('0');
                        $addResultPart1->setPosition('0');
                        $addResultPart1->setScore('0');
                        $this->em->persist($addResultPart1);
                        $this->em->flush();
        
                        $addResultPart2 = new WarzoneTournoisEquipeResultats();
                        $addResultPart2->setEquipe($addTeam);
                        $addResultPart2->setTournois($tournois);
                        $addResultPart2->setPartie('2');
                        $addResultPart2->setUser1($memberTeam1);
                        $addResultPart2->setUserKills1('0');
                        $addResultPart2->setUser2($memberTeam2);
                        $addResultPart2->setUserKills2('0');
                        $addResultPart2->setUser3($memberTeam3);
                        $addResultPart2->setUserKills3('0');
                        $addResultPart2->setPosition('0');
                        $addResultPart2->setScore('0');
                        $this->em->persist($addResultPart2);
                        $this->em->flush();
        
                        $addResultPart3 = new WarzoneTournoisEquipeResultats();
                        $addResultPart3->setEquipe($addTeam);
                        $addResultPart3->setTournois($tournois);
                        $addResultPart3->setPartie('3');
                        $addResultPart3->setUser1($memberTeam1);
                        $addResultPart3->setUserKills1('0');
                        $addResultPart3->setUser2($memberTeam2);
                        $addResultPart3->setUserKills2('0');
                        $addResultPart3->setUser3($memberTeam3);
                        $addResultPart3->setUserKills3('0');
                        $addResultPart3->setPosition('0');
                        $addResultPart3->setScore('0');
                        $this->em->persist($addResultPart3);
                        $this->em->flush();
                    } elseif ($tournois->getNombre() == '4') {
                        $addResultPart1 = new WarzoneTournoisEquipeResultats();
                        $addResultPart1->setEquipe($addTeam);
                        $addResultPart1->setTournois($tournois);
                        $addResultPart1->setPartie('1');
                        $addResultPart1->setUser1($memberTeam1);
                        $addResultPart1->setUserKills1('0');
                        $addResultPart1->setUser2($memberTeam2);
                        $addResultPart1->setUserKills2('0');
                        $addResultPart1->setUser3($memberTeam3);
                        $addResultPart1->setUserKills3('0');
                        $addResultPart1->setUser4($memberTeam4);
                        $addResultPart1->setUserKills4('0');
                        $addResultPart1->setPosition('0');
                        $addResultPart1->setScore('0');
                        $this->em->persist($addResultPart1);
                        $this->em->flush();
        
                        $addResultPart2 = new WarzoneTournoisEquipeResultats();
                        $addResultPart2->setEquipe($addTeam);
                        $addResultPart2->setTournois($tournois);
                        $addResultPart2->setPartie('2');
                        $addResultPart2->setUser1($memberTeam1);
                        $addResultPart2->setUserKills1('0');
                        $addResultPart2->setUser2($memberTeam2);
                        $addResultPart2->setUserKills2('0');
                        $addResultPart2->setUser3($memberTeam3);
                        $addResultPart2->setUserKills3('0');
                        $addResultPart2->setUser4($memberTeam4);
                        $addResultPart2->setUserKills4('0');
                        $addResultPart2->setPosition('0');
                        $addResultPart2->setScore('0');
                        $this->em->persist($addResultPart2);
                        $this->em->flush();
        
                        $addResultPart3 = new WarzoneTournoisEquipeResultats();
                        $addResultPart3->setEquipe($addTeam);
                        $addResultPart3->setTournois($tournois);
                        $addResultPart3->setPartie('3');
                        $addResultPart3->setUser1($memberTeam1);
                        $addResultPart3->setUserKills1('0');
                        $addResultPart3->setUser2($memberTeam2);
                        $addResultPart3->setUserKills2('0');
                        $addResultPart3->setUser3($memberTeam3);
                        $addResultPart3->setUserKills3('0');
                        $addResultPart3->setUser4($memberTeam4);
                        $addResultPart3->setUserKills4('0');
                        $addResultPart3->setPosition('0');
                        $addResultPart3->setScore('0');
                        $this->em->persist($addResultPart3);
                        $this->em->flush();
                    }
                    $this->addFlash('success', "Team inscrite !");
                    return $this->redirectToRoute('adminGestionWarzoneTeamsList', ['id' => $id]);
                }
                $titre = 'Add Team';
                return new Response($this->twig->render('Admin/formTemplate.html.twig', [
                    'titre' => $titre,
                    'form' => $form->createView()
                    ]));
            } else {
                return $this->redirectToRoute('index');
            }
        } else {
            return $this->redirectToRoute('index');
        }
    }
}
