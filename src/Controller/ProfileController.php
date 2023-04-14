<?php

namespace App\Controller;

use App\Form\NewAvatarFormType;
use App\Service\PictureService;
use App\Form\NewPasswordFormType;
use App\Repository\UsersRepository;
use Symfony\Component\Form\FormError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends AbstractController
{
    #[Route('/mon_compte', name: 'app_profile')]

    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();
        if ($user->getIsVerified()) {
            return $this->render('profile/index.html.twig', [
                'user' => $user
            ]);
        }
        $this->addFlash('danger', 'Votre compte n\'est pas encore activé');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/mon_compte/nouveau_mot_de_passe', name: 'new_pass')]

    public function newPass(
        Request $request,
        UserPasswordHasherInterface $hacher,
        EntityManagerInterface $entityManager,
        UsersRepository $usersRepository
    ): Response {

        $user = $this->getUser();
        $this->denyAccessUnlessGranted('ROLE_USER');
        $form = $this->createForm(NewPasswordFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($hacher->isPasswordValid($user, $form->get('oldPassword')->getData())) {
                $user->setPassword(
                    $hacher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Votre mot de passe à bien été changé !');

                return $this->redirectToRoute('app_profile');
            } else {
                $form->addError(new FormError('Ancien mot de passe incorrect'));
            }
        }

        return $this->render('profile/new_pass.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/mon_compte/nouvel_avatar', name: 'new_avatar')]
    public function newAvatar(
        Request $request,
        EntityManagerInterface $entityManager,
        PictureService $pictureService
    ): Response {

        $user = $this->getUser();
        $this->denyAccessUnlessGranted('ROLE_USER');
        $form = $this->createForm(NewAvatarFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getAvatar() != "") {
                $name = $user->getAvatar();
                $pictureService->delete($name, 'avatars', 300, 300);
            }

            $avatar = $form->get('avatar')->getData();
            $folder = 'avatars';
            $file = $pictureService->add($avatar, $folder, 300, 300);
            $user->setAvatar($file);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Votre image d\'avatar à bien été changée !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/new_avatar.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
