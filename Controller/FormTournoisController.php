<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;
use App\Form\FormTournoisType;
use App\Services\EmailService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use ReCaptcha\ReCaptcha;

class FormTournoisController extends AbstractController
{
    
    private $twig;
    private $sendContact;

    public function __construct(Environment $twig, EmailService $sendContact)
    {
        $this->twig = $twig;
        $this->sendContact = $sendContact;
    }

    public function inscription(MailerInterface $mailer, Request $request): Response
    {
        return new Response($this->twig->render('Tournois/Resultats/resultatsmk4.html.twig'));
        /*$form = $this->createForm(FormTournoisType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptcha = new ReCaptcha('**********************************');
            $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());
            if (!$resp->isSuccess()) {
                $this->addFlash('error', "Vous devez valider le captcha");
                return $this->redirectToRoute('formTournoisInscription');
            }
            // Envoie Email de validation
            $nomEquipe = $form->get('nomEquipe')->getData();

            $email = $form->get('email')->getData();
            $domain = explode("@", $email);

            if ($domain[1] == 'gmail.com') {
                $pseudo = $form->get('pseudo')->getData();
                $ID1 = $form->get('ID1')->getData();
                $plateformeID1 = $form->get('plateformeID1')->getData();
                $ID2 = $form->get('ID2')->getData();
                $plateformeID2 = $form->get('plateformeID2')->getData();
                $ID3 = $form->get('ID3')->getData();
                $plateformeID3 = $form->get('plateformeID3')->getData();
                $ID4 = $form->get('ID4')->getData();
                $plateformeID4 = $form->get('plateformeID4')->getData();

                $subject = 'Inscription MK4 Quad 21/11 : ' . $nomEquipe;
                $content = $this->twig->render('Tournois/inscription_mail.html.twig', [
                    'nomEquipe' => $nomEquipe,
                    'email' => $email,
                    'pseudo' => $pseudo,
                    'ID1' => $ID1,
                    'plateformeID1' => $plateformeID1,
                    'ID2' => $ID2,
                    'plateformeID2' => $plateformeID2,
                    'ID3' => $ID3,
                    'plateformeID3' => $plateformeID3,
                    'ID4' => $ID4,
                    'plateformeID4' => $plateformeID4,
                    ]);
                $this->sendContact->sendInscription($mailer, $email, $subject, $content);
                //

                $this->addFlash('success', "Merci pour votre inscription, vous recevrez un mail et une confirmation via MP Discord d'ici 24/48h !");
                return $this->redirectToRoute('index');
            } else {
                $this->addFlash('error', "Vous devez utiliser une adresse Gmail");
                return $this->redirectToRoute('formTournoisInscription');
            }
        } else {
            return new Response($this->twig->render('Tournois/inscription.html.twig', [
            'form' => $form->createView(),
            ]));
        }*/
    }

    //* Display Resultats Confrerie
    public function resultatsconfrerie(): Response
    {
        return new Response($this->twig->render('Tournois/Resultats/resultatsconfrerie.html.twig'));
    }

    //* Display Resultats MK1
    public function resultatsmk1(): Response
    {
        return new Response($this->twig->render('Tournois/Resultats/resultatsmk1.html.twig'));
    }

    //* Display Resultats DUODUO1
    public function resultatsduoduo1(): Response
    {
        return new Response($this->twig->render('Tournois/Resultats/resultatsduoduo1.html.twig'));
    }

    //* Display Resultats MK2
    public function resultatsmk2(): Response
    {
        return new Response($this->twig->render('Tournois/Resultats/resultatsmk2.html.twig'));
    }

    //* Display Resultats MK3
    public function resultatsmk3(): Response
    {
        return new Response($this->twig->render('Tournois/Resultats/resultatsmk3.html.twig'));
    }
    
    //* Display Resultats MK4
    public function resultatsmk4(): Response
    {
        return new Response($this->twig->render('Tournois/Resultats/resultatsmk4.html.twig'));
    }
}
