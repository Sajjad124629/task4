<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\LoginFormType;
use App\Form\RegistrationFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\UserMailer;

class AuthController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $form = $this->createForm(LoginFormType::class, [
            'email' => $lastUsername ?? ''
        ]);

        return $this->render('pages/auth/login.html.twig', [
            'loginForm' => $form->createView(),
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Security $security, UserMailer $userMailer): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->render('pages/auth/register.html.twig', [
                    'registrationForm' => $form->createView()
                ]);
            }
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $user->getPassword())
            );
            try {
                $entityManager->persist($user);
                $entityManager->flush();

                $userMailer->sendConfirmationEmail($user);
                $security->login($user);

                $this->addFlash('success', 'Registration successful! Please check your email to confirm your account.');
                return $this->redirectToRoute('app_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Registration Error: ' . $e->getMessage());
            }
        }

        return $this->render('pages/auth/register.html.twig', [
            'registrationForm' => $form->createView()
        ]);
    }

    #[Route(path: '/verify/email/{id}/{hash}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyUserEmail(int $id, string $hash, EntityManagerInterface $entityManager): Response
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('app_login');
        }

        $expectedHash = hash_hmac('sha256', $user->getEmail(), $this->getParameter('kernel.secret'));

        if (!hash_equals($expectedHash, $hash)) {
            $this->addFlash('error', 'Invalid verification link.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->getStatus() === User::STATUS_UNVERIFIED) {
            $user->setStatus(User::STATUS_ACTIVE);
            $entityManager->flush();
            $this->addFlash('success', 'Your email address has been verified.');
        } elseif ($user->getStatus() === User::STATUS_ACTIVE) {
            $this->addFlash('info', 'Your email address is already verified.');
        } elseif ($user->getStatus() === User::STATUS_BLOCKED) {
            // Nota bene: Clicking the link in the e-mail changes the status from "unverified" to "active" ("blocked" stays "blocked").
            $this->addFlash('error', 'Your account is blocked.');
        }

        return $this->redirectToRoute('app_login');
    }
}
