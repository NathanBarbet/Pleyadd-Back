<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;
use App\Entity\User;
use App\Entity\WarzoneTournois;
use App\Entity\WarzoneTournoisEquipe;
use App\Entity\WarzoneTournoisEquipeList;
use App\Entity\WarzoneTournoisTokenEquipe;
use App\Entity\WarzoneTournoisEquipeResultats;
use App\Entity\WarzoneUserPalmares;
use Doctrine\ORM\EntityManagerInterface;
use Hidehalo\Nanoid\Client;
use Hidehalo\Nanoid\GeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Services\EmailService;
use App\Form\WarzoneTournoisMkSoloType;
use App\Form\WarzoneTournoisMkDuoType;
use App\Form\WarzoneTournoisMkTrioType;
use App\Form\WarzoneTournoisMkQuadType;
use App\Form\WarzoneTournoisResultatsSoloTypePart1;
use App\Form\WarzoneTournoisResultatsSoloTypePart2;
use App\Form\WarzoneTournoisResultatsSoloTypePart3;
use App\Form\WarzoneTournoisResultatsDuoTypePart1;
use App\Form\WarzoneTournoisResultatsDuoTypePart2;
use App\Form\WarzoneTournoisResultatsDuoTypePart3;
use App\Form\WarzoneTournoisResultatsTrioTypePart1;
use App\Form\WarzoneTournoisResultatsTrioTypePart2;
use App\Form\WarzoneTournoisResultatsTrioTypePart3;
use App\Form\WarzoneTournoisResultatsQuadTypePart1;
use App\Form\WarzoneTournoisResultatsQuadTypePart2;
use App\Form\WarzoneTournoisResultatsQuadTypePart3;

class WarzoneTournoisController extends AbstractController
{
    
    private $twig;
    private $em;
    private $sendEmail;

    public function __construct(Environment $twig, EmailService $sendEmail, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->twig = $twig;
        $this->sendEmail = $sendEmail;
    }


