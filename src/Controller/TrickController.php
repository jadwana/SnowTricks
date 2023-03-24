<?php

namespace App\Controller;

use App\Entity\Medias;
use App\Entity\Tricks;
use App\Entity\Videos;
use App\Entity\Comments;
use App\Form\CommentFormType;
use App\Form\AddTrickFormType;
use App\Repository\CommentsRepository;
use App\Service\PictureService;
use App\Repository\TricksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
    public function addTrick(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        PictureService $pictureService
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_USER');

        //on créé une nvelle figure
        $trick = new Tricks();

        //on crée le formulaire
        $form = $this->createForm(AddTrickFormType::class, $trick);

        //on traite la requete du formulaire
        $form->handleRequest($request);

        //on vérifie que le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()) {

            //on recupère les images
            $images = $form->get('images')->getData();

            foreach ($images as $image) {
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
            foreach ($trick->getVideos() as $video) {

                $lien = explode("/", $video->getLink());

                if ($lien[2] == "www.youtube.com") {
                    $codeProv = $lien[3];
                    $code = str_replace("watch?v=", "", $codeProv);
                    $link = "https://www.youtube.com/embed/" . $code;
                } else if ($lien[2] == "youtu.be") {
                    $code = $lien[3];
                    $link = "https://www.youtube.com/embed/" . $code;
                } else if ($lien[2] == "dai.ly") {
                    $code = $lien[3];
                    $link = "https://www.dailymotion.com/embed/video/" . $code;
                } else if ($lien[2] == "www.dailymotion.com") {
                    $code = $lien[4];
                    $link = "https://www.dailymotion.com/embed/video/" . $code;
                } else {
                    throw new \Exception('adresse incorrecte');;
                }
                $video->setLink($link);

                $video->setTricks($trick);
            }

            // on génère le slug
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);

            //on stocke
            $entityManager->persist($trick);
            $entityManager->flush();

            $this->addFlash('success', 'Figure ajoutée avec succès');

            //on redirige
            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/add_trick.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/image-principale/{id}', name: 'main_picture')]
    public function mainPicture(Medias $medias, EntityManagerInterface $entityManager)
    {
        $params = ['slug' => $medias->getTricks()->getSlug()];
        $trickmedias = $medias->getTricks()->getMedias();
        foreach ($trickmedias as $media) {
            $media->setMain(0);
        }

        $medias->setMain(1);
        $entityManager->persist($medias);
        $entityManager->flush();

        return $this->redirectToRoute('edit_trick', $params);
    }

    #[Route('/suppression-figure/{slug}', name: 'delete_trick')]
    public function delete(Tricks $trick, EntityManagerInterface $entityManager, PictureService $pictureService): Response
    {
        //on verifie si l'utilisateur peut supprimer  avec le voter
        $this->denyAccessUnlessGranted('TRICK_DELETE', $trick);

        foreach ($trick->getMedias() as $media) {
            $name = $media->getPath();
            $pictureService->delete($name, 'tricks', 300, 300);
        }

        $entityManager->remove($trick);
        $entityManager->flush();

        return $this->redirectToRoute('app_home');
    }

    #[Route('/suppression-video/{id}', name: 'delete_video')]
    public function deleteVideo(Videos $videos, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $params = ['slug' => $videos->getTricks()->getSlug()];

        $entityManager->remove($videos);
        $entityManager->flush();

        return $this->redirectToRoute('edit_trick', $params);
    }

    #[Route('/suppression-image/{id}', name: 'delete_image', methods: ['DELETE'])]

    public function deleteImage(
        Medias $media,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
        PictureService $pictureService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');
        // on récupère le contenu de la requete
        $data = json_decode($request->getContent(), true);

        // on vérifie le token
        if ($this->isCsrfTokenValid('delete' . $media->getId(), $data['_token'])) {
            // le token csrf est valide
            // on récupère le nom de l'image
            $name = $media->getPath();

            if ($pictureService->delete($name, 'tricks', 300, 300)) {
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
    public function edit(Tricks $trick, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, PictureService $pictureService, ValidatorInterface $validator): Response
    {
        //on verifie si l'utilisateur peut éditer avec le voter
        $this->denyAccessUnlessGranted('TRICK_EDIT', $trick);

        //on crée le formulaire
        $form = $this->createForm(AddTrickFormType::class, $trick);

        //on traite la requete du formulaire
        $form->handleRequest($request);

        //on vérifie que le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()) {


            //on recupère les images
            $images = $form->get('images')->getData();

            foreach ($images as $image) {
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
            foreach ($trick->getVideos() as $video) {
                // on valide que c'est bien une url
                $errors = $validator->validate($video);
                if (count($errors) > 0) {
                    $errorsString = (string) $errors;
                    throw new \Exception($errorsString);

                    // return new Response($errorsString);
                }

                //return new Response('The video is valid! Yes!');

                // on modifie le lien 
                $lien = explode("/", $video->getLink());
                if ($lien[3] == "embed") {
                    $link = $video->getlink();
                } else if ($lien[2] == "www.youtube.com") {
                    $codeProv = $lien[3];
                    $code = str_replace("watch?v=", "", $codeProv);
                    $link = "https://www.youtube.com/embed/" . $code;
                } else if ($lien[2] == "youtu.be") {
                    $code = $lien[3];
                    $link = "https://www.youtube.com/embed/" . $code;
                } else if ($lien[2] == "dai.ly") {
                    $code = $lien[3];
                    $link = "https://www.dailymotion.com/embed/video/" . $code;
                } else if ($lien[2] == "www.dailymotion.com") {
                    $code = $lien[4];
                    $link = "https://www.dailymotion.com/embed/video/" . $code;
                } else {
                    throw new \Exception('adresse incorrecte');
                }
                $video->setLink($link);

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

            $this->addFlash('success', 'Figure modifiée avec succès');

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
    public function index(Tricks $tricks, Request $request, EntityManagerInterface $entityManager, CommentsRepository $commentsRepository): Response
    {
        // on va chercher le numéro de page dans l'url
        $page = $request->query->getInt('page', 1);

        $comments = $commentsRepository->findCommentsPaginated($page, $tricks->getSlug(), 5);

        $user = $this->getUser();
        //on verifie que l'utilisateur est logué et qu'il a validé son compte pour accéder au formulaire d'ajout de commentaire
        if ($user && $user->getIsVerified()) {
            $comment = new Comments();
            $form = $this->createForm(CommentFormType::class, $comment);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {

                $comment = $form->getData();
                $comment->setAuthor($user);

                $comment->setTrick($tricks);

                $entityManager->persist($comment);
                $entityManager->flush();

                $this->addFlash('success', 'Commentaire ajouté avec succès');

                return $this->redirectToRoute('app_trick', ['slug' => $tricks->getSlug()]);
            }


            return $this->render('trick/index.html.twig', [
                'trick' => $tricks,
                'comments' => $comments,
                'form' => $form->createView(),

            ]);
        }

        return $this->render('trick/index.html.twig', [
            'trick' => $tricks,
            'comments' => $comments,
        ]);
    }



    #[Route('/tricks/more/{offset}', name: 'more_tricks')]
    public function loadMoreTricks(TricksRepository $tricksRepository, $offset)
    {
        $html = $this->renderView('home/_more_tricks.html.twig', [
            'tricks' => $tricksRepository->findAll(),
            'offset' => $offset
        ]);
        return new JsonResponse(['html' => $html]);
    }
}
