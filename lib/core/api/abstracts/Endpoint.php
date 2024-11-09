<?php

namespace PS\Core\Api\Abstracts;

use Config;
use PS\Core\Api\Request;

class Endpoint
{
    public static function getEndpointData(): ?array
    {
        $request = Request::getInstance();
        $path = implode("/", array_slice($request->segments, 3));
        $allEndpoints = require Config::BASE_PATH . "build/customEndpoints/endpoints.php";
        if (isset($allEndpoints[$path])) {
            $class = $allEndpoints[$path]['class'];
            if (in_array($request->httpMethod, array_keys($allEndpoints[$path]))) {
                $instance = new $class;
                $method = strtolower($request->httpMethod);
                if (method_exists($instance, $method)) {
                    return $instance->$method();
                }
            }
        }
        return null;
    }
}
