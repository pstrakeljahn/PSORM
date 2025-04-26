<?php

namespace PS\Core\Ai;

use PS\Core\Helper\Env;

class GeminiHandler
{
    private $apiKey;
    private $model;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public const GEMINI_1_5_PRO = 'gemini-1.5-pro';
    public const GEMINI_1_5_FLASH = 'gemini-1.5-flash';
    public const GEMINI_2_0_PRO = 'gemini-2.0-pro';
    public const GEMINI_2_0_FLASH = 'gemini-2.0-flash';
    public const GEMINI_2_0_FLASH_LITE = 'gemini-2.0-flash-lite';
    public const GEMINI_2_5_PRO = 'gemini-2.5-pro';
    public const GEMINI_2_5_FLASH = 'gemini-2.5-flash';

    public function __construct(string $model = self::GEMINI_2_0_FLASH_LITE)
    {
        $this->apiKey = Env::get("GEMINI_KEY");
        $this->model = $model;
    }

    public function generateContent(string $prompt): ?array
    {
        $url = $this->baseUrl . $this->model . ":generateContent?key=" . $this->apiKey;

        $postData = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        $response = $this->makeRequest($url, $postData);
        return self::parseResponse($response);
    }

    public function streamGenerateContent(string $prompt, callable $onChunk): void
    {
        $url = $this->baseUrl . $this->model . ":streamGenerateContent?key=" . $this->apiKey;

        $postData = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        $this->makeStreamingRequest($url, $postData, $onChunk);
    }

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
        return $response;
    }

    private function makeStreamingRequest(string $url, array $postData, callable $onChunk): void
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use ($onChunk) {
            $response = self::parseResponse($chunk);
            if ($response !== null) {
                $onChunk($response);
            }
            return strlen($chunk);
        });

        curl_exec($ch);

        curl_close($ch);
    }

    private static function parseResponse($response): ?array
    {
        if ($response) {
            $response = ltrim($response, '[');
            $response = ltrim($response, ',');
            $response = rtrim($response, ']');
            $encoded = json_decode($response, true);
            if ($encoded === null) return null;
            if (isset($encoded["error"])) {
                throw new \Exception($encoded["error"]["message"]);
            } else {
                $returnArray = [
                    "message" => '',
                    "model" => $encoded['modelVersion'],
                    "inProgress" => true
                ];
                foreach ($encoded["candidates"] as $candidate) {
                    if (isset($candidate['finishReason'])) {
                        $returnArray["inProgress"] = $candidate['finishReason'] !== "STOP";
                    }
                    foreach ($candidate["content"]["parts"] as $part) {
                        $returnArray["message"] .= $part["text"];
                    }
                }
                return $returnArray;
            }
        }
        return null;
    }

    public function streamGenerateContentWithData(array $postData, callable $onChunk): void
    {
        $url = $this->baseUrl . $this->model . ":streamGenerateContent?key=" . $this->apiKey;
        $this->makeStreamingRequest($url, $postData, $onChunk);
    }
}
