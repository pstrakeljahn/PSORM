<?php

namespace PS\Core\Helper;

use PS\Core\Api\Response;

class WebSocketServerResponse
{
    private array $response;

    public function __construct()
    {
        $this->response = [
            "status" => Response::STATUS_OK,
            "data" => [],
            "error" => null
        ];
    }

    public function setStatus(int $statusCode): self
    {
        $this->response["status"] = $statusCode;
        return $this;
    }

    public function setData(array $data): self
    {
        $this->response["data"] = $data;
        return $this;
    }

    public function setError(string $errorMessage): self
    {
        $this->response["error"] = $errorMessage;
        return $this;
    }

    public function getResponse()
    {
        return json_encode($this->response);
    }
}
