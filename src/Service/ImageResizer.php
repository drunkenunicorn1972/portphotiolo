<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class ImageResizer
{
    public const SIZE_THUMBNAIL = 'thumb';
    public const SIZE_TABLET = 'tablet';
    public const SIZE_DESKTOP = 'desktop';
    public const SIZE_ORIGINAL = 'original';

    private const SIZES = [
        self::SIZE_THUMBNAIL => ['width' => 300, 'height' => 300],
        self::SIZE_TABLET => ['width' => 768, 'height' => 768],
        self::SIZE_DESKTOP => ['width' => 1920, 'height' => 1920],
    ];

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Generate all image sizes from the original
     * Returns array with paths to generated images
     */
    public function generateAllSizes(string $originalPath, string $outputDir): array
    {
        if (!file_exists($originalPath)) {
            throw new \RuntimeException("Original image not found: {$originalPath}");
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $pathInfo = pathinfo($originalPath);
        $baseFilename = $pathInfo['filename'];
        $extension = strtolower($pathInfo['extension']);

        $generatedPaths = [
            self::SIZE_ORIGINAL => basename($originalPath),
        ];

        // Load original image
        $sourceImage = $this->loadImage($originalPath, $extension);
        if (!$sourceImage) {
            $this->logger->error('Failed to load image', ['path' => $originalPath]);
            return $generatedPaths;
        }

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        // Generate each size
        foreach (self::SIZES as $sizeName => $dimensions) {
            try {
                $resizedImage = $this->resizeImage(
                    $sourceImage,
                    $originalWidth,
                    $originalHeight,
                    $dimensions['width'],
                    $dimensions['height']
                );

                $filename = $baseFilename . '_' . $sizeName . '.' . $extension;
                $outputPath = $outputDir . '/' . $filename;

                $this->saveImage($resizedImage, $outputPath, $extension);
                imagedestroy($resizedImage);

                $generatedPaths[$sizeName] = $filename;

                $this->logger->info('Generated image size', [
                    'size' => $sizeName,
                    'path' => $filename,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to generate image size', [
                    'size' => $sizeName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        imagedestroy($sourceImage);

        return $generatedPaths;
    }

    /**
     * Load image from file based on extension
     */
    private function loadImage(string $path, string $extension): ?\GdImage
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'gif' => @imagecreatefromgif($path),
            'webp' => @imagecreatefromwebp($path),
            default => null,
        };
    }

    /**
     * Resize image maintaining aspect ratio
     */
    private function resizeImage(
        \GdImage $sourceImage,
        int $originalWidth,
        int $originalHeight,
        int $maxWidth,
        int $maxHeight
    ): \GdImage {
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);

        // Don't upscale images
        if ($ratio > 1) {
            $ratio = 1;
        }

        $newWidth = (int) round($originalWidth * $ratio);
        $newHeight = (int) round($originalHeight * $ratio);

        // Create new image with transparency support
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);

        // Resize with high quality
        imagecopyresampled(
            $resizedImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        return $resizedImage;
    }

    /**
     * Save image to file based on extension
     */
    private function saveImage(\GdImage $image, string $path, string $extension, int $quality = 90): bool
    {
        return match (strtolower($extension)) {
            'jpg', 'jpeg' => imagejpeg($image, $path, $quality),
            'png' => imagepng($image, $path, (int) (9 - ($quality / 10))),
            'gif' => imagegif($image, $path),
            'webp' => imagewebp($image, $path, $quality),
            default => false,
        };
    }

    /**
     * Delete all generated sizes for a photo
     */
    public function deleteAllSizes(string $baseDir, array $filenames): void
    {
        foreach ($filenames as $filename) {
            if ($filename) {
                $path = $baseDir . '/' . $filename;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }

    /**
     * Get the appropriate image size based on viewport
     */
    public static function getSizeForViewport(string $viewport): string
    {
        return match ($viewport) {
            'mobile', 'thumbnail' => self::SIZE_THUMBNAIL,
            'tablet' => self::SIZE_TABLET,
            'desktop' => self::SIZE_DESKTOP,
            default => self::SIZE_ORIGINAL,
        };
    }
}