    //* Display One Tournoi
    public function tournoisWarzoneDetail($id, ?UserInterface $user, Request $request, MailerInterface $mailer)
    {
        $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
        $tournois = $repository->findOneBy(['id' => $id]);

        if ($tournois->getNombre() == '1') {
            $form = $this->createForm(WarzoneTournoisMkSoloType::class, $user);
            $form->handleRequest($request);
        } elseif ($tournois->getNombre() == '2') {
            $form = $this->createForm(WarzoneTournoisMkDuoType::class, $user);
            $form->handleRequest($request);
        } elseif ($tournois->getNombre() == '3') {
            $form = $this->createForm(WarzoneTournoisMkTrioType::class, $user);
            $form->handleRequest($request);
        } elseif ($tournois->getNombre() == '4') {
            $form = $this->createForm(WarzoneTournoisMkQuadType::class, $user);
            $form->handleRequest($request);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $pseudo = $user->getPseudo();

            $repository = $this->getDoctrine()->getRepository(WarzoneTournoisTokenEquipe::class);
            $countTokenSend = $repository->getCountSendToken($pseudo, $id);
            $count = array_column($countTokenSend, "1");

            if ($count[0] > '9') {
                $this->addFlash('error', "Tu as fait trop de tentative de création de team pour ce tournoi, contacte un admin sur le discord");
                return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
            } elseif ($tournois->getNombre() == '1') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipe::class);

                $team = $form->get('team')->getData();
                $verifNomTeam = $repository->findOneBy(['nom' => $team, 'tournois' => $tournois]);

                if (!empty($verifNomTeam)) {
                    $this->addFlash('error', "Nom de team déjà utiliser pour ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $pseudo = $user->getPseudo();
                $verifMemberTeam = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam)) {
                    if ($verifMemberTeam[0]['isValide'] == '1') {
                        $this->addFlash('error', "Tu participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($user->getTrn() == null) {
                    $this->addFlash('error', "Tu doit renseigner ton ID warzone dans ton profil pour t'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Ton KDRatio est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneGamesPlayed() == '0' or $user->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Ton compte Activision ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneGamesPlayed() < '100' & $user->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Ton nombre de parti est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
                $palmaresKillsUser1 = $repository->findBy(['user_id' => $user], ['tournois_id' => 'DESC']);
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

                $addTeam = new WarzoneTournoisEquipe();

                $addTeam->setNom($team);
                $addTeam->setTournois($tournois);
                $addTeam->setElo($elo);

                $this->em->persist($addTeam);
                $this->em->flush();

                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($user);
                $addTeamMember->setLead('1');
                $addTeamMember->setIsValide('1');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                $addResultPart1 = new WarzoneTournoisEquipeResultats();
                $addResultPart1->setEquipe($addTeam);
                $addResultPart1->setTournois($tournois);
                $addResultPart1->setPartie('1');
                $addResultPart1->setUser1($user);
                $addResultPart1->setUserKills1('0');

                $addResultPart1->setPosition('0');
                $addResultPart1->setScore('0');
                $this->em->persist($addResultPart1);
                $this->em->flush();

                $addResultPart2 = new WarzoneTournoisEquipeResultats();
                $addResultPart2->setEquipe($addTeam);
                $addResultPart2->setTournois($tournois);
                $addResultPart2->setPartie('2');
                $addResultPart2->setUser1($user);
                $addResultPart2->setUserKills1('0');

                $addResultPart2->setPosition('0');
                $addResultPart2->setScore('0');
                $this->em->persist($addResultPart2);
                $this->em->flush();

                $addResultPart3 = new WarzoneTournoisEquipeResultats();
                $addResultPart3->setEquipe($addTeam);
                $addResultPart3->setTournois($tournois);
                $addResultPart3->setPartie('3');
                $addResultPart3->setUser1($user);
                $addResultPart3->setUserKills1('0');

                $addResultPart3->setPosition('0');
                $addResultPart3->setScore('0');
                $this->em->persist($addResultPart3);
                $this->em->flush();

                $this->addFlash('success', "Ton équipe est inscrite !");
                return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
            } elseif ($tournois->getNombre() == '2') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipe::class);

                $team = $form->get('team')->getData();
                $verifNomTeam = $repository->findOneBy(['nom' => $team, 'tournois' => $tournois]);

                if (!empty($verifNomTeam)) {
                    $this->addFlash('error', "Nom de team déjà utiliser pour ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $pseudo = $user->getPseudo();
                $verifMemberTeam = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam)) {
                    if ($verifMemberTeam[0]['isValide'] == '1') {
                        $this->addFlash('error', "Tu participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($user->getTrn() == null) {
                    $this->addFlash('error', "Tu doit renseigner ton ID warzone dans ton profil pour t'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Ton KDRatio est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneGamesPlayed() == '0' or $user->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Ton compte Activision ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneGamesPlayed() < '100' & $user->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Ton nombre de parti est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                
                $memberTeam2 = $form->get('pseudo')->getData();
                $repository = $this->getDoctrine()->getRepository(User::class);
                $pseudo = $memberTeam2->getPseudo();

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $verifMemberTeam2 = $repository->getUserInWarzoneTournois($pseudo, $id);
                if (!empty($verifMemberTeam2)) {
                    if ($verifMemberTeam2[0]['isValide'] == '1') {
                        $this->addFlash('error', "$pseudo participe déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($memberTeam2->getTrn() == null) {
                    $this->addFlash('error', "$pseudo doit renseigner son ID warzone dans son profil pour s'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Le KDRatio de $pseudo est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneGamesPlayed() == '0' or $memberTeam2->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Le compte Activision de $pseudo ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneGamesPlayed() < '100' & $memberTeam2->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Le nombre de parti de $pseudo est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
                $palmaresKillsUser1 = $repository->findBy(['user_id' => $user], ['tournois_id' => 'DESC']);
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


                $addTeam = new WarzoneTournoisEquipe();

                $addTeam->setNom($team);
                $addTeam->setTournois($tournois);
                $addTeam->setElo($elo);

                $this->em->persist($addTeam);
                $this->em->flush();


                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($user);
                $addTeamMember->setLead('1');
                $addTeamMember->setIsValide('1');

                $this->em->persist($addTeamMember);
                $this->em->flush();


                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($memberTeam2);
                $addTeamMember->setLead('0');
                $addTeamMember->setIsValide('0');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                // vérification Token
                $token = new WarzoneTournoisTokenEquipe();

                $nanoId = new Client();
                $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
                $token->setToken($chain);
                $token->setDateCreate(new \DateTime('now'));
                $token->setIsUse('0');
                $token->setUserSend($user);
                $token->setUserReceive($memberTeam2);
                $token->setEquipeList($addTeamMember);
                $token->setEquipe($addTeam);
                $token->setTournois($tournois);

                $this->em->persist($token);
                $this->em->flush();

                // Envoie Email de validation
                $subject = 'Invitation Tournoi ' . $tournois->getNom();
                $content = $this->twig->render('Tournois/warzone_token_send_email.html.twig', [
                'token' => $chain,
                'pseudo' => $memberTeam2,
                'user' => $user,
                'tournois' => $tournois,
                'equipe' => $addTeam,
                ]);
                $this->sendEmail->sendEmail($mailer, $memberTeam2->getUsername(), $subject, $content);
                //

                $addResultPart1 = new WarzoneTournoisEquipeResultats();
                $addResultPart1->setEquipe($addTeam);
                $addResultPart1->setTournois($tournois);
                $addResultPart1->setPartie('1');
                $addResultPart1->setUser1($user);
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
                $addResultPart2->setUser1($user);
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
                $addResultPart3->setUser1($user);
                $addResultPart3->setUserKills1('0');
                $addResultPart3->setUser2($memberTeam2);
                $addResultPart3->setUserKills2('0');

                $addResultPart3->setPosition('0');
                $addResultPart3->setScore('0');
                $this->em->persist($addResultPart3);
                $this->em->flush();

                $this->addFlash('success', "Ton équipe est inscrite ! Mais il faut encore que ton coéquipier valide l'email qui vient de lui être envoyer");
                return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
            } elseif ($tournois->getNombre() == '3') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipe::class);

                $team = $form->get('team')->getData();
                $verifNomTeam = $repository->findOneBy(['nom' => $team, 'tournois' => $tournois]);

                if (!empty($verifNomTeam)) {
                    $this->addFlash('error', "Nom de team déjà utiliser pour ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $pseudo = $user->getPseudo();
                $verifMemberTeam = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam)) {
                    if ($verifMemberTeam[0]['isValide'] == '1') {
                        $this->addFlash('error', "Tu participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($user->getTrn() == null) {
                    $this->addFlash('error', "Tu doit renseigner ton ID warzone dans ton profil pour t'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Ton KDRatio est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneGamesPlayed() == '0' or $user->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Ton compte Activision ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneGamesPlayed() < '100' & $user->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Ton nombre de parti est insufisiant pour (et ratio trop élevé) participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $memberTeam2 = $form->get('pseudo')->getData();
                $repository = $this->getDoctrine()->getRepository(User::class);
                $pseudo = $memberTeam2->getPseudo();

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $verifMemberTeam2 = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam2)) {
                    if ($verifMemberTeam2[0]['isValide'] == '1') {
                        $this->addFlash('error', "$pseudo participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($memberTeam2->getTrn() == null) {
                    $this->addFlash('error', "$pseudo doit renseigner son ID warzone dans son profil pour s'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Le KDRatio de $pseudo est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneGamesPlayed() == '0' or $memberTeam2->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Le compte Activision de $pseudo ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneGamesPlayed() < '100' & $memberTeam2->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Le nombre de parti de $pseudo est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $memberTeam3 = $form->get('pseudo2')->getData();
                $repository = $this->getDoctrine()->getRepository(User::class);
                $pseudo = $memberTeam3->getPseudo();

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $verifMemberTeam3 = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam3)) {
                    if ($verifMemberTeam3[0]['isValide'] == '1') {
                        $this->addFlash('error', "$pseudo participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($memberTeam3->getTrn() == null) {
                    $this->addFlash('error', "$pseudo doit renseigner son ID warzone dans son profil pour s'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam3->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Le KDRatio de $pseudo est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam3->getWarzoneGamesPlayed() == '0' or $memberTeam3->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Le compte Activision de $pseudo ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam3->getWarzoneGamesPlayed() < '100' & $memberTeam3->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Le nombre de parti de $pseudo est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
                $palmaresKillsUser1 = $repository->findBy(['user_id' => $user], ['tournois_id' => 'DESC']);
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


                $addTeam = new WarzoneTournoisEquipe();

                $addTeam->setNom($team);
                $addTeam->setTournois($tournois);
                $addTeam->setElo($elo);

                $this->em->persist($addTeam);
                $this->em->flush();


                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($user);
                $addTeamMember->setLead('1');
                $addTeamMember->setIsValide('1');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($memberTeam2);
                $addTeamMember->setLead('0');
                $addTeamMember->setIsValide('0');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                // vérification Token
                $token = new WarzoneTournoisTokenEquipe();

                $nanoId = new Client();
                $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
                $token->setToken($chain);
                $token->setDateCreate(new \DateTime('now'));
                $token->setIsUse('0');
                $token->setUserSend($user);
                $token->setUserReceive($memberTeam2);
                $token->setEquipeList($addTeamMember);
                $token->setEquipe($addTeam);
                $token->setTournois($tournois);

                $this->em->persist($token);
                $this->em->flush();

                // Envoie Email de validation
                $subject = 'Invitation Tournoi ' . $tournois->getNom();
                $content = $this->twig->render('Tournois/warzone_token_send_email.html.twig', [
                'token' => $chain,
                'pseudo' => $memberTeam2,
                'user' => $user,
                'tournois' => $tournois,
                'equipe' => $addTeam,
                ]);
                $this->sendEmail->sendEmail($mailer, $memberTeam2->getUsername(), $subject, $content);
                //

                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($memberTeam3);
                $addTeamMember->setLead('0');
                $addTeamMember->setIsValide('0');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                // vérification Token
                $token = new WarzoneTournoisTokenEquipe();

                $nanoId = new Client();
                $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
                $token->setToken($chain);
                $token->setDateCreate(new \DateTime('now'));
                $token->setIsUse('0');
                $token->setUserSend($user);
                $token->setUserReceive($memberTeam3);
                $token->setEquipeList($addTeamMember);
                $token->setEquipe($addTeam);
                $token->setTournois($tournois);

                $this->em->persist($token);
                $this->em->flush();

                // Envoie Email de validation
                $subject = 'Invitation Tournoi ' . $tournois->getNom();
                $content = $this->twig->render('Tournois/warzone_token_send_email.html.twig', [
                'token' => $chain,
                'pseudo' => $memberTeam3,
                'user' => $user,
                'tournois' => $tournois,
                'equipe' => $addTeam,
                ]);
                $this->sendEmail->sendEmail($mailer, $memberTeam3->getUsername(), $subject, $content);
                //

                $addResultPart1 = new WarzoneTournoisEquipeResultats();
                $addResultPart1->setEquipe($addTeam);
                $addResultPart1->setTournois($tournois);
                $addResultPart1->setPartie('1');
                $addResultPart1->setUser1($user);
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
                $addResultPart2->setUser1($user);
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
                $addResultPart3->setUser1($user);
                $addResultPart3->setUserKills1('0');
                $addResultPart3->setUser2($memberTeam2);
                $addResultPart3->setUserKills2('0');
                $addResultPart3->setUser3($memberTeam3);
                $addResultPart3->setUserKills3('0');

                $addResultPart3->setPosition('0');
                $addResultPart3->setScore('0');
                $this->em->persist($addResultPart3);
                $this->em->flush();

                $this->addFlash('success', "Ton équipe est inscrite ! Mais il faut encore que tes coéquipiers valident l'email qui vient de leurs être envoyer");
                return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
            } elseif ($tournois->getNombre() == '4') {
                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipe::class);

                $team = $form->get('team')->getData();
                $verifNomTeam = $repository->findOneBy(['nom' => $team, 'tournois' => $tournois]);

                if (!empty($verifNomTeam)) {
                    $this->addFlash('error', "Nom de team déjà utiliser pour ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $pseudo = $user->getPseudo();
                $verifMemberTeam = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam)) {
                    if ($verifMemberTeam[0]['isValide'] == '1') {
                        $this->addFlash('error', "Tu participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($user->getTrn() == null) {
                    $this->addFlash('error', "Tu doit renseigner ton ID warzone dans ton profil pour t'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Ton KDRatio est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneGamesPlayed() == '0' or $user->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Ton compte Activision ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($user->getWarzoneGamesPlayed() < '100' & $user->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Ton nombre de parti est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $memberTeam2 = $form->get('pseudo')->getData();
                $repository = $this->getDoctrine()->getRepository(User::class);
                $pseudo = $memberTeam2->getPseudo();

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $verifMemberTeam2 = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam2)) {
                    if ($verifMemberTeam2[0]['isValide'] == '1') {
                        $this->addFlash('error', "$pseudo participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($memberTeam2->getTrn() == null) {
                    $this->addFlash('error', "$pseudo doit renseigner son ID warzone dans son profil pour s'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Le KDRatio de $pseudo est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneGamesPlayed() == '0' or $memberTeam2->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Le compte Activision de $pseudo ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam2->getWarzoneGamesPlayed() < '100' & $memberTeam2->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Le nombre de parti de $pseudo est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $memberTeam3 = $form->get('pseudo2')->getData();
                $repository = $this->getDoctrine()->getRepository(User::class);
                $pseudo = $memberTeam3->getPseudo();

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $verifMemberTeam3 = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam3)) {
                    if ($verifMemberTeam3[0]['isValide'] == '1') {
                        $this->addFlash('error', "$pseudo participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($memberTeam3->getTrn() == null) {
                    $this->addFlash('error', "$pseudo doit renseigner son ID warzone dans son profil pour s'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam3->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Le KDRatio de $pseudo est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam3->getWarzoneGamesPlayed() == '0' or $memberTeam3->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Le compte Activision de $pseudo ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam3->getWarzoneGamesPlayed() < '100' & $memberTeam3->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Le nombre de parti de $pseudo est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $memberTeam4 = $form->get('pseudo3')->getData();
                $repository = $this->getDoctrine()->getRepository(User::class);
                $pseudo = $memberTeam4->getPseudo();

                $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

                $verifMemberTeam4 = $repository->getUserInWarzoneTournois($pseudo, $id);

                if (!empty($verifMemberTeam4)) {
                    if ($verifMemberTeam4[0]['isValide'] == '1') {
                        $this->addFlash('error', "$pseudo participes déjà à ce tournoi");
                        return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                    }
                }

                if ($memberTeam4->getTrn() == null) {
                    $this->addFlash('error', "$pseudo doit renseigner son ID warzone dans son profil pour s'inscrire au tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam4->getWarzoneKdratio() > '3') {
                    $this->addFlash('error', "Le KDRatio de $pseudo est trop élever pour participer à ce tournoi");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam4->getWarzoneGamesPlayed() == '0' or $memberTeam4->getWarzoneKdratio() == '0') {
                    $this->addFlash('error', "Le compte Activision de $pseudo ne doit pas être en privé");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                if ($memberTeam4->getWarzoneGamesPlayed() < '100' & $memberTeam4->getWarzoneKdratio() > '1.5') {
                    $this->addFlash('error', "Le nombre de parti de $pseudo est insufisiant (et ratio trop élevé) pour participer à ce tournoi (limites : -100 games = ratio < 1.5)");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
                }

                $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
                $palmaresKillsUser1 = $repository->findBy(['user_id' => $user], ['tournois_id' => 'DESC']);
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


                $addTeam = new WarzoneTournoisEquipe();

                $addTeam->setNom($team);
                $addTeam->setTournois($tournois);
                $addTeam->setElo($elo);

                $this->em->persist($addTeam);
                $this->em->flush();


                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($user);
                $addTeamMember->setLead('1');
                $addTeamMember->setIsValide('1');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($memberTeam2);
                $addTeamMember->setLead('0');
                $addTeamMember->setIsValide('0');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                // vérification Token
                $token = new WarzoneTournoisTokenEquipe();

                $nanoId = new Client();
                $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
                $token->setToken($chain);
                $token->setDateCreate(new \DateTime('now'));
                $token->setIsUse('0');
                $token->setUserSend($user);
                $token->setUserReceive($memberTeam2);
                $token->setEquipeList($addTeamMember);
                $token->setEquipe($addTeam);
                $token->setTournois($tournois);

                $this->em->persist($token);
                $this->em->flush();

                // Envoie Email de validation
                $subject = 'Invitation Tournoi ' . $tournois->getNom();
                $content = $this->twig->render('Tournois/warzone_token_send_email.html.twig', [
                'token' => $chain,
                'pseudo' => $memberTeam2,
                'user' => $user,
                'tournois' => $tournois,
                'equipe' => $addTeam,
                ]);
                $this->sendEmail->sendEmail($mailer, $memberTeam2->getUsername(), $subject, $content);
                //

                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($memberTeam3);
                $addTeamMember->setLead('0');
                $addTeamMember->setIsValide('0');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                // vérification Token
                $token = new WarzoneTournoisTokenEquipe();

                $nanoId = new Client();
                $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
                $token->setToken($chain);
                $token->setDateCreate(new \DateTime('now'));
                $token->setIsUse('0');
                $token->setUserSend($user);
                $token->setUserReceive($memberTeam3);
                $token->setEquipeList($addTeamMember);
                $token->setEquipe($addTeam);
                $token->setTournois($tournois);

                $this->em->persist($token);
                $this->em->flush();

                // Envoie Email de validation
                $subject = 'Invitation Tournoi ' . $tournois->getNom();
                $content = $this->twig->render('Tournois/warzone_token_send_email.html.twig', [
                'token' => $chain,
                'pseudo' => $memberTeam3,
                'user' => $user,
                'tournois' => $tournois,
                'equipe' => $addTeam,
                ]);
                $this->sendEmail->sendEmail($mailer, $memberTeam3->getUsername(), $subject, $content);
                //

                $addTeamMember = new WarzoneTournoisEquipeList();

                $addTeamMember->setEquipe($addTeam);
                $addTeamMember->setUser($memberTeam4);
                $addTeamMember->setLead('0');
                $addTeamMember->setIsValide('0');

                $this->em->persist($addTeamMember);
                $this->em->flush();

                // vérification Token
                $token = new WarzoneTournoisTokenEquipe();

                $nanoId = new Client();
                $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
                $token->setToken($chain);
                $token->setDateCreate(new \DateTime('now'));
                $token->setIsUse('0');
                $token->setUserSend($user);
                $token->setUserReceive($memberTeam4);
                $token->setEquipeList($addTeamMember);
                $token->setEquipe($addTeam);
                $token->setTournois($tournois);

                $this->em->persist($token);
                $this->em->flush();

                // Envoie Email de validation
                $subject = 'Invitation Tournoi ' . $tournois->getNom();
                $content = $this->twig->render('Tournois/warzone_token_send_email.html.twig', [
                'token' => $chain,
                'pseudo' => $memberTeam4,
                'user' => $user,
                'tournois' => $tournois,
                'equipe' => $addTeam,
                ]);
                $this->sendEmail->sendEmail($mailer, $memberTeam4->getUsername(), $subject, $content);
                //

                $addResultPart1 = new WarzoneTournoisEquipeResultats();
                $addResultPart1->setEquipe($addTeam);
                $addResultPart1->setTournois($tournois);
                $addResultPart1->setPartie('1');
                $addResultPart1->setUser1($user);
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
                $addResultPart2->setUser1($user);
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
                $addResultPart3->setUser1($user);
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

                $this->addFlash('success', "Ton équipe est inscrite ! Mais il faut encore que tes coéquipiers valident l'email qui vient de leurs être envoyer");
                    return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
            }
        }

        $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);
        $tournoisEquipe = $repository->getWarzoneTournoisEquipe($id);

        foreach ($tournoisEquipe as $val) {
            if (array_key_exists('nom', $val)) {
                $result[$val['nom']][] = $val;
            } else {
                $result[""][] = $val;
            }
        }

        if ($tournoisEquipe == []) {
            $result = "0";
        }
        $div1 = $result;
        $div2 = "0";

        if ($result != "0") {
            if ($tournois->getType() != 'playoff') {
                if ($tournoisEquipe != "0") {
                    $elos = array();
                    foreach ($result as $value) {
                        array_push($elos, $value['0']["elo"]);
                    }

                    $moyElo = array_sum($elos) / count($result);

                    foreach ($result as $value) {
                        if ($value['0']["elo"] <= $moyElo) {
                            $result[$value['0']["nom"]]['div'] = "div2";
                        } else {
                            $result[$value['0']["nom"]]['div'] = "div1";
                        }
                    }

                    $div1 = array();
                    $div2 = array();
                    foreach ($result as $value) {
                        if ($value["div"] == "div1") {
                            $div1[$value['0']["nom"]] = $value;
                        }
                        if ($value["div"] == "div2") {
                            $div2[$value['0']["nom"]] = $value;
                        }
                    }
                }
            }
        }

        if ($user) {
            $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

            $pseudo = $user->getPseudo();
            $memberTeam = $repository->getUserValideInWarzoneTournois($pseudo, $id);

            foreach ($memberTeam as $key => $value) {
                if (empty($value)) {
                    unset($memberTeam[$key]);
                }
            }
            
            if (empty($memberTeam)) {
                $memberTeam = $repository->getUserInWarzoneTournois($pseudo, $id);
            }

            if (!empty($memberTeam)) {
                $nom = $memberTeam[0]['nom'];
                $team = $repository->getAllUserInWarzoneTeam($nom, $id);

                foreach ($team as $val) {
                    if (array_key_exists('nom', $val)) {
                        $resultTeam['team'][] = $val;
                    } else {
                        $resultTeam['team'][] = $val;
                    }
                }
            

                if (isset($memberTeam[1])) {
                    $nom = $memberTeam[1]['nom'];
                    $team = $repository->getAllUserInWarzoneTeam($nom, $id);

                    foreach ($team as $val) {
                        if (array_key_exists('nom', $val)) {
                            $resultTeam2['team'][] = $val;
                        } else {
                            $resultTeam2['team'][] = $val;
                        }
                    }
                } else {
                    $resultTeam2 = '0';
                }

                if (isset($memberTeam[2])) {
                    $nom = $memberTeam[2]['nom'];
                    $team = $repository->getAllUserInWarzoneTeam($nom, $id);

                    foreach ($team as $val) {
                        if (array_key_exists('nom', $val)) {
                            $resultTeam3['team'][] = $val;
                        } else {
                            $resultTeam3['team'][] = $val;
                        }
                    }
                } else {
                    $resultTeam3 = '0';
                }

                if ($memberTeam[0]['lead'] == 'true') {
                    $lead = 'true';
                } else {
                    $lead = 'false';
                }
            } else {
                $resultTeam3 = "0";
                $resultTeam2 = "0";
                $resultTeam = "0";
                $lead = "0";
            }
        } else {
            $resultTeam3 = "0";
            $resultTeam2 = "0";
            $resultTeam = "0";
            $lead = "0";
        }

        $titre = $tournois->getNom();

        return new Response($this->twig->render('Tournois/warzone_tournois.html.twig', [
        'titre' => $titre,
        'tournois' => $tournois,
        'tournoisEquipe1' => $div1,
        'tournoisEquipe2' => $div2,
        'yourTeam' => $resultTeam,
        'yourTeam2' => $resultTeam2,
        'yourTeam3' => $resultTeam3,
        'lead' => $lead,
        'form' => $form->createView(),
        ]));
    }

    //* Valide invitation of user
    public function activeInvitation($chain): Response
    {

        $repository = $this->getDoctrine()->getRepository(WarzoneTournoisTokenEquipe::class);
        $token = $repository->findOneBy(
            ['token' => $chain]
        );

        if (empty($token)) {
            $this->addFlash('error', 'Error');
            return $this->redirectToRoute('index');
        } elseif ($token->getEquipe() == null) {
            $this->addFlash('error', "Cette équipe à été supprimer");
            $tournois = $token->getTournois();
            $id = $tournois->getId();
            return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
        } else {
            $tournois = $token->getTournois();
            $id = $tournois->getId();

            $userReceive = $token->getUserReceive();
            $pseudo = $userReceive->getPseudo();

            $allToken = $repository->getAlreadyTokenValideUserInTournois($id, $pseudo);

            $isUse = $token->getIsUse();

            if ($isUse == '1') {
                $this->addFlash('error', "Lien d'activation déjà utilisé");
                return $this->redirectToRoute('index');
            } elseif (!empty($allToken)) {
                $this->addFlash('error', "Tu participe déjà à ce tournoi");
                return $this->redirectToRoute('index');
            } else {
                $token->setIsUse('1');
                $this->em->flush();

                $equipeList = $token->getEquipeList();
                $equipeList->setIsValide('1');
                $this->em->flush();

                $tournois = $token->getTournois();
                $id = $tournois->getId();

                $this->addFlash('success', 'Ta participation au tournoi est validé');
                return $this->redirectToRoute('tournoisDetails', ['id' => $id]);
            }
        }
    }

    //* Delete a teams from lead
    public function deleteTeam($id, UserInterface $user, Request $request): Response
    {
        $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipe::class);
        $team = $repository->findOneBy(['id' => $id]);
        $tournoi = $team->getTournois();
        $idTournoi = $tournoi->getId();

        $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);
        $idTeam = $team->getId();
        $teamMember = $repository->getLead($idTeam);
        $teamLead = $teamMember[0]['pseudo'];

        if ($tournoi->getIsClose() == '1') {
            $this->addFlash('error', "Le tournoi est terminé");
            return $this->redirectToRoute('tournoisDetails', ['id' => $idTournoi]);
        }

        if ($tournoi->getIsBegin() == '1') {
            $this->addFlash('error', "Le tournoi à déjà commencer");
            return $this->redirectToRoute('tournoisDetails', ['id' => $idTournoi]);
        }

        if ($user->getPseudo() == $teamLead) {
            $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipe::class);
            $team = $repository->findOneBy(['id' => $id]);
    
            $this->em->remove($team);
            $this->em->flush();

            $this->addFlash('success', "Ton équipe à été supprimer. Rappel: Tu ne peux faire que 3 tentatives de création de team lors d'un tournoi");
            return $this->redirectToRoute('tournoisDetails', ['id' => $idTournoi]);
        } else {
            return $this->redirectToRoute('index');
        }
    }

    //* Page resultats
    public function tournoisResultats($id, ?UserInterface $user, Request $request): Response
    {
        $repository = $this->getDoctrine()->getRepository(WarzoneTournois::class);
        $tournois = $repository->findOneBy(['id' => $id]);

        if ($user) {
            $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeList::class);

            $pseudo = $user->getPseudo();
            $memberTeam = $repository->getUserValideInWarzoneTournois($pseudo, $id);

            if (isset($memberTeam[0])) {
                if ($memberTeam[0]['lead'] == 'true') {
                    $lead = 'true';
                } else {
                    $lead = 'false';
                    $teamPart1 = '0';
                    $form1 = '0';
                    $form2 = '0';
                    $form3 = '0';
                }
    
                if ($lead == 'true') {
                    $repository = $this->getDoctrine()->getRepository(WarzoneTournoisEquipeResultats::class);
                    $teamPart1 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '1']);
                    $teamPart2 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '2']);
                    $teamPart3 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '3']);
                    if ($tournois->getNombre() == '1') {
                        $form1 = $this->createForm(WarzoneTournoisResultatsSoloTypePart1::class, $teamPart1);
                        $form1->handleRequest($request);
                        $form2 = $this->createForm(WarzoneTournoisResultatsSoloTypePart2::class, $teamPart2);
                        $form2->handleRequest($request);
                        $form3 = $this->createForm(WarzoneTournoisResultatsSoloTypePart3::class, $teamPart3);
                        $form3->handleRequest($request);
                    } elseif ($tournois->getNombre() == '2') {
                        $form1 = $this->createForm(WarzoneTournoisResultatsDuoTypePart1::class, $teamPart1);
                        $form1->handleRequest($request);
                        $form2 = $this->createForm(WarzoneTournoisResultatsDuoTypePart2::class, $teamPart2);
                        $form2->handleRequest($request);
                        $form3 = $this->createForm(WarzoneTournoisResultatsDuoTypePart3::class, $teamPart3);
                        $form3->handleRequest($request);
                    } elseif ($tournois->getNombre() == '3') {
                        $form1 = $this->createForm(WarzoneTournoisResultatsTrioTypePart1::class, $teamPart1);
                        $form1->handleRequest($request);
                        $form2 = $this->createForm(WarzoneTournoisResultatsTrioTypePart2::class, $teamPart2);
                        $form2->handleRequest($request);
                        $form3 = $this->createForm(WarzoneTournoisResultatsTrioTypePart3::class, $teamPart3);
                        $form3->handleRequest($request);
                    } elseif ($tournois->getNombre() == '4') {
                        $form1 = $this->createForm(WarzoneTournoisResultatsQuadTypePart1::class, $teamPart1);
                        $form1->handleRequest($request);
                        $form2 = $this->createForm(WarzoneTournoisResultatsQuadTypePart2::class, $teamPart2);
                        $form2->handleRequest($request);
                        $form3 = $this->createForm(WarzoneTournoisResultatsQuadTypePart3::class, $teamPart3);
                        $form3->handleRequest($request);
                    }
                }
            } else {
                $teamPart1 = '0';
                $lead = '0';
                $form1 = '0';
                $form2 = '0';
                $form3 = '0';
            }
        } else {
            $teamPart1 = '0';
            $lead = '0';
            $form1 = '0';
            $form2 = '0';
            $form3 = '0';
        }

        if ($tournois->getIsClose() != '1') {
            if ($form1 !== '0' & $form2 !== '0' & $form3 !== '0') {
                if ($form1->isSubmitted() && $form1->isValid()) {
                    if ($tournois->getNombre() == '1') {
                        $teamPart1 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '1']);

                        $userKills1 = $form1->get('userKills1')->getData();
                        $teamPart1->setUserKills1($userKills1);
                        $position = $form1->get('position')->getData();
                        $teamPart1->setPosition($position);

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

                        $teamPart1->setScore($score);
                        $this->em->flush();
                        $form1 = $this->createForm(WarzoneTournoisResultatsSoloTypePart1::class, $teamPart1);
                        $form1->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '2') {
                        $teamPart1 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '1']);
                        $userKills1 = $form1->get('userKills1')->getData();
                        $teamPart1->setUserKills1($userKills1);
                        $userKills2 = $form1->get('userKills2')->getData();
                        $teamPart1->setUserKills2($userKills2);
                        $position = $form1->get('position')->getData();
                        $teamPart1->setPosition($position);

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

                        $teamPart1->setScore($score);
                        $this->em->flush();
                        $form1 = $this->createForm(WarzoneTournoisResultatsDuoTypePart1::class, $teamPart1);
                        $form1->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '3') {
                        $teamPart1 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '1']);
                        $userKills1 = $form1->get('userKills1')->getData();
                        $teamPart1->setUserKills1($userKills1);
                        $userKills2 = $form1->get('userKills2')->getData();
                        $teamPart1->setUserKills2($userKills2);
                        $userKills3 = $form1->get('userKills3')->getData();
                        $teamPart1->setUserKills3($userKills3);
                        $position = $form1->get('position')->getData();
                        $teamPart1->setPosition($position);

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

                        $teamPart1->setScore($score);
                        $this->em->flush();
                        $form1 = $this->createForm(WarzoneTournoisResultatsTrioTypePart1::class, $teamPart1);
                        $form1->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '4') {
                        $teamPart1 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '1']);
                        $userKills1 = $form1->get('userKills1')->getData();
                        $teamPart1->setUserKills1($userKills1);
                        $userKills2 = $form1->get('userKills2')->getData();
                        $teamPart1->setUserKills2($userKills2);
                        $userKills3 = $form1->get('userKills3')->getData();
                        $teamPart1->setUserKills3($userKills3);
                        $userKills4 = $form1->get('userKills4')->getData();
                        $teamPart1->setUserKills4($userKills4);
                        $position = $form1->get('position')->getData();
                        $teamPart1->setPosition($position);

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

                        $teamPart1->setScore($score);
                        $this->em->flush();
                        $form1 = $this->createForm(WarzoneTournoisResultatsQuadTypePart1::class, $teamPart1);
                        $form1->handleRequest($request);
                    }
                } elseif ($form2->isSubmitted() && $form2->isValid()) {
                    if ($tournois->getNombre() == '1') {
                        $teamPart2 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '2']);

                        $userKills1 = $form2->get('userKills1')->getData();
                        $position = $form2->get('position')->getData();

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

                        $teamPart2->setScore($score);
                        $this->em->flush();
                        $form2 = $this->createForm(WarzoneTournoisResultatsSoloTypePart2::class, $teamPart2);
                        $form2->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '2') {
                        $teamPart2 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '2']);

                        $userKills1 = $form2->get('userKills1')->getData();
                        $userKills2 = $form2->get('userKills2')->getData();
                        $position = $form2->get('position')->getData();

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

                        $teamPart2->setScore($score);
                        $this->em->flush();
                        $form2 = $this->createForm(WarzoneTournoisResultatsDuoTypePart2::class, $teamPart2);
                        $form2->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '3') {
                        $teamPart2 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '2']);
                        $userKills1 = $form2->get('userKills1')->getData();
                        $teamPart2->setUserKills1($userKills1);
                        $userKills2 = $form2->get('userKills2')->getData();
                        $teamPart2->setUserKills2($userKills2);
                        $userKills3 = $form2->get('userKills3')->getData();
                        $teamPart2->setUserKills3($userKills3);
                        $position = $form2->get('position')->getData();
                        $teamPart2->setPosition($position);

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

                        $teamPart2->setScore($score);
                        $this->em->flush();
                        $form2 = $this->createForm(WarzoneTournoisResultatsTrioTypePart2::class, $teamPart2);
                        $form2->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '4') {
                        $teamPart2 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '2']);
                        $userKills1 = $form2->get('userKills1')->getData();
                        $teamPart2->setUserKills1($userKills1);
                        $userKills2 = $form2->get('userKills2')->getData();
                        $teamPart2->setUserKills2($userKills2);
                        $userKills3 = $form2->get('userKills3')->getData();
                        $teamPart2->setUserKills3($userKills3);
                        $userKills4 = $form2->get('userKills4')->getData();
                        $teamPart2->setUserKills4($userKills4);
                        $position = $form2->get('position')->getData();
                        $teamPart2->setPosition($position);

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

                        $teamPart2->setScore($score);
                        $this->em->flush();
                        $form2 = $this->createForm(WarzoneTournoisResultatsQuadTypePart2::class, $teamPart2);
                        $form2->handleRequest($request);
                    }
                } elseif ($form3->isSubmitted() && $form3->isValid()) {
                    if ($tournois->getNombre() == '1') {
                        $teamPart3 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '3']);

                        $userKills1 = $form3->get('userKills1')->getData();
                        $position = $form3->get('position')->getData();

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

                        $teamPart3->setScore($score);
                        $this->em->flush();
                        $form3 = $this->createForm(WarzoneTournoisResultatsSoloTypePart3::class, $teamPart3);
                        $form3->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '2') {
                        $teamPart3 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '3']);

                        $userKills1 = $form3->get('userKills1')->getData();
                        $userKills2 = $form3->get('userKills2')->getData();
                        $position = $form3->get('position')->getData();

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

                        $teamPart3->setScore($score);
                        $this->em->flush();
                        $form3 = $this->createForm(WarzoneTournoisResultatsDuoTypePart3::class, $teamPart3);
                        $form3->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '3') {
                        $teamPart3 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '3']);
                        $userKills1 = $form3->get('userKills1')->getData();
                        $teamPart3->setUserKills1($userKills1);
                        $userKills2 = $form3->get('userKills2')->getData();
                        $teamPart3->setUserKills2($userKills2);
                        $userKills3 = $form3->get('userKills3')->getData();
                        $teamPart3->setUserKills3($userKills3);
                        $position = $form3->get('position')->getData();
                        $teamPart3->setPosition($position);

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

                        $teamPart3->setScore($score);
                        $this->em->flush();
                        $form3 = $this->createForm(WarzoneTournoisResultatsTrioTypePart3::class, $teamPart3);
                        $form3->handleRequest($request);
                    }
                    if ($tournois->getNombre() == '4') {
                        $teamPart3 = $repository->findOneBy(['user1' => $user, 'tournois' => $tournois, 'partie' => '3']);
                        $userKills1 = $form3->get('userKills1')->getData();
                        $teamPart3->setUserKills1($userKills1);
                        $userKills2 = $form3->get('userKills2')->getData();
                        $teamPart3->setUserKills2($userKills2);
                        $userKills3 = $form3->get('userKills3')->getData();
                        $teamPart3->setUserKills3($userKills3);
                        $userKills4 = $form3->get('userKills4')->getData();
                        $teamPart3->setUserKills4($userKills4);
                        $position = $form3->get('position')->getData();
                        $teamPart3->setPosition($position);

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

                        $teamPart3->setScore($score);
                        $this->em->flush();
                        $form3 = $this->createForm(WarzoneTournoisResultatsQuadTypePart3::class, $teamPart3);
                        $form3->handleRequest($request);
                    }
                }
            }
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

        $titre = $tournois->getNom();

        if ($form1 !== '0' & $form2 !== '0' & $form3 !== '0') {
            if (!empty($_POST)) {
                unset($_POST);
                return $this->redirectToRoute('tournoisResultats', array('id' => $id));
            }
            return new Response($this->twig->render('Tournois/warzone_tournois_resultats.html.twig', [
                'titre' => $titre,
                'lead' => $lead,
                'team' => $teamPart1,
                'tournois' => $tournois,
                'tournoisResults' => $result3,
                'form1' => $form1->createView(),
                'form2' => $form2->createView(),
                'form3' => $form3->createView(),
            ]));
        } else {
            return new Response($this->twig->render('Tournois/warzone_tournois_resultats.html.twig', [
                'titre' => $titre,
                'lead' => $lead,
                'team' => $teamPart1,
                'tournois' => $tournois,
                'tournoisResults' => $result3,
            ]));
        }
    }
}
