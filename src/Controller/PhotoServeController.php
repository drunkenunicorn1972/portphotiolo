<?php

namespace App\Controller;

use App\Repository\PhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class PhotoServeController extends AbstractController
{
    private string $userRole;

    public function __construct(
        private readonly string $uploadDirectory
    ) {
        $this->userRole = 'ROLE_USER';
    }

    #[Route('/photo/serve/{uuid}', name: 'app_photo_serve', requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    #[Route('/photo/serve/{uuid}/{size}', name: 'app_photo_serve_size', requirements: ['uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function servePhoto(
        string $uuid,
        PhotoRepository $photoRepository,
        string $size = 'desktop'
    ): Response {
        // Convert string UUID to Uuid object
        try {
            $uuidObject = Uuid::fromString($uuid);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException('Invalid UUID format');
        }

        $user = $this->getUser();
        if ($user) {
            $userRoles = $user ? $user->getRoles() : ['ROLE_USER'];
            $this->userRole = $userRoles[0]; // Gets the first role
            if ($user->isAdmin()) {
                $this->userRole = 'ROLE_ADMIN';
            }
        }

        $photo = $photoRepository->findOneBy(['uuid' => $uuidObject]);

        if (!$photo) {
            throw $this->createNotFoundException('Photo not found');
        }

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

        // Determine which file to serve based on size
        $filename = match($size) {
            'thumbnail' => $photo->getFilenameThumbnail() ?? $photo->getFilename(),
            'tablet' => $photo->getFilenameTablet() ?? $photo->getFilename(),
            'desktop' => $photo->getFilenameDesktop() ?? $photo->getFilename(),
            default => $photo->getFilename(),
        };

        $filePath = $this->uploadDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Photo file not found');
        }

        // Serve the file
        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', mime_content_type($filePath));

        return $response;
    }
}
