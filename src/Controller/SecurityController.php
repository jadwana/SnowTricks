<?php

namespace App\Controller;

use App\Service\SendMailService;
use App\Form\ResetPasswordFormType;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route(path: '/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/oublipass', name: 'forgotten_password')]
    public function forgottenPassword(
        Request $request,
        UsersRepository $usersRepository,
        TokenGeneratorInterface $tokenGeneratorInterface,
        EntityManagerInterface $entityManagerInterface,
        SendMailService $mail
    ): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            //on va chercher l'utilisateur par son pseudo
            $user = $usersRepository->findOneByUsername($form->get('username')->getData());

            //on verifie si on a un utilisateur
            if($user){
                // if(!$user->getIsVerified()){
                // //le compte de l'utilisateur n'est pas vérifié
                // $this->addFlash('danger', 'Vous devez valider votre compte avant de pouvoir modifier votre mot de passe');
                // return $this->redirectToRoute('app_login');
                // }
             // on génère un token de réinitialisation
             $token = $tokenGeneratorInterface->generateToken();
             $user->setResetToken($token);
             $entityManagerInterface->persist($user);
             $entityManagerInterface->flush();

             //on génère un lien de réinitialisation du mot de passe
             $url = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
             
             //on crée les données du mail
             $context = compact('url', 'user');

             //envoi du mail
             $mail->send(
                 'no-reply@snowtricks.fr',
                 $user->getEmail(),
                 'Réinitialisation du mot de passe',
                 'password_reset',
                 $context
             );

             $this->addFlash('success', 'email envoyé avec succès');
             return $this->redirectToRoute('app_login');
         }
         //$user est nul
         $this->addFlash('danger', 'un problème est survenu');
         return $this->redirectToRoute('app_login');
            
        }

        return $this->render('security/reset_password_request.html.twig',[
            'requestPassForm' => $form->createView()
        ]);
    }

    #[Route(path: '/oublipass/{token}', name: 'reset_password')]
    public function resetPass(
        string $token,
        Request $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManagerInterface,
        UserPasswordHasherInterface $passwordHacher
    ): Response
    {
        // on vérifie si on a ce token dans la bdd
        $user = $usersRepository->findOneByResetToken($token);

        if($user){
            $form = $this->createForm(ResetPasswordFormType::class);

            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){
                //on efface le token
                $user->setResetToken('');
                $user->setPassWord(
                    $passwordHacher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $entityManagerInterface->persist($user);
                $entityManagerInterface->flush();

                $this->addFlash('success', 'Mot de passe modifié avec succès, vous pouvez maintenant vous connecter');
                return $this->redirectToRoute('app_home');
            }

            return $this->render('security/reset_password.html.twig', [
                'passForm' => $form->createView()
            ]);
        }
        $this->addFlash('danger', 'jeton invalide');
        return $this->redirectToRoute('app_login');
    }
}
