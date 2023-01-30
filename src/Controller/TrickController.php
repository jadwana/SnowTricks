<?php

namespace App\Controller;

use App\Entity\Tricks;
use App\Entity\Comments;
use App\Form\CommentFormType;
use App\Repository\TricksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrickController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(TricksRepository $tricksRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'tricks' => $tricksRepository->findAll()
        ]);
    }

    #[Route('/ajout-figure', name: 'add_trick')]
    public function addTrick(): Response
    {

        // $this->denyAccessUnlessGranted('ROLE_USER');
        return $this->render('home/add_trick.html.twig', [
            
        ]);
    }


    #[Route('/{slug}', name: 'app_trick')]
    public function index(Tricks $tricks, Request $request, EntityManagerInterface $entityManager): Response
    {
       

        $user = $this->getUser();
        //on verifie que l'utilisateur est logué et qu'il a validé son compte pour accéder au formulaire d'ajout de commentaire
        if($user && $user->getIsVerified()){
            $comment = new Comments();
            $form = $this->createForm(CommentFormType::class, $comment);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()){
                $comment = $form->getData();
                $comment->setAuthor($user);

            $comment->setTrick($tricks);

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'commentaire ajoué avec succès');

            return $this->redirectToRoute('app_trick', ['slug' => $tricks->getSlug()]);

            
            }
            return $this->render('trick/index.html.twig', [
                'trick' => $tricks,
                'form' => $form->createView()
            ]);
        }  

        return $this->render('trick/index.html.twig', [
            'trick' => $tricks
        ]);
        
    }
}
