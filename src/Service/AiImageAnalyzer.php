<?php
/**
 * with service logger now
 */
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AiImageAnalyzer
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $aiServiceLogger,
        private string $aiApiUrl,
        private string $aiApiKey,
        private string $publicUrl
    ) {
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
                'timeout' => 30,
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
     * Analyze image using OpenAI Vision API
     */
    public function analyzeImageWithOpenAI(string $filename): array
    {
        $defaultResult = [
            'name' => null,
            'description' => null,
            'tags' => [],
        ];

        $imageUrl = rtrim($this->publicUrl, '/') . '/uploads/photos/' . $filename;
        $startTime = microtime(true);

        $this->aiServiceLogger->info('OpenAI API Request Started', [
            'provider' => 'openai',
            'model' => 'gpt-5-mini',
            'filename' => $filename,
            'image_url' => $imageUrl,
            'timestamp' => date('Y-m-d H:i:s'),
            'api key' => $this->aiApiKey
        ]);

        try {
            $requestPayload = [
                'model' => 'gpt-5-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional photo curator. Analyze images and provide: 1) A short, descriptive title (max 60 chars), 2) A detailed description (2-3 sentences), 3) Relevant tags (comma-separated, max 10 tags). Format your response as JSON with keys: title, description, tags (array).',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Please analyze this photo and provide a title, description, and relevant tags.',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageUrl,
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 500,
                'response_format' => ['type' => 'json_object'],
            ];

//            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
                'headers' => [
                    'Authorization' => 'Bearer ' . substr($this->aiApiKey, 0, 10) . '...',
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestPayload,
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray();
            $duration = round(microtime(true) - $startTime, 3);

            $content = $responseData['choices'][0]['message']['content'] ?? '{}';
            $result = json_decode($content, true);

            $finalResult = [
                'name' => $result['title'] ?? null,
                'description' => $result['description'] ?? null,
                'tags' => is_array($result['tags'] ?? null) ? $result['tags'] : [],
            ];

            $this->aiServiceLogger->info('OpenAI API Request Successful', [
                'provider' => 'openai',
                'model' => 'gpt-5-mini',
                'filename' => $filename,
                'status_code' => $statusCode,
                'duration_seconds' => $duration,
                'result' => $finalResult,
                'usage' => $responseData['usage'] ?? null,
                'response_size_bytes' => strlen(json_encode($responseData)),
            ]);

            return $finalResult;

        } catch (TransportExceptionInterface $e) {
            $duration = round(microtime(true) - $startTime, 3);

            $this->aiServiceLogger->error('OpenAI API Transport Error', [
                'provider' => 'openai',
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

            $this->aiServiceLogger->error('OpenAI API Request Failed', [
                'provider' => 'openai',
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
     * Analyze image using Google Cloud Vision API
     */
    public function analyzeImageWithGoogleVision(string $filename): array
    {
        $defaultResult = [
            'name' => null,
            'description' => null,
            'tags' => [],
        ];

        $imageUrl = rtrim($this->publicUrl, '/') . '/uploads/photos/' . $filename;
        $startTime = microtime(true);

        $this->aiServiceLogger->info('Google Vision API Request Started', [
            'provider' => 'google',
            'filename' => $filename,
            'image_url' => $imageUrl,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        try {
            $requestPayload = [
                'requests' => [
                    [
                        'image' => [
                            'source' => [
                                'imageUri' => $imageUrl,
                            ],
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
                'response_size_bytes' => strlen(json_encode($responseData)),
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
                'trace' => $e->getTraceAsString(),
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
                'trace' => $e->getTraceAsString(),
            ]);

            return $defaultResult;
        }
    }
}
