<?php

namespace App\Controller;

use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/moncompte', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        return $this->render('profile/index.html.twig', [
            'user' => $user
        ]);
    }
}
