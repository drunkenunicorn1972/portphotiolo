<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\PhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HomeController extends AbstractController
{
    private string $userRole;

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
        // Initialize with default role
        $this->userRole = 'ROLE_USER';
    }

    #[Route('/', name: 'app_home')]
    public function index(
        PhotoRepository $photoRepository,
        AlbumRepository $albumRepository
    ): Response {
        $user = $this->getUser();
        if ($user) {
            $this->userRoles = $user->getRoles();
            $userRoles = $user ? $user->getRoles() : ['ROLE_USER'];
            $this->userRole = $userRoles[0]; // Gets the first role
        }

        return $this->render('home/index.html.twig', [
            'albums' => $albumRepository->findAccessibleAlbums($user),
            'recentPhotos' => $photoRepository->findAccessiblePhotos($user),
        ]);
    }



    #[Route('/album/{uuid}', name: 'app_album_view')]
    public function viewAlbum(string $uuid, AlbumRepository $albumRepository): Response
    {
        $user = $this->getUser();
        if ($user) {
            $userRoles = $user ? $user->getRoles() : ['ROLE_USER'];
            $this->userRole = $userRoles[0]; // Gets the first role
            if ($user->isAdmin()) {
                $this->userRole = 'ROLE_ADMIN';
            }
        }

//        $album = $albumRepository->findOneBy(['uuid' => $uuid, 'viewPrivacy' => 'public']);
        $album = $albumRepository->findOneBy(['uuid' => $uuid]);

        if (!$album) {
            throw $this->createNotFoundException('Album not found');
        }

        // Check privacy settings
        $viewPrivacy = $album->getViewPrivacy();

        // Check if user has required role
        if ($viewPrivacy !== 'public' && !$this->getUser()) {
            throw $this->createAccessDeniedException('You must be logged in to view this album.');
        } else {
            if ($this->userRole == 'ROLE_ADMIN' && !in_array($viewPrivacy, ['public', 'member', 'friend', 'family'])) {
                throw $this->createAccessDeniedException('You do not have access to this album.');
            } elseif ($this->userRole == 'ROLE_USER' && !in_array($viewPrivacy, ['public', 'member'])) {
                throw $this->createAccessDeniedException('You do not have access to this album.');
            } elseif ($this->userRole == 'ROLE_FRIEND' && !in_array($viewPrivacy, ['public', 'member', 'friend'])) {
                throw $this->createAccessDeniedException('You do not have access to this album.');
            } elseif ($this->userRole == 'ROLE_FAMILY' && !in_array($viewPrivacy, ['public', 'member', 'friend', 'family'])) {
                throw $this->createAccessDeniedException('You do not have access to this album.');
            } elseif ($viewPrivacy == 'public') {
                // do nothing anyone can view this album
            }
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
        $user = $this->getUser();
        if ($user) {
            $userRoles = $user ? $user->getRoles() : ['ROLE_USER'];
            $this->userRole = $userRoles[0]; // Gets the first role
            if ($user->isAdmin()) {
                $this->userRole = 'ROLE_ADMIN';
            }
        }

        $photo = $photoRepository->findOneBy(['uuid' => $uuid, 'viewPrivacy' => 'public']);

        if (!$photo) {
            throw $this->createNotFoundException('Photo not found');
        }

        // Check if user has required role
        // Check privacy settings
        $viewPrivacy = $photo->getViewPrivacy();

        if ($viewPrivacy === 'private') {
            // Only the photo owner can view private photos
            if (!$this->getUser() || $this->getUser() !== $photo->getUser() || $this->userRole !== 'ROLE_ADMIN') {
                throw $this->createAccessDeniedException('You do not have access to this photo.');
            }
        } elseif ($viewPrivacy === 'members' && !in_array($this->userRole, ['ROLE_ADMIN', 'ROLE_MEMBER', 'ROLE_FAMILY', 'ROLE_FRIEND'])) {
            // Any logged-in user can view
            if (!$this->getUser()) {
                throw $this->createAccessDeniedException('You must be logged in to view this photo.');
            }
        }  elseif ($viewPrivacy === 'friend' && !in_array($this->userRole, ['ROLE_ADMIN', 'ROLE_FAMILY', 'ROLE_FRIEND'])) {
            // Any logged-in user can view
            if (!$this->getUser()) {
                throw $this->createAccessDeniedException('You must be logged in to view this photo.');
            }
        } elseif ($viewPrivacy === 'family' && !in_array($this->userRole, ['ROLE_ADMIN', 'ROLE_FAMILY'])) {
            // Any logged-in user can view
            if (!$this->getUser()) {
                throw $this->createAccessDeniedException('You must be logged in to view this photo.');
            }
        } elseif ($viewPrivacy === 'public') {
            // do nothing anyone can view this photo
        } else {
            throw $this->createAccessDeniedException('You do not have access to this photo.');
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
