<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AiImageAnalyzer
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
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

        try {
            // Construct the full public URL to the image
            $imageUrl = rtrim($this->publicUrl, '/') . '/uploads/photos/' . $filename;

            // Call the AI API
            $response = $this->httpClient->request('POST', $this->aiApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->aiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'image_url' => $imageUrl,
                    'tasks' => [
                        'generate_title',
                        'generate_description',
                        'extract_tags',
                    ],
                    'options' => [
                        'max_tags' => 10,
                        'description_length' => 'medium', // short, medium, long
                    ],
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                $this->logger->warning('AI API returned non-200 status', [
                    'status' => $statusCode,
                    'image' => $filename,
                ]);
                return $defaultResult;
            }

            $data = $response->toArray();

            return [
                'name' => $data['title'] ?? $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'tags' => $data['tags'] ?? [],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to analyze image with AI', [
                'error' => $e->getMessage(),
                'image' => $filename,
            ]);

            return $defaultResult;
        }
    }

    /**
     * Analyze image using OpenAI Vision API (alternative implementation)
     */
    public function analyzeImageWithOpenAI(string $filename): array
    {
        $defaultResult = [
            'name' => null,
            'description' => null,
            'tags' => [],
        ];

        try {
            $imageUrl = rtrim($this->publicUrl, '/') . '/uploads/photos/' . $filename;

            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->aiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o',
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
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '{}';
            $result = json_decode($content, true);

            return [
                'name' => $result['title'] ?? null,
                'description' => $result['description'] ?? null,
                'tags' => is_array($result['tags'] ?? null) ? $result['tags'] : [],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to analyze image with OpenAI', [
                'error' => $e->getMessage(),
                'image' => $filename,
            ]);

            return $defaultResult;
        }
    }

    /**
     * Analyze image using Google Cloud Vision API (alternative implementation)
     */
    public function analyzeImageWithGoogleVision(string $filename): array
    {
        $defaultResult = [
            'name' => null,
            'description' => null,
            'tags' => [],
        ];

        try {
            $imageUrl = rtrim($this->publicUrl, '/') . '/uploads/photos/' . $filename;

            $response = $this->httpClient->request('POST',
                'https://vision.googleapis.com/v1/images:annotate?key=' . $this->aiApiKey,
                [
                    'json' => [
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
                    ],
                    'timeout' => 30,
                ]
            );

            $data = $response->toArray();
            $annotations = $data['responses'][0] ?? [];

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

            return [
                'name' => $name,
                'description' => $description,
                'tags' => array_slice($tags, 0, 10),
            ];

        } catch (\Exception $e) {
            $this->logger->error('Failed to analyze image with Google Vision', [
                'error' => $e->getMessage(),
                'image' => $filename,
            ]);

            return $defaultResult;
        }
    }
}
