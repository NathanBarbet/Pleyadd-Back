<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use App\Services\GetIp;
use App\Services\EmailService;
use App\Entity\User;
use App\Entity\Perm;
use App\Entity\TokenEmail;
use App\Entity\TokenPassword;
use App\Entity\EmailDisposable;
use App\Entity\WarzoneUserPoint;
use App\Entity\WarzoneSaison;
use App\Entity\WarzoneUserPalmares;
use App\Form\ForgetPasswordType;
use App\Form\NewPasswordType;
use App\Form\RegisterType;
use App\Form\AvatarType;
use App\Form\BanniereType;
use App\Form\EditProfilType;
use App\Form\EditEmailType;
use App\Form\EditPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Hidehalo\Nanoid\Client;
use Hidehalo\Nanoid\GeneratorInterface;
use GeoIp2\Database\Reader;
use ReCaptcha\ReCaptcha;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use GuzzleHttp\Cookie\CookieJarInterface;

class UserController extends AbstractController
{
    private $twig;
    private $encoder;
    private $em;
    private $ipService;
    private $sendEmail;

    public function __construct(UserPasswordEncoderInterface $encoder, Environment $twig, EntityManagerInterface $em, GetIp $ipService, EmailService $sendEmail)
    {
        $this->twig = $twig;
        $this->encoder = $encoder;
        $this->em = $em;
        $this->ipService = $ipService;
        $this->sendEmail = $sendEmail;
    }

  //* register page
    public function register(MailerInterface $mailer, AuthenticationUtils $authentificationUtils, UserPasswordEncoderInterface $encoder, Request $request): Response
    {
        $title = 'Inscription';
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);
        $error = $authentificationUtils->getLastAuthenticationError();

        
        if ($form->isSubmitted() && $form->isValid()) {
            $recaptcha = new ReCaptcha('*****************************************');
            $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());
            if (!$resp->isSuccess()) {
                $this->addFlash('error', "Vous devez valider le captcha");
                
                if (isset($_POST)) {
                    return new Response($this->twig->render('User/register.html.twig', [
                        'post' => $_POST,
                        'title' => $title,
                        'error' => $error,
                        'form' => $form->createView(),
                    ]));
                } else {
                    return $this->redirectToRoute('register');
                }
            }
            $error = $authentificationUtils->getLastAuthenticationError();
            // Verify email
            $email = $form->get('username')->getData();
            $domain = explode("@", $email);

            $repository = $this->getDoctrine()->getRepository(EmailDisposable::class);
            $domainDisposable = $repository->findOneBy(['domain' => $domain[1]]);
            if (!is_null($domainDisposable)) {
                $this->addFlash('error', "N'utilise pas d'email jetable");
                if (isset($_POST)) {
                    return new Response($this->twig->render('User/register.html.twig', [
                        'post' => $_POST,
                        'title' => $title,
                        'error' => $error,
                        'form' => $form->createView(),
                    ]));
                } else {
                    return $this->redirectToRoute('register');
                }
            } else {
                $repository = $this->getDoctrine()->getRepository(User::class);
                $emailDispo = $repository->findOneBy(['email' => $email]);
                if ($emailDispo != null) {
                    $this->addFlash('error', "Email déjà utilisé");
                    if (isset($_POST)) {
                        return new Response($this->twig->render('User/register.html.twig', [
                            'post' => $_POST,
                            'title' => $title,
                            'error' => $error,
                            'form' => $form->createView(),
                        ]));
                    } else {
                        return $this->redirectToRoute('register');
                    }
                } else {
                    $user->setUsername($email);
                }
            }

            // Encode password
            $user->setPassword($encoder->encodePassword($user, $form->get('password')->getData()));

            // vérification IP User
            $ipUser = $this->ipService->getUserIp();
            //    $ipUser = '******';
            $user->setIpUser($ipUser);

            // Set Timezone
            require_once("geoip/geoip2.phar");
            // City DB
            $reader = new Reader('geoip/GeoLite2-City.mmdb');
            $timezone = 'Europe/Paris';

            if ($ipUser === '::1') {
                try {
                    $record = $reader->city($ipUser);
                    $timezone = geoip_time_zone_by_country_and_region($record->country->isoCode, $record->mostSpecificSubdivision->isoCode);
                } catch (\Exception $e) {
                }
            }

            $user->setTimezone($timezone);

            // vérification Perm
            $permId = '1';
            $repository = $this->getDoctrine()->getRepository(Perm::class);
            $perm = $repository->findOneBy(['id' => $permId]);
            $user->setPerm($perm);

            $user->setDateRegister(new \DateTime('now'));

            $trn = $form->get('trn')->getData();

            $pseudo = $form->get('pseudo')->getData();

            $user->setTrn($trn);
            $user->setWarzonePlateforme('uno');

