<?php

namespace PS\Core\Api;

use PS\Core\Helper\Env;

/**
 * Class Response
 *
 * Handles building and sending standardized JSON API responses, including status codes,
 * errors, debug info, and proper CORS headers.
 */
class Response
{
    public const STATUS_OK         = 200;
    public const CREATED           = 201;
    public const BAD_REQUEST       = 400;
    public const UNAUTHORIZED      = 401;
    public const NOT_FOUND         = 404;
    public const SERVER_ERROR      = 500;

    public const ALLOWED_STATUS_CODES = [
        self::STATUS_OK,
        self::CREATED,
        self::BAD_REQUEST,
        self::UNAUTHORIZED,
        self::NOT_FOUND,
        self::SERVER_ERROR
    ];

    public const ALLOWED_METHODS = ['OPTIONS', 'GET', 'POST', 'PUT', 'DELETE'];

    /** @var int HTTP response status code */
    private int $statusCode = self::STATUS_OK;

    /** @var array The response payload */
    private array $data = [];

    /** @var array List of error messages */
    private array $error = [];

    /** @var array Optional debug information */
    private array $debug = [];

    /**
     * Sends the JSON response with all meta, data, and optional debug info.
     *
     * @param array $additionalMeta Additional metadata to include under `meta`.
     * @return string The JSON-encoded response.
     * @throws \Exception
     */
    public function getResponse(array $additionalMeta = []): string
    {
        $this->setHeaders();

        $response = [
            'meta' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'status' => $this->statusCode,
                ...$additionalMeta
            ],
            'data' => $this->data,
            'error' => $this->error,
        ];

        if (Env::get("DEBUG")) {
            $response['debug'] = $this->debug;
        }

        $json = json_encode($response, Env::get("DEBUG") ? JSON_PRETTY_PRINT : 0);
        echo $json;

        return $json;
    }

    /**
     * Sets the HTTP status code for the response.
     *
     * @param int $statusCode
     * @return $this
     * @throws \Exception If status code is not allowed.
     */
    public function setStatus(int $statusCode): self
    {
        if (!in_array($statusCode, self::ALLOWED_STATUS_CODES, true)) {
            throw new \Exception('Status code not allowed');
        }

        http_response_code($statusCode);
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Sets the response data payload.
     *
     * @param mixed $data Can be an array or scalar value.
     * @return $this
     */
    public function setData(mixed $data): self
    {
        if ($data !== null) {
            $this->data = is_array($data) ? $data : [$data];
        }
        return $this;
    }

    /**
     * Sets the error section of the response.
     *
     * @param mixed $error Can be an array or string.
     * @return $this
     */
    public function setError(mixed $error): self
    {
        if ($error !== null) {
            $this->error = is_array($error) ? $error : [$error];
        }
        return $this;
    }

    /**
     * Sets optional debug information, only shown if DEBUG is enabled.
     *
     * @param mixed $debug Can be an array or scalar value.
     * @return $this
     */
    public function setDebug(mixed $debug): self
    {
        $this->debug = is_array($debug) ? $debug : [$debug];
        return $this;
    }

    /**
     * Sets all required HTTP headers for CORS and JSON output.
     *
     * @return void
     */
    private function setHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: ' . implode(', ', self::ALLOWED_METHODS));
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Credentials: true');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
        }
    }
}
