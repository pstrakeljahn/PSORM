<?php

namespace PS\Core\Api\Abstracts;

class EndpointDto
{
    public string $url;
    // Not implemented yet
    public bool $needsAuthorization = true;
    public array $allowedMethodes = [];
    public array $requiredParamsByMethod = [];
}