            if (!empty($trn)) {
                $client = new \GuzzleHttp\Client(['cookies' => true]);
                $response = $client->request('GET', 'https://profile.callofduty.com/login');

                $jar = $client->getConfig('cookies');
                $xsrf = $jar->cookies[0]->data['Value'];

                $response = $client->request('POST', 'https://profile.callofduty.com/do_login?new_SiteId=cod', [
                    'form_params' => [
                        'username' => '**************',
                        'password' => '**************',
                        'remember_me' => 'true',
                        '_csrf' => $xsrf
                    ]
                ]);

                $trn2 = str_replace('#', '%23', $trn);

                $response = $client->request('GET', 'https://my.callofduty.com/api/papi-client/stats/cod/v1/title/mw/platform/uno/gamer/' . $trn2 . '/profile/type/wz');
                        
                $data = json_decode($response->getBody());
                        
                if ($data->status == 'error') {
                    $this->addFlash('error', "ID Activision invalide");
                    
                    if (isset($_POST)) {
                        return new Response($this->twig->render('User/register.html.twig', [
                            'post' => $_POST,
                            'title' => $title,
                            'error' => $error,
                            'form' => $form->createView(),
                        ]));
                    } else {
                        return $this->redirectToRoute('register');
                    }
                } elseif ($data->data->lifetime->mode->br->properties->kdRatio == '0' or $data->data->lifetime->mode->br->properties->gamesPlayed == '0') {
                    $this->addFlash('error', "Ton compte Activision ne doit pas être en privé");

                    if (isset($_POST)) {
                        return new Response($this->twig->render('User/register.html.twig', [
                            'post' => $_POST,
                            'title' => $title,
                            'error' => $error,
                            'form' => $form->createView(),
                        ]));
                    } else {
                        return $this->redirectToRoute('register');
                    }
                } else {
                    if (isset($data->data->lifetime->mode->br->properties->wins)) {
                        $user->setWarzoneWins($data->data->lifetime->mode->br->properties->wins);
                    } else {
                        $this->addFlash('error', "ID Activision invalide");

                        if (isset($_POST)) {
                            return new Response($this->twig->render('User/register.html.twig', [
                                'post' => $_POST,
                                'title' => $title,
                                'error' => $error,
                                'form' => $form->createView(),
                            ]));
                        } else {
                            return $this->redirectToRoute('register');
                        }
                    }
                    if (isset($data->data->lifetime->mode->br->properties->kills)) {
                        $user->setWarzoneKills($data->data->lifetime->mode->br->properties->kills);
                    } else {
                        $this->addFlash('error', "ID Activision invalide");

                        if (isset($_POST)) {
                            return new Response($this->twig->render('User/register.html.twig', [
                                'post' => $_POST,
                                'title' => $title,
                                'error' => $error,
                                'form' => $form->createView(),
                            ]));
                        } else {
                            return $this->redirectToRoute('register');
                        }
                    }
                    if (isset($data->data->lifetime->mode->br->properties->kdRatio)) {
                        $user->setWarzoneKdratio($data->data->lifetime->mode->br->properties->kdRatio);
                    } else {
                        $this->addFlash('error', "ID Activision invalide");

                        if (isset($_POST)) {
                            return new Response($this->twig->render('User/register.html.twig', [
                                'post' => $_POST,
                                'title' => $title,
                                'error' => $error,
                                'form' => $form->createView(),
                            ]));
                        } else {
                            return $this->redirectToRoute('register');
                        }
                    }
                    if (isset($data->data->lifetime->mode->br->properties->gamesPlayed)) {
                        $user->setWarzoneGamesplayed($data->data->lifetime->mode->br->properties->gamesPlayed);
                    } else {
                        $this->addFlash('error', "ID Activision invalide");

                        if (isset($_POST)) {
                            return new Response($this->twig->render('User/register.html.twig', [
                                'post' => $_POST,
                                'title' => $title,
                                'error' => $error,
                                'form' => $form->createView(),
                            ]));
                        } else {
                            return $this->redirectToRoute('register');
                        }
                    }
                }
            }
                
            $this->em->persist($user);
            $this->em->flush();

            // vérification Token
            $token = new TokenEmail();

            $nanoId = new Client();
            $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
            $token->setToken($chain);

            $repository = $this->getDoctrine()->getRepository(User::class);
            $userId = $repository->findOneBy(['id' => $user]);
            $token->setUser($userId);

            // vérification IP User pour token
            $token->setIpUser($this->ipService->getUserIp());

            $token->setIsUse('0');

            $token->setDateCreate(new \DateTime('now'));

            $this->em->persist($token);
            $this->em->flush();

            // Envoie Email de validation
            $subject = 'Validation email';
            $content = $this->twig->render('Mail/user_registered.html.twig', [
            'token' => $chain,
            'user' => $user,
            ]);
            $this->sendEmail->sendEmail($mailer, $user->getUsername(), $subject, $content);
            //

