<?php

use PS\Core\_devtools\Helper\ApiHelper;
use PS\Core\Api\Request;
use PS\Core\Api\Response;
use PS\Core\Api\Session;

try {
    require '../lib/core/init.php';
    $loggedIn = false;
    $request = Request::getInstance();
    $sessionInstance = Session::getInstance($request->segments[$request->apiIndex + 2] === Request::TYPE_LOGIN);

    $loggedIn = $sessionInstance->getLoggedIn();

    if (!$loggedIn && $request->segments[$request->apiIndex + 2] !== Request::TYPE_LOGIN) {
        throw new \Exception('Not logged in');
    }
    $error = null;
    if (count($request->segments) >= $request->apiIndex + 3) {
        switch ($request->segments[$request->apiIndex + 2]) {
            case Request::TYPE_OBJ:
                $additionalMeta = [];
                if (isset($request->segments[$request->apiIndex + 3])) {
                    $objectName = $request->segments[$request->apiIndex + 3];
                    if (isset($request->segments[$request->apiIndex + 4])) {
                        $objectID = $request->segments[$request->apiIndex + 4];
                        $data = ApiHelper::findObject($objectName, $objectID);
                    } else {
                        $data = ApiHelper::findObject($objectName);
                        $additionalMeta['count'] = count($data);
                    }
                }
                break;
            case Request::TYPE_LOGIN:
                $data = $sessionInstance->login($request);
                break;
        }
        (new Response)
            ->setError($error)
            ->setData($data)
            ->setStatus($data === null ? Response::NOT_FOUND : Response::STATUS_OK)
            ->getResponse($additionalMeta);
    }
} catch (\Exception $e) {
    (new Response)
        ->setError($e->getMessage())
        ->setStatus($loggedIn ? Response::SERVER_ERROR : Response::UNAUTHORIZED)
        ->setDebug($loggedIn ? [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace()
        ] : [])
        ->getResponse();
}
