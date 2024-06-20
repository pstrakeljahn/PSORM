<?php

$requestUri = $_SERVER['REQUEST_URI'];
$httpMethod = $_SERVER['REQUEST_METHOD'];
$urlParts = parse_url($requestUri);
$path = trim($urlParts['path'], '/');

$segments = explode('/', $path);

$apiIndex = array_search('api', $segments);

if ($apiIndex !== false && count($segments) >= $apiIndex + 3) {
    $version = $segments[$apiIndex + 1];

    switch ($segments[$apiIndex + 2]) {
        case 'obj':
            if (isset($segments[$apiIndex + 3])) {
                $objectName = $segments[$apiIndex + 3];

                if (isset($segments[$apiIndex + 4])) {
                    $objectID = $segments[$apiIndex + 4];
                }
            }
            echo $httpMethod . " // " . $version . " // " . $objectName . " // " . $objectID;
            break;

        default:
            echo "missing!";
            break;
    }
}
