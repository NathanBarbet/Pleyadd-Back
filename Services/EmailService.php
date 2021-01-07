<?php

namespace App\Services;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{

    public function sendEmail($mailer, $emailUser, $subject, $content)
    {
        $email = (new Email())
        ->from('***********')
        ->to($emailUser)
        ->subject($subject)
        ->text($content)
        ->html($content);

        /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
        $sentEmail = $mailer->send($email);
        // $messageId = $sentEmail->getMessageId();
    }

    public function sendContact($mailer, $emailUser, $subject, $content)
    {
        $email = (new Email())
        ->from($emailUser)
        ->to('**************')
        ->subject($subject)
        ->text($content)
        ->html($content);

        /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
        $sentContact = $mailer->send($email);
        // $messageId = $sentEmail->getMessageId();
    }

    public function sendInscription($mailer, $email, $subject, $content)
    {
        $email = (new Email())
        ->from($email)
        ->to('**************')
        ->subject($subject)
        ->text($content)
        ->html($content);

        /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
        $sentContact = $mailer->send($email);
        // $messageId = $sentEmail->getMessageId();
    }
}
