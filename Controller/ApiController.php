<?php

namespace App\Controller;

use Psr\Http\Client\ClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Twig\Environment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;

class ApiController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function xsrf()
    {

        $client = new Client(['cookies' => true]);
        $response = $client->request('GET', 'https://profile.callofduty.com/login');

        $jar = $client->getConfig('cookies');
        $xsrf = $jar->cookies[0]->data['Value'];

        return $this->connect($xsrf, $client);
    }

    public function connect($xsrf, $client)
    {
        $response = $client->request('POST', 'https://profile.callofduty.com/do_login?new_SiteId=cod', [
            'form_params' => [
                'username' => '***********',
                'password' => '***********',
                'remember_me' => 'true',
                '_csrf' => $xsrf
            ]
        ]);

        return $this->request($client);
    }

    public function request($client)
    {
        $repository = $this->getDoctrine()->getRepository(User::class);
        $pseudos = $repository->getPseudoForApiStats();

        for ($i = 0; $i < count($pseudos); $i++) {
            $repository = $this->getDoctrine()->getRepository(User::class);
            $user = $repository->FindOneBy(['pseudo' => $pseudos[$i]['pseudo']]);

            $plateforme = $user->getWarzonePlateforme();
            $trn = str_replace('#', '%23', $user->getTrn());

            $response = $client->request('GET', 'https://my.callofduty.com/api/papi-client/stats/cod/v1/title/mw/platform/' . $plateforme . '/gamer/' . $trn . '/profile/type/wz', [
                'debug' => true
            ]);
            
            $data = json_decode($response->getBody());
            
            if ($data->status == 'error') {
            } else {
                $user->setWarzoneWins($data->data->lifetime->mode->br->properties->wins);
                $user->setWarzoneKills($data->data->lifetime->mode->br->properties->kills);
                $user->setWarzoneKdratio($data->data->lifetime->mode->br->properties->kdRatio);
                $user->setWarzoneGamesplayed($data->data->lifetime->mode->br->properties->gamesPlayed);
            }

            $this->em->flush();
        }

        die();
    }

    public function xsrfUpdate($pseudo)
    {

        $client = new Client(['cookies' => true]);
        $response = $client->request('GET', 'https://profile.callofduty.com/login');

        $jar = $client->getConfig('cookies');
        $xsrf = $jar->cookies[0]->data['Value'];

        return $this->connectUpdate($xsrf, $client, $pseudo);
    }

    public function connectUpdate($xsrf, $client, $pseudo)
    {
        $response = $client->request('POST', 'https://profile.callofduty.com/do_login?new_SiteId=cod', [
            'form_params' => [
                'username' => '**********',
                'password' => '**********',
                'remember_me' => 'true',
                '_csrf' => $xsrf
            ]
        ]);

        return $this->requestUpdate($client, $pseudo);
    }

    public function requestUpdate($client, $pseudo): Response
    {
        $repository = $this->getDoctrine()->getRepository(User::class);
        $user = $repository->FindOneBy(['pseudo' => $pseudo]);

        $plateforme = $user->getWarzonePlateforme();
        $trn = str_replace('#', '%23', $user->getTrn());

        $response = $client->request('GET', 'https://my.callofduty.com/api/papi-client/stats/cod/v1/title/mw/platform/' . $plateforme . '/gamer/' . $trn . '/profile/type/wz');
            
        $data = json_decode($response->getBody());
            
        if ($data->status == 'error') {
            $this->addFlash('error', "ID Activision invalide");

            return $this->redirectToRoute('editprofil');
        } elseif ($data->data->lifetime->mode->br->properties->kdRatio == '0' or $data->data->lifetime->mode->br->properties->gamesPlayed == '0') {
            $this->addFlash('error', "Ton compte Activision ne doit pas être en privé");

            return $this->redirectToRoute('editprofil');
        } else {
            $user->setWarzoneWins($data->data->lifetime->mode->br->properties->wins);
            $user->setWarzoneKills($data->data->lifetime->mode->br->properties->kills);
            $user->setWarzoneKdratio($data->data->lifetime->mode->br->properties->kdRatio);
            $user->setWarzoneGamesplayed($data->data->lifetime->mode->br->properties->gamesPlayed);

            $this->em->flush();

            return $this->redirectToRoute('profil');
        }
    }

    public function xsrfRegister($trn, $plateforme, $user)
    {

        $client = new Client(['cookies' => true]);
        $response = $client->request('GET', 'https://profile.callofduty.com/login');

        $jar = $client->getConfig('cookies');
        $xsrf = $jar->cookies[0]->data['Value'];

        return $this->connectRegister($xsrf, $client, $trn, $plateforme, $user);
    }

    public function connectRegister($xsrf, $client, $trn, $plateforme, $user)
    {
        $response = $client->request('POST', 'https://profile.callofduty.com/do_login?new_SiteId=cod', [
            'form_params' => [
                'username' => '************',
                'password' => '************',
                'remember_me' => 'true',
                '_csrf' => $xsrf
            ]
        ]);

        return $this->requestRegister($client, $trn, $plateforme, $user);
    }

    public function requestRegister($client, $trn, $plateforme, $user): Response
    {
        $trn2 = str_replace('#', '%23', $trn);

        $response = $client->request('GET', 'https://my.callofduty.com/api/papi-client/stats/cod/v1/title/mw/platform/' . $plateforme . '/gamer/' . $trn2 . '/profile/type/wz');
            
        $data = json_decode($response->getBody());
            
        if ($data->status == 'error') {
            $this->addFlash('error', "ID Activision invalide");

            return $this->redirectToRoute('register');
        } else {
            $user->setWarzoneWins($data->data->lifetime->mode->br->properties->wins);
            $user->setWarzoneKills($data->data->lifetime->mode->br->properties->kills);
            $user->setWarzoneKdratio($data->data->lifetime->mode->br->properties->kdRatio);
            $user->setWarzoneGamesplayed($data->data->lifetime->mode->br->properties->gamesPlayed);

            $this->addFlash('success', "Tu as reçu un lien d'activation par email");

            $this->em->persist($user);
            $this->em->flush();

            return $this->redirectToRoute('login');
        }
    }


    public function test()
    {

        $client = new Client(['cookies' => true]);
        $response = $client->request('GET', 'https://profile.callofduty.com/login');

        $jar = $client->getConfig('cookies');
        $xsrf = $jar->cookies[0]->data['Value'];

        return $this->connecttest($xsrf, $client);
    }

    public function connecttest($xsrf, $client)
    {
        $response = $client->request('POST', 'https://profile.callofduty.com/do_login?new_SiteId=cod', [
            'form_params' => [
                'username' => '************',
                'password' => '************',
                'remember_me' => 'true',
                '_csrf' => $xsrf
            ]
        ]);

        return $this->requesttest($client);
    }

    public function requesttest($client)
    {
        // test user
        $response = $client->request('GET', 'https://my.callofduty.com/api/papi-client/stats/cod/v1/title/mw/platform/battle/gamer/******/profile/type/wz', [
            'debug' => true
        ]);

        // test partie
        /*$response = $client->request('GET', 'https://my.callofduty.com/api/papi-client/crm/cod/v2/title/mw/platform/battle/gamer//matches/wz/start/0/end/0/details', [
            'debug' => true
        ]);*/

        // test match ID
        /*$response = $client->request('GET', 'https://www.callofduty.com/api/papi-client/crm/cod/v2/title/mw/platform/battle/fullMatch/wz/5436777235416627980/it', [
            'debug' => true
        ]);*/
            
        $data = json_decode($response->getBody());
        dump($data);
        die();
    }
}
