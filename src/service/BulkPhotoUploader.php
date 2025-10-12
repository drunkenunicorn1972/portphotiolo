<?php

namespace App\Service;

use App\Entity\Album;
use App\Entity\Photo;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class BulkPhotoUploader
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExifExtractor $exifExtractor,
        private AiImageAnalyzer $aiImageAnalyzer,
        private ImageResizer $imageResizer,
        private TagRepository $tagRepository,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        private string $uploadDirectory,
        private string $aiProvider = 'openai'
    ) {
    }

    /**
     * Upload multiple photos to an album
     * Returns array with success count and any errors
     */
    public function uploadPhotosToAlbum(
        array $uploadedFiles,
        Album $album,
        UserInterface $user
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'photos' => [],
        ];

        foreach ($uploadedFiles as $index => $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                $results['failed']++;
                $results['errors'][] = "File #{$index}: Invalid file upload";
                continue;
            }

            try {
                $photo = $this->processPhoto($uploadedFile, $album, $user);
                $results['photos'][] = $photo;
                $results['success']++;

                $this->logger->info('Bulk upload: Photo processed successfully', [
                    'filename' => $photo->getFilename(),
                    'album' => $album->getName(),
                ]);
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    "File '%s': %s",
                    $uploadedFile->getClientOriginalName(),
                    $e->getMessage()
                );

                $this->logger->error('Bulk upload: Failed to process photo', [
                    'filename' => $uploadedFile->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update album photo count
        $album->updatePhotoCount();
        $this->entityManager->flush();

        return $results;
    }

    /**
     * Process a single photo: upload, resize, extract EXIF, analyze with AI
     */
    private function processPhoto(
        UploadedFile $uploadedFile,
        Album $album,
        UserInterface $user
    ): Photo {
        // Generate unique filename
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

        // Move the file to the upload directory
        $uploadedFile->move($this->uploadDirectory, $newFilename);
        $filePath = $this->uploadDirectory . '/' . $newFilename;

        // Create Photo entity
        $photo = new Photo();
        $photo->setUser($user);
        $photo->setFilename($newFilename);
        $photo->setViewPrivacy('public'); // Default to public

        // 1. Generate image sizes
        try {
            $generatedSizes = $this->imageResizer->generateAllSizes($filePath, $this->uploadDirectory);

            if (isset($generatedSizes[ImageResizer::SIZE_THUMBNAIL])) {
                $photo->setFilenameThumbnail($generatedSizes[ImageResizer::SIZE_THUMBNAIL]);
            }
            if (isset($generatedSizes[ImageResizer::SIZE_TABLET])) {
                $photo->setFilenameTablet($generatedSizes[ImageResizer::SIZE_TABLET]);
            }
            if (isset($generatedSizes[ImageResizer::SIZE_DESKTOP])) {
                $photo->setFilenameDesktop($generatedSizes[ImageResizer::SIZE_DESKTOP]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to generate image sizes', [
                'filename' => $newFilename,
                'error' => $e->getMessage(),
            ]);
        }

        // 2. Extract EXIF data
        try {
            $exifData = $this->exifExtractor->extractExifData($filePath);
            $this->applyExifData($photo, $exifData);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to extract EXIF data', [
                'filename' => $newFilename,
                'error' => $e->getMessage(),
            ]);
        }

        // 3. Analyze with AI
        try {
            $aiData = match($this->aiProvider) {
                'openai' => $this->aiImageAnalyzer->analyzeImageWithOpenAI($newFilename),
                'google' => $this->aiImageAnalyzer->analyzeImageWithGoogleVision($newFilename),
                default => $this->aiImageAnalyzer->analyzeImage($newFilename),
            };

            // Apply AI-generated data
            if (!empty($aiData['name'])) {
                $photo->setName($aiData['name']);
            } else {
                // Fallback to filename if AI doesn't provide a name
                $photo->setName($originalFilename);
            }

            if (!empty($aiData['description'])) {
                $photo->setDescription($aiData['description']);
            }

            // Process tags
            if (!empty($aiData['tags'])) {
                $this->processTags($photo, $aiData['tags']);
            }
        } catch (\Exception $e) {
            // If AI fails, use filename as name
            $photo->setName($originalFilename);

            $this->logger->warning('Failed to analyze image with AI', [
                'filename' => $newFilename,
                'error' => $e->getMessage(),
            ]);
        }

        // Add photo to album
        $album->addPhoto($photo);
        $photo->addAlbum($album);

        // Persist the photo
        $this->entityManager->persist($photo);

        return $photo;
    }

    /**
     * Apply EXIF data to photo entity
     */
    private function applyExifData(Photo $photo, array $exifData): void
    {
        if ($exifData['device']) {
            $photo->setDevice($exifData['device']);
        }
        if ($exifData['copyright']) {
            $photo->setCopyright($exifData['copyright']);
        }
        if ($exifData['latitude']) {
            $photo->setLatitude($exifData['latitude']);
        }
        if ($exifData['longitude']) {
            $photo->setLongitude($exifData['longitude']);
        }
        if ($exifData['aperture']) {
            $photo->setAperture($exifData['aperture']);
        }
        if ($exifData['focalLength']) {
            $photo->setFocalLength($exifData['focalLength']);
        }
        if ($exifData['exposureTime']) {
            $photo->setExposureTime($exifData['exposureTime']);
        }
        if ($exifData['iso']) {
            $photo->setIso($exifData['iso']);
        }
        $photo->setFlash($exifData['flash']);

        if ($exifData['createdAt']) {
            $photo->setCreatedAt($exifData['createdAt']);
        }
    }

    /**
     * Process and create tags for a photo
     */
    private function processTags(Photo $photo, array $tagNames): void
    {
        foreach ($tagNames as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) {
                continue;
            }

            // Find or create tag
            $tag = $this->tagRepository->findOneBy(['name' => $tagName]);
            if (!$tag) {
                $tag = new Tag();
                $tag->setName($tagName);
                $this->entityManager->persist($tag);
            }

            // Add tag to photo if not already present
            if (!$photo->getTags()->contains($tag)) {
                $photo->addTag($tag);
            }
        }
    }
}
