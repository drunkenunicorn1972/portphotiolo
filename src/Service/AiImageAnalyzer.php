<?php
/**
 * with service logger now
 */

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use OpenAI;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\RateLimitException;

class AiImageAnalyzer
{
    private \OpenAI\Client $client;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface     $aiServiceLogger,
        private string              $aiApiUrl,
        private string              $aiApiKey,
        private string              $publicUrl
    )
    {
        $this->client = OpenAI::client($aiApiKey);
    }

    /**
     * Analyze an image using AI and return name, description, and tags
     */
    public function analyzeImage(string $filename): array
    {
        $defaultResult = [
            'name' => null,
            'description' => null,
            'tags' => [],
        ];

        $imageUrl = rtrim($this->publicUrl, '/') . '/uploads/photos/' . $filename;
        $startTime = microtime(true);

        $this->aiServiceLogger->info('AI API Request Started', [
            'provider' => 'custom',
            'filename' => $filename,
            'image_url' => $imageUrl,
            'timestamp' => date('Y-m-d H:i:s'),
            'APIkey' => $this->aiApiKey
        ]);

        try {
            $requestPayload = [
                'image_url' => $imageUrl,
                'tasks' => [
                    'generate_title',
                    'generate_description',
                    'extract_tags',
                ],
                'options' => [
                    'max_tags' => 10,
                    'description_length' => 'medium',
                ],
            ];

            $this->aiServiceLogger->debug('AI API Request Payload', [
                'url' => $this->aiApiUrl,
                'payload' => $requestPayload,
            ]);

            // Call the AI API
            $response = $this->httpClient->request('POST', $this->aiApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . substr($this->aiApiKey, 0, 10) . '...',
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestPayload,
                'timeout' => 130,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray();
            $duration = round(microtime(true) - $startTime, 3);

            if ($statusCode !== 200) {
                $this->aiServiceLogger->warning('AI API returned non-200 status', [
                    'provider' => 'custom',
                    'filename' => $filename,
                    'status_code' => $statusCode,
                    'response' => $responseData,
                    'duration_seconds' => $duration,
                ]);
                return $defaultResult;
            }

            $result = [
                'name' => $responseData['title'] ?? $responseData['name'] ?? null,
                'description' => $responseData['description'] ?? null,
                'tags' => $responseData['tags'] ?? [],
            ];

            $this->aiServiceLogger->info('AI API Request Successful', [
                'provider' => 'custom',
                'filename' => $filename,
                'status_code' => $statusCode,
                'duration_seconds' => $duration,
                'result' => $result,
                'response_size_bytes' => strlen(json_encode($responseData)),
            ]);

            return $result;

        } catch (TransportExceptionInterface $e) {
            $duration = round(microtime(true) - $startTime, 3);

            $this->aiServiceLogger->error('AI API Transport Error', [
                'provider' => 'custom',
                'filename' => $filename,
                'error_type' => 'TransportException',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'duration_seconds' => $duration,
                'trace' => $e->getTraceAsString(),
            ]);

            return $defaultResult;
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 3);

            $this->aiServiceLogger->error('AI API Request Failed', [
                'provider' => 'custom',
                'filename' => $filename,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'duration_seconds' => $duration,
                'trace' => $e->getTraceAsString(),
            ]);

            return $defaultResult;
        }
    }

    /**
     * Analyze image using OpenAI Vision API with base64 encoding
     */
    public function analyzeImageWithOpenAI(string $filename): array
    {
        $ai_model = 'gpt-4o-mini';
        $defaultResult = [
            'name' => null,
            'description' => null,
            'tags' => [],
        ];

        try {
            // Read the file and encode as base64
            $filePath = $this->getFilePath($filename);

            if (!file_exists($filePath)) {
                $this->aiServiceLogger->error('Image file not found', [
                    'filename' => $filename,
                    'expected_path' => $filePath,
                ]);
                return $defaultResult;
            }

            $imageData = file_get_contents($filePath);
            $base64Image = base64_encode($imageData);
            $mimeType = mime_content_type($filePath);

            // Create data URL with base64
            $imageUrl = "data:{$mimeType};base64,{$base64Image}";

            $this->aiServiceLogger->info('OpenAI API Request Started', [
                'provider' => 'openai',
                'model' => $ai_model,
                'filename' => $filename,
                'image_size_bytes' => strlen($imageData),
                'base64_length' => strlen($base64Image),
                'timestamp' => date('Y-m-d H:i:s'),
            ]);

            $response = $this->client->chat()->create([
                'model' => $ai_model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "You are a professional photo curator. Analyze images and provide: 1) A short, descriptive title (max 60 chars), 2) A detailed description (2-3 sentences), 3) Relevant tags (comma-separated, max 10 tags). Format your response as JSON with keys: title, description, tags (array)."
                            ]
//                            [
//                                'type' => 'image_url',
//                                'image_url' => [
//                                    'url' => $imageUrl,
//                                    'detail' => 'low' // Use 'low' for faster/cheaper processing, 'high' for better quality
//                                ]
//                            ]
                        ],
                    ],
                ],
            ]);

            // Process response
            $output = $response['choices'][0]['message']['content'] ?? '';

            $this->aiServiceLogger->info('OpenAI Response Received', [
                'provider' => 'openai',
                'response_length' => strlen($output),
            ]);

            // Clean JSON markers
            $output = str_replace('```json', '', $output);
            $output = str_replace('```', '', $output);
            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->aiServiceLogger->error('OpenAI JSON Decode Failed', [
                    'provider' => 'openai',
                    'filename' => $filename,
                    'json_error_code' => json_last_error(),
                    'json_error_message' => json_last_error_msg(),
                    'output_preview' => substr($output, 0, 500),
                ]);

                $data = ['raw_output' => $output];
            }

            $this->aiServiceLogger->info('OpenAI API Request Success', [
                'provider' => 'openai',
                'filename' => $filename,
                'data' => $data,
            ]);

            $finalResult = [
                'name' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'tags' => is_array($data['tags'] ?? null) ? $data['tags'] : [],
            ];
            return $finalResult;

        } catch (RateLimitException $e) {
            $this->aiServiceLogger->error('OpenAI API Request Failed: Rate limit reached', [
                'provider' => 'openai',
                'filename' => $filename,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            return $defaultResult;
        } catch (ErrorException $e) {
            $this->aiServiceLogger->error('OpenAI API Request Failed: General Error', [
                'provider' => 'openai',
                'filename' => $filename,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            return $defaultResult;
        } catch (\Throwable $e) {
            $this->aiServiceLogger->error('OpenAI API Request Failed: Unexpected error', [
                'provider' => 'openai',
                'filename' => $filename,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            return $defaultResult;
        }
    }

    /**
     * Helper method to get the full file path
     */
    private function getFilePath(string $filename): string
    {
        // Try var/uploads/photos first (new secure location)
        $securePath = dirname(__DIR__, 2) . '/var/uploads/photos/' . $filename;
        if (file_exists($securePath)) {
            return $securePath;
        }

        // Fallback to public folder for backwards compatibility
        $publicPath = dirname(__DIR__, 2) . '/public/uploads/photos/' . $filename;
        return $publicPath;
    }

    /**
     * Analyze image using Google Cloud Vision API with base64
     */
    public function analyzeImageWithGoogleVision(string $filename): array
    {
        $defaultResult = [
            'name' => null,
            'description' => null,
            'tags' => [],
        ];

        $startTime = microtime(true);

        $this->aiServiceLogger->info('Google Vision API Request Started', [
            'provider' => 'google',
            'filename' => $filename,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        try {
            // Read the file and encode as base64
            $filePath = $this->getFilePath($filename);

            if (!file_exists($filePath)) {
                $this->aiServiceLogger->error('Image file not found', [
                    'filename' => $filename,
                    'expected_path' => $filePath,
                ]);
                return $defaultResult;
            }

            $imageData = file_get_contents($filePath);
            $base64Image = base64_encode($imageData);

            $requestPayload = [
                'requests' => [
                    [
                        'image' => [
                            'content' => $base64Image, // Use base64 content instead of URL
                        ],
                        'features' => [
                            ['type' => 'LABEL_DETECTION', 'maxResults' => 10],
                            ['type' => 'LANDMARK_DETECTION', 'maxResults' => 5],
                            ['type' => 'OBJECT_LOCALIZATION', 'maxResults' => 10],
                        ],
                    ],
                ],
            ];

            $response = $this->httpClient->request('POST',
                'https://vision.googleapis.com/v1/images:annotate?key=' . $this->aiApiKey,
                [
                    'json' => $requestPayload,
                    'timeout' => 30,
                ]
            );

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray();
            $duration = round(microtime(true) - $startTime, 3);

            $annotations = $responseData['responses'][0] ?? [];

            // Extract labels as tags
            $tags = [];
            if (isset($annotations['labelAnnotations'])) {
                foreach ($annotations['labelAnnotations'] as $label) {
                    if ($label['score'] > 0.7) {
                        $tags[] = strtolower($label['description']);
                    }
                }
            }

            // Generate name from top labels
            $name = null;
            if (!empty($tags)) {
                $name = ucfirst($tags[0]);
                if (isset($tags[1])) {
                    $name .= ' ' . ucfirst($tags[1]);
                }
            }

            // Generate description
            $description = null;
            if (!empty($tags)) {
                $description = 'An image featuring ' . implode(', ', array_slice($tags, 0, 3)) . '.';
            }

            $result = [
                'name' => $name,
                'description' => $description,
                'tags' => array_slice($tags, 0, 10),
            ];

            $this->aiServiceLogger->info('Google Vision API Request Successful', [
                'provider' => 'google',
                'filename' => $filename,
                'status_code' => $statusCode,
                'duration_seconds' => $duration,
                'result' => $result,
                'labels_count' => count($annotations['labelAnnotations'] ?? []),
            ]);

            return $result;

        } catch (TransportExceptionInterface $e) {
            $duration = round(microtime(true) - $startTime, 3);

            $this->aiServiceLogger->error('Google Vision API Transport Error', [
                'provider' => 'google',
                'filename' => $filename,
                'error_type' => 'TransportException',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'duration_seconds' => $duration,
            ]);

            return $defaultResult;
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 3);

            $this->aiServiceLogger->error('Google Vision API Request Failed', [
                'provider' => 'google',
                'filename' => $filename,
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'duration_seconds' => $duration,
            ]);

            return $defaultResult;
        }
    }

}
