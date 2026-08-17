<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router,
        private ParameterBagInterface $params
    ) {}

    public function sendConfirmationEmail(User $user): void
    {
        $secret = $this->params->get('kernel.secret');
        $hash = hash_hmac('sha256', $user->getEmail(), $secret);
        
        $url = $this->router->generate('app_verify_email', ['id' => $user->getId(), 'hash' => $hash], UrlGeneratorInterface::ABSOLUTE_URL);

        $fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'sajjadhossainridoy83@gmail.com';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Task 4';

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, $fromName))
            ->to($user->getEmail())
            ->subject('Welcome to Task 4 - Please Confirm your Email')
            ->text("Welcome, " . $user->getFullname() . "!\n\nPlease confirm your email address by clicking the link below:\n\n" . $url)
            ->htmlTemplate('emails/confirmation_email.html.twig')
            ->context([
                'user' => $user,
                'hash' => $hash
            ]);

        $this->mailer->send($email);
    }
}
