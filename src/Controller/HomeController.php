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
        // Get last 5 uploaded public photos
        $recentPhotos = $photoRepository->findBy(
            ['viewPrivacy' => 'public'],
            ['uploadedAt' => 'DESC'],
            10
        );

        // Get all albums with their photos
        $albums = $albumRepository->findAllWithPhotoCount();

        return $this->render('home/index.html.twig', [
            'recentPhotos' => $recentPhotos,
            'albums' => $albums,
        ]);
    }

    #[Route('/album/{id}', name: 'app_album_view')]
    public function viewAlbum(int $id, AlbumRepository $albumRepository): Response
    {
        $album = $albumRepository->find($id);

        if (!$album) {
            throw $this->createNotFoundException('Album not found');
        }

        // Increment view count
        $album->incrementViewCount();
        $albumRepository->save($album, true);

        return $this->render('home/album.html.twig', [
            'album' => $album,
        ]);
    }

    #[Route('/photo/{id}', name: 'app_photo_view')]
    public function viewPhoto(int $id, PhotoRepository $photoRepository): Response
    {
        $photo = $photoRepository->find($id);

        if (!$photo) {
            throw $this->createNotFoundException('Photo not found');
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
