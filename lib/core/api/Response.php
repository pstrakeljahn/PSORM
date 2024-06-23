<?php

namespace PS\Core\Api;

use Config;

class Response
{
    const STATUS_OK = 200;
    const SERVER_ERROR = 500;

    const ARR_STATUSCODE = [self::STATUS_OK, self::SERVER_ERROR];
    const ALLOWED_METHODES = ["OPTIONS", "GET", "POST", "PUT", "DELETE"];

    private int $statusCode = self::STATUS_OK;
    private array $data = [];
    private array $error = [];
    private array $debug = [];

    public final function getResponse(): string
    {
        $this->setHeader();
        $arrResponse = [
            "meta" => [
                "method" => $_SERVER['REQUEST_METHOD'],
                "status" => $this->statusCode,
            ],
            "data" => $this->data,
            "error" => $this->error,
        ];

        if (Config::DEBUG) {
            $arrResponse = [...$arrResponse, "debug" => $this->debug];
        }

        $jsonString = json_encode($arrResponse, Config::DEBUG ? JSON_PRETTY_PRINT : 0);
        echo $jsonString;

        return $jsonString;
    }

    public final function setStatus(int $statusCode): self
    {
        if (!in_array($statusCode, self::ARR_STATUSCODE)) {
            throw new \Exception("Status Code not allowed");
        }
        http_response_code($statusCode);
        $this->statusCode = $statusCode;
        return $this;
    }

    public final function setData($data): self
    {
        if (!is_null($data)) {
            if (!is_array($data)) {
                $data = [$data];
            }
            $this->data = $data;
        }
        return $this;
    }

    public final function setError($error): self
    {
        if (!is_null($error)) {
            if (!is_array($error)) {
                $error = [$error];
            }
            $this->error = $error;
        }
        return $this;
    }

    public final function setDebug($debug): self
    {
        if (!is_array($debug)) {
            $debug = [$debug];
        }
        $this->debug = $debug;
        return $this;
    }

    private function setHeader(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: ' . implode(", ", self::ALLOWED_METHODES));
    }
}
