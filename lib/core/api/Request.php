<?php

namespace PS\Core\Api;

use Config;

/**
 * Class Request
 *
 * Handles parsing and validation of the incoming HTTP request.
 */
class Request
{
    public const TYPE_LOGIN   = 'login';
    public const TYPE_LOGOUT  = 'logout';
    public const TYPE_REFRESH = 'refresh';
    public const TYPE_OBJ     = 'obj';
    public const TYPE_CORE    = 'core';
    public const TYPE_MOD     = 'mod';

    public const ALLOWED_TYPES = [
        self::TYPE_OBJ,
        self::TYPE_LOGIN,
        self::TYPE_LOGOUT,
        self::TYPE_REFRESH,
        self::TYPE_MOD,
        self::TYPE_CORE
    ];

    /** @var string Full request URI */
    public readonly string $requestUri;

    /** @var string HTTP method (GET, POST, etc.) */
    public readonly string $httpMethod;

    /** @var string|null Origin header if present */
    public readonly ?string $origin;

    /** @var string|null Request type (e.g. obj, login) */
    public readonly ?string $requestType;

    /** @var string API version (e.g. v1) */
    public readonly string $apiVersion;

    /** @var array Exploded URL segments */
    public readonly array $segments;

    /** @var int Position of "api" in the URI path */
    public readonly int $apiIndex;

    /** @var array Parsed request parameters (GET/POST/PATCH) */
    public readonly array $parameters;

    /** @var mixed Raw body input (e.g. JSON string) */
    public readonly mixed $file;

    /**
     * Request constructor.
     *
     * Parses URI, validates origin, request type and extracts parameters.
     *
     * @throws \Exception If request is malformed or disallowed.
     */
    public function __construct()
    {
        $this->requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $this->httpMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        $this->origin     = $_SERVER['HTTP_ORIGIN'] ?? null;

        if ($this->origin !== null && !in_array($this->origin, Config::ALLOWED_ORIGINS)) {
            throw new \Exception('Origin is not allowed');
        }

        if ($this->requestUri !== "") {
            $this->segments = $this->parseSegments($this->requestUri);
            $this->apiIndex = $this->locateApiIndex($this->segments);
            $this->apiVersion = $this->segments[$this->apiIndex + 1] ?? '';
            $this->requestType = $this->detectRequestType($this->segments[$this->apiIndex + 2] ?? '');
            $this->parameters = $this->extractParameters($this->httpMethod);
            $this->file = file_get_contents('php://input');
        } else {
            $this->segments = [];
            $this->apiIndex = 0;
            $this->apiVersion = "";
            $this->requestType = null;
            $this->parameters = [];
            $this->file = null;
        }
    }

    /**
     * Static factory for instantiating a Request object.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * Parses the URI into clean segments.
     *
     * @param string $uri
     * @return array
     */
    private function parseSegments(string $uri): array
    {
        $urlParts = parse_url($uri);
        $path = trim($urlParts['path'] ?? '', '/');
        return explode('/', $path);
    }

    /**
     * Locates the "api" keyword in URI segments.
     *
     * @param array $segments
     * @return int
     * @throws \Exception
     */
    private function locateApiIndex(array $segments): int
    {
        $index = array_search('api', $segments);
        if ($index === false || $index === 0) {
            throw new \Exception('Invalid API request: missing or misplaced "api" segment.');
        }
        return $index;
    }

    /**
     * Validates and returns the request type segment.
     *
     * @param string $type
     * @return string
     * @throws \Exception
     */
    private function detectRequestType(string $type): string
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new \Exception('Request type is not allowed');
        }
        return $type;
    }

    /**
     * Extracts request parameters based on HTTP method.
     *
     * @param string $method
     * @return array
     */
    private function extractParameters(string $method): array
    {
        return match ($method) {
            'GET'  => $_GET,
            'POST' => $_POST,
            'PATCH' => $this->parseRawInput(),
            default => []
        };
    }

    /**
     * Parses raw input stream (e.g. PATCH payload).
     *
     * @return array
     */
    private function parseRawInput(): array
    {
        $raw = file_get_contents('php://input');
        parse_str($raw, $parsed);
        return $parsed;
    }
}
