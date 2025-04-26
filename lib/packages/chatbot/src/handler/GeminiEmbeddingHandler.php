<?php

namespace PS\Package\Chatbot\Handler;

use PS\Core\Helper\Env;

/**
 * Handles embedding requests to the Gemini AI API.
 */
class GeminiEmbeddingHandler
{
    private string $apiKey;
    private string $embeddingModel;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public const GEMINI_EMBEDDING_MODEL = 'embedding-001';

    /**
     * GeminiEmbeddingHandler constructor.
     */
    public function __construct(string $model = self::GEMINI_EMBEDDING_MODEL)
    {
        $this->apiKey = Env::get("GEMINI_KEY");
        $this->embeddingModel = $model;
    }

    /**
     * Embeds a given text and returns the vector.
     *
     * @param string $text
     * @return array|null
     */
    public function embedText(string $text): ?array
    {
        $url = $this->baseUrl . $this->embeddingModel . ":embedContent?key=" . $this->apiKey;

        $postData = [
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ]
        ];

        $response = $this->makeRequest($url, $postData);
        return self::parseResponse($response);
    }

    /**
     * Makes a single HTTP POST request.
     *
     * @param string $url
     * @param array $postData
     * @return string
     */
    private function makeRequest(string $url, array $postData): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response ?: '';
    }

    /**
     * Parses the JSON response for the embedding.
     *
     * @param mixed $response
     * @return array|null
     */
    private static function parseResponse($response): ?array
    {
        if (!$response) {
            return null;
        }

        $encoded = json_decode($response, true);

        if ($encoded === null) {
            return null;
        }

        if (isset($encoded["error"])) {
            throw new \Exception($encoded["error"]["message"]);
        }

        if (isset($encoded['embedding']['values'])) {
            return $encoded['embedding']['values'];
        }

        return null;
    }
}
