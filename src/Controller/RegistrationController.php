<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\JWTService;
use App\Service\SendMailService;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]

    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        SendMailService $mail,
        JWTService $jwt,
        UserAuthenticatorInterface $userAuthenticator,
        UsersAuthenticator $authenticator,
        PictureService $pictureService
    ): Response {

        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            // get avatar image
            $avatar = $form->get('avatar')->getData();
            if ($avatar) {
                $folder = 'avatars';
                $file = $pictureService->add($avatar, $folder, 300, 300);
                $user->setAvatar($file);
            }
            $entityManager->persist($user);
            $entityManager->flush();

            // we generate the JWT of the user
            // we create the header
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];
            // We create the Payload
            $payload = [
                'user_id' => $user->getId()
            ];
            // We generate the token
            $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

            // we send an email
            $mail->send(
                'no-reply@snowtricks.fr',
                $user->getEmail(),
                'Activation de votre compte sur le site SnowTricks',
                'register',
                compact('user', 'token')
            );
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]

    public function verifyUser(
        $token, JWTService $jwt, 
        UsersRepository $usersRepository, 
        EntityManagerInterface $em
        ): Response{

        // We check if the token is valid, has not expired and has not been modified
        if ($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))) {
            // We recover the payload
            $payload = $jwt->getPayload($token);
            // We retrieve the user of the token
            $user = $usersRepository->find($payload['user_id']);
            // We check that the user exists and has not yet activated his account
            if ($user && !$user->getIsVerified()) {
                $user->setIsVerified(true);
                $em->flush($user);
                $this->addFlash('success', 'Utilisatrice·eur activé·e');
                return $this->redirectToRoute('app_home');
            }
        }
        // Here a problem arises in the token
        $this->addFlash('danger', 'Le lien d\'activation est invalide ou a expiré');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoiverif', name: 'resend_verif')]

    public function resendVerif(JWTService $jwt, SendMailService $mail, UsersRepository $usersRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté·e pour accéder à cette page');
            return $this->redirectToRoute('app_login');
        }
        if ($user->getIsVerified()) {
            $this->addFlash('warning', 'Ce compte est déjà activé!');
            return $this->redirectToRoute('app_home');
        }
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        $payload = [
            'user_id' => $user->getId()
        ];
        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));
        $mail->send(
            'no-reply@snowtricks.fr',
            $user->getEmail(),
            'Activation de votre compte sur le site SnowTricks',
            'register',
            compact('user', 'token')
        );
        $this->addFlash('success', 'Email de vérification envoyé');
        return $this->redirectToRoute('app_home');
    }
}
