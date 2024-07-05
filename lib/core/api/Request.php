<?php

namespace PS\Core\Api;

use Config;

class Request
{
    const TYPE_OBJ = 'obj';
    const TYPE_LOGIN = 'login';
    const ALLOWED_TYPES = [self::TYPE_OBJ, self::TYPE_LOGIN];

    public readonly string $requestUri;
    public readonly string $httpMethod;
    public readonly ?string $origin;
    public readonly string $requestType;
    public readonly string $apiVersion;
    public readonly array $segments;
    public readonly string|int $apiIndex;
    public readonly array $parameters;
    public readonly mixed $file;

    public function __construct()
    {
        $this->requestUri = $_SERVER['REQUEST_URI'];
        $this->httpMethod = $_SERVER['REQUEST_METHOD'];
        $this->origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        if (!is_null($this->origin) && !in_array($this->origin, Config::ALLOWED_ORIGINS)) {
            throw new \Exception('Origin is not allowed');
        }
        $this->getRequestType();
        $this->setParameters();
    }

    private function getRequestType(): void
    {
        $urlParts = parse_url($this->requestUri);
        $path = trim($urlParts['path'], '/');
        $this->segments = explode('/', $path);
        $this->apiIndex = array_search('api', $this->segments);
        if ($this->apiIndex === false) {
            throw new \Exception('Access API!');
        }
        $requestType = $this->segments[$this->apiIndex + 2];
        $this->apiVersion = $this->segments[$this->apiIndex + 1];
        if (!in_array($requestType, self::ALLOWED_TYPES)) {
            throw new \Exception('Request Type is not allowed');
        }
        $this->requestType = $requestType;
    }

    private function setParameters(): void
    {
        $parameters = [];
        if ($this->httpMethod === 'GET') {
            $parameters = [...$_GET];
        }
        if ($this->httpMethod === 'POST') {
            $parameters = [...$_POST];
        }
        $this->parameters = $parameters;
        $this->file = file_get_contents('php://input');
    }

    public static function getInstance(): Request
    {
        return new self;
    }
}
