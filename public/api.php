<?php

use PS\Core\_devtools\Helper\ApiHelper;
use PS\Core\Api\Abstracts\Endpoint;
use PS\Core\Api\Request;
use PS\Core\Api\Response;
use PS\Core\Api\Session;
use PS\Core\Logging\Logging;

try {
    require '../lib/core/init.php';
    $loggedIn = false;
    $request = Request::getInstance();
    $sessionInstance = Session::getInstance($request->segments[$request->apiIndex + 2] === Request::TYPE_LOGIN);

    $loggedIn = $sessionInstance->getLoggedIn();

    if (!$loggedIn && $request->segments[$request->apiIndex + 2] !== Request::TYPE_LOGIN && $request->httpMethod !== 'OPTIONS') {
        throw new \Exception('Not logged in');
    }
    $error = null;
    $status = null;
    $data = null;
    $additionalMeta = [];
    if (count($request->segments) >= $request->apiIndex + 3) {
        switch ($request->segments[$request->apiIndex + 2]) {
            case Request::TYPE_OBJ:
                if (isset($request->segments[$request->apiIndex + 3])) {
                    $objectName = $request->segments[$request->apiIndex + 3];
                    if (isset($request->segments[$request->apiIndex + 4])) {
                        $objectID = $request->segments[$request->apiIndex + 4];
                        if ($request->httpMethod === 'GET') {
                            $data = ApiHelper::findObject($objectName, $objectID);
                        } else if ($request->httpMethod === 'PATCH') {
                            $data = ApiHelper::saveObject($objectName, $objectID);
                        }
                    } else {
                        if ($request->httpMethod === 'GET') {
                            $pageSize = ApiHelper::DEFAULT_PAGESIZE;
                            $page = 1;
                            if (isset($request->parameters['_pageSize'])) {
                                $pageSize = (int)$request->parameters['_pageSize'];
                            }
                            if (isset($request->parameters['_page'])) {
                                $page = (int)$request->parameters['_page'];
                            }
                            if ($pageSize === -1) {
                                $page = 1;
                            }
                            $additionalMeta['page'] = $page;
                            $additionalMeta['pageSize'] = $pageSize;
                            $data = ApiHelper::findObject($objectName, null, $page, $pageSize);
                            $additionalMeta['totalCount'] = count($data);
                        } else if ($request->httpMethod === 'POST') {
                            $data = ApiHelper::saveObject($objectName);
                            $status = Response::CREATED;
                        } else if ($request->httpMethod === 'OPTIONS') {
                            $peerClass = "ObjectPeer\\" . $objectName . "Peer";
                            $data = $peerClass::OPTIONS;
                            $status = Response::STATUS_OK;
                        }
                    }
                }
                break;
            case Request::TYPE_LOGIN:
                $data = $sessionInstance->login($request);
                break;
            case Request::TYPE_MOD:
            case Request::TYPE_CORE:
                $data = Endpoint::getEndpointData();
                break;
        }
        (new Response)
            ->setError($error)
            ->setData($data)
            ->setStatus($status !== null ? $status : ($data === null ? Response::NOT_FOUND : Response::STATUS_OK))
            ->getResponse($additionalMeta);
    }
} catch (\Exception $e) {
    $log = new Logging;
    $log->add(Logging::LOG_TYPE_API, $e->getMessage());
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
