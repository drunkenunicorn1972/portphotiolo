<?php

namespace App\Service;

class ExifExtractor
{
    /**
     * Extract EXIF data from an image file
     */
    public function extractExifData(string $filePath): array
    {
        $data = [
            'device' => null,
            'copyright' => null,
            'latitude' => null,
            'longitude' => null,
            'location' => null,
            'aperture' => null,
            'focalLength' => null,
            'exposureTime' => null,
            'iso' => null,
            'flash' => false,
            'createdAt' => null,
        ];

        if (!file_exists($filePath)) {
            return $data;
        }

        // Check if file is an image
        $imageInfo = @getimagesize($filePath);
        if ($imageInfo === false) {
            return $data;
        }

        // Read EXIF data
        $exif = @exif_read_data($filePath, 0, true);
        if ($exif === false) {
            return $data;
        }

        // Extract camera/device information
        if (isset($exif['IFD0']['Make']) && isset($exif['IFD0']['Model'])) {
            $data['device'] = trim($exif['IFD0']['Make'] . ' ' . $exif['IFD0']['Model']);
        } elseif (isset($exif['IFD0']['Model'])) {
            $data['device'] = $exif['IFD0']['Model'];
        }

        // Extract copyright
        if (isset($exif['IFD0']['Copyright'])) {
            $data['copyright'] = $exif['IFD0']['Copyright'];
        } elseif (isset($exif['IFD0']['Artist'])) {
            $data['copyright'] = '© ' . $exif['IFD0']['Artist'];
        }

        // Extract GPS coordinates
        if (isset($exif['GPS'])) {
            $gpsData = $this->extractGpsData($exif['GPS']);
            $data['latitude'] = $gpsData['latitude'];
            $data['longitude'] = $gpsData['longitude'];
        }

        // Extract aperture (F-stop)
        if (isset($exif['EXIF']['FNumber'])) {
            $aperture = $this->convertToDecimal($exif['EXIF']['FNumber']);
            $data['aperture'] = $aperture ? (string) round($aperture, 1) : null;
        } elseif (isset($exif['EXIF']['ApertureValue'])) {
            $aperture = $this->convertToDecimal($exif['EXIF']['ApertureValue']);
            if ($aperture) {
                // ApertureValue is in APEX, convert to f-number
                $fNumber = pow(2, $aperture / 2);
                $data['aperture'] = (string) round($fNumber, 1);
            }
        }

        // Extract focal length
        if (isset($exif['EXIF']['FocalLength'])) {
            $focalLength = $this->convertToDecimal($exif['EXIF']['FocalLength']);
            $data['focalLength'] = $focalLength ? (string) round($focalLength, 1) : null;
        }

        // Extract exposure time (shutter speed)
        if (isset($exif['EXIF']['ExposureTime'])) {
            $data['exposureTime'] = $this->formatExposureTime($exif['EXIF']['ExposureTime']);
        }

        // Extract ISO
        if (isset($exif['EXIF']['ISOSpeedRatings'])) {
            $data['iso'] = is_array($exif['EXIF']['ISOSpeedRatings'])
                ? (int) $exif['EXIF']['ISOSpeedRatings'][0]
                : (int) $exif['EXIF']['ISOSpeedRatings'];
        }

        // Extract flash information
        if (isset($exif['EXIF']['Flash'])) {
            // Flash value is a bitmask, bit 0 indicates if flash fired
            $data['flash'] = (bool) ($exif['EXIF']['Flash'] & 1);
        }

        // Extract date taken
        if (isset($exif['EXIF']['DateTimeOriginal'])) {
            try {
                $data['createdAt'] = new \DateTimeImmutable($exif['EXIF']['DateTimeOriginal']);
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        } elseif (isset($exif['IFD0']['DateTime'])) {
            try {
                $data['createdAt'] = new \DateTimeImmutable($exif['IFD0']['DateTime']);
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        }

        return $data;
    }

    /**
     * Extract GPS coordinates from EXIF GPS data
     */
    private function extractGpsData(array $gps): array
    {
        $latitude = null;
        $longitude = null;

        if (isset($gps['GPSLatitude'], $gps['GPSLatitudeRef'])) {
            $latitude = $this->convertGpsCoordinate($gps['GPSLatitude'], $gps['GPSLatitudeRef']);
        }

        if (isset($gps['GPSLongitude'], $gps['GPSLongitudeRef'])) {
            $longitude = $this->convertGpsCoordinate($gps['GPSLongitude'], $gps['GPSLongitudeRef']);
        }

        return [
            'latitude' => $latitude ? (string) $latitude : null,
            'longitude' => $longitude ? (string) $longitude : null,
        ];
    }

    /**
     * Convert GPS coordinate from degrees/minutes/seconds to decimal
     */
    private function convertGpsCoordinate(array $coordinate, string $hemisphere): ?float
    {
        if (count($coordinate) !== 3) {
            return null;
        }

        $degrees = $this->convertToDecimal($coordinate[0]);
        $minutes = $this->convertToDecimal($coordinate[1]);
        $seconds = $this->convertToDecimal($coordinate[2]);

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        // Make negative if South or West
        if (in_array($hemisphere, ['S', 'W'])) {
            $decimal *= -1;
        }

        return round($decimal, 8);
    }

    /**
     * Convert EXIF fraction to decimal
     */
    private function convertToDecimal($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value) && strpos($value, '/') !== false) {
            $parts = explode('/', $value);
            if (count($parts) === 2 && $parts[1] != 0) {
                return (float) $parts[0] / (float) $parts[1];
            }
        }

        return null;
    }

    /**
     * Format exposure time for display
     */
    private function formatExposureTime(string $exposureTime): string
    {
        $decimal = $this->convertToDecimal($exposureTime);

        if ($decimal === null) {
            return $exposureTime;
        }

        // If exposure is 1 second or more, show as decimal
        if ($decimal >= 1) {
            return round($decimal, 1) . 's';
        }

        // If exposure is less than 1 second, show as fraction
        if (strpos($exposureTime, '/') !== false) {
            return $exposureTime . 's';
        }

        // Convert decimal to fraction
        $denominator = round(1 / $decimal);
        return '1/' . $denominator . 's';
    }
}
