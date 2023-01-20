<?php

namespace App\Controller;

use App\Repository\TricksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(TricksRepository $tricksRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'tricks' => $tricksRepository->findAll()
        ]);
    }
}
