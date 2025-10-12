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

            // Process bulk upload
            $results = $bulkPhotoUploader->uploadPhotosToAlbum(
                $uploadedFiles,
                $album,
                $this->getUser()
            );

            // Show results
            if ($results['success'] > 0) {
                $this->addFlash('success', sprintf(
                    'Successfully uploaded %d photo(s) to "%s"',
                    $results['success'],
                    $album->getName()
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
