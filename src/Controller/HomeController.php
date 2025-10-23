<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        PhotoRepository $photoRepository,
        AlbumRepository $albumRepository
    ): Response {
        $user = $this->getUser();

        return $this->render('home/index.html.twig', [
            'albums' => $albumRepository->findAccessibleAlbums($user),
            'recentPhotos' => $photoRepository->findAccessiblePhotos($user),
        ]);
    }

    #[Route('/album/{id}', name: 'app_album_view')]
    public function viewAlbum(int $id, AlbumRepository $albumRepository): Response
    {
        $album = $albumRepository->find($id);

        if (!$album) {
            throw $this->createNotFoundException('Album not found');
        }

        // Check if user has required role
        if ($album->getRequiredRole() && !$this->isGranted($album->getRequiredRole())) {
            throw $this->createAccessDeniedException('You need special access to view this album.');
        }

        // Increment view count
        $album->incrementViewCount();
        $albumRepository->save($album, true);

        return $this->render('home/album.html.twig', [
            'album' => $album,
        ]);
    }

    #[Route('/photo/{uuid}', name: 'app_photo_view')]
    public function viewPhoto(string $uuid, PhotoRepository $photoRepository): Response
    {
        $photo = $photoRepository->findOneBy(['uuid' => $uuid, 'viewPrivacy' => 'public']);

        if (!$photo) {
            throw $this->createNotFoundException('Photo not found');
        }

        // Check if user has required role
        if ($photo->getRequiredRole() && !$this->isGranted($photo->getRequiredRole())) {
            throw $this->createAccessDeniedException('You need special access to view this photo.');
        }

        // Check privacy (for now, only show public photos)
        if ($photo->getViewPrivacy() !== 'public') {
            throw $this->createAccessDeniedException('This photo is not public');
        }

        // Increment view count
        $photo->incrementViewCount();
        $photoRepository->save($photo, true);

        return $this->render('home/photo.html.twig', [
            'photo' => $photo,
        ]);
    }
}
