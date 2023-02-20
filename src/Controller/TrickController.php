<?php

namespace App\Controller;

use App\Entity\Medias;
use App\Entity\Tricks;
use App\Entity\Videos;
use App\Entity\Comments;
use App\Form\CommentFormType;
use App\Form\AddTrickFormType;
use App\Service\PictureService;
use App\Repository\TricksRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;

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
    public function addTrick(
        Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, 
        PictureService $pictureService): Response
    {

        $this->denyAccessUnlessGranted('ROLE_USER');

        //on créé une nvelle figure
        $trick = new Tricks();

        //on crée le formulaire
        $form = $this->createForm(AddTrickFormType::class, $trick);

        //on traite la requete du formulaire
        $form->handleRequest($request);

        //on vérifie que le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()){
            
            
            //on recupère les images
            $images = $form->get('images')->getData();
            
            foreach($images as $image){
                //on définie le dossier de destination
                $folder = 'tricks';

                // on appelle le service d'ajout
                $fichier = $pictureService->add($image, $folder, 300, 300);
                
                $img = new Medias();
                $img->setPath($fichier);
                $img->setType('picture');
                $img->setMain(0);
                $trick->addMedias($img);


            }
            
            //on récupère les videos
            foreach($trick->getVideos() as $video){
                $video->setTricks($trick);
            }

            // on génère le slug
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);

            //on stocke
            $entityManager->persist($trick);
            $entityManager->flush();

            $this->addFlash('success', 'figure ajoutée avec succès');

            //on redirige
            return $this->redirectToRoute('app_home');
        }
        
        return $this->render('home/add_trick.html.twig', [
            'form' =>$form->createView()
        ]);
    }

    #[Route('/image-principale/{id}', name: 'main_picture')]
    public function mainPicture(Medias $medias, EntityManagerInterface $entityManager)
    {
        $params = ['id' =>$medias->getTricks()->getId()];
        $trickmedias = $medias->getTricks()->getMedias();
        foreach($trickmedias as $media){
            $media->setMain(0);
        }

        $medias->setMain(1);
        $entityManager->persist($medias);
        $entityManager->flush();

        return $this->redirectToRoute('edit_trick', $params);
    }

    #[Route('/suppression-figure/{id}', name: 'delete_trick')]
    public function delete(Tricks $trick, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        //on verifie si l'utilisateur peut supprimer  avec le voter
        $this->denyAccessUnlessGranted('TRICK_DELETE', $trick);

        foreach ($trick->getMedias() as $media){
            $name = $media->getPath();
            $pictureService->delete($name, 'tricks', 300, 300);
        }

        $entityManager->remove($trick);
        $entityManager->flush();

        return $this->redirectToRoute('app_home');
    }

    #[Route('/suppression-video/{id}', name: 'delete_video')]
    

    public function deleteVideo(Videos $videos, EntityManagerInterface $entityManager, Request $request ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $params = ['id' =>$videos->getTricks()->getId()];
        // on récupère le contenu de la requete
        // $data = json_decode($request->getContent(), true);

        // on vérifie le token
        // if($this->isCsrfTokenValid('delete' .$videos->getId(), $data['_token'])){
            // le token csrf est valide
            $entityManager->remove($videos);
            $entityManager->flush();

            return $this->redirectToRoute('edit_trick',$params);
            // return new JsonResponse(['success' => true], 200);
            // }

        // return new JsonResponse(['error' => 'Token invalide'], 400);
    }

    #[Route('/suppression-image/{id}', name: 'delete_image', methods:['DELETE'])]

    public function deleteImage(
        Medias $media, Request $request, EntityManagerInterface $entityManagerInterface, 
        PictureService $pictureService): JsonResponse
    {
        // on récupère le contenu de la requete
        $data = json_decode($request->getContent(), true);

        // on vérifie le token
        if($this->isCsrfTokenValid('delete' .$media->getId(), $data['_token'])){
            // le token csrf est valide
            // on récupère le nom de l'image
            $name = $media->getPath();

            if($pictureService->delete($name, 'tricks', 300, 300)){
                // on supprime l'image de la base de donnée
                $entityManagerInterface->remove($media);
                $entityManagerInterface->flush();

                return new JsonResponse(['success' => true], 200);
            }
            // la suppression n'a pas focntionnée
            return new JsonResponse(['error' => 'erreur de suppression']);
        }

        return new JsonResponse(['error' => 'Token invalide'], 400);
    }


    #[Route('/modification-figure/{slug}', name: 'edit_trick')]
    public function edit(Tricks $trick, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, PictureService $pictureService): Response
    {
        //on verifie si l'utilisateur peut éditer avec le voter
        $this->denyAccessUnlessGranted('TRICK_EDIT', $trick);

        //on crée le formulaire
        $form = $this->createForm(AddTrickFormType::class, $trick);

        //on traite la requete du formulaire
        $form->handleRequest($request);

        //on vérifie que le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()){
            

            //on recupère les images
            $images = $form->get('images')->getData();
            
            foreach($images as $image){
                //on définie le dossier de destination
                $folder = 'tricks';

                // on appelle le service d'ajout
                $fichier = $pictureService->add($image, $folder, 300, 300);
                
                $img = new Medias();
                $img->setPath($fichier);
                $img->setType('picture');
                $img->setMain(0);
                $trick->addMedias($img);

            }

            //on ajoute les videos
            foreach($trick->getVideos() as $video){
               
                $video->setTricks($trick);
            }

            

            // on génère le slug
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);

            //on met à jour la date de modification
            $trick->setUpdatedAt(new \DateTimeImmutable());

            //on stocke
            $entityManager->persist($trick);
            $entityManager->flush();

            $this->addFlash('success', 'figure modifiée avec succès');

            //on redirige
            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/edit_trick.html.twig', [
            'form' => $form->createView(),
            'trick' => $trick
        ]);
    }

    #[Route('/{slug}', name: 'app_trick')]
    // #[Security("is_granted('ROLE_USER')")]
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
            
            $this->addFlash('success', 'commentaire ajouté avec succès');

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



    #[Route('/trick/{id}/comments/more/{offset}', name: 'more_comments')]
    public function loadMoreComments( Tricks $tricks, $offset)
    {
        $html = $this->renderView('trick/_comments.html.twig', [
            'trick' => $tricks,
        ]);
        return new JsonResponse(['html' => $html]);
    }
}
