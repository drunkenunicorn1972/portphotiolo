<?php

namespace App\Controller\Admin;

use App\Form\BulkPhotoUploadType;
use App\Repository\AlbumRepository;
use App\Service\BulkPhotoUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BulkPhotoUploadController extends AbstractController
{
    #[Route('/members/photos/bulk-upload', name: 'admin_bulk_photo_upload')]
    public function bulkUpload(
        Request $request,
        BulkPhotoUploader $bulkPhotoUploader,
        AlbumRepository $albumRepository
    ): Response {
        $form = $this->createForm(BulkPhotoUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $album = $form->get('album')->getData();
            $uploadedFiles = $form->get('photos')->getData();
            $viewPrivacy = $form->get('viewPrivacy')->getData();
            $requiredRole = $form->get('requiredRole')->getData();

            // Process bulk upload with privacy settings
            $results = $bulkPhotoUploader->uploadPhotosToAlbum(
                $uploadedFiles,
                $album,
                $this->getUser(),
                $viewPrivacy,
                $requiredRole
            );

            // Show results
            if ($results['success'] > 0) {
                $privacyLabel = match($viewPrivacy) {
                    'public' => 'Public',
                    'friend' => 'Friends',
                    'family' => 'Family',
                    'private' => 'Private',
                    default => 'Private',
                };

                $this->addFlash('success', sprintf(
                    'Successfully uploaded %d photo(s) to "%s" with %s privacy',
                    $results['success'],
                    $album->getName(),
                    $privacyLabel
                ));
            }

            if ($results['failed'] > 0) {
                foreach ($results['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
            }

            // Redirect to photo list filtered by the album
            return $this->redirectToRoute('members', [
                'crudAction' => 'index',
                'crudControllerFqcn' => PhotoCrudController::class,
                'filters' => ['albums' => $album->getId()],
            ]);
        }

        return $this->render('admin/bulk_upload.html.twig', [
            'form' => $form,
        ]);
    }
}
