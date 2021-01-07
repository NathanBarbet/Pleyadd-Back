<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;
use App\Form\ContactType;
use App\Form\ContactPartenaireType;
use App\Services\EmailService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use ReCaptcha\ReCaptcha;

class ContactController extends AbstractController
{
    
    private $twig;
    private $sendContact;

    public function __construct(Environment $twig, EmailService $sendContact)
    {
        $this->twig = $twig;
        $this->sendContact = $sendContact;
    }

    public function contact(MailerInterface $mailer, Request $request): Response
    {
        $title = 'Contact';
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptcha = new ReCaptcha('**************************************');
            $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());
            if (!$resp->isSuccess()) {
                $this->addFlash('error', "Vous devez valider le captcha");
                if (isset($_POST)) {
                    return new Response($this->twig->render('contact.html.twig', [
                        'post' => $_POST,
                        'title' => $title,
                        'form' => $form->createView(),
                    ]));
                } else {
                    return $this->redirectToRoute('contact');
                }
            }
            // Envoie Email de validation
            $nameUser = $form->get('name')->getData();
            $firstnameUser = $form->get('firstname')->getData();
            $pseudoUser = $form->get('pseudo')->getData();
            $emailUser = $form->get('username')->getData();
            $sujet = $form->get('sujet')->getData();
            $texte = $form->get('texte')->getData();


            $subject = 'Contact : ' . $emailUser;
            $content = $this->twig->render('Mail/contact.html.twig', [
                'nameUser' => $nameUser,
                'firstnameUser' => $firstnameUser,
                'pseudoUser' => $pseudoUser,
                'emailUser' => $emailUser,
                'sujet' => $sujet,
                'texte' => $texte,
                ]);
            $this->sendContact->sendContact($mailer, $emailUser, $subject, $content);
            //

            $this->addFlash('success', "Ton message à bien été envoyer");
            return $this->redirectToRoute('contact');


            return new Response($this->twig->render('contact.html.twig', [
            'title' => $title,
            ]));
        } else {
            return new Response($this->twig->render('contact.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            ]));
        }
    }

    public function contactPartenaire(MailerInterface $mailer, Request $request): Response
    {
        $title = 'Devenir partenaire';
        $form = $this->createForm(ContactPartenaireType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recaptcha = new ReCaptcha('***********************************');
            $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());
            if (!$resp->isSuccess()) {
                $this->addFlash('error', "Vous devez valider le captcha");
                if (isset($_POST)) {
                    return new Response($this->twig->render('contactpartenaire.html.twig', [
                        'post' => $_POST,
                        'title' => $title,
                        'form' => $form->createView(),
                    ]));
                } else {
                    return $this->redirectToRoute('contactPartenaire');
                }
            }
            // Envoie Email de validation
            $nameUser = $form->get('name')->getData();
            $firstnameUser = $form->get('firstname')->getData();
            $raisonSociale = $form->get('raisonSociale')->getData();
            $emailUser = $form->get('username')->getData();
            $telephone = $form->get('telephone')->getData();
            $sujet = $form->get('sujet')->getData();
            $texte = $form->get('texte')->getData();


            $subject = 'Partenaire : ' . $raisonSociale;
            $content = $this->twig->render('Mail/contact_partenaire.html.twig', [
                'nameUser' => $nameUser,
                'firstnameUser' => $firstnameUser,
                'raisonSociale' => $raisonSociale,
                'emailUser' => $emailUser,
                'telephone' => $telephone,
                'sujet' => $sujet,
                'texte' => $texte,
                ]);
            $this->sendContact->sendContact($mailer, $emailUser, $subject, $content);
            //

            $this->addFlash('success', "Ton message à bien été envoyer");
            return $this->redirectToRoute('contactPartenaire');


            return new Response($this->twig->render('contactpartenaire.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            ]));
        } else {
            return new Response($this->twig->render('contactpartenaire.html.twig', [
            'title' => $title,
            'form' => $form->createView(),
            ]));
        }
    }
}
