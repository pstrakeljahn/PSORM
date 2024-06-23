<?php

use PS\Core\Api\Response;

try{
    $requestUri = $_SERVER['REQUEST_URI'];
    $httpMethod = $_SERVER['REQUEST_METHOD'];
    $urlParts = parse_url($requestUri);
    $path = trim($urlParts['path'], '/');
    
    $segments = explode('/', $path);
    
    $apiIndex = array_search('api', $segments);

    require "../lib/core/init.php";
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    if(!is_null($origin) && !in_array($_SERVER['HTTP_ORIGIN'], Config::ALLOWED_ORIGINS)) {
        throw new \Exception("Origin is not allowed");
    }

    $error = null;
    $data = null;
    
    if ($apiIndex !== false && count($segments) >= $apiIndex + 3) {
        $version = $segments[$apiIndex + 1];
    
        switch ($segments[$apiIndex + 2]) {
            case 'obj':
                if (isset($segments[$apiIndex + 3])) {
                    $objectName = $segments[$apiIndex + 3];
    
                    if (isset($segments[$apiIndex + 4])) {
                        $objectID = $segments[$apiIndex + 4];
                        $data = ["ID" => $objectID];
                    }
                }
                break;
    
            default:
                echo "missing!";
                break;
        }
        (new Response)->setError($error)->setData($data)->getResponse();
}

} catch(\Exception $e) {
    (new Response)
        ->setError($e->getMessage())
        ->setStatus(Response::SERVER_ERROR)
        ->setDebug([
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ])
        ->getResponse();
}
