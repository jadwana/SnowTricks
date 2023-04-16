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
use App\Service\VideoLinkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TrickController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(TricksRepository $tricksRepository): Response
    {
        return $this->render(
            'home/index.html.twig', [
            'tricks' => $tricksRepository->findAll()
            ]
        );
    }

    #[Route('/ajout-figure', name: 'add_trick')]
    public function addTrick(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        PictureService $pictureService,
        VideoLinkService $videoLinkService
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_USER');
        $trick = new Tricks();
        $form = $this->createForm(AddTrickFormType::class, $trick);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // We recover the images
            $images = $form->get('images')->getData();
            foreach ($images as $image) {
                // We define the destination folder
                $folder = 'tricks';
                // We call the add service
                $file = $pictureService->add($image, $folder, 300, 300);
                $img = new Medias();
                $img->setPath($file);
                $img->setMain(0);
                $trick->addMedias($img);
            }

            // We get the videos
            foreach ($trick->getVideos() as $video) {
                $link = $videoLinkService->checkLink($video);
                $video->setLink($link);
                $video->setTricks($trick);
            }

            // We generate the slug
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);

            $entityManager->persist($trick);
            $entityManager->flush();

            $this->addFlash('success', 'Figure ajoutée avec succès');

            return $this->redirectToRoute('app_home');
        }

        return $this->render(
            'home/add_trick.html.twig', [
            'form' => $form->createView()
            ]
        );
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
        // We check if the user can delete with the voter
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
        // Retrieve the content of the request
        $data = json_decode($request->getContent(), true);

        // We check the token
        if ($this->isCsrfTokenValid('delete' . $media->getId(), $data['_token'])) {
            // The csrf token is valid
            // We get the name of the image
            $name = $media->getPath();

            if ($pictureService->delete($name, 'tricks', 300, 300)) {
                // Delete the image from the database
                $entityManagerInterface->remove($media);
                $entityManagerInterface->flush();

                return new JsonResponse(['success' => true], 200);
            }
            // Deletion did not work
            return new JsonResponse(['error' => 'erreur de suppression']);
        }

        return new JsonResponse(['error' => 'Token invalide'], 400);
    }


    #[Route('/modification-figure/{slug}', name: 'edit_trick')]
    public function edit(
        Tricks $trick,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        PictureService $pictureService,
        VideoLinkService $videoLinkService
    ): Response {
        // We check if the user can edit with the voter
        $this->denyAccessUnlessGranted('TRICK_EDIT', $trick);

        $form = $this->createForm(AddTrickFormType::class, $trick);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $images = $form->get('images')->getData();
            // Adding pictures
            foreach ($images as $image) {
                $folder = 'tricks';
                $file = $pictureService->add($image, $folder, 300, 300);
                $img = new Medias();
                $img->setPath($file);
                $img->setMain(0);
                $trick->addMedias($img);
            }
            // Adding videos
            foreach ($trick->getVideos() as $video) {
                $link = $videoLinkService->checkLink($video);
                $video->setLink($link);
                $video->setTricks($trick);
            }
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);

            // Update the modification date
            $trick->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($trick);
            $entityManager->flush();

            $this->addFlash('success', 'Figure modifiée avec succès');

            return $this->redirectToRoute('app_home');
        }

        return $this->render(
            'home/edit_trick.html.twig', [
            'form' => $form->createView(),
            'trick' => $trick
            ]
        );
    }

    #[Route('/{slug}', name: 'app_trick')]
    public function index(
        Tricks $tricks,
        Request $request,
        EntityManagerInterface $entityManager,
        CommentsRepository $commentsRepository
    ): Response {
        // We will look for the page number in the url
        $page = $request->query->getInt('page', 1);

        $comments = $commentsRepository->findCommentsPaginated($page, $tricks->getSlug(), 10);

        $user = $this->getUser();
        // We check that the user is logged in and that he has validated his account to access the form to add a comment
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

            return $this->render(
                'home/trick.html.twig', [
                'trick' => $tricks,
                'comments' => $comments,
                'form' => $form->createView(),
                ]
            );
        }
        return $this->render(
            'home/trick.html.twig', [
            'trick' => $tricks,
            'comments' => $comments,
            ]
        );
    }

    #[Route('/tricks/more/{offset}', name: 'more_tricks')]
    public function loadMoreTricks(TricksRepository $tricksRepository, $offset)
    {
        $html = $this->renderView(
            'home/_more_tricks.html.twig', [
            'tricks' => $tricksRepository->findAll(),
            'offset' => $offset
            ]
        );
        return new JsonResponse(['html' => $html]);
    }
}