            $this->addFlash('success', "Tu as reçu un lien d'activation par email");
            return $this->redirectToRoute('login');
        } else {
            return new Response($this->twig->render('User/register.html.twig', [
            'title' => $title,
            'error' => $error,
            'form' => $form->createView(),

            ]));
        }
    }

  //* Valide email of user
    public function activeemail($chain): Response
    {

        $repository = $this->getDoctrine()->getRepository(TokenEmail::class);
        $token = $repository->findOneBy(
            ['token' => $chain]
        );

        if (empty($token)) {
            $this->addFlash('error', 'Error');
            return $this->redirectToRoute('register');
        } else {
            $dateUse = date_add($token->getDateCreate(), date_interval_create_from_date_string('1 day'));

            $isUse = $token->getIsUse();

            $dateNow = new \DateTime();

            if ($isUse == '1') {
                $this->addFlash('error', "Lien d'activation déjà utilisé");
                return $this->redirectToRoute('login');
            } elseif ($dateNow < $dateUse) {
                $token->setIsUse('1');
                $this->em->flush();
                $email = $token->getEmail();
                $user = $token->getUser();

                if ($user->getDateValide() == null) {
                    $user->setDateValide(new \DateTime());
                }
                if ($email == null) {
                    $this->em->flush();
                    $this->addFlash('success', 'Compte validé!');
                    return $this->redirectToRoute('login');
                } else {
                    $user = $token->getUser();
                    $user->setUsername($email);
                    $this->em->flush();
                    $this->addFlash('success', 'Email changé!');
                    return $this->redirectToRoute('login');
                }
            } else {
                $token->setIsUse('1');
                $this->em->flush();
                $this->addFlash('error', '24h dépasé, vous devez refaire une demande');
                return $this->redirectToRoute('login');
            }
        }
    }

    //* Display forgot password page
    public function forgetpassword(MailerInterface $mailer, AuthenticationUtils $authentificationUtils, Request $request): Response
    {
        $title = 'Mot de passe oublié';
        $form = $this->createForm(ForgetPasswordType::class);
        $form->handleRequest($request);
        $error = $authentificationUtils->getLastAuthenticationError();

        if ($form->isSubmitted() && $form->isValid()) {
            $emailUser = $form->get('username')->getData();
            $repository = $this->getDoctrine()->getRepository(User::class);
            $user = $repository->findOneBy(
                ['email' => $emailUser]
            );

            if (!empty($user)) {
                $token = new TokenPassword();

                $nanoId = new Client();
                $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
                $token->setToken($chain);

                $repository = $this->getDoctrine()->getRepository(User::class);
                $userId = $repository->findOneBy(['id' => $user]);
                $token->setUser($userId);

              // vérification IP User pour token
                $token->setIpUser($this->ipService->getUserIp());

                $token->setIsUse('0');

                $token->setDateCreate(new \DateTime('now'));

                $this->em->persist($token);
                $this->em->flush();

              // Envoie Email de validation
                $subject = 'Mot de passe oublié';
                $content = $this->twig->render('Mail/reset_password.html.twig', [
                'token' => $chain,
                'user' => $user,
                ]);
                $this->sendEmail->sendEmail($mailer, $emailUser, $subject, $content);

                $this->addFlash('success', 'Si cette email correspond à un compte éxistant, Vous avez reçu un lien pour changer votre mot de passe.');
                return $this->redirectToRoute('forgetpassword');
            } else {
                $this->addFlash('success', 'Si cette email correspond à un compte éxistant, Vous avez reçu un lien pour changer votre mot de passe.');
                return $this->redirectToRoute('forgetpassword');
            }
        }
        return new Response($this->twig->render('Form/formtemplate.html.twig', [
        'title' => $title,
        'form' => $form->createView(),
        'error' => $error
        ]));
    }


      //* page reset password with verification of token
    public function newpassword($chain, AuthenticationUtils $authentificationUtils, Request $request): Response
    {
        $error = $authentificationUtils->getLastAuthenticationError();

        $title = 'Nouveau mot de passe';
        $form = $this->createForm(NewPasswordType::class);
        $form->handleRequest($request);
        $error = $authentificationUtils->getLastAuthenticationError();

        $repository = $this->getDoctrine()->getRepository(TokenPassword::class);
        $token = $repository->findOneBy(
            ['token' => $chain]
        );

        if (empty($token)) {
            $this->addFlash('error', 'Error');
            return $this->redirectToRoute('forgetpassword');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $dateUse = date_add($date = $token->getDateCreate(), date_interval_create_from_date_string('1 day'));

            $dateNow = new \DateTime();

            $isUse = $token->getIsUse();

            if ($isUse == '1') {
                $this->addFlash('error', 'Lien déjà utilisé');
                return $this->redirectToRoute('forgetpassword');
            } elseif ($dateNow < $dateUse) {
                $user = $token->getUser();

                    // vérification password
                    $user->setPassword($this->encoder->encodePassword($user, $form->get('password')->getData()));

                    $this->em->flush();

                    $token->setIsUse('1');
                    $this->em->flush();

                    $this->addFlash('success', 'Mot de passe modifié');
                    return $this->redirectToRoute('login');
            } else {
                $this->addFlash('error', 'Limite de temps dépassé');
                return $this->redirectToRoute('forgetpassword');
            }
        } else {
            return new Response($this->twig->render('Form/formtemplate.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            'error' => $error,
            'chain' => $chain
            ]));
        }
    }

    //* Login function
    public function login(AuthenticationUtils $authentificationUtils, Request $request): Response
    {
        $error = $authentificationUtils->getLastAuthenticationError();
        $lastUsername = $authentificationUtils->getLastUsername();

        return new Response($this->twig->render('User/login.html.twig', [
          'error' => $error,
          'last_username' => $lastUsername
        ]));
    }

    //* Display profil page
    public function profil(UserInterface $user, Request $request, SluggerInterface $slugger): Response
    {
        $title = 'Profil';
        $date = (new \DateTime())->format('m');
        $userPseudo = $user->getPseudo();

        $form = $this->createForm(AvatarType::class, $user);
        $form->handleRequest($request);

        $form2 = $this->createForm(BanniereType::class, $user);
        $form2->handleRequest($request);

        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
        $palmares = $repository->findBy(['user_id' => $user], ['tournois_id' => 'DESC'], 10);
        $palmaresWin = $repository->findBy(['user_id' => $user,'position' => '1']);

        $repository = $this->getDoctrine()->getRepository(User::class);
        $pseudo = $repository->findOneBy(['pseudo' => $userPseudo]);

        $repository = $this->getDoctrine()->getRepository(WarzoneSaison::class);
        $dateNow = time();
        $saison = $repository->getActualSaison($dateNow);

        $dateDebut = $saison[0]->getDateDebut();
        $dateFin = $saison[0]->getDateFin();

        $repository = $this->getDoctrine()->getRepository(User::class);
        $data = $repository->getClassementWarzoneGlobal($dateDebut, $dateFin);

        if (in_array($userPseudo, array_column($data, 'pseudo'))) {
            $placeGlobal = array_search($userPseudo, array_column($data, 'pseudo'));

            $placeGlobal = $placeGlobal + 1;
        } else {
            $placeGlobal = 0;
        }

        $data = $repository->getClassementWarzoneMensuel($date);
        $placeMensuel = array_search($userPseudo, array_column($data, 'pseudo'));

        $placeMensuel = $placeMensuel + 1;

        $pointsGlobal = $repository->getPseudoPointsGlobal($userPseudo, $dateDebut, $dateFin);
        $pointsMensuel = $repository->getPseudoPointsMensuel($date, $userPseudo);

        $repository = $this->getDoctrine()->getRepository(WarzoneUserPoint::class);
        $playerWithPoints = $repository->getInscritTotalWithPoints();
        $countPlayerWithPoints = count($playerWithPoints);
        
        if ($placeGlobal != '0') {
            $percentPlace = $placeGlobal * 100 / $countPlayerWithPoints;

            if ($percentPlace <= '100') {
                $rankPlayer = 'bronze';
            }
            if ($percentPlace <= '20') {
                $rankPlayer = 'bronze';
            }
            if ($percentPlace <= '10') {
                $rankPlayer = 'argent';
            }
            if ($percentPlace <= '5') {
                $rankPlayer = 'or';
            }
            if ($percentPlace <= '2') {
                $rankPlayer = 'diamant';
            }
            if ($percentPlace <= '1') {
                $rankPlayer = 'platine';
            }
            if ($percentPlace <= '0,1') {
                $rankPlayer = 'master';
            }
            if ($percentPlace > '100') {
                $rankPlayer = 'nc';
            }
        } else {
            $rankPlayer = 'nc';
        }

        $tropheBronze = 0;
        $tropheArgent = 0;
        $tropheOr = 0;
        $trophePlatine = 0;

        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
        $trophes = $repository->getTrophes($user);

        if (isset($trophes[0])) {
            for ($i = 0; $i < count($trophes); ++$i) {
                if ($trophes[$i]['type'] == 'mk') {
                    if ($trophes[$i]['position'] <= '10' and $trophes[$i]['position'] >= '4') {
                        $tropheBronze = $tropheBronze + 1;
                    }
                    if ($trophes[$i]['position'] == '2' or $trophes[$i]['position'] == '3') {
                        $tropheArgent = $tropheArgent + 1;
                    }
                    if ($trophes[$i]['position'] == '1') {
                        $tropheOr = $tropheOr + 1;
                    }
                }
                if ($trophes[$i]['type'] == 'playoff') {
                    if ($trophes[$i]['position'] <= '15' and $trophes[$i]['position'] >= '11') {
                        $tropheBronze = $tropheBronze + 1;
                    }
                    if ($trophes[$i]['position'] <= '10' and $trophes[$i]['position'] >= '4') {
                        $tropheArgent = $tropheArgent + 1;
                    }
                    if ($trophes[$i]['position'] == '2' or $trophes[$i]['position'] == '3') {
                        $tropheOr = $tropheOr + 1;
                    }
                    if ($trophes[$i]['position'] == '1') {
                        $trophePlatine = $trophePlatine + 1;
                    }
                }
                if ($trophes[$i]['type'] == 'event') {
                    if ($trophes[$i]['position'] <= '20' and $trophes[$i]['position'] >= '11') {
                        $tropheBronze = $tropheBronze + 1;
                    }
                    if ($trophes[$i]['position'] <= '10' and $trophes[$i]['position'] >= '4') {
                        $tropheArgent = $tropheArgent + 1;
                    }
                    if ($trophes[$i]['position'] == '2' or $trophes[$i]['position'] == '3') {
                        $tropheOr = $tropheOr + 1;
                    }
                    if ($trophes[$i]['position'] == '1') {
                        $trophePlatine = $trophePlatine + 1;
                    }
                }
            }
        }

        $top = $repository->getTop($user);

        $top1 = 0;
        $top3 = 0;
        $top10 = 0;

        if (isset($top[0])) {
            for ($i = 0; $i < count($top); ++$i) {
                    $top1 = $top1 + $top[$i]['top1'];
                    $top3 = $top3 + $top[$i]['top3'];
                    $top10 = $top10 + $top[$i]['top10'];
            }
        }

        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
        $palmaresKills = $repository->findBy(['user_id' => $user], ['tournois_id' => 'DESC']);
        $killsTournois = 0;

        if (isset($palmaresKills[0])) {
            for ($i = 0; $i < count($palmaresKills); ++$i) {
                    $killsTournois = $killsTournois + $palmaresKills[$i]->getNombreKills();
            }
        }

        $succes = 25;
        if ($user->getTrn() != null) {
            $succes = $succes + 25;
        }
        if (!empty($palmares)) {
            $succes = $succes + 25;
        }
        if ($user->getAvatar() != null & $user->getBanniere() != null & $user->getBio() != null) {
            $succes = $succes + 25;
        }

        /* SECTION HISTORIQUE SAISON */

        $repository = $this->getDoctrine()->getRepository(WarzoneSaison::class);
        $allSaisons = $repository->findAllSaisons();

        for ($i = 0; $i < count($allSaisons); ++$i) {
            $dateDebut = $allSaisons[$i]['dateDebut'];
            $dateFin = $allSaisons[$i]['dateFin'];

            $repository = $this->getDoctrine()->getRepository(User::class);
            $pointsGlobalSaison = $repository->getPseudoPointsGlobalProfil($userPseudo, $dateDebut, $dateFin);
            if (!empty($pointsGlobalSaison)) {
                $pointsGlobalSaison2 = $pointsGlobalSaison[0];
            } else {
                $pointsGlobalSaison2 = 0;
            }
            $data = $repository->getClassementWarzoneGlobal($dateDebut, $dateFin);

            if (in_array($userPseudo, array_column($data, 'pseudo'))) {
                $placeGlobalSaison = array_search($userPseudo, array_column($data, 'pseudo'));

                $placeGlobalSaison = $placeGlobalSaison + 1;
            } else {
                $placeGlobalSaison = 0;
            }

            $repository = $this->getDoctrine()->getRepository(WarzoneUserPoint::class);
            $playerWithPoints = $repository->getInscritSaisonWithPoints($dateDebut, $dateFin);
            $countPlayerWithPoints = count($playerWithPoints);
            
            if ($placeGlobalSaison != '0') {
                $percentPlace = $placeGlobalSaison * 100 / $countPlayerWithPoints;

                if ($percentPlace <= '100') {
                    $rankPlayerSaison = 'bronze';
                }
                if ($percentPlace <= '20') {
                    $rankPlayerSaison = 'bronze';
                }
                if ($percentPlace <= '10') {
                    $rankPlayerSaison = 'argent';
                }
                if ($percentPlace <= '5') {
                    $rankPlayerSaison = 'or';
                }
                if ($percentPlace <= '2') {
                    $rankPlayerSaison = 'diamant';
                }
                if ($percentPlace <= '1') {
                    $rankPlayerSaison = 'platine';
                }
                if ($percentPlace <= '0,1') {
                    $rankPlayerSaison = 'master';
                }
                if ($percentPlace > '100') {
                    $rankPlayerSaison = 'nc';
                }
            } else {
                $rankPlayerSaison = 'nc';
            }

            array_push($allSaisons[$i], $rankPlayerSaison);
            if (!empty($pointsGlobalSaison2)) {
                array_push($allSaisons[$i], $pointsGlobalSaison2);
            } else {
                $pointsGlobalSaison = 0;
                array_push($allSaisons[$i], $pointsGlobalSaison2);
            }
            
            $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
            $palmaresSaison = $repository->getPalmaresSaison($user, $dateDebut, $dateFin);
            
            $victoires = 0;
            $elims = 0;
            for ($i2 = 0; $i2 < count($palmaresSaison); ++$i2) {
                if ($palmaresSaison[$i2]['position'] == '1') {
                    $victoires = $victoires + 1;
                }
                $elims = $elims + $palmaresSaison[$i2]['nombreKills'];
            }

            if (!empty($palmaresSaison)) {
                array_push($allSaisons[$i], count($palmaresSaison));
            } else {
                $palmaresSaison = 0;
                array_push($allSaisons[$i], $palmaresSaison);
            }

            if (!empty($victoires)) {
                array_push($allSaisons[$i], $victoires);
            } else {
                $victoires = 0;
                array_push($allSaisons[$i], $victoires);
            }

            if (!empty($elims)) {
                array_push($allSaisons[$i], $elims);
            } else {
                $elims = 0;
                array_push($allSaisons[$i], $elims);
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $avatar = $form->get('avatar')->getData();
            if (!empty($avatar)) {
                $originalFilename = pathinfo($avatar->getClientOriginalName(), PATHINFO_FILENAME);

                $pseudo = $user->getPseudo();
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $pseudo . '-' . $safeFilename . '-' . uniqid() . '.' . $avatar->guessExtension();

                try {
                    $avatar->move(
                        $this->getParameter('Avatar'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }
                $user->setAvatar($newFilename);
                $this->em->flush();
            }
        }

        if ($form2->isSubmitted() && $form2->isValid()) {
            $banniere = $form2->get('banniere')->getData();
            if (!empty($banniere)) {
                $originalFilename2 = pathinfo($banniere->getClientOriginalName(), PATHINFO_FILENAME);

                $pseudo = $user->getPseudo();
                $safeFilename2 = $slugger->slug($originalFilename2);
                $newFilename2 = $pseudo . '-' . $safeFilename2 . '-' . uniqid() . '.' . $banniere->guessExtension();

                try {
                    $banniere->move(
                        $this->getParameter('Banniere'),
                        $newFilename2
                    );
                } catch (FileException $e) {
                }
                $user->setBanniere($newFilename2);
                $this->em->flush();
            }
        }

        return new Response($this->twig->render('User/profil.html.twig', [
        'title' => $title,
        'form' => $form->createView(),
        'form2' => $form2->createView(),
        'pointsGlobal' => $pointsGlobal,
        'pointsMensuel' => $pointsMensuel,
        'placeGlobal' => $placeGlobal,
        'placeMensuel' => $placeMensuel,
        'palmares' => $palmares,
        'palmaresWin' => $palmaresWin,
        'rankPlayer' => $rankPlayer,
        'tropheBronze' => $tropheBronze,
        'tropheArgent' => $tropheArgent,
        'tropheOr' => $tropheOr,
        'trophePlatine' => $trophePlatine,
        'top1' => $top1,
        'top3' => $top3,
        'top10' => $top10,
        'killsTournois' => $killsTournois,
        'succes' => $succes,
        'saison' => $saison,
        'allSaisons' => $allSaisons,
        ]));
    }

    //* Display profil public page
    public function profilpublic($userPseudo): Response
    {
        $title = 'Profil';
        $date = (new \DateTime())->format('m');

        $repository = $this->getDoctrine()->getRepository(User::class);
        $pseudo = $repository->findOneBy(['pseudo' => $userPseudo]);

        if (empty($pseudo)) {
            $this->addFlash("error", "Ce joueur n'existe pas");
            return $this->redirectToRoute('index');
        }

        $repository = $this->getDoctrine()->getRepository(WarzoneSaison::class);
        $dateNow = time();
        $saison = $repository->getActualSaison($dateNow);

        $dateDebut = $saison[0]->getDateDebut();
        $dateFin = $saison[0]->getDateFin();

        $repository = $this->getDoctrine()->getRepository(User::class);
        $data = $repository->getClassementWarzoneGlobal($dateDebut, $dateFin);

        if (in_array($userPseudo, array_column($data, 'pseudo'))) {
            $placeGlobal = array_search($userPseudo, array_column($data, 'pseudo'));

            $placeGlobal = $placeGlobal + 1;
        } else {
            $placeGlobal = 0;
        }

        $data = $repository->getClassementWarzoneMensuel($date);
        $placeMensuel = array_search($userPseudo, array_column($data, 'pseudo'));

        $placeMensuel = $placeMensuel + 1;

        $pointsGlobal = $repository->getPseudoPointsGlobal($userPseudo, $dateDebut, $dateFin);
        $pointsMensuel = $repository->getPseudoPointsMensuel($date, $userPseudo);

        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
        $palmares = $repository->findBy(['user_id' => $pseudo], ['tournois_id' => 'DESC']);
        $palmaresWin = $repository->findBy(['user_id' => $pseudo,'position' => '1']);

        $repository = $this->getDoctrine()->getRepository(WarzoneUserPoint::class);
        $playerWithPoints = $repository->getInscritTotalWithPoints();
        $countPlayerWithPoints = count($playerWithPoints);
        
        if ($placeGlobal != '0') {
            $percentPlace = $placeGlobal * 100 / $countPlayerWithPoints;

            if ($percentPlace <= '100') {
                $rankPlayer = 'bronze';
            }
            if ($percentPlace <= '20') {
                $rankPlayer = 'bronze';
            }
            if ($percentPlace <= '10') {
                $rankPlayer = 'argent';
            }
            if ($percentPlace <= '5') {
                $rankPlayer = 'or';
            }
            if ($percentPlace <= '2') {
                $rankPlayer = 'diamant';
            }
            if ($percentPlace <= '1') {
                $rankPlayer = 'platine';
            }
            if ($percentPlace <= '0,1') {
                $rankPlayer = 'master';
            }
            if ($percentPlace > '100') {
                $rankPlayer = 'nc';
            }
        } else {
            $rankPlayer = 'nc';
        }

        $tropheBronze = 0;
        $tropheArgent = 0;
        $tropheOr = 0;
        $trophePlatine = 0;

        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
        $trophes = $repository->getTrophesPseudo($pseudo);

        if (isset($trophes[0])) {
            for ($i = 0; $i < count($trophes); ++$i) {
                if ($trophes[$i]['type'] == 'mk') {
                    if ($trophes[$i]['position'] <= '10' and $trophes[$i]['position'] >= '4') {
                        $tropheBronze = $tropheBronze + 1;
                    }
                    if ($trophes[$i]['position'] == '2' or $trophes[$i]['position'] == '3') {
                        $tropheArgent = $tropheArgent + 1;
                    }
                    if ($trophes[$i]['position'] == '1') {
                        $tropheOr = $tropheOr + 1;
                    }
                }
                if ($trophes[$i]['type'] == 'playoff') {
                    if ($trophes[$i]['position'] <= '15' and $trophes[$i]['position'] >= '11') {
                        $tropheBronze = $tropheBronze + 1;
                    }
                    if ($trophes[$i]['position'] <= '10' and $trophes[$i]['position'] >= '4') {
                        $tropheArgent = $tropheArgent + 1;
                    }
                    if ($trophes[$i]['position'] == '2' or $trophes[$i]['position'] == '3') {
                        $tropheOr = $tropheOr + 1;
                    }
                    if ($trophes[$i]['position'] == '1') {
                        $trophePlatine = $trophePlatine + 1;
                    }
                }
                if ($trophes[$i]['type'] == 'event') {
                    if ($trophes[$i]['position'] <= '20' and $trophes[$i]['position'] >= '11') {
                        $tropheBronze = $tropheBronze + 1;
                    }
                    if ($trophes[$i]['position'] <= '10' and $trophes[$i]['position'] >= '4') {
                        $tropheArgent = $tropheArgent + 1;
                    }
                    if ($trophes[$i]['position'] == '2' or $trophes[$i]['position'] == '3') {
                        $tropheOr = $tropheOr + 1;
                    }
                    if ($trophes[$i]['position'] == '1') {
                        $trophePlatine = $trophePlatine + 1;
                    }
                }
            }
        }

        $top = $repository->getTopPseudo($pseudo);

        $top1 = 0;
        $top3 = 0;
        $top10 = 0;

        if (isset($top[0])) {
            for ($i = 0; $i < count($top); ++$i) {
                    $top1 = $top1 + $top[$i]['top1'];
                    $top3 = $top3 + $top[$i]['top3'];
                    $top10 = $top10 + $top[$i]['top10'];
            }
        }

        $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
        $palmaresKills = $repository->findBy(['user_id' => $pseudo], ['tournois_id' => 'DESC']);
        $killsTournois = 0;

        if (isset($palmaresKills[0])) {
            for ($i = 0; $i < count($palmaresKills); ++$i) {
                    $killsTournois = $killsTournois + $palmaresKills[$i]->getNombreKills();
            }
        }

        $succes = 25;
        if ($pseudo->getTrn() != null) {
            $succes = $succes + 25;
        }
        if (!empty($palmares)) {
            $succes = $succes + 25;
        }
        if ($pseudo->getAvatar() != null & $pseudo->getBanniere() != null & $pseudo->getBio() != null) {
            $succes = $succes + 25;
        }

        /* SECTION HISTORIQUE SAISON */

        $repository = $this->getDoctrine()->getRepository(WarzoneSaison::class);
        $allSaisons = $repository->findAllSaisons();

        for ($i = 0; $i < count($allSaisons); ++$i) {
            $dateDebut = $allSaisons[$i]['dateDebut'];
            $dateFin = $allSaisons[$i]['dateFin'];

            $repository = $this->getDoctrine()->getRepository(User::class);
            $pointsGlobalSaison = $repository->getPseudoPointsGlobalProfil($userPseudo, $dateDebut, $dateFin);
            if (!empty($pointsGlobalSaison)) {
                $pointsGlobalSaison2 = $pointsGlobalSaison[0];
            } else {
                $pointsGlobalSaison2 = 0;
            }
            $data = $repository->getClassementWarzoneGlobal($dateDebut, $dateFin);

            if (in_array($userPseudo, array_column($data, 'pseudo'))) {
                $placeGlobalSaison = array_search($userPseudo, array_column($data, 'pseudo'));

                $placeGlobalSaison = $placeGlobalSaison + 1;
            } else {
                $placeGlobalSaison = 0;
            }

            $repository = $this->getDoctrine()->getRepository(WarzoneUserPoint::class);
            $playerWithPoints = $repository->getInscritSaisonWithPoints($dateDebut, $dateFin);
            $countPlayerWithPoints = count($playerWithPoints);
            
            if ($placeGlobalSaison != '0') {
                $percentPlace = $placeGlobalSaison * 100 / $countPlayerWithPoints;

                if ($percentPlace <= '100') {
                    $rankPlayerSaison = 'bronze';
                }
                if ($percentPlace <= '20') {
                    $rankPlayerSaison = 'bronze';
                }
                if ($percentPlace <= '10') {
                    $rankPlayerSaison = 'argent';
                }
                if ($percentPlace <= '5') {
                    $rankPlayerSaison = 'or';
                }
                if ($percentPlace <= '2') {
                    $rankPlayerSaison = 'diamant';
                }
                if ($percentPlace <= '1') {
                    $rankPlayerSaison = 'platine';
                }
                if ($percentPlace <= '0,1') {
                    $rankPlayerSaison = 'master';
                }
                if ($percentPlace > '100') {
                    $rankPlayerSaison = 'nc';
                }
            } else {
                $rankPlayerSaison = 'nc';
            }

            array_push($allSaisons[$i], $rankPlayerSaison);
            array_push($allSaisons[$i], $pointsGlobalSaison2);
            
            $repository = $this->getDoctrine()->getRepository(WarzoneUserPalmares::class);
            $palmaresSaison = $repository->getPalmaresSaisonPseudo($pseudo, $dateDebut, $dateFin);
            
            $victoires = 0;
            $elims = 0;
            for ($i2 = 0; $i2 < count($palmaresSaison); ++$i2) {
                if ($palmaresSaison[$i2]['position'] == '1') {
                    $victoires = $victoires + 1;
                }
                $elims = $elims + $palmaresSaison[$i2]['nombreKills'];
            }

            array_push($allSaisons[$i], count($palmaresSaison));
            array_push($allSaisons[$i], $victoires);
            array_push($allSaisons[$i], $elims);
        }

        return new Response($this->twig->render('User/profilpublic.html.twig', [
        'title' => $title,
        'pseudo' => $pseudo,
        'pointsGlobal' => $pointsGlobal,
        'pointsMensuel' => $pointsMensuel,
        'placeGlobal' => $placeGlobal,
        'placeMensuel' => $placeMensuel,
        'palmares' => $palmares,
        'palmaresWin' => $palmaresWin,
        'rankPlayer' => $rankPlayer,
        'tropheBronze' => $tropheBronze,
        'tropheArgent' => $tropheArgent,
        'tropheOr' => $tropheOr,
        'trophePlatine' => $trophePlatine,
        'top1' => $top1,
        'top3' => $top3,
        'top10' => $top10,
        'succes' => $succes,
        'killsTournois' => $killsTournois,
        'saison' => $saison,
        'allSaisons' => $allSaisons,
        ]));
    }

    //* Page edit profil
    public function editprofil(MailerInterface $mailer, AuthenticationUtils $authentificationUtils, UserPasswordEncoderInterface $encoder, Request $request, UserInterface $user, SluggerInterface $slugger): Response
    {
        $form1 = $this->createForm(EditProfilType::class, $user);
        $form1->handleRequest($request);
        $form2 = $this->createForm(EditEmailType::class, $user);
        $form2->handleRequest($request);
        $form3 = $this->createForm(EditPasswordType::class, $user);
        $form3->handleRequest($request);

        if ($form2->isSubmitted() && $form2->isValid()) {
            // vérification email
            $emailUser = $form2->get('username')->getData();

            if (!empty($emailUser)) {
                if (!empty($emailUser)) {
                  // Verify email
                    $domain = explode("@", $emailUser);

                    $repository = $this->getDoctrine()->getRepository(EmailDisposable::class);
                    $domainDisposable = $repository->findOneBy(['domain' => $domain[1]]);
                    if (!is_null($domainDisposable)) {
                        $this->addFlash('error', "N'utilise pas d'email jetable");
                        return $this->redirectToRoute('editprofilWarzone');
                    }
                  // vérification Token
                    $token = new TokenEmail();

                    $nanoId = new Client();
                    $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
                    $token->setToken($chain);

                    $repository = $this->getDoctrine()->getRepository(User::class);
                    $userId = $repository->findOneBy(['id' => $user]);
                    $token->setUser($userId);

                  // vérification IP User pour token
                    $token->setIpUser($this->ipService->getUserIp());

                    $token->setEmail($emailUser);

                    $token->setIsUse('0');

                    $token->setDateCreate(new \DateTime('now'));

                    $this->em->persist($token);
                    $this->em->flush();

                  // ***

                  // Envoie Email de validation
                    $subject = 'Vérification email';
                    $content = $this->twig->render('Mail/email_updated.html.twig', [
                    'token' => $chain,
                    'user' => $user,
                    ]);
                    $this->sendEmail->sendEmail($mailer, $emailUser, $subject, $content);
                  //

                    $this->addFlash('success', "Tu as reçu un lien d'activation par email, la modification auras lieu quand tu auras valider ce lien");
                    return $this->redirectToRoute('profil');
                } else {
                    $this->addFlash("error", "Email déjà utiliser");
                    return $this->redirectToRoute('editprofil');
                }
            }
        }

        if ($form3->isSubmitted() && $form3->isValid()) {
            $password = $form3->get('password')->getData();
            if (!empty($password)) {
                $user->setPassword($encoder->encodePassword($user, $password));
            }

            $this->addFlash('success', 'Ton mot de passe à été modifié');

            $this->em->flush();
            return $this->redirectToRoute('profil');
        }

        if ($form1->isSubmitted() && $form1->isValid()) {
          // **
            $trn = $form1->get('trn')->getData();

            $this->em->flush();

            $this->addFlash('success', 'Ton profil à été modifié');

            if (!empty($trn)) {
                $user->setTrn($trn);

                $pseudo = $user->getPseudo();

                $response = $this->forward('App\Controller\ApiController::xsrfUpdate', [
                    'pseudo'  => $pseudo,
                ]);
                        
                return $response;
            }
            return $this->redirectToRoute('profil');
        }

        return new Response($this->twig->render('User/editprofil.html.twig', [
          'form1' => $form1->createView(),
          'form2' => $form2->createView(),
          'form3' => $form3->createView()
        ]));
    }

    public function resendemail(MailerInterface $mailer, AuthenticationUtils $authentificationUtils, Request $request, UserInterface $user): Response
    {
              $repository = $this->getDoctrine()->getRepository(TokenEmail::class);
              $tokens = $repository->findValidToken($user);


        if (count($tokens) >= 3) {
            $minDateToken = min(array_column($tokens, 'dateCreate'));
            $minDateToken->add(new \DateInterval('PT1H'));
            $datetime = new \DateTime();
            $timeLeft = $datetime->diff($minDateToken);

            $this->addFlash('error', 'Tu as effectuer trop de demandes, soit plus patient ' . $timeLeft->i . ' minutes et ' . $timeLeft->s . ' secondes');
            return $this->redirectToRoute('profilWarzone');
        } else {
            $token = new TokenEmail();

            $nanoId = new Client();
            $chain = $nanoId->generateId($size = 21, $mode = Client::MODE_DYNAMIC);
            $token->setToken($chain);

            $repository = $this->getDoctrine()->getRepository(User::class);
            $userId = $repository->findOneBy(['id' => $user]);
            $token->setUser($userId);

          // vérification IP User pour token
            $token->setIpUser($this->ipService->getUserIp());

            $token->setIsUse('0');

            $token->setDateCreate(new \DateTime('now'));

            $this->em->persist($token);
            $this->em->flush();
          // ***

          // Envoie Email de validation
            $subject = 'Validation email';
            $content = $this->twig->render('Mail/user_registered.html.twig', [
            'token' => $chain,
            'user' => $user,
                  ]);
            $this->sendEmail->sendEmail($mailer, $user->getUsername(), $subject, $content);
          //

            $this->addFlash('success', "Tu as reçu un lien d'activation par mail");
            return $this->redirectToRoute('profilWarzone');
        }
    }

    public function searchUser(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $requestString = $request->get('q');
        $repository = $this->getDoctrine()->getRepository(User::class);
        $entities = $repository->findEntitiesByString($requestString);

        if (!$entities) {
            $result['entities']['error'] = "Aucun utilisateur trouver";
        } else {
            $result['entities'] = $this->getRealEntities($entities);
        }

        return new Response(json_encode($result));
    }

    public function getRealEntities($entities)
    {

        foreach ($entities as $entity) {
            $realEntities[$entity->getId()] = $entity->getPseudo();
        }

        return $realEntities;
    }
}
