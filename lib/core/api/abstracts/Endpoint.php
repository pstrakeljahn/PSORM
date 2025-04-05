<?php

namespace PS\Core\Api\Abstracts;

use Config;
use PS\Core\Api\Request;

/**
 * Class Endpoint
 *
 * Handles dynamic routing of custom API endpoints based on request path and method.
 */
class Endpoint
{
    /**
     * Dynamically resolves and executes a custom endpoint handler based on the request.
     *
     * @return array|null The result of the endpoint handler or null if not found.
     */
    public static function getEndpointData(): ?array
    {
        $request = Request::getInstance();

        // Build the path after /api/{version}/{type}
        $customPath = implode("/", array_slice($request->segments, 3));

        // Load all configured endpoints
        $allEndpoints = require Config::BASE_PATH . "build/customEndpoints/endpoints.php";

        // Check if path exists in the config
        if (!isset($allEndpoints[$customPath])) {
            return null;
        }

        $endpointConfig = $allEndpoints[$customPath];
        $className = $endpointConfig['class'] ?? null;

        // Validate class
        if (!$className || !class_exists($className)) {
            return null;
        }

        $method = strtolower($request->httpMethod);

        // Check if method is allowed in config and implemented in class
        if (!array_key_exists($request->httpMethod, $endpointConfig)) {
            return null;
        }

        $instance = new $className();

        if (!method_exists($instance, $method)) {
            return null;
        }

        return $instance->$method();
    }
}
